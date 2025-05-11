<?php

use Aws\S3\S3Client;
use App\Models\Gallery;
use Illuminate\Support\Str;
use Intervention\Image\Image;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Request;
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

        // $fullUrl = config('AWS_FILE_LOAD_BASE') . $filePath;
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





    function uploadDocumentsToS3($fileData, $filePath, $dateFolder, $sonodId)
{
    if (!$fileData) {
        Log::error('No file data provided.');
        return null;
    }

    // Handle case where fileData is inside an array
    if (is_array($fileData) && isset($fileData[0])) {
        $fileData = $fileData[0];
    }

    // Define the directory structure
    $directory = "sonod/$filePath/$dateFolder/$sonodId";
    $fileName = time() . '_' . Str::random(10);

    // Check if it's a base64-encoded string
    if (is_string($fileData) && preg_match('/^data:image\/(\w+);base64,/', $fileData, $matches)) {
        $base64Data = substr($fileData, strpos($fileData, ',') + 1);
        $decodedData = base64_decode($base64Data);
        $extension = $matches[1];

        $fileName .= '.' . $extension;
        $filePath = "$directory/$fileName";

        // Upload to S3
        Storage::disk('s3')->put($filePath, $decodedData);
    }
    // Handle regular file uploads
    elseif ($fileData instanceof UploadedFile) {
        $fileName .= '.' . $fileData->getClientOriginalExtension();
        $filePath = Storage::disk('s3')->putFileAs($directory, $fileData, $fileName);
    }
    // Invalid file type
    else {
        Log::error('Invalid file upload', ['fileData' => $fileData, 'type' => gettype($fileData)]);
        throw new \Exception('Invalid file upload');
    }

    Log::info('File uploaded to S3', ['file_path' => $filePath]);

    return $filePath;
}

function getUploadDocumentsToS3LifeTime($filename)
{
    if (!$filename) {
        return null;
    }

    $bucket = env('AWS_BUCKET');
    $region = env('AWS_DEFAULT_REGION');

    return "https://{$bucket}.s3.{$region}.amazonaws.com/{$filename}";
}

function getUploadDocumentsToS3($filename)
{
    if (!$filename) {
        return null; // If filename is empty, return null instead of an error
    }

    try {
        $s3 = new S3Client([
            'version'     => 'latest',
            'region'      => env('AWS_DEFAULT_REGION'),
            'credentials' => [
                'key'    => env('AWS_ACCESS_KEY_ID'),
                'secret' => env('AWS_SECRET_ACCESS_KEY'),
            ],
        ]);

        $bucket = env('AWS_BUCKET');

        $cmd = $s3->getCommand('GetObject', [
            'Bucket' => $bucket,
            'Key'    => $filename,
        ]);

        $request = $s3->createPresignedRequest($cmd, 604800);
        return (string) $request->getUri(); // Return only the presigned URL
    } catch (\Exception $e) {
        Log::error('Error generating S3 presigned URL: ' . $e->getMessage());
        return null;
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
