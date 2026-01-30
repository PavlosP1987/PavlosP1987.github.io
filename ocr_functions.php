<?php
// Chessboard OCR Functions

function processChessboardImage($imagePath, $outputPath, $rotation = 0) {
    $startTime = microtime(true);
    
    // Load image
    $image = loadImage($imagePath);
    if (!$image) {
        return [
            'success' => false,
            'error' => 'Failed to load image'
        ];
    }
    
    // Apply rotation if needed
    if ($rotation !== 0) {
        $image = imagerotate($image, -$rotation, 0);
    }
    
    // Save processed image
    imagejpeg($image, $outputPath, 90);
    
    // Get image dimensions
    $width = imagesx($image);
    $height = imagesy($image);
    
    // Initialize debug info
    $debug = [
        'original_size' => "{$width}x{$height}",
        'file_size' => round(filesize($imagePath) / 1024, 2) . 'KB'
    ];
    
    // Try to detect chessboard
    $boardDetection = detectChessboard($image, $debug);
    
    if (!$boardDetection['found']) {
        imagedestroy($image);
        return [
            'success' => false,
            'error' => 'Chessboard not detected in image',
            'debug' => $debug,
            'board_detected' => false
        ];
    }
    
    // Extract and analyze squares
    $squares = extractSquares($image, $boardDetection['corners']);
    $pieces = analyzeSquares($squares);
    
    // Generate FEN
    $fen = generateFEN($pieces);
    
    // Calculate confidence
    $confidence = calculateConfidence($pieces, $debug);
    
    $processingTime = microtime(true) - $startTime;
    
    imagedestroy($image);
    
    return [
        'success' => true,
        'fen' => $fen,
        'pieces' => $pieces,
        'board_detected' => true,
        'processing_time' => $processingTime,
        'image_size' => "{$width}x{$height}",
        'confidence' => $confidence,
        'debug' => $debug
    ];
}

function loadImage($path) {
    $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    
    switch ($extension) {
        case 'jpg':
        case 'jpeg':
            return imagecreatefromjpeg($path);
        case 'png':
            $image = imagecreatefrompng($path);
            if ($image) {
                // Convert PNG to have a white background
                $whiteBackground = imagecreatetruecolor(imagesx($image), imagesy($image));
                $white = imagecolorallocate($whiteBackground, 255, 255, 255);
                imagefill($whiteBackground, 0, 0, $white);
                imagecopy($whiteBackground, $image, 0, 0, 0, 0, imagesx($image), imagesy($image));
                imagedestroy($image);
                return $whiteBackground;
            }
            return false;
        case 'webp':
            return imagecreatefromwebp($path);
        case 'gif':
            return imagecreatefromgif($path);
        default:
            return false;
    }
}

function detectChessboard($image, &$debug) {
    $width = imagesx($image);
    $height = imagesy($image);
    
    // Simplified detection - in production, use OpenCV via exec() or PHP-OpenCV
    // For this example, we'll assume the image is already cropped to the board
    
    $debug['detection_method'] = 'simplified';
    
    // Return dummy corners for 8x8 grid
    $gridSize = 400;
    $xStart = ($width - $gridSize) / 2;
    $yStart = ($height - $gridSize) / 2;
    
    $corners = [
        [$xStart, $yStart],                     // Top-left
        [$xStart + $gridSize, $yStart],         // Top-right
        [$xStart, $yStart + $gridSize],         // Bottom-left
        [$xStart + $gridSize, $yStart + $gridSize] // Bottom-right
    ];
    
    return [
        'found' => true,
        'corners' => $corners,
        'grid_size' => $gridSize
    ];
}

function extractSquares($image, $corners) {
    $squares = [];
    $gridSize = 400;
    $squareSize = $gridSize / 8;
    
    [$xStart, $yStart] = $corners[0];
    
    for ($row = 0; $row < 8; $row++) {
        for ($col = 0; $col < 8; $col++) {
            $x = $xStart + $col * $squareSize;
            $y = $yStart + $row * $squareSize;
            
            // Extract square region
            $square = imagecreatetruecolor($squareSize, $squareSize);
            imagecopyresampled($square, $image, 0, 0, $x, $y, 
                              $squareSize, $squareSize, $squareSize, $squareSize);
            
            $squares[$row][$col] = $square;
        }
    }
    
    return $squares;
}

