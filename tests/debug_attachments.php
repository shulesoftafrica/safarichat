<?php
// Quick debug script to check what's actually in the database
$pdo = new PDO('pgsql:host=localhost;dbname=safarichat_db', 'postgres', 'password');

echo "=== CHECKING DATABASE RECORDS ===\n\n";

// Check products table
echo "Recent products:\n";
$stmt = $pdo->query("SELECT id, name, created_at FROM products ORDER BY created_at DESC LIMIT 5");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "Product ID {$row['id']}: {$row['name']} ({$row['created_at']})\n";
}

echo "\n=== CHECKING ATTACHMENTS ===\n";
// Check if product_attachments table exists
$tables = $pdo->query("SELECT tablename FROM pg_tables WHERE schemaname = 'public' AND tablename LIKE '%attachment%'")->fetchAll();
echo "Tables with 'attachment': " . implode(', ', array_column($tables, 'tablename')) . "\n\n";

// Check attachments if table exists
if (in_array('product_attachments', array_column($tables, 'tablename'))) {
    echo "Product attachments:\n";
    $stmt = $pdo->query("SELECT id, product_id, original_filename, file_path, created_at FROM product_attachments ORDER BY created_at DESC LIMIT 10");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "Attachment ID {$row['id']}: Product {$row['product_id']} - {$row['original_filename']} ({$row['created_at']})\n";
        echo "  File path: {$row['file_path']}\n";
    }
    
    // Check if files exist
    echo "\nChecking if files exist on disk:\n";
    $stmt = $pdo->query("SELECT file_path FROM product_attachments ORDER BY created_at DESC LIMIT 5");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $fullPath = "storage/app/public/" . $row['file_path'];
        $exists = file_exists($fullPath) ? "✓ EXISTS" : "✗ MISSING";
        echo "  {$row['file_path']} - $exists\n";
    }
} else {
    echo "product_attachments table not found!\n";
}

echo "\n=== CHECKING RECENT STORAGE FILES ===\n";
$attachmentDir = "storage/app/public/attachments";
if (is_dir($attachmentDir)) {
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($attachmentDir));
    $recentFiles = [];
    
    foreach ($files as $file) {
        if ($file->isFile()) {
            $recentFiles[] = [
                'path' => $file->getPathname(),
                'time' => $file->getMTime(),
                'size' => $file->getSize()
            ];
        }
    }
    
    // Sort by modification time, newest first
    usort($recentFiles, function($a, $b) { return $b['time'] - $a['time']; });
    
    echo "Recent files in storage:\n";
    foreach (array_slice($recentFiles, 0, 10) as $file) {
        echo "  " . basename($file['path']) . " - " . date('Y-m-d H:i:s', $file['time']) . " - " . round($file['size']/1024, 2) . " KB\n";
    }
} else {
    echo "Attachments directory not found!\n";
}
?>