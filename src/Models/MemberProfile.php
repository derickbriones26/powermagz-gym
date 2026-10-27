<?php

namespace App\Models;

use App\Core\Model;

class MemberProfile extends Model
{
    protected static string $table = 'member_profiles';
    protected static array $fillable = [
        'id',
        'user_id',
        'phone',
        'address',
        'birth_date',
        'emergency_contact',
        'fitness_goal',
        'sessions_left'
    ];
}
