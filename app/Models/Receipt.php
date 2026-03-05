<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Receipt extends Model
{
    /** @use HasFactory<\Database\Factories\ReceiptFactory> */
    use HasFactory;

    protected $fillable = [
        'image_path',
        'invoice_number',
        'supplier',
        'transaction_date',
        'raw_text',
        'metadata',
        'status',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'transaction_date' => 'date',
        ];
    }
}
