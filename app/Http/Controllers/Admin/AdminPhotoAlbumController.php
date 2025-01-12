<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PhotoAlbum;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Database\QueryException;
use App\Traits\WebTrait;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\File;

class AdminPhotoAlbumController extends Controller
{
    use WebTrait;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $photoAlbum = PhotoAlbum::with('user')->paginate(12);

        return $this->data($photoAlbum);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id) //ID of user
    {
        $user = User::with('albums')->findOrFail($id);

        return $this->data($user);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $photoAlbum = PhotoAlbum::findOrFail($id);

        Storage::disk('public')->delete($photoAlbum->path);

        $photoAlbum->delete();

        return $this->success('S103');
    }
}
