<?php

namespace App\Models\HostingAgency;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\HostingAgency\HostingAgencyMember;

class HostingAgencyVideoHourPerformance extends Model
{
    use HasFactory;

    protected $table = 'hosting_agency_video_hour_pe';

    protected $casts = [
        'created_at' => 'date',
    ];
    
    public function agencyMember()
    {
        return $this->belongsTo(HostingAgencyMember::class, 'agency_member_id');
    }

}
