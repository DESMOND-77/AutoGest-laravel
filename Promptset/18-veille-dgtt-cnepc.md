# Prompt — Veille réglementaire : permis digitalisé gabonais (DGTT/CNEPC)

## Contexte

Le Gabon a lancé le **permis de conduire digitalisé** le 24 mars 2026 (DGTT, ANINF, opérateur technique Rengus Digital) — enrôlement biométrique, paiement mobile money obligatoire, délivrance sous 7 jours (`docs/audit/etude-marche-fonctionnalites.md` §2.1). C'est l'événement réglementaire le plus structurant identifié pour ce produit, mais **trop récent pour qu'une API publique d'intégration existe aujourd'hui** — aucune spécification officielle n'a été trouvée lors de la recherche ayant produit ce document.

## Objectif

⚠️ **Ce prompt n'est pas une tâche d'implémentation.** Il ne doit produire **aucun code**. Son seul livrable est une mise à jour documentaire, à répéter périodiquement (ex. trimestriellement, ou déclenché par une actualité identifiée), tant qu'aucune spécification officielle n'est publiée.

## Ce qu'il faut faire

1. Rechercher si une spécification technique publique (API DGTT/CNEPC/ANINF, format d'échange de dossier candidat) a été publiée depuis la dernière veille.
2. Rechercher si un intégrateur agréé ou un programme partenaire pour les auto-écoles a été annoncé.
3. Vérifier si les catégories de permis gérées par l'application (`App\Domain\Students\Enums\LicenseCategory` : `A, B, C, D, E`) correspondent toujours aux catégories officielles réellement utilisées (les sources consultées en 2026-08 mentionnaient aussi `F`/`G` selon certaines sources secondaires, à confirmer via une source officielle avant tout ajout).
4. Mettre à jour `docs/audit/etude-marche-fonctionnalites.md` §2.1 avec toute évolution constatée, en conservant la date de la veille.
5. Si — et seulement si — une spécification officielle exploitable est trouvée, **ne pas l'implémenter directement** : la documenter et créer un nouveau prompt dédié dans `Promptset/` (numéroté à la suite, ex. `19-integration-dgtt.md`) suivant le même gabarit que les autres prompts de ce répertoire, pour qu'elle soit traitée comme une tâche d'implémentation à part entière avec ses propres tests et critères d'acceptation.

## Contraintes

- Ne jamais coder une intégration sur la base d'une source non officielle (article de presse, blog) — seule une documentation technique publiée par la DGTT/ANINF ou un partenaire officiellement désigné justifie de passer à l'implémentation.
- Ne pas modifier `LicenseCategory` sans confirmation d'une source officielle — un changement sur cet enum affecte des données existantes en production potentielle.

## Critère d'acceptation

- Le document `etude-marche-fonctionnalites.md` reflète l'état de la veille à la date la plus récente, avec ses sources citées.
- Aucun code n'a été modifié par ce prompt, sauf création d'un nouveau prompt d'implémentation si une spécification officielle a été trouvée.
