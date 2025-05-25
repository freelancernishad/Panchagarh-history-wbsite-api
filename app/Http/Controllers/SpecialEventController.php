<?php

namespace App\Http\Controllers;

use Illuminate\Support\Str;
use App\Models\SpecialEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class SpecialEventController extends Controller
{

public function index()
{
    $events = SpecialEvent::latest()->paginate(10);

    $events->getCollection()->transform(function ($event) {
        // Ensure gallery is an array
        $gallery = is_array($event->gallery) ? $event->gallery : json_decode($event->gallery, true);

        // Map each gallery image to full S3 URL if it's not already a full URL
        $event->gallery = collect($gallery)->map(function ($file) {
            return filter_var($file, FILTER_VALIDATE_URL) ? $file : getUploadDocumentsToS3($file);
        });

        // Handle cover_image similarly if needed
        if (!empty($event->cover_image) && !filter_var($event->cover_image, FILTER_VALIDATE_URL)) {
            $event->cover_image = getUploadDocumentsToS3($event->cover_image);
        }

        return $event;
    });

    return response()->json($events);
}


public function store(Request $request)
{
    $validator = Validator::make($request->all(), [
        'title' => 'required|string|max:255',
        'event_date' => 'required|date',
        'start_time' => 'required|string',
        'end_time' => 'required|string',
        'description' => 'nullable|string',
        'location' => 'required|string|max:255',
        'event_type' => 'required|string|max:100',
        'organizer_type' => 'nullable|string|max:100',
        'cover_image' => 'nullable|file|mimes:jpg,jpeg,png',
        'gallery.*' => 'nullable|file|mimes:jpg,jpeg,png',
        'meta_description' => 'nullable|string|max:255',
        'meta_keywords' => 'nullable|string|max:255',
        'is_featured' => 'boolean',
        'registration_required' => 'boolean',
        'registration_link' => 'nullable|string|max:255',
        'tags' => 'nullable|array',
        'tags.*' => 'string|max:100',
        'status' => 'nullable|in:active,inactive',
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    $data = $validator->validated();

    // ✅ Generate slug from title
    $slug = Str::slug($data['title']);
    $originalSlug = $slug;
    $i = 1;
    while (SpecialEvent::where('slug', $slug)->exists()) {
        $slug = $originalSlug . '-' . $i++;
    }
    $data['slug'] = $slug;

    // ✅ Upload cover image
    if ($request->hasFile('cover_image')) {
        $data['cover_image'] = uploadFileToS3(
            $request->file('cover_image'),
            'special_events/cover',
            [
                'description' => 'Cover image of ' . $data['title'],
                'type' => 'special_event',
                'uploaded_by' => auth()->check() ? auth()->id() : 'admin',
            ]
        );
    }

    // ✅ Create event without gallery first
    $event = SpecialEvent::create($data);

    // ✅ Upload gallery images
    $galleryUrls = [];
    if ($request->hasFile('gallery')) {
        foreach ($request->file('gallery') as $galleryImage) {
            $galleryUrl = uploadFileToS3(
                $galleryImage,
                'special_events/gallery',
                [
                    'description' => 'Gallery image of ' . $data['title'],
                    'type' => 'special_event_gallery',
                    'uploaded_by' => auth()->check() ? auth()->id() : 'admin',
                ]
            );
            $galleryUrls[] = $galleryUrl;
        }
    }

    // ✅ Save gallery if exists
    if (!empty($galleryUrls)) {
        $event->gallery = $galleryUrls;
        $event->save();
    }

    return response()->json($event, 201);
}

public function show($id)
{
    $event = SpecialEvent::findOrFail($id);

    // Ensure gallery is an array
    $gallery = is_array($event->gallery) ? $event->gallery : json_decode($event->gallery, true);

    // Map each gallery image to full S3 URL if it's not already a full URL
    $event->gallery = collect($gallery)->map(function ($file) {
        return filter_var($file, FILTER_VALIDATE_URL) ? $file : getUploadDocumentsToS3($file);
    });

    // Normalize cover image
    if (!empty($event->cover_image) && !filter_var($event->cover_image, FILTER_VALIDATE_URL)) {
        $event->cover_image = getUploadDocumentsToS3($event->cover_image);
    }

    return response()->json($event);
}


public function update(Request $request, $id)
{
    $event = SpecialEvent::findOrFail($id);

    $validator = Validator::make($request->all(), [
        'title' => 'sometimes|required|string|max:255',
        'event_date' => 'sometimes|required|date',
        'start_time' => 'sometimes|required|string',
        'end_time' => 'sometimes|required|string',
        'description' => 'nullable|string',
        'location' => 'sometimes|required|string|max:255',
        'event_type' => 'sometimes|required|string|max:100',
        'organizer_type' => 'nullable|string|max:100',
        'cover_image' => 'nullable|file|mimes:jpg,jpeg,png|max:2048',
        'gallery.*' => 'nullable|file|mimes:jpg,jpeg,png|max:2048',
        'meta_description' => 'nullable|string|max:255',
        'meta_keywords' => 'nullable|string|max:255',
        'is_featured' => 'boolean',
        'registration_required' => 'boolean',
        'registration_link' => 'nullable|string|max:255',
        'tags' => 'nullable|array',
        'tags.*' => 'string|max:100',
        'status' => 'nullable|in:active,inactive',
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    $data = $validator->validated();

    // ✅ Handle cover image upload
    if ($request->hasFile('cover_image')) {
        $data['cover_image'] = uploadFileToS3(
            $request->file('cover_image'),
            'special_events/cover',
            [
                'description' => 'Updated cover image of ' . ($data['title'] ?? $event->title),
                'type' => 'special_event',
                'uploaded_by' => auth()->check() ? auth()->id() : 'admin',
            ]
        );
    }

    // ✅ Handle gallery image uploads and merge with existing
    if ($request->hasFile('gallery')) {
        $galleryUrls = [];

        foreach ($request->file('gallery') as $galleryImage) {
            $galleryUrls[] = uploadFileToS3(
                $galleryImage,
                'special_events/gallery',
                [
                    'description' => 'Added to gallery of ' . ($data['title'] ?? $event->title),
                    'type' => 'special_event_gallery',
                    'uploaded_by' => auth()->check() ? auth()->id() : 'admin',
                ]
            );
        }

        // Merge old + new gallery
        $existingGallery = $event->gallery ?? [];
        $data['gallery'] = array_merge($existingGallery, $galleryUrls);

        Log::info('Merged gallery images for event ID ' . $event->id, $data['gallery']);
    }

    $event->update($data);

    return response()->json([
        'message' => 'Event updated successfully',
        'event' => $event
    ]);
}


    public function destroy($id)
    {
        SpecialEvent::findOrFail($id)->delete();
        return response()->json(['message' => 'Event deleted']);
    }
}
