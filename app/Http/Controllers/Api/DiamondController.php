<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\DiamondPackage;
use App\Traits\MobileTrait;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class DiamondController extends Controller
{
    use MobileTrait;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $diamondPackages = DiamondPackage::orderBy('price')->get();

        return $this->data($diamondPackages);
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
     * Purchase the specified Diamond Package
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function purchase(Request $request, $id)
    {
        $diamondPackage = DiamondPackage::findOrFail($id);
        $user = auth()->user();

        $user->diamond_balance+= $diamondPackage->quantity;
        $user->save();

        return $this->successWithoutData('تمت عملية الشحن بنجاح، شكرا لإستخدامك fun chat. نتمنى لك قضاء وقت ممتع');
    }

}
