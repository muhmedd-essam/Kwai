<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Level;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Database\QueryException;
use App\Traits\WebTrait;

class AdminLevelsController extends Controller
{
    use WebTrait;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $levels = Level::orderBy('number', 'ASC')->withCount('users')->paginate(12);
        
        return $this->data($levels);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $level = Level::withCount('users')->findOrFail($id);

        return $this->data($level);
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
        $level = Level::findOrFail($id);

        $rules = [
            'required_exp' => ['required', 'numeric', 'min:0'],
        ];

        $validator = Validator::make($request->all(), $rules);
        if($validator->fails()) {
            $code = $this->returnCodeAccordingToInput($validator);
            return $this->validationError($code, $validator);
        }

        try{
            $level->update($request->all());

            return $this->success('S101', '');
        }catch(QueryException $e){
            return $this->error('E200', '');
        }

    }

    public function changeUserLevel(Request $request)
    {
        $rules = [
            'level_id' => ['required', 'numeric'],
            'user_id' => ['required', 'numeric'],
        ];

        $validator = Validator::make($request->all(), $rules);
        if($validator->fails()) {
            $code = $this->returnCodeAccordingToInput($validator);
            return $this->validationError($code, $validator);
        }

        $level = Level::findOrFail($request->level_id);
        $user = User::findOrFail($request->user_id);
        
        try{
            $user->level_id = $level->id;
            $user->exp_points = 0;
            $user->save();

            return $this->success('S101', '');
        }catch(QueryException $e){
            return $this->error('E200', '');
        }
    }

}
