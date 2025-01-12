<?php

namespace App\Http\Controllers\Api\Games\Rolling;

use App\Events\Games\RoundResult as GamesRoundResult;
use App\Events\Games\RoundStatusUpdate as GamesRoundStatusUpdate;
use App\Events\Games\GlobalRouletteNumber;
use App\Events\Games\PrivateGameStatus;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Games\Rolling\Round;
use App\Models\Games\Rolling\Bet;
use App\Events\RoundStatusUpdate;
use App\Events\RoundResult;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class GameController extends Controller
{
    // Get the current round
    public function getCurrentRound()
    {
        return Round::where('start_time', '<=', Carbon::now())
                    ->where('end_time', '>=', Carbon::now())
                    ->orderBy('id', 'desc')
                    ->first();
    }

    // User attempting to join the game
    public function joinGame(Request $request)
    {
        $user = Auth::user();

        $currentRound = $this->getCurrentRound();

        if (!$currentRound) {
            return response()->json([
                'status' => 'waiting',
                'message' => 'Please wait for the next round to start.',
            ]);
        }

        $timeElapsed = Carbon::now()->diffInSeconds($currentRound->start_time);
        $currentPhase = $this->getCurrentPhase($timeElapsed);

        // Broadcast the private event with the current second and phase
        broadcast(new PrivateGameStatus($user->id, $currentPhase, $timeElapsed));

        return response()->json([
            'status' => 'ongoing',
            'message' => 'You can join the current round.',
            'time_elapsed' => $timeElapsed,
            'current_phase' => $currentPhase
        ]);
    }

    // Determine the current phase of the round based on the time elapsed
    protected function getCurrentPhase($timeElapsed)
    {
        if ($timeElapsed <= 21) {
            return ['phase' => 'betting', 'seconds' => $timeElapsed];
        } elseif ($timeElapsed > 21 && $timeElapsed <= 25) {
            return ['phase' => 'spinning', 'seconds' => $timeElapsed - 21];
        } else {
            return ['phase' => 'waiting', 'seconds' => $timeElapsed - 25];
        }
    }

    // Placing a bet by the user
    public function placeBet(Request $request)
    {
        $user = Auth::user();

        $betAmount = $request->input('bet_amount');
        $betNumber = $request->input('bet_number');
        $multiplier = $request->input('multiplier');

        $currentRound = $this->getCurrentRound();

        if (!$currentRound) {
            return response()->json([
                'status' => 'error',
                'message' => 'No active round. Please wait for the next round.',
            ], 400);
        }

        if ($user->diamond_balance < $betAmount) {
            return response()->json([
                'status' => 'error',
                'message' => 'Insufficient balance for betting.',
            ], 400);
        }

        // Deduct the bet amount from user's balance
        $user->diamond_balance -= $betAmount;
        $user->save();

        // Create the bet in the database
        $bet = Bet::create([
            'user_id' => $user->id,
            'round_id' => $currentRound->id,
            'amount' => $betAmount,
            'number' => $betNumber,
            'multiplier' => $multiplier,
        ]);

        // Update the total bet for the round (stored in Cache)
        Cache::increment('total_bet_' . $currentRound->id, $betAmount);

        return response()->json([
            'status' => 'success',
            'message' => 'Bet placed successfully.',
        ]);
    }

    // End the current round and process the bets
    public function endRound(Request $request)
    {
        $winningNumber = $request->input('winning_number');

        $currentRound = $this->getCurrentRound();

        if (!$currentRound) {
            return response()->json([
                'status' => 'error',
                'message' => 'No active round to end.',
            ], 400);
        }

        // Set the round end time to the current time
        $currentRound->end_time = Carbon::now();
        $currentRound->number = $winningNumber;
        $currentRound->save();

        // Process bets and determine the winners
        $this->processRound($currentRound->id, $winningNumber);

        // Broadcast the round status update and results
        broadcast(new GamesRoundStatusUpdate('ended', 0));

        // Start a new round
        $this->startNewRound();

        return response()->json([
            'status' => 'success',
            'message' => 'Round ended successfully.',
        ]);
    }

    // Start a new round
    public function startNewRound()
    {
        $newRound = Round::create([
            'start_time' => Carbon::now(),
            'end_time' => Carbon::now()->addSeconds(28), // 21 + 4 + 3 seconds
            'number' => null, // The winning number will be set at the end of the round
        ]);

        // Broadcast the start of the new round
        broadcast(new GamesRoundStatusUpdate('started', 21));

        // A Scheduler or Job can be added to automatically end the round after 21 seconds
    }

    // Calculate and broadcast the random number for roulette
    public function getRandomNumber()
    {
        // Generate a random number (Example for a roulette number from 0 to 36)
        $randomNumber = rand(0, 36);

        // Broadcast the random number to all players globally
        broadcast(new GlobalRouletteNumber($randomNumber));

        // Return the random number in the API response
        return response()->json([
            'status' => 'success',
            'random_number' => $randomNumber,
        ]);
    }

    // Process the round: calculate winnings and update users' balances
    protected function processRound($roundId, $winningNumber)
    {
        $round = Round::find($roundId);
        $bets = $round->bets;

        foreach ($bets as $bet) {
            $user = $bet->user;

            if ($bet->number == $winningNumber) {
                $winAmount = $bet->amount * $bet->multiplier;
                $user->diamond_balance += $winAmount;
                $result = 'win';
            } else {
                $result = 'lose';
            }

            // Save updates to the user
            $user->save();

            // Broadcast the result to the specific user
            broadcast(new GamesRoundResult($user->id, $result, $winAmount ?? 0, $user->diamond_balance));
        }
    }
}
