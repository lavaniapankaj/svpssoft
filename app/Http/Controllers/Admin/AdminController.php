<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\SessionMaster;
use App\Models\User;
use App\Models\LoginLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Admin\SessionMasterController;
use App\Http\Controllers\Admin\ClassMasterController;
use App\Http\Controllers\Student\StudentMasterController;

class AdminController extends Controller
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
        return view('admin.default');
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
        return view('admin.change_pass');
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
            return redirect()->route('admin.changePass')->with('success', 'Password updated successfully.');
        } else {
            return redirect()->back()->with('error', 'Something went wrong, please try again.');
        }
    }

    public function signature()
    {
        return view('admin.signature.signature_upload');
    }

    public function uploadPrincipleSignature(Request $request)
    {
        $request->validate([
            'signature' => 'image|mimes:png|required|max:500'
        ]);

        if ($request->hasFile('signature')) {
            $destinationPath = public_path('admin/images');

            // Delete any existing images in the folder
            // $existingFiles = glob($destinationPath . '/*.{jpeg,jpg,png,svg}', GLOB_BRACE);
            $existingFiles = glob($destinationPath . '/principle_signature.{jpeg,jpg,png,svg}', GLOB_BRACE);
            foreach ($existingFiles as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }

            // $imageName = time() . '.' . $request->signature->getClientOriginalExtension();
            $imageName = 'principle_signature' . '.' . $request->signature->getClientOriginalExtension();
            $request->signature->move($destinationPath, $imageName);

            return redirect()->route('admin.signature')->with('success', 'Principle Signature uploaded successfully.');
        }

        return redirect()->route('admin.signature')->with('error', 'No file uploaded.');
    }

    /**
     * Left out std
     */

    public function leftOutStd(Request $request)
    {

        $student = [];
        if ($request->session !== '' && $request->class !== '' && $request->section !== '') {
            $fields = [
                'stu_main_srno.id',
                'stu_main_srno.srno',
                'stu_main_srno.school',
                'stu_main_srno.class',
                'stu_main_srno.section',
                'stu_main_srno.session_id',
                'stu_main_srno.ssid',
                'stu_main_srno.active',
                'class_masters.class as class_name',
                'section_masters.section as section_name',
                'stu_detail.name as student_name',
                'parents_detail.f_name',
            ];
            $where = [
                'where' => [
                    'stu_main_srno.session_id' => $request->session,
                    'stu_main_srno.class' => $request->class,
                    'stu_main_srno.section' => $request->section,
                ]
            ];
            $student =  StudentMasterController::getStd($fields, $where)->paginate(10);
        }
        $sessions = SessionMasterController::getSessions();
        $classes = ClassMasterController::getClasses();
        return view('admin.left_out_std.index', compact('student', 'classes', 'sessions'));
    }

    public function leftOutStdEdit($id)
    {
        if (!$id) {
            return redirect()->back()->with('error', 'Something went wrong, please try again.');
        }

        $student = DB::table('stu_main_srno')
            ->where('srno', $id)
            ->whereIn('active', [1, 2, 3, 4])
            ->whereIn('ssid', [4, 5])
            ->first();

        if (!$student) {
            return redirect()->route('admin.left-out-std.index')->with('error', 'This student is already active.');
        }

        //  $user = Auth::user();

        DB::table('stu_main_srno')
            ->where('srno', $id)
            ->whereIn('active', [1, 2, 3, 4])
            ->whereIn('ssid', [4, 5])
            ->update([
                'active' => 1,
                'ssid' => 1,
                'edit_user_id' => Session::get('login_user'),
            ]);

        return redirect()->route('admin.left-out-std.index')->with('success', 'Student activated successfully.');
    }

    /**
     * Login Logs
     */

    public function loginLogs(Request $request)
    {
        # code...
        $query = LoginLog::select('id', 'ip_address', 'panel', 'browser', 'user_name', 'date', 'success', 'password_attempt');
        if ($request->search !== '') {
            $query->where('user_name', 'LIKE', "%$request->search%");
        }
        if ($request->date !== '') {
            $query->whereDate(
                'date',
                'LIKE',
                "%$request->date%"
            );
        }
        $data = $query->orderBy('date', 'DESC')->paginate(10);
        return view('admin.login_logs.index', compact('data'));
    }
}
