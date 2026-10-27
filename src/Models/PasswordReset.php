<?php

namespace App\Models;

use App\Core\Model;

class PasswordReset extends Model
{
    protected static string $table = 'password_resets';
    protected static array $fillable = ['id', 'user_id', 'token', 'expires_at', 'used'];
}
