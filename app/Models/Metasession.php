<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Metasession extends Model
{
    protected $fillable = [
        'tenant_id',
        'session_id',
        'first_seen_at',
        'last_seen_at'
    ];
}
