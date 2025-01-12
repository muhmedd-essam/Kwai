<?php

namespace App\Models\HostingAgency;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\HostingAgency\HostingAgency;
use App\Models\HostingAgency\HostingAgencyTarget;
use App\Models\HostingAgency\HostingAgencyDiamondPerformance;
use App\Models\HostingAgency\HostingAgencyHourPerformance;
use App\Models\HostingAgency\HostingAgencyVideoDiamondPerformance;
use App\Models\HostingAgency\HostingAgencyVideoHourPerformance;
use App\Models\HostingAgency\HostingAgencyVideoTarget;

class HostingAgencyMember extends Model
{
    use HasFactory;

    protected $fillable=[
        'hosting_agency_id',
        'user_id',
        'current_target_id',
        'current_target_video_id'
    ];

    public function agency()
    {
        return $this->belongsTo(HostingAgency::class, 'hosting_agency_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function target()
    {
        return $this->belongsTo(HostingAgencyTarget::class, 'current_target_id');
    }

    public function videoTarget()
    {
        return $this->belongsTo(HostingAgencyVideoTarget::class, 'current_target_video_id');
    }

    public function diamondPerformance()
    {
        return $this->hasMany(HostingAgencyDiamondPerformance::class ,'agency_member_id');
    }

    public function videoDiamondPerformance()
    {
        return $this->hasMany(HostingAgencyVideoDiamondPerformance::class ,'agency_member_id');
    }

    public function videoHoursPerformance()
    {
        return $this->hasMany(HostingAgencyVideoHourPerformance::class ,'agency_member_id');
    }

    public function hoursPerformance()
    {
        return $this->hasMany(HostingAgencyHourPerformance::class ,'agency_member_id');
    }

}
