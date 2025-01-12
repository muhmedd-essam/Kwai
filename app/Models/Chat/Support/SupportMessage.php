<?php

namespace App\Models\Chat\Support;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Chat\Support\SupportConversation;
use App\Models\Chat\Support\SupportAttachment;

class SupportMessage extends Model
{
    use HasFactory;

    public function conversation()
    {
        return $this->belongsTo(SupportConversation::class);
    }

    public function attachments()
    {
        return $this->hasMany(SupportAttachment::class);
    }

}
