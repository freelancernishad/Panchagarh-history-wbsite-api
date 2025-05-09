<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TouristPlaceCategory extends Model
{
    protected $fillable = ['name'];

    public function places()
    {
        return $this->hasMany(TouristPlace::class, 'category_id');
    }
}
