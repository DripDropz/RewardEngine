<?php

namespace App\Traits;

use App\Models\ProjectWallet;

trait WalletAuthTrait
{
    public function buildWalletChallengeHex(ProjectWallet $projectWallet): string
    {
        return bin2hex(json_encode([
            'expiration' => $projectWallet->auth_expiration->toAtomString(),
            'issued' => $projectWallet->auth_issued->toAtomString(),
            'nonce' => $projectWallet->auth_nonce,
            'type' => 'UserAuthentication',
            'uri' => url('/'),
            'userID' => $projectWallet->stake_key_hex . '|' . $projectWallet->stake_key_address,
            'version' => '1.0.0',
        ]));
    }
}
