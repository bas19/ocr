<?php

namespace App\Console\Commands;

use App\Contracts\OcrServiceInterface;
use App\Models\Receipt;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class ProcessReceiptCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'receipt:process {path : The path to the receipt image file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process a receipt image using OCR and extract data';

    /**
     * Execute the console command.
     */
    public function handle(OcrServiceInterface $ocrService): int
    {
        $imagePath = $this->argument('path');

        if (! file_exists($imagePath)) {
            $this->error("Image file not found: {$imagePath}");

            return self::FAILURE;
        }

        $this->info("Processing receipt: {$imagePath}");

        try {
            // Extract text from image
            $this->info('Extracting text from image...');
            $rawText = $ocrService->extractText($imagePath);

            $this->line('');
            $this->line('Raw OCR Text:');
            $this->line('─────────────────────────────────────');
            $this->line($rawText);
            $this->line('─────────────────────────────────────');
            $this->line('');

            // Parse receipt data
            $this->info('Parsing receipt data...');
            $parsedData = $ocrService->parseReceiptData($rawText);

            // Store the image in storage
            $fileName = basename($imagePath);
            $storagePath = Storage::putFileAs('receipts', $imagePath, $fileName);

            // Create receipt record
            $receipt = Receipt::create([
                'image_path' => $storagePath,
                'invoice_number' => $parsedData['invoice_number'],
                'transaction_date' => $parsedData['transaction_date'],
                'raw_text' => $rawText,
                'metadata' => [
                    'processed_at' => now()->toIso8601String(),
                    'original_path' => $imagePath,
                ],
                'status' => 'processed',
            ]);

            $this->info('Receipt processed successfully!');
            $this->line('');

            // Display extracted data
            $this->table(
                ['Field', 'Value'],
                [
                    ['Receipt ID', $receipt->id],
                    ['Invoice Number', $parsedData['invoice_number'] ?? 'Not found'],
                    ['Supplier', $parsedData['supplier'] ?? 'Not found'],
                    ['Transaction Date', $parsedData['transaction_date'] ?? 'Not found'],
                ]
            );

            return self::SUCCESS;
        } catch (Exception $e) {
            $this->error('Failed to process receipt: '.$e->getMessage());

            // Create failed receipt record
            Receipt::create([
                'image_path' => $imagePath,
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            return self::FAILURE;
        }
    }
}
