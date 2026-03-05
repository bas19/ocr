<?php

namespace App\Contracts;

interface OcrServiceInterface
{
    /**
     * Process an image and extract text using OCR.
     *
     * @param  string  $imagePath  Full path to the image file
     * @return string Raw extracted text
     *
     * @throws \Exception
     */
    public function extractText(string $imagePath): string;

    /**
     * Parse receipt data from raw OCR text.
     *
     * @param  string  $rawText  Raw OCR text
     * @return array{transaction_date: ?string, invoice_number: ?string, supplier: ?string}
     */
    public function parseReceiptData(string $rawText): array;
}
