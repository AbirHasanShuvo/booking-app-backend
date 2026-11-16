<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $data = $request->validate([
            'name'     => 'required|string|max:100',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create($data);

        return $this->respondWithToken(
            auth('api')->login($user),
            $user,
            'Registered successfully',
            201
        );
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        if (! $token = auth('api')->attempt($credentials)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid email or password',
                'errors'  => (object) [],
            ], 401);
        }

        return $this->respondWithToken($token, auth('api')->user(), 'Login successful');
    }

    public function logout()
    {
        auth('api')->logout();

        return response()->json([
            'success' => true,
            'message' => 'Logged out',
            'data'    => null,
        ]);
    }

    public function me()
    {
        return response()->json([
            'success' => true,
            'message' => 'OK',
            'data'    => auth('api')->user(),
        ]);
    }

    private function respondWithToken($token, $user, $message, $status = 200)
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data'    => [
                'user'         => $user,
                'access_token' => $token,
                'token_type'   => 'bearer',
                'expires_in'   => auth('api')->factory()->getTTL() * 60,
            ],
        ], $status);
    }
}
