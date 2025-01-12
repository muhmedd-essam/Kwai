<?php

namespace App\Http\Controllers\Admin\Rooms;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Database\QueryException;
use App\Traits\WebTrait;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\File;
use App\Models\Rooms\Emoji;

class AdminEmojisController extends Controller
{
    use WebTrait;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $emojis = Emoji::orderBy('id', 'DESC')->paginate(12);

        return $this->data($emojis);
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
            'cover_image' => ['required', 'mimes:jpeg,png,jpg,webp', 'max:500'],
            'body_image' => ['required', 'mimes:gif', 'max:1024'],
        ];

        $validator = Validator::make($request->all(), $rules);
        if($validator->fails()) {
            $code = $this->returnCodeAccordingToInput($validator);
            return $this->validationError($code, $validator);
        }

        //Upload
        $cover = Storage::disk('public')->putFile('images/emojis/covers', new File($request->cover_image));

        $gif = Storage::disk('public')->putFile('images/emojis/gifs', new File($request->body_image));

        $request->request->add(['cover' => $cover, 'body' => $gif]);

        //DB:
        try{
            $emoji = Emoji::create($request->all());
            
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
        $emoji = Emoji::findOrFail($id);
        
        return $this->data($emoji);
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
        $emoji = Emoji::findOrFail($id);

        $rules = [
            'cover_image' => ['mimes:jpeg,png,jpg,webp', 'max:500'],
            'body_image' => ['mimes:gif', 'max:1024'],
        ];

        $validator = Validator::make($request->all(), $rules);
        if($validator->fails()) {
            $code = $this->returnCodeAccordingToInput($validator);
            return $this->validationError($code, $validator);
        }

        //Upload
        if($request->hasFile('cover_image')){
            $cover = Storage::disk('public')->putFile('images/emojis/covers', new File($request->cover_image));

            $request->request->add(['cover' => $cover]);
            Storage::disk('public')->delete($emoji->cover);
        }
        
        if($request->hasFile('body_image')){
            $gif = Storage::disk('public')->putFile('images/emojis/gifs', new File($request->body_image));
            
            $request->request->add(['body' => $gif]);
            Storage::disk('public')->delete($emoji->body);
        }

        //DB:
        try{
            $emoji->update($request->all());

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
        $emoji = Emoji::findOrFail($id);

        Storage::disk('public')->delete($emoji->cover);
        Storage::disk('public')->delete($emoji->body);

        $emoji->delete();
        
        return $this->success('S103');
    }
}
