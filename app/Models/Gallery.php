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

    public function category()
    {
        return $this->belongsTo(TouristPlaceCategory::class, 'category_id');
    }
}
