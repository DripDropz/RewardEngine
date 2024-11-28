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

    public function generateNewWallet(): array|null
    {
        return $this->executeCardanoSidecar([
            'type' => 'generateNewWallet',
        ]);
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

    private function verifyWalletChallenge(array $answer): bool
    {
        $result = $this->executeCardanoSidecar($answer);

        return (
            $result &&
            isset($result['isValid']) &&
            $result['isValid'] === true
        );
    }

    private function executeCardanoSidecar(array $payload): array|null
    {
        if (app()->environment('local')) {
            try {
                $response = Http::post('http://rewardengine-cardano-sidecar:3000', $payload)->throw();
                if ($response->successful()) {
                    return $response->json();
                }
            } catch (Throwable $exception) {
                $this->logException('Local verifyWalletChallengeSignature Error', $exception);
            }
        } else {
            try {
                $result = Cardano::execute($payload);
                return $result->body();
            } catch (Throwable $exception) {
                $this->logException('Sidecar verifyWalletChallengeSignature Error', $exception);
            }
        }

        return null;
    }
}
