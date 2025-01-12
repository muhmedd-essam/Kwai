<?php

namespace App\Http\Controllers\Api\HostingAgency;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Traits\MobileTrait;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Validator;
use App\Models\HostingAgency\HostingAgency;
use App\Models\HostingAgency\HostingAgencyMember;
use App\Models\HostingAgency\MemberRequest;

class HostingMembersController extends Controller
{
    use MobileTrait;
    
    public function search(Request $request)
    {
        $rules = [
            'q' => ['required', 'min:1'],
        ];

        $validator = Validator::make($request->all(), $rules);
        if($validator->fails()) {
            return $this->validationError(422, 'The given data was invalid.', $validator);
        }

        $hostingAgencies = HostingAgency::where('name', 'LIKE', '%'.$request->q.'%')->orWhere('aid', 'LIKE', '%'.$request->q.'%')->get();

        return $this->data($hostingAgencies);
    }

    public function joinRequest($id)
    {
        $user = auth()->user();
        $hostingAgency = HostingAgency::findOrFail($id);

        if($user->is_hosting_agency_owner == 1 || $user->is_hosting_agent == 1)
        {
            return $this->error('أنت إما صاحب وكالة أو مضيف في وكالة اخرى، برجاء مغادرة وكالتك اولا ', 403);
        }

        if(MemberRequest::where('user_id', $user->id)->where('hosting_agency_id', $hostingAgency->id)->exists()){
            return $this->error('لقد أرسلت طلب إنضمام بالفعل', 403);
        }

        try{
            MemberRequest::insert(['user_id' => $user->id, 'hosting_agency_id' => $hostingAgency->id, 'created_at' => now(), 'updated_at' => now()]);
            
            return $this->successWithoutData('تم إرسال الطلب');
        }catch(QueryException $e){
            // return $e;
            return $this->error500();
        }
    }

    public function leave()
    {
        $user = auth()->user();

        if(!$user->hostingAgencyMember){
            return $this->error('انت مش في وكالة يبنلعرص', 403);
        }

        $user->is_hosting_agent = 0;
        $user->save();
        
        $user->hostingAgencyMember()->delete();

        return $this->successWithoutData('تمت مغادرة الوكالة بنجاح');
    }
}
