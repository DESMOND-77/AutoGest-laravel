# Prompt - Paiement Airtel Money / Moov Money via agrégateur

## ⚠️ Prérequis bloquant

**Ne pas écrire une seule ligne d'appel réseau avant d'avoir vérifié concrètement** : la disponibilité d'un compte marchand agrégateur (ex. PVit, ou toute solution CEMAC équivalente confirmée disponible pour ce projet), ses CGU, ses coûts réels, et l'existence d'un environnement sandbox pour tester. C'est une règle déjà actée dans `docs/audit/roadmap.md` étape 13 point 4 et rappelée dans `docs/audit/etude-marche-fonctionnalites.md` §2.3 : **ne jamais inventer une intégration** (§26 CLAUDE.md). Si ces prérequis ne sont pas confirmés, implémenter uniquement l'abstraction (voir ci-dessous) avec une gateway factice, et documenter le blocage.

## Contexte

`PaymentMethod` (`app/Domain/Finance/Enums/PaymentMethod.php`) a déjà les valeurs `AirtelMoney`/`MoovMoney` - actuellement, un paiement par ces moyens est **enregistré manuellement** par l'admin (« j'ai reçu tel montant par Airtel Money »), sans vérification automatique auprès de l'opérateur. `docs/audit/etude-marche-fonctionnalites.md` §2.3 recommande un **agrégateur** (type PVit, multi-opérateurs Airtel/Moov/GIMAC Bank, pensé CEMAC) plutôt que deux intégrations directes séparées, pour réduire le risque projet.

## Objectif

Une fois les prérequis confirmés : permettre à un élève (dans le cadre de `13-reservation-libre-service-eleve.md` ou d'un paiement direct de facture) de payer par mobile money avec confirmation automatique (webhook), au lieu d'une saisie manuelle côté admin.

## Périmètre exact

- `app/Domain/Finance/Contracts/PaymentGatewayInterface.php` (nouvelle interface abstraite, déjà anticipée dans `docs/audit/roadmap.md` étape 13 point 4) : méthodes `initiate(Invoice $invoice, PaymentMethod $method, float $amount): PaymentIntent` et `handleWebhook(Request $request): PaymentResult` (signatures indicatives, à affiner selon l'API réelle de l'agrégateur retenu).
- `app/Domain/Finance/Services/<Agregateur>PaymentGateway.php` : implémentation réelle, isolée derrière l'interface - **tout le reste du domaine Finance ne doit jamais dépendre directement du SDK de l'agrégateur**.
- Route webhook publique dédiée (`POST /webhooks/payments/<agregateur>`), **hors** groupe `auth` mais avec vérification de signature/secret partagé obligatoire (ne jamais faire confiance à un webhook non authentifié - c'est une surface d'attaque directe sur la caisse).
- Intégration avec `PaymentService::record()` existant : le webhook, une fois vérifié, doit appeler `PaymentService::record()` exactement comme le fait aujourd'hui la saisie manuelle admin - ne pas dupliquer la logique de mise à jour de `Invoice`/`LedgerEntry`.
- Gestion explicite des états intermédiaires : un paiement mobile money initié mais pas encore confirmé par webhook doit avoir un état visible (`Payment` avec un statut `pending` si ce statut n'existe pas encore - vérifier le modèle `Payment` actuel, qui semble aujourd'hui n'avoir que des paiements déjà confirmés).

## Contraintes

- Ne jamais stocker de secret d'API en dur - `.env` uniquement, jamais commité.
- La vérification de signature de webhook est **obligatoire**, pas optionnelle - un webhook falsifié ne doit jamais pouvoir créditer une facture.
- Respecter le plafond anti-sur-paiement déjà en place (FIN-01) même pour ce nouveau chemin de paiement.
- Idempotence obligatoire sur le traitement du webhook (un même événement reçu deux fois - cas fréquent chez les fournisseurs de webhook - ne doit pas créditer deux fois).

## Étapes suggérées (TDD)

1. Confirmation des prérequis (voir préambule) - s'arrêter et documenter si non disponibles.
2. Lire `PaymentService.php`, `Payment.php`, `Invoice.php` en entier.
3. Tests Feature/Unit d'abord : webhook valide crédite correctement la facture (idempotent si rejoué) ; webhook avec signature invalide rejeté sans effet ; paiement en attente n'apparaît pas comme soldé tant que le webhook n'est pas reçu ; anti-sur-paiement toujours actif sur ce chemin.
4. Implémenter l'interface, l'implémentation (mockée si pas de sandbox réel disponible), la route webhook, l'intégration à `PaymentService`.
5. `php artisan test --compact --filter=MobileMoney` (ou nom choisi).
6. `vendor/bin/pint --dirty --format agent`.

## Critères d'acceptation

- Le domaine Finance ne dépend jamais directement du SDK de l'agrégateur (abstraction respectée).
- Aucun webhook non authentifié ne peut créditer une facture.
- Traitement idempotent des webhooks dupliqués, testé explicitement.
- Comportement identique à un paiement manuel une fois confirmé (même mise à jour d'`Invoice`/`LedgerEntry`, même trace d'audit).
