<?php

namespace App\Traits;

use Illuminate\Support\Facades\Log;
use Throwable;

trait LogExceptionTrait
{
    function logException(string $message, Throwable $exception, array $metadata = []): void
    {
        Log::error($message, [
            ...$metadata,
            'request' => request()->toArray(),
            'error' => $exception->getMessage(),
            'file' => $exception->getFile() . ':' . $exception->getLine(),
            'previous' => $this->parsePreviousException($exception->getPrevious()),
        ]);
    }

    function parsePreviousException(Throwable|null $exception = null): array|null
    {
        $previous = null;
        if ($exception) {
            $previous = [
                'error' => $exception->getMessage(),
                'file' => $exception->getFile() . ':' . $exception->getLine(),
                'previous' => ($exception->getPrevious()
                    ? $this->parsePreviousException($exception->getPrevious())
                    : null
                ),
            ];
        }
        return $previous;
    }
}
