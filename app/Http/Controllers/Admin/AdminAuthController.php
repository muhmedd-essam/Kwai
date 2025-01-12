<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Support\Facades\Validator;
use App\Traits\WebTrait;
use Tymon\JWTAuth\Facades\JWTAuth;

class AdminAuthController extends Controller
{
    use WebTrait;

    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth:admin', ['except' => ['login']]);
    }

    /**
     * Get a JWT via given credentials.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function login()
    {
        $rules = [
            "email" => ['required', 'email'],
            "password" => ['required',],
        ];

        $credentials = request(['email', 'password']);
        $validator = Validator::make($credentials, $rules);

        if ($validator->fails()) {
            $code = $this->returnCodeAccordingToInput($validator);
            return $this->validationError($code, $validator);
        }


        //     $token = JWTAuth::attempt([
        //         "email" => $credentials->email,
        //         "password" => $credentials->password
        //     ]);

        // if(!empty($token)){
        //     $user =User::where('email', $credentials->email)->first();

        //     return response()->json([
        //         "status" => true,
        //         "message" => "User logged in succcessfully",
        //         "token" => [$token, $user]
        //     ]);
        // }

        // return response()->json([
        //     "status" => false,
        //     "message" => "Invalid details"
        // ]);

        if (!$token = auth('admin')->attempt($credentials)) {
            return $this->error('E001', 'خطأ في الإيميل أو كلمة السر');
        }

        $user = auth('admin')->user();
        $user->access_token = $token;
        $user->token_expires_in = auth('admin')->factory()->getTTL() * 60 * 2400;

        return $this->data($user);
    }

    /**
     * Get the authenticated User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function me()
    {
        $user = auth('admin')->user();
        return $this->data($user);
    }

    /**
     * Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        auth('admin')->logout(true);

        return $this->success();
    }

    /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh()
    {
        return $this->respondWithToken(auth('admin')->refresh());
    }

    /**
     * Get the token array structure.
     *
     * @param  string $token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function respondWithToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth('admin')->factory()->getTTL() * 60 * 2400,
        ]);
    }
}
