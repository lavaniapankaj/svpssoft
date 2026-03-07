<?php

namespace App\Http\Controllers\Marks;

use App\Http\Controllers\Admin\ClassMasterController;
use App\Http\Controllers\Admin\ExamMasterController;
use App\Http\Controllers\Admin\SubjectMasterController;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Student\StdAttendanceController;
use App\Http\Controllers\Student\StudentMasterController;
use App\Models\Admin\AttendanceSchedule;
use App\Models\Admin\ClassMaster;
use App\Models\Admin\ExamMaster;
use App\Models\Admin\MarksMaster;
use App\Models\Admin\SectionMaster;
use App\Models\Admin\SessionMaster;
use App\Models\Admin\SubjectMaster;
use App\Models\Marks\Marks;
use App\Models\Student\Attendance;
use App\Models\Student\StudentMaster;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;

class StdMarksController extends Controller
{
    //
    public function marksEntry()
    {
        $classes = ClassMasterController::getClasses();
        $exams = ExamMasterController::getAllExam();
        $subjects = SubjectMasterController::getAllSubjects();

        return view('marks.marks_entry.index', compact('classes', 'exams'));
    }

    /** Check for existing marks */
    public function checkExistingMarks(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'class' => 'required|exists:class_masters,id,active,1',
                'section' => 'required|exists:section_masters,id,active,1',
                'subject' => 'required|exists:subject_masters,id,active,1',
                'exam' => 'required|exists:exam_masters,id,active,1',
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $validator->errors()
                ], 200);
            }

            $current_session = Session::get('marks_current_session');

            $classId = $request->class;
            $sectionId = $request->section;
            $subjectId = $request->subject;
            $examId = $request->exam;
            $sessionId = $current_session->id;

            /** Get Student srno */
            $studentSrnos = DB::table('stu_main_srno')->where('class', $classId)->where('section', $sectionId)->where('session_id', $sessionId)->where('ssid', 1)->where('active', 1)->pluck('srno');
            if (count($studentSrnos) == 0) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No students found.',
                    'exists' => false,
                    'studentsCount' => count($studentSrnos) ?? 0
                ], 200);
            }
            $existingMarks = Marks::where('class_id', $classId)->where('exam_id', $examId)->where('subject_id', $subjectId)->where('session_id', $sessionId)->whereIn('srno', $studentSrnos)->exists();
            // Fetch names for friendly message
            $class = ClassMaster::find($classId)->class ?? 'Unknown Class';
            $section = SectionMaster::find($sectionId)->section ?? 'Unknown Section';
            $subject = SubjectMaster::find($subjectId)->subject ?? 'Unknown Subject';
            $exam = ExamMaster::find($examId)->exam ?? 'Unknown Exam';

            $msg = $existingMarks ? "Marks are already filled for {$subject} in {$class} - {$section} for {$exam}." : "No marks have been filled yet for {$subject} in {$class} - {$section} for {$exam}.";

            return response()->json([
                'status' => 'success',
                'message' => $msg,
                'exists' => $existingMarks,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => "Something went wrong, please try again.",
            ], 200);
        }
    }
    public function marksEntryStore(Request $request)
    {
        // ------------------------------------------------------------------ //
        //  1. Decode the JSON string BEFORE validation so Laravel can reach
        //     the nested keys (updated_students.*.srno etc.)
        // ------------------------------------------------------------------ //
        $updatedStudents = json_decode($request->input('updated_students'), true);

        if (!is_array($updatedStudents) || empty($updatedStudents)) {
            return response()->json([
                'success' => false,
                'message' => 'No student data received.',
            ], 422);
        }

        // Re-merge decoded array back into the request so the validator can
        // walk the nested structure with dot-notation rules.
        $request->merge(['updated_students' => $updatedStudents]);

        // ------------------------------------------------------------------ //
        //  2. Resolve session — prefer the value sent by the client, fall back
        //     to the server-side session (same logic as before).
        // ------------------------------------------------------------------ //
        $current_session = Session::get('marks_current_session');
        $currentSession = $current_session->id;

        if (!$currentSession) {
            return response()->json([
                'success' => false,
                'message' => 'Session not found. Please refresh and try again.',
            ], 422);
        }

        // Friendly aliases for the incoming field names (JS sends class_id, not hidden_class)
        $classId   = $request->input('class_id');
        $subjectId = $request->input('subject_id');
        $examId    = $request->input('exam_id');

        // ------------------------------------------------------------------ //
        //  3. Validate
        // ------------------------------------------------------------------ //
        $request->validate([
            'class_id'                   => 'required',
            'section_id'                 => 'required',
            'subject_id'                 => 'required',
            'exam_id'                    => 'required',
            'updated_students'           => 'required|array|min:1',
            'updated_students.*.srno'    => 'required|exists:stu_main_srno,srno',
            'updated_students.*.status'  => 'nullable|in:0,1',
            'updated_students.*.marks'   => [
                'nullable',
                'numeric',
                function ($attribute, $value, $fail) use ($currentSession, $classId, $subjectId, $examId) {
                    if (is_null($value)) {
                        return; // Allow null/empty marks
                    }

                    $maxMarks = MarksMaster::where('session_id', $currentSession)
                        ->where('class_id',   $classId)
                        ->where('subject_id', $subjectId)
                        ->where('exam_id',    $examId)
                        ->where('active', 1)
                        ->value('max_marks');

                    if (!is_null($maxMarks) && $value > $maxMarks) {
                        $fail("Marks must not exceed the maximum allowed marks of {$maxMarks}.");
                    }
                },
            ],
        ]);

        // ------------------------------------------------------------------ //
        //  4. Upsert each student record
        // ------------------------------------------------------------------ //
        $user         = Auth::user();
        $updatedCount = 0;

        foreach ($updatedStudents as $std) {
            $marks      = (isset($std['marks']) && $std['marks'] !== '') ? $std['marks'] : null;
            $attendance = isset($std['status']) ? (int) $std['status'] : 0;

            // Match keys — these uniquely identify a marks record
            $matchKeys = [
                'srno'       => $std['srno'],
                'class_id'   => $classId,
                'exam_id'    => $examId,
                'subject_id' => $subjectId,
                'session_id' => $currentSession,
            ];

            // Values to set on create or update
            $fillValues = [
                'marks'       => $attendance ? $marks : null, // If attendance is marked as 0 (absent), we nullify the marks
                'attendance'  => $attendance,
                'add_user_id' => $user->id,
                'edit_user_id'=> $user->id,
                'active'      => 1,
            ];

            Marks::updateOrCreate($matchKeys, $fillValues);
            $updatedCount++;
        }

        // ------------------------------------------------------------------ //
        //  5. Response
        // ------------------------------------------------------------------ //
        if ($updatedCount > 0) {
            return response()->json([
                'success' => true,
                'message' => 'Student marks and attendance updated successfully.',
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Something went wrong. Please try again.',
        ]);
    }

    public function marksReport()
    {
        $classes = ClassMasterController::getClasses();
        $exams = ExamMasterController::getAllExam();
        return view('marks.marks_entry.report', compact('classes', 'exams'));
    }

    public function getMarksReport(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'class' => 'required|exists:class_masters,id,active,1',
                'subject' => 'required',
                'exam'  => 'required|exists:exam_masters,id,active,1',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status'  => 'error',
                    'message' => $validator->errors(),
                ], 200);
            }

            $current_session = Session::get('marks_current_session');
            $sessionId       = $current_session->id;
            $examId          = $request->exam;
            $classId         = $request->class;
            $filterSubjectId = $request->subject;   // renamed to avoid collision
            $filterStudentId = $request->std_id;

            // ── 1. Fetch all students in one query ────────────────────────────────
            $studentQuery = DB::table('stu_main_srno')
                ->join('stu_detail', 'stu_main_srno.srno', '=', 'stu_detail.srno')
                ->select('stu_main_srno.srno', 'stu_main_srno.rollno', 'stu_detail.name')
                ->where('stu_main_srno.class',      $classId)
                ->where('stu_main_srno.session_id', $sessionId)
                ->where('stu_main_srno.ssid',       1)
                ->where('stu_main_srno.active',     1)
                ->orderBy('stu_main_srno.rollno')
                ->orderBy('stu_detail.name');

            if (!empty($filterStudentId) && $filterStudentId !== 'all') {
                $studentQuery->where('stu_main_srno.srno', $filterStudentId);
            }

            // Keyed by srno for O(1) lookup
            $students = $studentQuery->get()->keyBy('srno');
            $studentIds = $students->keys()->toArray();

            // ── 2. Fetch all subjects in one query ────────────────────────────────
            $subjectQuery = DB::table('subject_masters')
                ->select('id', 'subject')          // <-- subject name for header
                ->where('class_id', $classId)
                ->where('active', 1)
                ->orderBy('subject');

            if (!empty($filterSubjectId) && $filterSubjectId !== 'all') {
                $subjectQuery->where('id', $filterSubjectId);
            }

            $subjects   = $subjectQuery->get()->keyBy('id');
            $subjectIds = $subjects->keys()->toArray();

            // ── 3. Fetch ALL marks in one query ───────────────────────────────────
            $marksQuery = DB::table('marks')
                ->select('subject_id', 'srno', 'marks')
                ->where('session_id', $sessionId)
                ->where('exam_id',    $examId)
                ->where('class_id',   $classId)
                ->where('active',     1)
                ->whereIn('subject_id', $subjectIds)
                ->whereIn('srno',       $studentIds);

            // Group as [subject_id][srno] => marks
            $marksGrouped = $marksQuery->get()
                ->groupBy('subject_id')
                ->map(fn($rows) => $rows->keyBy('srno'));

            // ── 4. Build report (pure in-memory, zero extra queries) ──────────────
            $report = [];

            foreach ($subjectIds as $subId) {
                $subject       = $subjects[$subId];
                $subjectMarks  = $marksGrouped[$subId] ?? collect();

                $studentReports = [];
                foreach ($studentIds as $stuId) {
                    $student     = $students[$stuId];
                    $markRow     = $subjectMarks[$stuId] ?? null;

                    $studentReports[] = [
                        'student_id'  => $stuId,
                        'name'        => $student->name,
                        'roll_number' => $student->rollno,
                        'marks'       => $markRow ? $markRow->marks : 'N/A',
                    ];
                }

                $report[] = [
                    'subject_id'   => $subId,
                    'subject_name' => $subject->subject,
                    'students'     => $studentReports,
                ];
            }

            // ── 5. Build flat pivot table (student × subject) for the UI ──────────
            // This makes the frontend table trivial: one row per student,
            // one column per subject — exactly like the screenshot.
            $pivot = [];
            foreach ($studentIds as $stuId) {
                $student = $students[$stuId];
                $row = [
                    'student_id'  => $stuId,
                    'name'        => $student->name,
                    'roll_number' => $student->rollno,
                    'total'       => 0,
                ];
                foreach ($subjectIds as $subId) {
                    $markRow = $marksGrouped[$subId][$stuId] ?? null;
                    $marks   = $markRow ? (float) $markRow->marks : null;

                    $row['subjects'][$subId] = [
                        'subject_name' => $subjects[$subId]->subject,
                        'marks'        => $marks ?? 'N/A',
                    ];

                    if ($marks !== null) {
                        $row['total'] += $marks;
                    }
                }
                $pivot[] = $row;
            }

            return response()->json([
                'status'   => 200,
                'message'  => 'Subject Marks List',
                'subjects' => $subjects->values()->map(fn($s) => [   // header list
                    'id'   => $s->id,
                    'name' => $s->subject,
                ]),
                'data'     => $pivot,   // flat pivot — one row per student
                'report'   => $report,  // grouped by subject if also needed
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to get subjects marks report: ' . $e->getMessage(),
            ], 200);
        }
    }

    public function marksReportExcel(Request $request)
    {
        try {
            $response = $this->getMarksReport($request);
            $decoded  = json_decode($response->getContent(), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Invalid JSON response: ' . json_last_error_msg(),
                ], 500);
            }
            if (($decoded['status'] ?? null) !== 200) {
                return response()->json([
                    'status'  => 'error',
                    'message' => $decoded['message'] ?? 'Failed to generate report.',
                ], 500);
            }
            // ── Read from the pivot structure ──────────────────────────────────────
            $subjects = $decoded['subjects'] ?? [];
            $pivot    = $decoded['data']     ?? [];   // one row per student
            if (empty($pivot) || empty($subjects)) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'No data found.',
                ], 404);
            }
            // ── Build CSV in memory ────────────────────────────────────────────────
            $output = fopen('php://memory', 'w');
            if ($output === false) {
                throw new \Exception('Failed to open output stream.');
            }

            // Header row: # | Student | Subject1 | Subject2 | … | Total
            $headerRow = ['#', 'Student'];
            foreach ($subjects as $subject) {
                $headerRow[] = $subject['name'];
            }
            $headerRow[] = 'Total';
            fputcsv($output, $headerRow);
            // Data rows — pivot already ordered by roll number from API
            foreach ($pivot as $index => $student) {
                $row   = [$index + 1, $student['name']];
                $total = 0;
                foreach ($subjects as $subject) {
                    $subjectId = $subject['id'];
                    $marks     = $student['subjects'][$subjectId]['marks'] ?? 'N/A';
                    $row[] = $marks;
                    if ($marks !== 'N/A' && $marks !== null) {
                        $total += (float) $marks;
                    }
                }
                $row[] = $total;
                fputcsv($output, $row);
            }
            rewind($output);
            $csvContent = stream_get_contents($output);
            fclose($output);
            $fileName = 'marks_report_' . now()->format('Y_m_d_His') . '.csv';
            return response($csvContent, 200)->header('Content-Type', 'text/csv')->header('Content-Disposition', 'attachment; filename="' . $fileName . '"');
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to export report: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Marksheet
     */
    public function marksheet()
    {
        return view('marks.marksheet.index');
    }

    /**
     * Public School Exam-Wise
     */
    public function publicSchoolExamWise()
    {
        $classes = ClassMasterController::getClasses();
        $exams = ExamMasterController::getAllExam();

        return view('marks.marksheet.exam_wise_public_report', compact('classes', 'exams'));
    }

    public function publicSchoolExamWisePrint(Request $request)
    {
        $exam = $request->session()->get('exam');
        $class = $request->session()->get('class');
        $section = $request->session()->get('section');
        $students = $request->session()->get('students');

        // Pass the data to the view
        return view('marks.marksheet.exam_wise_public_report_print', [
            'exam' => $exam,
            'class' => $class,
            'section' => $section,
            'students' => $students,
        ]);
        // return view('marks.marksheet.exam_wise_public_report_print');
    }

    public function publicSchoolExamWisePrintStore(Request $request)
    {
        $request->validate([
            'exam' => [
                'required',
                'exists:exam_masters,id,active,1',
            ],
            'class' => [
                'required',
                'exists:class_masters,id,active,1',
            ],
            'section' => [
                'required',
                'exists:section_masters,id,active,1',
            ],
            'std' => 'required',
        ]);
        $exam = $request->exam;
        $classId = $request->class;
        $sectionId = $request->section;
        $students = $request->std;
        return redirect()->route('marks.marks-report.public-exam-wise.print')->with('exam', $exam)->with('class', $classId)->with('section', $sectionId)->with('students', $students);
    }

    /**
     * Play School Exam-Wise
     */
    public function playSchoolExamWise()
    {
        $classes = ClassMasterController::getClasses();
        $exams = ExamMasterController::getAllExam();

        return view('marks.marksheet.exam_wise_play_report', compact('classes', 'exams'));
    }

    public function playSchoolExamWisePrint(Request $request)
    {
        $exam = $request->session()->get('exam');
        $class = $request->session()->get('class');
        $section = $request->session()->get('section');
        $students = $request->session()->get('students');

        // Pass the data to the view
        return view('marks.marksheet.exam_wise_play_report_print', [
            'exam' => $exam,
            'class' => $class,
            'section' => $section,
            'students' => $students,
        ]);
        // return view('marks.marksheet.exam_wise_play_report_print');
    }

    public function playSchoolExamWisePrintStore(Request $request)
    {
        $request->validate([
            'exam' => [
                'required',
                'exists:exam_masters,id,active,1',
            ],
            'class' => [
                'required',
                'exists:class_masters,id,active,1',
            ],
            'section' => [
                'required',
                'exists:section_masters,id,active,1',
            ],
            'std' => 'required',
        ]);
        $exam = $request->exam;
        $classId = $request->class;
        $sectionId = $request->section;
        $students = $request->std;
        return redirect()->route('marks.marks-report.play-exam-wise.print')->with('exam', $exam)->with('class', $classId)->with('section', $sectionId)->with('students', $students);
    }

    private function getGrade($total, $marks, $classId, $examId, $sessionId, $subjectId = null, $isTotal = false, $isOralPracticalSubjectExists = false)
    {   $totalMarks = (int) $total;
        if ($marks == 'Ab.') {
            return 'Ab.';
        }

        // Subject-specific total grades
        if ($isTotal && !empty($subjectId) && $isOralPracticalSubjectExists) {
            $grade = DB::table('subject_grades')->select(['grade_name'])->where('subject_id', $subjectId)->where('is_overall', 1)->where('min_marks', '<=', $marks)->where('max_marks', '>=', $marks)->where('class_id', $classId)->where('exam_id', $examId)->where('session_id', $sessionId)->where('active', 1)->first();
            if (!empty($grade)) {
                return $grade->grade_name;
            }
        }
        /** Subject-specific total grades */
        if (!empty($subjectId) && $isTotal && !$isOralPracticalSubjectExists) {

            $grade = DB::table('subject_grades')->select(['grade_name'])->whereNull('is_overall')->where('subject_id', $subjectId)->where('min_marks', '<=', $marks)->where('max_marks', '>=', $marks)->where('class_id', $classId)->where('exam_id', $examId)->where('session_id', $sessionId)->where('active', 1)->first();
            if (!empty($grade)) {
                return $grade->grade_name;
            }
        }
        /** Subject-specific grades */
        if (!empty($subjectId) && !$isTotal) {
            $grade = DB::table('subject_grades')->select(['grade_name'])->whereNull('is_overall')->where('subject_id', $subjectId)->where('class_id', $classId)->where('exam_id', $examId)->where('session_id', $sessionId)->where('min_marks', '<=', $marks)->where('max_marks', '>=', $marks)->where('active', 1)->first();
            if (!empty($grade)) {
                return $grade->grade_name;
            }
        }

        /** Fallback  */

        if ($totalMarks == 5) {
            if ($marks == 5) {
                return 'A';
            } elseif ($marks == 4) {
                return 'B';
            } elseif ($marks == 3) {
                return 'C';
            } else {
                return 'D';
            }
        } elseif ($totalMarks == 10) {
            if ($marks >= 9 && $marks <= 10) {
                return 'A';
            } elseif ($marks >= 7 && $marks <= 8) {
                return 'B';
            } elseif ($marks >= 5 && $marks <= 6) {
                return 'C';
            } else {
                return 'D';
            }
        } elseif ($totalMarks == 20) {
            if ($marks >= 17 && $marks <= 20) {
                return 'A';
            } elseif ($marks >= 13 && $marks <= 16) {
                return 'B';
            } elseif ($marks >= 9 && $marks <= 12) {
                return 'C';
            } else {
                return 'D';
            }
        } elseif ($totalMarks == 25) {
            if ($marks >= 21 && $marks <= 25) {
                return 'A';
            } elseif ($marks >= 16 && $marks <= 20) {
                return 'B';
            } elseif ($marks >= 11 && $marks <= 15) {
                return 'C';
            } else {
                return 'D';
            }
        } elseif ($totalMarks == 30) {
            if ($marks >= 25 && $marks <= 30) {
                return 'A';
            } elseif ($marks >= 19 && $marks <= 24) {
                return 'B';
            } elseif ($marks >= 13 && $marks <= 18) {
                return 'C';
            } else {
                return 'D';
            }
        } elseif ($totalMarks == 45) {
            if ($marks >= 38 && $marks <= 45) {
                return 'A';
            } elseif ($marks >= 30 && $marks <= 37) {
                return 'B';
            } elseif ($marks >= 22 && $marks <= 29) {
                return 'C';
            } else {
                return 'D';
            }
        }
        elseif ($totalMarks == 50) {
            if ($marks >= 41 && $marks <= 50) {
                return 'A';
            } elseif ($marks >= 31 && $marks <= 40) {
                return 'B';
            } elseif ($marks >= 21 && $marks <= 30) {
                return 'C';
            } else {
                return 'D';
            }
        } elseif ($totalMarks == 70) {
            if ($marks >= 57 && $marks <= 70) {
                return 'A';
            } elseif ($marks >= 43 && $marks <= 56) {
                return 'B';
            } elseif ($marks >= 29 && $marks <= 42) {
                return 'C';
            } else {
                return 'D';
            }
        } elseif ($totalMarks == 100) {
            if ($marks >= 81 && $marks <= 100) {
                return 'A';
            } elseif ($marks >= 61 && $marks <= 80) {
                return 'B';
            } elseif ($marks >= 41 && $marks <= 60) {
                return 'C';
            } else {
                return 'D';
            }
        } elseif ($totalMarks == 150) {
            if ($marks >= 136 && $marks <= 150) {
                return 'A+';
            } elseif ($marks >= 121 && $marks <= 135) {
                return 'A';
            } elseif ($marks >= 91 && $marks <= 120) {
                return 'B+';
            } else {
                return 'B';
            }
        } elseif ($totalMarks == 200) {
            if ($marks >= 161 && $marks <= 200) {
                return 'A';
            } elseif ($marks >= 121 && $marks <= 160) {
                return 'B';
            } elseif ($marks >= 81 && $marks <= 120) {
                return 'C';
            } else {
                return 'D';
            }
        }

        return '';
    }

    private function getPGNurGradeSubject($total, $marks, $classId, $sessionId, $subjectId, $examId)
    {
        // Prevent illegal comparison with NULL or EMPTY
        if ($marks === null || $marks === '') {
            $marks = 0;
        }
        // Try to get grade from SubjectGrade table
        $grade = DB::table('subject_grades')->select(['grade_name'])->whereNull('is_overall')->where('subject_id', $subjectId)->where('class_id', $classId)->where('exam_id', $examId)->where('session_id', $sessionId)->where('min_marks', '<=', $marks)->where('max_marks', '>=', $marks)->where('active', 1)->first();
        if (!empty($grade)) {
            return $grade->grade_name;
        }
        if ($total == 0) {
            return 'E';
        }

        $percent_m = $marks * 100 / $total;
        if ($percent_m >= 86) {
            return 'A';
        } elseif ($percent_m >= 71) {
            return 'B';
        } elseif ($marks >= 51) {
            return 'C';
        } elseif ($marks >= 33) {
            return 'D';
        } else {
            return 'E';
        }
    }
    private function getPGNurGrade($total, $marks, $classId, $sessionId)
    {
        // Prevent illegal comparison with NULL or EMPTY
        if ($marks === null || $marks === '') {
            $marks = 0;
        }

        /* Try to get grade from SubjectGrade table */
        $grade = DB::table('subject_grades')->select(['grade_name'])->whereNull('subject_id')->where('is_overall', 4)->where('class_id', $classId)->whereNull('exam_id')->where('session_id', $sessionId)->where('min_marks', '<=', $marks)->where('max_marks', '>=', $marks)->where('active', 1)->first();
        if (!empty($grade)) {
            return $grade->grade_name;
        }
        if ($total == 0) {
            return 'E';
        }

        $percent_m = $marks * 100 / $total;
        if ($percent_m >= 86) {
            return 'A';
        } elseif ($percent_m >= 71) {
            return 'B';
        } elseif ($marks >= 51) {
            return 'C';
        } elseif ($marks >= 33) {
            return 'D';
        } else {
            return 'E';
        }
    }




    public function getMarkSheetReport(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'class' => 'required|exists:class_masters,id,active,1',
                'section' => 'required|exists:section_masters,id,active,1',
                'exam' => 'required|exists:exam_masters,id,active,1',
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $validator->errors(),
                ], 400);
            }
            $current_session = Session::get('marks_current_session');
            $sessionId = $current_session->id;
            $examId = $request->exam;
            $classId = $request->class;
            $sectionId = $request->section;
            $studentId = $request->std_id;
            $fields = [
                'stu_main_srno.session_id',
                'session_masters.session as session_name',
                'class_masters.class as class_name',
                'section_masters.section as section_name',
                'stu_main_srno.class',
                'stu_main_srno.section',
                'stu_main_srno.srno',
                'stu_main_srno.school',
                'stu_main_srno.rollno',
                'stu_main_srno.ssid',
                'stu_main_srno.active',
                'stu_detail.name',
                'stu_detail.dob',
                'stu_detail.srno',
                'parents_detail.srno',
                'parents_detail.f_name',
                'parents_detail.m_name',
            ];
            $studentQuery = StudentMasterController::getMarksheetStdWithNames(false, $fields)->where('stu_main_srno.class', $classId)->where('stu_main_srno.section', $sectionId)->where('stu_main_srno.session_id', $sessionId);
            if (!empty($studentId) && $studentId !== 'all') {
                $studentQuery->where('stu_main_srno.srno', $studentId);
            }
            $students = $studentQuery->get();

            if ($students->isNotEmpty()) {
                $exam = ExamMasterController::getAllExam(['id', 'exam'], ['id' => $examId]);
                $report = [
                    'student' => [],
                ];

                /** variables to collect the max marks data */
                $max_marks_written = 0;
                /** priority 1 */
                $max_marks_oral = 0;
                /** priority 2 */
                $max_marks_practicle = 0;
                /** priority 3 */
                $subjects = SubjectMasterController::getAllSubjects(['subject', 'id', 'subject_id', 'by_m_g', 'priority', 'class_id'], '', ['class_id' => $classId], [], true);
                foreach ($subjects as $subject) {

                    if (! empty($subject) && $subject['priority'] == 1 && $subject['by_m_g'] == 1) {
                        $marksMaster = MarksMaster::where('exam_id', $examId)->where('session_id', $sessionId)->where('class_id', $classId)->where('subject_id', $subject['id'])->where('active', 1)->get();
                        $max_marks_written = $marksMaster['0']['max_marks'] ?? 0;
                    }
                    if (! empty($subject) && $subject['priority'] == 2 && $subject['by_m_g'] == 1) {

                        $marksMaster = MarksMaster::where('exam_id', $examId)->where('session_id', $sessionId)->where('class_id', $classId)->where('subject_id', $subject['id'])->where('active', 1)->get();
                        $max_marks_oral = $marksMaster['0']['max_marks'] ?? 0;
                    }
                    if (! empty($subject) && $subject['priority'] == 3 && $subject['by_m_g'] == 1) {
                        $marksMaster = MarksMaster::where('exam_id', $examId)->where('session_id', $sessionId)->where('class_id', $classId)->where('subject_id', $subject['id'])->where('active', 1)->get();
                        $max_marks_practicle = $marksMaster['0']['max_marks'] ?? 0;
                    }
                }

                $total_maximum_marks = $max_marks_oral + $max_marks_written + $max_marks_practicle;

                foreach ($students as $key => $st) {
                    // $subjects = SubjectMasterController::getAllSubjects(['subject', 'id', 'subject_id', 'by_m_g', 'priority', 'class_id'], '', ['class_id' => $st->class], [], true);
                    $writtenSubjects = $subjects->whereNull('subject_id')->where('priority', 1)->values();
                    $oralSubjects = $subjects->whereNotNull('subject_id')->where('priority', 2)->values();
                    $practicalSubjects = $subjects->whereNotNull('subject_id')->where('priority', 3)->values();
                    $studentSubjects = [];

                    $marks = Marks::where('exam_id', $examId)->where('session_id', $sessionId)->where('class_id', $st->class)->where('srno', $st->srno)
                        // ->where('attendance', 1)
                        ->where('active', 1)->get();

                    $marksMaster = MarksMaster::where('exam_id', $examId)->where('session_id', $sessionId)->where('class_id', $st->class)->where('active', 1)->get();

                    foreach ($writtenSubjects as $writtenSubject) {
                        $isOralPracticalSubjectSExists = false;
                        $oral = $oralSubjects->where('subject_id', $writtenSubject->id)->where('by_m_g', $writtenSubject->by_m_g)->first();
                        $practical = $practicalSubjects->where('subject_id', $writtenSubject->id)->where('by_m_g', $writtenSubject->by_m_g)->first();

                        $writtenMarks = $marks->where('subject_id', $writtenSubject->id)->first();
                        $maxMarksWrittenGrade = $marksMaster->where('subject_id', $writtenSubject->id)->value('max_marks') ?? 0;

                        $oralMarks = null;
                        $maxMarksOralGrade = 0;
                        if ($oral) {
                            $oralMarks = $marks->where('subject_id', $oral->id)->first();
                            $maxMarksOralGrade = $marksMaster->where('subject_id', $oral->id)->value('max_marks') ?? 0;
                            $isOralPracticalSubjectSExists = true;
                        }

                        $practicalMarks = null;
                        $maxMarksPracticalGrade = 0;
                        if ($practical) {
                            $practicalMarks = $marks->where('subject_id', $practical->id)->first();
                            $maxMarksPracticalGrade = $marksMaster->where('subject_id', $practical->id)->value('max_marks') ?? 0;
                            $isOralPracticalSubjectSExists = true;
                        }

                        /* $writtenValue = $writtenMarks ? $writtenMarks->marks : null;
                        $oralValue = $oralMarks ? $oralMarks->marks : null;
                        $practicalValue = $practicalMarks ? $practicalMarks->marks : null; */

                        $writtenValue = ($writtenMarks && $writtenMarks->attendance == 1) ? $writtenMarks->marks : ($writtenMarks ? 'Ab.' : null);

                        $oralValue = ($oralMarks && $oralMarks->attendance == 1) ? $oralMarks->marks : ($oralMarks ? 'Ab.' : null);

                        $practicalValue = ($practicalMarks && $practicalMarks->attendance == 1) ? $practicalMarks->marks : ($practicalMarks ? 'Ab.' : null);

                        $totalMarks = 0;

                        $totalMaxMarks = $maxMarksWrittenGrade + $maxMarksOralGrade + $maxMarksPracticalGrade;

                        /* if ($writtenValue !== null) $totalMarks += $writtenValue;
                        if ($oralValue !== null) $totalMarks += $oralValue;
                        if ($practicalValue !== null) $totalMarks += $practicalValue; */
                        if ($writtenValue === 'Ab.' || $oralValue === 'Ab.' || $practicalValue === 'Ab.') {
                            $totalMarks = 'Ab.';
                        } else {
                            $totalMarks += is_numeric($writtenValue) ? $writtenValue : 0;
                            $totalMarks += is_numeric($oralValue) ? $oralValue : 0;
                            $totalMarks += is_numeric($practicalValue) ? $practicalValue : 0;
                        }

                        if ($writtenSubject->by_m_g == 1) {
                            $studentSubjects[] = [
                                'name' => $writtenSubject->subject,
                                'by_m_g' => $writtenSubject->by_m_g,
                                'written' => $st->school == 1 && $writtenSubject->by_m_g == 2 ? ($writtenValue !== null ? ($maxMarksWrittenGrade !== 0 ? $this->getGrade($maxMarksWrittenGrade, $writtenValue, $st->class, $examId, $st->session_id, $writtenSubject->id, false) : '') : '') : $writtenValue,
                                'oral' => $st->school == 1 && $writtenSubject->by_m_g == 2 ? ($oralValue !== null ? ($maxMarksOralGrade !== 0 ? $this->getGrade($maxMarksOralGrade, $oralValue, $st->class, $examId, $st->session_id, $oral->id, false) : '') : '') : $oralValue,
                                'practical' => $st->school == 1 && $writtenSubject->by_m_g == 2 ? ($practicalValue !== null ? ($maxMarksPracticalGrade !== 0 ? $this->getGrade($maxMarksPracticalGrade, $practicalValue, $st->class, $examId, $st->session_id, $practical->id, false) : '') : '') : $practicalValue,
                                'total' => $st->school == 1 && $writtenSubject->by_m_g == 2 ? (($writtenValue !== null || $oralValue !== null || $practicalValue !== null) ? ($totalMaxMarks !== 0 ? $this->getGrade($totalMaxMarks, $totalMarks, $st->class, $examId, $st->session_id, $writtenSubject->id, true, $isOralPracticalSubjectSExists) : '') : '') : $totalMarks,
                            ];
                        } else {
                            $studentSubjects[] = [
                                'name' => $writtenSubject->subject,
                                'by_m_g' => $writtenSubject->by_m_g,
                                'written' => $writtenValue !== null ? ($maxMarksWrittenGrade != 0 ? $this->getGrade($maxMarksWrittenGrade, $writtenValue, $st->class, $examId, $st->session_id,$writtenSubject->id, false) : '') : '',
                                'oral' => $oralValue !== null ? ($maxMarksOralGrade != 0 ? $this->getGrade($maxMarksOralGrade, $oralValue, $st->class, $examId, $st->session_id, $oral->id, false) : '') : '',
                                'practical' => $practicalValue != null ? ($maxMarksPracticalGrade != 0 ? $this->getGrade($maxMarksPracticalGrade, $practicalValue, $st->class, $examId, $st->session_id, $practical->id, false) : '') : '',
                                'total' => ($writtenValue != null || $oralValue != null || $practicalValue != null) ? ($totalMaxMarks != 0 ? $this->getGrade($totalMaxMarks, $totalMarks, $st->class, $examId, $st->session_id, $writtenSubject->id, true, $isOralPracticalSubjectSExists) : '') : '',
                            ];
                        }
                    }
                    $grandTotalMarks = array_sum(array_map(function ($item) {
                        if ($item['by_m_g'] == 1 && is_numeric($item['total'])) {
                            return $item['total'];
                        } else {

                            return 0;
                        }
                    }, $studentSubjects));

                    $report['student'][] = [
                        'session' => $st->session_name,
                        'logo' => config('myconfig.mylogo'),
                        'school' => $st->school == 1 ? 'St. Vivekanand Play House' : 'St. Vivekanand Public Secondary School',
                        'srno' => $st->srno,
                        'name' => $st->name ?? 'N/A',
                        'rollno' => $st->rollno,
                        'dob' => $st->dob ? date('d-M-Y', strtotime($st->dob)) : 'N/A',
                        'father_name' => $st->f_name,
                        'mother_name' => $st->m_name,
                        'class_name' => $st->class_name,
                        'section_name' => $st->section_name,
                        'exam_name' => array_values($exam)[0],
                        'principle_sign' => config('myconfig.mysignature'),
                        'subjects' => $studentSubjects,
                        'grand_total_marks' => $grandTotalMarks,
                    ];
                }

                $report['max_marks'][] = [
                    'written' => $max_marks_written,
                    'oral' => $max_marks_oral,
                    'practicle' => $max_marks_practicle,
                    'total_maximum_marks' => $total_maximum_marks,
                ];

                return response()->json([
                    'status' => 200,
                    'message' => 'Student With Marks List',
                    'data' => $report,
                ]);
            } else {
                return response()->json([
                    'status' => 202,
                    'message' => 'Student Not Found',
                    'data' => [],
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get report: ',
            ], 500);
        }
    }

    /**
     * Rank Report
     */
    public function rankReport()
    {
        $classes = ClassMasterController::getClasses();

        return view('marks.marks_entry.rank_class_wise', compact('classes'));
    }

    /**
     * Class Wise Rank Report Get
     */
    // public function classWiseRankReport(Request $request)
    // {
    //     try {
    //         $validator = Validator::make($request->all(), [
    //             'class' => 'required|exists:class_masters,id,active,1',
    //         ]);

    //         if ($validator->fails()) {
    //             return response()->json([
    //                 'status' => 'error',
    //                 'message' => $validator->errors(),
    //             ], 200);
    //         }

    //         $sessionId = session('marks_current_session')->id;
    //         $classId = $request->class;
    //         $fields = [
    //             'stu_main_srno.session_id',
    //             'class_masters.class as class_name',
    //             'section_masters.section as section_name',
    //             'stu_main_srno.class',
    //             'stu_main_srno.section',
    //             'stu_main_srno.srno',
    //             'stu_main_srno.school',
    //             'stu_main_srno.rollno',
    //             'stu_main_srno.ssid',
    //             'stu_main_srno.active',
    //             'stu_detail.name',
    //             'stu_detail.dob',
    //             'stu_detail.srno',
    //             'parents_detail.srno',
    //             'parents_detail.f_name',
    //             'parents_detail.m_name',
    //         ];
    //         // $students = StudentMasterController::getMarksheetStdWithNames(false, $fields)->where('stu_main_srno.class', $classId)->where('stu_main_srno.session_id', $sessionId)->get();
    //         $students = DB::table('stu_main_srno')
    //             ->leftJoin('stu_detail',      'stu_main_srno.srno',    '=', 'stu_detail.srno')
    //             ->leftJoin('parents_detail',  'stu_main_srno.srno',    '=', 'parents_detail.srno')
    //             ->leftJoin('class_masters',   'stu_main_srno.class',   '=', 'class_masters.id')
    //             ->leftJoin('section_masters', 'stu_main_srno.section', '=', 'section_masters.id')
    //             ->select($fields)
    //             ->where('stu_main_srno.class',      $classId)
    //             ->where('stu_main_srno.session_id', $sessionId)
    //             ->where('stu_main_srno.ssid',   1)
    //             ->where('stu_main_srno.active', 1)
    //             ->orderBy('stu_main_srno.rollno', 'asc')
    //             ->get();
    //         if ($students->isNotEmpty()) {
    //             $report = [];
    //             foreach ($students as $st) {

    //                 $marks = DB::table('marks')
    //                     ->leftJoin('subject_masters', 'marks.subject_id', '=', 'subject_masters.id')
    //                     ->where('subject_masters.by_m_g', 1)
    //                     ->where('marks.srno', $st->srno)
    //                     ->where('marks.class_id', $classId)->where('marks.session_id', $sessionId)->where('marks.active', 1)->get(['marks.subject_id', 'marks.marks']);
    //                 $totalMarks = $marks->sum('marks');
    //                 $totalMeeting = 0;
    //                 // Get total meetings
    //                 $totalMeetingsData = AttendanceSchedule::where('session_id', $sessionId)->sum('status');

    //                 if ($totalMeetingsData) {
    //                     $totalMeeting = $totalMeetingsData * 2;
    //                 }
    //                 $whereAttend = [
    //                     'where' => [
    //                         'session_id' => $sessionId,
    //                         'srno' => $st->srno,
    //                         'class' => $st->class,
    //                     ],
    //                 ];
    //                 $meetingsAttended = StdAttendanceController::getAttendance(['id', 'session_id', 'srno', 'class', 'status'], $whereAttend)->sum('status');
    //                 $meetingsAttended = $meetingsAttended ? ($meetingsAttended * 2) : 0;

    //                 $report[] = [
    //                     'class' => $st->class_name,
    //                     'section' => $st->section_name,
    //                     'srno' => $st->srno,
    //                     'rollno' => $st->rollno,
    //                     'name' => $st->name,
    //                     'total_marks' => $totalMarks,
    //                     'total_meeting' => $totalMeeting,
    //                     'meeting_attended' => $meetingsAttended,

    //                 ];
    //             }
    //             usort($report, function ($a, $b) {
    //                 return $b['total_marks'] <=> $a['total_marks'];
    //             });
    //             /* First, get unique marks and assign ranks */
    //             $uniqueMarks = [];
    //             $rank = 1;
    //             foreach ($report as $item) {
    //                 if (!isset($uniqueMarks[$item['total_marks']])) {
    //                     $uniqueMarks[$item['total_marks']] = $rank++;
    //                 }
    //             }
    //             /* Then assign ranks to all students */
    //             foreach ($report as &$student) {
    //                 $student['rank'] = $uniqueMarks[$student['total_marks']];
    //             }
    //             return response()->json([
    //                 'status' => 200,
    //                 'message' => 'Class-Wise Rank Report',
    //                 'data' => $report,
    //             ]);
    //         } else {
    //             return response()->json([
    //                 'status' => 200,
    //                 'message' => 'Class-Wise Rank Report',
    //                 'data' => [],
    //             ]);
    //         }
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'status' => 'error',
    //             'message' => 'Failed to get Class-Wise Rank Report',
    //         ], 200);
    //     }
    // }

    public function classWiseRankReport(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'class' => 'required|exists:class_masters,id,active,1',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $validator->errors(),
                ], 200);
            }

            $sessionId = session('marks_current_session')->id;
            $classId   = $request->class;

            // ─── 1. Fetch all students for this class in one query ───────────────
            $students = DB::table('stu_main_srno')
                ->leftJoin('stu_detail',      'stu_main_srno.srno',    '=', 'stu_detail.srno')
                ->leftJoin('parents_detail',  'stu_main_srno.srno',    '=', 'parents_detail.srno')
                ->leftJoin('class_masters',   'stu_main_srno.class',   '=', 'class_masters.id')
                ->leftJoin('section_masters', 'stu_main_srno.section', '=', 'section_masters.id')
                ->select([
                    'class_masters.class  as class_name',
                    'section_masters.section as section_name',
                    'stu_main_srno.class',
                    'stu_main_srno.section',
                    'stu_main_srno.srno',
                    'stu_main_srno.rollno',
                    'stu_detail.name',
                ])
                ->where('stu_main_srno.class',      $classId)
                ->where('stu_main_srno.session_id', $sessionId)
                ->where('stu_main_srno.ssid',       1)
                ->where('stu_main_srno.active',     1)
                ->orderBy('stu_main_srno.rollno', 'asc')
                ->get();

            if ($students->isEmpty()) {
                return response()->json([
                    'status'  => 200,
                    'message' => 'Class-Wise Rank Report',
                    'data'    => [],
                ]);
            }

            // Collect all srno values for use in bulk queries
            $srnoList = $students->pluck('srno')->all();

            // ─── 2. Fetch ALL marks for this class+session in ONE query ──────────
            //    (was: 1 query per student inside the loop)
            $allMarks = DB::table('marks')
                ->leftJoin('subject_masters', 'marks.subject_id', '=', 'subject_masters.id')
                ->where('subject_masters.by_m_g',  1)
                ->where('marks.class_id',           $classId)
                ->where('marks.session_id',         $sessionId)
                ->where('marks.active',             1)
                ->whereIn('marks.srno', $srnoList)
                ->select('marks.srno', 'marks.marks')
                ->get();

            // Group marks by srno and pre-sum them  →  ['srno' => totalMarks]
            $marksBySrno = $allMarks
                ->groupBy('srno')
                ->map(fn($rows) => $rows->sum('marks'));

            // ─── 3. Calculate total meetings ONCE (was recalculated every iteration) ──
            $totalMeetingsSum = AttendanceSchedule::where('session_id', $sessionId)->sum('status');
            $totalMeeting     = $totalMeetingsSum ? (int)($totalMeetingsSum * 2) : 0;

            // ─── 4. Fetch ALL attendance for this class+session in ONE query ─────
            //    (was: 1 query per student via StdAttendanceController::getAttendance)
            $allAttendance = DB::table('attendance')   // ← adjust table name if different
                ->where('session_id', $sessionId)
                ->where('class',      $classId)
                ->whereIn('srno',     $srnoList)
                ->select('srno', 'status')
                ->get();

            // Group attendance by srno and pre-sum  →  ['srno' => attendedCount*2]
            $attendanceBySrno = $allAttendance
                ->groupBy('srno')
                ->map(fn($rows) => (int)($rows->sum('status') * 2));

            // ─── 5. Build the report array using pre-fetched data ────────────────
            $report = [];
            foreach ($students as $st) {
                $report[] = [
                    'class'            => $st->class_name,
                    'section'          => $st->section_name,
                    'srno'             => $st->srno,
                    'rollno'           => $st->rollno,
                    'name'             => $st->name,
                    'total_marks'      => (int) ($marksBySrno[$st->srno] ?? 0),
                    'total_meeting'    => $totalMeeting,
                    'meeting_attended' => $attendanceBySrno[$st->srno] ?? 0,
                ];
            }

            // ─── 6. Sort by marks descending ────────────────────────────────────
            usort($report, fn($a, $b) => $b['total_marks'] <=> $a['total_marks']);

            // ─── 7. Assign ranks (tie-aware) ────────────────────────────────────
            $uniqueMarks = [];
            $rank        = 1;
            foreach ($report as $item) {
                if (!isset($uniqueMarks[$item['total_marks']])) {
                    $uniqueMarks[$item['total_marks']] = $rank++;
                }
            }
            foreach ($report as &$student) {
                $student['rank'] = $uniqueMarks[$student['total_marks']];
            }
            unset($student); // break reference

            return response()->json([
                'status'  => 200,
                'message' => 'Class-Wise Rank Report',
                'data'    => $report,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to get Class-Wise Rank Report'.$e->getMessage(),
            ], 200);
        }
    }

    /**
     * Class-Wise Rank Report Excel File
     */
    public function classWiseRankReportExcel(Request $request)
    {
        try {
            $response = $this->classWiseRankReport($request);

            if ($response->getStatusCode() !== 200) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to generate report: '.$response->getContent(),
                ], 500);
            }

            $decodedResponse = json_decode($response->getContent(), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return response()->json(['status' => 'error', 'message' => 'Invalid JSON response: '.json_last_error_msg()], 500);
            }

            $reportData = $decodedResponse['data'] ?? null;

            if (! $reportData) {
                return response()->json(['status' => 'error', 'message' => 'No data found'], 404);
            }

            $fileName = 'class_wise_rank_report.csv';

            // Create a temporary file in memory
            $csvContent = '';
            $handle = fopen('php://memory', 'w');

            if ($handle === false) {
                throw new \Exception('Failed to open memory stream.');
            }

            // Set the CSV column headers
            $headers = ['Class', 'Section', 'SRNO', 'Name', 'Total Obt. Marks', 'Rank', 'Total Meetings', 'Meetings Attended'];
            fputcsv($handle, $headers);

            foreach ($reportData as $row) {
                fputcsv($handle, [
                    $row['class'],
                    $row['section'],
                    $row['srno'],
                    $row['name'],
                    $row['total_marks'],
                    $row['rank'],
                    $row['total_meeting'],
                    $row['meeting_attended'],
                ]);
            }

            // Rewind the memory to the start
            rewind($handle);

            // Capture the content into a string
            $csvContent = stream_get_contents($handle);
            fclose($handle);

            // Return the content as a response for download
            return response($csvContent, 200)
                ->header('Content-Type', 'text/csv')
                ->header('Content-Disposition', 'attachment; filename="'.$fileName.'"');
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to export report',
            ], 500);
        }
    }

    /**
     * Final Marksheet Only For Class PG And Nursary
     */
    public function finalMarksheetOnlyForClassPGAndNursary()
    {
        $classes = ClassMasterController::getClasses();

        return view('marks.marksheet.marksheet_final_pg_nur', compact('classes'));
    }

    public function finalMarksheetPGNURPrint(Request $request)
    {
        // Retrieve the data from the session
        $class = $request->session()->get('class');
        $section = $request->session()->get('section');
        $students = $request->session()->get('students');
        $sessionMessage = $request->session()->get('sessionMessage');
        $dateMessage = $request->session()->get('dateMessage');

        // Pass the data to the view
        return view('marks.marksheet.marksheet_final_pg_nur_print', [
            'class' => $class,
            'section' => $section,
            'students' => $students,
            'sessionMessage' => $sessionMessage,
            'dateMessage' => $dateMessage,
        ]);

        // return view('marks.marksheet.marksheet_final_pg_nur_print');
    }

    public function finalMarksheetPGNURStore(Request $request)
    {
        $request->validate([
            'class' => [
                'required',
                'exists:class_masters,id,active,1',
            ],
            'section' => [
                'required',
                'exists:section_masters,id,active,1',
            ],
            'std' => 'required',
        ]);
        $classId = $request->class;
        $sectionId = $request->section;
        $students = $request->std;
        $sessionMessage = $request->sessionMessage;
        $dateMessage = $request->dateMessage;

        return redirect()->route('marks.marks-report.marksheet.pg.nursary.print')
            ->with('class', $classId)
            ->with('section', $sectionId)
            ->with('students', $students)
            ->with('sessionMessage', $sessionMessage)
            ->with('dateMessage', $dateMessage);
    }

    /**
     * Final Marksheet Only For Class PG And Nuursary Report
     */
    public function finalMarksheetClassPGAndNursaryReport(Request $request)
    {
        try {
            $validated = $request->validate([
                'class'   => 'required|exists:class_masters,id,active,1',
                'section' => 'required|exists:section_masters,id,active,1',
            ]);

            $session = session('marks_current_session');
            if (!$session) {
                return response()->json([
                    'error'   => 'No active session found',
                    'message' => 'Set Current Session',
                ], 400);
            }

            $classId   = $validated['class'];
            $sectionId = $validated['section'];
            $sessionId = $session->id;
            $srno      = $request->std_id;

            $fields = [
                'stu_main_srno.session_id',
                'session_masters.session as session_name',
                'class_masters.class as class_name',
                'section_masters.section as section_name',
                'stu_main_srno.class',
                'stu_main_srno.section',
                'stu_main_srno.srno',
                'stu_main_srno.school',
                'stu_main_srno.rollno',
                'stu_main_srno.ssid',
                'stu_main_srno.active',
                'stu_detail.name',
                'stu_detail.dob',
                'stu_detail.srno',
                'parents_detail.srno',
                'parents_detail.f_name',
                'parents_detail.m_name',
            ];

            // ── 1. Fetch all students in ONE query ────────────────────────────────
            $studentQuery = StudentMasterController::getMarksheetStdWithNames(false, $fields)
                ->where('stu_main_srno.session_id', $sessionId)
                ->where('stu_main_srno.class',      $classId)
                ->where('stu_main_srno.section',    $sectionId);

            if (!empty($srno) && $srno !== 'all') {
                $studentQuery->where('stu_main_srno.srno', $srno);
            }

            $students = $studentQuery->get();

            if ($students->isEmpty()) {
                return response()->json([
                    'error'   => 'No students found',
                    'message' => 'No students in this class and section',
                ], 404);
            }

            $studentIds = $students->pluck('srno')->toArray();

            // ── 2. Fetch subjects ONCE (outside loop) ─────────────────────────────
            $subFields = ['id', 'class_id', 'subject', 'subject_id', 'by_m_g', 'priority', 'active'];
            $subjects  = SubjectMasterController::getAllSubjects(
                $subFields, '', ['class_id' => $classId], ['by_m_g' => 'asc'], true
            );
            $subjectIds = $subjects->pluck('id')->toArray();

            // ── 3. Fetch exams ONCE (outside loop) ────────────────────────────────
            $exams   = ExamMasterController::getAllExam(['id', 'exam']); // plain array [id => name]
            $examIds = array_keys($exams);

            // ── 4. Fetch ALL marks joined with marks_masters in ONE query ──────────
            // marks table has no max_marks — it lives in marks_masters
            $allMarks = DB::table('marks')
                ->select(
                    'marks.exam_id',
                    'marks.subject_id',
                    'marks.srno',
                    'marks.marks',
                    'marks_masters.max_marks',
                    'marks_masters.min_marks'
                )
                ->leftJoin('marks_masters', function ($join) {
                    $join->on('marks_masters.exam_id',    '=', 'marks.exam_id')
                        ->on('marks_masters.subject_id', '=', 'marks.subject_id')
                        ->on('marks_masters.class_id',   '=', 'marks.class_id')
                        ->on('marks_masters.session_id', '=', 'marks.session_id');
                })
                ->where('marks.session_id', $sessionId)
                ->where('marks.class_id',   $classId)
                ->where('marks.active',     1)
                ->whereIn('marks.exam_id',    $examIds)
                ->whereIn('marks.subject_id', $subjectIds)
                ->whereIn('marks.srno',       $studentIds)
                ->get()
                ->groupBy('srno')
                ->map(fn($rows) => $rows->groupBy('exam_id')
                    ->map(fn($rows) => $rows->keyBy('subject_id')));

            // ── 5. Fetch valid exam IDs ONCE (exams that have marks entered) ───────
            $activeExamIds = DB::table('marks')
                ->where('session_id', $sessionId)
                ->where('class_id',   $classId)
                ->where('active',     1)
                ->whereIn('exam_id',  $examIds)
                ->pluck('exam_id')
                ->unique()
                ->toArray();

            // ── 6. Fetch attendance ONCE for all students ─────────────────────────
            $totalAttendanceDays = DB::table('attendance_schedule')
                ->where('session_id', $sessionId)
                ->sum('status');

            $attendanceMap = StdAttendanceController::getAttendance([
                    'id', 'status', 'session_id', 'class', 'section', 'srno'
                ])
                ->where('session_id', $sessionId)
                ->where('class',      $classId)
                ->where('section',    $sectionId)
                ->whereIn('srno',     $studentIds)
                ->get()
                ->groupBy('srno')
                ->map(fn($rows) => $rows->sum('status'));

            // ── 7. Build report in memory (zero extra queries) ────────────────────
            $studentReports = [];

            foreach ($students as $student) {
                $stuId        = $student->srno;
                $studentMarks = $allMarks[$stuId] ?? collect();
                $subjectMarks = [];
                $totalMax     = 0;
                $totalObtained = 0;

                foreach ($subjects as $subject) {
                    $subId = $subject->id;

                    $subjectResult = [
                        'subject_id'     => $subId,
                        'subject_name'   => $subject->subject,
                        'sub_subject_id' => $subject->subject_id,
                        'by_m_g'         => $subject->by_m_g,
                        'exams'          => [],
                    ];

                    $subjectTotalMax      = 0;
                    $subjectTotalObtained = 0;

                    foreach ($exams as $examId => $examName) {

                        // Skip exams with no marks data entered at all
                        if (!in_array($examId, $activeExamIds)) {
                            continue;
                        }

                        // Get mark from in-memory grouped collection
                        $markRow = $studentMarks[$examId][$subId] ?? null;

                        if ($markRow) {
                            $obtainedMarks = $markRow->marks     ?? 0;
                            $maxMarks      = $markRow->max_marks ?? 0;

                            $subjectResult['exams'][] = [
                                'exam_id'        => $examId,
                                'exam_name'      => $examName,
                                'obtained_marks' => $obtainedMarks,
                                'max_marks'      => $maxMarks,
                                'grade'          => $this->getPGNurGradeSubject(
                                                        $maxMarks, $obtainedMarks,
                                                        $classId, $sessionId, $subId, $examId
                                                    ) ?? 'Abst',
                                'status'         => $obtainedMarks ? 'Present' : 'Abst',
                            ];

                            $subjectTotalMax      += $maxMarks;
                            $subjectTotalObtained += $obtainedMarks;

                        } else {
                            $subjectResult['exams'][] = [
                                'exam_id'        => $examId,
                                'exam_name'      => $examName,
                                'obtained_marks' => 0,
                                'max_marks'      => 0,
                                'grade'          => $this->getPGNurGradeSubject(
                                                        0, 0,
                                                        $classId, $sessionId, $subId, $examId
                                                    ) ?? 'Abst',
                                'status'         => 'Abst',
                            ];
                        }
                    }

                    // Overall subject grade based on totals
                    $grade = $this->getPGNurGrade(
                        $subjectTotalMax, $subjectTotalObtained, $classId, $sessionId
                    );

                    $subjectResult['total_max']     = ($subjectTotalMax === 0      || $grade === null) ? 'Abst' : $grade;
                    $subjectResult['total_obtained'] = ($subjectTotalObtained === 0 || $grade === null) ? 'Abst' : $grade;

                    $totalMax      += $subjectTotalMax;
                    $totalObtained += $subjectTotalObtained;

                    $subjectMarks[] = $subjectResult;
                }

                // Attendance from in-memory map
                $studentAttendance = $attendanceMap[$stuId] ?? 0;

                $studentReports[] = [
                    'student_details' => [
                        'name'        => $student->name,
                        'sr_no'       => $stuId,
                        'father_name' => $student->f_name,
                        'mother_name' => $student->m_name,
                        'class'       => $student->class_name,
                        'section'     => $student->section_name,
                        'roll_no'     => $student->rollno,
                        'dob'         => $student->dob
                                            ? Carbon::parse($student->dob)->format('d-M-Y')
                                            : 'N/A',
                    ],
                    'subject_marks' => $subjectMarks,
                    'total_marks'   => [
                        'max_marks'      => $totalMax,
                        'obtained_marks' => $totalObtained,
                    ],
                    'attendance' => [
                        'days_present' => $studentAttendance * 2,
                        'total_days'   => $totalAttendanceDays * 2,
                    ],
                    'result_data' => [
                        'result'                => 'Pass',
                        'result_date_message'   => $request->dateMessage    ?? '',
                        'session_start_message' => $request->sessionMessage ?? '',
                    ],
                ];
            }

            return response()->json([
                'session' => $session->session,
                'logo'    => [
                    'school_logo'    => config('myconfig.mylogo'),
                    'principal_sign' => config('myconfig.mysignature'),
                ],
                'students' => $studentReports,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error'   => 'Access Denied',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Final Marksheet Only For Class KG
     */
    public function finalMarksheetOnlyForClassKG()
    {
        $classes = ClassMasterController::getClasses();

        return view('marks.marksheet.marksheet_final_kg', compact('classes'));
    }

    /**
     * Final Marksheet Only For Class KG Report
     */
    public function finalMarksheetKGPrint(Request $request)
    {
        // Retrieve the data from the session
        $class = $request->session()->get('class');
        $section = $request->session()->get('section');
        $students = $request->session()->get('students');
        $sessionMessage = $request->session()->get('sessionMessage');
        $dateMessage = $request->session()->get('dateMessage');

        // Pass the data to the view
        return view('marks.marksheet.marksheet_final_kg_print', [
            'class' => $class,
            'section' => $section,
            'students' => $students,
            'sessionMessage' => $sessionMessage,
            'dateMessage' => $dateMessage,
        ]);
    }

    public function finalMarksheetOnlyForClassKGStore(Request $request)
    {
        $request->validate([
            'class' => [
                'required',
                'exists:class_masters,id,active,1',
            ],
            'section' => [
                'required',
                'exists:section_masters,id,active,1',
            ],
            'std' => 'required',
        ]);
        $classId = $request->class;
        $sectionId = $request->section;
        $students = $request->std;
        $sessionMessage = $request->sessionMessage;
        $dateMessage = $request->dateMessage;

        return redirect()->route('marks.marks-report.marksheet.kg.print')
            ->with('class', $classId)
            ->with('section', $sectionId)
            ->with('students', $students)
            ->with('sessionMessage', $sessionMessage)
            ->with('dateMessage', $dateMessage);
    }

    public function finalMarksheetClassKGReport(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'class'    => 'required|exists:class_masters,id,active,1',
            'section'  => 'required|exists:section_masters,id,active,1',
            'students' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success'  => false,
                'error'    => 'Validation Error',
                'messages' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();
        $session   = session('marks_current_session');

        if (!$session) {
            return response()->json([
                'error'   => 'Session Error',
                'message' => 'No active session found',
            ], 404);
        }

        $classId   = $validated['class'];
        $sectionId = $validated['section'];
        $sessionId = $session->id;

        // ── Resolve student IDs ───────────────────────────────────────────────────
        $rawStudents = trim($request->students ?? '');

        if (empty($rawStudents) || $rawStudents === 'all') {
            $studentIds = StudentMasterController::getMarksheetStdWithNames(false, ['stu_main_srno.srno'])
                ->where('stu_main_srno.session_id', $sessionId)
                ->where('stu_main_srno.class',      $classId)
                ->where('stu_main_srno.section',    $sectionId)
                ->pluck('stu_main_srno.srno')
                ->toArray();
        } else {
            // Single student ID
            $studentIds = [$rawStudents];
        }

        if (empty($studentIds)) {
            return response()->json([
                'success' => false,
                'error'   => 'No students found for this class and section',
            ], 404);
        }

        $fields = [
            'stu_main_srno.session_id',
            'session_masters.session as session_name',
            'class_masters.class as class_name',
            'section_masters.section as section_name',
            'stu_main_srno.class',
            'stu_main_srno.section',
            'stu_main_srno.srno',
            'stu_main_srno.school',
            'stu_main_srno.rollno',
            'stu_main_srno.ssid',
            'stu_main_srno.active',
            'stu_detail.name',
            'stu_detail.dob',
            'stu_detail.srno',
            'parents_detail.srno',
            'parents_detail.f_name',
            'parents_detail.m_name',
        ];

        // ── 1. Fetch ALL students in ONE query ────────────────────────────────────
        $students = StudentMasterController::getMarksheetStdWithNames(false, $fields)
            ->where('stu_main_srno.session_id', $sessionId)
            ->where('stu_main_srno.class',      $classId)
            ->where('stu_main_srno.section',    $sectionId)
            ->whereIn('stu_main_srno.srno',     $studentIds)
            ->get()
            ->keyBy('srno');

        if ($students->isEmpty()) {
            return response()->json([
                'success' => false,
                'error'   => 'No students found',
            ], 404);
        }

        $foundStudentIds = $students->keys()->toArray();

        // ── 2. Fetch subjects ONCE ────────────────────────────────────────────────
        $subFields = ['id', 'class_id', 'subject', 'subject_id', 'by_m_g', 'priority', 'active'];
        $subjects  = SubjectMasterController::getAllSubjects(
            $subFields, '', ['class_id' => $classId], ['by_m_g' => 'asc'], true
        );
        $subjectIds = $subjects->pluck('id')->toArray();

        // ── 3. Fetch exams ONCE ───────────────────────────────────────────────────
        $exams   = ExamMasterController::getAllExam(['id', 'exam']); // [id => name]
        $examIds = array_keys($exams);

        // ── 4. Fetch ALL marks + max_marks in ONE query (join marks_masters) ──────
        $allMarks = DB::table('marks')
            ->select(
                'marks.srno',
                'marks.exam_id',
                'marks.subject_id',
                'marks.marks',
                'marks.attendance',
                'marks_masters.max_marks',
                'marks_masters.min_marks'
            )
            ->leftJoin('marks_masters', function ($join) {
                $join->on('marks_masters.exam_id',    '=', 'marks.exam_id')
                    ->on('marks_masters.subject_id', '=', 'marks.subject_id')
                    ->on('marks_masters.class_id',   '=', 'marks.class_id')
                    ->on('marks_masters.session_id', '=', 'marks.session_id');
            })
            ->where('marks.session_id', $sessionId)
            ->where('marks.class_id',   $classId)
            ->where('marks.active',     1)
            ->whereIn('marks.srno',       $foundStudentIds)
            ->whereIn('marks.exam_id',    $examIds)
            ->whereIn('marks.subject_id', $subjectIds)
            ->get()
            ->groupBy('srno')
            ->map(fn($rows) => $rows->groupBy('exam_id')
                ->map(fn($rows) => $rows->keyBy('subject_id')));

        // ── 5. Fetch marks_masters ONCE (for absent students with no mark row) ────
        $masterMarks = DB::table('marks_masters')
            ->select('exam_id', 'subject_id', 'max_marks', 'min_marks')
            ->where('session_id', $sessionId)
            ->where('class_id',   $classId)
            ->where('active',     1)
            ->whereIn('exam_id',    $examIds)
            ->whereIn('subject_id', $subjectIds)
            ->get()
            ->groupBy('exam_id')
            ->map(fn($rows) => $rows->keyBy('subject_id'));

        // ── 6. Fetch ALL subject grades in ONE query ──────────────────────────────
        $allSubjectGrades = DB::table('subject_grades')
            ->select([
                'grade_name', 'subject_id', 'class_id',
                'exam_id', 'session_id', 'min_marks',
                'max_marks', 'is_overall',
            ])
            ->where('session_id', $sessionId)
            ->where('class_id',   $classId)
            ->where('active',     1)
            ->whereIn('subject_id', $subjectIds)
            ->get();

        // Exam-wise grades: [subject_id][exam_id] => collection of grade rows
        $examWiseGrades = $allSubjectGrades
            ->filter(fn($row) => is_null($row->is_overall) && !is_null($row->exam_id))
            ->groupBy('subject_id')
            ->map(fn($rows) => $rows->groupBy('exam_id'));

        // Overall grades: [subject_id] => collection of grade rows
        $overallGrades = $allSubjectGrades
            ->filter(fn($row) => $row->is_overall == 4 && is_null($row->exam_id))
            ->groupBy('subject_id');

        // ── 7. Fetch attendance ONCE for all students ─────────────────────────────
        $totalAttendanceDays = DB::table('attendance_schedule')
            ->where('session_id', $sessionId)
            ->sum('status');

        $attendanceMap = StdAttendanceController::getAttendance([
                'id', 'status', 'session_id', 'class', 'section', 'srno',
            ])
            ->where('session_id', $sessionId)
            ->where('class',      $classId)
            ->where('section',    $sectionId)
            ->whereIn('srno',     $foundStudentIds)
            ->get()
            ->groupBy('srno')
            ->map(fn($rows) => $rows->sum('status'));

        // ── 8. Build all report cards in memory (zero extra queries) ──────────────
        $reportCards = [];

        foreach ($studentIds as $stuId) {
            $stuId = (string) $stuId;

            if (!$students->has($stuId)) {
                $reportCards[] = [
                    'student_id' => $stuId,
                    'error'      => 'Student not found',
                ];
                continue;
            }

            try {
                $student      = $students[$stuId];
                $studentMarks = $allMarks[$stuId] ?? collect();
                $marksData    = [];

                foreach ($subjects as $subject) {
                    $subId         = $subject->id;
                    $subjectMarks  = [];
                    $totalMax      = 0;
                    $totalObtained = 0;

                    foreach ($exams as $examId => $examName) {
                        // Only include exams that have a marks_masters entry
                        $master   = $masterMarks[$examId][$subId] ?? null;
                        $maxMarks = $master ? ($master->max_marks ?? null) : null;

                        if ($maxMarks === null) {
                            continue;
                        }

                        $markRow       = $studentMarks[$examId][$subId] ?? null;
                        $isAbsent      = !$markRow || $markRow->attendance != 1;
                        $obtainedMarks = !$isAbsent ? ($markRow->marks ?? 0) : 0;

                        $grade = $this->resolveGrade(
                            $obtainedMarks, $maxMarks, $subId, $examId, true,
                            $examWiseGrades, $overallGrades
                        );

                        $subjectMarks[] = [
                            'exam_id'        => $examId,
                            'exam_name'      => $examName,
                            'max_marks'      => $maxMarks,
                            'obtained_marks' => ($maxMarks > 0 && $isAbsent) ? 'Abs' : $obtainedMarks,
                            'grade'          => $grade,
                        ];

                        $totalMax      += $maxMarks;
                        $totalObtained += $obtainedMarks;
                    }

                    $overallGrade = $this->resolveGrade(
                        $totalObtained, $totalMax, $subId, null, false,
                        $examWiseGrades, $overallGrades
                    );

                    $marksData[] = [
                        'subject_id'           => $subId,
                        'subject_name'         => $subject->subject,
                        'by_m_g'               => $subject->by_m_g,
                        'exam_marks'           => $subjectMarks,
                        'total_max_marks'      => $totalMax,
                        'total_obtained_marks' => $totalObtained,
                        'percentage'           => $totalMax > 0
                                                    ? round(($totalObtained / $totalMax) * 100, 2)
                                                    : 'NaN',
                        'overall_grade'        => $overallGrade,
                    ];
                }

                // ── Summary (inline — no extra loop function needed) ───────────────
                $summaryMax      = 0;
                $summaryObtained = 0;
                $subjectGrades   = [];

                foreach ($marksData as $subjectData) {
                    if ($subjectData['by_m_g'] !== 2) {
                        $summaryMax      += $subjectData['total_max_marks'];
                        $summaryObtained += $subjectData['total_obtained_marks'];
                        $subjectGrades[]  = $subjectData['overall_grade'];
                    }
                }

                $studentAttendance = $attendanceMap[$stuId] ?? 0;

                $reportCards[] = [
                    'student_id'      => $stuId,
                    'student_details' => [
                        'name'        => $student->name,
                        'father_name' => $student->f_name,
                        'mother_name' => $student->m_name,
                        'class'       => $student->class_name,
                        'section'     => $student->section_name,
                        'roll_no'     => $student->rollno,
                        'dob'         => $student->dob,
                        'ssid'        => $student->ssid,
                    ],
                    'attendance' => [
                        'days_present'          => $studentAttendance * 2,
                        'total_days'            => $totalAttendanceDays * 2,
                        'result_date_message'   => $request->dateMessage    ?? '',
                        'session_start_message' => $request->sessionMessage ?? '',
                    ],
                    'marks_data' => $marksData,
                    'summary'    => [
                        'total_max_marks'      => $summaryMax,
                        'total_obtained_marks' => $summaryObtained,
                        'overall_percentage'   => $summaryMax > 0
                                                    ? round(($summaryObtained / $summaryMax) * 100, 2)
                                                    : null,
                        'overall_result'       => $this->determineOverallResult($subjectGrades),
                    ],
                ];

            } catch (\Exception $e) {
                $reportCards[] = [
                    'student_id' => $stuId,
                    'error'      => $e->getMessage(),
                ];
            }
        }

        return response()->json([
            'success'        => true,
            'logo'           => [
                'school_logo'    => config('myconfig.mylogo'),
                'principal_sign' => config('myconfig.mysignature'),
            ],
            'session'        => [
                'name' => $session->session,
                'id'   => $sessionId,
            ],
            'report_cards'   => $reportCards,
            'class_id'       => $classId,
            'section_id'     => $sectionId,
            'total_students' => count($reportCards),
        ]);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────────

    private function resolveGrade($obtainedMarks, $maxMarks, $subjectId, $examId, $isExamWise, $examWiseGrades, $overallGrades)
    {
        if ($isExamWise) {
            $rows  = $examWiseGrades[$subjectId][$examId] ?? collect();
            $grade = $rows->first(fn($g) =>
                $g->min_marks <= $obtainedMarks && $g->max_marks >= $obtainedMarks
            );
            if ($grade) return $grade->grade_name;
        } else {
            $rows  = $overallGrades[$subjectId] ?? collect();
            $grade = $rows->first(fn($g) =>
                $g->min_marks <= $obtainedMarks && $g->max_marks >= $obtainedMarks
            );
            if ($grade) return $grade->grade_name;
        }

        // Fallback to hardcoded ranges
        if ($maxMarks == 50) {
            if ($obtainedMarks >= 46) return 'A+';
            if ($obtainedMarks >= 41) return 'A';
            if ($obtainedMarks >= 31) return 'B+';
            return 'B';
        }

        if ($maxMarks == 150) {
            if ($obtainedMarks >= 136) return 'A+';
            if ($obtainedMarks >= 121) return 'A';
            if ($obtainedMarks >= 91)  return 'B+';
            return 'B';
        }

        return 'ER';
    }

    private function determineOverallResult($grades)
    {
        // Simple result determination based on grades
        $failGrades = array_filter($grades, function ($grade) {
            return $grade == 'F' || $grade == 'D';
        });

        return count($failGrades) > 0 ? 'Fail' : 'Pass';
    }

    private function gradeDisplay($marksObtained, $maxMarks)
    {
        if ($marksObtained == 'Abst') {
            return 'Abst';
        }

        $percentage = ($maxMarks > 0) ? ($marksObtained * 100) / $maxMarks : 0;

        // Define grade based on percentage
        if ($percentage >= 90) {
            return 'A+';
        } elseif ($percentage >= 80) {
            return 'A';
        } elseif ($percentage >= 70) {
            return 'B+';
        } elseif ($percentage >= 60) {
            return 'B';
        } elseif ($percentage >= 50) {
            return 'C';
        } elseif ($percentage >= 40) {
            return 'D';
        } else {
            return 'F';
        }
    }

    /**
     * Final Marksheet Only For Class First And Second
     */
    public function finalMarksheetOnlyForClassFirstSecond()
    {
        $classes = ClassMasterController::getClasses();

        return view('marks.marksheet.marksheet_final_first_second', compact('classes'));
    }

    /**
     * Final Marksheet Report Only For Class First And Second
     */
    public function finalMarksheetFirstSecondPrint(Request $request)
    {
        // Retrieve the data from the session
        $class = $request->session()->get('class');
        $section = $request->session()->get('section');
        $students = $request->session()->get('students');
        $sessionMessage = $request->session()->get('sessionMessage');
        $dateMessage = $request->session()->get('dateMessage');

        // Pass the data to the view
        return view('marks.marksheet.marksheet_final_first_second_print', [
            'class' => $class,
            'section' => $section,
            'students' => $students,
            'sessionMessage' => $sessionMessage,
            'dateMessage' => $dateMessage,
        ]);
    }

    public function finalMarksheetFirstSecondStore(Request $request)
    {
        $request->validate([
            'class' => [
                'required',
                'exists:class_masters,id,active,1',
            ],
            'section' => [
                'required',
                'exists:section_masters,id,active,1',
            ],
            'std' => 'required',
        ]);
        $classId = $request->class;
        $sectionId = $request->section;
        $students = $request->std;
        $sessionMessage = $request->sessionMessage;
        $dateMessage = $request->dateMessage;

        return redirect()->route('marks.marks-report.marksheet.first.second.print')
            ->with('class', $classId)
            ->with('section', $sectionId)
            ->with('students', $students)
            ->with('sessionMessage', $sessionMessage)
            ->with('dateMessage', $dateMessage);
    }

    public function finalMarksheetClassFirstSecond(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'class'    => 'required|exists:class_masters,id,active,1',
                'section'  => 'required|exists:section_masters,id,active,1',
                'students' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success'  => false,
                    'error'    => 'Validation Error',
                    'messages' => $validator->errors(),
                ], 422);
            }

            $validatedData = $validator->validated();
            $session       = session('marks_current_session');

            if (blank($session)) {
                return response()->json(['error' => 'Current session not set'], 400);
            }

            $classId   = $validatedData['class'];
            $sectionId = $validatedData['section'];
            $sessionId = $session->id;
            $studentId = $request->students;

            // ── 1. Fetch main subjects (priority 1) ONCE ──────────────────────────
            $subFields  = ['id', 'subject', 'by_m_g', 'priority', 'subject_id', 'class_id'];
            $subWhere   = ['class_id' => $classId];
            $subWhereIn = ['priority' => [1]];
            $mainSubjects = SubjectMasterController::getAllSubjects(
                $subFields, '', $subWhere, ['by_m_g' => 'asc'], true, '', false, $subWhereIn
            );

            if ($mainSubjects->isEmpty()) {
                return response()->json(['error' => 'No subjects found'], 404);
            }

            $mainSubjectIds = $mainSubjects->pluck('id')->toArray();

            // ── 2. Fetch ALL sub-subjects (priority 2 / oral) ONCE ────────────────
            // Oral subjects are linked to main subjects via subject_id
            $oralSubjects = DB::table('subject_masters')
                ->select('id', 'subject_id', 'priority')
                ->where('class_id', $classId)
                ->where('priority', 2)
                ->where('active',   1)
                ->whereIn('subject_id', $mainSubjectIds)
                ->get()
                ->keyBy('subject_id'); // [main_subject_id => oral_subject_row]

            $oralSubjectIds = $oralSubjects->pluck('id')->toArray();
            $allSubjectIds  = array_merge($mainSubjectIds, $oralSubjectIds);

            // ── 3. Fetch students ONCE ────────────────────────────────────────────
            $fields = [
                'stu_main_srno.srno',
                'stu_main_srno.rollno',
                'stu_main_srno.school',
                'stu_main_srno.class',
                'stu_main_srno.section',
                'stu_detail.dob',
                'stu_detail.name',
                'parents_detail.f_name',
                'parents_detail.m_name',
                'class_masters.class as class_name',
                'section_masters.section as section_name',
            ];

            $studentQuery = StudentMasterController::getMarksheetStdWithNames(false, $fields)
                ->where('stu_main_srno.session_id', $sessionId)
                ->where('stu_main_srno.class',      $classId)
                ->where('stu_main_srno.section',    $sectionId);

            if (!empty($studentId) && $studentId !== 'all') {
                $studentQuery->where('stu_main_srno.srno', $studentId);
            }

            $studentDetails  = $studentQuery->get();
            $foundStudentIds = $studentDetails->pluck('srno')->toArray();

            if ($studentDetails->isEmpty()) {
                return response()->json(['error' => 'No students found'], 404);
            }

            // ── 4. Fetch exams ONCE ───────────────────────────────────────────────
            $exams   = ExamMasterController::getAllExam(['id', 'exam']); // [id => name]
            $examIds = array_keys($exams);

            // ── 5. Fetch ALL marks + max_marks in ONE query ───────────────────────
            $allMarks = DB::table('marks')
                ->select(
                    'marks.srno',
                    'marks.exam_id',
                    'marks.subject_id',
                    'marks.marks',
                    'marks.attendance',
                    'marks_masters.max_marks',
                    'subject_masters.priority',
                    'subject_masters.subject_id as parent_subject_id'
                )
                ->join('subject_masters', 'subject_masters.id', '=', 'marks.subject_id')
                ->leftJoin('marks_masters', function ($join) {
                    $join->on('marks_masters.subject_id', '=', 'marks.subject_id')
                        ->on('marks_masters.exam_id',    '=', 'marks.exam_id')
                        ->on('marks_masters.class_id',   '=', 'marks.class_id')
                        ->on('marks_masters.session_id', '=', 'marks.session_id');
                })
                ->where('marks.session_id', $sessionId)
                ->where('marks.class_id',   $classId)
                ->where('marks.active',     1)
                ->where('marks_masters.active',    1)
                ->where('subject_masters.active',  1)
                ->whereIn('marks.srno',       $foundStudentIds)
                ->whereIn('marks.exam_id',    $examIds)
                ->whereIn('marks.subject_id', $allSubjectIds)
                ->get()
                ->groupBy('srno')
                ->map(fn($rows) => $rows->groupBy('exam_id')
                    ->map(fn($rows) => $rows->keyBy('subject_id')));

            // ── 6. Fetch ALL subject grades in ONE query ──────────────────────────
            $allGrades = DB::table('subject_grades')
                ->select(['grade_name', 'subject_id', 'exam_id', 'min_marks', 'max_marks'])
                ->where('session_id', $sessionId)
                ->where('class_id',   $classId)
                ->where('is_overall', 2)
                ->where('active',     1)
                ->whereIn('subject_id', $mainSubjectIds)
                ->whereIn('exam_id',    $examIds)
                ->get()
                ->groupBy('subject_id')
                ->map(fn($rows) => $rows->groupBy('exam_id'));

            // ── 7. Fetch attendance schedule grouped by month ONCE ────────────────
            $scheduleByMonth = DB::table('attendance_schedule')
                ->selectRaw('MONTH(a_date) as month_number, SUM(status) as total')
                ->where('session_id', $sessionId)
                ->groupBy(DB::raw('MONTH(a_date)'))
                ->get()
                ->keyBy('month_number');

            // ── 8. Fetch ALL student attendance grouped by srno + month ONCE ──────
            $attendanceByStudent = DB::table('attendance')
                ->selectRaw('srno, MONTH(a_date) as month_number, SUM(status) as total')
                ->where('session_id', $sessionId)
                ->whereIn('srno', $foundStudentIds)
                ->groupBy('srno', DB::raw('MONTH(a_date)'))
                ->get()
                ->groupBy('srno')
                ->map(fn($rows) => $rows->keyBy('month_number'));

            // ── 9. Build report in memory (zero extra queries) ────────────────────
            $monthNames = [
                4 => 'April',    5 => 'May',       6 => 'June',
                7 => 'July',     8 => 'August',    9 => 'September',
                10 => 'October', 11 => 'November', 12 => 'December',
                1 => 'January',  2 => 'February',  3 => 'March',
            ];

            $finalData = [];

            foreach ($studentDetails as $studentDetail) {
                $stuId        = $studentDetail->srno;
                $studentMarks = $allMarks[$stuId] ?? collect();

                $reportData = [
                    'student_info' => $studentDetail,
                    'exams'        => [],
                    'attendance'   => [],
                ];

                // ── Marks ─────────────────────────────────────────────────────────
                foreach ($mainSubjects as $subject) {
                    $subId = $subject->id;

                    // Get oral subject linked to this main subject
                    $oralSubject   = $oralSubjects[$subId] ?? null;
                    $oralSubjectId = $oralSubject ? $oralSubject->id : null;

                    $subjectMarksData = [
                        'id'           => $subId,
                        'subject'      => $subject->subject,
                        'by_m_g'       => $subject->by_m_g,
                        'priority'     => $subject->priority,
                        'subSubjectId' => $subject->subject_id,
                        'exam-info'    => [],
                    ];

                    foreach ($exams as $examId => $examName) {
                        $examMarks = $studentMarks[$examId] ?? collect();

                        // Written (priority 1)
                        $mainRow      = $examMarks[$subId] ?? null;
                        // Oral (priority 2)
                        $oralRow      = $oralSubjectId ? ($examMarks[$oralSubjectId] ?? null) : null;

                        // Skip if neither written nor oral marks exist
                        if (!$mainRow && !$oralRow) {
                            continue;
                        }

                        $writtenMarks    = $mainRow ? ($mainRow->marks    ?? 0) : 0;
                        $writtenMaxMarks = $mainRow ? ($mainRow->max_marks ?? 0) : 0;
                        $oralMarksVal    = $oralRow ? ($oralRow->marks    ?? 0) : 0;
                        $oralMaxMarks    = $oralRow ? ($oralRow->max_marks ?? 0) : 0;

                        $totalMarks    = $writtenMarks + $oralMarksVal;
                        $totalMaxMarks = $writtenMaxMarks + $oralMaxMarks;

                        // Grade from in-memory lookup
                        $grade = $this->resolveGradeFirstSecond(
                            $totalMarks, $totalMaxMarks, $subId, $examId, $allGrades
                        );

                        $subjectMarksData['exam-info'][] = [
                            'exam_id'           => $examId,
                            'exam'              => $examName,
                            'written_marks'     => ($mainRow && $mainRow->attendance == 1) ? $writtenMarks : 'Abs',
                            'written_max_marks' => $writtenMaxMarks,
                            'oral_marks'        => ($oralRow && $oralRow->attendance == 1) ? $oralMarksVal : 'Abs',
                            'oral_max_marks'    => $oralMaxMarks,
                            'total_marks'       => $totalMarks,
                            'max_marks'         => $totalMaxMarks,
                            'grade'             => $grade,
                        ];
                    }

                    $reportData['exams'][] = $subjectMarksData;
                }

                // ── Attendance (in-memory) ────────────────────────────────────────
                $stuAttendance  = $attendanceByStudent[$stuId] ?? collect();
                $monthlyData    = [];
                $totalMeetings  = 0;
                $totalAttended  = 0;

                foreach ($monthNames as $monthNumber => $monthName) {
                    $scheduled = $scheduleByMonth[$monthNumber]->total ?? 0;
                    $attended  = $stuAttendance[$monthNumber]->total   ?? 0;

                    $monthlyData[] = [
                        'month'                  => $monthName,
                        'month_number'           => $monthNumber,
                        'total_meetings'         => $scheduled * 2,
                        'attended_meetings'      => $attended  * 2,
                        'attendance_percentage'  => $scheduled > 0
                                                    ? round(($attended / $scheduled) * 100, 2)
                                                    : 0,
                    ];

                    $totalMeetings += $scheduled;
                    $totalAttended += $attended;
                }

                $reportData['attendance'] = [
                    'student_id'         => $stuId,
                    'session_id'         => $sessionId,
                    'monthly_attendance' => $monthlyData,
                    'summary'            => [
                        'total_meetings'               => $totalMeetings * 2,
                        'total_attended'               => $totalAttended * 2,
                        'overall_attendance_percentage' => $totalMeetings > 0
                                                            ? round(($totalAttended / $totalMeetings) * 100, 2)
                                                            : 0,
                    ],
                ];

                $finalData[] = $reportData;
            }

            return response()->json([
                'success' => true,
                'data'    => $finalData,
                'session' => $session,
                'logo'    => [
                    'school_logo'           => config('myconfig.mylogo'),
                    'principal_sign'        => config('myconfig.mysignature'),
                    'result_date_message'   => $request->dateMessage    ?? '',
                    'session_start_message' => $request->sessionMessage ?? '',
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error'   => 'An error occurred',
                'message' => $e->getMessage() . ' at line number ' . $e->getLine(),
            ], 500);
        }
    }

    // ── Grade resolver (in-memory, zero DB calls) ─────────────────────────────────
    private function resolveGradeFirstSecond($marks, $maxMarks, $subjectId, $examId, $allGrades)
    {
        $rows  = $allGrades[$subjectId][$examId] ?? collect();
        $grade = $rows->first(fn($g) =>
            $g->min_marks <= $marks && $g->max_marks >= $marks
        );

        if ($grade) return $grade->grade_name;

        // Fallback percentage-based grade
        if ($maxMarks <= 0) return 'ER';

        $percentage = ($marks * 100.0) / $maxMarks;

        if ($percentage > 80) return 'A';
        if ($percentage > 60) return 'B';
        if ($percentage > 40) return 'C';

        return 'D';
    }

    // Helper method to get exam marks for a student and subject
    private function getExamMarksForStudent($classId, $examId, $sessionId, $studentSrno, $subjectId)
    {
        // Fetch main subject marks (priority 1 - written)
        $mainMarks = DB::table('marks')
            ->join('subject_masters', 'subject_masters.id', '=', 'marks.subject_id')
            ->join('marks_masters', function ($join) use ($classId, $examId, $sessionId) {
                $join->on('marks_masters.subject_id', '=', 'subject_masters.id')
                    ->where('marks_masters.class_id', $classId)
                    ->where('marks_masters.exam_id', $examId)
                    ->where('marks_masters.session_id', $sessionId);
            })
            ->where('marks.class_id', $classId)
            ->where('marks.exam_id', $examId)
            ->where('marks.session_id', $sessionId)
            ->where('marks.srno', $studentSrno)
            ->where('marks.subject_id', $subjectId)
            ->where('subject_masters.priority', 1)
            ->where('marks.active', 1)
            ->where('marks_masters.active', 1)
            ->where('subject_masters.active', 1)
            ->select(
                'marks.attendance',
                'marks.marks as written_marks',
                'marks_masters.max_marks as written_max_marks'
            )
            ->first();

        // Fetch oral marks (priority 2) for the same subject
        $oralMarks = DB::table('marks')
            ->join('subject_masters', 'subject_masters.id', '=', 'marks.subject_id')
            ->join('marks_masters', function ($join) use ($classId, $examId, $sessionId, $subjectId) {
                $join->on('marks_masters.subject_id', '=', 'subject_masters.id')
                    ->where('marks_masters.class_id', $classId)
                    ->where('marks_masters.exam_id', $examId)
                    ->where('marks_masters.session_id', $sessionId)
                    ->where('subject_masters.subject_id', $subjectId);
            })
            ->where('marks.class_id', $classId)
            ->where('marks.exam_id', $examId)
            ->where('marks.session_id', $sessionId)
            ->where('marks.srno', $studentSrno)
            ->where('subject_masters.priority', 2)
            ->where('subject_masters.subject_id', $subjectId)
            ->where('marks.active', 1)
            ->where('marks_masters.active', 1)
            ->where('subject_masters.active', 1)
            ->select(
                'marks.attendance',
                'marks.marks as oral_marks',
                'marks_masters.max_marks as oral_max_marks'
            )
            ->first();

        // If no marks found, return null
        if (!$mainMarks && !$oralMarks) {
            return null;
        }

        // Calculate total marks and grade
        $totalMarks = ($mainMarks ? $mainMarks->written_marks : 0) + ($oralMarks ? $oralMarks->oral_marks : 0);
        $totalMaxMarks = ($mainMarks ? $mainMarks->written_max_marks : 0) + ($oralMarks ? $oralMarks->oral_max_marks : 0);

        // Calculate grade based on total marks
        $grade = $this->getGradeFirstSecond($totalMarks, $totalMaxMarks, $classId, $examId, $sessionId, $subjectId);

        return [
            'written_marks' => $mainMarks && $mainMarks->attendance == 1 ? $mainMarks->written_marks : 'Abs',
            'written_max_marks' => $mainMarks ? $mainMarks->written_max_marks : 0,
            'oral_marks' => $oralMarks && $oralMarks->attendance == 1 ? $oralMarks->oral_marks : 'Abs',
            'oral_max_marks' => $oralMarks ? $oralMarks->oral_max_marks : 0,
            'total_marks' => $totalMarks,
            'max_marks' => $totalMaxMarks,
            'grade' => $grade,
        ];
    }

    //Grade Function for only Class First And Second Final Marsheet
    private function getGradeFirstSecond($marks, $max_marks, $classId, $examId, $sessionId, $subjectId)
    {
        // Try to get grade from SubjectGrade table
        $grade = DB::table('subject_grades')->select(['grade_name'])->where('is_overall', 2)->where('subject_id', $subjectId)->where('class_id', $classId)->where('exam_id', $examId)->where('session_id', $sessionId)->where('min_marks', '<=', $marks)->where('max_marks', '>=', $marks)->where('active', 1)->first();
        if (!empty($grade)) {
            return $grade->grade_name;
        }
        $total = $marks * 100.00 / $max_marks;
        if ($total > 80) {
            return 'A';
        } elseif ($total > 60) {
            return 'B';
        } elseif ($total > 40) {
            return 'C';
        } else {
            return 'D';
        }

        return 'ER';
    }

    //Attendance for class First and Second

    private function firstSecondAttendance($sessionId, $studentId)
    {

        // Complete list of months with their full names
        $monthNames = [
            4 => 'April',
            5 => 'May',
            6 => 'June',
            7 => 'July',
            8 => 'August',
            9 => 'September',
            10 => 'October',
            11 => 'November',
            12 => 'December',
            1 => 'January',
            2 => 'February',
            3 => 'March',
        ];

        $monthlyData = [];
        $totalMeetings = 0;
        $totalAttended = 0;

        // Iterate through all months
        foreach ($monthNames as $monthNumber => $monthName) {
            // Query total meetings for the month
            $meetingsQuery = DB::table('attendance_schedule')
                ->where('session_id', $sessionId)
                ->whereMonth('a_date', $monthNumber)
                ->sum('status');

            // Query student attendance for the month
            $studentAttendanceQuery = DB::table('attendance')
                ->where('session_id', $sessionId)
                ->where('srno', $studentId)
                ->whereMonth('a_date', $monthNumber)
                ->sum('status');

            // Prepare month data
            $monthData = [
                'month' => $monthName,
                'month_number' => $monthNumber,
                'total_meetings' => $meetingsQuery * 2,
                'attended_meetings' => $studentAttendanceQuery * 2,
                'attendance_percentage' => $meetingsQuery > 0
                    ? round(($studentAttendanceQuery / $meetingsQuery) * 100, 2)
                    : 0,
            ];

            $monthlyData[] = $monthData;

            // Update total meetings and attendance
            $totalMeetings += $meetingsQuery;
            $totalAttended += $studentAttendanceQuery;
        }

        // Prepare final report data
        $reportData = [
            'student_id' => $studentId,
            'session_id' => $sessionId,
            'monthly_attendance' => $monthlyData,
            'summary' => [
                'total_meetings' => $totalMeetings * 2,
                'total_attended' => $totalAttended * 2,
                'overall_attendance_percentage' => $totalMeetings > 0
                    ? round(($totalAttended / $totalMeetings) * 100, 2)
                    : 0,
            ],

        ];

        return $reportData;
    }

    /*
       * class 1st and second final marksheet
     */

    /*
     *  marksheet Select Option
    */
    public function selectExamWithOrWithout(Request $request)
    {
        // $data = ExamMaster::where('active', 1)->orderBy('order', 'ASC')->get();
        $data = ExamMasterController::getAllExam(['id', 'exam']);
        // Retrieve the data from the session
        $class = $request->session()->get('class');
        $section = $request->session()->get('section');
        $students = $request->session()->get('students');
        $sessionMessage = $request->session()->get('sessionMessage');
        $dateMessage = $request->session()->get('dateMessage');

        // Pass the data to the view
        return view('marks.marksheet.marksheet_print_options', [
            'data' => $data,
            'class' => $class,
            'section' => $section,
            'students' => $students,
            'sessionMessage' => $sessionMessage,
            'dateMessage' => $dateMessage,
        ]);

        // return view('marks.marksheet.marksheet_print_options', $data);
    }

    /*
     * class 3rd to 5th final marksheet
    */

    public function finalMarksheetThirdToFifth()
    {
        $classes = ClassMasterController::getClasses();

        return view('marks.marksheet.marksheet_final_third_fifth', compact('classes'));
    }

    public function finalMarksheetThirdToFifthPrint(Request $request)
    {
        // Retrieve the data from the session
        $class = $request->session()->get('class');
        $section = $request->session()->get('section');
        $students = $request->session()->get('students');
        $sessionMessage = $request->session()->get('sessionMessage');
        $dateMessage = $request->session()->get('dateMessage');
        $exam = $request->session()->get('exam');
        $with = $request->session()->get('with');
        $without = $request->session()->get('without');

        // Pass the data to the view
        return view('marks.marksheet.marksheet_final_third_fifth_print', [
            'class' => $class,
            'section' => $section,
            'students' => $students,
            'sessionMessage' => $sessionMessage,
            'dateMessage' => $dateMessage,
            'exam' => $exam,
            'with' => $with,
            'without' => $without,
        ]);
    }

    public function finalMarksheetThirdToFifthStore(Request $request)
    {
        $request->validate([
            'class' => [
                'required',
                'exists:class_masters,id,active,1',
            ],
            'section' => [
                'required',
                'exists:section_masters,id,active,1',
            ],
            'std' => 'required',
        ]);
        $classId = $request->class;
        $sectionId = $request->section;
        $students = $request->std;
        $sessionMessage = $request->sessionMessage;
        $dateMessage = $request->dateMessage;

        return redirect()->route('marks.marks-report.select.exam')
            ->with('class', $classId)
            ->with('section', $sectionId)
            ->with('students', $students)
            ->with('sessionMessage', $sessionMessage)
            ->with('dateMessage', $dateMessage);
    }

    public function selectExamWithOrWithoutStore(Request $request)
    {
        $request->validate([
            'class' => [
                'required',
                'exists:class_masters,id,active,1',
            ],
            'section' => [
                'required',
                'exists:section_masters,id,active,1',
            ],
            'students' => 'required',
        ]);
        $data = [
            'class' => $request->class,
            'section' => $request->section,
            'students' => $request->students,
            'sessionMessage' => $request->sessionMessage,
            'dateMessage' => $request->dateMessage,
            'exam' => $request->exams,
            'with' => $request->withExam,
            'without' => $request->withoutExam,
        ];
        if (empty($data)) {
            return redirect()->back()->with('error', 'Something went wrong, please try again.');
        } else {

            return redirect()->route('marks.marks-report.marksheet.third.fifth.print')->with($data);
        }
    }

    public function finalMarksheetThirdtoFiveReport(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'class'    => 'required|exists:class_masters,id,active,1',
                'section'  => 'required|exists:section_masters,id,active,1',
                'students' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status'  => 'error',
                    'message' => $validator->errors(),
                ], 400);
            }

            $session = session('marks_current_session');
            if (blank($session)) {
                return response()->json(['status' => 'error', 'message' => 'Current session not set'], 400);
            }

            $classId   = $request->class;
            $sectionId = $request->section;
            $sessionId = $session->id;
            $studentId = $request->students;

            // ── 1. Fetch main subjects ONCE ───────────────────────────────────────
            $subFields    = ['id', 'subject', 'by_m_g', 'priority', 'subject_id', 'class_id'];
            $subWhere     = ['class_id' => $classId];
            $subWhereIn   = ['priority' => [1]];
            $mainSubjects = SubjectMasterController::getAllSubjects(
                $subFields, '', $subWhere, ['by_m_g' => 'asc'], true, '', false, $subWhereIn
            );

            if ($mainSubjects->isEmpty()) {
                return response()->json(['status' => 'error', 'message' => 'No subjects found'], 404);
            }

            $mainSubjectIds = $mainSubjects->pluck('id')->toArray();

            // ── 2. Fetch oral subjects (priority 2) ONCE ──────────────────────────
            $oralSubjects = DB::table('subject_masters')
                ->select('id', 'subject_id', 'priority')
                ->where('class_id', $classId)
                ->where('priority', 2)
                ->where('active',   1)
                ->whereIn('subject_id', $mainSubjectIds)
                ->get()
                ->keyBy('subject_id'); // [main_subject_id => oral_subject_row]

            $oralSubjectIds = $oralSubjects->pluck('id')->toArray();
            $allSubjectIds  = array_merge($mainSubjectIds, $oralSubjectIds);

            // ── 3. Fetch exams ONCE ───────────────────────────────────────────────
            $requestedExamIds = array_filter(array_map('trim', explode(',', $request->exam ?? '')));
            $exams            = ExamMasterController::getAllExam(['id', 'exam'], [], [], '', false, ['id' => $requestedExamIds]);

            if (empty($exams)) {
                return response()->json(['status' => 'error', 'message' => 'No exams found'], 404);
            }

            $examIds = array_keys($exams);

            // ── 4. Fetch students ONCE ────────────────────────────────────────────
            $fields = [
                'stu_main_srno.srno',
                'stu_main_srno.rollno',
                'stu_main_srno.school',
                'stu_main_srno.class',
                'stu_main_srno.section',
                'stu_detail.dob',
                'stu_detail.name',
                'parents_detail.f_name',
                'parents_detail.m_name',
                'class_masters.class as class_name',
                'section_masters.section as section_name',
            ];

            $studentQuery = StudentMasterController::getMarksheetStdWithNames(false, $fields)
                ->where('stu_main_srno.session_id', $sessionId)
                ->where('stu_main_srno.class',      $classId)
                ->where('stu_main_srno.section',    $sectionId);

            if (!empty($studentId) && $studentId !== 'all') {
                $studentQuery->where('stu_main_srno.srno', $studentId);
            }

            $students = $studentQuery->get();

            if ($students->isEmpty()) {
                return response()->json(['status' => 'error', 'message' => 'No students found'], 404);
            }

            $foundStudentIds = $students->pluck('srno')->toArray();

            // ── 5. Fetch ALL marks + max_marks in ONE query ───────────────────────
            $allMarks = DB::table('marks')
                ->select(
                    'marks.srno',
                    'marks.exam_id',
                    'marks.subject_id',
                    'marks.marks',
                    'marks.attendance',
                    'marks_masters.max_marks',
                    'subject_masters.priority',
                    'subject_masters.subject_id as parent_subject_id'
                )
                ->join('subject_masters', 'subject_masters.id', '=', 'marks.subject_id')
                ->leftJoin('marks_masters', function ($join) {
                    $join->on('marks_masters.subject_id', '=', 'marks.subject_id')
                        ->on('marks_masters.exam_id',    '=', 'marks.exam_id')
                        ->on('marks_masters.class_id',   '=', 'marks.class_id')
                        ->on('marks_masters.session_id', '=', 'marks.session_id');
                })
                ->where('marks.session_id',       $sessionId)
                ->where('marks.class_id',         $classId)
                ->where('marks.active',           1)
                ->where('marks_masters.active',   1)
                ->where('subject_masters.active', 1)
                ->whereIn('marks.srno',       $foundStudentIds)
                ->whereIn('marks.exam_id',    $examIds)
                ->whereIn('marks.subject_id', $allSubjectIds)
                ->get()
                ->groupBy('srno')
                ->map(fn($rows) => $rows->groupBy('exam_id')
                    ->map(fn($rows) => $rows->keyBy('subject_id')));

            // ── 6. Fetch ALL subject grades in ONE query ──────────────────────────
            $allGrades = DB::table('subject_grades')
                ->select(['grade_name', 'subject_id', 'exam_id', 'min_marks', 'max_marks'])
                ->where('session_id', $sessionId)
                ->where('class_id',   $classId)
                ->where('is_overall', 2)
                ->where('active',     1)
                ->whereIn('subject_id', $mainSubjectIds)
                ->whereIn('exam_id',    $examIds)
                ->get()
                ->groupBy('subject_id')
                ->map(fn($rows) => $rows->groupBy('exam_id'));

            // ── 7. Fetch attendance schedule grouped by month ONCE ────────────────
            $scheduleByMonth = DB::table('attendance_schedule')
                ->selectRaw('MONTH(a_date) as month_number, SUM(status) as total')
                ->where('session_id', $sessionId)
                ->groupBy(DB::raw('MONTH(a_date)'))
                ->get()
                ->keyBy('month_number');

            // ── 8. Fetch ALL student attendance grouped by srno + month ONCE ──────
            $attendanceByStudent = DB::table('attendance')
                ->selectRaw('srno, MONTH(a_date) as month_number, SUM(status) as total')
                ->where('session_id', $sessionId)
                ->whereIn('srno',     $foundStudentIds)
                ->groupBy('srno', DB::raw('MONTH(a_date)'))
                ->get()
                ->groupBy('srno')
                ->map(fn($rows) => $rows->keyBy('month_number'));

            // ── 9. Build report in memory (zero extra queries) ────────────────────
            $monthNames = [
                4  => 'April',     5  => 'May',       6  => 'June',
                7  => 'July',      8  => 'August',    9  => 'September',
                10 => 'October',   11 => 'November',  12 => 'December',
                1  => 'January',   2  => 'February',  3  => 'March',
            ];

            $finalData = [];

            foreach ($students as $studentDetail) {
                $stuId        = $studentDetail->srno;
                $studentMarks = $allMarks[$stuId] ?? collect();

                // ── Marks ─────────────────────────────────────────────────────────
                $examsData = [];

                foreach ($mainSubjects as $subject) {
                    $subId         = $subject->id;
                    $oralSubject   = $oralSubjects[$subId]   ?? null;
                    $oralSubjectId = $oralSubject ? $oralSubject->id : null;
                    $allExamsTotal = 0;
                    $examInfo      = [];

                    foreach ($exams as $examId => $examName) {
                        $examMarks = $studentMarks[$examId] ?? collect();

                        $mainRow = $examMarks[$subId]         ?? null;
                        $oralRow = $oralSubjectId
                                    ? ($examMarks[$oralSubjectId] ?? null)
                                    : null;

                        if (!$mainRow && !$oralRow) {
                            continue;
                        }

                        $writtenMarks    = $mainRow ? ($mainRow->marks    ?? 0) : 0;
                        $writtenMaxMarks = $mainRow ? ($mainRow->max_marks ?? 0) : 0;
                        $oralMarksVal    = $oralRow ? ($oralRow->marks    ?? 0) : 0;
                        $oralMaxMarks    = $oralRow ? ($oralRow->max_marks ?? 0) : 0;

                        $totalMarks    = $writtenMarks + $oralMarksVal;
                        $totalMaxMarks = $writtenMaxMarks + $oralMaxMarks;

                        $allExamsTotal += $totalMarks;

                        // Grade from in-memory lookup
                        $grade = $this->resolveGradeFirstSecond(
                            $totalMarks, $totalMaxMarks, $subId, $examId, $allGrades
                        );

                        $examInfo[] = [
                            'exam_id'           => $examId,
                            'exam'              => $examName,
                            'written_marks'     => ($mainRow && $mainRow->attendance == 1) ? $writtenMarks    : 'Abs',
                            'written_max_marks' => $writtenMaxMarks,
                            'oral_marks'        => ($oralRow && $oralRow->attendance == 1) ? $oralMarksVal    : 'Abs',
                            'oral_max_marks'    => $oralMaxMarks,
                            'total_marks'       => $totalMarks,
                            'max_marks'         => $totalMaxMarks,
                            'grade'             => $grade,
                        ];
                    }

                    $examsData[] = [
                        'id'           => $subId,
                        'subject'      => $subject->subject,
                        'by_m_g'       => $subject->by_m_g,
                        'priority'     => $subject->priority,
                        'subSubjectId' => $subject->subject_id,
                        'exam-info'    => $examInfo,
                        'allExamsTotal' => $allExamsTotal,
                    ];
                }

                // ── Attendance (in-memory) ────────────────────────────────────────
                $stuAttendance = $attendanceByStudent[$stuId] ?? collect();
                $monthlyData   = [];
                $totalMeetings = 0;
                $totalAttended = 0;

                foreach ($monthNames as $monthNumber => $monthName) {
                    $scheduled = $scheduleByMonth[$monthNumber]->total ?? 0;
                    $attended  = $stuAttendance[$monthNumber]->total   ?? 0;

                    $monthlyData[] = [
                        'month'                  => $monthName,
                        'month_number'           => $monthNumber,
                        'total_meetings'         => $scheduled * 2,
                        'attended_meetings'      => $attended  * 2,
                        'attendance_percentage'  => $scheduled > 0
                                                    ? round(($attended / $scheduled) * 100, 2)
                                                    : 0,
                    ];

                    $totalMeetings += $scheduled;
                    $totalAttended += $attended;
                }

                $finalData[] = [
                    'student_info' => $studentDetail,
                    'exams'        => $examsData,
                    'attendance'   => [
                        'student_id'         => $stuId,
                        'session_id'         => $sessionId,
                        'monthly_attendance' => $monthlyData,
                        'summary'            => [
                            'total_meetings'                => $totalMeetings * 2,
                            'total_attended'                => $totalAttended * 2,
                            'overall_attendance_percentage' => $totalMeetings > 0
                                                                ? round(($totalAttended / $totalMeetings) * 100, 2)
                                                                : 0,
                        ],
                    ],
                ];
            }

            return response()->json([
                'status'  => 'success',
                'session' => $session,
                'data'    => $finalData,
                'logo'    => [
                    'school_logo'           => config('myconfig.mylogo'),
                    'principal_sign'        => config('myconfig.mysignature'),
                    'result_date_message'   => $request->dateMessage    ?? '',
                    'session_start_message' => $request->sessionMessage ?? '',
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to export report: ' . $e->getMessage() . ' at line ' . $e->getLine(),
            ], 500);
        }
    }

    /*
     *  marksheet Select Option For Class 6th To 8th
    */
    public function selectExamWithOrWithoutSixEighth(Request $request)
    {
        // $data = ExamMaster::where('active', 1)->orderBy('order', 'ASC')->get();
        $data = ExamMasterController::getAllExam(['id', 'exam']);
        $class = $request->session()->get('class');
        $section = $request->session()->get('section');
        $students = $request->session()->get('students');
        $sessionMessage = $request->session()->get('sessionMessage');
        $dateMessage = $request->session()->get('dateMessage');

        // Pass the data to the view
        return view('marks.marksheet.marksheet_print_options_six_eighth', [
            'data' => $data,
            'class' => $class,
            'section' => $section,
            'students' => $students,
            'sessionMessage' => $sessionMessage,
            'dateMessage' => $dateMessage,
        ]);
    }

    /*
     * class 6th to 8th final marksheet
    */

    public function finalMarksheetSixToEighth()
    {
        $classes = ClassMasterController::getClasses();

        return view('marks.marksheet.marksheet_final_six_eighth', compact('classes'));
    }

    public function finalMarksheetSixToEighthPrint(Request $request)
    {
        // Retrieve the data from the session
        $class = $request->session()->get('class');
        $section = $request->session()->get('section');
        $students = $request->session()->get('students');
        $sessionMessage = $request->session()->get('sessionMessage');
        $dateMessage = $request->session()->get('dateMessage');
        $exam = $request->session()->get('exam');
        $with = $request->session()->get('with');
        $without = $request->session()->get('without');

        // Pass the data to the view
        return view('marks.marksheet.marksheet_final_six_eighth_print', [
            'class' => $class,
            'section' => $section,
            'students' => $students,
            'sessionMessage' => $sessionMessage,
            'dateMessage' => $dateMessage,
            'exam' => $exam,
            'with' => $with,
            'without' => $without,
        ]);
        // return view('marks.marksheet.marksheet_final_six_eighth_print');
    }

    public function finalMarksheetSixToEighthStore(Request $request)
    {
        $request->validate([
            'class' => [
                'required',
                'exists:class_masters,id,active,1',
            ],
            'section' => [
                'required',
                'exists:section_masters,id,active,1',
            ],
            'std' => 'required',
        ]);
        $classId = $request->class;
        $sectionId = $request->section;
        $students = $request->std;
        $sessionMessage = $request->sessionMessage;
        $dateMessage = $request->dateMessage;

        return redirect()->route('marks.marks-report.select.exam.six.eighth')->with('class', $classId)->with('section', $sectionId)->with('students', $students)->with('sessionMessage', $sessionMessage)->with('dateMessage', $dateMessage);
    }

    public function selectExamWithOrWithoutSixEighthStore(Request $request)
    {
        $request->validate([
            'class' => [
                'required',
                'exists:class_masters,id,active,1',
            ],
            'section' => [
                'required',
                'exists:section_masters,id,active,1',
            ],
            'students' => 'required',
        ]);
        $data = [
            'class' => $request->class,
            'section' => $request->section,
            'students' => $request->students,
            'sessionMessage' => $request->sessionMessage,
            'dateMessage' => $request->dateMessage,
            'exam' => $request->exams,
            'with' => $request->withExam,
            'without' => $request->withoutExam,
        ];
        if (empty($data)) {
            return redirect()->back()->with('error', 'Something went wrong, please try again.');
        } else {

            return redirect()->route('marks.marks-report.marksheet.six.eighth.print')->with($data);
        }
    }

    public function finalMarksheetSixtoEighthReport(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'class'    => 'required|exists:class_masters,id,active,1',
                'section'  => 'required|exists:section_masters,id,active,1',
                'students' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status'  => 'error',
                    'message' => $validator->errors(),
                ], 400);
            }

            $session = session('marks_current_session');
            if (blank($session)) {
                return response()->json(['status' => 'error', 'message' => 'Current session not set'], 400);
            }

            $classId   = $request->class;
            $sectionId = $request->section;
            $sessionId = $session->id;
            $studentId = $request->students;

            // ── 1. Fetch main subjects ONCE ───────────────────────────────────────
            $subFields    = ['id', 'subject', 'by_m_g', 'priority', 'subject_id', 'class_id'];
            $subWhere     = ['class_id' => $classId];
            $subWhereIn   = ['priority' => [1]];
            $mainSubjects = SubjectMasterController::getAllSubjects(
                $subFields, '', $subWhere, ['by_m_g' => 'asc'], true, '', false, $subWhereIn
            );

            if ($mainSubjects->isEmpty()) {
                return response()->json(['status' => 'error', 'message' => 'No subjects found'], 404);
            }

            $mainSubjectIds = $mainSubjects->pluck('id')->toArray();

            // ── 2. Fetch oral subjects (priority 2) ONCE ──────────────────────────
            $oralSubjects = DB::table('subject_masters')
                ->select('id', 'subject_id', 'priority')
                ->where('class_id', $classId)
                ->where('priority', 2)
                ->where('active',   1)
                ->whereIn('subject_id', $mainSubjectIds)
                ->get()
                ->keyBy('subject_id'); // [main_subject_id => oral_subject_row]

            $oralSubjectIds = $oralSubjects->pluck('id')->toArray();
            $allSubjectIds  = array_merge($mainSubjectIds, $oralSubjectIds);

            // ── 3. Fetch exams ONCE ───────────────────────────────────────────────
            $requestedExamIds = array_filter(array_map('trim', explode(',', $request->exam ?? '')));
            $exams            = ExamMasterController::getAllExam(['id', 'exam'], [], [], '', false, ['id' => $requestedExamIds]);

            if (empty($exams)) {
                return response()->json(['status' => 'error', 'message' => 'No exams found'], 404);
            }

            $examIds = array_keys($exams);

            // ── 4. Fetch students ONCE ────────────────────────────────────────────
            $fields = [
                'stu_main_srno.srno',
                'stu_main_srno.rollno',
                'stu_main_srno.school',
                'stu_main_srno.class',
                'stu_main_srno.section',
                'stu_detail.dob',
                'stu_detail.name',
                'parents_detail.f_name',
                'parents_detail.m_name',
                'class_masters.class as class_name',
                'section_masters.section as section_name',
            ];

            $studentQuery = StudentMasterController::getMarksheetStdWithNames(false, $fields)
                ->where('stu_main_srno.session_id', $sessionId)
                ->where('stu_main_srno.class',      $classId)
                ->where('stu_main_srno.section',    $sectionId);

            if (!empty($studentId) && $studentId !== 'all') {
                $studentQuery->where('stu_main_srno.srno', $studentId);
            }

            $students = $studentQuery->get();

            if ($students->isEmpty()) {
                return response()->json(['status' => 'error', 'message' => 'No students found'], 404);
            }

            $foundStudentIds = $students->pluck('srno')->toArray();

            // ── 5. Fetch ALL marks + max_marks in ONE query ───────────────────────
            $allMarks = DB::table('marks')
                ->select(
                    'marks.srno',
                    'marks.exam_id',
                    'marks.subject_id',
                    'marks.marks',
                    'marks.attendance',
                    'marks_masters.max_marks',
                    'subject_masters.priority',
                    'subject_masters.subject_id as parent_subject_id'
                )
                ->join('subject_masters', 'subject_masters.id', '=', 'marks.subject_id')
                ->leftJoin('marks_masters', function ($join) {
                    $join->on('marks_masters.subject_id', '=', 'marks.subject_id')
                        ->on('marks_masters.exam_id',    '=', 'marks.exam_id')
                        ->on('marks_masters.class_id',   '=', 'marks.class_id')
                        ->on('marks_masters.session_id', '=', 'marks.session_id');
                })
                ->where('marks.session_id',       $sessionId)
                ->where('marks.class_id',         $classId)
                ->where('marks.active',           1)
                ->where('marks_masters.active',   1)
                ->where('subject_masters.active', 1)
                ->whereIn('marks.srno',       $foundStudentIds)
                ->whereIn('marks.exam_id',    $examIds)
                ->whereIn('marks.subject_id', $allSubjectIds)
                ->get()
                ->groupBy('srno')
                ->map(fn($rows) => $rows->groupBy('exam_id')
                    ->map(fn($rows) => $rows->keyBy('subject_id')));

            // ── 6. Fetch ALL subject grades in ONE query ──────────────────────────
            $allGrades = DB::table('subject_grades')
                ->select(['grade_name', 'subject_id', 'exam_id', 'min_marks', 'max_marks'])
                ->where('session_id', $sessionId)
                ->where('class_id',   $classId)
                ->where('is_overall', 2)
                ->where('active',     1)
                ->whereIn('subject_id', $mainSubjectIds)
                ->whereIn('exam_id',    $examIds)
                ->get()
                ->groupBy('subject_id')
                ->map(fn($rows) => $rows->groupBy('exam_id'));

            // ── 7. Fetch attendance schedule grouped by month ONCE ────────────────
            $scheduleByMonth = DB::table('attendance_schedule')
                ->selectRaw('MONTH(a_date) as month_number, SUM(status) as total')
                ->where('session_id', $sessionId)
                ->groupBy(DB::raw('MONTH(a_date)'))
                ->get()
                ->keyBy('month_number');

            // ── 8. Fetch ALL student attendance grouped by srno + month ONCE ──────
            $attendanceByStudent = DB::table('attendance')
                ->selectRaw('srno, MONTH(a_date) as month_number, SUM(status) as total')
                ->where('session_id', $sessionId)
                ->whereIn('srno',     $foundStudentIds)
                ->groupBy('srno', DB::raw('MONTH(a_date)'))
                ->get()
                ->groupBy('srno')
                ->map(fn($rows) => $rows->keyBy('month_number'));

            // ── 9. Build report in memory (zero extra queries) ────────────────────
            $monthNames = [
                4  => 'April',     5  => 'May',       6  => 'June',
                7  => 'July',      8  => 'August',    9  => 'September',
                10 => 'October',   11 => 'November',  12 => 'December',
                1  => 'January',   2  => 'February',  3  => 'March',
            ];

            $finalData = [];

            foreach ($students as $studentDetail) {
                $stuId        = $studentDetail->srno;
                $studentMarks = $allMarks[$stuId] ?? collect();

                // ── Marks ─────────────────────────────────────────────────────────
                $examsData = [];

                foreach ($mainSubjects as $subject) {
                    $subId         = $subject->id;
                    $oralSubject   = $oralSubjects[$subId]        ?? null;
                    $oralSubjectId = $oralSubject ? $oralSubject->id : null;
                    $allExamsTotal = 0;
                    $examInfo      = [];

                    foreach ($exams as $examId => $examName) {
                        $examMarks = $studentMarks[$examId] ?? collect();

                        $mainRow = $examMarks[$subId]                          ?? null;
                        $oralRow = $oralSubjectId ? ($examMarks[$oralSubjectId] ?? null) : null;

                        if (!$mainRow && !$oralRow) {
                            continue;
                        }

                        $writtenMarks    = $mainRow ? ($mainRow->marks    ?? 0) : 0;
                        $writtenMaxMarks = $mainRow ? ($mainRow->max_marks ?? 0) : 0;
                        $oralMarksVal    = $oralRow ? ($oralRow->marks    ?? 0) : 0;
                        $oralMaxMarks    = $oralRow ? ($oralRow->max_marks ?? 0) : 0;

                        $totalMarks    = $writtenMarks + $oralMarksVal;
                        $totalMaxMarks = $writtenMaxMarks + $oralMaxMarks;

                        $allExamsTotal += $totalMarks;

                        $grade = $this->resolveGradeFirstSecond(
                            $totalMarks, $totalMaxMarks, $subId, $examId, $allGrades
                        );

                        $examInfo[] = [
                            'exam_id'           => $examId,
                            'exam'              => $examName,
                            'written_marks'     => ($mainRow && $mainRow->attendance == 1) ? $writtenMarks : 'Abs',
                            'written_max_marks' => $writtenMaxMarks,
                            'oral_marks'        => ($oralRow && $oralRow->attendance == 1) ? $oralMarksVal : 'Abs',
                            'oral_max_marks'    => $oralMaxMarks,
                            'total_marks'       => $totalMarks,
                            'max_marks'         => $totalMaxMarks,
                            'grade'             => $grade,
                        ];
                    }

                    $examsData[] = [
                        'id'            => $subId,
                        'subject'       => $subject->subject,
                        'by_m_g'        => $subject->by_m_g,
                        'priority'      => $subject->priority,
                        'subSubjectId'  => $subject->subject_id,
                        'exam-info'     => $examInfo,
                        'allExamsTotal' => $allExamsTotal,
                    ];
                }

                // ── Attendance (in-memory) ────────────────────────────────────────
                $stuAttendance = $attendanceByStudent[$stuId] ?? collect();
                $monthlyData   = [];
                $totalMeetings = 0;
                $totalAttended = 0;

                foreach ($monthNames as $monthNumber => $monthName) {
                    $scheduled = $scheduleByMonth[$monthNumber]->total ?? 0;
                    $attended  = $stuAttendance[$monthNumber]->total   ?? 0;

                    $monthlyData[] = [
                        'month'                 => $monthName,
                        'month_number'          => $monthNumber,
                        'total_meetings'        => $scheduled * 2,
                        'attended_meetings'     => $attended  * 2,
                        'attendance_percentage' => $scheduled > 0
                                                    ? round(($attended / $scheduled) * 100, 2)
                                                    : 0,
                    ];

                    $totalMeetings += $scheduled;
                    $totalAttended += $attended;
                }

                $finalData[] = [
                    'student_info' => $studentDetail,
                    'exams'        => $examsData,
                    'attendance'   => [
                        'student_id'         => $stuId,
                        'session_id'         => $sessionId,
                        'monthly_attendance' => $monthlyData,
                        'summary'            => [
                            'total_meetings'                => $totalMeetings * 2,
                            'total_attended'                => $totalAttended * 2,
                            'overall_attendance_percentage' => $totalMeetings > 0
                                                                ? round(($totalAttended / $totalMeetings) * 100, 2)
                                                                : 0,
                        ],
                    ],
                ];
            }

            return response()->json([
                'status'  => 'success',
                'session' => $session,
                'data'    => $finalData,
                'logo'    => [
                    'school_logo'           => config('myconfig.mylogo'),
                    'principal_sign'        => config('myconfig.mysignature'),
                    'result_date_message'   => $request->dateMessage    ?? '',
                    'session_start_message' => $request->sessionMessage ?? '',
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to export report.',
            ], 500);
        }
    }

    /**
     * PG Exam-Wise
     */
    public function pgClassExamWise()
    {
        $classes = ClassMasterController::getClasses();
        $exams = ExamMasterController::getAllExam();

        return view('marks.marksheet.pg_class_exam_wise_play_report', compact('classes', 'exams'));
    }

    public function pgClassExamWisePrint(Request $request)
    {
        $exam = $request->session()->get('exam');
        $class = $request->session()->get('class');
        $section = $request->session()->get('section');
        $students = $request->session()->get('students');

        // Pass the data to the view
        return view('marks.marksheet.pg_class_exam_wise_play_report_print', [
            'exam' => $exam,
            'class' => $class,
            'section' => $section,
            'students' => $students,
        ]);
        // return view('marks.marksheet.exam_wise_play_report_print');
    }

    public function pgClassExamWisePrintStore(Request $request)
    {
        $request->validate([
            'exam' => [
                'required',
                'exists:exam_masters,id,active,1',
            ],
            'class' => [
                'required',
                'exists:class_masters,id,active,1',
            ],
            'section' => [
                'required',
                'exists:section_masters,id,active,1',
            ],
            'std' => 'required',
        ]);
        $exam = $request->exam;
        $classId = $request->class;
        $sectionId = $request->section;
        $students = $request->std;

        return redirect()->route('marks.marks-report.pg-class-exam-wise.print')->with('exam', $exam)->with('class', $classId)->with('section', $sectionId)->with('students', $students);
    }

    public function getPgClassMarkSheetReport(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'class' => 'required|exists:class_masters,id,active,1',
                'section' => 'required|exists:section_masters,id,active,1',
                'exam' => 'required|exists:exam_masters,id,active,1',
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $validator->errors(),
                ], 400);
            }
            $current_session = Session::get('marks_current_session');
            $sessionId = $current_session->id;
            $examId = $request->exam;
            $classId = $request->class;
            $sectionId = $request->section;
            $studentId = $request->std_id;
            $fields = [
                'stu_main_srno.session_id',
                'session_masters.session as session_name',
                'class_masters.class as class_name',
                'section_masters.section as section_name',
                'stu_main_srno.class',
                'stu_main_srno.section',
                'stu_main_srno.srno',
                'stu_main_srno.school',
                'stu_main_srno.rollno',
                'stu_main_srno.ssid',
                'stu_main_srno.active',
                'stu_detail.name',
                'stu_detail.dob',
                'stu_detail.srno',
                'parents_detail.srno',
                'parents_detail.f_name',
                'parents_detail.m_name',
            ];
            $studentQuery = StudentMasterController::getMarksheetStdWithNames(false, $fields)
            ->where('stu_main_srno.class', $classId)
            ->where('stu_main_srno.section', $sectionId)
            ->where('stu_main_srno.session_id', $sessionId);
            if (!empty($studentId) && $studentId != 'all') {
                $studentQuery->where('stu_main_srno.srno', $studentId);
            }
            $students = $studentQuery->get();

            if ($students->isNotEmpty()) {
                $exam = ExamMasterController::getAllExam(['id', 'exam'], ['id' => $examId]);
                $report = [
                    'student' => [],
                ];

                /** variables to collect the max marks data */
                $max_marks = 0;
                $subjects = SubjectMasterController::getAllSubjects(['subject', 'id', 'subject_id', 'by_m_g', 'priority', 'class_id'], '', ['class_id' => $classId], ['order_by' => 'asc'], true);


                foreach ($students as $key => $st) {
                    $writtenSubjects = $subjects->whereNull('subject_id')->where('priority', 1)->values();
                    $studentSubjects = [];
                    $totalObtained = 0;
                    $totalMaxMarksOverall = 0;

                    $marks = Marks::where('exam_id', $examId)->where('session_id', $sessionId)->where('class_id', $st->class)->where('srno', $st->srno)->where('active', 1)->get();

                    $marksMaster = MarksMaster::where('exam_id', $examId)->where('session_id', $sessionId)->where('class_id', $st->class)->where('active', 1)->get();

                    foreach ($writtenSubjects as $writtenSubject) {
                        $writtenMarks = $marks->where('subject_id', $writtenSubject->id)->first();
                        $maxMarksWrittenGrade = $marksMaster->where('subject_id', $writtenSubject->id)->value('max_marks') ?? 0;

                        $writtenValue = ($writtenMarks && $writtenMarks->attendance == 1) ? $writtenMarks->marks : ($writtenMarks ? 'Ab.' : null);

                        $totalMarks = 0;
                        $totalMaxMarks = $maxMarksWrittenGrade;

                        if ($writtenValue === 'Ab.') {
                            $totalMarks = 'Ab.';
                        } else {
                            $totalMarks += is_numeric($writtenValue) ? $writtenValue : 0;
                        }

                        // Add to overall calculation
                        $totalObtained += is_numeric($totalMarks) ? $totalMarks : 0;
                        $totalMaxMarksOverall += $totalMaxMarks;

                        $studentSubjects[] = [
                            'name' => $writtenSubject->subject,
                            'by_m_g' => $writtenSubject->by_m_g,
                            // 'written' => $writtenValue != null ? ($maxMarksWrittenGrade != 0 ? $this->getPgClassGrade($maxMarksWrittenGrade, $writtenValue) : '') : '',
                            'written' => $writtenValue != null ? ($maxMarksWrittenGrade != 0 ? $this->getPgClassGrade($writtenSubject->id, $classId, $examId, $sessionId, $writtenValue, $maxMarksWrittenGrade) : '') : '',
                            // 'total' => ($writtenValue != null) ? ($totalMaxMarks != 0 ? $this->getPgClassGrade($totalMaxMarks, $totalMarks) : '') : '',
                            'total' => ($writtenValue != null) ? ($totalMaxMarks != 0 ? $this->getPgClassGrade($writtenSubject->id, $classId, $examId, $sessionId, $writtenValue, $maxMarksWrittenGrade) : '') : '',
                        ];
                    }
                    /* Calculate overall grade */
                    // $overallGrade = $totalMaxMarksOverall > 0 ? $this->getPgClassGrade($totalMaxMarksOverall, $totalObtained) : '';
                    // $overallGrade = $totalMaxMarksOverall > 0 ? $this->getPgClassGrade($totalMaxMarksOverall, $totalObtained) : '';
                    $overallGrade = $totalMaxMarksOverall > 0 ? $this->getPgClassOverAllGrade($classId, $examId, $sessionId, $totalObtained,$totalMaxMarksOverall) : '';
                    $report['student'][] = [
                        'session' => $st->session_name,
                        'logo' => config('myconfig.mylogo'),
                        'school' => $st->school == 1 ? 'St. Vivekanand Play House' : 'St. Vivekanand Public Secondary School',
                        'srno' => $st->srno,
                        'name' => $st->name ?? 'N/A',
                        'rollno' => $st->rollno,
                        'dob' => $st->dob ? date('d-M-Y', strtotime($st->dob)) : 'N/A',
                        'father_name' => $st->f_name,
                        'mother_name' => $st->m_name,
                        'class_name' => $st->class_name,
                        'section_name' => $st->section_name,
                        'exam_name' => array_values($exam)[0],
                        'principle_sign' => config('myconfig.mysignature'),
                        'subjects' => $studentSubjects,
                        'overall_grade' => $overallGrade,
                    ];
                }



                return response()->json([
                    'status' => 200,
                    'message' => 'Student With Marks List',
                    'data' => $report,
                ]);
            } else {
                return response()->json([
                    'status' => 202,
                    'message' => 'Student Not Found',
                    'data' => [],
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get report',
            ], 500);
        }
    }

