<?php

namespace App\Http\Controllers\Admin\HostingAgency;

use App\Http\Controllers\Controller;
use App\Models\HostingAgency\HostingAgencyMember;
use App\Models\hostingagency\HostingAgencyMemberBd;
use App\Models\User;
use App\Traits\MobileTrait;

use Illuminate\Http\Request;

class AdminMemberBdController extends Controller
{
    use MobileTrait;

    public function index(){
        $allMemberBd = HostingAgencyMemberBd::with('user', 'agency', 'owner')->get();
        return $this->data($allMemberBd);
    }

    public function indexOwnerBd(){
        $allOwnerBd = HostingAgencyMemberBd::with('owner')->distinct('owner_bd')->get();
        return $this->data($allOwnerBd);
    }

    public function showOwnerBdMembers($id){
        $allOwnerBd = HostingAgencyMemberBd::where('owner_bd', $id)->with('user')->get();
        return $this->data($allOwnerBd);
    }


    public function show($id){
        $memberBds = HostingAgencyMemberBd::where('user_id', $id)->with('agency')->get();
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
        $memberBd = HostingAgencyMemberBd::where('user_id', $id)->with('user', 'target')->first();

        $memberBd->user->allSalaryYourAgencies = $salary;
        $memberBd->user->yourSalaryIs = $memberBd->target->bd_salary;
        $memberBd->agencies = $agencies;
        return $this->data($memberBd->user);
    }


}
