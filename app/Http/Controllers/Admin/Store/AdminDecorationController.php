<?php

namespace App\Http\Controllers\Admin\Store;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Database\QueryException;
use App\Traits\WebTrait;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\File;
use App\Models\Store\Decoration;

class AdminDecorationController extends Controller
{
    use WebTrait;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $decorations = Decoration::withCount('users')->paginate(12);

        return $this->data($decorations);
    }

    /**
     * Display Decoration by type
     * @accept [frame, entry, chat_bubble, room_background, room_frame]
     */
    public function getDecorationsByType(Request $request)
    {
        $rules = [
            'type' => ['required', 'string', 'in:frame,entry,chat_bubble,room_background,room_frame'],
        ];           

        $validator = Validator::make($request->all(), $rules);
        if($validator->fails()) {
            $code = $this->returnCodeAccordingToInput($validator);
            return $this->validationError($code, $validator);
        }

        $data = Decoration::where('type', $request->type)->paginate(12);

        return $this->data($data);
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
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'type' => ['required', 'string', 'in:frame,entry,chat_bubble,room_background,room_frame'],
            'cover_image' => ['required', 'mimes:jpeg,png,jpg'],
            'svga_image' => ['required_if:type,frame,entry'],
            'is_free' => ['required', 'in:0,1'],
            'price' => ['required', 'numeric'],
            'currency_type' => ['required', 'in:diamond,gold'],
            'valid_days' => ['required', 'numeric'],
        ];

        $validator = Validator::make($request->all(), $rules);
        if($validator->fails()) {
            $code = $this->returnCodeAccordingToInput($validator);
            return $this->validationError($code, $validator);
        }

        //Upload
        $cover = Storage::disk('public')->putFile('images/decorations', new File($request->cover_image));
        $request->request->add(['cover' => $cover]);

        if($request->hasFile('svga_image')){
            // $file = $request->file('svga_image');
            // $file->move(base_path('\storage\app\public\images\decorations'), $uniqueName . '.svga');
            
            $uniqueName = $this->generateUniqueFileName();
            $fileName = $uniqueName . '.svga';

            $svga = $request->file('svga_image')->storeAs('/public/svgas', $fileName);

            $fullFileName = 'svgas/' . $fileName;

            $request->request->add(['svga' => $fullFileName]);
        }

        //DB:
        try{
            $decorations = Decoration::create($request->all());
            
            return $this->success('S100'); //Success Insert
        }catch(QueryException $e){
            //return $e;
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
        $decoration = Decoration::withCount('users')->findOrFail($id);

        return $this->data($decoration);
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
        $decoration = Decoration::findOrFail($id);

        $rules = [
            'name' => ['string', 'min:2', 'max:255'],
            'type' => ['string', 'in:frame,entry,chat_bubble,room_background,room_frame'],
            'cover_image' => ['mimes:jpeg,png,jpg', 'max:20048'],
            'svga_image' => ['max:50048'],
            'is_free' => ['in:0,1'],
            'price' => ['numeric'],
            'currency_type' => ['in:diamond,gold'],
            'valid_days' => ['numeric'],
        ];

        $validator = Validator::make($request->all(), $rules);
        if($validator->fails()) {
            $code = $this->returnCodeAccordingToInput($validator);
            return $this->validationError($code, $validator);
        }

        //Upload
        if($request->hasFile('cover_image')){
            //Remove old image
            Storage::disk('public')->delete($decoration->cover);
            $cover = Storage::disk('public')->putFile('images/decorations', new File($request->cover_image));
            $request->request->add(['cover' => $cover]);
        }
        
        if($request->hasFile('svga_image')){
            // $file = $request->file('svga_image');
            // $file->move(base_path('\storage\app\public\images\decorations'), $uniqueName . '.svga');
            
            $uniqueName = $this->generateUniqueFileName();
            $fileName = $uniqueName . '.svga';

            $svga = $request->file('svga_image')->storeAs('/public/svgas', $fileName);

            $fullFileName = 'svgas/' . $fileName;

            $request->request->add(['svga' => $fullFileName]);
        }

        //DB:
        try{
            $decoration->update($request->all());
            
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
        $decoration = Decoration::findOrFail($id);

        //Delete Images
        Storage::disk('public')->delete($decoration->cover);
        if($decoration->gif != null){
            Storage::disk('public')->delete($decoration->gif);
        }

        $decoration->delete();
        
        return $this->success('S103');
    }

    /**
     * Give certain decoration to user
     */
    public function giveToUser(Request $request, $id) //ID of decoration
    {
        $decoration = Decoration::findOrFail($id);

        $rules = [
            'user_id' => ['required', 'numeric', 'exists:users,id'],
        ];

        $validator = Validator::make($request->all(), $rules);
        if($validator->fails()) {
            $code = $this->returnCodeAccordingToInput($validator);
            return $this->validationError($code, $validator);
        }

        $user = User::findOrFail($request->user_id);

        /* Manual Validation */
        if($user->decorations->contains($decoration->id)){
            return $this->error('E500', 'المستخدم يمتلك هذا العنصر بالفعل');
        }

        try{
            $user->decorations()->attach($decoration->id);
            return $this->success('S100');
        }catch(QueryException $e){
            return $this->error('E200', '');
        }
    }

    /**
     * Remove certain decoration from user
     */
    public function removeFromUser(Request $request, $id)
    {
        $decoration = Decoration::findOrFail($id);

        $rules = [
            'user_id' => ['required', 'numeric', 'exists:users,id'],
        ];

        $validator = Validator::make($request->all(), $rules);
        if($validator->fails()) {
            $code = $this->returnCodeAccordingToInput($validator);
            return $this->validationError($code, $validator);
        }

        $user = User::findOrFail($request->user_id);

        /* Manual Validation */
        if(!$user->decorations->contains($decoration->id)){
            return $this->error('E500', 'المستخدم لا يمتلك هذا العنصر');
        }

        try{
            $user->decorations()->detach($decoration->id);
            return $this->success('S103');
        }catch(QueryException $e){
            return $this->error('E200', '');
        }
    }

    /**
     * List all user's decoration
     */
    public function getUserDecoration($id) //ID of user
    {
        $user = User::with('decorations')->findOrFail($id);

        return $this->data($user);
    }

    protected function generateUniqueFileName()
    {
        $length = 20;
        $chars='0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $totalChars = strlen($chars);
        $totalRepeat = ceil($length/$totalChars);
        $repeatString = str_repeat($chars, $totalRepeat);
        $shuffleString = str_shuffle($repeatString);
        $fileName = substr($shuffleString,1,$length);

        if($this->fileNameExists($fileName)){
            $this->generateUniqueFileName();
        }

        return $fileName;
    }

    protected function fileNameExists($fileName)
    {
        if(Decoration::where('name', $fileName)->exists()){
            return true;
        }

        return false;
    }
    
}
