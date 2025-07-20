<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

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
            return redirect()->route('login')->with('error', 'Failed to connect to Microsoft: ' . $e->getMessage());
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
        
        try {
            // Verify state token to prevent CSRF attacks
            if ($request->state !== session('microsoft_auth_state')) {
                Log::error('Invalid state token', [
                    'received' => $request->state, 
                    'expected' => session('microsoft_auth_state')
                ]);
                return redirect()->route('login')
                    ->with('error', 'Invalid state token. Please try again.');
            }
            
            // Check for error response
            if ($request->has('error')) {
                Log::error('Microsoft OAuth error', [
                    'error' => $request->error, 
                    'description' => $request->error_description
                ]);
                return redirect()->route('login')
                    ->with('error', 'Microsoft authentication error: ' . $request->error_description);
            }
            
            // Exchange authorization code for access token
            try {
                $tokenResponse = $this->getAccessToken($request->code);
                Log::info('Access token retrieved');
            } catch (GuzzleException $e) {
                Log::error('Failed to get access token', ['error' => $e->getMessage()]);
                return redirect()->route('login')
                    ->with('error', 'Failed to authenticate with Microsoft. Please try again.');
            }
            
            // Get user info from Microsoft Graph API
            try {
                $msGraphData = $this->getUserInfo($tokenResponse['access_token']);
                Log::info('Microsoft Graph data retrieved', ['display_name' => $msGraphData['displayName'] ?? 'Unknown']);
            } catch (GuzzleException $e) {
                Log::error('Failed to get user info', ['error' => $e->getMessage()]);
                return redirect()->route('login')
                    ->with('error', 'Failed to retrieve your Microsoft account information. Please try again.');
            }
            
            // Get email from Microsoft account
            $email = $msGraphData['mail'] ?? $msGraphData['userPrincipalName'] ?? null;
            
            if (!$email) {
                Log::error('No email found in Microsoft data');
                return redirect()->route('login')
                    ->with('error', 'Could not retrieve email from Microsoft account.');
            }
            
            Log::info('Processing Microsoft login for email', ['email' => $email]);
            
            // Process user login/registration
            $user = $this->processUserLogin($email, $msGraphData, $tokenResponse);
            
            // Log the user in
            Auth::login($user);
            Log::info('User logged in successfully', ['user_id' => $user->user_id]);
            
            return redirect()->route('dashboard');
            
        } catch (\Exception $e) {
            Log::error('Error in Microsoft callback', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return redirect()->route('login')
                ->with('error', 'Authentication failed: ' . $e->getMessage());
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
     * Get user info from Microsoft Graph API
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
        
        return json_decode((string) $response->getBody(), true);
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
            
            // Update existing user with Microsoft ID if not already set
            if (empty($user->ms_id) && isset($msGraphData['id'])) {
                $user->ms_id = $msGraphData['id'];
            }
            
            // Auto-update user information from Active Directory
            if (isset($msGraphData['displayName'])) {
                $user->name = $msGraphData['displayName'];
                Log::info('Updated user name from AD', ['name' => $msGraphData['displayName']]);
            }
        } else {
            Log::info('Creating new user from Microsoft account');
            
            // Create new user with Microsoft information
            $user = new User();
            $user->name = $msGraphData['displayName'] ?? 'Microsoft User';
            $user->email = $email;
            $user->ms_id = $msGraphData['id'] ?? null;
            $user->password = Hash::make(Str::random(24)); // Generate a secure random password
            $user->role = 'Balsuojantysis'; // Default role
            $user->gender = false; // Default gender
            
            Log::info('New user created', ['email' => $email]);
        }
        
        // Store Microsoft tokens in user record or session if needed
        // You can add code here to store the tokens if needed
        
        // Update login status
        $user->isLoggedIn = true;
        $user->last_login = now();
        $user->save();
        
        return $user;
    }
    
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
            ->with('success', 'Successfully disconnected from Microsoft.');
    }
}
