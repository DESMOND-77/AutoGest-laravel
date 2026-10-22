<?php

namespace App\Console\Commands;

use App\Domain\Scheduling\Enums\PresenceStatus;
use App\Domain\Scheduling\Enums\SessionType;
use App\Domain\Scheduling\Exceptions\SchedulingConflict;
use App\Domain\Scheduling\Models\LessonSession;
use App\Domain\Scheduling\Services\ConflictRule;
use App\Domain\Scheduling\Services\SchedulingService;
use App\Domain\Students\Models\Student;
use App\Domain\Tenancy\Models\Structure;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * Imports the legacy weekly schedule grids (etp*.csv = practical sessions,
 * ett*.csv = theoretical) that the legacy app's own setup/import.php
 * explicitly left unhandled - its docblock calls them "grilles
 * hebdomadaires complexes à parser... Non géré par ce script" (see
 * docs/audit/legacy-feature-parity.md). This is a new import, not a port
 * of an existing one.
 *
 * Format, from inspecting the real files in autoecole_jh/data/: row 4 is a
 * header carrying the week as free text ("JOURS( du 09 au 14 Mars 2026)"),
 * row 5 names the day columns (Lundi..Samedi), and every row from 6 on is
 * one hour slot with a student-surname cell, six per-day presence-status
 * cells, and a moniteur-first-name cell.
 *
 * The catch: several real rows pack MULTIPLE whitespace-joined names into
 * a single cell - a slot shared by more than one student, or a moniteur
 * who changes across the week - with no delimiter and no reliable
 * positional pairing back to the day columns. Rather than guess a split,
 * any cell with more than one name token makes the whole row unimportable,
 * and it's skipped and reported rather than silently mis-imported. Follow
 * the workflow the project brief asks for: run with --dry-run first,
 * review the report, then run for real. Dry-run only checks for conflicts
 * against sessions already in the database - it does not simulate
 * conflicts between two rows of the same import run.
 */
class ImportLegacyPlanning extends Command
{
    protected $signature = 'import:legacy-planning
        {structure : Structure (tenant) id to import into}
        {path : Directory containing etp*.csv/ett*.csv files}
        {--dry-run : Report what would happen without writing anything}';

    protected $description = 'Import the legacy etp*/ett* weekly planning grids into LessonSessions for one tenant';

    private const DAY_COLUMNS = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'];

    private const STATUS_MAP = [
        'présente' => PresenceStatus::Present,
        'presente' => PresenceStatus::Present,
        'absente' => PresenceStatus::Absent,
        'annulé' => PresenceStatus::Cancelled,
        'annule' => PresenceStatus::Cancelled,
    ];

    private const MONTHS = [
        'janvier' => 1, 'février' => 2, 'fevrier' => 2, 'mars' => 3, 'avril' => 4,
        'mai' => 5, 'juin' => 6, 'juillet' => 7, 'août' => 8, 'aout' => 8,
        'septembre' => 9, 'octobre' => 10, 'novembre' => 11, 'décembre' => 12, 'decembre' => 12,
    ];

    private int $imported = 0;

    private int $skipped = 0;

    /** @var array<int, string> */
    private array $skipReasons = [];

