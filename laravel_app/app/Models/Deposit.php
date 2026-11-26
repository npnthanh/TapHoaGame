<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Deposit extends Model
{
    protected $guarded = ['id'];
    protected function casts(): array { return ['amount' => 'integer', 'expires_at' => 'datetime', 'paid_at' => 'datetime']; }
}
