<?php

namespace App\Models\Chat;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class ChatBlock extends Model
{
    use HasFactory;

    public function blockedUser()
    {
        return $this->belongsTo(User::class, 'blocked_id');
    }

}
