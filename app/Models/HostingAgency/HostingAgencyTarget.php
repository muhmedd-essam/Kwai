<?php

namespace App\Models\HostingAgency;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\HostingAgency\HostingAgencyMember;

class HostingAgencyTarget extends Model
{
    use HasFactory;

    public function agenciesMembers()
    {
        return $this->hasMany(HostingAgencyMember::class, 'current_target_id');
    }

}
