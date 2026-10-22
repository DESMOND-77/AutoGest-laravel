<?php

namespace App\Support\Ui;

use App\Models\User;
use Illuminate\Support\Facades\Route;

/**
 * Navigation par rôle (§7.2). Les entrées sont filtrées sur la présence réelle
 * de la route et sur le rôle : jamais un lien mort dans la sidebar.
 *
 * @phpstan-type NavItem array{label: string, route: string, icon: string, match: string}
 * @phpstan-type NavGroup array{label: string, items: array<int, NavItem>}
 */
class Navigation
{
    /** @return array<int, NavGroup> */
    public static function forUser(?User $user): array
    {
        if (! $user) {
            return [];
        }

        return match (true) {
            $user->hasRole('superadmin') => self::superadmin(),
            $user->hasRole('admin') => self::admin(),
            $user->hasRole('moniteur') => self::moniteur(),
            $user->hasRole('eleve') => self::eleve(),
            default => [],
        };
    }

    /** @return array<int, NavGroup> */
    public static function admin(): array
    {
        return self::filter([
            ['label' => 'Pilotage', 'items' => [
                ['label' => 'Tableau de bord', 'route' => 'admin.dashboard', 'icon' => 'layout-dashboard', 'match' => 'admin.dashboard'],
                ['label' => 'Planning', 'route' => 'scheduling.index', 'icon' => 'calendar-days', 'match' => 'scheduling.*'],
            ]],
            ['label' => 'Élèves et formation', 'items' => [
                ['label' => 'Élèves', 'route' => 'students.index', 'icon' => 'graduation-cap', 'match' => 'students.*'],
                ['label' => 'Dossiers à valider', 'route' => 'dossiers.index', 'icon' => 'clipboard-check', 'match' => 'dossiers.*'],
                ['label' => 'Compétences', 'route' => 'training.skills.index', 'icon' => 'target', 'match' => 'training.skills.*'],
                ['label' => 'Examens', 'route' => 'training.exams.index', 'icon' => 'award', 'match' => 'training.exams.*'],
                ['label' => 'Prospects', 'route' => 'crm.leads.index', 'icon' => 'sparkles', 'match' => 'crm.*'],
            ]],
            ['label' => 'Équipe et ressources', 'items' => [
                ['label' => 'Moniteurs', 'route' => 'instructors.index', 'icon' => 'users', 'match' => 'instructors.*'],
                ['label' => 'Flotte', 'route' => 'fleet.index', 'icon' => 'car', 'match' => 'fleet.*'],
            ]],
            ['label' => 'Finance et boutique', 'items' => [
                ['label' => 'Factures', 'route' => 'finance.invoices.index', 'icon' => 'receipt', 'match' => 'finance.invoices.*'],
                ['label' => 'Forfaits', 'route' => 'finance.packages.index', 'icon' => 'ticket', 'match' => 'finance.packages.*'],
                ['label' => 'Journal comptable', 'route' => 'finance.ledger.index', 'icon' => 'landmark', 'match' => 'finance.ledger.*'],
                ['label' => 'Recyclage', 'route' => 'recyclage.index', 'icon' => 'refresh-cw', 'match' => 'recyclage.*'],
                ['label' => 'Produits', 'route' => 'store.products.index', 'icon' => 'package', 'match' => 'store.products.*'],
                ['label' => 'Commandes', 'route' => 'store.orders.index', 'icon' => 'shopping-bag', 'match' => 'store.orders.*'],
                ['label' => 'Bons de commande', 'route' => 'store.purchase-orders.index', 'icon' => 'clipboard-list', 'match' => 'store.purchase-orders.*'],
                ['label' => 'Rapports boutique', 'route' => 'store.reports.show', 'icon' => 'chart-bar', 'match' => 'store.reports.*'],
            ]],
            ['label' => 'Administration', 'items' => [
                ['label' => 'Utilisateurs', 'route' => 'settings.users.index', 'icon' => 'user-check', 'match' => 'settings.users.*'],
                ['label' => 'Paramètres', 'route' => 'settings.show', 'icon' => 'settings', 'match' => 'settings.*'],
                ['label' => 'Journal d’audit', 'route' => 'audit.index', 'icon' => 'shield', 'match' => 'audit.*'],
            ]],
        ]);
    }

