<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\ClassMaster;
use App\Models\Admin\FeeMaster;
use App\Models\Admin\MarksMaster;
use App\Models\Admin\SectionMaster;
use App\Models\Admin\SessionMaster;
use App\Models\Fee\FeeDetail;
use App\Models\Marks\Marks;
use App\Models\Student\StudentMaster;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Session;
use App\Http\Controllers\Admin\SessionMasterController;
use App\Http\Controllers\Admin\ClassMasterController;
use App\Http\Controllers\Admin\ExamMasterController;
use App\Http\Controllers\Admin\StateMasterController;
use App\Http\Controllers\Student\StudentMasterController;

class EditSectionsController extends Controller
{
    //
    public function index()
    {
        return view('admin.editSections.index');
    }

    public function editStd(Request $request)
    {
        $fields = [
            'stu_main_srno.id',
            'stu_main_srno.srno',
            'stu_main_srno.session_id',
            'stu_detail.name',
            'parents_detail.f_name',
            'class_masters.class as class_name',
            'section_masters.section as section_name',
            'stu_main_srno.ssid',
        ];
        $session = isset($request->session_id) ? $request->session_id : session('current_session')->id;
        $sessions = SessionMasterController::getSessions();
        $data = StudentMasterController::getStd($fields, [], [], true)->where('stu_main_srno.session_id', $session)
            ->whereIn('stu_main_srno.ssid', session('current_session')->id ? [1] : [1, 2, 3, 4, 5])->paginate(15);
        return view('admin.editSections.std', compact('data', 'sessions'));
    }

    public function editStdAdmissionPromotion()
    {
        return view('admin.editSections.std_change_admission_promotion');
    }
    public function editStdAdmissionDate()
    {
        return view('admin.editSections.std_add_delete_admission_date');
    }
    public function editStdByPreSrno()
    {
        return view('admin.editSections.edit_std_prev_records');
    }
    public function editStdRollSection()
    {
        $classes = ClassMasterController::getClasses();
        return view('admin.editSections.std_set_section_rollno', compact('classes'));
    }
    public function editResult()
    {
        $sessions = SessionMasterController::getSessions();
        return view('admin.editSections.edit_result', compact('sessions'));
    }
    public function editRemoveRelativeStd()
    {
        $classes = ClassMasterController::getClasses();
        return view('admin.editSections.edit_remove_relative_students', compact('classes'));
    }
    public function editStdInfoClass()
    {
        $classes = ClassMasterController::getClasses();
        return view('admin.editSections.edit_student_info_class_wise', compact('classes'));
    }

    public function editStdMarks()
    {
        $classes = ClassMasterController::getClasses();
        $exams = ExamMasterController::getAllExam();
        return view('admin.editSections.edit_std_marks', compact('classes', 'exams'));
    }

    public function editStdAttendance()
    {
        $classes = ClassMasterController::getClasses();
        return view('admin.editSections.edit_std_attendance', compact('classes'));
    }

    public function editRemoveStdFee()
    {
        $classes = ClassMasterController::getClasses();
        return view('admin.editSections.edit_remove_std_fee_entry', compact('classes'));
    }

    public function editStdFeeDetailsView()
    {
        return view('admin.editSections.edit_fee_details_std');
    }
    public function editResultStore(Request $request)
    {
        $request->validate([
            'session_id' => 'required|exists:session_masters,id,active,1',
            'resultDate' => 'required|date_format:Y-m-d',
        ]);

        $sessionMaster = SessionMasterController::getSessions([], ['id' => $request->session_id]);
        // $sessionMaster = SessionMaster::where('id', $request->session_id)->where('active', 1)->first();

        if (empty($sessionMaster)) {
            return redirect()->back()->with('error', 'Active session not found. Please try again.');
        }

        SessionMaster::where('id', $request->session_id)->update(['result_date' => $request->resultDate]);

        return redirect()->route('admin.editSection.editResult')->with('success', 'Result Date updated successfully.');
    }


