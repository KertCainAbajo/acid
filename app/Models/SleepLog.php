<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SleepLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'sleep_hours', 'sleep_quality', 'stress_level', 'logged_on', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'sleep_hours' => 'float',
            'sleep_quality' => 'integer',
            'stress_level' => 'integer',
            'logged_on' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
