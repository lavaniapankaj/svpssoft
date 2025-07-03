<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Student\StudentMasterController;
use App\Models\Admin\ClassMaster;
use App\Models\Admin\SectionMaster;
use App\Models\Admin\SessionMaster;
use App\Models\Fee\FeeDetail;
use App\Models\Student\Attendance;
use App\Models\Student\StudentMaster;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    //
    public function index()
    {
        //
        return view('admin.reports.index');
    }

    public function newAdmissionReport()
    {
        //
        return view('admin.reports.new_admission.new_admission_report');
    }

    public function newAdmissionReportByDateView()
    {
        $sessions = SessionMasterController::getSessions(['id','session']);
        return view('admin.reports.new_admission.new_admission_report_by_date', compact('sessions'));
    }
    public function newAdmissionReportByCategoryView()
    {
        $sessions = SessionMasterController::getSessions(['id','session']);
        return view('admin.reports.new_admission.new_admission_report_by_category', compact('sessions'));
    }
    public function newAdmissionReportByReligionView()
    {
        $sessions = SessionMasterController::getSessions(['id','session']);
        return view('admin.reports.new_admission.new_admission_report_by_religion', compact('sessions'));
    }
    public function newAdmissionReportByAgeProofView()
    {
        $sessions = SessionMasterController::getSessions(['id','session']);
        return view('admin.reports.new_admission.new_admission_report_by_age_proof', compact('sessions'));
    }
    public function newAdmissionReportBetwwenDatesView()
    {
        $sessions = SessionMasterController::getSessions(['id','session']);
        return view('admin.reports.new_admission.new_admission_report_between_dates', compact('sessions'));
    }

    public function stdregisterView()
    {
        $sessions = SessionMasterController::getSessions(['id','session']);
        return view('admin.reports.sr_register_report', compact('sessions'));
    }


    public function reportAgeWiseView()
    {
        return view('admin.reports.age_wise_report');
    }

    public function transportWiseReportView()
    {
        return view('admin.reports.transport_details_report');
    }

    public function tcIssueView()
    {
        return view('admin.reports.tc_issue');
    }

    public function newAdmissionReportByDate(Request $request)
    {
        try {
            $request->validate([
                'session_id' => 'required|exists:session_masters,id,active,1',
                'class' => 'required|exists:class_masters,id,active,1',
            ]);



            $admissionDate = $request->by_date;
            $ageProofId = explode(',', $request->age_proof);
            // $studentsQuery = StudentMaster::query()
            $studentsQuery = StudentMaster::where('session_id', $request->session_id)
                ->whereIn('age_proof', $ageProofId)
                ->whereIn('ssid', [1, 2])
                ->where('active', 1)
                ->whereNotNull('admission_date');
            // ->whereIn('class', [2, 3]);

            // if ($admissionDate) {
            //     $studentsQuery->whereDate('admission_date', $admissionDate);
            // }

            $studentsGrouped = $studentsQuery->get()->groupBy('class');

            // Retrieve all active classes
            $classID = explode(',', $request->class);
            // $allClasses = ClassMaster::whereIn('id', $classID)->orderBy('sort', 'ASC')->where('active', 1)->get();
            $allClasses = ClassMasterController::getClasses(['id','class'], null, false, ['id' => $classID], 'whereIn', true);

            $report = $allClasses->map(function ($class) use ($studentsGrouped, $admissionDate) {
                $classId = $class->id;
                $className = $class->class;

                // Get students for this class
                $students = $studentsGrouped->get($classId, collect());

                $boys = $students->where('gender', 1)->count();
                $girls = $students->where('gender', 2)->count();
                $totalStudents = $boys + $girls;

                if ($admissionDate) {
                    $boysBefore = $students->where('gender', 1)->where('admission_date', '<=', $admissionDate)->count();
                    $girlsBefore = $students->where('gender', 2)->where('admission_date', '<=', $admissionDate)->count();
                    $totalBefore = $boysBefore + $girlsBefore;

                    $boysAfter = $students->where('gender', 1)->where('admission_date', '>', $admissionDate)->count();
                    $girlsAfter = $students->where('gender', 2)->where('admission_date', '>', $admissionDate)->count();
                    $totalAfter = $boysAfter + $girlsAfter;

                    return [
                        'class' => $className,
                        'before' => [
                            'boys' => $boysBefore,
                            'girls' => $girlsBefore,
                            'total' => $totalBefore,
                        ],
                        'after' => [
                            'boys' => $boysAfter,
                            'girls' => $girlsAfter,
                            'total' => $totalAfter,
                        ],
                    ];
                }

                return [
                    'class' => $className,
                    'boys' => $boys,
                    'girls' => $girls,
                    'total' => $totalStudents,
                ];
            });

            return response()->json([
                'status' => 'success',
                'data' => $report,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => "Failed to get new admission report: "
            ], 500);
        }
    }

    // export report
    public function exportReport(Request $request)
    {
        try {
            // Call the newAdmissionReportByDate function to get the report data
            $response = $this->newAdmissionReportByDate($request);

            // Check if the response is successful and contains data
            if ($response->getStatusCode() !== 200) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to generate report: ' . $response->getContent()
                ], 500);
            }

            // Get the report data from the response
            $reportData = json_decode($response->getContent(), true)['data'];

            // Set the file name for the exported CSV file
            $fileName = 'new_admission_report.csv';

            // Open the output stream for writing to the CSV file
            $output = fopen('php://memory', 'w');
            // $output = fopen('php://output', 'w');
            if ($output === false) {
                throw new \Exception('Failed to open output stream.');
            }

            // Set the CSV column headers
            $headers = ['Class', 'Boys', 'Girls', 'Total'];
            fputcsv($output, $headers);

            // Write the report data to the CSV file
            foreach ($reportData as $row) {
                if (isset($row['before'])) {
                    fputcsv($output, [
                        $row['class'],
                        $row['before']['boys'],
                        $row['before']['girls'],
                        $row['before']['total'],
                    ]);
                    fputcsv($output, [
                        $row['class'] . ' (After)',
                        $row['after']['boys'],
                        $row['after']['girls'],
                        $row['after']['total'],
                    ]);
                } else {
                    fputcsv($output, [
                        $row['class'],
                        $row['boys'],
                        $row['girls'],
                        $row['total'],
                    ]);
                }
            }

                rewind($output);


                $csvContent = stream_get_contents($output);


                fclose($output);


                return response($csvContent, 200)
                    ->header('Content-Type', 'text/csv')
                    ->header('Content-Disposition', 'attachment; filename="' . $fileName . '"');


        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => "Failed to export report: "
            ], 500);
        }
    }

    public function newAdmissionReportByCategory(Request $request)
    {
        try {
            $request->validate([
                'session_id' => 'required|exists:session_masters,id,active,1',

            ]);
            $allCategories = [
                1 => 'General',
                2 => 'OBC',
                3 => 'SC',
                4 => 'ST',
                5 => 'BC',
            ];

            $studentsQuery = StudentMaster::query()
                ->where('session_id', $request->session_id)
                ->whereIn('ssid', [1, 2])
                ->where('active', 1);
            // ->whereIn('class', [2, 5]);

            if ($request->new_admission) {
                $studentsQuery->whereNotNull('admission_date');
            }

            $studentsGrouped = $studentsQuery->get()->groupBy('class');

            // Retrieve all active classes
            $classID = explode(',', $request->class);
            // $allClasses = ClassMaster::whereIn('id', $classID)->where('active', 1)->orderBy('sort', 'ASC')->get();
            $allClasses = ClassMasterController::getClasses(['id','class'], null, false, ['id' => $classID], 'whereIn', true);

            $report = $allClasses->map(function ($class) use ($studentsGrouped, $allCategories) {
                $classId = $class->id;
                $className = $class->class;

                // Get students for this class or set to empty collection
                $students = $studentsGrouped->get($classId, collect());

                // Group students by category
                $categoryCounts = $students->groupBy(function ($student) {
                    return DB::table('stu_detail')->where('srno', $student->srno)->where('active', 1)->value('category_id');
                });

                $result = [
                    'class' => $className,
                    'categories' => []
                ];

                // Loop through all defined categories
                foreach ($allCategories as $categoryId => $categoryName) {
                    // Get students for this category or set to empty collection
                    $categoryStudents = $categoryCounts->get($categoryId, collect());

                    $boys = $categoryStudents->where('gender', 1)->count();
                    $girls = $categoryStudents->where('gender', 2)->count();
                    $totalStudents = $boys + $girls;

                    $result['categories'][] = [
                        'category_id' => $categoryId,
                        'category_name' => $categoryName,
                        'boys' => $boys,
                        'girls' => $girls,
                        'totalStudents' => $totalStudents,
                    ];
                }

                return $result;
            })->values();

            return response()->json([
                'status' => 'success',
                'data' => $report,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => "Failed to get new admission report: "
            ], 500);
        }
    }

    /**
     * export report by category-wise
     */
    public function exportReportByCategory(Request $request)
    {
        try {
            // Call the newAdmissionReportByCategory function to get the report data
            $response = $this->newAdmissionReportByCategory($request);

            // Check if the response is successful and contains data
            if ($response->getStatusCode() !== 200) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to generate report: ' . $response->getContent()
                ], 500);
            }

            // Get the report data from the response
            $reportData = json_decode($response->getContent(), true)['data'];

            // Set the file name for the exported CSV file
            $fileName = 'report_by_category.csv';

            // Open the output stream for writing to the CSV file
            $output = fopen('php://memory', 'w');
            // Open the output stream for writing to the CSV file
            // $output = fopen('php://output', 'w');
            if ($output === false) {
                throw new \Exception('Failed to open output stream.');
            }

            // Set the CSV column headers
            $headers = ['Class', 'Gender', 'General', 'OBC', 'SC', 'ST', 'BC'];
            fputcsv($output, $headers);

            // Write the report data to the CSV file
            foreach ($reportData as $row) {
                $boysData = [
                    'General' => 0,
                    'OBC' => 0,
                    'SC' => 0,
                    'ST' => 0,
                    'BC' => 0
                ];
                $girlsData = [
                    'General' => 0,
                    'OBC' => 0,
                    'SC' => 0,
                    'ST' => 0,
                    'BC' => 0
                ];
                foreach ($row['categories'] as $key => $value) {
                    # code...
                    $boysData[$value['category_name']] = $value['boys'];
                    $girlsData[$value['category_name']] = $value['girls'];
                }


                fputcsv($output, [
                    $row['class'],
                    'Boys',
                    $boysData['General'],
                    $boysData['OBC'],
                    $boysData['SC'],
                    $boysData['ST'],
                    $boysData['BC'],
                ]);
                fputcsv($output, [
                    '',
                    'Girls',
                    $girlsData['General'],
                    $girlsData['OBC'],
                    $girlsData['SC'],
                    $girlsData['ST'],
                    $girlsData['BC'],
                ]);
            }
            // Rewind the memory stream to the beginning
            rewind($output);

            // Get the contents of the memory stream (CSV content)
            $csvContent = stream_get_contents($output);

            // Close the memory stream
            fclose($output);

            // Return the CSV content as a response
            return response($csvContent, 200)
                    ->header('Content-Type', 'text/csv')
                    ->header('Content-Disposition', 'attachment; filename="' . $fileName . '"');


        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => "Failed to export report: "
            ]);
        }
    }


    public function newAdmissionReportByReligion(Request $request)
    {
        try {
            $request->validate([
                'session_id' => 'required|exists:session_masters,id,active,1',

            ]);

            $allReligion = [
                1 => 'Hindu',
                2 => 'Muslim',
                3 => 'Christian',
                4 => 'Sikh',
            ];
            $studentsQuery = StudentMaster::query()
                ->where('session_id', $request->session_id)
                ->where('ssid', [1, 2])
                ->where('active', 1);
            // ->whereIn('class', [2, 5]);

            if ($request->new_admission) {
                $studentsQuery->whereNotNull('admission_date');
            }
            $studentsGrouped = $studentsQuery->get()->groupBy('class');

            // Retrieve all active classes
            $classID = explode(',', $request->class);
            // $allClasses = ClassMaster::whereIn('id', $classID)->where('active', 1)->orderBy('sort', 'ASC')->get();
            $allClasses = ClassMasterController::getClasses(['id','class'], null, false, ['id' => $classID], 'whereIn', true);

            $report = $allClasses->map(function ($class) use ($studentsGrouped, $allReligion) {
                $classId = $class->id;
                $className = $class->class;

                // Get students for this class or set to empty collection
                $students = $studentsGrouped->get($classId, collect());

                // Group students by religion
                $religionCounts = $students->groupBy('religion');

                $result = [
                    'class' => $className,
                    'religions' => []
                ];

                // Loop through all defined categories
                foreach ($allReligion as $religionId => $religionName) {
                    // Get students for this category or set to empty collection
                    $religionStudents = $religionCounts->get($religionId, collect());

                    $boys = $religionStudents->where('gender', 1)->count();
                    $girls = $religionStudents->where('gender', 2)->count();
                    $totalStudents = $boys + $girls;

                    $result['religions'][] = [
                        'religion_id' => $religionId,
                        'religion_name' => $religionName,
                        'boys' => $boys,
                        'girls' => $girls,
                        'totalStudents' => $totalStudents,
                    ];
                }

                return $result;
            })->values();

            return response()->json([
                'status' => 'success',
                'data' => $report,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => "Failed to get new admission report: "
            ], 500);
        }
    }


    /**
     * export report by religion-wise
     */
    public function exportReportByReligion(Request $request)
    {
        try {
            // Call the newAdmissionReportByCategory function to get the report data
            $response = $this->newAdmissionReportByReligion($request);

            // Check if the response is successful and contains data
            if ($response->getStatusCode() !== 200) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to generate report: ' . $response->getContent()
                ], 500);
            }

            // Get the report data from the response
            $reportData = json_decode($response->getContent(), true)['data'];

            // Set the file name for the exported CSV file
            $fileName = 'report_by_religion.csv';
            $output = fopen('php://memory', 'w');

            if ($output === false) {
                throw new \Exception('Failed to open output stream.');
            }

            // Set the CSV column headers
            $headers = ['Class', 'Gender', 'Hindu', 'Muslim', 'Christian', 'Sikh'];
            fputcsv($output, $headers);

            // Write the report data to the CSV file
            foreach ($reportData as $row) {
                $boysData = [
                    'Hindu' => 0,
                    'Muslim' => 0,
                    'Christian' => 0,
                    'Sikh' => 0

                ];
                $girlsData = [
                    'Hindu' => 0,
                    'Muslim' => 0,
                    'Christian' => 0,
                    'Sikh' => 0
                ];
                // dd($row);
                foreach ($row['religions'] as $key => $value) {
                    # code...
                    $boysData[$value['religion_name']] = $value['boys'];
                    $girlsData[$value['religion_name']] = $value['girls'];
                }


                fputcsv($output, [
                    $row['class'],
                    'Boys',
                    $boysData['Hindu'],
                    $boysData['Muslim'],
                    $boysData['Christian'],
                    $boysData['Sikh']
                ]);
                fputcsv($output, [
                    '',
                    'Girls',
                    $girlsData['Hindu'],
                    $girlsData['Muslim'],
                    $girlsData['Christian'],
                    $girlsData['Sikh']
                ]);
            }
            // Rewind the memory stream to the beginning
            rewind($output);

            // Get the contents of the memory stream (CSV content)
            $csvContent = stream_get_contents($output);

            // Close the memory stream
            fclose($output);

            // Return the CSV content as a response
            return response($csvContent, 200)
                    ->header('Content-Type', 'text/csv')
                    ->header('Content-Disposition', 'attachment; filename="' . $fileName . '"');


        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => "Failed to export report: "
            ], 500);
        }
    }
    public function newAdmissionReportByAgeProof(Request $request)
    {
        try {
            $request->validate([
                'session_id' => 'required|exists:session_masters,id,active,1',

            ]);

            $allAgeProof = [
                1 => 'By TC',
                2 => 'By Birth Cert.',
                3 => 'By Affidavit',
                4 => 'By Aadhar Card',
                0 => 'Without Proof',
            ];
            $classID = explode(',', $request->class);
            $studentsQuery = StudentMaster::query()
                ->where('session_id', $request->session_id)
                ->where('ssid', [1, 2])
                ->where('active', 1)
                ->whereIn('class', $classID);

            if ($request->new_admission) {
                $studentsQuery->whereNotNull('admission_date');
            }
            $studentsGrouped = $studentsQuery->get()->groupBy('class');

            // Retrieve all active classes
            $classID = explode(',', $request->class);
            // $allClasses = ClassMaster::whereIn('id', $classID)->where('active', 1)->orderBy('sort', 'ASC')->get();
            $allClasses = ClassMasterController::getClasses(['id','class'], null, false, ['id' => $classID], 'whereIn', true);

            $report = $allClasses->map(function ($class) use ($studentsGrouped, $allAgeProof) {
                $classId = $class->id;
                $className = $class->class;

                // Get students for this class or set to empty collection
                $students = $studentsGrouped->get($classId, collect());

                // Group students by religion
                $ageProofCounts = $students->groupBy('age_proof');

                $result = [
                    'class' => $className,
                    'ageProofs' => []
                ];

                // Loop through all defined categories
                foreach ($allAgeProof as $ageProofId => $ageProofName) {
                    // Get students for this category or set to empty collection
                    $ageProofStudents = $ageProofCounts->get($ageProofId, collect());

                    $boys = $ageProofStudents->where('gender', 1)->count();
                    $girls = $ageProofStudents->where('gender', 2)->count();
                    $totalStudents = $boys + $girls;

                    $result['ageProofs'][] = [
                        'age_proof_id' => $ageProofId,
                        'age_proof_name' => $ageProofName,
                        'boys' => $boys,
                        'girls' => $girls,
                        'totalStudents' => $totalStudents,
                    ];
                }

                return $result;
            })->values();

            return response()->json([
                'status' => 'success',
                'data' => $report,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => "Failed to get new admission report: "
            ], 500);
        }
    }

    /**
     * export report by age-proof-wise
     */
    public function exportReportByAgeProof(Request $request)
    {
        try {
            // Call the newAdmissionReportByCategory function to get the report data
            $response = $this->newAdmissionReportByAgeProof($request);

            // Check if the response is successful and contains data
            if ($response->getStatusCode() !== 200) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to generate report: ' . $response->getContent()
                ], 500);
            }

            // Get the report data from the response
            $reportData = json_decode($response->getContent(), true)['data'];

            // Set the file name for the exported CSV file
            $fileName = 'report_by_age_proof.csv';


            $output = fopen('php://memory', 'w');
            if ($output === false) {
                throw new \Exception('Failed to open output stream.');
            }

            // Set the CSV column headers
            $headers = ['Class', 'Gender', 'By TC', 'By Birth Cert.', 'By Affidavit', 'By Aadhar Card', 'Without Proof'];
            fputcsv($output, $headers);

            // Write the report data to the CSV file
            foreach ($reportData as $row) {
                $boysData = [
                    'By TC' => 0,
                    'By Birth Cert.' => 0,
                    'By Affidavit' => 0,
                    'By Aadhar Card' => 0,
                    'Without Proof' => 0

                ];
                $girlsData = [
                    'By TC' => 0,
                    'By Birth Cert.' => 0,
                    'By Affidavit' => 0,
                    'By Aadhar Card' => 0,
                    'Without Proof' => 0
                ];
                // dd($row);
                foreach ($row['ageProofs'] as $key => $value) {
                    # code...
                    $boysData[$value['age_proof_name']] = $value['boys'];
                    $girlsData[$value['age_proof_name']] = $value['girls'];
                }


                fputcsv($output, [
                    $row['class'],
                    'Boys',
                    $boysData['By TC'],
                    $boysData['By Birth Cert.'],
                    $boysData['By Affidavit'],
                    $boysData['By Aadhar Card'],
                    $boysData['Without Proof'],
                ]);
                fputcsv($output, [
                    '',
                    'Girls',
                    $girlsData['By TC'],
                    $girlsData['By Birth Cert.'],
                    $girlsData['By Affidavit'],
                    $girlsData['By Aadhar Card'],
                    $girlsData['Without Proof'],
                ]);
            }
            // Rewind the memory stream to the beginning
            rewind($output);

            // Get the contents of the memory stream (CSV content)
            $csvContent = stream_get_contents($output);

            // Close the memory stream
            fclose($output);

            // Return the CSV content as a response
            return response($csvContent, 200)
                    ->header('Content-Type', 'text/csv')
                    ->header('Content-Disposition', 'attachment; filename="' . $fileName . '"');


        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => "Failed to export report: "
            ], 500);
        }
    }

    public function newAdmissionReportByBetweenDates(Request $request)
    {
        try {
            //code...
            $request->validate([
                'session_id' => 'required|exists:session_masters,id,active,1',

            ]);
            $admissionStartDate = $request->startDate;
            $admissionEndDate = $request->endDate;

            $studentsQuery = StudentMaster::query()
                ->where('session_id', $request->session_id)
                ->whereIn('ssid', [1, 2])
                ->where('active', 1)
                ->whereNotNull('admission_date');
            // ->whereIn('class', [2, 3]);

            if ($admissionStartDate && $admissionEndDate) {
                $studentsQuery->whereBetween('admission_date', [$admissionStartDate, $admissionEndDate]);
            }

            $studentsGrouped = $studentsQuery->get()->groupBy('class');

            // Retrieve all active classes
            $classID = explode(',', $request->class);
            // $allClasses = ClassMaster::whereIn('id', $classID)->where('active', 1)->orderBy('sort', 'ASC')->get();
            $allClasses = ClassMasterController::getClasses(['id','class'], null, false, ['id' => $classID], 'whereIn', true);

            $report = $allClasses->map(function ($class) use ($studentsGrouped) {
                $classId = $class->id;
                $className = $class->class;

                // Get students for this class
                $students = $studentsGrouped->get($classId, collect());

                $boys = $students->where('gender', 1)->count();
                $girls = $students->where('gender', 2)->count();
                $totalStudents = $boys + $girls;


                return [
                    'class' => $className,
                    'boys' => $boys,
                    'girls' => $girls,
                    'total' => $totalStudents,
                ];
            });

            return response()->json([
                'status' => 'success',
                'data' => $report,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => "Failed to get new admission report: "
            ], 500);
        }
    }

    /**
     * export report by age-proof-wise
     */
    public function exportReportByBetweenDates(Request $request)
    {
        try {
            // Call the newAdmissionReportByCategory function to get the report data
            $response = $this->newAdmissionReportByBetweenDates($request);

            // Check if the response is successful and contains data
            if ($response->getStatusCode() !== 200) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to generate report: ' . $response->getContent()
                ], 500);
            }

            // Get the report data from the response
            $reportData = json_decode($response->getContent(), true)['data'];

            // Set the file name for the exported CSV file
            $fileName = 'new_admission_report_between_dates.csv';


            $output = fopen('php://memory', 'w');
            if ($output === false) {
                throw new \Exception('Failed to open output stream.');
            }

            // Set the CSV column headers
            $headers = ['Class', 'Gender', 'Total'];
            fputcsv($output, $headers);

            // Write the report data to the CSV file
            foreach ($reportData as $row) {
                fputcsv($output, [
                    $row['class'],
                    'Boys',
                    $row['boys'],
                ]);
                fputcsv($output, [
                    '',
                    'Girls',
                    $row['girls'],
                ]);
            }

            rewind($output);

            // Get the contents of the memory stream (CSV content)
            $csvContent = stream_get_contents($output);

            // Close the memory stream
            fclose($output);

            // Return the CSV content as a response
            return response($csvContent, 200)
                    ->header('Content-Type', 'text/csv')
                    ->header('Content-Disposition', 'attachment; filename="' . $fileName . '"');
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => "Failed to export report: "
            ], 500);
        }
    }



    public function reportAgeWise(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'session_id' => 'required|exists:session_masters,id,active,1',
                'date' => 'required|date',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $validator->errors()
                ], 400);
            }

            $classID = explode(',', $request->class);

            // Retrieve all active classes
            $allClasses = ClassMasterController::getClasses(['id','class'], null, false, ['id' => $classID], 'whereIn', true);

            $report = $allClasses->map(function ($class) use ($request) {
                // Get students for THIS CLASS ONLY
                $students = StudentMaster::where('session_id', $request->session_id)
                    ->where('class', $class->id) // Filter by specific class
                    ->whereIn('ssid', [1,2,4,5])
                    // ->where('ssid', 1)
                    ->where('active', 1)
                    ->get();
                // dd($students);

                $ageGroups = [
                    'lessThanFive' => ['boys' => 0, 'girls' => 0],
                    'equalToFive' => ['boys' => 0, 'girls' => 0],
                    'equalToSix' => ['boys' => 0, 'girls' => 0],
                    'equalToSeven' => ['boys' => 0, 'girls' => 0],
                    'equalToEight' => ['boys' => 0, 'girls' => 0],
                    'equalToNine' => ['boys' => 0, 'girls' => 0],
                    'equalToTen' => ['boys' => 0, 'girls' => 0],
                    'equalToEleven' => ['boys' => 0, 'girls' => 0],
                    'equalToTwelve' => ['boys' => 0, 'girls' => 0],
                    'equalToThirteen' => ['boys' => 0, 'girls' => 0],
                    'equalToFourteen' => ['boys' => 0, 'girls' => 0],
                    'equalToFifteen' => ['boys' => 0, 'girls' => 0],
                    'equalToSixteen' => ['boys' => 0, 'girls' => 0],
                    'aboveToSixteen' => ['boys' => 0, 'girls' => 0],
                ];

                foreach ($students as $student) {
                    $dob = DB::table('stu_detail')
                        ->where('srno', $student->srno)
                        ->where('active', 1)
                        ->value('dob');

                    if ($dob) {
                        $date = Carbon::parse($request->date);
                        $dob = Carbon::parse($dob);

                        // Calculate the age
                        $age = (int) $dob->diffInYears($date);
                        // dd($age);

                        $ageGroup = match (true) {
                            $age < 5 => 'lessThanFive',
                            $age === 5 => 'equalToFive',
                            $age === 6 => 'equalToSix',
                            $age === 7 => 'equalToSeven',
                            $age === 8 => 'equalToEight',
                            $age === 9 => 'equalToNine',
                            $age === 10 => 'equalToTen',
                            $age === 11 => 'equalToEleven',
                            $age === 12 => 'equalToTwelve',
                            $age === 13 => 'equalToThirteen',
                            $age === 14 => 'equalToFourteen',
                            $age === 15 => 'equalToFifteen',
                            $age === 16 => 'equalToSixteen',
                            default => 'aboveToSixteen',
                        };

                        $gender = $student->gender == 1 ? 'boys' : 'girls';
                        $ageGroups[$ageGroup][$gender]++;
                    }
                }

                return [
                    'class' => $class->class,
                    'ageGroups' => $ageGroups,
                ];
            });

            // Return the report as JSON
            return response()->json([
                'status' => 'success',
                'data' => $report,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => "Failed to get age-wise report: "
            ], 500);
        }
    }

    /**
     * export report age-wise
     */

    public function exportReportByAge(Request $request)
    {
        try {
            // Call the newAdmissionReportByCategory function to get the report data
            $response = $this->reportAgeWise($request);

            // Check if the response is successful and contains data
            if ($response->getStatusCode() !== 200) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to generate report: ' . $response->getContent()
                ], 500);
            }

            // Get the report data from the response
            $reportData = json_decode($response->getContent(), true)['data'];

            // Set the file name for the exported CSV file
            $fileName = 'age_wise_report.csv';


            $output = fopen('php://memory', 'w');
            if ($output === false) {
                throw new \Exception('Failed to open output stream.');
            }

            // Set the CSV column headers

            $headers = ['Class', 'Gender', '<5', '5', '6', '7', '8', '9', '10', '11', '12', '13', '14', '15', '16', '>16'];
            fputcsv($output, $headers);

            // Write the report data to the CSV file
            foreach ($reportData as $row) {
                fputcsv($output, [
                    $row['class'],
                    'Boys',
                    $row['ageGroups']['lessThanFive']['boys'],
                    $row['ageGroups']['equalToFive']['boys'],
                    $row['ageGroups']['equalToSix']['boys'],
                    $row['ageGroups']['equalToSeven']['boys'],
                    $row['ageGroups']['equalToEight']['boys'],
                    $row['ageGroups']['equalToNine']['boys'],
                    $row['ageGroups']['equalToTen']['boys'],
                    $row['ageGroups']['equalToEleven']['boys'],
                    $row['ageGroups']['equalToTwelve']['boys'],
                    $row['ageGroups']['equalToThirteen']['boys'],
                    $row['ageGroups']['equalToFourteen']['boys'],
                    $row['ageGroups']['equalToFifteen']['boys'],
                    $row['ageGroups']['equalToSixteen']['boys'],
                    $row['ageGroups']['aboveToSixteen']['boys'],
                ]);
                fputcsv($output, [
                    '',
                    'Girls',
                    $row['ageGroups']['lessThanFive']['girls'],
                    $row['ageGroups']['equalToFive']['girls'],
                    $row['ageGroups']['equalToSix']['girls'],
                    $row['ageGroups']['equalToSeven']['girls'],
                    $row['ageGroups']['equalToEight']['girls'],
                    $row['ageGroups']['equalToNine']['girls'],
                    $row['ageGroups']['equalToTen']['girls'],
                    $row['ageGroups']['equalToEleven']['girls'],
                    $row['ageGroups']['equalToTwelve']['girls'],
                    $row['ageGroups']['equalToThirteen']['girls'],
                    $row['ageGroups']['equalToFourteen']['girls'],
                    $row['ageGroups']['equalToFifteen']['girls'],
                    $row['ageGroups']['equalToSixteen']['girls'],
                    $row['ageGroups']['aboveToSixteen']['girls'],
                ]);
            }

            rewind($output);

            // Get the contents of the memory stream (CSV content)
            $csvContent = stream_get_contents($output);

            // Close the memory stream
            fclose($output);

            // Return the CSV content as a response
            return response($csvContent, 200)
                    ->header('Content-Type', 'text/csv')
                    ->header('Content-Disposition', 'attachment; filename="' . $fileName . '"');


        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => "Failed to export report: "
            ], 500);
        }
    }

    public function reportAgeWiseWithDetails(Request $request)
    {
        try {
            //code...
            $request->validate([
                'session_id' => 'required',
            ]);
            $classID = explode(',', $request->class);
            $fields = [
                'stu_main_srno.srno',
                'stu_main_srno.session_id',
                'stu_main_srno.class',
                'stu_main_srno.section',
                'stu_main_srno.active',
                'stu_main_srno.rollno',
                'stu_detail.name',
                'stu_detail.dob',
                'parents_detail.f_name',
                'parents_detail.m_name',
                'parents_detail.f_mobile',
                'class_masters.class as class_name',
                'section_masters.section as section_name',
            ];
            $studentsQuery = StudentMasterController::getStdWithNames(false, $fields)
                            ->where('stu_main_srno.session_id', $request->session_id)
                            ->whereIn('stu_main_srno.class', $classID)->get();
            // dd($studentsQuery);
            // $studentsQuery = StudentMaster::query()
            //     ->where('session_id', $request->session_id)
            //     ->whereIn('class', $classID)
            //     // ->where('ssid', 1)
            //     ->whereIn('ssid', [1,2,4,5])
            //     ->where('active', 1)->get(['srno', 'class', 'section']);

            $report = $studentsQuery->map(function ($student) use ($request) {
                // $dob = DB::table('stu_detail')->where('srno', $student->srno)->where('active', 1)->value('dob');
                $dob = $student->dob;
                $age = (int) Carbon::parse($dob)->diffInYears(Carbon::parse($request->date));
                // dd($age);
                return [
                    'class' => $student->class_name ?? '',
                    'section' => $student->section_name ?? '',
                    'srno' => $student->srno ?? '',
                    'name' => $student->name ?? '',
                    'f_name' => $student->f_name  ?? '',
                    'm_name' => $student->m_name ?? '',
                    'dob' => $dob ?? '',
                    'age' => $age ?? '',
                    'mobile' => $student->f_mobile ?? '',
                ];
            });
            return response()->json([
                'status' => 'success',
                'data' => $request->page ? $report->paginate(10) : $report,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => "Failed to get age-wise report: "
            ], 500);
        }
    }

    /**
     * export report age-wise-with-details
     */

    public function exportReportByAgeWithDetails(Request $request)
    {
        try {
            // Call the newAdmissionReportByCategory function to get the report data
            $response = $this->reportAgeWiseWithDetails($request);

            // Check if the response is successful and contains data
            if ($response->getStatusCode() !== 200) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to generate report: ' . $response->getContent()
                ], 500);
            }

            // Get the report data from the response
            $reportData = json_decode($response->getContent(), true)['data'];

            // Set the file name for the exported CSV file
            $fileName = 'age_wise_with_details_report.csv';


            $output = fopen('php://memory', 'w');
            if ($output === false) {
                throw new \Exception('Failed to open output stream.');
            }

            // Set the CSV column headers

            $headers = ['S.No.', 'Class', 'Section', 'SRNO', 'Name', "Father's Name", "Mother's Name", 'DOB', 'AGE', 'Mobile No.'];
            fputcsv($output, $headers);

            // Write the report data to the CSV file
            $i = 1;
            foreach ($reportData as $row) {
                // dd($row);

                fputcsv($output, [
                    $i++,
                    $row['class'],
                    $row['section'],
                    $row['srno'],
                    $row['name'],
                    $row['f_name'],
                    $row['m_name'],
                    $row['dob'],
                    $row['age'],
                    $row['mobile'],

                ]);
            }
            // Rewind the memory stream to the beginning
            rewind($output);

            // Get the contents of the memory stream (CSV content)
            $csvContent = stream_get_contents($output);

            // Close the memory stream
            fclose($output);

            // Return the CSV content as a response
            return response($csvContent, 200)
                    ->header('Content-Type', 'text/csv')
                    ->header('Content-Disposition', 'attachment; filename="' . $fileName . '"');

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => "Failed to export report: "
            ], 500);
        }
    }
    private function getStudentDetails()
    {
        $baseQuery = DB::table('stu_main_srno')
        // $baseQuery = DB::table('stu_main_srno')
            ->select(
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
                'stu_main_srno.ssid',
                'stu_main_srno.gender',
                'stu_main_srno.religion',
                'class_masters.class as class_name',
                'section_masters.section as section_name',
                'stu_detail.name as student_name',
                'stu_detail.mobile as student_mobile',
                'stu_detail.email as student_email',
                'stu_detail.dob',
                'stu_detail.address',
                'stu_detail.category_id as category',
                'parents_detail.f_name',
                'parents_detail.m_name',
                'parents_detail.g_father as g_f_name',
                'parents_detail.f_mobile',
                'parents_detail.m_mobile',
                'parents_detail.f_occupation',
                'parents_detail.m_occupation',
                'parents_detail.address as parent_address',
            )
            ->leftJoin('stu_detail', 'stu_main_srno.srno', '=', 'stu_detail.srno')
            ->leftJoin('parents_detail', 'stu_main_srno.srno', '=', 'parents_detail.srno')
            ->leftJoin('class_masters', 'stu_main_srno.class', '=', 'class_masters.id')
            ->leftJoin('section_masters', 'stu_main_srno.section', '=', 'section_masters.id')
            ->where('stu_main_srno.active', 1);
        return $baseQuery;
    }

    public function reportTransportWise(Request $request)
    {
        try {
            //code...
            $validator = Validator::make($request->all(), [
                'session_id' => 'required|exists:session_masters,id,active,1',
                'class' => 'exists:class_masters,id,active,1',
                'section' => 'exists:section_masters,id,active,1',

            ]);
            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $validator->errors()
                ], 400);
            }
            // $baseQuery = $this->getStudentDetails();
            $baseQuery = StudentMasterController::getStdWithNames(false, []);
            $classId = explode(',', $request->class);
            $sectionId = explode(',', $request->section);
            $transport = explode(',', $request->transport);

            if (filled($classId) && filled($sectionId) && filled($transport)) {
                # code...
                $data = StudentMasterController::getStdWithNames(false, [])->whereIn('stu_main_srno.class', $classId)
                    ->whereIn('stu_main_srno.section', $sectionId)
                    ->whereIn('stu_main_srno.transport', $transport)->where('stu_main_srno.session_id', $request->session_id);
                    // ->whereIn('stu_main_srno.transport', $transport)->where('session_id', $request->session_id)->where('stu_main_srno.ssid', 1);

                return response()->json([
                    'status' => 'success',
                    'data' => $request->page ? $data->paginate(10) : $data->get(),
                ], 200);
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => "Failed to get transport-wise report: "
            ]);
        }
    }

    /**
     * export report transport-wise
     */

    public function exportReportByTransportWise(Request $request)
    {
        try {
            // Call the newAdmissionReportByCategory function to get the report data
            $response = $this->reportTransportWise($request);

            // Check if the response is successful and contains data
            if ($response->getStatusCode() !== 200) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to generate report: ' . $response->getContent()
                ], 500);
            }

            // Get the report data from the response
            $reportData = json_decode($response->getContent(), true)['data'];

            // Set the file name for the exported CSV file
            $fileName = 'student_transport_details_report.csv';

            $output = fopen('php://memory', 'w');
            if ($output === false) {
                throw new \Exception('Failed to open output stream.');
            }

            // Set the CSV column headers
            $headers = ['R.N.', 'REGNO', 'Class', 'Section', 'Name', "Father's Name", 'DOB', 'Address', 'Mobile No.'];
            fputcsv($output, $headers);

            // Write the report data to the CSV file
            foreach ($reportData as $row) {
                fputcsv($output, [
                    $row['rollno'],
                    $row['srno'],
                    $row['class_name'],
                    $row['section_name'],
                    $row['student_name'],
                    $row['f_name'],
                    $row['dob'],
                    $row['address'],
                    $row['f_mobile'],

                ]);
            }
            // Rewind the memory stream to the beginning
            rewind($output);

            // Get the contents of the memory stream (CSV content)
            $csvContent = stream_get_contents($output);

            // Close the memory stream
            fclose($output);

            // Return the CSV content as a response
            return response($csvContent, 200)
                    ->header('Content-Type', 'text/csv')
                    ->header('Content-Disposition', 'attachment; filename="' . $fileName . '"');


        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => "Failed to export report: "
            ], 500);
        }
    }

    public function reportSrRegisterWise(Request $request)
    {
        try {
            //code...

            $validator = Validator::make($request->all(), [
                'session_id' => 'required|exists:session_masters,id,active,1',

            ]);
            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $validator->errors()
                ], 400);
            }
            $fields = [
                'stu_main_srno.srno',
                'stu_main_srno.session_id',
                'stu_main_srno.class',
                'stu_main_srno.section',
                'stu_main_srno.active',
                'stu_detail.name',
                'parents_detail.f_name',
                'class_masters.class as class_name',
                'class_masters.sort as class_sort',
            ];
            // $baseQuery = $this->getStudentDetails();
            $stdType = explode(',', $request->type);
            // $stdType = [1, 4, 5];
            if (filled($stdType)) {
                # code...
                $where = [
                    'whereIn' => ['stu_main_srno.ssid' => $stdType],
                    'where' => ['stu_main_srno.session_id' => $request->session_id],
                ];
                $orderBy = ['class_masters.sort' => 'asc'];
                $baseQuery = StudentMasterController::getStd($fields, $where, $orderBy);
                // $data = $baseQuery->where('session_id', $request->session_id)->whereIn('stu_main_srno.ssid', $stdType);
                $data = $baseQuery->where('stu_main_srno.session_id', $request->session_id)->whereIn('stu_main_srno.ssid', $stdType);
                return response()->json([
                    'status' => 'success',
                    'data' => $request->page ? $data->paginate(10) : $data->get(),
                ], 200);
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => "Failed to get transport-wise report: "
            ], 500);
        }
    }

    /**
     * st-previous-details(TC Issue View)
     */

    public function stPreviousDetails(Request $request)
    {
        try {
            //code...

            $validator = Validator::make($request->all(), [
                'srno' => 'required|exists:stu_main_srno,srno',

            ]);
            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $validator->errors()
                ], 400);
            }
            $baseQuery = $this->getStudentDetails();
            $data = $baseQuery->where('stu_main_srno.srno', $request->srno)->first();
            if ($data) {
                return response()->json([
                    'status' => 'success',
                    'data' => $data,
                ], 200);
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Student not found.',
                ], 404);
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => "Failed to get student previous details: "
            ], 500);
        }
    }




    /**
     * tc Student Details
     */

    public function tcStudentDetails(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'srno' => 'required_if:srno,true|string',
                'prevsrno' => 'required_if:prevsrno,true|string',

            ]);
            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $validator->errors()
                ], 400);
            }
            $srno = $request->srno;
            $prevsrno = $request->prevsrno;


            # code...
            $baseQuery = DB::table('stu_main_srno')
                ->select(
                    'stu_main_srno.id',
                    'stu_main_srno.srno',
                    'stu_main_srno.school',
                    'stu_main_srno.class',
                    'stu_main_srno.section',
                    'stu_main_srno.prev_srno',
                    'stu_main_srno.admission_date',
                    'stu_main_srno.form_submit_date',
                    'stu_main_srno.rollno',
                    'stu_main_srno.session_id',
                    'stu_main_srno.ssid',
                    'stu_main_srno.gender',
                    'stu_main_srno.religion',
                    'class_masters.class as class_name',
                    'section_masters.section as section_name',
                    'stu_detail.name as student_name',
                    'stu_detail.mobile as student_mobile',
                    'stu_detail.email as student_email',
                    'stu_detail.dob',
                    'stu_detail.address',
                    'stu_detail.category_id as category',
                    'parents_detail.f_name',
                    'parents_detail.m_name',
                    'parents_detail.f_mobile',
                    'parents_detail.m_mobile',
                    'parents_detail.f_occupation',
                    'parents_detail.m_occupation',
                    'parents_detail.address as parent_address',
                )
                ->leftJoin('stu_detail', 'stu_main_srno.srno', '=', 'stu_detail.srno')
                ->leftJoin('parents_detail', 'stu_main_srno.srno', '=', 'parents_detail.srno')
                ->leftJoin('class_masters', 'stu_main_srno.class', '=', 'class_masters.id')
                ->leftJoin('section_masters', 'stu_main_srno.section', '=', 'section_masters.id')
                ->orderBy('stu_main_srno.class', 'asc')->orderBy('stu_main_srno.section', 'asc');
            if ($srno) {
                # code...
                $baseQuery->where('stu_main_srno.srno', $srno)->where('ssid', 1);
            } elseif ($prevsrno) {
                # code...

                $baseQuery->where(function ($query) use ($prevsrno) {
                    $query->where('stu_main_srno.srno', $prevsrno)
                        ->orWhere('stu_main_srno.prev_srno', $prevsrno);
                })->where('ssid', '!=', 1);
            }
            return response()->json([
                'status' => 'success',
                'data' => $baseQuery->get(),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => "Failed to get student report: "
            ], 500);
        }
    }
    /**
     * tc student current details get
     */

    public function tcStCurrentDetails(Request $request)
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

            $tables = [];
            $srno = $request->srno;
            // Student Details Table
            $studentDetails = DB::table('stu_detail')
                ->where('srno', $srno)
                ->where('active', 1)
                ->select(
                    'srno',
                    'name',
                    'dob',
                    'address',
                    'category_id',
                    'email',
                    'mobile'
                )
                ->get()
                ->map(function ($row) {
                    return [
                        'srno' => $row->srno,
                        'name' => $row->name,
                        'dob' => Carbon::parse($row->dob)->format('d-M-Y'),
                        'address' => $row->address,
                        // 'pincode' => $row->pincode,
                        'category' => match ($row->category_id) {
                            1 => 'General',
                            2 => 'OBC',
                            3 => 'SC',
                            4 => 'ST',
                            5 => 'BC',
                            default => ''
                        },
                        'email' => $row->email,
                        'mobile' => $row->mobile
                    ];
                });

            if ($studentDetails->count() > 0) {
                $tables[] = [
                    'title' => 'Student Details',
                    'headers' => ['SRNO', 'Name', 'DOB', 'Address', 'Category', 'E-Mail', 'Mobile'],
                    'data' => $studentDetails->toArray()
                ];
            }

            // Parent Details Table
            $parentDetails = DB::table('parents_detail')
                ->where('srno', $srno)
                ->where('active', 1)
                ->select(
                    'f_name',
                    'm_name',
                    'address',
                    'f_mobile',
                    'm_mobile',
                    'f_occupation',
                    'm_occupation'
                )
                ->get()
                ->map(function ($row) {
                    return [
                        'father_name' => $row->f_name,
                        'mother_name' => $row->m_name,
                        'address' => $row->address,
                        'father_mobile' => $row->f_mobile,
                        'mother_mobile' => $row->m_mobile,
                        'father_occupation' => match ($row->f_occupation) {
                            '1' => 'Pvt. Service',
                            '2' => 'Govt. Service',
                            '3' => 'Farmer',
                            '4' => 'Business',
                            '5' => 'Military',
                            '6' => 'Professional',
                            default => ''
                        },
                        'mother_occupation' => match ($row->m_occupation) {
                            '1' => 'Pvt. Service',
                            '2' => 'Govt. Service',
                            '3' => 'House Wife',
                            '4' => 'Business',
                            '5' => 'Military',
                            '6' => 'Professional',
                            default => ''
                        }
                    ];
                });

            if ($parentDetails->count() > 0) {
                $tables[] = [
                    'title' => 'Parent Details',
                    'headers' => ["Father's Name", "Mother's Name", 'Address', 'F. Mobile', 'M. Mobile', 'F. Occupation', 'M. Occupation'],
                    'data' => $parentDetails->toArray()
                ];
            }

            // Academic Details Table
            $academicDetails = DB::table('stu_main_srno')
                ->join('session_masters', 'session_masters.id', '=', 'stu_main_srno.session_id')
                ->join('class_masters', 'class_masters.id', '=', 'stu_main_srno.class')
                ->join('section_masters', 'section_masters.id', '=', 'stu_main_srno.section')
                ->where('stu_main_srno.srno', $srno)
                ->where('stu_main_srno.ssid', 1)
                ->select(
                    'session_masters.session',
                    'class_masters.class',
                    'section_masters.section',
                    'stu_main_srno.rollno',
                    'stu_main_srno.gender',
                    'stu_main_srno.religion',
                    'stu_main_srno.admission_date',
                    'stu_main_srno.form_submit_date'
                )
                ->get()
                ->map(function ($row) {
                    return [
                        'session' => $row->session,
                        'class' => $row->class,
                        'section' => $row->section,
                        'rollno' => $row->rollno,
                        'gender' => match ($row->gender) {
                            1 => 'Male',
                            2 => 'Female',
                            3 => "Other's",
                            default => ''
                        },
                        'religion' => match ($row->religion) {
                            1 => 'Hindu',
                            2 => 'Muslim',
                            3 => 'Christian',
                            4 => 'Sikh',
                            default => ''
                        },
                        'admission_date' => $row->admission_date ?
                            Carbon::parse($row->admission_date)->format('d-M-Y') : ($row->form_submit_date ?
                                Carbon::parse($row->form_submit_date)->format('d-M-Y') :
                                '')
                    ];
                });


            if ($academicDetails->count() > 0) {
                $tables[] = [
                    'title' => 'Academic Details',
                    'headers' => ['Session', 'Class', 'Section', 'Rollno', 'Gender', 'Religion', 'Admission Date'],
                    'data' => $academicDetails->toArray()
                ];
            }
            // select a_date from tbl_attendance where srno='" + srno + "' and status=1
            $attendance = Attendance::where('srno', $srno)->where('status', 1)->latest('id')->first();
            // dd($attendance);
            if ($attendance) {
                // $attendanceData = $attendance->get()->map(function ($row) {
                $attendanceData =  [
                    'date' => Carbon::parse($attendance->a_date)->format('d-M-Y'),
                    'attendance_status' => $attendance->status === 1 ? 'Present' : 'Absent'
                ];
                // });
                $tables[] = [
                    'title' => 'Attendance',
                    'headers' => ['Date', 'Attendance Status'],
                    'data' => $attendanceData
                ];
            } else {
                # code...
                $tables[] = [
                    'title' => 'Attendance',
                    'headers' => ['Date', 'Attendance Status'],
                    'data' => []
                ];
            }

            return response()->json([
                'status' => 'success',
                'tables' => $tables
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => "Failed to get student details: "
            ], 500);
        }
    }


    /**
     * TC Student Previous Details
     */
    public function tcStPreviousDetails(Request $request)
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

            $tables = [];
            $srno = $request->srno;
            // Student Details Table
            $student = StudentMasterController::getStd(['stu_main_srno.srno', 'stu_main_srno.session_id'], [
                'where' => ['stu_main_srno.srno' => $srno],
                'whereNot' => ['stu_main_srno.session_id' => $request->session]
            ])->first();

            if (!$student) {
                # code...
                // return response()->json([
                //    'status' => 'error',
                //    'message' => 'No student found for given SRNO and session.'
                // ], 404);
                return response()->json([
                    'status' => 'success',
                    'tables' => $tables
                ]);
            }
            $studentDetails = DB::table('stu_detail')
                // ->where('srno', $srno)
                ->where('srno', $student->srno)
                ->where('active', 1)
                // ->where('active', '!=', 1)
                ->select(
                    'srno',
                    'name',
                    'dob',
                    'address',
                    'pincode',
                    'category_id',
                    'email',
                    'mobile'
                )
                ->get()
                ->map(function ($row) {
                    return [
                        'srno' => $row->srno,
                        'name' => $row->name,
                        'dob' => Carbon::parse($row->dob)->format('d-M-Y'),
                        'address' => $row->address,
                        'pincode' => $row->pincode,
                        'category' => match ($row->category_id) {
                            1 => 'General',
                            2 => 'OBC',
                            3 => 'SC',
                            4 => 'ST',
                            5 => 'BC',
                            default => ''
                        },
                        'email' => $row->email,
                        'mobile' => $row->mobile
                    ];
                });

            if ($studentDetails->count() > 0) {
                $tables[] = [
                    'title' => 'Previous Details',
                    'headers' => ['SRNO', 'Name', 'DOB', 'Address', 'Pin C.', 'Category', 'E-Mail', 'Mobile'],
                    'data' => $studentDetails->toArray()
                ];
            }

            // Parent Details Table
            $parentDetails = DB::table('parents_detail')
                ->where('srno', $student->srno)
                // ->where('srno', $srno)
                ->where('active', 1)
                // ->where('active', '!=', 1)
                ->select(
                    'f_name',
                    'm_name',
                    'address',
                    'f_mobile',
                    'm_mobile',
                    'f_occupation',
                    'm_occupation'
                )
                ->get()
                ->map(function ($row) {
                    return [
                        'father_name' => $row->f_name,
                        'mother_name' => $row->m_name,
                        'address' => $row->address,
                        'father_mobile' => $row->f_mobile,
                        'mother_mobile' => $row->m_mobile,
                        'father_occupation' => match ($row->f_occupation) {
                            '1' => 'Pvt. Service',
                            '2' => 'Govt. Service',
                            '3' => 'Farmer',
                            '4' => 'Business',
                            '5' => 'Military',
                            '6' => 'Professional',
                            default => ''
                        },
                        'mother_occupation' => match ($row->m_occupation) {
                            '1' => 'Pvt. Service',
                            '2' => 'Govt. Service',
                            '3' => 'House Wife',
                            '4' => 'Business',
                            '5' => 'Military',
                            '6' => 'Professional',
                            default => ''
                        }
                    ];
                });

            if ($parentDetails->count() > 0) {
                $tables[] = [
                    'title' => 'Parent Details',
                    'headers' => ["Father's Name", "Mother's Name", 'Address', 'F. Mobile', 'M. Mobile', 'F. Occupation', 'M. Occupation'],
                    'data' => $parentDetails->toArray()
                ];
            }

            // Academic Details Table
            $academicStFields = [
                'session_masters.session',
                'class_masters.class',
                'section_masters.section',
                'stu_main_srno.rollno',
                'stu_main_srno.gender',
                'stu_main_srno.religion',
                'stu_main_srno.admission_date',
                'stu_main_srno.form_submit_date',
            ];
            $academicStWhere = [
                'where' => ['stu_main_srno.srno' => $srno],
                'whereNot' => [
                    'stu_main_srno.session_id' => $request->session,
                ]
            ];
            $academicDetails = StudentMasterController::getStd($academicStFields, $academicStWhere)

                ->get()
                ->map(function ($row) {
                    return [
                        'session' => $row->session,
                        'class' => $row->class,
                        'section' => $row->section,
                        'rollno' => $row->rollno,
                        'gender' => match ($row->gender) {
                            1 => 'Male',
                            2 => 'Female',
                            3 => "Other's",
                            default => ''
                        },
                        'religion' => match ($row->religion) {
                            1 => 'Hindu',
                            2 => 'Muslim',
                            3 => 'Christian',
                            4 => 'Sikh',
                            default => ''
                        },
                        'admission_date' => $row->admission_date ?
                            Carbon::parse($row->admission_date)->format('d-M-Y') : ($row->form_submit_date ?
                                Carbon::parse($row->form_submit_date)->format('d-M-Y') :
                                '')
                    ];
                });


            if ($academicDetails->count() > 0) {
                $tables[] = [
                    'title' => 'Academic Details',
                    'headers' => ['Session', 'Class', 'Section', 'Rollno', 'Gender', 'Religion', 'Admission Date'],
                    'data' => $academicDetails->toArray()
                ];
            }

            return response()->json([
                'status' => 'success',
                'tables' => $tables
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => "Failed to get student previous details: "
            ], 500);
        }
    }
    /**
     * Tc Student Status MEssages
     */
    public function tcStudentStatusMessages(Request $request)
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
            $response = [
                'error' => '',
                'message' => '',
            ];
            $srno = $request->srno;
            if (!empty($srno)) {
                $student = DB::table('stu_main_srno')
                    ->where('srno', $srno)
                    ->get(); // Get records that match the srno

                // Check if the student record exists
                if ($student->isEmpty()) {
                    $response['error'] = "No Record Found.";
                } else {
                    $student = $student->last();

                    // Get the `uid` and `active` values for further processing
                    $studentId = $student->active;
                    $activeStatus = $student->ssid;

                    // Set appropriate message based on the `uid` and `active` status
                    if ($studentId == 1) {
                        switch ($activeStatus) {
                            case 1:
                                $response['message'] = "Student Studying Currently. No any action taken yet.";
                                break;
                            case 2:
                                $response['message'] = "Promoted to New Class";
                                break;
                            case 3:
                                $response['message'] = "Promoted to New School";
                                break;
                            case 4:
                                $response['message'] = "Set as 'TC to Student', but TC NOT Issued Yet";
                                break;
                            case 5:
                                $response['message'] = "Set as 'Left Out', but TC NOT Issued Yet";
                                break;
                        }
                    } elseif ($studentId == 2) {
                        $response['message'] = "Last Class Passed TC Issued.";
                    } elseif ($studentId == 3) {
                        $response['message'] = "Studying Currently But Last Class Passed TC Issued.";
                    } elseif ($studentId == 4) {
                        $response['message'] = "Studying TC Issued.";
                    }
                }
            } else {
                $response['error'] = "Enter Properly.";
            }

            // Return the response as JSON
            // return response()->json($response);
            return response()->json([
                'status' => 'success',
                'data' => $response
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => "Failed to get student previous details: "
            ], 500);
        }
    }

    /**
     * tc to the student button 1
     */

    public function tcToTheStudent(Request $request)
    {
        try {
            // Validate input
            $validator = Validator::make($request->all(), [
                'srno' => 'required|string',
                'reason' => 'required|string',
                'ref_no' => 'required|string',
                'tc_date' => 'required|date',
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $validator->errors()
                ], 400);
            }


            $srno = $request->input('srno');
            $reason = $request->input('reason');
            $refTCNo = $request->input('ref_no');
            $tc_date = $request->input('tc_date');

            // Fetch the student details
            $student = DB::table('stu_main_srno')
                ->where('srno', $srno)
                ->orderByDesc('id')
                ->get();

            if ($student->isEmpty()) {
                return response()->json(['errorMessage' => 'Student not found.'], 404);
            }

            $lastRecord = $student->first();

            // Check if uid is 1 (TC not issued yet)
            if ($lastRecord->active == 1) {
                // Check if active is 1 or 5 (Student is currently active or left out)
                if (in_array($lastRecord->ssid, [1, 5])) {
                    // Case for student who is studying but TC was issued for last class
                    if ($student->count() > 1) {
                        // Get second last record and check session info
                        $secondLastRecord = $student->skip(1)->first();
                        $session = SessionMaster::find($secondLastRecord->session_id);

                        if ($session && $session->result_date) {
                            // Update the last record
                            DB::table('stu_main_srno')
                                ->where('id', $lastRecord->id)
                                ->update([
                                    'active' => 3,
                                    'ssid' => 4,
                                    'reason' => $reason,
                                    'TCRefNo' => $refTCNo,
                                    'updated_at' => $tc_date,
                                ]);

                            // Generate the TC print view
                            $session_id = $lastRecord->session_id;
                            return response()->json([
                                'message' => 'TC successfully issued.',
                                'print_url' => url("admin/print-tc?srno={$srno}&session={$session_id}&tid=2&rep=0")
                            ]);
                        } else {
                            return response()->json(['errorMessage' => 'Result date not updated. Please update result date first.'], 400);
                        }
                    } else {
                        // No previous record found
                        return response()->json(['errorMessage' => 'No previous records found, issuing TC as studying.'], 400);
                    }
                } else {
                    return response()->json(['errorMessage' => 'Student is not in new class or left out.'], 400);
                }
            } else {
                return response()->json(['errorMessage' => 'TC already issued. Only reprint is allowed.'], 400);
            }
        } catch (\Exception $e) {
            // Catch any unexpected errors
            return response()->json(['error' => 'An error occurred. Please try again.'], 500);
        }
    }

    /**
     * tc to the student button 2
     */

    public function tcToTheStudentBtn2(Request $request)
    {
        try {
            // Validate input
            $validator = Validator::make($request->all(), [
                'srno' => 'required|string',
                'reason' => 'required|string',
                'ref_no' => 'required|string',
                'tc_date' => 'required|date',
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $validator->errors()
                ], 400);
            }

            $srno = $request->input('srno');
            $reason = $request->input('reason');
            $refTCNo = $request->input('ref_no');
            $tc_date = $request->input('tc_date');

            // Fetch the student details where uid = 1 (TC not issued)
            $student = DB::table('stu_main_srno')
                ->where('srno', $srno)
                ->where('active', 1)
                ->orderByDesc('id')
                ->get();

            if ($student->isEmpty()) {
                return response()->json(['errorMessage' => 'Student not found or TC already issued.'], 404);
            }

            $lastRecord = $student->first();

            // Check if the last record is eligible for TC issuance (uid = 1 and active = 4)
            if ($lastRecord->active == 1 && $lastRecord->ssid == 4) {

                if ($student->count() > 1) {
                    // Get the second last record and check session info
                    $secondLastRecord = $student->skip(1)->first();
                    $session = SessionMaster::find($secondLastRecord->session_id);

                    if ($session && $session->result_date) {
                        // Update the last record with active = 2 (TC issued, can reprint)
                        DB::table('stu_main_srno')
                            ->where('id', $lastRecord->id)
                            ->update([
                                'active' => 2,
                                'ssid' => 4,
                                'reason' => $reason,
                                'TCRefNo' => $refTCNo,
                                'updated_at' => $tc_date,
                            ]);

                        // Generate the TC print view (tid = 1 means last class passed TC, rep = 0 means not reprint)
                        $session_id = $lastRecord->session_id;
                        return response()->json([
                            'message' => 'TC successfully issued.',
                            'print_url' => url("admin/print-tc?srno={$srno}&session={$session_id}&tid=1&rep=0")
                        ]);
                    } else {
                        return response()->json(['errorMessage' => 'Result date not updated. Please update the result date first.'], 400);
                    }
                } else {
                    return response()->json(['errorMessage' => 'No previous records found, issuing TC as studying.'], 400);
                }
            } else {
                // Error if student is not set as "TC to Student" from Promote Class Panel
                return response()->json(['errorMessage' => 'Student not set as "TC to Student" from Promote Class Panel.'], 400);
            }
        } catch (\Exception $e) {
            // Catch any unexpected errors
            return response()->json(['error' => 'An error occurred. Please try again.'], 500);
        }
    }
    /**
     * tc to the student button 3
     */

    public function tcToTheStudentBtn3(Request $request)
    {
        try {
            // Validate input
            $validator = Validator::make($request->all(), [
                'srno' => 'required|string',
                'reason' => 'required|string',
                'ref_no' => 'required|string',
                'tc_date' => 'required|date',
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $validator->errors()
                ], 400);
            }

            $srno = $request->input('srno');
            $reason = $request->input('reason');
            $refTCNo = $request->input('ref_no');
            $tc_date = $request->input('tc_date');

            // Fetch the student details where uid = 1 (TC not issued yet)
            $student = DB::table('stu_main_srno')
                ->where('srno', $srno)
                ->where('active', 1)
                ->orderByDesc('id')
                ->get();

            if ($student->isEmpty()) {
                return response()->json(['errorMessage' => 'Student not found or TC already issued.'], 404);
            }

            $lastRecord = $student->first();

            // Check if the last record is eligible for issuing a Studying TC (uid = 1 and active = 1 or 5)
            if ($lastRecord->active == 1 && in_array($lastRecord->ssid, [1, 5])) {

                // If there's more than one record (i.e., the student has a previous session)
                if ($student->count() > 1) {
                    // Get the second last record and check session info
                    $secondLastRecord = $student->skip(1)->first();
                    $session = SessionMaster::find($secondLastRecord->session_id);

                    if ($session && $session->result_date) {
                        // Update the last record with uid = 4 (Studying TC Issued)
                        DB::table('stu_main_srno')
                            ->where('id', $lastRecord->id)
                            ->update([
                                'active' => 4,
                                'ssid' => 4,
                                'reason' => $reason,
                                'TCRefNo' => $refTCNo,
                                'updated_at' => $tc_date,
                            ]);

                        // Generate the TC print view (tid = 3 means Studying TC, rep = 0 means not reprint)
                        $session_id = $lastRecord->session_id;
                        return response()->json([
                            'message' => 'Studying TC successfully issued.',
                            'print_url' => url("admin/print-tc?srno={$srno}&session={$session_id}&tid=3&rep=0")
                        ]);
                    } else {
                        return response()->json(['errorMessage' => 'Result date not updated. Please update the result date first.'], 400);
                    }
                } else {
                    return response()->json(['errorMessage' => 'No previous records found, issuing TC as studying.'], 400);
                }
            } else {
                // Error if student is not in new class or is left out
                return response()->json(['errorMessage' => 'Student not in new class or left out.'], 400);
            }
        } catch (\Exception $e) {
            // Catch any unexpected errors
            return response()->json(['error' => 'An error occurred. Please try again.'], 500);
        }
    }
    /**
     * tc to the student button 4
     */
    public function tcToTheStudentBtn4(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'srno' => 'required|string',

            ]);
            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $validator->errors()
                ], 400);
            }
            $srno = $request->srno; // Assuming 'srno' is passed as input

            if (!empty($srno)) {
                // Query the database to find student details
                $dsrno = DB::table('stu_main_srno')->where('srno', $srno)->get();

                if ($dsrno->isNotEmpty()) {
                    $uid = $dsrno->last()->active; // Get the last row's 'active' value

                    switch ($uid) {
                        case 2:
                            // If uid == 2, print TC (passed last class)
                            return response()->json([
                                'message' => 'TC can be reprinted.',
                                'print_url' => url("admin/print-tc?srno={$srno}&session={$dsrno->last()->session_id}&tid=1&rep=0")
                            ]);

                            break;

                        case 3:
                            // If uid == 3, print TC (studying but passed)
                            return response()->json([
                                'message' => 'TC can be reprinted.',
                                'print_url' => url("admin/print-tc?srno={$srno}&session={$dsrno->get($dsrno->count() - 2)->session_id}&tid=2&rep=0")
                            ]);

                            break;

                        case 4:
                            // If uid == 4, print TC (studying)
                            return response()->json([
                                'message' => 'TC can be reprinted.',
                                'print_url' => url("admin/print-tc?srno={$srno}&session={$dsrno->last()->session_id}&tid=3&rep=0")
                            ]);

                            break;

                        default:
                            // UID not recognized or TC not issued
                            return response()->json(['errorMessage' => 'TC NOT issued yet, Reprint after issue.'], 400);
                            break;
                    }
                } else {
                    return response()->json(['errorMessage' => 'TC NOT issued yet, Reprint after issue.'], 400);
                }
            } else {
                return response()->json(['errorMessage' => 'Enter Properly.......Try Again.'], 400);
            }
        } catch (\Exception $e) {
            return response()->json(['error' => 'An error occurred. Please try again.'], 500);
        }
    }

    /**
     * RTE Student Report View
     */
    public function rteStudentReportView()
    {

        return view('admin.reports.rte_std_report');
    }

    /**
     * RTE Student Report
     */
    public function rteStudentReport(Request $request)
    {
        try {
            // Validate input
            $validator = Validator::make($request->all(), [
                'class' => 'required|exists:class_masters,id,active,1',
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $validator->errors()
                ], 400);
            }

            // Parse the class input (comma-separated values)
            $class = explode(',', $request->class);

            // Build the query
            $fields = [
                'stu_main_srno.srno',
                'stu_main_srno.class',
                'class_masters.sort',
                'stu_main_srno.ssid',
                'stu_main_srno.active',
                'stu_main_srno.session_id',
                'stu_detail.name',
                'parents_detail.f_name',
            ];
            $studentQuery =  StudentMasterController::getStdWithNames(false, $fields)->where('stu_main_srno.session_id', $request->session)
                             ->where('stu_main_srno.srno', 'like', '%RTE%')
                             ->whereIn('stu_main_srno.class', $class)->orderBy('class_masters.sort', 'asc');
            // $studentQuery = StudentMaster::whereIn('class', $class)
            //     ->where('session_id', $request->session)
            //     ->where('srno', 'like', '%RTE%')
            //     // ->where('ssid', 1);
            //     ->whereIn('ssid', [1,2,4,5]);

            // Paginate or get all students based on the request
            $students = isset($request->page) ? $studentQuery->paginate(10) : $studentQuery->get();

            $report = [];
            foreach ($students as $student) {
                // $parents = DB::table('parents_detail')
                //     ->where('srno', $student->srno)
                //     ->where('active', 1)
                //     ->value('f_name');

                // $studentDetails = DB::table('stu_detail')
                //     ->where('srno', $student->srno)
                //     ->where('active', 1)
                //     ->value('name');

                $report[] = [
                    'srno' => $student->srno,
                    'name' => $student->name,
                    'f_name' => $student->f_name,
                ];
            }


            // Return the full report
            return response()->json([
                'status' => 'success',
                'data' => $report,
                'pagination' => isset($request->page) ?
                    [
                        'total' => $students->total(),
                        'per_page' => $students->perPage(),
                        'current_page' => $students->currentPage(),
                        'last_page' => $students->lastPage(),
                        'from' => $students->firstItem(),
                        'to' => $students->lastItem(),
                    ] : [],
            ], 200);
        } catch (\Exception $e) {
            // Catch any unexpected errors
            return response()->json(['error' => 'An error occurred. Please try again.'], 500);
        }
    }

    /**
     * RTE Student Report in excel file
     */
    public function rteStudentReportExcel(Request $request)
    {
        try {
            // Call the newAdmissionReportByCategory function to get the report data
            $response = $this->rteStudentReport($request);

            // Check if the response is successful and contains data
            if ($response->getStatusCode() !== 200) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to generate report: ' . $response->getContent()
                ], 500);
            }

            // Get the report data from the response
            $reportData = json_decode($response->getContent(), true)['data'];

            // Set the file name for the exported CSV file
            $fileName = 'RTE_st_report.csv';


            $output = fopen('php://memory', 'w');
            if ($output === false) {
                throw new \Exception('Failed to open output stream.');
            }

            // Set the CSV column headers
            $headers = ['S.No.', 'SRNO', 'Name', 'Gurdian Name', 'Category(WS/DG)', 'Sign. of Certifier	', 'Remark'];
            fputcsv($output, $headers);

            // Write the report data to the CSV file
            foreach ($reportData as $index => $row) {
                fputcsv($output, [
                    $index + 1,
                    $row['srno'],
                    $row['name'],
                    $row['f_name'],
                    '-',
                    '-',
                    '-',
                ]);
            }
            // Rewind the memory stream to the beginning
            rewind($output);

            // Get the contents of the memory stream (CSV content)
            $csvContent = stream_get_contents($output);

            // Close the memory stream
            fclose($output);

            // Return the CSV content as a response
            return response($csvContent, 200)
                    ->header('Content-Type', 'text/csv')
                    ->header('Content-Disposition', 'attachment; filename="' . $fileName . '"');

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => "Failed to export report"
            ], 500);
        }
    }

    /**
     * SR Register(Full) View
     */
    public function srRegisterView()
    {

        return view('admin.reports.sr_register_full_details');
    }

    /**
     * SR Register (Full Details)
     */

    public function srRegisterFullDetails(Request $request)
    {
        try {
            // Validate input
            $rules = [
                'class' => 'required|exists:class_masters,id,active,1',
                'srnoType' => 'required',
            ];
            if (isset($request->startSrno) || isset($request->endSrno)) {
                # code...
                $rules['startSrno'] = [
                    'required',
                    function ($attribute, $value, $fail) use ($request) {
                        if (isset($request->endSrno) && $value > $request->endSrno) {
                            $fail('Start SRNO must be less then or equal to End SRNO');
                        }
                    },
                    'exists:stu_main_srno,srno',
                ];
                $rules['endSrno'] =
                    [
                        'required_if:startSrno, true',
                        function ($attribute, $value, $fail) use ($request) {
                            if (isset($request->startSrno) && $value < $request->startSrno) {
                                $fail('Start SRNO must be greater then or equal to Start SRNO');
                            }
                        },
                        'exists:stu_main_srno,srno',
                    ];
                // $rules['endSrno'] ='required|exists:stu_main_srno,srno|lt:startSrno';
            }
            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $validator->errors()
                ], 400);
            }

            // Parse the class input (comma-separated values)
            $class = explode(',', $request->class);
            $section = explode(',', $request->section);

            // Build the query
            $baseQuery = StudentMasterController::getStdWithNames(false,[]);
            // $baseQuery = $this->getStudentDetails();
            $studentQuery = $baseQuery->whereIn('stu_main_srno.class', $class)
                ->whereIn('stu_main_srno.section', $section)
                ->where('stu_main_srno.session_id', $request->session)->orderBy('class_masters.sort','asc');

            if ($request->srnoType == 1) {
                # code...
                $studentQuery->where('stu_main_srno.school', 2);
            } elseif ($request->srnoType == 2) {
                # code...
                $studentQuery->where('stu_main_srno.school', 1);
            } elseif ($request->srnoType == 3) {
                # code...
                $studentQuery->where('stu_main_srno.srno', 'like', '%RTE%');
            }


            if (isset($request->startSrno) && isset($request->endSrno)) {
                $studentQuery->whereBetween('stu_main_srno.srno', [$request->startSrno, $request->endSrno]);
            }

            // Paginate or get all students based on the request
            $students = isset($request->page) ? $studentQuery->paginate(10) : $studentQuery->get();


            // Return the full report
            return response()->json([
                'status' => 'success',
                'data' => $students,

            ], 200);
        } catch (\Exception $e) {
            // Catch any unexpected errors
            return response()->json(['error' => 'An error occurred. Please try again.'], 500);
        }
    }
    /**
     * SR Register(Full Details) Excel
     */
    public function srRegisterFullDetailsExcel(Request $request)
    {
        try {
            // Call the newAdmissionReportByCategory function to get the report data
            $response = $this->srRegisterFullDetails($request);

            // Check if the response is successful and contains data
            if ($response->getStatusCode() !== 200) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to generate report: ' . $response->getContent()
                ], 500);
            }

            // Get the report data from the response
            $reportData = json_decode($response->getContent(), true)['data'];

            // Set the file name for the exported CSV file
            $fileName = 'sr_register_full_report.csv';

            $output = fopen('php://memory', 'w');
            if ($output === false) {
                throw new \Exception('Failed to open output stream.');
            }

            // Set the CSV column headers
            $headers = ['S.No.', 'SRNO', 'Class', 'Section', 'Roll No.', 'Status', 'Name', "Father's Name", "Mother's Name", 'Contact No.', 'Contact 2', 'Address', 'Gender', 'DOB', 'Admission Date', 'Age Proof', 'Prev. SRNO', 'Religion',    'Transport', 'Category', "Father's Occupation", "Mother's Occupation"];
            fputcsv($output, $headers);

            // Write the report data to the CSV file
            foreach ($reportData as $index => $row) {
                fputcsv($output, [
                    $index + 1,
                    $row['srno'],
                    $row['class_name'],
                    $row['section_name'],
                    $row['rollno'],
                    $row['ssid'] == 1 ? 'Active' : ($row['ssid'] == 2 ? 'Class Promoted' : ($row['ssid'] == 3 ? 'School Promoted' : ($row['ssid'] == 4 ? 'Tc' : ($row['ssid'] == 5 ? 'Left Out' : '')))),
                    $row['student_name'],
                    $row['f_name'],
                    $row['m_name'],
                    $row['f_mobile'],
                    $row['m_mobile'],
                    $row['address'],
                    $row['gender'] == 1 ? 'Male' : ($row['gender'] == 2 ? 'Female' : ($row['gender'] == 3 ? "Other's" : '')),
                    $row['dob'],
                    $row['form_submit_date'],
                    $row['age_proof'] == 0 ? 'N/A' : ($row['age_proof'] == 1 ? 'Transfer Certificate (T.C.)' : ($row['age_proof'] == 2 ? 'Birth Certificate' : ($row['age_proof'] == 3 ? 'Affidavit' : ($row['age_proof'] == 4 ? 'Aadhar Card' : '')))),
                    $row['prev_srno'],
                    $row['religion'] == 1 ? 'Hindu' : ($row['religion'] == 2 ? 'Muslim' : ($row['religion'] == 3 ? 'Christian' : ($row['religion'] == 4 ? 'Sikh' : ''))),
                    $row['transport'] == 0 ? 'No' : ($row['transport'] == 1 ? 'Yes' : ''),
                    $row['category'] == 1 ? 'General' : ($row['category'] == 2 ? 'OBC' : ($row['category'] == 3 ? 'SC' : ($row['category'] == 4 ? 'ST' : ($row['category'] == 5 ? 'BC' : '')))),
                    $row['f_occupation'] == 1 ? 'Private Service' : ($row['f_occupation'] == 2 ? 'Govt. Service' : ($row['f_occupation'] == 3 ? 'Farmer' : ($row['f_occupation'] == 4 ? 'Business' : ($row['f_occupation'] == 5 ? 'Military Service' : '')))),
                    $row['m_occupation'] == 1 ? 'Private Service' : ($row['m_occupation'] == 2 ? 'Govt. Service' : ($row['m_occupation'] == 3 ? 'House Wife' : ($row['m_occupation'] == 4 ? 'Business' : ($row['m_occupation'] == 5 ? 'Military Service' : '')))),
                ]);
            }
            // Rewind the memory stream to the beginning
            rewind($output);

            // Get the contents of the memory stream (CSV content)
            $csvContent = stream_get_contents($output);

            // Close the memory stream
            fclose($output);

            // Return the CSV content as a response
            return response($csvContent, 200)
                    ->header('Content-Type', 'text/csv')
                    ->header('Content-Disposition', 'attachment; filename="' . $fileName . '"');


        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => "Failed to export report: "
            ], 500);
        }
    }

    /**
     * Fee Report Admin View
     */

    public function feeReportAdminView()
    {
        $sessions = SessionMasterController::getSessions(['id','session']);
        return view('admin.reports.fee_report_admin', compact('sessions'));
    }

    /**
     * fee Report Admin
     */

    public function feeReportAdmin(Request $request)
    {
        try {
            // Validate input
            $rules = [
                'session' => 'required|exists:session_masters,id,active,1',
                'feeType' => 'required',
                'reportType' => 'required',
            ];
            if (isset($request->startDate) || isset($request->endDate)) {
                # code...
                $rules['startDate'] = [
                    'required',
                    function ($attribute, $value, $fail) use ($request) {
                        if (isset($request->endDate) && $value > $request->endDate) {
                            $fail('Start Date must be less then or equal to End Date.');
                        }
                    },
                    'exists:fee_details,pay_date',
                ];
                $rules['endDate'] =
                    [
                        'required_if:startDate, true',
                        function ($attribute, $value, $fail) use ($request) {
                            if (isset($request->startDate) && $value < $request->startDate) {
                                $fail('Start Date must be greater then or equal to Start Date.');
                            }
                        },
                        // 'exists:fee_details,pay_date',
                    ];
            }
            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $validator->errors()
                ], 400);
            }

            $startDate = $request->startDate;
            $endDate = $request->endDate;
            $reportType = $request->reportType;
            $session = $request->session;
            $academic_trans = $request->feeType == 1 ? 1 : 2;

            // Build the query
            $feeDetails = FeeDetail::where('session_id', $session)
                ->where('academic_trans', $academic_trans)
                ->where('paid_mercy', 1)
                ->where('active', 1)->orderBy('srno', 'asc')->orderBy('ref_slip_no', 'asc');

            // Apply date filters if provided
            if ($startDate && $endDate) {
                $feeDetails->whereBetween('pay_date', [$startDate, $endDate]);
            }

            $report = [];

            if ($reportType == 1) {
                $summeryAmount = $feeDetails->sum('amount');
                $report[] = [
                    'summeryAmount' => $summeryAmount > 0 ? $summeryAmount : 'No any Fee Entry Found.',
                ];
            } else {
                // $baseQuery = $this->getStudentDetails();
                $baseQuery = StudentMasterController::getStdWithNames(false, []);

                $feeDetailsQuery = isset($request->page) ?
                    $feeDetails->paginate(10) :
                    $feeDetails->get();

                $studentSrnos = $feeDetailsQuery->pluck('srno')->unique();

                $studentsData = $baseQuery->whereIn('stu_main_srno.srno', $studentSrnos)
                    // ->where('ssid', 1)
                    ->where('session_id', $session)
                    ->get(['stu_main_srno.srno', 'class_name', 'section_name', 'student_name', 'f_name', 'school'])
                    ->groupBy('srno');

                // Map fee details to students
                foreach ($feeDetailsQuery as $feeDetail) {
                    $matchingStudents = $studentsData->get($feeDetail->srno, collect([]));

                    foreach ($matchingStudents as $student) {
                        $report[] = [
                            'school' => $student->school,
                            'name' => $student->student_name,
                            'f_name' => $student->f_name,
                            'class_name' => $student->class_name,
                            'section_name' => $student->section_name,
                            'feeDetails' => $feeDetail,
                        ];
                    }
                }

                // Calculate total amount
                $report['grandTotal'] = $feeDetails->sum('amount');
            }

            // Return the full report
            return response()->json([
                'status' => 'success',
                'data' => $report,
                'pagination' => isset($request->page) && $reportType == 2 ?
                    [
                        'total' => $feeDetailsQuery->total(),
                        'per_page' => $feeDetailsQuery->perPage(),
                        'current_page' => $feeDetailsQuery->currentPage(),
                        'last_page' => $feeDetailsQuery->lastPage(),
                        'from' => $feeDetailsQuery->firstItem(),
                        'to' => $feeDetailsQuery->lastItem(),
                    ] : [],
            ], 200);
        } catch (\Exception $e) {
            // Catch any unexpected errors
            return response()->json([
                'error' => 'An error occurred. Please try again.',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     *fee Report Admin Excel
     */
    public function feeReportAdminExcel(Request $request)
    {
        try {
            // Call the newAdmissionReportByCategory function to get the report data
            $response = $this->feeReportAdmin($request);

            // Check if the response is successful and contains data
            if ($response->getStatusCode() !== 200) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to generate report: ' . $response->getContent()
                ], 500);
            }

            // Get the report data from the response
            $reportData = json_decode($response->getContent(), true)['data'];

            // Set the file name for the exported CSV file
            $fileName = 'fee_admin_full_report.csv';


            $output = fopen('php://memory', 'w');
            if ($output === false) {
                throw new \Exception('Failed to open output stream.');
            }

            // Set the CSV column headers
            $headers = ['S.No.', 'Pay Date', 'Ref. Slip No.', 'C. Slip No.', 'SRNO', 'Class', 'Section', 'Name', "Father's Name", 'Amount (Rs.)'];
            fputcsv($output, $headers);

            // Separate students into junior and senior sections
            $juniorStudents = [];
            $seniorStudents = [];
            $juniorTotal = 0;
            $seniorTotal = 0;

            // Group students
            foreach ($reportData as $key => $row) {
                if ($key === 'grandTotal') continue;
                $studentData = $row;
                if ($studentData['school'] == 1) {
                    array_push($juniorStudents, $studentData);
                    $juniorTotal += floatval($studentData['feeDetails']['amount'] ?? 0);
                } else {
                    array_push($seniorStudents, $studentData);
                    $seniorTotal += floatval($studentData['feeDetails']['amount'] ?? 0);
                }
            }

            // Write Junior Section
            if (count($juniorStudents) > 0) {
                fputcsv($output, ['Junior Section']);
                $counter = 1;

                foreach ($juniorStudents as $studentData) {
                    fputcsv($output, [
                        $counter++,
                        $studentData['feeDetails']['pay_date'] ?? '-',
                        $studentData['feeDetails']['ref_slip_no'] ?? '-',
                        $studentData['feeDetails']['recp_no'] ?? '-',
                        $studentData['feeDetails']['srno'] ?? '-',
                        $studentData['class_name'] ?? '-',
                        $studentData['section_name'] ?? '-',
                        $studentData['name'] ?? '-',
                        $studentData['f_name'] ?? '-',
                        $studentData['feeDetails']['amount'] ?? 0
                    ]);
                }

                // Add Junior Section Total
                fputcsv($output, ['', '', '', '', '', '', '', '', 'Junior Section Total:', $juniorTotal]);
            }

            // Write Senior Section
            if (count($seniorStudents) > 0) {
                fputcsv($output, ['Senior Section']);

                foreach ($seniorStudents as $index => $studentData) {
                    fputcsv($output, [
                        $counter++,
                        $studentData['feeDetails']['pay_date'] ?? '-',
                        $studentData['feeDetails']['ref_slip_no'] ?? '-',
                        $studentData['feeDetails']['recp_no'] ?? '-',
                        $studentData['feeDetails']['srno'] ?? '-',
                        $studentData['class_name'] ?? '-',
                        $studentData['section_name'] ?? '-',
                        $studentData['name'] ?? '-',
                        $studentData['f_name'] ?? '-',
                        $studentData['feeDetails']['amount'] ?? 0
                    ]);
                }

                // Add Senior Section Total
                fputcsv($output, ['', '', '', '', '', '', '', '', 'Senior Section Total:', $seniorTotal]);
            }

            // Add Grand Total
            if (isset($reportData['grandTotal'])) {
                fputcsv($output, ['', '', '', '', '', '', '', '', 'Grand Total:', $reportData['grandTotal']]);
            }
            // Rewind the memory stream to the beginning
            rewind($output);

            // Get the contents of the memory stream (CSV content)
            $csvContent = stream_get_contents($output);

            // Close the memory stream
            fclose($output);

            // Return the CSV content as a response
            return response($csvContent, 200)
                    ->header('Content-Type', 'text/csv')
                    ->header('Content-Disposition', 'attachment; filename="' . $fileName . '"');


        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => "Failed to export report: "
            ], 500);
        }
    }
    /**
     * Fee Report (Mercy) Admin View
     */

    public function feeReportMercyAdminView()
    {
        $sessions = SessionMasterController::getSessions(['id','session']);
        return view('admin.reports.mercy_fee_report_admin', compact('sessions'));
    }

    /**
     * fee Report Admin
     */

    public function feeReportMercyAdmin(Request $request)
    {
        try {
            // Validate input
            $rules = [
                'session' => 'required|exists:session_masters,id,active,1',
                'feeType' => 'required',
                'reportType' => 'required',
            ];
            if (isset($request->startDate) || isset($request->endDate)) {
                # code...
                $rules['startDate'] = [
                    'required',
                    function ($attribute, $value, $fail) use ($request) {
                        if (isset($request->endDate) && $value > $request->endDate) {
                            $fail('Start Date must be less then or equal to End Date.');
                        }
                    },
                    'exists:fee_details,pay_date',
                ];
                $rules['endDate'] =
                    [
                        'required_if:startDate, true',
                        function ($attribute, $value, $fail) use ($request) {
                            if (isset($request->startDate) && $value < $request->startDate) {
                                $fail('Start Date must be greater then or equal to Start Date.');
                            }
                        },
                        // 'exists:fee_details,pay_date',
                    ];
            }
            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $validator->errors()
                ], 400);
            }

            $startDate = $request->startDate;
            $endDate = $request->endDate;
            $reportType = $request->reportType;
            $session = $request->session;
            $academic_trans = $request->feeType == 1 ? 1 : 2;

            // Build the query
            $feeDetails = FeeDetail::where('session_id', $session)
                ->where('academic_trans', $academic_trans)
                ->where('paid_mercy', 2)
                ->where('active', 1)->orderBy('srno', 'asc')->orderBy('ref_slip_no', 'asc');

            // Apply date filters if provided
            if ($startDate && $endDate) {
                $feeDetails->whereBetween('pay_date', [$startDate, $endDate]);
            }

            $report = [];

            if ($reportType == 1) {
                $summeryAmount = $feeDetails->sum('amount');
                $report[] = [
                    'summeryAmount' => $summeryAmount > 0 ? $summeryAmount : 'No any Fee Entry Found.',
                ];
            } else {
                // $baseQuery = $this->getStudentDetails();
                $baseQuery = StudentMasterController::getStdWithNames(false, []);

                $feeDetailsQuery = isset($request->page) ?
                    $feeDetails->paginate(10) :
                    $feeDetails->get();

                $studentSrnos = $feeDetailsQuery->pluck('srno')->unique();

                $studentsData = $baseQuery->whereIn('stu_main_srno.srno', $studentSrnos)
                    // ->where('ssid', 1)
                    // ->whereIn('ssid', [1,2,4,5])
                    ->where('session_id', $session)
                    ->get(['stu_main_srno.srno', 'class_name', 'section_name', 'student_name', 'f_name', 'school'])
                    ->groupBy('srno');

                // Map fee details to students
                foreach ($feeDetailsQuery as $feeDetail) {
                    $matchingStudents = $studentsData->get($feeDetail->srno, collect([]));

                    foreach ($matchingStudents as $student) {
                        $report[] = [
                            'school' => $student->school,
                            'name' => $student->student_name,
                            'f_name' => $student->f_name,
                            'class_name' => $student->class_name,
                            'section_name' => $student->section_name,
                            'feeDetails' => $feeDetail,
                        ];
                    }
                }

                // Calculate total amount
                $report['grandTotal'] = $feeDetails->sum('amount');
            }

            // Return the full report
            return response()->json([
                'status' => 'success',
                'data' => $report,
                'pagination' => isset($request->page) && $reportType == 2 ?
                    [
                        'total' => $feeDetailsQuery->total(),
                        'per_page' => $feeDetailsQuery->perPage(),
                        'current_page' => $feeDetailsQuery->currentPage(),
                        'last_page' => $feeDetailsQuery->lastPage(),
                        'from' => $feeDetailsQuery->firstItem(),
                        'to' => $feeDetailsQuery->lastItem(),
                    ] : [],
            ], 200);
        } catch (\Exception $e) {
            // Catch any unexpected errors
            return response()->json([
                'error' => 'An error occurred. Please try again.',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     *fee Report Admin Excel
     */
    public function feeReportMercyAdminExcel(Request $request)
    {
        try {
            // Call the newAdmissionReportByCategory function to get the report data
            $response = $this->feeReportMercyAdmin($request);

            // Check if the response is successful and contains data
            if ($response->getStatusCode() !== 200) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to generate report: ' . $response->getContent()
                ], 500);
            }

            // Get the report data from the response
            $reportData = json_decode($response->getContent(), true)['data'];

            // Set the file name for the exported CSV file
            $fileName = 'mercy_fee_report.csv';

            // Open the output stream for writing to the CSV file
            // $output = fopen('php://output', 'w');
            // Open the output stream for writing to the CSV file
            $output = fopen('php://memory', 'w');
            if ($output === false) {
                throw new \Exception('Failed to open output stream.');
            }

            // Set the CSV column headers
            $headers = ['S.No.', 'Pay Date', 'Ref. Slip No.', 'C. Slip No.', 'SRNO', 'Class', 'Section', 'Name', "Father's Name", 'Amount (Rs.)'];
            fputcsv($output, $headers);

            // Separate students into junior and senior sections
            $juniorStudents = [];
            $seniorStudents = [];
            $juniorTotal = 0;
            $seniorTotal = 0;

            // Group students
            foreach ($reportData as $key => $row) {
                if ($key === 'grandTotal') continue;
                $studentData = $row;
                if ($studentData['school'] == 1) {
                    array_push($juniorStudents, $studentData);
                    $juniorTotal += floatval($studentData['feeDetails']['amount'] ?? 0);
                } else {
                    array_push($seniorStudents, $studentData);
                    $seniorTotal += floatval($studentData['feeDetails']['amount'] ?? 0);
                }
            }

            // Write Junior Section
            $counter = 1;
            if (count($juniorStudents) > 0) {
                fputcsv($output, ['Junior Section']);

                foreach ($juniorStudents as $studentData) {
                    fputcsv($output, [
                        $counter++,
                        $studentData['feeDetails']['pay_date'] ?? '-',
                        $studentData['feeDetails']['ref_slip_no'] ?? '-',
                        $studentData['feeDetails']['recp_no'] ?? '-',
                        $studentData['feeDetails']['srno'] ?? '-',
                        $studentData['class_name'] ?? '-',
                        $studentData['section_name'] ?? '-',
                        $studentData['name'] ?? '-',
                        $studentData['f_name'] ?? '-',
                        $studentData['feeDetails']['amount'] ?? 0
                    ]);
                }

                // Add Junior Section Total
                fputcsv($output, ['', '', '', '', '', '', '', '', 'Junior Section Total:', $juniorTotal]);
            }

            // Write Senior Section
            if (count($seniorStudents) > 0) {
                fputcsv($output, ['Senior Section']);

                foreach ($seniorStudents as $index => $studentData) {
                    fputcsv($output, [
                        $counter++,
                        $studentData['feeDetails']['pay_date'] ?? '-',
                        $studentData['feeDetails']['ref_slip_no'] ?? '-',
                        $studentData['feeDetails']['recp_no'] ?? '-',
                        $studentData['feeDetails']['srno'] ?? '-',
                        $studentData['class_name'] ?? '-',
                        $studentData['section_name'] ?? '-',
                        $studentData['name'] ?? '-',
                        $studentData['f_name'] ?? '-',
                        $studentData['feeDetails']['amount'] ?? 0
                    ]);
                }

                // Add Senior Section Total
                fputcsv($output, ['', '', '', '', '', '', '', '', 'Senior Section Total:', $seniorTotal]);
            }

            // Add Grand Total
            if (isset($reportData['grandTotal'])) {
                fputcsv($output, ['', '', '', '', '', '', '', '', 'Grand Total:', $reportData['grandTotal']]);
            }
            // Rewind the memory stream to the beginning
            rewind($output);

            // Get the contents of the memory stream (CSV content)
            $csvContent = stream_get_contents($output);

            // Close the memory stream
            fclose($output);

            // Return the CSV content as a response
            return response($csvContent, 200)
                    ->header('Content-Type', 'text/csv')
                    ->header('Content-Disposition', 'attachment; filename="' . $fileName . '"');

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => "Failed to export report: "
            ], 500);
        }
    }

    /**
     * Miss Fields Report View
     */

    public function missFieldsReportView()
    {
        return view('admin.reports.miss_fields_records');
    }

    /**
     * Miss Fields Report
     */

    public function missFieldsReport(Request $request)
    {
        try {
            //code...
            $validator = Validator::make($request->all(), [
                'session' => 'required|exists:session_masters,id,active,1',
                'field' => 'required',

            ]);
            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $validator->errors()
                ], 400);
            }
            // $baseQuery = $this->getStudentDetails();
            $classId = explode(',', $request->class);
            $sectionId = explode(',', $request->section);
            $field = $request->field;
            $session = $request->session;

            if (filled($classId) && filled($sectionId) && filled($field)) {
                $fields = [
                        'stu_main_srno.id',
                        'stu_main_srno.srno',
                        'stu_main_srno.school',
                        'stu_main_srno.class',
                        'stu_main_srno.section',
                        'stu_main_srno.prev_srno',
                        'stu_main_srno.admission_date',
                        'stu_main_srno.rollno',
                        'stu_main_srno.age_proof',
                        'stu_main_srno.session_id',
                        'stu_main_srno.ssid',
                        'stu_main_srno.active',
                        'class_masters.class as class_name',
                        'section_masters.section as section_name',
                        'stu_detail.name as student_name',
                        'stu_detail.dob',
                        'stu_detail.address',
                        'parents_detail.f_name',
                        'parents_detail.f_mobile',
                        'parents_detail.m_mobile',
                ];
                $baseQuery = StudentMasterController::getStdWithNames(false, $fields)
                            ->whereIn('stu_main_srno.class', $classId)
                            ->whereIn('stu_main_srno.section', $sectionId)
                            ->where('stu_main_srno.session_id', $session)
                            ->orderBy('stu_main_srno.class', 'asc')->orderBy('stu_main_srno.section', 'asc');

                if ($field == 1) {
                    # code...
                    $students = $baseQuery
                            ->where(function ($query) {
                            $query->whereNull('stu_detail.dob')
                                ->orWhere('stu_detail.dob', '')->orWhere('stu_detail.dob', '1981-01-01');
                            });
                    $heading = 'Date of Birth not available.';
                } elseif ($field == 2) {
                    # code...
                    $students = $baseQuery->where(function ($query) {
                        $query->whereNull('stu_main_srno.admission_date')
                            ->orWhere('stu_main_srno.admission_date', '');
                    })->whereNotIn('stu_main_srno.srno', function ($query) {
                            $query->select('stu_main_srno.srno')
                                ->from('stu_main_srno')
                                ->whereNotNull('admission_date');
                        });
                    $heading = 'Admission Date not available.';
                } elseif ($field == 3) {
                    # code...
                   $students = $baseQuery
                        ->where(function ($query) {
                            $query->where('parents_detail.f_mobile', '')
                                ->orWhereNull('parents_detail.f_mobile');
                        });
                    $heading = 'Mobile No. not available.';
                } elseif ($field == 4) {
                    # code...
                   $students = $baseQuery
                        ->where(function ($query) {
                            $query->whereNull('stu_main_srno.age_proof')
                                ->orWhere('stu_main_srno.age_proof', '0');
                        });
                    $heading = 'Age Proof not available.';
                }
                return response()->json([
                    'status' => 'success',
                    'data' => $request->page ? $students->paginate(10) : $students->get(),
                    'heading' => $heading
                ], 200);
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => "Failed to get transport-wise report: "
            ], 500);
        }
    }


    /**
     * export report miss-fields
     */

    public function exportReportByMissFields(Request $request)
    {
        try {
            // Call the newAdmissionReportByCategory function to get the report data
            $response = $this->missFieldsReport($request);

            // Check if the response is successful and contains data
            if ($response->getStatusCode() !== 200) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to generate report: ' . $response->getContent()
                ], 500);
            }

            // Get the report data from the response
            $reportData = json_decode($response->getContent(), true)['data'];

            // Set the file name for the exported CSV file
            $fileName = 'miss_fields_st_report.csv';


            $output = fopen('php://memory', 'w');
            if ($output === false) {
                throw new \Exception('Failed to open output stream.');
            }

            // Set the CSV column headers
            $headers = ['R.N.', 'REGNO', 'Class', 'Section', 'Name', "Father's Name", 'DOB', 'Address', 'Mobile No.'];
            fputcsv($output, $headers);

            // Write the report data to the CSV file
            foreach ($reportData as $row) {
                fputcsv($output, [
                    $row['rollno'],
                    $row['srno'],
                    $row['class_name'],
                    $row['section_name'],
                    $row['student_name'],
                    $row['f_name'],
                    $row['dob'] ?? '',
                    $row['address'],
                    $row['f_mobile'] ?? '',

                ]);
            }
            // Rewind the memory stream to the beginning
            rewind($output);

            // Get the contents of the memory stream (CSV content)
            $csvContent = stream_get_contents($output);

            // Close the memory stream
            fclose($output);

            // Return the CSV content as a response
            return response($csvContent, 200)
                    ->header('Content-Type', 'text/csv')
                    ->header('Content-Disposition', 'attachment; filename="' . $fileName . '"');


        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => "Failed to export report"
            ], 500);
        }
    }

    /**
     * reprint fee slip view
     */
    public function reprintFeeSlipView()
    {
        return view('admin.reports.reprint_fee_slip');
    }
    /**
     * reprint fee slip
     */
    public function reprintFeeSlip(Request $request)
    {
        $academic_trans_value = $request->academic_trans_value;
        $session = $request->session;
        $slipNo = $request->slip_no;

        $rules = [
            'slip_no' => [
                'required',
                Rule::exists('fee_details', 'recp_no')->where(function ($query) use ($slipNo, $session, $academic_trans_value) {
                    $query->where('recp_no', 'like', '%' . $slipNo . '%')
                        ->where('session_id', $session)
                        ->where('academic_trans', $academic_trans_value)
                        ->where('active', 1);
                })
            ],
            'academic_trans_value' => 'required',
            'session' => 'required'
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()->first()
            ], 422);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Fee Reprint Successfully',
            'print_url' =>  url("admin/print-fee-slip-no?recpNo={$slipNo}&feeId={$academic_trans_value}&session={$session}")
        ], 200);
    }
}