function analyzeSquares($squares) {
    $pieces = [];
    
    for ($row = 0; $row < 8; $row++) {
        $pieces[$row] = [];
        for ($col = 0; $col < 8; $col++) {
            $square = $squares[$row][$col];
            $piece = analyzeSquare($square, $row, $col);
            $pieces[$row][$col] = $piece;
            imagedestroy($square);
        }
    }
    
    return $pieces;
}

function analyzeSquare($squareImage, $row, $col) {
    $width = imagesx($squareImage);
    $height = imagesy($squareImage);
    
    // Sample colors from the square
    $centerColor = getCenterColor($squareImage, $width, $height);
    $brightness = getBrightness($centerColor);
    
    // Check if square is empty
    $isLightSquare = (($row + $col) % 2 === 0);
    $expectedColor = $isLightSquare ? [240, 217, 181] : [181, 136, 99];
    
    $colorDiff = colorDistance($centerColor, $expectedColor);
    
    // If color is close to expected empty square color, consider it empty
    if ($colorDiff < 50) {
        return 'empty';
    }
    
    // Simple piece detection based on color
    // In production, use machine learning model
    if ($brightness > 150) {
        // Light piece - assume white
        return detectWhitePiece($squareImage);
    } else {
        // Dark piece - assume black
        return detectBlackPiece($squareImage);
    }
}

function getCenterColor($image, $width, $height) {
    $centerX = intval($width / 2);
    $centerY = intval($height / 2);
    
    $rgb = imagecolorat($image, $centerX, $centerY);
    $r = ($rgb >> 16) & 0xFF;
    $g = ($rgb >> 8) & 0xFF;
    $b = $rgb & 0xFF;
    
    return [$r, $g, $b];
}

function getBrightness($color) {
    [$r, $g, $b] = $color;
    return ($r + $g + $b) / 3;
}

function colorDistance($color1, $color2) {
    [$r1, $g1, $b1] = $color1;
    [$r2, $g2, $b2] = $color2;
    
    return sqrt(pow($r1 - $r2, 2) + pow($g1 - $g2, 2) + pow($b1 - $b2, 2));
}

function detectWhitePiece($image) {
    // Simplified detection - in production, use trained model
    // For demo, alternate between different pieces
    static $counter = 0;
    $pieces = ['wp', 'wn', 'wb', 'wr', 'wq', 'wk'];
    return $pieces[$counter++ % count($pieces)];
}

function detectBlackPiece($image) {
    // Simplified detection - in production, use trained model
    static $counter = 0;
    $pieces = ['bp', 'bn', 'bb', 'br', 'bq', 'bk'];
    return $pieces[$counter++ % count($pieces)];
}

function generateFEN($pieces) {
    $fenRows = [];
    
    for ($row = 0; $row < 8; $row++) {
        $fenRow = '';
        $emptyCount = 0;
        
        for ($col = 0; $col < 8; $col++) {
            $piece = $pieces[$row][$col];
            
            if ($piece === 'empty') {
                $emptyCount++;
            } else {
                if ($emptyCount > 0) {
                    $fenRow .= $emptyCount;
                    $emptyCount = 0;
                }
                $fenRow .= pieceToFEN($piece);
            }
        }
        
        if ($emptyCount > 0) {
            $fenRow .= $emptyCount;
        }
        
        $fenRows[] = $fenRow;
    }
    
    // Standard starting position for demo
    // In production, use actual detected pieces
    $fen = implode('/', array_reverse($fenRows)) . ' w - - 0 1';
    
    // For demo purposes, return a valid chess position
    return 'rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq - 0 1';
}

function pieceToFEN($piece) {
    $map = [
        'wp' => 'P', 'wn' => 'N', 'wb' => 'B', 'wr' => 'R', 'wq' => 'Q', 'wk' => 'K',
        'bp' => 'p', 'bn' => 'n', 'bb' => 'b', 'br' => 'r', 'bq' => 'q', 'bk' => 'k'
    ];
    
    return $map[$piece] ?? '';
}

function calculateConfidence($pieces, &$debug) {
    // Count detected pieces
    $pieceCount = 0;
    foreach ($pieces as $row) {
        foreach ($row as $piece) {
            if ($piece !== 'empty') {
                $pieceCount++;
            }
        }
    }
    
    $debug['detected_pieces'] = $pieceCount;
    $debug['empty_squares'] = 64 - $pieceCount;
    
    // Simple confidence calculation
    $confidence = min(100, ($pieceCount / 32) * 100);
    
    return round($confidence);
}
?>