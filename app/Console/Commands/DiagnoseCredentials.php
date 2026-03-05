<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class DiagnoseCredentials extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'credentials:diagnose';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Diagnose Google Cloud Vision credentials configuration';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🔍 Diagnosing Google Cloud Vision Credentials...');
        $this->newLine();

        // Check environment variables
        $credentialsJson = config('services.google_vision.credentials_json');
        $keyFile = config('services.google_vision.key_file');

        $this->components->twoColumnDetail('GOOGLE_CREDENTIALS_JSON set', $credentialsJson ? '✅ Yes' : '❌ No');
        $this->components->twoColumnDetail('GOOGLE_APPLICATION_CREDENTIALS set', $keyFile ? '✅ Yes' : '❌ No');
        $this->newLine();

        // Check which credentials will be used
        if ($credentialsJson) {
            $this->info('📋 Will use: GOOGLE_CREDENTIALS_JSON (environment variable)');
            $this->newLine();

            // Validate JSON
            $this->info('Validating JSON structure...');
            $credentials = json_decode($credentialsJson, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->error('❌ Invalid JSON: '.json_last_error_msg());
                $this->newLine();
                $this->warn('Common issues:');
                $this->line('  • Extra quotes around the JSON');
                $this->line('  • Truncated JSON (incomplete paste)');
                $this->line('  • Newlines in the middle of the JSON');
                $this->newLine();
                $this->info('💡 Fix: Run ./deploy-to-cloud.sh to regenerate valid JSON');

                return self::FAILURE;
            }

            $this->info('✅ JSON is valid');
            $this->newLine();

            // Check required fields
            $requiredFields = ['type', 'project_id', 'private_key_id', 'private_key', 'client_email'];
            $this->info('Checking required fields...');

            $missing = [];
            foreach ($requiredFields as $field) {
                $exists = isset($credentials[$field]) && ! empty($credentials[$field]);
                $this->components->twoColumnDetail($field, $exists ? '✅ Present' : '❌ Missing');
                if (! $exists) {
                    $missing[] = $field;
                }
            }

            if (! empty($missing)) {
                $this->newLine();
                $this->error('Missing required fields: '.implode(', ', $missing));

                return self::FAILURE;
            }

            $this->newLine();

            // Check private key format
            if (isset($credentials['private_key'])) {
                $privateKey = $credentials['private_key'];
                $hasBegin = str_contains($privateKey, '-----BEGIN PRIVATE KEY-----');
                $hasEnd = str_contains($privateKey, '-----END PRIVATE KEY-----');
                $hasNewlines = str_contains($privateKey, '\n') || str_contains($privateKey, "\n");

                $this->components->twoColumnDetail('Private key starts with BEGIN', $hasBegin ? '✅ Yes' : '❌ No');
                $this->components->twoColumnDetail('Private key ends with END', $hasEnd ? '✅ Yes' : '❌ No');
                $this->components->twoColumnDetail('Private key has newlines', $hasNewlines ? '✅ Yes' : '❌ No');

                if (! $hasBegin || ! $hasEnd) {
                    $this->newLine();
                    $this->error('❌ Private key format is invalid');
                    $this->warn('The private_key should start with "-----BEGIN PRIVATE KEY-----"');
                    $this->warn('and end with "-----END PRIVATE KEY-----"');

                    return self::FAILURE;
                }
            }

            $this->newLine();
            $this->info('📊 Credentials Summary:');
            $this->line('  Type: '.$credentials['type']);
            $this->line('  Project ID: '.$credentials['project_id']);
            $this->line('  Client Email: '.$credentials['client_email']);
            $this->line('  Private Key ID: '.substr($credentials['private_key_id'], 0, 20).'...');

        } elseif ($keyFile) {
            $this->info('📁 Will use: GOOGLE_APPLICATION_CREDENTIALS (file path)');
            $this->newLine();

            // Resolve path
            $resolvedPath = str_starts_with($keyFile, '/') ? $keyFile : base_path($keyFile);
            $this->components->twoColumnDetail('Path', $resolvedPath);

            if (! file_exists($resolvedPath)) {
                $this->error('❌ Credentials file not found');
                $this->newLine();
                $this->warn('For production deployment, use GOOGLE_CREDENTIALS_JSON instead');
                $this->info('Run: ./deploy-to-cloud.sh');

                return self::FAILURE;
            }

            $this->info('✅ File exists');
            $this->newLine();

            // Validate file content
            $content = file_get_contents($resolvedPath);
            $credentials = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->error('❌ Invalid JSON in file: '.json_last_error_msg());

                return self::FAILURE;
            }

            $this->info('✅ File contains valid JSON');

        } else {
            $this->error('❌ No credentials configured');
            $this->newLine();
            $this->warn('Set one of these environment variables:');
            $this->line('  • GOOGLE_CREDENTIALS_JSON (recommended for production)');
            $this->line('  • GOOGLE_APPLICATION_CREDENTIALS (for local development)');
            $this->newLine();
            $this->info('See: FIX_JSON_SYNTAX_ERROR.md or DEPLOYMENT.md');

            return self::FAILURE;
        }

        $this->newLine();
        $this->info('✨ Credentials configuration looks good!');
        $this->newLine();
        $this->components->info('Next step: Test OCR by uploading a receipt');

        return self::SUCCESS;
    }
}
