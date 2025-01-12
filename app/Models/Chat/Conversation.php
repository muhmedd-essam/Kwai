<?php

namespace App\Models\Chat;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Chat\Message;

class Conversation extends Model
{
    use HasFactory;

    public function initializer()
    {
        return $this->belongsTo(User::class, 'initializer_id');
    }

    public function dependent()
    {
        return $this->belongsTo(User::class, 'dependent_id');
    }

    public function messages()
    {
        return $this->hasMany(Message::class)->orderBy('id', 'DESC');
    }

    public function lastMessage()
    {
        return $this->hasMany(Message::class)->orderBy('id', 'DESC')->take(1);
    }
    
}
