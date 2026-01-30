<?php
header('Content-Type: application/json');

// Simple FEN validator
function validateFEN($fen) {
    // Basic FEN format validation
    $parts = explode(' ', $fen);
    
    if (count($parts) < 6) {
        return ['valid' => false, 'error' => 'Invalid FEN format'];
    }
    
    // Validate board position
    $rows = explode('/', $parts[0]);
    
    if (count($rows) !== 8) {
        return ['valid' => false, 'error' => 'Board must have 8 rows'];
    }
    
    foreach ($rows as $row) {
        $squareCount = 0;
        $length = strlen($row);
        
        for ($i = 0; $i < $length; $i++) {
            $char = $row[$i];
            
            if (ctype_digit($char)) {
                $squareCount += intval($char);
                if ($char === '0' || $char > '8') {
                    return ['valid' => false, 'error' => 'Invalid empty square count'];
                }
            } elseif (preg_match('/^[prnbqkPRNBQK]$/', $char)) {
                $squareCount++;
            } else {
                return ['valid' => false, 'error' => "Invalid character: $char"];
            }
        }
        
        if ($squareCount !== 8) {
            return ['valid' => false, 'error' => 'Each row must have 8 squares'];
        }
    }
    
    // Validate active color
    if (!in_array($parts[1], ['w', 'b'])) {
        return ['valid' => false, 'error' => 'Invalid active color'];
    }
    
    // Validate castling availability
    if (!preg_match('/^[KQkq-]+$/', $parts[2])) {
        return ['valid' => false, 'error' => 'Invalid castling rights'];
    }
    
    // Validate en passant square
    if ($parts[3] !== '-') {
        if (!preg_match('/^[a-h][36]$/', $parts[3])) {
            return ['valid' => false, 'error' => 'Invalid en passant square'];
        }
    }
    
    // Validate halfmove clock
    if (!ctype_digit($parts[4]) || $parts[4] < 0 || $parts[4] > 50) {
        return ['valid' => false, 'error' => 'Invalid halfmove clock'];
    }
    
    // Validate fullmove number
    if (!ctype_digit($parts[5]) || $parts[5] < 1) {
        return ['valid' => false, 'error' => 'Invalid fullmove number'];
    }
    
    return ['valid' => true, 'message' => 'Valid FEN notation'];
}

// Get input
$data = json_decode(file_get_contents('php://input'), true);
$fen = $data['fen'] ?? '';

if (empty($fen)) {
    echo json_encode(['valid' => false, 'error' => 'No FEN provided']);
    exit;
}

$result = validateFEN($fen);
echo json_encode($result);
?>