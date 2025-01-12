<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\SpiningWheel;
use App\Traits\MobileTrait;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class SpinningWheelController extends Controller
{
    use MobileTrait;

    public function store(Request $request)
    {
        $rules = [
            'spinning_type' => ['required', 'in:0,1,2'], // 0 => 1spin, 1 => 5spins, 2 => 10spins
        ];

        $validator = Validator::make($request->all(), $rules);
        if($validator->fails()) {
            return $this->validationError(422, 'The given data was invalid.', $validator);
        }

        $user = auth()->user();
        
        if($request->spinning_type == 0){
            $cost = 300;
            $spinningsNo = 1;
        }elseif($request->spinning_type == 1){
            $cost = 1400;
            $spinningsNo = 5;
        }elseif($request->spinning_type == 2){
            $cost = 2700;
            $spinningsNo = 10;
        }

        if($user->diamond_balance < $cost){
            return $this->error('عفوا رصيدك لا يكفي', 403);
        }

        $user->diamond_balance-= $cost;
        $user->save();

        $spinnings = SpiningWheel::where('user_id', $user->id);

        if($spinnings->exists()){
            $spinnings = $spinnings->first();
            $spinnings->spinning_no+= $spinningsNo;
            $spinnings->save();
        }else{
            $spinnings = SpiningWheel::insert([
                'user_id' => $user->id,
                'spinning_no' => $spinningsNo,
                'spinning_id' => $this->generateSID(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return $this->successWithoutData('رائع! لديك: '. $spinningsNo . ' لفات جديدة، ليبدأ المرح');
    }

    public function getUserSpinnigs()
    {
        $user = auth()->user();

        $avSpinnings = SpiningWheel::where('user_id', $user->id)->first();

        if($avSpinnings){
            return $this->data($avSpinnings);
        }else{
            return $this->data(0, 'لا يوجد لديك أي لفات متاحة، قم بشراء البعض الأن وابدأ باللعب');
        }
    }

    public function played(Request $request, $sId)
    {
        $user = auth()->user();

        $spinnings = SpiningWheel::where('spinning_id', $sId)->first();

        if(!$spinnings){
            return $this->error('wrong id', 400);
        }

        $rules = [
            'result' => ['required', 'in:0,1,2,3,4,5,6,7'],
        ];

        $validator = Validator::make($request->all(), $rules);
        if($validator->fails()) {
            return $this->validationError(422, 'The given data was invalid.', $validator);
        }

        if($spinnings->spinning_no == 1){
            $spinnings->delete();
        }else{
            $spinnings->spinning_no-= 1;
            $spinnings->save();
        }

        switch ($request->result) {
            case 0:
                $user->diamond_balance+= 2;
                $win = 2;
                break;
            case 1:
                $user->diamond_balance+= 10;
                $win = 10;
                break;
            case 2:
                $user->diamond_balance+= 15;
                $win = 15;
                break;
            case 3:
                $user->diamond_balance+= 20;
                $win = 20;
                break;
            case 4:
                $user->diamond_balance+= 149;
                $win = 149;
                break;
            case 5:
                $user->diamond_balance+= 399;
                $win = 399;
                break;
            case 6:
                $user->diamond_balance+= 999;
                $win = 999;
                break;
            case 7:
                $user->diamond_balance+= 9999;
                $win = 9999;
                break;
            default:
                return $this->error500();
                break;
        }

        $user->save();

        return $this->successWithoutData('رائع، لقد ربحت: '. $win . ' ماسات' . ' رصيدك الحالي: ' . $user->diamond_balance);
    }

    protected function generateSID()
    {
        $sId = rand(100000000, 999999999);
        
        if($this->sIDExists($sId)){
            $this->generateSID();
        }

        return strval($sId);
    }

    protected function sIDExists($sId)
    {
        if(SpiningWheel::where('spinning_id', $sId)->exists()){
            return true;
        }

        return false;
    }
}
