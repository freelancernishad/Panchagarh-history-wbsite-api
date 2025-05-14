<?php

namespace App\Http\Controllers\Admin;

use App\Models\Gallery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\TouristPlaceCategory;
use App\Http\Controllers\Controller;

class GalleryController extends Controller
{
    // Admin Route: Get all galleries (paginated)
    public function index(Request $request)
    {
        $query = Gallery::with('category');

        // Apply filters if provided
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('uploaded_by')) {
            $query->where('uploaded_by', $request->uploaded_by);
        }

        if ($request->filled('date_from') && $request->filled('date_to')) {
            $query->whereBetween('created_at', [$request->date_from, $request->date_to]);
        } elseif ($request->filled('date_from')) {
            $query->where('created_at', '>=', $request->date_from);
        } elseif ($request->filled('date_to')) {
            $query->where('created_at', '<=', $request->date_to);
        }

        $galleries = $query->latest('created_at')->paginate(10);

        return response()->json($galleries);
    }

    // Public Route: Get a single gallery by ID
    public function show($id)
    {
        $gallery = Gallery::with('category')->findOrFail($id);

        return response()->json($gallery);
    }

    // Admin Route: Create a new gallery (multiple files)
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'category_id' => 'required|exists:tourist_place_categories,id',
            'urls' => 'required|array', // Ensure 'urls' is an array of files
            'urls.*' => 'file|mimes:jpeg,jpg,png,mp4,mkv,avi|max:51200', // Ensure each file is an image or video and max size
            'description' => 'nullable|string',
            'type' => 'required|string',
            'uploaded_by' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $uploadedUrls = [];

        // Loop through each file in the 'urls' array
        foreach ($request->file('urls') as $file) {
            try {
                // Use the uploadFileToS3 function to upload to S3
                $uploadedUrl = $this->uploadFileToS3($file, 'galleries', [
                    'category_id' => $request->category_id,
                    'description' => $request->description,
                    'type' => $request->type,
                    'uploaded_by' => $request->uploaded_by,
                ]);

                // Save the uploaded file URL to the uploadedUrls array for returning in response
                $uploadedUrls[] = $uploadedUrl;
            } catch (\Exception $e) {
                // Handle any errors during file upload
                return response()->json(['error' => 'File upload failed', 'message' => $e->getMessage()], 500);
            }
        }

        // Return the response with all the uploaded file URLs
        return response()->json([
            'message' => 'Gallery items uploaded successfully.',
            'uploaded_urls' => $uploadedUrls,
        ], 201);
    }

    // Admin Route: Update an existing gallery by ID
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'category_id' => 'required|exists:tourist_place_categories,id',
            'description' => 'nullable|string',
            'type' => 'required|string',
            'uploaded_by' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $gallery = Gallery::findOrFail($id);

        $gallery->update([
            'category_id' => $request->category_id,
            'description' => $request->description,
            'type' => $request->type,
            'uploaded_by' => $request->uploaded_by,
        ]);

        return response()->json([
            'message' => 'Gallery updated successfully.',
            'gallery' => $gallery
        ], 200);
    }

    // Admin Route: Delete a gallery by ID
    public function destroy($id)
    {
        $gallery = Gallery::findOrFail($id);

        // Assuming you want to remove the image from S3 as well
        $this->deleteFileFromS3($gallery->url);

        $gallery->delete();

        return response()->json(['message' => 'Gallery deleted successfully.'], 200);
    }

    // Helper function to upload file to S3
    private function uploadFileToS3($file, $directory = 'uploads', $options = [])
    {
        if (!$file->isValid()) {
            \Log::error('Invalid file upload');
            throw new \Exception('Invalid file upload');
        }

        $fileName = time() . '_' . $file->getClientOriginalName();

        try {
            $filePath = $file->storeAs($directory, $fileName, 's3');

            if ($filePath === false) {
                \Log::error('S3 file upload failed');
                throw new \Exception('Failed to upload file to S3');
            }

            $fullUrl = $filePath;

            // Save to Gallery
            Gallery::create([
                'url' => $fullUrl,
                'category_id' => $options['category_id'] ?? null,
                'description' => $options['description'] ?? null,
                'type' => $options['type'] ?? null,
                'uploaded_by' => $options['uploaded_by'] ?? null,
            ]);

            \Log::info('File uploaded to S3', ['file_path' => $filePath]);

            return $fullUrl;
        } catch (\Exception $e) {
            \Log::error('Error uploading file to S3: ' . $e->getMessage());
            throw $e;
        }
    }

    // Helper function to delete file from S3
    private function deleteFileFromS3($fileUrl)
    {
        try {
            $s3 = \Storage::disk('s3');
            $s3->delete($fileUrl);
        } catch (\Exception $e) {
            \Log::error('Error deleting file from S3: ' . $e->getMessage());
        }
    }
}
