<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Admin\ClassMasterController;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Student\StudentMasterController;
use App\Models\Student\Attendance;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StdAttendanceController extends Controller
{
    public function index()
    {
        $classes = ClassMasterController::getClasses();
        return view('student.attendance.index', compact('classes'));
    }

    public function store(Request $request)
    {
        try {
                $data = $request->validate([
                    'a_date' => 'required|date_format:Y-m-d',
                    'students' => 'required|array',
                    'students.*.srno' => 'required|exists:stu_main_srno,srno',
                    'students.*.status' => 'in:1,0',
                ]);
                $currentSessionId = session('std_current_session')->id;
                // First check if attendance already exists for this date/class/section/session
                $exists = Attendance::where([
                    'class'      => $request->class,
                    'section'    => $request->section,
                    'a_date'     => $request->a_date,
                    'session_id' => $currentSessionId,
                ])->exists();

                if ($exists) {
                    return response()->json([
                        'status'  => 'error',
                        'message' => "Attendance has already been taken for this date."
                    ], 400);
                }

                // If not exists → Insert fresh attendance
                $insertedCount = 0;

                foreach ($data['students'] as $std) {
                    Attendance::create([
                        'session_id'   => $currentSessionId,
                        'class'        => $request->class,
                        'section'      => $request->section,
                        'srno'         => $std['srno'],
                        'a_date'       => $request->a_date,
                        'status'       => isset($std['status']) ? $std['status'] : 0,
                        'add_user_id'  => Session::get('login_user'),
                        'edit_user_id' => Session::get('login_user'),
                    ]);
                    $insertedCount++;
                }

                return response()->json([
                    'status'  => 'success',
                    'message' => "Attendance recorded successfully for $insertedCount students."
                ], 200);

            } catch (\Exception $e) {
                return response()->json([
                    'status'  => 'error',
                    'message' => "Failed to update student attendance"
                ], 500);
            }
    }

    // Student Attendance report view
    public function report()
    {
        $classes = ClassMasterController::getClasses();
        return view('student.attendance.report', compact('classes'));
    }


    // student attendance report
    /* public function getReport(Request $request)
    {
        try {
            // Validate request
            $validator = Validator::make($request->all(), [
                'class' => 'required|exists:class_masters,id,active,1',
                'section' => 'required|exists:section_masters,id,active,1',
                'start_date' => 'required|date_format:Y-m-d',
                'end_date' => 'required|date_format:Y-m-d',
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $validator->errors()
                ], 400);
            }

            // Explode student IDs and prepare dates
            $std = explode(",", $request->std_id);
            $dates = [$request->start_date, $request->end_date];

            // Base query to fetch attendance data
            $fields = ['id', 'class', 'section', 'session_id', 'srno', 'a_date', 'status'];
            $where = [
                'where' => ['class' => $request->class, 'section' => $request->section, 'session_id' => $request->current_session],
                'whereIn' => ['srno' => $std],
                'whereBetween' => ['a_date' => $dates],
            ];
            $orderBy = ['a_date' => 'asc'];
            $baseQuery = self::getAttendance($fields, $where, $orderBy);


            // If no attendance data is found
            if ($baseQuery->count() === 0) {
                return response()->json([
                    'status' => 'success',
                    'message' => "No Record Found",
                ], 202);
            }

            // Get attendance data
            $data = $baseQuery->get(['srno', 'status', 'a_date']);
            $stFields = ['stu_main_srno.srno', 'stu_detail.name', 'stu_main_srno.rollno'];
            $stWhere = [
                'whereIn' => ['stu_main_srno.srno' => $data->pluck('srno')->toArray(), 'stu_detail.srno' => $data->pluck('srno')->toArray()],
                'where' => ['stu_main_srno.session_id' => $request->current_session, 'stu_main_srno.class' => $request->class, 'stu_main_srno.section' => $request->section, 'stu_main_srno.active' => 1],
                // 'whereIn' => ['stu_main_srno.ssid' => [1, 2, 4, 5]],
                'whereIn' => ['stu_main_srno.ssid' => [1, 2]],
            ];
            $stOrderBy = ['stu_main_srno.rollno' => 'asc'];
            $students = StudentMasterController::getStd($stFields, $stWhere, $stOrderBy)->get();
            // Map student names and roll numbers to the attendance data
            $formattedData = $data->map(function ($item) use ($students) {
                // Get student
                $student = $students->firstWhere('srno', $item->srno);
                // Add student name and roll number to attendance data
                $item->name = $student ? $student->name : 'Unknown';
                $item->rollno = $student ? $student->rollno : 'N/A';

                // Format attendance date
                $item->a_date = Carbon::parse($item->a_date)->format('d M, Y');
                return $item;
            });

            // Calculate present and absent counts
            $absent = $baseQuery->clone()->where('status', 0)->count();
            $present = $baseQuery->clone()->where('status', 1)->count();

            // Return response
            return response()->json([
                'status' => 'success',
                'message' => "Student Attendance Report",
                'present' => $present,
                'absent' => $absent,
                'data' => $formattedData,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => "Failed to get student attendance report "
            ], 500);
        }
    } */

    // student attendance report csv file format
    /*public function downloadCsv(Request $request)
    {
        try {
            // Reuse getReport logic to fetch and format attendance data
            $reportResponse = $this->getReport($request);

            // Decode the JSON response from getReport
            $report = json_decode($reportResponse->getContent(), true);

            if ($report['status'] === 'error') {
                return response()->json($report, 400);
            }

            if (empty($report['data'])) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'No attendance data found to export.'
                ], 202);
            }

            // Prepare CSV data
            $csvData = [];
            $csvData[] = ['Roll No.', 'Name', 'Date', 'Status']; // Header row

            foreach ($report['data'] as $item) {
                $csvData[] = [
                    $item['rollno'],
                    $item['name'],
                    $item['a_date'],
                    $item['status'] == 1 ? 'Present' : 'Absent',
                ];
            }

            // Add summary row
            $csvData[] = [];
            $csvData[] = ['Summary'];
            $csvData[] = ['Present', 'Absent'];
            $csvData[] = [$report['present'], $report['absent']];

            // Create a CSV response using StreamedResponse
            $response = new StreamedResponse(function () use ($csvData) {
                $handle = fopen('php://output', 'w');
                foreach ($csvData as $row) {
                    fputcsv($handle, $row);
                }
                fclose($handle);
            });

            // Set headers for the CSV file
            $response->headers->set('Content-Type', 'text/csv');
            $response->headers->set('Content-Disposition', 'attachment; filename="attendance_report.csv"');

            return $response;
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => "Failed to download CSV: "
            ], 500);
        }
    } */

    // cumulative-attendance

    public function cumulativeAttendReport()
    {
        $classes = ClassMasterController::getClasses();
        return view('student.cumulative_attendance.index', compact('classes'));
    }

    /* public function cumulativeReportData(Request $request)
    {
        try {
            // Enhanced validation
            $validator = Validator::make($request->all(), [
                'class' => 'required|exists:class_masters,id,active,1',
                'section' => 'required|exists:section_masters,id,active,1',
                'session' => 'required|exists:session_masters,id,active,1',
                'std_id' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $validator->errors()
                ], 400);
            }

            // More robust month handling
            $monthNames = ['Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec', 'Jan', 'Feb', 'Mar'];
            $results = [];
            $std = explode(",", $request->std_id);
            $cumulativePresentAttendance = array_fill_keys($std, 0);

            $stFields = ['stu_main_srno.srno', 'stu_detail.name', 'rollno'];
            $stWhere = [
                'whereIn' => ['stu_main_srno.srno' => $std, 'stu_detail.srno' => $std],
                'where' => ['stu_main_srno.session_id' => $request->session, 'stu_main_srno.class' => $request->class, 'stu_main_srno.section' => $request->section, 'stu_main_srno.active' => 1],
                'whereIn' => ['stu_main_srno.ssid' => [1, 2]],
            ];
            $stOrderBy = ['stu_main_srno.rollno' => 'asc'];

            $students = StudentMasterController::getStd($stFields, $stWhere, $stOrderBy)->get()->keyBy('srno');
            // Process attendance for each month
            foreach ($monthNames as $index => $monthName) {
                // Correct month number calculation
                $monthNumber = $index < 9 ? $index + 4 : $index - 8;

                $fields = ['id', 'class', 'section', 'session_id', 'srno', 'a_date', 'status'];
                $where = [
                    'where' => ['class' => $request->class, 'section' => $request->section, 'session_id' => $request->session],
                    'whereIn' => ['srno' => $std],
                    'whereMonth' => ['a_date' => $monthNumber],
                ];
                $attendanceData = self::getAttendance($fields, $where)->get()->groupBy('srno');

                // Calculate attendance for each student
                foreach ($std as $id) {
                    $monthAttendance = $attendanceData->get($id, collect());

                    $presentCount = $monthAttendance->where('status', 1)->count();
                    $absentCount = $monthAttendance->where('status', 0)->count();

                    $cumulativePresentAttendance[$id] += $presentCount;

                    $results[$id][$monthName] = [
                        'P' => $presentCount,
                        'A' => $absentCount,
                        'C' => $cumulativePresentAttendance[$id],
                    ];
                }
            }

            // Prepare the final data structure
            $mergeArray = [];
            foreach ($students as $student) {
                $mergeArray[$student->srno] = [
                    'Name' => $student->name,
                    'Rollno' => $student->rollno,
                    'Attendance' => $results[$student->srno] ?? []
                ];
            }
            return response()->json([
                'status' => 'success',
                'message' => 'Attendance',
                'data' => $mergeArray
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => "Failed to get cumulative attendance report: "
            ], 500);
        }
    } */

    //cumulative Report Excel File
   /*  public function cumulativeAttendExcel(Request $request)
    {
        try {
            // Fetch data using cumulativeReportData
            $response = $this->cumulativeReportData($request);

            // Decode the JSON response to access the data
            $responseData = json_decode($response->getContent(), true);

            // Check if the response was successful
            if ($responseData['status'] !== 'success') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to generate report: ' . ($responseData['message'] ?? 'Unknown error')
                ], 400);
            }

            // Extract the data
            $data = $responseData['data'];
            $monthNames = ['Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec', 'Jan', 'Feb', 'Mar'];

            // Prepare the CSV data
            $csvData = [];
            $header = ['Roll Number', 'Name'];

            // Add headers for months
            foreach ($monthNames as $month) {
                $header[] = $month . ' (P)';   // Present
                $header[] = $month . ' (A)';   // Absent
                $header[] = $month . ' (C)';   // Cumulative
            }

            $csvData[] = $header;

            // Populate rows with student attendance data
            foreach ($data as $student) {
                if (!empty($student['Attendance'])) {
                    # code...
                    $row = [
                        $student['Rollno'],
                        $student['Name'],
                    ];

                    foreach ($monthNames as $month) {
                        $attendance = $student['Attendance'][$month] ?? [];
                        $row[] = $attendance['P'] ?? 0;
                        $row[] = $attendance['A'] ?? 0;
                        $row[] = $attendance['C'] ?? 0;
                    }

                    $csvData[] = $row;
                }
            }

            // Stream the CSV response
            $response = new StreamedResponse(function () use ($csvData) {
                $handle = fopen('php://output', 'w');
                foreach ($csvData as $row) {
                    fputcsv($handle, $row);
                }
                fclose($handle);
            });

            // Set headers for CSV download
            $response->headers->set('Content-Type', 'text/csv');
            $response->headers->set('Content-Disposition', 'attachment; filename="cumulative_attendance_report.csv"');

            return $response;
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => "Failed to download cumulative attendance CSV: "
            ], 500);
        }
    } */


    // Get Student Attendance
    public static function getAttendance($fields = [], $where = [], $orderBy = [])
    {
        $query = Attendance::query();

        if (!empty($fields) && is_array($fields)) {

            $query->select($fields);
        } else {
            $query->select('*');
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
            foreach ($orderBy as $orderField => $orderValue) {
                $query->orderBy($orderField, $orderValue);
            }
        }
        return $query;
    }

    /* Date 13-02-2026 - T10 */

    /**
     * Cumulative Attendance Report
     *
     * Returns month-wise P / A / Cumulative attendance for one student or
     * every student in a class-section for the active academic session.
     *
     * Academic year runs April (current year) → March (next year).
     */
    public function cumulativeReportData(Request $request)
    {
        // ------------------------------------------------------------------ //
        //  1. Validate incoming request
        // ------------------------------------------------------------------ //
        $validator = Validator::make($request->all(), [
            'class'   => 'required|exists:class_masters,id,active,1',
            'section' => 'required|exists:section_masters,id,active,1',
            'std'     => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => $validator->errors(),
            ], 200);
        }

        try {
            // ---------------------------------------------------------------- //
            //  2. Resolve session year boundaries
            //     Session::get('std_current_session') returns the session ID.
            //     We look up the actual start_year from the sessions table so we
            //     can correctly split Apr-Dec (start_year) vs Jan-Mar (start_year+1).
            // ---------------------------------------------------------------- //
            $currentSessionId = session('std_current_session')->id;
            $class            = $request->class;
            $section          = $request->section;
            $stdInput         = $request->std;          // 'all'  OR  a single srno

            // Fetch the academic year start from the sessions table.
            // Adjust the column name ('start_year') to match your actual schema.
            $sessionRecord = DB::table('session_masters')->where('id', $currentSessionId)->first(['start_year', 'end_year']);

            if (empty($sessionRecord)) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Active session not found.',
                ], 200);
            }

            $startYear = (int) $sessionRecord->start_year;
            $endYear   = (int) $sessionRecord->end_year;

            // ---------------------------------------------------------------- //
            //  3. Month definitions
            //     Each entry: [ display-label, month-number, year ]
            // ---------------------------------------------------------------- //
            $months = [
                ['label' => 'Apr', 'month' => 4,  'year' => $startYear],
                ['label' => 'May', 'month' => 5,  'year' => $startYear],
                ['label' => 'Jun', 'month' => 6,  'year' => $startYear],
                ['label' => 'Jul', 'month' => 7,  'year' => $startYear],
                ['label' => 'Aug', 'month' => 8,  'year' => $startYear],
                ['label' => 'Sep', 'month' => 9,  'year' => $startYear],
                ['label' => 'Oct', 'month' => 10, 'year' => $startYear],
                ['label' => 'Nov', 'month' => 11, 'year' => $startYear],
                ['label' => 'Dec', 'month' => 12, 'year' => $startYear],
                ['label' => 'Jan', 'month' => 1,  'year' => $endYear],
                ['label' => 'Feb', 'month' => 2,  'year' => $endYear],
                ['label' => 'Mar', 'month' => 3,  'year' => $endYear],
            ];

            // ---------------------------------------------------------------- //
            //  4. Fetch students
            // ---------------------------------------------------------------- //
            $studentQuery = DB::table('stu_main_srno')
                ->leftJoin('stu_detail', 'stu_main_srno.srno', '=', 'stu_detail.srno')
                ->select([
                    'stu_main_srno.srno',
                    'stu_main_srno.rollno',
                    'stu_detail.name as student_name',   // aliased correctly
                ])
                ->where('stu_main_srno.class', $class)
                ->where('stu_main_srno.section', $section)
                ->where('stu_main_srno.session_id', $currentSessionId)
                ->where('stu_main_srno.ssid', 1)
                ->where('stu_main_srno.active', 1)
                ->orderBy('stu_main_srno.rollno', 'asc');

            if ($stdInput !== 'all') {
                $studentQuery->where('stu_main_srno.srno', $stdInput);
            }

            // Key by srno for O(1) look-ups later
            $students = $studentQuery->get()->keyBy('srno');

            if ($students->isEmpty()) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'No students found for the given criteria.',
                ], 200);
            }

            $studentIds = $students->keys()->all();   // array of srno values

            // ---------------------------------------------------------------- //
            //  5. Initialise running totals
            // ---------------------------------------------------------------- //
            $cumulativePresent = array_fill_keys($studentIds, 0);
            $results           = [];

            // ---------------------------------------------------------------- //
            //  6. Process each month
            // ---------------------------------------------------------------- //
            foreach ($months as $monthDef) {
                $label = $monthDef['label'];
                $month = $monthDef['month'];
                $year  = $monthDef['year'];

                // Build the attendance query for this specific month + year.
                // We keep class / section / session_id filters AND optionally
                // filter by a single student — without ever dropping any condition.
                $attendanceQuery = DB::table('attendance')   // ← use your actual table name
                    ->select(['srno', 'a_date', 'status'])
                    ->where('class',      $class)
                    ->where('section',    $section)
                    ->where('session_id', $currentSessionId)
                    ->whereMonth('a_date', $month)
                    ->whereYear('a_date',  $year)
                    ->whereIn('srno', $studentIds);   // always scoped to our student list

                // Extra filter when a single student is requested
                if ($stdInput !== 'all') {
                    $attendanceQuery->where('srno', $stdInput);
                }

                // Group records by srno for easy per-student aggregation
                $attendanceByStudent = $attendanceQuery
                    ->get()
                    ->groupBy('srno');

                // Aggregate for every student we care about
                foreach ($studentIds as $srno) {
                    $records = $attendanceByStudent->get($srno, collect());

                    $presentCount = $records->where('status', 1)->count();
                    $absentCount  = $records->where('status', 0)->count();

                    $cumulativePresent[$srno] += $presentCount;

                    $results[$srno][$label] = [
                        'P'   => $presentCount,
                        'A'   => $absentCount,
                        'Cum' => $cumulativePresent[$srno],
                    ];
                }
            }

            // ---------------------------------------------------------------- //
            //  7. Build the final response payload
            // ---------------------------------------------------------------- //
            $data = [];
            foreach ($students as $srno => $student) {
                $data[$srno] = [
                    'Name'       => $student->student_name,   // matches the alias above
                    'Rollno'     => $student->rollno,
                    'Attendance' => $results[$srno] ?? [],
                ];
            }

            return response()->json([
                'status'  => 'success',
                'message' => 'Cumulative attendance report',
                'data'    => $data,
            ], 200);

        } catch (\Exception $e) {

            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to generate cumulative attendance report.',
            ], 200);
        }
    }

    /**
     * Download the cumulative attendance report as a CSV file.
     *
     * Reuses the same data-building logic as cumulativeReportData() but
     * streams the result directly — no internal HTTP round-trip needed.
     */
    public function cumulativeAttendExcel(Request $request)
    {
        // ------------------------------------------------------------------ //
        //  1. Validate (same rules as cumulativeReportData)
        // ------------------------------------------------------------------ //
        $validator = Validator::make($request->all(), [
            'class'   => 'required|exists:class_masters,id,active,1',
            'section' => 'required|exists:section_masters,id,active,1',
            'std'     => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => $validator->errors(),
            ], 200);
        }

        try {
            // ---------------------------------------------------------------- //
            //  2. Resolve session
            // ---------------------------------------------------------------- //
            $currentSessionId = session('std_current_session')->id;
            $class            = $request->class;
            $section          = $request->section;
            $stdInput         = $request->std;

            $sessionRecord = DB::table('session_masters')
                ->where('id', $currentSessionId)
                ->first(['start_year', 'end_year']);

            if (empty($sessionRecord)) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Active session not found.',
                ], 200);
            }

            $startYear = (int) $sessionRecord->start_year;
            $endYear   = (int) $sessionRecord->end_year;

            // ---------------------------------------------------------------- //
            //  3. Month definitions (Apr–Mar academic year)
            // ---------------------------------------------------------------- //
            $months = [
                ['label' => 'Apr', 'month' => 4,  'year' => $startYear],
                ['label' => 'May', 'month' => 5,  'year' => $startYear],
                ['label' => 'Jun', 'month' => 6,  'year' => $startYear],
                ['label' => 'Jul', 'month' => 7,  'year' => $startYear],
                ['label' => 'Aug', 'month' => 8,  'year' => $startYear],
                ['label' => 'Sep', 'month' => 9,  'year' => $startYear],
                ['label' => 'Oct', 'month' => 10, 'year' => $startYear],
                ['label' => 'Nov', 'month' => 11, 'year' => $startYear],
                ['label' => 'Dec', 'month' => 12, 'year' => $startYear],
                ['label' => 'Jan', 'month' => 1,  'year' => $endYear],
                ['label' => 'Feb', 'month' => 2,  'year' => $endYear],
                ['label' => 'Mar', 'month' => 3,  'year' => $endYear],
            ];

            $monthLabels = array_column($months, 'label');

            // ---------------------------------------------------------------- //
            //  4. Fetch students
            // ---------------------------------------------------------------- //
            $studentQuery = DB::table('stu_main_srno')
                ->leftJoin('stu_detail', 'stu_main_srno.srno', '=', 'stu_detail.srno')
                ->select([
                    'stu_main_srno.srno',
                    'stu_main_srno.rollno',
                    'stu_detail.name as student_name',
                ])
                ->where('stu_main_srno.class',      $class)
                ->where('stu_main_srno.section',    $section)
                ->where('stu_main_srno.session_id', $currentSessionId)
                ->where('stu_main_srno.ssid',       1)
                ->where('stu_main_srno.active',     1)
                ->orderBy('stu_main_srno.rollno', 'asc');

            if ($stdInput !== 'all') {
                $studentQuery->where('stu_main_srno.srno', $stdInput);
            }

            $students = $studentQuery->get()->keyBy('srno');

            if ($students->isEmpty()) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'No students found for the given criteria.',
                ], 200);
            }

            $studentIds = $students->keys()->all();

            // ---------------------------------------------------------------- //
            //  5. Build attendance results (same logic as cumulativeReportData)
            // ---------------------------------------------------------------- //
            $cumulativePresent = array_fill_keys($studentIds, 0);
            $results           = [];

            foreach ($months as $monthDef) {
                $label = $monthDef['label'];
                $month = $monthDef['month'];
                $year  = $monthDef['year'];

                $attendanceQuery = DB::table('attendance')
                    ->select(['srno', 'a_date', 'status'])
                    ->where('class',      $class)
                    ->where('section',    $section)
                    ->where('session_id', $currentSessionId)
                    ->whereMonth('a_date', $month)
                    ->whereYear('a_date',  $year)
                    ->whereIn('srno', $studentIds);

                if ($stdInput !== 'all') {
                    $attendanceQuery->where('srno', $stdInput);
                }

                $attendanceByStudent = $attendanceQuery->get()->groupBy('srno');

                foreach ($studentIds as $srno) {
                    $records = $attendanceByStudent->get($srno, collect());

                    $presentCount = $records->where('status', 1)->count();
                    $absentCount  = $records->where('status', 0)->count();

                    $cumulativePresent[$srno] += $presentCount;

                    $results[$srno][$label] = [
                        'P'   => $presentCount,
                        'A'   => $absentCount,
                        'Cum' => $cumulativePresent[$srno],
                    ];
                }
            }

            // ---------------------------------------------------------------- //
            //  6. Build CSV rows
            // ---------------------------------------------------------------- //

            // Header row: Roll Number | Name | Apr (P) | Apr (A) | Apr (Cum) | May ...
            $headerRow = ['Roll Number', 'Name'];
            foreach ($monthLabels as $label) {
                $headerRow[] = "{$label} (P)";
                $headerRow[] = "{$label} (A)";
                $headerRow[] = "{$label} (Cum)";
            }

            // Data rows
            $dataRows = [];
            foreach ($students as $srno => $student) {
                $attendance = $results[$srno] ?? [];

                // Skip students with no attendance at all
                if (empty($attendance)) {
                    continue;
                }

                $row = [
                    $student->rollno,
                    $student->student_name,
                ];

                foreach ($monthLabels as $label) {
                    $month = $attendance[$label] ?? [];
                    $row[] = $month['P']   ?? 0;
                    $row[] = $month['A']   ?? 0;
                    $row[] = $month['Cum'] ?? 0;
                }

                $dataRows[] = $row;
            }

            if (empty($dataRows)) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'No attendance data available to export.',
                ], 200);
            }

            // ---------------------------------------------------------------- //
            //  7. Stream the CSV response
            // ---------------------------------------------------------------- //
            $filename = 'cumulative_attendance_' . $startYear . '-' . $endYear . '.csv';

            $response = new StreamedResponse(function () use ($headerRow, $dataRows) {
                $handle = fopen('php://output', 'w');

                // UTF-8 BOM so Excel opens the file with correct encoding
                fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

                fputcsv($handle, $headerRow);

                foreach ($dataRows as $row) {
                    fputcsv($handle, $row);
                }

                fclose($handle);
            });

            $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
            $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');
            $response->headers->set('Pragma', 'no-cache');
            $response->headers->set('Expires', '0');

            return $response;

        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to download cumulative attendance report.',
            ], 200);
        }
    }

    /* Student attendance report */
    public function getReport(Request $request)
    {
        try {
            // ------------------------------------------------------------------ //
            //  1. Validate incoming request
            // ------------------------------------------------------------------ //
            $validator = Validator::make($request->all(), [
                'class'      => 'required|exists:class_masters,id,active,1',
                'section'    => 'required|exists:section_masters,id,active,1',
                'std'        => 'required',
                'start_date' => 'required|date_format:Y-m-d',
                'end_date'   => 'required|date_format:Y-m-d|after_or_equal:start_date',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status'  => 'error',
                    'message' => $validator->errors(),
                ], 200);
            }

            $currentSessionId = session('std_current_session')->id;
            $class            = $request->class;
            $section          = $request->section;
            $stdInput         = $request->std;
            $startDate        = $request->start_date;
            $endDate          = $request->end_date;

            // ------------------------------------------------------------------ //
            //  2. Fetch students
            // ------------------------------------------------------------------ //
            $studentQuery = DB::table('stu_main_srno')
                ->leftJoin('stu_detail', 'stu_main_srno.srno', '=', 'stu_detail.srno')
                ->select([
                    'stu_main_srno.srno',
                    'stu_main_srno.rollno',
                    'stu_detail.name as student_name',
                ])
                ->where('stu_main_srno.class',      $class)
                ->where('stu_main_srno.section',    $section)
                ->where('stu_main_srno.session_id', $currentSessionId)
                ->where('stu_main_srno.ssid',   1)
                ->where('stu_main_srno.active', 1)
                ->orderBy('stu_main_srno.rollno', 'asc');

            if ($stdInput !== 'all') {
                $studentQuery->where('stu_main_srno.srno', $stdInput);
            }
            $students = $studentQuery->get()->keyBy('srno');
            if ($students->isEmpty()) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'No students found.',
                ], 200);
            }
            // ------------------------------------------------------------------ //
            //  3. Fetch attendance in ONE query for all students in date range
            // ------------------------------------------------------------------ //
            $attendanceQuery = DB::table('attendance')->select(['srno', 'a_date', 'status'])->where('class', $class)->where('section', $section)->where('session_id', $currentSessionId)->whereBetween('a_date', [$startDate, $endDate])->orderBy('a_date', 'asc');
            if ($stdInput !== 'all') {
                $attendanceQuery->where('srno', $stdInput);
            } else {
                $attendanceQuery->whereIn('srno', $students->keys()->toArray());
            }
            $attendance = $attendanceQuery->get();
            if ($attendance->isEmpty()) {
                return response()->json([
                    'status'  => 'success',
                    'message' => 'No attendance records found.',
                ], 200);
            }
            // ------------------------------------------------------------------ //
            //  4. Group attendance by srno, calculate per-student summary
            //     and format records — all in PHP, zero extra queries
            // ------------------------------------------------------------------ //
            $totalPresent = 0;
            $totalAbsent  = 0;

            // Format attendance records and attach student info
            $formattedData = $attendance->map(function ($record) use ($students, &$totalPresent, &$totalAbsent) {
                $student = $students->get($record->srno);
                /* Tally totals */
                if ((int) $record->status === 1) {
                    $totalPresent++;
                } else {
                    $totalAbsent++;
                }

                return [
                    'srno'         => $record->srno,
                    'rollno'       => $student->rollno       ?? 'N/A',
                    'student_name' => $student->student_name ?? 'Unknown',
                    'a_date'       => Carbon::parse($record->a_date)->format('d M, Y'),
                    'status'       => (int) $record->status === 1 ? 'P' : 'A',
                ];
            });
            // ------------------------------------------------------------------ //
            //  5. Return response
            // ------------------------------------------------------------------ //
            return response()->json([
                'status'  => 'success',
                'message' => 'Student Attendance Report',
                'summary' => [
                    'present' => $totalPresent,
                    'absent'  => $totalAbsent,
                    'total'   => $totalPresent + $totalAbsent,
                ],
                'data' => $formattedData,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Something went wrong, please try again.',
            ], 200);
        }
    }

    /* Student attendance report csv file format */
    public function downloadCsv(Request $request)
    {
        try {
            // ------------------------------------------------------------------ //
            //  1. Reuse getReport to fetch and format attendance data
            // ------------------------------------------------------------------ //
            $reportResponse = $this->getReport($request);
            $report         = json_decode($reportResponse->getContent(), true);

            if ($report['status'] === 'error') {
                return response()->json([
                    'status'  => 'error',
                    'message' => $report['message'] ?? 'Failed to generate report.',
                ], 200);
            }

            if (empty($report['data'])) {
                return response()->json([
                    'status'  => 'success',
                    'message' => 'No attendance data found to export.',
                ], 200);
            }

            // ------------------------------------------------------------------ //
            //  2. Build CSV rows
            // ------------------------------------------------------------------ //
            $csvData   = [];

            // Header row
            $csvData[] = ['Roll No.', 'Name', 'Date', 'Status'];

            // Data rows — field names match getReport response exactly
            foreach ($report['data'] as $item) {
                $csvData[] = [
                    $item['rollno']       ?? 'N/A',
                    $item['student_name'] ?? 'Unknown',   // ← was $item['name']
                    $item['a_date']       ?? '',
                    $item['status']       ?? '',           // ← already 'P' or 'A', no conversion needed
                ];
            }

            // Summary rows
            $csvData[] = [];
            $csvData[] = ['Summary'];
            $csvData[] = ['Present', 'Absent', 'Total'];
            $csvData[] = [
                $report['summary']['present'] ?? 0,   // ← was $report['present']
                $report['summary']['absent']  ?? 0,   // ← was $report['absent']
                $report['summary']['total']   ?? 0,
            ];

            // ------------------------------------------------------------------ //
            //  3. Stream CSV response
            // ------------------------------------------------------------------ //
            $fileName = 'attendance_report_' . date('Y-m-d') . '.csv';

            $response = new StreamedResponse(function () use ($csvData) {
                $handle = fopen('php://output', 'w');

                if ($handle === false) {
                    throw new \Exception('Failed to open output stream.');
                }
                foreach ($csvData as $row) {
                    fputcsv($handle, $row);
                }
                fclose($handle);
            });
            $response->headers->set('Content-Type', 'text/csv');
            $response->headers->set('Content-Disposition', 'attachment; filename="' . $fileName . '"');
            return $response;
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Something went wrong, please try again.',
            ], 200);
        }
    }
}
