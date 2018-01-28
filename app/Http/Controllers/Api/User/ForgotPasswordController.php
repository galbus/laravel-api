<?php

namespace App\Http\Controllers\Api\User;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\SendsPasswordResetEmails;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Log;
use Illuminate\Auth\Passwords\DatabaseTokenRepository;


class ForgotPasswordController extends Controller
{
    //
    use SendsPasswordResetEmails;

    private $resetToken = '';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
    }


    /**
     * Send a password reset link to a user.
     * This overrides the broker()->sendResetLink() so we can store the reset token
     *
     * @param  array  $credentials
     * @return string
     */
    public function sendResetLink(array $credentials)
    {
        // First we will check to see if we found a user at the given credentials
        $user = $this->broker()->getUser($credentials);

        if (is_null($user)) {
            return Password::INVALID_USER;
        }

        // Once we have the reset token, we are ready to send the message out to this
        // user with a link to reset their password.
        // Store token as class variable so we can return back to user for testing
        $this->resetToken = $this->broker()->getRepository()->create($user);

        $user->sendPasswordResetNotification(
            $this->resetToken
        );

        return Password::RESET_LINK_SENT;
    }

    /**
     * Send a reset link to the given user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function sendResetLinkEmail(Request $request)
    {
        $this->validateEmail($request);
        $data = $request->only('email');

        // We will send the password reset link to this user. Once we have attempted
        // to send the link, we will examine the response then see the message we
        // need to show to the user. Finally, we'll send out a proper response.
        // $response = $this->broker()->sendResetLink(
        //     $data
        // );
        $response = $this->sendResetLink(
            $data
        );

        if ($response == Password::RESET_LINK_SENT || $response == Password::INVALID_USER) {
           $statusCode = 200;
            $message = sprintf('If a matching account was found, an email was sent to %s to allow you to reset your password.',
                $data['email']
            );
        }
        else {
            $statusCode = 500;
            $message ='Could not send password reset email';
        }

        $data = [
            'message' => $message,
            // 'environment' => env('APP_ENV'),
        ];

        if (env('APP_ENV') == 'local' && $this->resetToken) {
            $data['token'] = $this->resetToken;
        }

        return response()->json($data, $statusCode);
    }
}
