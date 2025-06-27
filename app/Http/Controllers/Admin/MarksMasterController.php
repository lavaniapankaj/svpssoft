<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\ExamMaster;
use App\Models\Admin\MarksMaster;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Session;
use App\Http\Controllers\Admin\ExamMasterController;
use App\Http\Controllers\Admin\ClassMasterController;

class MarksMasterController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        //
        $currentSession = Session::get('current_session')->id;
        $query = MarksMaster::query();

        if ($query) {
            # code...
            if ($request->class_id !== '' && $request->subject_id !== '') {
                $query->where('session_id', $currentSession)->where('class_id', $request->class_id)->where('subject_id', $request->subject_id)->where('active', 1);
            }
            $data = $query->where('session_id', $currentSession)->where('active', 1)->orderBy('created_at', 'DESC')->paginate(10);

            // dd($data);
            if ($data !== null) {
                # code...
                $classes = ClassMasterController::getClasses();
                return view('admin.marks.index', compact('data', 'classes'));
            } else {
                return redirect()->back()->with('error', 'Something went wrong, please try again.');
            }
        } else {
            return redirect()->back()->with('error', 'Something went wrong, please try again.');
        }
    }


    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        # code...
        $classes = ClassMasterController::getClasses();
        $exams = ExamMasterController::getAllExam();
        return view('admin.marks.create', compact('exams', 'classes'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {

        $request->validate([
            'min_marks' => [
                'required',
                'numeric',
                'lt:max_marks',
                'different:max_marks',
                Rule::unique('marks_masters')
                    ->where(function ($query) use ($request) {
                        return $query->where('class_id', $request->class_id)
                            ->where('subject_id', $request->subject_id)
                            ->where('exam_id', $request->exam_id)
                            ->where('session_id', Session::get('current_session')->id)
                            ->where('active', 1);
                    }), // This ensures the current record is excluded from the check.
            ],
            'max_marks' => [
                'required',
                'numeric',
                'gt:min_marks',
                'different:min_marks',
                Rule::unique('marks_masters')
                    ->where(function ($query) use ($request) {
                        return $query->where('class_id', $request->class_id)
                            ->where('subject_id', $request->subject_id)
                            ->where('exam_id', $request->exam_id)
                            ->where('session_id', Session::get('current_session')->id)
                            ->where('active', 1);
                    }),
            ],
            'class_id' => 'required|exists:class_masters,id,active,1',
            'subject_id' => 'required|exists:subject_masters,id,active,1',
            // 'exam_id' => 'required|exists:exam_masters,id',
            'exam_id' => [
                'required',
                'exists:exam_masters,id,active,1',
                Rule::unique('marks_masters')->where(function ($query) use ($request) {
                    return $query->where('subject_id', $request->subject_id)
                        ->where('exam_id', $request->exam_id)
                        ->where('session_id', Session::get('current_session')->id)
                        ->where('active', 1);
                }),
            ],
        ], [
            'class_id.exists' => 'The selected class does not exist.',
            'subject_id.exists' => 'The selected subject does not exist.',
            'exam_id.exists' => 'The selected exam does not exist.',
            'exam_id.unique' => 'Marks already exists for this exam and subject.',
            'class_id.required' => 'Please select a class.',
            'subject_id.required' => 'Please select a subject.',
            'min_marks.required' => 'Please enter minimum marks.',
            'min_marks.numeric' => 'Minimum marks should be a number.',
            'min_marks.lt' => 'Minimum marks should be less than maximum marks.',
            'min_marks.different' => 'Minimum marks should be different from maximum marks.',
            'max_marks.required' => 'Please enter maximum marks.',
            'max_marks.numeric' => 'Maximum marks should be a number.',
            'max_marks.gt' => 'Maximum marks should be greater than minimum marks.',
            'max_marks.different' => 'Maximum marks should be different from minimum marks.',
            'exam_id.required' => 'Please select an exam.',
        ]);

        $user = Auth::user();

        $marksData = [
            'session_id' => Session::get('current_session')->id,
            'class_id' => $request->class_id,
            'subject_id' => $request->subject_id,
            'exam_id' => $request->exam_id,
            'min_marks' => $request->min_marks,
            'max_marks' => $request->max_marks,
            'add_user_id' => Session::get('login_user'),
            'edit_user_id' => Session::get('login_user'),
            'active' => 1,
        ];

        $marks = MarksMaster::create($marksData);

        if ($marks) {
            return redirect()->route('admin.marks-master.index')->with('success', 'Marks saved successfully.');
        } else {
            return redirect()->back()->with('error', 'Something went wrong, please try again.');
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
    public function edit(MarksMaster $marksMaster)
    {
        //
        if (!empty($marksMaster)) {
            $classes = ClassMasterController::getClasses();
            $exams = ExamMasterController::getAllExam();
            return view('admin.marks.edit', compact('marksMaster', 'exams', 'classes'));
        } else {
            return redirect()->back()->with('error', 'Something went wrong, please try again.');
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, MarksMaster $marksMaster)
    {
        //
        $request->validate([
            'min_marks' => [
                'required',
                'numeric',
                'lt:max_marks',
                'different:max_marks',
                Rule::unique('marks_masters')
                    ->where(function ($query) use ($request) {
                        return $query->where('class_id', $request->class_id)
                            ->where('subject_id', $request->subject_id)
                            ->where('exam_id', $request->exam_id)
                            ->where('session_id', Session::get('current_session')->id)
                            ->where('active', 1);
                    })->ignore($request->id),
            ],
            'max_marks' => [
                'required',
                'numeric',
                'gt:min_marks',
                'different:min_marks',
                Rule::unique('marks_masters')
                    ->where(function ($query) use ($request) {
                        return $query->where('class_id', $request->class_id)
                            ->where('subject_id', $request->subject_id)
                            ->where('exam_id', $request->exam_id)
                            ->where('session_id', Session::get('current_session')->id)
                            ->where('active', 1);
                    })->ignore($request->id),
            ],
            'class_id' => 'required|exists:class_masters,id,active,1',
            'subject_id' => 'required|exists:subject_masters,id,active,1',
            // 'exam_id' => 'required|exists:exam_masters,id',
            'exam_id' => [
                'required',
                'exists:exam_masters,id,active,1',
                Rule::unique('marks_masters')->where(function ($query) use ($request) {
                    return $query->where('subject_id', $request->subject_id)
                        ->where('exam_id', $request->exam_id)
                        ->where('session_id', Session::get('current_session')->id)
                        ->where('active', 1);
                })->ignore($request->id),
            ],
        ], [
            'class_id.exists' => 'The selected class does not exist.',
            'subject_id.exists' => 'The selected subject does not exist.',
            'exam_id.exists' => 'The selected exam does not exist.',
            'exam_id.unique' => 'Marks already exists for this exam and subject.',
            'class_id.required' => 'Please select a class.',
            'subject_id.required' => 'Please select a subject.',
            'min_marks.required' => 'Please enter minimum marks.',
            'min_marks.numeric' => 'Minimum marks should be a number.',
            'min_marks.lt' => 'Minimum marks should be less than maximum marks.',
            'min_marks.different' => 'Minimum marks should be different from maximum marks.',
            'max_marks.required' => 'Please enter maximum marks.',
            'max_marks.numeric' => 'Maximum marks should be a number.',
            'max_marks.gt' => 'Maximum marks should be greater than minimum marks.',
            'max_marks.different' => 'Maximum marks should be different from minimum marks.',
            'exam_id.required' => 'Please select an exam.',
        ]);
        $marksData = [
            'session_id' => Session::get('current_session')->id,
            'class_id' => $request->class_id,
            'subject_id' => $request->subject_id,
            'exam_id' => $request->exam_id,
            'min_marks' => $request->min_marks,
            'max_marks' => $request->max_marks,
            'edit_user_id' => Session::get('login_user'),

        ];
        if (!empty($marksData)) {
            $marks = $marksMaster->where('session_id', Session::get('current_session')->id)->where('class_id', $request->class_id)
                    ->where('subject_id', $request->subject_id)->where('exam_id', $request->exam_id)->update($marksData);
            if ($marks) {
                return redirect()->route('admin.marks-master.index')->with('success', 'Marks updated successfully.');
            } else {
                return redirect()->back()->with('error', 'Something went wrong, please try again.');
            }
        } else {
            return redirect()->back()->with('error', 'Something went wrong, please try again.');
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
