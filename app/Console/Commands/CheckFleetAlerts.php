<?php

namespace App\Console\Commands;

use App\Domain\Fleet\Services\AlertService;
use App\Domain\Notifications\Notifications\AlertNotification;
use App\Domain\Tenancy\Models\Structure;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Console\Command;

/**
 * Equivalent of the legacy "visite technique expire -> notification" example
 * from the proposal — meant to run on a schedule (see routes/console.php),
 * one tenant at a time since AlertService relies on the tenant scope.
 */
class CheckFleetAlerts extends Command
{
    protected $signature = 'fleet:check-alerts';

    protected $description = 'Notify each structure\'s admins about vehicles with a technical inspection or insurance expiring soon';

    public function handle(AlertService $alerts): int
    {
        Structure::query()->each(function (Structure $structure) use ($alerts) {
            TenantContext::set($structure);

            $vehicles = $alerts->expiringSoon();

            if ($vehicles->isEmpty()) {
                TenantContext::clear();

                return;
            }

            $admins = User::role('admin')->where('structure_id', $structure->id)->get();

            foreach ($vehicles as $vehicle) {
                $notification = new AlertNotification(
                    title: 'Document véhicule bientôt expiré',
                    message: "Le véhicule {$vehicle->plate} a un contrôle technique ou une assurance qui expire sous 30 jours",
                    link: route('fleet.show', $vehicle, absolute: false),
                );

                $admins->each(fn (User $admin) => $admin->notify($notification));
            }

            TenantContext::clear();
        });

        $this->info('Fleet alerts checked.');

        return self::SUCCESS;
    }
}
