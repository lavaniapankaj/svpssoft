<?php

namespace App\Http\Controllers\Marks;

use App\Http\Controllers\Controller;
use App\Models\Admin\SessionMaster;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class MarksController extends Controller
{

    // public function __construct()
    // {
    //     $this->middleware('auth');
    // }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
        return view('marks.default');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    public function login()
    {
        return view('auth.login');
    }

    public function changePass()
    {
        return view('marks.change_pass');
    }
    public function changePassStore(Request $request)
    {
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
            return redirect()->route('marks.changePass')->with('success', 'Password updated successfully.');
        } else {
            return redirect()->back()->with('error', 'Something went wrong, please try again.');
        }
    }
}
