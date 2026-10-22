<?php

/**
 * Contenu commercial de la page publique, extrait de la landing existante
 * (aucun chiffre, prix, témoignage ni intégration n'est inventé ici).
 * Les valeurs absentes restent vides et la section correspondante est masquée.
 */
return [

    'hero' => [
        'eyebrow' => 'Logiciel de gestion d’auto-école',
        'title' => 'Toute votre auto-école, une seule route à suivre.',
        'subtitle' => 'Élèves, moniteurs, planning, facturation, flotte et examens : Auto-GestBoard centralise chaque étape de votre activité dans une plateforme Cloud pensée pour les auto-écoles professionnelles.',
        'primary_cta' => 'Créer mon établissement',
        'secondary_cta' => 'Demander une démo',
        'stats' => [
            ['value' => '15', 'label' => 'Étapes de parcours suivies'],
            ['value' => '8', 'label' => 'Modules intégrés'],
            ['value' => '4', 'label' => 'Rôles dédiés'],
        ],
    ],

    'modules' => [
        ['title' => 'Gestion des élèves', 'icon' => 'graduation-cap', 'description' => 'Dossiers complets, statut d’inscription, documents et progression pédagogique centralisés pour chaque élève.'],
        ['title' => 'Gestion des moniteurs', 'icon' => 'users', 'description' => 'Profils, disponibilités hebdomadaires et affectation aux élèves pour un encadrement pédagogique fluide.'],
        ['title' => 'Gestion de la flotte', 'icon' => 'car', 'description' => 'Suivi des véhicules, entretiens, carburant et alertes automatiques avant expiration du contrôle technique.'],
        ['title' => 'Gestion financière', 'icon' => 'wallet', 'description' => 'Forfaits, factures, paiements partiels ou soldés et journal comptable, sans double saisie.'],
        ['title' => 'Vente de livres et accessoires', 'icon' => 'shopping-bag', 'description' => 'Catalogue, fournisseurs et commandes de codes Rousseau et accessoires intégrés à la facturation.'],
        ['title' => 'Examens et planning', 'icon' => 'calendar-days', 'description' => 'Planning interactif sans conflit de créneau (moniteur et véhicule) et suivi des résultats aux examens.'],
        ['title' => 'Reporting et statistiques', 'icon' => 'chart-bar', 'description' => 'Tableau de bord en temps réel : chiffre d’affaires, taux de réussite, dossiers incomplets, alertes flotte.'],
        ['title' => 'Notifications', 'icon' => 'bell', 'description' => 'Rappels de rendez-vous, alertes de paiement et de planning envoyés automatiquement au bon rôle.'],
    ],

    'audiences' => [
        ['title' => 'Direction', 'icon' => 'building-office', 'description' => 'Pilotage financier, dossiers à valider, planning de toute l’équipe et alertes véhicules, sur un seul écran.'],
        ['title' => 'Moniteur', 'icon' => 'car', 'description' => 'Sa journée dès la connexion, la feuille de route de chaque élève et le marquage de présence en un geste, sur téléphone.'],
        ['title' => 'Élève', 'icon' => 'graduation-cap', 'description' => 'Son parcours en 5 phases, ses prochaines séances, ses paiements et son dossier, sans passer par le secrétariat.'],
    ],

    'why' => [
        ['title' => 'Gain de temps', 'icon' => 'clock', 'description' => 'Moins de ressaisie, moins de paperasse : ce qui prenait une matinée se règle en quelques clics.'],
        ['title' => 'Automatisation', 'icon' => 'zap', 'description' => 'Facturation, alertes véhicules et notifications se déclenchent seules, sans intervention manuelle.'],
        ['title' => 'Centralisation des données', 'icon' => 'folder', 'description' => 'Élèves, moniteurs, véhicules, finances : une seule source de vérité, accessible à tous les rôles habilités.'],
        ['title' => 'Sécurité', 'icon' => 'shield-check', 'description' => 'Mots de passe chiffrés, autorisations par rôle et par établissement vérifiées à chaque action.'],
        ['title' => 'Multi-établissement', 'icon' => 'building-office', 'description' => 'Chaque auto-école dispose de son propre espace isolé, sur une même plateforme mutualisée.'],
        ['title' => 'Hébergement Cloud', 'icon' => 'desktop', 'description' => 'Accessible depuis un ordinateur, une tablette ou un smartphone, sans installation ni maintenance serveur.'],
    ],

    'steps' => [
        ['title' => 'Inscription', 'description' => 'Le gérant crée un compte et décrit son établissement en quelques minutes.'],
        ['title' => 'Création de l’auto-école', 'description' => 'Un espace dédié et isolé est provisionné pour la structure.'],
        ['title' => 'Configuration', 'description' => 'Forfaits, moniteurs, véhicules et identité de l’établissement sont paramétrés.'],
        ['title' => 'Utilisation quotidienne', 'description' => 'Élèves, planning, paiements et examens se gèrent depuis un seul tableau de bord.'],
    ],

    'pricing' => [
        [
            'name' => 'Essentiel',
            'price' => '29 000',
            'period' => 'FCFA / mois',
            'description' => 'Pour une auto-école qui démarre sa transition numérique.',
            'features' => ['Jusqu’à 100 élèves actifs', '2 comptes moniteur', 'Planning et facturation', 'Gestion de flotte', 'Support par e-mail'],
            'cta' => 'Commencer',
            'featured' => false,
        ],
        [
            'name' => 'Pro',
            'price' => '59 000',
            'period' => 'FCFA / mois',
            'description' => 'Pour un établissement établi qui veut tout centraliser.',
            'features' => ['Élèves illimités', 'Moniteurs illimités', 'Boutique et CRM intégrés', 'Rapports et exports CSV', 'Notifications automatiques', 'Support prioritaire'],
            'cta' => 'Démarrer l’essai gratuit',
            'featured' => true,
        ],
        [
            'name' => 'Réseau',
            'price' => 'Sur devis',
            'period' => '',
            'description' => 'Pour un groupe multi-établissements avec besoins spécifiques.',
            'features' => ['Établissements multiples', 'Tableau de bord consolidé', 'Accompagnement à la mise en place', 'Intégrations sur mesure', 'Gestionnaire de compte dédié'],
            'cta' => 'Demander une démo',
            'featured' => false,
        ],
    ],

    'comparison' => [
        ['Élèves actifs', '100 max', 'Illimité', 'Illimité'],
        ['Comptes moniteur', '2', 'Illimité', 'Illimité'],
        ['Gestion de flotte', true, true, true],
        ['Boutique et CRM', false, true, true],
        ['Rapports et exports CSV', false, true, true],
        ['Établissements multiples', false, false, true],
        ['Gestionnaire de compte dédié', false, false, true],
    ],

    'faq' => [
        ['question' => 'Combien de temps faut-il pour mettre en place mon auto-école ?', 'answer' => 'La création de votre espace est immédiate après inscription. La configuration initiale (moniteurs, véhicules, forfaits) prend généralement moins d’une heure.'],
        ['question' => 'Mes données sont-elles visibles par les autres établissements ?', 'answer' => 'Non. Chaque établissement dispose d’un espace strictement isolé : aucune donnée n’est jamais partagée ni visible entre deux auto-écoles de la plateforme.'],
        ['question' => 'Puis-je essayer la plateforme avant de m’engager ?', 'answer' => 'Oui, l’inscription donne accès à un essai gratuit sans engagement. Vous pouvez également demander une démonstration personnalisée avec notre équipe.'],
        ['question' => 'La plateforme fonctionne-t-elle sur mobile et tablette ?', 'answer' => 'Oui, l’interface est entièrement responsive et s’adapte à tout écran, du smartphone du moniteur sur le terrain à l’ordinateur du bureau.'],
        ['question' => 'Que se passe-t-il si je change de forfait en cours d’utilisation ?', 'answer' => 'Vous pouvez changer de forfait à tout moment depuis votre espace d’administration ; le changement prend effet dès la période suivante.'],
    ],

    'contact' => [
        'email' => 'contact@auto-gestboard.com',
        'location' => 'Libreville, Gabon',
    ],
];
