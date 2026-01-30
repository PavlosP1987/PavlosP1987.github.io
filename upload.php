<?php
header('Content-Type: application/json');

// Create necessary directories
$directories = ['uploads', 'temp'];
foreach ($directories as $dir) {
    if (!file_exists($dir)) {
        mkdir($dir, 0755, true);
    }
}

// Check if image was uploaded
if (!isset($_FILES['image'])) {
    echo json_encode([
        'success' => false,
        'error' => 'No image uploaded'
    ]);
    exit;
}

$file = $_FILES['image'];
$allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
$maxSize = 5 * 1024 * 1024; // 5MB

// Validate file
if (!in_array($file['type'], $allowedTypes)) {
    echo json_encode([
        'success' => false,
        'error' => 'Invalid file type. Only JPG, PNG, WebP, and GIF are allowed.'
    ]);
    exit;
}

if ($file['size'] > $maxSize) {
    echo json_encode([
        'success' => false,
        'error' => 'File size exceeds 5MB limit.'
    ]);
    exit;
}

if ($file['error'] !== UPLOAD_ERR_OK) {
    echo json_encode([
        'success' => false,
        'error' => 'Upload error: ' . $file['error']
    ]);
    exit;
}

// Generate unique filename
$filename = uniqid('chess_', true) . '_' . time();
$extension = pathinfo($file['name'], PATHINFO_EXTENSION);
$uploadPath = "uploads/{$filename}.{$extension}";
$tempPath = "temp/{$filename}_original.{$extension}";

// Save original file
if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
    echo json_encode([
        'success' => false,
        'error' => 'Failed to save uploaded file'
    ]);
    exit;
}

// Create a copy for processing
copy($uploadPath, $tempPath);

// Check if rotation is needed
$rotation = isset($_POST['rotation']) ? intval($_POST['rotation']) : 0;
if ($rotation !== 0) {
    rotateImage($tempPath, $rotation);
}

echo json_encode([
    'success' => true,
    'filename' => $filename,
    'path' => $uploadPath,
    'temp_path' => $tempPath,
    'rotation' => $rotation,
    'message' => 'File uploaded successfully'
]);

// Function to rotate image
function rotateImage($imagePath, $degrees) {
    $extension = strtolower(pathinfo($imagePath, PATHINFO_EXTENSION));
    
    switch ($extension) {
        case 'jpg':
        case 'jpeg':
            $image = imagecreatefromjpeg($imagePath);
            break;
        case 'png':
            $image = imagecreatefrompng($imagePath);
            break;
        case 'webp':
            $image = imagecreatefromwebp($imagePath);
            break;
        case 'gif':
            $image = imagecreatefromgif($imagePath);
            break;
        default:
            return false;
    }
    
    if (!$image) return false;
    
    // Rotate the image
    $rotated = imagerotate($image, -$degrees, 0);
    
    // Save the rotated image
    switch ($extension) {
        case 'jpg':
        case 'jpeg':
            imagejpeg($rotated, $imagePath, 90);
            break;
        case 'png':
            imagepng($rotated, $imagePath, 9);
            break;
        case 'webp':
            imagewebp($rotated, $imagePath, 90);
            break;
        case 'gif':
            imagegif($rotated, $imagePath);
            break;
    }
    
    // Free memory
    imagedestroy($image);
    imagedestroy($rotated);
    
    return true;
}
?>