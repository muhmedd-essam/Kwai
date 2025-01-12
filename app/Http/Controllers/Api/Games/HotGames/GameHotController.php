<?php

namespace App\Http\Controllers\Api\Games\HotGames;

use App\Http\Controllers\Controller;
use App\Models\Games\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Models\User;

class GameHotController extends Controller
{
    private $key;

    public function __construct()
    {
        $this->key = env('GAME_PRIVATE_KEY');
    }

    public function getUserInfo(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'gameId' => 'required|string',
            'uid' => 'required|string',
            'token' => 'required|string',
            'sign' => 'required|string'
        ]);


        if ($validator->fails()) {
            return response()->json(['errorCode' => 400, 'message' => 'Validation error']);
        }

        $sign = md5($request->gameId . $request->uid . $request->token . $this->key);

        if (strtoupper($request->sign) !== strtoupper($request->sign)) {
            return response()->json(['errorCode' => 401, 'message' => 'Invalid signature']);
        }

        // Fetch user data from database
        $user = User::where('uid', $request->uid)->first();

        if (!$user) {
            return response()->json(['errorCode' => 404, 'message' => 'User not found']);
        }

        return response()->json([
            'errorCode' => 0,
            'data' => [
                'uid' => $user->uid,
                'nickname' => $user->name,
                'avatar' => $user->profile_picture,
                'coin' => $user->diamond_balance,
                'level' => $user->level,
                'vipLevel' => $user->vip,
            ]
        ]);
    }

    public function updateBalance(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'orderId' => 'required|string|unique:transactions,order_id',
            'gameId' => 'required|string',
            'roundId' => 'required|integer',
            'uid' => 'required|string',
            'coin' => 'required|integer',
            'type' => 'required|integer|in:1,2',
            'token' => 'required|string',
            'sign' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['errorCode' => 400, 'message' => 'Validation error']);
        }

        $sign = md5($request->orderId . $request->gameId . $request->roundId . $request->uid . $request->coin . $request->type . $request->token . $this->key);

        if (strtoupper($request->sign) !== strtoupper($request->sign)) {
            return response()->json(['errorCode' => 401, 'message' => 'Invalid signature']);
        }

        // Fetch user data from database
        $user = User::where('uid', $request->uid)->first();

        if (!$user) {
            return response()->json(['errorCode' => 404, 'message' => 'User not found']);
        }

        if ($request->type == 1 && $user->diamond_balance < $request->coin) {
            return response()->json(['errorCode' => 2001, 'message' => 'Insufficient coins']);
        }

        // Update user balance
        if ($request->type == 1) {
            $user->diamond_balance -= $request->coin;
        } else {
            $user->diamond_balance += $request->coin;
        }

        $user->save();
// dd('ss');
        // Log the transaction
        Transaction::create([
            'order_id' => $request->orderId,
            'game_id' => $request->gameId,
            'round_id' => $request->roundId,
            'uid' => $request->uid,
            'coin' => $request->coin,
            'type' => $request->type,
            'token' => $request->token,
            'sign' => $request->sign
        ]);

        return response()->json([
            'errorCode' => 0,
            'data' => [
                'coin' => $user->diamond_balance
            ]
        ]);
    }

}
