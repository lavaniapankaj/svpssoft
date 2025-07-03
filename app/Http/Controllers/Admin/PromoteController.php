<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\SessionMaster;
use App\Models\Student\StudentMaster;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Session;
use App\Http\Controllers\Admin\SessionMasterController;
use App\Http\Controllers\Admin\ClassMasterController;

class PromoteController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $currentSession = Session::get('current_session')->id;
        if ($currentSession) {
            # code...
            // $maxPlaySchool = DB::table('stu_main_srno')->where('stu_main_srno.school', 1)->max('srno');

            // $maxPublicSchool = DB::table('stu_main_srno')->where('stu_main_srno.school', 2)->max('srno');
            $maxPublicSchool =  DB::table(DB::raw('(select distinct srno, created_at from stu_main_srno where school = 2 order by created_at desc) as subquery'))
            ->select('srno')->limit(1)->first();

            $publicSchoolSrno = DB::table('stu_main_srno')->select('stu_main_srno.srno')->where('stu_main_srno.srno', $maxPublicSchool->srno)->first();
            $publicSchoolSrnoLatest = isset($publicSchoolSrno) ? $publicSchoolSrno->srno : null;


            $maxPlaySchool = DB::table(DB::raw('(select distinct srno, created_at from stu_main_srno where school = 1 order by created_at desc) as subquery'))
                                ->select('srno')->limit(1)->first();
            $playSchoolSrno = DB::table('stu_main_srno')->select('stu_main_srno.srno')->where('stu_main_srno.srno', $maxPlaySchool->srno)->first();
            $playSchoolSrnoLatest = isset($playSchoolSrno) ? $playSchoolSrno->srno : null;
            // }
            // $sessions = SessionMaster::where('id', '>=', $currentSession)->where('active', 1)->pluck('session', 'id');
            $sessions = array_filter(SessionMasterController::getSessions(), function ($value, $key) use ($currentSession) {
                return $key >= $currentSession;
            }, ARRAY_FILTER_USE_BOTH);
            $classes = ClassMasterController::getClasses();
            return view('admin.promote_student.index', compact('publicSchoolSrnoLatest', 'playSchoolSrnoLatest', 'sessions', 'classes'));
        } else {
            return redirect()->back()->with('error', 'Something went wrong, please try again.');
        }
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
        try {
            //code...
            // Validate the request data
            $sessionRules = [
                'sometimes',
                'required',
                'exists:session_masters,id,active,1',
            ];
            $secondClassRules = [
                'sometimes',
                'required',
                'exists:class_masters,id,active,1',
            ];
            if (empty($request->tc) && empty($request->leftOut)) {
                $sessionRules[] = Rule::notIn([Session::get('current_session')->id]);
            }
            if ($request->srno || $request->allStd) {
                $secondClassRules[] = Rule::notIn([$request->class_id]);
            }
            // dd($request->all());

            // dd($sessionRules);
            $validator = Validator::make($request->all(), [
                'class_id' => 'required|exists:class_masters,id,active,1',
                // 'second_class_id' => 'sometimes|required|exists:class_masters,id',
                'second_class_id' => $secondClassRules,
                'section_id' => 'required|exists:section_masters,id,active,1',
                'second_section_id' => 'sometimes|required|exists:section_masters,id,active,1',
                'session_id' => $sessionRules,
                'std_id' => 'required|exists:stu_main_srno,srno',
                'second_std_id' => 'sometimes|required|exists:stu_main_srno,srno',
                'promote_date' => 'required',
                'srno' => [
                    'nullable',
                    'string',
                    'max:255',
                    Rule::unique('stu_main_srno')->where(function ($query) {
                        return $query->whereNotNull('admission_date')
                            ->whereNotNull('form_submit_date');
                    }),
                ],
            ], [
                'class_id.required' => 'Select the class',
                'section_id.required' => 'Select the section',
                'second_class_id.required' => 'Select the class',
                'second_section_id.required' => 'Select the section',
                'std_id.required' => 'Select the Student',
                'session_id.required' => 'Select the session',
                'session_id.not_in' => "Can't Promote in same session",
                'second_class_id.not_in' => "Can't Promote in same class",
                'promote_date.required' => 'Enter the date',
            ]);


            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $validator->errors()
                ], 400);
            }

            //ssid = 2 for promote class
            //ssid = 3 for promote school
            //ssid = 4 for TC to Student
            //ssid = 5 for Left Out

            $selectedStdIds = $request->input('std_id');
            $students = StudentMaster::whereIn('srno', $selectedStdIds)->where('ssid', 1)->where('active', 1)->get();
            $successMessages = 'Student Promoted successfully.';
            if ($students->isEmpty()) {
                return redirect()->back()->with('error', 'No students found.');
            }

            foreach ($students as $std) {
                $commanData = [
                    'class' => $request->second_class_id,
                    'section' => $request->second_section_id,
                    'rollno' => $std->rollno,
                    'session_id' => $request->session_id,
                    'image' => $std->image,
                    'age_proof' => $std->age_proof,
                    'gender' => $std->gender,
                    'religion' => $std->religion,
                    'relation_code' => $std->relation_code,
                    'transport' => $std->transport,
                    'trans_1st_inst' => $std->trans_1st_inst,
                    'trans_2nd_inst' => $std->trans_2nd_inst,
                    'trans_discount' => $std->trans_discount,
                    'trans_total' => $std->trans_total,
                    'reason' => $std->reason,
                    'TCRefNo' => $std->TCRefNo,
                    'form_submit_date' => $request->promote_date,
                    'active' => 1,
                    'add_user_id' => Session::get('login_user'),
                    'edit_user_id' => Session::get('login_user'),
                ];
                $stdSsIdUpdate = StudentMaster::whereIn('srno', $selectedStdIds)->where('ssid', 1)->latest()->first();

                if (!empty($request->srno)) {
                    $promoteStdSchoolData = array_merge($commanData, [
                        'srno' => $request->srno,
                        'school' => $std->school === 1 ? 2 : 1,
                        'prev_srno' => $std->srno,
                        'admission_date' => $request->promote_date,
                        'ssid' => 1,
                    ]);

                    if ($stdSsIdUpdate) {
                        $stdSsIdUpdate->update(['ssid' => 3]);
                    }
                    $stdDetails = DB::table('stu_detail')->where('srno', $std->srno)->first();
                    if (!empty($stdDetails)) {
                        DB::table('stu_detail')->updateOrInsert(['srno' => $request->srno], [
                            'srno' => $request->srno,
                            'name' => $stdDetails->name,
                            'dob' => $stdDetails->dob,
                            'state_id' => $stdDetails->state_id,
                            'district_id' => $stdDetails->district_id,
                            'address' => $stdDetails->address,
                            'category_id' => $stdDetails->category_id,
                            'email' => $stdDetails->email,
                            'mobile' => $stdDetails->mobile,
                            'pincode' => $stdDetails->pincode,
                            'pre_school' => $stdDetails->pre_school,
                            'pre_class' => $stdDetails->pre_class,
                            'add_user_id' => Session::get('login_user'),
                            'edit_user_id' => Session::get('login_user'),
                            'active' => $stdDetails->active,
                        ]);
                    }
                    $stdParentDetails = DB::table('parents_detail')->where('srno', $std->srno)->first();
                    if (!empty($stdParentDetails)) {
                        // DB::table('parents_detail')->where('srno', $std->srno)->update(['srno' => $request->srno]);

                        DB::table('parents_detail')->updateOrInsert(['srno' => $request->srno], [
                            'srno' => $request->srno,
                            'f_name' => $stdParentDetails->f_name,
                            'm_name' => $stdParentDetails->m_name,
                            'g_father' => $stdParentDetails->g_father,
                            'state_id' => $stdParentDetails->state_id,
                            'district_id' => $stdParentDetails->district_id,
                            'address' => $stdParentDetails->address,
                            'category_id' => $stdParentDetails->category_id,
                            'email' => $stdParentDetails->email,
                            'f_mobile' => $stdParentDetails->f_mobile,
                            'pin_code' => $stdParentDetails->pin_code,
                            'f_occupation' => $stdParentDetails->f_occupation,
                            'm_occupation' => $stdParentDetails->m_occupation,
                            'm_mobile' => $stdParentDetails->m_mobile,
                            'add_user_id' => Session::get('login_user'),
                            'edit_user_id' => Session::get('login_user'),
                            'active' => $stdParentDetails->active,
                        ]);
                    }
                    StudentMaster::updateOrCreate([
                        'class' => $request->second_class_id,
                        'section' => $request->second_section_id,
                        'session_id' => $request->session_id,
                        'prev_srno' => $std->srno,
                        'admission_date' => $request->promote_date,
                    ], $promoteStdSchoolData);
                    $successMessages = 'Student School Promoted successfully.';
                    continue;
                }

                if (!empty($request->tc)) {
                    $tcStdData = array_merge($commanData, [
                        'srno' => $std->srno,
                        'school' => $std->school,
                        'ssid' => 4,
                    ]);
                    if ($stdSsIdUpdate) {
                        $stdSsIdUpdate->update(['ssid' => 2]);
                    }
                    StudentMaster::updateOrCreate([
                        'admission_date' => null,
                        'class' => $request->second_class_id,
                        'section' => $request->second_section_id,
                        'session_id' => $request->session_id,
                        'prev_srno' => $std->srno,
                    ], $tcStdData);
                    $successMessages = 'Student TC to Out Successfully.';
                    continue;
                }

                if (!empty($request->leftOut)) {
                    $leftStdData = array_merge($commanData, [
                        'srno' => $std->srno,
                        'school' => $std->school,
                        'ssid' => 5,
                    ]);
                    if ($stdSsIdUpdate) {
                        $stdSsIdUpdate->update(['ssid' => 2]);
                    }
                    StudentMaster::updateOrCreate([
                        'admission_date' => null,
                        'class' => $request->second_class_id,
                        'section' => $request->second_section_id,
                        'session_id' => $request->session_id,
                        'prev_srno' => $std->srno,
                    ], $leftStdData);
                    $successMessages = 'Student has Been left out Successfully.';
                    continue;
                }


                $promoteData = array_merge($commanData, [
                    'srno' => $std->srno,
                    'school' => $std->school,
                    'ssid' => 1,
                ]);
                $stdSsIdUpdate->update(['ssid' => 2]);
                StudentMaster::updateOrCreate([
                    'admission_date' => null,
                    'class' => $request->second_class_id,
                    'section' => $request->second_section_id,
                    'srno' => $std->srno,
                    'session_id' => $request->session_id,
                ], $promoteData);
            }

            return response()->json(['success' => $successMessages]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => "Failed to get std"
            ], 500);
        }
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
}
