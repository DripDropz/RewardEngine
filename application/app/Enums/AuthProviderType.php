<?php

namespace App\Enums;

use App\Traits\EnumToArrayTrait;

enum AuthProviderType: string
{
    use EnumToArrayTrait;

    case WALLET = 'wallet';
    case GOOGLE = 'google';
    case TWITTER = 'twitter';
    case DISCORD = 'discord';
    case GITHUB = 'github';
}
