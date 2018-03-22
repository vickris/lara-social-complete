<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/home';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    public function redirectToProvider($provider)
    {
        return Socialite::driver($provider)->redirect();
    }

    public function handleProviderCallback($provider)
    {
        try {
            $user = Socialite::driver($provider)->user();
        } catch (Exception $e) {
            return redirect('auth/'.$provider);
        }

        $authUser = $this->findOrCreateUser($user, $provider);
        Auth::login($authUser, true);
        return redirect($this->redirectTo);
    }


    public function findOrCreate($user, $provider)
    {
        $account = SocialIdentity::whereProviderName($provider)
                   ->whereProviderId($user->getId())
                   ->first();

        if ($account) {
            return $account->user;
        } else {
            $user = User::whereEmail($user->getEmail())->first();

            if (! $user) {
                $user = User::create([
                    'email' => $user->getEmail(),
                    'name'  => $user->getName(),
                ]);
            }

            $user->socialAccounts()->create([
                'provider_id'   => $user->getId(),
                'provider_name' => $provider,
            ]);

            return $user;
        }
    }

}
