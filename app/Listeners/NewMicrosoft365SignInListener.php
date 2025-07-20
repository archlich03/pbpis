<?php

namespace App\Listeners;

use App\Models\User;
use Dcblogdev\MsGraph\MsGraph;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Exception;

class NewMicrosoft365SignInListener
{
    public function handle(object $event): void
    {
        Log::info('Microsoft authentication event triggered');
        
        try {
            // Get email from Microsoft account
            $email = $event->token['info']['mail'] ?? $event->token['info']['userPrincipalName'] ?? null;
            
            if (!$email) {
                Log::error('No email found in Microsoft token');
                return;
            }
            
            Log::info('Processing Microsoft login for email', ['email' => $email]);
            
            // Check if user with this email already exists
            $user = User::where('email', $email)->first();
            
            if ($user) {
                Log::info('Found existing user', ['user_id' => $user->user_id]);
                
                // Update existing user with Microsoft ID if not already set
                if (empty($user->ms_id) && isset($event->token['info']['id'])) {
                    $user->ms_id = $event->token['info']['id'];
                }
                
                // Auto-update user information from Active Directory
                $this->updateUserInfoFromAD($user, $event->token['info']);
                
                $user->save();
            } else {
                Log::info('Creating new user from Microsoft account');
                
                // Create new user with Microsoft information
                $user = new User();
                $user->name = $event->token['info']['displayName'] ?? 'Microsoft User';
                $user->email = $email;
                $user->ms_id = $event->token['info']['id'] ?? null;
                $user->password = Hash::make(Str::random(24)); // Generate a secure random password
                $user->role = 'Balsuojantysis'; // Default role
                $user->gender = false; // Default gender
                $user->save();
                
                Log::info('New user created successfully', ['user_id' => $user->user_id]);
            }
            
            // Update login status
            $user->isLoggedIn = true;
            $user->last_login = now();
            $user->save();

            // Store Microsoft Graph token
            (new MsGraph)->storeToken(
                $event->token['accessToken'],
                $event->token['refreshToken'],
                $event->token['expires'],
                $user->user_id, // Use the correct primary key
                $user->email
            );

            // Log the user in
            Auth::login($user);
            Log::info('User logged in successfully', ['user_id' => $user->user_id]);
            
        } catch (Exception $e) {
            Log::error('Error in Microsoft authentication', ['error' => $e->getMessage()]);
        }
    }
    
    /**
     * Update user information from Active Directory
     *
     * @param User $user
     * @param array $msInfo
     * @return void
     */
    private function updateUserInfoFromAD(User $user, array $msInfo): void
    {
        try {
            // Update basic user information if available
            if (isset($msInfo['displayName'])) {
                $user->name = $msInfo['displayName'];
            }
            
            // You can add more fields to update here as needed
            // For example, job title, department, etc.
            
            Log::info('Updated user information from Active Directory', ['user_id' => $user->user_id]);
        } catch (Exception $e) {
            Log::error('Failed to update user info from AD', ['error' => $e->getMessage()]);
        }
    }
}
