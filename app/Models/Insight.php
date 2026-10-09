<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Insight extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'risk_level', 'risk_score', 'insight_text', 'details', 'generated_at'];

    protected function casts(): array
    {
        return ['generated_at' => 'datetime', 'risk_score' => 'integer', 'details' => 'array'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
