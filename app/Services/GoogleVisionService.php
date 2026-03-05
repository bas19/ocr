<?php

namespace App\Services;

use App\Contracts\OcrServiceInterface;
use Exception;
use Google\Cloud\Vision\V1\AnnotateImageRequest;
use Google\Cloud\Vision\V1\BatchAnnotateImagesRequest;
use Google\Cloud\Vision\V1\Client\ImageAnnotatorClient;
use Google\Cloud\Vision\V1\Feature;
use Google\Cloud\Vision\V1\Feature\Type;
use Google\Cloud\Vision\V1\Image;
use Illuminate\Support\Facades\Log;

class GoogleVisionService implements OcrServiceInterface
{
    protected ImageAnnotatorClient $client;

    /**
     * Initialize Google Cloud Vision client.
     *
     * @throws Exception
     */
    public function __construct()
    {
        try {
            $config = [];

            // Priority 1: Use JSON credentials from environment variable (for production)
            if ($credentialsJson = config('services.google_vision.credentials_json')) {
                $credentials = json_decode($credentialsJson, true);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new Exception('Invalid JSON in GOOGLE_CREDENTIALS_JSON: '.json_last_error_msg());
                }

                $config['credentials'] = $credentials;
                Log::info('Using Google Vision credentials from JSON environment variable');
            }
            // Priority 2: Use credentials file path (for local development)
            elseif ($keyPath = config('services.google_vision.key_file')) {
                // Resolve relative paths from the application base path
                if (! str_starts_with($keyPath, '/')) {
                    $keyPath = base_path($keyPath);
                }

                if (! file_exists($keyPath)) {
                    throw new Exception("Credentials file not found at: {$keyPath}");
                }

                $config['credentials'] = $keyPath;
                Log::info('Using Google Vision credentials from file', ['path' => $keyPath]);
            }
            // No credentials configured
            else {
                throw new Exception('No Google Vision credentials configured. Set either GOOGLE_CREDENTIALS_JSON or GOOGLE_APPLICATION_CREDENTIALS in .env');
            }

            $this->client = new ImageAnnotatorClient($config);
        } catch (Exception $e) {
            Log::error('Failed to initialize Google Vision client', [
                'error' => $e->getMessage(),
            ]);
            throw new Exception('Google Vision client initialization failed: '.$e->getMessage());
        }
    }

    /**
     * Process an image and extract text using Google Cloud Vision OCR.
     *
     * @param  string  $imagePath  Full path to the image file
     * @return string Raw extracted text
     *
     * @throws Exception
     */
    public function extractText(string $imagePath): string
    {
        if (! file_exists($imagePath)) {
            throw new Exception("Image file not found: {$imagePath}");
        }

        try {
            // Read image file
            $imageContent = file_get_contents($imagePath);

            // Create image object
            $image = new Image;
            $image->setContent($imageContent);

            // Create feature for text detection
            $feature = new Feature;
            $feature->setType(Type::TEXT_DETECTION);

            // Create annotation request
            $request = new AnnotateImageRequest;
            $request->setImage($image);
            $request->setFeatures([$feature]);

            // Create batch request
            $batchRequest = new BatchAnnotateImagesRequest;
            $batchRequest->setRequests([$request]);

            // Perform text detection
            $response = $this->client->batchAnnotateImages($batchRequest);
            $annotations = $response->getResponses();

            if (count($annotations) === 0) {
                Log::warning('No response from Google Vision API', ['path' => $imagePath]);

                return '';
            }

            $annotation = $annotations[0];
            $texts = $annotation->getTextAnnotations();

            if (count($texts) === 0) {
                Log::warning('No text detected in image', ['path' => $imagePath]);

                return '';
            }

            // The first text annotation contains the entire detected text
            $fullText = $texts[0]->getDescription();

            // Check for errors
            $error = $annotation->getError();
            if ($error) {
                throw new Exception('Google Vision API error: '.$error->getMessage());
            }

            Log::info('Text extracted successfully via Google Vision', [
                'path' => $imagePath,
                'text_length' => strlen($fullText),
            ]);

            return $fullText;
        } catch (Exception $e) {
            Log::error('Failed to extract text with Google Vision', [
                'error' => $e->getMessage(),
                'path' => $imagePath,
            ]);

            throw $e;
        }
    }

    /**
     * Parse receipt data from raw OCR text.
     *
     * @param  string  $rawText  Raw OCR text
     * @return array{transaction_date: ?string, invoice_number: ?string, supplier: ?string, total_amount: ?float, description: ?string}
     */
    public function parseReceiptData(string $rawText): array
    {
        return [
            'transaction_date' => $this->extractDate($rawText),
            'invoice_number' => $this->extractInvoiceNumber($rawText),
            'supplier' => $this->extractSupplier($rawText),
            'total_amount' => $this->extractTotalAmount($rawText),
            'description' => $this->extractDescription($rawText),
        ];
    }

    /**
     * Extract supplier/merchant name from receipt text.
     */
    protected function extractSupplier(string $text): ?string
    {
        $lines = explode("\n", $text);

        // Usually supplier name is in the first few lines
        // Look for lines that are not too long and not prices
        foreach (array_slice($lines, 0, 5) as $line) {
            $line = trim($line);
            if (strlen($line) > 3 && strlen($line) < 50 && ! preg_match('/\d+\.\d{2}/', $line)) {
                // Avoid lines that look like addresses or dates
                if (! preg_match('/^\d+\s/', $line) && ! preg_match('/\d{2}\/\d{2}\/\d{2,4}/', $line)) {
                    return $line;
                }
            }
        }

        return null;
    }

    /**
     * Extract transaction date from receipt text.
     */
    protected function extractDate(string $text): ?string
    {
        // Pattern for YY/MM/DD or DD/MM/YY format (e.g., 17/02/26 or 26/02/17)
        if (preg_match('/\b(\d{2})\/(\d{2})\/(\d{2})\b/', $text, $matches)) {
            $first = (int) $matches[1];
            $second = (int) $matches[2];
            $third = (int) $matches[3];

            // Determine format based on values
            if ($second <= 12) {
                // Second value is valid month, assume YY/MM/DD format
                $year = $first >= 0 && $first <= 50 ? 2000 + $first : 1900 + $first;
                $month = $second;
                $day = $third;

                if ($day >= 1 && $day <= 31) {
                    return sprintf('%04d-%02d-%02d', $year, $month, $day);
                }
            }

            // If first check didn't work, try DD/MM/YY format
            if ($second <= 12 && $first >= 1 && $first <= 31) {
                $year = $third >= 0 && $third <= 50 ? 2000 + $third : 1900 + $third;
                $month = $second;
                $day = $first;

                return sprintf('%04d-%02d-%02d', $year, $month, $day);
            }
        }

        // Common date patterns
        $patterns = [
            '/\b(\d{1,2})\/(\d{1,2})\/(\d{4})\b/', // DD/MM/YYYY or MM/DD/YYYY
            '/\b(\d{4})-(\d{2})-(\d{2})\b/', // YYYY-MM-DD
            '/\b(\d{1,2})-(\d{1,2})-(\d{2,4})\b/', // DD-MM-YYYY or MM-DD-YYYY
            '/\b([A-Za-z]{3})\s+(\d{1,2}),?\s+(\d{4})\b/', // Jan 15, 2024
        ];

        foreach ($patterns as $index => $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                try {
                    if ($index === 3) {
                        // Month name format
                        $date = date('Y-m-d', strtotime($matches[0]));
                    } elseif ($index === 1) {
                        // YYYY-MM-DD format (ISO)
                        $date = sprintf('%04d-%02d-%02d', (int) $matches[1], (int) $matches[2], (int) $matches[3]);
                    } else {
                        // Try to parse DD/MM/YYYY format first
                        $parts = preg_split('/[\/\-]/', $matches[0]);
                        if (count($parts) === 3) {
                            $first = (int) $parts[0];
                            $second = (int) $parts[1];
                            $third = (int) $parts[2];

                            // If third part is 4 digits, it's the year
                            if ($third > 999) {
                                // Could be DD/MM/YYYY or MM/DD/YYYY
                                if ($second > 12) {
                                    // Must be DD/MM/YYYY
                                    $date = sprintf('%04d-%02d-%02d', $third, $first, $second);
                                } elseif ($first > 12) {
                                    // Must be MM/DD/YYYY
                                    $date = sprintf('%04d-%02d-%02d', $third, $second, $first);
                                } else {
                                    // Ambiguous - assume DD/MM/YYYY (common in receipts)
                                    $date = sprintf('%04d-%02d-%02d', $third, $second, $first);
                                }
                            } else {
                                // Use strtotime as fallback
                                $date = date('Y-m-d', strtotime($matches[0]));
                            }
                        } else {
                            $date = date('Y-m-d', strtotime($matches[0]));
                        }
                    }

                    if ($date !== false && $date !== '1970-01-01') {
                        return $date;
                    }
                } catch (Exception) {
                    continue;
                }
            }
        }

        return null;
    }

    /**
     * Extract invoice/receipt number from text.
     */
    protected function extractInvoiceNumber(string $text): ?string
    {
        // Common invoice number patterns (ordered by specificity)
        $patterns = [
            '/\b(\d{10})\b/', // Standalone 10-digit number like 4680648477
            '/\bNO\.?\s*[:.]?\s*(\d{10})/i', // NO: 4680648477 or NO 4680648477 (10-digit numbers)
            '/\bNO\.?\s*[:.]?\s*([A-Z0-9\-\/]{5,})/i', // NO with other alphanumeric formats
            '/\bINVOICE\s+NO\.?\s*[:.\s]*([A-Z0-9\-\/]+)/i', // INVOICE NO: 4680648477 or INVOICE NO 12345
            '/\b(?:INVOICE|RECEIPT)\s+(?:NO|NUMBER|#)\.?\s*[:.\s]*([A-Z0-9\-\/]+)/i', // INVOICE NUMBER, RECEIPT NO, etc.
            '/\b(?:INV|RCPT|ORD|REF)\s*(?:NO|NUM|#)?\.?\s*[:.\s]*([A-Z0-9\-\/]+)/i', // INV NO, RCPT #, etc.
            '/\b(?:ORDER|REFERENCE)\s+(?:NO|NUMBER|#)\.?\s*[:.\s]*([A-Z0-9\-\/]+)/i', // ORDER NO, REFERENCE NUMBER
            '/#\s*([A-Z0-9\-\/]{3,})/i', // # followed by alphanumeric (min 3 chars)
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                $invoiceNo = trim($matches[1]);

                // Skip if it's clearly not an invoice number
                // But allow long numeric sequences (like 4680648477)
                if (strlen($invoiceNo) < 3 || strlen($invoiceNo) > 50) {
                    continue;
                }

                // Skip if it looks like a date (DD/MM or MM/DD format)
                if (preg_match('/^\d{1,2}[\/\-]\d{1,2}[\/\-]/', $invoiceNo)) {
                    continue;
                }

                // Skip if it's a price (e.g., 12.34)
                if (preg_match('/^\d+\.\d{2}$/', $invoiceNo)) {
                    continue;
                }

                // Accept purely numeric invoice numbers (like 4680648477)
                // or alphanumeric combinations
                if (preg_match('/^[A-Z0-9\-\/]+$/i', $invoiceNo)) {
                    return $invoiceNo;
                }
            }
        }

        return null;
    }

    /**
     * Extract total amount from receipt text.
     */
    protected function extractTotalAmount(string $text): ?float
    {
        // Common total patterns (ordered by specificity)
        $patterns = [
            '/\b(?:TOTAL|GRAND\s+TOTAL|AMOUNT\s+DUE)\s*[:.]?\s*\$?\s*(\d+[,.]?\d*\.?\d{2})\b/i',
            '/\b(?:TOTAL|BALANCE)\s*[:.]?\s*(\d+[,.]?\d*\.?\d{2})\s*$/mi',
            '/\bTOTAL\s*[:.]?\s*([A-Z]{3})?\s*\$?\s*(\d+[,.]?\d*\.?\d{2})\b/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                // Get the last match (the amount)
                $amount = end($matches);
                $amount = str_replace(',', '', $amount);

                if (is_numeric($amount)) {
                    return (float) $amount;
                }
            }
        }

        // Fallback: find the largest amount in the text
        if (preg_match_all('/\$?\s*(\d+[,.]?\d*\.?\d{2})\b/', $text, $matches)) {
            $amounts = array_map(function ($amount) {
                return (float) str_replace(',', '', $amount);
            }, $matches[1]);

            if (! empty($amounts)) {
                return max($amounts);
            }
        }

        return null;
    }

    /**
     * Extract description from receipt text.
     */
    protected function extractDescription(string $text): ?string
    {
        $lines = explode("\n", $text);
        $descriptions = [];

        // Look for item descriptions (lines that might be products/services)
        foreach ($lines as $line) {
            $line = trim($line);

            // Skip empty lines, very short lines, and header/footer text
            if (strlen($line) < 3 || strlen($line) > 100) {
                continue;
            }

            // Skip lines that look like totals, dates, or addresses
            if (preg_match('/^(?:TOTAL|SUBTOTAL|TAX|BALANCE|INVOICE|RECEIPT|NO\.|DATE)/i', $line)) {
                continue;
            }

            // Skip lines that are just prices
            if (preg_match('/^\$?\s*\d+[,.]?\d*\.?\d{2}$/', $line)) {
                continue;
            }

            // Skip lines that look like dates or phone numbers
            if (preg_match('/\d{2}[\/\-]\d{2}[\/\-]\d{2,4}|\d{3}[.\-]?\d{3}[.\-]?\d{4}/', $line)) {
                continue;
            }

            // Lines with prices might be item descriptions
            if (preg_match('/[A-Za-z]{3,}/', $line)) {
                $descriptions[] = $line;
            }
        }

        // Return first few items as description
        if (! empty($descriptions)) {
            return implode(' | ', array_slice($descriptions, 0, 5));
        }

        return null;
    }

    /**
     * Close the client connection.
     */
    public function __destruct()
    {
        if (isset($this->client)) {
            $this->client->close();
        }
    }
}
