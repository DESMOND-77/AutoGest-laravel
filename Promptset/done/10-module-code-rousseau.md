Après analyse des pratiques de gestion de boutique dans les auto-écoles et des fonctionnalités attendues d'un tel module, voici le prompt enrichi et structuré.

---

# Prompt - Module Boutique Auto-École (ex. Code Rousseau)

## ⚠️ Prérequis bloquant

**Ne pas implémenter avant confirmation explicite du métier gabonais**, pour trois raisons cumulées :
1. `docs/audit/legacy-feature-parity.md` classe cette fonctionnalité comme « à trancher avec le métier ».
2. `docs/audit/etude-marche-fonctionnalites.md` (§3) note que « Codes Rousseau » est une marque **spécifiquement française** (éditeur Codes Rousseau) sans équivalent identifié dans la réglementation ou le marché gabonais - le besoin réel pourrait être « vente de supports de révision du code » en général, pas cette marque précise.
3. Les recherches effectuées confirment que **le besoin métier d'une auto-école va bien au-delà de la simple vente de codes Rousseau** : il s'agit d'une véritable **boutique physique ou en ligne** couvrant l'ensemble des produits pédagogiques et services annexes.

Si ce prompt est exécuté sans confirmation métier explicite (y compris sur le nom du module, à généraliser - ex. « Boutique » ou « Vente de supports pédagogiques »), **s'arrêter et la demander**.

## Contexte métier (une fois confirmé)

### La boutique dans une auto-école : un besoin transverse

Une auto-école ne se limite pas à la formation pratique et théorique. Elle propose **une gamme de produits et services complémentaires** qui constituent un véritable point de vente :

| Catégorie | Exemples de produits |
|-----------|----------------------|
| **Supports pédagogiques** | Livres du code de la route, livrets d'apprentissage (REMC numérique), DVD, applications mobiles |
| **Produits dérivés** | Kits de signalisation, gilets réfléchissants, autocollants "A" |
| **Prestations** | Forfaits de conduite, heures supplémentaires, examens blancs |
| **Services numériques** | Accès à des plateformes de code en ligne, simulateurs de conduite |

Cette diversité implique une **gestion structurée** : catalogue produits, suivi de stock, ventes au comptoir, encaissements partiels, et reporting.

## Objectif

étendre `Store`  intégre l'ensemble des fonctionnalités décrites ci-dessous.

## Périmètre fonctionnel détaillé

### Architecture de l'interface utilisateur (vue admin)

La page principale du module « Boutique » dans le sideBar  sera organisée en **4 onglets (TABS)** pour une navigation claire et intuitive :

| Onglet | Contenu | Objectif |
|--------|---------|----------|
| **📦 Ventes** | Liste des ventes avec filtres (date, produit, acheteur, statut de paiement). Création d'une nouvelle vente (sélection produit, quantité, acheteur, montant encaissé). | Suivi opérationnel des ventes au comptoir. |
| **📊 Rapports** | Tableaux de bord et graphiques : chiffre d'affaires par période, top produits vendus, évolution des ventes, marge brute, stocks critiques. | Pilotage stratégique de la boutique. |
| **🏷️ Produits** | Catalogue des produits : ajout/modification/suppression, prix d'achat et de vente, seuil d'alerte stock, fournisseur, code-barres (optionnel). | Gestion de l'offre produits. |
| **📥 Réapprovisionnement** | Commandes fournisseurs : création, réception, mise à jour du stock. Historique des réceptions. | Gestion des approvisionnements. |

**Justification** : les logiciels de gestion pour auto-écoles intègrent généralement des tableaux de bord et des indicateurs de performance pour piloter l'activité. La séparation en onglets permet de distinguer clairement les flux opérationnels (ventes, réappro) des flux de pilotage (rapports) et de gestion (produits).

### 1. Onglet « Ventes »

- **Création d'une vente** :
  - Sélection d'un produit du catalogue (ou saisie libre pour un produit hors catalogue).
  - Quantité vendue (avec vérification du stock disponible - *alerte* si stock insuffisant, mais vente autorisée avec notification).
  - Acheteur : nom (obligatoire), téléphone, email (optionnels).
  - Montant total = prix unitaire × quantité (calcul automatique, modifiable manuellement si remise).
  - Montant encaissé (saisie manuelle, peut être inférieur au total → encaissement partiel).
  - Date de la vente (par défaut aujourd'hui).
  - **Stock** : décrémentation automatique du stock du produit vendu.
- **Liste des ventes** :
  - Filtres : date (période), produit, acheteur, statut (payé, partiellement payé, impayé).
  - Colonnes : date, produit, acheteur, quantité, total, encaissé, reste à percevoir, statut.
  - Actions : voir le détail, enregistrer un nouvel encaissement, modifier (si non clôturé), annuler (avec remise en stock).
- **Encaissement partiel** :
  - Logique identique à `PaymentService` (réutilisation via trait/service partagé).
  - Un événement `SalePaymentRecorded` est déclenché → listener pour intégration ledger (même pattern FIN-04 que le prompt Recyclage).
  - Calcul du reste à percevoir = total - somme des encaissements (bcmath, pas de flottant).

### 2. Onglet « Produits »

- **Catalogue produits** :
  - Champs : nom, description, référence, prix d'achat HT, prix de vente TTC, TVA (taux), stock actuel, seuil d'alerte, fournisseur, catégorie (ex. "Livres", "DVD", "Accessoires", "Prestations").
  - Code-barres (optionnel, pour scan en caisse).
  - Image du produit (optionnelle).
- **Suivi de stock** :
  - Historique des mouvements (ventes, réceptions, ajustements).
  - Alerte visuelle si stock < seuil d'alerte.
  - Possibilité d'ajuster manuellement le stock (inventaire).

### 3. Onglet « Rapports »

- **Tableau de bord synthétique** :
  - Chiffre d'affaires (aujourd'hui, cette semaine, ce mois, cette année).
  - Nombre de ventes.
  - Top 5 des produits les plus vendus (quantité et CA).
  - Évolution des ventes (graphique linéaire).
  - Stocks critiques (produits sous le seuil).
  - Montant total des encaissements partiels en attente.
