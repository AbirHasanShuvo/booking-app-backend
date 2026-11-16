<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    protected $fillable = [
        'category_id',
        'name',
        'description',
        'price',
        'duration_minutes',
        'image_url',
        'is_featured',
        'is_active',
    ];

    protected function casts(): array
    {
        return ['price' => 'decimal:2', 'is_featured' => 'boolean', 'is_active' => 'boolean'];
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }
}
