<?php

namespace App\Models\HostingAgency;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
// use App\Models\hostingagency\HostingAgencyMemberBd;

class HostingAgencyMemberBd extends Model
{
    use HasFactory;
    protected $table = 'hosting_agency_member_bd';
    protected $fillable=[
        'hosting_agency_id',
        'user_id',
        'current_target_id',
        'owner_bd'
    ];

    public function agency()
    {
        return $this->belongsTo(HostingAgency::class, 'hosting_agency_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_bd');
    }


    public function target()
    {
        return $this->belongsTo(HostingAgencyTargetBd::class, 'current_target_id');
    }


}
