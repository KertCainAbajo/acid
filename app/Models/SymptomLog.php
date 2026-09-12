<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SymptomLog extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'symptom_type', 'intensity', 'time', 'notes'];

    protected function casts(): array
    {
        return ['time' => 'datetime', 'intensity' => 'integer'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