/*     private function getPgClassGrade($maxMarks, $obtainedMarks)
    {
        // Handle absent case
        if ($obtainedMarks == 'Ab.' || $obtainedMarks == null) {
            return 'Ab.';
        }

        // Ensure numeric values
        if (!is_numeric($obtainedMarks) || !is_numeric($maxMarks) || $maxMarks <= 0) {
            return '';
        }

        // Calculate percentage
        $percentage = ($obtainedMarks / $maxMarks) * 100;

        // Assign grade based on percentage ranges
        if ($percentage >= 80 && $percentage <= 100) {
            return 'A';
        } elseif ($percentage >= 65 && $percentage < 80) {
            return 'B';
        } elseif ($percentage >= 50 && $percentage < 65) {
            return 'C';
        } elseif ($percentage >= 33 && $percentage < 50) {
            return 'D';
        } elseif ($percentage >= 0 && $percentage < 33) {
            return 'E';
        } else {
            return '';
        }
    } */

    private function getPgClassGrade($subjectId, $classId, $examId, $sessionId, $obtainedMarks, $maxMarks)
    {
        // Handle absent case
        if ($obtainedMarks == 'Ab.' || $obtainedMarks === null) {
            return 'Ab.';
        }

        // Ensure numeric values
        if (!is_numeric($obtainedMarks) || !is_numeric($maxMarks) || $maxMarks <= 0) {
            return '';
        }

        // Try to get grade from SubjectGrade table
        $grade = DB::table('subject_grades')->select(['grade_name'])->whereNull('is_overall')->where('subject_id', $subjectId)->where('class_id', $classId)->where('exam_id', $examId)->where('session_id', $sessionId)->where('min_marks', '<=', $obtainedMarks)->where('max_marks', '>=', $obtainedMarks)->where('active', 1)->first();

        if (!empty($grade)) {
            return $grade->grade_name;
        }

        // Fallback: calculate grade based on percentage
        $percentage = ($obtainedMarks / $maxMarks) * 100;

        if ($percentage >= 80 && $percentage <= 100) {
            return 'A';
        } elseif ($percentage >= 65 && $percentage < 80) {
            return 'B';
        } elseif ($percentage >= 50 && $percentage < 65) {
            return 'C';
        } elseif ($percentage >= 33 && $percentage < 50) {
            return 'D';
        } elseif ($percentage >= 0 && $percentage < 33) {
            return 'E';
        } else {
            return '';
        }
    }

    private function getPgClassOverAllGrade($classId, $examId, $sessionId, $obtainedMarks, $maxMarks)
    {
        // Handle absent case
        if ($obtainedMarks == 'Ab.' || $obtainedMarks == null) {
            return 'Ab.';
        }

        // Ensure numeric values
        if (!is_numeric($obtainedMarks) || !is_numeric($maxMarks) || $maxMarks <= 0) {
            return '';
        }

        // Try to get grade from SubjectGrade table
        $grade = DB::table('subject_grades')->select(['grade_name'])->whereNull('subject_id')->where('is_overall', 2)->where('class_id', $classId)->where('exam_id', $examId)->where('session_id', $sessionId)->where('min_marks', '<=', $obtainedMarks)->where('max_marks', '>=', $obtainedMarks)->where('active', 1)->first();
        if (!empty($grade)) {
            return $grade->grade_name;
        }

        // Fallback: calculate grade based on percentage
        $percentage = ($obtainedMarks / $maxMarks) * 100;

        if ($percentage >= 80 && $percentage <= 100) {
            return 'A';
        } elseif ($percentage >= 65 && $percentage < 80) {
            return 'B';
        } elseif ($percentage >= 50 && $percentage < 65) {
            return 'C';
        } elseif ($percentage >= 33 && $percentage < 50) {
            return 'D';
        } elseif ($percentage >= 0 && $percentage < 33) {
            return 'E';
        } else {
            return '';
        }
    }




}
