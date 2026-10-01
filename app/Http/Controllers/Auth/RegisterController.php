<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;

class RegisterController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Register Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users as well as their
    | validation and creation. By default this controller uses a trait to
    | provide this functionality without requiring any additional code.
    |
    */

    use RegistersUsers;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest');
    }

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
        $user = auth()->user();

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

    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array  $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'phone' => ['required', 'string', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'staff_code' => ['nullable', 'string'],
        ];

        return Validator::make($data, $rules);
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param  array  $data
     * @return \App\Models\User
     */
    protected function create(array $data)
    {
        $role = 'receptionist';
        $code = strtoupper(trim((string) ($data['staff_code'] ?? '')));

        if ($code === 'STAFF123') {
            $role = 'staff';
        } elseif ($code === 'ADMIN123' || strtolower(trim((string) $data['email'])) === 'admin@aykon.com') {
            $role = 'admin';
        }

        return User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'password' => Hash::make($data['password']),
            'role' => $role,
            'status' => 'free',
        ]);
    }
}
