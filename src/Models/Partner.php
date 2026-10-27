<?php

namespace App\Models;

use App\Core\Model;

class Partner extends Model
{
    protected static string $table = 'partners';
    protected static array $fillable = ['id', 'name', 'type', 'contact', 'sales_total', 'referrals_count', 'status'];
}
