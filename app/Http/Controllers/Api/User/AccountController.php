<?php

namespace App\Http\Controllers\Api\User;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;

class AccountController extends Controller
{
    //
    /**
     */
    private function removeUserProps($user) {
      $fields = ['id', 'created_at', 'updated_at'];
      foreach ($fields as $f) {
        unset($user[$f]);
      }
      return $user;
    }

    /**
     * return user details
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        // https://laracasts.com/discuss/channels/laravel/passport-how-can-i-manually-revoke-access-token
        $user = $request->user();

        if (!$user) {
            $statusCode = 500;
            $data = [
                'message' => 'ERROR',
            ];
        }
        else {
            $statusCode = 200;
            $data = $this->removeUserProps($user);

        }

        return response()->json($data, $statusCode);
    }


    /**
     * Get the password reset validation rules.
     *
     * @return array
     */
    protected function updateValidationRules()
    {
        return [
            'name' => 'required',
            'email' => 'required|email',
        ];
    }

    /**
     * update with user credentials
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        $this->validate($request, $this->updateValidationRules(), []);

        $user = $request->user();

        if (!$user) {
            $statusCode = 500;
            $data = [
                'message' => 'ERROR',
            ];
        }
        else {
            $user->email = $request->input('email');
            $user->name = $request->input('name');
            $user->save();

            $statusCode = 200;
            $data = $this->removeUserProps($user);
        }

        return response()->json($data, $statusCode);
    }
}
