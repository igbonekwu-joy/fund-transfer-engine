<?php

namespace App\Enums;

enum LedgerEntryDirection: string
{
    case Debit = 'debit';
    case Credit = 'credit';
}
