<?php

namespace App\Traits;

use App\Sidecar\Cardano;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

trait WalletAuthTrait
{
    use LogExceptionTrait;

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
        string $walletAuthChallengeHex,
        string $stakeKeyAddress,
    ): bool
    {
        return $this->verifyWalletChallenge([
            'type' => 'verifyTransaction',
            ...compact(
                'transactionCbor',
                'walletAuthChallengeHex',
                'walletAuthChallengeHex',
                'stakeKeyAddress',
            ),
        ]);
    }

    public function verifyWalletChallengeSignature(
        string $signatureCbor,
        string $signatureKey,
        string $walletAuthChallengeHex,
        string $stakeKeyAddress,
        int $networkMode,
    ): bool
    {
        return $this->verifyWalletChallenge([
            'type' => 'verifySignature',
            ...compact(
                'signatureCbor',
                'signatureKey',
                'walletAuthChallengeHex',
                'stakeKeyAddress',
                'networkMode',
            ),
        ]);
    }

    private function verifyWalletChallenge(array $answer): bool
    {
        if (app()->environment('local')) {
            try {
                $response = Http::post('http://rewardengine-cardano-sidecar:3000', $answer)->throw();
                if ($response->successful()) {
                    return (bool) $response->json('isValid');
                }
            } catch (Throwable $exception) {
                $this->logException('Local verifyWalletChallengeSignature Error', $exception);
            }
        }

        try {
            $result = Cardano::execute($answer);
            return (bool) $result->body()['isValid'];
        } catch (Throwable $exception) {
            $this->logException('Sidecar verifyWalletChallengeSignature Error', $exception);
        }

        return false;
    }

    public function resolveAdaHandle(string $stakeKeyAddress): string
    {
        return Cache::remember(sprintf('adahandle:%s', $stakeKeyAddress), 1800, function () use ($stakeKeyAddress) {
            try {
                $response = Http::timeout(10)
                    ->connectTimeout(10)
                    ->get(sprintf('https://api.handle.me/handles?holder_address=%s', $stakeKeyAddress))
                    ->throw();
                if ($response->successful() && isset($response->json()[0]['default_in_wallet'])) {
                    return $response->json()[0]['default_in_wallet'];
                }
            } catch (Throwable $exception) {
                $this->logException('Failed to resolve adahandle', $exception, [
                    'stakeKeyAddress' => $stakeKeyAddress,
                ]);
            }
            return $stakeKeyAddress;
        });
    }
}
