<?php

namespace App\Models\Chat\Support;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Chat\Support\SupportMessage;
use App\Models\User;

class SupportConversation extends Model
{
    use HasFactory;

    public function messages()
    {
        return $this->hasMany(SupportMessage::class)->orderBy('id', 'DESC');
    }

    public function lastMessage()
    {
        return $this->hasMany(SupportMessage::class)->orderBy('id', 'DESC')->take(1);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

}
