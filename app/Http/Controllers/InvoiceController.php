<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use OpenAI;
use Smalot\PdfParser\Parser;

class InvoiceController extends Controller
{
    public function extractInvoiceData(Request $request)
    {
        \Log::info('Invoice extraction request received');
        
        $request->validate([
            'pdf' => 'required|file|mimes:pdf|max:10240' // Max 10MB
        ]);
        
        \Log::info('Request validation passed');

        // Get the uploaded PDF file
        $pdfFile = $request->file('pdf');
        \Log::info('PDF file received: ' . $pdfFile->getClientOriginalName());
        
        // Test PDF parser
        \Log::info('Testing PDF parser...');
        try {
            $parser = new Parser();
            \Log::info('Parser created successfully');
            
            $pdf = $parser->parseFile($pdfFile->getPathname());
            \Log::info('PDF parsed successfully');
            
            $pdfText = $pdf->getText();
            \Log::info('Text extracted successfully. Length: ' . strlen($pdfText));
            
            // Now send to OpenAI for structured extraction
            \Log::info('Sending to OpenAI for structured extraction...');
            $client = OpenAI::client(config('services.openai.api_key'));
            
            $response = $client->chat()->create([
                'model' => 'gpt-4o-2024-08-06',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are an expert invoice data extractor. Extract all relevant information from the provided invoice text and return it in the exact JSON structure specified. Be thorough and accurate in your extraction.'
                    ],
                    [
                        'role' => 'user',
                        'content' => 'Please extract all invoice data from this invoice text and return it in the following JSON structure:\n\n' . $pdfText
                    ]
                ],
                'response_format' => [
                    'type' => 'json_schema',
                    'json_schema' => [
                        'name' => 'invoice_data',
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'issuer' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'name' => ['type' => 'string'],
                                        'address' => ['type' => 'string'],
                                        'email' => ['type' => 'string'],
                                        'phone' => ['type' => 'string'],
                                        'tax_id' => ['type' => 'string']
                                    ]
                                ],
                                'recipient' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'name' => ['type' => 'string'],
                                        'address' => ['type' => 'string'],
                                        'email' => ['type' => 'string'],
                                        'phone' => ['type' => 'string']
                                    ]
                                ],
                                'invoice_number' => ['type' => 'string'],
                                'invoice_date' => ['type' => 'string'],
                                'due_date' => ['type' => 'string'],
                                'line_items' => [
                                    'type' => 'array',
                                    'items' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'description' => ['type' => 'string'],
                                            'quantity' => ['type' => 'number'],
                                            'unit_price' => ['type' => 'number'],
                                            'total' => ['type' => 'number']
                                        ]
                                    ]
                                ],
                                'subtotal' => ['type' => 'number'],
                                'tax_amount' => ['type' => 'number'],
                                'tax_rate' => ['type' => 'number'],
                                'total_amount' => ['type' => 'number'],
                                'currency' => ['type' => 'string'],
                                'payment_terms' => ['type' => 'string'],
                                'notes' => ['type' => 'string']
                            ],
                            'required' => ['issuer', 'recipient', 'invoice_number', 'invoice_date', 'line_items', 'total_amount']
                        ]
                    ]
                ],
                'max_tokens' => 2000,
                'temperature' => 0.1
            ]);

            \Log::info('OpenAI response received');
            $responseContent = $response->choices[0]->message->content;
            $extractedData = json_decode($responseContent, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Invalid JSON response from OpenAI: ' . json_last_error_msg());
            }

            \Log::info('Data extracted successfully');
            return response()->json([
                'success' => true,
                'data' => $extractedData
            ]);
            
        } catch (\Exception $e) {
            \Log::error('PDF parser error: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'error' => 'PDF parser failed: ' . $e->getMessage(),
                'details' => $e->getTraceAsString()
            ], 500);
        }
    }

    /**
     * Save extracted invoice data to database
     */
    public function saveInvoiceData(Request $request)
    {
        try {
            // Log the incoming request for debugging
            \Log::info('Save invoice request received', [
                'request_data' => $request->all(),
                'content_type' => $request->header('Content-Type')
            ]);

            $request->validate([
                'invoice_data' => 'required|array'
            ]);

            $data = $request->input('invoice_data');
            
            // Extract key information from your specific JSON structure
            $issuerName = $data['issuer']['name'] ?? 'Unknown Vendor';
            $totalAmount = $data['total_amount'] ?? 0;
            $invoiceNumber = $data['invoice_number'] ?? 'INV-' . time();
            $invoiceDate = $data['invoice_date'] ?? now()->format('Y-m-d');
            
            // Save to database using DB facade
            $invoiceId = DB::table('invoices')->insertGetId([
                'issuer_name' => $issuerName,
                'total_amount' => $totalAmount,
                'invoice_number' => $invoiceNumber,
                'invoice_date' => $invoiceDate,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Save line items if they exist
            if (isset($data['line_items']) && is_array($data['line_items'])) {
                foreach ($data['line_items'] as $itemData) {
                    DB::table('invoice_items')->insert([
                        'invoice_id' => $invoiceId,
                        'description' => $itemData['description'] ?? 'No description',
                        'quantity' => $itemData['quantity'] ?? 1,
                        'unit_price' => $itemData['unit_price'] ?? 0,
                        'total' => $itemData['total'] ?? 0,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            \Log::info("Invoice saved to database with ID: {$invoiceId}", [
                'issuer_name' => $issuerName,
                'total_amount' => $totalAmount,
                'invoice_number' => $invoiceNumber
            ]);

            return response()->json([
                'success' => true,
                'invoice_id' => $invoiceId,
                'message' => 'Invoice saved successfully',
                'data' => [
                    'issuer_name' => $issuerName,
                    'total_amount' => $totalAmount,
                    'invoice_number' => $invoiceNumber,
                    'invoice_date' => $invoiceDate
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Error saving invoice: ' . $e->getMessage(), [
                'exception' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Failed to save invoice: ' . $e->getMessage()
            ], 500);
        }
    }
}
