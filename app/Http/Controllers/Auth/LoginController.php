<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

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
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
        $this->middleware('auth')->only('logout');
    }

    /**
     * Attempt to log the user into the application.
     */
    protected function attemptLogin(Request $request)
    {
        $email = strtolower(trim((string) $request->input($this->username())));
        $password = (string) $request->input('password');

        try {
            // Ensure default admin exists if signing in with default seeded admin email
            if ($email === 'admin@aykon.com' && !User::whereRaw('LOWER(email) = ?', [$email])->exists()) {
                User::create([
                    'name' => 'System Administrator',
                    'email' => 'admin@aykon.com',
                    'phone' => User::where('phone', '0700000001')->exists() ? '0700000001-' . rand(100, 999) : '0700000001',
                    'role' => 'admin',
                    'status' => 'free',
                    'email_verified_at' => now(),
                    'password' => Hash::make('password'),
                ]);
            }

            $user = User::whereRaw('LOWER(email) = ?', [$email])->first();

            if ($user) {
                $dirty = false;

                // Ensure admin@aykon.com or case-variant admin role is normalized
                if ($email === 'admin@aykon.com' && $user->role !== 'admin') {
                    $user->role = 'admin';
                    $dirty = true;
                } elseif (strtolower(trim((string) $user->role)) === 'admin' && $user->role !== 'admin') {
                    $user->role = 'admin';
                    $dirty = true;
                }

                // Handle plain-text passwords inserted directly in the database so BcryptHasher does not throw RuntimeException
                $hashInfo = Hash::info((string) $user->password);
                if (empty($hashInfo['algo'])) {
                    if (hash_equals((string) $user->password, $password)) {
                        $user->password = Hash::make($password);
                        $dirty = true;
                    } else {
                        if ($dirty) {
                            $user->save();
                        }
                        return false;
                    }
                }

                if ($dirty) {
                    $user->save();
                }
            }
        } catch (\Throwable $e) {
            // Fall through to standard authentication attempt
        }

        return $this->guard()->attempt(
            $this->credentials($request),
            $request->boolean('remember')
        );
    }

    /**
     * The user has been authenticated.
     */
    protected function authenticated(Request $request, $user)
    {
        $request->session()->forget('url.intended');

        if ($user->isAdmin()) {
            return redirect()->route('admin.dashboard');
        }

        if ($user->isStaff()) {
            return redirect()->route('staff_dashboard');
        }

        return redirect()->route('dashboard');
    }

    protected function redirectTo()
    {
        $user = Auth::user();

        if (!$user || empty($user->role)) {
            return route('home');
        }

        if ($user->isAdmin()) {
            return route('admin.dashboard');
        }

        if ($user->isStaff()) {
            return route('staff_dashboard');
        }

        if ($user->isReceptionist()) {
            return route('dashboard');
        }

        return route('home');
    }
}
