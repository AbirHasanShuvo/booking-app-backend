<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function update(Request $request)
    {
        $user = auth('api')->user();

        $data = $request->validate([
            'name'  => 'sometimes|required|string|max:100',
            'email' => ['sometimes', 'required', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => 'nullable|string|max:20',
        ]);

        $user->update($data);

        return response()->json(['success' => true, 'message' => 'Profile updated', 'data' => $user->fresh()]);
    }

    public function changePassword(Request $request)
    {
        $user = auth('api')->user();

        $request->validate([
            'current_password' => 'required|string',
            'password'         => 'required|string|min:8|different:current_password',
        ]);

        if (! Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Current password is incorrect',
                'errors'  => ['current_password' => ['Current password is incorrect']],
            ], 422);
        }

        $user->update(['password' => $request->password]); // hashed by the model cast

        return response()->json(['success' => true, 'message' => 'Password changed', 'data' => null]);
    }
}