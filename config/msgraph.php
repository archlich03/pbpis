<?php

return [

    /*
    * the clientId is set from the Microsoft portal to identify the application
    * https://apps.dev.microsoft.com
    */
    'clientId' => env('MSGRAPH_CLIENT_ID', 'c14d9447-b88f-4d67-a797-e430242eb9c7'),

    /*
    * set the tenant id
    */
    'tenantId' => env('MSGRAPH_TENANT_ID', '82c51a82-548d-43ca-bcf9-bf4b7eb1d012'),

    /*
    * set the application secret id
    */

    'clientSecret' => env('MSGRAPH_SECRET_ID'),

    /*
    * Set the url to trigger the oauth process this url should call return MsGraph::connect();
    * IMPORTANT: This must be a full absolute URL and must match exactly what's configured in Azure
    */
    'redirectUri' => env('MSGRAPH_OAUTH_URL', 'http://localhost/login/microsoft/callback'),

    /*
    * set the url to be redirected to once the token has been saved
    */

    'msgraphLandingUri' => env('MSGRAPH_LANDING_URL'),

    /*
    set the tenant authorize url
    */

    'tenantUrlAuthorize' => env('MSGRAPH_TENANT_AUTHORIZE'),

    /*
    set the tenant token url
    */
    'tenantUrlAccessToken' => env('MSGRAPH_TENANT_TOKEN', 'https://login.microsoftonline.com/{tenant_id}/oauth2/v2.0/token'),

    /*
    set the authorize url
    */
    'urlAuthorize' => 'https://login.microsoftonline.com/'.env('MSGRAPH_TENANT_ID', 'common').'/oauth2/v2.0/authorize',

    /*
    set the token url
    */
    'urlAccessToken' => 'https://login.microsoftonline.com/'.env('MSGRAPH_TENANT_ID', 'common').'/oauth2/v2.0/token',

    /*
    set the resource url
    */
    'urlResourceOwnerDetails' => 'https://login.microsoftonline.com/'.env('MSGRAPH_TENANT_ID', 'common').'/oauth2/v2.0/resource',

    /*
    set the scopes to be used, Microsoft Graph API will accept up to 20 scopes
    */

    'scopes' => 'offline_access openid profile email user.read',

    /*
    The default timezone is set to Europe/London this option allows you to set your prefered timetime
    */
    'preferTimezone' => env('MSGRAPH_PREFER_TIMEZONE', 'outlook.timezone="Europe/London"'),

    /*
    set the database connection
    */
    'dbConnection' => env('MSGRAPH_DB_CONNECTION', 'mysql'),
];
