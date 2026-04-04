<?php

namespace App\Http\Controllers\Marks;

use App\Http\Controllers\Admin\SectionMasterController;
use App\Http\Controllers\Controller;
use App\Models\Admin\SessionMaster;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class MarksController extends Controller
{

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
       return view('marks.default');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
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


    /**
     * Get Sections
     */
    public function getSections(Request $request){
        return SectionMasterController::getAllClassSectionsAjax($request);
    }


    /**
     * Get Students by class and section
     */
    public function getStudents(Request $request){
        try {
            $current_session = Session::get('marks_current_session');
            $validator = Validator::make($request->all(), [
                'session_id' => 'nullable|exists:session_masters,id,active,1',
                'class_id' => 'required',
                'section_id' => 'required',
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $validator->errors()
                ], 200);
            }
            $currentSession = isset($request->session_id) ? $request->session_id : $current_session->id;
            $ssid = isset($request->session_id) ? [1, 2, 3, 4, 5] : [1, 2];
            $classId = $request->class_id;
            $sectionId = $request->section_id;
            $baseQuery = DB::table('stu_main_srno')
                ->leftJoin('stu_detail', 'stu_main_srno.srno', '=', 'stu_detail.srno')
                ->leftJoin('parents_detail as parents', 'stu_main_srno.srno', '=', 'parents.srno')
                ->select(
                    'stu_main_srno.srno',
                    'stu_main_srno.rollno',
                    'stu_detail.name as student_name',
                    'parents.f_name as father_name',
                )
                ->where('stu_main_srno.active', 1)
                ->where('stu_main_srno.session_id', $currentSession)
                ->whereIn('stu_main_srno.ssid', $ssid)->orderBy('stu_main_srno.rollno', 'asc');

            if(!empty($classId) && $classId != 'all' && !empty($sectionId) && $sectionId != 'all'){
                $baseQuery->where('stu_main_srno.class', $classId)->where('stu_main_srno.section', $sectionId);
            }
            $data = $baseQuery->get();
            if ($data->isEmpty()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No student found for the selected class and section.',
                ], 200);
            }else {
                if ($classId == 'all' || $sectionId == 'all') {
                   return response()->json([
                    'status' => 'success',
                        'message' => 'Students fetched successfully.',
                        'data' => 'all'
                    ], 200);
                } else {
                    $data = $data->map(function ($item) {
                        return [
                            'srno' => $item->srno,
                            'rollno' => $item->rollno,
                            'display_name' => $item->rollno . ' - ' . $item->student_name . ' / ' . $item->father_name,
                            'student_name' => $item->student_name,
                            'father_name' => $item->father_name,
                        ];
                    })->values(); /* Reindex the collection */
                    return response()->json([
                        'status' => 'success',
                        'message' => 'Students fetched successfully.',
                        'data' => $data
                    ], 200);
                }
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => "Failed to get students.",
            ], 200);
        }
    }
}
