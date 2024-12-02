<?php

namespace App\Sidecar;

use Hammerstone\Sidecar\LambdaFunction;

class Cardano extends LambdaFunction
{
    public function handler(): string
    {
        return 'sidecar/cardano/index.handler';
    }

    public function package(): array
    {
        return [
            'sidecar/cardano',
        ];
    }

    public function runtime(): string
    {
        return 'nodejs20.x';
    }
}
