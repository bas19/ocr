# Deployment Guide

## Laravel Cloud Deployment

### Google Cloud Vision Credentials Setup

For production deployments (Laravel Cloud, Forge, etc.), you cannot use file-based credentials. Instead, set the credentials as an environment variable.

#### Step 1: Get Your Credentials JSON

Download your Google Cloud service account JSON key file from Google Cloud Console.

#### Step 2: Minify the JSON

Convert the JSON to a single line (remove all newlines and extra spaces):

**Option A: Using `jq` (recommended)**

```bash
cat google-credentials.json | jq -c
```

**Option B: Using online tool**
Visit https://codebeautify.org/jsonminifier and paste your JSON.

**Option C: Manual (example output)**

```json
{
    "type": "service_account",
    "project_id": "your-project-123",
    "private_key_id": "abc123",
    "private_key": "-----BEGIN PRIVATE KEY-----\nYOUR_KEY_HERE\n-----END PRIVATE KEY-----\n",
    "client_email": "your-service@your-project.iam.gserviceaccount.com",
    "client_id": "123456789",
    "auth_uri": "https://accounts.google.com/o/oauth2/auth",
    "token_uri": "https://oauth2.googleapis.com/token",
    "auth_provider_x509_cert_url": "https://www.googleapis.com/oauth2/v1/certs",
    "client_x509_cert_url": "https://www.googleapis.com/robot/v1/metadata/x509/your-service%40your-project.iam.gserviceaccount.com"
}
```

#### Step 3: Set Environment Variable in Laravel Cloud

In your Laravel Cloud dashboard:

1. Go to your application settings
2. Navigate to "Environment Variables" or "Secrets"
3. Add a new variable:
    - **Key**: `GOOGLE_CREDENTIALS_JSON`
    - **Value**: Paste the minified JSON from Step 2

#### Step 4: Deploy

Deploy your application. The service will automatically use the JSON credentials.

### Verification

After deployment, check your logs:

### Verification

After deployment, check your logs for:
```bash
# You should see this:
"Using Google Vision credentials from JSON environment variable"
```

**Run diagnostic:**
```bash
php artisan credentials:diagnose
```

### Troubleshooting

📖 **Complete troubleshooting guide:** [TROUBLESHOOTING_CREDENTIALS.md](TROUBLESHOOTING_CREDENTIALS.md)

**Quick reference:**

**Error: "OpenSSL unable to validate key"**  
→ Malformed JSON credentials. Run `./deploy-to-cloud.sh` and repaste.

**Error: "Invalid JSON in GOOGLE_CREDENTIALS_JSON: Syntax error"**  
→ Same fix - regenerate with deployment script and repaste properly.

**Error: "Credentials file not found at: /var/www/html/..."**  
→ Remove `GOOGLE_APPLICATION_CREDENTIALS` (file paths don't work in cloud).  
→ Use `GOOGLE_CREDENTIALS_JSON` instead.

**Error: "No Google Vision credentials configured"**  
→ Set `GOOGLE_CREDENTIALS_JSON` in environment variables.  
→ Make sure you removed `GOOGLE_APPLICATION_CREDENTIALS`.

## Other Platforms

### Heroku

```bash
heroku config:set GOOGLE_CREDENTIALS_JSON='{"type":"service_account",...}'
```

### DigitalOcean App Platform

Add environment variable in App settings:

- Key: `GOOGLE_CREDENTIALS_JSON`
- Value: Your minified JSON
- Encrypt: Yes

### Railway.app

Add in Variables section:

```
GOOGLE_CREDENTIALS_JSON={"type":"service_account",...}
```

### Forge / VPS

Add to your `.env` file on the server:

```bash
# In production .env
GOOGLE_CREDENTIALS_JSON='{"type":"service_account","project_id":"...",...}'
```

## Local Development

For local development, you can still use the file-based approach:

```bash
# .env
GOOGLE_APPLICATION_CREDENTIALS=storage/app/google-credentials.json
```

The service will automatically detect and use the appropriate method.

## Security Best Practices

1. ✅ **Never commit credentials** to version control
2. ✅ **Use environment variables** in production
3. ✅ **Encrypt credentials** in your deployment platform
4. ✅ **Rotate keys periodically** (every 90 days recommended)
5. ✅ **Use least privilege** - only grant necessary Cloud Vision API permissions
6. ✅ **Monitor usage** in Google Cloud Console

## Cost Optimization

Google Cloud Vision pricing (as of 2026):

- First 1,000 units/month: **FREE**
- 1,001 - 5,000,000 units: $1.50 per 1,000

For a typical receipt OCR app processing 10 receipts/day:

- Monthly requests: ~300
- Cost: **$0** (within free tier)

### Monitoring Usage

Check usage in Google Cloud Console:

1. Go to "APIs & Services" → "Dashboard"
2. Select "Cloud Vision API"
3. View usage metrics

Set up billing alerts to avoid surprises:

1. Go to "Billing" → "Budgets & alerts"
2. Create a budget for Cloud Vision API
3. Set alerts at 50%, 80%, 100% of budget
