## Résumé

<!-- Décrivez en une ou deux phrases ce que fait cette Pull Request et pourquoi. -->

Closes #<!-- numéro de l'Issue liée, si applicable -->

## Type de changement

- [ ] 🐛 Correction de bug (`fix`)
- [ ] ✨ Nouvelle fonctionnalité (`feat`)
- [ ] 💥 Breaking change (modifie un comportement existant de façon incompatible)
- [ ] 📝 Documentation uniquement (`docs`)
- [ ] ♻️ Refactorisation sans changement de comportement (`refactor`)
- [ ] ✅ Tests uniquement (`test`)
- [ ] 🔧 Maintenance / CI / dépendances (`chore`)

## Domaine(s) concerné(s)

<!-- Cochez le ou les domaines DDD touchés (app/Domain/...) -->

- [ ] Students / Instructors / Scheduling / Training
- [ ] Finance / Store
- [ ] Fleet
- [ ] CRM / Notifications / Documents / Audit
- [ ] Reports / Settings
- [ ] Tenancy / Users / Auth
- [ ] Infrastructure (CI, config, dépendances)

## Description détaillée

<!-- Expliquez l'approche technique choisie et pourquoi, notamment si plusieurs approches étaient possibles. -->

## Tests effectués

<!-- Décrivez les tests ajoutés ou mis à jour, et comment vous avez vérifié le changement manuellement le cas échéant. -->

- [ ] `php artisan test --compact` passe intégralement en local
- [ ] `php artisan test --compact --filter=DomainBoundariesTest` passe (si un domaine a été touché)
- [ ] `vendor/bin/pint --dirty --format agent` ne signale aucune erreur
- [ ] Un test a été ajouté ou mis à jour pour couvrir ce changement

## Captures d'écran

<!-- Si le changement a un impact visuel (Blade/Tailwind), ajoutez un avant/après. Sinon, indiquez "Non applicable". -->

## Breaking changes

<!-- Ce changement casse-t-il une API, une route, un contrat de données ou une migration existante ? Si oui, détaillez l'impact et la procédure de migration. Sinon, indiquez "Aucun". -->

## Checklist

- [ ] Mon code respecte le style du projet (`vendor/bin/pint`)
- [ ] J'ai vérifié qu'aucune requête sur un modèle scopé ne contourne le tenant scope (`BelongsToTenant`)
- [ ] J'ai mis à jour la documentation concernée (README, `docs/`) si nécessaire
- [ ] Ma branche est à jour avec `dev`
- [ ] J'ai relu ma propre diff avant de demander une revue
