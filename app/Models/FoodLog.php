<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FoodLog extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'food_name', 'category', 'time', 'notes'];

    protected function casts(): array
    {
        return ['time' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
