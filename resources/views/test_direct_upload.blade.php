<!DOCTYPE html>
<html>
<head>
    <title>Direct RAG Upload Test</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body>
    <h2>Direct RAG Upload Test</h2>
    <form id="testForm">
        <p>Select Product ID: 
            <select id="productId" required>
                <option value="">Select Product...</option>
                @foreach(\App\Models\Product::latest()->take(5)->get() as $product)
                <option value="{{ $product->id }}">{{ $product->id }} - {{ $product->name }}</option>
                @endforeach
            </select>
        </p>
        <p>Select file: <input type="file" id="fileInput" accept=".pdf,.doc,.docx,.txt" required></p>
        <button type="button" onclick="testUpload()">Test Upload</button>
    </form>
    
    <div id="result"></div>
    
    <hr>
    
    <div id="attachmentsList">
        <h3>Recent Attachments:</h3>
        <ul id="attachments"></ul>
    </div>

    <script>
    function testUpload() {
        const productId = document.getElementById('productId').value;
        const fileInput = document.getElementById('fileInput');
        
        if (!productId || fileInput.files.length === 0) {
            alert('Please select both product and file');
            return;
        }
        
        const formData = new FormData();
        const file = fileInput.files[0];
        
        formData.append('files[]', file);
        formData.append('attachment_types[]', 'documentation');
        formData.append('titles[]', file.name);
        formData.append('descriptions[]', 'Test RAG document');
        formData.append('is_public[]', true);
        formData.append('process_with_rag', true);
        
        console.log('Uploading to:', `/api/products/${productId}/attachments`);
        
        fetch(`/api/products/${productId}/attachments`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        })
        .then(response => {
            console.log('Response status:', response.status);
            return response.text();
        })
        .then(text => {
            console.log('Response text:', text);
            document.getElementById('result').innerHTML = `<pre>${text}</pre>`;
            loadAttachments();
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('result').innerHTML = `Error: ${error.message}`;
        });
    }
    
    function loadAttachments() {
        const productId = document.getElementById('productId').value;
        if (!productId) return;
        
        fetch(`/api/products/${productId}/attachments`)
        .then(response => response.json())
        .then(data => {
            const list = document.getElementById('attachments');
            list.innerHTML = '';
            
            if (data.attachments && data.attachments.length > 0) {
                data.attachments.forEach(att => {
                    const li = document.createElement('li');
                    li.innerHTML = `<strong>${att.title}</strong> - ${att.original_filename} (${att.file_size} bytes) - <a href="/storage/${att.file_path}" target="_blank">View</a>`;
                    list.appendChild(li);
                });
            } else {
                list.innerHTML = '<li>No attachments found</li>';
            }
        })
        .catch(error => {
            console.error('Error loading attachments:', error);
        });
    }
    
    // Load attachments when product changes
    document.getElementById('productId').addEventListener('change', loadAttachments);
    </script>
</body>
</html>