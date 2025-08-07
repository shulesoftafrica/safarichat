# Google Contacts Sync Setup Instructions

## Overview
This feature allows users to sync their Google contacts by simply logging in with their Gmail account. The implementation uses Google's People API with OAuth 2.0 for secure authentication.

## Setup Steps

### 1. Create Google Cloud Project
1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Create a new project or select existing one
3. Enable the **People API** for your project

### 2. Configure OAuth 2.0
1. Go to **APIs & Services** > **Credentials**
2. Click **Create Credentials** > **OAuth 2.0 Client IDs**
3. Configure OAuth consent screen:
   - Application type: Web application
   - Add your domain to authorized domains
   - Add scopes: `https://www.googleapis.com/auth/contacts.readonly`

### 3. Create API Credentials
1. **OAuth 2.0 Client ID:**
   - Application type: Web application
   - Authorized JavaScript origins: `https://yourdomain.com`
   - Authorized redirect URIs: `https://yourdomain.com/auth/google/callback`

2. **API Key:**
   - Create an API key (restrict to People API for security)

### 4. Configure Environment Variables
Add these variables to your `.env` file:

```env
# Google API Configuration
GOOGLE_CLIENT_ID=your_google_client_id_here
GOOGLE_CLIENT_SECRET=your_google_client_secret_here
GOOGLE_API_KEY=your_google_api_key_here
GOOGLE_REDIRECT_URI=https://yourdomain.com/auth/google/callback
```

### 5. Security Configuration
For production, make sure to:
- Restrict API key usage to your domain
- Configure OAuth consent screen properly
- Use HTTPS for all OAuth flows
- Regularly rotate API keys

## Features Implemented

### 🔐 Security Features
- **OAuth 2.0 Authentication**: Secure Google login flow
- **Read-Only Access**: Only contacts.readonly scope requested
- **No Password Storage**: Uses tokens, never stores passwords
- **HTTPS Required**: Secure communication with Google APIs

### 🚀 User Experience
- **One-Click Sync**: Simple "Sign in with Google" button
- **Real-time Feedback**: Progress indicators and status messages
- **Duplicate Prevention**: Automatic duplicate contact detection
- **Phone Validation**: Smart phone number formatting and validation

### 📱 Contact Processing
- **Smart Phone Formatting**: Automatically adds country codes
- **Data Validation**: Validates phone numbers and contact data
- **Selective Import**: Only imports contacts with valid phone numbers
- **Error Handling**: Graceful error handling for invalid data

## Usage Flow

1. User clicks "Sync from Google" button
2. Google OAuth popup opens for authentication
3. User grants permission to read contacts
4. System fetches contacts using People API
5. Contacts are processed and validated
6. Valid contacts are imported to guest list
7. User sees import results with count
8. Page refreshes to show new contacts

## API Endpoints Used

- **Google People API**: `https://people.googleapis.com/v1/people/me/connections`
- **OAuth 2.0**: `https://accounts.google.com/oauth2/auth`
- **Local Import Endpoint**: `/guest/importGoogleContacts`

## Error Handling

The system handles various error scenarios:
- Invalid or missing API credentials
- Network connectivity issues
- OAuth authentication failures
- API rate limiting
- Invalid contact data
- Duplicate contact prevention

## Testing

To test the integration:
1. Ensure Google API credentials are configured
2. Open guest management page
3. Click "Sync from Google" button
4. Complete OAuth flow
5. Verify contacts are imported correctly

## Troubleshooting

### Common Issues:
1. **"Google API not initialized"**: Check API credentials in .env
2. **"OAuth failed"**: Verify redirect URIs match exactly
3. **"No contacts imported"**: Check if contacts have phone numbers
4. **"Permission denied"**: Verify OAuth consent screen configuration

### Debug Mode:
Enable JavaScript console to see detailed error messages and API responses.
