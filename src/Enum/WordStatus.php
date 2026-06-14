<?php

declare(strict_types=1);

namespace App\Enum;

enum WordStatus: string
{
    case PENDING = 'pending';
    case ACCEPTED = 'accepted';
    case REJECTED = 'rejected';
}