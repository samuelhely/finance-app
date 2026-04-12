<?php

namespace App\Enums;

enum TransactionSourceType: string
{
    case Manual = 'manual';
    case Installment = 'installment';
    case Recurrence = 'recurrence';
}
