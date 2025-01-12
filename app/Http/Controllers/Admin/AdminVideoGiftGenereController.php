<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\VideoGifts\VideoGift;
use App\Models\VideoGifts\VideoGiftGenere;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Database\QueryException;
use App\Traits\WebTrait;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\File;

class AdminVideoGiftGenereController extends Controller
{
    use WebTrait;
    
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $generes = VideoGiftGenere::withCount('gifts')->orderBy('id', 'DESC')->paginate(12);

        return $this->data($generes);
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
            'precentage' => ['required', 'numeric', 'max:100'],
        ];

        $validator = Validator::make($request->all(), $rules);
        if($validator->fails()) {
            $code = $this->returnCodeAccordingToInput($validator);
            return $this->validationError($code, $validator);
        }

        try{
            $gift = VideoGiftGenere::create($request->all());
            
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
        $genere = VideoGiftGenere::with('gifts')->withCount('gifts')->findOrFail($id);

        return $this->data($genere);
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
        $genere = VideoGiftGenere::findOrFail($id);

        $rules = [
            'name' => ['string', 'min:2', 'max:255'],
            'precentage' => ['numeric', 'max:100'],
        ];

        $validator = Validator::make($request->all(), $rules);
        if($validator->fails()) {
            $code = $this->returnCodeAccordingToInput($validator);
            return $this->validationError($code, $validator);
        }

        try{
            $genere->update($request->all());
            
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
        $genere = VideoGiftGenere::findOrFail($id);

        $genere->delete();

        return $this->success('S103');
    }
}
