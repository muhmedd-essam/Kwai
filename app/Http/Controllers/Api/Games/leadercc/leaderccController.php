<?php

namespace App\Http\Controllers\Api\Games\leadercc;

use App\Http\Controllers\Controller;
use App\Models\Games\leadercc\GameSession;
use App\Models\Games\leadercc\GameTransaction;
use App\Models\User;
use Illuminate\Http\Request;

class leaderccController extends Controller
{
    public function getUserInfo(Request $request)
    {
        $validated = $request->validate([
            'gameId' => 'required|string',
            'uid' => 'required|string',
            'token' => 'required|string',
            'roomId' => 'nullable|string',
            'sign' => 'required|string'
        ]);

        $key = "62783916546757869029399"; // Temporary Key
        $expectedSign = md5($validated['gameId'] . $validated['uid'] . $validated['token'] . ($validated['roomId'] ?? '') . $key);

        // if ($validated['sign'] !== $expectedSign) {
        //     return response()->json(['errorCode' => 401, 'message' => 'Invalid Signature'], 401);
        // }

        $user = User::where('uid', $validated['uid'])->first();

        if (!$user) {
            return response()->json(['errorCode' => 404, 'message' => 'User not found'], 404);
        }

        return response()->json([
            'errorCode' => 0,
            'data' => [
                'uid' => $user->uid,
                'nickname' => $user->name,
                'avatar' => $user->profile_picture,
                'coin' => $user->diamond_balance,
                'vipLevel' => $user->vip
            ]
        ]);
    }


    public function updateCurrency(Request $request)
    {
        $validated = $request->validate([
            'orderId' => 'required|string',
            'gameId' => 'required|string',
            'roundId' => 'required|string',
            'uid' => 'required|string',
            'coin' => 'required|integer',
            'type' => 'required|integer|in:1,2',
            'token' => 'required|string',
            'winId' => 'nullable|string',
            'sign' => 'required|string'
        ]);

        $key = "62783916546757869029399"; // Temporary Key
        $expectedSign = md5($validated['orderId'] . $validated['gameId'] . $validated['roundId'] . $validated['uid'] . $validated['coin'] . $validated['type'] . $validated['token'] . ($validated['winId'] ?? '') . $key);

        // if ($validated['sign'] !== $expectedSign) {
        //     return response()->json(['errorCode' => 401, 'message' => 'Invalid Signature'], 401);
        // }

        $user = User::where('uid', $validated['uid'])->first();

        if (!$user) {
            return response()->json(['errorCode' => 404, 'message' => 'User not found'], 404);
        }

        // Deduplicate based on orderId
        if (GameTransaction::where('order_id', $validated['orderId'])->exists()) {
            return response()->json(['errorCode' => 409, 'message' => 'Duplicate Order ID'], 409);
        }

        // Update currency based on type
        if ($validated['type'] === 1 && $user->diamond_balance < $validated['coin']) {
            return response()->json(['errorCode' => 400, 'message' => 'Insufficient coins'], 400);
        }

        $user->diamond_balance += ($validated['type'] === 1 ? -1 : 1) * $validated['coin'];
        $user->save();

        GameTransaction::create($validated);

        return response()->json([
            'errorCode' => 0,
            'data' => [
                'coin' => $user->diamond_balance
            ]
        ]);
    }
}
