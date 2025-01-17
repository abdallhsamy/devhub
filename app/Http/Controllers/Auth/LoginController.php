<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Auth;
use Hash;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\RedirectResponse;
use Laravel\Socialite\Facades\Socialite;
use Str;

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
    protected $redirectTo = '/';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    public function github()
    {
        return Socialite::driver('github')->redirect();
    }

    public function githubRedirect(): RedirectResponse
    {
        $user = Socialite::driver('github')->user();

        $auth = User::firstOrCreate(
            [
                'github_id' => $user->id,
            ],
            [
                'name'              => $user->name ?? '',
                'description'       => $user->user['bio'],
                'email'             => $user->email,
                'username'          => $user->nickname,
                'avatar'            => $user->id.'.jpeg',
                'github_id'         => $user->id,
                'website'           => $user->user['blog'],
                'twitter'           => $user->user['twitter_username'],
                'email_verified_at' => \Carbon::now()->toDateTimeString(),
                'password'          => Hash::make(Str::random(24)),
            ]
        );
        $auth->github = $user->user['login'];
        $auth->twitter = $user->user['twitter_username'];
        $auth->company = $user->user['company'];
        $auth->save();
        if ($auth->wasRecentlyCreated) {
            $auth->addMediaFromUrl($user->avatar)->toMediaCollection('avatars');
        }
        Auth::login($auth, true);

        return back();
    }
}
