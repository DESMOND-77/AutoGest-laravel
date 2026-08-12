@php
    $features = [
        [
            'title' => 'Gestion des élèves',
            'description' => "Dossiers complets, statut d'inscription, documents et progression pédagogique centralisés pour chaque élève.",
            'icon' => '<svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" /></svg>',
        ],
        [
            'title' => 'Gestion des moniteurs',
            'description' => 'Profils, disponibilités hebdomadaires et affectation aux élèves pour un encadrement pédagogique fluide.',
            'icon' => '<svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M17.982 18.725A7.488 7.488 0 0 0 12 15.75a7.488 7.488 0 0 0-5.982 2.975m11.964 0a9 9 0 1 0-11.964 0m11.964 0A8.966 8.966 0 0 1 12 21a8.966 8.966 0 0 1-5.982-2.275M15 9.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>',
        ],
        [
            'title' => 'Gestion de la flotte',
            'description' => "Suivi des véhicules, entretiens, carburant et alertes automatiques avant expiration du contrôle technique.",
            'icon' => '<svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 0 0-3.213-9.193 2.056 2.056 0 0 0-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 0 0-10.026 0 1.106 1.106 0 0 0-.987 1.106v7.635m12-6.677v6.677m0 0h-12" /></svg>',
        ],
        [
            'title' => 'Gestion financière',
            'description' => 'Forfaits, factures, paiements partiels ou soldés et journal comptable, sans double saisie.',
            'icon' => '<svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>',
        ],
        [
            'title' => 'Vente de livres et accessoires',
            'description' => 'Catalogue, fournisseurs et commandes de codes Rousseau et accessoires intégrés à la facturation.',
            'icon' => '<svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25" /></svg>',
        ],
        [
            'title' => 'Examens et planning',
            'description' => 'Planning interactif sans conflit de créneau (moniteur et véhicule) et suivi des résultats aux examens.',
            'icon' => '<svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5m-9-6 2.25 2.25 4.5-4.5" /></svg>',
        ],
        [
            'title' => 'Reporting et statistiques',
            'description' => "Tableau de bord en temps réel : chiffre d'affaires, taux de réussite, dossiers incomplets, alertes flotte.",
            'icon' => '<svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" /></svg>',
        ],
        [
            'title' => 'Notifications',
            'description' => 'Rappels de rendez-vous, alertes de paiement et de planning envoyés automatiquement au bon rôle.',
            'icon' => '<svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" /></svg>',
        ],
    ];

    $whyUs = [
        [
            'title' => 'Gain de temps',
            'description' => 'Moins de ressaisie, moins de paperasse : ce qui prenait une matinée se règle en quelques clics.',
            'icon' => '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>',
        ],
        [
            'title' => 'Automatisation',
            'description' => "Facturation, alertes véhicules et notifications se déclenchent seules, sans intervention manuelle.",
            'icon' => '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5 9.75 3l-.75 6.75h6.75l-6 10.5.75-6.75H3.75Z" /></svg>',
        ],
        [
            'title' => 'Centralisation des données',
            'description' => "Élèves, moniteurs, véhicules, finances : une seule source de vérité, accessible à tous les rôles habilités.",
            'icon' => '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375m16.5 5.625c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125" /></svg>',
        ],
        [
            'title' => 'Sécurité',
            'description' => "Mots de passe chiffrés, autorisations par rôle et par établissement vérifiées à chaque action.",
            'icon' => '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" /></svg>',
        ],
        [
            'title' => 'Multi-établissement',
            'description' => "Chaque auto-école dispose de son propre espace isolé, sur une même plateforme mutualisée.",
            'icon' => '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6 21v-3.375c0-.621.504-1.125 1.125-1.125h1.5c.621 0 1.125.504 1.125 1.125V21" /></svg>',
        ],
        [
            'title' => 'Hébergement Cloud',
            'description' => "Accessible depuis un ordinateur, une tablette ou un smartphone, sans installation ni maintenance serveur.",
            'icon' => '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15a4.5 4.5 0 0 0 4.5 4.5H18a3.75 3.75 0 0 0 1.332-7.257 3 3 0 0 0-3.758-3.848 5.25 5.25 0 0 0-10.233 2.33A4.502 4.502 0 0 0 2.25 15Z" /></svg>',
        ],
    ];

    $steps = [
        ['title' => 'Inscription', 'description' => "Le gérant crée un compte et décrit son établissement en quelques minutes."],
        ['title' => "Création de l'auto-école", 'description' => "Un espace dédié et isolé est provisionné pour la structure."],
        ['title' => 'Configuration', 'description' => "Forfaits, moniteurs, véhicules et identité de l'établissement sont paramétrés."],
        ['title' => 'Utilisation quotidienne', 'description' => "Élèves, planning, paiements et examens se gèrent depuis un seul tableau de bord."],
    ];

    $pricing = [
        [
            'name' => 'Essentiel',
            'price' => '29 000',
            'period' => 'FCFA / mois',
            'description' => 'Pour une auto-école qui démarre sa transition numérique.',
            'features' => ['Jusqu\'à 100 élèves actifs', '2 comptes moniteur', 'Planning et facturation', 'Gestion de flotte', 'Support par e-mail'],
            'cta' => 'Commencer',
            'featured' => false,
        ],
        [
            'name' => 'Pro',
            'price' => '59 000',
            'period' => 'FCFA / mois',
            'description' => "Pour un établissement établi qui veut tout centraliser.",
            'features' => ['Élèves illimités', 'Moniteurs illimités', 'Boutique et CRM intégrés', 'Rapports et exports CSV', 'Notifications automatiques', 'Support prioritaire'],
            'cta' => "Démarrer l'essai gratuit",
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
    ];

    $faqs = [
        [
            'question' => "Combien de temps faut-il pour mettre en place mon auto-école ?",
            'answer' => "La création de votre espace est immédiate après inscription. La configuration initiale (moniteurs, véhicules, forfaits) prend généralement moins d'une heure.",
        ],
        [
            'question' => "Mes données sont-elles visibles par les autres établissements ?",
            'answer' => "Non. Chaque établissement dispose d'un espace strictement isolé : aucune donnée n'est jamais partagée ni visible entre deux auto-écoles de la plateforme.",
        ],
        [
            'question' => "Puis-je essayer la plateforme avant de m'engager ?",
            'answer' => "Oui, l'inscription donne accès à un essai gratuit sans engagement. Vous pouvez également demander une démonstration personnalisée avec notre équipe.",
        ],
        [
            'question' => "La plateforme fonctionne-t-elle sur mobile et tablette ?",
            'answer' => "Oui, l'interface est entièrement responsive et s'adapte à tout écran, du smartphone du moniteur sur le terrain à l'ordinateur du bureau.",
        ],
        [
            'question' => "Que se passe-t-il si je change de forfait en cours d'utilisation ?",
            'answer' => "Vous pouvez changer de forfait à tout moment depuis votre espace d'administration ; le changement prend effet dès la période suivante.",
        ],
    ];
@endphp

<!DOCTYPE html>
<html lang="fr" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0E1526">

    <title>{{ config('app.name', 'Auto-GestBoard') }} — Le logiciel tout-en-un pour auto-écoles professionnelles</title>
    <meta name="description" content="Auto-GestBoard centralise la gestion de votre auto-école : élèves, moniteurs, planning, facturation, flotte et examens, dans une seule plateforme Cloud sécurisée et multi-établissement.">
    <meta name="keywords" content="logiciel auto-école, gestion auto-école, SaaS auto-école, planning moniteur, facturation auto-école">
    <link rel="canonical" href="{{ url('/') }}">

    <meta property="og:type" content="website">
    <meta property="og:title" content="{{ config('app.name', 'Auto-GestBoard') }} — Le logiciel tout-en-un pour auto-écoles">
    <meta property="og:description" content="Élèves, moniteurs, planning, facturation, flotte et examens : toute votre auto-école dans une seule plateforme.">
    <meta property="og:image" content="{{ asset('images/logo.png') }}">
    <meta property="og:url" content="{{ url('/') }}">
    <meta name="twitter:card" content="summary">

    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700|barlow-condensed:500,600,700|ibm-plex-mono:500" rel="stylesheet" />

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
</head>
<body class="bg-cream font-sans text-ink antialiased [scrollbar-gutter:stable]">

    <a href="#contenu" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-[100] focus:rounded-lg focus:bg-route focus:px-4 focus:py-2 focus:text-sm focus:font-medium focus:text-white">
        Aller au contenu
    </a>

    {{-- ============ NAVIGATION ============ --}}
    <header x-data="{ mobileOpen: false }" class="sticky top-0 z-50 border-b border-white/5 bg-asphalt/95 backdrop-blur">
        <nav class="mx-auto flex max-w-7xl items-center justify-between px-6 py-3 lg:px-8" aria-label="Navigation principale">
            <a href="/" class="flex items-center gap-2.5">
                <img src="{{ asset('images/logo.png') }}" alt="Auto-GestBoard" class="h-9 w-auto">
                <span class="font-display text-lg font-semibold uppercase tracking-wide text-white">Auto-GestBoard</span>
            </a>

            <div class="hidden items-center gap-8 lg:flex">
                <a href="#fonctionnalites" class="text-sm font-medium text-white/70 transition hover:text-white">Fonctionnalités</a>
                <a href="#fonctionnement" class="text-sm font-medium text-white/70 transition hover:text-white">Fonctionnement</a>
                <a href="#tarifs" class="text-sm font-medium text-white/70 transition hover:text-white">Tarifs</a>
                <a href="#faq" class="text-sm font-medium text-white/70 transition hover:text-white">FAQ</a>
            </div>

            <div class="hidden items-center gap-3 lg:flex">
                <a href="{{ route('login') }}" class="text-sm font-medium text-white/80 transition hover:text-white">
                    Connexion
                </a>
                <a href="{{ route('register') }}" class="inline-flex items-center rounded-lg bg-signal-600 px-4 py-2 font-display text-sm font-semibold uppercase tracking-wide text-white transition hover:bg-signal-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white">
                    Essai gratuit
                </a>
            </div>

            <button
                type="button"
                @click="mobileOpen = !mobileOpen"
                :aria-expanded="mobileOpen.toString()"
                aria-controls="menu-mobile"
                class="inline-flex items-center justify-center rounded-lg p-2 text-white lg:hidden"
            >
                <span class="sr-only">Ouvrir le menu</span>
                <svg x-show="!mobileOpen" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5m-16.5 5.25h16.5m-16.5 5.25h16.5" /></svg>
                <svg x-show="mobileOpen" x-cloak class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
            </button>
        </nav>

        <div id="menu-mobile" x-show="mobileOpen" x-cloak class="border-t border-white/10 px-6 pb-6 pt-2 lg:hidden">
            <div class="flex flex-col gap-4">
                <a href="#fonctionnalites" @click="mobileOpen = false" class="text-sm font-medium text-white/80">Fonctionnalités</a>
                <a href="#fonctionnement" @click="mobileOpen = false" class="text-sm font-medium text-white/80">Fonctionnement</a>
                <a href="#tarifs" @click="mobileOpen = false" class="text-sm font-medium text-white/80">Tarifs</a>
                <a href="#faq" @click="mobileOpen = false" class="text-sm font-medium text-white/80">FAQ</a>
                <hr class="border-white/10">
                <a href="{{ route('login') }}" class="text-sm font-medium text-white/80">Connexion</a>
                <a href="{{ route('register') }}" class="inline-flex w-fit items-center rounded-lg bg-signal-600 px-4 py-2 font-display text-sm font-semibold uppercase tracking-wide text-white transition hover:bg-signal-700">
                    Essai gratuit
                </a>
            </div>
        </div>
    </header>

    <main id="contenu">

        {{-- ============ HERO ============ --}}
        <section class="relative overflow-hidden bg-asphalt">
            {{-- Signature motif: converging route lines --}}
            <svg class="pointer-events-none absolute inset-0 h-full w-full opacity-40" preserveAspectRatio="none" aria-hidden="true">
                <line x1="-10%" y1="15%" x2="110%" y2="8%" stroke="#F2790A" stroke-width="1.5" stroke-dasharray="10 10" class="motion-safe:animate-dash-drift" />
                <line x1="-10%" y1="55%" x2="110%" y2="50%" stroke="#1E40AF" stroke-width="1.5" stroke-dasharray="10 10" class="motion-safe:animate-dash-drift" />
                <line x1="-10%" y1="92%" x2="110%" y2="98%" stroke="#F2790A" stroke-width="1.5" stroke-dasharray="10 10" class="motion-safe:animate-dash-drift" />
            </svg>

            <div class="relative mx-auto grid max-w-7xl gap-16 px-6 py-20 lg:grid-cols-12 lg:items-center lg:py-32 lg:px-8">
                <div class="lg:col-span-6">
                    <p class="font-display text-sm font-semibold uppercase tracking-[0.25em] text-signal">
                        Logiciel de gestion d'auto-école
                    </p>
                    <h1 class="mt-4 font-display text-4xl font-semibold uppercase leading-[1.05] tracking-tight text-white sm:text-5xl lg:text-6xl">
                        Toute votre auto-école,
                        <span class="text-signal">une seule route</span>
                        à suivre.
                    </h1>
                    <p class="mt-6 max-w-xl text-lg leading-relaxed text-white/70">
                        Élèves, moniteurs, planning, facturation, flotte et examens : Auto-GestBoard centralise chaque étape de votre activité dans une plateforme Cloud pensée pour les auto-écoles professionnelles.
                    </p>

                    <div class="mt-10 flex flex-col gap-4 sm:flex-row">
                        <a href="{{ route('register') }}" class="inline-flex items-center justify-center gap-2 rounded-lg bg-signal-600 px-6 py-3.5 font-display text-sm font-semibold uppercase tracking-wide text-white shadow-lg shadow-signal/20 transition hover:bg-signal-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white">
                            Essai gratuit
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M17.25 8.25 21 12m0 0-3.75 3.75M21 12H3" /></svg>
                        </a>
                        <a href="mailto:contact@auto-gestboard.com?subject=Demande%20de%20d%C3%A9monstration" class="inline-flex items-center justify-center gap-2 rounded-lg border border-white/20 px-6 py-3.5 font-display text-sm font-semibold uppercase tracking-wide text-white transition hover:bg-white/10 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white">
                            Demander une démo
                        </a>
                    </div>

                    <dl class="mt-14 flex max-w-md flex-wrap gap-x-8 gap-y-6 border-t border-white/10 pt-8">
                        <div class="min-w-0">
                            <dt class="text-xs uppercase tracking-wide text-white/50">Espaces dédiés</dt>
                            <dd class="mt-1 font-mono text-xl text-white sm:text-2xl">4</dd>
                        </div>
                        <div class="min-w-0">
                            <dt class="text-xs uppercase tracking-wide text-white/50">Modules intégrés</dt>
                            <dd class="mt-1 font-mono text-xl text-white sm:text-2xl">15</dd>
                        </div>
                        <div class="min-w-0">
                            <dt class="text-xs uppercase tracking-wide text-white/50">Établissements</dt>
                            <dd class="mt-1 font-mono text-xl text-white sm:text-2xl">Illimités</dd>
                        </div>
                    </dl>
                </div>

                {{-- Floating product mockup --}}
                <div class="lg:col-span-6">
                    <div class="relative mx-auto max-w-lg animate-fade-up [animation-delay:150ms]">
                        <div class="absolute -inset-4 rounded-3xl bg-route/20 blur-2xl" aria-hidden="true"></div>
                        <div class="relative overflow-hidden rounded-2xl border border-white/10 bg-asphalt-2 shadow-2xl">
                            <div class="flex items-center gap-1.5 border-b border-white/10 px-4 py-3">
                                <span class="h-2.5 w-2.5 rounded-full bg-white/20"></span>
                                <span class="h-2.5 w-2.5 rounded-full bg-white/20"></span>
                                <span class="h-2.5 w-2.5 rounded-full bg-white/20"></span>
                                <span class="ml-3 font-mono text-xs text-white/40">app.auto-gestboard.com/admin/dashboard</span>
                            </div>
                            <div class="grid grid-cols-3 gap-3 p-5">
                                <div class="col-span-3 grid grid-cols-3 gap-3">
                                    <div class="rounded-lg bg-white/5 p-3">
                                        <p class="text-[10px] uppercase tracking-wide text-white/40">Élèves actifs</p>
                                        <p class="mt-1 font-mono text-lg text-white">128</p>
                                    </div>
                                    <div class="rounded-lg bg-white/5 p-3">
                                        <p class="text-[10px] uppercase tracking-wide text-white/40">Recettes du mois</p>
                                        <p class="mt-1 font-mono text-lg text-signal">1,86M</p>
                                    </div>
                                    <div class="rounded-lg bg-white/5 p-3">
                                        <p class="text-[10px] uppercase tracking-wide text-white/40">Taux de réussite</p>
                                        <p class="mt-1 font-mono text-lg text-white">82%</p>
                                    </div>
                                </div>
                                <div class="col-span-3 mt-1 flex items-end gap-2 rounded-lg bg-white/5 p-4" role="img" aria-label="Graphique illustratif du chiffre d'affaires mensuel">
                                    <div class="w-full rounded-sm bg-route" style="height:2.2rem"></div>
                                    <div class="w-full rounded-sm bg-route" style="height:3.4rem"></div>
                                    <div class="w-full rounded-sm bg-route" style="height:2.8rem"></div>
                                    <div class="w-full rounded-sm bg-signal" style="height:4.5rem"></div>
                                    <div class="w-full rounded-sm bg-route" style="height:3.1rem"></div>
                                    <div class="w-full rounded-sm bg-route" style="height:3.9rem"></div>
                                </div>
                                <div class="col-span-3 space-y-2 rounded-lg bg-white/5 p-3">
                                    <div class="flex items-center justify-between text-xs">
                                        <span class="text-white/60">M. NGUEMA — Permis B</span>
                                        <span class="rounded-full bg-route/20 px-2 py-0.5 text-[10px] font-medium text-route-100">Cours pratique</span>
                                    </div>
                                    <div class="flex items-center justify-between text-xs">
                                        <span class="text-white/60">S. OBAME — Permis B</span>
                                        <span class="rounded-full bg-signal/20 px-2 py-0.5 text-[10px] font-medium text-signal-600">Examen prévu</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- ============ FONCTIONNALITÉS ============ --}}
        <section id="fonctionnalites" aria-labelledby="fonctionnalites-titre" class="mx-auto max-w-7xl px-6 py-24 lg:px-8">
            <div class="max-w-2xl">
                <p class="font-display text-sm font-semibold uppercase tracking-[0.25em] text-route">Fonctionnalités</p>
                <h2 id="fonctionnalites-titre" class="mt-3 font-display text-3xl font-semibold uppercase tracking-tight text-ink sm:text-4xl">
                    Tout ce qu'il faut pour piloter votre auto-école
                </h2>
                <p class="mt-4 text-lg leading-relaxed text-slate">
                    Huit modules pensés pour couvrir l'intégralité de votre activité quotidienne, du premier contact avec l'élève jusqu'à l'obtention de son permis.
                </p>
            </div>

            <div class="mt-14 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($features as $feature)
                    <x-landing.feature-card :title="$feature['title']" :description="$feature['description']">
                        <x-slot:icon>{!! $feature['icon'] !!}</x-slot:icon>
                    </x-landing.feature-card>
                @endforeach
            </div>
        </section>

        {{-- ============ POURQUOI NOUS CHOISIR ============ --}}
        <section aria-labelledby="pourquoi-titre" class="border-y border-line bg-route-50/60">
            <div class="mx-auto max-w-7xl px-6 py-24 lg:px-8">
                <div class="max-w-2xl">
                    <p class="font-display text-sm font-semibold uppercase tracking-[0.25em] text-route">Pourquoi choisir Auto-GestBoard</p>
                    <h2 id="pourquoi-titre" class="mt-3 font-display text-3xl font-semibold uppercase tracking-tight text-ink sm:text-4xl">
                        Une plateforme conçue pour la réalité du terrain
                    </h2>
                </div>

                <div class="mt-14 grid gap-x-10 gap-y-10 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($whyUs as $item)
                        <x-landing.why-card :title="$item['title']" :description="$item['description']">
                            <x-slot:icon>{!! $item['icon'] !!}</x-slot:icon>
                        </x-landing.why-card>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- ============ FONCTIONNEMENT ============ --}}
        <section id="fonctionnement" aria-labelledby="fonctionnement-titre" class="bg-asphalt">
            <div class="mx-auto max-w-7xl px-6 py-24 lg:px-8">
                <div class="max-w-2xl">
                    <p class="font-display text-sm font-semibold uppercase tracking-[0.25em] text-signal">Fonctionnement</p>
                    <h2 id="fonctionnement-titre" class="mt-3 font-display text-3xl font-semibold uppercase tracking-tight text-white sm:text-4xl">
                        De l'inscription à l'usage quotidien, en quatre étapes
                    </h2>
                </div>

                <div class="mt-16 flex flex-col gap-12 md:flex-row md:gap-8">
                    @foreach ($steps as $index => $step)
                        <x-landing.route-step
                            :number="sprintf('%02d', $index + 1)"
                            :title="$step['title']"
                            :description="$step['description']"
                            :last="$loop->last"
                        />
                    @endforeach
                </div>
            </div>
        </section>

        {{-- ============ APERÇU DE LA PLATEFORME ============ --}}
        <section aria-labelledby="apercu-titre" class="mx-auto max-w-7xl px-6 py-24 lg:px-8">
            <div class="mx-auto max-w-2xl text-center">
                <p class="font-display text-sm font-semibold uppercase tracking-[0.25em] text-route">Aperçu</p>
                <h2 id="apercu-titre" class="mt-3 font-display text-3xl font-semibold uppercase tracking-tight text-ink sm:text-4xl">
                    Un tableau de bord pensé pour décider vite
                </h2>
                <p class="mt-4 text-lg leading-relaxed text-slate">
                    Chiffre d'affaires, dossiers à traiter, alertes véhicules et planning du jour : l'essentiel visible en un coup d'œil, dès la connexion.
                </p>
            </div>

            <div class="relative mx-auto mt-14 max-w-5xl">
                <div class="overflow-hidden rounded-2xl border border-line bg-paper shadow-[0_30px_80px_-30px_rgba(11,18,32,0.25)]">
                    <div class="flex items-center gap-1.5 border-b border-line bg-cream px-5 py-3">
                        <span class="h-2.5 w-2.5 rounded-full bg-line"></span>
                        <span class="h-2.5 w-2.5 rounded-full bg-line"></span>
                        <span class="h-2.5 w-2.5 rounded-full bg-line"></span>
                        <span class="ml-3 font-mono text-xs text-slate-light">app.auto-gestboard.com</span>
                    </div>
                    <div class="grid grid-cols-1 gap-6 p-8 sm:grid-cols-4">
                        <aside class="hidden flex-col gap-1 border-r border-line pr-6 sm:flex" aria-hidden="true">
                            <span class="rounded-lg bg-route px-3 py-2 text-xs font-medium text-white">Tableau de bord</span>
                            <span class="px-3 py-2 text-xs font-medium text-slate">Élèves</span>
                            <span class="px-3 py-2 text-xs font-medium text-slate">Planning</span>
                            <span class="px-3 py-2 text-xs font-medium text-slate">Facturation</span>
                            <span class="px-3 py-2 text-xs font-medium text-slate">Flotte</span>
                        </aside>
                        <div class="sm:col-span-3">
                            <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                                <div class="rounded-xl border border-line p-4">
                                    <p class="text-[11px] uppercase tracking-wide text-slate-light">Élèves</p>
                                    <p class="mt-1 font-mono text-xl text-ink">128</p>
                                </div>
                                <div class="rounded-xl border border-line p-4">
                                    <p class="text-[11px] uppercase tracking-wide text-slate-light">Séances / jour</p>
                                    <p class="mt-1 font-mono text-xl text-ink">14</p>
                                </div>
                                <div class="rounded-xl border border-line p-4">
                                    <p class="text-[11px] uppercase tracking-wide text-slate-light">Réussite examens</p>
                                    <p class="mt-1 font-mono text-xl text-route">82%</p>
                                </div>
                                <div class="rounded-xl border border-signal/40 bg-signal/5 p-4">
                                    <p class="text-[11px] uppercase tracking-wide text-signal-600">Alertes flotte</p>
                                    <p class="mt-1 font-mono text-xl text-signal-600">2</p>
                                </div>
                            </div>
                            <div class="mt-4 space-y-2 rounded-xl border border-line p-4">
                                <p class="text-[11px] uppercase tracking-wide text-slate-light">Planning du jour</p>
                                <div class="flex items-center justify-between border-b border-line py-2 text-sm">
                                    <span class="text-ink">08:00 — Code théorique</span>
                                    <span class="text-slate-light">Salle A</span>
                                </div>
                                <div class="flex items-center justify-between border-b border-line py-2 text-sm">
                                    <span class="text-ink">09:30 — Conduite, M. NGUEMA</span>
                                    <span class="text-slate-light">Véhicule GX-204</span>
                                </div>
                                <div class="flex items-center justify-between py-2 text-sm">
                                    <span class="text-ink">11:00 — Examen blanc</span>
                                    <span class="text-slate-light">Circuit sud</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- ============ TÉMOIGNAGES ============ --}}
        {{-- TODO: Remplacer par de véritables témoignages clients avant mise en production. --}}
        <section aria-labelledby="temoignages-titre" class="border-y border-line bg-cream">
            <div class="mx-auto max-w-7xl px-6 py-24 lg:px-8">
                <div class="max-w-2xl">
                    <p class="font-display text-sm font-semibold uppercase tracking-[0.25em] text-route">Témoignages</p>
                    <h2 id="temoignages-titre" class="mt-3 font-display text-3xl font-semibold uppercase tracking-tight text-ink sm:text-4xl">
                        Ce que nos futurs utilisateurs en attendent
                    </h2>
                </div>

                <div class="mt-14 grid gap-6 lg:grid-cols-3">
                    <figure class="flex flex-col justify-between rounded-2xl border border-line bg-paper p-8">
                        <blockquote class="text-lg leading-relaxed text-ink">
                            « Ce qui nous manquait, c'était une vue unique sur les paiements et le planning. Pouvoir enfin croiser les deux nous ferait gagner un temps précieux chaque semaine. »
                        </blockquote>
                        <figcaption class="mt-6 flex items-center gap-3">
                            <span class="flex h-10 w-10 items-center justify-center rounded-full bg-route-50 font-display text-sm font-semibold text-route">GM</span>
                            <span>
                                <span class="block text-sm font-semibold text-ink">Gérante d'auto-école</span>
                                <span class="block text-xs text-slate-light">Libreville, Gabon</span>
                            </span>
                        </figcaption>
                    </figure>
                    <figure class="flex flex-col justify-between rounded-2xl border border-line bg-paper p-8">
                        <blockquote class="text-lg leading-relaxed text-ink">
                            « Suivre les entretiens de nos véhicules à la main nous a déjà coûté un contrôle technique expiré. Une alerte automatique, c'est exactement ce qu'il nous fallait. »
                        </blockquote>
                        <figcaption class="mt-6 flex items-center gap-3">
                            <span class="flex h-10 w-10 items-center justify-center rounded-full bg-route-50 font-display text-sm font-semibold text-route">RE</span>
                            <span>
                                <span class="block text-sm font-semibold text-ink">Responsable de flotte</span>
                                <span class="block text-xs text-slate-light">Port-Gentil, Gabon</span>
                            </span>
                        </figcaption>
                    </figure>
                    <figure class="flex flex-col justify-between rounded-2xl border border-line bg-paper p-8">
                        <blockquote class="text-lg leading-relaxed text-ink">
                            « Chaque moniteur a besoin de voir uniquement ses élèves du jour, sans fouiller dans des cahiers. Un accès mobile clair changerait vraiment notre organisation. »
                        </blockquote>
                        <figcaption class="mt-6 flex items-center gap-3">
                            <span class="flex h-10 w-10 items-center justify-center rounded-full bg-route-50 font-display text-sm font-semibold text-route">MB</span>
                            <span>
                                <span class="block text-sm font-semibold text-ink">Moniteur indépendant</span>
                                <span class="block text-xs text-slate-light">Franceville, Gabon</span>
                            </span>
                        </figcaption>
                    </figure>
                </div>
            </div>
        </section>

        {{-- ============ TARIFICATION ============ --}}
        <section id="tarifs" aria-labelledby="tarifs-titre" class="mx-auto max-w-7xl px-6 py-24 lg:px-8">
            <div class="mx-auto max-w-2xl text-center">
                <p class="font-display text-sm font-semibold uppercase tracking-[0.25em] text-route">Tarification</p>
                <h2 id="tarifs-titre" class="mt-3 font-display text-3xl font-semibold uppercase tracking-tight text-ink sm:text-4xl">
                    Un tarif simple, qui grandit avec vous
                </h2>
                <p class="mt-4 text-lg leading-relaxed text-slate">
                    Sans engagement, sans frais cachés. Changez de forfait à tout moment.
                </p>
            </div>

            <div class="mt-14 grid gap-8 lg:grid-cols-3">
                @foreach ($pricing as $plan)
                    <x-landing.pricing-card
                        :name="$plan['name']"
                        :price="$plan['price']"
                        :period="$plan['period']"
                        :description="$plan['description']"
                        :features="$plan['features']"
                        :cta="$plan['cta']"
                        :featured="$plan['featured']"
                        :href="$plan['featured'] || $plan['cta'] === 'Commencer' ? route('register') : 'mailto:contact@auto-gestboard.com?subject=Demande%20de%20d%C3%A9monstration'"
                    />
                @endforeach
            </div>

            {{-- Comparatif des fonctionnalités --}}
            <div class="mt-16 overflow-x-auto rounded-2xl border border-line">
                <table class="w-full min-w-[640px] border-collapse text-left text-sm">
                    <caption class="sr-only">Comparaison détaillée des fonctionnalités par forfait</caption>
                    <thead>
                        <tr class="border-b border-line bg-cream">
                            <th scope="col" class="px-6 py-4 font-display text-xs font-semibold uppercase tracking-wide text-slate">Fonctionnalité</th>
                            <th scope="col" class="px-6 py-4 font-display text-xs font-semibold uppercase tracking-wide text-slate">Essentiel</th>
                            <th scope="col" class="px-6 py-4 font-display text-xs font-semibold uppercase tracking-wide text-route">Pro</th>
                            <th scope="col" class="px-6 py-4 font-display text-xs font-semibold uppercase tracking-wide text-slate">Réseau</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        @foreach ([
                            ['Élèves actifs', '100 max', 'Illimité', 'Illimité'],
                            ['Comptes moniteur', '2', 'Illimité', 'Illimité'],
                            ['Gestion de flotte', true, true, true],
                            ['Boutique et CRM', false, true, true],
                            ['Rapports et exports CSV', false, true, true],
                            ['Établissements multiples', false, false, true],
                            ['Gestionnaire de compte dédié', false, false, true],
                        ] as [$label, $essentiel, $pro, $reseau])
                            <tr>
                                <td class="px-6 py-4 text-ink">{{ $label }}</td>
                                @foreach ([$essentiel, $pro, $reseau] as $value)
                                    <td class="px-6 py-4">
                                        @if (is_bool($value))
                                            @if ($value)
                                                <svg class="h-5 w-5 text-route" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                                                <span class="sr-only">Inclus</span>
                                            @else
                                                <svg class="h-5 w-5 text-line" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                                                <span class="sr-only">Non inclus</span>
                                            @endif
                                        @else
                                            <span class="text-slate">{{ $value }}</span>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        {{-- ============ FAQ ============ --}}
        <section id="faq" aria-labelledby="faq-titre" class="border-y border-line bg-cream">
            <div class="mx-auto max-w-4xl px-6 py-24 lg:px-8">
                <div class="max-w-2xl">
                    <p class="font-display text-sm font-semibold uppercase tracking-[0.25em] text-route">Questions fréquentes</p>
                    <h2 id="faq-titre" class="mt-3 font-display text-3xl font-semibold uppercase tracking-tight text-ink sm:text-4xl">
                        Tout ce que vous vous demandez encore
                    </h2>
                </div>

                <div class="mt-12">
                    @foreach ($faqs as $faq)
                        <x-landing.faq-item :question="$faq['question']" :answer="$faq['answer']" />
                    @endforeach
                </div>
            </div>
        </section>

        {{-- ============ APPEL À L'ACTION FINAL ============ --}}
        <section class="relative overflow-hidden bg-asphalt">
            <svg class="pointer-events-none absolute inset-0 h-full w-full opacity-30" preserveAspectRatio="none" aria-hidden="true">
                <line x1="-10%" y1="30%" x2="110%" y2="20%" stroke="#F2790A" stroke-width="1.5" stroke-dasharray="10 10" class="motion-safe:animate-dash-drift" />
                <line x1="-10%" y1="75%" x2="110%" y2="85%" stroke="#1E40AF" stroke-width="1.5" stroke-dasharray="10 10" class="motion-safe:animate-dash-drift" />
            </svg>
            <div class="relative mx-auto max-w-4xl px-6 py-24 text-center lg:px-8">
                <h2 class="font-display text-3xl font-semibold uppercase tracking-tight text-white sm:text-4xl">
                    Prêt à reprendre la main sur votre auto-école ?
                </h2>
                <p class="mx-auto mt-4 max-w-xl text-lg leading-relaxed text-white/70">
                    Créez votre espace en quelques minutes, sans carte bancaire. Notre équipe reste disponible pour vous accompagner à chaque étape.
                </p>
                <div class="mt-10 flex flex-col justify-center gap-4 sm:flex-row">
                    <a href="{{ route('register') }}" class="inline-flex items-center justify-center gap-2 rounded-lg bg-signal-600 px-6 py-3.5 font-display text-sm font-semibold uppercase tracking-wide text-white transition hover:bg-signal-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white">
                        Essai gratuit
                    </a>
                    <a href="mailto:contact@auto-gestboard.com?subject=Demande%20de%20d%C3%A9monstration" class="inline-flex items-center justify-center gap-2 rounded-lg border border-white/20 px-6 py-3.5 font-display text-sm font-semibold uppercase tracking-wide text-white transition hover:bg-white/10 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white">
                        Demander une démo
                    </a>
                </div>
            </div>
        </section>
    </main>

    {{-- ============ FOOTER ============ --}}
    <footer class="bg-asphalt-2 text-white/70">
        <div class="mx-auto max-w-7xl px-6 py-16 lg:px-8">
            <div class="grid gap-12 md:grid-cols-2 lg:grid-cols-5">
                <div class="lg:col-span-2">
                    <a href="/" class="flex items-center gap-2.5">
                        <img src="{{ asset('images/logo.png') }}" alt="Auto-GestBoard" class="h-9 w-auto">
                        <span class="font-display text-lg font-semibold uppercase tracking-wide text-white">Auto-GestBoard</span>
                    </a>
                    <p class="mt-4 max-w-sm text-sm leading-relaxed">
                        La plateforme Cloud multi-établissement qui centralise la gestion des élèves, moniteurs, planning et finances de votre auto-école.
                    </p>
                    <ul class="mt-6 flex items-center gap-4" aria-label="Réseaux sociaux">
                        {{-- TODO: Renseigner les URL réelles des réseaux sociaux avant mise en production. --}}
                        <li>
                            <a href="#" class="flex h-9 w-9 items-center justify-center rounded-full border border-white/15 transition hover:border-white/40 hover:text-white" aria-label="Auto-GestBoard sur Facebook">
                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M22 12.06C22 6.505 17.523 2 12 2S2 6.505 2 12.06c0 5.02 3.657 9.184 8.438 9.94v-7.03H7.898v-2.91h2.54V9.845c0-2.507 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562v1.877h2.773l-.443 2.91h-2.33V22c4.78-.756 8.437-4.92 8.437-9.94Z"/></svg>
                            </a>
                        </li>
                        <li>
                            <a href="#" class="flex h-9 w-9 items-center justify-center rounded-full border border-white/15 transition hover:border-white/40 hover:text-white" aria-label="Auto-GestBoard sur LinkedIn">
                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.049c.476-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286ZM5.337 7.433a2.062 2.062 0 1 1 0-4.124 2.062 2.062 0 0 1 0 4.124ZM7.114 20.452H3.558V9h3.556v11.452Z"/></svg>
                            </a>
                        </li>
                        <li>
                            <a href="#" class="flex h-9 w-9 items-center justify-center rounded-full border border-white/15 transition hover:border-white/40 hover:text-white" aria-label="Auto-GestBoard sur WhatsApp">
                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M12.04 2c-5.52 0-10 4.48-10 10 0 1.77.46 3.43 1.26 4.87L2 22l5.27-1.28A9.96 9.96 0 0 0 12.04 22c5.52 0 10-4.48 10-10s-4.48-10-10-10Zm5.78 14.24c-.24.68-1.4 1.3-1.93 1.36-.5.06-1.02.26-3.4-.72-2.87-1.18-4.7-4.08-4.85-4.27-.14-.19-1.16-1.55-1.16-2.96 0-1.4.73-2.09 1-2.38.24-.26.53-.32.7-.32h.5c.16 0 .38-.06.6.46.24.58.8 2 .87 2.14.07.14.11.3.02.49-.09.19-.14.3-.28.46-.14.16-.29.36-.42.48-.14.14-.28.29-.12.57.16.28.72 1.19 1.55 1.93 1.06.95 1.96 1.24 2.24 1.38.28.14.44.12.6-.07.16-.19.7-.82.89-1.1.19-.28.37-.23.61-.14.24.09 1.55.73 1.82.87.27.14.44.2.51.32.07.12.07.68-.17 1.35Z"/></svg>
                            </a>
                        </li>
                    </ul>
                </div>

                <nav aria-label="Produit">
                    <h3 class="font-display text-xs font-semibold uppercase tracking-widest text-white">Produit</h3>
                    <ul class="mt-4 space-y-3 text-sm">
                        <li><a href="#fonctionnalites" class="transition hover:text-white">Fonctionnalités</a></li>
                        <li><a href="#tarifs" class="transition hover:text-white">Tarifs</a></li>
                        <li><a href="#fonctionnement" class="transition hover:text-white">Fonctionnement</a></li>
                        <li><a href="{{ route('register') }}" class="transition hover:text-white">Essai gratuit</a></li>
                    </ul>
                </nav>

                <nav aria-label="Ressources">
                    <h3 class="font-display text-xs font-semibold uppercase tracking-widest text-white">Ressources</h3>
                    <ul class="mt-4 space-y-3 text-sm">
                        {{-- TODO: Remplacer par les URL du centre d'aide public une fois publié. --}}
                        <li><a href="#" class="transition hover:text-white">Documentation</a></li>
                        <li><a href="#faq" class="transition hover:text-white">FAQ</a></li>
                        <li><a href="mailto:contact@auto-gestboard.com?subject=Demande%20de%20d%C3%A9monstration" class="transition hover:text-white">Demander une démo</a></li>
                    </ul>
                </nav>

                <nav aria-label="Entreprise">
                    <h3 class="font-display text-xs font-semibold uppercase tracking-widest text-white">Contact</h3>
                    <ul class="mt-4 space-y-3 text-sm">
                        <li><a href="mailto:contact@auto-gestboard.com" class="transition hover:text-white">contact@auto-gestboard.com</a></li>
                        <li class="text-white/50">Libreville, Gabon</li>
                    </ul>
                </nav>
            </div>

            <div class="mt-14 flex flex-col items-center justify-between gap-4 border-t border-white/10 pt-8 text-xs text-white/50 sm:flex-row">
                <p>&copy; {{ now()->year }} Auto-GestBoard. Tous droits réservés.</p>
                {{-- TODO: Publier de véritables pages Mentions légales / CGU / Politique de confidentialité et lier ci-dessous. --}}
                <div class="flex items-center gap-6">
                    <a href="#" class="transition hover:text-white">Mentions légales</a>
                    <a href="#" class="transition hover:text-white">Confidentialité</a>
                    <a href="#" class="transition hover:text-white">CGU</a>
                </div>
            </div>
        </div>
    </footer>

</body>
</html>
