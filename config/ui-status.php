<?php

/**
 * Source unique de vérité pour l'affichage d'un statut métier :
 * libellé français, ton visuel et icône. Aucun mapping enum -> couleur
 * ne doit exister dans une vue (§5.7).
 *
 * tons : neutral | accent | success | warning | danger | info
 * Le libellé de secours reste la méthode label() de l'enum quand elle existe.
 */
return [

    'enums' => [

        App\Domain\Students\Enums\LifecycleStage::class => [
            'prospect' => ['label' => 'Prospect', 'tone' => 'neutral', 'icon' => 'user-plus'],
            'pre_enrollment' => ['label' => 'Pré-inscription', 'tone' => 'info', 'icon' => 'clipboard-list'],
            'enrollment' => ['label' => 'Inscription', 'tone' => 'info', 'icon' => 'file-text'],
            'payment' => ['label' => 'Paiement', 'tone' => 'warning', 'icon' => 'wallet'],
            'dossier_setup' => ['label' => 'Constitution du dossier', 'tone' => 'info', 'icon' => 'folder'],
            'validation' => ['label' => 'Validation du dossier', 'tone' => 'warning', 'icon' => 'clipboard-check'],
            'theory_course' => ['label' => 'Cours théorique', 'tone' => 'accent', 'icon' => 'book-open'],
            'mock_exams' => ['label' => 'Examens blancs', 'tone' => 'accent', 'icon' => 'pencil-ruler'],
            'code_obtained' => ['label' => 'Code obtenu', 'tone' => 'success', 'icon' => 'badge-check'],
            'practical_course' => ['label' => 'Cours pratique', 'tone' => 'accent', 'icon' => 'car'],
            'continuous_evaluation' => ['label' => 'Évaluation continue', 'tone' => 'accent', 'icon' => 'gauge'],
            'ready_for_exam' => ['label' => 'Prêt pour l\'examen', 'tone' => 'info', 'icon' => 'flag'],
            'practical_exam' => ['label' => 'Examen pratique', 'tone' => 'warning', 'icon' => 'clipboard-check'],
            'license_obtained' => ['label' => 'Permis obtenu', 'tone' => 'success', 'icon' => 'trophy'],
            'former_student' => ['label' => 'Ancien élève', 'tone' => 'neutral', 'icon' => 'archive-box'],
        ],

        App\Domain\Students\Enums\DossierStatus::class => [
            'incomplete' => ['label' => 'Incomplet', 'tone' => 'warning', 'icon' => 'circle-alert'],
            'submitted' => ['label' => 'Soumis', 'tone' => 'info', 'icon' => 'inbox'],
            'complete' => ['label' => 'Complet', 'tone' => 'success', 'icon' => 'circle-check'],
            'validated' => ['label' => 'Validé', 'tone' => 'success', 'icon' => 'badge-check'],
        ],

        App\Domain\Documents\Enums\DocumentReviewStatus::class => [
            'pending' => ['label' => 'En attente', 'tone' => 'warning', 'icon' => 'clock'],
            'approved' => ['label' => 'Approuvé', 'tone' => 'success', 'icon' => 'circle-check'],
            'rejected' => ['label' => 'Rejeté', 'tone' => 'danger', 'icon' => 'circle-x'],
        ],

        App\Domain\Documents\Enums\DocumentType::class => [
            'id_card' => ['label' => 'Pièce d\'identité', 'tone' => 'neutral', 'icon' => 'file-text'],
            'proof_of_address' => ['label' => 'Justificatif de domicile', 'tone' => 'neutral', 'icon' => 'file-text'],
            'photo' => ['label' => 'Photo', 'tone' => 'neutral', 'icon' => 'file-text'],
            'contract' => ['label' => 'Contrat', 'tone' => 'neutral', 'icon' => 'file-text'],
            'signature' => ['label' => 'Signature', 'tone' => 'neutral', 'icon' => 'file-text'],
            'insurance_certificate' => ['label' => 'Attestation d\'assurance', 'tone' => 'neutral', 'icon' => 'shield'],
            'technical_inspection_certificate' => ['label' => 'Contrôle technique', 'tone' => 'neutral', 'icon' => 'wrench'],
            'other' => ['label' => 'Autre document', 'tone' => 'neutral', 'icon' => 'file-text'],
        ],

        App\Domain\Finance\Enums\InvoiceStatus::class => [
            'unpaid' => ['label' => 'Impayée', 'tone' => 'danger', 'icon' => 'circle-alert'],
            'partial' => ['label' => 'Partielle', 'tone' => 'warning', 'icon' => 'circle-minus'],
            'paid' => ['label' => 'Payée', 'tone' => 'success', 'icon' => 'circle-check'],
        ],

        App\Domain\Finance\Enums\PaymentMethod::class => [
            'cash' => ['label' => 'Espèces', 'tone' => 'neutral', 'icon' => 'wallet'],
            'airtel_money' => ['label' => 'Airtel Money', 'tone' => 'neutral', 'icon' => 'wallet'],
            'moov_money' => ['label' => 'Moov Money', 'tone' => 'neutral', 'icon' => 'wallet'],
            'bank' => ['label' => 'Virement', 'tone' => 'neutral', 'icon' => 'landmark'],
            'cheque' => ['label' => 'Chèque', 'tone' => 'neutral', 'icon' => 'file-text'],
        ],

        App\Domain\Finance\Enums\LedgerEntryType::class => [
            'income' => ['label' => 'Recette', 'tone' => 'success', 'icon' => 'arrow-down-left'],
            'expense' => ['label' => 'Dépense', 'tone' => 'danger', 'icon' => 'arrow-up-right'],
            'bank_deposit' => ['label' => 'Dépôt bancaire', 'tone' => 'info', 'icon' => 'landmark'],
            'bank_withdrawal' => ['label' => 'Retrait bancaire', 'tone' => 'warning', 'icon' => 'landmark'],
        ],

        App\Domain\Scheduling\Enums\PresenceStatus::class => [
            'planned' => ['label' => 'À marquer', 'tone' => 'neutral', 'icon' => 'clock'],
            'present' => ['label' => 'Présent', 'tone' => 'success', 'icon' => 'circle-check'],
            'absent' => ['label' => 'Absent', 'tone' => 'danger', 'icon' => 'circle-x'],
            'cancelled' => ['label' => 'Annulée', 'tone' => 'neutral', 'icon' => 'ban'],
            'rescheduled' => ['label' => 'Reportée', 'tone' => 'warning', 'icon' => 'calendar-clock'],
        ],

        App\Domain\Scheduling\Enums\SessionType::class => [
            'theoretical' => ['label' => 'Théorie', 'tone' => 'info', 'icon' => 'book-open'],
            'practical' => ['label' => 'Conduite', 'tone' => 'accent', 'icon' => 'car'],
            'code' => ['label' => 'Code', 'tone' => 'info', 'icon' => 'clipboard-list'],
            'mock_exam' => ['label' => 'Examen blanc', 'tone' => 'warning', 'icon' => 'pencil-ruler'],
        ],

        App\Domain\CRM\Enums\LeadStatus::class => [
            'new' => ['label' => 'Nouveau', 'tone' => 'info', 'icon' => 'sparkles'],
            'contacted' => ['label' => 'Contacté', 'tone' => 'accent', 'icon' => 'phone'],
            'qualified' => ['label' => 'Qualifié', 'tone' => 'warning', 'icon' => 'target'],
            'converted' => ['label' => 'Converti', 'tone' => 'success', 'icon' => 'circle-check'],
            'lost' => ['label' => 'Perdu', 'tone' => 'neutral', 'icon' => 'circle-x'],
        ],

        App\Domain\Fleet\Enums\VehicleStatus::class => [
            'active' => ['label' => 'En service', 'tone' => 'success', 'icon' => 'circle-check'],
            'maintenance' => ['label' => 'En entretien', 'tone' => 'warning', 'icon' => 'wrench'],
            'out_of_service' => ['label' => 'Hors service', 'tone' => 'danger', 'icon' => 'ban'],
        ],

        App\Domain\Instructors\Enums\InstructorStatus::class => [
            'active' => ['label' => 'Actif', 'tone' => 'success', 'icon' => 'circle-check'],
            'inactive' => ['label' => 'Inactif', 'tone' => 'neutral', 'icon' => 'ban'],
        ],

        App\Domain\Store\Enums\OrderStatus::class => [
            'pending' => ['label' => 'En attente', 'tone' => 'warning', 'icon' => 'clock'],
            'confirmed' => ['label' => 'Confirmée', 'tone' => 'info', 'icon' => 'circle-check'],
            'fulfilled' => ['label' => 'Livrée', 'tone' => 'success', 'icon' => 'package'],
            'cancelled' => ['label' => 'Annulée', 'tone' => 'neutral', 'icon' => 'ban'],
        ],

        App\Domain\Store\Enums\PurchaseOrderStatus::class => [
            'pending' => ['label' => 'En attente', 'tone' => 'warning', 'icon' => 'clock'],
            'partially_received' => ['label' => 'Partiellement reçue', 'tone' => 'info', 'icon' => 'package'],
            'received' => ['label' => 'Reçue', 'tone' => 'success', 'icon' => 'circle-check'],
            'cancelled' => ['label' => 'Annulée', 'tone' => 'neutral', 'icon' => 'ban'],
        ],

        App\Domain\Training\Enums\ExamResult::class => [
            'pending' => ['label' => 'À venir', 'tone' => 'info', 'icon' => 'calendar'],
            'passed' => ['label' => 'Réussi', 'tone' => 'success', 'icon' => 'trophy'],
            'failed' => ['label' => 'Échec', 'tone' => 'danger', 'icon' => 'circle-x'],
            'cancelled' => ['label' => 'Annulé', 'tone' => 'neutral', 'icon' => 'ban'],
        ],

        App\Domain\Training\Enums\ExamType::class => [
            'code' => ['label' => 'Examen du code', 'tone' => 'info', 'icon' => 'clipboard-list'],
            'driving' => ['label' => 'Examen pratique', 'tone' => 'accent', 'icon' => 'car'],
        ],

        App\Domain\Training\Enums\SkillLevel::class => [
            'not_started' => ['label' => 'Non commencée', 'tone' => 'neutral', 'icon' => 'circle'],
            'in_progress' => ['label' => 'En cours', 'tone' => 'warning', 'icon' => 'gauge'],
            'acquired' => ['label' => 'Acquise', 'tone' => 'success', 'icon' => 'circle-check'],
        ],

        App\Domain\Tenancy\Enums\StructureStatus::class => [
            'pending' => ['label' => 'En attente', 'tone' => 'warning', 'icon' => 'clock'],
            'active' => ['label' => 'Actif', 'tone' => 'success', 'icon' => 'circle-check'],
            'suspended' => ['label' => 'Suspendu', 'tone' => 'danger', 'icon' => 'ban'],
            'deactivated' => ['label' => 'Désactivé', 'tone' => 'neutral', 'icon' => 'archive-box'],
        ],

        App\Domain\Recyclage\Enums\RecyclageMotif::class => [
            'test' => ['label' => 'Test', 'tone' => 'info', 'icon' => 'clipboard-check'],
            'recyclage' => ['label' => 'Recyclage', 'tone' => 'accent', 'icon' => 'refresh-cw'],
        ],

        App\Domain\Students\Enums\CourseType::class => [
            'normal' => ['label' => 'Formule normale', 'tone' => 'neutral', 'icon' => 'calendar'],
            'accelerated' => ['label' => 'Formule accélérée', 'tone' => 'info', 'icon' => 'zap'],
            'accelerated_10d' => ['label' => 'Formule accélérée 10 jours', 'tone' => 'info', 'icon' => 'zap'],
        ],

        App\Domain\Students\Enums\LicenseCategory::class => [
            'A' => ['label' => 'Permis A', 'tone' => 'neutral', 'icon' => 'car'],
            'B' => ['label' => 'Permis B', 'tone' => 'neutral', 'icon' => 'car'],
            'C' => ['label' => 'Permis C', 'tone' => 'neutral', 'icon' => 'truck'],
            'D' => ['label' => 'Permis D', 'tone' => 'neutral', 'icon' => 'truck'],
            'E' => ['label' => 'Permis E', 'tone' => 'neutral', 'icon' => 'truck'],
        ],
    ],

    /**
     * Phases visuelles du parcours élève (§2) : 15 étapes regroupées en 5 phases
     * pour rester lisible sur mobile.
     */
    'phases' => [
        ['key' => 'admission', 'label' => 'Admission', 'stages' => ['prospect', 'pre_enrollment', 'dossier_setup', 'validation']],
        ['key' => 'paiement', 'label' => 'Paiement', 'stages' => ['enrollment', 'payment']],
        ['key' => 'code', 'label' => 'Code', 'stages' => ['theory_course', 'mock_exams', 'code_obtained']],
        ['key' => 'conduite', 'label' => 'Conduite', 'stages' => ['practical_course', 'continuous_evaluation', 'ready_for_exam']],
        ['key' => 'permis', 'label' => 'Permis', 'stages' => ['practical_exam', 'license_obtained', 'former_student']],
    ],
];
