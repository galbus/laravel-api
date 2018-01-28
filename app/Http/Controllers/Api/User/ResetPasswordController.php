<?php

namespace App\Http\Controllers\Api\User;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\ResetsPasswords;
use Illuminate\Support\Facades\Password;

class ResetPasswordController extends Controller
{
    //
    use ResetsPasswords;

    /**
     * Reset the given user's password.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function reset(Request $request)
    {
        $this->validate($request, $this->rules(), $this->validationErrorMessages());

        // Here we will attempt to reset the user's password. If it is successful we
        // will update the password on an actual user model and persist it to the
        // database. Otherwise we will parse the error and return the response.
        $response = $this->broker()->reset(
            $this->credentials($request), function ($user, $password) {
                $this->resetPassword($user, $password);
            }
        );

        if ($response == Password::PASSWORD_RESET) {
            $statusCode = 200;
            $data = [
                'message' => 'Password was reset.',
            ];
        }
        else {
            $statusCode = 500;

            if ($response == Password::INVALID_TOKEN || $response == Password::INVALID_PASSWORD || $response == Password::INVALID_USER) {
                $statusCode = 422;
            }

            $data = [
                'code' => $response,
                'message' => 'Could not reset password.'
            ];
        }


        return response()->json($data, $statusCode);
    }
}
