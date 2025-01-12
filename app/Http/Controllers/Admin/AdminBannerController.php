<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Database\QueryException;
use App\Traits\WebTrait;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\File;
use App\Models\User;
use App\Models\Banner;
use App\Models\Rooms\Room;

class AdminBannerController extends Controller
{
    use WebTrait;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $banners = Banner::orderBy('id', 'DESC')->paginate(12);

        return $this->data($banners);
    }

    public function activeBanners()
    {
        $banners = Banner::whereDate('valid_to', '>=', now()->format('Y-m-d'))->orderBy('id', 'DESC')->paginate(12);

        return $this->data($banners);
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
            'cover_image' => ['required', 'mimes:jpeg,png,jpg'],
            'related_to' => ['required', 'numeric', 'in:0,1,2'],
            'related_to_id' => ['required_if:related_to,0,1', 'numeric'],
            'valid_to' => ['required', 'date', 'after:now'],
        ];

        $validator = Validator::make($request->all(), $rules);
        if($validator->fails()) {
            $code = $this->returnCodeAccordingToInput($validator);
            return $this->validationError($code, $validator);
        }

        //Upload
        $cover = Storage::disk('public')->putFile('images/banners/covers', new File($request->cover_image));

        $request->request->add(['cover' => $cover]);

        if($request->related_to == 0) // Room
        {
            $room = Room::find($request->related_to_id);
            if(!$room){
                return $this->error('400' ,'Invalid Room ID');
            }

            $request->request->add(['related_to_id' => $room->id, 'related_to_type' => get_class($room)]);
        }elseif($request->related_to == 2){
            //
        }
        else{ //Game
            return $this->error('E400', 'لسه مجيبناش والله يصاحبي', 400);
        }

        //DB:
        try{
            $banner = Banner::create($request->all());
            
            return $this->success('S100'); //Success Insert
        }catch(QueryException $e){
            // return $e;
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
        $banner = Banner::findOrFail($id);

        return $this->data($banner);
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
        $banner = Banner::findOrFail($id);

        $rules = [
            'cover_image' => ['mimes:jpeg,png,jpg'],
            'related_to' => ['required', 'numeric', 'in:0,1,2'],
            'related_to_id' => ['required', 'numeric'],
            'valid_to' => ['date', 'after:now'],
        ];

        $validator = Validator::make($request->all(), $rules);
        if($validator->fails()) {
            $code = $this->returnCodeAccordingToInput($validator);
            return $this->validationError($code, $validator);
        }

        if($request->hasFile('cover_image')){
            //Upload
            $cover = Storage::disk('public')->putFile('images/banners/covers', new File($request->cover_image));
    
            $request->request->add(['cover' => $cover]);
        }

        if($request->related_to == 0) // Room
        {
            $room = Room::find($request->related_to_id);
            if(!$room){
                return $this->error('400' ,'Invalid Room ID');
            }
            
            $request->request->add(['related_to_id' => $room->id, 'related_to_type' => get_class($room)]);
        }elseif($request->related_to == 2){
            //
        }
        else{ //Game
            return $this->error('E400', 'لسه مجيبناش والله يصاحبي', 400);
        }

        //DB:
        try{
            $banner->update($request->all());
            
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
        $banner = Banner::findOrFail($id);

        $banner->delete();

        return $this->success('S103', 'Banner Deleted Successfully');
    }
}
