<!DOCTYPE html>
<html>
<head>
    <title>Simple Upload Test</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        .log { background: #f5f5f5; padding: 10px; margin: 10px 0; border-left: 4px solid #007cba; }
        .error { border-left-color: #dc3545; background: #f8d7da; }
        .success { border-left-color: #28a745; background: #d4edda; }
        .form-group { margin: 15px 0; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input, select, button { padding: 8px; margin: 5px 0; }
        button { background: #007cba; color: white; border: none; cursor: pointer; padding: 10px 20px; }
        button:hover { background: #0056b3; }
    </style>
</head>
<body>
    <h1>RAG Document Upload Test</h1>
    
    <div class="form-group">
        <label for="productId">Product ID:</label>
        <select id="productId" required>
            <option value="">Select Product...</option>
            @foreach(\App\Models\Product::orderBy('id')->take(10)->get() as $product)
            <option value="{{ $product->id }}">{{ $product->id }} - {{ $product->name }}</option>
            @endforeach
        </select>
    </div>
    
    <div class="form-group">
        <label for="fileInput">Select File:</label>
        <input type="file" id="fileInput" accept=".pdf,.doc,.docx,.txt" required>
    </div>
    
    <button onclick="testDirectUpload()">Test Upload</button>
    <button onclick="testGetAttachments()">Get Attachments</button>
    <button onclick="clearLogs()">Clear Logs</button>
    
    <div id="logs"></div>

    <script>
    function log(message, type = 'log') {
        const logs = document.getElementById('logs');
        const div = document.createElement('div');
        div.className = `log ${type}`;
        div.innerHTML = `<strong>[${new Date().toLocaleTimeString()}]</strong> ${message}`;
        logs.appendChild(div);
        console.log(`[${type.toUpperCase()}]`, message);
    }
    
    function clearLogs() {
        document.getElementById('logs').innerHTML = '';
    }
    
    function testDirectUpload() {
        const productId = document.getElementById('productId').value;
        const fileInput = document.getElementById('fileInput');
        
        if (!productId) {
            log('Please select a product', 'error');
            return;
        }
        
        if (!fileInput.files.length) {
            log('Please select a file', 'error');
            return;
        }
        
        const file = fileInput.files[0];
        log(`Starting upload test for Product ID: ${productId}, File: ${file.name}`);
        
        const formData = new FormData();
        formData.append('files[]', file);
        formData.append('attachment_types[]', 'technical_spec');
        formData.append('titles[]', file.name);
        formData.append('descriptions[]', 'Test upload from direct test page');
        formData.append('is_public[]', true);
        formData.append('process_with_rag', false);
        
        const url = `/api/products/${productId}/attachments`;
        log(`API URL: ${url}`);
        
        fetch(url, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        })
        .then(response => {
            log(`Response Status: ${response.status} ${response.statusText}`);
            log(`Response URL: ${response.url}`);
            
            if (!response.ok) {
                return response.text().then(text => {
                    log(`ERROR RESPONSE: ${text}`, 'error');
                    throw new Error(`HTTP ${response.status}: ${text}`);
                });
            }
            return response.json();
        })
        .then(data => {
            log(`SUCCESS: ${JSON.stringify(data, null, 2)}`, 'success');
            
            if (data.files && data.files.length > 0) {
                const file = data.files[0];
                log(`File uploaded with ID: ${file.id}, URL: ${file.url}`, 'success');
            }
        })
        .catch(error => {
            log(`UPLOAD FAILED: ${error.message}`, 'error');
            console.error('Full error:', error);
        });
    }
    
    function testGetAttachments() {
        const productId = document.getElementById('productId').value;
        
        if (!productId) {
            log('Please select a product', 'error');
            return;
        }
        
        const url = `/api/products/${productId}/attachments`;
        log(`Getting attachments from: ${url}`);
        
        fetch(url, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => {
            log(`Response Status: ${response.status} ${response.statusText}`);
            
            if (!response.ok) {
                return response.text().then(text => {
                    log(`ERROR RESPONSE: ${text}`, 'error');
                    throw new Error(`HTTP ${response.status}: ${text}`);
                });
            }
            return response.json();
        })
        .then(data => {
            log(`ATTACHMENTS: ${JSON.stringify(data, null, 2)}`, 'success');
            
            if (data.attachments && data.attachments.length > 0) {
                data.attachments.forEach((att, index) => {
                    log(`Attachment ${index + 1}: ${att.title} (${att.filename})`, 'success');
                });
            } else {
                log('No attachments found for this product', 'log');
            }
        })
        .catch(error => {
            log(`GET ATTACHMENTS FAILED: ${error.message}`, 'error');
            console.error('Full error:', error);
        });
    }
    </script>
</body>
</html>