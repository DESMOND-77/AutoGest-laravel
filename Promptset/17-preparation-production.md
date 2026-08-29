# Prompt — Préparation production (étapes 14-15 de la roadmap)

## Contexte

`docs/audit/roadmap.md` étapes 14-15, non traitées à ce jour. Ce sont des tâches de configuration/infrastructure, pas de nouvelles fonctionnalités métier — mais elles restent bloquantes avant toute mise en production réelle.

## Objectif

Traiter, dans l'ordre, les trois points restants listés dans la roadmap :

### 1. Alignement de version PHP (TECH-09)

`composer.json` déclare `"php": "^8.2"` alors que `CLAUDE.md` (Foundational Context) déclare PHP 8.5. Ce n'est **pas automatiquement une erreur à corriger dans un sens ou dans l'autre** — c'est un écart à clarifier d'abord :
- Vérifier quelle version PHP tourne réellement sur l'environnement cible (production/staging).
- Si 8.5 est bien la cible réelle, remonter la contrainte `composer.json` en conséquence et vérifier la compatibilité des dépendances (`composer why-not php ^8.5` ou équivalent).
- Si 8.2/8.3 reste la cible réelle pour l'instant, corriger `CLAUDE.md` plutôt que le code — ne jamais choisir arbitrairement sans vérifier l'environnement réel.

### 2. Vérification `APP_DEBUG=false` en production + garde CI/CD (SEC-12)

- Vérifier qu'aucune configuration de déploiement ne fixe `APP_DEBUG=true` par défaut.
- Ajouter un contrôle automatisé (étape de CI, ou commande Artisan de vérification pré-déploiement) qui échoue si `APP_DEBUG` est `true` alors que `APP_ENV=production` — pour éviter qu'une régression de configuration ne fuite des stack traces en production.

### 3. Docker / CI-CD / monitoring / sauvegardes

Selon la checklist déjà référencée en §46 CLAUDE.md (à relire pour le détail exact des exigences) :
- `Dockerfile`/`docker-compose.yml` de production (si absent) — vérifier d'abord ce qui existe déjà (`laravel/sail` est présent en dev, à ne pas confondre avec une configuration de production).
- Pipeline CI (tests + Pint + éventuellement analyse statique) sur chaque push/PR.
- Stratégie de sauvegarde de la base de données (fréquence, rétention, test de restauration — une sauvegarde jamais testée en restauration n'est pas une sauvegarde fiable).
- Monitoring applicatif minimal (erreurs, temps de réponse) — évaluer les options disponibles sans imposer un choix non validé par l'équipe (Sentry, Laravel Pulse, ou autre).

## Contraintes

- Ne rien déployer ni modifier de configuration de production sans confirmation explicite — ce prompt couvre la préparation, pas l'exécution d'un déploiement réel.
- Ne pas introduire de secrets (clés API, mots de passe de base de données) dans des fichiers versionnés, y compris dans un `docker-compose.yml` de démonstration.
- Toute nouvelle dépendance (outil CI, agent de monitoring) doit être validée avant d'être ajoutée.

## Étapes suggérées

1. Relire §46 CLAUDE.md en entier pour la checklist exacte (non reproduite ici en détail pour éviter toute divergence avec la source de vérité).
2. Traiter le point 1 (version PHP) en premier — c'est un simple constat à vérifier, rapide à trancher.
3. Traiter le point 2 (APP_DEBUG) — ajout d'un contrôle CI, testable immédiatement.
4. Traiter le point 3 par sous-étapes, chacune validée séparément avant de passer à la suivante (Docker, puis CI, puis sauvegardes, puis monitoring) — ne pas tout livrer d'un bloc sans revue intermédiaire vu l'impact opérationnel.

## Critères d'acceptation

- L'écart de version PHP est résolu dans un sens ou dans l'autre, avec la justification documentée dans `docs/audit/roadmap.md` (mise à jour du statut TECH-09).
- Un contrôle automatisé empêche `APP_DEBUG=true` en production.
- Configuration Docker de production fonctionnelle et documentée dans `docs/deployment.md` (déjà existant, à compléter).
- Stratégie de sauvegarde documentée et sa restauration testée au moins une fois.
