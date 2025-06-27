<?php

namespace App\Http\Controllers\Marks;

use App\Http\Controllers\Controller;
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
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use PhpParser\Node\Stmt\TryCatch;
use App\Http\Controllers\Admin\ClassMasterController;
use App\Http\Controllers\Admin\ExamMasterController;
use App\Http\Controllers\Admin\SectionMasterController;
use App\Http\Controllers\Admin\SessionMasterController;
use App\Http\Controllers\Admin\SubjectMasterController;
use App\Http\Controllers\Student\StdAttendanceController;
use App\Http\Controllers\Student\StudentController;
use App\Http\Controllers\Student\StudentMasterController;

class StdMarksController extends Controller
{
    //
    public function marksEntry()
    {
        $classes = ClassMasterController::getClasses();
        $exams = ExamMasterController::getAllExam();
        return view('marks.marks_entry.index', compact('classes', 'exams'));
    }




    public function marksEntryStore(Request $request)
    {
        // Decode the JSON string for updated students
        $updatedStudents = json_decode($request->input('updated_students'), true);

        // Validate the decoded student data
        $data = $request->validate([
            'updated_students' => 'required', // Ensure the updated_students is an array
            'updated_students.*.srno' => 'required|exists:stu_main_srno,srno', // Validate each student's srno
            'updated_students.*.status' => 'in:1,0', // Validate attendance status
            'updated_students.*.marks' => [
                'nullable',
                'numeric', // Ensure marks are numeric
                function ($attribute, $value, $fail) use ($request) {
                    // Max marks validation
                    $threshold = MarksMaster::where('session_id', $request->current_session)
                        ->where('class_id', $request->hidden_class)
                        ->where('subject_id', $request->hidden_subject)
                        ->where('exam_id', $request->hidden_exam)
                        ->where('active', 1)
                        ->value('max_marks');
                    if ($value > $threshold) {
                        $fail("The $attribute must not be greater than $threshold.");
                    }
                },
            ],
        ]);

        $user = Auth::user();
        $updatedCount = 0;

        // Loop through the updated students and update or create their records
        foreach ($updatedStudents as $std) {
            // Get the current marks of the student (if any)
            $st = Marks::where('srno', $std['srno'])
                ->where('class_id', $request->hidden_class)
                ->where('exam_id', $request->hidden_exam)
                ->where('subject_id', $request->hidden_subject)
                ->where('session_id', $request->current_session)
                ->value('marks');

            // Update or create the student record
            $marks = isset($std['marks']) && $std['marks'] !== '' ? $std['marks'] : null;
            $student = Marks::updateOrCreate(
                [
                    'srno' => $std['srno'],
                    'class_id' => $request->hidden_class,
                    'exam_id' => $request->hidden_exam,
                    'subject_id' => $request->hidden_subject,
                    'session_id' => $request->current_session,
                ],
                [
                    'session_id' => $request->current_session,
                    'class_id' => $request->hidden_class,
                    'exam_id' => $request->hidden_exam,
                    'subject_id' => $request->hidden_subject,
                    'srno' => $std['srno'],
                    'marks' => $marks, // Keep existing marks if not provided
                    // 'marks' => isset($std['marks']) ? $std['marks'] : $st, // Keep existing marks if not provided
                    'attendance' => isset($std['status']) ? $std['status'] : 0, // Default to 0 (absent) if no status is provided
                    'add_user_id' => $user->id,
                    'edit_user_id' => $user->id,
                    'active' => 1, // Mark as active
                ]
            );

            // If the student data is updated or created, increment the counter
            if ($student) {
                $updatedCount++;
            }
        }

        // After processing all students, check if updates were made
        if ($updatedCount > 0) {
            return response()->json([
                'success' => true,
                'message' => "Students Marks Entered successfully.",

            ]);
            // return redirect()->route('marks.marks-entry.index')->with('success', 'Students Marks Entered successfully.');
        } else {
            return response()->json([
                'error' => true,
                'message' => "Something went wrong, please try again.",

            ]);
            // return redirect()->back()->with('error', 'Something went wrong, please try again.');
        }
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
            // Validate the request
            $validator = Validator::make($request->all(), [
                'class' => 'required|exists:class_masters,id,active,1',
                'subject' => 'required|exists:subject_masters,id,active,1',
                'exam' => 'required|exists:exam_masters,id,active,1',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $validator->errors()
                ], 400);
            }
            $sessionId = $request->session;
            $examId = $request->exam;
            $classId = $request->class;
            $subjectIds = $request->subject;
            $studentIds = explode(",", $request->std_id);



            $marks = Marks::where('session_id', $sessionId)
                ->where('exam_id', $examId)
                ->where('class_id', $classId)
                ->whereIn('subject_id', $subjectIds)
                ->whereIn('srno', $studentIds)
                ->get();
            $marksGroup = $marks->groupBy(['subject_id', 'srno']);
            $subjects = SubjectMasterController::getAllSubjects(['id', 'subject'], '', [], [], false, '', false, ['id' => $subjectIds]);


            $report = [];

