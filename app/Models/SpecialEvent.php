<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SpecialEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'event_date',
        'start_time',
        'end_time',
        'description',
        'location',
        'event_type',
        'organizer_type',
        'cover_image',
        'gallery',
        'meta_description',
        'meta_keywords',
        'is_featured',
        'registration_required',
        'registration_link',
        'tags',
        'status',
    ];

    protected $casts = [
        'gallery' => 'array',
        'tags' => 'array',
        'is_featured' => 'boolean',
        'registration_required' => 'boolean',
    ];


    public function getCoverImageAttribute($value)
    {
        if (is_string($value)) {
            return getUploadDocumentsToS3($value);

            // return url('/file/' . ltrim($value, '/'));
        }

        return null;
    }


}
