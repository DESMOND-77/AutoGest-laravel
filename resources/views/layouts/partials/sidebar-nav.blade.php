@php
    $user = auth()->user();
    $onDashboard = request()->routeIs('dashboard')
        || request()->routeIs('admin.dashboard')
        || request()->routeIs('moniteur.dashboard')
        || request()->routeIs('eleve.dashboard');

    // Number of students with at least one dossier document still awaiting
    // review - shown as a badge on "Dossiers en attente" so an admin sees
    // at a glance whether the queue needs attention.
    $pendingDossierStudentCount = $user?->hasRole('admin')
        ? \App\Domain\Documents\Models\Document::query()
            ->where('is_current', true)
            ->where('review_status', \App\Domain\Documents\Enums\DocumentReviewStatus::Pending)
            ->whereNotNull('required_document_type_id')
            ->pluck('documentable_id')
            ->unique()
            ->count()
        : 0;
@endphp

<nav class="flex-1 overflow-y-auto overflow-x-hidden px-3 py-4 space-y-6" aria-label="Navigation principale">
    <a
        href="{{ route('dashboard') }}"
        @class([
            'group relative flex items-center gap-3 rounded-ui-md px-3 py-2.5 text-sm font-medium transition',
            'bg-primary text-primary-content shadow-soft-sm' => $onDashboard,
            'text-content-secondary hover:bg-surface-elevated hover:text-content' => ! $onDashboard,
        ])
        :class="collapsed && 'justify-center'"
    >
        <x-icon name="home" class="w-5 h-5 shrink-0" />
        <span x-show="!collapsed" x-cloak>Tableau de bord</span>
        <span x-show="collapsed" x-cloak class="pointer-events-none absolute left-full ml-2 z-20 hidden whitespace-nowrap rounded-ui-sm bg-content px-2 py-1 text-xs font-medium text-background group-hover:block">
            Tableau de bord
        </span>
    </a>

    @can('viewAny', \App\Domain\Students\Models\Student::class)
        <div>
            <p x-show="!collapsed" x-cloak class="px-3 text-[11px] font-semibold uppercase tracking-wider text-content-muted mb-1">Gestion</p>
            <div class="space-y-1">
                <x-sidebar-link :href="route('students.index')" :active="request()->routeIs('students.*')" icon="users">Élèves</x-sidebar-link>
                @if ($user?->hasRole('admin'))
                    <x-sidebar-link :href="route('dossiers.index')" :active="request()->routeIs('dossiers.*')" icon="clipboard-list" :badge="$pendingDossierStudentCount ?: null">Dossiers en attente</x-sidebar-link>
                    <x-sidebar-link :href="route('recyclage.index')" :active="request()->routeIs('recyclage.*')" icon="clock">Recyclage &amp; Tests</x-sidebar-link>
                @endif
                @can('viewAny', \App\Domain\Instructors\Models\Instructor::class)
                    <x-sidebar-link :href="route('instructors.index')" :active="request()->routeIs('instructors.*')" icon="academic-cap">Moniteurs</x-sidebar-link>
                @endcan
            </div>
        </div>
    @endcan

    @if ($user?->hasRole('admin'))
        <div>
            <p x-show="!collapsed" x-cloak class="px-3 text-[11px] font-semibold uppercase tracking-wider text-content-muted mb-1">Formation</p>
            <div class="space-y-1">
                <x-sidebar-link :href="route('scheduling.index')" :active="request()->routeIs('scheduling.index')" icon="calendar">Planning</x-sidebar-link>
                <x-sidebar-link :href="route('training.skills.index')" :active="request()->routeIs('training.skills.*')" icon="star">Compétences</x-sidebar-link>
                <x-sidebar-link :href="route('training.exams.index')" :active="request()->routeIs('training.exams.*')" icon="document-check">Examens</x-sidebar-link>
            </div>
        </div>
    @elseif ($user?->hasRole('moniteur'))
        <div>
            <p x-show="!collapsed" x-cloak class="px-3 text-[11px] font-semibold uppercase tracking-wider text-content-muted mb-1">Formation</p>
            <div class="space-y-1">
                <x-sidebar-link :href="route('moniteur.agenda')" :active="request()->routeIs('moniteur.agenda')" icon="calendar">Mon agenda</x-sidebar-link>
            </div>
        </div>
    @elseif ($user?->hasRole('eleve'))
        <div>
            <p x-show="!collapsed" x-cloak class="px-3 text-[11px] font-semibold uppercase tracking-wider text-content-muted mb-1">Formation</p>
            <div class="space-y-1">
                <x-sidebar-link :href="route('eleve.planning')" :active="request()->routeIs('eleve.planning')" icon="calendar">Mon planning</x-sidebar-link>
                <x-sidebar-link :href="route('eleve.progression')" :active="request()->routeIs('eleve.progression')" icon="academic-cap">Ma progression</x-sidebar-link>
                <x-sidebar-link :href="route('eleve.paiements')" :active="request()->routeIs('eleve.paiements')" icon="currency">Mes paiements</x-sidebar-link>
                <x-sidebar-link :href="route('eleve.dossier.show')" :active="request()->routeIs('eleve.dossier.*')" icon="clipboard-list">Mon dossier</x-sidebar-link>
                <x-sidebar-link :href="route('quiz.play')" :active="request()->routeIs('quiz.*')" icon="star">Entraînement au code</x-sidebar-link>
            </div>
        </div>
    @endif

    @can('viewAny', \App\Domain\Finance\Models\Invoice::class)
        <div>
            <p x-show="!collapsed" x-cloak class="px-3 text-[11px] font-semibold uppercase tracking-wider text-content-muted mb-1">Finances</p>
            <div class="space-y-1">
                <x-sidebar-link :href="route('finance.invoices.index')" :active="request()->routeIs('finance.invoices.*')" icon="receipt">Factures</x-sidebar-link>
                <x-sidebar-link :href="route('finance.packages.index')" :active="request()->routeIs('finance.packages.*')" icon="archive-box">Forfaits</x-sidebar-link>
                <x-sidebar-link :href="route('finance.ledger.index')" :active="request()->routeIs('finance.ledger.*')" icon="book-open">Journal</x-sidebar-link>
            </div>
        </div>
    @endcan

    @if ($user?->hasRole('admin'))
        <div>
            <p x-show="!collapsed" x-cloak class="px-3 text-[11px] font-semibold uppercase tracking-wider text-content-muted mb-1">Flotte</p>
            <div class="space-y-1">
                <x-sidebar-link :href="route('fleet.index')" :active="request()->routeIs('fleet.*')" icon="truck">Véhicules</x-sidebar-link>
            </div>
        </div>

        <div>
            <p x-show="!collapsed" x-cloak class="px-3 text-[11px] font-semibold uppercase tracking-wider text-content-muted mb-1">Relation client</p>
            <div class="space-y-1">
                <x-sidebar-link :href="route('crm.leads.index')" :active="request()->routeIs('crm.*')" icon="user-plus">Prospects</x-sidebar-link>
            </div>
        </div>

        <div>
            <p x-show="!collapsed" x-cloak class="px-3 text-[11px] font-semibold uppercase tracking-wider text-content-muted mb-1">Boutique</p>
            <div class="space-y-1">
                <x-sidebar-link :href="route('store.index')" :active="request()->routeIs('store.*')" icon="shopping-bag">Boutique</x-sidebar-link>
            </div>
        </div>
    @endif

    @if ($user?->hasAnyRole(['admin', 'superadmin']))
        <div>
            <p x-show="!collapsed" x-cloak class="px-3 text-[11px] font-semibold uppercase tracking-wider text-content-muted mb-1">Administration</p>
            <div class="space-y-1">
                @if ($user?->hasRole('admin'))
                    <x-sidebar-link :href="route('settings.show')" :active="request()->routeIs('settings.show')" icon="cog">Paramètres</x-sidebar-link>
                    <x-sidebar-link :href="route('settings.student-registration.show')" :active="request()->routeIs('settings.student-registration.*')" icon="user-plus">Inscription publique</x-sidebar-link>
                    <x-sidebar-link :href="route('settings.document-types.index')" :active="request()->routeIs('settings.document-types.*')" icon="clipboard-list">Pièces requises</x-sidebar-link>
                    <x-sidebar-link :href="route('settings.users.index')" :active="request()->routeIs('settings.users.*')" icon="users">Comptes utilisateurs</x-sidebar-link>
                @endif
                <x-sidebar-link :href="route('audit.index')" :active="request()->routeIs('audit.*')" icon="shield-check">Audit</x-sidebar-link>
                @if ($user?->hasRole('superadmin'))
                    <x-sidebar-link :href="route('superadmin.structures.index')" :active="request()->routeIs('superadmin.*')" icon="building-office">Établissements</x-sidebar-link>
                @endif
            </div>
        </div>
    @endif
</nav>
