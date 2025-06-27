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
    //
    public function index()
    {
        $classes = ClassMasterController::getClasses();
        return view('student.attendance.index', compact('classes'));
    }

    public function store(Request $request)
    {
        // dd($request->all());
        try {
            //code...

            $data = $request->validate([
                'hidden_a_date' => 'required|date_format:Y-m-d',
                'students' => 'required|array',
                'students.*.srno' => 'required|exists:stu_main_srno,srno',
                'students.*.status' => 'in:1,0',
            ]);

            $updatedCount = 0;

            foreach ($data['students'] as $std) {
                $student = Attendance::updateOrCreate(
                    [
                        'srno' => $std['srno'],
                        'class' => $request->hidden_class,
                        'section' => $request->hidden_section,
                        'a_date' => $request->hidden_a_date,
                        'session_id' => $request->current_session,
                    ],
                    [
                        'session_id' => $request->current_session,
                        'class' => $request->hidden_class,
                        'section' => $request->hidden_section,
                        'srno' => $std['srno'],
                        'a_date' => $request->hidden_a_date,
                        'status' => isset($std['status']) ? $std['status'] : 0,
                        'add_user_id' => Session::get('login_user'),
                        'edit_user_id' => Session::get('login_user'),
                    ]
                );

                if ($student) {
                    $updatedCount++;
                }
            }

            if ($updatedCount > 0) {
                return response()->json([
                    'status' => 'success',
                    'message' => "Attendance updated successfully for $updatedCount students."
                ], 200);
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => "Something went wrong, please try again."
                ], 400);
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => "Failed to update student attendance" . $e->getMessage()
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
    public function getReport(Request $request)
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
                'whereIn' => ['stu_main_srno.ssid' => [1, 2, 4, 5]],
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
                'message' => "Failed to get student attendance report " . $e->getMessage()
            ], 500);
        }
    }


    // student attendance report csv file format
    public function downloadCsv(Request $request)
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
                'message' => "Failed to download CSV: " . $e->getMessage()
            ], 500);
        }
    }

    // cumulative-attendance

    public function cumulativeAttendReport()
    {
        $classes = ClassMasterController::getClasses();
        return view('student.cumulative_attendance.index', compact('classes'));
    }



    public function cumulativeReportData(Request $request)
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
                'whereIn' => ['stu_main_srno.ssid' => [1, 2, 4, 5]],
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
            // More informative error logging

            return response()->json([
                'status' => 'error',
                'message' => "Failed to get cumulative attendance report: " . $e->getMessage()
            ], 500);
        }
    }

    //cumulative Report Excel File
    public function cumulativeAttendExcel(Request $request)
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
                'message' => "Failed to download cumulative attendance CSV: " . $e->getMessage()
            ], 500);
        }
    }


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
}
