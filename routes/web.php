<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\InvoiceController;




Route::get('/', function () {
    return view('invoice-extractor');
});



// Chat API route
Route::post('/api/chat', [ChatController::class, 'sendMessage']);

// Invoice extraction API route
Route::post('/api/extract-invoice', [InvoiceController::class, 'extractInvoiceData']);

// Save invoice data API route
Route::post('/api/save-invoice', [InvoiceController::class, 'saveInvoiceData']);

// SEPA QR code generation API routes
Route::post('/api/generate-sepa-qr', [InvoiceController::class, 'generateSepaQrCode']);
Route::post('/api/generate-upn-qr', [InvoiceController::class, 'generateUpnQrCode']);
Route::post('/api/download-sepa-qr', [InvoiceController::class, 'downloadSepaQrCode']);

// Test QR code route (for debugging)
Route::get('/api/test-qr', [InvoiceController::class, 'testQrCode']);
Route::get('/api/test-sepa-qr', [InvoiceController::class, 'testSepaQrCode']);
Route::get('/api/test-simple-payment-qr', [InvoiceController::class, 'testSimplePaymentQr']);
Route::get('/api/test-slovenian-payment-qr', [InvoiceController::class, 'testSlovenianPaymentQr']);
Route::get('/api/test-nlb-pay-flik-qr', [InvoiceController::class, 'testNlbPayFlikQr']);
Route::get('/api/test-upn-qr', [InvoiceController::class, 'testUpnQrCode']);
Route::get('/api/test-realistic-upn-qr', [InvoiceController::class, 'testRealisticUpnQr']);
Route::get('/api/test-minimal-upn-qr', [InvoiceController::class, 'testMinimalUpnQr']);
Route::get('/api/test-nlb-compatibility', [InvoiceController::class, 'testNlbCompatibility']);
Route::get('/api/test-official-upn-qr', [InvoiceController::class, 'testOfficialUpnQr']);
Route::get('/api/test-both-formats', [InvoiceController::class, 'testBothFormats']);
Route::get('/api/test-upn-variations', [InvoiceController::class, 'testUpnVariations']);
Route::get('/api/test-valid-slovenian-upn', [InvoiceController::class, 'testValidSlovenianUpn']);
Route::get('/api/test-real-upn-examples', [InvoiceController::class, 'testRealUpnExamples']);
Route::get('/api/test-real-ibans', [InvoiceController::class, 'testRealIbans']);
Route::get('/api/test-official-upn-format', [InvoiceController::class, 'testOfficialUpnFormat']);