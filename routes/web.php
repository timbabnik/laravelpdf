<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\InvoiceController;
use SimpleSoftwareIO\QrCode\Facades\QrCode;




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

// Simple QR code generator for testing
Route::post('/api/generate-qr', function(Request $request) {
    try {
        $text = $request->input('text');
        if (!$text) {
            return response()->json(['success' => false, 'error' => 'Text is required']);
        }
        
        $qrCode = QrCode::format('svg')
            ->size(300)
            ->margin(2)
            ->generate($text);
            
        return response()->json([
            'success' => true,
            'qr_code' => base64_encode($qrCode)
        ]);
    } catch (\Exception $e) {
        return response()->json(['success' => false, 'error' => $e->getMessage()]);
    }
});
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
Route::get('/api/test-upn-field-order', [InvoiceController::class, 'testUpnFieldOrder']);
Route::get('/api/test-amount-position', [InvoiceController::class, 'testAmountPosition']);
Route::get('/api/test-working-variation', [InvoiceController::class, 'testWorkingVariation']);
Route::get('/api/test-confirmed-working', [InvoiceController::class, 'testConfirmedWorking']);
Route::get('/api/test-field-order', [InvoiceController::class, 'testFieldOrder']);
Route::get('/api/test-exact-working-format', [InvoiceController::class, 'testExactWorkingFormat']);
Route::get('/api/test-large-amount', [InvoiceController::class, 'testLargeAmount']);
Route::get('/api/test-real-upn-examples', [InvoiceController::class, 'testRealUpnExamples']);
Route::get('/api/test-real-ibans', [InvoiceController::class, 'testRealIbans']);
Route::get('/api/test-official-upn-format', [InvoiceController::class, 'testOfficialUpnFormat']);