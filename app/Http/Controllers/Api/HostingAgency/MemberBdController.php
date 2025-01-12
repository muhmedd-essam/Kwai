<?php

namespace App\Http\Controllers\Api\HostingAgency;

use App\Http\Controllers\Controller;
use App\Models\HostingAgency\HostingAgencyMember;
// use App\Models\HostingAgency\HostingAgencyMemberBd;

use App\Traits\MobileTrait;

use Illuminate\Http\Request;

class MemberBdController extends Controller
{
    use MobileTrait;

    public function index(){
        $user = auth()->user();
        $memberBds = HostingAgencyMemberBd::where('user_id', $user->id)->with('agency')->get();
        $salary = 0;
        $agencies = [];
        foreach($memberBds as $memberBd){
            $membersAgency = HostingAgencyMember::where('hosting_agency_id', $memberBd->agency->id)->with('target')->get();
            $agencies [] =$memberBd->agency;
            foreach($membersAgency as $memberAgency){
                $salary += $memberAgency->target->salary;
                $salary += $memberAgency->target->owner_salary;
            }

        }
        $memberBd = HostingAgencyMemberBd::where('user_id', $user->id)->with('user', 'target', 'owner')->first();
        $memberBd->user->allSalaryYourAgencies = $salary;
        $memberBd->user->yourSalaryIs = $memberBd->target->bd_salary;
        $memberBd->agencies = $agencies;
        return $this->data($memberBd);
    }

}
