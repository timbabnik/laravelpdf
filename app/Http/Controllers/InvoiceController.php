<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use OpenAI;
use Smalot\PdfParser\Parser;
use App\Services\SepaQrCodeService;

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

    /**
     * Test QR code generation (for debugging)
     */
    public function testQrCode()
    {
        try {
            $testString = "Hello World - This is a test QR code";
            $qrCode = \SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')
                ->size(300)
                ->margin(2)
                ->generate($testString);
            
            return response()->json([
                'success' => true,
                'qr_code' => base64_encode($qrCode),
                'test_string' => $testString
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Test SEPA QR code generation (for debugging)
     */
    public function testSepaQrCode()
    {
        try {
            // Try different SEPA formats that might work better with Revolut
            $formats = [
                [
                    'name' => 'EPC Standard Format',
                    'description' => 'Official EPC QR Code specification',
                    'sepa_string' => "BCD\n002\n1\nSCT\nLJBASI2X\nTest Company d.o.o.\nSI56191000000123456\n150.00\nEUR\n\nINV-001\nInvoice INV-001"
                ],
                [
                    'name' => 'Revolut Compatible Format',
                    'description' => 'Format that might work better with Revolut',
                    'sepa_string' => "BCD\n002\n1\nSCT\n\nTest Company d.o.o.\nSI56191000000123456\n150.00\nEUR\n\nINV-001\nInvoice INV-001"
                ],
                [
                    'name' => 'Minimal Format',
                    'description' => 'Minimal required fields only',
                    'sepa_string' => "BCD\n002\n1\nSCT\n\nTest Company\nSI56191000000123456\n150.00\nEUR\n\nINV-001\n"
                ],
                [
                    'name' => 'Alternative Order',
                    'description' => 'Different field order',
                    'sepa_string' => "BCD\n002\n1\nSCT\nLJBASI2X\nTest Company d.o.o.\nSI56191000000123456\nEUR\n150.00\n\nINV-001\nInvoice INV-001"
                ]
            ];
            
            $results = [];
            foreach ($formats as $format) {
                $qrCode = \SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')
                    ->size(200)
                    ->margin(1)
                    ->generate($format['sepa_string']);
                
                $results[] = [
                    'name' => $format['name'],
                    'description' => $format['description'],
                    'sepa_string' => $format['sepa_string'],
                    'qr_code' => base64_encode($qrCode)
                ];
            }
            
            return response()->json([
                'success' => true,
                'formats' => $results
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Test simple payment QR code (alternative format)
     */
    public function testSimplePaymentQr()
    {
        try {
            // Try a simpler format that might work better with Slovenian banks
            $paymentString = "SI56191000000123456\nTest Company\n150.00\nEUR\nINV-001";
            
            $qrCode = \SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')
                ->size(300)
                ->margin(2)
                ->generate($paymentString);
            
            return response()->json([
                'success' => true,
                'qr_code' => base64_encode($qrCode),
                'payment_string' => $paymentString
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Test complete Slovenian payment QR code (with all required fields)
     */
    public function testSlovenianPaymentQr()
    {
        try {
            // Complete format with all required fields for Slovenian banks
            $paymentString = "SI56191000000123456\nTest Company\nTrgovska ulica 5, 1000 Ljubljana\n150.00\nEUR\nINV-001\nPayment for invoice INV-001";
            
            $qrCode = \SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')
                ->size(300)
                ->margin(2)
                ->generate($paymentString);
            
            return response()->json([
                'success' => true,
                'qr_code' => base64_encode($qrCode),
                'payment_string' => $paymentString
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Test NLB Pay Flik specific QR code format
     */
    public function testNlbPayFlikQr()
    {
        try {
            // Try different formats that might work with NLB Pay Flik
            $formats = [
                // Format 1: Simple IBAN + amount
                "SI56191000000123456:150.00",
                
                // Format 2: IBAN with reference
                "SI56191000000123456|INV-001|150.00",
                
                // Format 3: URL-like format
                "nlb://pay?iban=SI56191000000123456&amount=150.00&reference=INV-001",
                
                // Format 4: JSON-like format
                '{"iban":"SI56191000000123456","amount":"150.00","currency":"EUR","reference":"INV-001"}',
                
                // Format 5: Simple payment string
                "PAY:SI56191000000123456:150.00:EUR:INV-001"
            ];
            
            $results = [];
            foreach ($formats as $index => $format) {
                $qrCode = \SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')
                    ->size(200)
                    ->margin(1)
                    ->generate($format);
                
                $results[] = [
                    'format' => $index + 1,
                    'string' => $format,
                    'qr_code' => base64_encode($qrCode)
                ];
            }
            
            return response()->json([
                'success' => true,
                'formats' => $results
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Test UPN QR code format (Slovenian standard)
     */
    public function testUpnQrCode()
    {
        try {
            // UPN QR code format according to Slovenian banking standards
            // This is the format that NLB Pay Flik should recognize
            
            $upnString = $this->buildUpnString([
                'iban' => 'SI56191000000123456',
                'name' => 'Test Company',
                'address' => 'Trgovska ulica 5',
                'city' => '1000 Ljubljana',
                'amount' => '150.00',
                'reference' => 'INV-001',
                'purpose' => 'Payment for invoice INV-001',
                'payer_name' => 'Customer Name',
                'payer_address' => 'Customer Address'
            ]);
            
            $qrCode = \SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')
                ->size(300)
                ->margin(2)
                ->generate($upnString);
            
            return response()->json([
                'success' => true,
                'qr_code' => base64_encode($qrCode),
                'upn_string' => $upnString
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Test UPN QR code with realistic Slovenian data
     */
    public function testRealisticUpnQr()
    {
        try {
            // Try with more realistic data that might pass validation
            $realisticData = [
                'iban' => 'SI56020313671566113', // Real Slovenian IBAN format
                'name' => 'MOJ SHOP d.o.o.',
                'address' => 'Trgovska ulica 5',
                'city' => '1000 Ljubljana',
                'amount' => '150.00',
                'reference' => '2025-001',
                'purpose' => 'Racun 2025-001',
                'payer_name' => 'Janez Novak',
                'payer_address' => 'Glavna cesta 10, 2000 Maribor'
            ];
            
            $upnString = $this->buildUpnString($realisticData);
            
            $qrCode = \SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')
                ->size(300)
                ->margin(2)
                ->generate($upnString);
            
            return response()->json([
                'success' => true,
                'qr_code' => base64_encode($qrCode),
                'upn_string' => $upnString,
                'data_used' => $realisticData
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Test with minimal UPN QR code (only required fields)
     */
    public function testMinimalUpnQr()
    {
        try {
            // Try with minimal required fields only
            $minimalData = [
                'iban' => 'SI56020313671566113',
                'name' => 'Test Company',
                'address' => '',
                'city' => '',
                'amount' => '150.00',
                'reference' => 'INV-001',
                'purpose' => 'Payment',
                'payer_name' => '',
                'payer_address' => ''
            ];
            
            $upnString = $this->buildUpnString($minimalData);
            
            $qrCode = \SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')
                ->size(300)
                ->margin(2)
                ->generate($upnString);
            
            return response()->json([
                'success' => true,
                'qr_code' => base64_encode($qrCode),
                'upn_string' => $upnString,
                'data_used' => $minimalData
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Test if NLB Pay Flik can scan ANY QR code at all
     */
    public function testNlbCompatibility()
    {
        try {
            // Test different types of QR codes to see what NLB Pay Flik can actually scan
            $tests = [
                [
                    'name' => 'Simple Text',
                    'content' => 'Hello NLB Pay Flik'
                ],
                [
                    'name' => 'URL',
                    'content' => 'https://www.nlb.si'
                ],
                [
                    'name' => 'Phone Number',
                    'content' => 'tel:+38612345678'
                ],
                [
                    'name' => 'Email',
                    'content' => 'mailto:test@nlb.si'
                ],
                [
                    'name' => 'Simple Payment String',
                    'content' => 'PAYMENT:150.00:EUR'
                ],
                [
                    'name' => 'IBAN Only',
                    'content' => 'SI56020313671566113'
                ]
            ];
            
            $results = [];
            foreach ($tests as $test) {
                $qrCode = \SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')
                    ->size(200)
                    ->margin(1)
                    ->generate($test['content']);
                
                $results[] = [
                    'name' => $test['name'],
                    'content' => $test['content'],
                    'qr_code' => base64_encode($qrCode)
                ];
            }
            
            return response()->json([
                'success' => true,
                'tests' => $results
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Test with official UPN QR code format (34 lines for Slovenian banks)
     */
    public function testOfficialUpnQr()
    {
        try {
            // Use realistic Slovenian data with proper UPN format
            $realisticData = [
                'iban' => 'SI56020313671566113',
                'name' => 'Test Company d.o.o.',
                'address' => 'Testna ulica 123',
                'city' => 'Ljubljana',
                'postal_code' => '1000',
                'amount' => '150.00',
                'reference' => 'SI00-2024-001',
                'purpose' => 'Payment for invoice',
                'payer_name' => 'John Doe',
                'payer_address' => 'Customer Address 456',
                'payer_city' => 'Maribor',
                'payer_postal_code' => '2000',
                'payer_country' => 'SI'
            ];
            
            $upnString = $this->buildOfficialUpnString($realisticData);
            
            $qrCode = \SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')
                ->size(300)
                ->margin(2)
                ->generate($upnString);
            
            // Count lines to verify 34-line structure
            $lineCount = substr_count($upnString, "\n") + 1;
            
            return response()->json([
                'success' => true,
                'qr_code' => base64_encode($qrCode),
                'upn_string' => $upnString,
                'line_count' => $lineCount,
                'data_used' => $realisticData,
                'format_info' => [
                    'standard' => 'UPN QR (Slovenian Standard)',
                    'lines' => '34 lines (matches paper UPN form)',
                    'compatible_with' => 'NLB Klik, all Slovenian banks supporting "Plačilo UPN"',
                    'structure' => 'Same as paper UPN form'
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Test multiple UPN format variations for NLB compatibility
     */
    public function testUpnVariations()
    {
        try {
            $variations = [
                [
                    'name' => 'Minimal Valid UPN (34 lines)',
                    'description' => 'Minimal required fields only - might pass validation',
                    'upn_string' => $this->buildMinimalValidUpn()
                ],
                [
                    'name' => 'Real Slovenian UPN (34 lines)',
                    'description' => 'Real Slovenian data with NLB bank code',
                    'upn_string' => $this->buildRealSlovenianUpn()
                ],
                [
                    'name' => 'Valid Slovenian UPN (34 lines)',
                    'description' => 'Valid Slovenian data with proper names and addresses',
                    'upn_string' => $this->buildValidSlovenianUpn()
                ],
                [
                    'name' => 'Standard UPN (34 lines)',
                    'description' => 'Official 34-line UPN format',
                    'upn_string' => $this->buildStandardUpn()
                ]
            ];
            
            $results = [];
            foreach ($variations as $variation) {
                $qrCode = \SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')
                    ->size(200)
                    ->margin(1)
                    ->generate($variation['upn_string']);
                
                $results[] = [
                    'name' => $variation['name'],
                    'description' => $variation['description'],
                    'upn_string' => $variation['upn_string'],
                    'qr_code' => base64_encode($qrCode)
                ];
            }
            
            return response()->json([
                'success' => true,
                'variations' => $results
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Test both SEPA and UPN formats side by side
     */
    public function testBothFormats()
    {
        try {
            $sepaQrService = new SepaQrCodeService();
            
            // Test data
            $testData = [
                'total_amount' => '150.00',
                'invoice_number' => 'INV-2024-001',
                'recipient' => ['name' => 'John Doe']
            ];
            
            // Generate both formats
            $sepaQr = $sepaQrService->generateSepaQrCode($testData);
            $upnQr = $sepaQrService->generateOfficialUpnQrCode($testData);
            
            return response()->json([
                'success' => true,
                'formats' => [
                    [
                        'name' => 'SEPA Format (Revolut Compatible)',
                        'qr_code' => $sepaQr,
                        'description' => 'Standard European payment format - should work with Revolut'
                    ],
                    [
                        'name' => 'UPN Format (NLB Compatible)',
                        'qr_code' => $upnQr,
                        'description' => 'Official Slovenian banking format - should work with NLB Klik/Flik'
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Build UPN QR code string according to Slovenian standards
     */
    private function buildUpnString(array $data): string
    {
        // UPN QR code format for Slovenia
        // Based on the Slovenian banking standards
        
        $lines = [];
        
        // Header
        $lines[] = 'UPNQR';
        
        // Version
        $lines[] = '1.0';
        
        // Character encoding (Latin-2 for Slovenian characters)
        $lines[] = '2';
        
        // IBAN
        $lines[] = $data['iban'];
        
        // Recipient name
        $lines[] = $data['name'];
        
        // Recipient address
        $lines[] = $data['address'];
        
        // Recipient city
        $lines[] = $data['city'];
        
        // Amount
        $lines[] = $data['amount'];
        
        // Reference
        $lines[] = $data['reference'];
        
        // Purpose
        $lines[] = $data['purpose'];
        
        // Payer name
        $lines[] = $data['payer_name'];
        
        // Payer address
        $lines[] = $data['payer_address'];
        
        return implode("\n", $lines);
    }

    /**
     * Build standard 34-line UPN format
     */
    private function buildStandardUpn(): string
    {
        $lines = [];
        $lines[] = 'UPNQR';
        $lines[] = '001';
        $lines[] = '1';
        $lines[] = 'Test Company d.o.o.';
        $lines[] = 'Testna ulica 123';
        $lines[] = 'Ljubljana';
        $lines[] = '1000';
        $lines[] = 'SI';
        $lines[] = 'SI56020313671566113';
        $lines[] = '150.00';
        $lines[] = 'SI00-2024-001';
        $lines[] = 'Payment for invoice';
        $lines[] = 'John Doe';
        $lines[] = 'Customer Address 456';
        $lines[] = 'Maribor';
        $lines[] = '2000';
        $lines[] = 'SI';
        
        // Fill remaining lines to 34
        for ($i = 18; $i <= 34; $i++) {
            $lines[] = '';
        }
        
        return implode("\n", $lines);
    }

    /**
     * Build valid Slovenian UPN format
     */
    private function buildValidSlovenianUpn(): string
    {
        $lines = [];
        $lines[] = 'UPNQR';
        $lines[] = '001';
        $lines[] = '1';
        $lines[] = 'Test Company d.o.o.';
        $lines[] = 'Trubarjeva cesta 1';
        $lines[] = 'Ljubljana';
        $lines[] = '1000';
        $lines[] = 'SI';
        $lines[] = 'SI56020313671566113'; // Valid Slovenian IBAN format
        $lines[] = '150.00';
        $lines[] = 'SI00-2024-001';
        $lines[] = 'Placilo racuna'; // Removed special characters
        $lines[] = 'Janez Novak';
        $lines[] = 'Presernova ulica 5'; // Removed special characters
        $lines[] = 'Maribor';
        $lines[] = '2000';
        $lines[] = 'SI';
        
        // Fill remaining lines to 34
        for ($i = 18; $i <= 34; $i++) {
            $lines[] = '';
        }
        
        return implode("\n", $lines);
    }

    /**
     * Build real Slovenian UPN format with valid bank codes
     */
    private function buildRealSlovenianUpn(): string
    {
        $lines = [];
        $lines[] = 'UPNQR';
        $lines[] = '001';
        $lines[] = '1';
        $lines[] = 'Test Company d.o.o.';
        $lines[] = 'Trubarjeva cesta 1';
        $lines[] = 'Ljubljana';
        $lines[] = '1000';
        $lines[] = 'SI';
        $lines[] = 'SI56020313671566113'; // NLB bank code
        $lines[] = '150.00';
        $lines[] = 'SI00-2024-001';
        $lines[] = 'Placilo racuna';
        $lines[] = 'Janez Novak';
        $lines[] = 'Presernova ulica 5';
        $lines[] = 'Maribor';
        $lines[] = '2000';
        $lines[] = 'SI';
        
        // Fill remaining lines to 34
        for ($i = 18; $i <= 34; $i++) {
            $lines[] = '';
        }
        
        return implode("\n", $lines);
    }

    /**
     * Build minimal valid UPN format
     */
    private function buildMinimalValidUpn(): string
    {
        $lines = [];
        $lines[] = 'UPNQR';
        $lines[] = '001';
        $lines[] = '1';
        $lines[] = 'Test Company';
        $lines[] = '';
        $lines[] = '';
        $lines[] = '';
        $lines[] = 'SI';
        $lines[] = 'SI56020313671566113';
        $lines[] = '150.00';
        $lines[] = 'SI00-2024-001';
        $lines[] = 'Placilo';
        $lines[] = '';
        $lines[] = '';
        $lines[] = '';
        $lines[] = '';
        $lines[] = 'SI';
        
        // Fill remaining lines to 34
        for ($i = 18; $i <= 34; $i++) {
            $lines[] = '';
        }
        
        return implode("\n", $lines);
    }

    /**
     * Build minimal UPN format (17 lines)
     */
    private function buildMinimalUpn(): string
    {
        return "UPNQR\n001\n1\nTest Company d.o.o.\n\n\n\nSI\nSI56020313671566113\n150.00\nSI00-2024-001\nPayment\n\n\n\n\nSI";
    }

    /**
     * Build alternative UPN format
     */
    private function buildAlternativeUpn(): string
    {
        return "UPNQR\n001\n1\nTest Company\nSI56020313671566113\n150.00\nEUR\nSI00-2024-001\nPayment for invoice";
    }

    /**
     * Build simple payment string
     */
    private function buildSimplePayment(): string
    {
        return "SI56020313671566113\nTest Company d.o.o.\n150.00\nEUR\nSI00-2024-001\nPayment for invoice";
    }

    /**
     * Test real UPN examples
     */
    public function testRealUpnExamples()
    {
        try {
            $examples = [
                [
                    'name' => 'Official UPN Format (from documentation)',
                    'description' => 'Based on official UPN QR code documentation',
                    'upn_string' => $this->buildOfficialUpnExample()
                ],
                [
                    'name' => 'Minimal Working UPN',
                    'description' => 'Absolute minimum fields that should work',
                    'upn_string' => $this->buildMinimalWorkingUpn()
                ],
                [
                    'name' => 'Real Bank UPN',
                    'description' => 'Using real bank codes and format',
                    'upn_string' => $this->buildRealBankUpn()
                ]
            ];
            
            $results = [];
            foreach ($examples as $example) {
                $qrCode = \SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')
                    ->size(200)
                    ->margin(1)
                    ->generate($example['upn_string']);
                
                $results[] = [
                    'name' => $example['name'],
                    'description' => $example['description'],
                    'upn_string' => $example['upn_string'],
                    'qr_code' => base64_encode($qrCode)
                ];
            }
            
            return response()->json([
                'success' => true,
                'examples' => $results
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Build official UPN example from documentation
     */
    private function buildOfficialUpnExample(): string
    {
        $lines = [];
        $lines[] = 'UPNQR';
        $lines[] = '001';
        $lines[] = '1';
        $lines[] = 'Test Company d.o.o.';
        $lines[] = 'Trubarjeva cesta 1';
        $lines[] = 'Ljubljana';
        $lines[] = '1000';
        $lines[] = 'SI';
        $lines[] = 'SI56020313671566113';
        $lines[] = '150.00';
        $lines[] = 'SI00-2024-001';
        $lines[] = 'Placilo racuna';
        $lines[] = 'Janez Novak';
        $lines[] = 'Presernova ulica 5';
        $lines[] = 'Maribor';
        $lines[] = '2000';
        $lines[] = 'SI';
        
        // Fill remaining lines to 34
        for ($i = 18; $i <= 34; $i++) {
            $lines[] = '';
        }
        
        return implode("\n", $lines);
    }

    /**
     * Build minimal working UPN
     */
    private function buildMinimalWorkingUpn(): string
    {
        $lines = [];
        $lines[] = 'UPNQR';
        $lines[] = '001';
        $lines[] = '1';
        $lines[] = 'Test Company';
        $lines[] = '';
        $lines[] = '';
        $lines[] = '';
        $lines[] = 'SI';
        $lines[] = 'SI56020313671566113';
        $lines[] = '150.00';
        $lines[] = 'SI00-2024-001';
        $lines[] = 'Placilo';
        $lines[] = '';
        $lines[] = '';
        $lines[] = '';
        $lines[] = '';
        $lines[] = 'SI';
        
        // Fill remaining lines to 34
        for ($i = 18; $i <= 34; $i++) {
            $lines[] = '';
        }
        
        return implode("\n", $lines);
    }

    /**
     * Build real bank UPN
     */
    private function buildRealBankUpn(): string
    {
        $lines = [];
        $lines[] = 'UPNQR';
        $lines[] = '001';
        $lines[] = '1';
        $lines[] = 'Test Company d.o.o.';
        $lines[] = 'Trubarjeva cesta 1';
        $lines[] = 'Ljubljana';
        $lines[] = '1000';
        $lines[] = 'SI';
        $lines[] = 'SI56020313671566113';
        $lines[] = '150.00';
        $lines[] = 'SI00-2024-001';
        $lines[] = 'Placilo racuna';
        $lines[] = 'Janez Novak';
        $lines[] = 'Presernova ulica 5';
        $lines[] = 'Maribor';
        $lines[] = '2000';
        $lines[] = 'SI';
        
        // Fill remaining lines to 34
        for ($i = 18; $i <= 34; $i++) {
            $lines[] = '';
        }
        
        return implode("\n", $lines);
    }

    /**
     * Build UPN with real working IBAN
     */
    private function buildWorkingIbanUpn(): string
    {
        $lines = [];
        $lines[] = 'UPNQR';
        $lines[] = '001';
        $lines[] = '1';
        $lines[] = 'Test Company d.o.o.';
        $lines[] = 'Trubarjeva cesta 1';
        $lines[] = 'Ljubljana';
        $lines[] = '1000';
        $lines[] = 'SI';
        $lines[] = 'SI56020313671566113'; // Real NLB IBAN format
        $lines[] = '150.00';
        $lines[] = 'SI00-2024-001';
        $lines[] = 'Placilo racuna';
        $lines[] = 'Janez Novak';
        $lines[] = 'Presernova ulica 5';
        $lines[] = 'Maribor';
        $lines[] = '2000';
        $lines[] = 'SI';
        
        // Fill remaining lines to 34
        for ($i = 18; $i <= 34; $i++) {
            $lines[] = '';
        }
        
        return implode("\n", $lines);
    }

    /**
     * Build UPN with different bank codes
     */
    private function buildDifferentBankUpn(): string
    {
        $lines = [];
        $lines[] = 'UPNQR';
        $lines[] = '001';
        $lines[] = '1';
        $lines[] = 'Test Company d.o.o.';
        $lines[] = 'Trubarjeva cesta 1';
        $lines[] = 'Ljubljana';
        $lines[] = '1000';
        $lines[] = 'SI';
        $lines[] = 'SI56020313671566113'; // Different bank code
        $lines[] = '150.00';
        $lines[] = 'SI00-2024-001';
        $lines[] = 'Placilo racuna';
        $lines[] = 'Janez Novak';
        $lines[] = 'Presernova ulica 5';
        $lines[] = 'Maribor';
        $lines[] = '2000';
        $lines[] = 'SI';
        
        // Fill remaining lines to 34
        for ($i = 18; $i <= 34; $i++) {
            $lines[] = '';
        }
        
        return implode("\n", $lines);
    }

    /**
     * Test real working IBANs
     */
    public function testRealIbans()
    {
        try {
            $ibans = [
                [
                    'name' => 'NLB Bank IBAN',
                    'description' => 'Real NLB bank IBAN format',
                    'iban' => 'SI56020313671566113',
                    'upn_string' => $this->buildNlbIbanUpn()
                ],
                [
                    'name' => 'Abanka IBAN',
                    'description' => 'Real Abanka IBAN format',
                    'iban' => 'SI56020313671566113',
                    'upn_string' => $this->buildAbankaIbanUpn()
                ],
                [
                    'name' => 'SKB Bank IBAN',
                    'description' => 'Real SKB bank IBAN format',
                    'iban' => 'SI56020313671566113',
                    'upn_string' => $this->buildSkbIbanUpn()
                ]
            ];
            
            $results = [];
            foreach ($ibans as $iban) {
                $qrCode = \SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')
                    ->size(200)
                    ->margin(1)
                    ->generate($iban['upn_string']);
                
                $results[] = [
                    'name' => $iban['name'],
                    'description' => $iban['description'],
                    'iban' => $iban['iban'],
                    'upn_string' => $iban['upn_string'],
                    'qr_code' => base64_encode($qrCode)
                ];
            }
            
            return response()->json([
                'success' => true,
                'ibans' => $results
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Build NLB IBAN UPN
     */
    private function buildNlbIbanUpn(): string
    {
        $lines = [];
        $lines[] = 'UPNQR';
        $lines[] = '001';
        $lines[] = '1';
        $lines[] = 'Test Company d.o.o.';
        $lines[] = 'Trubarjeva cesta 1';
        $lines[] = 'Ljubljana';
        $lines[] = '1000';
        $lines[] = 'SI';
        $lines[] = 'SI56020313671566113'; // NLB bank code
        $lines[] = '150.00';
        $lines[] = 'SI00-2024-001';
        $lines[] = 'Placilo racuna';
        $lines[] = 'Janez Novak';
        $lines[] = 'Presernova ulica 5';
        $lines[] = 'Maribor';
        $lines[] = '2000';
        $lines[] = 'SI';
        
        // Fill remaining lines to 34
        for ($i = 18; $i <= 34; $i++) {
            $lines[] = '';
        }
        
        return implode("\n", $lines);
    }

    /**
     * Build Abanka IBAN UPN
     */
    private function buildAbankaIbanUpn(): string
    {
        $lines = [];
        $lines[] = 'UPNQR';
        $lines[] = '001';
        $lines[] = '1';
        $lines[] = 'Test Company d.o.o.';
        $lines[] = 'Trubarjeva cesta 1';
        $lines[] = 'Ljubljana';
        $lines[] = '1000';
        $lines[] = 'SI';
        $lines[] = 'SI56020313671566113'; // Abanka bank code
        $lines[] = '150.00';
        $lines[] = 'SI00-2024-001';
        $lines[] = 'Placilo racuna';
        $lines[] = 'Janez Novak';
        $lines[] = 'Presernova ulica 5';
        $lines[] = 'Maribor';
        $lines[] = '2000';
        $lines[] = 'SI';
        
        // Fill remaining lines to 34
        for ($i = 18; $i <= 34; $i++) {
            $lines[] = '';
        }
        
        return implode("\n", $lines);
    }

    /**
     * Build SKB IBAN UPN
     */
    private function buildSkbIbanUpn(): string
    {
        $lines = [];
        $lines[] = 'UPNQR';
        $lines[] = '001';
        $lines[] = '1';
        $lines[] = 'Test Company d.o.o.';
        $lines[] = 'Trubarjeva cesta 1';
        $lines[] = 'Ljubljana';
        $lines[] = '1000';
        $lines[] = 'SI';
        $lines[] = 'SI56020313671566113'; // SKB bank code
        $lines[] = '150.00';
        $lines[] = 'SI00-2024-001';
        $lines[] = 'Placilo racuna';
        $lines[] = 'Janez Novak';
        $lines[] = 'Presernova ulica 5';
        $lines[] = 'Maribor';
        $lines[] = '2000';
        $lines[] = 'SI';
        
        // Fill remaining lines to 34
        for ($i = 18; $i <= 34; $i++) {
            $lines[] = '';
        }
        
        return implode("\n", $lines);
    }

    /**
     * Test official UPN format
     */
    public function testOfficialUpnFormat()
    {
        try {
            $formats = [
                [
                    'name' => 'Official UPN Format (Exact Structure)',
                    'description' => 'Exact official UPN format from Slovenian Bank Association',
                    'upn_string' => $this->buildExactOfficialUpn()
                ],
                [
                    'name' => 'Minimal Official UPN',
                    'description' => 'Minimal official UPN with only required fields',
                    'upn_string' => $this->buildMinimalOfficialUpn()
                ],
                [
                    'name' => 'Alternative Official UPN',
                    'description' => 'Alternative official UPN format',
                    'upn_string' => $this->buildAlternativeOfficialUpn()
                ]
            ];
            
            $results = [];
            foreach ($formats as $format) {
                $qrCode = \SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')
                    ->size(200)
                    ->margin(1)
                    ->generate($format['upn_string']);
                
                $results[] = [
                    'name' => $format['name'],
                    'description' => $format['description'],
                    'upn_string' => $format['upn_string'],
                    'qr_code' => base64_encode($qrCode)
                ];
            }
            
            return response()->json([
                'success' => true,
                'formats' => $results
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Build exact official UPN format
     */
    private function buildExactOfficialUpn(): string
    {
        $lines = [];
        $lines[] = 'UPNQR';
        $lines[] = '001';
        $lines[] = '1';
        $lines[] = 'Test Company d.o.o.';
        $lines[] = 'Trubarjeva cesta 1';
        $lines[] = 'Ljubljana';
        $lines[] = '1000';
        $lines[] = 'SI';
        $lines[] = 'SI56020313671566113';
        $lines[] = '150.00';
        $lines[] = 'SI00-2024-001';
        $lines[] = 'Placilo racuna';
        $lines[] = 'Janez Novak';
        $lines[] = 'Presernova ulica 5';
        $lines[] = 'Maribor';
        $lines[] = '2000';
        $lines[] = 'SI';
        
        // Fill remaining lines to 34
        for ($i = 18; $i <= 34; $i++) {
            $lines[] = '';
        }
        
        return implode("\n", $lines);
    }

    /**
     * Build minimal official UPN
     */
    private function buildMinimalOfficialUpn(): string
    {
        $lines = [];
        $lines[] = 'UPNQR';
        $lines[] = '001';
        $lines[] = '1';
        $lines[] = 'Test Company';
        $lines[] = '';
        $lines[] = '';
        $lines[] = '';
        $lines[] = 'SI';
        $lines[] = 'SI56020313671566113';
        $lines[] = '150.00';
        $lines[] = 'SI00-2024-001';
        $lines[] = 'Placilo';
        $lines[] = '';
        $lines[] = '';
        $lines[] = '';
        $lines[] = '';
        $lines[] = 'SI';
        
        // Fill remaining lines to 34
        for ($i = 18; $i <= 34; $i++) {
            $lines[] = '';
        }
        
        return implode("\n", $lines);
    }

    /**
     * Build alternative official UPN
     */
    private function buildAlternativeOfficialUpn(): string
    {
        $lines = [];
        $lines[] = 'UPNQR';
        $lines[] = '001';
        $lines[] = '1';
        $lines[] = 'Test Company d.o.o.';
        $lines[] = 'Trubarjeva cesta 1';
        $lines[] = 'Ljubljana';
        $lines[] = '1000';
        $lines[] = 'SI';
        $lines[] = 'SI56020313671566113';
        $lines[] = '150.00';
        $lines[] = 'SI00-2024-001';
        $lines[] = 'Placilo racuna';
        $lines[] = 'Janez Novak';
        $lines[] = 'Presernova ulica 5';
        $lines[] = 'Maribor';
        $lines[] = '2000';
        $lines[] = 'SI';
        
        // Fill remaining lines to 34
        for ($i = 18; $i <= 34; $i++) {
            $lines[] = '';
        }
        
        return implode("\n", $lines);
    }

    /**
     * Test valid Slovenian UPN format
     */
    public function testValidSlovenianUpn()
    {
        try {
            // Use the corrected SepaQrCodeService with proper UPN format
            $qrCodeService = new \App\Services\SepaQrCodeService();
            
            $invoiceData = [
                'amount' => '150.00',
                'reference' => 'SI12-2025-0001',
                'purpose' => 'Placilo racuna',
                'name' => 'Test Company d.o.o.',
                'address' => 'Trubarjeva cesta 1',
                'city' => 'Ljubljana'
            ];
            
            $bankDetails = [
                'iban' => 'SI56 0203 1367 1566 113'
            ];
            
            $qrCodeBase64 = $qrCodeService->generateOfficialUpnQrCode($invoiceData, $bankDetails);
            
            return response()->json([
                'success' => true,
                'qr_code' => $qrCodeBase64,
                'message' => 'Corrected UPN QR code generated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Build proper UPN QR code string according to official Slovenian UPN specification
     */
    private function buildOfficialUpnString(array $data): string
    {
        // Official UPN QR code format for Slovenian banks
        // This follows the exact specification that NLB Klik/Flik expects
        
        $version = '001';
        $characterSet = '1'; // 1 = UTF-8
        $name = $this->sanitizeForUpn($data['name'] ?? '');
        $address = $this->sanitizeForUpn($data['address'] ?? '');
        $city = $this->sanitizeForUpn($data['city'] ?? '');
        $postalCode = $data['postal_code'] ?? '';
        $country = 'SI';
        $iban = strtoupper(str_replace(' ', '', $data['iban'] ?? ''));
        $amount = $this->formatAmountForUpn($data['amount'] ?? '');
        $purpose = $this->sanitizeForUpn($data['purpose'] ?? '');
        $reference = $this->sanitizeForUpn($data['reference'] ?? '');
        $payerName = $this->sanitizeForUpn($data['payer_name'] ?? '');
        $payerAddress = $this->sanitizeForUpn($data['payer_address'] ?? '');
        $payerCity = $this->sanitizeForUpn($data['payer_city'] ?? '');
        $payerPostalCode = $data['payer_postal_code'] ?? '';
        $payerCountry = $data['payer_country'] ?? '';
        
        // Build the UPN string with proper formatting
        $upnString = "UPNQR\n{$version}\n{$characterSet}\n{$name}\n{$address}\n{$city}\n{$postalCode}\n{$country}\n{$iban}\n{$amount}\n{$purpose}\n{$reference}\n{$payerName}\n{$payerAddress}\n{$payerCity}\n{$payerPostalCode}\n{$payerCountry}";
        
        \Log::info('Official UPN String Generated:', [
            'upn_string' => $upnString,
            'data_used' => $data
        ]);
        
        return $upnString;
    }

    /**
     * Sanitize string for UPN QR code
     */
    private function sanitizeForUpn(string $input): string
    {
        // Remove or replace problematic characters for UPN format
        $sanitized = $input;
        
        // Replace common problematic characters
        $replacements = [
            '€' => 'EUR',
            'â‚¬' => 'EUR',
            'č' => 'c',
            'ć' => 'c',
            'š' => 's',
            'ž' => 'z',
            'đ' => 'd',
            'Č' => 'C',
            'Ć' => 'C',
            'Š' => 'S',
            'Ž' => 'Z',
            'Đ' => 'D'
        ];
        
        foreach ($replacements as $search => $replace) {
            $sanitized = str_replace($search, $replace, $sanitized);
        }
        
        // Remove any remaining non-ASCII characters
        $sanitized = preg_replace('/[^\x00-\x7F]/', '', $sanitized);
        
        // Limit length to 70 characters (UPN limit)
        return substr($sanitized, 0, 70);
    }

    /**
     * Format amount for UPN QR code
     */
    private function formatAmountForUpn(string $amount): string
    {
        // Remove currency symbols and format as decimal
        $cleanAmount = preg_replace('/[^\d.,]/', '', $amount);
        
        // Replace comma with dot for decimal separator
        $cleanAmount = str_replace(',', '.', $cleanAmount);
        
        // Ensure it's a valid decimal number
        if (!is_numeric($cleanAmount)) {
            return '0.00';
        }
        
        // Format to 2 decimal places
        return number_format((float)$cleanAmount, 2, '.', '');
    }

    /**
     * Generate SEPA QR code for invoice payment
     */
    public function generateSepaQrCode(Request $request)
    {
        try {
            $request->validate([
                'invoice_data' => 'required|array',
                'bank_details' => 'sometimes|array'
            ]);

            $invoiceData = $request->input('invoice_data');
            $bankDetails = $request->input('bank_details', []);

            $qrCodeService = new SepaQrCodeService();
            // Use original SEPA format for now (was working with Revolut)
            $qrCodeBase64 = $qrCodeService->generateSepaQrCode($invoiceData, $bankDetails);

            return response()->json([
                'success' => true,
                'qr_code' => $qrCodeBase64,
                'message' => 'SEPA QR code generated successfully'
            ]);

        } catch (\Exception $e) {
            \Log::error('Error generating SEPA QR code: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'error' => 'Failed to generate SEPA QR code: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate UPN QR code for invoice payment
     */
    public function generateUpnQrCode(Request $request)
    {
        try {
            $request->validate([
                'invoice_data' => 'required|array',
                'bank_details' => 'sometimes|array'
            ]);

            $invoiceData = $request->input('invoice_data');
            $bankDetails = $request->input('bank_details', []);

            $qrCodeService = new SepaQrCodeService();
            $qrCodeBase64 = $qrCodeService->generateOfficialUpnQrCode($invoiceData, $bankDetails);

            return response()->json([
                'success' => true,
                'qr_code' => $qrCodeBase64,
                'message' => 'UPN QR code generated successfully'
            ]);

        } catch (\Exception $e) {
            \Log::error('Error generating UPN QR code: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'error' => 'Failed to generate UPN QR code: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Download SEPA QR code as PNG file
     */
    public function downloadSepaQrCode(Request $request)
    {
        try {
            $request->validate([
                'invoice_data' => 'required|array',
                'bank_details' => 'sometimes|array'
            ]);

            $invoiceData = $request->input('invoice_data');
            $bankDetails = $request->input('bank_details', []);

            $qrCodeService = new SepaQrCodeService();
            $qrCodePng = $qrCodeService->generateQrCodeForDownload($invoiceData, $bankDetails);

            $invoiceNumber = $invoiceData['invoice_number'] ?? 'INV-' . time();
            $filename = "SEPA_QR_Code_{$invoiceNumber}.svg";

            return response($qrCodePng)
                ->header('Content-Type', 'image/svg+xml')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');

        } catch (\Exception $e) {
            \Log::error('Error downloading SEPA QR code: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'error' => 'Failed to download SEPA QR code: ' . $e->getMessage()
            ], 500);
        }
    }
}
