<?php

namespace App\Traits;

use Carbon\Carbon;

trait WalletAuthTrait
{
    public function buildWalletChallengeHex(
        Carbon $expiration,
        Carbon $issued,
        string $nonce,
        string $stakeKeyAddress,
    ): string
    {
        return bin2hex(json_encode([
            'expiration' => $expiration->toAtomString(),
            'issued' => $issued->toAtomString(),
            'nonce' => $nonce,
            'type' => 'UserAuthentication',
            'uri' => url('/'),
            'userID' => $stakeKeyAddress,
            'version' => '1.0.0',
        ]));
    }
}
