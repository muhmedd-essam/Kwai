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

use App\Models\Gift;
use GuzzleHttp\Client;
use Illuminate\Http\File;

class AdminGiftController extends Controller
{
    use WebTrait;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $gifts = Gift::orderBy('id', 'DESC')->paginate(12);

        return $this->data($gifts);
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
            'cover_image' => ['required', 'mimes:jpeg,png,jpg', 'max:2048'],
            'svga_image' => ['required', 'max:2048'],
            'price' => ['required', 'numeric'],

            'type' => ['in:0,1,2'],

            'category_gift_id' => 'required|exists:category_gifts,id',
        ];

        $validator = Validator::make($request->all(), $rules);
        if($validator->fails()) {
            $code = $this->returnCodeAccordingToInput($validator);
            return $this->validationError($code, $validator);
        }

        //Upload
        $cover = Storage::disk('public')->putFile('images/gifts', new File($request->cover_image));
        $request->request->add(['cover' => $cover]);

        //Svga
        $uniqueName = $this->generateUniqueFileName();
        $fileName = $uniqueName . '.svga';
        $svga = $request->file('svga_image')->storeAs('/public/svgas/gifts', $fileName);
        $fullFileName = 'svgas/gifts/' . $fileName;
        $request->request->add(['svga' => $fullFileName]);

        try{

            $decorations = Gift::create($request->all());
            return response()->json(['message'=>'تم تسجيل الهدية', 'gift'=>$decorations]); //Success Insert
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
        $gift = Gift::findOrFail($id);

        return $this->data($gift);
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
        $gift = Gift::findOrFail($id);

        $rules = [
            'name' => ['string', 'min:2', 'max:255'],
            'cover_image' => ['mimes:jpeg,png,jpg', 'max:2048'],
            'svga_image' => ['max:2048'],
            'price' => ['numeric'],
            'type' => ['in:0,1,2'],
        ];

        $validator = Validator::make($request->all(), $rules);
        if($validator->fails()) {
            $code = $this->returnCodeAccordingToInput($validator);
            return $this->validationError($code, $validator);
        }


        if($request->hasFile('cover_image')){
            $cover = Storage::disk('public')->putFile('images/gifts', new File($request->cover_image));
            $request->request->add(['cover' => $cover]);
        }

        if($request->hasFile('svga_image'))
        {
            $uniqueName = $this->generateUniqueFileName();
            $fileName = $uniqueName . '.svga';

            $svga = $request->file('svga_image')->storeAs('/public/svgas', $fileName);

            $fullFileName = 'svgas/' . $fileName;

            $request->request->add(['svga' => $fullFileName]);
        }

        try{
            $gift->update($request->all());

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
        $gift = Gift::findOrFail($id);

        //Delete Image
        Storage::disk('public')->delete($gift->cover);

        $gift->delete();

        return $this->success('S103');
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
        if(Gift::where('name', $fileName)->exists()){
            return true;
        }

        return false;
    }
}