    public function editStdMarksStore(Request $request)
    {
        try {
            $session = Session::get('current_session')->id;
            $validator = Validator::make($request->all(), [
                'class_id' => 'required|exists:class_masters,id,active,1',
                'section_id' => 'required|exists:section_masters,id,active,1',
                'subject' => 'required|exists:subject_masters,id,active,1',
                'exam' => 'required|exists:exam_masters,id,active,1',
                'std_id' => 'required|exists:stu_main_srno,srno',
                'attendance' => 'required|in:1',
                'marks' => [
                    'required',
                    'numeric',
                    function ($attribute, $value, $fail) use ($request, $session) {
                        // Retrieve the maximum marks threshold
                        $threshold = MarksMaster::where('session_id', $session)
                            ->where('class_id', $request->class_id)
                            ->where('subject_id', $request->subject)
                            ->where('exam_id', $request->exam)
                            ->where('active', 1)
                            ->value('max_marks');
                        // Handle case where no threshold is set
                        if (is_null($threshold)) {
                            $fail("The maximum marks configuration is missing for the selected session, class, subject, or exam.");
                            return;
                        }

                        // Validate the value against the threshold
                        if ($value > $threshold) {
                            $fail("The $attribute must not be greater than $threshold.");
                        }
                    },
                ],
            ], [
                'class_id.required' => 'Class is required.',
                'class_id.exists' => 'Invalid Class.',
                'section_id.required' => 'Section is required.',
                'section_id.exists' => 'Invalid Section.',
                'subject.required' => 'Subject is required.',
                'subject.exists' => 'Invalid Subject.',
                'exam.required' => 'Exam is required.',
                'exam.exists' => 'Invalid Exam.',
                'std_id.required' => 'Student is required.',
                'std_id.exists' => 'Invalid Student.',
                'attendance.required' => 'Attendance is required.',
                'attendance.in' => 'Invalid Attendance.',
                'marks.required' => 'Marks are required.',
                'marks.numeric' => 'Marks must be numeric.',
                // 'marks.max' => 'Marks must not be greater than maximum allowed marks.',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $validator->errors()
                ], 400);
            }
            $marks = Marks::updateOrCreate(
                [
                    'session_id' => $session,
                    'class_id' => $request->class_id,
                    'srno' => $request->std_id,
                    'subject_id' => $request->subject,
                    'exam_id' => $request->exam,
                ],
                [

                    'marks' => $request->marks,
                    'attendance' => $request->attendance,
                    'add_user_id' => Session::get('login_user'),
                    'edit_user_id' => Session::get('login_user'),
                    'active' => 1,
                ]
            );
            if ($marks) {
                return response()->json([
                    'status' => 'success',
                    'message' => "Student Marks updated successfully.",
                ], 200);
            } else {

                return response()->json([
                    'status' => 'error',
                    'message' => 'Something went wrong, please try again.',
                ], 422);
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => "Failed to get std " . $e->getMessage()
            ], 500);
        }
    }

    public function editStdStore(Request $request)
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
        $stds = StudentMaster::where('id', $request->id)->where('class', $request->class)->where('section', $request->section)->value('session_id');
        $request->validate([
            'srno' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('stu_main_srno')
                ->where(function ($query) {
                    return $query->whereNotNull('admission_date')
                        ->whereNotNull('form_submit_date');
                })
                ->ignore($request->id), // Ignore current record while updating
            ],

            'school' => 'nullable',
            'class' => 'nullable',
            'section' => 'nullable',
            'rollno' => [
                'nullable',
                'numeric',
                 Rule::unique('stu_main_srno')
                    ->where(function ($query) use ($request, $stds) {
                        return $query->where('class', $request->class)
                            ->where('section',$request->section)->where('session_id', $stds);
                            // ->orWhere('id', $request->id); // Add ID to be ignored conditionally
                    })->ignore($request->id),
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
            'session_id' => $stds,
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
        if ($studentData) {
            // $student = StudentMaster::updateOrCreate(
            StudentMaster::updateOrCreate(['id' => $request->id], $studentData);
        }

        $commonData = [
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
        if ($stuDetailData) {

            DB::table('stu_detail')->updateOrInsert(
                ['srno' => $request->srno],
                $stuDetailData
            );
        }

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
        if ($parentsDetailData) {

            DB::table('parents_detail')->updateOrInsert(
                ['srno' => $request->srno],
                $parentsDetailData
            );
        }

        $message = $request->id ? 'Student updated successfully.' : 'Student saved successfully.';
        return redirect()->route('admin.editSection.std')->with('success', $message);
    }

    public function editStdEdit(string $id, Request $request)
    {

        if ($id) {
            # code...
            $student = StudentMaster::findOrFail($id);
            if ($student !== null) {
                # code...
                $data = [];
                $fields = [
                    'stu_main_srno.srno',
                    'stu_detail.name',
                    'parents_detail.f_name',
                    'class_masters.class',
                    'stu_main_srno.ssid',
                    'stu_main_srno.active',
                    'stu_main_srno.relation_code',
                ];
                if ($request->search) {
                    $data = StudentMasterController::getStdWithNames(false, $fields)->where('stu_detail.name', 'like', '%' . $request->search . '%')->where('stu_main_srno.session_id', $student->session_id)->get();
                    // $data = DB::table('stu_main_srno')
                    //     ->join('stu_detail', 'stu_main_srno.srno', '=', 'stu_detail.srno')
                    //     ->join('parents_detail', 'stu_detail.srno', '=', 'parents_detail.srno')
                    //     ->join('class_masters', 'stu_main_srno.class', '=', 'class_masters.id')
                    //     ->where('stu_detail.name', 'like', '%' . $request->search . '%')
                    //     ->whereIn('stu_main_srno.ssid', [1, 2, 4, 5])
                    //     ->select('stu_main_srno.srno', 'stu_detail.name', 'parents_detail.f_name', 'class_masters.class')
                    //     ->get();
                }
                if ($request->ajax()) {
                    // Return the data as a JSON response

                    return response()->json([
                        'success' => true,
                        'data' => $data
                    ], 200);
                }
                $relatives = StudentMasterController::getStdWithNames(false, $fields)
                    ->whereNotNull('stu_main_srno.relation_code')->whereNot('stu_main_srno.srno', $student->srno)
                    ->where('stu_main_srno.session_id', $student->session_id)
                    ->where('stu_main_srno.relation_code', $student->relation_code)
                    ->get();
                // $relatives = DB::table('stu_main_srno')
                //     ->join('stu_detail', 'stu_main_srno.srno', '=', 'stu_detail.srno')
                //     ->join('parents_detail', 'stu_detail.srno', '=', 'parents_detail.srno')
                //     ->join('class_masters', 'stu_main_srno.class', '=', 'class_masters.id')
                //     ->where('stu_main_srno.relation_code', $student->relation_code)
                //     ->where('stu_detail.active', 1)
                //     ->whereIn('stu_main_srno.ssid', [1, 2, 4, 5])
                //     ->select('stu_main_srno.srno', 'stu_detail.name', 'parents_detail.f_name', 'class_masters.class')
                //     ->get();
                $parent_detail = DB::table('parents_detail')->where('srno', $student->srno)->where('active', 1)->first();
                // dd($parent_detail);
                $student_detail = DB::table('stu_detail')->where('srno', $student->srno)->where('active', 1)->first();
                $classes = ClassMasterController::getClasses();
                $states = StateMasterController::getAllStates();
                return view('admin.student.create', compact('student', 'parent_detail', 'student_detail', 'relatives', 'data', 'classes', 'states'));
            }
        } else {
            return redirect()->back()->with('error', 'Something went wrong, please try again.');
        }
    }

    // Edit section Edit Std Edit Relative
    public function editStdAddRelative(Request $request)
    {
        $request->validate([
            'std_id' => 'required|exists:stu_main_srno,srno',
            'second_std_id' => 'required|exists:stu_main_srno,srno',
        ]);
        $std = StudentMaster::where('srno', $request->std_id)->whereIn('ssid', [1, 2, 3, 4, 5])->first();
        $secondStd = StudentMaster::where('srno', $request->second_std_id)->whereIn('ssid', [1, 2, 3, 4, 5])->first();

        if ($std || $secondStd) {
            $stdRelationCode = $std->relation_code;
            $secondStdRelationCode = $secondStd ? $secondStd->relation_code : null;

            if ($stdRelationCode === null && $secondStdRelationCode === null) {
                $maxRelationCode = StudentMaster::max('relation_code');
                $newRelationCode = $maxRelationCode ? $maxRelationCode + 1 : 1;
                $std->update(['relation_code' => $newRelationCode]);
                $secondStd->update(['relation_code' => $newRelationCode]);
            } else {
                if ($stdRelationCode !== null && $secondStdRelationCode === null) {
                    $secondStd->update(['relation_code' => $stdRelationCode]);
                }
            }
            return response()->json([
                'status' => 'success',
                'message' => "Relative Added successfully."
            ], 200);
        } else {
            return response()->json([
                'status' => 'error',
                'message' => "Something went wrong, please try again."
            ], 500);
        }
    }

    public function editStdRollSectionStore(Request $request)
    {
        $data = $request->validate([
            'students' => 'required|array',
            'students.*.srno' => 'required|exists:stu_main_srno,srno',
            'students.*.rollno' => 'nullable|numeric',
            'students.*.sectionSecond' => 'nullable|exists:section_masters,id',
            'students.*.sectionCheck' => 'sometimes|required|boolean',
        ]);
        $currentSession = session('current_session')->id;
        foreach ($data['students'] as $std) {
            $student = StudentMaster::where('srno', $std['srno'])->whereIn('ssid', [1, 2, 4, 5])->where('session_id', $currentSession)->first();
            // $student = StudentMaster::where('srno', $std['srno'])->where('ssid', 1)->first();
            $updates = [];
            if ($student) {
                if (isset($std['rollno'])) {
                    $updates['rollno'] = $std['rollno'];
                }
                if (isset($std['sectionCheck']) && $std['sectionCheck']  && isset($std['sectionSecond'])) {
                    $updates['section'] = $std['sectionSecond'];
                }
            }
            if (!empty($updates)) {
                $student->update($updates);
                return redirect()->route('admin.editSection.editStdRollSection')->with('success', 'Students updated successfully.');
            } else {
                return redirect()->back()->with('error', 'Something went wrong, please try again.');
            }
        }
    }
    public function editRemoveRelativeStdStore(Request $request)
    {
        $request->validate([
            'std_id' => 'required|exists:stu_main_srno,srno',
            'second_std_id' => 'required|exists:stu_main_srno,srno',
        ]);
        $currentSession = session('current_session')->id;
        $std = StudentMaster::where('srno', $request->std_id)->where('session_id', $currentSession)->whereIn('ssid', [1, 2, 3, 4, 5])->first();
        // $secondStd = StudentMaster::where('srno', $request->second_std_id)->where('ssid', 1)->first();
        $secondStd = StudentMaster::where('srno', $request->second_std_id)->where('session_id', $currentSession)->whereIn('ssid', [1, 2, 3, 4, 5])->first();

        if ($std || $secondStd) {
            $stdRelationCode = $std->relation_code;
            $secondStdRelationCode = $secondStd ? $secondStd->relation_code : null;

            if ($stdRelationCode === null && $secondStdRelationCode === null) {
                $maxRelationCode = StudentMaster::whereNotNUll('relation_code')->max('relation_code');
                $newRelationCode = $maxRelationCode ? $maxRelationCode + 1 : 1;
                $std->update(['relation_code' => $newRelationCode]);
                $secondStd->update(['relation_code' => $newRelationCode]);
            } else {
                if ($stdRelationCode !== null && $secondStdRelationCode === null) {
                    $secondStd->update(['relation_code' => $stdRelationCode]);
                }
            }

            return redirect()->route('admin.editSection.editRemoveRelativeStd')->with('success', 'Relative Added successfully.');
        } else {
            return redirect()->back()->with('error', 'Something went wrong, please try again.');
        }
    }
    public function editRemoveRelativeStdRemove($id)
    {
        $stdData = ['relation_code' => null];
        $currentSession = session('current_session')->id;
        $std = StudentMaster::where('srno', $id)->where('session_id', $currentSession)->first();
        if ($std) {
            $std->update($stdData);
            return redirect()->route('admin.editSection.editRemoveRelativeStd')->with('success', 'Remove successfully.');
        } else {
            return redirect()->back()->with('error', 'Something went wrong, please try again.');
        }
    }

    public function editStdInfoClassStore(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'students' => 'required|array',
                'students.*.srno' => 'required|exists:stu_main_srno,srno',
                'students.*.student_name' => 'required|string',
                'students.*.f_name' => 'required|string|max:255',
                'students.*.m_name' => 'required|string|max:255',
                'students.*.g_f_name' => 'required|string|max:255',
                'students.*.dob' => 'nullable|date_format:Y-m-d',
                'students.*.f_mobile' => 'nullable|regex:/^[0-9]{10}$/',
                'students.*.m_mobile' => 'nullable|regex:/^[0-9]{10}$/',
                'students.*.age_proof' => 'nullable',
            ]);

            if ($validator->fails()) {
                return redirect()
                    ->back()
                    ->withErrors($validator)
                    ->withInput();
            }

            $data = $validator->validated();
            $currentSession = session('current_session')->id;

            foreach ($data['students'] as $std) {
                $student = StudentMaster::where('srno', $std['srno'])
                    ->where('session_id', $currentSession)
                    ->whereIn('ssid', [1, 2, 3, 4, 5])
                    ->first();

                if ($student) {
                    $stdDetail = DB::table('stu_detail')->where('srno', $student->srno);
                    if ($stdDetail) {
                        $stdDetail->update([
                            'name' => $std['student_name'],
                            'dob' => $std['dob'],
                        ]);
                    }

                    $stdParentDetail = DB::table('parents_detail')->where('srno', $student->srno);
                    if ($stdParentDetail) {
                        $stdParentDetail->update([
                            'f_name' => $std['f_name'],
                            'm_name' => $std['m_name'],
                            'g_father' => $std['g_f_name'],
                            'f_mobile' => $std['f_mobile'],
                            'm_mobile' => $std['m_mobile'],
                        ]);
                    }

                    $student->update([
                        'age_proof' => $std['age_proof']
                    ]);
                }
            }

            return redirect()
                ->route('admin.editSection.editStdInfoClass')
                ->with('success', 'Student updated successfully.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Something went wrong, please try again.');
        }
    }

    public function editStdAdmissionDateStore(Request $request)
    {
        $request->validate(
            [
                'a_date' => 'required|date_format:Y-m-d'
            ]
        );
        $std = StudentMaster::where('id', $request->hidden_srno)->first();
        if ($std) {
            $std->update(['admission_date' => $request->a_date]);
            return redirect()->route('admin.editSection.editStdAdmissionDate')->with('success', 'Student Admission Date updated successfully.');
        } else {
            return redirect()->back()->with('error', 'Something went wrong, please try again.');
        }
    }

    public function editStdByPreSrnoStore(Request $request)
    {
        // dd($request->all());
        $request->validate(
            [
                'std_name' => 'required|string|max:255',
                'f_name' => 'required|string|max:255',
                'm_name' => 'required|string|max:255',
                'dob' => 'required|date_format:Y-m-d',
                'category' => 'required',
                'address' => 'required',
            ]
        );
        $student = StudentMaster::where('srno', $request->std_srno)->where('active', 1)->first();
        if ($student) {
            $stdDetail = DB::table('stu_detail')->where('srno', $student->srno);
            if ($stdDetail) {
                $stdDetail->update(
                    [
                        'name' => $request->std_name,
                        'dob' => $request->dob,
                        'category_id' => $request->category,
                        'address' => $request->address,
                    ]
                );
            }
            $stdParentDetail = DB::table('parents_detail')->where('srno', $student->srno);
            if ($stdParentDetail) {
                $stdParentDetail->update(
                    [
                        'f_name' => $request->f_name,
                        'm_name' => $request->m_name,
                    ]

                );
            }
            return redirect()->route('admin.editSection.editStdByPreSrno')->with('success', 'Student updated successfully.');
        } else {
            return redirect()->back()->with('error', 'Something went wrong, please try again.');
        }
    }

    public function editStdAdmissionPromotionStore(Request $request)
    {

        $validates = $request->validate([
            'hidden_srno' => 'required',
            'hidden_a_date' => 'nullable|date_format:Y-m-d',
            'hidden_p_date' => 'nullable|date_format:Y-m-d',
            'a_p_date' => 'required|date_format:Y-m-d',
        ]);
        if ($validates) {
            # code...
            $query = StudentMaster::query();
            if (!empty($request->hidden_a_date)) {
                $query->where('id', $request->hidden_srno)
                    ->where('admission_date', $request->hidden_a_date)
                    ->update(['admission_date' => $request->a_p_date]);
            } else {
                $query->where('id', $request->hidden_srno)
                    ->where('form_submit_date', $request->hidden_p_date)
                    ->update(['form_submit_date' => $request->a_p_date]);
            }
            return redirect()->route('admin.editSection.editStdAdmissionPromotion')->with('success', 'Student updated successfully.');
        } else {
            return redirect()->back()->with('error', 'Something went wrong, please try again.');
        }
    }

    public function stdFeeDetailFetch(Request $request)
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

            $feeDetails = FeeDetail::where('srno', $request->srno)->where('session_id', $request->session)
                ->get(['ref_slip_no', 'pay_date', 'academic_trans', 'fee_of', 'amount', 'paid_mercy', 'srno', 'id']);

            if ($feeDetails->isNotEmpty()) {
                # code...
                return response()->json([
                    'status' => 'success',
                    'data' => $feeDetails
                ], 200);
            } else {
                # code...
                return response()->json([
                    'status' => 'success',
                    'message' => 'No Fee Detail Found',
                    'data' => []
                ], 200);
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => "Failed to get student fee detail: " . $e->getMessage()
            ], 500);
        }
    }

    public function stdFeeEdit(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'refSlip' => 'nullable|exists:fee_details,ref_slip_no',
                'pay_date' => 'required|date_format:Y-m-d',
                'academicTrans' => 'required|exists:fee_details,academic_trans',
                'feeOf' => 'required|string|max:255',
                'amount' => 'required|numeric',
                'paidMercy' => 'nullable|numeric',
                'srno' => 'required|exists:fee_details,srno',
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $validator->errors()
                ], 400);
            }

            FeeDetail::updateOrCreate([
                'srno' => $request->srno,
                'session_id' => $request->session,
                'fee_of' => $request->feeOf,
                'academic_trans' => $request->academicTrans,
                'paid_mercy' => $request->paidMercy,
                'ref_slip_no' => $request->refSlip ?? null,
            ], [
                'amount' => $request->amount,
                'pay_date' => $request->pay_date,
                'edit_user_id' => Session::get('login_user'),

            ]);
            return response()->json([
                'status' => 'success',
                'message' => 'Fee Detail updated successfully',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => "Failed to get student fee detail: " . $e->getMessage()
            ], 500);
        }
    }

    public function stdFeeRemove($id)
    {
        try {
            // $fee = FeeDetail::where('srno', $request->srno)->where('session_id', $request->session)->where('academic_trans', $request->academicTrans)->where('paid_mercy', $request->paidMercy)->where('ref_slip_no', $request->refSlip)->first();
            $fee = FeeDetail::find($id);
            if ($fee) {
                $fee->delete();
                return response()->json(['status' => 'success', 'message' => 'Student Fee Detail deleted successfully.']);
            } else {
                return response()->json(['status' => 'error', 'message' => 'Failed to delete Class Master.'], 404);
            }
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'An error occurred while deleting.'], 500);
        }
    }

    public function mercyFeeBoth()
    {
        $classes = ClassMasterController::getClasses();
        return view('admin.editSections.edit_std_mercy_fee_both', compact('classes'));
    }



    public function mercyFeeBothStore(Request $request)
    {
        try {
            $academic_trans_value = isset($request->transport) ? (int) $request->transport : 1;
            $session = $request->session;
            $student = StudentMaster::where('srno', $request->std_id)->where('class', $request->class)->where('section', $request->section)->where('active', 1)->where('session_id', $session)->whereIn('ssid', [1, 2, 3, 4, 5])->first();
            // $student = StudentMaster::where('srno', $request->std_id)->where('class', $request->class)->where('section', $request->section)->where('active', 1)->where('session_id', $session)->whereIn('ssid', [1, 2, 3])->first();
            // $student = StudentMaster::where('srno', $request->std_id)->where('active', 1)->where('ssid', 1)->first();

            if (!$student) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Student not found for the given SRNO.'
                ], 404);
            }

            $feeMaster = FeeMaster::where('class_id', $request->class)
                ->where('session_id', $session)
                ->where('active', 1)
                ->first(['admission_fee', 'inst_1', 'inst_2', 'inst_total', 'ins_discount']);

            if ($academic_trans_value == 2 && $student->transport == 0) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No Transport Fee Applicable For This Student.'
                ], 404);
            }

            $fee_master_fees = $academic_trans_value == 2
                ? (object) [
                    'inst_1' => $student->trans_1st_inst,
                    'inst_2' => $student->trans_2nd_inst,
                    'inst_total' => $student->trans_total,
                    'ins_discount' => $student->trans_discount,
                ]
                : $feeMaster;

            $baseQuery = FeeDetail::where('srno', $request->std_id)
                ->where('session_id', $session)
                ->where('academic_trans', $academic_trans_value)->get();

            // dd($baseQuery->get());
            $first_inst_fee_total = $baseQuery->where('fee_of', $academic_trans_value == 2 ? 1 : 2)->where('paid_mercy', 1)->sum('amount');
            $second_inst_fee_total = $baseQuery->where('fee_of', $academic_trans_value == 2 ? 2 : 3)->where('paid_mercy', 1)->sum('amount');
            $complete_fee_total = $baseQuery->where('fee_of', $academic_trans_value == 2 ? 3 : 4)->where('paid_mercy', 1)->sum('amount');
            $mercy_fee_total = $baseQuery->where('fee_of', $academic_trans_value == 2 ? 3 : 4)->where('paid_mercy', 2)->sum('amount');
            $admission_fee_total = $baseQuery->where('fee_of', 1)->where('paid_mercy', 1)->where('academic_trans', 1)->sum('amount');

            $totalPaid = $first_inst_fee_total + $second_inst_fee_total + $complete_fee_total + $mercy_fee_total + $admission_fee_total;

            $validator = Validator::make($request->all(), [
                'class' => 'required|exists:class_masters,id,active,1',
                'section' => 'required|exists:section_masters,id,active,1',
                'session' => 'required|exists:session_masters,id,active,1',
                'mercy_date' => 'required|date_format:Y-m-d',
                'amount' => [
                    'required',
                    'regex:/^\d*(\.\d{2})?$/',
                    function ($attribute, $value, $fail) use ($fee_master_fees, $totalPaid) {
                        if ($totalPaid == $fee_master_fees->inst_total) {
                            $fail('Complete fee has already been paid.');
                        } else {
                            $dueAmount = $fee_master_fees->inst_total - $totalPaid;
                            if ($value > $dueAmount) {
                                $fail("Only {$dueAmount} is due for Complete Fee.");
                            }
                        }
                    },
                ]
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $validator->errors()
                ], 400);
            }

            $recp_no = FeeDetail::where('academic_trans', $academic_trans_value)->max('recp_no') + 1;

            FeeDetail::create([
                'srno' => $request->std_id,
                'session_id' => $session,
                'academic_trans' => $academic_trans_value,
                'pay_date' => $request->mercy_date,
                'fee_of' => $academic_trans_value == 2 ? 3 : 4,
                'amount' => $request->amount,
                'paid_mercy' => 2,
                'recp_no' => $recp_no,
                'ref_slip_no' => null,
                'active' => 1,
                'add_user_id' => Session::get('login_user'),
                'edit_user_id' => Session::get('login_user'),
            ]);

            return response()->json([
                'status' => 'success',
                'message' => "Mercy Fee Submitted Successfully.",
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => "Failed to insert mercy amount: " . $e->getMessage()
            ], 500);
        }
    }


    /**
     * Edit Student Fee Functions
     */

    public function editStdFee()
    {
        $classes = ClassMasterController::getClasses();
        return view('admin.editSections.edit_fee_details_std', compact('classes'));
    }

    public function getStdFeeInfo1(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'computer_slip' => 'required_if:computer_slip,true|exists:fee_details,recp_no',
                'school_slip' => 'required_if:school_slip,true|exists:fee_details,ref_slip_no',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $validator->errors()
                ], 400);
            }
            $academic_trans_value = $request->transport;
            $session = $request->session;
            $feeDetails = FeeDetail::query();
            // dd($request->all());

            if ($request->computer_slip) {
                $feeDetails->where('academic_trans', $academic_trans_value)
                    ->where('active', 1)->where('paid_mercy', 1)
                    ->where('recp_no', $request->computer_slip)
                    ->where('session_id', $session);
            } elseif ($request->school_slip) {
                $feeDetails->where('academic_trans', $academic_trans_value)
                    ->where('active', 1)->where('paid_mercy', 1)
                    ->where('ref_slip_no', $request->school_slip)
                    ->where('session_id', $session);
            }

            $data = $feeDetails->get();
            // dd($data);

            if ($data->isEmpty()) {
                return response()->json([
                    'status' => 'error',
                    'message' => "No fee details found."
                ], 404);
            }

            // Assuming you want the first student's class and section.
            $student = StudentMaster::where('srno', $data[0]->srno)
                // ->where('ssid', 1)
                ->whereIn('ssid', [1, 2, 4, 5])
                ->where('active', 1)
                ->where('session_id', $session)
                ->first();

            if (!$student) {
                return response()->json([
                    'status' => 'error',
                    'message' => "Student not found."
                ], 404);
            }

            $class = ClassMaster::where('id', $student->class)->where('active', 1)->value('id'); // Adjust 'name' if needed
            $section = SectionMaster::where('id', $student->section)->where('active', 1)->value('id'); // Adjust 'name' if needed

            return response()->json([
                'status' => 'success',
                'message' => "Get student fee detail",
                'data' => $data,
                'class' => $class,
                'section' => $section,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => "Failed to get student fee detail: " . $e->getMessage()
            ], 500);
        }
    }

    /**
     * edit student fee store
     */

    public function editStdFeeStore(Request $request)
    {
        try {
            $academic_trans_value = $request->transport;
            $session = isset($request->session) ? $request->session : $request->current_session;
            $sessionName = SessionMasterController::getSessions([], ['id' => $session]);
            $student = StudentMasterController::getStdWithNames(true)->where('stu_main_srno.srno', $request->std_id)->where('stu_main_srno.class', $request->class)->where('stu_main_srno.section', $request->section)->where('stu_main_srno.session_id', $session)->first();
            if (!$student) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Student not found for the given SRNO.'
                ], 404);
            }

            $feeMaster = FeeMaster::where('class_id', $request->class)->where('session_id', $session)->where('active', 1)->first(
                ['admission_fee', 'inst_1', 'inst_2', 'inst_total', 'ins_discount']
            );

            if (isset($request->transport) && $request->transport == 2) {
                # code...

                if ($student->transport == 0) {
                    # code...
                    return response()->json([
                        'status' => 'error',
                        'message' => 'No Transport Fee Applicable For This Student.'
                    ], 404);
                }
            }

            $tcsArray =  [
                'inst_1' => $student->trans_1st_inst,
                'inst_2' => $student->trans_2nd_inst,
                'inst_total' => $student->trans_total,
                'ins_discount' => $student->trans_discount,
            ];
            $tcs = (object) $tcsArray;
            $fee_master_fees = isset($request->transport) && $request->transport == 2 ? $tcs : $feeMaster;

            // dd($fee_master_fees);

            $admission_fee_paid = FeeDetail::where('srno', $request->std_id)->where('academic_trans', $academic_trans_value)->where('fee_of', 1)->exists();
            $baseQuery = FeeDetail::where('srno', $request->std_id)->where('session_id', $session)->where('academic_trans', $academic_trans_value)->get();
            // dd($baseQuery);
            $first_inst_fee_total = $baseQuery->where('fee_of', isset($request->transport) && $request->transport == 2 ? 1 : 2)->sum('amount');
            // dd($first_inst_fee_total);
            $second_inst_fee_total = $baseQuery->where('fee_of', isset($request->transport) && $request->transport == 2 ? 2 : 3)->sum('amount');
            $complete_fee_total = $baseQuery->where('fee_of', isset($request->transport) && $request->transport == 2 ? 3 : 4)->where('paid_mercy', 1)->sum('amount');
            $mercy_fee_total = $baseQuery->where('fee_of', isset($request->transport) && $request->transport == 2 ? 3 : 4)->where('paid_mercy', 2)->sum('amount');


            $first_inst_fee_exists = FeeDetail::where('srno', $request->std_id)->where('session_id', $session)->where('academic_trans', $academic_trans_value)->where('fee_of', isset($request->transport) && $request->transport == 2 ? 1 : 2)->where('paid_mercy', 1)->exists();
            $second_inst_fee_exists = FeeDetail::where('srno', $request->std_id)->where('session_id', $session)->where('academic_trans', $academic_trans_value)->where('fee_of', isset($request->transport) && $request->transport == 2 ? 2 : 3)->where('paid_mercy', 1)->exists();
            $complete_fee_exists = FeeDetail::where('srno', $request->std_id)->where('session_id', $session)->where('academic_trans', $academic_trans_value)->where('fee_of', isset($request->transport) && $request->transport == 2 ? 3 : 4)->where('paid_mercy', 1)->exists();
            $mercy_fee_exists = FeeDetail::where('srno', $request->std_id)->where('session_id', $session)->where('academic_trans', $academic_trans_value)->where('fee_of', isset($request->transport) && $request->transport == 2 ? 3 : 4)->where('paid_mercy', 2)->exists();
            $uniqueRefSlip = array_values($sessionName)[0] . $request->ref_slip;
            $rules = [
                'class' => 'required|exists:class_masters,id,active,1',
                'section' => 'required|exists:section_masters,id,active,1',
                'std_id' => 'required|exists:stu_main_srno,srno',
                'fee_date' => 'required|date_format:Y-m-d',
                'total_amount' => 'required|regex:/^\d*(\.\d{2})?$/',
                'ref_slip' => [
                    'required',
                    'string',
                    function ($attribute, $value, $fail) use ($academic_trans_value, $uniqueRefSlip) {
                        $exists = DB::table('fee_details')
                            ->where('ref_slip_no', $uniqueRefSlip)
                            ->where('academic_trans', $academic_trans_value)
                            ->exists();
                        if ($exists) {
                            $fail('The reference slip number already exists for the given academic transaction.');
                        }
                    },
                    // Rule::unique('fee_details','ref_slip_no')->where('ref_slip_no', 'LIKE', array_values($sessionName)[0] . '%')->where('academic_trans', $academic_trans_value),
                ],
                'admission_fee' => [
                    'required_if:admission_fee,true',
                    'nullable',
                    'regex:/^\d*(\.\d{2})?$/',
                    function ($attribute, $value, $fail) use ($fee_master_fees) {
                        if ($fee_master_fees && $value > $fee_master_fees->admission_fee) {
                            $fail('The admission fee must be equal to ' . $fee_master_fees->admission_fee . '.');
                        } elseif ($fee_master_fees && $value < $fee_master_fees->admission_fee) {
                            $fail('The admission fee must be equal to ' . $fee_master_fees->admission_fee . '.');
                        }
                    },
                    function ($attribute, $value, $fail) use ($student, $admission_fee_paid) {
                        if (is_null($student->admission_date) && $admission_fee_paid == true) {
                            $fail('The admission fee cannot be accepted because the admission fee is already paid.');
                        }elseif ($admission_fee_paid == true){
                            $fail('The admission fee cannot be accepted because the admission fee is already paid.');
                        }
                    },
                ],
                'first_inst_fee' => [
                    'required_if:first_inst_fee,true',
                    'nullable',
                    'regex:/^\d*(\.\d{2})?$/',
                    function ($attribute, $value, $fail) use ($fee_master_fees, $first_inst_fee_exists) {
                        if ($fee_master_fees && $value > $fee_master_fees->inst_1 && $first_inst_fee_exists == false) {
                            $fail('The first installment fee must be equal to ' . $fee_master_fees->inst_1 . '.');
                        }
                    },
                    function ($attribute, $value, $fail) use ($request, $fee_master_fees, $first_inst_fee_total, $mercy_fee_exists, $mercy_fee_total, $first_inst_fee_exists, $complete_fee_total, $complete_fee_exists) {
                        $totalDue = $fee_master_fees->inst_1 - $first_inst_fee_total;
                        if ($first_inst_fee_exists == true && (isset($request->transport) && $request->transport == 2 ? $complete_fee_exists : $mercy_fee_exists == true)) {
                            # code...
                            $totalDue = $fee_master_fees->inst_1 - ($first_inst_fee_total + isset($request->transport) && $request->transport == 2 ? $complete_fee_total : $mercy_fee_total);
                        }
                        // dd($totalDue);
                        if ($totalDue <= 0) {
                            $fail('Already Paid First Installment.');
                            return; // Stop further validations
                        } elseif ($value > $totalDue && $first_inst_fee_exists == true) {
                            $fail('Only ' . $totalDue . ' due of First Installment.');
                        }
                    },
                    function ($attribute, $value, $fail) use ($request, $fee_master_fees, $first_inst_fee_total, $mercy_fee_total, $complete_fee_total) {
                        $totalWithMercy = $first_inst_fee_total + isset($request->transport) && $request->transport == 2 ? $complete_fee_total : $mercy_fee_total;
                        if ($totalWithMercy === $fee_master_fees->inst_1) {
                            # code...
                            $fail('Already Paid First Installment.');
                            return; // Stop further validations
                        }
                    },
                    function ($attribute, $value, $fail) use ($request, $fee_master_fees, $first_inst_fee_exists, $second_inst_fee_total, $mercy_fee_exists, $mercy_fee_total, $second_inst_fee_exists, $complete_fee_total, $complete_fee_exists) {
                        if (($second_inst_fee_exists == true || (isset($request->transport) && $request->transport == 2 ? $complete_fee_exists : $mercy_fee_exists == true)) && $first_inst_fee_exists == false) {
                            # code...
                            $total = $second_inst_fee_total + isset($request->transport) && $request->transport == 2 ? $complete_fee_total : $mercy_fee_total;
                            if ($total == $fee_master_fees->inst_total) {
                                $fail('Already Paid complete fee');
                                return; // Stop further validations
                            }
                        }
                    },
                    function ($attribute, $value, $fail) use ($baseQuery, $request) {
                        $completeFee = $baseQuery->where('fee_of', isset($request->transport) && $request->transport == 2 ? 3 : 4)->where('paid_mercy', 1)->first();
                        if ($completeFee) {
                            $fail("Fee previously enter as complete fee, now can't by insatllment. Please enter by complete fee");
                        }
                    },

                ],
                'second_inst_fee' => [
                    'required_if:second_inst__fee,true',
                    'nullable',
                    'regex:/^\d*(\.\d{2})?$/',
                    function ($attribute, $value, $fail) use ($fee_master_fees, $second_inst_fee_exists) {
                        if ($fee_master_fees && $value > $fee_master_fees->inst_2 && $second_inst_fee_exists == false) {
                            $fail('The second installment fee must be equal to ' . $fee_master_fees->inst_2 . '.');
                        }
                    },

                    function ($attribute, $value, $fail) use ($request, $fee_master_fees, $second_inst_fee_total, $mercy_fee_exists, $mercy_fee_total, $second_inst_fee_exists, $complete_fee_total) {
                        $totalDue = $fee_master_fees->inst_2 - $second_inst_fee_total;
                        if ($second_inst_fee_exists == true && $mercy_fee_exists == true) {
                            # code...
                            $totalDue = $fee_master_fees->inst_2 - ($second_inst_fee_total + isset($request->transport) && $request->transport == 2 ? $complete_fee_total : $mercy_fee_total);
                        }
                        $totalWithMercy = $second_inst_fee_total + isset($request->transport) && $request->transport == 2 ? $complete_fee_total : $mercy_fee_total;
                        if (($totalDue <= 0) || ($totalWithMercy === $fee_master_fees->inst_2)) {
                            $fail('Already Paid Second Installment.');
                            return; // Stop further validations
                        } elseif ($value > $totalDue && $second_inst_fee_exists == true) {
                            $fail('Only ' . $totalDue . ' due of Second Installment.');
                        }
                    },
                    function ($attribute, $value, $fail) use ($request, $fee_master_fees, $complete_fee_exists, $complete_fee_total, $first_inst_fee_exists, $first_inst_fee_total, $mercy_fee_exists, $mercy_fee_total, $second_inst_fee_exists) {
                        if (($first_inst_fee_exists == true || (isset($request->transport) && $request->transport == 2 ? $complete_fee_exists : $mercy_fee_exists == true)) && $second_inst_fee_exists == false) {
                            # code...
                            $total = $first_inst_fee_total + isset($request->transport) && $request->transport == 2 ? $complete_fee_total : $mercy_fee_total;
                            if ($total == $fee_master_fees->inst_total) {
                                $fail('Already Paid complete fee');
                                return; // Stop further validations
                            }
                        }
                    },

                    function ($attribute, $value, $fail) use ($baseQuery, $academic_trans_value) {
                        $completeFee = $baseQuery->where('fee_of', $academic_trans_value == 2 ? 3 : 4)->where('paid_mercy', 1)->first();
                        if ($completeFee) {
                            $fail("Fee previously enter as complete fee, now can't by insatllment. Please enter by complete fee");
                        }
                    },
                ],
                'complete_fee' => [
                    'required_if:complete_fee,true',
                    'nullable',
                    'regex:/^\d*(\.\d{2})?$/',
                    function ($attribute, $value, $fail) use ($fee_master_fees, $complete_fee_exists) {
                        if ($fee_master_fees && $value > $fee_master_fees->inst_total && $complete_fee_exists == false) {
                            $fail('The complete fee must be equal to ' . $fee_master_fees->inst_total . '.');
                        }
                    },
                    function ($attribute, $value, $fail) use ($fee_master_fees, $complete_fee_total, $mercy_fee_exists, $mercy_fee_total, $complete_fee_exists) {
                        $totalDue = $fee_master_fees->inst_total - $complete_fee_total;
                        if ($complete_fee_exists == true && $mercy_fee_exists == true) {
                            # code...
                            $totalDue = $fee_master_fees->inst_total - ($complete_fee_total + $mercy_fee_total);
                        }
                        $totalWithMercy = $complete_fee_total + $mercy_fee_total;
                        if (($totalDue <= 0) || ($totalWithMercy === $fee_master_fees->inst_total)) {
                            $fail('Already Paid Complete Fee.');
                            return; // Stop further validations
                        } elseif ($value > $totalDue && $complete_fee_exists == true) {
                            $fail('Only ' . $totalDue . ' due of Complete Fee.');
                        }
                    },
                    function ($attribute, $value, $fail) use ($request, $session, $academic_trans_value) {
                        $installFee = FeeDetail::where(function ($query) {
                            isset($request->transport) && $request->transport == 2 ? $query->where('fee_of', 1)->orWhere('fee_of', 2) : $query->where('fee_of', 2)->orWhere('fee_of', 3);
                        })
                            ->where('srno', $request->std_id)
                            ->where('session_id', $session)
                            ->where('academic_trans', $academic_trans_value)
                            ->first();

                        // $installFee = $baseQuery->where('fee_of', 2)->whereOr('fee_of', 3)->where('paid_mercy', 1)->first();
                        if ($installFee) {
                            $fail("If any installment is paid, then you can't enter complete fee. Please enter by installment.");
                        }
                    },
                ],

            ];
            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $validator->errors()
                ], 400);
            }
            // $recp_no = FeeDetail::where('session_id', $session)->where('academic_trans', $academic_trans_value)->max('recp_no');
            FeeDetail::where('session_id', $session)->where('academic_trans', $academic_trans_value)->where('recp_no', $request->cSlip)->where('active', 1)->update(['active' => 0]);
            $commonData = [
                'srno' => $request->std_id,
                'session_id' => $session,
                // 'academic_trans' => 1,
                'academic_trans' => $academic_trans_value,
                'pay_date' => $request->fee_date,
                'recp_no' => $request->cSlip,
                'ref_slip_no' => isset($request->ref_slip) ? array_values($sessionName)[0] . $request->ref_slip : null,
                'active' => 1,
                'add_user_id' => Session::get('login_user'),
                'edit_user_id' => Session::get('login_user'),
            ];
            if (!empty($request->admission_fee)) {
                # code...
                $admissionData = array_merge($commonData, [
                    'fee_of' => 1,
                    'amount' => $request->admission_fee,
                    'paid_mercy' => 1,
                ]);
                FeeDetail::create($admissionData);
            }
            if (!empty($request->first_inst_fee)) {
                # code...
                $firstInstData = array_merge($commonData, [
                    'fee_of' => isset($request->transport) && $request->transport == 2 ? 1 : 2,
                    'amount' => $request->first_inst_fee,
                    'paid_mercy' => 1,
                ]);

                FeeDetail::create($firstInstData);
            }
            if (!empty($request->second_inst_fee)) {
                # code...
                $secondInstData = array_merge($commonData, [
                    'fee_of' => isset($request->transport) && $request->transport == 2 ? 2 : 3,
                    'amount' => $request->second_inst_fee,
                    'paid_mercy' => 1,
                ]);


                FeeDetail::create($secondInstData);
            }
            if (!empty($request->complete_fee)) {
                # code...
                $completeData = array_merge($commonData, [
                    'fee_of' => isset($request->transport) && $request->transport == 2 ? 3 : 4,
                    'amount' => $request->complete_fee,
                    'paid_mercy' => 1,
                ]);

                FeeDetail::create($completeData);
            }


            return response()->json([
                'status' => 'success',
                'message' => "Fee Updated Successfully. Slip No. " . ($request->cSlip),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => "Failed to submit fee " . $e->getMessage()
            ], 500);
        }
    }
}
