<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Arr;
use App\Traits\MobileTrait;
use App\Models\Level;
use App\Models\VideoGifts\VideoGiftGenere;
use App\Models\VideoGifts\VideoGift;
use App\Models\Rooms\Video\VideoRoom;
use App\Models\Rooms\Video\VideoRoomMember;
use App\Models\HostingAgency\HostingAgencyVideoDiamondPerformance;
use App\Models\HostingAgency\HostingAgencyVideoHourPerformance;
use App\Models\HostingAgency\HostingAgencyVideoTarget;
use App\Events\VideoGlobalGiftBar;
use App\Events\VideoGiftSent;
use App\Models\GlobalBox;

class VideoGiftController extends Controller
{
    use MobileTrait;

    public function index()
    {
        $generes = VideoGiftGenere::with('gifts')->orderBy('name', 'ASC')->get();

        return $this->dataPaginated($generes);
    }

    public function sendGift(Request $request, $id)
    {
        $rules = [
            'gift_id' => ['required', 'numeric'],
            'quantity' => ['required', 'numeric', 'min:1', 'max:1000'],
        ];

        $validator = Validator::make($request->all(), $rules);
        if($validator->fails()) {
            return $this->validationError(422, 'The given data was invalid.', $validator);
        }

        $room = VideoRoom::findOrFail($id);
        $gift = VideoGift::findOrFail($request->gift_id);
        $sender = auth()->user();
        $reciever = $room->owner;

        //Validate sender Balance
        if($sender->diamond_balance < $gift->price * $request->quantity){
            return $this->error('عفوا رصيدك لا يكفي', 403);
        }

        //Validate that the sender and the reciever is in the same room of the request
        if(!VideoRoomMember::where('user_id', $sender->id)->where('video_room_id', $room->id)->exists())
        {
            return $this->error('يجب أن يكون المرسل في الغرفه', 403);
        }

        if($gift->type == 1)
        {
            if($gift->sending_counter < $gift->required_sending_counter)
            {
                $actualGiftIdIndex = array_rand($gift->related_gift_ids);
                
                $actualGift = VideoGift::findOrFail($gift->related_gift_ids[$actualGiftIdIndex]);
                
                $gift->sending_counter+= 1;
                $gift->save();
            }else{
                $gift->sending_counter = 0;
                $gift->save();

                $actualGift = VideoGift::findOrFail($gift->surprise_gift_id);
            }

        }else{
            $actualGift = $gift;
        }

        try{
            if($gift->type == 2) //multiply gift
            {
                $winMultiply = $this->multiplyGift($gift->price * $request->quantity, $reciever);
            }else{
                $winMultiply = null;
            }

            //Subtract sender balance
            $sender->diamond_balance-= $gift->price * $request->quantity;
            $sender->save();

            //Give Balance to reciever
            if($gift->type != 2){
                $singleDiamondPrice = 0.00075;
                $reciever->money+= $singleDiamondPrice * (($actualGift->price * $request->quantity) * 0.5);
                $reciever->save();
            

            /* Hosting Agency */
            if($reciever->is_hosting_agent == 1){
                $agencyMembership = $reciever->hostingAgencyMember()->with('agency')->first();

                HostingAgencyVideoDiamondPerformance::insert(['agency_member_id' => $agencyMembership->id, 'supporter_id' => $sender->id, 'hosting_agency_id' => $agencyMembership->agency->id, 'amount' => $actualGift->price * $request->quantity, 'created_at' => now(), 'updated_at' => now()]);

                $this->levelVideoTargetUp($agencyMembership);
            }
        }
        
            //Fire Events
            VideoGiftSent::dispatch($actualGift, $sender, $request->quantity, $room->id, $winMultiply);

            //Global Rooms Bar
            if($actualGift->price * $request->quantity >= 1000){
                VideoGlobalGiftBar::dispatch($actualGift, $request->quantity, $sender, $reciever, $room->id);
            }
            
            //Handle user level
            $sender->exp_points += $actualGift->price * $request->quantity;
            $sender->save();
            $this->levelUserUp($sender);

            $sender = $sender->refresh();
            return $this->success($sender->diamond_balance, 'تم إرسال الهدية بنجاح');
        }catch(QueryException $e){
            return $this->error500();
        }
    }

