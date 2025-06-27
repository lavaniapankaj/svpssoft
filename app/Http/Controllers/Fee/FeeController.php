<?php

namespace App\Http\Controllers\Fee;

use App\Http\Controllers\Controller;
use App\Models\Admin\SessionMaster;
use Illuminate\Foundation\Auth\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class FeeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
        return view('fee.default');
    }
    /**
     * login(Fee Dashboard)
     */
    public function login(){
        return view('auth.login');
    }

    /**
     * change password
     */
    public function changePass(){
        return view('fee.change_pass');
    }
    public function changePassStore(Request $request){
        $request->validate([
            'old_user_name' => [
                'required',
                'max:255',
                Rule::exists('users', 'name')->where('id', $request->id),
            ],
            'old_user_pass' => [
                'required',
                'min:8',
                function ($attribute, $value, $fail) use ($request) {
                    // Validate the hashed password
                    $user = User::find($request->id);
                    if (!$user || !Hash::check($value, $user->password)) {
                        $fail('The provided old password is incorrect.');
                    }
                },
            ],
            'user_name' => [
                'required',
                'max:255',
                'unique:users,name',
                // 'unique:users,name,' . $request->id,
            ],
            'user_pass' => [
                'required_with:user_pass_confirmation',
                'same:user_pass_confirmation',
                Password::min(8)
                    ->mixedCase()
                    ->numbers()
                    ->symbols(),
                function ($attribute, $value, $fail) use ($request) {
                        // Validate the hashed password
                        $user = User::find($request->id);
                        if ($user && Hash::check($value, $user->password)) {
                            $fail('The provided new password should differ from the old password.');
                        }
                },
            ],
            'user_pass_confirmation' => 'required',
        ]);
        $user = User::find($request->id);
        if ($user) {
            User::where('id', $request->id)->update([
                'name' => $request->user_name,
                'password' => Hash::make($request->user_pass),
                'edit_user_id' => $request->id,
            ]);
            return redirect()->route('fee.changePass')->with('success', 'Password updated successfully.');
        } else {
            return redirect()->back()->with('error', 'Something went wrong, please try again.');
        }
    }
}
