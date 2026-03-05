<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CheckReceiptSchema extends Command
{
    protected $signature = 'receipts:check-schema';

    protected $description = 'Check receipts table schema and verify total_amount and description columns exist';

    public function handle(): int
    {
        $this->info('🔍 Checking receipts table schema...');
        $this->newLine();

        // Check if table exists
        if (! Schema::hasTable('receipts')) {
            $this->error('❌ receipts table does not exist!');

            return self::FAILURE;
        }

        $this->info('✅ receipts table exists');
        $this->newLine();

        // Get all columns
        $columns = Schema::getColumnListing('receipts');

        $this->info('📋 Current columns in receipts table:');
        foreach ($columns as $column) {
            $this->line("   - {$column}");
        }
        $this->newLine();

        // Check for specific columns
        $requiredColumns = [
            'total_amount' => 'Total Amount (new field)',
            'description' => 'Description (new field)',
            'invoice_number' => 'Invoice Number',
            'supplier' => 'Supplier',
            'transaction_date' => 'Transaction Date',
        ];

        $this->info('🔎 Checking required columns:');
        $allPresent = true;

        foreach ($requiredColumns as $column => $label) {
            if (Schema::hasColumn('receipts', $column)) {
                $this->info("   ✅ {$label}: {$column}");
            } else {
                $this->error("   ❌ {$label}: {$column} - MISSING!");
                $allPresent = false;
            }
        }

        $this->newLine();

        if (! $allPresent) {
            $this->error('❌ Some columns are missing!');
            $this->newLine();
            $this->warn('💡 Fix: Run migrations with:');
            $this->line('   php artisan migrate --force');

            return self::FAILURE;
        }

        $this->info('✅ All required columns are present!');
        $this->newLine();

        // Check for sample data
        $receiptCount = DB::table('receipts')->count();
        $this->info("📊 Total receipts in database: {$receiptCount}");

        if ($receiptCount > 0) {
            $recentReceipt = DB::table('receipts')
                ->latest('created_at')
                ->first();

            $this->newLine();
            $this->info('📝 Most recent receipt:');
            $this->line("   ID: {$recentReceipt->id}");
            $this->line('   Invoice: '.($recentReceipt->invoice_number ?? 'null'));
            $this->line('   Supplier: '.($recentReceipt->supplier ?? 'null'));
            $this->line('   Total: '.($recentReceipt->total_amount ?? 'null'));
            $this->line('   Description: '.($recentReceipt->description ?? 'null'));
            $this->line('   Date: '.($recentReceipt->transaction_date ?? 'null'));
        }

        $this->newLine();
        $this->info('✨ Schema check complete!');

        return self::SUCCESS;
    }
}
