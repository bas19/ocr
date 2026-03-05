# 🔧 Google Vision Credentials Troubleshooting

Common errors and solutions for Google Cloud Vision API credentials.

---

## ❌ Error: "OpenSSL unable to validate key"

### Full Error Message:
```
OpenSSL unable to validate key
path: /var/www/html/storage/app/public/receipts/xxxxx.png
```

### 🔍 Problem
The private key in your `GOOGLE_CREDENTIALS_JSON` is malformed or improperly formatted. This happens when:
- The JSON was truncated during copy/paste
- Extra quotes were added around the JSON
- The private key's newline characters (`\n`) are corrupted
- The JSON has syntax errors

### ✅ Solution

#### Step 1: Diagnose the Issue

Run the diagnostic command:
```bash
php artisan credentials:diagnose
```

This will check:
- ✅ JSON validity
- ✅ Required fields presence
- ✅ Private key format
- ✅ Configuration source

#### Step 2: Regenerate Valid Credentials

**Locally:**
```bash
./deploy-to-cloud.sh
```

This will:
1. Validate your source `storage/app/google-credentials.json`
2. Create a properly minified version
3. Copy to clipboard (macOS)
4. Show validation results

**OR manually:**
```bash
# Validate source file first
jq empty storage/app/google-credentials.json

# Create minified version
jq -c . storage/app/google-credentials.json > google-credentials-minified.txt

# Verify it's valid
cat google-credentials-minified.txt | jq empty && echo "✅ Valid"

# Copy to clipboard (macOS)
cat google-credentials-minified.txt | pbcopy
```

#### Step 3: Update Laravel Cloud Environment Variable

1. **Go to Laravel Cloud Dashboard**
2. **Navigate to Environment Variables**
3. **Find `GOOGLE_CREDENTIALS_JSON`**
4. **Click Edit**
5. **Delete the entire current value**
6. **Paste the new value** (⌘+V)

#### Step 4: Critical - Verify Before Saving

✅ **DO:**
- Starts with: `{"type":"service_account"`
- Contains: `"private_key":"-----BEGIN PRIVATE KEY-----\\n`
- Ends with: `}"` (curly brace + double quote)
- Single line (no actual newlines)

❌ **DON'T:**
- Add extra quotes: ~~`"{'type':'service_account',...}"`~~
- Truncate the JSON
- Manually edit the JSON in the web interface

#### Step 5: Verify Private Key Format

The private key should look like this in the minified JSON:
```json
"private_key":"-----BEGIN PRIVATE KEY-----\\nMIIEvQIBADANBg...\\n-----END PRIVATE KEY-----\\n"
```

Note the `\\n` escape sequences for newlines.

#### Step 6: Save and Redeploy

1. **Save** the environment variable
2. **Clear cache** (if option available)
3. **Redeploy** your application
4. Check logs for: `"Using Google Vision credentials from JSON environment variable"`

---

## ❌ Error: "Invalid JSON in GOOGLE_CREDENTIALS_JSON: Syntax error"

### 🔍 Problem
The JSON string itself is malformed.

### ✅ Solution

See the solution above - same fix applies. The key is to regenerate the JSON properly using the deployment script.

---

## ❌ Error: "Credentials file not found at: /var/www/html/..."

### 🔍 Problem
You have `GOOGLE_APPLICATION_CREDENTIALS` set in production, which tries to use a file path that doesn't exist in cloud environments.

### ✅ Solution

1. **Delete** `GOOGLE_APPLICATION_CREDENTIALS` from environment variables
2. **Use** `GOOGLE_CREDENTIALS_JSON` instead (with JSON content)
3. Follow steps above to set it correctly

---

## 🧪 Local Testing

### Test Credentials Locally

```bash
# Run diagnostic
php artisan credentials:diagnose

# Test actual OCR
php artisan tinker
```

In Tinker:
```php
$service = app(\App\Services\GoogleVisionService::class);
$text = $service->extractText(storage_path('app/public/receipts/test.jpg'));
echo $text;
```

### Validate JSON Structure