    protected function levelUserUp($user)
    {
        $currentLevel = $user->level()->first(); //????
        $nextLevel = Level::where('number', $currentLevel->number + 1)->first();

        if($nextLevel && $user->exp_points >= $nextLevel->required_exp)
        {
            $user->level_id = $nextLevel->id;

            if($user->exp_points > $nextLevel->required_exp){
                $user->exp_points = $user->exp_points - $nextLevel->required_exp;
            }else{
                $user->exp_points = 0;
            }

            $user->save();

            $this->levelUserUp($user);
        }else{
            return;
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

    protected function multiplyGift($bit, $host)
    {
        $globalBox = GlobalBox::find(1);
        $user = auth()->user();

        //increament times played
        $globalBox->times_played+=1;
        $globalBox->save();

        //Give host 20%
        $singleDiamondPrice = 0.00075;
        $host->money+= $singleDiamondPrice * ($bit * 0.2);
        // $host->diamond_balance+= $bit * 0.2;
        $host->save();

        //check global box's balance
        if($globalBox->amount < $bit * 2){ // bit * 2 => x1
            $globalBox->amount+= $bit * 0.7;
            $globalBox->in_box+= $bit * 0.7;

            $globalBox->save();

            return 0;
        }

        //get a lose or a win 2/3
        $winOrLosePossibilities = [1, 2, 3, 4, 5];
        if(Arr::random($winOrLosePossibilities) != 1){
            $globalBox->amount+= $bit * 0.7;
            $globalBox->in_box+= $bit * 0.7;
            $globalBox->save();

            return 0;
        }

        //get global box's max multiplying
        switch (true) {
            case $globalBox->amount >= $bit * 540:
                $maxMultiply = 500;
                break;
            case $globalBox->amount >= $bit * 22:
                $maxMultiply = 10;
                break;
            case $globalBox->amount >= $bit * 20:
                $maxMultiply = 9;
                break;
            case $globalBox->amount >= $bit * 18:
                $maxMultiply = 8;
                break;
            case $globalBox->amount >= $bit * 16:
                $maxMultiply = 7;
                break;
            case $globalBox->amount >= $bit * 14:
                $maxMultiply = 6;
                break;
            case $globalBox->amount >= $bit * 12:
                $maxMultiply = 5;
                break;
            case $globalBox->amount >= $bit * 10:
                $maxMultiply = 4;
                break;
            case $globalBox->amount >= $bit * 8:
                $maxMultiply = 3;
                break;
            case $globalBox->amount >= $bit * 6:
                $maxMultiply = 2;
                break;
            case $globalBox->amount >= $bit * 4:
                $maxMultiply = 1;
                break;
            default:
                $globalBox->amount+= $bit * 0.7;
                $globalBox->in_box+= $bit * 0.7;
                $globalBox->save();

                return 0;
                break;
        }

        //set possibilities of multiplying
        switch ($maxMultiply) {
            case 500:
                $multiplyingPossibilities = [1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,2,2,2,2,2,2,2,2,2,2,2,2,2,2,2,2,2,2,2,2,3,3,3,3,3,3,3,3,3,3,3,3,3,3,3,3,3,3,4,4,4,4,4,4,4,4,4,4,4,4,4,4,4,4,5,5,5,5,5,5,5,5,5,5,5,5,5,5,6,6,6,6,6,6,6,6,6,6,6,6,7,7,7,7,7,7,7,7,7,7,8,8,8,8,8,8,8,8,9,9,9,9,9,9,10,10,10,10,500];
                break;
            case 10:
                $multiplyingPossibilities = [1,1,1,1,1,1,1,1,1,1,2,2,2,2,2,2,2,2,2,3,3,3,3,3,3,3,3,4,4,4,4,4,4,4,4,5,5,5,5,5,5,6,6,6,6,6,7,7,7,7,8,8,8,9,9,10];
                break;
            case 9:
                $multiplyingPossibilities = [1,1,1,1,1,1,1,1,1,1,2,2,2,2,2,2,2,2,2,3,3,3,3,3,3,3,3,4,4,4,4,4,4,4,4,5,5,5,5,5,5,6,6,6,6,6,7,7,7,7,8,8,8,9,9];
                break;
            case 8:
                $multiplyingPossibilities = [1,1,1,1,1,1,1,1,1,1,2,2,2,2,2,2,2,2,2,3,3,3,3,3,3,3,3,4,4,4,4,4,4,4,4,5,5,5,5,5,5,6,6,6,6,6,7,7,7,7,8,8,8];
                break;
            case 7:
                $multiplyingPossibilities = [1,1,1,1,1,1,1,1,1,1,2,2,2,2,2,2,2,2,2,3,3,3,3,3,3,3,3,4,4,4,4,4,4,4,4,5,5,5,5,5,5,6,6,6,6,6,7,7,7,7];
                break;
            case 6:
                $multiplyingPossibilities = [1,1,1,1,1,1,1,1,1,1,2,2,2,2,2,2,2,2,2,3,3,3,3,3,3,3,3,4,4,4,4,4,4,4,4,5,5,5,5,5,5,6,6,6,6,6];
                break;
            case 5:
                $multiplyingPossibilities = [1,1,1,1,1,1,1,1,1,1,2,2,2,2,2,2,2,2,2,3,3,3,3,3,3,3,3,4,4,4,4,4,4,4,4,5,5,5,5,5,5];
                break;
            case 4:
                $multiplyingPossibilities = [1,1,1,1,1,1,1,1,1,1,2,2,2,2,2,2,2,2,2,3,3,3,3,3,3,3,3,4,4,4,4,4,4,4,4];
                break;
            case 3:
                $multiplyingPossibilities = [1,1,1,1,1,1,1,1,1,1,2,2,2,2,2,2,2,2,2,3,3,3,3,3,3,3,3];
                break;
            case 2:
                $multiplyingPossibilities = [1,1,1,1,1,1,1,1,1,1,2,2,2,2,2,2,2,2,2];
                break;
            case 1:
            $multiplyingPossibilities = [1,1,1,1,1,1,1,1,1,1];
                break;            
            default:
                $globalBox->amount+= $bit * 0.7;
                $globalBox->in_box+= $bit * 0.7;
                $globalBox->save();

                return 0;
                break;

            }
        
        $winMultiply = Arr::random($multiplyingPossibilities);
        
        if($winMultiply == 500){
            if(Carbon::parse($globalBox->last_500)->format('Y-m-d') == Carbon::parse(now())->format('Y-m-d')){
                $winMultiply = Arr::random($multiplyingPossibilities);
            }
        }

        $user->diamond_balance+= $bit * $winMultiply;
        
        $globalBox->amount-= $bit * $winMultiply;
        $globalBox->out_box+= $bit * $winMultiply;
        $globalBox->save();

        return $winMultiply;
    }
}
