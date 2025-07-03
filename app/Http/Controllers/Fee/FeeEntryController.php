<?php

namespace App\Http\Controllers\Fee;

use App\Http\Controllers\Admin\ClassMasterController;
use App\Http\Controllers\Admin\SessionMasterController;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Student\StudentMasterController;
use App\Models\Admin\ClassMaster;
use App\Models\Admin\FeeMaster;
use App\Models\Admin\SectionMaster;
use App\Models\Admin\SessionMaster;
use App\Models\Fee\FeeDetail;
use App\Models\Student\StudentMaster;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class FeeEntryController extends Controller
{
    //
    // public function __construct()
    // {
    //     $this->middleware('auth');
    // }
    public function index(Request $request)
    {

        return view('fee.fee_entry.index');
    }
    public function transportFee(Request $request)
    {
        $classes = ClassMasterController::getClasses();
        return view('fee.fee_entry.transport_fee', compact('classes'));
    }
    public function academicFee(Request $request)
    {
        $classes = ClassMasterController::getClasses();
        return view('fee.fee_entry.accademic_fee', compact('classes'));
    }

    //fee detail view

    public function feeDetail(Request $request)
    {
        $classes = ClassMasterController::getClasses();
        return view('fee.fee_details.index', compact('classes'));
    }

    public function academicFeeStore(Request $request)
    {
        try {
            $academic_trans_value = isset($request->transport) ? $request->transport : 1;
            $session = isset($request->session) ? $request->session : $request->current_session;
            $student = StudentMasterController::getStdWithNames(false)->where('stu_main_srno.srno', $request->std_id)->where('stu_main_srno.class', $request->class)->where('stu_main_srno.section', $request->section)->where('stu_main_srno.session_id', $session)->first();
            // $student = StudentMaster::where('srno', $request->std_id)->where('class', $request->class)->where('section', $request->section)->where('session_id',$session)->where('active', 1)->whereIn('ssid', [1,2,4,5])->first();
            $sessionData = SessionMasterController::getSessions(['id', 'session'], ['id' => $session]);
            $sessionName = array_values($sessionData)[0];
            if (!$student) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Student not found for the given SRNO.'
                ], 404);
            }

            $feeMaster = FeeMaster::where('class_id', $request->class)->where('session_id', $session)->where('active', 1)->first(
                ['admission_fee', 'inst_1', 'inst_2', 'inst_total', 'ins_discount']
            );
            if (isset($request->transport)) {
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
            $fee_master_fees = isset($request->transport) ? $tcs : $feeMaster;



            $admission_fee_paid = FeeDetail::where('srno', $request->std_id)->where('academic_trans', $academic_trans_value)->where('fee_of', 1)->exists();
            $baseQuery = FeeDetail::where('srno', $request->std_id)->where('session_id', $session)->where('academic_trans', $academic_trans_value)->get();
            // dd($baseQuery);
            $first_inst_fee_total = $baseQuery->where('fee_of', isset($request->transport) ? 1 : 2)->where('paid_mercy', 1)->sum('amount');
            // dd($first_inst_fee_total);
            $second_inst_fee_total = $baseQuery->where('fee_of', isset($request->transport) ? 2 : 3)->where('paid_mercy', 1)->sum('amount');
            $complete_fee_total = $baseQuery->where('fee_of', isset($request->transport) ? 3 : 4)->where('paid_mercy', 1)->sum('amount');
            $mercy_fee_total = $baseQuery->where('fee_of', isset($request->transport) ? 3 : 4)->where('paid_mercy', 2)->sum('amount');


            $first_inst_fee_exists = FeeDetail::where('srno', $request->std_id)->where('session_id', $session)->where('academic_trans', $academic_trans_value)->where('fee_of',  isset($request->transport) ? 1 : 2)->where('paid_mercy', 1)->exists();
            $second_inst_fee_exists = FeeDetail::where('srno', $request->std_id)->where('session_id', $session)->where('academic_trans', $academic_trans_value)->where('fee_of', isset($request->transport) ? 2 : 3)->where('paid_mercy', 1)->exists();
            $complete_fee_exists = FeeDetail::where('srno', $request->std_id)->where('session_id', $session)->where('academic_trans', $academic_trans_value)->where('fee_of', isset($request->transport) ? 3 : 4)->where('paid_mercy', 1)->exists();
            $mercy_fee_exists = FeeDetail::where('srno', $request->std_id)->where('session_id', $session)->where('academic_trans', $academic_trans_value)->where('fee_of', isset($request->transport) ? 3 : 4)->where('paid_mercy', 2)->exists();
            $uniqueRefSlip = $sessionName . $request->ref_slip;
            // dd($uniqueRefSlip);
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
                        }elseif($admission_fee_paid == true){
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
                    function ($attribute, $value, $fail) use ($request, $fee_master_fees, $first_inst_fee_total, $complete_fee_exists, $complete_fee_total, $mercy_fee_exists, $mercy_fee_total, $first_inst_fee_exists) {
                        $totalDue = $fee_master_fees->inst_1 - $first_inst_fee_total;
                        if ($first_inst_fee_exists == true && (isset($request->transport) ? $complete_fee_exists : $mercy_fee_exists == true)) {
                            # code...
                            $totalDue = $fee_master_fees->inst_1 - ($first_inst_fee_total + isset($request->transport) ? $complete_fee_total : $mercy_fee_total);
                        }
                        // dd($totalDue);
                        if ($totalDue <= 0) {
                            $fail('Already Paid First Installment.');
                            return; // Stop further validations
                        } elseif ($value > $totalDue && $first_inst_fee_exists == true) {
                            $fail('Only ' . $totalDue . ' due of First Installment.');
                        }
                    },
                    function ($attribute, $value, $fail) use ($request, $fee_master_fees, $complete_fee_total, $first_inst_fee_total, $mercy_fee_total) {
                        $totalWithMercy = $first_inst_fee_total + isset($request->transport) ? $complete_fee_total : $mercy_fee_total;
                        if ($totalWithMercy === $fee_master_fees->inst_1) {
                            # code...
                            $fail('Already Paid First Installment.');
                            return; // Stop further validations
                        }
                    },
                    function ($attribute, $value, $fail) use ($request, $fee_master_fees, $complete_fee_total, $first_inst_fee_exists, $second_inst_fee_total, $mercy_fee_exists, $mercy_fee_total, $second_inst_fee_exists, $complete_fee_exists) {
                        if (($second_inst_fee_exists == true || (isset($request->transport) ? $complete_fee_exists : $mercy_fee_exists == true)) && $first_inst_fee_exists == false) {
                            # code...
                            $total = $second_inst_fee_total + isset($request->transport) ? $complete_fee_total : $mercy_fee_total;
                            if ($total == $fee_master_fees->inst_total) {
                                $fail('Already Paid complete fee');
                                return; // Stop further validations
                            }
                        }
                    },
                    function ($attribute, $value, $fail) use ($baseQuery, $request) {
                        $completeFee = $baseQuery->where('fee_of',  isset($request->transport) ? 3 : 4)->where('paid_mercy', 1)->first();
                        if ($completeFee) {
                            $fail("Fee previously enter as complete fee, now can't by insatllment. Please enter by complete fee");
                            return; // Stop further validations
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

                    function ($attribute, $value, $fail) use ($request, $fee_master_fees, $second_inst_fee_total, $mercy_fee_exists, $mercy_fee_total, $complete_fee_total, $second_inst_fee_exists, $complete_fee_exists) {
                        $totalDue = $fee_master_fees->inst_2 - $second_inst_fee_total;
                        if ($second_inst_fee_exists == true && (isset($request->transport) ? $complete_fee_exists : $mercy_fee_exists == true)) {
                            # code...
                            $totalDue = $fee_master_fees->inst_2 - ($second_inst_fee_total + isset($request->transport) ? $complete_fee_total : $mercy_fee_total);
                        }
                        $totalWithMercy = $second_inst_fee_total + isset($request->transport) ? $complete_fee_total : $mercy_fee_total;
                        if (($totalDue <= 0) || ($totalWithMercy === $fee_master_fees->inst_2)) {
                            $fail('Already Paid Second Installment.');
                            return; // Stop further validations
                        } elseif ($value > $totalDue && $second_inst_fee_exists == true) {
                            $fail('Only ' . $totalDue . ' due of Second Installment.');
                        }
                    },
                    function ($attribute, $value, $fail) use ($request, $fee_master_fees, $first_inst_fee_exists, $first_inst_fee_total, $mercy_fee_exists, $mercy_fee_total, $complete_fee_total, $second_inst_fee_exists, $complete_fee_exists) {
                        if (($first_inst_fee_exists == true || (isset($request->transport) ? $complete_fee_exists : $mercy_fee_exists == true)) && $second_inst_fee_exists == false) {
                            # code...
                            $total = $first_inst_fee_total +  isset($request->transport) ? $complete_fee_total : $mercy_fee_total;
                            if ($total == $fee_master_fees->inst_total) {
                                $fail('Already Paid complete fee');
                                return; // Stop further validations
                            }
                        }
                    },

                    function ($attribute, $value, $fail) use ($baseQuery, $request) {
                        $completeFee = $baseQuery->where('fee_of', (isset($request->transport) ? 3 : 4))->where('paid_mercy', 1)->first();
                        if ($completeFee) {
                            $fail("Fee previously enter as complete fee, now can't by insatllment. Please enter by complete fee");
                            return; // Stop further validations
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
                        $installFee = FeeDetail::where(function ($query) use ($request) {
                            isset($request->transport) ? $query->where('fee_of', 1)->orWhere('fee_of', 2) : $query->where('fee_of', 2)->orWhere('fee_of', 3);
                            // $query->where('fee_of', 2)->orWhere('fee_of', 3);
                        })
                            ->where('srno', $request->std_id)
                            ->where('session_id', $session)
                            ->where('academic_trans', $academic_trans_value)
                            ->first();
                        if ($installFee) {
                            $fail("If any installment is paid, then you can't enter complete fee. Please enter by installment.");
                            return; // Stop further validations
                        }
                    },
                ],
                'mercy_fee' => [
                    'required_if:mercy_fee,true',
                    'nullable',
                    'regex:/^\d*(\.\d{2})?$/',
                    function ($attribute, $value, $fail) use ($complete_fee_exists, $second_inst_fee_exists, $first_inst_fee_exists, $mercy_fee_total, $request, $fee_master_fees, $first_inst_fee_total, $second_inst_fee_total, $complete_fee_total, $mercy_fee_exists) {
                        if (!empty($request->first_inst_fee) && !empty($request->mercy_fee)) {

                            $first_inst_fee = $request->first_inst_fee ?? 0;
                            $mercy_fee = $request->mercy_fee ?? 0;

                            $first_inst = $first_inst_fee_total + $first_inst_fee + $mercy_fee;
                            if ($first_inst_fee_exists == true && $mercy_fee_exists == true) {
                                # code...
                                $first_inst = $first_inst_fee_total + $mercy_fee_total + $first_inst_fee + $mercy_fee;
                            }

                            if ($first_inst > $fee_master_fees->inst_1) {
                                return $fail('If you want to pay mercy then deduct some amount from 1st Installment and enter only the rest balance');
                            }
                        } elseif (!empty($request->second_inst_fee) && !empty($request->mercy_fee)) {
                            $second_inst_fee = $request->second_inst_fee ?? 0;
                            $mercy_fee = $request->mercy_fee ?? 0;

                            $second_inst = $second_inst_fee_total + $second_inst_fee + $mercy_fee;

                            if ($second_inst_fee_exists == true && $mercy_fee_exists == true) {
                                $second_inst = $second_inst_fee_total + $mercy_fee_total + $second_inst_fee + $mercy_fee;
                            }
                            if ($second_inst > $fee_master_fees->inst_2) {
                                return $fail('If you want to pay mercy then deduct some amount from 2nd Installment and enter only the rest balance');
                            }
                        } elseif (!empty($request->complete_fee) && !empty($request->mercy_fee)) {
                            $complete_fee = $request->complete_fee ?? 0;
                            $mercy_fee = $request->mercy_fee ?? 0;

                            $complete_inst = $complete_fee + $mercy_fee;
                            // $complete_inst = $complete_fee_total + $complete_fee + $mercy_fee;

                            // dd($complete_inst);
                            if ($complete_fee_exists == true && $mercy_fee_exists == true) {
                                $complete_inst = $complete_fee_total + $mercy_fee_total + $complete_fee + $mercy_fee;
                            }
                            if ($complete_inst > $fee_master_fees->inst_total) {
                                return $fail('If you want to pay mercy then deduct some amount from Complete Fee and enter only the rest balance');
                            }
                        }
                    },
                    function ($attribute, $value, $fail) use ($first_inst_fee_exists, $second_inst_fee_exists, $complete_fee_exists, $mercy_fee_total, $request, $mercy_fee_exists, $fee_master_fees, $first_inst_fee_total, $second_inst_fee_total, $complete_fee_total) {
                        if (empty($request->first_inst_fee) && empty($request->second_inst_fee) && empty($request->complete_fee) && !empty($request->mercy_fee)) {
                            $totalPaid = 0;
                            if ($first_inst_fee_exists == true || $second_inst_fee_exists == true || $complete_fee_exists == true || $mercy_fee_exists == true) {
                                # code...
                                $totalPaid = $first_inst_fee_total + $second_inst_fee_total + $complete_fee_total + $mercy_fee_total;
                                if ($totalPaid == $fee_master_fees->inst_total) {
                                    # code...
                                    $fail('Already Paid Complete Fee.');
                                    return; // Stop further validations
                                } else {
                                    # code...
                                    $dueAmount = $fee_master_fees->inst_total - $totalPaid;
                                    if ($request->mercy_fee > $dueAmount) {
                                        $fail('Only ' . $dueAmount . ' due of Complete Fee.');
                                    }
                                }
                            }
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
            $recp_no = FeeDetail::where('session_id', $session)->where('academic_trans', $academic_trans_value)->max('recp_no');
            // $sessionName = SessionMaster::where('id', $session)->where('active', 1)->value('session');


            $commonData = [
                'srno' => $request->std_id,
                'session_id' => $session,
                'academic_trans' => $academic_trans_value,
                'pay_date' => $request->fee_date,
                'recp_no' => $recp_no ? $recp_no + 1 : 1,
                'ref_slip_no' => isset($request->ref_slip) ? $sessionName . $request->ref_slip : null,
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
                    'fee_of' => isset($request->transport) ? 1 : 2,
                    'amount' => $request->first_inst_fee,
                    'paid_mercy' => 1,
                ]);

                FeeDetail::create($firstInstData);
            }
            if (!empty($request->second_inst_fee)) {
                # code...
                $secondInstData = array_merge($commonData, [
                    'fee_of' => isset($request->transport) ? 2 : 3,
                    'amount' => $request->second_inst_fee,
                    'paid_mercy' => 1,
                ]);


                FeeDetail::create($secondInstData);
            }
            if (!empty($request->complete_fee)) {
                # code...
                $completeData = array_merge($commonData, [
                    'fee_of' => isset($request->transport) ? 3 : 4,
                    'amount' => $request->complete_fee,
                    'paid_mercy' => 1,
                ]);

                FeeDetail::create($completeData);
            }
            if (!empty($request->mercy_fee)) {
                # code...
                $mercyData = array_merge($commonData, [
                    'fee_of' => isset($request->transport) ? 3 : 4,
                    'amount' => $request->mercy_fee,
                    'paid_mercy' => 2,
                    'recp_no' => null,
                    'ref_slip_no' => null,
                ]);
                FeeDetail::create($mercyData);
            }
            $printSlipNo = $recp_no ? $recp_no + 1 : 1;
            return response()->json([
                'status' => 'success',
                'message' => "Fee Submitted Successfully. Slip No. " . ($recp_no ? $recp_no + 1 : 1),
                'print_url' =>  url("fee/print-fee-slip?recpNo={$printSlipNo}&feeId={$academic_trans_value}&session={$session}")
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => "Failed to submit fee"
            ], 500);
        }
    }

    /**
     * student academic fee due amount list(all the session)
     */


    private function stdNameWithFather()
    {
        // $baseQuery = DB::table('stu_main_srno')
        //     ->select(
        //         'stu_main_srno.srno',
        //         'stu_main_srno.school',
        //         'stu_main_srno.relation_code',
        //         'stu_main_srno.transport',
        //         'stu_main_srno.trans_1st_inst',
        //         'stu_main_srno.trans_2nd_inst',
        //         'stu_main_srno.trans_total',
        //         'stu_detail.name as student_name',
        //         'parents_detail.f_name',
        //         'parents_detail.m_name'
        //     )
        //     ->leftJoin('stu_detail', 'stu_main_srno.srno', '=', 'stu_detail.srno')
        //     ->leftJoin('parents_detail', 'stu_main_srno.srno', '=', 'parents_detail.srno')
        //     ->where('stu_main_srno.active', 1)
        //     ->whereIn('stu_main_srno.ssid', [1,2,4,5]);
        $fields = [
            'stu_main_srno.srno',
            'stu_main_srno.school',
            'stu_main_srno.relation_code',
            'stu_main_srno.transport',
            'stu_main_srno.trans_1st_inst',
            'stu_main_srno.trans_2nd_inst',
            'stu_main_srno.trans_total',
            'stu_detail.name as student_name',
            'parents_detail.f_name',
            'parents_detail.m_name',
        ];
        $baseQuery = StudentMasterController::getStdWithNames(false, $fields);

        return $baseQuery;
    }
    public function academicFeeDueAmount(Request $request)
    {
        try {
            // Validate input
            $request->validate([
                'srno' => 'required',
            ]);
            $stdFields = [
                'stu_main_srno.srno',
                'stu_main_srno.prev_srno',
                'stu_main_srno.admission_date',
                'stu_main_srno.class',
                'stu_main_srno.section',
                'stu_main_srno.session_id',
                'stu_main_srno.school',
                'class_masters.sort',
                'stu_main_srno.rollno',
                'stu_main_srno.relation_code',
                'stu_main_srno.transport',
                'stu_main_srno.trans_1st_inst',
                'stu_main_srno.trans_2nd_inst',
                'stu_main_srno.trans_total',
                'stu_detail.name as student_name',
                'parents_detail.f_name',
                'parents_detail.m_name',
            ];

            $baseQuery = StudentMasterController::getStdWithNames(false, $stdFields)->get();
            // $baseQuery = $this->stdNameWithFather()->get();
            $class = explode(',', $request->class);
            $section = explode(',', $request->section);
            $srno = explode(',', $request->srno);
            // $students = $baseQuery->whereIn('class', $class)->whereIn('section', $section)->where('session_id', $request->current_session)->whereIn('srno', $srno)->get();
            // $students = $this->stdNameWithFather()
            $students = StudentMasterController::getStdWithNames(false, $stdFields)
                ->whereIn('stu_main_srno.class', $class)
                ->whereIn('stu_main_srno.section', $section)
                ->where('stu_main_srno.session_id', $request->current_session)
                ->whereIn('stu_main_srno.srno', $srno)
                ->get();


            if (!$students) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Student not found for the given SRNO.'
                ], 404);
            }
            // $result = [];
            $finalResult = []; // Array to store all students' results
            $processedSrnos = []; // Array to keep track of processed student SRNOs
            foreach ($students as $student) {
                  // Skip if the student has already been processed
                    if (in_array($student->srno, $processedSrnos)) {
                        continue;
                    }
                $fields = [
                    'session_masters.id as session_id',
                    'session_masters.session',
                    'stu_main_srno.ssid',
                    'stu_main_srno.srno',
                    'stu_main_srno.prev_srno',
                    'stu_main_srno.admission_date',
                    'class_masters.sort',
                    'stu_main_srno.rollno',
                    'stu_main_srno.class as class_id',
                    'class_masters.class as class_name',
                    'stu_main_srno.section as section_id',
                    'section_masters.section as section_name',
                    'stu_main_srno.transport',
                    'stu_main_srno.trans_1st_inst',
                    'stu_main_srno.trans_2nd_inst',
                    'stu_main_srno.trans_total',
                ];
                $sessionClasses = StudentMasterController::getStdWithNames(false, $fields)
                    ->where('stu_main_srno.srno', $student->srno)
                    ->get();
                $result = [
                    'school' => $student->school == 1 ? 'St. Vivekanand Play House' : 'St. Vivekanand Public Secondary School',
                    'srno' => $student->srno ?? [],
                    'student_name' => $student->student_name ?? [],
                    'father_name' => $student->f_name ?? [],
                    'mother_name' => $student->m_name ?? [],
                    'sessions' => [],
                    // 'relatives' => $result['relatives'] ?? [],
                    'relatives' => [],
                ];
                $academic_trans_value = isset($request->transport) ? $request->transport : 1;


                foreach ($sessionClasses as $sessionClass) {
                    $feeDetail = FeeDetail::where('srno', $student->srno)
                        ->where('session_id', $sessionClass->session_id);
                    $feeDetail = (($sessionClass->prev_srno == '' || $sessionClass->prev_srno == null) && $sessionClass->admission_date != '') ? $feeDetail : $feeDetail->where('fee_of', '!=', 1);

                    $feeDetails = $feeDetail->where('academic_trans', 1)->get();
                    $admission_fee_paid = FeeDetail::where('srno', $student->srno)->where('session_id', $sessionClass->session_id)
                        ->where('fee_of', 1)->sum('amount');

                    $feeMaster = FeeMaster::where('session_id', $sessionClass->session_id)->where('class_id', $sessionClass->class_id)->where('active', 1)->first();
                    $payableAmount = $feeMaster ? (($sessionClass->prev_srno == '' || $sessionClass->prev_srno == null) && $sessionClass->admission_date != '' ?  $feeMaster->inst_total + $feeMaster->admission_fee : $feeMaster->inst_total) : 0;
                    $feeDetailFirst = $feeDetails->sum('amount');
                    $totalPaid = $feeDetailFirst;

                    // Transport fee details
                    $transportFeeDetails = FeeDetail::where('srno', $student->srno)
                        ->where('session_id', $sessionClass->session_id)
                        ->where('academic_trans', 2)
                        ->get();

                    // $transportPayableAmount = $student->trans_total ?? 0;
                    $transportPayableAmount = $sessionClass->trans_total ?? 0;
                    $transportTotalPaid = $transportFeeDetails->sum('amount');
                    $result['sessions'][] = [
                        'session_id' => $sessionClass->session_id,
                        'prev_srno' => $sessionClass->prev_srno,
                        'admission_date' => $sessionClass->admission_date,
                        'session_id' => $sessionClass->session_id,
                        'session' => $sessionClass->session,
                        'class_id' => $sessionClass->class_id,
                        'class' => $sessionClass->class_name,
                        'section_id' => $sessionClass->section_id,
                        'section' => $sessionClass->section_name,
                        'admission_fee' => $feeMaster->admission_fee ?? 0,
                        'admission_fee_paid' => $admission_fee_paid ?? 0,
                        'inst_1' => $feeMaster->inst_1 ?? 0,
                        'inst_2' => $feeMaster->inst_2 ?? 0,
                        'inst_total' => $feeMaster->inst_total ?? 0,
                        'payable_amount' => $payableAmount,
                        'paid_amount' => $totalPaid,
                        'due_amount' => $payableAmount - $totalPaid,
                        'installments' => $this->calInstFees($sessionClass, $student, 1),
                        'transport' => [
                            'transport' => $sessionClass->transport ?? 0,
                            'inst_1' => $sessionClass->trans_1st_inst ?? 0,
                            'inst_2' => $sessionClass->trans_2nd_inst ?? 0,
                            'inst_total' => $sessionClass->trans_total ?? 0,
                            'payable_amount' => $transportPayableAmount,
                            'paid_amount' => $transportTotalPaid,
                            'due_amount' => $transportPayableAmount - $transportTotalPaid,
                            'trans_installments' => $this->calInstFees($sessionClass, $student, 2),
                        ],

                    ];
                }

                if ($student->relation_code !== null) {
                    // $result['relatives'] = [];
                    // $relatives = $baseQuery->where('relation_code', $student->relation_code)->where('srno', '!=', $student->srno)->whereNotNull('relation_code')->all();
                    $relatives = $baseQuery
                    ->filter(function ($relative) use ($student, $processedSrnos) {
                        return $relative->relation_code === $student->relation_code &&
                               $relative->srno !== $student->srno &&
                               !in_array($relative->srno, $processedSrnos);
                    });
                    foreach ($relatives as $relative) {
                        $sessionClassR = StudentMasterController::getStdWithNames(false, $fields)->where('stu_main_srno.srno', $relative->srno)
                            ->where('stu_main_srno.session_id', $request->current_session)
                            ->distinct()
                            ->first();

                        if ($sessionClassR) {
                            // Academic fee details
                            $feeDetails = FeeDetail::where('srno', $relative->srno)
                                ->where('session_id', $sessionClassR->session_id)
                                ->where('academic_trans', 1)
                                ->get();

                            $feeMaster = FeeMaster::where('session_id', $sessionClassR->session_id)
                                ->where('class_id', $sessionClassR->class_id)->where('active', 1)
                                ->first();

                            $payableAmount = $feeMaster ? (($sessionClassR->prev_srno == '' || $sessionClassR->prev_srno == null) && $sessionClassR->admission_date != '' ? $feeMaster->inst_total + $feeMaster->admission_fee : $feeMaster->inst_total) : 0;

                            // $totalPaid = $feeDetails->where('fee_of', '!=', 1)->sum('amount');
                            $totalPaid = ($sessionClassR->prev_srno == '' || $sessionClassR->prev_srno == null) && $sessionClassR->admission_date != '' ? $feeDetails->sum('amount') : $feeDetails->where('fee_of', '!=', 1)->sum('amount');

                            // Transport fee details
                            $transportFeeDetails = FeeDetail::where('srno', $relative->srno)
                                ->where('session_id', $sessionClassR->session_id)
                                ->where('academic_trans', 2)
                                ->get();

                            // $transportPayableAmount = $relative->trans_total ?? 0;
                            $transportPayableAmount = $sessionClassR->trans_total ?? 0;
                            $transportTotalPaid = $transportFeeDetails->sum('amount');

                            $result['relatives'][] = [
                                'srno' => $relative->srno,
                                'student_name' => $relative->student_name,
                                'father_name' => $relative->f_name,
                                'mother_name' => $relative->m_name,
                                'session_id' => $sessionClassR->session_id,
                                'session' => $sessionClassR->session,
                                'class' => $sessionClassR->class_name,
                                'class_id' => $sessionClassR->class_id,
                                'section' => $sessionClassR->section_name,
                                'section_id' => $sessionClassR->section_id,
                                'inst_1' => $feeMaster->inst_1 ?? 0,
                                'inst_2' => $feeMaster->inst_2 ?? 0,
                                'inst_total' => $feeMaster->inst_total ?? 0,
                                'payable_amount' => $payableAmount,
                                'paid_amount' => $totalPaid,
                                'due_amount' => $payableAmount - $totalPaid,
                                'transport' => [
                                    'inst_1' => $sessionClassR->trans_1st_inst ?? 0,
                                    'inst_2' => $sessionClassR->trans_2nd_inst ?? 0,
                                    'inst_total' => $sessionClassR->trans_total ?? 0,
                                    'payable_amount' => $transportPayableAmount,
                                    'paid_amount' => $transportTotalPaid,
                                    'due_amount' => $transportPayableAmount - $transportTotalPaid
                                ]
                            ];
                            $processedSrnos[] = $relative->srno; // Mark relative as processed
                        }
                    }
                }
                $finalResult[] = $result;
                $processedSrnos[] = $student->srno; // Mark student as processed
            }

            return response()->json([
                'status' => 'success',
                'data' => $finalResult
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => "Failed to get Due Payment list"
            ], 500);
        }
    }




    private function calInstFees($sessionClass, $student, $academicTrans)
    {

        if ($academicTrans == 2) {
            # code...
            $feeDetails = FeeDetail::where('srno', $student->srno)
                ->where('session_id', $sessionClass->session_id)
                ->where('academic_trans', $academicTrans)
                ->get(['fee_of', 'amount', 'pay_date', 'recp_no', 'ref_slip_no', 'paid_mercy']);
        } else {
            $feeDetails = FeeDetail::where('srno', $student->srno)
                ->where('session_id', $sessionClass->session_id)
                ->where('academic_trans', $academicTrans)
                ->where('fee_of', '!=', 1)
                ->get(['fee_of', 'amount', 'pay_date', 'recp_no', 'ref_slip_no', 'paid_mercy']);
        }


        $installmentResults = [
            'first_inst' => $feeDetails->where('fee_of', $academicTrans == 2 ? 1 : 2)->values(),
            'second_inst' => $feeDetails->where('fee_of', $academicTrans == 2 ? 2 : 3)->values(),
            'complete_inst' => $feeDetails->where('fee_of', $academicTrans == 2 ? 3 : 4)->where('paid_mercy', 1)->values(),
            'mercy' => $feeDetails->where('fee_of', $academicTrans == 2 ? 3 : 4)->where('paid_mercy', 2)->values(),
        ];

        return $installmentResults;
    }


    /**
     * academic back session fee entry view
     */

    public function academicBackSessionFeeEntry($session, $srno, $class, $section)
    {
        if ($session && $srno && $class && $section) {
            return view('fee.fee_entry.back_session_fee_entry', compact('session', 'srno', 'class', 'section'));
        }
    }
    public function transBackSessionFeeEntry($session, $srno, $class, $section)
    {
        if ($session && $srno && $class && $section) {
            return view('fee.fee_entry.back_session_transport_fee_entry', compact('session', 'srno', 'class', 'section'));
        }
    }

    public function relativewiseFeeDetails()
    {
        # code...
        $currentSession = session('fee_current_session')->id;
        if ($currentSession !== '') {
            # code...
            $feeDetail = FeeDetail::where('session_id', $currentSession);
            $totalAcademic = $feeDetail->where('academic_trans', 1)->sum('amount');
            $totalTrans = $feeDetail->where('academic_trans', 2)->sum('amount');

            // $std = StudentMaster::where('session_id', $currentSession)->where('active', 1)->whereIn('ssid', [1,2,4,5])->where('transport', 1)->sum('trans_total');
            $std = StudentMasterController::getStdWithNames(false, ['stu_main_srno.session_id', 'stu_main_srno.transport', 'stu_main_srno.trans_total'])->where('stu_main_srno.session_id', $currentSession)->where('stu_main_srno.transport', 1)->sum('stu_main_srno.trans_total');
            $feeMaster = FeeMaster::where('session_id', $currentSession)->where('active', 1)->sum('inst_total');

            $academicDue = $feeMaster - $totalAcademic;
            $transDue = $std - $totalTrans;

            return view('fee.fee_details.relativewise_fee_detail', compact('totalAcademic', 'totalTrans', 'std', 'feeMaster', 'academicDue', 'transDue'));
        }
    }

    public function individualFeeDetail($st, $session, $class, $section)
    {
        if ($st && $session && $class && $section) {
            # code...
            return view('fee.fee_details.individual_fee_detail', compact('st', 'session', 'class', 'section'));
        }
    }


    public function backSessionFeeDetails()
    {
        $sessions = SessionMasterController::getSessions(['id', 'session']);
        return view('fee.fee_details.back_session_fee_detail', compact('sessions'));
    }

    /**
     * student without ssid
     */




    public function studentWithoutSsid(Request $request)
    {
        // $st = StudentMaster::where('session_id', $request->session);
        try {
            $students = collect();
            $fields = [
                'stu_main_srno.srno',
                'stu_main_srno.prev_srno',
                'stu_main_srno.admission_date',
                'stu_main_srno.class',
                'stu_main_srno.section',
                'stu_main_srno.session_id',
                'stu_main_srno.transport',
                'stu_main_srno.trans_1st_inst',
                'stu_main_srno.trans_2nd_inst',
                'stu_main_srno.trans_total',
                'stu_main_srno.ssid',
                'stu_main_srno.active',
                'stu_detail.name',
                'parents_detail.f_name',
                'parents_detail.f_mobile',
                'class_masters.class as class_name',
                'section_masters.section as section_name',
            ];
            if (!empty($request->session) && !empty($request->class) && !empty($request->section)) {
                $class = explode(',', $request->class);
                $section = explode(',', $request->section);

                $students = StudentMasterController::getStdWithNames(false, $fields)->where('stu_main_srno.session_id', $request->session)->whereIn('stu_main_srno.class', $class)->whereIn('stu_main_srno.section', $section)->get();
            }

            $srno = explode(',', $request->srno);
            if (!empty($request->page) && !empty($request->srno)) {
                # code..
                $class = explode(',', $request->class);
                $section = explode(',', $request->section);
                // $students = StudentMaster::where('session_id', $request->session)
                $students = StudentMasterController::getStdWithNames(false, $fields)->where('stu_main_srno.session_id', $request->session)->whereIn('stu_main_srno.class', $class)->whereIn('stu_main_srno.section', $section)->whereIn('stu_main_srno.srno', $srno)->orderBy('stu_main_srno.class')->paginate(10);
            } elseif (!empty($request->srno) && !empty($request->session)) {
                $class = explode(',', $request->class);
                $section = explode(',', $request->section);
                $students = StudentMasterController::getStdWithNames(false, $fields)->where('stu_main_srno.session_id', $request->session)->whereIn('stu_main_srno.class', $class)->whereIn('stu_main_srno.section', $section)->whereIn('stu_main_srno.srno', $srno)->orderBy('stu_main_srno.class')->get();
            }


            $result = [];
            foreach ($students as $key => $st) {

                $feeDetails = FeeDetail::where('srno', $st->srno)->where('session_id', $st->session_id);
                // dd($feeDetails->get());

                // $feeDetail = $feeDetails->where('fee_of', '!=', 1);
                $feeDetail = ($st->prev_srno == '' || $st->prev_srno == null) && $st->admission_date != '' ? $feeDetails : $feeDetails->where('fee_of', '!=', 1);

                $feeMaster = FeeMaster::where('session_id', $st->session_id)->where('class_id', $st->class)->where('active', 1)->first();
                $payableAmount = $feeMaster ? (($st->prev_srno == '' || $st->prev_srno == null) && $st->admission_date != '' ? $feeMaster->inst_total + $feeMaster->admission_fee : $feeMaster->inst_total) : 0;
                $feeDetailFirst = $feeDetail->where('academic_trans', 1)->sum('amount');
                $totalPaid = $feeDetailFirst;

                $admissionFee = $feeMaster ? $feeMaster->admission_fee : 0;
                $admissionFeePaid = FeeDetail::where('srno', $st->srno)->where('session_id', $st->session_id)->where('fee_of', 1)->where('academic_trans', 1)->sum('amount');

                $transPayable = $st ? $st->trans_total : 0;
                $transPaid = FeeDetail::where('srno', $st->srno)->where('session_id', $st->session_id)->where('academic_trans', 2)->sum('amount');

                $result[] = [
                    'student' => $st,
                    'student_name' => $st->name,
                    'father_name' => $st->f_name,
                    'class_name' => $st->class_name,
                    'section_name' => $st->section_name,
                    'admission_fee' => $admissionFee,
                    'admission_fee_paid' => $admissionFeePaid,
                    'inst_1' => $feeMaster->inst_1 ?? 0,
                    'inst_2' => $feeMaster->inst_2 ?? 0,
                    'inst_total' => $feeMaster->inst_total ?? 0,
                    'payable_amount' => $payableAmount,
                    'paid_amount' => $totalPaid,
                    'due_amount' => $payableAmount - $totalPaid,
                    'installments' => $this->calInstFees($st, $st, 1),
                    'transport' => $st->transport ?? 0,
                    'trans_inst_1' => $st->trans_1st_inst ?? 0,
                    'trans_inst_2' => $st->trans_2nd_inst ?? 0,
                    'trans_inst_total' => $st->trans_total ?? 0,
                    'trans_payable_amount' => $transPayable,
                    'trans_paid_amount' => $transPaid,
                    'trans_due_amount' => $transPayable - $transPaid,
                    'trans_installments' => $this->calInstFees($st, $st, 2),
                ];
            }

            return response()->json([
                'status' => 'success',
                'data' => $result,
                'pagination' => isset($request->page) ?
                    [
                        'total' => $students->total(),
                        'per_page' => $students->perPage(),
                        'current_page' => $students->currentPage(),
                        'last_page' => $students->lastPage(),
                        'from' => $students->firstItem(),
                        'to' => $students->lastItem(),
                    ] : [],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => "Failed to get Students"
            ], 500);
        }
    }

    public function backSessionIndividualFeeDetail($st, $session, $class, $section)
    {
        if ($st && $session && $class && $section) {
            # code...
            return view('fee.fee_details.back_session_individual_fee_detail', compact('st', 'session', 'class', 'section'));
        }
    }

    public function printDueReceipt()
    {
        $classes = ClassMasterController::getClasses();
        return view('fee.fee_details.print_due_receipt', compact('classes'));
    }

    public function dueFeeReport()
    {
        $classes = ClassMasterController::getClasses();
        return view('fee.fee_details.due_fee_report', compact('classes'));
    }
    public function dueFeeReportSMS()
    {
        $classes = ClassMasterController::getClasses();
        return view('fee.fee_details.due_fee_report_sms', compact('classes'));
    }


    // relative-wise fee report as excel file



    public function exportRelativeWiseFeeReport(Request $request)
    {
        try {
            $response = $this->academicFeeDueAmount($request);

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

            $reportDatas = $decodedResponse['data'] ?? null;
            if (!$reportDatas) {
                return response()->json(['status' => 'error', 'message' => 'No data found'], 404);
            }

            $fileName = 'relative_wise_fee_report.csv';
            $csvContent = '';
            $output = fopen('php://memory', 'w');
            if ($output === false) {
                throw new \Exception('Failed to open output stream.');
            }

            // Set the CSV column headers
            $headers = ['Class', 'Section', 'Name', "Father's Name", 'Payable Amount(Ac.)', 'Paid Amount(Ac.)', 'Due Amount(Ac.)', 'Payable Amount(Tr.)', 'Paid Amount(Tr.)', 'Due Amount(Tr.)', 'Payable Amount(St.)', 'Paid Amount(St.)', 'Due Amount(St.)'];
            fputcsv($output, $headers);

            // Process main student's sessions
            foreach ($reportDatas as $reportData) {
                if (isset($reportData['sessions']) && is_array($reportData['sessions'])) {
                    foreach ($reportData['sessions'] as $session) {
                        if (isset($session['session_id']) && $session['session_id'] == $request->current_session) {
                            fputcsv($output, [
                                $session['class'],
                                $session['section'],
                                $reportData['student_name'],
                                $reportData['father_name'],
                                $session['payable_amount'],
                                $session['paid_amount'],
                                $session['due_amount'],
                                $session['transport']['payable_amount'],
                                $session['transport']['paid_amount'],
                                $session['transport']['due_amount'],
                                'N/A', // St. Payable Amount
                                'N/A', // St. Paid Amount
                                'N/A'  // St. Due Amount
                            ]);
                        }
                    }
                }

                // Process relatives
                if (isset($reportData['relatives']) && is_array($reportData['relatives'])) {
                    foreach ($reportData['relatives'] as $relative) {
                        fputcsv($output, [
                            $relative['class'],
                            $relative['section'],
                            $relative['student_name'],
                            $relative['father_name'],
                            $relative['payable_amount'],
                            $relative['paid_amount'],
                            $relative['due_amount'],
                            $relative['transport']['payable_amount'],
                            $relative['transport']['paid_amount'],
                            $relative['transport']['due_amount'],
                            'N/A', // St. Payable Amount
                            'N/A', // St. Paid Amount
                            'N/A'  // St. Due Amount
                        ]);
                    }
                }
            }

            // Rewind the memory to the start
            rewind($output);

            // Capture the content into a string
            $csvContent = stream_get_contents($output);

            fclose($output);

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
    // back-session fee report as excel file

    // export report
    public function exportBackSessionFeeReport(Request $request)
    {
        try {
            $response = $this->studentWithoutSsid($request);


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
            // return $reportData;
            if (!$reportData) {
                return response()->json(['status' => 'error', 'message' => 'No data found'], 404);
            }
            $fileName = 'back_session_fee_report.csv';
            // $output = fopen('php://output', 'w');
            $csvContent = '';
            $output = fopen('php://memory', 'w');
            if ($output === false) {
                throw new \Exception('Failed to open output stream.');
            }
            // Set the CSV column headers
            $headers = ['Class', 'Section', 'Name', "Father's Name", 'Payable(Ac.)', 'Paid(Ac.)', 'Due(Ac.)', 'Payable(Tr.)', 'Paid(Tr.)', 'Due(Tr.)', 'Total Due', 'Status'];
            fputcsv($output, $headers);

            foreach ($reportData as $row) {
                $totalDue = $row['due_amount'] + $row['trans_due_amount'];
                if ($request->reportType === 'complete' || $totalDue > 0) {

                    fputcsv($output, [
                        $row['class_name'],
                        $row['section_name'],
                        $row['student_name'],
                        $row['father_name'],
                        $row['payable_amount'],
                        $row['paid_amount'],
                        $row['due_amount'],
                        $row['trans_payable_amount'],
                        $row['trans_paid_amount'],
                        $row['trans_due_amount'],
                        $totalDue,
                        $row['student']['ssid'] == 1 ? 'Active' : ($row['student']['ssid'] == 2 ? 'Class Promoted' : ($row['student']['ssid'] == 3 ? 'School Promoted' : ($row['student']['ssid'] == 4 ? 'Tc' : ($row['student']['ssid'] == 5 ? 'Left Out' : ''))))
                    ]);
                }
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
                'message' => "Failed to export report"
            ], 500);
        }
    }

    // due fee report as excel file

    // export report
    public function exportDueFeeReport(Request $request)
    {
        try {
            $response = $this->studentWithoutSsid($request);

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
            // return $reportData;
            if (!$reportData) {
                return response()->json(['status' => 'error', 'message' => 'No data found'], 404);
            }

            $fileName = 'due_fee_report.csv';
            $csvContent = '';
            $output = fopen('php://memory', 'w');
            if ($output === false) {
                throw new \Exception('Failed to open output stream.');
            }
            // Set the CSV column headers
            $headers = ['Class', 'Section', 'Name', "Father's Name", 'Payable(Ac.)', 'Paid(Ac.)', 'Due(Ac.)', 'Payable(Tr.)', 'Paid(Tr.)', 'Due(Tr.)', 'Total Due'];
            fputcsv($output, $headers);

            foreach ($reportData as $row) {
                $academicInstAmount = array_reduce($row['installments']['first_inst'] ?? [], function ($total, $inst) {
                    return $total + ($inst['amount'] ?? 0);
                }, 0);

                // Calculate transport amounts
                $transportInstAmount = array_reduce($row['trans_installments']['first_inst'] ?? [], function ($total, $inst) {
                    return $total + ($inst['amount'] ?? 0);
                }, 0);

                // Calculate due amounts matching frontend logic
                $academicDue = $request->reportType == 'complete'
                    ? ($row['payable_amount'] ?? 0) - ($row['paid_amount'] ?? 0)
                    : ($row['inst_1'] ?? 0) - $academicInstAmount;

                $transportDue = $row['transport'] == 1
                    ? ($request->reportType == 'complete'
                        ? ($row['trans_payable_amount'] ?? 0) - ($row['trans_paid_amount'] ?? 0)
                        : ($row['trans_inst_1'] ?? 0) - $transportInstAmount)
                    : 0;

                $totalDue = $academicDue + $transportDue;
                if (($request->reportType === 'complete' || $totalDue > 0) || ($request->reportType === 'firstInstDue' && $totalDue > 0)) {
                    fputcsv($output, [
                        $row['class_name'],
                        $row['section_name'],
                        $row['student_name'],
                        $row['father_name'],
                        $request->reportType == 'firstInstDue' ? $row['inst_1'] : $row['payable_amount'],
                        $request->reportType == 'firstInstDue' ? $academicInstAmount : $row['paid_amount'],
                        $academicDue,
                        $row['transport'] == 1 ? ($request->reportType == 'firstInstDue' ? ($row['trans_inst_1'] ?? 0) : ($row['trans_payable_amount'] ?? 0)) : 0,
                        $row['transport'] == 1 ? ($request->reportType == 'firstInstDue' ? $transportInstAmount : ($row['trans_paid_amount'] ?? 0)) : 0,
                        $transportDue,
                        $totalDue
                    ]);
                }
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
                'message' => "Failed to export report"
            ], 500);
        }
    }

    public function sendSMSSt(Request $request)
    {
        // Validate input data
        $request->validate([
            'session' => 'required',
            'class' => 'required',
            'section' => 'required',
            'srno' => 'nullable|string',
            'reportType' => 'required|integer',
            'message' => 'required|string', // Ensure the message is required
        ]);

        // Check if the API key is provided
        $apiKey = ''; // Replace this with your API key configuration
        if (empty($apiKey)) {
            return response()->json([
                'status' => 'error',
                'message' => 'SMS will be sent after the API integration.',
            ], 501); // HTTP 501 Not Implemented
        }

        // Fetch data from the studentWithoutSsid method
        $response = $this->studentWithoutSsid($request);

        if ($response->getStatusCode() !== 200) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to generate report: ' . $response->getContent(),
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

        $allStData = [];
        foreach ($reportData as $value) {
            $firstInst = $value['installments']['first_inst'] ?? [];
            $secondInst = $value['installments']['second_inst'] ?? [];
            $transFirstInst = $value['trans_installments']['first_inst'] ?? [];
            $transSecondInst = $value['trans_installments']['second_inst'] ?? [];

            $academicInstAmount = $request->reportType == 1
                ? array_sum(array_column($firstInst, 'amount'))
                : array_sum(array_column($secondInst, 'amount'));

            $transportInstAmount = $request->reportType == 1
                ? array_sum(array_column($transFirstInst, 'amount'))
                : array_sum(array_column($transSecondInst, 'amount'));

            $academicDue = $request->reportType == 1
                ? $value['inst_1'] - $academicInstAmount
                : $value['inst_2'] - $academicInstAmount;

            $transportDue = $value['transport'] == 1
                ? ($request->reportType == 1
                    ? ($value['trans_inst_1'] ?? 0) - $transportInstAmount
                    : ($value['trans_inst_2'] ?? 0) - $transportInstAmount)
                : 0;

            $totalDue = $academicDue + $transportDue;

            if (($request->reportType == 1 || $request->reportType == 2) && $totalDue > 0) {
                if (!empty($value['student']['f_mobile'])) {
                    $allStData[] = [
                        'student_name' => $value['student_name'],
                        'f_mobile' => $value['student']['f_mobile'],
                        'srno' => $value['student']['srno'],
                        'totalDue' => $totalDue,
                        'reportType' => $request->reportType,
                    ];
                }
            }
        }

        if (empty($allStData)) {
            return response()->json(['status' => 'error', 'message' => 'No students found with valid mobile numbers'], 404);
        }

        // Send SMS to all students
        foreach ($allStData as $student) {
            $message = str_replace("{%student_name%}", $student['student_name'], $request->message);
            $message = str_replace("{%total_due%}", $student['totalDue'], $message);

            // Check if the mobile number is valid
            if (preg_match('/^[0-9]{10}$/', $student['f_mobile'])) {
                $apiUrl = "http://www.dakshinfosoft.com/api/sendhttp.php";
                $queryParams = http_build_query([
                    'authkey' => $apiKey,
                    'mobiles' => $student['f_mobile'],
                    'message' => $message,
                    'sender' => 'SVPSCH',
                    'route' => 6,
                    'unicode' => 1,
                    'country' => 0,
                ]);

                $fullUrl = $apiUrl . '?' . $queryParams;

                $smsResponse = file_get_contents($fullUrl); // Sends the request to the SMS API
                if (!$smsResponse) {
                    return response()->json(['status' => 'error', 'message' => 'Failed to send SMS to ' . $student['f_mobile']], 500);
                }
            } else {
                return response()->json(['status' => 'error', 'message' => 'Invalid mobile number for student: ' . $student['student_name']], 400);
            }
        }

        return response()->json(['status' => 'success', 'message' => 'SMS sent successfully'], 200);
    }

}
