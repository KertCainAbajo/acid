<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FoodLog extends Model
{
    use HasFactory;

    /**
     * The food categories a patient can assign to a meal.
     *
     * @var list<string>
     */
    public const CATEGORIES = ['acidic', 'oily', 'spicy', 'dairy', 'other'];

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
