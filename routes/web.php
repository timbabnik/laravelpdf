<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\InvoiceController;

Route::get('/', function () {
    return view('chat');
});



Route::get('/pdf', function () {
    return view('invoice-extractor');
});



// Chat API route
Route::post('/api/chat', [ChatController::class, 'sendMessage']);

// Invoice extraction API route
Route::post('/api/extract-invoice', [InvoiceController::class, 'extractInvoiceData']);

// Save invoice data API route
Route::post('/api/save-invoice', [InvoiceController::class, 'saveInvoiceData']);