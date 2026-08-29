# Prompt - KPIs caisse et flux d'activité du jour sur le tableau de bord admin

## Contexte

Le tableau de bord admin (`ReportsController::dashboard()`, `resources/views/admin/dashboard.blade.php`) affiche déjà : élèves actifs, anciens élèves, taux de réussite examens, alertes flotte, graphique recettes 6 mois, examens à venir, actions rapides. C'est déjà solide.

Deux écarts confirmés par navigation réelle face à la version PHP vanilla de référence (`docs/audit/comparaison-vanilla-vs-laravel.md` §3.3, recoupé par `docs/audit/etude-marche-fonctionnalites.md` recommandation #5) :
1. **Aucun indicateur de trésorerie** : solde de caisse courant, total des impayés en cours (« reste à collecter »).
2. **Aucun flux d'activité du jour** : séances du jour, dernières opérations financières.

Toutes les données sous-jacentes existent déjà dans les domaines `Finance` (`Invoice`, `Payment`, `LedgerEntry`) et `Scheduling` (`LessonSession`) - c'est un travail d'agrégation + UI, pas de nouveau domaine.

## Objectif

Ajouter au tableau de bord admin :
- Une carte KPI « Solde caisse » (somme des `LedgerEntry` de type crédit moins débit, ou logique déjà utilisée par `LedgerService` si elle expose déjà un solde).
- Une carte KPI « Reste à collecter » (somme de `Invoice.amount_due - Invoice.amount_paid` sur les factures non soldées).
- Un bloc « Séances aujourd'hui » (liste des `LessonSession` du jour avec horaire/élève/moniteur/type).
- Un bloc « Dernières opérations financières » (derniers `LedgerEntry`, type/montant/date/motif).

## Périmètre exact

- `app/Domain/Reports/Services/ReportsService.php` (ou équivalent - vérifier le nom exact du service injecté dans `ReportsController`) : ajouter les méthodes d'agrégation, ex. `cashBalance()`, `outstandingBalance()`, `todaysSessions()`, `recentLedgerEntries(int $limit = 6)`. Vérifier avant tout si `LedgerService` expose déjà un calcul de solde réutilisable plutôt que de le dupliquer.
- `app/Domain/Reports/Http/Controllers/ReportsController.php` : passer ces nouvelles données à la vue.
- `resources/views/admin/dashboard.blade.php` : ajouter les cartes KPI (réutiliser `<x-kpi-card>` déjà utilisé) et les deux blocs de flux (réutiliser le style des blocs « Examens à venir » déjà présent).

## Contraintes

- Toutes les requêtes d'agrégation doivent être scopées au tenant courant - vérifier que `BelongsToTenant` s'applique bien par défaut sur les modèles concernés (c'est le cas pour tous les modèles du domaine), ne pas contourner ce scope.
- Ne pas dupliquer une logique de calcul de solde si `LedgerService` en a déjà une (lire ce service avant d'écrire une nouvelle méthode).
- Respecter le format monétaire déjà utilisé ailleurs dans l'app (`number_format($x, 0, ',', ' ') . ' FCFA'`).

## Étapes suggérées (TDD)

1. Lire `app/Domain/Finance/Services/LedgerService.php`, `Invoice.php` (chercher une méthode `balanceDue()` déjà existante, mentionnée dans `docs/audit/business-workflow.md` FIN-01), `app/Domain/Reports/Services/*`.
2. Écrire un test Feature/Unit qui vérifie le calcul du solde de caisse et du reste à collecter sur un jeu de données connu (plusieurs factures partiellement payées, plusieurs écritures de ledger).
3. Implémenter les méthodes d'agrégation.
4. Étendre `ReportsController::dashboard()`.
5. Étendre la vue.
6. `php artisan test --compact --filter=Dashboard` (ou le nom du test créé).
7. `vendor/bin/pint --dirty --format agent`.

## Critères d'acceptation

- Le solde de caisse affiché correspond exactement à la somme des écritures `Income`/`BankDeposit` moins `Expense`/`BankWithdrawal` du tenant courant.
- Le reste à collecter correspond à la somme des soldes dus des factures `Unpaid`/`Partial`.
- Les séances du jour et les dernières opérations financières sont scopées au tenant courant (test d'isolation).
- Aucune requête N+1 introduite (vérifier avec `Telescope`/`Debugbar` si disponible, ou lecture attentive des relations chargées).
