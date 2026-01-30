<?php
header('Content-Type: application/json');

// Include OCR functions
require_once 'ocr_functions.php';

// Check if we have an uploaded file
if (!isset($_POST['filename'])) {
    // Check for direct file upload
    if (isset($_FILES['image'])) {
        require_once 'upload.php';
        exit;
    }
    
    echo json_encode([
        'success' => false,
        'error' => 'No filename provided'
    ]);
    exit;
}

$filename = $_POST['filename'];
$rotation = isset($_POST['rotation']) ? intval($_POST['rotation']) : 0;

// Paths
$originalPath = "uploads/{$filename}." . pathinfo($_POST['original_name'] ?? 'image.jpg', PATHINFO_EXTENSION);
$tempPath = "temp/{$filename}_processed.jpg";

// Process the image
try {
    $result = processChessboardImage($originalPath, $tempPath, $rotation);
    
    if ($result['success']) {
        echo json_encode([
            'success' => true,
            'fen' => $result['fen'],
            'pieces' => $result['pieces'],
            'debug' => [
                'board_detected' => $result['board_detected'] ? 'Yes' : 'No',
                'processing_time' => round($result['processing_time'], 3) . 's',
                'image_size' => $result['image_size'],
                'confidence' => $result['confidence'] . '%'
            ],
            'image_path' => $tempPath
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => $result['error'],
            'debug' => $result['debug'] ?? []
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Processing error: ' . $e->getMessage()
    ]);
}
?>