    /** @return array<int, NavGroup> */
    public static function superadmin(): array
    {
        return self::filter([
            ['label' => 'Plateforme', 'items' => [
                ['label' => 'Établissements', 'route' => 'superadmin.structures.index', 'icon' => 'building-office', 'match' => 'superadmin.*'],
            ]],
            ['label' => 'Supervision', 'items' => [
                ['label' => 'Journal d’audit', 'route' => 'audit.index', 'icon' => 'shield', 'match' => 'audit.*'],
            ]],
        ]);
    }

    /** @return array<int, NavGroup> */
    public static function moniteur(): array
    {
        return self::filter([
            ['label' => 'Aujourd’hui', 'items' => [
                ['label' => 'Ma journée', 'route' => 'moniteur.dashboard', 'icon' => 'home', 'match' => 'moniteur.dashboard'],
                ['label' => 'Mon agenda', 'route' => 'moniteur.agenda', 'icon' => 'calendar-days', 'match' => 'moniteur.agenda'],
            ]],
            ['label' => 'Mes élèves', 'items' => [
                ['label' => 'Élèves', 'route' => 'students.index', 'icon' => 'graduation-cap', 'match' => 'students.*'],
                ['label' => 'Compétences', 'route' => 'training.skills.index', 'icon' => 'target', 'match' => 'training.skills.*'],
            ]],
            ['label' => 'Ressources', 'items' => [
                ['label' => 'Flotte', 'route' => 'fleet.index', 'icon' => 'car', 'match' => 'fleet.*'],
                ['label' => 'Moniteurs', 'route' => 'instructors.index', 'icon' => 'users', 'match' => 'instructors.*'],
            ]],
        ]);
    }

    /** @return array<int, NavGroup> */
    public static function eleve(): array
    {
        return self::filter([
            ['label' => 'Mon parcours', 'items' => [
                ['label' => 'Accueil', 'route' => 'eleve.dashboard', 'icon' => 'home', 'match' => 'eleve.dashboard'],
                ['label' => 'Planning', 'route' => 'eleve.planning', 'icon' => 'calendar-days', 'match' => 'eleve.planning'],
                ['label' => 'Progression', 'route' => 'eleve.progression', 'icon' => 'target', 'match' => 'eleve.progression'],
            ]],
            ['label' => 'Administratif', 'items' => [
                ['label' => 'Paiements', 'route' => 'eleve.paiements', 'icon' => 'wallet', 'match' => 'eleve.paiements'],
                ['label' => 'Mon dossier', 'route' => 'eleve.dossier.show', 'icon' => 'folder', 'match' => 'eleve.dossier.*'],
                ['label' => 'Entraînement au code', 'route' => 'quiz.play', 'icon' => 'book-open', 'match' => 'quiz.*'],
            ]],
        ]);
    }

    /**
     * Entrées du menu « Nouveau » : uniquement les créations réellement
     * autorisées au rôle (§7.4).
     *
     * @return array<int, array{label: string, route: string, icon: string}>
     */
    public static function createActions(?User $user): array
    {
        if (! $user) {
            return [];
        }

        $actions = match (true) {
            $user->hasRole('admin') => [
                ['label' => 'Nouvel élève', 'route' => 'students.create', 'icon' => 'user-plus'],
                ['label' => 'Nouvelle séance', 'route' => 'scheduling.index', 'icon' => 'calendar'],
                ['label' => 'Nouveau prospect', 'route' => 'crm.leads.index', 'icon' => 'sparkles'],
                ['label' => 'Nouveau véhicule', 'route' => 'fleet.index', 'icon' => 'car'],
            ],
            $user->hasRole('moniteur') => $user->can('create', \App\Domain\Students\Models\Student::class)
                ? [['label' => 'Nouvel élève', 'route' => 'students.create', 'icon' => 'user-plus']]
                : [],
            $user->hasRole('superadmin') => [
                ['label' => 'Voir les établissements', 'route' => 'superadmin.structures.index', 'icon' => 'building-office'],
            ],
            default => [],
        };

        return array_values(array_filter($actions, fn (array $action) => Route::has($action['route'])));
    }

    /**
     * @param  array<int, NavGroup>  $groups
     * @return array<int, NavGroup>
     */
    private static function filter(array $groups): array
    {
        $filtered = [];

        foreach ($groups as $group) {
            $items = array_values(array_filter(
                $group['items'],
                fn (array $item) => Route::has($item['route']),
            ));

            if ($items !== []) {
                $filtered[] = ['label' => $group['label'], 'items' => $items];
            }
        }

        return $filtered;
    }

    /** Élément actif ? Le motif `match` est comparé à route()->getName(). */
    public static function isActive(string $match): bool
    {
        return request()->routeIs($match);
    }
}
