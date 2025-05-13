<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TouristPlace extends Model
{
    protected $fillable = [
        'category_id',
        'name',
        'location',
        'description',
        'short_description',
        'history',
        'architecture',
        'how_to_go',
        'where_to_stay',
        'where_to_eat',
        'ticket_price',
        'opening_hours',
        'best_time_to_visit',
        'image_url',
        'gallery',
        'map_link'
    ];

    protected $casts = [
        'gallery' => 'array',
    ];

    public function category()
    {
        return $this->belongsTo(TouristPlaceCategory::class, 'category_id');
    }

    // Override the image_url attribute
    public function getImageUrlAttribute($value)
    {
        if (is_string($value)) {
            return getUploadDocumentsToS3($value);

            // return url('/file/' . ltrim($value, '/'));
        }

        return null;
    }

    
}
