<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\MarksMaster;
use App\Models\Admin\SubjectGrade;
use App\Models\Admin\SubjectMaster;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;

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
            // code...
            if ($request->class_id !== '' && $request->subject_id !== '') {
                $query->where('session_id', $currentSession)->where('class_id', $request->class_id)->where('subject_id', $request->subject_id)->where('active', 1);
            }
            $data = $query->where('session_id', $currentSession)->where('active', 1)->orderBy('created_at', 'DESC')->paginate(10);

            // dd($data);
            if ($data !== null) {
                // code...
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
        $classes = ClassMasterController::getClasses();
        $exams = ExamMasterController::getAllExam();

        return view('admin.marks.create', compact('exams', 'classes'));
    }

    /**
     * Store a newly created resource in storage or update existing.
     */
    public function store(Request $request)
    {

        // Step 1: Validate top-level fields
        $request->validate([
            'class_id' => 'required|exists:class_masters,id,active,1',
            'exam_id' => 'required|exists:exam_masters,id,active,1',
            'subjects' => 'required|array|min:1',
            'subjects.*.min' => 'required|numeric|min:0',
            'subjects.*.max' => 'required|numeric|min:0',
            'subjects.*.grades' => 'nullable|array',
            'subjects.*.grades.*.name' => 'required_with:subjects.*.grades|string',
            'subjects.*.grades.*.min' => 'required_with:subjects.*.grades|numeric|min:0',
            'subjects.*.grades.*.max' => 'required_with:subjects.*.grades|numeric|min:0',
        ], [
            'class_id.required' => 'Please select a class.',
            'class_id.exists' => 'The selected class does not exist.',
            'exam_id.required' => 'Please select an exam.',
            'exam_id.exists' => 'The selected exam does not exist.',
            'subjects.required' => 'Please add at least one subject.',
            'subjects.*.min.required' => 'Minimum marks is required.',
            'subjects.*.max.required' => 'Maximum marks is required.',
            'subjects.*.grades.*.name.required_with' => 'Please enter grade name.',
            'subjects.*.grades.*.min.required_with' => 'Minimum marks is required.',
            'subjects.*.grades.*.max.required_with' => 'Maximum marks is required.',
        ]);

        // Step 2: Additional logical validations
        $errors = [];

        foreach ($request->subjects as $subjectId => $subject) {
            // --- Subject Min/Max Validation ---
            if ($subject['min'] >= $subject['max'] && $subject['min'] != 0 && $subject['max'] != 0) {
                $errors["subjects.$subjectId.min"] = 'Minimum marks must be less than maximum marks.';
                $errors["subjects.$subjectId.max"] = 'Maximum marks must be greater than minimum marks.';
            }

            // --- Grade Validations ---
            if (!empty($subject['grades']) && is_array($subject['grades'])) {
                $ranges = [];
                $names = [];

                foreach ($subject['grades'] as $index => $grade) {
                    $gName = trim($grade['name'] ?? '');
                    $gMin = $grade['min'] ?? null;
                    $gMax = $grade['max'] ?? null;

                    // Required fields
                    if ($gName === '' || $gMin === '' || $gMax === '') {
                        $errors["subjects.$subjectId.grades.$index.name"] = 'Grade name and marks are required.';
                        continue;
                    }

                    // Min < Max
                    if ($gMin >= $gMax) {
                        $errors["subjects.$subjectId.grades.$index.min"] = 'Grade min must be less than max.';
                        $errors["subjects.$subjectId.grades.$index.max"] = 'Grade max must be greater than min.';
                    }

                    // Duplicate grade names
                    if (in_array(strtolower($gName), $names)) {
                        $errors["subjects.$subjectId.grades.$index.name"] = "Duplicate grade name '$gName' found.";
                    } else {
                        $names[] = strtolower($gName);
                    }

                    // Overlapping ranges
                    foreach ($ranges as $rIndex => $r) {
                        if (
                            ($gMin >= $r['min'] && $gMin <= $r['max']) ||
                            ($gMax >= $r['min'] && $gMax <= $r['max']) ||
                            ($gMin <= $r['min'] && $gMax >= $r['max'])
                        ) {
                            $errors["subjects.$subjectId.grades.$index.min"] = 'This grade range overlaps with another grade.';
                            $errors["subjects.$subjectId.grades.$index.max"] = 'This grade range overlaps with another grade.';
                        }
                    }

                    $ranges[] = ['min' => $gMin, 'max' => $gMax];
                }
            }
        }

        // If errors found → return early with error bag
        if (!empty($errors)) {
            return redirect()->back()->withErrors($errors)->withInput();
        }

        // Step 3: Store or Update
        $isEdit = $request->boolean('is_edit');
        DB::beginTransaction();

        try {
            $sessionId = Session::get('current_session')->id;
            $userId = Session::get('login_user');
            $savedCount = 0;
            $updatedCount = 0;

            $requestSubjectIds = array_keys($request->subjects);

            // 🔹 If Edit Mode — Only remove grades not included now
            if ($isEdit) {
                SubjectGrade::where('session_id', $sessionId)
                    ->where('exam_id', $request->exam_id)
                    ->where('class_id', $request->class_id)
                    ->whereNotIn('subject_id', $requestSubjectIds)
                    ->delete();
            }

            // --- Create or Update Marks & Grades ---
            foreach ($request->subjects as $subjectId => $subjectData) {
                // MarksMaster: update or create only (no delete)
                $marksRecord = MarksMaster::updateOrCreate(
                    [
                        'session_id' => $sessionId,
                        'class_id' => $request->class_id,
                        'subject_id' => $subjectId,
                        'exam_id' => $request->exam_id,
                    ],
                    [
                        'min_marks' => $subjectData['min'],
                        'max_marks' => $subjectData['max'],
                        'add_user_id' => $userId,
                        'edit_user_id' => $userId,
                        'active' => 1,
                    ]
                );

                if ($marksRecord->wasRecentlyCreated) {
                    $marksRecord->add_user_id = $userId;
                    $marksRecord->save();
                    $savedCount++;
                } else {
                    $updatedCount++;
                }

                // Delete existing grades for this subject
                SubjectGrade::where('session_id', $sessionId)
                    ->where('exam_id', $request->exam_id)
                    ->where('class_id', $request->class_id)
                    ->where('subject_id', $subjectId)
                    ->whereNull('is_overall')
                    ->delete();

                // Recreate grades
                if (!empty($subjectData['grades'])) {
                    foreach ($subjectData['grades'] as $grade) {
                        if (empty($grade['name']) && empty($grade['min']) && empty($grade['max'])) {
                            continue;
                        }

                        SubjectGrade::create([
                            'session_id' => $sessionId,
                            'exam_id' => $request->exam_id,
                            'class_id' => $request->class_id,
                            'subject_id' => $subjectId,
                            'grade_name' => $grade['name'],
                            'min_marks' => $grade['min'],
                            'max_marks' => $grade['max'],
                            'add_user_id' => $userId,
                            'edit_user_id' => $userId,
                            'is_overall' => null,
                            'active' => 1,
                        ]);
                    }
                }
            }

            DB::commit();

            // --- Success Message ---
           /*  if ($isEdit) {
                $message = match (true) {
                    $savedCount > 0 && $updatedCount > 0 => "Marks updated for {$updatedCount} subject(s) and created for {$savedCount} new subject(s).",
                    $updatedCount > 0 => "Marks and grades updated successfully for {$updatedCount} subject(s).",
                    default => "Marks and grades saved successfully for {$savedCount} subject(s)."
                };
            } else {
            } */
            $message = "Marks and grades saved successfully.";

            // return redirect()->route('admin.marks-master.index')->with('success', $message);
            return redirect()->back()->with('success', $message);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Marks store/update error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Something went wrong, please try again.')->withInput();
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
        if (empty($marksMaster)) {
            return redirect()->back()->with('error', 'Something went wrong, please try again.');
        }
        // Dropdown data
        $classes = ClassMasterController::getClasses();
        $exams = ExamMasterController::getAllExam();

        // Get the specific subject details
        $subject = SubjectMaster::where('id', $marksMaster->subject_id)->where('active', 1)->first();
        if (empty($subject)) {
            return redirect()->back()->with('error', 'Subject not found or inactive.');
        }

        // Get grades for this subject, exam, class, and session
        $subjectGrades = SubjectGrade::where('exam_id', $marksMaster->exam_id)->where('class_id', $marksMaster->class_id)->where('subject_id', $marksMaster->subject_id)->where('session_id', $marksMaster->session_id)->where('active', 1)->get(['id', 'grade_name', 'min_marks', 'max_marks']);

        return view('admin.marks.edit', compact('marksMaster', 'classes', 'exams', 'subjectGrades'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, MarksMaster $marksMaster)
    {
        $userId = Session::get('login_user');
        $sessionId = Session::get('current_session')->id;
        $marksMasterId = $request->id;

        // --- MarksMaster Validation ---
        $request->validate([
            'min_marks' => [
                'required',
                'numeric',
                'lt:max_marks',
                'different:max_marks',
                Rule::unique('marks_masters')
                    ->where(function ($query) use ($request, $sessionId) {
                        return $query->where('class_id', $request->class_id)
                            ->where('subject_id', $request->subject_id)
                            ->where('exam_id', $request->exam_id)
                            ->where('session_id', $sessionId)
                            ->where('active', 1);
                    })->ignore($marksMasterId),
            ],
            'max_marks' => [
                'required',
                'numeric',
                'gt:min_marks',
                'different:min_marks',
                Rule::unique('marks_masters')
                    ->where(function ($query) use ($request, $sessionId) {
                        return $query->where('class_id', $request->class_id)
                            ->where('subject_id', $request->subject_id)
                            ->where('exam_id', $request->exam_id)
                            ->where('session_id', $sessionId)
                            ->where('active', 1);
                    })->ignore($marksMasterId),
            ],
            'class_id' => 'required|exists:class_masters,id,active,1',
            'subject_id' => 'required|exists:subject_masters,id,active,1',
            'exam_id' => 'required|exists:exam_masters,id,active,1',
        ], [
            'class_id.required' => 'Please select a class.',
            'class_id.exists' => 'The selected class does not exist.',
            'subject_id.required' => 'Please select a subject.',
            'subject_id.exists' => 'The selected subject does not exist.',
            'exam_id.required' => 'Please select an exam.',
            'exam_id.exists' => 'The selected exam does not exist.',
            'min_marks.required' => 'Please enter minimum marks.',
            'min_marks.numeric' => 'Minimum marks must be a number.',
            'min_marks.lt' => 'Minimum marks must be less than maximum marks.',
            'min_marks.different' => 'Minimum marks should be different from maximum marks.',
            'max_marks.required' => 'Please enter maximum marks.',
            'max_marks.numeric' => 'Maximum marks must be a number.',
            'max_marks.gt' => 'Maximum marks must be greater than minimum marks.',
            'max_marks.different' => 'Maximum marks should be different from minimum marks.',
            'min_marks.unique' => 'A MarksMaster already exists for this class, subject, and exam.',
            'max_marks.unique' => 'A MarksMaster already exists for this class, subject, and exam.',
        ]);

        // --- Grade Validations ---
        $grades = $request->grades ?? [];
        $gradeErrors = [];
        $gradeRanges = [];
        $gradeNames = [];

        foreach ($grades as $key => $grade) {
            $name = trim($grade['name'] ?? '');
            $min = $grade['min'] ?? null;
            $max = $grade['max'] ?? null;

            // Skip completely empty rows
            if (! $name && $min === null && $max === null) {
                continue;
            }

            // Required fields per input
            if (! $name) {
                $gradeErrors["grades.$key.name"] = 'Grade name is required.';
            }
            if ($min === null) {
                $gradeErrors["grades.$key.min"] = "Minimum marks are required for grade {$name}.";
            }
            if ($max === null) {
                $gradeErrors["grades.$key.max"] = "Maximum marks are required for grade {$name}.";
            }

            if (! empty($gradeErrors)) {
                continue;
            } // Skip further validation if required fields are missing

            // Numeric check
            if (! is_numeric($min)) {
                $gradeErrors["grades.$key.min"] = "Minimum marks for grade {$name} must be numeric.";
            }
            if (! is_numeric($max)) {
                $gradeErrors["grades.$key.max"] = "Maximum marks for grade {$name} must be numeric.";
            }

            // Min < Max
            if ($min >= $max) {
                $gradeErrors["grades.$key.min"] = "Minimum marks must be less than maximum marks for grade {$name}.";
                $gradeErrors["grades.$key.max"] = "Maximum marks must be greater than minimum marks for grade {$name}.";
            }

            // Duplicate grade names
            if (in_array(strtoupper($name), $gradeNames)) {
                $gradeErrors["grades.$key.name"] = "Duplicate grade name found: {$name}.";
            } else {
                $gradeNames[] = strtoupper($name);
            }

            // Overlapping ranges
            foreach ($gradeRanges as $range) {
                if (($min >= $range['min'] && $min <= $range['max']) ||
                    ($max >= $range['min'] && $max <= $range['max']) ||
                    ($min <= $range['min'] && $max >= $range['max'])) {
                    $gradeErrors["grades.$key.min"] = "Grade {$name} range overlaps with grade {$range['name']}.";
                    $gradeErrors["grades.$key.max"] = "Grade {$name} range overlaps with grade {$range['name']}.";
                }
            }

            $gradeRanges[] = ['min' => $min, 'max' => $max, 'name' => $name];
        }

        if (! empty($gradeErrors)) {
            return redirect()->back()->withErrors($gradeErrors)->withInput();
        }

        // --- Update MarksMaster and Grades ---
        DB::beginTransaction();
        try {
            // Update MarksMaster
            $marksMaster->where('id', $marksMasterId)->update([
                'session_id' => $sessionId,
                'class_id' => $request->class_id,
                'subject_id' => $request->subject_id,
                'exam_id' => $request->exam_id,
                'min_marks' => $request->min_marks,
                'max_marks' => $request->max_marks,
                'edit_user_id' => $userId,
            ]);

            // Handle Grades
            $existingGradeIds = SubjectGrade::where('class_id', $request->class_id)
                ->where('subject_id', $request->subject_id)
                ->where('exam_id', $request->exam_id)
                ->where('session_id', $sessionId)
                ->pluck('id')
                ->toArray();

            $submittedGradeIds = [];

            foreach ($grades as $gradeId => $grade) {
                if (empty($grade['name']) && empty($grade['min']) && empty($grade['max'])) {
                    continue;
                }

                $gradeData = [
                    'grade_name' => $grade['name'],
                    'min_marks' => $grade['min'],
                    'max_marks' => $grade['max'],
                    'class_id' => $request->class_id,
                    'subject_id' => $request->subject_id,
                    'exam_id' => $request->exam_id,
                    'edit_user_id' => $userId,
                ];

                if (is_numeric($gradeId)) {
                    SubjectGrade::where('id', $gradeId)->update($gradeData);
                    $submittedGradeIds[] = $gradeId;
                } else {
                    $newGrade = SubjectGrade::create(array_merge($gradeData, [
                        'session_id' => $sessionId,
                        'add_user_id' => $userId,
                        'active' => 1,
                    ]));
                    $submittedGradeIds[] = $newGrade->id;
                }
            }

            // Hard delete removed grades
            $gradesToDelete = array_diff($existingGradeIds, $submittedGradeIds);
            if (! empty($gradesToDelete)) {
                SubjectGrade::whereIn('id', $gradesToDelete)->delete();
            }

            DB::commit();

            return redirect()->route('admin.marks-master.index')->with('success', 'Marks and grades updated successfully.');
        } catch (\Exception $e) {
            \Log::error('MarksMaster Update Error: '.$e->getMessage());
            DB::rollBack();

            return redirect()->back()->with('error', 'Something went wrong. Please try again.')->withInput();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }



    /** Get Subject with marks and grade */

    public function getSubjectMarksGrade(Request $request)
    {
        $classId = $request->class;
        $examId = $request->exam;
        $sessionId = Session::get('current_session')->id;

        try {
            // Fetch all subjects, even if they don't have corresponding marks or grades
            $subjects = DB::table('subject_masters as s')
            ->leftJoin('marks_masters as m', function($join) use ($classId, $examId, $sessionId) {
                $join->on('s.id', '=', 'm.subject_id')
                    ->where('m.class_id', $classId)
                    ->where('m.exam_id', $examId)
                    ->where('m.session_id', $sessionId)
                    ->where('m.active', 1);
            })
            ->where('s.class_id', $classId)
            ->where('s.active', 1)
            ->select(
                's.id as subject_id',
                's.subject as subject_name',
                DB::raw('MAX(m.min_marks) as min_marks'),
                DB::raw('MAX(m.max_marks) as max_marks')
            )
            ->groupBy('s.id', 's.subject')
            ->get();


            $data = [];

            foreach ($subjects as $subject) {
                // Get grades for this subject, if any
                $grades = DB::table('subject_grades')
                    ->where('subject_id', $subject->subject_id)
                    ->where('class_id', $classId)
                    ->where('exam_id', $examId)
                    ->where('session_id', $sessionId)
                    ->whereNull('is_overall')
                    ->where('active', 1)
                    ->orderBy('min_marks', 'desc')
                    ->get(['grade_name', 'min_marks', 'max_marks']);

                // If no grades found, set to an empty array
                if ($grades->isEmpty()) {
                    $grades = [];
                }

                // Add subject data to the response
                $data[] = [
                    'id' => $subject->subject_id,
                    'name' => $subject->subject_name,
                    'min' => $subject->min_marks ?? 0, // Handle cases where min_marks is null
                    'max' => $subject->max_marks ?? 0, // Handle cases where max_marks is null
                    'grades' => $grades,  // This will be an empty array if no grades are found
                ];
            }

            return response()->json([
                'status' => 'success',
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get record',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show the form for creating overall global grade.
     */
    public function overAllGradeIndex()
    {
        $classes = ClassMasterController::getClasses();
        // return view('admin.marks.overAllGrade', compact('classes'));
        return view('admin.marks.withoutExamsubjectWiseOverallGrade', compact('classes'));
    }

    /** Store Overall Grade and Subject-wise Overall Grade - without exam */
    public function storeOverAllMarksGradeWithoutExam(Request $request)
    {
        $sessionId = Session::get('current_session')->id;
        $userId = Session::get('login_user');

        // Basic validation
        $validated = $request->validate([
            'grade_type' => 'required|in:overall,subject_wise',
            'class_id' => 'required|exists:class_masters,id,active,1',
        ], [
            'grade_type.required' => 'Please select a grade type.',
            'class_id.required' => 'Please select a class.',
        ]);

        // Validate based on grade type
        if ($request->grade_type === 'overall') {
            return $this->storeGlobalOverAllMarksGrade($request, $sessionId, $userId);
        } elseif ($request->grade_type === 'subject_wise') {
            return $this->storeSubjectWiseGradesWithoutexam($request, $sessionId, $userId);
        }

        return back()->withErrors(['error' => 'Invalid grade type.'])->withInput();
    }
    /** Get Overall Grades */
    public function getGlobalOverAllMarksGrade(Request $request)
    {
        $classId = $request->class;
        $sessionId = Session::get('current_session')->id;

        try {
            // Fetch overall grades (subject_id is NULL)
            $grades = DB::table('subject_grades')
                ->whereNull('subject_id')
                ->whereNull('exam_id')
                ->where('class_id', $classId)
                ->where('session_id', $sessionId)
                ->where('active', 1)
                ->where('is_overall', 3)
                ->get(['id', 'grade_name', 'min_marks', 'max_marks']);

            return response()->json([
                'status' => 'success',
                'message' => 'Overall grades fetched successfully',
                'data' => $grades,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get record',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    /** Store Overall Grade */
    public function storeGlobalOverAllMarksGrade($request, $sessionId, $userId)
    {
        // Validate grades
        $validated = $request->validate([
            'grades' => 'required|array|min:1',
            'grades.*.name' => 'required|string|max:50',
            'grades.*.min' => 'required|numeric|min:0',
            'grades.*.max' => 'required|numeric|min:0|gt:grades.*.min',
        ], [
            'grades.required' => 'Please add at least one grade.',
            'grades.min' => 'At least one grade is required.',
            'grades.*.name.required' => 'Grade name is required.',
            'grades.*.min.required' => 'Minimum marks is required.',
            'grades.*.max.required' => 'Maximum marks is required.',
            'grades.*.max.gt' => 'Maximum marks must be greater than minimum marks.',
        ]);

        $grades = $request->grades;

        // Check for duplicate grade names
        $gradeNames = array_column($grades, 'name');
        if (count($gradeNames) !== count(array_unique($gradeNames))) {
            return back()->withErrors(['grades' => 'Duplicate grade names are not allowed.'])->withInput();
        }

        // Check for overlapping ranges
        if ($this->hasOverlappingRanges($grades)) {
            return back()->withErrors(['grades' => 'Grade ranges cannot overlap.'])->withInput();
        }

        try {
            DB::beginTransaction();

            // Delete existing overall grades for this exam and class
            SubjectGrade::where('class_id', $request->class_id)->where('session_id', $sessionId)->whereNull('subject_id')->whereNull('exam_id')->where('is_overall', 3)->delete();

            // Insert new grades
            foreach ($validated['grades'] as $grade) {
                SubjectGrade::create([
                    'exam_id' => null,
                    'subject_id' => null,
                    'class_id' => $request->class_id,
                    'grade_name' => $grade['name'],
                    'min_marks' => $grade['min'],
                    'max_marks' => $grade['max'],
                    'is_overall' => 3, // Overall grade
                    'session_id' => $sessionId,
                    'active' => 1,
                    'add_user_id' => $userId,
                ]);
            }

            DB::commit();
            // return redirect()->route('admin.marks-master.index')->with('success', 'Overall grades saved successfully.');
            return redirect()->back()->with('success', 'Overall grades saved successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to save grades. Please try again.'])->withInput();
        }
    }


    /** Get Subject-wise OverAllGrade */
    public function getGlobalSubjectWiseOverallGrade(Request $request)
    {
        $classId = $request->class;
        $sessionId = Session::get('current_session')->id;

        try {

            // Step 1: Fetch parent subjects which have child subjects
            $subjects = DB::table('subject_masters as s')
                ->whereExists(function($query) use ($classId) {
                    $query->select(DB::raw(1))
                        ->from('subject_masters as child')
                        ->whereRaw('child.subject_id = s.id')
                        ->where('child.class_id', $classId)
                        ->where('child.active', 1);
                })
                ->where('s.class_id', $classId)
                ->where('s.active', 1)
                ->whereNull('s.subject_id') // Only parent subjects
                ->select(
                    's.id as subject_id',
                    's.subject as subject_name'
                )
                ->orderBy('s.priority')
                ->orderBy('s.order_by')
                ->get();

            // -----------------------------------------------
            // NEW CONDITION: If no parent-with-child found → load all subjects of class
            // -----------------------------------------------
            if ($subjects->isEmpty()) {
                $subjects = DB::table('subject_masters')
                    ->where('class_id', $classId)
                    ->where('active', 1)
                    ->select('id as subject_id', 'subject as subject_name')
                    ->orderBy('priority')
                    ->orderBy('order_by')
                    ->get();
            }

            $data = [];

            foreach ($subjects as $subject) {
                // Get grades for this subject
                $grades = DB::table('subject_grades')
                    ->where('subject_id', $subject->subject_id)
                    ->where('class_id', $classId)
                    ->whereNull('exam_id')
                    ->where('session_id', $sessionId)
                    ->where('active', 1)
                    ->where('is_overall', 4)
                    ->orderBy('min_marks', 'desc')
                    ->get(['grade_name', 'min_marks', 'max_marks']);

                $data[] = [
                    'id' => $subject->subject_id,
                    'name' => $subject->subject_name,
                    'grades' => $grades->isEmpty() ? [] : $grades,
                ];
            }

            return response()->json([
                'status' => 'success',
                'data' => $data,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get record',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /** Store Subject-wise Overall Grades - without exam */
    private function storeSubjectWiseGradesWithoutexam($request, $sessionId, $userId)
    {
       // Basic Validation (only subjects list)
        $request->validate([
            'subjects' => 'required|array|min:1',
            'subjects.*.subject_id' => 'required|exists:subject_masters,id,active,1',
            'class_id' => 'required|integer',
        ]);

        $subjects = $request->subjects;
        /**************************************
         * 2️⃣ Validate ONLY subjects with grades
         **************************************/
        foreach ($subjects as $subjectId => $subjectData) {

            // Skip subjects without grades
            if (empty($subjectData['grades'])) {
                continue;
            }

            $grades = $subjectData['grades'];

            // 🔹 Duplicate grade names
            $names = array_column($grades, 'name');
            if (count($names) !== count(array_unique($names))) {
                return back()
                    ->withErrors([
                        "subjects.$subjectId.grades" => "Duplicate grade names are not allowed for this subject."
                    ])->withInput();
            }

            // 🔹 Validate each grade entry
            foreach ($grades as $gradeIndex => $grade) {

                if (!isset($grade['name']) || trim($grade['name']) === '') {
                    return back()->withErrors([
                        "subjects.$subjectId.grades.$gradeIndex.name" => "Grade name is required."
                    ])->withInput();
                }

                if (!isset($grade['min']) || !is_numeric($grade['min'])) {
                    return back()->withErrors([
                        "subjects.$subjectId.grades.$gradeIndex.min" => "Minimum marks must be a number."
                    ])->withInput();
                }

                if (!isset($grade['max']) || !is_numeric($grade['max'])) {
                    return back()->withErrors([
                        "subjects.$subjectId.grades.$gradeIndex.max" => "Maximum marks must be a number."
                    ])->withInput();
                }

                if ($grade['max'] <= $grade['min']) {
                    return back()->withErrors([
                        "subjects.$subjectId.grades.$gradeIndex.max" =>
                            "Max marks must be greater than min marks."
                    ])->withInput();
                }
            }

            // 🔹 Overlapping Range Check (INLINE)
            if ($this->hasOverlappingRanges($grades)) {
                return back()->withErrors([
                    "subjects.$subjectId.grades" =>
                        "Grade ranges cannot overlap for this subject."
                ])->withInput();
            }
        }

        /**************************************
         * 3️⃣ SAVE DATA
         **************************************/
        try {
            DB::beginTransaction();

            // Delete only subjects that have grades
            $subjectIdsWithGrades = array_keys(
                array_filter($subjects, fn($s) => !empty($s['grades']))
            );

            SubjectGrade::whereNull('exam_id')->where('class_id', $request->class_id)->where('session_id', $sessionId)->where('is_overall', 4)->whereIn('subject_id', $subjectIdsWithGrades)->delete();
            // Insert new grades
            foreach ($subjects as $subjectId => $subjectData) {
                if (empty($subjectData['grades'])) {
                    continue;
                }
                foreach ($subjectData['grades'] as $grade) {
                    SubjectGrade::create([
                        'exam_id' => null,
                        'subject_id' => $subjectId,
                        'class_id' => $request->class_id,
                        'grade_name' => $grade['name'],
                        'min_marks' => $grade['min'],
                        'max_marks' => $grade['max'],
                        'is_overall' => 4,
                        'session_id' => $sessionId,
                        'active' => 1,
                        'add_user_id' => $userId,
                    ]);
                }
            }
            DB::commit();
            // return redirect()->route('admin.marks-master.index')->with('success', 'Subject-wise overall grades saved successfully.');
            return redirect()->back()->with('success', 'Subject-wise overall grades saved successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Failed to save subject-wise grades: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Failed to save grades. Please try again.'])->withInput();
        }
    }




    /**
     * Show the form for creating overalln grade.
     */
   /*  public function overAllGradeIndex()
    {
        $classes = ClassMasterController::getClasses();
        $exams = ExamMasterController::getAllExam();

        return view('admin.marks.overAllGrade', compact('exams', 'classes'));
    } */

    /** Get Overall Grades */
    public function getOverAllMarksGrade(Request $request)
    {
        $classId = $request->class;
        $examId = $request->exam;
        $sessionId = Session::get('current_session')->id;

        try {
            // Fetch overall grades (subject_id is NULL)
            $grades = DB::table('subject_grades')
                ->whereNull('subject_id')
                ->where('class_id', $classId)
                ->where('exam_id', $examId)
                ->where('session_id', $sessionId)
                ->where('active', 1)
                ->where('is_overall', 2)
                ->orderBy('max_marks', 'desc')
                ->get(['id', 'grade_name', 'min_marks', 'max_marks']);

            return response()->json([
                'status' => 'success',
                'message' => 'Overall grades fetched successfully',
                'data' => $grades,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get record',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /** Subject-wise Overall Grade */
    public function getSubjectWiseOverallGrade(Request $request)
    {
        $classId = $request->class;
        $examId = $request->exam;
        $sessionId = Session::get('current_session')->id;

        try {
            // Fetch only parent subjects that have child subjects
            $subjects = DB::table('subject_masters as s')
                        ->whereExists(function($query) use ($classId) {
                            $query->select(DB::raw(1))
                                ->from('subject_masters as child')
                                ->whereRaw('child.subject_id = s.id')
                                ->where('child.class_id', $classId)
                                ->where('child.active', 1);
                        })
                        ->where('s.class_id', $classId)
                        ->where('s.active', 1)
                        ->whereNull('s.subject_id') // Only parent subjects
                        ->select(
                            's.id as subject_id',
                            's.subject as subject_name'
                        )
                        ->orderBy('s.priority')
                        ->orderBy('s.order_by')
                        ->get();

            $data = [];

            foreach ($subjects as $subject) {
                // Get grades for this parent subject
                $grades = DB::table('subject_grades')
                        ->where('subject_id', $subject->subject_id)
                        ->where('class_id', $classId)
                        ->where('exam_id', $examId)
                        ->where('session_id', $sessionId)
                        ->where('active', 1)
                        ->where('is_overall', 1)
                        ->orderBy('min_marks', 'desc')
                        ->get(['grade_name', 'min_marks', 'max_marks']);

                // If no grades found, set to an empty array
                if ($grades->isEmpty()) {
                    $grades = [];
                }

                // Add subject data to the response
                $data[] = [
                    'id' => $subject->subject_id,
                    'name' => $subject->subject_name,
                    'grades' => $grades,
                ];
            }

            return response()->json([
                'status' => 'success',
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get record',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /** Subject wise overall grade index */
    public function subjectWiseOverallGradeIndex()
    {
        $classes = ClassMasterController::getClasses();
        $exams = ExamMasterController::getAllExam();

        return view('admin.marks.subjectWiseOverallGrade', compact('exams', 'classes'));
    }

    /** Store Overall Grade and Subject-wise Overall Grade */
    public function storeOverAllMarksGrade(Request $request)
    {
        $sessionId = Session::get('current_session')->id;
        $userId = Session::get('login_user');

        // Basic validation
        $validated = $request->validate([
            'grade_type' => 'required|in:overall,subject_wise',
            'exam_id' => 'required|exists:exam_masters,id,active,1',
            'class_id' => 'required|exists:class_masters,id,active,1',
        ], [
            'grade_type.required' => 'Please select a grade type.',
            'exam_id.required' => 'Please select an exam.',
            'class_id.required' => 'Please select a class.',
        ]);

        // Validate based on grade type
        if ($request->grade_type === 'overall') {
            return $this->storeOverallGrades($request, $sessionId, $userId);
        } elseif ($request->grade_type === 'subject_wise') {
            return $this->storeSubjectWiseGrades($request, $sessionId, $userId);
        }

        return back()->withErrors(['error' => 'Invalid grade type.'])->withInput();
    }

    /** Store Overall Grades */
    private function storeOverallGrades($request, $sessionId, $userId)
    {
        // Validate grades
        $validated = $request->validate([
            'grades' => 'required|array|min:1',
            'grades.*.name' => 'required|string|max:50',
            'grades.*.min' => 'required|numeric|min:0',
            'grades.*.max' => 'required|numeric|min:0|gt:grades.*.min',
        ], [
            'grades.required' => 'Please add at least one grade.',
            'grades.min' => 'At least one grade is required.',
            'grades.*.name.required' => 'Grade name is required.',
            'grades.*.min.required' => 'Minimum marks is required.',
            'grades.*.max.required' => 'Maximum marks is required.',
            'grades.*.max.gt' => 'Maximum marks must be greater than minimum marks.',
        ]);

        $grades = $request->grades;

        // Check for duplicate grade names
        $gradeNames = array_column($grades, 'name');
        if (count($gradeNames) !== count(array_unique($gradeNames))) {
            return back()->withErrors(['grades' => 'Duplicate grade names are not allowed.'])->withInput();
        }

        // Check for overlapping ranges
        if ($this->hasOverlappingRanges($grades)) {
            return back()->withErrors(['grades' => 'Grade ranges cannot overlap.'])->withInput();
        }

        try {
            DB::beginTransaction();

            // Delete existing overall grades for this exam and class
            SubjectGrade::where('exam_id', $request->exam_id)
                ->where('class_id', $request->class_id)
                ->where('session_id', $sessionId)
                ->whereNull('subject_id')
                ->where('is_overall', 2) // Overall grades
                ->delete();

            // Insert new grades
            foreach ($validated['grades'] as $grade) {
                SubjectGrade::create([
                    'exam_id' => $request->exam_id,
                    'subject_id' => null,
                    'class_id' => $request->class_id,
                    'grade_name' => $grade['name'],
                    'min_marks' => $grade['min'],
                    'max_marks' => $grade['max'],
                    'is_overall' => 2, // Overall grade
                    'session_id' => $sessionId,
                    'active' => 1,
                    'add_user_id' => $userId,
                ]);
            }

            DB::commit();
            // return redirect()->route('admin.marks-master.index')->with('success', 'Overall grades saved successfully.');
            return redirect()->back()->with('success', 'Overall grades saved successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to save grades. Please try again.'])->withInput();
        }
    }



    /** Store Subject-wise Overall Grades */
    private function storeSubjectWiseGrades($request, $sessionId, $userId)
    {
        // Basic Validation (only subjects list)
        $request->validate([
            'subjects' => 'required|array|min:1',
            'subjects.*.subject_id' => 'required|exists:subject_masters,id,active,1',
            'exam_id' => 'required|integer',
            'class_id' => 'required|integer',
        ]);

        $subjects = $request->subjects;
        /**************************************
         * 2️⃣ Validate ONLY subjects with grades
         **************************************/
        foreach ($subjects as $subjectId => $subjectData) {

            // Skip subjects without grades
            if (empty($subjectData['grades'])) {
                continue;
            }

            $grades = $subjectData['grades'];

            // 🔹 Duplicate grade names
            $names = array_column($grades, 'name');
            if (count($names) !== count(array_unique($names))) {
                return back()
                    ->withErrors([
                        "subjects.$subjectId.grades" => "Duplicate grade names are not allowed for this subject."
                    ])->withInput();
            }

            // 🔹 Validate each grade entry
            foreach ($grades as $gradeIndex => $grade) {

                if (!isset($grade['name']) || trim($grade['name']) === '') {
                    return back()->withErrors([
                        "subjects.$subjectId.grades.$gradeIndex.name" => "Grade name is required."
                    ])->withInput();
                }

                if (!isset($grade['min']) || !is_numeric($grade['min'])) {
                    return back()->withErrors([
                        "subjects.$subjectId.grades.$gradeIndex.min" => "Minimum marks must be a number."
                    ])->withInput();
                }

                if (!isset($grade['max']) || !is_numeric($grade['max'])) {
                    return back()->withErrors([
                        "subjects.$subjectId.grades.$gradeIndex.max" => "Maximum marks must be a number."
                    ])->withInput();
                }

                if ($grade['max'] <= $grade['min']) {
                    return back()->withErrors([
                        "subjects.$subjectId.grades.$gradeIndex.max" =>
                            "Max marks must be greater than min marks."
                    ])->withInput();
                }
            }

            // 🔹 Overlapping Range Check (INLINE)
            if ($this->hasOverlappingRanges($grades)) {
                return back()->withErrors([
                    "subjects.$subjectId.grades" =>
                        "Grade ranges cannot overlap for this subject."
                ])->withInput();
            }
        }

        /**************************************
         * 3️⃣ SAVE DATA
         **************************************/
        try {
            DB::beginTransaction();

            // Delete only subjects that have grades
            $subjectIdsWithGrades = array_keys(
                array_filter($subjects, fn($s) => !empty($s['grades']))
            );

            SubjectGrade::where('exam_id', $request->exam_id)
                ->where('class_id', $request->class_id)
                ->where('session_id', $sessionId)
                ->where('is_overall', 1)
                ->whereIn('subject_id', $subjectIdsWithGrades)
                ->delete();

            // Insert new grades
            foreach ($subjects as $subjectId => $subjectData) {

                if (empty($subjectData['grades'])) {
                    continue;
                }

                foreach ($subjectData['grades'] as $grade) {
                    SubjectGrade::create([
                        'exam_id' => $request->exam_id,
                        'subject_id' => $subjectId,
                        'class_id' => $request->class_id,
                        'grade_name' => $grade['name'],
                        'min_marks' => $grade['min'],
                        'max_marks' => $grade['max'],
                        'is_overall' => 1,
                        'session_id' => $sessionId,
                        'active' => 1,
                        'add_user_id' => $userId,
                    ]);
                }
            }

            DB::commit();

            // return redirect()->route('admin.marks-master.index')->with('success', 'Subject-wise overall grades saved successfully.');
            return redirect()->back()->with('success', 'Subject-wise overall grades saved successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Failed to save subject-wise grades: ' . $e->getMessage());

            return back()->withErrors(['error' => 'Failed to save grades. Please try again.'])->withInput();
        }
    }
    /** Check for overlapping grade ranges */
    private function hasOverlappingRanges($grades)
    {
        foreach ($grades as $i => $grade1) {
            foreach ($grades as $j => $grade2) {
                if ($i !== $j) {
                    // Check if ranges overlap
                    if (
                        ($grade1['min'] >= $grade2['min'] && $grade1['min'] <= $grade2['max']) ||
                        ($grade1['max'] >= $grade2['min'] && $grade1['max'] <= $grade2['max']) ||
                        ($grade2['min'] >= $grade1['min'] && $grade2['min'] <= $grade1['max'])
                    ) {
                        return true;
                    }
                }
            }
        }
        return false;
    }


}
