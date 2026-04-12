<?php

namespace App\Enums;

enum TransactionPaymentMethod: string
{
    case Pix = 'pix';
    case Cash = 'cash';
    case Credit = 'credit_card';
    case Debit = 'debit_card';
    case Other = 'other';
}
