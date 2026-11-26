<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    public $timestamps = false;
    protected $guarded = ['id'];
    protected function casts(): array { return ['metadata' => 'array', 'created_at' => 'datetime']; }

    public static function record(string $action, ?Model $subject = null, array $metadata = []): void
    {
        static::create([
            'user_id' => auth()->id(), 'action' => $action,
            'subject_type' => $subject ? $subject->getMorphClass() : null,
            'subject_id' => $subject?->getKey(), 'metadata' => $metadata,
            'ip_address' => request()->ip(),
        ]);
    }
}
