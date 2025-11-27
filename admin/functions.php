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