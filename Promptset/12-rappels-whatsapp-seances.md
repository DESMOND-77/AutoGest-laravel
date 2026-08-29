# Prompt — Rappels de séance automatiques via WhatsApp Business API

## Contexte

`docs/audit/etude-marche-fonctionnalites.md` §2.4 : plus de 85% des échanges commerciaux PME↔clients en Afrique subsaharienne passent par WhatsApp, avec une réduction démontrée des no-shows (25% → 5-8%) pour les rappels de rendez-vous envoyés sur ce canal. C'est recommandé **en priorité sur le SMS** pour ce cas d'usage précis (voir `11-notifications-sms.md`, qui reste pertinent pour d'autres cas ou en repli).

Le domaine `App\Domain\Notifications` a une architecture de canaux déjà extensible (`Contracts/`, `Channels/`) — ajouter un canal WhatsApp suit le même patron que le canal SMS existant, sans réécrire l'infrastructure.

## Objectif

Envoyer un message WhatsApp de rappel à l'élève (si son numéro est renseigné et a un compte WhatsApp) la veille de chaque séance planifiée (`LessonSession`), via l'API Meta Cloud (WhatsApp Business Platform).

## Périmètre exact

- `app/Domain/Notifications/Contracts/WhatsAppGateway.php` (nouveau contrat, même esprit que `SmsGateway`).
- `app/Domain/Notifications/Channels/WhatsAppChannel.php` (nouveau canal Laravel Notification).
- `app/Domain/Notifications/Channels/MetaCloudWhatsAppGateway.php` : implémentation réelle contre l'API Meta Cloud (template de message pré-approuvé requis par Meta pour les messages sortants hors fenêtre de 24h — **vérifier ce point précisément avant implémentation**, c'est une contrainte structurante de l'API WhatsApp Business, pas un détail).
- Réutiliser/étendre la même commande planifiée que `11-notifications-sms.md` (`SendLessonReminders`) pour choisir le canal WhatsApp en priorité et retomber sur SMS si WhatsApp échoue ou si le fournisseur SMS n'est pas encore branché — logique de repli explicite, testée.
- Configuration : `.env.example` avec `WHATSAPP_CLOUD_API_*` (token, phone number ID, template name).

## Contraintes

- **Vérification préalable obligatoire** (§26 CLAUDE.md, déjà répété dans `docs/audit/roadmap.md` étape 13 point 4 pour le mobile money — même exigence ici) : confirmer concrètement l'accès à un compte Meta Business Manager, un numéro WhatsApp Business vérifié, et au moins un template de message approuvé par Meta, **avant** d'écrire l'implémentation réelle. Si rien de tout cela n'est disponible dans l'environnement du projet, implémenter uniquement le contrat + une gateway de test (log), et documenter explicitement le blocage plutôt que de simuler un appel API qui n'a jamais été validé.
- Respecter les règles WhatsApp Business sur les templates (un message proactif hors fenêtre de 24h doit utiliser un template pré-approuvé, texte libre interdit) — ne pas coder un envoi de texte libre en supposant que ça fonctionnera en production.
- Envoi strictement asynchrone (queue), jamais bloquant sur une requête utilisateur.
- Ne jamais envoyer de rappel à un numéro qui n'a pas explicitement été renseigné/consenti par l'élève — pas de contournement du consentement.

## Étapes suggérées (TDD)

1. Confirmer la disponibilité réelle des prérequis Meta Business (voir contrainte) — s'arrêter ici si non disponible et le signaler.
2. Lire l'architecture `Notifications` existante en entier (canal SMS comme gabarit direct).
3. Tests : `Notification::fake()` pour vérifier le déclenchement J-1, le repli SMS si WhatsApp indisponible, l'absence d'envoi si pas de numéro/consentement.
4. Implémenter contrat, canal, gateway (mockée en test tant que les credentials réels ne sont pas fournis).
5. Étendre la commande planifiée avec la logique de choix de canal.
6. `php artisan test --compact --filter=Reminder`.
7. `vendor/bin/pint --dirty --format agent`.

## Critères d'acceptation

- Le canal WhatsApp est prêt derrière un contrat propre, activable dès que les credentials Meta réels sont fournis.
- La logique de repli WhatsApp → SMS est testée explicitement.
- Aucun appel réseau réel n'est fait dans les tests (tout est mocké/faked).
- Aucune supposition non vérifiée sur le comportement de l'API Meta n'est codée en dur (templates, fenêtre 24h) sans confirmation documentée.
