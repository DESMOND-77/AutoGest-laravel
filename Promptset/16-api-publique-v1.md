# Prompt — API publique versionnée (`/api/v1/`) avec Sanctum

## Contexte

`docs/audit/roadmap.md` étape 13 point 5 : « API publique versionnée (`/api/v1/`) avec Sanctum — seulement quand le domaine interne est stable ». C'est explicitement la **dernière** brique de la roadmap fonctionnelle — à n'entreprendre qu'après que les écarts fonctionnels prioritaires (`01` à `13` de ce Promptset) sont traités, car une API publique fige un contrat qu'il devient coûteux de faire évoluer.

## Objectif

Exposer une API REST versionnée permettant à des clients externes (application mobile future, intégrations tierces) d'interagir avec les données du tenant courant, authentifiée par token Sanctum, avec la même isolation tenant et les mêmes policies que l'interface web.

## Périmètre exact (première version, à ne pas dépasser sans validation)

- `routes/api.php` : groupe `/v1`, middleware `auth:sanctum`.
- Endpoints prioritaires (lecture seule dans un premier temps, à étendre progressivement) : `GET /v1/students`, `GET /v1/students/{id}`, `GET /v1/scheduling/sessions`, `GET /v1/students/{id}/skills` — à ajuster selon les besoins réels du premier client externe (ne pas construire une API exhaustive sans consommateur identifié).
- `App\Domain\<Domaine>\Http\Resources\<Modele>Resource.php` (Eloquent API Resources, cf. CLAUDE.md « APIs & Eloquent Resources ») pour chaque endpoint — jamais retourner un modèle Eloquent brut en JSON.
- Génération de token : endpoint `POST /v1/tokens` (ou intégré à l'écran `/settings` existant) permettant à un admin de générer un token Sanctum scopé à son tenant, avec des abilities explicites (`students:read`, etc. — définir la granularité avec le métier selon le premier cas d'usage réel).
- Policies : réutiliser strictement les policies existantes (`StudentPolicy`, etc.) — ne jamais dupliquer la logique d'autorisation entre web et API.

## Contraintes

- **Isolation tenant non négociable** : chaque endpoint doit être testé pour vérifier qu'un token d'un tenant ne peut jamais lire les données d'un autre tenant — c'est la classe de faille la plus critique déjà rencontrée dans ce projet (`docs/audit/multi-tenancy-audit.md`), à ne surtout pas réintroduire côté API.
- Versionner dès le départ (`/v1/`) même si aucune `v2` n'est prévue à court terme — ne jamais exposer de route non versionnée.
- Rate limiting sur tous les endpoints (`throttle:`), cohérent avec les limites déjà appliquées sur les routes publiques existantes (`routes/auth.php`, `routes/web.php` pour l'auto-inscription).
- Documenter chaque endpoint au fur et à mesure dans `docs/api.md` (déjà existant — le compléter, pas le dupliquer).
- Ne pas construire d'endpoints d'écriture avant qu'un besoin concret (client mobile, intégration tierce) ne soit identifié — cf. objectif : lecture seule en premier.

## Étapes suggérées (TDD)

1. Lire `docs/api.md` existant (déjà en place selon la structure `docs/`) pour connaître les conventions déjà actées, s'il y en a.
2. Vérifier que `laravel/sanctum` est bien disponible (déjà listé dans les dépendances du projet ou à ajouter — dans ce dernier cas, valider l'ajout avant `composer require`).
3. Tests Feature d'abord, par endpoint : accès autorisé avec token valide et bonnes abilities ; accès refusé sans token ; accès refusé avec token d'un autre tenant (isolation) ; accès refusé si l'ability requise n'est pas accordée au token.
4. Implémenter routes, resources, policies (réutilisées), génération de token.
5. `php artisan test --compact --filter=Api`.
6. `vendor/bin/pint --dirty --format agent`.
7. Compléter `docs/api.md`.

## Critères d'acceptation

- Isolation tenant testée explicitement sur chaque endpoint.
- Aucune donnée exposée sans passer par une API Resource (pas de modèle brut sérialisé).
- Documentation à jour dans `docs/api.md`.
- Rate limiting actif sur tous les endpoints publics.
