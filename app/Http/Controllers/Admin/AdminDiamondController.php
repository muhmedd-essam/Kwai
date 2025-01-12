<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Database\QueryException;
use App\Traits\WebTrait;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\File;
use App\Models\DiamondPackage;

class AdminDiamondController extends Controller
{
    use WebTrait;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $diamondPackages = DiamondPackage::paginate(12);

        return $this->data($diamondPackages);
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
            'quantity' => ['required', 'numeric'],
            'price' => ['required', 'numeric'],
            'cover_image' => ['required', 'mimes:jpeg,png,jpg'],
        ];

        $validator = Validator::make($request->all(), $rules);
        if($validator->fails()) {
            $code = $this->returnCodeAccordingToInput($validator);
            return $this->validationError($code, $validator);
        }

        //Upload
        $cover = Storage::disk('public')->putFile('images/diamonds/covers', new File($request->cover_image));

        $request->request->add(['cover' => $cover]);

        //DB:
        try{
            $package = DiamondPackage::create($request->all());
            
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
        $diamondPackage = DiamondPackage::findOrFail($id);

        return $this->data($diamondPackage);
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
        $diamondPackage = DiamondPackage::findOrFail($id);

        $rules = [
            'quantity' => ['numeric'],
            'price' => ['numeric'],
            'cover_image' => ['mimes:jpeg,png,jpg'],
        ];

        $validator = Validator::make($request->all(), $rules);
        if($validator->fails()) {
            $code = $this->returnCodeAccordingToInput($validator);
            return $this->validationError($code, $validator);
        }

        if($request->hasFile('cover_image')){
            //Delete old cover
            Storage::disk('public')->delete($diamondPackage->cover);

            //Upload
            $cover = Storage::disk('public')->putFile('images/diamonds/covers', new File($request->cover_image));
            $request->request->add(['cover' => $cover]);
        }

        //DB:
        try{
            $diamondPackage->update($request->all());
            
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
        $diamondPackage = DiamondPackage::findOrFail($id);

        //Delete old cover
        Storage::disk('public')->delete($diamondPackage->cover);

        $diamondPackage->delete();
        
        return $this->success('S103');
    }

    public function chargeUser(Request $request)
    {
        $rules = [
            'amount' => ['required', 'numeric'],
            'user_id' => ['required', 'exists:users,id'],
        ];

        $validator = Validator::make($request->all(), $rules);
        if($validator->fails()) {
            $code = $this->returnCodeAccordingToInput($validator);
            return $this->validationError($code, $validator);
        }

        $user = User::findOrFail($request->user_id);

        //DB:
        try{
            $user->diamond_balance+= $request->amount;
            $user->save();

            return $this->success('S101');
        }catch(QueryException $e){
            return $this->error('E200', ''); //DB err(General)
        }
    }

    public function rollbackUser(Request $request)
    {
        $rules = [
            'amount' => ['required', 'numeric'],
            'user_id' => ['required', 'exists:users,id'],
        ];

        $validator = Validator::make($request->all(), $rules);
        if($validator->fails()) {
            $code = $this->returnCodeAccordingToInput($validator);
            return $this->validationError($code, $validator);
        }

        $user = User::findOrFail($request->user_id);

        //DB:
        try{
            $user->diamond_balance-= $request->amount;
            $user->save();

            return $this->success('S101');
        }catch(QueryException $e){
            return $this->error('E200', ''); //DB err(General)
        }
    }

}
