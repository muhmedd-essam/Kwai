<?php

namespace App\Models\HostingAgency;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\HostingAgency\HostingAgencyMember;
use App\Models\HostingAgency\HostingAgencyDiamondPerformance;
use App\Models\HostingAgency\HostingAgencyHourPerformance;
use App\Models\HostingAgency\HostingAgencyVideoDiamondPerformance;
use App\Models\HostingAgency\HostingAgencyVideoHourPerformance;

class HostingAgency extends Model
{
    use HasFactory;

    public function getCoverAttribute($value){
        return asset('/storage/' . $value);
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function bd()
    {
        return $this->belongsTo(User::class, 'bd');
    }

    public function members()
    {
        return $this->hasMany(HostingAgencyMember::class, 'hosting_agency_id');
    }

    public function membersBd()
    {
        return $this->hasMany(HostingAgencyMemberBd::class, 'hosting_agency_id');
    }

    public function diamondPerformance()
    {
        return $this->hasMany(HostingAgencyDiamondPerformance::class ,'hosting_agency_id');
    }

    public function videoDiamondPerformance()
    {
        return $this->hasMany(HostingAgencyVideoDiamondPerformance::class ,'hosting_agency_id');
    }

    public function hoursPerformance()
    {
        return $this->hasMany(HostingAgencyHourPerformance::class ,'hosting_agency_id');
    }

    public function videoHoursPerformance()
    {
        return $this->hasMany(HostingAgencyVideoHourPerformance::class ,'hosting_agency_id');
    }

}
