<?php

namespace App\Models\HostingAgency;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\HostingAgency\HostingAgencyMember;

class HostingAgencyHourPerformance extends Model
{
    use HasFactory;

    protected $casts = [
        'created_at' => 'date',
    ];

    public function agencyMember()
    {
        return $this->belongsTo(HostingAgencyMember::class, 'agency_member_id');
    }

}
