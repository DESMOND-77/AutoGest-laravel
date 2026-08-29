# Prompt — Module Code Rousseau

## ⚠️ Prérequis bloquant

**Ne pas implémenter avant confirmation explicite du métier gabonais**, pour deux raisons cumulées :
1. `docs/audit/legacy-feature-parity.md` classe cette fonctionnalité comme « à trancher avec le métier ».
2. `docs/audit/etude-marche-fonctionnalites.md` (§3) note que « Codes Rousseau » est une marque **spécifiquement française** (éditeur Codes Rousseau) sans équivalent identifié dans la réglementation ou le marché gabonais — le besoin réel pourrait être « vente de supports de révision du code » en général, pas cette marque précise.

Si ce prompt est exécuté sans confirmation métier explicite (y compris sur le nom du module, à généraliser si besoin — ex. « Vente de supports pédagogiques »), **s'arrêter et la demander**.

## Contexte (une fois confirmé)

La version PHP vanilla de référence a un écran dédié (`/modules/admin/code_rousseau.php`) : suivi des ventes de codes/supports de révision, avec acheteur, nombre de codes, montant total, montant encaissé, reste à percevoir — logique d'encaissement partiel similaire à `Invoice`/`Payment` mais sur un objet métier distinct (`docs/audit/comparaison-vanilla-vs-laravel.md` §1.2).

**Avant de coder quoi que ce soit**, évaluer sérieusement si le module `Store` générique déjà existant (catalogue produits + commandes fournisseurs, `app/Domain/Store`) peut simplement absorber ce besoin en ajoutant un produit « Support de révision code » avec vente directe (pas juste commande fournisseur), plutôt que de créer un domaine dédié en doublon. C'est explicitement suggéré comme option dans `docs/audit/etude-marche-fonctionnalites.md`.

## Objectif

Selon l'arbitrage obtenu du métier :
- **Option A (recommandée par défaut)** : étendre `Store` pour supporter une vente directe (pas juste une commande fournisseur) avec encaissement partiel — réutilise `Product`, ajoute un flux de vente client au lieu d'un domaine séparé.
- **Option B** : domaine dédié `app/Domain/CodeSales` (ou nom validé), sur le même gabarit que `09-module-recyclage-tests.md`, si le métier confirme que ce n'est vraiment pas assimilable à une vente boutique classique (ex. règles de suivi encaissé/reste très spécifiques).

## Périmètre exact (Option A — vente directe Store)

- `app/Domain/Store/Models/Sale.php` (nouveau, distinct de `Order` qui reste pour les commandes fournisseurs) : `structure_id`, `product_id` (nullable si vente générique), `buyer_name` (nullable — la version de référence a des « Acheteur anonyme »), `quantity`, `amount_total`, `amount_paid`, `sold_at`.
- `app/Domain/Store/Services/SaleService.php` : `record()` transactionnel, même pattern que `PaymentService::record()` pour l'encaissement partiel, événement + listener pour intégration ledger (même pattern FIN-04 que le prompt Recyclage).
- Policy, form request, contrôleur, routes `store.sales.*`, vue.

## Périmètre exact (Option B — domaine dédié)

Reprendre point pour point la structure du prompt `09-module-recyclage-tests.md`, adaptée aux champs acheteur/nb codes/montant total/encaissé/reste.

## Contraintes

- Ne pas dupliquer la logique d'encaissement partiel déjà écrite dans `PaymentService` — l'extraire en trait/service partagé si Option A et l'existant `PaymentService` finissent par se ressembler trop, plutôt que copier-coller la même logique bcmath une troisième fois.
- Isolation tenant obligatoire.
- Intégration ledger via événement/listener découplé (jamais d'appel direct cross-domaine).

## Étapes suggérées (TDD)

1. Confirmation métier obtenue (y compris arbitrage Option A/B).
2. Lire `app/Domain/Store` en entier, et `app/Domain/Finance/Services/PaymentService.php` comme référence d'encaissement partiel.
3. Tests Feature d'abord : vente créée, encaissement partiel, solde correct, apparition dans le ledger, isolation tenant.
4. Implémenter selon l'option retenue.
5. `php artisan test --compact --filter=Sale` (ou nom choisi).
6. `vendor/bin/pint --dirty --format agent`.

## Critères d'acceptation

- Une vente (quel que soit le nom retenu) avec encaissement partiel calcule correctement le reste à percevoir, en arithmétique décimale exacte (bcmath, pas de flottant — cohérent avec l'exigence déjà appliquée à `Invoice`/`Payment`).
- Isolation tenant testée.
- Pas de duplication de la logique d'encaissement partiel déjà existante pour les factures élèves.
