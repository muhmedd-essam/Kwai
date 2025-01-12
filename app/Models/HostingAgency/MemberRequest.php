<?php

namespace App\Models\HostingAgency;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\HostingAgency\HostingAgency;

class MemberRequest extends Model
{
    use HasFactory;

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function agency()
    {
        return $this->belongsTo(HostingAgency::class, 'hosting_agency_id');
    }

}
