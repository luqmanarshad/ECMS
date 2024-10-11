<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\SessionLogs;
use App\User;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Session;
use Illuminate\Support\Facades\Auth;
use App\Setting;
use App\Role;
use Illuminate\Validation\ValidationException;
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

    public function showLoginForm()
    {
        return view('auth.login');
    }

    protected function attemptLogin(Request $request)
    {
        $this->validate($request, [
            'email'    => 'required|email',
            'password' => 'required|min:3'
        ]);
        $active = '';
        $email  = \App\User::where(['email' => strtolower($request->email)])->first();
        if (!is_null($email)) {
            $active = \App\User::where(['email' => strtolower($request->email), 'active' => 1])->first();
        } else {
            throw ValidationException::withMessages([
                'error' => "Invalid Email Address or Password",
            ]);
        }
        if (is_null($active)) {
            throw ValidationException::withMessages([
                'error' => "User is Inactive.",
            ]);
        }


        $credentials = array(
            'email'    => $request->email,
            'password' => $request->password,
            'active'   => 1
        );
        if (Auth::attempt($credentials)) {
            $user         = Auth::user();
            $setting      = Setting::first();
            $role         = Role::where('id', $user->role_id)->first();
            $session_data = array(
                'admin_id'        => $user->id,
                'logged_in'       => TRUE,
                'admin_user_name' => ucfirst($user->first_name) . ' ' . $user->last_name,
                'role_id'         => $user->role_id,
                'role_name'       => $role->name,
                'subject_id'      => $user->subject_id,
                'type_id'         => $user->type_id,
                'setting'         => $setting,
            );
            Session::put('user_info', $session_data);
            /*--Save ip detail  -- */
            $ipLogs             = new SessionLogs;
            $ipLogs->user_id    = $user->id;
            $ipLogs->ip_address = $request->ip();
            $ipLogs->browser    = $request->header('User-Agent');
            $ipLogs->save();
            return true;
        } else {
            throw ValidationException::withMessages([
                'error' => "Invalid Email Address or Password New",
            ]);
        }

    }

    public function logout(Request $request)
    {
        //$this->guard()->logout();
        Auth::logout();
        Session::flush();
        return redirect('/login');
    }



}
