<?php

namespace App\Listeners;

use App\Domain\Notifications\Notifications\NewStructureRegisteredNotification;
use App\Domain\Tenancy\Events\StructureRegistered;
use App\Models\User;

/**
 * The registering visitor is a guest (no TenantContext active), and
 * superadmin accounts have structure_id = null, so User::role('superadmin')
 * here naturally searches across every tenant with no special-casing —
 * same reasoning as StudentRegistrationLinkService::validate()'s public
 * token lookup.
 */
class NotifyPlatformAdminsOnStructureRegistration
{
    public function handle(StructureRegistered $event): void
    {
        $structure = $event->structure;

        $notification = new NewStructureRegisteredNotification(
            structureName: $structure->name,
            structureEmail: $structure->email,
            link: route('superadmin.structures.index'),
        );

        User::role('superadmin')->get()->each(fn (User $admin) => $admin->notify($notification));
    }
}
