<?php

namespace App\Http\Middleware;

class TrustHosts extends \Illuminate\Http\Middleware\TrustHosts
{
    public function hosts(): array
    {
        return array_values(array_filter([
            $this->allSubdomainsOfApplicationUrl(),
            '^127\.0\.0\.1$',
            '^localhost$',
            '^(.+\.)?ngrok-free\.dev$',
            '^(.+\.)?zynquiz\.shadhinlab\.xyz$',
        ]));
    }
}
