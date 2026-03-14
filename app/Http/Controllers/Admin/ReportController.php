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
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    //
    public function index()
    {
        return view('admin.reports.index');
    }

    public function newAdmissionReport()
    {
        return view('admin.reports.new_admission.new_admission_report');
    }

    public function newAdmissionReportByDateView()
    {
        $sessions = SessionMasterController::getSessions(['id', 'session']);
        $classes = ClassMasterController::getClasses();
        return view('admin.reports.new_admission.new_admission_report_by_date', compact('sessions', 'classes'));
    }
    public function newAdmissionReportByCategoryView()
    {
        $sessions = SessionMasterController::getSessions(['id', 'session']);
        $classes = ClassMasterController::getClasses();
        return view('admin.reports.new_admission.new_admission_report_by_category', compact('sessions', 'classes'));
    }
    public function newAdmissionReportByReligionView()
    {
        $sessions = SessionMasterController::getSessions(['id', 'session']);
        $classes = ClassMasterController::getClasses();
        return view('admin.reports.new_admission.new_admission_report_by_religion', compact('sessions', 'classes'));
    }
    public function newAdmissionReportByAgeProofView()
    {
        $sessions = SessionMasterController::getSessions(['id', 'session']);
        $classes = ClassMasterController::getClasses();
        return view('admin.reports.new_admission.new_admission_report_by_age_proof', compact('sessions', 'classes'));
    }
    public function newAdmissionReportBetwwenDatesView()
    {
        $sessions = SessionMasterController::getSessions(['id', 'session']);
        $classes = ClassMasterController::getClasses();
        return view('admin.reports.new_admission.new_admission_report_between_dates', compact('sessions', 'classes'));
    }

    public function stdregisterView()
    {
        $sessions = SessionMasterController::getSessions(['id', 'session']);
        return view('admin.reports.sr_register_report', compact('sessions'));
    }


    public function reportAgeWiseView()
    {
        $classes = ClassMasterController::getClasses();
        return view('admin.reports.age_wise_report', compact('classes'));
    }

    public function transportWiseReportView()
    {
        $classes = ClassMasterController::getClasses();
        return view('admin.reports.transport_details_report', compact('classes'));
    }

    public function tcIssueView()
    {
        return view('admin.reports.tc_issue');
    }

    public function newAdmissionReportByDate(Request $request)
    {
        try {
            // ── Validation ────────────────────────────────────────────────
            $validator = Validator::make($request->all(), [
                'session_id' => 'required|exists:session_masters,id,active,1',
                'class'      => [
                    'required',
                    function ($attribute, $value, $fail) {
                        if ($value !== 'all' && !DB::table('class_masters')->where('id', $value)->where('active', 1)->exists()) {
                            $fail('The selected class is invalid.');
                        }
                    },
                ],
            ], [
                'session_id.required' => 'Session is required.',
                'session_id.exists'   => 'The selected session is invalid.',
                'class.required'      => 'Class is required.',
                'class.exists'        => 'The selected class is invalid.'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status'  => 'error',
                    'message' => $validator->errors()
                ], 200);
            }

            $sessionId     = (int) $request->session_id;
            $classId       = $request->class;
            $admissionDate = $request->by_date ?: null;
            $ageProofIds   = array_map('intval', explode(',', $request->age_proof));

            // ── Fetch active classes (one query) ──────────────────────────
            $classQuery = DB::table('class_masters')->where('active', 1)->orderBy('sort');

            if ($classId !== 'all') {
                $classQuery->where('id', (int) $classId);
            }

            $classes = $classQuery->get(['id', 'class']);

            if ($classes->isEmpty()) {
                return response()->json(['status' => 'success', 'data' => []], 200);
            }

            $classIds = $classes->pluck('id')->all();

            // ── Base student query ────────────────────────────────────────
            // Single aggregation query — no PHP-side collection loops
            $baseQuery = DB::table('stu_main_srno')
                ->select(
                    'class',                                         // class FK = class_masters.id
                    'gender',
                    DB::raw('COUNT(*) as total')
                )
                ->where('session_id', $sessionId)
                ->whereIn('age_proof', $ageProofIds)
                ->whereIn('ssid', [1, 2])
                ->where('active', 1)
                ->whereNotNull('admission_date')
                ->whereIn('class', $classIds)
                ->groupBy('class', 'gender');

            if ($admissionDate) {
                // Two aggregations in one query using conditional SUM
                $rows = DB::table('stu_main_srno')
                    ->select(
                        'class',
                        'gender',
                        DB::raw('SUM(CASE WHEN admission_date <= ? THEN 1 ELSE 0 END) as before_count'),
                        DB::raw('SUM(CASE WHEN admission_date >  ? THEN 1 ELSE 0 END) as after_count')
                    )
                    ->addBinding([$admissionDate, $admissionDate], 'select')
                    ->where('session_id', $sessionId)
                    ->whereIn('age_proof', $ageProofIds)
                    ->whereIn('ssid', [1, 2])
                    ->where('active', 1)
                    ->whereNotNull('admission_date')
                    ->whereIn('class', $classIds)
                    ->groupBy('class', 'gender')
                    ->get()
                    ->groupBy('class');     // key = class id

                $report = $classes->map(function ($class) use ($rows) {
                    $genderRows = $rows->get($class->id, collect());

                    $boysBefore  = (int) optional($genderRows->firstWhere('gender', 1))->before_count;
                    $girlsBefore = (int) optional($genderRows->firstWhere('gender', 2))->before_count;
                    $boysAfter   = (int) optional($genderRows->firstWhere('gender', 1))->after_count;
                    $girlsAfter  = (int) optional($genderRows->firstWhere('gender', 2))->after_count;

                    return [
                        'class'  => $class->class,
                        'before' => [
                            'boys'  => $boysBefore,
                            'girls' => $girlsBefore,
                            'total' => $boysBefore + $girlsBefore,
                        ],
                        'after'  => [
                            'boys'  => $boysAfter,
                            'girls' => $girlsAfter,
                            'total' => $boysAfter + $girlsAfter,
                        ],
                    ];
                });
            } else {
                $rows = $baseQuery->get()->groupBy('class');    // key = class id

                $report = $classes->map(function ($class) use ($rows) {
                    $genderRows = $rows->get($class->id, collect());

                    $boys  = (int) optional($genderRows->firstWhere('gender', 1))->total;
                    $girls = (int) optional($genderRows->firstWhere('gender', 2))->total;

                    return [
                        'class' => $class->class,
                        'boys'  => $boys,
                        'girls' => $girls,
                        'total' => $boys + $girls,
                    ];
                });
            }

            return response()->json([
                'status' => 'success',
                'data'   => $report->values(),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to get new admission report.'. $e->getMessage(),
            ], 200);
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
            // ── Validation ────────────────────────────────────────────────
            $validator = Validator::make($request->all(), [
                'session_id' => 'required|exists:session_masters,id,active,1',
                'class'      => [
                    'required',
                    function ($attribute, $value, $fail) {
                        if ($value !== 'all' && !DB::table('class_masters')->where('id', $value)->where('active', 1)->exists()) {
                            $fail('The selected class is invalid.');
                        }
                    },
                ],
            ], [
                'session_id.required' => 'Session is required.',
                'session_id.exists'   => 'The selected session is invalid.',
                'class.required'      => 'Class is required.',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status'  => 'error',
                    'message' => $validator->errors(),
                ], 200);
            }

            $allCategories = [
                1 => 'General',
                2 => 'OBC',
                3 => 'SC',
                4 => 'ST',
                5 => 'BC',
            ];

            $sessionId    = (int) $request->session_id;
            $classId      = $request->class;
            $newAdmission = $request->new_admission;

            // ── Fetch active classes (one query) ──────────────────────────
            $classQuery = DB::table('class_masters')
                ->where('active', 1)
                ->orderBy('sort');

            if ($classId !== 'all') {
                $classQuery->where('id', (int) $classId);
            }

            $classes = $classQuery->get(['id', 'class']);

            if ($classes->isEmpty()) {
                return response()->json(['status' => 'success', 'data' => []], 200);
            }

            $classIds = $classes->pluck('id')->all();

            // ── Single aggregation query — no N+1, no PHP-side loops ──────
            // JOIN stu_main_srno → stu_detail to get category_id in one shot,
            // then GROUP BY class + category_id + gender for counts.
            $aggregateQuery = DB::table('stu_main_srno as sm')
                ->join('stu_detail as sd', function ($join) {
                    $join->on('sd.srno', '=', 'sm.srno')
                         ->where('sd.active', 1);
                })
                ->select(
                    'sm.class',
                    'sd.category_id',
                    'sm.gender',
                    DB::raw('COUNT(*) as total')
                )
                ->where('sm.session_id', $sessionId)
                ->whereIn('sm.ssid', [1, 2])
                ->where('sm.active', 1)
                ->whereIn('sm.class', $classIds)
                ->whereIn('sd.category_id', array_keys($allCategories));

            if ($newAdmission) {
                $aggregateQuery->whereNotNull('sm.admission_date');
            }

            // Result keyed as [class_id][category_id][gender] = count
            $grouped = $aggregateQuery
                ->groupBy('sm.class', 'sd.category_id', 'sm.gender')
                ->get()
                ->groupBy('class');

            // ── Build report from pre-aggregated data ─────────────────────
            $report = $classes->map(function ($class) use ($grouped, $allCategories) {
                $classRows = $grouped->get($class->id, collect());

                // Further group by category for O(1) lookups
                $byCategory = $classRows->groupBy('category_id');

                $categories = [];
                foreach ($allCategories as $categoryId => $categoryName) {
                    $categoryRows = $byCategory->get($categoryId, collect());

                    $boys  = (int) optional($categoryRows->firstWhere('gender', 1))->total;
                    $girls = (int) optional($categoryRows->firstWhere('gender', 2))->total;

                    $categories[] = [
                        'category_id'   => $categoryId,
                        'category_name' => $categoryName,
                        'boys'          => $boys,
                        'girls'         => $girls,
                        'totalStudents' => $boys + $girls,
                    ];
                }

                return [
                    'class'      => $class->class,
                    'categories' => $categories,
                ];
            })->values();

            return response()->json([
                'status' => 'success',
                'data'   => $report,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to get category report.',
            ], 200);
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
            // ── Validation ────────────────────────────────────────────────
            $validator = Validator::make($request->all(), [
                'session_id' => 'required|exists:session_masters,id,active,1',
                'class'      => [
                    'required',
                    function ($attribute, $value, $fail) {
                        if ($value !== 'all' && !DB::table('class_masters')->where('id', $value)->where('active', 1)->exists()) {
                            $fail('The selected class is invalid.');
                        }
                    },
                ],
            ], [
                'session_id.required' => 'Session is required.',
                'session_id.exists'   => 'The selected session is invalid.',
                'class.required'      => 'Class is required.',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status'  => 'error',
                    'message' => $validator->errors(),
                ], 200);
            }

            $allReligions = [
                1 => 'Hindu',
                2 => 'Muslim',
                3 => 'Christian',
                4 => 'Sikh',
            ];

            $sessionId    = (int) $request->session_id;
            $classId      = $request->class;
            $newAdmission = $request->new_admission;

            // ── Fetch active classes (one query) ──────────────────────────
            $classQuery = DB::table('class_masters')
                ->where('active', 1)
                ->orderBy('sort');

            if ($classId !== 'all') {
                $classQuery->where('id', (int) $classId);
            }

            $classes = $classQuery->get(['id', 'class']);

            if ($classes->isEmpty()) {
                return response()->json(['status' => 'success', 'data' => []], 200);
            }

            $classIds = $classes->pluck('id')->all();

            // ── Single aggregation query — religion is a column on stu_main_srno ──
            // No JOIN needed unlike category — religion is stored directly on the student row.
            $aggregateQuery = DB::table('stu_main_srno')
                ->select(
                    'class',
                    'religion',
                    'gender',
                    DB::raw('COUNT(*) as total')
                )
                ->where('session_id', $sessionId)
                ->whereIn('ssid', [1, 2])
                ->where('active', 1)
                ->whereIn('class', $classIds)
                ->whereIn('religion', array_keys($allReligions));

            if ($newAdmission) {
                $aggregateQuery->whereNotNull('admission_date');
            }

            // Result keyed as [class_id][religion_id][gender] = count
            $grouped = $aggregateQuery
                ->groupBy('class', 'religion', 'gender')
                ->get()
                ->groupBy('class');

            // ── Build report from pre-aggregated data ─────────────────────
            $report = $classes->map(function ($class) use ($grouped, $allReligions) {
                $classRows = $grouped->get($class->id, collect());

                // Further group by religion for O(1) lookups
                $byReligion = $classRows->groupBy('religion');

                $religions = [];
                foreach ($allReligions as $religionId => $religionName) {
                    $religionRows = $byReligion->get($religionId, collect());

                    $boys  = (int) optional($religionRows->firstWhere('gender', 1))->total;
                    $girls = (int) optional($religionRows->firstWhere('gender', 2))->total;

                    $religions[] = [
                        'religion_id'   => $religionId,
                        'religion_name' => $religionName,
                        'boys'          => $boys,
                        'girls'         => $girls,
                        'totalStudents' => $boys + $girls,
                    ];
                }

                return [
                    'class'     => $class->class,
                    'religions' => $religions,
                ];
            })->values();

            return response()->json([
                'status' => 'success',
                'data'   => $report,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to get religion report.',
            ], 200);
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
            // ── Validation ────────────────────────────────────────────────
            $validator = Validator::make($request->all(), [
                'session_id' => 'required|exists:session_masters,id,active,1',
                'class'      => [
                    'required',
                    function ($attribute, $value, $fail) {
                        if ($value !== 'all' && !DB::table('class_masters')->where('id', $value)->where('active', 1)->exists()) {
                            $fail('The selected class is invalid.');
                        }
                    },
                ],
            ], [
                'session_id.required' => 'Session is required.',
                'session_id.exists'   => 'The selected session is invalid.',
                'class.required'      => 'Class is required.',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status'  => 'error',
                    'message' => $validator->errors(),
                ], 200);
            }

            $allAgeProofs = [
                1 => 'By TC',
                2 => 'By Birth Cert.',
                3 => 'By Affidavit',
                4 => 'By Aadhar Card',
                0 => 'Without Proof',
            ];

            $sessionId    = (int) $request->session_id;
            $classId      = $request->class;
            $newAdmission = $request->new_admission;

            // ── Fetch active classes (one query) ──────────────────────────
            $classQuery = DB::table('class_masters')
                ->where('active', 1)
                ->orderBy('sort');

            if ($classId !== 'all') {
                $classQuery->where('id', (int) $classId);
            }

            $classes = $classQuery->get(['id', 'class']);

            if ($classes->isEmpty()) {
                return response()->json(['status' => 'success', 'data' => []], 200);
            }

            $classIds = $classes->pluck('id')->all();

            // ── Single aggregation query — no PHP-side loops ──────────────
            // age_proof is a direct column on stu_main_srno (0–4),
            // so no JOIN needed — same pattern as religion report.
            // Note: age_proof = 0 means "Without Proof" (NULL or 0).
            $aggregateQuery = DB::table('stu_main_srno')
                ->select(
                    'class',
                    DB::raw('COALESCE(age_proof, 0) as age_proof'),  // treat NULL as 0 = Without Proof
                    'gender',
                    DB::raw('COUNT(*) as total')
                )
                ->where('session_id', $sessionId)
                ->whereIn('ssid', [1, 2])           // fix: original used ->where('ssid', [1,2]) which generates WHERE ssid = Array
                ->where('active', 1)
                ->whereIn('class', $classIds);

            if ($newAdmission) {
                $aggregateQuery->whereNotNull('admission_date');
            }

            // Result keyed as [class_id][age_proof_id][gender] = count
            $grouped = $aggregateQuery
                ->groupBy('class', DB::raw('COALESCE(age_proof, 0)'), 'gender')
                ->get()
                ->groupBy('class');

            // ── Build report from pre-aggregated data ─────────────────────
            $report = $classes->map(function ($class) use ($grouped, $allAgeProofs) {
                $classRows = $grouped->get($class->id, collect());

                // Further group by age_proof for O(1) lookups
                $byAgeProof = $classRows->groupBy('age_proof');

                $ageProofs = [];
                foreach ($allAgeProofs as $ageProofId => $ageProofName) {
                    $ageProofRows = $byAgeProof->get($ageProofId, collect());

                    $boys  = (int) optional($ageProofRows->firstWhere('gender', 1))->total;
                    $girls = (int) optional($ageProofRows->firstWhere('gender', 2))->total;

                    $ageProofs[] = [
                        'age_proof_id'   => $ageProofId,
                        'age_proof_name' => $ageProofName,
                        'boys'           => $boys,
                        'girls'          => $girls,
                        'totalStudents'  => $boys + $girls,
                    ];
                }

                return [
                    'class'     => $class->class,
                    'ageProofs' => $ageProofs,
                ];
            })->values();

            return response()->json([
                'status' => 'success',
                'data'   => $report,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to get age proof report.',
            ], 200);
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
            // ── Validation ────────────────────────────────────────────────
            $validator = Validator::make($request->all(), [
                'session_id' => 'required|exists:session_masters,id,active,1',
                'class'      => [
                    'required',
                    function ($attribute, $value, $fail) {
                        if ($value !== 'all' && !DB::table('class_masters')->where('id', $value)->where('active', 1)->exists()) {
                            $fail('The selected class is invalid.');
                        }
                    },
                ],
                'startDate'  => ['nullable', 'date', 'before_or_equal:endDate'],
                'endDate'    => ['nullable', 'date', 'after_or_equal:startDate'],
            ], [
                'session_id.required'      => 'Session is required.',
                'session_id.exists'        => 'The selected session is invalid.',
                'class.required'           => 'Class is required.',
                'startDate.date'           => 'Start date must be a valid date.',
                'startDate.before_or_equal'=> 'Start date must not be greater than end date.',
                'endDate.date'             => 'End date must be a valid date.',
                'endDate.after_or_equal'   => 'End date must not be less than start date.',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status'  => 'error',
                    'message' => $validator->errors(),
                ], 200);
            }

            $sessionId  = (int) $request->session_id;
            $classId    = $request->class;
            $startDate  = $request->startDate ?: null;
            $endDate    = $request->endDate   ?: null;

            // ── Fetch active classes (one query) ──────────────────────────
            $classQuery = DB::table('class_masters')
                ->where('active', 1)
                ->orderBy('sort');

            if ($classId !== 'all') {
                $classQuery->where('id', (int) $classId);
            }

            $classes = $classQuery->get(['id', 'class']);

            if ($classes->isEmpty()) {
                return response()->json(['status' => 'success', 'data' => []], 200);
            }

            $classIds = $classes->pluck('id')->all();

            // ── Single aggregation query ───────────────────────────────────
            $aggregateQuery = DB::table('stu_main_srno')
                ->select(
                    'class',
                    'gender',
                    DB::raw('COUNT(*) as total')
                )
                ->where('session_id', $sessionId)
                ->whereIn('ssid', [1, 2])
                ->where('active', 1)
                ->whereNotNull('admission_date')
                ->whereIn('class', $classIds);

            if ($startDate && $endDate) {
                $aggregateQuery->whereBetween('admission_date', [$startDate, $endDate]);
            } elseif ($startDate) {
                $aggregateQuery->where('admission_date', '>=', $startDate);
            } elseif ($endDate) {
                $aggregateQuery->where('admission_date', '<=', $endDate);
            }

            $grouped = $aggregateQuery
                ->groupBy('class', 'gender')
                ->get()
                ->groupBy('class');

            // ── Build report from pre-aggregated data ─────────────────────
            $report = $classes->map(function ($class) use ($grouped) {
                $classRows = $grouped->get($class->id, collect());

                $boys  = (int) optional($classRows->firstWhere('gender', 1))->total;
                $girls = (int) optional($classRows->firstWhere('gender', 2))->total;

                return [
                    'class' => $class->class,
                    'boys'  => $boys,
                    'girls' => $girls,
                    'total' => $boys + $girls,
                ];
            })->values();

            return response()->json([
                'status' => 'success',
                'data'   => $report,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to get admission report.',
            ], 200);
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
            $current_session = Session::get('current_session');

            $validator = Validator::make($request->all(), [
                'class' => 'required',
                'date'  => 'required|date',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status'  => 'error',
                    'message' => $validator->errors()
                ], 400);
            }

            $classID   = $request->class;
            $sessionId = $current_session->id;
            $calcDate  = Carbon::parse($request->date);

            // ─── Load classes ─────────────────────────────────────────────
            // Pass null when "all" is selected so no class filter is applied
            $allClasses = ClassMasterController::getClasses(
                ['id', 'class'],
                null,
                false,
                $classID !== 'all' ? ['id' => $classID] : [],   // ← empty = no filter
                'whereIn',
                true
            );

            // ─── Pre-load all DOBs in one query (eliminates N+1) ──────────
            // Collect every srno we'll need across all classes first
            $allSrnos = StudentMaster::where('session_id', $sessionId)
                ->when($classID !== 'all', fn($q) => $q->where('class', $classID))
                ->whereIn('ssid', [1, 2, 4, 5])
                ->where('active', 1)
                ->pluck('gender', 'srno');   // [ srno => gender ]

            $dobMap = DB::table('stu_detail')
                ->whereIn('srno', $allSrnos->keys())
                ->where('active', 1)
                ->pluck('dob', 'srno');      // [ srno => dob ]

            // ─── Build report per class ────────────────────────────────────
            $report = $allClasses->map(function ($class) use (
                $sessionId, $calcDate, $dobMap, $allSrnos
            ) {
                // Students belonging to this class only
                $students = StudentMaster::where('session_id', $sessionId)
                    ->where('class', $class->id)
                    ->whereIn('ssid', [1, 2, 4, 5])
                    ->where('active', 1)
                    ->pluck('gender', 'srno');

                $ageGroups = [
                    'lessThanFive'    => ['boys' => 0, 'girls' => 0],
                    'equalToFive'     => ['boys' => 0, 'girls' => 0],
                    'equalToSix'      => ['boys' => 0, 'girls' => 0],
                    'equalToSeven'    => ['boys' => 0, 'girls' => 0],
                    'equalToEight'    => ['boys' => 0, 'girls' => 0],
                    'equalToNine'     => ['boys' => 0, 'girls' => 0],
                    'equalToTen'      => ['boys' => 0, 'girls' => 0],
                    'equalToEleven'   => ['boys' => 0, 'girls' => 0],
                    'equalToTwelve'   => ['boys' => 0, 'girls' => 0],
                    'equalToThirteen' => ['boys' => 0, 'girls' => 0],
                    'equalToFourteen' => ['boys' => 0, 'girls' => 0],
                    'equalToFifteen'  => ['boys' => 0, 'girls' => 0],
                    'equalToSixteen'  => ['boys' => 0, 'girls' => 0],
                    'aboveToSixteen'  => ['boys' => 0, 'girls' => 0],
                ];

                foreach ($students as $srno => $gender) {
                    $dob = $dobMap->get($srno);
                    if (!$dob) continue;

                    $age = (int) Carbon::parse($dob)->diffInYears($calcDate);

                    $ageGroup = match (true) {
                        $age < 5    => 'lessThanFive',
                        $age === 5  => 'equalToFive',
                        $age === 6  => 'equalToSix',
                        $age === 7  => 'equalToSeven',
                        $age === 8  => 'equalToEight',
                        $age === 9  => 'equalToNine',
                        $age === 10 => 'equalToTen',
                        $age === 11 => 'equalToEleven',
                        $age === 12 => 'equalToTwelve',
                        $age === 13 => 'equalToThirteen',
                        $age === 14 => 'equalToFourteen',
                        $age === 15 => 'equalToFifteen',
                        $age === 16 => 'equalToSixteen',
                        default     => 'aboveToSixteen',
                    };

                    $genderKey = $gender == 1 ? 'boys' : 'girls';
                    $ageGroups[$ageGroup][$genderKey]++;
                }

                return [
                    'class'     => $class->class,
                    'ageGroups' => $ageGroups,
                ];
            });

            return response()->json([
                'status' => 'success',
                'data'   => $report,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to get age-wise report: ' . $e->getMessage(),
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
            $current_session = Session::get('current_session');

            $validator = Validator::make($request->all(), [
                'class' => 'required',
                'date'  => 'required|date',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status'  => 'error',
                    'message' => $validator->errors()
                ], 400);
            }

            $classID   = $request->class;
            $sessionId = $current_session->id;
            $calcDate  = Carbon::parse($request->date);

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
                $query =  DB::table('stu_main_srno')
                        ->select($fields)
                        ->leftJoin('stu_detail', 'stu_main_srno.srno', '=', 'stu_detail.srno')
                        ->leftJoin('parents_detail', 'stu_main_srno.srno', '=', 'parents_detail.srno')
                        ->leftJoin('class_masters', 'stu_main_srno.class', '=', 'class_masters.id')
                        ->leftJoin('section_masters', 'stu_main_srno.section', '=', 'section_masters.id')
                        ->where('stu_main_srno.session_id', $sessionId)
                        ->whereIn('stu_main_srno.ssid', [1, 2, 4, 5])
                        ->where('stu_main_srno.active', 1);


            if ($classID !== 'all') {
                $query->where('stu_main_srno.class', $classID);
            }
            // ─── Shared transform: map DB row → report array ──────────────
            $transform = function ($student) use ($calcDate) {
                $age = $student->dob
                    ? (int) Carbon::parse($student->dob)->diffInYears($calcDate)
                    : null;

                return [
                    'class'   => $student->class_name   ?? '',
                    'section' => $student->section_name ?? '',
                    'srno'    => $student->srno          ?? '',
                    'name'    => $student->name          ?? '',
                    'f_name'  => $student->f_name        ?? '',
                    'm_name'  => $student->m_name        ?? '',
                    'dob'     => $student->dob           ?? '',
                    'age'     => $age,
                    'mobile'  => $student->f_mobile      ?? '',
                ];
            };

            // ─── Paginated mode (AJAX table) ───────────────────────────────
            // Triggered when ?page= is present in the request
            if ($request->filled('page')) {
                    $paginated = $query->paginate(50);

                    $paginated->getCollection()->transform($transform);

                return response()->json([
                    'status' => 'success',
                    'data'   => $paginated,
                ], 200);
            }

            // ─── Full get mode (export / all records) ─────────────────────
            $data = $query->get()->map($transform);

            return response()->json([
                'status' => 'success',
                'data'   => $data,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to get age-wise report: ' . $e->getMessage(),
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
            $validator = Validator::make($request->all(), [
                'class' => 'required',
                'section' => 'required',
                'transport' => 'required'
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $validator->errors()
                ], 400);
            }
            $classId = $request->class;
            $sectionId = $request->section;
            $transport = $request->transport;
            $transportValues = array_map('intval', explode(',', $transport));
            if (filled($classId) && filled($sectionId) && filled($transport)) {
                $fields = [
                            'stu_main_srno.srno',
                            'stu_main_srno.rollno',
                            'class_masters.class as class_name',
                            'section_masters.section as section_name',
                            'stu_detail.name as student_name',
                            'stu_detail.dob',
                            'stu_detail.address',
                            'parents_detail.f_name',
                            'parents_detail.f_mobile',
                            'stu_main_srno.ssid'
                        ];
                $query =  DB::table('stu_main_srno')
                        ->select($fields)
                        ->leftJoin('stu_detail', 'stu_main_srno.srno', '=', 'stu_detail.srno')
                        ->leftJoin('parents_detail', 'stu_main_srno.srno', '=', 'parents_detail.srno')
                        ->leftJoin('class_masters', 'stu_main_srno.class', '=', 'class_masters.id')
                        ->leftJoin('section_masters', 'stu_main_srno.section', '=', 'section_masters.id')
                        ->whereIn('stu_main_srno.transport', $transportValues)
                        ->where('stu_main_srno.active', 1)
                        ->where('stu_main_srno.ssid', 1);


                if ($classId != 'all') {
                    $query->where('stu_main_srno.class', $classId);
                }
                if ($sectionId != 'all') {
                    $query->where('stu_main_srno.section', $sectionId);
                }
            $data = $query->orderBy('class_masters.sort', 'asc')->orderBy('section_masters.id', 'asc')->orderBy('stu_main_srno.rollno', 'asc');
                return response()->json([
                    'status' => 'success',
                    'data' => $request->page ? $data->paginate(50) : $data->get(),
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
                    'data' => $request->page ? $data->paginate(50) : $data->get(),
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
            $validator = Validator::make($request->all(), [
                'srno' => 'required|string',
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $validator->errors()
                ], 200);
            }

            $tables = [];
            $srno = $request->srno;
            // Student Details Table
            $student = StudentMasterController::getStd(['stu_main_srno.srno', 'stu_main_srno.session_id'], [
                'where' => ['stu_main_srno.srno' => $srno],
                'whereNot' => ['stu_main_srno.session_id' => $request->session]
            ])->first();

            if (!$student) {
                return response()->json([
                    'status' => 'success',
                    'tables' => $tables
                ]);
            }
            $studentDetails = DB::table('stu_detail')
                ->where('srno', $student->srno)
                ->where('active', 1)
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
                        'admission_date' => $row->admission_date ? Carbon::parse($row->admission_date)->format('d-M-Y') : ($row->form_submit_date ? Carbon::parse($row->form_submit_date)->format('d-M-Y') : '')
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
            ], 200);
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
                    $active = $student->active;
                    $activeStatus = $student->ssid;

                    // Set appropriate message based on the `uid` and `active` status
                    if ($active == 1) {
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
                    } elseif ($active == 2) {
                        $response['message'] = "Last Class Passed TC Issued.";
                    } elseif ($active == 3) {
                        $response['message'] = "Studying Currently But Last Class Passed TC Issued.";
                    } elseif ($active == 4) {
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
            $validator = Validator::make($request->all(), [
                'session' => 'required|exists:session_masters,id,active,1',
                'class'   => 'nullable|exists:class_masters,id,active,1',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status'  => 'error',
                    'message' => $validator->errors(),
                ], 400);
            }

            $sessionId = $request->session;

            // ── Parse class safely (empty string → no filter = all classes) ───────
            $classIds = array_filter(array_map('trim', explode(',', $request->class ?? '')));

            $fields = [
                'stu_main_srno.srno',
                'stu_main_srno.class',
                'stu_main_srno.ssid',
                'stu_main_srno.active',
                'stu_main_srno.session_id',
                'class_masters.sort',
                'stu_detail.name',
                'parents_detail.f_name',
            ];

            // ── Build query ───────────────────────────────────────────────────────
            $query = StudentMasterController::getStdWithNames(false, $fields)
                ->where('stu_main_srno.session_id', $sessionId)
                ->where(function ($q) {
                    //  Wrapped in closure so session_id/class scope is preserved
                    $q->where('stu_main_srno.srno', 'like', '%RTE%')
                    ->orWhere('stu_main_srno.is_rtest', 1);
                })
                ->orderBy('class_masters.sort', 'asc');

            if (!empty($classIds)) {
                $query->whereIn('stu_main_srno.class', $classIds);
            }

            // ── Paginate or get all ───────────────────────────────────────────────
            $students = $request->page
                ? $query->paginate(50)
                : $query->get();

            // ── Return paginated object directly (blade expects data.data) ────────
            return response()->json([
                'status' => 'success',
                'data'   => $request->page ? $students : [
                    'data'         => $students,
                    'current_page' => 1,
                    'per_page'     => $students->count(),
                    'total'        => $students->count(),
                    'last_page'    => 1,
                    'from'         => 1,
                    'to'           => $students->count(),
                ],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'An error occurred: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * RTE Student Report in excel file
     */
    public function rteStudentReportExcel(Request $request)
    {
        try {
            // Always fetch full data (no pagination for export)
            $request->merge(['page' => null]);

            $response = $this->rteStudentReport($request);

            if ($response->getStatusCode() !== 200) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Failed to generate report'
                ], 500);
            }

            $decodedResponse = json_decode($response->getContent(), true);

            if (!isset($decodedResponse['data']['data'])) {
                throw new \Exception('Invalid report structure');
            }

            $reportData = $decodedResponse['data']['data'];

            $fileName = 'RTE_st_report_' . date('Y_m_d_H_i_s') . '.csv';

            $output = fopen('php://memory', 'w');

            if ($output === false) {
                throw new \Exception('Failed to open memory stream.');
            }

            // CSV Headers
            fputcsv($output, [
                'S.No.',
                'SRNO',
                'Name',
                'Guardian Name',
                'Category (WS/DG)',
                'Sign. of Certifier',
                'Remark'
            ]);

            foreach ($reportData as $index => $row) {
                fputcsv($output, [
                    $index + 1,
                    $row['srno'] ?? '',
                    $row['name'] ?? '',
                    $row['f_name'] ?? '',
                    '',
                    '',
                    '',
                ]);
            }

            rewind($output);
            $csvContent = stream_get_contents($output);
            fclose($output);

            return response($csvContent, 200)
                ->header('Content-Type', 'text/csv')
                ->header('Content-Disposition', 'attachment; filename="' . $fileName . '"');

        } catch (\Exception $e) {

            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to export report: ' . $e->getMessage()
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
            $baseQuery = StudentMasterController::getStdWithNames(false, []);
            // $baseQuery = $this->getStudentDetails();
            $studentQuery = $baseQuery->whereIn('stu_main_srno.class', $class)
                ->whereIn('stu_main_srno.section', $section)
                ->where('stu_main_srno.session_id', $request->session)->orderBy('class_masters.sort', 'asc');

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
            $students = isset($request->page) ? $studentQuery->paginate(50) : $studentQuery->get();


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
        $sessions = SessionMasterController::getSessions(['id', 'session']);
        return view('admin.reports.fee_report_admin', compact('sessions'));
    }

    /**
     * fee Report Admin
     */

    public function feeReportAdmin(Request $request)
    {
        try {
            $rules = [
                'session'    => 'required|exists:session_masters,id,active,1',
                'feeType'    => 'required',
                'reportType' => 'required',
            ];

            if (isset($request->startDate) || isset($request->endDate)) {
                $rules['startDate'] = [
                    'required',
                    function ($attribute, $value, $fail) use ($request) {
                        if (isset($request->endDate) && $value > $request->endDate) {
                            $fail('Start Date must be less than or equal to End Date.');
                        }
                    },
                    'exists:fee_details,pay_date',
                ];
                $rules['endDate'] = [
                    'required_if:startDate,true',
                    function ($attribute, $value, $fail) use ($request) {
                        if (isset($request->startDate) && $value < $request->startDate) {
                            $fail('End Date must be greater than or equal to Start Date.');
                        }
                    },
                ];
            }

            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                return response()->json([
                    'status'  => 'error',
                    'message' => $validator->errors(),
                ], 400);
            }

            $startDate     = $request->startDate;
            $endDate       = $request->endDate;
            $reportType    = $request->reportType;
            $sessionId     = $request->session;
            $academicTrans = $request->feeType == 1 ? 1 : 2;

            // ── Base fee query ────────────────────────────────────────────────────
            $feeQuery = FeeDetail::where('session_id',     $sessionId)
                ->where('academic_trans', $academicTrans)
                ->where('paid_mercy',     1)
                ->where('active',         1)
                ->orderBy('srno',        'asc')
                ->orderBy('ref_slip_no', 'asc');

            if ($startDate && $endDate) {
                $feeQuery->whereBetween('pay_date', [$startDate, $endDate]);
            }

            // ── Report Type 1: Summary only ───────────────────────────────────────
            if ($reportType == 1) {
                $summaryAmount = (clone $feeQuery)->sum('amount');
                return response()->json([
                    'status'     => 'success',
                    'data'       => [
                        ['summeryAmount' => $summaryAmount > 0 ? $summaryAmount : 'No any Fee Entry Found.'],
                    ],
                    'pagination' => [],
                ], 200);
            }

            // ── Report Type 2: Detailed ───────────────────────────────────────────

            // ── 1. Fetch fee details (paginated or full) in ONE query ─────────────
            $feeDetailsResult = isset($request->page)
                ? (clone $feeQuery)->paginate(50)
                : (clone $feeQuery)->get();

            // ── 2. Grand total in ONE query ───────────────────────────────────────
            $grandTotal = (clone $feeQuery)->sum('amount');

            if ($feeDetailsResult->isEmpty()) {
                return response()->json([
                    'status'     => 'success',
                    'data'       => ['grandTotal' => 0],
                    'pagination' => [],
                ], 200);
            }

            // ── 3. Fetch ALL student details in ONE query ─────────────────────────
            $studentSrnos = $feeDetailsResult->pluck('srno')->unique()->toArray();

            $studentsMap = DB::table('stu_main_srno')
                ->leftJoin('stu_detail',      'stu_main_srno.srno', '=', 'stu_detail.srno')
                ->leftJoin('parents_detail',  'stu_main_srno.srno', '=', 'parents_detail.srno')
                ->leftJoin('class_masters',   'stu_main_srno.class', '=', 'class_masters.id')
                ->leftJoin('session_masters', 'stu_main_srno.session_id', '=', 'session_masters.id')
                ->where('stu_main_srno.session_id', $sessionId)
                ->where('stu_main_srno.active',     1)
                ->whereIn('stu_main_srno.srno',     $studentSrnos)
                ->select(
                    'stu_main_srno.srno',
                    'stu_main_srno.school',
                    'class_masters.class as class_name',
                    'stu_main_srno.section as section_name',
                    'stu_detail.name as student_name',
                    'parents_detail.f_name'
                )
                ->get()
                ->groupBy('srno'); // [srno => collection]

            // ── 4. Build report in memory (zero extra queries) ────────────────────
            $report = [];

            foreach ($feeDetailsResult as $feeDetail) {
                $matchingStudents = $studentsMap->get($feeDetail->srno, collect([]));

                foreach ($matchingStudents as $student) {
                    $report[] = [
                        'school'       => $student->school,
                        'name'         => $student->student_name,
                        'f_name'       => $student->f_name,
                        'class_name'   => $student->class_name,
                        'section_name' => $student->section_name,
                        'feeDetails'   => $feeDetail,
                    ];
                }
            }

            $report['grandTotal'] = $grandTotal;

            return response()->json([
                'status'     => 'success',
                'data'       => $report,
                'pagination' => isset($request->page) ? [
                    'total'        => $feeDetailsResult->total(),
                    'per_page'     => $feeDetailsResult->perPage(),
                    'current_page' => $feeDetailsResult->currentPage(),
                    'last_page'    => $feeDetailsResult->lastPage(),
                    'from'         => $feeDetailsResult->firstItem(),
                    'to'           => $feeDetailsResult->lastItem(),
                ] : [],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'error'   => 'An error occurred. Please try again.',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     *fee Report Admin Excel
     */
    public function feeReportAdminExcel(Request $request)
    {
        try {
            // ── Force no pagination for Excel export ──────────────────────────────
            $request->request->remove('page');

            $response = $this->feeReportAdmin($request);

            if ($response->getStatusCode() !== 200) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Failed to generate report: ' . $response->getContent(),
                ], 500);
            }

            $reportData = json_decode($response->getContent(), true)['data'] ?? [];

            if (empty($reportData)) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'No data found.',
                ], 404);
            }

            // ── Split into junior/senior in ONE pass ──────────────────────────────
            $juniorStudents = [];
            $seniorStudents = [];
            $juniorTotal    = 0;
            $seniorTotal    = 0;

            foreach ($reportData as $key => $row) {
                if ($key === 'grandTotal') continue;

                if ($row['school'] == 1) {
                    $juniorStudents[] = $row;
                    $juniorTotal     += floatval($row['feeDetails']['amount'] ?? 0);
                } else {
                    $seniorStudents[] = $row;
                    $seniorTotal     += floatval($row['feeDetails']['amount'] ?? 0);
                }
            }

            // ── Write CSV ─────────────────────────────────────────────────────────
            $output = fopen('php://memory', 'w');
            if ($output === false) {
                throw new \Exception('Failed to open output stream.');
            }

            fputcsv($output, [
                'S.No.', 'Pay Date', 'Ref. Slip No.', 'C. Slip No.',
                'SRNO', 'Class', 'Section', 'Name', "Father's Name", 'Amount (Rs.)',
            ]);

            $counter = 1;

            // Junior Section
            if (!empty($juniorStudents)) {
                fputcsv($output, []);
                fputcsv($output, ['Junior Section']);
                foreach ($juniorStudents as $row) {
                    fputcsv($output, [
                        $counter++,
                        $row['feeDetails']['pay_date']    ?? '-',
                        $row['feeDetails']['ref_slip_no'] ?? '-',
                        $row['feeDetails']['recp_no']     ?? '-',
                        $row['feeDetails']['srno']        ?? '-',
                        $row['class_name']                ?? '-',
                        $row['section_name']              ?? '-',
                        $row['name']                      ?? '-',
                        $row['f_name']                    ?? '-',
                        $row['feeDetails']['amount']      ?? 0,
                    ]);
                }
                fputcsv($output, ['', '', '', '', '', '', '', '', 'Junior Section Total:', $juniorTotal]);
            }

            // Senior Section
            if (!empty($seniorStudents)) {
                fputcsv($output, []);
                fputcsv($output, ['Senior Section']);
                foreach ($seniorStudents as $row) {
                    fputcsv($output, [
                        $counter++,
                        $row['feeDetails']['pay_date']    ?? '-',
                        $row['feeDetails']['ref_slip_no'] ?? '-',
                        $row['feeDetails']['recp_no']     ?? '-',
                        $row['feeDetails']['srno']        ?? '-',
                        $row['class_name']                ?? '-',
                        $row['section_name']              ?? '-',
                        $row['name']                      ?? '-',
                        $row['f_name']                    ?? '-',
                        $row['feeDetails']['amount']      ?? 0,
                    ]);
                }
                fputcsv($output, ['', '', '', '', '', '', '', '', 'Senior Section Total:', $seniorTotal]);
            }

            // Grand Total
            if (isset($reportData['grandTotal'])) {
                fputcsv($output, ['', '', '', '', '', '', '', '', 'Grand Total:', $reportData['grandTotal']]);
            }

            rewind($output);
            $csvContent = stream_get_contents($output);
            fclose($output);

            $fileName = 'fee_admin_report_' . now()->format('Y_m_d_His') . '.csv';

            return response($csvContent, 200)
                ->header('Content-Type',        'text/csv')
                ->header('Content-Disposition', 'attachment; filename="' . $fileName . '"');

        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to export report: ' . $e->getMessage(),
            ], 500);
        }
    }
    /**
     * Fee Report (Mercy) Admin View
     */

    public function feeReportMercyAdminView()
    {
        $sessions = SessionMasterController::getSessions(['id', 'session']);
        return view('admin.reports.mercy_fee_report_admin', compact('sessions'));
    }

    /**
     * fee Report Admin
     */

    public function feeReportMercyAdmin(Request $request)
    {
        try {
            $rules = [
                'session'    => 'required|exists:session_masters,id,active,1',
                'feeType'    => 'required',
                'reportType' => 'required',
            ];

            if (isset($request->startDate) || isset($request->endDate)) {
                $rules['startDate'] = [
                    'required',
                    function ($attribute, $value, $fail) use ($request) {
                        if (isset($request->endDate) && $value > $request->endDate) {
                            $fail('Start Date must be less than or equal to End Date.');
                        }
                    },
                    'exists:fee_details,pay_date',
                ];
                $rules['endDate'] = [
                    'required_if:startDate,true',
                    function ($attribute, $value, $fail) use ($request) {
                        if (isset($request->startDate) && $value < $request->startDate) {
                            $fail('End Date must be greater than or equal to Start Date.');
                        }
                    },
                ];
            }

            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                return response()->json([
                    'status'  => 'error',
                    'message' => $validator->errors(),
                ], 400);
            }

            $startDate      = $request->startDate;
            $endDate        = $request->endDate;
            $reportType     = $request->reportType;
            $sessionId      = $request->session;
            $academicTrans  = $request->feeType == 1 ? 1 : 2;

            // ── Base fee query ────────────────────────────────────────────────────
            $feeQuery = FeeDetail::where('session_id',     $sessionId)
                ->where('academic_trans', $academicTrans)
                ->where('paid_mercy',     2)
                ->where('active',         1)
                ->orderBy('srno',        'asc')
                ->orderBy('ref_slip_no', 'asc');

            if ($startDate && $endDate) {
                $feeQuery->whereBetween('pay_date', [$startDate, $endDate]);
            }

            // ── Report Type 1: Summary only ───────────────────────────────────────
            if ($reportType == 1) {
                $summaryAmount = (clone $feeQuery)->sum('amount');
                return response()->json([
                    'status' => 'success',
                    'data'   => [
                        ['summeryAmount' => $summaryAmount > 0 ? $summaryAmount : 'No any Fee Entry Found.'],
                    ],
                    'pagination' => [],
                ], 200);
            }

            // ── Report Type 2: Detailed ───────────────────────────────────────────

            // ── 1. Fetch fee details (paginated or full) in ONE query ─────────────
            $feeDetailsResult = isset($request->page)
                ? (clone $feeQuery)->paginate(50)
                : (clone $feeQuery)->get();

            // ── 2. Get grand total in ONE query (no re-fetch) ─────────────────────
            $grandTotal = (clone $feeQuery)->sum('amount');

            if ($feeDetailsResult->isEmpty()) {
                return response()->json([
                    'status'     => 'success',
                    'data'       => ['grandTotal' => 0],
                    'pagination' => [],
                ], 200);
            }

            // ── 3. Fetch ALL student details in ONE query ─────────────────────────
            $studentSrnos = $feeDetailsResult->pluck('srno')->unique()->toArray();

            $studentsMap = DB::table('stu_main_srno')
                ->leftJoin('stu_detail',      'stu_main_srno.srno', '=', 'stu_detail.srno')
                ->leftJoin('parents_detail',  'stu_main_srno.srno', '=', 'parents_detail.srno')
                ->leftJoin('class_masters',   'stu_main_srno.class', '=', 'class_masters.id')
                ->leftJoin('session_masters', 'stu_main_srno.session_id', '=', 'session_masters.id')
                ->where('stu_main_srno.session_id', $sessionId)
                ->where('stu_main_srno.active',     1)
                ->whereIn('stu_main_srno.srno',     $studentSrnos)
                ->select(
                    'stu_main_srno.srno',
                    'stu_main_srno.school',
                    'class_masters.class as class_name',
                    'stu_main_srno.section as section_name',
                    'stu_detail.name as student_name',
                    'parents_detail.f_name'
                )
                ->get()
                ->groupBy('srno'); // [srno => collection of student rows]

            // ── 4. Build report in memory (zero extra queries) ────────────────────
            $report = [];

            foreach ($feeDetailsResult as $feeDetail) {
                $matchingStudents = $studentsMap->get($feeDetail->srno, collect([]));

                foreach ($matchingStudents as $student) {
                    $report[] = [
                        'school'       => $student->school,
                        'name'         => $student->student_name,
                        'f_name'       => $student->f_name,
                        'class_name'   => $student->class_name,
                        'section_name' => $student->section_name,
                        'feeDetails'   => $feeDetail,
                    ];
                }
            }

            $report['grandTotal'] = $grandTotal;

            return response()->json([
                'status'     => 'success',
                'data'       => $report,
                'pagination' => isset($request->page) ? [
                    'total'        => $feeDetailsResult->total(),
                    'per_page'     => $feeDetailsResult->perPage(),
                    'current_page' => $feeDetailsResult->currentPage(),
                    'last_page'    => $feeDetailsResult->lastPage(),
                    'from'         => $feeDetailsResult->firstItem(),
                    'to'           => $feeDetailsResult->lastItem(),
                ] : [],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'error'   => 'An error occurred. Please try again.',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * fee Report Admin Excel
     */
    public function feeReportMercyAdminExcel(Request $request)
    {
        try {
            // ── Force no pagination for Excel export ──────────────────────────────
            $request->request->remove('page');

            $response = $this->feeReportMercyAdmin($request);

            if ($response->getStatusCode() !== 200) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Failed to generate report: ' . $response->getContent(),
                ], 500);
            }

            $reportData = json_decode($response->getContent(), true)['data'] ?? [];

            if (empty($reportData)) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'No data found.',
                ], 404);
            }

            // ── Split into junior/senior in ONE pass ──────────────────────────────
            $juniorStudents = [];
            $seniorStudents = [];
            $juniorTotal    = 0;
            $seniorTotal    = 0;

            foreach ($reportData as $key => $row) {
                if ($key === 'grandTotal') continue;

                if ($row['school'] == 1) {
                    $juniorStudents[] = $row;
                    $juniorTotal     += floatval($row['feeDetails']['amount'] ?? 0);
                } else {
                    $seniorStudents[] = $row;
                    $seniorTotal     += floatval($row['feeDetails']['amount'] ?? 0);
                }
            }

            // ── Write CSV ─────────────────────────────────────────────────────────
            $output = fopen('php://memory', 'w');
            if ($output === false) {
                throw new \Exception('Failed to open output stream.');
            }

            fputcsv($output, [
                'S.No.', 'Pay Date', 'Ref. Slip No.', 'C. Slip No.',
                'SRNO', 'Class', 'Section', 'Name', "Father's Name", 'Amount (Rs.)',
            ]);

            $counter = 1;

            // Junior Section
            if (!empty($juniorStudents)) {
                fputcsv($output, ['Junior Section']);
                foreach ($juniorStudents as $row) {
                    fputcsv($output, [
                        $counter++,
                        $row['feeDetails']['pay_date']    ?? '-',
                        $row['feeDetails']['ref_slip_no'] ?? '-',
                        $row['feeDetails']['recp_no']     ?? '-',
                        $row['feeDetails']['srno']        ?? '-',
                        $row['class_name']                ?? '-',
                        $row['section_name']              ?? '-',
                        $row['name']                      ?? '-',
                        $row['f_name']                    ?? '-',
                        $row['feeDetails']['amount']      ?? 0,
                    ]);
                }
                fputcsv($output, ['', '', '', '', '', '', '', '', 'Junior Section Total:', $juniorTotal]);
            }

            // Senior Section
            if (!empty($seniorStudents)) {
                fputcsv($output, ['Senior Section']);
                foreach ($seniorStudents as $row) {
                    fputcsv($output, [
                        $counter++,
                        $row['feeDetails']['pay_date']    ?? '-',
                        $row['feeDetails']['ref_slip_no'] ?? '-',
                        $row['feeDetails']['recp_no']     ?? '-',
                        $row['feeDetails']['srno']        ?? '-',
                        $row['class_name']                ?? '-',
                        $row['section_name']              ?? '-',
                        $row['name']                      ?? '-',
                        $row['f_name']                    ?? '-',
                        $row['feeDetails']['amount']      ?? 0,
                    ]);
                }
                fputcsv($output, ['', '', '', '', '', '', '', '', 'Senior Section Total:', $seniorTotal]);
            }

            // Grand Total
            if (isset($reportData['grandTotal'])) {
                fputcsv($output, ['', '', '', '', '', '', '', '', 'Grand Total:', $reportData['grandTotal']]);
            }

            rewind($output);
            $csvContent = stream_get_contents($output);
            fclose($output);

            $fileName = 'mercy_fee_report_' . now()->format('Y_m_d_His') . '.csv';

            return response($csvContent, 200)
                ->header('Content-Type',        'text/csv')
                ->header('Content-Disposition', 'attachment; filename="' . $fileName . '"');

        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to export report.' ,
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
            $validator = Validator::make($request->all(), [
                'session' => 'required|exists:session_masters,id,active,1',
                'field'   => 'required|in:1,2,3,4',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status'  => 'error',
                    'message' => $validator->errors(),
                ], 400);
            }

            $sessionId = $request->session;
            $field     = $request->field;

            // ── Parse class/section safely (empty string → no filter) ─────────────
            $classIds   = array_filter(array_map('trim', explode(',', $request->class   ?? '')));
            $sectionIds = array_filter(array_map('trim', explode(',', $request->section ?? '')));

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

            // ── Base query ────────────────────────────────────────────────────────
            $baseQuery = StudentMasterController::getStdWithNames(false, $fields)
                ->where('stu_main_srno.session_id', $sessionId)
                ->orderBy('stu_main_srno.class',   'asc')
                ->orderBy('stu_main_srno.section', 'asc');

            if (!empty($classIds)) {
                $baseQuery->whereIn('stu_main_srno.class', $classIds);
            }

            if (!empty($sectionIds)) {
                $baseQuery->whereIn('stu_main_srno.section', $sectionIds);
            }

            // ── Apply field-specific filter ───────────────────────────────────────
            switch ($field) {
                case 1: // Date of Birth
                    $baseQuery->where(function ($q) {
                        $q->whereNull('stu_detail.dob')
                        ->orWhere('stu_detail.dob', '')
                        ->orWhere('stu_detail.dob', '1981-01-01');
                    });
                    $heading = 'Date of Birth not available.';
                    break;

                case 2: // Admission Date
                    $baseQuery->where(function ($q) {
                        $q->whereNull('stu_main_srno.admission_date')
                        ->orWhere('stu_main_srno.admission_date', '');
                    });
                    $heading = 'Admission Date not available.';
                    break;

                case 3: // Mobile No.
                    $baseQuery->where(function ($q) {
                        $q->whereNull('parents_detail.f_mobile')
                        ->orWhere('parents_detail.f_mobile', '');
                    });
                    $heading = 'Mobile No. not available.';
                    break;

                case 4: // Age Proof
                    $baseQuery->where(function ($q) {
                        $q->whereNull('stu_main_srno.age_proof')
                        ->orWhere('stu_main_srno.age_proof', '0')
                        ->orWhere('stu_main_srno.age_proof', 0);
                    });
                    $heading = 'Age Proof not available.';
                    break;

                default:
                    return response()->json([
                        'status'  => 'error',
                        'message' => 'Invalid field selected.',
                    ], 400);
            }

            // ── Return paginated or full result ───────────────────────────────────
            $data = $request->page
                ? $baseQuery->paginate(50)
                : $baseQuery->get();

            return response()->json([
                'status'  => 'success',
                'data'    => $data,
                'heading' => $heading,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to get miss field report: ' . $e->getMessage(),
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


    /** ClassWise Sections */
    public function getAllClassSections(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'class_id' => 'required',
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $validator->errors()
                ], 400);
            }

            $classId = $request->class_id;
            if (!empty($classId)) {
                // Get sections for multiple class IDs
                $sections = [];
                if ($classId == 'all') {
                    $sections = SectionMaster::where('active', 1)->pluck('section', 'id')->toArray();
                } else {
                    if (is_numeric($classId)) {
                        $sections = SectionMaster::where('active', 1)->where('class_id', (int) $classId)->pluck('section', 'id')->toArray();
                    } else {
                        $sections = [];
                    }
                }
                if (!empty($sections)) {
                    return response()->json([
                        'status' => 'success',
                        'message' => "All class sections",
                        'data' => $sections,
                    ], 200);
                } else {
                    return response()->json([
                        'status' => 'error',
                        'message' => "No sections found for the provided classes.",
                        'data' => [],
                    ], 404);
                }
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => "Please Select the class",
                    'data' => [],
                ], 400);
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => "Failed to get sections"
            ], 500);
        }
    }


    /** Day wise collections report Index*/
    public function dayWiseCollectionIndex()
    {
        $classes = ClassMasterController::getClasses();

        return view('admin.reports.day_wise_collection_fee_detail', compact('classes'));
    }
    /** Day wise collection report as excel */

    public function exportdDayWiseCollectionReprt(Request $request)
    {
        try {

            $fromDate = $request->from_date;
            $toDate   = $request->to_date;
            $class    = $request->class_id;
            $section  = $request->section_id;
            $fee_mode = $request->fee_mode;
            $academic_trans = $request->academic_trans;

            //  Get detailed report (student-wise)
            $reportData = DB::table('fee_details as fc')
                /* ->join('stu_main_srno as s', 'fc.srno', '=', 's.srno')
                ->join('parents_detail as p', 'fc.srno', '=', 'p.srno')
                ->join('stu_detail as sd', 'fc.srno', '=', 'sd.srno') */
                ->join('stu_main_srno as s', function($join) {
                    $join->on('fc.srno', '=', 's.srno')
                        ->on('fc.session_id', '=', 's.session_id'); // Match fee session with student session
                })
                ->joinSub(
                    DB::table('parents_detail')
                        ->select('srno', 'f_name')
                        ->groupBy('srno', 'f_name'),
                    'p',
                    'fc.srno', '=', 'p.srno'
                )
                ->joinSub(
                    DB::table('stu_detail')
                        ->select('srno', 'name')
                        ->groupBy('srno', 'name'),
                    'sd',
                    'fc.srno', '=', 'sd.srno'
                )
                ->join('class_masters as c', 's.class', '=', 'c.id')
                ->join('section_masters as sec', 's.section', '=', 'sec.id')
                ->select(
                    DB::raw('DATE(fc.pay_date) as payment_date'),
                    'fc.srno',
                    'fc.ref_slip_no',
                    'sd.name as student_name',
                    'p.f_name as parent_name',
                    'c.class as class_name',
                    'sec.section as section_name',
                    'fc.fee_mode',
                    'fc.academic_trans',
                    'fc.fee_of',
                    'fc.amount'
                )
                ->where('fc.paid_mercy', 1)
                ->whereBetween('fc.pay_date', [$fromDate, $toDate])
                ->when($class && $class !== 'all', fn($q) => $q->where('s.class', $class))
                ->when($section && $section !== 'all', fn($q) => $q->where('s.section', $section))
                ->when($academic_trans && $academic_trans !== 'all', fn($q) => $q->where('fc.academic_trans', $academic_trans))
                ->when($fee_mode && $fee_mode !== 'all', fn($q) => $q->where('fc.fee_mode', $fee_mode))
                ->orderBy('payment_date', 'ASC')
                ->get();

            if ($reportData->isEmpty()) {
                return redirect()->route('admin.dayWiseCollectionIndex')->with('error', 'No data found.')->withInput();
            }


            // CSV Export
            $fileName = 'day_wise_collection_report.csv';
            $output = fopen('php://memory', 'w');

            // CSV headers
            $headers = [
                'Payment Date',
                'Receipt Number',
                'Student Name',
                'Parent Name',
                'Class',
                'Section',
                'Payment Mode',
                'Type (Academic/Transport)',
                'Fee Of',
                'Amount'
            ];
            fputcsv($output, $headers);

            $grandTotal = 0;
            $totalTransactions = 0;

            foreach ($reportData as $row) {
                $grandTotal += $row->amount;
                $totalTransactions++;
                fputcsv($output, [
                    $row->payment_date,
                    $row->ref_slip_no,
                    $row->student_name,
                    $row->parent_name,
                    $row->class_name,
                    $row->section_name,
                    $this->getFeeModeName($row->fee_mode),
                    $row->academic_trans == 1 ? 'Academic' : ($row->academic_trans == 2 ? 'Transport' : 'Other'),
                    $this->getFeeOf($row->fee_of, $row->academic_trans),
                    $row->amount,
                ]);
            }

            // Add Grand Total row
            fputcsv($output, []);
            fputcsv($output, [
                'Grand Total',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                $totalTransactions . ' Transactions',
                $grandTotal
            ]);

            rewind($output);
            $csvContent = stream_get_contents($output);
            fclose($output);
            return response($csvContent, 200)->header('Content-Type', 'text/csv')->header('Content-Disposition', 'attachment; filename="' . $fileName . '"');
        } catch (\Exception $e) {
            // return redirect()->back()->with('error', 'Something went wrong, please try again.', $e->getMessage());
            return redirect()->route('admin.dayWiseCollectionIndex')->with('error', 'Something went wrong, please try again.')->withInput();
        }
    }



    private function getFeeModeName($id)
    {
        return match ($id) {
            1 => 'Cash',
            2 => 'UPI',
            3 => 'Bank Transfer',
            default => 'Other',
        };
    }

    private function getFeeOf($id, $academic_trans)
    {
        if ($academic_trans == 1) {
            return match ($id) {
                1 => 'Admission Fee',
                2 => 'Ist Installment',
                3 => 'IInd Installment',
                4 => 'Complete Fee',
                default => 'Other',
            };
        }
        if ($academic_trans == 2) {
            return match ($id) {
                1 => 'Ist Installment',
                2 => 'IInd Installment',
                3 => 'Complete Fee',
                default => 'Other',
            };
        }
    }
}