    public function handle(SchedulingService $scheduling, ConflictRule $conflictRule): int
    {
        $structure = Structure::query()->find((int) $this->argument('structure'));

        if (! $structure) {
            $this->error('Structure not found.');

            return self::FAILURE;
        }

        $directory = rtrim((string) $this->argument('path'), '/');
        $files = collect(glob("{$directory}/*.csv") ?: [])
            ->filter(fn (string $file) => preg_match('/^(etp|ett)/i', basename($file)) === 1)
            ->sort()
            ->values();

        if ($files->isEmpty()) {
            $this->error("No etp*.csv/ett*.csv files found in {$directory}.");

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');

        TenantContext::set($structure);

        try {
            foreach ($files as $file) {
                $this->importFile($file, $scheduling, $conflictRule, $dryRun);
            }
        } finally {
            TenantContext::clear();
        }

        $this->newLine();
        $this->table(
            [$dryRun ? 'Séances qui seraient importées' : 'Séances importées', 'Lignes ignorées'],
            [[$this->imported, $this->skipped]],
        );

        if ($this->skipReasons !== []) {
            $this->newLine();
            $this->line($dryRun ? 'Détail de ce qui serait ignoré :' : 'Détail des lignes ignorées :');

            foreach ($this->skipReasons as $reason) {
                $this->line("  - {$reason}");
            }
        }

        return self::SUCCESS;
    }

    private function importFile(string $path, SchedulingService $scheduling, ConflictRule $conflictRule, bool $dryRun): void
    {
        $filename = basename($path);
        $type = str_starts_with(mb_strtolower($filename), 'etp') ? SessionType::Practical : SessionType::Theoretical;

        $rows = $this->readCsv($path);

        if (count($rows) < 6) {
            $this->skip("{$filename}: fichier trop court, format inattendu.");

            return;
        }

        $weekStart = $this->parseWeekStart($rows[3][2] ?? '');

        if (! $weekStart) {
            $header = $rows[3][2] ?? '';
            $this->skip("{$filename}: semaine introuvable dans l'en-tête (\"{$header}\").");

            return;
        }

        $dayNameRow = array_map(fn ($cell) => trim($cell), array_slice($rows[4] ?? [], 2, 6));

        if (array_map('mb_strtolower', $dayNameRow) !== array_map('mb_strtolower', self::DAY_COLUMNS)) {
            $this->skip("{$filename}: colonnes des jours inattendues (\"".implode(';', $dayNameRow).'"), fichier non importé.');

            return;
        }

        $dayDates = [];
        foreach (self::DAY_COLUMNS as $i => $dayName) {
            $dayDates[$dayName] = $weekStart->copy()->addDays($i);
        }

        for ($i = 5; $i < count($rows); $i++) {
            $this->importRow($rows[$i], $filename, $dayDates, $type, $scheduling, $conflictRule, $dryRun);
        }
    }

    /**
     * @param  array<int, string>  $row
     * @param  array<string, Carbon>  $dayDates
     */
    private function importRow(
        array $row,
        string $filename,
        array $dayDates,
        SessionType $type,
        SchedulingService $scheduling,
        ConflictRule $conflictRule,
        bool $dryRun,
    ): void {
        $hourCell = trim($row[0] ?? '');

        if (! preg_match('/^(\d{1,2})h(\d{2})-(\d{1,2})h(\d{2})$/', $hourCell, $m)) {
            return; // title, blank, or footer row - not an hour slot
        }

        $startsAt = sprintf('%02d:%02d', (int) $m[1], (int) $m[2]);
        $endsAt = sprintf('%02d:%02d', (int) $m[3], (int) $m[4]);

        $studentCell = trim($row[1] ?? '');

        if ($studentCell === '' || str_starts_with(mb_strtoupper($studentCell, 'UTF-8'), 'FERMET')) {
            return; // empty slot, or the "closed" footer disguised as a row
        }

        $studentTokens = preg_split('/\s+/u', $studentCell, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        if (count($studentTokens) !== 1) {
            $this->skip("{$filename} {$hourCell}: plusieurs élèves dans la même cellule (\"{$studentCell}\"), ligne non importée.");

            return;
        }

        $student = $this->matchStudent($studentTokens[0]);

        if ($student === null) {
            $this->skip("{$filename} {$hourCell}: élève introuvable ou ambigu pour \"{$studentCell}\", ligne non importée.");

            return;
        }

        $instructorCell = trim($row[8] ?? '');
        $instructorName = $this->singleInstructorName($instructorCell);

        if ($instructorName === null) {
            $this->skip("{$filename} {$hourCell} ({$studentCell}): plusieurs moniteurs sur la ligne (\"{$instructorCell}\"), ligne non importée.");

            return;
        }

        $instructor = $this->matchInstructor($instructorName);

        if ($instructor === null) {
            $this->skip("{$filename} {$hourCell} ({$studentCell}): moniteur introuvable ou ambigu pour \"{$instructorCell}\", ligne non importée.");

            return;
        }

        foreach (self::DAY_COLUMNS as $dayIndex => $dayName) {
            $statusCell = trim($row[2 + $dayIndex] ?? '');

            if ($statusCell === '') {
                continue;
            }

            $statusTokens = preg_split('/\s+/u', $statusCell, -1, PREG_SPLIT_NO_EMPTY) ?: [];

            if (count($statusTokens) !== 1) {
                $this->skip("{$filename} {$hourCell} {$dayName} ({$studentCell}): statut ambigu (\"{$statusCell}\"), jour non importé.");

                continue;
            }

            $normalizedStatus = mb_strtolower($statusTokens[0], 'UTF-8');

            if ($normalizedStatus === 'nul') {
                continue; // "Nul" = not applicable that day, not an error
            }

            $presence = self::STATUS_MAP[$normalizedStatus] ?? null;

            if ($presence === null) {
                $this->skip("{$filename} {$hourCell} {$dayName} ({$studentCell}): statut inconnu (\"{$statusCell}\").");

                continue;
            }

            $this->importSession(
                $scheduling,
                $conflictRule,
                $dryRun,
                label: "{$filename} {$hourCell} {$dayName} ({$studentCell})",
                student: $student,
                instructor: $instructor,
                type: $type,
                date: $dayDates[$dayName],
                startsAt: $startsAt,
                endsAt: $endsAt,
                presence: $presence,
            );
        }
    }

    private function importSession(
        SchedulingService $scheduling,
        ConflictRule $conflictRule,
        bool $dryRun,
        string $label,
        Student $student,
        User $instructor,
        SessionType $type,
        Carbon $date,
        string $startsAt,
        string $endsAt,
        PresenceStatus $presence,
    ): void {
        // A plain conflict check isn't enough to make re-running the import
        // a no-op: ConflictRule deliberately excludes cancelled sessions
        // (a cancelled slot is meant to be free for a new, different
        // booking), so a row imported as "Annulé" would otherwise pass the
        // conflict check again on a second run and be duplicated.
        $alreadyImported = LessonSession::query()
            ->where('student_id', $student->id)
            ->where('instructor_id', $instructor->id)
            ->where('scheduled_date', $date->toDateString())
            ->where('starts_at', $startsAt)
            ->where('ends_at', $endsAt)
            ->exists();

        if ($alreadyImported) {
            $this->skip("{$label}: déjà importée.");

            return;
        }

        if ($dryRun) {
            if ($conflictRule->hasConflict($instructor->id, $date->toDateString(), $startsAt, $endsAt)) {
                $this->skip("{$label}: créneau déjà occupé pour ce moniteur.");

                return;
            }

            $this->imported++;

            return;
        }

        try {
            $session = $scheduling->schedule([
                'student_id' => $student->id,
                'instructor_id' => $instructor->id,
                'vehicle_id' => null,
                'type' => $type->value,
                'scheduled_date' => $date->toDateString(),
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
            ]);

            if ($presence !== PresenceStatus::Planned) {
                $scheduling->markPresence($session, $presence);
            }

            $this->imported++;
        } catch (SchedulingConflict $e) {
            $this->skip("{$label}: {$e->getMessage()}");
        }
    }

    private function matchStudent(string $token): ?Student
    {
        $matches = Student::query()->where('last_name', 'like', "%{$token}%")->limit(2)->get();

        return $matches->count() === 1 ? $matches->first() : null;
    }

    private function matchInstructor(string $name): ?User
    {
        $matches = User::role('moniteur')->where('name', 'like', "%{$name}%")->limit(2)->get();

        return $matches->count() === 1 ? $matches->first() : null;
    }

    /**
     * A single moniteur cell reads "M <first name>" (Monsieur) or "Mme
     * <first name>" - one title token plus exactly one name token. Two or
     * more such pairs in the same cell ("M Junior            M Cédric")
     * means the slot has more than one moniteur that week with no reliable
     * way to tell which day goes with which, so it's treated as ambiguous
     * rather than guessed. Returns null for anything but exactly one name.
     */
    private function singleInstructorName(string $cell): ?string
    {
        $tokens = preg_split('/\s+/u', $cell, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        if ($tokens !== [] && preg_match('/^(m|mme)\.?$/ui', $tokens[0]) === 1) {
            array_shift($tokens);
        }

        return count($tokens) === 1 ? $tokens[0] : null;
    }

    private function parseWeekStart(string $header): ?Carbon
    {
        if (! preg_match('/du\s+(\d{1,2})\s+au\s+(\d{1,2})\s+(\p{L}+)\s+(\d{4})/ui', $header, $m)) {
            return null;
        }

        [, $startDay, $endDay, $monthName, $year] = $m;
        $month = self::MONTHS[mb_strtolower($monthName, 'UTF-8')] ?? null;

        if ($month === null) {
            return null;
        }

        try {
            $endDate = Carbon::create((int) $year, $month, (int) $endDay);
        } catch (\Exception) {
            return null;
        }

        if (! $endDate) {
            return null;
        }

        // The header only names the end date's month, so a range crossing a
        // month boundary ("du 30 au 04 Avril 2026") never states the start
        // month explicitly - walk backwards from the known end date until
        // the day-of-month matches, which is arithmetic, not a guess.
        $startDate = $endDate->copy();

        for ($i = 0; $i < 10 && (int) $startDate->format('j') !== (int) $startDay; $i++) {
            $startDate->subDay();
        }

        if ((int) $startDate->format('j') !== (int) $startDay) {
            return null;
        }

        return $startDate->startOfDay();
    }

    private function skip(string $reason): void
    {
        $this->skipped++;
        $this->skipReasons[] = $reason;
    }

    /**
     * @return array<int, array<int, string>>
     */
    private function readCsv(string $path): array
    {
        $rows = [];
        $contents = file_get_contents($path);

        if ($contents === false) {
            return [];
        }

        // The legacy exports are ISO-8859-1 (Windows/French locale), not
        // UTF-8 - decode before parsing so accented statuses/months match.
        if (! mb_check_encoding($contents, 'UTF-8')) {
            $contents = mb_convert_encoding($contents, 'UTF-8', 'ISO-8859-1');
        }

        $handle = fopen('php://temp', 'r+');
        fwrite($handle, $contents);
        rewind($handle);

        while (($row = fgetcsv($handle, 0, ';')) !== false) {
            $rows[] = array_map(fn ($value) => trim((string) $value), $row);
        }

        fclose($handle);

        return $rows;
    }
}
