<?php
// Image upload helper functions

function validateImage($file, $max_size = 5242880) { // 5MB default
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    
    // Check if file was uploaded
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'File upload error.'];
    }
    
    // Check file size
    if ($file['size'] > $max_size) {
        return ['success' => false, 'message' => 'File size too large. Maximum size is ' . formatBytes($max_size) . '.'];
    }
    
    // Check MIME type
    if (!in_array($file['type'], $allowed_types)) {
        return ['success' => false, 'message' => 'Invalid file type. Only JPEG, PNG, GIF, and WebP files are allowed.'];
    }
    
    // Check file extension
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($file_extension, $allowed_extensions)) {
        return ['success' => false, 'message' => 'Invalid file extension.'];
    }
    
    // Additional security check - verify it's actually an image
    $image_info = getimagesize($file['tmp_name']);
    if ($image_info === false) {
        return ['success' => false, 'message' => 'File is not a valid image.'];
    }
    
    return ['success' => true, 'extension' => $file_extension];
}

function uploadImage($file, $upload_dir, $old_image = null) {
    // Validate image first
    $validation = validateImage($file);
    if (!$validation['success']) {
        return $validation;
    }
    
    // Create upload directory if it doesn't exist
    if (!file_exists($upload_dir)) {
        if (!mkdir($upload_dir, 0777, true)) {
            return ['success' => false, 'message' => 'Failed to create upload directory.'];
        }
    }
    
    // Generate unique filename
    $new_filename = uniqid() . '.' . $validation['extension'];
    $upload_path = $upload_dir . $new_filename;
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $upload_path)) {
        // Delete old image if exists
        if ($old_image && file_exists($upload_dir . $old_image)) {
            unlink($upload_dir . $old_image);
        }
        
        return ['success' => true, 'filename' => $new_filename];
    } else {
        return ['success' => false, 'message' => 'Failed to upload file.'];
    }
}

function deleteImage($filename, $upload_dir) {
    if ($filename && file_exists($upload_dir . $filename)) {
        return unlink($upload_dir . $filename);
    }
    return true;
}

function formatBytes($size, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    
    for ($i = 0; $size > 1024 && $i < count($units) - 1; $i++) {
        $size /= 1024;
    }
    
    return round($size, $precision) . ' ' . $units[$i];
}

function resizeImage($source_path, $destination_path, $max_width = 800, $max_height = 600, $quality = 85) {
    // Get image info
    $image_info = getimagesize($source_path);
    if ($image_info === false) {
        return false;
    }
    
    $width = $image_info[0];
    $height = $image_info[1];
    $mime_type = $image_info['mime'];
    
    // Calculate new dimensions
    $ratio = min($max_width / $width, $max_height / $height);
    if ($ratio >= 1) {
        // Image is already smaller than max dimensions
        return copy($source_path, $destination_path);
    }
    
    $new_width = intval($width * $ratio);
    $new_height = intval($height * $ratio);
    
    // Create image resource from source
    switch ($mime_type) {
        case 'image/jpeg':
            $source_image = imagecreatefromjpeg($source_path);
            break;
        case 'image/png':
            $source_image = imagecreatefrompng($source_path);
            break;
        case 'image/gif':
            $source_image = imagecreatefromgif($source_path);
            break;
        case 'image/webp':
            $source_image = imagecreatefromwebp($source_path);
            break;
        default:
            return false;
    }
    
    if (!$source_image) {
        return false;
    }
    
    // Create new image
    $new_image = imagecreatetruecolor($new_width, $new_height);
    
    // Preserve transparency for PNG and GIF
    if ($mime_type == 'image/png' || $mime_type == 'image/gif') {
        imagealphablending($new_image, false);
        imagesavealpha($new_image, true);
        $transparent = imagecolorallocatealpha($new_image, 255, 255, 255, 127);
        imagefilledrectangle($new_image, 0, 0, $new_width, $new_height, $transparent);
    }
    
    // Resize image
    imagecopyresampled($new_image, $source_image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
    
    // Save resized image
    $result = false;
    switch ($mime_type) {
        case 'image/jpeg':
            $result = imagejpeg($new_image, $destination_path, $quality);
            break;
        case 'image/png':
            $result = imagepng($new_image, $destination_path, 9);
            break;
        case 'image/gif':
            $result = imagegif($new_image, $destination_path);
            break;
        case 'image/webp':
            $result = imagewebp($new_image, $destination_path, $quality);
            break;
    }
    
    // Clean up memory
    imagedestroy($source_image);
    imagedestroy($new_image);
    
    return $result;
}
?>
