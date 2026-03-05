# Google Cloud Vision Credentials

To use Google Cloud Vision for OCR, you need to place your service account JSON key file here.

## Setup Instructions:

1. **Download your Google Cloud service account key:**
    - Go to [Google Cloud Console](https://console.cloud.google.com/)
    - Navigate to IAM & Admin > Service Accounts
    - Create or select a service account with Cloud Vision API access
    - Click "Keys" > "Add Key" > "Create New Key" > JSON
    - Download the JSON file

2. **Place the credentials file:**

    ```bash
    # Copy your downloaded key to this location:
    cp ~/Downloads/your-project-xxxxx.json storage/app/google-credentials.json
    ```

3. **Update your .env file:**

    ```dotenv
    OCR_DRIVER=google_vision
    GOOGLE_APPLICATION_CREDENTIALS=storage/app/google-credentials.json
    ```

4. **Secure the file:**
    ```bash
    # Make sure the file is not tracked by Git
    chmod 600 storage/app/google-credentials.json
    ```

## File Location

The credentials file should be placed at:

```
/path/to/your/project/storage/app/google-credentials.json
```

## Alternative: Use Absolute Path

You can also use an absolute path in your `.env`:

```dotenv
GOOGLE_APPLICATION_CREDENTIALS=/absolute/path/to/service-account-key.json
```

## Security Note

**NEVER commit your credentials file to version control!**

The `.gitignore` file already excludes `storage/app/google-credentials.json`.
