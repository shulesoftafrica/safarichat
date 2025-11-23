<?php

// Temporary test route for QR code functionality
use Illuminate\Support\Facades\Route;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

Route::get('/test-qr', function () {
    try {
        // Test if QR library is working
        $qrCode = QrCode::format('png')->size(300)->generate('Test QR Code');
        $base64 = 'data:image/png;base64,' . base64_encode($qrCode);
        
        return response()->json([
            'success' => true,
            'qr_code' => $base64,
            'message' => 'QR library working correctly'
        ]);
    } catch (Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
});