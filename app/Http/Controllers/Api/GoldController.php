<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Validator;
use App\Traits\MobileTrait;
use App\Models\User;

class GoldController extends Controller
{
    use MobileTrait;

    public function getGoldBalance()
    {
        return $this->data(auth()->user()->gold_balance);
    }
    
    public function convertGoldToDiamond(Request $request)
    {
        $user = auth()->user();

        $rules = [
            'gold_amount' => ['required', 'numeric'],
        ];

        $validator = Validator::make($request->all(), $rules);
        if($validator->fails()) {
            return $this->validationError(422, 'The given data was invalid.', $validator);
        }

        if($user->gold_balance < $request->gold_amount)
        {
            return $this->error('عفوا رصيدك من الذهب لا يكفي', 403);
        }
        
        try{
            $user->gold_balance-= $request->gold_amount;
            $user->diamond_balance+= $request->gold_amount * 0.3;
            $user->save();

            return $this->successWithoutData('تم تحويل الذهب إلى ماسات بنجاح');
        }catch(QueryException $e){
            return $e;
            return $this->error('لقد حدث خطأ ما، برجاء المحاولة لاحقا', 500);
        }
    }

}