```bash
# Check if JSON is valid
cat google-credentials-minified.txt | jq empty && echo "✅ Valid" || echo "❌ Invalid"

# View JSON structure (without private key)
cat google-credentials-minified.txt | jq 'del(.private_key)'

# Count characters
wc -c < google-credentials-minified.txt
```

### Test from Clipboard (macOS)

```bash
# See what's in clipboard
pbpaste | jq empty && echo "✅ Valid" || echo "❌ Invalid"

# View first 100 characters
pbpaste | head -c 100
```

---

## 📋 Environment Variables Checklist

### Production (Laravel Cloud)

- [ ] `GOOGLE_CREDENTIALS_JSON` is set with minified JSON
- [ ] `GOOGLE_CREDENTIALS_JSON` starts with `{"type":"service_account"`
- [ ] `GOOGLE_CREDENTIALS_JSON` ends with `}"`
- [ ] `GOOGLE_APPLICATION_CREDENTIALS` is **NOT** set (delete it)
- [ ] `GOOGLE_VISION_ENABLED=true`
- [ ] Application has been redeployed

### Local Development

- [ ] `storage/app/google-credentials.json` exists
- [ ] `GOOGLE_APPLICATION_CREDENTIALS=storage/app/google-credentials.json`
- [ ] `GOOGLE_VISION_ENABLED=true`
- [ ] File contains valid JSON

---

## 🔬 Advanced Debugging

### Check What Laravel Sees

```bash
php artisan tinker
```

```php
// See which credentials are configured
config('services.google_vision.credentials_json') ? 'JSON SET' : 'JSON NOT SET';
config('services.google_vision.key_file') ? 'FILE SET' : 'FILE NOT SET';

// Try to decode JSON credentials
$json = config('services.google_vision.credentials_json');
$data = json_decode($json, true);
json_last_error_msg(); // Should be "No error"

// Check if private key is present
isset($data['private_key']) ? 'Present' : 'Missing';

// Check private key format
str_contains($data['private_key'], '-----BEGIN PRIVATE KEY-----') ? 'Valid start' : 'Invalid';
```

### Check Logs

Laravel Cloud logs or local logs:
```bash
tail -f storage/logs/laravel.log
```

Look for:
- ✅ `"Using Google Vision credentials from JSON environment variable"`
- ❌ `"Failed to initialize Google Vision client"`
- ❌ `"Invalid JSON in GOOGLE_CREDENTIALS_JSON"`

---

## 💡 Common Mistakes

### 1. Adding Quotes Around JSON
**Wrong:**
```bash
GOOGLE_CREDENTIALS_JSON="{'type':'service_account',...}"
```

**Right:**
```bash
GOOGLE_CREDENTIALS_JSON={"type":"service_account",...}
```

### 2. Copying from Web UI
Don't copy from the Laravel Cloud web interface after pasting - always copy from your local `google-credentials-minified.txt` file.

### 3. Manual Editing
Never manually edit the JSON in the environment variable field. Always use the properly generated minified version.

### 4. Using File Paths in Production
`GOOGLE_APPLICATION_CREDENTIALS` only works for local development where you have file system access.

---

## 📖 Related Documentation

- Run: `php artisan credentials:diagnose` for automated checking
- See: [DEPLOYMENT.md](DEPLOYMENT.md) for full deployment guide
- See: [DEPLOYMENT_SCRIPTS.md](DEPLOYMENT_SCRIPTS.md) for script usage
- Run: `./deploy-to-cloud.sh` to prepare credentials

---

## 🆘 Still Not Working?

1. **Clear all caches:**
   ```bash
   php artisan config:clear
   php artisan cache:clear
   ```

2. **Regenerate credentials from Google Cloud Console:**
   - Download a fresh service account key
   - Replace `storage/app/google-credentials.json`
   - Run `./deploy-to-cloud.sh` again

3. **Verify Google Cloud:**
   - Vision API is enabled
   - Service account has "Cloud Vision API User" role
   - Service account key is active (not deleted/revoked)

---

Last updated: March 5, 2026
