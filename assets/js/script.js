class ChessOCRApp {
    constructor() {
        this.image = null;
        this.rotation = 0;
        this.init();
    }

    init() {
        this.bindEvents();
        this.initDropzone();
    }

    bindEvents() {
        const fileInput = document.getElementById('fileInput');
        const processBtn = document.getElementById('processBtn');
        const copyBtn = document.getElementById('copyFEN');
        const validateBtn = document.getElementById('validateBtn');
        const lichessBtn = document.getElementById('lichessBtn');
        const rotateLeft = document.getElementById('rotateLeft');
        const rotateRight = document.getElementById('rotateRight');
        const removeBtn = document.getElementById('removeBtn');

        fileInput.addEventListener('change', (e) => this.handleFileSelect(e));
        processBtn.addEventListener('click', () => this.processImage());
        copyBtn.addEventListener('click', () => this.copyFEN());
        validateBtn.addEventListener('click', () => this.validateFEN());
        lichessBtn.addEventListener('click', () => this.openInLichess());
        rotateLeft.addEventListener('click', () => this.rotateImage(-90));
        rotateRight.addEventListener('click', () => this.rotateImage(90));
        removeBtn.addEventListener('click', () => this.removeImage());
    }

    initDropzone() {
        const dropArea = document.getElementById('dropArea');
        
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            dropArea.addEventListener(eventName, preventDefaults, false);
        });

        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }

        ['dragenter', 'dragover'].forEach(eventName => {
            dropArea.addEventListener(eventName, highlight, false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            dropArea.addEventListener(eventName, unhighlight, false);
        });

        function highlight() {
            dropArea.style.background = '#eef2ff';
            dropArea.style.borderColor = '#764ba2';
        }

        function unhighlight() {
            dropArea.style.background = '#f8f9fa';
            dropArea.style.borderColor = '#667eea';
        }

        dropArea.addEventListener('drop', (e) => {
            const dt = e.dataTransfer;
            const files = dt.files;
            this.handleFiles(files);
        });
    }

    handleFileSelect(e) {
        const files = e.target.files;
        this.handleFiles(files);
    }

    handleFiles(files) {
        if (files.length === 0) return;
        
        const file = files[0];
        if (!file.type.match('image.*')) {
            alert('Please select an image file');
            return;
        }

        if (file.size > 5 * 1024 * 1024) {
            alert('File size must be less than 5MB');
            return;
        }

        const reader = new FileReader();
        reader.onload = (e) => {
            this.image = new Image();
            this.image.onload = () => {
                this.showPreview(this.image);
            };
            this.image.src = e.target.result;
        };
        reader.readAsDataURL(file);
    }

    showPreview(image) {
        const previewSection = document.querySelector('.preview-section');
        const previewImage = document.getElementById('previewImage');
        
        previewImage.src = image.src;
        previewSection.style.display = 'block';
        this.updatePreview();
    }

    rotateImage(degrees) {
        this.rotation += degrees;
        this.updatePreview();
    }

    updatePreview() {
        const previewImage = document.getElementById('previewImage');
        previewImage.style.transform = `rotate(${this.rotation}deg)`;
    }

    removeImage() {
        const fileInput = document.getElementById('fileInput');
        const previewSection = document.querySelector('.preview-section');
        const resultsSection = document.querySelector('.results-section');
        
        fileInput.value = '';
        previewSection.style.display = 'none';
        resultsSection.style.display = 'none';
        this.image = null;
        this.rotation = 0;
    }

    async processImage() {
        if (!this.image) {
            alert('Please select an image first');
            return;
        }

        this.showLoading(true);
        
        try {
            // Create FormData to send to PHP
            const formData = new FormData();
            const fileInput = document.getElementById('fileInput');
            formData.append('image', fileInput.files[0]);
            formData.append('rotation', this.rotation);

            const response = await fetch('process.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();
            
            if (result.success) {
                this.displayResults(result);
            } else {
                throw new Error(result.error || 'Processing failed');
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Error processing image: ' + error.message);
        } finally {
            this.showLoading(false);
        }
    }

    displayResults(result) {
        const resultsSection = document.querySelector('.results-section');
        const fenOutput = document.getElementById('fenOutput');
        const debugOutput = document.getElementById('debugOutput');
        
        // Show results section
        resultsSection.style.display = 'block';
        
        // Scroll to results
        resultsSection.scrollIntoView({ behavior: 'smooth' });
        
        // Display FEN
        fenOutput.value = result.fen;
        
        // Display debug info
        debugOutput.innerHTML = '';
        for (const [key, value] of Object.entries(result.debug || {})) {
            const div = document.createElement('div');
            div.innerHTML = `<strong>${key}:</strong> ${value}`;
            debugOutput.appendChild(div);
        }
        
        // Render chessboard visualization
        this.renderChessboard(result.pieces || []);
    }

    renderChessboard(pieces) {
        const boardVisual = document.getElementById('boardVisualization');
        boardVisual.innerHTML = '';
        
        const boardSize = 400;
        const squareSize = boardSize / 8;
        
        const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
        svg.setAttribute('width', '100%');
        svg.setAttribute('height', '100%');
        svg.setAttribute('viewBox', `0 0 ${boardSize} ${boardSize}`);
        
        // Draw squares
        for (let row = 0; row < 8; row++) {
            for (let col = 0; col < 8; col++) {
                const x = col * squareSize;
                const y = row * squareSize;
                const isLight = (row + col) % 2 === 0;
                
                const square = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
                square.setAttribute('x', x);
                square.setAttribute('y', y);
                square.setAttribute('width', squareSize);
                square.setAttribute('height', squareSize);
                square.setAttribute('fill', isLight ? '#f0d9b5' : '#b58863');
                svg.appendChild(square);
            }
        }
        
        // Draw pieces (simplified - in production, you'd use actual piece images)
        pieces.forEach((pieceRow, row) => {
            pieceRow.forEach((piece, col) => {
                if (piece !== 'empty') {
                    const x = col * squareSize + squareSize / 2;
                    const y = row * squareSize + squareSize / 2;
                    
                    const circle = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
                    circle.setAttribute('cx', x);
                    circle.setAttribute('cy', y);
                    circle.setAttribute('r', squareSize * 0.3);
                    circle.setAttribute('fill', piece[0] === 'w' ? '#ffffff' : '#000000');
                    circle.setAttribute('stroke', piece[0] === 'w' ? '#000000' : '#ffffff');
                    circle.setAttribute('stroke-width', '2');
                    svg.appendChild(circle);
                    
                    // Add piece letter
                    const text = document.createElementNS('http://www.w3.org/2000/svg', 'text');
                    text.setAttribute('x', x);
                    text.setAttribute('y', y + 5);
                    text.setAttribute('text-anchor', 'middle');
                    text.setAttribute('font-size', squareSize * 0.4);
                    text.setAttribute('fill', piece[0] === 'w' ? '#000000' : '#ffffff');
                    text.setAttribute('font-weight', 'bold');
                    text.textContent = this.getPieceSymbol(piece);
                    svg.appendChild(text);
                }
            });
        });
        
        boardVisual.appendChild(svg);
    }

    getPieceSymbol(piece) {
        const symbols = {
            'wp': '♟', 'wn': '♞', 'wb': '♝', 'wr': '♜', 'wq': '♛', 'wk': '♚',
            'bp': '♟', 'bn': '♞', 'bb': '♝', 'br': '♜', 'bq': '♛', 'bk': '♚'
        };
        return symbols[piece] || '?';
    }

    copyFEN() {
        const fenOutput = document.getElementById('fenOutput');
        fenOutput.select();
        document.execCommand('copy');
        
        // Visual feedback
        const copyBtn = document.getElementById('copyFEN');
        const originalText = copyBtn.innerHTML;
        copyBtn.innerHTML = '<i class="fas fa-check"></i> Copied!';
        copyBtn.style.background = '#28a745';
        
        setTimeout(() => {
            copyBtn.innerHTML = originalText;
            copyBtn.style.background = '';
        }, 2000);
    }

    validateFEN() {
        const fenOutput = document.getElementById('fenOutput');
        const fen = fenOutput.value.trim();
        
        if (!fen) {
            alert('No FEN to validate');
            return;
        }
        
        // Send to PHP for validation
        fetch('validate_fen.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ fen: fen })
        })
        .then(response => response.json())
        .then(data => {
            if (data.valid) {
                alert('✓ Valid FEN notation');
            } else {
                alert('✗ Invalid FEN: ' + (data.error || 'Unknown error'));
            }
        })
        .catch(error => {
            alert('Validation error: ' + error.message);
        });
    }

    openInLichess() {
        const fenOutput = document.getElementById('fenOutput');
        const fen = encodeURIComponent(fenOutput.value.trim());
        
        if (!fen) {
            alert('No FEN to open');
            return;
        }
        
        window.open(`https://lichess.org/editor/${fen}`, '_blank');
    }

    showLoading(show) {
        const loadingOverlay = document.getElementById('loadingOverlay');
        loadingOverlay.style.display = show ? 'flex' : 'none';
    }
}

// Initialize app when page loads
document.addEventListener('DOMContentLoaded', () => {
    window.app = new ChessOCRApp();
});
