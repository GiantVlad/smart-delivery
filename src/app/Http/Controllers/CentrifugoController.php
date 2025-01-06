<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CentrifugoController extends Controller
{
    /**
     * Generate a connection token for the current user.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getConnectionToken()
    {
        $userId = Auth::user()->id; // Assume you're using Laravel's auth
        $info = ['name' => Auth::user()->name];

        $token = $this->centrifugo->generateConnectionToken($userId, 0, $info);

        return response()->json(['token' => $token]);
    }

    /**
     * Generate a subscription token for a private channel.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSubscriptionToken(Request $request)
    {
        $channel = $request->input('channel');

        $token = $this->centrifugo->generateSubscriptionToken(Auth::user()->id, $channel);

        return response()->json(['token' => $token]);
    }
}
