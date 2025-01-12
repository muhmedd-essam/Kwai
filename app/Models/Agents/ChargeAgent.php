<?php

namespace App\Models\Agents;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Agents\ChargeAgentAdminHistory;

class ChargeAgent extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'charge_agent_no',
    ];

    public function owner()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function adminHistory()
    {
        return $this->hasMany(ChargeAgentAdminHistory::class, 'charge_agent_id');
    }

    public function usersHistory()
    {
        return $this->hasMany(ChargeAgentsHistory::class, 'charge_agent_id');
    }
    
}
