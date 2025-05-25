<?php

namespace App\Http\Controllers\Admin;

use App\Models\TouristPlace;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class TouristPlaceController extends Controller
{


    public function index()
    {
        $places = TouristPlace::with('category')->get();

        $places->transform(function ($place) {
            // Ensure gallery is an array
            $gallery = is_array($place->gallery) ? $place->gallery : json_decode($place->gallery, true);

            // Check if gallery contains direct URLs or needs mapping
            $place->gallery = collect($gallery)->map(function ($file) {
                return filter_var($file, FILTER_VALIDATE_URL) ? $file : getUploadDocumentsToS3($file);
            });

            return $place;
        });

        return response()->json($places);
    }



    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'category_id' => 'nullable|exists:tourist_place_categories,id',
            'name' => 'nullable|string|max:255',
            'short_description' => 'nullable|string|max:255',
            'description' => 'nullable|string',
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
            'map_link' => 'nullable|string',
            'main_attractions' => 'nullable|string', // ✅ New field
            'purpose_and_significance' => 'nullable|string', // ✅ New field
            'special_features' => 'nullable|string', // ✅ New field
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();

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

        $touristPlace = TouristPlace::create($data);

        $galleryUrls = [];
        if ($request->hasFile('gallery')) {
            foreach ($request->file('gallery') as $galleryImage) {
                $galleryUrl = uploadFileToS3(
                    $galleryImage,
                    'tourist_places/gallery',
                    [
                        'category_id' => $data['category_id'] ?? null,
                        'description' => 'Gallery image of ' . $data['name'],
                        'type' => 'tourist_place_gallery',
                        'uploaded_by' => auth()->check() ? auth()->id() : 'admin'
                    ]
                );
                $galleryUrls[] = $galleryUrl;
            }
        }

        if (!empty($galleryUrls)) {
            $touristPlace->gallery = $galleryUrls;
            $touristPlace->save();
        }

        return response()->json($touristPlace, 201);
    }

    public function show($id)
    {
        $place = TouristPlace::with('category')->findOrFail($id);

        // Ensure gallery is an array
        $gallery = is_array($place->gallery) ? $place->gallery : json_decode($place->gallery, true);

        // Check if gallery contains direct URLs or needs mapping
        $place->gallery = collect($gallery)->map(function ($file) {
            return filter_var($file, FILTER_VALIDATE_URL) ? $file : getUploadDocumentsToS3($file);
        });


        return response()->json($place);
    }


public function update(Request $request, $id)
{
    $place = TouristPlace::findOrFail($id);

    $validator = Validator::make($request->all(), [
        'category_id' => 'nullable|exists:tourist_place_categories,id',
        'name' => 'nullable|string|max:255',
        'short_description' => 'nullable|string|max:255',
        'description' => 'nullable|string',
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
        'map_link' => 'nullable|string',
        'main_attractions' => 'nullable|string',
        'purpose_and_significance' => 'nullable|string',
        'special_features' => 'nullable|string', // ✅ New field
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    $data = $validator->validated();

    // ✅ Handle main image upload
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

    // ✅ Handle gallery image uploads and retain old images
    if ($request->hasFile('gallery')) {
        $galleryUrls = [];

        foreach ($request->file('gallery') as $galleryImage) {
            $galleryUrl = uploadFileToS3(
                $galleryImage,
                'tourist_places/gallery',
                [
                    'category_id' => $data['category_id'] ?? $place->category_id,
                    'description' => 'Added to gallery of ' . ($data['name'] ?? $place->name),
                    'type' => 'tourist_place_gallery',
                    'uploaded_by' => auth()->check() ? auth()->id() : 'admin'
                ]
            );
            $galleryUrls[] = $galleryUrl;
        }

        // ✅ Merge old gallery with new images
        $existingGallery = $place->gallery ?? [];
        $mergedGallery = array_merge($existingGallery, $galleryUrls);
        $data['gallery'] = $mergedGallery;

        Log::info('Gallery URLs merged: ', $mergedGallery);
    }

    // ✅ Update the tourist place
    $place->update($data);

    return response()->json($place->toArray(), 200);
}


    public function destroy($id)
    {
        $place = TouristPlace::findOrFail($id);
        $place->delete();

        return response()->json(null, 204);
    }
}
