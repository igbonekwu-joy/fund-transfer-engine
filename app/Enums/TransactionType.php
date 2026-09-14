<?php

namespace App\Enums;

enum TransactionType: string
{
    case Deposit = 'deposit';
    case Transfer = 'transfer';
    case Withdrawal = 'withdrawal';
    case Fee = 'fee';
}
