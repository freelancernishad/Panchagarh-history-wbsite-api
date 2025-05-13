<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\TouristPlace;
use Illuminate\Http\Request;

class TouristPlacePublicController extends Controller
{
    // ✅ List All (with optional filters)
    public function index(Request $request)
    {
        $query = TouristPlace::select('id', 'name', 'location', 'category_id', 'image_url', 'created_at')
            ->with(['category:id,name'])
            ->orderBy('created_at', 'desc');

        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->has('limit')) {
            $query->limit($request->limit);
        }

        return $query->get();
    }




    // ✅ Get by name (exact match)
    public function showByName($name)
    {
        $place = TouristPlace::with('category')->where('name', $name)->first();

        if (!$place) {
            return response()->json(['message' => 'Place not found'], 404);
        }

        // Ensure gallery is an array
        $gallery = is_array($place->gallery) ? $place->gallery : json_decode($place->gallery, true);

        // Check if gallery contains direct URLs or needs mapping
        $place->gallery = collect($gallery)->map(function ($file) {
            return filter_var($file, FILTER_VALIDATE_URL) ? $file : getUploadDocumentsToS3($file);
        });

        return response()->json($place);
    }

}
