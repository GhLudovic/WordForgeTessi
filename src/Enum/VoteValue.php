<?php

declare(strict_types=1);

namespace App\Enum;

enum VoteValue: string
{
    case YES = 'yes';
    case NO = 'no';
}
