<?php

namespace App\Providers;

use App\Support\Ui\DashboardData;
use App\Support\Ui\Navigation;
use App\Support\Ui\Preferences;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

/**
 * Fournit à toutes les vues les attributs de <html> (préférences validées) et
 * la navigation filtrée par rôle, pour qu'aucune vue n'ait à décoder un cookie.
 */
class UiServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        View::composer('*', function ($view): void {
            $preferences = Preferences::fromRequest(request());

            $view->with('uiAttributes', $preferences->htmlAttributes());
            $view->with('uiPrefsJson', $preferences->toJson());
            $view->with('navGroups', Navigation::forUser(request()->user()));
            $view->with('createActions', Navigation::createActions(request()->user()));
        });

        // Les deux espaces servis par Route::view (aucun contrôleur) reçoivent
        // leurs données ici, sans toucher aux routes ni aux contrôleurs.
        View::composer('moniteur.dashboard', function ($view): void {
            $user = request()->user();
            $view->with($user ? DashboardData::forMonitor($user) : []);
        });

        View::composer('eleve.dashboard', function ($view): void {
            $user = request()->user();
            $view->with($user ? DashboardData::forStudent($user) : []);
        });
    }
}
