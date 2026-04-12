<?php

namespace App\Enums;

enum TransactionStatus: string
{
    case Scheduled = 'scheduled';
    case Completed = 'completed';
    case Canceled = 'canceled';
}
