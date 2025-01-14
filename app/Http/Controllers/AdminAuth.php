<?php

namespace App\Http\Controllers;

use App\Admin;
use App\Http\Controllers\Controller;
use App\Mail\AdminResetPassword;
use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;
use Mail;

class AdminAuth extends Controller {
	public function login() {
		return view('admin.login');
	}

	public function dologin(Request $request) {
		$rememberme = $request->rememberme ? true : false;

		if (auth('admin')->attempt(['email' => $request->email, 'password' => $request->password], $rememberme)) {
			return redirect()->route('admin.index');
		}
		session()->flush('error', 'Incorrect Information Login');
		return redirect()->route('admin_login');
	}

	public function logout() {
		auth('admin')->logout();

		return redirect()->route('admin_login');
	}

	public function forgot_password() {
		return view('admin.forgot_password');
	}

	public function forgot_password_post() {
		$admin = Admin::where('email', request('email'))->first();

		if (!empty($admin)) {

			$token = app('auth.password.broker')->createToken($admin);

			$data = DB::table('password_resets')->insert([
				'email' => $admin->email,
				'token' => $token,
				'created_at' => Carbon::now(),
			]);

			Mail::to($admin->email)->send(new AdminResetPassword(['data' => $admin, 'token' => $token]));
			session()->flash('success', 'The Link Reset Send');
			return back();
		}

		return back();
	}

	public function reset_password($token) {
		$check_token = DB::table('password_resets')->where('token', $token)->where('created_at', '>', Carbon::now()->subHours(2))->first();

		if (!empty($check_token)) {
			return view('admin.reset_password', ['data' => $check_token]);
		} else {
			return redirect()->route('admin_forgot_password');
		}

	}

	public function reset_password_post($token) {

		$this->validate(request(), [
			'password' => 'required|confirmed',
			'password_confirmation' => 'required',
		], [], [
			'password' => 'Password',
			'password_confirmation' => 'Confirmation Password',
		]);

		$check_token = DB::table('password_resets')->where('token', $token)->where('created_at', '>', Carbon::now()->subHours(2))->first();

		if (!empty($check_token)) {
			$admin = Admin::where('email', $check_token->email)->update(['password' => bcrypt(request('password'))]);

			DB::table('password_resets')->where('email', $check_token->email)->delete();

			auth('admin')->attempt(['email' => request('email'), 'password' => request('password')], true);

			return redirect(route('admin_index'));
		} else {
			return redirect()->route('admin_forgot_password');
		}
	}
}
