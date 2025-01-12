<?php

namespace App\Http\Controllers\Admin\HostingAgency;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Database\QueryException;
use App\Traits\WebTrait;
use App\Models\HostingAgency\HostingAgencyVideoTarget;

class VideoTargetsController extends Controller
{
    use WebTrait;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $targets = HostingAgencyVideoTarget::withCount('agenciesMembers')->orderBy('target_no', 'ASC')->get();

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
            'diamonds_required' => ['required', 'numeric', 'min:1'],
            'hours_required' => ['required', 'numeric', 'min:1'],
            'salary' => ['required', 'numeric', 'min:1'],
            'owner_salary' => ['required', 'numeric', 'min:1'],
        ];

        $validator = Validator::make($request->all(), $rules);
        if($validator->fails()) {
            $code = $this->returnCodeAccordingToInput($validator);
            return $this->validationError($code, $validator);
        }

        $lastTargetNo = HostingAgencyVideoTarget::latest()->first();
        $targetNo = $lastTargetNo->target_no + 1;

        try{
            $target = HostingAgencyVideoTarget::insert(['target_no' => $targetNo, 'diamonds_required' => $request->diamonds_required, 'hours_required' => $request->hours_required, 'salary' => $request->salary, 'owner_salary' => $request->owner_salary, 'created_at' => now(), 'updated_at' => now(),]);
            
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
        $targets = HostingAgencyVideoTarget::with('agenciesMembers.user', 'agenciesMembers.agency')->withCount('agenciesMembers')->findOrFail($id);

        return $this->data($targets);
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
        $target = HostingAgencyVideoTarget::findOrFail($id);

        $rules = [
            'diamonds_required' => ['numeric', 'min:1'],
            'hours_required' => ['numeric', 'min:1'],
            'salary' => ['numeric', 'min:1'],
            'owner_salary' => ['numeric', 'min:1'],
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
