# Receipt OCR using Google Cloud Vision

This Laravel application uses **Google Cloud Vision API** for accurate receipt text extraction.

## Features

- ✅ Extract text from receipt images using Google Cloud Vision AI
- ✅ Parse invoice numbers (including 10-digit formats like 4680648477)
- ✅ Extract transaction dates (multiple format support)
- ✅ Identify supplier/merchant names
- ✅ Bootstrap 5 UI with drag-and-drop upload
- ✅ Display raw OCR text for debugging

## Setup

### 1. Install Dependencies

```bash
composer install
npm install
```

### 2. Configure Google Cloud Vision

1. **Create a Google Cloud Project**:
    - Go to [Google Cloud Console](https://console.cloud.google.com)
    - Create a new project or select an existing one

2. **Enable Cloud Vision API**:
    - Navigate to "APIs & Services" > "Library"
    - Search for "Cloud Vision API"
    - Click "Enable"

3. **Create Service Account**:
    - Go to "APIs & Services" > "Credentials"
    - Click "Create Credentials" > "Service Account"
    - Fill in the details and click "Create"
    - Grant "Cloud Vision API User" role
    - Click "Done"

4. **Download Credentials**:
    - Click on the created service account
    - Go to "Keys" tab
    - Click "Add Key" > "Create new key"
    - Choose JSON format
    - Download the key file

5. **Place Credentials**:

    ```bash
    # Copy your downloaded JSON key to:
    storage/app/google-credentials.json
    ```

6. **Update .env**:
    ```dotenv
    GOOGLE_APPLICATION_CREDENTIALS=storage/app/google-credentials.json
    GOOGLE_VISION_ENABLED=true
    ```

### 3. Run Migrations

```bash
php artisan migrate
```

### 4. Build Frontend Assets

```bash
npm run build
# Or for development with hot reload:
npm run dev
```

### 5. Start the Application

```bash
php artisan serve
```

Visit: [http://localhost:8000/receipts/upload](http://localhost:8000/receipts/upload)

## Usage

### Web Interface

1. Navigate to `/receipts/upload`
2. Drag and drop a receipt image or click to browse
3. Click "Extract Data"
4. View extracted invoice number, supplier, date, and raw OCR text

### Command Line

Process a receipt from the command line:

```bash
php artisan receipt:process /path/to/receipt.jpg
```

## Supported Invoice Number Formats

The system recognizes various invoice number patterns:

- **10-digit numbers**: `4680648477`
- **NO keyword**: `NO 4680648477`, `NO: 4680648477`
- **INVOICE NO**: `INVOICE NO 12345`, `INVOICE NO: ABC-123`
- **Other formats**: `RECEIPT NO`, `INV #`, `ORDER NO`, etc.

## Supported Date Formats

- `17/02/26` (YY/MM/DD)
- `26/02/2017` (DD/MM/YYYY)
- `2017-02-26` (YYYY-MM-DD)
- `Feb 26, 2017` (Month name format)

## Database Schema

Receipts are stored with the following fields:

- `invoice_number` - Extracted invoice/receipt number
- `supplier` - Merchant/supplier name
- `transaction_date` - Date of transaction
- `raw_text` - Complete OCR extracted text
- `metadata` - Additional JSON data
- `status` - Processing status (completed/failed)

## Deployment Considerations

### No System Dependencies Required

Since this application uses Google Cloud Vision API:

- ✅ No need to install Tesseract OCR on server
- ✅ No need to install ImageMagick
- ✅ Works on any server with PHP 8.2+

### Recommended Servers (Budget-Friendly)

- **Hetzner Cloud**: €4.15/month (2GB RAM)
- **DigitalOcean**: $6/month (1GB RAM)
- **Vultr**: $6/month (1GB RAM)
- **Linode**: $5/month (1GB RAM)

### Requirements

- PHP 8.2 or higher
- Composer
- Node.js & npm
- MySQL/PostgreSQL/SQLite database
- Google Cloud Vision API credentials

## Troubleshooting

### "Could not find keyfile"

Make sure:

- The JSON key file exists at the path specified in `.env`
- The path is relative to the Laravel root or use an absolute path
- File has proper read permissions

### "Class 'Google\Cloud\Vision\V1\Client\ImageAnnotatorClient' not found"

Run:

```bash
composer update
composer dump-autoload
```

### Poor Extraction Accuracy

Google Cloud Vision is highly accurate, but you can improve results by:

- Using higher resolution images (300 DPI or higher)
- Ensuring good lighting and contrast
- Avoiding blurry or skewed images
- Cropping to focus on the receipt area

## Cost Considerations

Google Cloud Vision API pricing:

- First 1,000 units/month: **FREE**
- After that: $1.50 per 1,000 units

For typical receipt processing (5-10 receipts/day):

- Monthly usage: ~150-300 units
- Cost: **$0** (within free tier)

## Support

For more information on Google Cloud Vision:

- [Documentation](https://cloud.google.com/vision/docs)
- [Pricing](https://cloud.google.com/vision/pricing)
- [API Reference](https://cloud.google.com/vision/docs/reference/rest)
