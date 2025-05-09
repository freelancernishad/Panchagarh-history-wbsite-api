<?php

namespace App\Http\Controllers\Admin;

use App\Models\TouristPlace;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class TouristPlaceController extends Controller
{
    public function index()
    {
        return TouristPlace::with('category')->get();
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'category_id' => 'nullable|exists:tourist_place_categories,id',
            'name' => 'required|string|max:255',
            'short_description' => 'nullable|string|max:255',
            'description' => 'required|string',
            'location' => 'nullable|string|max:255',
            'history' => 'nullable|string',
            'architecture' => 'nullable|string',
            'how_to_go' => 'nullable|string',
            'where_to_stay' => 'nullable|string',
            'where_to_eat' => 'nullable|string',
            'ticket_price' => 'nullable|string|max:100',
            'opening_hours' => 'nullable|string|max:255',
            'best_time_to_visit' => 'nullable|string|max:255',
            'image_url' => 'nullable|string|max:255',
            'gallery' => 'nullable|array',
            'gallery.*' => 'nullable|string|max:255',
            'map_link' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        return TouristPlace::create($data);
    }

    public function show($id)
    {
        return TouristPlace::with('category')->findOrFail($id);
    }

    public function update(Request $request, $id)
{
    $place = TouristPlace::findOrFail($id);

    $validator = Validator::make($request->all(), [
        'category_id' => 'nullable|exists:tourist_place_categories,id',
        'name' => 'sometimes|required|string|max:255',
        'short_description' => 'nullable|string|max:255',
        'description' => 'sometimes|required|string',
        'location' => 'nullable|string|max:255',
        'history' => 'nullable|string',
        'architecture' => 'nullable|string',
        'how_to_go' => 'nullable|string',
        'where_to_stay' => 'nullable|string',
        'where_to_eat' => 'nullable|string',
        'ticket_price' => 'nullable|string|max:100',
        'opening_hours' => 'nullable|string|max:255',
        'best_time_to_visit' => 'nullable|string|max:255',
        'image_url' => 'nullable|string|max:255',
        'gallery' => 'nullable|array',
        'gallery.*' => 'nullable|string|max:255',
        'map_link' => 'nullable|string|max:255',
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    $data = $validator->validated();
    $place->update($data);
    return $place;
}

    public function destroy($id)
    {
        $place = TouristPlace::findOrFail($id);
        $place->delete();

        return response()->json(null, 204);
    }
}
