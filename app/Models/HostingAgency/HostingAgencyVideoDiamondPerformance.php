<?php

namespace App\Models\HostingAgency;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\HostingAgency\HostingAgencyMember;
use App\Models\User;

class HostingAgencyVideoDiamondPerformance extends Model
{
    use HasFactory;

    protected $table = 'hosting_agency_video_diamond_pe';

    public function agencyMember()
    {
        return $this->belongsTo(HostingAgencyMember::class, 'agency_member_id');
    }

    public function supporter()
    {
        return $this->belongsTo(User::class, 'supporter_id');
    }

}