- **Rapports exportables** (PDF, CSV) :
  - Rapport de ventes par période.
  - Rapport de rentabilité par produit (marge = prix vente - prix achat).
  - État des stocks (inventaire).

### 4. Onglet « Réapprovisionnement »

- **Commandes fournisseurs** (distinct des ventes) :
  - Création d'une commande : fournisseur, date, liste des produits avec quantités.
  - Statut : "En attente", "Réception partielle", "Réceptionnée", "Annulée".
  - Réception : saisie des quantités réellement reçues → mise à jour du stock.
  - Historique des commandes.
- **Gestion des fournisseurs** (si non déjà présente dans `Store`) : nom, contact, délai de livraison.

## Périmètre technique 

la structure suivante est proposée, analyse, compare, discute et faire des suggetions :

### Modèles

``` tree
app/Domain/Store/
├── Models/
│   ├── Product.php               # Catalogue produits
│   ├── Category.php              # Catégories de produits
│   ├── Supplier.php              # Fournisseurs
│   ├── Sale.php                  # Vente (structure_id, product_id, buyer_name, quantity, amount_total, amount_paid, sold_at, status)
│   ├── SaleItem.php              # Lignes de vente (si vente multi-produits)
│   ├── SalePayment.php           # Encaissements partiels (montant, date, méthode)
│   ├── PurchaseOrder.php         # Commande fournisseur
│   ├── PurchaseOrderItem.php     # Lignes de commande
│   ├── StockMovement.php         # Mouvement de stock (vente, réception, ajustement)
│   └── ...
├── Services/
│   ├── SaleService.php           # record() transactionnel, encaissement partiel
│   ├── ProductService.php        # Gestion du catalogue et du stock
│   ├── PurchaseOrderService.php  # Commandes fournisseurs
│   └── ReportService.php         # Génération des rapports
├── Events/
│   ├── SaleCreated.php
│   ├── SalePaymentRecorded.php   # → listener intégration ledger
│   └── StockUpdated.php
└── Http/
    ├── Controllers/
    │   ├── SaleController.php
    │   ├── ProductController.php
    │   ├── ReportController.php
    │   └── PurchaseOrderController.php
    ├── Requests/
    │   ├── StoreSaleRequest.php
    │   ├── RecordPaymentRequest.php
    │   └── ...
    └── Resources/
        ├── SaleResource.php
        └── ...

```

### Vues (Blade / Inertia)

- Chaque onglet charge son contenu via Livewire, Inertia, ou AJAX (selon stack choisie).

## Contraintes techniques

- **Isolation tenant** obligatoire (`structure_id` sur toutes les tables).
- **Intégration ledger** via événement/listener découplé (jamais d'appel direct cross-domaine).
- **Arithmétique décimale exacte** : utilisation de `bcmath` pour tous les calculs monétaires (total, encaissé, reste).
- **Ne pas dupliquer** la logique d'encaissement partiel déjà écrite dans `PaymentService` - l'extraire en trait/service partagé (`app/Domain/Finance/Services/PartialPaymentTrait.php` ou `PartialPaymentService.php`).
- **Tests** : Feature tests couvrant les cas nominaux et les edge cases (stock insuffisant, encaissement partiel, annulation avec remise en stock).

## Étapes suggérées (TDD)

1. Confirmation métier obtenue (y compris arbitrage Option A/B et nom du module).
2. Lecture exhaustive de `app/Domain/Store` et `app/Domain/Finance/Services/PaymentService.php`.
3. Extraction de la logique d'encaissement partiel en service/trait partagé.
4. Tests Feature d'abord :
   - Création d'un produit, vente avec encaissement total → stock décrémenté, ledger alimenté.
   - Vente avec encaissement partiel → solde correct, événement déclenché.
   - Vente avec stock insuffisant → alerte mais vente autorisée.
   - Annulation d'une vente → remise en stock.
   - Isolation tenant : les données d'un tenant ne sont pas visibles par un autre.
5. Implémentation selon l'option retenue.
6. `php artisan test --compact --filter=Store` (ou nom choisi).
7. `vendor/bin/pint --dirty --format agent`.

## Critères d'acceptation

- ✅ Une vente (quel que soit le nom retenu) avec encaissement partiel calcule correctement le reste à percevoir, en arithmétique décimale exacte (bcmath).
- ✅ Le stock est correctement décrémenté lors d'une vente et remis à jour en cas d'annulation.
- ✅ Les 4 onglets (Ventes, Rapports, Produits, Réapprovisionnement) sont fonctionnels et permettent une navigation fluide.
- ✅ Les rapports présentent des indicateurs clés (CA, top produits, stocks critiques).
- ✅ Isolation tenant testée.
- ✅ Pas de duplication de la logique d'encaissement partiel déjà existante pour les factures élèves.
- ✅ Les événements `SalePaymentRecorded` et `SaleCreated` déclenchent les listeners appropriés (ledger, notification, etc.).

## Sources et références

- Logiciels de gestion auto-école : Unipresta, Klaxo, GestAuto-École
- Codes Rousseau : éditeur historique de supports pédagogiques
- Gestion de stock et rapports : les logiciels métier intègrent des tableaux de bord pour le suivi des ventes et des stocks