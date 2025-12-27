<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Support\Facades\Auth;
use Illuminate\Console\Command;
use Opekunov\Centrifugo\Contracts\CentrifugoInterface;

class TestCentrifugoPush extends Command
{
    protected $signature = 'centrifugo:publish';

    protected $description = 'Test Centrifugo';

    public function handle(CentrifugoInterface $centrifugo): void
    {
        $this->info('Started test centrifugo');
        // Send message into channel
        $centrifugo->publish('test', ['message' => 'Hello world']);

        Auth::loginUsingId(1);

        // Generate connection token
        $token = $centrifugo->generateConnectionToken((string)Auth::id(), 0, [
            'name' => Auth::user()->name ?? '',
        ]);
        $this->info('Connection token ' . substr($token, 0, 5) . '...');

        // Generate subscription token
        $expire = now()->addDay();
        $apiSign = $centrifugo->generateSubscriptionToken((string)Auth::id(), 'channel', $expire, [
            'name' => Auth::user()->name ?? '',
        ]);
        $this->info('Subscription token '. substr($apiSign, 0, 5) . '...');

        //Get a list of currently active channels.
        $cannels = $centrifugo->channels();
        $this->info('Active channels '. implode(',', $cannels));

        //Get channel presence information (all clients currently subscribed on this channel).
        $centrifugo->presence('test');
    }
}
