<?php

namespace App\Http\Controllers;

use App\Models\Log;
use App\Models\User;
use App\Utils\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Google_Client;
use Facebook\facebook;

class AuthController extends Controller
{
    /**
     * Loggedin a single user via API
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
                'password' => 'required'
            ]);

            if ($validator->fails()) {
                Log::error(Message::AUTH_KO, __METHOD__, new $this, $request, null, json_encode($validator->errors()->toArray()));
                return $this->sendError(Message::AUTH_KO, $validator->errors()->toArray(), 400);
            }

            if (Auth::attempt([
                'email' => $request->email,
                'password' => $request->password
            ])) {

                $user = Auth::user();

                $token = $user->createToken('pinsecret')->accessToken;

                Log::info(Message::AUTH_OK, __METHOD__, $user, $request);

                return $this->sendResponse(
                    [
                        'user' => $user,
                        'token' => $token
                    ],
                    Message::AUTH_OK
                );
            }

            return $this->sendError(Message::AUTH_KO, [], 400);
        } catch (\Exception $ex) {
            Log::error(Message::AUTH_KO, __METHOD__, new $this, $request, $ex);

            return $this->sendError(Message::AUTH_KO, [$ex->getMessage()], 400);
        }
    }

    /**
     * Loggedin with google
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function google(Request $request)
    {
        $clientId = '942100922079-sp5o9iavfckmia0re19q4ptvpubhv4q7.apps.googleusercontent.com';
        try {
            $client = new Google_Client(['client_id' => $clientId]);
            $googleData = $client->verifyIdToken($request->get('token'));

            $userExists = User::where('email', $googleData['email'])->first();

            if ($userExists) {

                $token = $userExists->createToken('pinsecret')->accessToken;

                Log::info(Message::AUTH_OK, __METHOD__, $userExists, $request);
                return $this->sendResponse(
                    [
                        'user' => $userExists,
                        'token' => $token
                    ],
                    Message::AUTH_OK
                );
            } else {
                $user = User::create([
                    'email' => $googleData['email'],
                    'name' => $googleData['given_name'] . ' ' . $googleData['family_name'],
                    'password' => bcrypt('a\wzsxdcfgvbhjnkmxdfcgvbhjnm4'),
                    'role_id' => 3
                ]);

                $token = $user->createToken('pinsecret')->accessToken;

                Log::info(Message::CREATE_OK, __METHOD__, new $this, $request);
                return $this->sendResponse([
                    'user' => $user,
                    'token' => $token
                ], Message::CREATE_OK, 201);
            }
        } catch (\Exception $ex) {
            Log::error(Message::AUTH_KO, __METHOD__, new $this, $request, $ex);

            return $this->sendError(Message::AUTH_KO, [$ex->getMessage()], 400);
        }
    }

    /**
     * Loggedin with facebook
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function facebook(Request $request)
    {
        try {
            $fb = new Facebook([
                'app_id'                => config('fb.app_id'),
                'app_secret'            => config('fb.app_secret'),
                'default_graph_version' => 'v11.0',
            ]);
            $response = $fb->get('/me?fields=id,first_name,last_name,email,picture.type(large)', $request->get('token'));
            $fbUser = $response->getGraphUser();


            // $facebookToken =  $request->get('token');
            // $url =  "https://graph.facebook.com/v13.0/me?fields=id,first_name,last_name,email&access_token=$facebookToken";

            $response = Http::get($url);

            $userExists = User::where('email', $response['email'])->first();

            if ($userExists) {
                $token = $userExists->createToken('pinsecret')->accessToken;

                Log::info(Message::AUTH_OK, __METHOD__, $userExists, $request);
                return $this->sendResponse(
                    [
                        'user' => $userExists,
                        'token' => $token
                    ],
                    Message::AUTH_OK
                );
            } else {
                $user = User::create([
                    'email' => $response['email'],
                    'name' => $response['first_name'] . ' ' . $response['last_name'],
                    'password' => bcrypt('a\wzsxdcfgvbhjnkmxdfcgvbhjnm4'),
                    'role_id' => 3
                ]);

                $token = $user->createToken('pinsecret')->accessToken;

                Log::info(Message::CREATE_OK, __METHOD__, new $this, $request);
                return $this->sendResponse([
                    'user' => $user,
                    'token' => $token
                ], Message::CREATE_OK, 201);
            }
        } catch (\Exception $ex) {
            Log::error(Message::AUTH_KO, __METHOD__, new $this, $request, $ex);
            throw $ex;
            return $this->sendError(Message::AUTH_KO, [$ex->getMessage()], 400);
        }
    }
}
