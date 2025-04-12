<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserInfo;
use App\Notifications\LoginNeedsVerification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class LoginController extends Controller
{
    public function submit(Request $request)
    {
        // validate the phone number
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|min:6'
        ]);

        // find a user model
        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Password is not correct.'], 401);
        }

        if (!$user) {
            return response()->json([
                'message' => `The account doesn't exist`
            ], 404);
        }

        $token = $user->createToken('auth_token')->plainTextToken;
        $user->load('user_info');

        return response()->json([
            'message' => "Login is successfully.",
            'token' => $token,
            'user' => $user
        ]);
    }

    public function register(Request $request)
    {
        // validate the phone number
        $request->validate([
            'name' => 'required|min:2',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6'
        ]);

        // find or create a user model
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'is_admin' => 0
        ]);

        UserInfo::create([
            'user_id' => $user->id
        ]);

        $user->load('user_info');


        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => "Register is successfully.",
            'user' => $user,
            'token' => $token
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logout is successfully.'
        ]);
    }
}
