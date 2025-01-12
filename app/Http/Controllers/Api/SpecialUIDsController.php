<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Store\SpecialUID;
use App\Traits\MobileTrait;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class SpecialUIDsController extends Controller
{
    use MobileTrait;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $specilUIDs = SpecialUID::where('type', 0)->where('is_purchased', 0)->paginate(12);

        return $this->dataPaginated($specilUIDs);
    }

    public function indexSelects()
    {
        $specilUIDs = SpecialUID::where('type', 1)->where('is_selected', 0)->paginate(12);
        return $this->dataPaginated($specilUIDs);
    }

    public function storeUidFromSelects(Request $request)
    {
        $rules = [
            'id' => ['required'],
        ];
        $validator = Validator::make($request->all(), $rules);
        if($validator->fails()) {
            $code = $this->returnCodeAccordingToInput($validator);
            return $code;
        }

        $uId = SpecialUID::findOrFail($request->id);
        $user = auth()->user();

        if ($user->level_gift == 1){
            return $this->error('بالفعل انت معك هدية الليفل 18', 403);
        }

        if ($user->level_gift == 2){
            return $this->error('بالفعل انت معك هدية الليفل 30', 403);
        }
        if($uId->is_selected == 1){
            return $this->error('هذا الاسم تم اختياره بالفعل من شخص اخر .. اختار غيره', 403);
        }

        

        if($user->level_id < 19 ){
            return $this->error('لازال الليفل الخاص بك اقل من 18', 403);

        }

        try{
            $user->uid = $uId->body;
            $user->level_gift = 1;
            $user->save();
    
            $uId->user_id = $user->id;
            $uId->is_selected = 1;
            $uId->save();

            return $this->success('S101', 'تم اخذ ال id');
        }catch(QueryException $e){
            return $this->error('E200', '');
        }


        
    }


    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $specialUID = SpecialUID::findOrFail($id);
        $user = auth()->user();

        if($specialUID->is_purchased == 1){
            return $this->error('هذا ال id المميز تم شرائه بالفعل', 403);
        }
        
        if($specialUID->price > $user->diamond_balance){
            return $this->error('عفوا، رصيدك من الماسات لا يكفي. برجاء الشحن والمحاولة مرة اخرى', 403);
        }


        $userUID = SpecialUID::where('user_id', $user->id);

        try{
            $specialUID->user_id = $user->id;
            $specialUID->is_purchased = 1;
            $specialUID->save();
            $user->uid = $specialUID->body;
            $user->save();

            if($userUID->exists()){
                $userUID = $userUID->first();
                $userUID->user_id = null;
                $userUID->is_purchased = 0;
                $userUID->save();
            }
            
            return $this->successWithoutData('تم تغيير الID الخاص بك بنجاح');
        }catch(QueryException $e){
            return $this->error('لقد حدث خطأ ما برجاء المحاولة لاحقا', 500);
        }
    }
}
