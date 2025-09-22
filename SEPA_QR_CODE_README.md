# SEPA QR Code Feature

This feature adds SEPA QR code generation to your Laravel invoice reader application. Customers can scan the generated QR code with their banking app to automatically fill in payment details for SEPA bank transfers.

## Features

- ✅ Generate SEPA-compliant QR codes from extracted invoice data
- ✅ Display QR code in the invoice summary modal
- ✅ Download QR code as PNG file
- ✅ Configurable bank details
- ✅ Follows EPC QR Code specification

## How It Works

1. **Extract Invoice Data**: Upload a PDF invoice and extract data using OpenAI
2. **Generate QR Code**: Click "Generate QR Code" button in the invoice summary
3. **Display QR Code**: The QR code appears in the modal for easy scanning
4. **Download QR Code**: Click "Download QR Code" to save as PNG file

## Configuration

### Bank Details

Edit `config/sepa.php` or set environment variables:

```env
SEPA_IBAN=SI56 1910 0000 0123 456
SEPA_BIC=LJBASI2X
SEPA_COMPANY_NAME=Your Company Name
SEPA_COMPANY_ADDRESS=Your Company Address
SEPA_COMPANY_CITY=Ljubljana
SEPA_COMPANY_POSTAL_CODE=1000
SEPA_COMPANY_COUNTRY=SI
```

### QR Code Settings

```php
// config/sepa.php
'qr_code' => [
    'size' => 300,           // Display size
    'margin' => 2,           // Display margin
    'download_size' => 400,  // Download size
    'download_margin' => 3,  // Download margin
],
```

## API Endpoints

### Generate QR Code
```
POST /api/generate-sepa-qr
Content-Type: application/json

{
    "invoice_data": {
        "invoice_number": "INV-001",
        "total_amount": 150.00,
        "currency": "EUR",
        "recipient": {
            "name": "Customer Name"
        }
    },
    "bank_details": {
        "iban": "SI56 1910 0000 0123 456",
        "bic": "LJBASI2X",
        "name": "Your Company"
    }
}
```

### Download QR Code
```
POST /api/download-sepa-qr
Content-Type: multipart/form-data

invoice_data: {"invoice_number": "INV-001", ...}
bank_details: {"iban": "SI56...", ...}
```

## SEPA QR Code Format

The generated QR code follows the EPC QR Code specification:

```
BCD
002
1
SCT
BANKBIC
Company Name
SI56191000000123456
150.00
EUR
Invoice Payment
INV-001
Payment for invoice INV-001
INV-001
```

## Usage in Banking Apps

Customers can scan the QR code with their banking app to automatically:
- Fill in recipient IBAN
- Set payment amount
- Add payment reference (invoice number)
- Include payment description

## Requirements

- Laravel 8+
- SimpleSoftwareIO/simple-qrcode package
- Valid IBAN and BIC for your company

## Testing

1. Upload a PDF invoice
2. Extract the data
3. Click "Generate QR Code" in the invoice summary
4. Verify the QR code displays correctly
5. Test downloading the QR code
6. Scan with a banking app to verify it works

## Troubleshooting

- **QR code not generating**: Check that the QR code package is installed
- **Invalid QR code**: Verify bank details are correct (IBAN format, BIC)
- **Download fails**: Check file permissions and CSRF token
- **QR code not scannable**: Ensure sufficient size and contrast

## Customization

You can customize the QR code generation by:
- Modifying bank details in config
- Adjusting QR code size and margins
- Adding custom payment descriptions
- Implementing user-specific bank details
