<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Gallery extends Model
{
    protected $fillable = [
        'category_id',
        'url',
        'description',
        'type',
        'uploaded_by'
    ];

        // Override the image_url attribute
    public function getUrlAttribute($value)
    {
        if (is_string($value)) {
            return getUploadDocumentsToS3($value);

            // return url('/file/' . ltrim($value, '/'));
        }

        return null;
    }
    public function category()
    {
        return $this->belongsTo(TouristPlaceCategory::class, 'category_id');
    }
}
