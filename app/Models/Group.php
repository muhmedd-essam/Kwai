<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\GroupMember;
use App\Models\User;

class Group extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'cover',
        'owner_id',
        'description',
    ];

    public function getCoverAttribute($value){
        return asset('/storage/' . $value);
    }

    public function members()
    {
        return $this->hasMany(GroupMember::class);
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

}
