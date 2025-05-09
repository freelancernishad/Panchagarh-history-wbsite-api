<?php

use App\Models\Gallery;
use Illuminate\Support\Facades\Storage;




/**
     * Upload a file to the S3 disk.
     *
     * @param \Illuminate\Http\UploadedFile $file
     * @param string $directory
     * @return string
     * @throws \Exception
     */
function uploadFileToS3($file, $directory = 'uploads', $options = [])
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

        $fullUrl = config('AWS_FILE_LOAD_BASE') . $filePath;

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




/**
 * Upload a file to the 'protected' disk.
 *
 * @param \Illuminate\Http\UploadedFile $file
 * @param string $directory
 * @return string $filePath
 */
function uploadFileToProtected($file, $directory = 'uploads')
{
    // Validate file
    if (!$file->isValid()) {
        throw new \Exception('Invalid file upload');
    }

    // Store file in the 'protected' disk
    $filePath = $file->store($directory, 'protected');

    return $filePath;
}

/**
 * Read a file from the 'protected' disk.
 *
 * @param string $filename
 * @return \Symfony\Component\HttpFoundation\StreamedResponse
 */
function readFileFromProtected($filename)
{
    // Define file path
    $filePath = "uploads/{$filename}";

    // Check if the file exists
    if (!Storage::disk('protected')->exists($filePath)) {
        throw new \Exception('File not found');
    }

    // Return file as download
    return Storage::disk('protected')->download($filePath);
}
