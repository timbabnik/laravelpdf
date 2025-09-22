<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>🎨 Magic Invoice Extractor - Upload Your PDF! 📄</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=comic-neue:400,700|nunito:400,600,700,800" rel="stylesheet" />
    
    <!-- Styles / Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: 'Comic Neue', 'Nunito', cursive, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #2d3748;
            line-height: 1.6;
            min-height: 100vh;
            position: relative;
            overflow-x: hidden;
        }
        
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="20" cy="20" r="2" fill="rgba(255,255,255,0.1)"/><circle cx="80" cy="40" r="1.5" fill="rgba(255,255,255,0.1)"/><circle cx="40" cy="80" r="1" fill="rgba(255,255,255,0.1)"/><circle cx="90" cy="90" r="2.5" fill="rgba(255,255,255,0.1)"/><circle cx="10" cy="60" r="1.5" fill="rgba(255,255,255,0.1)"/></svg>');
            pointer-events: none;
            z-index: 0;
        }
        
        
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 2rem 1rem;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
            z-index: 1;
        }
        
        .header {
            text-align: center;
            margin-bottom: 3rem;
        }
        
        .header h1 {
            font-size: 2.5rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 0.5rem;
        }
        
        .header p {
            font-size: 1.1rem;
            color: #64748b;
            font-weight: 400;
        }
        
        .upload-card {
            background: linear-gradient(145deg, #ffffff, #f0f8ff);
            border-radius: 25px;
            padding: 2.5rem;
            box-shadow: 
                0 20px 40px rgba(0, 0, 0, 0.1),
                0 0 0 3px #ff6b6b,
                0 0 0 6px #4ecdc4,
                0 0 0 9px #45b7d1;
            border: 4px solid #fff;
            transform: rotate(1deg);
            transition: all 0.3s ease;
        }
        
        .upload-card:hover {
            transform: rotate(0deg) scale(1.02);
            box-shadow: 
                0 25px 50px rgba(0, 0, 0, 0.15),
                0 0 0 4px #ff6b6b,
                0 0 0 8px #4ecdc4,
                0 0 0 12px #45b7d1;
        }
        
        .upload-area {
            border: 4px dashed #ff6b6b;
            border-radius: 20px;
            padding: 3rem 2rem;
            text-align: center;
            transition: all 0.3s ease;
            cursor: pointer;
            position: relative;
            background: linear-gradient(45deg, #fff5f5, #f0f8ff);
            transform: rotate(-1deg);
        }
        
        .upload-area:hover {
            border-color: #4ecdc4;
            background: linear-gradient(45deg, #f0fff4, #f0f8ff);
            transform: rotate(0deg) scale(1.05);
            box-shadow: 0 10px 30px rgba(78, 205, 196, 0.3);
        }
        
        .upload-area.dragover {
            border-color: #45b7d1;
            background: linear-gradient(45deg, #e6f3ff, #f0f8ff);
            transform: rotate(0deg) scale(1.08);
            box-shadow: 0 15px 40px rgba(69, 183, 209, 0.4);
        }
        
        .upload-icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 1.5rem;
            background: linear-gradient(45deg, #ff6b6b, #4ecdc4);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            box-shadow: 0 8px 20px rgba(255, 107, 107, 0.3);
            animation: pulse 2s infinite;
        }
        
        .upload-area:hover .upload-icon {
            background: linear-gradient(45deg, #4ecdc4, #45b7d1);
            transform: scale(1.1) rotate(10deg);
            box-shadow: 0 12px 30px rgba(78, 205, 196, 0.4);
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        
        .upload-icon svg {
            width: 32px;
            height: 32px;
        }
        
        .upload-text {
            margin-bottom: 1rem;
        }
        
        .upload-text h3 {
            font-size: 1.25rem;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 0.5rem;
        }
        
        .upload-text p {
            color: #64748b;
            font-size: 0.95rem;
        }
        
        .file-input {
            display: none;
        }
        
        .browse-button {
            display: inline-block;
            background: linear-gradient(45deg, #ff6b6b, #4ecdc4);
            color: white;
            padding: 1rem 2rem;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 700;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            font-size: 1.1rem;
            box-shadow: 0 8px 20px rgba(255, 107, 107, 0.3);
            transform: rotate(-2deg);
        }
        
        .browse-button:hover {
            background: linear-gradient(45deg, #4ecdc4, #45b7d1);
            transform: rotate(0deg) translateY(-3px);
            box-shadow: 0 12px 30px rgba(78, 205, 196, 0.4);
        }
        
        .extract-button {
            width: 100%;
            background: linear-gradient(45deg, #ff6b6b, #4ecdc4, #45b7d1);
            background-size: 200% 200%;
            color: white;
            padding: 1.2rem 2rem;
            border: none;
            border-radius: 30px;
            font-size: 1.3rem;
            font-weight: 800;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 2rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.8rem;
            box-shadow: 0 10px 30px rgba(255, 107, 107, 0.3);
            transform: rotate(-1deg);
            animation: gradientShift 3s ease infinite;
        }
        
        .extract-button:hover {
            transform: rotate(0deg) translateY(-3px);
            box-shadow: 0 15px 40px rgba(78, 205, 196, 0.4);
        }
        
        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        
        .extract-button:disabled {
            background: #94a3b8;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }
        
        .file-info {
            margin-top: 1.5rem;
            padding: 1rem;
            background: #f1f5f9;
            border-radius: 8px;
            display: none;
        }
        
        .file-info.show {
            display: block;
        }
        
        .file-info h4 {
            color: #1e293b;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        
        .file-info p {
            color: #64748b;
            font-size: 0.9rem;
        }
        
        .file-info .file-name {
            color: #3b82f6;
            font-weight: 500;
        }
        
        .loading {
            display: none;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        
        .loading.show {
            display: flex;
        }
        
        .spinner {
            width: 20px;
            height: 20px;
            border: 2px solid #ffffff;
            border-top: 2px solid transparent;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Mobile Responsive */
        @media (max-width: 640px) {
            .container {
                padding: 1rem;
            }
            
            .header h1 {
                font-size: 2rem;
            }
            
            .upload-card {
                padding: 1.5rem;
            }
            
            .upload-area {
                padding: 2rem 1rem;
            }
            
            .upload-icon {
                width: 48px;
                height: 48px;
            }
            
            .upload-icon svg {
                width: 24px;
                height: 24px;
            }
        }
        
        /* Success/Error States */
        .success {
            border-color: #10b981 !important;
            background: #ecfdf5 !important;
        }
        
        .error {
            border-color: #ef4444 !important;
            background: #fef2f2 !important;
        }
        
        /* Modal Animations */
        @keyframes modalFadeIn {
            from {
                opacity: 0;
                transform: scale(0.95);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }
        
        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .modal-content {
            animation: modalSlideIn 0.3s ease-out;
        }
        
        .modal-backdrop {
            animation: modalFadeIn 0.3s ease-out;
        }
        
        /* Print Styles */
        @media print {
            .modal-backdrop {
                background: white !important;
            }
            
            .modal-content {
                box-shadow: none !important;
                border: 1px solid #ccc !important;
            }
            
            button {
                display: none !important;
            }
        }
        
        /* Theme Toggle Styles */
        .theme-toggle-container {
            position: relative;
        }
        
        .theme-toggle {
            background: #f8fafc;
            border: 2px solid #e2e8f0;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .theme-toggle:hover {
            background: #f1f5f9;
            border-color: #cbd5e1;
            transform: scale(1.05);
        }
        
        .theme-icon {
            font-size: 1.5rem;
            transition: all 0.3s ease;
        }
        
        /* Dark Mode Styles */
        body.dark-mode {
            background: #0f172a;
            color: #e2e8f0;
        }
        
        body.dark-mode .header h1 {
            color: #f1f5f9;
        }
        
        body.dark-mode .header p {
            color: #94a3b8;
        }
        
        body.dark-mode .upload-card {
            background: #1e293b;
            border-color: #334155;
        }
        
        body.dark-mode .upload-area {
            background: #0f172a;
            border-color: #475569;
        }
        
        body.dark-mode .upload-area:hover {
            background: #1e293b;
            border-color: #3b82f6;
        }
        
        body.dark-mode .upload-area.dragover {
            background: #1e293b;
            border-color: #3b82f6;
        }
        
        body.dark-mode .upload-icon {
            background: #334155;
        }
        
        body.dark-mode .upload-area:hover .upload-icon {
            background: #3b82f6;
        }
        
        body.dark-mode .upload-text h3 {
            color: #f1f5f9;
        }
        
        body.dark-mode .upload-text p {
            color: #94a3b8;
        }
        
        body.dark-mode .upload-area {
            background: #0f172a !important;
            border-color: #475569 !important;
        }
        
        body.dark-mode .upload-area:hover {
            background: #1e293b !important;
            border-color: #3b82f6 !important;
        }
        
        body.dark-mode .upload-area.dragover {
            background: #1e293b !important;
            border-color: #3b82f6 !important;
        }
        
        body.dark-mode .browse-button {
            background: #3b82f6;
        }
        
        body.dark-mode .browse-button:hover {
            background: #2563eb;
        }
        
        body.dark-mode .extract-button {
            background: #3b82f6;
        }
        
        body.dark-mode .extract-button:hover {
            background: #2563eb;
        }
        
        body.dark-mode .file-info {
            background: #1e293b;
        }
        
        body.dark-mode .file-info h4 {
            color: #f1f5f9;
        }
        
        body.dark-mode .file-info p {
            color: #94a3b8;
        }
        
        body.dark-mode .file-info .file-name {
            color: #60a5fa;
        }
        
        body.dark-mode .theme-toggle {
            background: #1e293b;
            border-color: #334155;
        }
        
        body.dark-mode .theme-toggle:hover {
            background: #334155;
            border-color: #475569;
        }
        
        /* Enhanced Responsive Design */
        @media (max-width: 768px) {
            .modal-content {
                margin: 0.5rem;
                max-height: 95vh;
            }
            
            .invoice-details-grid {
                grid-template-columns: 1fr !important;
                gap: 1rem !important;
            }
            
            .line-items-grid {
                grid-template-columns: 1fr !important;
                gap: 0.5rem !important;
            }
            
            .summary-grid {
                grid-template-columns: 1fr !important;
            }
            
            .action-buttons {
                flex-direction: column !important;
                gap: 0.5rem !important;
            }
            
            .theme-toggle {
                width: 45px;
                height: 45px;
            }
            
            .theme-icon {
                font-size: 1.25rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; width: 100%;">
                <div style="flex: 1;">
                    <h1>Invoice Extractor</h1>
                    <p>Upload your invoice PDF to extract key data automatically</p>
                   
                </div>
                <div class="theme-toggle-container">
                    <button id="themeToggle" class="theme-toggle" onclick="toggleTheme()">
                        <span class="theme-icon" id="themeIcon">🌙</span>
                    </button>
                </div>
            </div>
        </div>
        
        <div class="upload-card">
            <div class="upload-area" id="uploadArea">
                <div class="upload-icon">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                    </svg>
                </div>
                <div class="upload-text">
                    <h3>📄 Drop your PDF here! 🎯</h3>
                    <p>🖱️ or click to browse files! 🗂️</p>
                </div>
                <button class="browse-button" onclick="document.getElementById('fileInput').click()">
                    Browse Files!
                </button>
                <input type="file" id="fileInput" class="file-input" accept=".pdf" />
            </div>
            
            <div class="file-info" id="fileInfo">
                <h4>Selected File:</h4>
                <p class="file-name" id="fileName"></p>
                <p id="fileSize"></p>
            </div>
            
            <button class="extract-button" id="extractButton" disabled>
                <span class="button-text">Extract Data!</span>
                <div class="loading" id="loading">
                    <div class="spinner"></div>
                    <span>🎭 Working my magic...</span>
                </div>
            </button>
        </div>
    </div>

    <script>
        class InvoiceExtractor {
            constructor() {
                this.uploadArea = document.getElementById('uploadArea');
                this.fileInput = document.getElementById('fileInput');
                this.fileInfo = document.getElementById('fileInfo');
                this.fileName = document.getElementById('fileName');
                this.fileSize = document.getElementById('fileSize');
                this.extractButton = document.getElementById('extractButton');
                this.loading = document.getElementById('loading');
                this.buttonText = document.querySelector('.button-text');
                
                this.init();
            }
            
            init() {
                this.setupEventListeners();
            }
            
            setupEventListeners() {
                // File input change
                this.fileInput.addEventListener('change', (e) => this.handleFileSelect(e));
                
                // Drag and drop events
                this.uploadArea.addEventListener('dragover', (e) => this.handleDragOver(e));
                this.uploadArea.addEventListener('dragleave', (e) => this.handleDragLeave(e));
                this.uploadArea.addEventListener('drop', (e) => this.handleDrop(e));
                
                // Click to upload (but not on the browse button)
                this.uploadArea.addEventListener('click', (e) => {
                    // Don't trigger if clicking on the browse button
                    if (!e.target.closest('.browse-button')) {
                        this.fileInput.click();
                    }
                });
                
                // Extract button
                this.extractButton.addEventListener('click', () => this.extractData());
            }
            
            handleDragOver(e) {
                e.preventDefault();
                this.uploadArea.classList.add('dragover');
            }
            
            handleDragLeave(e) {
                e.preventDefault();
                this.uploadArea.classList.remove('dragover');
            }
            
            handleDrop(e) {
                e.preventDefault();
                this.uploadArea.classList.remove('dragover');
                
                const files = e.dataTransfer.files;
                if (files.length > 0) {
                    // Update the file input with the dropped file
                    const dataTransfer = new DataTransfer();
                    dataTransfer.items.add(files[0]);
                    this.fileInput.files = dataTransfer.files;
                    
                    this.processFile(files[0]);
                }
            }
            
            handleFileSelect(e) {
                const file = e.target.files[0];
                if (file) {
                    this.processFile(file);
                }
            }
            
            processFile(file) {
                // Validate file type
                if (file.type !== 'application/pdf') {
                    this.showError('Please select a valid PDF file.');
                    return;
                }
                
                // Validate file size (max 10MB)
                if (file.size > 10 * 1024 * 1024) {
                    this.showError('File size must be less than 10MB.');
                    return;
                }
                
                this.showFileInfo(file);
                this.extractButton.disabled = false;
                this.uploadArea.classList.add('success');
            }
            
            showFileInfo(file) {
                this.fileName.textContent = file.name;
                this.fileSize.textContent = this.formatFileSize(file.size);
                this.fileInfo.classList.add('show');
            }
            
            showError(message) {
                this.uploadArea.classList.add('error');
                this.fileInfo.classList.remove('show');
                this.extractButton.disabled = true;
                
                // Remove error state after 3 seconds
                setTimeout(() => {
                    this.uploadArea.classList.remove('error');
                }, 3000);
                
                alert(message);
            }
            
            formatFileSize(bytes) {
                if (bytes === 0) return '0 Bytes';
                const k = 1024;
                const sizes = ['Bytes', 'KB', 'MB', 'GB'];
                const i = Math.floor(Math.log(bytes) / Math.log(k));
                return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
            }
            
            async extractData() {
                // Check if we have a file selected
                if (!this.fileInput.files || this.fileInput.files.length === 0) {
                    alert('Please select a PDF file first.');
                    return;
                }
                
                const file = this.fileInput.files[0];
                if (!file) {
                    alert('Please select a PDF file first.');
                    return;
                }

                // Show loading state
                this.extractButton.disabled = true;
                this.buttonText.style.display = 'none';
                this.loading.classList.add('show');
                
                try {
                    // Create FormData to send the PDF file
                    const formData = new FormData();
                    formData.append('pdf', file);
                    
                    // Get CSRF token
                    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                    
                    // Send to backend
                    const response = await fetch('/api/extract-invoice', {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-CSRF-TOKEN': csrfToken
                        }
                    });
                    
                    // Debug: Check response status and content type
                    console.log('Response status:', response.status);
                    console.log('Response content-type:', response.headers.get('content-type'));
                    
                    // Check if response is JSON
                    const contentType = response.headers.get('content-type');
                    if (!contentType || !contentType.includes('application/json')) {
                        const text = await response.text();
                        console.error('Non-JSON response:', text);
                        throw new Error('Server returned non-JSON response. Check console for details.');
                    }
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        this.displayExtractedData(result.data);
                    } else {
                        throw new Error(result.error || 'Failed to extract data');
                    }
                    
                } catch (error) {
                    console.error('Error:', error);
                    alert('Error extracting data: ' + error.message);
                } finally {
                    // Reset button state
                    this.buttonText.style.display = 'block';
                    this.loading.classList.remove('show');
                    this.extractButton.disabled = false;
                }
            }
            
            displayExtractedData(data) {
                // Create a modal to show the extracted data in a beautiful format
                const modal = this.createInvoiceSummaryModal(data);
                document.body.appendChild(modal);
                modal.style.display = 'flex';
            }
            
            createInvoiceSummaryModal(data) {
                const modal = document.createElement('div');
                modal.className = 'modal-backdrop';
                modal.style.cssText = `
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    background: rgba(0, 0, 0, 0.5);
                    display: none;
                    align-items: center;
                    justify-content: center;
                    z-index: 1000;
                    padding: 1rem;
                `;
                
                const modalContent = document.createElement('div');
                modalContent.className = 'modal-content';
                modalContent.style.cssText = `
                    background: white;
                    border-radius: 16px;
                    padding: 0;
                    max-width: 900px;
                    max-height: 90vh;
                    overflow-y: auto;
                    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
                    width: 100%;
                `;
                
                // Create the invoice summary HTML
                const invoiceSummary = this.createInvoiceSummaryHTML(data);
                modalContent.innerHTML = invoiceSummary;
                
                // Close modal functionality
                const closeModal = () => {
                    document.body.removeChild(modal);
                };
                
                modalContent.querySelector('#closeModal').addEventListener('click', closeModal);
                modalContent.querySelector('#viewRawJson').addEventListener('click', () => {
                    this.showRawJsonModal(data);
                });
                modalContent.querySelector('#saveToDatabase').addEventListener('click', () => {
                    this.saveInvoiceToDatabase(data);
                });
                modalContent.querySelector('#generateQrCode').addEventListener('click', () => {
                    this.generateSepaQrCode(data);
                });
                modalContent.querySelector('#downloadQrCode').addEventListener('click', () => {
                    this.downloadSepaQrCode(data);
                });
                
                // Format selector change handler
                modalContent.querySelector('#qrFormatSelector').addEventListener('change', (e) => {
                    this.updateFormatDescription(e.target.value);
                });
                modal.addEventListener('click', (e) => {
                    if (e.target === modal) closeModal();
                });
                
                modal.appendChild(modalContent);
                return modal;
            }
            
            createInvoiceSummaryHTML(data) {
                // Extract key information from the data
                const invoiceNumber = data.invoice_number || data.invoiceNumber || 'N/A';
                const vendor = data.vendor || data.supplier || data.company_name || 'N/A';
                const date = data.date || data.invoice_date || data.issue_date || 'N/A';
                const total = data.total || data.total_amount || data.grand_total || 'N/A';
                const subtotal = data.subtotal || data.sub_total || 'N/A';
                const tax = data.tax || data.tax_amount || data.vat || 'N/A';
                const lineItems = data.line_items || data.items || data.products || [];
                
                // Format currency
                const formatCurrency = (amount) => {
                    if (amount === 'N/A' || !amount) return 'N/A';
                    const num = parseFloat(amount);
                    if (isNaN(num)) return amount;
                    return new Intl.NumberFormat('en-US', {
                        style: 'currency',
                        currency: 'USD'
                    }).format(num);
                };
                
                // Format date
                const formatDate = (dateStr) => {
                    if (dateStr === 'N/A' || !dateStr) return 'N/A';
                    try {
                        const date = new Date(dateStr);
                        return date.toLocaleDateString('en-US', {
                            year: 'numeric',
                            month: 'long',
                            day: 'numeric'
                        });
                    } catch {
                        return dateStr;
                    }
                };
                
                return `
                    <div style="padding: 2rem;">
                        <!-- Header -->
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; padding-bottom: 1rem; border-bottom: 2px solid #e2e8f0;">
                            <h2 style="color: #1e293b; font-size: 1.75rem; font-weight: 700; margin: 0;">Invoice Summary</h2>
                            <button id="closeModal" style="background: none; border: none; font-size: 1.5rem; cursor: pointer; color: #64748b; padding: 0.5rem; border-radius: 50%; transition: all 0.2s;" onmouseover="this.style.background='#f1f5f9'" onmouseout="this.style.background='none'">&times;</button>
                        </div>
                        
                        <!-- Invoice Details Card -->
                        <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 12px; padding: 2rem; margin-bottom: 2rem; color: white;">
                            <div class="invoice-details-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem;">
                                <div>
                                    <div style="font-size: 0.875rem; opacity: 0.9; margin-bottom: 0.25rem;">Invoice Number</div>
                                    <div style="font-size: 1.25rem; font-weight: 600;">${invoiceNumber}</div>
                                </div>
                                <div>
                                    <div style="font-size: 0.875rem; opacity: 0.9; margin-bottom: 0.25rem;">Vendor</div>
                                    <div style="font-size: 1.25rem; font-weight: 600;">${vendor}</div>
                                </div>
                                <div>
                                    <div style="font-size: 0.875rem; opacity: 0.9; margin-bottom: 0.25rem;">Date</div>
                                    <div style="font-size: 1.25rem; font-weight: 600;">${formatDate(date)}</div>
                                </div>
                                <div>
                                    <div style="font-size: 0.875rem; opacity: 0.9; margin-bottom: 0.25rem;">Total Amount</div>
                                    <div style="font-size: 1.5rem; font-weight: 700;">${formatCurrency(total)}</div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Line Items Table -->
                        ${lineItems.length > 0 ? `
                            <div style="margin-bottom: 2rem;">
                                <h3 style="color: #1e293b; font-size: 1.25rem; font-weight: 600; margin-bottom: 1rem;">Line Items</h3>
                                <div style="background: white; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden;">
                                    <div class="line-items-grid" style="background: #f8fafc; padding: 1rem; border-bottom: 1px solid #e2e8f0; display: grid; grid-template-columns: 2fr 1fr 1fr 1fr; gap: 1rem; font-weight: 600; color: #374151; font-size: 0.875rem;">
                                        <div>Description</div>
                                        <div>Quantity</div>
                                        <div>Unit Price</div>
                                        <div>Total</div>
                                    </div>
                                    ${lineItems.map(item => `
                                        <div class="line-items-grid" style="padding: 1rem; border-bottom: 1px solid #f1f5f9; display: grid; grid-template-columns: 2fr 1fr 1fr 1fr; gap: 1rem; align-items: center;">
                                            <div style="color: #374151; font-weight: 500;">${item.description || item.name || item.item || 'N/A'}</div>
                                            <div style="color: #6b7280;">${item.quantity || item.qty || 'N/A'}</div>
                                            <div style="color: #6b7280;">${formatCurrency(item.unit_price || item.price || item.rate)}</div>
                                            <div style="color: #1e293b; font-weight: 600;">${formatCurrency(item.total || item.amount || item.line_total)}</div>
                                        </div>
                                    `).join('')}
                                </div>
                            </div>
                        ` : ''}
                        
                        <!-- Summary Section -->
                        <div style="background: #f8fafc; border-radius: 8px; padding: 1.5rem; margin-bottom: 2rem;">
                            <h3 style="color: #1e293b; font-size: 1.25rem; font-weight: 600; margin-bottom: 1rem;">Summary</h3>
                            <div class="summary-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem;">
                                <div style="text-align: center; padding: 1rem; background: white; border-radius: 6px; border: 1px solid #e2e8f0;">
                                    <div style="font-size: 0.875rem; color: #6b7280; margin-bottom: 0.25rem;">Subtotal</div>
                                    <div style="font-size: 1.125rem; font-weight: 600; color: #1e293b;">${formatCurrency(subtotal)}</div>
                                </div>
                                <div style="text-align: center; padding: 1rem; background: white; border-radius: 6px; border: 1px solid #e2e8f0;">
                                    <div style="font-size: 0.875rem; color: #6b7280; margin-bottom: 0.25rem;">Tax</div>
                                    <div style="font-size: 1.125rem; font-weight: 600; color: #1e293b;">${formatCurrency(tax)}</div>
                                </div>
                                <div style="text-align: center; padding: 1rem; background: linear-gradient(135deg, #10b981, #059669); border-radius: 6px; color: white;">
                                    <div style="font-size: 0.875rem; opacity: 0.9; margin-bottom: 0.25rem;">Total</div>
                                    <div style="font-size: 1.25rem; font-weight: 700;">${formatCurrency(total)}</div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- SEPA QR Code Section -->
                        <div style="background: #f8fafc; border-radius: 8px; padding: 1.5rem; margin-bottom: 2rem; border: 1px solid #e2e8f0;">
                            <h3 style="color: #1e293b; font-size: 1.25rem; font-weight: 600; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                                🏦 Payment QR Code
                            </h3>
                            <div style="display: flex; align-items: center; gap: 2rem; flex-wrap: wrap;">
                                <div id="qrCodeContainer" style="display: none;">
                                    <img id="qrCodeImage" style="border: 2px solid #e2e8f0; border-radius: 8px; max-width: 200px;" />
                                </div>
                                <div style="flex: 1; min-width: 200px;">
                                    <p style="color: #6b7280; font-size: 0.9rem; margin-bottom: 1rem;">
                                        Generate a QR code for easy bank transfer. Choose the format that works with your customer's banking app.
                                    </p>
                                    
                                    <!-- Format Selector -->
                                    <div style="margin-bottom: 1rem;">
                                        <label style="display: block; font-weight: 500; margin-bottom: 0.5rem; color: #374151;">QR Code Format:</label>
                                        <select id="qrFormatSelector" style="padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 6px; background: white; width: 100%; max-width: 300px;">
                                            <option value="sepa">SEPA Format (Works with Revolut)</option>
                                            <option value="upn">UPN Format (Works with NLB Klik/Flik)</option>
                                        </select>
                                        <div id="formatDescription" style="font-size: 0.8rem; color: #6b7280; margin-top: 0.25rem;">
                                            ✅ Compatible with Revolut and most European banking apps
                                        </div>
                                    </div>
                                    
                                    <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                                        <button id="generateQrCode" style="background: #3b82f6; color: white; padding: 0.75rem 1.5rem; border: none; border-radius: 8px; font-weight: 500; cursor: pointer; transition: all 0.2s; display: flex; align-items: center; gap: 0.5rem;" onmouseover="this.style.background='#2563eb'" onmouseout="this.style.background='#3b82f6'">
                                            <span>📱</span> Generate QR Code
                                        </button>
                                        <button id="downloadQrCode" style="background: #10b981; color: white; padding: 0.75rem 1.5rem; border: none; border-radius: 8px; font-weight: 500; cursor: pointer; transition: all 0.2s; display: none; align-items: center; gap: 0.5rem;" onmouseover="this.style.background='#059669'" onmouseout="this.style.background='#10b981'">
                                            <span>💾</span> Download QR Code
                                        </button>
                                    </div>
                                    <div id="qrCodeLoading" style="display: none; color: #6b7280; font-size: 0.9rem; margin-top: 0.5rem;">
                                        Generating QR code...
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="action-buttons" style="display: flex; gap: 1rem; justify-content: flex-end; padding-top: 1rem; border-top: 1px solid #e2e8f0;">
                            <button id="viewRawJson" style="background: #6b7280; color: white; padding: 0.75rem 1.5rem; border: none; border-radius: 8px; font-weight: 500; cursor: pointer; transition: all 0.2s;" onmouseover="this.style.background='#4b5563'" onmouseout="this.style.background='#6b7280'">
                                View Raw JSON
                            </button>
                            <button id="saveToDatabase" style="background: #10b981; color: black; padding: 0.75rem 1.5rem; border: none; border-radius: 8px; font-weight: 500; cursor: pointer; transition: all 0.2s;" onmouseover="this.style.background='#059669'" onmouseout="this.style.background='#10b981'">
                                Save to Database
                            </button>
                        </div>
                    </div>
                `;
            }
            
            showRawJsonModal(data) {
                const modal = document.createElement('div');
                modal.style.cssText = `
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    background: rgba(0, 0, 0, 0.5);
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    z-index: 1001;
                    padding: 2rem;
                `;
                
                const modalContent = document.createElement('div');
                modalContent.style.cssText = `
                    background: white;
                    border-radius: 16px;
                    padding: 2rem;
                    max-width: 800px;
                    max-height: 80vh;
                    overflow-y: auto;
                    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
                `;
                
                modalContent.innerHTML = `
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                        <h2 style="color: #1e293b; font-size: 1.5rem; font-weight: 600;">Raw JSON Data</h2>
                        <button id="closeJsonModal" style="background: none; border: none; font-size: 1.5rem; cursor: pointer; color: #64748b;">&times;</button>
                    </div>
                    <div id="rawJsonData" style="font-family: 'Courier New', monospace; background: #f8fafc; padding: 1rem; border-radius: 8px; white-space: pre-wrap; font-size: 0.9rem; line-height: 1.5; border: 1px solid #e2e8f0; max-height: 60vh; overflow-y: auto;"></div>
                `;
                
                // Format and display the JSON data
                const dataElement = modalContent.querySelector('#rawJsonData');
                dataElement.textContent = JSON.stringify(data, null, 2);
                
                // Close modal functionality
                const closeModal = () => {
                    document.body.removeChild(modal);
                };
                
                modalContent.querySelector('#closeJsonModal').addEventListener('click', closeModal);
                modal.addEventListener('click', (e) => {
                    if (e.target === modal) closeModal();
                });
                
                modal.appendChild(modalContent);
                document.body.appendChild(modal);
            }
            
            async saveInvoiceToDatabase(data) {
                try {
                    // Show loading state
                    const saveButton = document.getElementById('saveToDatabase');
                    const originalText = saveButton.textContent;
                    saveButton.textContent = 'Saving...';
                    saveButton.disabled = true;
                    
                    // Get CSRF token
                    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                    
                    // Send data to backend
                    const response = await fetch('/api/save-invoice', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken
                        },
                        body: JSON.stringify({
                            invoice_data: data
                        })
                    });
                    
                    // Check if response is JSON
                    const contentType = response.headers.get('content-type');
                    if (!contentType || !contentType.includes('application/json')) {
                        const text = await response.text();
                        console.error('Non-JSON response:', text);
                        throw new Error('Server returned non-JSON response. Check console for details.');
                    }
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        // Show success message
                        saveButton.textContent = '✓ Saved!';
                        saveButton.style.background = '#10b981';
                        
                        // Show success notification
                        this.showNotification('Invoice saved to database successfully!', 'success');
                        
                        // Update the modal to show saved status
                        const header = document.querySelector('h2').parentElement;
                        if (!header.querySelector('.saved-indicator')) {
                            const savedIndicator = document.createElement('p');
                            savedIndicator.className = 'saved-indicator';
                            savedIndicator.style.cssText = 'color: #10b981; font-size: 0.875rem; margin: 0.25rem 0 0 0; font-weight: 500;';
                            savedIndicator.textContent = `✓ Saved to database (ID: ${result.invoice_id})`;
                            header.appendChild(savedIndicator);
                        }
                        
                        // Reset button after 3 seconds
                        setTimeout(() => {
                            saveButton.textContent = originalText;
                            saveButton.disabled = false;
                            saveButton.style.background = '#10b981';
                        }, 3000);
                        
                    } else {
                        throw new Error(result.error || 'Failed to save invoice');
                    }
                    
                } catch (error) {
                    console.error('Error saving invoice:', error);
                    this.showNotification('Error saving invoice: ' + error.message, 'error');
                    
                    // Reset button
                    const saveButton = document.getElementById('saveToDatabase');
                    saveButton.textContent = 'Save to Database';
                    saveButton.disabled = false;
                }
            }
            
            showNotification(message, type = 'info') {
                const notification = document.createElement('div');
                notification.style.cssText = `
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    padding: 1rem 1.5rem;
                    border-radius: 8px;
                    color: white;
                    font-weight: 500;
                    z-index: 10000;
                    max-width: 400px;
                    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
                    transform: translateX(100%);
                    transition: transform 0.3s ease;
                `;
                
                if (type === 'success') {
                    notification.style.background = '#10b981';
                } else if (type === 'error') {
                    notification.style.background = '#ef4444';
                } else {
                    notification.style.background = '#3b82f6';
                }
                
                notification.textContent = message;
                document.body.appendChild(notification);
                
                // Animate in
                setTimeout(() => {
                    notification.style.transform = 'translateX(0)';
                }, 100);
                
                // Auto remove after 5 seconds
                setTimeout(() => {
                    notification.style.transform = 'translateX(100%)';
                    setTimeout(() => {
                        if (notification.parentNode) {
                            notification.parentNode.removeChild(notification);
                        }
                    }, 300);
                }, 5000);
            }

            updateFormatDescription(format) {
                const descriptionDiv = document.getElementById('formatDescription');
                if (format === 'sepa') {
                    descriptionDiv.innerHTML = '✅ Compatible with Revolut and most European banking apps';
                } else if (format === 'upn') {
                    descriptionDiv.innerHTML = '✅ Compatible with NLB Klik/Flik and Slovenian banking apps';
                }
            }

            async generateSepaQrCode(data) {
                try {
                    // Show loading state
                    const generateButton = document.getElementById('generateQrCode');
                    const loadingDiv = document.getElementById('qrCodeLoading');
                    const originalText = generateButton.innerHTML;
                    
                    generateButton.disabled = true;
                    generateButton.innerHTML = '<span>⏳</span> Generating...';
                    loadingDiv.style.display = 'block';
                    
                    // Get selected format
                    const formatSelector = document.getElementById('qrFormatSelector');
                    const selectedFormat = formatSelector.value;
                    
                    // Get CSRF token
                    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                    
                    // Choose endpoint based on format
                    const endpoint = selectedFormat === 'upn' ? '/api/generate-upn-qr' : '/api/generate-sepa-qr';
                    
                    // Send data to backend
                    const response = await fetch(endpoint, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken
                        },
                        body: JSON.stringify({
                            invoice_data: data
                        })
                    });
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        // Display QR code
                        const qrCodeContainer = document.getElementById('qrCodeContainer');
                        const qrCodeImage = document.getElementById('qrCodeImage');
                        const downloadButton = document.getElementById('downloadQrCode');
                        
                        qrCodeImage.src = 'data:image/svg+xml;base64,' + result.qr_code;
                        qrCodeContainer.style.display = 'block';
                        downloadButton.style.display = 'flex';
                        
                        const formatName = selectedFormat === 'upn' ? 'UPN' : 'SEPA';
                        this.showNotification(`${formatName} QR code generated successfully!`, 'success');
                    } else {
                        throw new Error(result.error || 'Failed to generate QR code');
                    }
                    
                } catch (error) {
                    console.error('Error generating QR code:', error);
                    this.showNotification('Error generating QR code: ' + error.message, 'error');
                } finally {
                    // Reset button state
                    const generateButton = document.getElementById('generateQrCode');
                    const loadingDiv = document.getElementById('qrCodeLoading');
                    
                    generateButton.disabled = false;
                    generateButton.innerHTML = '<span>📱</span> Generate QR Code';
                    loadingDiv.style.display = 'none';
                }
            }

            async downloadSepaQrCode(data) {
                try {
                    // Show loading state
                    const downloadButton = document.getElementById('downloadQrCode');
                    const originalText = downloadButton.innerHTML;
                    
                    downloadButton.disabled = true;
                    downloadButton.innerHTML = '<span>⏳</span> Downloading...';
                    
                    // Get CSRF token
                    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                    
                    // Create form data for file download
                    const formData = new FormData();
                    formData.append('invoice_data', JSON.stringify(data));
                    formData.append('_token', csrfToken);
                    
                    // Send request for file download
                    const response = await fetch('/api/download-sepa-qr', {
                        method: 'POST',
                        body: formData
                    });
                    
                    if (response.ok) {
                        // Get filename from response headers
                        const contentDisposition = response.headers.get('Content-Disposition');
                        let filename = 'SEPA_QR_Code.svg';
                        if (contentDisposition) {
                            const filenameMatch = contentDisposition.match(/filename="(.+)"/);
                            if (filenameMatch) {
                                filename = filenameMatch[1];
                            }
                        }
                        
                        // Create blob and download
                        const blob = await response.blob();
                        const url = window.URL.createObjectURL(blob);
                        const a = document.createElement('a');
                        a.href = url;
                        a.download = filename;
                        document.body.appendChild(a);
                        a.click();
                        window.URL.revokeObjectURL(url);
                        document.body.removeChild(a);
                        
                        this.showNotification('QR code downloaded successfully!', 'success');
                    } else {
                        const errorData = await response.json();
                        throw new Error(errorData.error || 'Failed to download QR code');
                    }
                    
                } catch (error) {
                    console.error('Error downloading QR code:', error);
                    this.showNotification('Error downloading QR code: ' + error.message, 'error');
                } finally {
                    // Reset button state
                    const downloadButton = document.getElementById('downloadQrCode');
                    downloadButton.disabled = false;
                    downloadButton.innerHTML = '<span>💾</span> Download QR Code';
                }
            }
        }
        
        // Theme toggle functionality
        function toggleTheme() {
            const body = document.body;
            const themeIcon = document.getElementById('themeIcon');
            
            if (body.classList.contains('dark-mode')) {
                // Switch to light mode
                body.classList.remove('dark-mode');
                themeIcon.textContent = '🌙';
                localStorage.setItem('theme', 'light');
            } else {
                // Switch to dark mode
                body.classList.add('dark-mode');
                themeIcon.textContent = '☀️';
                localStorage.setItem('theme', 'dark');
            }
        }
        
        // Load saved theme on page load
        function loadTheme() {
            const savedTheme = localStorage.getItem('theme');
            const body = document.body;
            const themeIcon = document.getElementById('themeIcon');
            
            if (savedTheme === 'dark') {
                body.classList.add('dark-mode');
                themeIcon.textContent = '☀️';
            } else {
                body.classList.remove('dark-mode');
                themeIcon.textContent = '🌙';
            }
        }
        
        // Initialize the app when the page loads
        document.addEventListener('DOMContentLoaded', () => {
            loadTheme(); // Load saved theme first
            new InvoiceExtractor();
        });
    </script>
</body>
</html>
