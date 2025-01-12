<?php

namespace App\Console\Commands;

use App\Models\HostingAgency\HostingAgencyDiamondPerformance;
use App\Models\HostingAgency\HostingAgencyMember;
use App\Models\HostingAgency\HostingAgencyTarget;
use App\Models\Rooms\RoomContribution;
use Illuminate\Console\Command;
use App\Models\User;

class MonthlyTask extends Command
{
    protected $signature = 'monthly:task';
    protected $description = 'Run tasks on the first day of each month';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $allUsers = User::all();
        $agencyMembers = HostingAgencyMember::all();

        foreach ($agencyMembers as $agencyMember) {
            $performance = HostingAgencyDiamondPerformance::where('agency_member_id', $agencyMember->user_id)->sum('amount');
            $performanceNext = HostingAgencyTarget::find($agencyMember->current_target_id);
            $resultPerfomance = $performance - $performanceNext->diamonds_required;
            if($resultPerfomance >= 0){
                HostingAgencyDiamondPerformance::insert([
                    'agency_member_id' => $agencyMember->id,
                    'supporter_id' => $agencyMember->user_id,
                    'hosting_agency_id' => $agencyMember->agency->id,
                    'amount' => $resultPerfomance,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }

            $agencyMember->current_target_id = 1;
            $agencyMember->save();
        }

        HostingAgencyDiamondPerformance::truncate();
        RoomContribution::truncate();

        foreach ($allUsers as $user) {
            if ($user->supported_send >= 100000){
                $user->diamond_balance = $user->supported_send * 0.1;
                $user->save();
            }
            $user->supported_send = 0;
            $user->supported_recieve = 0;
            $user->save();
        }
    }
}
