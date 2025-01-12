<?php

namespace App\Http\Controllers\Admin\HostingAgency;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Database\QueryException;
use App\Traits\WebTrait;
use App\Models\HostingAgency\HostingAgencyTarget;
use App\Models\User;

use App\Models\HostingAgency\HostingAgencyDiamondPerformance;
use App\Models\HostingAgency\HostingAgencyMember;
use App\Models\Rooms\RoomContribution;

class TargetsController extends Controller
{
    use WebTrait;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $targets = HostingAgencyTarget::withCount('agenciesMembers')->orderBy('target_no', 'ASC')->get();

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

        $lastTargetNo = HostingAgencyTarget::latest()->first();
        $targetNo = $lastTargetNo->target_no + 1;

        try{
            $target = HostingAgencyTarget::insert(['target_no' => $targetNo, 'diamonds_required' => $request->diamonds_required, 'hours_required' => $request->hours_required, 'salary' => $request->salary, 'owner_salary' => $request->owner_salary, 'created_at' => now(), 'updated_at' => now(),]);
            
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
    $targets = HostingAgencyTarget::with(['agenciesMembers.user', 'agenciesMembers.agency'])
                ->withCount('agenciesMembers')
                ->findOrFail($id);

    // قم بتصفية العناصر التي يكون فيها user null
    $membersToDelete = $targets->agenciesMembers->filter(function ($member) {
        return $member->user === null;
    });

    // حذف العناصر من قاعدة البيانات
    foreach ($membersToDelete as $member) {
        $member->delete();
    }

    // قم بتحديث agenciesMembers بالعناصر التي يكون فيها user غير null وأخذ أول 5 عناصر فقط
    $filteredMembers = $targets->agenciesMembers->filter(function ($member) {
        return $member->user !== null;
    })->slice(0, 5);

    // تحديث agenciesMembers بالعناصر المفلترة
    $targets->agenciesMembers = $filteredMembers->values();
    
    
        //  $allUsers = User::all();
        // $agencyMembers = HostingAgencyMember::all();

        // foreach ($agencyMembers as $agencyMember) {
        //     $performance = HostingAgencyDiamondPerformance::where('agency_member_id', $agencyMember->user_id)->sum('amount');
        //     $performanceNext = HostingAgencyTarget::find($agencyMember->current_target_id);
        //     $resultPerfomance = $performance - $performanceNext->diamonds_required;
        //     if($resultPerfomance >= 0){
        //         HostingAgencyDiamondPerformance::insert([
        //             'agency_member_id' => $agencyMember->id,
        //             'supporter_id' => $agencyMember->user_id,
        //             'hosting_agency_id' => $agencyMember->agency->id,
        //             'amount' => $resultPerfomance,
        //             'created_at' => now(),
        //             'updated_at' => now()
        //         ]);
        //     }

        //     $agencyMember->current_target_id = 1;
        //     $agencyMember->save();
        // }

        // HostingAgencyDiamondPerformance::truncate();
        // RoomContribution::truncate();

        // foreach ($allUsers as $user) {
        //     if ($user->supported_send >= 100000){
        //         $user->diamond_balance = $user->supported_send * 0.1;
        //         $user->save();
        //     }
        //     $user->supported_send = 0;
        //     $user->supported_recieve = 0;
        //     $user->save();
        // }

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
        $target = HostingAgencyTarget::findOrFail($id);

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
