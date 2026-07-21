<?php

namespace App\Domain\Documents\Enums;

enum DocumentType: string
{
    case IdCard = 'id_card';
    case ProofOfAddress = 'proof_of_address';
    case Photo = 'photo';
    case Contract = 'contract';
    case Signature = 'signature';
    case InsuranceCertificate = 'insurance_certificate';
    case TechnicalInspectionCertificate = 'technical_inspection_certificate';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::IdCard => "Pièce d'identité",
            self::ProofOfAddress => 'Justificatif de domicile',
            self::Photo => 'Photo',
            self::Contract => 'Contrat',
            self::Signature => 'Signature',
            self::InsuranceCertificate => "Attestation d'assurance",
            self::TechnicalInspectionCertificate => 'Certificat de contrôle technique',
            self::Other => 'Autre',
        };
    }
}
