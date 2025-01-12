<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CategoryGift;
use App\Models\Gift;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;

class CategoryGiftsController extends Controller
{
    public function index(){
        $Categories = CategoryGift::with('Gift')->orderBy('id', 'ASC')->paginate(12);

        return response()->json($Categories);

    }

    public function store(Request $request){
        $rules = [
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'type' => ['in:0,1,2'],
        ];

        $validator = Validator::make($request->all(), $rules);
        if($validator->fails()) {
            $code = $this->returnCodeAccordingToInput($validator);
            return $this->validationError($code, $validator);
        }



        try{
            $category= new CategoryGift();
            $category->name = $request->name;
            $category->type = $request->type;
            $category->save();
            // $category = CategoryGift::create($request->all());

            return response()->json(['message' =>'success', 'data'=> $category]); //Success Insert
        }catch(QueryException $e){
            return $this->error('E200', ''); //DB err(General)
        }
    }


    public function show($id){
        $category= CategoryGift::find($id);
        if (!$category) {
            return response()->json(['message' => 'Category not found'], 404);
        }
        return response()->json($category);
    }


    public function update($id , Request $request){
        $rules = [
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'type' => ['in:0,1,2'],
        ];


        $validator = Validator::make($request->all(), $rules);

        if($validator->fails()) {
            $code = $this->returnCodeAccordingToInput($validator);
            return $this->validationError($code, $validator);
        }
        $name= $request->input('name');

        $category= CategoryGift::find($id);
        if (!$category) {
            return response()->json(['message' => 'category not found'], 404);
        }
        $category->update([
            'name'=> $request->input('name'),
            'type'=> $request->input('type'),
        ]);
        return response()->json(['message'=>'success', 'data'=>$category]);
    }

    public function destroy($id){
        $category= CategoryGift::find($id);
        if (!$category) {
            return response()->json(['message' => 'category not found'], 404);
        }
        $category->delete();
        return response()->json(['message'=>'success delete', 'data'=>$category]);
    }


}
