# Prompt - Activer les notifications SMS

## Contexte

Le domaine `App\Domain\Notifications` a déjà une infrastructure de canal SMS scaffoldée : `SmsGateway` (contrat), `SmsChannel`, et un `LogSmsGateway` (implémentation de test qui écrit dans les logs au lieu d'envoyer réellement). Aujourd'hui, **rien ne déclenche d'envoi SMS** - seule l'alerte véhicule quotidienne (`CheckFleetAlerts`, `AlertNotification`) existe, et elle passe actuellement par un canal in-app, pas SMS.

Roadmap : `docs/audit/roadmap.md` étape 13, point 3.

## Objectif

1. Choisir et brancher un fournisseur SMS réel adapté au Gabon (à vérifier - pas d'invention d'API, voir contrainte ci-dessous), en remplaçant `LogSmsGateway` par une implémentation réelle **derrière le même contrat `SmsGateway`**.
2. Utiliser ce canal pour au moins un cas d'usage concret : rappel de séance à J-1 (le cas le plus demandé, cf. `docs/audit/etude-marche-fonctionnalites.md`), en complément ou alternative du canal WhatsApp traité dans `12-rappels-whatsapp-seances.md`.

## Périmètre exact

- `app/Domain/Notifications/Channels/SmsChannel.php`, `app/Domain/Notifications/Contracts/SmsGateway.php` : lire l'interface existante avant tout, ne pas la casser.
- Nouvelle implémentation `app/Domain/Notifications/Channels/<Fournisseur>SmsGateway.php` (nom selon le fournisseur retenu), injectée via le service container en remplacement de `LogSmsGateway` en environnement de production (garder `LogSmsGateway` pour les tests/dev - binding conditionnel dans un `ServiceProvider`, pattern déjà probablement utilisé pour d'autres intégrations).
- Configuration : nouvelles variables d'environnement (`SMS_GATEWAY_*`) dans `.env.example`, jamais de clé en dur dans le code.
- Nouveau job planifié (ou extension de `CheckFleetAlerts`/nouveau `Command`) : `SendLessonReminders`, qui identifie les `LessonSession` du lendemain et envoie un rappel SMS à l'élève concerné (si son numéro de téléphone est renseigné).

## Contraintes

- **Ne jamais inventer une intégration** : avant d'écrire la moindre ligne d'appel réseau, vérifier concrètement quel fournisseur SMS est disponible au Gabon (coût, CGU, sandbox de test) - cf. §26 CLAUDE.md et `docs/audit/etude-marche-fonctionnalites.md` §2.4, qui recommande d'évaluer WhatsApp Business API en priorité et le SMS en repli. Si aucun fournisseur n'est confirmé disponible, **s'arrêter et documenter le blocage** plutôt que de simuler une intégration.
- Ne jamais faire échouer une requête HTTP utilisateur à cause d'un échec d'envoi SMS - le déclenchement doit être asynchrone (queue Laravel) avec retry/log d'échec, jamais synchrone dans le flux de création d'une séance.
- Pas de nouvelle dépendance Composer sans validation explicite (le SDK du fournisseur retenu doit être proposé et validé avant `composer require`).

## Étapes suggérées (TDD)

1. Vérifier la disponibilité réelle d'un fournisseur SMS gabonais/CEMAC (recherche + confirmation utilisateur si besoin).
2. Lire `app/Domain/Notifications` en entier, `CheckFleetAlerts.php` comme gabarit de commande planifiée par tenant.
3. Écrire les tests : `Notification::fake()` pour vérifier que le rappel est bien mis en queue pour les bonnes séances (J-1, élève avec téléphone renseigné), et qu'aucun rappel n'est envoyé pour un élève sans téléphone ou une séance déjà annulée.
4. Implémenter la nouvelle gateway (mockée en test), la commande planifiée, l'enregistrement dans `routes/console.php` (`Schedule::command(...)->dailyAt(...)`, cohérent avec `fleet:check-alerts`).
5. `php artisan test --compact --filter=Reminder`.
6. `vendor/bin/pint --dirty --format agent`.

## Critères d'acceptation

- Le canal SMS réel fonctionne derrière le contrat `SmsGateway` existant, sans changement d'API pour le code appelant.
- Les rappels de séance sont envoyés en asynchrone, avec gestion d'échec (log, pas de crash).
- Aucune clé API en dur dans le code versionné.
