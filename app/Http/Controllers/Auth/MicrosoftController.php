<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class MicrosoftController extends Controller
{
    protected $client;
    protected $clientId;
    protected $clientSecret;
    protected $tenantId;
    protected $redirectUri;
    protected $scopes;
    
    /**
     * Constructor to initialize properties
     */
    public function __construct()
    {
        $this->client = new Client();
        $this->clientId = config('msgraph.clientId');
        $this->clientSecret = config('msgraph.clientSecret');
        $this->tenantId = config('msgraph.tenantId');
        $this->redirectUri = config('msgraph.redirectUri');
        $this->scopes = config('msgraph.scopes');
    }
    
    /**
     * Redirect to Microsoft OAuth login page
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function redirect()
    {
        Log::info('Starting Microsoft OAuth redirect');
        
        try {
            // Store a state token in the session to prevent CSRF attacks
            $state = Str::random(40);
            session(['microsoft_auth_state' => $state]);
            
            // Build the authorization URL
            $authUrl = 'https://login.microsoftonline.com/' . $this->tenantId . '/oauth2/v2.0/authorize';
            $authUrl .= '?client_id=' . $this->clientId;
            $authUrl .= '&response_type=code';
            $authUrl .= '&redirect_uri=' . urlencode($this->redirectUri);
            $authUrl .= '&response_mode=query';
            $authUrl .= '&scope=' . urlencode($this->scopes);
            $authUrl .= '&state=' . $state;
            
            Log::info('Redirecting to Microsoft OAuth URL', ['url' => $authUrl]);
            
            return redirect()->away($authUrl);
        } catch (\Exception $e) {
            Log::error('Error in Microsoft redirect', ['error' => $e->getMessage()]);
            return redirect()->route('login')->with('error', __('Failed to connect to Microsoft: :error', ['error' => $e->getMessage()]));
        }
    }

    /**
     * Handle the callback from Microsoft OAuth
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function callback(Request $request)
    {
        Log::info('Microsoft OAuth callback received', ['query' => $request->query()]);
        
        // Apply rate limiting for Microsoft authentication attempts
        $throttleKey = 'microsoft-auth:' . $request->ip();
        if (RateLimiter::tooManyAttempts($throttleKey, 10)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            $minutes = ceil($seconds / 60);
            return redirect()->route('login')
                ->with('error', __('Too many Microsoft authentication attempts. Please try again in :minutes minutes.', ['minutes' => $minutes]));
        }
        
        try {
            // Verify state token to prevent CSRF attacks
            if ($request->state !== session('microsoft_auth_state')) {
                // Hit rate limiter for invalid state token (potential attack)
                RateLimiter::hit($throttleKey, 1800);
                
                Log::error('Invalid state token', [
                    'received' => $request->state, 
                    'expected' => session('microsoft_auth_state')
                ]);
                return redirect()->route('login')
                    ->with('error', __('Invalid state token. Please try again.'));
            }
            
            // Clear the state token after successful validation
            session()->forget('microsoft_auth_state');
            
            // Check for error response
            if ($request->has('error')) {
                // Hit rate limiter for OAuth errors
                RateLimiter::hit($throttleKey, 1800);
                
                Log::error('Microsoft OAuth error', [
                    'error' => $request->error, 
                    'description' => $request->error_description
                ]);
                return redirect()->route('login')
                    ->with('error', __('Microsoft authentication error: :description', ['description' => $request->error_description]));
            }
            
            // Exchange authorization code for access token
            try {
                $tokenResponse = $this->getAccessToken($request->code);
                Log::info('Access token retrieved');
            } catch (GuzzleException $e) {
                Log::error('Failed to get access token', ['error' => $e->getMessage()]);
                return redirect()->route('login')
                    ->with('error', __('Failed to authenticate with Microsoft. Please try again.'));
            }
            
            // Get user info from ID token claims instead of Graph API
            try {
                $msGraphData = $this->getUserInfoFromIdToken($tokenResponse['id_token']);
                
                // Verify we have the Microsoft ID
                if (!isset($msGraphData['oid']) || empty($msGraphData['oid'])) {
                    Log::error('Microsoft ID missing in ID token', ['data' => $msGraphData]);
                    return redirect()->route('login')
                        ->with('error', __('Could not retrieve Microsoft ID. Please try again.'));
                }
                
                Log::info('Microsoft user data retrieved from ID token', [
                    'display_name' => $msGraphData['name'] ?? 'Unknown',
                    'ms_id' => $msGraphData['oid']
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to decode ID token', ['error' => $e->getMessage()]);
                return redirect()->route('login')
                    ->with('error', __('Failed to retrieve your Microsoft account information. Please try again.'));
            }
            
            // Get email from ID token claims
            $email = $msGraphData['email'] ?? $msGraphData['preferred_username'] ?? $msGraphData['upn'] ?? null;
            
            if (!$email) {
                Log::error('No email found in Microsoft data');
                return redirect()->route('login')
                    ->with('error', __('Could not retrieve email from Microsoft account.'));
            }
            
            // Validate and sanitize Microsoft data
            $email = filter_var($email, FILTER_VALIDATE_EMAIL);
            if (!$email) {
                Log::error('Invalid email format from Microsoft', ['email' => $msGraphData['email'] ?? $msGraphData['preferred_username'] ?? 'null']);
                return redirect()->route('login')
                    ->with('error', __('Invalid email format from Microsoft account.'));
            }
            
            // Sanitize display name
            $displayName = isset($msGraphData['name']) ? strip_tags(trim($msGraphData['name'])) : null;
            if ($displayName && strlen($displayName) > 255) {
                $displayName = substr($displayName, 0, 255);
            }
            
            // Validate Microsoft ID (oid = object ID in Azure AD)
            $msId = isset($msGraphData['oid']) ? preg_replace('/[^a-zA-Z0-9\-]/', '', $msGraphData['oid']) : null;
            if ($msId && strlen($msId) > 255) {
                $msId = substr($msId, 0, 255);
            }
            
            Log::info('Processing Microsoft login for email', ['email' => $email]);
            
            // Regular login flow - pass sanitized data
            $sanitizedData = [
                'id' => $msId,
                'displayName' => $displayName,
                'mail' => $email,
                'userPrincipalName' => $email
            ];
            $user = $this->processUserLogin($email, $sanitizedData, $tokenResponse);
            
            // Log the user in
            Auth::login($user);
            
            // Regenerate session to prevent session fixation attacks
            $request->session()->regenerate();
            
            // Clear rate limiting on successful authentication
            RateLimiter::clear($throttleKey);
            
            // Log successful Microsoft login
            AuditLog::log(
                $user->user_id,
                'microsoft_login',
                $request->ip(),
                $request->userAgent(),
                ['microsoft_id' => $msId]
            );
            
            Log::info('User logged in successfully', ['user_id' => $user->user_id]);
            
            return redirect()->route('dashboard');
            
        } catch (\Exception $e) {
            // Hit rate limiter on failed authentication attempts
            RateLimiter::hit($throttleKey, 1800); // 30-minute decay
            
            Log::error('Error in Microsoft callback', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return redirect()->route('login')
                ->with('error', __('Authentication failed: :error', ['error' => $e->getMessage()]));
        }
    }
    
    /**
     * Exchange authorization code for access token
     *
     * @param string $code
     * @return array
     * @throws GuzzleException
     */
    protected function getAccessToken(string $code): array
    {
        $tokenUrl = 'https://login.microsoftonline.com/' . $this->tenantId . '/oauth2/v2.0/token';
        
        $response = $this->client->post($tokenUrl, [
            'form_params' => [
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'code' => $code,
                'redirect_uri' => $this->redirectUri,
                'grant_type' => 'authorization_code'
            ]
        ]);
        
        return json_decode((string) $response->getBody(), true);
    }
    
    /**
     * Get user info from Microsoft Graph API (DEPRECATED - kept for reference)
     *
     * @param string $accessToken
     * @return array
     * @throws GuzzleException
     */
    protected function getUserInfo(string $accessToken): array
    {
        $response = $this->client->get('https://graph.microsoft.com/v1.0/me', [
            'headers' => [
                'Authorization' => 'Bearer ' . $accessToken,
                'Accept' => 'application/json'
            ]
        ]);
        
        $userData = json_decode((string) $response->getBody(), true);
        
        // Log the Microsoft Graph API response for debugging
        Log::info('Microsoft Graph API response', [
            'id' => $userData['id'] ?? 'Not found',
            'displayName' => $userData['displayName'] ?? 'Not found',
            'email' => $userData['mail'] ?? $userData['userPrincipalName'] ?? 'Not found',
            'has_id' => isset($userData['id']),
            'response_keys' => array_keys($userData)
        ]);
        
        return $userData;
    }
    
    /**
     * Get user info from ID token claims (no Graph API call needed)
     *
     * @param string $idToken
     * @return array
     */
    protected function getUserInfoFromIdToken(string $idToken): array
    {
        // ID tokens are JWT tokens with 3 parts: header.payload.signature
        $parts = explode('.', $idToken);
        
        if (count($parts) !== 3) {
            throw new \Exception('Invalid ID token format');
        }
        
        // Decode the payload (second part)
        $payload = base64_decode(strtr($parts[1], '-_', '+/'));
        $claims = json_decode($payload, true);
        
        if (!$claims) {
            throw new \Exception('Failed to decode ID token payload');
        }
        
        // Log the ID token claims for debugging
        Log::info('ID token claims', [
            'oid' => $claims['oid'] ?? 'Not found',
            'name' => $claims['name'] ?? 'Not found',
            'email' => $claims['email'] ?? $claims['preferred_username'] ?? 'Not found',
            'has_oid' => isset($claims['oid']),
            'claim_keys' => array_keys($claims)
        ]);
        
        return $claims;
    }
    
    /**
     * Process user login or registration
     *
     * @param string $email
     * @param array $msGraphData
     * @param array $tokenResponse
     * @return User
     */
    protected function processUserLogin(string $email, array $msGraphData, array $tokenResponse): User
    {
        // Check if user with this email already exists
        $user = User::where('email', $email)->first();
        
        if ($user) {
            Log::info('Found existing user', ['user_id' => $user->user_id]);
            
            // Always update Microsoft ID (allows re-linking after unlinking)
            if (isset($msGraphData['id'])) {
                $user->ms_id = $msGraphData['id'];
                Log::info('Updated/re-linked Microsoft ID', ['ms_id' => $msGraphData['id']]);
            }
            
            // Auto-update user information from Active Directory
            if (isset($msGraphData['displayName'])) {
                $user->name = $msGraphData['displayName'];
                Log::info('Updated user name from AD', ['name' => $msGraphData['displayName']]);
            }
            
            // Save the updated user
            $user->save();
        } else {
            Log::info('Creating new user from Microsoft account');
            
            // Create new user with Microsoft information
            $user = new User();
            $userName = $msGraphData['displayName'] ?? 'Microsoft User';
            $user->name = $userName;
            $user->email = $email;
            $user->ms_id = $msGraphData['id'] ?? null;
            $user->password = Hash::make(Str::random(24)); // Generate a secure random password
            $user->role = 'Balsuojantysis'; // Default role
            $user->gender = User::detectGenderFromLithuanianName($userName); // Auto-detect gender
            $user->save();
            
            // Log account creation
            AuditLog::log(
                $user->user_id,
                'microsoft_account_created',
                request()->ip(),
                request()->userAgent() ?? 'Unknown',
                [
                    'name' => $userName,
                    'email' => $email,
                    'ms_id' => $msGraphData['id'] ?? null
                ]
            );
            
            Log::info('New user created', ['email' => $email]);
        }
        
        // Store Microsoft tokens in user record or session if needed
        // You can add code here to store the tokens if needed
        
        // Note: Login status is tracked via sessions table, not user model
        
        return $user;
    }
    
    // Disconnect method removed as we're now using the simpler approach
    /**
     * Disconnect from Microsoft
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function disconnect()
    {
        // You can add code here to revoke the Microsoft token if needed
        
        $user = Auth::user();
        
        if ($user) {
            $user->ms_id = null;
            $user->save();
            
            Log::info('User disconnected from Microsoft', ['user_id' => $user->user_id]);
        }
        
        return redirect()->route('dashboard')
            ->with('success', __('Successfully disconnected from Microsoft.'));
    }
}
