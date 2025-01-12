<?php

namespace App\Http\Controllers\Agora;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Controllers\Agora\RtcTokenBuilder;
use DateTime;
use DateTimeZone;
use App\Models\User;
use App\Traits\MobileTrait;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Validator;

class TokenGeneratorController extends Controller
{
    use MobileTrait;
    
    public $appID;
    public $appCertificate;
    public $channelName;
    public $uid;
    public $uidStr;
    public $role;
    public $expireTimeInSeconds = 3600 * 24;
    public $currentTimestamp;
    public $privilegeExpiredTs;
    
    public function __construct($role, $channelName)
    {
        $this->uid = auth()->id();
        $this->uidStr = strval($this->uid);

        $this->appID = env('AGORA_APP_ID', '627cb831ca4843e5b4c93214ff18fe84');
        $this->appCertificate = env('AGORA_APP_CERTIFICATE', 'c5b7e88779f14ee5b106966944895527');

        $this->currentTimestamp = (new DateTime("now", new DateTimeZone('UTC')))->getTimestamp();
        $this->privilegeExpiredTs = $this->currentTimestamp + $this->expireTimeInSeconds;

        $this->channelName = $channelName;

        switch ($role) {
            case 0:
                $this->role = RtcTokenBuilder::RoleAttendee;
                break;
            case 1:
                $this->role = RtcTokenBuilder::RolePublisher;
                break;
            case 2:
                $this->role = RtcTokenBuilder::RoleSubscriber;
                break;
            case 101:
                $this->role = RtcTokenBuilder::RoleAdmin;
                break;
            default:
            return $this->error('invalid choise', 422);
          }

    }

    public function generateToken()
    {
        try{
            $token = RtcTokenBuilder::buildTokenWithUid($this->appID, $this->appCertificate, $this->channelName, $this->uid, $this->role, $this->privilegeExpiredTs);
            
            return $token;
        }catch(QueryException $e){
            return $this->error('something went wrong :(', 500);
        }
    }

}
