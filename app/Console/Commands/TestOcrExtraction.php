<?php

namespace App\Console\Commands;

use App\Contracts\OcrServiceInterface;
use Illuminate\Console\Command;

class TestOcrExtraction extends Command
{
    protected $signature = 'ocr:test {--text= : Raw OCR text to test}';

    protected $description = 'Test OCR data extraction from sample text';

    public function __construct(public OcrServiceInterface $ocrService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $text = $this->option('text') ?? $this->getSampleText();

        $this->info('🔍 Testing OCR data extraction...');
        $this->newLine();

        $this->info('📄 Input text:');
        $this->line('─────────────────────────────────────────────');
        $this->line($text);
        $this->line('─────────────────────────────────────────────');
        $this->newLine();

        $parsed = $this->ocrService->parseReceiptData($text);

        $this->info('✨ Extracted data:');
        $this->newLine();

        $this->line('  Invoice Number: '.($parsed['invoice_number'] ?? 'NOT FOUND'));
        $this->line('  Supplier: '.($parsed['supplier'] ?? 'NOT FOUND'));
        $this->line('  Date: '.($parsed['transaction_date'] ?? 'NOT FOUND'));
        $this->line('  Total: $'.($parsed['total_amount'] ?? 'NOT FOUND'));
        $this->line('  Description: '.($parsed['description'] ?? 'NOT FOUND'));

        $this->newLine();

        if (empty($parsed['total_amount'])) {
            $this->warn('⚠️  Total amount was not extracted');
        } else {
            $this->info('✅ Total amount extracted successfully');
        }

        if (empty($parsed['description'])) {
            $this->warn('⚠️  Description was not extracted');
        } else {
            $this->info('✅ Description extracted successfully');
        }

        return self::SUCCESS;
    }

    protected function getSampleText(): string
    {
        return <<<'TEXT'
Renco
CORPORATE OFFICE
17/02/26
NO 4680648477
TABLE 01
WAITER James

Espresso 4.50
Cappuccino 5.00
Latte 5.50
Americano 4.00
Mocha 6.00
Hot Chocolate 5.00

SUB TOTAL 30.00
TAX 10% 3.00
TOTAL 33.00

THANK YOU
TEXT;
    }
}
