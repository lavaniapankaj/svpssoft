<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Admin\ClassMaster;
use App\Models\Admin\DistrictMaster;
use App\Models\Admin\SectionMaster;
use App\Models\Admin\SessionMaster;

use App\Models\Admin\StateMaster;
use App\Models\Admin\TransportMaster;
use App\Models\Student\StudentMaster;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use App\Http\Controllers\Admin\ClassMasterController;
use App\Http\Controllers\Admin\StateMasterController;
use Illuminate\Support\Facades\Session;

class StudentMasterController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->input('search');
        if ($search !== '') {
            $baseQuery = DB::table('stu_main_srno')
                ->select(
                    'stu_main_srno.id',
                    'stu_main_srno.srno',
                    'stu_main_srno.school',
                    'stu_main_srno.class',
                    'stu_main_srno.section',
                    'stu_detail.name as student_name',
                    'class_masters.class as class_name',
                    'section_masters.section as section_name',
                    'parents_detail.f_name',
                    'parents_detail.m_name'
                )
                ->leftJoin('stu_detail', 'stu_main_srno.srno', '=', 'stu_detail.srno')
                ->leftJoin('parents_detail', 'stu_main_srno.srno', '=', 'parents_detail.srno')
                ->leftJoin('class_masters', 'stu_main_srno.class', '=', 'class_masters.id')
                ->leftJoin('section_masters', 'stu_main_srno.section', '=', 'section_masters.id')
                ->whereIn('ssid', [1, 2, 3, 4, 5]);

            $baseQuery->where(function ($q) use ($search) {
                $q->where('stu_main_srno.srno', 'LIKE', "%{$search}%")
                    ->orWhere('stu_detail.name', 'LIKE', "%{$search}%");
            });
        }

        $data = $baseQuery->orderBy('stu_main_srno.created_at', 'DESC')->paginate(10);
        // $maxPlaySchool = DB::table('stu_main_srno')->where('stu_main_srno.school', 1)->max('srno');
        $maxPlaySchool = DB::table(DB::raw('(select distinct srno, created_at from stu_main_srno where school = 1 order by created_at desc) as subquery'))
            ->select('srno')->limit(1)->first();
        $playSchoolLatest = DB::table('stu_main_srno')
            ->select('stu_main_srno.srno', 'stu_detail.name', 'parents_detail.f_name')
            ->leftJoin('stu_detail', 'stu_main_srno.srno', '=', 'stu_detail.srno')
            ->leftJoin('parents_detail', 'stu_main_srno.srno', '=', 'parents_detail.srno')
            // ->where('stu_main_srno.school', 1)
            ->where('stu_main_srno.srno', $maxPlaySchool->srno)
            ->first();
        // $maxPublicSchool = DB::table('stu_main_srno')->where('stu_main_srno.school', 2)->max('srno');
        $maxPublicSchool = DB::table(DB::raw('(select distinct srno, created_at from stu_main_srno where school = 2 order by created_at desc) as subquery'))
            ->select('srno')->limit(1)->first();



        $publicSchoolLatest = DB::table('stu_main_srno')
            ->select('stu_main_srno.srno', 'stu_detail.name', 'parents_detail.f_name')
            ->leftJoin('stu_detail', 'stu_main_srno.srno', '=', 'stu_detail.srno')
            ->leftJoin('parents_detail', 'stu_main_srno.srno', '=', 'parents_detail.srno')
            // ->where('stu_main_srno.school', 2)
            ->where('stu_main_srno.srno', $maxPublicSchool->srno)
            ->first();

        $playSchoolLatestSrno = $playSchoolLatest ? $playSchoolLatest->srno : null;
        $playSchoolLatestName = $playSchoolLatest ? $playSchoolLatest->name : null;
        $playSchoolLatestFatherName = $playSchoolLatest ? $playSchoolLatest->f_name : null;

        $publicSchoolLatestSrno = $publicSchoolLatest ? $publicSchoolLatest->srno : null;
        $publicSchoolLatestName = $publicSchoolLatest ? $publicSchoolLatest->name : null;
        $publicSchoolLatestFatherName = $publicSchoolLatest ? $publicSchoolLatest->f_name : null;

        return view('student.registration.index', compact('data', 'playSchoolLatestSrno', 'playSchoolLatestName', 'playSchoolLatestFatherName', 'publicSchoolLatestSrno', 'publicSchoolLatestName', 'publicSchoolLatestFatherName'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
        $classes = ClassMasterController::getClasses();
        $states = StateMasterController::getAllStates();
        return view('student.registration.create', compact('classes', 'states'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $requiredFields = [
            'current_session',
            'srno',
            'school',
            'class',
            'section',
            'rollno',
            'gender',
            'religion',
            'name',
            'std_email',
            'mobile',
            'category_id',
            'state_id',
            'district_id',
            'pincode',
            'address',
            'f_name',
            'g_father',
            'm_name',
            'parent_category_id',
            'f_occupation',
            'm_occupation',
            'parent_email',
            'parent_state_id',
            'parent_district_id',
            'pin_code',
            'parent_address'
        ];
        $request->validate([
            'srno' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('stu_main_srno')->where(function ($query) use ($request) {
                    return $query->whereNotNull('admission_date')->whereNotNull('form_submit_date');
                }),
            ],
            'school' => 'nullable',
            'class' => 'nullable',
            'section' => 'nullable',
            'rollno' => [
                'nullable',
                'numeric',
                Rule::unique('stu_main_srno')
                    ->where(function ($query) use ($request) {
                        return $query->where('class', $request->class)
                            ->where('section', $request->section)->where('session_id', $request->current_session);
                    }),
            ],
            'transport' => 'nullable',
            'age_proof' => 'nullable',
            'gender' => 'nullable',
            'religion' => 'nullable',
            'admission_date' => 'nullable|date_format:Y-m-d',
            'prev_srno' => 'nullable|string|max:255',
            'trans_1st_inst' => 'nullable|numeric|min:0',
            'trans_2nd_inst' => 'nullable|numeric|min:0',
            'trans_total' => 'nullable|numeric|min:0',
            'trans_discount' => 'nullable|numeric|min:0',
            'reason' => 'nullable|string|max:255',
            'TCRefNo' => 'nullable|string|max:100',
            'state_id' => 'nullable|exists:state_masters,id',
            'district_id' => 'nullable|exists:district_masters,id',
            'category_id' => 'nullable',
            'address' => 'nullable|string|max:255',
            'name' => 'nullable|string|max:255',
            'dob' => 'nullable|date_format:Y-m-d',
            'mobile' => 'nullable|regex:/^[0-9]{10}$/',
            'pincode' => 'nullable|regex:/^[0-9]{6}$/',
            'pre_school' => 'nullable|string|max:255',
            'pre_class' => 'nullable|string|max:50',
            'f_name' => 'nullable|string|max:255',
            'm_name' => 'nullable|string|max:255',
            'g_father' => 'nullable|string|max:255',
            'parent_email' => 'nullable|email|max:255',
            'std_email' => 'nullable|email|max:255',
            'f_mobile' => 'nullable|string|regex:/^[0-9]{10}$/',
            'pin_code' => 'nullable|string|regex:/^[0-9]{6}$/',
            'f_occupation' => 'nullable|string|max:255',
            'm_occupation' => 'nullable|string|max:255',
            'm_mobile' => 'nullable|string|regex:/^[0-9]{10}$/',
        ], [
            'srno.max' => 'Serial number must not exceed 255 characters.',
            'srno.unique' => 'The serial number has already been taken.',

            'rollno.numeric' => 'Roll number must be a number.',
            'rollno.unique' => 'This roll number is already taken in the same class, section, and session.',

            'admission_date.date_format' => 'Admission date must be in the format YYYY-MM-DD.',
            'dob.date_format' => 'Date of birth must be in the format YYYY-MM-DD.',

            'mobile.regex' => 'Student\'s mobile number must be exactly 10 digits.',
            'f_mobile.regex' => 'Father\'s mobile number must be exactly 10 digits.',
            'm_mobile.regex' => 'Mother\'s mobile number must be exactly 10 digits.',
            'pin_code.regex' => 'Pin code must be exactly 6 digits.',
            'pincode.regex' => 'Pincode must be exactly 6 digits.',

            'std_email.email' => 'Please enter a valid email address for the student.',
            'parent_email.email' => 'Please enter a valid email address for the parent.',

            'prev_srno.max' => 'Previous school registration number must not exceed 255 characters.',
            'TCRefNo.max' => 'Transfer Certificate reference number must not exceed 100 characters.',

            'state_id.exists' => 'Selected state is invalid.',
            'district_id.exists' => 'Selected district is invalid.',

            'reason.max' => 'Reason must not exceed 255 characters.',
            'address.max' => 'Address must not exceed 255 characters.',
            'name.max' => 'Name must not exceed 255 characters.',
            'f_name.max' => 'Father\'s name must not exceed 255 characters.',
            'm_name.max' => 'Mother\'s name must not exceed 255 characters.',
            'g_father.max' => 'Guardian\'s name must not exceed 255 characters.',
            'f_occupation.max' => 'Father\'s occupation must not exceed 255 characters.',
            'm_occupation.max' => 'Mother\'s occupation must not exceed 255 characters.',
            'pre_school.max' => 'Previous school name must not exceed 255 characters.',
            'pre_class.max' => 'Previous class name must not exceed 50 characters.',

            'trans_1st_inst.numeric' => 'Transport first installment must be a number.',
            'trans_1st_inst.min' => 'Transport first installment must be at least 0.',
            'trans_2nd_inst.numeric' => 'Transport second installment must be a number.',
            'trans_2nd_inst.min' => 'Transport second installment must be at least 0.',
            'trans_total.numeric' => 'Total transport fee must be a number.',
            'trans_total.min' => 'Total transport fee must be at least 0.',
            'trans_discount.numeric' => 'Transport discount must be a number.',
            'trans_discount.min' => 'Transport discount must be at least 0.',
        ]);
        $allFieldsFilled = collect($requiredFields)->every(function ($field) use ($request) {
            return !empty($request->input($field));
        });

        $studentData = [
            'srno' => $request->srno,
            'school' => $request->school,
            'class' => $request->class,
            'section' => $request->section,
            'rollno' => $request->rollno,
            'session_id' => $request->current_session,
            'transport' => $request->transport,
            'age_proof' => $request->age_proof,
            'gender' => $request->gender,
            'religion' => $request->religion,
            'admission_date' => $request->admission_date,
            'prev_srno' => $request->prev_srno,
            'form_submit_date' => Carbon::now()->format('Y-m-d'),
            'trans_1st_inst' => $request->trans_1st_inst,
            'trans_2nd_inst' => $request->trans_2nd_inst,
            'trans_total' => $request->trans_total,
            'trans_discount' => $request->trans_discount,
            'reason' => $request->reason,
            'TCRefNo' => $request->TCRefNo,
            'add_user_id' => Session::get('login_user'),
            'edit_user_id' => Session::get('login_user'),
            'active' => $allFieldsFilled ? 1 : 0,
        ];


        $commonData = [
            'srno' => $request->srno,
            'add_user_id' => Session::get('login_user'),
            'edit_user_id' => Session::get('login_user'),
            'active' =>  1,
        ];
        $stuDetailData = array_merge($commonData, [
            'name' => $request->name,
            'dob' => $request->dob,
            'mobile' => $request->mobile,
            'email' => $request->std_email,
            'pincode' => $request->pincode,
            'pre_school' => $request->pre_school,
            'pre_class' => $request->pre_class,
            'category_id' => $request->category_id,
            'state_id' => $request->state_id,
            'district_id' => $request->district_id,
            'address' => $request->address,
        ]);


        $parentsDetailData = array_merge($commonData, [
            'f_name' => $request->f_name,
            'm_name' => $request->m_name,
            'g_father' => $request->g_father,
            'email' => $request->parent_email,
            'f_mobile' => $request->f_mobile,
            'pin_code' => $request->pin_code,
            'f_occupation' => $request->f_occupation,
            'm_occupation' => $request->m_occupation,
            'm_mobile' => $request->m_mobile,
            'category_id' => $request->parent_category_id,
            'state_id' => $request->parent_state_id,
            'district_id' => $request->parent_district_id,
            'address' => $request->parent_address,
        ]);
        $student = StudentMaster::Create($studentData);
        $stDetail = DB::table('stu_detail')->insert($stuDetailData);
        $parentDetail = DB::table('parents_detail')->insert($parentsDetailData);
        if ($student && $stDetail && $parentDetail) {
            return redirect()->route('student.student-master.index')->with('success', 'Student saved successfully.');
        } else {
            return redirect()->route('student.student-master.index')->with('error', 'Something went wrong.');
        }
    }


    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        if ($id) {
            # code...
            $student = StudentMaster::findOrFail($id);
            if ($student !== null) {
                # code...
                $parent_detail = DB::table('parents_detail')
                    ->where('srno', $student->srno)
                    ->where('active', 1)
                    ->first();

                $student_detail = DB::table('stu_detail')
                    ->where('srno', $student->srno)
                    ->where('active', 1)
                    ->first();
                $class = ClassMaster::where('id', $student->class)
                    ->where('active', 1)
                    ->first();
                $state = null;
                if ($student_detail) {
                    $state = StateMaster::where('id', $student_detail->state_id)
                        ->where('active', 1)
                        ->first();
                }
                if ($parent_detail && !$state) {
                    $state = StateMaster::where('id', $parent_detail->state_id)
                        ->where('active', 1)
                        ->first();
                }
                $section = null;
                if ($class) {
                    $section = SectionMaster::where('class_id', $student->class)
                        ->where('active', 1)
                        ->first();
                }
                $district = null;
                if ($state) {
                    $district = DistrictMaster::where('state_id', $state->id)
                        ->where('id', $student_detail ? $student_detail->district_id : null)
                        ->orWhere('id', $parent_detail ? $parent_detail->district_id : null)
                        ->where('active', 1)
                        ->first();
                }
                return view('student.registration.show', compact('student', 'section', 'parent_detail', 'student_detail', 'state', 'district', 'class'));
            }
        } else {
            return redirect()->back()->with('error', 'Something went wrong, please try again.');
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        if ($id) {
            # code...
            $student = StudentMaster::findOrFail($id);
            if ($student !== null) {
                # code...
                $parent_detail = DB::table('parents_detail')->where('srno', $student->srno)->where('active', 1)->first();
                $student_detail = DB::table('stu_detail')->where('srno', $student->srno)->where('active', 1)->first();
                $classes = ClassMasterController::getClasses();
                $states = StateMasterController::getAllStates();
                return view('student.registration.edit', compact('student', 'parent_detail', 'student_detail', 'classes', 'states'));
            }
        } else {
            return redirect()->back()->with('error', 'Something went wrong, please try again.');
        }
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
        $requiredFields = [
            'session',
            'srno',
            'school',
            'class',
            'section',
            'rollno',
            'gender',
            'religion',
            'name',
            'std_email',
            'mobile',
            'category_id',
            'state_id',
            'district_id',
            'pincode',
            'address',
            'f_name',
            'g_father',
            'm_name',
            'parent_category_id',
            'f_occupation',
            'm_occupation',
            'parent_email',
            'parent_state_id',
            'parent_district_id',
            'pin_code',
            'parent_address'
        ];
        $request->validate([
            'srno' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('stu_main_srno')
                    ->where(function ($query) use ($request) {
                        return $query->whereNotNull('admission_date')
                            ->whereNotNull('form_submit_date');
                        // ->orWhere('id', $request->id); // Add ID to be ignored conditionally
                    })->ignore($id),
            ],
            'school' => 'nullable',
            'class' => 'nullable',
            'section' => 'nullable',
            'rollno' => [
                'nullable',
                'numeric',
                Rule::unique('stu_main_srno')
                    ->where(function ($query) use ($request) {
                        return $query->where('class', $request->class)
                            ->where('section', $request->section)->where('session_id', $request->session);
                        // ->orWhere('id', $request->id); // Add ID to be ignored conditionally
                    })->ignore($id),
            ],
            'transport' => 'nullable',
            'age_proof' => 'nullable',
            'gender' => 'nullable',
            'religion' => 'nullable',
            'admission_date' => 'nullable|date_format:Y-m-d',
            'prev_srno' => 'nullable|string|max:255',
            'trans_1st_inst' => 'nullable|numeric|min:0',
            'trans_2nd_inst' => 'nullable|numeric|min:0',
            'trans_total' => 'nullable|numeric|min:0',
            'trans_discount' => 'nullable|numeric|min:0',
            'reason' => 'nullable|string|max:255',
            'TCRefNo' => 'nullable|string|max:100',
            'state_id' => 'nullable|exists:state_masters,id',
            'district_id' => 'nullable|exists:district_masters,id',
            'category_id' => 'nullable',
            'address' => 'nullable|string|max:255',
            'name' => 'nullable|string|max:255',
            'dob' => 'nullable|date_format:Y-m-d',
            'mobile' => 'nullable|regex:/^[0-9]{10}$/',
            'pincode' => 'nullable|regex:/^[0-9]{6}$/',
            'pre_school' => 'nullable|string|max:255',
            'pre_class' => 'nullable|string|max:50',
            'f_name' => 'nullable|string|max:255',
            'm_name' => 'nullable|string|max:255',
            'g_father' => 'nullable|string|max:255',
            'parent_email' => 'nullable|email|max:255',
            'std_email' => 'nullable|email|max:255',
            'f_mobile' => 'nullable|string|regex:/^[0-9]{10}$/',
            'pin_code' => 'nullable|string|regex:/^[0-9]{6}$/',
            'f_occupation' => 'nullable|string|max:255',
            'm_occupation' => 'nullable|string|max:255',
            'm_mobile' => 'nullable|string|regex:/^[0-9]{10}$/',
        ], [
            // Custom messages for srno and rollno
            'srno.max' => 'Serial number must not exceed 255 characters.',
            'srno.unique' => 'The serial number has already been used for a record with valid admission and form submission dates.',
            'rollno.numeric' => 'Roll number must be a number.',
            'rollno.unique' => 'This roll number is already assigned in the selected class, section, and session.',

            // Date formats
            'admission_date.date_format' => 'Admission date must be in the format YYYY-MM-DD.',
            'dob.date_format' => 'Date of birth must be in the format YYYY-MM-DD.',

            // Mobile number & pin code
            'mobile.regex' => 'Student\'s mobile number must be exactly 10 digits.',
            'f_mobile.regex' => 'Father\'s mobile number must be exactly 10 digits.',
            'm_mobile.regex' => 'Mother\'s mobile number must be exactly 10 digits.',
            'pincode.regex' => 'Pincode must be exactly 6 digits.',
            'pin_code.regex' => 'Pin code must be exactly 6 digits.',

            // Email validation
            'std_email.email' => 'Please enter a valid student email address.',
            'parent_email.email' => 'Please enter a valid parent email address.',

            // Numeric fields with min
            'trans_1st_inst.numeric' => 'Transport first installment must be a number.',
            'trans_1st_inst.min' => 'Transport first installment cannot be negative.',
            'trans_2nd_inst.numeric' => 'Transport second installment must be a number.',
            'trans_2nd_inst.min' => 'Transport second installment cannot be negative.',
            'trans_total.numeric' => 'Transport total must be a number.',
            'trans_total.min' => 'Transport total cannot be negative.',
            'trans_discount.numeric' => 'Transport discount must be a number.',
            'trans_discount.min' => 'Transport discount cannot be negative.',

            // Max lengths
            'prev_srno.max' => 'Previous SR number must not exceed 255 characters.',
            'reason.max' => 'Reason must not exceed 255 characters.',
            'TCRefNo.max' => 'Transfer Certificate Reference Number must not exceed 100 characters.',
            'address.max' => 'Address must not exceed 255 characters.',
            'name.max' => 'Name must not exceed 255 characters.',
            'f_name.max' => 'Father\'s name must not exceed 255 characters.',
            'm_name.max' => 'Mother\'s name must not exceed 255 characters.',
            'g_father.max' => 'Guardian\'s name must not exceed 255 characters.',
            'f_occupation.max' => 'Father\'s occupation must not exceed 255 characters.',
            'm_occupation.max' => 'Mother\'s occupation must not exceed 255 characters.',
            'pre_school.max' => 'Previous school name must not exceed 255 characters.',
            'pre_class.max' => 'Previous class must not exceed 50 characters.',
            'state_id.exists' => 'Please select a valid state.',
            'district_id.exists' => 'Please select a valid district.',
        ]);
        $allFieldsFilled = collect($requiredFields)->every(function ($field) use ($request) {
            return !empty($request->input($field));
        });

        $studentData = [
            'srno' => $request->srno,
            'school' => $request->school,
            'class' => $request->class,
            'section' => $request->section,
            'rollno' => $request->rollno,
            'session_id' => $request->session,
            'transport' => $request->transport,
            'age_proof' => $request->age_proof,
            'gender' => $request->gender,
            'religion' => $request->religion,
            'admission_date' => $request->admission_date,
            'prev_srno' => $request->prev_srno,
            'form_submit_date' => Carbon::now()->format('Y-m-d'),
            'trans_1st_inst' => $request->trans_1st_inst,
            'trans_2nd_inst' => $request->trans_2nd_inst,
            'trans_total' => $request->trans_total,
            'trans_discount' => $request->trans_discount,
            'reason' => $request->reason,
            'TCRefNo' => $request->TCRefNo,
            'edit_user_id' => Session::get('login_user'),
            'active' => $allFieldsFilled ? 1 : 0,
        ];


        $commonData = [
            'srno' => $request->srno,
            'add_user_id' => Session::get('login_user'),
            'edit_user_id' => Session::get('login_user'),
            'active' =>  1,
        ];
        $stuDetailData = array_merge($commonData, [
            'name' => $request->name,
            'dob' => $request->dob,
            'mobile' => $request->mobile,
            'email' => $request->std_email,
            'pincode' => $request->pincode,
            'pre_school' => $request->pre_school,
            'pre_class' => $request->pre_class,
            'category_id' => $request->category_id,
            'state_id' => $request->state_id,
            'district_id' => $request->district_id,
            'address' => $request->address,
        ]);


        $parentsDetailData = array_merge($commonData, [
            'f_name' => $request->f_name,
            'm_name' => $request->m_name,
            'g_father' => $request->g_father,
            'email' => $request->parent_email,
            'f_mobile' => $request->f_mobile,
            'pin_code' => $request->pin_code,
            'f_occupation' => $request->f_occupation,
            'm_occupation' => $request->m_occupation,
            'm_mobile' => $request->m_mobile,
            'category_id' => $request->parent_category_id,
            'state_id' => $request->parent_state_id,
            'district_id' => $request->parent_district_id,
            'address' => $request->parent_address,
        ]);

        $student = StudentMaster::where('id', $id)->update($studentData);
        $stDetail = DB::table('stu_detail')->updateOrInsert(['srno' => $request->srno], $stuDetailData);
        $parentDetail = DB::table('parents_detail')->updateOrInsert(['srno' => $request->srno], $parentsDetailData);

        if ($student || $stDetail || $parentDetail) {
            # code...
            return redirect()->route('student.student-master.index')->with('success', 'Student updated successfully.');
        } else {
            return redirect()->route('student.student-master.index')->with('error', 'Failed to update student.');
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }


    /**
     * with ssid
     */
    public static function getStdWithNames($isSSID = false, $field = [])
    {
        $fields = !empty($field) ? $field : [
            'stu_main_srno.id',
            'stu_main_srno.srno',
            'stu_main_srno.school',
            'stu_main_srno.class',
            'stu_main_srno.section',
            'stu_main_srno.prev_srno',
            'stu_main_srno.admission_date',
            'stu_main_srno.form_submit_date',
            'stu_main_srno.rollno',
            'stu_main_srno.relation_code',
            'stu_main_srno.transport',
            'stu_main_srno.trans_1st_inst',
            'stu_main_srno.trans_2nd_inst',
            'stu_main_srno.trans_discount',
            'stu_main_srno.trans_total',
            'stu_main_srno.age_proof',
            'stu_main_srno.session_id',
            'stu_main_srno.gender',
            'stu_main_srno.religion',
            'stu_main_srno.active',
            'class_masters.class as class_name',
            'class_masters.sort',
            'section_masters.section as section_name',
            'state_masters.name as state_name',
            'district_masters.name as district_name',
            'stu_detail.name as student_name',
            'stu_detail.mobile as student_mobile',
            'stu_detail.email as student_email',
            'stu_detail.dob',
            'stu_detail.address',
            'stu_detail.state_id',
            'stu_detail.district_id',
            'stu_detail.category_id as category',
            'parents_detail.f_name',
            'parents_detail.m_name',
            'parents_detail.g_father as g_f_name',
            'parents_detail.f_mobile',
            'parents_detail.m_mobile',
            'parents_detail.f_occupation',
            'parents_detail.m_occupation',
            'parents_detail.address as parent_address',
            'stu_main_srno.created_at',
            'stu_main_srno.ssid',
        ];

        $where = $isSSID == true ? [
            'whereIn' => ['stu_main_srno.ssid' => [1, 2, 4, 5]],
            'where' => ['stu_main_srno.active' => 1],
        ] : [
            'where' => ['stu_main_srno.active' => 1],

        ];
        $orderBy = ['class_masters.sort' => 'asc', 'stu_main_srno.rollno' => 'asc'];
        $baseQuery = self::getStd($fields, $where, $orderBy);
        return $baseQuery;
    }

    /**
     * without ssid
     */
    public function getStdWithNamesWithoutSSID()
    {
        $fields = [
            'stu_main_srno.id',
            'stu_main_srno.srno',
            'stu_main_srno.school',
            'stu_main_srno.class',
            'stu_main_srno.section',
            'stu_main_srno.prev_srno',
            'stu_main_srno.admission_date',
            'stu_main_srno.form_submit_date',
            'stu_main_srno.rollno',
            'stu_main_srno.relation_code',
            'stu_main_srno.trans_1st_inst',
            'stu_main_srno.trans_2nd_inst',
            'stu_main_srno.trans_discount',
            'stu_main_srno.trans_total',
            'stu_main_srno.age_proof',
            'stu_main_srno.session_id',
            'stu_main_srno.ssid',
            'stu_main_srno.gender',
            'stu_main_srno.religion',
            'stu_main_srno.active',
            'class_masters.class as class_name',
            'section_masters.section as section_name',
            'state_masters.name as state_name',
            'district_masters.name as district_name',
            'stu_detail.name as student_name',
            'stu_detail.mobile as student_mobile',
            'stu_detail.email as student_email',
            'stu_detail.dob',
            'stu_detail.address',
            'stu_detail.state_id',
            'stu_detail.district_id',
            'stu_detail.category_id as category',
            'parents_detail.f_name',
            'parents_detail.m_name',
            'parents_detail.g_father as g_f_name',
            'parents_detail.f_mobile',
            'parents_detail.m_mobile',
            'parents_detail.f_occupation',
            'parents_detail.m_occupation',
            'parents_detail.address as parent_address',
        ];
        $where = [
            'where' => ['stu_main_srno.active' => 1],
        ];
        $orderBy = ['stu_main_srno.rollno' => 'asc'];
        $baseQuery = self::getStd($fields, $where, $orderBy);
        return $baseQuery;
    }


    public function getStdNameFather(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'session_id' => 'nullable|exists:session_masters,id,active,1',
                'class_id' => 'nullable|exists:class_masters,id,active,1',
                'section_id' => 'nullable|exists:section_masters,id,active,1',
                'srno' => 'nullable|exists:stu_main_srno,srno',
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $validator->errors()
                ], 400);
            }
            //code...
            $currentSession = session('current_session')->id ?? null;
            // $baseQuery = self::getStdWithNames(true);
            $baseQuery = self::getStdWithNames(false);

            if (filled($request->class_id) && filled($request->section_id) && filled($request->session_id)) {
                // Explode and filter out empty values
                $classExplode = array_filter(explode(',', $request->class_id), fn($value) => $value !== '');
                $sectionExplode = array_filter(explode(',', $request->section_id), fn($value) => $value !== '');

                if (!empty($classExplode) && !empty($sectionExplode)) {
                    // Use whereIn if there are valid values
                    $baseQuery->whereIn('stu_main_srno.class', $classExplode)
                        ->whereIn('stu_main_srno.section', $sectionExplode)
                        ->where('stu_main_srno.session_id', $request->session_id);
                } else {
                    // Use simple where if no valid values after filtering
                    $baseQuery->where('stu_main_srno.class', $request->class_id)
                        ->where('stu_main_srno.section', $request->section_id)
                        ->where('stu_main_srno.session_id', $request->session_id);
                }
            }

            if (filled($request->srno)) {
                $baseQuery->where('stu_main_srno.srno', $request->srno);
            }

            if (filled($request->session_id)) {
                $baseQuery->where('stu_main_srno.session_id', $request->session_id);
            } else {
                $baseQuery->where('stu_main_srno.session_id', $currentSession);
            }

            $data = $request->page ? $baseQuery->paginate(10) : $baseQuery->get();
            return response()->json($data);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => "Failed to get students"
            ], 500);
        }
    }


    /**
     * get students with relative std
     */
    public function getStdWithRelativeStd(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'srno' => 'required|exists:stu_main_srno,srno',
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $validator->errors()
                ], 400);
            }
            //code...
            // $baseQuery = self::getStdWithNames(true)->get();
            $baseQuery = self::getStdWithNames(false)->get();
            $std = $baseQuery->where('srno', $request->srno)->first();
            if (!empty($std)) {
                $data = [$std];
                $relatives = $baseQuery->where('relation_code', $std->relation_code)->where('srno', '!=', $std->srno)->whereNotNull('relation_code')->all();
                if (!empty($relatives)) {
                    $data = array_merge($data, $relatives);
                    return response()->json([
                        'status' => 'success',
                        'message' => "Student With All Relatives ",
                        'data' => $data
                    ], 200);
                } else {
                    # code...
                    return response()->json([
                        'status' => 'success',
                        'message' => "Student Without Relatives ",
                        'data' => $data
                    ], 200);
                }
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => "Failed to get students"
            ], 500);
        }
    }

    /**
     * Get students with their relatives. for student panel
     */
    // public function getStdsWithRelativeStd(Request $request)
    // {
    //     try {
    //         // Validate the request input
    //         $validator = Validator::make($request->all(), [
    //             'srno' => 'required',
    //         ]);

    //         if ($validator->fails()) {
    //             return response()->json([
    //                 'status' => 'error',
    //                 'message' => $validator->errors()
    //             ], 400);
    //         }

    //         // Fetch the input srno(s)
    //         $srnoList = explode(',', $request->srno);

    //         // Get the base query of students
    //         $baseQuery = self::getStdWithNames(false);

    //         // Filter the base query by the provided srno(s)
    //         $students = $baseQuery->whereIn('stu_main_srno.srno', $srnoList)->get();
    //         if ($students->isEmpty()) {
    //             return response()->json([
    //                 'status' => 'error',
    //                 'message' => 'No student found with the provided srno(s).'
    //             ], 404);
    //         }

    //         $resultData = [];
    //         foreach ($students as $student) {
    //             $relatives = self::getStdWithNames(false)->where('relation_code', $student->relation_code)
    //                 ->where('stu_main_srno.srno', '!=', $student->srno)
    //                 ->whereNotNull('relation_code')
    //                 ->get();

    //             $studentData = [
    //                 'student' => $student,
    //                 'relatives' => $relatives
    //             ];
    //             $resultData[] = $studentData;
    //         }

    //         return response()->json([
    //             'status' => 'success',
    //             'message' => 'Students with their relatives fetched successfully.',
    //             'data' => $resultData
    //         ], 200);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'status' => 'error',
    //             'message' => 'Failed to get students'
    //         ], 500);
    //     }
    // }
    public function getStdsWithRelativeStd(Request $request)
    {
        try {
            // Validate the request input
            $validator = Validator::make($request->all(), [
                'srno' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $validator->errors()
                ], 400);
            }

            // Fetch the input srno(s)
            $srnoList = explode(',', $request->srno);

            // Get the base query of students
            $baseQuery = self::getStdWithNames(false);

            // Filter the base query by the provided srno(s)
            $students = $baseQuery->whereIn('stu_main_srno.srno', $srnoList)->get();
            if ($students->isEmpty()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No student found with the provided srno(s).'
                ], 404);
            }

            $resultData = [];
            $processedSrnos = []; // Array to track already included SRNOs

            foreach ($students as $student) {
                // Skip if the student has already been processed
                if (in_array($student->srno, $processedSrnos)) {
                    continue;
                }

                // Fetch relatives of the student
                $relatives = self::getStdWithNames(false)
                    ->where('relation_code', $student->relation_code)
                    ->where('stu_main_srno.srno', '!=', $student->srno)
                    ->whereNotNull('relation_code')
                    ->whereNotIn('stu_main_srno.srno', $processedSrnos) // Exclude already processed SRNOs
                    ->get();

                // Add the student and relatives to the result
                $studentData = [
                    'student' => $student,
                    'relatives' => $relatives
                ];
                $resultData[] = $studentData;

                // Mark the student and their relatives as processed
                $processedSrnos[] = $student->srno;
                foreach ($relatives as $relative) {
                    $processedSrnos[] = $relative->srno;
                }
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Students with their relatives fetched successfully.',
                'data' => $resultData
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get students'
            ], 500);
        }
    }


    /**
     * get student detail using srno without checking ssid
     */
    public function getStdWithSrno(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'srno' => 'required|exists:stu_main_srno,srno',
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $validator->errors()
                ], 400);
            }


            $baseQuery = self::getStdWithNames(false);

            $std = $baseQuery->where('stu_main_srno.srno', $request->srno)->get();

            if (!empty($std)) {
                $data = $std;
                return response()->json([
                    'status' => 'success',
                    'message' => "Student record with permoted",
                    'data' => $data
                ], 200);
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => "Failed to get students"
            ], 500);
        }
    }
    /**
     * search student section
     */

    public function search(Request $request)
    {
        $baseQuery = self::getStdWithNames(false)->orderBy('class_masters.sort', 'asc');
        // $baseQuery = $this->getStdWithNamesWithoutSSID();
        // $currentSession = session('current_session')->id;
        $currentSession = session('current_session')->id;
        $classes = ClassMasterController::getClasses(['id', 'class']);
        if (!empty($request->search)) {
            $search = $request->search;
            $baseQuery->where(function ($q) use ($search, $currentSession) {
                $q->where('stu_detail.name', 'LIKE', "%{$search}%")->whereIn('stu_main_srno.ssid', [1, 4, 5]);
                // $q->where('stu_detail.name', 'LIKE', "%{$search}%")->where('stu_main_srno.session_id', $currentSession);
            });
        }
        $data = $baseQuery->where('session_id', $currentSession)->whereIn('stu_main_srno.ssid', [1, 4, 5])->orderBy('class_masters.sort', 'asc')->paginate(10);
        $sessions = SessionMaster::where('id', '>=', $currentSession)->where('active', 1)->pluck('session', 'id');
        return view('admin.student.search', compact('data', 'sessions', 'classes'));
    }

    /**
     * search student section data save
     */

    public function searchStore(Request $request)
    {
        $validates = $request->validate([
            'admission_date' => 'required|date_format:Y-m-d',
            'session_id' => 'required|exists:session_masters,id,active,1',
            'class_id' => 'required|exists:class_masters,id,active,1',
            'section_id' => 'required|exists:section_masters,id,active,1',
        ]);
        if ($validates) {
            # code...
            $currentSession = session('current_session')->id ?? null;

            $student = StudentMaster::where('srno', $request->srno)->where('ssid', '<>', 1)->get();
            if ($student->count() > 0) {
                foreach ($student as $st) {
                    if ($st->session_id == $currentSession) {
                        # code...
                        return redirect()->back()->with('error', "There is a record of this Session Can not Update.");
                    } else {
                        $std = StudentMaster::where('srno', $request->srno)->where('ssid', 1)->update([
                            'form_submit_date' => $request->admission_date,
                            'class' => $request->class_id,
                            'section' => $request->section_id,
                            'session_id' => $request->session_id,
                            'edit_user_id' => Session::get('login_user'),
                        ]);
                        if (!$std) {
                            # code...

                            // Update failed
                            return redirect()->back()->with('error', 'Critical Error, Contact to Software Service Provider.');
                        } else {
                            return redirect()->route('admin.student-master.search')->with('success', 'Student Updated Successfully');
                        }
                    }
                }
            } else {
                // Record not found or not promoted
                return redirect()->back()->with('error', "This is not a Promoted record Can not Update.");
            }
        } else {
            # code...
            return redirect()->back()->with('error', 'Something went wrong, please try again.');
        }
    }
    /**
     * Academic fee details
     */
    public function searchStFeeDetails(Request $request)
    {
        try {
            // Validate input
            $validator = Validator::make($request->all(), [
                'srno' => 'required|string',
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $validator->errors()
                ], 400);
            }

            $srno = $request->srno;

            // Array to store the tables
            $tables = [];

            // Fetch student academic details along with session and class
            $fields = [
                'stu_main_srno.session_id',
                'stu_main_srno.class',
                'stu_main_srno.admission_date',
                'session_masters.session',
                'class_masters.class as classname',
            ];
            $where = [
                'where' => ['stu_main_srno.srno' => $srno],
            ];
            $academicDetails = self::getStd($fields, $where)->get();
            if ($academicDetails->count() > 0) {
                $feeDetails = [];

                foreach ($academicDetails as $academicRow) {
                    // Get payable fee details
                    $dpayable = DB::table('fee_masters')
                        ->where('session_id', $academicRow->session_id)
                        ->where('class_id', $academicRow->class)
                        ->first();

                    // Get paid fee details
                    $dpaid = DB::table('fee_details')
                        ->where('session_id', $academicRow->session_id)
                        ->where('srno', $srno)
                        ->where('active', 1)
                        ->where('academic_trans', 1)
                        ->selectRaw('SUM(amount) as total_paid')
                        ->first();

                    $payableAmount = 0;
                    $paidAmount = 0;
                    $dueAmount = 0;

                    // Calculate payable, paid, and due amounts
                    if ($dpayable) {
                        $payableAmount = $dpayable->admission_fee + $dpayable->inst_total;

                        if ($dpaid) {
                            $paidAmount = $dpaid->total_paid;
                        }

                        $dueAmount = $payableAmount - $paidAmount;
                    }

                    // Format the data for the table
                    $feeDetails[] = [
                        'session' => $academicRow->session,
                        'classname' => $academicRow->classname,
                        'payable_amount' => $payableAmount,
                        'paid_amount' => $paidAmount ?? 0,
                        'due_amount' => $dueAmount,
                    ];
                }

                if (count($feeDetails) > 0) {
                    $tables[] = [
                        'title' => 'Fee Details',
                        'headers' => ['Session', 'Class', 'Payable Amount (Rs.)', 'Paid Amount (Rs.)', 'Due Amount (Rs.)'],
                        'data' => $feeDetails
                    ];
                }
            }

            // Return the response
            return response()->json([
                'status' => 'success',
                'tables' => $tables
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => "Failed to get student previous fee details"
            ], 500);
        }
    }

    /**
     * Transport Fee Details
     */
    public function searchStTransportFeeDetails(Request $request)
    {
        try {
            // Validate input
            $validator = Validator::make($request->all(), [
                'srno' => 'required|string',
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $validator->errors()
                ], 400);
            }
            $srno = $request->srno;
            // Array to store the tables
            $tables = [
                'title' => 'Transport Fee Details',
                'headers' => ['Session', 'Class', 'Payable Amount (Rs.)', 'Paid Amount (Rs.)', 'Due Amount (Rs.)'],
                'data' => '[]'
            ];

            // Fetch student transport details along with session and class
            $fields = [
                'stu_main_srno.session_id',
                'stu_main_srno.class',
                'stu_main_srno.admission_date',
                'session_masters.session',
                'class_masters.class as classname',
                'stu_main_srno.transport',
                'stu_main_srno.trans_1st_inst',
                'stu_main_srno.trans_2nd_inst',
                'stu_main_srno.trans_total',
                'stu_main_srno.trans_discount',
            ];
            $where = [
                'where' => ['stu_main_srno.srno' => $srno],
            ];
            $transportDetails = self::getStd($fields, $where)->get();
            if ($transportDetails->count() > 0) {
                $transportFeeDetails = [];
                foreach ($transportDetails as $transportRow) {
                    // Get payable transport fee details
                    $dpayable = $transportRow->transport == 1 ? $transportRow->trans_total : 0;
                    // Get paid transport fee details
                    $dpaid = DB::table('fee_details')
                        ->where('session_id', $transportRow->session_id)
                        ->where('srno', $srno)
                        ->where('active', 1)
                        ->where('academic_trans', 2)
                        ->selectRaw('SUM(amount) as total_paid')
                        ->first();

                    $payableAmount = 0;
                    $paidAmount = 0;
                    $dueAmount = 0;
                    // Calculate payable, paid, and due amounts
                    if ($dpayable) {
                        $payableAmount = $dpayable;

                        if ($dpaid) {
                            $paidAmount = $dpaid->total_paid;
                        }

                        $dueAmount = $payableAmount - $paidAmount;
                    }
                    // Format the data for the table
                    $transportFeeDetails[] = [
                        'session' => $transportRow->session,
                        'classname' => $transportRow->classname,
                        'payable_amount' => $payableAmount,
                        'paid_amount' => $paidAmount ?? 0,
                        'due_amount' => $dueAmount,
                    ];

                    if (count($transportFeeDetails) > 0) {
                        $tables['data'] = $transportFeeDetails;
                    }
                }
                // Return the response
                return response()->json([
                    'status' => 'success',
                    'tables' => $tables
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => "Failed to get student session-wise transport fee details"
            ], 500);
        }
    }

    /**
     * student-report class-wise excel file
     */

    public function stdReportClassWiseExcel(Request $request)
    {
        try {
            $response = $this->getStdNameFather($request);
            // dd($response);

            if ($response->getStatusCode() !== 200) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to generate report: ' . $response->getContent()
                ], 500);
            }

            $decodedResponse = json_decode($response->getContent(), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return response()->json(['status' => 'error', 'message' => 'Invalid JSON response: ' . json_last_error_msg()], 500);
            }

            $reportData = $decodedResponse ?? null;

            if (!$reportData) {
                return response()->json(['status' => 'error', 'message' => 'No data found'], 404);
            }

            $fileName = 'student_report.csv';

            $output = fopen('php://memory', 'w');
            if ($output === false) {
                throw new \Exception('Failed to open output stream.');
            }
            // Set the CSV column headers

            $headers = ['Rollno.', 'Class', 'Section',    'Admission Date', 'SRNO', 'Name', "Father's Name", "Mother's Name", "Grand Father's Name", "DOB", "Address", 'Contact 1', 'Contact 2', 'Gender', 'Religion', 'Category'];
            fputcsv($output, $headers);

            foreach ($reportData as $row) {

                fputcsv($output, [
                    $row['rollno'],
                    $row['class_name'],
                    $row['section_name'],
                    $row['admission_date'],
                    $row['srno'],
                    $row['student_name'],
                    $row['f_name'],
                    $row['m_name'],
                    $row['g_f_name'],
                    $row['dob'],
                    $row['address'],
                    $row['f_mobile'],
                    $row['m_mobile'],
                    $row['gender'] == 1 ? 'Male' : ($row['gender'] == 2 ? 'Female' : ($row['gender'] == 3 ? "Other's" : '')),
                    $row['religion'] == 1 ? 'Hindu' : ($row['religion'] == 2 ? 'Muslim' : ($row['religion'] == 3 ? 'Christian' : 'Sikh')),
                    $row['category'] == 1 ? 'General' : ($row['category'] == 2 ? 'OBC' : ($row['category'] == 3 ? 'SC' : ($row['category'] == 4 ? 'ST' : 'BC'))),

                ]);
            }
            // Rewind the memory stream to the beginning
            rewind($output);

            // Get the contents of the memory stream (CSV content)
            $csvContent = stream_get_contents($output);

            // Close the memory stream


            fclose($output);

            return response($csvContent, 200)->header('Content-Type', 'text/csv')
                ->header('Content-Disposition', 'attachment; filename="' . $fileName . '"');
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => "Failed to export report"
            ], 500);
        }
    }

    /**
     * relative-wise Student report
     */

    public function stdRelativeWiseView()
    {
        $classes = ClassMasterController::getClasses();
        return view('student.std_report.relative_wise_report', compact('classes'));
    }



    /**
     * Get Student
     */
    public static function getStd($fields = [], $where = [], $orderBy = [], $isAllActive = false)
    {
        $query = DB::table('stu_main_srno')
            ->leftJoin('session_masters', 'stu_main_srno.session_id', '=', 'session_masters.id')
            ->leftJoin('stu_detail', 'stu_main_srno.srno', '=', 'stu_detail.srno')
            ->leftJoin('parents_detail', 'stu_main_srno.srno', '=', 'parents_detail.srno')
            ->leftJoin('class_masters', 'stu_main_srno.class', '=', 'class_masters.id')
            ->leftJoin('section_masters', 'stu_main_srno.section', '=', 'section_masters.id')
            ->leftJoin('state_masters', 'stu_detail.state_id', '=', 'state_masters.id')
            ->leftJoin('district_masters', 'stu_detail.district_id', '=', 'district_masters.id');
        // ->where('session_masters.active', 1)
        // ->where('stu_detail.active', 1)
        // ->where('parents_detail.active', 1)
        // ->where('class_masters.active', 1)
        // ->where('section_masters.active', 1)
        // ->where('state_masters.active', 1)
        // ->where('district_masters.active', 1);
        if ($isAllActive == false) {
            $query->where('session_masters.active', 1)
                ->where('stu_detail.active', 1)
                ->where('parents_detail.active', 1)
                ->where('class_masters.active', 1)
                ->where('section_masters.active', 1)
                ->where('state_masters.active', 1)
                ->where('district_masters.active', 1);
        }

        if (!empty($fields) && is_array($fields)) {
            $query->select($fields);
        } else {
            $query->select('stu_main_srno.*', 'stu_detail.*', 'parents_detail.*', 'class_masters.*', 'section_masters.*', 'state_masters.*', 'district_masters.*');
        }
        if (!empty($where) && is_array($where)) {
            foreach ($where as $whereAttr => $attr) {

                foreach ($attr as $field => $value) {

                    $query->$whereAttr($field, $value);
                }
                $query = $query;
                // dd($query->toSql());
            }
        }
        if (!empty($orderBy) && is_array($orderBy)) {

            foreach ($orderBy as $field => $value) {

                $query->orderBy($field, $value);
            }
        }
        return $query;
    }


    /** Get Students For marksheet */
    public static function getMarksheetStd($fields = [], $where = [], $orderBy = [], $isAllActive = false)
    {
        $query = DB::table('stu_main_srno')
            ->leftJoin('session_masters', 'stu_main_srno.session_id', '=', 'session_masters.id')
            ->leftJoin('stu_detail', 'stu_main_srno.srno', '=', 'stu_detail.srno')
            ->leftJoin('parents_detail', 'stu_main_srno.srno', '=', 'parents_detail.srno')
            ->leftJoin('class_masters', 'stu_main_srno.class', '=', 'class_masters.id')
            ->leftJoin('section_masters', 'stu_main_srno.section', '=', 'section_masters.id');
        if ($isAllActive == false) {
            $query->where('session_masters.active', 1)
                ->where('stu_detail.active', 1)
                ->where('parents_detail.active', 1)
                ->where('class_masters.active', 1)
                ->where('section_masters.active', 1);
        }

        if (!empty($fields) && is_array($fields)) {
            $query->select($fields);
        } else {
            $query->select('stu_main_srno.*', 'stu_detail.*', 'parents_detail.*', 'class_masters.*', 'section_masters.*', 'state_masters.*', 'district_masters.*');
        }
        if (!empty($where) && is_array($where)) {
            foreach ($where as $whereAttr => $attr) {

                foreach ($attr as $field => $value) {

                    $query->$whereAttr($field, $value);
                }
                $query = $query;
            }
        }
        if (!empty($orderBy) && is_array($orderBy)) {

            foreach ($orderBy as $field => $value) {

                $query->orderBy($field, $value);
            }
        }
        return $query;
    }




     /**
     * with ssid for marksheet
     */
    public static function getMarksheetStdWithNames($isSSID = false, $field = [])
    {
        $fields = !empty($field) ? $field : [
            'stu_main_srno.id',
            'stu_main_srno.srno',
            'stu_main_srno.school',
            'stu_main_srno.class',
            'stu_main_srno.section',
            'stu_main_srno.prev_srno',
            'stu_main_srno.admission_date',
            'stu_main_srno.form_submit_date',
            'stu_main_srno.rollno',
            'stu_main_srno.relation_code',
            'stu_main_srno.transport',
            'stu_main_srno.trans_1st_inst',
            'stu_main_srno.trans_2nd_inst',
            'stu_main_srno.trans_discount',
            'stu_main_srno.trans_total',
            'stu_main_srno.age_proof',
            'stu_main_srno.session_id',
            'stu_main_srno.gender',
            'stu_main_srno.religion',
            'stu_main_srno.active',
            'class_masters.class as class_name',
            'class_masters.sort',
            'section_masters.section as section_name',
            'stu_detail.name as student_name',
            'stu_detail.mobile as student_mobile',
            'stu_detail.email as student_email',
            'stu_detail.dob',
            'stu_detail.address',
            'stu_detail.state_id',
            'stu_detail.district_id',
            'stu_detail.category_id as category',
            'parents_detail.f_name',
            'parents_detail.m_name',
            'parents_detail.g_father as g_f_name',
            'parents_detail.f_mobile',
            'parents_detail.m_mobile',
            'parents_detail.f_occupation',
            'parents_detail.m_occupation',
            'parents_detail.address as parent_address',
            'stu_main_srno.created_at',
            'stu_main_srno.ssid',
        ];

        $where = $isSSID == true ? [
            'whereIn' => ['stu_main_srno.ssid' => [1, 2, 4, 5]],
            'where' => ['stu_main_srno.active' => 1],
        ] : [
            'where' => ['stu_main_srno.active' => 1],

        ];
        $orderBy = ['class_masters.sort' => 'asc', 'stu_main_srno.rollno' => 'asc'];
        $baseQuery = self::getMarksheetStd($fields, $where, $orderBy);
        return $baseQuery;
    }

}
