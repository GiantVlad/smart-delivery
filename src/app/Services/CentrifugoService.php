<?php

declare(strict_types=1);

namespace App\Services;

use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Config;

class CentrifugoService
{
    protected $secretKey;

    public function __construct()
    {
        $this->secretKey = Config::get('broadcasting.centrifugo.secret');
    }

    /**
     * Generate a connection token for a user.
     *
     * @param string $userId
     * @param array $info
     * @param int $ttl
     * @return string
     */
    public function generateConnectionToken(string $userId, array $info = [], int $ttl = 3600): string
    {
        $payload = [
            'sub' => $userId,
            'exp' => time() + $ttl,
            'info' => $info,
        ];

        return JWT::encode($payload, $this->secretKey, 'HS256');
    }

    /**
     * Generate a subscription token for a private channel.
     *
     * @param string $channel
     * @param int $ttl
     * @return string
     */
    public function generateSubscriptionToken(string $channel, int $ttl = 3600): string
    {
        $payload = [
            'channel' => $channel,
            'exp' => time() + $ttl,
        ];

        return JWT::encode($payload, $this->secretKey, 'HS256');
    }
}
