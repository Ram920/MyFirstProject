<?php

/**
 * Resizes and crops an image to a standard resolution from its center.
 * Supports JPG, PNG, and GIF formats.
 *
 * @param string $source_path      Path to the source image.
 * @param string $destination_path Path to save the new image.
 * @param int    $target_width     The target width (e.g., 800).
 * @param int    $target_height    The target height (e.g., 600).
 * @return bool True on success, false on failure.
 */
function resizeAndCropImage($source_path, $destination_path, $target_width = 800, $target_height = 600) {
    list($source_width, $source_height, $source_type) = getimagesize($source_path);

    switch ($source_type) {
        case IMAGETYPE_GIF:
            $source_gdim = imagecreatefromgif($source_path);
            break;
        case IMAGETYPE_JPEG:
            $source_gdim = imagecreatefromjpeg($source_path);
            break;
        case IMAGETYPE_PNG:
            $source_gdim = imagecreatefrompng($source_path);
            break;
        default:
            return false; // Unsupported image type
    }

    $source_aspect_ratio = $source_width / $source_height;
    $target_aspect_ratio = $target_width / $target_height;

    if ($source_aspect_ratio > $target_aspect_ratio) {
        // Source is wider than target, crop width
        $temp_height = $target_height;
        $temp_width = (int) ($target_height * $source_aspect_ratio);
    } else {
        // Source is taller than target, crop height
        $temp_width = $target_width;
        $temp_height = (int) ($target_width / $source_aspect_ratio);
    }

    $temp_gdim = imagecreatetruecolor($temp_width, $temp_height);
    imagecopyresampled($temp_gdim, $source_gdim, 0, 0, 0, 0, $temp_width, $temp_height, $source_width, $source_height);

    $x0 = ($temp_width - $target_width) / 2;
    $y0 = ($temp_height - $target_height) / 2;

    $target_gdim = imagecreatetruecolor($target_width, $target_height);
    imagecopy($target_gdim, $temp_gdim, 0, 0, $x0, $y0, $target_width, $target_height);

    // Save the new image
    imagejpeg($target_gdim, $destination_path, 90); // Save as JPG with 90% quality

    // Free up memory
    imagedestroy($source_gdim);
    imagedestroy($temp_gdim);
    imagedestroy($target_gdim);

    return true;
}

/**
 * Uploads a file to Supabase Storage.
 *
 * @param string $file_tmp_path Temporary path of the uploaded file.
 * @param string $file_name Desired name of the file in Supabase Storage (including path within bucket).
 * @param string $bucket_name The Supabase Storage bucket name (default: 'catalogs').
 * @return string|false The public URL of the uploaded file on success, false on failure.
 */
function uploadToSupabase($file_tmp_path, $file_name, $bucket_name = 'catalogs') {
    if (!defined('SUPABASE_URL') || !defined('SUPABASE_ANON_KEY')) {
        error_log("Supabase URL or Anon Key not defined in config.php");
        return false;
    }

    $supabase_url = SUPABASE_URL;
    $supabase_anon_key = SUPABASE_ANON_KEY;

    $url = $supabase_url . '/storage/v1/object/' . $bucket_name . '/' . $file_name;

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, file_get_contents($file_tmp_path));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $supabase_anon_key,
        'apikey: ' . $supabase_anon_key,
        'Content-Type: ' . (function_exists('mime_content_type') ? mime_content_type($file_tmp_path) : 'application/octet-stream'), // Use actual MIME type or fallback
        'x-upsert: true' // Overwrite if file exists
    ]);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code >= 200 && $http_code < 300) {
        // Supabase returns a JSON object with { "Key": "..." } or similar on success
        // The public URL is usually constructed as: SUPABASE_URL/storage/v1/object/public/BUCKET_NAME/FILE_PATH
        return $supabase_url . '/storage/v1/object/public/' . $bucket_name . '/' . $file_name;
    } else {
        error_log("Supabase upload failed with HTTP code {$http_code}: {$response}");
        return false;
    }
}