            foreach ($subjects as $subjectId => $subjectName) {
                $subjectReport = [
                    'subject' => $subjectName,
                    'students' => []
                ];

                foreach ($studentIds as $studentId) {
                    $studentMarks = $marksGroup[$subjectId][$studentId] ?? null;
                    $fields = [
                        'stu_main_srno.srno',
                        'stu_main_srno.rollno',
                        'stu_detail.name',
                        'stu_main_srno.ssid',
                        'stu_main_srno.class',
                        'stu_main_srno.section',
                        'stu_main_srno.session_id',
                        'stu_main_srno.active',
                    ];

                    $student = StudentMasterController::getStdWithNames(false, $fields)->where('stu_main_srno.srno', $studentId)->where('stu_main_srno.class', $classId)->where('stu_main_srno.session_id', $sessionId)->first();
                    $subjectReport['students'][] = [
                        'student_id' => $studentId,
                        'name' => $student->name ?? 'N/A',
                        'roll_number' => $student->rollno ?? 'N/A',
                        'marks' => $studentMarks ? $studentMarks->first()->marks : 'N/A',
                    ];
                }

                $report[] = $subjectReport;
            }
            return response()->json([
                'status' => 200,
                'message' => "Subject Marks List",
                'data' => $report
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => "Failed to get subjects " . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Marks-Report Excel File
     */


    public function marksReportExcel(Request $request)
    {
        try {
            $response = $this->getMarksReport($request);

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

            $reportData = $decodedResponse['data'] ?? null;
            if (!$reportData) {
                return response()->json(['status' => 'error', 'message' => 'No data found'], 404);
            }

            $fileName = 'marks_report.csv';
            $csvContent = '';
            $output = fopen('php://memory', 'w');
            if ($output === false) {
                throw new \Exception('Failed to open output stream.');
            }

            // Collect all subjects and initialize student data
            $subjects = [];
            $studentsData = [];

            foreach ($reportData as $subjectData) {
                $subjects[] = $subjectData['subject'];

                foreach ($subjectData['students'] as $student) {
                    if (!isset($studentsData[$student['roll_number']])) {
                        $studentsData[$student['roll_number']] = [
                            'name' => $student['name'],
                            'marks' => []
                        ];
                    }
                    $studentsData[$student['roll_number']]['marks'][$subjectData['subject']] = $student['marks'];
                }
            }

            // Write headers
            $headers = ['Student'];
            foreach ($subjects as $subject) {
                $headers[] = $subject;
            }
            $headers[] = 'Total';
            fputcsv($output, $headers);

            // Write student data
            ksort($studentsData); // Sort by roll number
            foreach ($studentsData as $rollNumber => $student) {
                $row = [$student['name']];
                $totalMarks = 0;

                // Add marks for each subject
                foreach ($subjects as $subject) {
                    $mark = $student['marks'][$subject] ?? 'N/A';
                    $row[] = $mark;
                    if ($mark !== 'N/A' && $mark !== null) {
                        $totalMarks += (float)$mark;
                    }
                }

                // Add total
                $row[] = ($totalMarks > 0) ? $totalMarks : 0;

                // Write the complete row
                fputcsv($output, $row);
            }
            // Rewind the memory to the start
            rewind($output);

            // Capture the content into a string
            $csvContent = stream_get_contents($output);

            fclose($output);

            return response($csvContent, 200)->header('Content-Type', 'text/csv')
                ->header('Content-Disposition', 'attachment; filename="' . $fileName . '"');
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => "Failed to export report: " . $e->getMessage()
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
            'students' => $students
        ]);
        // return view('marks.marksheet.exam_wise_public_report_print');
    }
    public function publicSchoolExamWisePrintStore(Request $request)
    {
        $request->validate([
            'exam' => [
                'required',
                'exists:exam_masters,id,active,1'
            ],
            'class' => [
                'required',
                'exists:class_masters,id,active,1'
            ],
            'section' => [
                'required',
                'exists:section_masters,id,active,1'
            ],
            'std_id' => 'required',
        ]);
        $exam = $request->exam;
        $classId = $request->class;
        $sectionId = $request->section;
        $students = $request->std_id;



        return redirect()->route('marks.marks-report.public-exam-wise.print')
            ->with('exam', $exam)
            ->with('class', $classId)
            ->with('section', $sectionId)
            ->with('students', $students);

        // return view('marks.marksheet.exam_wise_public_report_print');
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
            'students' => $students
        ]);
        // return view('marks.marksheet.exam_wise_play_report_print');
    }
    public function playSchoolExamWisePrintStore(Request $request)
    {
        $request->validate([
            'exam' => [
                'required',
                'exists:exam_masters,id,active,1'
            ],
            'class' => [
                'required',
                'exists:class_masters,id,active,1'
            ],
            'section' => [
                'required',
                'exists:section_masters,id,active,1'
            ],
            'std_id' => 'required',
        ]);
        $exam = $request->exam;
        $classId = $request->class;
        $sectionId = $request->section;
        $students = $request->std_id;
        return redirect()->route('marks.marks-report.play-exam-wise.print')
            ->with('exam', $exam)
            ->with('class', $classId)
            ->with('section', $sectionId)
            ->with('students', $students);
    }




    private function getGrade($total, $marks)
    {
        if ($total == 5) {
            if ($marks == 5) {
                return "A";
            } else if ($marks == 4) {
                return "B";
            } else if ($marks == 3) {
                return "C";
            } else {
                return "D";
            }
        } else if ($total == 10) {
            if ($marks >= 9 && $marks <= 10) {
                return "A";
            } else if ($marks >= 7 && $marks <= 8) {
                return "B";
            } else if ($marks >= 5 && $marks <= 6) {
                return "C";
            } else {
                return "D";
            }
        } else if ($total == 20) {
            if ($marks >= 17 && $marks <= 20) {
                return "A";
            } else if ($marks >= 13 && $marks <= 16) {
                return "B";
            } else if ($marks >= 9 && $marks <= 12) {
                return "C";
            } else {
                return "D";
            }
        } else if ($total == 25) {
            if ($marks >= 21 && $marks <= 25) {
                return "A";
            } else if ($marks >= 16 && $marks <= 20) {
                return "B";
            } else if ($marks >= 11 && $marks <= 15) {
                return "C";
            } else {
                return "D";
            }
        } else if ($total == 50) {
            if ($marks >= 41 && $marks <= 50) {
                return "A";
            } else if ($marks >= 31 && $marks <= 40) {
                return "B";
            } else if ($marks >= 21 && $marks <= 30) {
                return "C";
            } else {
                return "D";
            }
        } else if ($total == 70) {
            if ($marks >= 57 && $marks <= 70) {
                return "A";
            } else if ($marks >= 43 && $marks <= 56) {
                return "B";
            } else if ($marks >= 29 && $marks <= 42) {
                return "C";
            } else {
                return "D";
            }
        } else if ($total == 100) {
            if ($marks >= 81 && $marks <= 100) {
                return "A";
            } else if ($marks >= 61 && $marks <= 80) {
                return "B";
            } else if ($marks >= 41 && $marks <= 60) {
                return "C";
            } else {
                return "D";
            }
        } else if ($total == 150) {
            if ($marks >= 136 && $marks <= 150) {
                return "A+";
            } else if ($marks >= 121 && $marks <= 135) {
                return "A";
            } else if ($marks >= 91 && $marks <= 120) {
                return "B+";
            } else {
                return "B";
            }
        } else if ($total == 200) {
            if ($marks >= 161 && $marks <= 200) {
                return "A";
            } else if ($marks >= 121 && $marks <= 160) {
                return "B";
            } else if ($marks >= 81 && $marks <= 120) {
                return "C";
            } else {
                return "D";
            }
        }
        return "ER";
    }

    public function getMarkSheetReport(Request $request)
    {
        try {
            //code...
            $validator = Validator::make($request->all(), [
                'class' => 'required|exists:class_masters,id,active,1',
                'section' => 'required|exists:section_masters,id,active,1',
                'exam' => 'required|exists:exam_masters,id,active,1',
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $validator->errors()
                ], 400);
            }

            $sessionId = $request->session;
            $examId = $request->exam;
            $classId = $request->class;
            $sectionId = $request->section;
            $studentIds = explode(",", $request->std_id);
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
            ];
            $students = StudentMasterController::getStdWithNames(false, $fields)
                ->whereIn('stu_main_srno.srno', $studentIds)
                ->where('stu_main_srno.class', $classId)
                ->where('stu_main_srno.section', $sectionId)
                ->where('stu_main_srno.session_id', $sessionId)
                ->get();
            if ($students->isNotEmpty()) {
                $exam = ExamMasterController::getAllExam(['id', 'exam'], ['id' => $examId]);
                $report = [
                    'student' => []
                ];
                foreach ($students as $key => $st) {
                    # code...
                    $subjects = SubjectMasterController::getAllSubjects(['subject', 'id', 'subject_id', 'by_m_g', 'priority', 'class_id'], '', ['class_id' => $st->class], [], true);
                    $writtenSubjects = $subjects->whereNull('subject_id')->where('priority', 1)->values();
                    $oralSubjects = $subjects->whereNotNull('subject_id')->where('priority', 2)->values();
                    $practicalSubjects = $subjects->whereNotNull('subject_id')->where('priority', 3)->values();
                    $studentSubjects = [];

                    $marks = Marks::where('exam_id', $examId)
                        ->where('session_id', $sessionId)
                        ->where('class_id', $st->class)
                        ->where('srno', $st->srno)->where('attendance', 1)
                        ->where('active', 1)->get();

                    $marksMaster = MarksMaster::where('exam_id', $examId)
                        ->where('session_id', $sessionId)
                        ->where('class_id', $st->class)
                        ->where('active', 1)->get();


                    foreach ($writtenSubjects as $writtenSubject) {
                        $oral = $oralSubjects->where('subject_id', $writtenSubject->id)->where('by_m_g', $writtenSubject->by_m_g)->first();
                        $practical = $practicalSubjects->where('subject_id', $writtenSubject->id)->where('by_m_g', $writtenSubject->by_m_g)->first();

                        $writtenMarks = $marks->where('subject_id', $writtenSubject->id)->first();
                        $maxMarksWrittenGrade = $marksMaster->where('subject_id', $writtenSubject->id)->value('max_marks') ?? 0;

                        $oralMarks = null;
                        $maxMarksOralGrade = 0;
                        if ($oral) {
                            $oralMarks = $marks->where('subject_id', $oral->id)->first();
                            $maxMarksOralGrade = $marksMaster->where('subject_id', $oral->id)->value('max_marks') ?? 0;
                        }

                        $practicalMarks = null;
                        $maxMarksPracticalGrade = 0;
                        if ($practical) {
                            $practicalMarks = $marks->where('subject_id', $practical->id)->first();
                            $maxMarksPracticalGrade = $marksMaster->where('subject_id', $practical->id)->value('max_marks') ?? 0;
                        }

                        $writtenValue = $writtenMarks ? $writtenMarks->marks : null;
                        $oralValue = $oralMarks ? $oralMarks->marks : null;
                        $practicalValue = $practicalMarks ? $practicalMarks->marks : null;

                        $totalMarks = 0;
                        $totalMaxMarks = $maxMarksWrittenGrade + $maxMarksOralGrade + $maxMarksPracticalGrade;

                        if ($writtenValue !== null) $totalMarks += $writtenValue;
                        if ($oralValue !== null) $totalMarks += $oralValue;
                        if ($practicalValue !== null) $totalMarks += $practicalValue;

                        if ($writtenSubject->by_m_g == 1) {
                            $studentSubjects[] = [
                                'name' => $writtenSubject->subject,
                                'by_m_g' => $writtenSubject->by_m_g,
                                'written' => $st->school == 1 && $writtenSubject->by_m_g == 2 ? ($writtenValue !== null ? ($maxMarksWrittenGrade !== 0 ? $this->getGrade($maxMarksWrittenGrade, $writtenValue) : '') : '') : $writtenValue,
                                'oral' => $st->school == 1 && $writtenSubject->by_m_g == 2  ? ($oralValue !== null ? ($maxMarksOralGrade !== 0 ? $this->getGrade($maxMarksOralGrade, $oralValue) : '') : '') : $oralValue,
                                'practical' => $st->school == 1  && $writtenSubject->by_m_g == 2  ? ($practicalValue !== null ? ($maxMarksPracticalGrade !== 0 ? $this->getGrade($maxMarksPracticalGrade, $practicalValue) : '') : '') : $practicalValue,
                                'total' => $st->school == 1 && $writtenSubject->by_m_g == 2  ? (($writtenValue !== null || $oralValue !== null || $practicalValue !== null) ? ($totalMaxMarks !== 0 ? $this->getGrade($totalMaxMarks, $totalMarks) : '') : '') : $totalMarks,
                            ];
                        } else {
                            $studentSubjects[] = [
                                'name' => $writtenSubject->subject,
                                'by_m_g' => $writtenSubject->by_m_g,
                                'written' => $writtenValue !== null ? ($maxMarksWrittenGrade !== 0 ? $this->getGrade($maxMarksWrittenGrade, $writtenValue) : '') : '',
                                'oral' => $oralValue !== null ? ($maxMarksOralGrade !== 0 ? $this->getGrade($maxMarksOralGrade, $oralValue) : '') : '',
                                'practical' => $practicalValue !== null ? ($maxMarksPracticalGrade !== 0 ? $this->getGrade($maxMarksPracticalGrade, $practicalValue) : '') : '',
                                'total' => ($writtenValue !== null || $oralValue !== null || $practicalValue !== null) ? ($totalMaxMarks !== 0 ? $this->getGrade($totalMaxMarks, $totalMarks) : '') : '',
                            ];
                        }
                    }

                    $grandTotalMarks = array_sum(array_map(function ($item) {
                        if ($item['by_m_g'] == 1) {
                            # code...
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
                        'class_name' => $st->class_name,
                        'section_name' => $st->section_name,
                        'exam_name' => array_values($exam)[0],
                        'principle_sign' => config('myconfig.mysignature'),
                        'subjects' => $studentSubjects,
                        'grand_total_marks' => $grandTotalMarks,
                    ];
                }
                return response()->json([
                    'status' => 200,
                    'message' => "Student With Marks List",
                    'data' => $report
                ]);
            } else {
                return response()->json([
                    'status' => 202,
                    'message' => "Student Not Found",
                    'data' => []
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => "Failed to get report " . $e->getMessage() . " at line number " . $e->getLine()
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

    public function classWiseRankReport(Request $request)
    {
        try {
            //code...
            $validator = Validator::make($request->all(), [
                'class' => 'required|exists:class_masters,id,active,1',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $validator->errors()
                ], 400);
            }

            $sessionId = $request->session;
            $classId = $request->class;
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
            ];
            $students = StudentMasterController::getStdWithNames(false, $fields)->where('stu_main_srno.class', $classId)->where('stu_main_srno.session_id', $sessionId)->get();
            if ($students->isNotEmpty()) {
                # code...
                $report = [];
                foreach ($students as $st) {

                    $marks = Marks::where('srno', $st->srno)->where('session_id', $sessionId)
                        ->where('active', 1)
                        ->get(['subject_id', 'marks']);
                    $totalMarks = $marks->sum('marks');
                    $totalMeeting = 0;

                    // Get total meetings
                    $totalMeetingsData = AttendanceSchedule::where('session_id', $sessionId)->sum('status');

                    if ($totalMeetingsData) {
                        $totalMeeting = $totalMeetingsData * 2;
                    }
                    $whereAttend = [
                        'where' => [
                            'session_id' => $sessionId,
                            'srno' => $st->srno,
                            'class' => $st->class,
                        ]
                    ];
                    $meetingsAttended = StdAttendanceController::getAttendance(['id', 'session_id', 'srno', 'class', 'status'], $whereAttend)->sum('status');
                    $meetingsAttended = $meetingsAttended ? ($meetingsAttended * 2) : 0;

                    $report[] = [
                        'class' => $st->class_name,
                        'section' => $st->section_name,
                        'srno' => $st->srno,
                        'rollno' => $st->rollno,
                        'name' => $st->name,
                        'total_marks' => $totalMarks,
                        'total_meeting' => $totalMeeting,
                        'meeting_attended' => $meetingsAttended,

                    ];
                }
                usort($report, function ($a, $b) {
                    return $b['total_marks'] <=> $a['total_marks'];
                });

                // First, get unique marks and assign ranks
                $uniqueMarks = [];
                $rank = 1;
                foreach ($report as $item) {
                    if (!isset($uniqueMarks[$item['total_marks']])) {
                        $uniqueMarks[$item['total_marks']] = $rank++;
                    }
                }

                // Then assign ranks to all students
                foreach ($report as &$student) {
                    $student['rank'] = $uniqueMarks[$student['total_marks']];
                }

                return response()->json([
                    'status' => 200,
                    'message' => "Class-Wise Rank Report",
                    'data' => $report
                ]);
            } else {
                # code...
                return response()->json([
                    'status' => 200,
                    'message' => "Class-Wise Rank Report",
                    'data' => []
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => "Failed to get Class-Wise Rank Report " . $e->getMessage() . " at line number " . $e->getLine()
            ], 500);
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
                    'message' => 'Failed to generate report: ' . $response->getContent()
                ], 500);
            }

            $decodedResponse = json_decode($response->getContent(), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return response()->json(['status' => 'error', 'message' => 'Invalid JSON response: ' . json_last_error_msg()], 500);
            }

            $reportData = $decodedResponse['data'] ?? null;

            if (!$reportData) {
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
                ->header('Content-Disposition', 'attachment; filename="' . $fileName . '"');
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => "Failed to export report: " . $e->getMessage()
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
            'dateMessage' => $dateMessage
        ]);

        // return view('marks.marksheet.marksheet_final_pg_nur_print');
    }


    public function finalMarksheetPGNURStore(Request $request)
    {
        $request->validate([
            'class' => [
                'required',
                'exists:class_masters,id,active,1'
            ],
            'section' => [
                'required',
                'exists:section_masters,id,active,1'
            ],
            'std_id' => 'required',
        ]);
        $classId = $request->class;
        $sectionId = $request->section;
        $students = $request->std_id;
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
            // Validate input parameters
            $validated = $request->validate([
                'class' => 'required|exists:class_masters,id,active,1',
                'section' => 'required|exists:section_masters,id,active,1'
            ]);

            // Get current active session
            $session = session('marks_current_session');

            if (!$session) {
                return response()->json([
                    'error' => 'No active session found',
                    'message' => 'Set Current Session'
                ], 400);
            }
            $srno = explode(",", $request->std_id);
            // Get students for this session, class, and section

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
            ];
            $students = StudentMasterController::getStdWithNames(false, $fields)
                ->where('stu_main_srno.session_id', $session->id)
                ->where('stu_main_srno.class', $validated['class'])
                ->where('stu_main_srno.section', $validated['section'])
                ->whereIn('stu_main_srno.srno', $srno)->get();

            if ($students->isEmpty()) {
                return response()->json([
                    'error' => 'No students found',
                    'message' => 'No students in this class and section'
                ], 404);
            }

            $studentReports = [];


            foreach ($students as $student) {
                // Get student details
                $studentDetail = StudentMasterController::getStdWithNames(false, $fields)
                    ->where('stu_main_srno.session_id', $student->session_id)
                    ->where('stu_main_srno.class', $student->class)
                    ->where('stu_main_srno.section', $student->section)
                    ->where('stu_main_srno.srno', $student->srno)->first();


                // Get subjects for this class
                $subFields = ['id', 'class_id', 'subject', 'subject_id', 'by_m_g', 'priority', 'active'];
                $subWhere = ['class_id' => $validated['class']];
                $subjects = SubjectMasterController::getAllSubjects($subFields, '', $subWhere, ['by_m_g' => 'asc'], true);

                $exams = ExamMasterController::getAllExam(['id', 'exam']);

                $subjectMarks = [];
                $totalMax = 0;
                $totalObtained = 0;

                // Calculate marks for each subject and exam
                foreach ($subjects as $subject) {
                    $subjectResult = [
                        'subject_id' => $subject->id,
                        'subject_name' => $subject->subject,
                        'sub_subject_id' => $subject->subject_id,
                        'by_m_g' => $subject->by_m_g,
                        'exams' => []
                    ];

                    $subjectTotalMax = 0;
                    $subjectTotalObtained = 0;

                    foreach ($exams as $key => $exam) {
                        // Check if marks exist for this exam and subject
                        $marksCheck = DB::table('marks')
                            ->where('class_id', $validated['class'])
                           ->where('exam_id', $key)
                            ->where('active', 1)
                            ->exists();

                        if ($marksCheck) {
                            $marks = DB::table('marks_masters')
                                ->join('marks', function ($join) use ($key, $subject, $student, $session, $validated) {
                                    $join->on('marks_masters.exam_id', '=', 'marks.exam_id')
                                        ->on('marks_masters.subject_id', '=', 'marks.subject_id')
                                        ->where('marks_masters.exam_id', $key)
                                        ->where('marks_masters.subject_id', $subject->id)
                                        ->where('marks.session_id', $session->id)
                                        ->where('marks.class_id', $validated['class'])
                                        ->where('marks.srno', $student->srno);
                                })
                                ->first();

                            if ($marks) {
                                $subjectResult['exams'][] = [
                                    'exam_id' => $key,
                                   'exam_name' => $exam,
                                    'obtained_marks' => $marks->marks ?? 0,
                                    'max_marks' => $marks->max_marks ?? 0,
                                    'grade' => $this->getGrade($marks->max_marks, $marks->marks) ?? 'Abst',
                                    'status' => $marks->marks ? 'Present' : 'Absent'
                                ];

                                $subjectTotalMax += $marks->max_marks ?? 0;
                                $subjectTotalObtained += $marks->marks ?? 0;
                            } else {
                                $subjectResult['exams'][] = [
                                   'exam_name' => $exam,
                                    'obtained_marks' => 0,
                                    'max_marks' => 0,
                                    'status' => 'Absent'
                                ];
                            }
                        }
                    }

                    $subjectResult['total_max'] = ($subjectTotalMax === null || $this->getGrade($subjectTotalMax, $subjectTotalObtained) === null)
                        ? 'Abst'
                        : ($subject->by_m_g == 1 ? $subjectTotalMax : $this->getGrade($subjectTotalMax, $subjectTotalObtained));

                    $subjectResult['total_obtained'] = ($subjectTotalObtained === null || $this->getGrade($subjectTotalMax, $subjectTotalObtained) === null)
                        ? 'Abst'
                        : ($subject->by_m_g == 1 ? $subjectTotalObtained : $this->getGrade($subjectTotalMax, $subjectTotalObtained));

                    $totalMax += $subjectTotalMax;
                    $totalObtained += $subjectTotalObtained;

                    $subjectMarks[] = $subjectResult;
                }

                // Calculate attendance
                $totalAttendanceDays = DB::table('attendance_schedule')
                    ->where('session_id', $session->id)
                    ->sum('status');
                $studentAttendance = 0;
                if ($totalAttendanceDays > 0 || $totalObtained !== '') {
                    # code...
                    $studentAttendance = StdAttendanceController::getAttendance(['id', 'status', 'session_id', 'class', 'section', 'srno'])
                        ->where('session_id', $session->id)
                        ->where('class', $student->class)
                        ->where('section', $student->section)
                        ->where('srno', $student->srno)
                        ->sum('status');
                }

                $studentReports[] = [
                    'student_details' => [
                        'name' => $studentDetail->name,
                        'sr_no' => $student->srno,
                        'father_name' => $studentDetail->f_name,
                        'class' => $studentDetail->class_name,
                        'section' => $studentDetail->section_name,
                        'roll_no' => $student->rollno,
                        'dob' => $studentDetail->dob ? Carbon::parse($studentDetail->dob)->format('d-M-Y') : 'N/A'
                    ],
                    'subject_marks' => $subjectMarks,
                    'total_marks' => [
                        'max_marks' => $totalMax,
                        'obtained_marks' => $totalObtained,
                    ],
                    'attendance' => [
                        'days_present' => $studentAttendance * 2,
                        'total_days' => $totalAttendanceDays * 2,

                    ],
                   'result_data' => [
                        'result' => 'Pass',
                        'result_date_message' => $request->dateMessage ?? '',
                        'session_start_message' => $request->sessionMessage ?? '',
                    ]
                ];
            }

            return response()->json([
                'session' => $session->session,
                'logo' => [
                    'school_logo' => config('myconfig.mylogo'),
                    'principal_sign' => config('myconfig.mysignature')
                ],
                'students' => $studentReports
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Access Denied',
                'message' => $e->getMessage()
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
            'dateMessage' => $dateMessage
        ]);
    }
    public function finalMarksheetOnlyForClassKGStore(Request $request)
    {
        $request->validate([
            'class' => [
                'required',
                'exists:class_masters,id,active,1'
            ],
            'section' => [
                'required',
                'exists:section_masters,id,active,1'
            ],
            'std_id' => 'required',
        ]);
        $classId = $request->class;
        $sectionId = $request->section;
        $students = $request->std_id;
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
            'class' => 'required|exists:class_masters,id,active,1',
            'section' => 'required|exists:section_masters,id,active,1',
            'students' => 'nullable|string',
        ]);

        // Check if validation fails
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => 'Validation Error',
                'messages' => $validator->errors(),
            ], 422);
        }

        // Get validated data
        $validated = $validator->validated();

        $fields = ['stu_main_srno.class', 'stu_main_srno.section', 'stu_main_srno.session_id', 'stu_main_srno.srno'];
        $studentIds = explode(",", $request->students);

        $reportCards = [];
        $session = session('marks_current_session');

        foreach ($studentIds as $student) {
        // foreach ($students as $student) {
            try {
                $reportCard = $this->generateSingleReportCard(
                    $student,
                    $validated['class'],
                    $validated['section'] ?? null,
                    $request->dateMessage ?? '',
                    $request->sessionMessage ?? ''
                );

                $reportCards[] = $reportCard;
            } catch (Exception $e) {
                $reportCards[] = [
                    'student_id' => $student,
                    'error' => $e->getMessage()
                ];
            }
        }

        // Return JSON response
        return response()->json([
            'success' => true,
            'logo' => [
                'school_logo' => config('myconfig.mylogo'),
                'principal_sign' => config('myconfig.mysignature')
            ],
            'session' => [
                'name' => $session->session,
                'id' => $session->id
            ],
            'report_cards' => $reportCards,
            'class_id' => $validated['class'],
            'section_id' => $validated['section'] ?? null,
            'total_students' => count($reportCards)
        ]);
    }


    private function generateSingleReportCard($student, $classId, $sectionId = null, $dateMessage, $sessionMessage)
    {

        // Get current active session
        $session = session('marks_current_session');

        if (!$session) {
            return response()->json([
                'error' => 'Session Error',
                'message' => 'No active session found'
            ], 404);
        }

        // Build the base query for student details with multiple joins
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
        ];


        $detail = StudentMasterController::getStdWithNames(false, $fields)
        ->where('stu_main_srno.srno', $student)
        ->where('stu_main_srno.class', $classId)
        ->where('stu_main_srno.section', $sectionId)
        ->where('stu_main_srno.session_id', $session->id)->first();
        if (!$detail) {
            return response()->json([
                'error' => 'No students found',
                'message' => 'No students in this class and section'
            ], 404);
        }

        // Fetch subjects for the class
        $subFields = ['id', 'class_id', 'subject', 'subject_id', 'by_m_g', 'priority', 'active'];
        $subWhere = ['class_id' => $classId];
        $subjects = SubjectMasterController::getAllSubjects($subFields, '', $subWhere, [], true);


        // Fetch exams
        $exams = ExamMasterController::getAllExam(['id', 'exam']);

        // Calculate marks and grades
        $marksData = $this->calculateMarksData($student, $classId, $session->id, $subjects, $exams);
        // Calculate attendance
        $totalAttendanceDays = DB::table('attendance_schedule')
            ->where('session_id', $session->id)
            ->sum('status');
        $studentAttendance = 0;
        if ($totalAttendanceDays > 0) {
            # code...

            $studentAttendance = StdAttendanceController::getAttendance(['id', 'status', 'session_id', 'class', 'section', 'srno'])
                ->where('session_id', $session->id)
                ->where('srno', $detail->srno)
                ->where('class', $classId)
                ->sum('status');

        }
        return [

            'student_id' => $detail->srno,
            'student_details' => [
                'name' => $detail->name,
                'father_name' => $detail->f_name,
                'class' => $detail->class,
                'section' => $detail->section,
                'roll_no' => $detail->rollno,
                'dob' => $detail->dob,
                'ssid' => $detail->ssid,
            ],

            'attendance' => [
                'days_present' => $studentAttendance * 2,
                'total_days' => $totalAttendanceDays * 2,
                'result_date_message' => $dateMessage,
                'session_start_message' => $sessionMessage,

            ],
            'marks_data' => $marksData,
            'summary' => $this->calculateReportSummary($marksData)
        ];
    }

    private function calculateMarksData($student, $classId, $sessionId, $subjects, $exams)
    {
        $marksData = [];

        foreach ($subjects as $subject) {
            $subjectMarks = [];
            $totalMaxMarks = 0;
            $totalObtainedMarks = 0;

            foreach ($exams as $key => $exam) {
                // Fetch marks for the subject and exam
                $marks = DB::table('marks')
                    ->where('srno', $student)
                    ->where('class_id', $classId)
                    ->where('session_id', $sessionId)
                    ->where('exam_id', $key)
                    ->where('subject_id', $subject->id)->where('attendance', 1)
                    ->where('active', 1)
                    ->first();

                // Fetch max marks
                $maxMarks = DB::table('marks_masters')
                    ->where('class_id', $classId)
                    ->where('session_id', $sessionId)
                    ->where('exam_id', $key)
                    ->where('subject_id', $subject->id)
                    ->where('active', 1)
                    ->value('max_marks');
                if ($maxMarks !== null) {
                    $obtainedMarks = $marks ? $marks->marks : 0;
                    $grade = $this->calculateGrade($obtainedMarks, $maxMarks);

                    $subjectMarks[] = [
                        'exam_id' => $key,
                        'exam_name' => $exam,
                        'max_marks' => $maxMarks ?? 0,
                        'obtained_marks' => ($maxMarks != null && $obtainedMarks == 0) ? 'Abs' : $obtainedMarks,
                        'grade' => $grade
                    ];

                    // Accumulate total marks
                    $totalMaxMarks += $maxMarks;
                    $totalObtainedMarks += $obtainedMarks;
                }
            }

            // Calculate subject-level summary
            $marksData[] = [
                'subject_id' => $subject->id,
                'subject_name' => $subject->subject,
                'by_m_g' => $subject->by_m_g,
                'exam_marks' => $subjectMarks,
                'total_max_marks' => $totalMaxMarks,
                'total_obtained_marks' => $totalObtainedMarks,
                'percentage' => $totalMaxMarks > 0
                    ? round(($totalObtainedMarks / $totalMaxMarks) * 100, 2)
                    : 'NaN',
                'overall_grade' => $this->calculateGrade($totalObtainedMarks, $totalMaxMarks)
            ];
        }

        return $marksData;
    }

    private function calculateReportSummary($marksData)
    {
        $totalMaxMarks = 0;
        $totalObtainedMarks = 0;
        $subjectGrades = [];

        foreach ($marksData as $subject) {
            if ($subject['by_m_g'] !== 2) {
                $totalMaxMarks += $subject['total_max_marks'];
                $totalObtainedMarks += $subject['total_obtained_marks'];
                $subjectGrades[] = $subject['overall_grade'];
            }
        }

        return [
            'total_max_marks' => $totalMaxMarks,
            'total_obtained_marks' => $totalObtainedMarks,
            'overall_percentage' => $totalMaxMarks > 0
                ? round(($totalObtainedMarks / $totalMaxMarks) * 100, 2)
                : null,
           'overall_result' => $this->determineOverallResult($subjectGrades)
        ];
    }

    private function calculateGrade($obtainedMarks, $maxMarks)
    {
        $total = $maxMarks;
        if ($total == 50) {
            if ($obtainedMarks >= 46 && $obtainedMarks <= 50) {
                return "A+";
            } elseif ($obtainedMarks >= 41 && $obtainedMarks <= 45) {
                return "A";
            } elseif ($obtainedMarks >= 31 && $obtainedMarks <= 40) {
                return "B+";
            } else {
                return "B";
            }
        } else if ($total == 150) {
            if ($obtainedMarks >= 136 && $obtainedMarks <= 150) {
                return "A+";
            } elseif ($obtainedMarks >= 121 && $obtainedMarks <= 135) {
                return "A";
            } elseif ($obtainedMarks >= 91 && $obtainedMarks <= 120) {
                return "B+";
            } else {
                return "B";
            }
        }
        return "ER";
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
            'dateMessage' => $dateMessage
        ]);


    }

    public function finalMarksheetFirstSecondStore(Request $request)
    {
        $request->validate([
            'class' => [
                'required',
                'exists:class_masters,id,active,1'
            ],
            'section' => [
                'required',
                'exists:section_masters,id,active,1'
            ],
            'std_id' => 'required',
        ]);
        $classId = $request->class;
        $sectionId = $request->section;
        $students = $request->std_id;
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
            // Validate input parameters
            $validator = Validator::make($request->all(), [
                'class' => 'required|exists:class_masters,id,active,1',
                'section' => 'required|exists:section_masters,id,active,1',
                'students' => 'required',
            ]);

            // Check if validation fails
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Validation Error',
                    'messages' => $validator->errors(),
                ], 422);
            }
            // Get validated data
            $validatedData = $validator->validated();
            // Get current active session
            $session = session('marks_current_session');
            if (blank($session)) {
                return response()->json(['error' => 'Current session not set'], 400);
            }

            // Fetch main subjects for the class
            $subFields = ['id', 'subject', 'by_m_g', 'priority', 'subject_id', 'class_id'];
            $subWhere = ['class_id' => $validatedData['class']];
            $subWhereIn = ['priority' => [1]];
            $mainSubjects = SubjectMasterController::getAllSubjects($subFields, '', $subWhere, ['by_m_g' => 'asc'], true, '', false, $subWhereIn);
            // Check if no subjects found for the class
            if ($mainSubjects->isEmpty()) {
                return response()->json(['error' => 'No subjects found'], 404);
            }
            $studentIds = explode(",", $request->students);

            // Student details query
            $fields = [
                'stu_main_srno.srno',
                'stu_main_srno.rollno',
                'stu_main_srno.school',
                'stu_main_srno.class',
                'stu_main_srno.section',
                'stu_detail.dob',
                'stu_detail.name',
                'parents_detail.f_name',
                'class_masters.class as class_name',
                'section_masters.section as section_name'
            ];
            $studentDetails = StudentMasterController::getStdWithNames(false, $fields)
                         ->whereIn('stu_main_srno.srno', $studentIds) // Using whereIn for multiple srnos
                         ->where('stu_main_srno.session_id', $session->id)
                         ->where('stu_main_srno.class', $validatedData['class'])
                         ->where('stu_main_srno.section', $validatedData['section'])->get();


            // Fetch exams
            $exams = ExamMasterController::getAllExam(['id', 'exam']);


            $finalData = [];

            foreach ($studentDetails as $studentDetail) {

                // Prepare results data structure for each student
                $reportData = [
                    'student_info' => $studentDetail,
                    'exams' => [],
                    'attendance' => [],
                ];

                // Iterate through each subject
                foreach ($mainSubjects as $subject) {
                    $subjectMarksData = [
                        'id' => $subject->id,
                        'subject' => $subject->subject,
                        'by_m_g' => $subject->by_m_g,
                        'priority' => $subject->priority,
                        'subSubjectId' => $subject->subject_id,
                        'exam-info' => []
                    ];

                    // Collect marks and grades for each exam
                    foreach ($exams as $key => $exam) {
                        $examMarks = $this->getExamMarksForStudent(
                            $validatedData['class'],
                            $key,
                            $session->id,
                            $studentDetail->srno,
                            $subject->id // Pass the subject_id to filter marks for the specific subject
                        );

                        // If marks data exists, add to the subject's exam-info
                        if (!empty($examMarks)) {

                            $subjectMarksData['exam-info'][] = [
                                'exam_id' => $key,
                                'exam' => $exam,
                                'written_marks' => $examMarks['written_marks'],
                                'written_max_marks' => $examMarks['written_max_marks'],
                                'oral_marks' => $examMarks['oral_marks'],
                                'oral_max_marks' => $examMarks['oral_max_marks'],
                                'total_marks' => $examMarks['total_marks'],
                                'max_marks' => $examMarks['max_marks'],
                                'grade' => $examMarks['grade']
                            ];
                        }
                    }

                    // Add subject data to reportData
                    $reportData['exams'][] = $subjectMarksData;
                }
                $reportData['attendance'] = $this->firstSecondAttendance(
                    $session->id,
                    $studentDetail->srno
                );
                // Add the student's report data to the final result
                $finalData[] = $reportData;
            }

            // return response()->json($finalData);
            return response()->json([
                'success' => true,
                'data' => $finalData,
                'session' => $session,
                'logo' => [
                    'school_logo' => config('myconfig.mylogo'),
                    // 'principal_sign' => asset('public/marks/images/' . $image),
                    'principal_sign' => config('myconfig.mysignature'),
                    'result_date_message' => $request->dateMessage ?? '',
                    'session_start_message' => $request->sessionMessage ?? '',
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'An error occurred',
                'message' => $e->getMessage() . " at line number " . $e->getLine()
            ], 500);
        }
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
        $totalMarks = ($mainMarks ? $mainMarks->written_marks : 0) +
            ($oralMarks ? $oralMarks->oral_marks : 0);
        $totalMaxMarks = ($mainMarks ? $mainMarks->written_max_marks : 0) +
            ($oralMarks ? $oralMarks->oral_max_marks : 0);

        // Calculate grade based on total marks
        $grade = $this->getGradeFirstSecond($totalMarks, $totalMaxMarks);

        return [
            'written_marks' => $mainMarks && $mainMarks->attendance == 1 ? $mainMarks->written_marks : "Abs",
            'written_max_marks' => $mainMarks ? $mainMarks->written_max_marks : 0,
            'oral_marks' => $oralMarks && $oralMarks->attendance == 1 ? $oralMarks->oral_marks : "Abs",
            'oral_max_marks' => $oralMarks ? $oralMarks->oral_max_marks : 0,
            'total_marks' => $totalMarks,
            'max_marks' => $totalMaxMarks,
            'grade' => $grade
        ];
    }





    //Grade Function for only Class First And Second Final Marsheet
    private function getGradeFirstSecond($marks, $max_marks)
    {
        $total = $marks * 100.00 / $max_marks;
        if ($total > 80) {
            return "A";
        } elseif ($total > 60) {
            return "B";
        } elseif ($total > 40) {
            return "C";
        } else {
            return "D";
        }
        return "ER";
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
            3 => 'March'
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
                    : 0
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
                    : 0
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
        $data = ExamMasterController::getAllExam(['id','exam']);
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
            'dateMessage' => $dateMessage
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
                'exists:class_masters,id,active,1'
            ],
            'section' => [
                'required',
                'exists:section_masters,id,active,1'
            ],
            'std_id' => 'required',
        ]);
        $classId = $request->class;
        $sectionId = $request->section;
        $students = $request->std_id;
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
                'exists:class_masters,id,active,1'
            ],
            'section' => [
                'required',
                'exists:section_masters,id,active,1'
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
            // Validate the request
            $validator = Validator::make($request->all(), [
                'class' => 'required|exists:class_masters,id,active,1',
                'section' => 'required|exists:section_masters,id,active,1',
                'students' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $validator->errors()
                ], 400);
            }

            // Get active session
            // $session = SessionMaster::select('id', 'session')->where('marks_current_session', 1)->where('active', 1)->first();
            $session = session('marks_current_session');

            if (blank($session)) {
                return response()->json(['status' => 'error', 'message' => 'Current session not set'], 400);
            }

            // Fetch main subjects and exams
            $subFields = ['id', 'subject', 'by_m_g', 'priority', 'subject_id', 'class_id'];
            $subWhere = ['class_id' => $request->class];
            $subWhereIn = ['priority' => [1]];
            $mainSubjects = SubjectMasterController::getAllSubjects($subFields, '', $subWhere, ['by_m_g' => 'asc'], true, '', false, $subWhereIn);


            if ($mainSubjects->isEmpty()) {
                return response()->json(['status' => 'error', 'message' => 'No subjects found'], 404);
            }

            $examIds = explode(",", $request->exam);
            $exams = ExamMasterController::getAllExam(['id', 'exam'],[],[],'', false, ['id' => $examIds]);
            if (empty($exams)) {
                return response()->json(['status' => 'error','message' => 'No exams found'], 404);
            }

            // Fetch students with necessary details using joins
            $studentIds = explode(",", $request->students);
            $fields = [
                'stu_main_srno.srno',
                'stu_main_srno.rollno',
                'stu_main_srno.school',
                'stu_main_srno.class',
                'stu_main_srno.section',
                'stu_detail.dob',
                'stu_detail.name',
                'parents_detail.f_name',
                'class_masters.class as class_name',
                'section_masters.section as section_name'
            ];
            $students = StudentMasterController::getStdWithNames(false, $fields)
                         ->whereIn('stu_main_srno.srno', $studentIds) // Using whereIn for multiple srnos
                         ->where('stu_main_srno.session_id', $session->id)
                         ->where('stu_main_srno.class', $request->class)
                         ->where('stu_main_srno.section', $request->section)->get();

            // Process students
            $finalData = $students->map(function ($studentDetail) use ($mainSubjects, $exams, $session, $request) {
                return [
                    'student_info' => $studentDetail,

                    'exams' => $mainSubjects->map(function ($subject) use ($exams, $request, $session, $studentDetail) {
                        return $this->processSubjectMarks(
                            $subject,
                            $exams,
                            $request->class,
                            $session->id,
                            $studentDetail->srno
                        );
                    }),
                    'attendance' => $this->firstSecondAttendance($session->id, $studentDetail->srno)
                ];
            });

            return response()->json([
                'status' => 'success',
                'session' => $session,
                'data' => $finalData,
                'logo' => [
                    'school_logo' => config('myconfig.mylogo'),
                    'principal_sign' => config('myconfig.mysignature'),
                    'result_date_message' => $request->dateMessage ?? '',
                    'session_start_message' => $request->sessionMessage ?? ''
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => "Failed to export report: " . $e->getMessage() . " at line number " . $e->getLine()
            ], 500);
        }
    }



    /*
     *  marksheet Select Option For Class 6th To 8th
    */
    public function selectExamWithOrWithoutSixEighth(Request $request)
    {
        // $data = ExamMaster::where('active', 1)->orderBy('order', 'ASC')->get();
        $data = ExamMasterController::getAllExam(['id','exam']);
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
            'dateMessage' => $dateMessage
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
                'exists:class_masters,id,active,1'
            ],
            'section' => [
                'required',
                'exists:section_masters,id,active,1'
            ],
            'std_id' => 'required',
        ]);
        $classId = $request->class;
        $sectionId = $request->section;
        $students = $request->std_id;
        $sessionMessage = $request->sessionMessage;
        $dateMessage = $request->dateMessage;

        return redirect()->route('marks.marks-report.select.exam.six.eighth')
            ->with('class', $classId)
            ->with('section', $sectionId)
            ->with('students', $students)
            ->with('sessionMessage', $sessionMessage)
            ->with('dateMessage', $dateMessage);
    }
    public function selectExamWithOrWithoutSixEighthStore(Request $request)
    {
        $request->validate([
            'class' => [
                'required',
                'exists:class_masters,id,active,1'
            ],
            'section' => [
                'required',
                'exists:section_masters,id,active,1'
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



    private function processSubjectMarks($subject, $exams, $class, $sessionId, $studentSrno)
    {
        $allExamsTotal = 0;
        $subjectMarksData = [
            'id' => $subject->id,
            'subject' => $subject->subject,
            'by_m_g' => $subject->by_m_g,
            'priority' => $subject->priority,
            'subSubjectId' => $subject->subject_id,

            'exam-info' => collect($exams)->map(function ($exam, $key) use (&$allExamsTotal, $class, $sessionId, $studentSrno, $subject) {
                $examMarks = $this->getExamMarksForStudent($class, $key, $sessionId, $studentSrno, $subject->id);
                $allExamsTotal += $examMarks ? $examMarks['total_marks'] : 0;
                return $examMarks ? [
                    'exam_id' => $key,
                    'exam' => $exam,
                    'written_marks' => $examMarks['written_marks'],
                    'written_max_marks' => $examMarks['written_max_marks'],
                    'oral_marks' => $examMarks['oral_marks'],
                    'oral_max_marks' => $examMarks['oral_max_marks'],
                    'total_marks' => $examMarks['total_marks'],
                    'max_marks' => $examMarks['max_marks'],
                    'grade' => $examMarks['grade']
                ] : null;
            })->filter()->values(),
            'allExamsTotal' => $allExamsTotal,
        ];

        return $subjectMarksData;
    }

    public function finalMarksheetSixtoEighthReport(Request $request)
    {
        try {
            // Validate the request
            $validator = Validator::make($request->all(), [
                'class' => 'required|exists:class_masters,id,active,1',
                'section' => 'required|exists:section_masters,id,active,1',
                'students' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $validator->errors()
                ], 400);
            }

            // Get active session
            // $session = SessionMaster::select('id', 'session')->where('marks_current_session', 1)->where('active', 1)->first();
            $session = session('marks_current_session');

            if (blank($session)) {
                return response()->json(['status' => 'error', 'message' => 'Current session not set'], 400);
            }

            // Fetch main subjects and exams

            $subFields = ['id', 'subject', 'by_m_g', 'priority', 'subject_id', 'class_id'];
            $subWhere = ['class_id' => $request->class];
            $subWhereIn = ['priority' => [1]];
            $mainSubjects = SubjectMasterController::getAllSubjects($subFields, '', $subWhere, ['by_m_g' => 'asc'], true, '', false, $subWhereIn);


            if ($mainSubjects->isEmpty()) {
                return response()->json(['status' => 'error', 'message' => 'No subjects found'], 404);
            }

            $examIds = explode(",", $request->exam);
            $exams = ExamMasterController::getAllExam(['id', 'exam'],[],[],'', false, ['id' => $examIds]);
            if (empty($exams)) {
                return response()->json(['status' => 'error','message' => 'No exams found'], 404);
            }

            // Fetch students with necessary details using joins
            $studentIds = explode(",", $request->students);
            $fields = [
                'stu_main_srno.srno',
                'stu_main_srno.rollno',
                'stu_main_srno.school',
                'stu_main_srno.class',
                'stu_main_srno.section',
                'stu_detail.dob',
                'stu_detail.name',
                'parents_detail.f_name',
                'class_masters.class as class_name',
                'section_masters.section as section_name'
            ];
            $students = StudentMasterController::getStdWithNames(false, $fields)
                         ->whereIn('stu_main_srno.srno', $studentIds) // Using whereIn for multiple srnos
                         ->where('stu_main_srno.session_id', $session->id)
                         ->where('stu_main_srno.class', $request->class)
                         ->where('stu_main_srno.section', $request->section)->get();

            // Process students
            $finalData = $students->map(function ($studentDetail) use ($mainSubjects, $exams, $session, $request) {
                return [
                    'student_info' => $studentDetail,

                    'exams' => $mainSubjects->map(function ($subject) use ($exams, $request, $session, $studentDetail) {
                        return $this->processSubjectMarks(
                            $subject,
                            $exams,
                            $request->class,
                            $session->id,
                            $studentDetail->srno
                        );
                    }),
                    'attendance' => $this->firstSecondAttendance($session->id, $studentDetail->srno)
                ];
            });

            return response()->json([
                'status' => 'success',
                'session' => $session,
                'data' => $finalData,
                'logo' => [
                    'school_logo' => config('myconfig.mylogo'),
                    'principal_sign' => config('myconfig.mysignature'),
                    'result_date_message' => $request->dateMessage ?? '',
                    'session_start_message' => $request->sessionMessage ?? ''
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => "Failed to export report: " . $e->getMessage() . " at line number " . $e->getLine()
            ], 500);
        }
    }
}
