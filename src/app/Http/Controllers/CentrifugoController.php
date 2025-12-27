<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CentrifugoController extends Controller
{
    /**
     * Generate a connection token for the authenticated user.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getConnectionToken()
    {
        /** @var User|null $user */
        $user = Auth::user();

        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $info = [
            'name' => $user->name,
            'email' => $user->email,
            'roles' => $user->getRoleNames()->toArray()
        ];

        $token = $this->centrifugo->generateConnectionToken(
            (string) $user->id,
            time() + config('broadcasting.centrifugo.token_expire_time', 3600*5), // 1 hour by default
            $info
        );

        return response()->json([
            'token' => $token,
            'expires_in' => config('broadcasting.centrifugo.token_expire_time', 3600*5)
        ]);
    }

    /**
     * Generate a subscription token for a private channel.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSubscriptionToken(Request $request)
    {
        $user = Auth::user();
        $channel = $request->input('channel');

        if (!$user || !$channel) {
            return response()->json(['error' => 'Unauthenticated or invalid channel'], 401);
        }

        // You might want to add additional channel authorization logic here
        // For example, check if the user has access to this channel

        $token = $this->centrifugo->generateSubscriptionToken(
            $channel,
            time() + config('broadcasting.centrifugo.token_expire_time', 3600)
        );

        return response()->json([
            'token' => $token,
            'channel' => $channel,
            'expires_in' => config('broadcasting.centrifugo.token_expire_time', 3600)
        ]);
    }
}
