<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Laravel\Socialite\Facades\Socialite;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Http\Request;

class GoogleAuthController extends Controller
{
   
    // Redirect ke Google
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->stateless()->redirect();
    }

    // Handle callback dari Google
    public function handleGoogleCallback(Request $request)
    {
        try {
            // Dapatkan user info dari Google
            $googleUser = Socialite::driver('google')->stateless()->user();
            
            // Cari atau buat user
            $user = User::where('email', $googleUser->email)->first();
            
            if (!$user) {
                $user = User::create([
                    'name' => $googleUser->name,
                    'email' => $googleUser->email,
                    'google_id' => $googleUser->id,
                    'password' => bcrypt(Str::random(16)), // Random password
                ]);
            } else {
                // Update google_id jika belum ada
                if (!$user->google_id) {
                    $user->update(['google_id' => $googleUser->id]);
                }
            }
            
            // Buat token untuk API
            $token = $user->createToken('google-auth-token')->plainTextToken;
            
            return response()->json([
                'success' => true,
                'message' => 'Login berhasil',
                'user' => $user,
                'token' => $token
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Login gagal',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    // Login dengan Google ID Token
    public function loginWithGoogleToken(Request $request)
    {
        $request->validate([
            'token' => 'required|string'
        ]);
        
        try {
            // Verifikasi token dengan Google
            $client = new \Google_Client(['client_id' => config('services.google.client_id')]);
            $payload = $client->verifyIdToken($request->token);
            
            if (!$payload) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token tidak valid'
                ], 401);
            }
            
            // Cari atau buat user
            $user = User::where('email', $payload['email'])->first();
            
            if (!$user) {
                $user = User::create([
                    'name' => $payload['name'],
                    'email' => $payload['email'],
                    'google_id' => $payload['sub'],
                    'password' => bcrypt(Str::random(16)),
                ]);
            }
            
            $token = $user->createToken('google-auth-token')->plainTextToken;
            
            return response()->json([
                'success' => true,
                'message' => 'Login berhasil',
                'user' => $user,
                'token' => $token
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Login gagal',
                'error' => $e->getMessage()
            ], 500);
        }
    }

}
