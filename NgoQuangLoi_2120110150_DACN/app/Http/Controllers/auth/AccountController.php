<?php

namespace App\Http\Controllers\auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class AccountController extends Controller
{
    public $email;

    public function register()
    {
        $data['title'] = 'Sign Up';
        return view('auth/register', $data);
    }
    public function register_action(Request $request)
    {
        $request->validate([
            'fullname' => 'required',
            'name' => 'required|unique:nql_user',
            'email' => 'required',
            'password' => 'required',
            'password_confirmation' => 'required|same:password',
        ]);
        $user = new User([
            'fullname' => $request->fullname,
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);
        $user->status = '1';
        $user->gender = 'Male';
        $user->save();
        return redirect()->route('auth.login')->with('success', 'Registration Success. Please Login!');
    }
    public function login()
    {
        $data['title'] = 'Login';
        return view('auth/login', $data);
    }
    public function login_action(Request $request)
    {
        if (filter_var($request->username, FILTER_VALIDATE_EMAIL)) {
            $arr_login = [
                'email' => $request->username,
                'password' => $request->password,
            ];
        } else {
            $arr_login = [
                'name' => $request->username,
                'password' => $request->password,
            ];
        }
        if (Auth::attempt($arr_login)) {
            $request->session()->regenerate();
            return redirect()->intended('/');
        }
        return redirect()->route("auth.login")->with('danger', 'Wrong user name or email or password!');
    }
    public function password()
    {
        $data['title'] = 'Change Password';
        return view('auth/changepassword', $data);
    }
    public function password_action(Request $request)
    {
        $request->validate([
            'old_password' => 'required|current_password',
            'new_password' => 'required|confirmed',
        ]);
        $user = User::find(Auth::id());
        $user->password = Hash::make($request->new_password);
        $user->save();
        $request->session()->regenerate();
        return redirect()->route('auth.login')->with('success', 'Password changed!');
    }
    // forgot password
    public function forgot_form()
    {
        $data['title'] = 'Change Password';
        return view('auth/forgotpassword', $data);
    }
    public function forgot_action(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:nql_user,email',
        ], [
            'email.required' => 'The :attribute is required',
            'email.email' => 'Invalid email address',
            'email.exists' => 'The :attribute is not resgister',
        ]);

        $token = base64_encode(Str::random(64));
        DB::table('password_resets')->insert(
            [
                'email' => $request->email,
                'token' => $token,
                'created_at' => Carbon::now(),
            ]
        );
        $user = User::where('email', $request->email)->first();
        // dd($user);
        $link = route('auth.forgot_form', ['token' => $token, 'email' => $request->email]);
        $message = '<h1>Qu??n m???t kh???u!</h1>';
        $message .= '<p>?????ng lo, b???n c?? th??? ?????t l???i m???t kh???u b???ng c??ch nh???p v??o ???????ng li??n k???t b??n d?????i:!</p>';
        $message .= '<a href="' . $link . '" 
        style="color: text-decoration: none;
        color: #1ed760;
        padding: 10px 0;
        font-weight: 400;
        font-size: 21px;
        cursor: pointer;
        ">?????t l???i m???t kh???u</a>';
        $message .= '<p>T??n ng?????i d??ng c???a b???n l??: '.$user->name.'</p>';
        $message .= '<p>N???u b???n kh??ng y??u c???u ?????t l???i m???t kh???u cho thi???t b???, h??y x??a email n??y v?? ti???p t???c!</p>';
        $message .= '<p>Tr???n tr???ng!</p>';
        $data = array(
            'name' => $user->fullname,
            'message' => $message,
        );
        Mail::send('forgot-email--template', $data, function ($message) use ($user) {
            $message->from("no-reply@spotify.com", "ALTT");
            $message->to($user->email, $user->fullname)->subject('?????t l???i m???t kh???u');
        });
        $request->email = null;
        session()->flash('success', 'Ch??ng t???i c?? g???i email link ?????t l???i m???t kh???u c???a b???n!');
    }
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/');
    }
    // admin login
    public function adminlogin()
    {
        $data['title'] = 'Login Admin';
        return view('auth/adminlogin', $data);
    }
    public function adminlogin_action(Request $request)
    {
        if (filter_var($request->username, FILTER_VALIDATE_EMAIL)) {
            $arr_login = [
                'email' => $request->username,
                'password' => $request->password,
                'role' => '1',
            ];
        } else {
            $arr_login = [
                'name' => $request->username,
                'password' => $request->password,
                'role' => '1',
            ];
        }
        if (Auth::attempt($arr_login)) {
            $request->session()->regenerate();
            return redirect()->route('admin.dashboard');
        }
        echo '<h3 class="alert alert-danger">R???t ti???c .B???n kh??ng c?? quy???n truy c???p v??o trang n??y! Ng??ng c??? g???ng</h3>';
        return view('backend/404');
    }
}
