<?php

namespace App\Http\Controllers\Api\Rooms\Video;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Traits\MobileTrait;
use Illuminate\Database\QueryException;
use Carbon\Carbon;
use App\Models\Rooms\Video\VideoRoom;
use App\Models\HostingAgency\HostingAgencyVideoDiamondPerformance;
use App\Models\HostingAgency\HostingAgencyVideoHourPerformance;
use App\Models\HostingAgency\HostingAgencyVideoTarget;

class VideoRoomController extends Controller
{
    use MobileTrait;

    public function index()
    {
        $videoRooms = VideoRoom::with('owner')->withCount('members')->orderBy('id', 'DESC')->paginate(20);

        return $this->dataPaginated($videoRooms);
    }

    public function scorll($id)
    {
        $videoRoom = VideoRoom::with('owner')->withCount('members')->findOrFail($id);
        
        return $videoRoom->next();

        return $this->data($videoRoom);
    }

    public function show($id)
    {
        $videoRoom = VideoRoom::with('owner')->withCount('members')->findOrFail($id);

        return $this->data($videoRoom);
    }

    public function next($id)
    {
        $videoRoom = VideoRoom::with('owner')->withCount('members')->findOrFail($id);

        return $this->data($videoRoom->next());
    }

    public function previous($id)
    {
        $videoRoom = VideoRoom::with('owner')->withCount('members')->findOrFail($id);

        return $this->data($videoRoom->previous());
    }

    public function store(Request $request)
    {
        $user = auth()->user();

        if($user->profile_picture == null){
            return $this->error('برجاء إختيار صورة شخصية أولا', 403);
        }
        //Check if user is a member of agency here

        if($user->is_video_hosting == 1|| $user->is_video_cohosting == 1){
            return $this->error('you are not allowed to host in more than one room', 403);
        }

        try{
            VideoRoom::insert(['user_id' => $user->id, 'name' => $user->name, 'cover' => $user->profile_picture, 'language' => $user-> language, 'created_at' => now(), 'updated_at' => now()]);

            $user->is_video_hosting = 1;
            $user->save();

            $videoRoom = VideoRoom::where('user_id', $user->id)->first();

            return $this->success($videoRoom, 'Video room created successfully');
        }catch(QueryException $e){
            // return $e;
            return $this->error500();
        }
    }

    public function destroy()
    {
        $user = auth()->user();
        $videoRoom = VideoRoom::where('user_id', $user->id);


        if(!$videoRoom->exists()){
            return $this->error('you are not broadcasting currently!', 403);
        }

        $videoRoom = $videoRoom->first();

        //return statistics here
        try
        {
            /* Hosting Agency */
            if($user->is_hosting_agent == 1){
                $agencyMembership = $user->hostingAgencyMember()->with('agency')->first();
                $start = Carbon::parse($videoRoom->created_at);
                $end = Carbon::parse(now());
                $diff = $end->diffInMinutes ($start);
                $diff = round($diff/60, 2);

                if($diff > 0.25){

                    if($diff > 2){
                        $diff = 2;
                    }

                    $totalDuration = HostingAgencyVideoHourPerformance::where('agency_member_id', $agencyMembership->id)->whereDate('created_at', '=', now()->format('Y-m-d'))->sum('duration');
                    if($totalDuration < 2){
                        
                        $hoursLeft = 2 - $totalDuration;

                        if($diff > $hoursLeft){
                            $diff = $hoursLeft;
                        }

                        HostingAgencyVideoHourPerformance::insert(['agency_member_id' => $agencyMembership->id, 'hosting_agency_id' => $agencyMembership->agency->id, 'duration' => $diff, 'created_at' => now(), 'updated_at' => now()]);
                        
                        $this->levelVideoTargetUp($agencyMembership);
                    }
                }
            }
            $videoRoom->delete();
            
            $stats = null;

            $user->is_video_hosting = 0;
            $user->save();

            return $this->success($stats, 'Video room Deleted');
        }catch(QueryException $e){
            // return $e;
            return $this->error500();
        }

    }

    protected function levelVideoTargetUp($membership)
    {
        $currentTarget = $membership->videoTarget()->first();
        
        $nextTarget = HostingAgencyVideoTarget::where('target_no', $currentTarget->target_no + 1)->first();
        
        if($nextTarget && HostingAgencyVideoDiamondPerformance::where('agency_member_id', $membership->id)->sum('amount') >= $nextTarget->diamonds_required && HostingAgencyVideoHourPerformance::where('agency_member_id', $membership->id)->sum('duration') >= $nextTarget->hours_required){
            
            $membership->current_target_video_id = $nextTarget->id;
            $membership->save();
            $this->levelVideoTargetUp($membership);
        }else{
            return;    
        }
    }

}
