<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use JWTAuth;
use TymonJWTAuthExceptionsJWTException;
use App\User;
use App\Role;

class AuthenticateController extends Controller
{
    public function __construct()
    {
        $this->middleware('jwt.auth', ['except' => ['login', 'register']]);
    }

    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        try {
            if (!$token = JWTAuth::attempt($credentials)) {
                return response()->json(['error' => 'Invalid credentials.'], Response::HTTP_UNAUTHORIZED);
            }
        } catch (JWTException $e) {
            return response()->json(['error' => 'could_not_create_token'], Response::HTTP_NOT_IMPLEMENTED);
        }

        return response()->json(compact('token'));
    }

    public function register(Request $request)
    {
        $credentials = $request->only('name', 'email', 'password');

        if (!isset($credentials['name']) || !isset($credentials['email']) || !isset($credentials['password']))
            return response()->json(['error' => 'Not enough fields.'], Response::HTTP_CONFLICT);

        try {
            $user = User::create([
                'name' => $credentials['name'],
                'email' => $credentials['email'],
                'password' => bcrypt($request['password']),
            ]);
            $userRole = Role::where('name', '=', 'user')->first();
            $user->attachRole($userRole);
        } catch (\Exception $e) {
            return response()->json(['error' => 'User already exists.'], Response::HTTP_CONFLICT);
        }

        $token = JWTAuth::fromUser($user);

        return response()->json(compact('token'));
    }
}
