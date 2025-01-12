<?php

namespace App\Models\Agents;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Agents\ChargeAgent;

class ChargeAgentAdminHistory extends Model
{
    use HasFactory;

    public function agent()
    {
        return $this->belongsTo(ChargeAgent::class, 'charge_agent_id');
    }
    
}
