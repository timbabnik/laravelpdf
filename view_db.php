<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== YOUR SQLITE DATABASE CONTENTS ===\n\n";

// Show all tables
echo "Tables in your database:\n";
$tables = DB::select("SELECT name FROM sqlite_master WHERE type='table'");
foreach ($tables as $table) {
    echo "- {$table->name}\n";
}

echo "\n=== INVOICES TABLE ===\n";
$invoices = DB::table('invoices')->get();
if ($invoices->count() > 0) {
    foreach ($invoices as $invoice) {
        echo "ID: {$invoice->id}\n";
        echo "Issuer: " . ($invoice->issuer_name ?? 'N/A') . "\n";
        echo "Total Amount: " . ($invoice->total_amount ?? 'N/A') . "\n";
        echo "Invoice Number: " . ($invoice->invoice_number ?? 'N/A') . "\n";
        echo "Invoice Date: " . ($invoice->invoice_date ?? 'N/A') . "\n";
        echo "Created: {$invoice->created_at}\n";
        echo "---\n";
    }
} else {
    echo "No invoices found.\n";
}

echo "\n=== INVOICE ITEMS TABLE ===\n";
$items = DB::table('invoice_items')->get();
if ($items->count() > 0) {
    foreach ($items as $item) {
        echo "ID: {$item->id}\n";
        echo "Invoice ID: {$item->invoice_id}\n";
        echo "Description: " . ($item->description ?? 'N/A') . "\n";
        echo "Quantity: " . ($item->quantity ?? 'N/A') . "\n";
        echo "Unit Price: " . ($item->unit_price ?? 'N/A') . "\n";
        echo "Total: " . ($item->total ?? 'N/A') . "\n";
        echo "Created: {$item->created_at}\n";
        echo "---\n";
    }
} else {
    echo "No invoice items found.\n";
}

echo "\n=== USERS TABLE ===\n";
$users = DB::table('users')->get();
if ($users->count() > 0) {
    foreach ($users as $user) {
        echo "ID: {$user->id}\n";
        echo "Name: {$user->name}\n";
        echo "Email: {$user->email}\n";
        echo "---\n";
    }
} else {
    echo "No users found.\n";
}

echo "\n=== SUMMARY ===\n";
echo "Total Invoices: " . DB::table('invoices')->count() . "\n";
echo "Total Invoice Items: " . DB::table('invoice_items')->count() . "\n";
echo "Total Users: " . DB::table('users')->count() . "\n";
