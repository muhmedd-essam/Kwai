<?php

namespace App\Models\HostingAgency;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\HostingAgency\HostingAgencyMember;
use App\Models\User;

class HostingAgencyDiamondPerformance extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'agency_member_id',
        'supporter_id',
        'hosting_agency_id',
        'amount',
        'created_at',
        'updated_at',
    ];

    public function agencyMember()
    {
        return $this->belongsTo(HostingAgencyMember::class, 'agency_member_id');
    }

    public function supporter()
    {
        return $this->belongsTo(User::class, 'supporter_id');
    }

}
