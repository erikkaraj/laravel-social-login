- Install on API
  composer require google/apiclient

- Controller 
    use Google_Client;
    
    public function google(Request $request)
    {
        $clientId = '`12345678910-2039ir23rs89u4o8ru3oruuu349qruq394ru93u.apps.googleusercontent.com';
        try {
            $client = new Google_Client(['client_id' => $clientId]);
            $googleData = $client->verifyIdToken($request->get('token'));

            if ($googleData) {
                dd($googleData);
            }
            return $this->unauthorized([
                'message' => 'Invalid token',
            ]);
        } catch (\Exception $e) {
            return $this->unauthorized([
                'message' => $e->getMessage(),
            ]);
        }
    }

- Install on API
    composer require facebook/graph-sdk

- Controller 
    use Facebook\Facebook;
    
    public function facebook(Request $request): JsonResponse
    {
        try {
            $fb = new Facebook([
                'app_id'                => config('fb.app_id'),
                'app_secret'            => config('fb.app_secret'),
                'default_graph_version' => 'v11.0',
            ]);
            $response = $fb->get('/me?fields=id,first_name,last_name,email,picture.type(large)', $request->get('token'));
            $fbUser = $response->getGraphUser();

            /** @var User $user */
            $user = User::query()
                        ->where('email', $fbUser['email'])
                        ->first();
            if (!$user) {
                dd($fbUser);
            }

            return response()->json([
                'token' => $user->createToken('api')->accessToken,
                'user'  => $this->item($user, $this->transformer, 'user')
                                ->getData()->data,
            ]);
        } catch (Throwable $e) {
            return $this->unauthorized([
                'message' => $e->getMessage(),
            ]);
        }
    }
