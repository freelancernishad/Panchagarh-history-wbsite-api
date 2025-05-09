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
        'image_url' => 'nullable|file|mimes:jpg,jpeg,png|max:2048',
        'gallery' => 'nullable|array',
        'gallery.*' => 'nullable|file|mimes:jpg,jpeg,png|max:2048',
        'map_link' => 'nullable|string|max:255',
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    $data = $validator->validated();

    // Upload main image and create Gallery entry
    if ($request->hasFile('image_url')) {
        $data['image_url'] = uploadFileToS3(
            $request->file('image_url'),
            'tourist_places',
            [
                'category_id' => $data['category_id'] ?? null,
                'description' => 'Main image of ' . $data['name'],
                'type' => 'tourist_place',
                'uploaded_by' => auth()->check() ? auth()->id() : 'admin'
            ]
        );
    }

    // Create the main TouristPlace record
    $touristPlace = TouristPlace::create($data);

    // Upload gallery images and create Gallery entries
    if ($request->hasFile('gallery')) {
        foreach ($request->file('gallery') as $galleryImage) {
            uploadFileToS3(
                $galleryImage,
                'tourist_places/gallery',
                [
                    'category_id' => $data['category_id'] ?? null,
                    'description' => 'Gallery image of ' . $data['name'],
                    'type' => 'tourist_place_gallery',
                    'uploaded_by' => auth()->check() ? auth()->id() : 'admin'
                ]
            );
        }
    }

    return response()->json($touristPlace, 201);
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
        'image_url' => 'nullable|file|mimes:jpg,jpeg,png|max:2048',
        'gallery' => 'nullable|array',
        'gallery.*' => 'nullable|file|mimes:jpg,jpeg,png|max:2048',
        'map_link' => 'nullable|string|max:255',
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    $data = $validator->validated();

    // Upload new image_url and create gallery record
    if ($request->hasFile('image_url')) {
        $data['image_url'] = uploadFileToS3(
            $request->file('image_url'),
            'tourist_places',
            [
                'category_id' => $data['category_id'] ?? $place->category_id,
                'description' => 'Updated main image of ' . ($data['name'] ?? $place->name),
                'type' => 'tourist_place',
                'uploaded_by' => auth()->check() ? auth()->id() : 'admin'
            ]
        );
    }

    // Upload new gallery images and create gallery records
    if ($request->hasFile('gallery')) {
        foreach ($request->file('gallery') as $galleryImage) {
            uploadFileToS3(
                $galleryImage,
                'tourist_places/gallery',
                [
                    'category_id' => $data['category_id'] ?? $place->category_id,
                    'description' => 'Added to gallery of ' . ($data['name'] ?? $place->name),
                    'type' => 'tourist_place_gallery',
                    'uploaded_by' => auth()->check() ? auth()->id() : 'admin'
                ]
            );
        }
    }

    $place->update($data);
    return response()->json($place);
}


    public function destroy($id)
    {
        $place = TouristPlace::findOrFail($id);
        $place->delete();

        return response()->json(null, 204);
    }
}
