<?php

namespace App\Domain\Students\Enums;

/**
 * The student lifecycle from the 2026-08-23 design (docs/superpowers/specs/
 * 2026-08-23-inscription-eleve-otp-dossier-design.md): Prospect -> ... ->
 * LicenseObtained -> FormerStudent, with two allowed back-edges — Validation
 * -> DossierSetup on a rejected document, and PracticalExam ->
 * ContinuousEvaluation on a failed exam.
 */
enum LifecycleStage: string
{
    case Prospect = 'prospect';
    case PreEnrollment = 'pre_enrollment';
    case Enrollment = 'enrollment';
    case Payment = 'payment';
    case DossierSetup = 'dossier_setup';
    case Validation = 'validation';
    case TheoryCourse = 'theory_course';
    case MockExams = 'mock_exams';
    case CodeObtained = 'code_obtained';
    case PracticalCourse = 'practical_course';
    case ContinuousEvaluation = 'continuous_evaluation';
    case ReadyForExam = 'ready_for_exam';
    case PracticalExam = 'practical_exam';
    case LicenseObtained = 'license_obtained';
    case FormerStudent = 'former_student';

    /**
     * @return LifecycleStage[] stages this one may transition to.
     */
    public function allowedNextStages(): array
    {
        return match ($this) {
            self::Prospect => [self::PreEnrollment],
            self::PreEnrollment => [self::DossierSetup],
            self::DossierSetup => [self::Validation],
            self::Validation => [self::Enrollment, self::DossierSetup],
            self::Enrollment => [self::Payment],
            self::Payment => [self::TheoryCourse],
            self::TheoryCourse => [self::MockExams],
            self::MockExams => [self::CodeObtained],
            self::CodeObtained => [self::PracticalCourse],
            self::PracticalCourse => [self::ContinuousEvaluation],
            self::ContinuousEvaluation => [self::ReadyForExam],
            self::ReadyForExam => [self::PracticalExam],
            self::PracticalExam => [self::LicenseObtained, self::ContinuousEvaluation],
            self::LicenseObtained => [self::FormerStudent],
            self::FormerStudent => [],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedNextStages(), true);
    }

    public function label(): string
    {
        return match ($this) {
            self::Prospect => 'Prospect',
            self::PreEnrollment => 'Pré-inscription',
            self::Enrollment => 'Inscription',
            self::Payment => 'Paiement',
            self::DossierSetup => 'Constitution du dossier',
            self::Validation => 'Validation',
            self::TheoryCourse => 'Cours théorique',
            self::MockExams => 'Examens blancs',
            self::CodeObtained => 'Code obtenu',
            self::PracticalCourse => 'Cours pratique',
            self::ContinuousEvaluation => 'Évaluation continue',
            self::ReadyForExam => 'Prêt examen',
            self::PracticalExam => 'Examen pratique',
            self::LicenseObtained => 'Permis obtenu',
            self::FormerStudent => 'Ancien élève',
        };
    }
}
