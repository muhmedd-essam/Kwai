<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\GlobalBox;
use Illuminate\Support\Arr;

class TestController extends Controller
{
    public function testing(Request $request)
    {
        $globalBox = GlobalBox::find(1);
        $user = auth()->user();
        // $bit = $request->bit;

        //increament times played
        $globalBox->times_played+=1;
        $globalBox->save();

        //check user's balance

        //subtract user's balance
        // $user->diamond_balance-= $bit;
        // $user->save();

        //Increament Global box
        // $globalBox->amount+= $bit * 0.2;
        // $globalBox->save();

        //check global box's balance
        if($globalBox->amount < $bit * 2){ // bit * 2 => x1
            $globalBox->amount+= $bit * 0.7;
            $globalBox->save();
            
            return response(['lost :(', 'box\'s balance: '.$globalBox->amount]);
        }

        //get a lose or a win 2/3
        $winOrLosePossibilities = [1, 2, 3, 4, 5, 6];
        if(Arr::random($winOrLosePossibilities) != 1){
            $globalBox->amount+= $bit * 0.7;
            $globalBox->save();
            return response(['lost :(', 'box\'s balance: '.$globalBox->amount]);
        }

        //get global box's max multiplying
        switch (true) {
            case $globalBox->amount >= $bit * 220:
                $maxMultiply = 100;
                break;
            case $globalBox->amount >= $bit * 22:
                $maxMultiply = 10;
                break;
            case $globalBox->amount >= $bit * 20:
                $maxMultiply = 9;
                break;
            case $globalBox->amount >= $bit * 18:
                $maxMultiply = 8;
                break;
            case $globalBox->amount >= $bit * 16:
                $maxMultiply = 7;
                break;
            case $globalBox->amount >= $bit * 14:
                $maxMultiply = 6;
                break;
            case $globalBox->amount >= $bit * 12:
                $maxMultiply = 5;
                break;
            case $globalBox->amount >= $bit * 10:
                $maxMultiply = 4;
                break;
            case $globalBox->amount >= $bit * 8:
                $maxMultiply = 3;
                break;
            case $globalBox->amount >= $bit * 6:
                $maxMultiply = 2;
                break;
            case $globalBox->amount >= $bit * 4:
                $maxMultiply = 1;
                break;
            default:
                $globalBox->amount+= $bit * 0.7;
                $globalBox->save();
                return response(['lost :(', 'box\'s balance: '.$globalBox->amount]);
                break;
        }

        //set possibilities of multiplying
        switch ($maxMultiply) {
            case 100:
                $multiplyingPossibilities = [1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,2,2,2,2,2,2,2,2,2,2,2,2,2,2,2,2,2,2,2,2,3,3,3,3,3,3,3,3,3,3,3,3,3,3,3,3,3,3,4,4,4,4,4,4,4,4,4,4,4,4,4,4,4,4,5,5,5,5,5,5,5,5,5,5,5,5,5,5,6,6,6,6,6,6,6,6,6,6,6,6,7,7,7,7,7,7,7,7,7,7,8,8,8,8,8,8,8,8,9,9,9,9,9,9,10,10,10,10,100];

                // if($globalBox->amount >= $bit * 220){
                //     $multiplyingPossibilities = [1,1,1,1,1,1,1,1,1,1,2,2,2,2,2,2,2,2,2,3,3,3,3,3,3,3,3,4,4,4,4,4,4,4,4,5,5,5,5,5,5,6,6,6,6,6,7,7,7,7,8,8,8,9,9,10,10,10,10,100,100,100];
                // }elseif($globalBox->amount >= $bit * 340){
                //     $multiplyingPossibilities = [1,1,1,1,1,1,1,1,1,1,2,2,2,2,2,2,2,2,2,3,3,3,3,3,3,3,3,4,4,4,4,4,4,4,4,5,5,5,5,5,5,6,6,6,6,6,7,7,7,7,8,8,8,9,9,10,10,10,10,10,10,100,100,100,100,100];
                // }
                break;
            case 10:
                $multiplyingPossibilities = [1,1,1,1,1,1,1,1,1,1,2,2,2,2,2,2,2,2,2,3,3,3,3,3,3,3,3,4,4,4,4,4,4,4,4,5,5,5,5,5,5,6,6,6,6,6,7,7,7,7,8,8,8,9,9,10];
                break;
            case 9:
                $multiplyingPossibilities = [1,1,1,1,1,1,1,1,1,1,2,2,2,2,2,2,2,2,2,3,3,3,3,3,3,3,3,4,4,4,4,4,4,4,4,5,5,5,5,5,5,6,6,6,6,6,7,7,7,7,8,8,8,9,9];
                break;
            case 8:
                $multiplyingPossibilities = [1,1,1,1,1,1,1,1,1,1,2,2,2,2,2,2,2,2,2,3,3,3,3,3,3,3,3,4,4,4,4,4,4,4,4,5,5,5,5,5,5,6,6,6,6,6,7,7,7,7,8,8,8];
                break;
            case 7:
                $multiplyingPossibilities = [1,1,1,1,1,1,1,1,1,1,2,2,2,2,2,2,2,2,2,3,3,3,3,3,3,3,3,4,4,4,4,4,4,4,4,5,5,5,5,5,5,6,6,6,6,6,7,7,7,7];
                break;
            case 6:
                $multiplyingPossibilities = [1,1,1,1,1,1,1,1,1,1,2,2,2,2,2,2,2,2,2,3,3,3,3,3,3,3,3,4,4,4,4,4,4,4,4,5,5,5,5,5,5,6,6,6,6,6];
                break;
            case 5:
                $multiplyingPossibilities = [1,1,1,1,1,1,1,1,1,1,2,2,2,2,2,2,2,2,2,3,3,3,3,3,3,3,3,4,4,4,4,4,4,4,4,5,5,5,5,5,5];
                break;
            case 4:
                $multiplyingPossibilities = [1,1,1,1,1,1,1,1,1,1,2,2,2,2,2,2,2,2,2,3,3,3,3,3,3,3,3,4,4,4,4,4,4,4,4];
                break;
            case 3:
                $multiplyingPossibilities = [1,1,1,1,1,1,1,1,1,1,2,2,2,2,2,2,2,2,2,3,3,3,3,3,3,3,3];
                break;
            case 2:
                $multiplyingPossibilities = [1,1,1,1,1,1,1,1,1,1,2,2,2,2,2,2,2,2,2];
                break;
            case 1:
            $multiplyingPossibilities = [1,1,1,1,1,1,1,1,1,1];
                break;            
            case 50:
                return 50;
                break;
            default:
                $globalBox->amount+= $bit * 0.7;
                $globalBox->save();
                return response(['lost :(', 'box\'s balance: '.$globalBox->amount]);
                break;

            }
        
        $winMultiply = Arr::random($multiplyingPossibilities);
        
        $user->diamond_balance+= $bit * $winMultiply;
        
        $globalBox->amount-= $bit * $winMultiply;
        $globalBox->save();

        return response()->json(['user win: ' => $bit * $winMultiply]);
    }

    
}
