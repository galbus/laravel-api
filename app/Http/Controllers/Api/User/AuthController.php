<?php

namespace App\Http\Controllers\Api\User;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Lib\AuthProxy;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cookie;

// http://esbenp.github.io/2017/03/19/modern-rest-api-laravel-part-4/

class AuthController extends Controller
{
    const REFRESH_TOKEN = 'refreshToken';
    private $authProxy = null;
    private $cookieLifetime = 60 * 60 * 24;

    private function getAuthProxy() {
        if ($this->authProxy === null) {
            $authProxyConfig = [
                'clientId' => env('AUTH_CLIENT_ID', ''),
                'clientSecret' => env('AUTH_CLIENT_SECRET', ''),
                'proxyBaseUrl' => env('AUTH_PROXY_BASE_URL', ''),
            ];
            $this->authProxy = new AuthProxy($authProxyConfig);
        }
        return $this->authProxy;
    }
    //
    /**
     * login with user credentials
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function login(Request $request)
    {
        $data = [
            'username' => $request->input('username'),
            'password' => $request->input('password'),
        ];

        $result = $this->getAuthProxy()->login($data);
        // Log::info($result);
        $cookie = $this->setRefreshTokenCookie($result['data']);
        unset($result['data']['refresh_token']);
        return response()->json($result['data'], $result['status'])->cookie($cookie);
    }


    private function setRefreshTokenCookie($result) {
        $token = '';
        if (!empty($result['refresh_token'])) {
            $token = $result['refresh_token'];
        }
        $secureCookie = (App::environment() === 'local') ? false : true;
        $path = null;
        $domain = null;
        $httpOnly = true;

        return Cookie::make(
            self::REFRESH_TOKEN,
            $token,
            $this->cookieLifetime, // 10 days
            $path,
            $domain,
            $secureCookie,
            $httpOnly // HttpOnly
        );
    }

    public function refreshToken(Request $request) {
        $user = $request->user();

        $data = [
            // 'refreshToken' => $request->input('refreshToken'),
            'refreshToken' => $request->cookie(self::REFRESH_TOKEN)
        ];

        $result = $this->getAuthProxy()->refreshToken($data);
        $cookie = $this->setRefreshTokenCookie($result['data']);
        unset($result['data']['refresh_token']);
        return response()->json($result['data'], $result['status'])->cookie($cookie);
    }

    /**
     * logout should revoke the access token
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function logout(Request $request)
    {
        // https://laracasts.com/discuss/channels/laravel/passport-how-can-i-manually-revoke-access-token
        $user = $request->user();
        $result = $this->getAuthProxy()->logout($user);
        Cookie::forget(self::REFRESH_TOKEN);

        $statusCode = 200;
        $statusMessage = 'OK';

        if (!$result) {
            $statusCode = 500;
            $statusMessage = 'ERROR';
        }

        $data = [
            'status' => $statusMessage,
        ];

        return response()->json($data, $statusCode);
    }
}
