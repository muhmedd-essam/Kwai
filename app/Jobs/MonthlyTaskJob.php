<?php

namespace App\Jobs;

use App\Models\HostingAgency\HostingAgencyDiamondPerformance;
use App\Models\HostingAgency\HostingAgencyMember;
use App\Models\HostingAgency\HostingAgencyTarget;
use App\Models\Rooms\RoomContribution;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class MonthlyTaskJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $allUsers = User::all();
        $agencyMembers = HostingAgencyMember::all();

        foreach ($agencyMembers as $agencyMember) {
            $performance = HostingAgencyDiamondPerformance::where('agency_member_id', $agencyMember->user_id)->sum('amount');
            $performanceNext = HostingAgencyTarget::find($agencyMember->current_target_id);
            $resultPerfomance = $performance - $performanceNext->diamonds_required;
            if($resultPerfomance >= 0){
                HostingAgencyDiamondPerformance::insert(['agency_member_id' => $agencyMember->id, 'supporter_id' => $agencyMember->user_id, 'hosting_agency_id' => $agencyMember->agency->id, 'amount' => $resultPerfomance, 'created_at' => now(), 'updated_at' => now()]);

            }

            $agencyMember->current_target_id = 1;
            $agencyMember->save();
        }
        // delete data from daimond table and room contributtion table
        HostingAgencyDiamondPerformance::truncate();
        RoomContribution::truncate();
        // make support send and recieve = 0
        // supported sender and reciever Bonus

        foreach ($allUsers as $user) {

            if ($user->supported_send >= 100000){
                $user->diamond_balance = $user->supported_send * 0.1;
                $user->save();
            }
            $user->supported_send=0;
            $user->supported_recieve= 0;
            $user->save();

    }
}
}
