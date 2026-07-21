<?php

namespace App\Domain\Finance\Enums;

enum LedgerEntryType: string
{
    case Income = 'income';
    case Expense = 'expense';
    case BankDeposit = 'bank_deposit';
    case BankWithdrawal = 'bank_withdrawal';

    public function label(): string
    {
        return match ($this) {
            self::Income => 'Recette (caisse)',
            self::Expense => 'Dépense (caisse)',
            self::BankDeposit => 'Dépôt (banque)',
            self::BankWithdrawal => 'Retrait (banque)',
        };
    }

    public function isCredit(): bool
    {
        return in_array($this, [self::Income, self::BankDeposit], true);
    }
}
