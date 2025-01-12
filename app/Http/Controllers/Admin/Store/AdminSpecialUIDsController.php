<?php

namespace App\Http\Controllers\Admin\Store;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Database\QueryException;
use App\Traits\WebTrait;
use App\Models\Store\SpecialUID;
use App\Models\User;

class AdminSpecialUIDsController extends Controller
{
    use WebTrait;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $uIds = SpecialUID::with('user')->paginate(12);

        return $this->data($uIds);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $rules = [
            'body' => ['required', 'unique:special_u_i_d_s,body'],
            'price' => ['required', 'numeric', 'min:1'],
        ];

        $validator = Validator::make($request->all(), $rules);
        if($validator->fails()) {
            $code = $this->returnCodeAccordingToInput($validator);
            return $this->validationError($code, $validator);
        }

        //DB:
        try{
            $uId = SpecialUID::create($request->all());
            
            return $this->success('S100'); //Success Insert
        }catch(QueryException $e){
            return $this->error('E200', ''); //DB err(General)
        }

    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $uId = SpecialUID::with('user')->findOrFail($id);

        return $this->data($uId);
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
        $uId = SpecialUID::findOrFail($id);

        $rules = [
            'body' => ['required', 'unique:special_u_i_d_s,body'],
            'price' => ['required', 'numeric', 'min:1'],
        ];

        $validator = Validator::make($request->all(), $rules);
        if($validator->fails()) {
            $code = $this->returnCodeAccordingToInput($validator);
            return $this->validationError($code, $validator);
        }

        //DB:
        try{
            $uId->update($request->all());
            
            return $this->success('S101');
        }catch(QueryException $e){
            return $this->error('E200', ''); //DB err(General)
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $uId = SpecialUID::findOrFail($id);
        $uId->delete();

        //re-generate user's id(who owns the uid)
        $newUid = $this->generateUID();
        if($uId->user !== null){
            $uId->user->uid = $newUid;
            $uId->user->save();
        }

        return $this->success('S103');
    }

    public function giveUidToUser(Request $request, $id) //ID of UID
    {
        $uId = SpecialUID::findOrFail($id);

        $rules = [
            'user_id' => ['required', 'exists:users,id'],
        ];

        $validator = Validator::make($request->all(), $rules);
        if($validator->fails()) {
            $code = $this->returnCodeAccordingToInput($validator);
            return $this->validationError($code, $validator);
        }

        $user = User::findOrFail($request->user_id);

        try{
            $user->uid = $uId->body;
            $user->save();
    
            $uId->user_id = $user->id;
            $uId->is_purchased = 1;
            $uId->save();

            return $this->success('S101');
        }catch(QueryException $e){
            return $this->error('E200', '');
        }
    }

    public function removeUidFromUser($id) //Id or UID
    {
        $uId = SpecialUID::findOrFail($id);

        $user = $uId->user;

        try{
            $user->uid = $this->generateUID();
            $user->save();
    
            $uId->user_id = null;
            $uId->is_purchased = 0;
            $uId->save();

            return $this->success('S101');
        }catch(QueryException $e){
            return $this->error('E200', '');
        }
    }

    protected function generateUID()
    {
        $uId = rand(100000000, 999999999);
        
        if($this->uIDExists($uId)){
            $this->generateUID();
        }

        return $uId;
    }

    protected function uIDExists($uId)
    {
        if(SpecialUID::where('body', $uId)->exists()){
            return true;
        }

        return false;
    }
}
