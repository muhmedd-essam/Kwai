<?php

namespace App\Models\HostingAgency;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HostingAgencyTargetBd extends Model
{
    use HasFactory;

    protected $table = 'hosting_agency_targets_bd';


    protected $fillable=[
        'target_no',
        'salary_required',
        'bd_salary',
    ];

    public function agenciesMembersBd()
    {
        return $this->hasMany(HostingAgencyMemberBd::class, 'current_target_id');
    }

}
