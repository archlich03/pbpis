# Microsoft Authentication Setup Guide

This guide will help you set up Microsoft Authentication for your POBIS application.

## Step 1: Register an Application in the Microsoft Azure Portal

1. Go to the [Azure Portal](https://portal.azure.com/)
2. Navigate to "Azure Active Directory" > "App registrations" > "New registration"
3. Enter the following information:
   - **Name**: POBIS Application (or your preferred name)
   - **Supported account types**: Accounts in any organizational directory (Any Azure AD directory - Multitenant) and personal Microsoft accounts
   - **Redirect URI**: Set the platform to "Web" and enter your callback URL (e.g., `https://your-domain.com/login/microsoft/callback` or for local development `http://localhost:8000/login/microsoft/callback`)
4. Click "Register"

## Step 2: Configure Authentication

1. In your newly created app, go to "Authentication"
2. Make sure the redirect URI is correctly set
3. Under "Implicit grant and hybrid flows", check "ID tokens"
4. Click "Save"

## Step 3: Create a Client Secret

1. Go to "Certificates & secrets" > "Client secrets" > "New client secret"
2. Add a description and select an expiration period
3. Click "Add"
4. **IMPORTANT**: Copy the secret value immediately as it won't be shown again

## Step 4: Note Your Application (Client) ID and Tenant ID

1. Go to the "Overview" page of your app registration
2. Note the "Application (client) ID" and "Directory (tenant) ID"

## Step 5: Configure Your .env File

Add the following variables to your `.env` file:

```
MSGRAPH_CLIENT_ID=your-client-id
MSGRAPH_SECRET_ID=your-client-secret
MSGRAPH_TENANT_ID=your-tenant-id
MSGRAPH_OAUTH_URL=login/microsoft/callback
MSGRAPH_LANDING_URL=/user/dashboard
```

Replace `your-client-id`, `your-client-secret`, and `your-tenant-id` with the values you obtained from the Azure Portal.

## Step 6: Clear Configuration Cache

Run the following command to clear the configuration cache:

```
sudo docker exec pobis php artisan config:clear
```

## Step 7: Test the Authentication

1. Visit your application's login page
2. Click on the "Microsoft" button
3. You should be redirected to Microsoft's login page
4. After logging in, you should be redirected back to your application and be logged in

## Troubleshooting

- If you encounter any issues, check the Laravel logs for more information
- Ensure that the redirect URI in the Azure Portal exactly matches the one in your application
- Make sure the client ID and secret are correctly entered in your `.env` file
- If you're testing locally, ensure that your application is accessible at the URL you specified in the redirect URI
