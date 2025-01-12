<?php

namespace App\Http\Controllers\Admin\HostingAgency;

use App\Http\Controllers\Controller;
use App\Models\HostingAgency\HostingAgencyTargetBd;

use Illuminate\Http\Request;
use App\Traits\MobileTrait;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Database\QueryException;
use App\Traits\WebTrait;

class TargetsBdController extends Controller
{
    use MobileTrait;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    public function index()
    {
        $targets = HostingAgencyTargetBd::withCount('agenciesMembersBd')->orderBy('target_no', 'ASC')->get();

        return $this->data($targets);
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
             'salary_required' => ['required', 'numeric', 'min:1'],
             'bd_salary' => ['required', 'numeric', 'min:1'],
         ];

         $validator = Validator::make($request->all(), $rules);
         if($validator->fails()) {
             $code = $this->returnCodeAccordingToInput($validator);
             return $this->validationError($code, $validator);
         }

         $lastTargetNo = HostingAgencyTargetBd::latest()->first();

        // if there is no target >> make first target this will be 0
         if (is_null($lastTargetNo)) {
            $newTarget = HostingAgencyTargetBd::create([
                'target_no' => 0,
                'salary_required' => 0,
                'bd_salary' => 0,
            ]);
            $lastTargetNo = HostingAgencyTargetBd::latest()->first();
        }

         $targetNo = $lastTargetNo->target_no + 1;

         try{
             $target = HostingAgencyTargetBd::insert(['target_no' => $targetNo, 'salary_required' => $request->salary_required, 'bd_salary' => $request->bd_salary, 'created_at' => now(), 'updated_at' => now(),]);

             return $this->success('S100');
         }catch(QueryException $e){
             return $this->error('E200', '');
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
        $targets = HostingAgencyTargetBd::with('agenciesMembersBd.user', 'agenciesMembersBd.agency')->withCount('agenciesMembersBd')->findOrFail($id);

        return $this->data($targets);
    }

    public function update(Request $request, $id)
    {
        $target = HostingAgencyTargetBd::findOrFail($id);

        $rules = [
            'salary_required' => ['required', 'numeric', 'min:1'],
            'bd_salary' => ['required', 'numeric', 'min:1'],
        ];

        $validator = Validator::make($request->all(), $rules);
        if($validator->fails()) {
            $code = $this->returnCodeAccordingToInput($validator);
            return $this->validationError($code, $validator);
        }

        try{
            $update = $request->all();

            foreach($update as $key => $value) {
                $target->$key = $value;
            }
            $target->save();

            return $this->success('S101');
        }catch(QueryException $e){
            return $this->error('E200', '');
        }
    }

}
