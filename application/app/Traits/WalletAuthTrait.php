<?php

namespace App\Traits;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

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

    public function verifyWalletChallengeTransaction(
        string $transactionCbor,
        string $transactionWitness,
        string $walletAuthChallengeHex,
        string $stakeKeyAddress,
        int $networkMode,
    ): bool
    {
        // TODO: implementation
        return false;
    }

    public function verifyWalletChallengeSignature(
        string $signatureCbor,
        string $signatureKey,
        string $walletAuthChallengeHex,
        string $stakeKeyAddress,
        int $networkMode,
    ): bool
    {
        $payload = [
            'type' => 'verifySignature',
            ...compact(
                'signatureCbor',
                'signatureKey',
                'walletAuthChallengeHex',
                'stakeKeyAddress',
                'networkMode',
            ),
        ];

        if (app()->environment('local')) {
            $response = Http::post('http://rewardengine-cardano-sidecar:3000', $payload);
            if ($response->successful()) {
                return $response->json('isValid');
            }
        }

        // TODO Call Sidecar Lambda function

        return false;
    }

    public function resolveAdaHandle(string $stakeKeyAddress): string
    {
        return Cache::remember(sprintf('adahandle:%s', $stakeKeyAddress), 1800, function () use ($stakeKeyAddress) {
            try {

                $response = Http::timeout(10)
                    ->connectTimeout(10)
                    ->get(sprintf('https://api.handle.me/handles?holder_address=%s', $stakeKeyAddress));

                if ($response->successful() && isset($response->json()[0]['default_in_wallet'])) {
                    return $response->json()[0]['default_in_wallet'];
                }

            } catch (Throwable) {}

            return $stakeKeyAddress;
        });
    }
}
