<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WalletTransaction extends Model
{
    protected $guarded = ['id'];
    protected function casts(): array { return ['amount' => 'integer', 'balance_after' => 'integer']; }
}
