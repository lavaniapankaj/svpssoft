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
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class FeeEntryController extends Controller
{
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
        return view('fee.fee_entry.academic_fee', compact('classes'));
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
            $session = isset($request->session) ? $request->session : Session::get('fee_current_session')->id;
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
                if ($student->transport == 0) {
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


            $admission_fee_paid = FeeDetail::where('srno', $request->std_id)->where('academic_trans', $academic_trans_value)->where('active', 1)->where('fee_of', 1)->exists();
            $baseQuery = FeeDetail::where('srno', $request->std_id)->where('session_id', $session)->where('active', 1)->where('academic_trans', $academic_trans_value)->get();
            $first_inst_fee_total = $baseQuery->where('fee_of', isset($request->transport) ? 1 : 2)->where('paid_mercy', 1)->sum('amount');
            $second_inst_fee_total = $baseQuery->where('fee_of', isset($request->transport) ? 2 : 3)->where('paid_mercy', 1)->sum('amount');
            $complete_fee_total = $baseQuery->where('fee_of', isset($request->transport) ? 3 : 4)->where('paid_mercy', 1)->sum('amount');
            $mercy_fee_total = $baseQuery->where('fee_of', isset($request->transport) ? 3 : 4)->where('paid_mercy', 2)->sum('amount');

            $first_inst_fee_exists = FeeDetail::where('srno', $request->std_id)->where('session_id', $session)->where('academic_trans', $academic_trans_value)->where('fee_of',  isset($request->transport) ? 1 : 2)->where('paid_mercy', 1)->where('active', 1)->exists();
            $second_inst_fee_exists = FeeDetail::where('srno', $request->std_id)->where('session_id', $session)->where('academic_trans', $academic_trans_value)->where('fee_of', isset($request->transport) ? 2 : 3)->where('paid_mercy', 1)->where('active', 1)->exists();
            $complete_fee_exists = FeeDetail::where('srno', $request->std_id)->where('session_id', $session)->where('academic_trans', $academic_trans_value)->where('fee_of', isset($request->transport) ? 3 : 4)->where('paid_mercy', 1)->where('active', 1)->exists();
            $mercy_fee_exists = FeeDetail::where('srno', $request->std_id)->where('session_id', $session)->where('academic_trans', $academic_trans_value)->where('fee_of', isset($request->transport) ? 3 : 4)->where('paid_mercy', 2)->where('active', 1)->exists();
            $uniqueRefSlip = $sessionName . $request->ref_slip;
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
                            ->where('active', 1)
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
                        } elseif ($admission_fee_paid == true) {
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
                            $totalDue = $fee_master_fees->inst_1 - ($first_inst_fee_total + isset($request->transport) ? $complete_fee_total : $mercy_fee_total);
                        }
                        if ($totalDue <= 0) {
                            $fail('Already Paid First Installment.');
                            return; // Stop further validations
                        } elseif ($value > $totalDue && $first_inst_fee_exists == true) {
                            $fail('Only ' . $totalDue . ' due of First Installment.');
                        }
                    },
                    function ($attribute, $value, $fail) use ($request, $fee_master_fees, $complete_fee_total, $first_inst_fee_total, $mercy_fee_total) {
                        $totalWithMercy = $first_inst_fee_total + isset($request->transport) ? $complete_fee_total : $mercy_fee_total;
                        if ($totalWithMercy == $fee_master_fees->inst_1) {
                            $fail('Already Paid First Installment.');
                            return; // Stop further validations
                        }
                    },
                    function ($attribute, $value, $fail) use ($request, $fee_master_fees, $complete_fee_total, $first_inst_fee_exists, $second_inst_fee_total, $mercy_fee_exists, $mercy_fee_total, $second_inst_fee_exists, $complete_fee_exists) {
                        if (($second_inst_fee_exists == true || (isset($request->transport) ? $complete_fee_exists : $mercy_fee_exists == true)) && $first_inst_fee_exists == false) {
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
                            $totalDue = $fee_master_fees->inst_2 - ($second_inst_fee_total + isset($request->transport) ? $complete_fee_total : $mercy_fee_total);
                        }
                        $totalWithMercy = $second_inst_fee_total + isset($request->transport) ? $complete_fee_total : $mercy_fee_total;
                        if (($totalDue <= 0) || ($totalWithMercy == $fee_master_fees->inst_2)) {
                            $fail('Already Paid Second Installment.');
                            return; // Stop further validations
                        } elseif ($value > $totalDue && $second_inst_fee_exists == true) {
                            $fail('Only ' . $totalDue . ' due of Second Installment.');
                        }
                    },
                    function ($attribute, $value, $fail) use ($request, $fee_master_fees, $first_inst_fee_exists, $first_inst_fee_total, $mercy_fee_exists, $mercy_fee_total, $complete_fee_total, $second_inst_fee_exists, $complete_fee_exists) {
                        if (($first_inst_fee_exists == true || (isset($request->transport) ? $complete_fee_exists : $mercy_fee_exists == true)) && $second_inst_fee_exists == false) {
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
                        if (($totalDue <= 0) || ($totalWithMercy == $fee_master_fees->inst_total)) {
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
                            ->where('session_id', $session)->where('active', 1)
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
                                $totalPaid = $first_inst_fee_total + $second_inst_fee_total + $complete_fee_total + $mercy_fee_total;
                                if ($totalPaid == $fee_master_fees->inst_total) {
                                    $fail('Already Paid Complete Fee.');
                                    return; // Stop further validations
                                } else {
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
                'fee_mode' => $request->fee_mode ?? 1,
                'payment_note' => $request->payment_note ?? 1,
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
                'message' => "Failed to submit fee."
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
                'stu_main_srno.ssid',
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
                    $feeDetail = FeeDetail::where('srno', $student->srno)->where('session_id', $sessionClass->session_id)->where('active', 1);
                    $feeDetail = (($sessionClass->prev_srno == '' || $sessionClass->prev_srno == null) && $sessionClass->admission_date != '') ? $feeDetail : $feeDetail->where('fee_of', '!=', 1);
                    $feeDetails = $feeDetail->where('academic_trans', 1)->get();
                    $admission_fee_paid = FeeDetail::where('srno', $student->srno)->where('session_id', $sessionClass->session_id)->where('fee_of', 1)->where('active', 1)->sum('amount');
                    $feeMaster = FeeMaster::where('session_id', $sessionClass->session_id)->where('class_id', $sessionClass->class_id)->where('active', 1)->first();
                    $payableAmount = $feeMaster ? (($sessionClass->prev_srno == '' || $sessionClass->prev_srno == null) && $sessionClass->admission_date != '' ?  $feeMaster->inst_total + $feeMaster->admission_fee : $feeMaster->inst_total) : 0;
                    $feeDetailFirst = $feeDetails->sum('amount');
                    $totalPaid = $feeDetailFirst;
                    // Transport fee details
                    $transportFeeDetails = FeeDetail::where('srno', $student->srno)
                        ->where('session_id', $sessionClass->session_id)->where('active', 1)
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
                    // dd($baseQuery);
                    $relatives = StudentMasterController::getStdWithNames(false, $stdFields)
                        ->where('stu_main_srno.relation_code', $student->relation_code)
                        ->where('stu_main_srno.srno', '!=', $student->srno)
                        ->whereNotIn('stu_main_srno.srno', $processedSrnos)
                        ->get()
                        ->unique('srno');
                    /*  $relatives = $baseQuery
                        ->filter(function ($relative) use ($student, $processedSrnos) {
                            return $relative->relation_code == $student->relation_code &&
                                $relative->srno != $student->srno &&
                                !in_array($relative->srno, $processedSrnos);
                        }); */
                    foreach ($relatives as $relative) {
                        $sessionClassR = StudentMasterController::getStdWithNames(false, $fields)->where('stu_main_srno.srno', $relative->srno)
                            ->where('stu_main_srno.session_id', $request->current_session)
                            ->distinct()
                            ->first();
                        if (!empty($sessionClassR)) {
                            // Academic fee details
                            $feeDetails = FeeDetail::where('srno', $relative->srno)
                                ->where('session_id', $sessionClassR->session_id)->where('active', 1)
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
                                ->where('session_id', $sessionClassR->session_id)->where('active', 1)
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
            $feeDetails = FeeDetail::where('srno', $student->srno)
                ->where('session_id', $sessionClass->session_id)
                ->where('academic_trans', $academicTrans)->where('active', 1)
                ->get(['fee_of', 'amount', 'pay_date', 'recp_no', 'ref_slip_no', 'paid_mercy']);
        } else {
            $feeDetails = FeeDetail::where('srno', $student->srno)
                ->where('session_id', $sessionClass->session_id)
                ->where('academic_trans', $academicTrans)->where('active', 1)
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
        $classes = ClassMasterController::getClasses();
        return view('fee.fee_details.relativewise_fee_detail', compact('classes'));
    }
    public function individualFeeDetail($st, $session, $class, $section)
    {
        if ($st && $session && $class && $section) {
            return view('fee.fee_details.individual_fee_detail', compact('st', 'session', 'class', 'section'));
        }
    }

    public function backSessionFeeDetails()
    {
        $sessions = SessionMasterController::getSessions(['id', 'session']);
        $classes = ClassMasterController::getClasses();
        return view('fee.fee_details.back_session_fee_detail', compact('sessions', 'classes'));
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
            /* if (!empty($request->page) && !empty($request->srno)) {
                $class = explode(',', $request->class);
                $section = explode(',', $request->section);
                // $students = StudentMaster::where('session_id', $request->session)
                $students = StudentMasterController::getStdWithNames(false, $fields)->where('stu_main_srno.session_id', $request->session)->whereIn('stu_main_srno.class', $class)->whereIn('stu_main_srno.section', $section)->whereIn('stu_main_srno.srno', $srno)->orderBy('stu_main_srno.class')->paginate(10);
            } else */
            if (!empty($request->srno) && !empty($request->session)) {
                $class = explode(',', $request->class);
                $section = explode(',', $request->section);
                $students = StudentMasterController::getStdWithNames(false, $fields)->where('stu_main_srno.session_id', $request->session)->whereIn('stu_main_srno.class', $class)->whereIn('stu_main_srno.section', $section)->whereIn('stu_main_srno.srno', $srno)->orderBy('stu_main_srno.class')->get();
            }

            // $result = [];
            $result = collect();
            foreach ($students as $key => $st) {
                $feeDetails = FeeDetail::where('srno', $st->srno)->where('session_id', $st->session_id)->where('active', 1);
                $feeDetail = ($st->prev_srno == '' || $st->prev_srno == null) && $st->admission_date != '' ? $feeDetails : $feeDetails->where('fee_of', '!=', 1);
                $feeMaster = FeeMaster::where('session_id', $st->session_id)->where('class_id', $st->class)->where('active', 1)->first();
                $payableAmount = $feeMaster ? (($st->prev_srno == '' || $st->prev_srno == null) && $st->admission_date != '' ? $feeMaster->inst_total + $feeMaster->admission_fee : $feeMaster->inst_total) : 0;
                $feeDetailFirst = $feeDetail->where('academic_trans', 1)->sum('amount');
                $totalPaid = $feeDetailFirst;
                $admissionFee = $feeMaster ? $feeMaster->admission_fee : 0;
                $admissionFeePaid = FeeDetail::where('srno', $st->srno)->where('session_id', $st->session_id)->where('fee_of', 1)->where('academic_trans', 1)->where('active', 1)->sum('amount');
                $transPayable = $st ? $st->trans_total : 0;
                $transPaid = FeeDetail::where('srno', $st->srno)->where('session_id', $st->session_id)->where('academic_trans', 2)->where('active', 1)->sum('amount');
                $allDueAmount = $payableAmount - $totalPaid + $transPayable - $transPaid;
                if ($request->reportType == 'due' && $allDueAmount <= 0) {
                    continue;
                }
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
            // 2. Apply pagination only if requested
            if (!empty($request->page)) {
                $page = (int) $request->page;
                $perPage = 10;
                $total = $result->count();
                $sliced = $result->slice(($page - 1) * $perPage, $perPage)->values();
                $paginator = new LengthAwarePaginator(
                    $sliced,
                    $total,
                    $perPage,
                    $page,
                    ['path' => $request->url(), 'query' => $request->query()]
                );

                return response()->json([
                    'status' => 'success',
                    'data' => $paginator->items(),
                    'pagination' => [
                        'total' => $paginator->total(),
                        'per_page' => $paginator->perPage(),
                        'current_page' => $paginator->currentPage(),
                        'last_page' => $paginator->lastPage(),
                        'from' => $paginator->firstItem(),
                        'to' => $paginator->lastItem(),
                    ]
                ]);
            }

            // 3. Return full list if no pagination requested
            return response()->json([
                'status' => 'success',
                'data' => $result,
                'pagination' => [],
            ]);
            /*   return response()->json([
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
            ]); */
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => "Failed to get Students"], 500);
        }
    }
    public function backSessionIndividualFeeDetail($st, $session, $class, $section)
    {
        if ($st && $session && $class && $section) {
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
                if (($request->reportType === 'complete' && $totalDue > 0) || ($request->reportType === 'firstInstDue' && $totalDue > 0)) {
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
            $academicInstAmount = $request->reportType == 1 ? array_sum(array_column($firstInst, 'amount')) : array_sum(array_column($secondInst, 'amount'));
            $transportInstAmount = $request->reportType == 1 ? array_sum(array_column($transFirstInst, 'amount')) : array_sum(array_column($transSecondInst, 'amount'));
            $academicDue = $request->reportType == 1 ? $value['inst_1'] - $academicInstAmount : $value['inst_2'] - $academicInstAmount;
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

    /** Single Student Academice fee  according to the month */
    public function singleStAcademiceFee(Request $request)
    {
        $current_session = Session::get('fee_current_session')->id;
        $student = StudentMaster::where('srno', $request->std_id)->where('class', $request->class)->where('section', $request->section)->where('session_id', $current_session)->where('active', 1)->first();
        $feeMaster = FeeMaster::where('class_id', $request->class)->where('session_id', $current_session)->where('active', 1)->first(['admission_fee', 'inst_1', 'inst_2', 'inst_total', 'ins_discount']);
        $admission_fee_paid = FeeDetail::where('srno', $request->std_id)->where('academic_trans', 1)->where('active', 1)->where('fee_of', 1)->exists();
        $first_inst_fee_total = FeeDetail::where('srno', $request->std_id)->where('session_id', $current_session)->where('active', 1)->where('academic_trans', 1)->where('fee_of', 2)->where('paid_mercy', 1)->sum('amount');
        $second_inst_fee_total = FeeDetail::where('srno', $request->std_id)->where('session_id', $current_session)->where('active', 1)->where('academic_trans', 1)->where('fee_of', 3)->where('paid_mercy', 1)->sum('amount');
        $complete_fee_total = FeeDetail::where('srno', $request->std_id)->where('session_id', $current_session)->where('active', 1)->where('academic_trans', 1)->where('fee_of', 4)->where('paid_mercy', 1)->sum('amount');
        $mercy_fee_total = FeeDetail::where('srno', $request->std_id)->where('session_id', $current_session)->where('active', 1)->where('academic_trans', 1)->where('fee_of', 4)->where('paid_mercy', 2)->sum('amount');


        $first_inst_fee_exists = FeeDetail::where('srno', $request->std_id)->where('session_id', $current_session)->where('academic_trans', 1)->where('fee_of', 2)->where('paid_mercy', 1)->where('active', 1)->exists();
        $second_inst_fee_exists = FeeDetail::where('srno', $request->std_id)->where('session_id', $current_session)->where('academic_trans', 1)->where('fee_of', 3)->where('paid_mercy', 1)->where('active', 1)->exists();
        $complete_fee_exists = FeeDetail::where('srno', $request->std_id)->where('session_id', $current_session)->where('academic_trans', 1)->where('fee_of', 4)->where('paid_mercy', 1)->where('active', 1)->exists();
        $mercy_fee_exists = FeeDetail::where('srno', $request->std_id)->where('session_id', $current_session)->where('academic_trans', 1)->where('fee_of', 4)->where('paid_mercy', 2)->where('active', 1)->exists();


        $admissionFeeTotal = 0;
        $firstInstFeeDueTotal = 0;
        $secondInstFeeDueTotal = 0;
        $completeFeeDueTotal = 0;
        $mercyFeeDueTotal = 0;

        $totalPaid = 0;
        $totalPaid += $first_inst_fee_total;
        $totalPaid += $second_inst_fee_total;
        $totalPaid += $complete_fee_total;
        $totalPaid += $mercy_fee_total;
        if (is_null($student->admission_date) || $admission_fee_paid == true) {
            $admissionFeeTotal = 0;
        } elseif ($admission_fee_paid == true) {
            $admissionFeeTotal = 0;
        } else {
            $admissionFeeTotal = $feeMaster->admission_fee;
        }

        if ($first_inst_fee_total == $feeMaster->inst_1) {
            $firstInstFeeDueTotal = 0;
        } elseif ($first_inst_fee_total < $feeMaster->inst_1 && $complete_fee_exists == false && $mercy_fee_exists == false) {
            $firstInstFeeDueTotal = $feeMaster->inst_1 - $first_inst_fee_total;
        }

        if ($second_inst_fee_total == $feeMaster->inst_2) {
            $secondInstFeeDueTotal = 0;
        } elseif ($second_inst_fee_total < $feeMaster->inst_2 && $complete_fee_exists == false && $mercy_fee_exists == false) {
            $secondInstFeeDueTotal = $feeMaster->inst_2 - $second_inst_fee_total;
        }
        if ($complete_fee_exists == true) {
            // $completeFeeDueTotal = $feeMaster->inst_total - $complete_fee_total;
            $completeFeeDueTotal = $feeMaster->inst_total - $totalPaid;
        }
        if ($mercy_fee_exists == true) {
            $mercyFeeDueTotal = $feeMaster->inst_total - $totalPaid;
        }

        return response()->json([
            'admissionFeeTotal' => $admissionFeeTotal,
            'firstInstFeeDueTotal' => $firstInstFeeDueTotal,
            'secondInstFeeDueTotal' => $secondInstFeeDueTotal,
            'completeFeeDueTotal' => $completeFeeDueTotal,
            'mercyFeeDueTotal' => $mercyFeeDueTotal,
        ]);
    }
    public function singleStTransportFee(Request $request)
    {
        $current_session = Session::get('fee_current_session')->id;
        $student = StudentMaster::where('srno', $request->std_id)->where('class', $request->class)->where('section', $request->section)->where('session_id', $current_session)->where('active', 1)->first();
        $first_inst_fee_total = FeeDetail::where('srno', $request->std_id)->where('session_id', $current_session)->where('active', 1)->where('academic_trans', 2)->where('fee_of', 1)->where('paid_mercy', 1)->sum('amount');
        $second_inst_fee_total = FeeDetail::where('srno', $request->std_id)->where('session_id', $current_session)->where('active', 1)->where('academic_trans', 2)->where('fee_of', 2)->where('paid_mercy', 1)->sum('amount');
        $complete_fee_total = FeeDetail::where('srno', $request->std_id)->where('session_id', $current_session)->where('active', 1)->where('academic_trans', 2)->where('fee_of', 3)->where('paid_mercy', 1)->sum('amount');
        $mercy_fee_total = FeeDetail::where('srno', $request->std_id)->where('session_id', $current_session)->where('active', 1)->where('academic_trans', 2)->where('fee_of', 3)->where('paid_mercy', 2)->sum('amount');


        $first_inst_fee_exists = FeeDetail::where('srno', $request->std_id)->where('session_id', $current_session)->where('academic_trans', 2)->where('fee_of', 1)->where('paid_mercy', 1)->where('active', 1)->exists();
        $second_inst_fee_exists = FeeDetail::where('srno', $request->std_id)->where('session_id', $current_session)->where('academic_trans', 2)->where('fee_of', 2)->where('paid_mercy', 1)->where('active', 1)->exists();
        $complete_fee_exists = FeeDetail::where('srno', $request->std_id)->where('session_id', $current_session)->where('academic_trans', 2)->where('fee_of', 3)->where('paid_mercy', 1)->where('active', 1)->exists();
        $mercy_fee_exists = FeeDetail::where('srno', $request->std_id)->where('session_id', $current_session)->where('academic_trans', 2)->where('fee_of', 3)->where('paid_mercy', 2)->where('active', 1)->exists();

        $firstInstFeeDueTotal = 0;
        $secondInstFeeDueTotal = 0;
        $completeFeeDueTotal = 0;
        $mercyFeeDueTotal = 0;
        $totalPaid = 0;
        $totalPaid += $first_inst_fee_total;
        $totalPaid += $second_inst_fee_total;
        $totalPaid += $complete_fee_total;
        $totalPaid += $mercy_fee_total;

        if ($first_inst_fee_total == $student->trans_1st_inst) {
            $firstInstFeeDueTotal = 0;
        } elseif ($first_inst_fee_total < $student->trans_1st_inst && $complete_fee_exists == false && $mercy_fee_exists == false) {
            $firstInstFeeDueTotal = $student->trans_1st_inst - $first_inst_fee_total;
        }

        if ($second_inst_fee_total == $student->trans_2nd_inst) {
            $secondInstFeeDueTotal = 0;
        } elseif ($second_inst_fee_total < $student->trans_2nd_inst && $complete_fee_exists == false && $mercy_fee_exists == false) {
            $secondInstFeeDueTotal = $student->trans_2nd_inst - $second_inst_fee_total;
        }
        if ($complete_fee_exists == true) {
            // $completeFeeDueTotal = $student->trans_total - $complete_fee_total;
            $completeFeeDueTotal = $student->trans_total - $totalPaid;
        }
        if ($mercy_fee_exists == true) {
            // $mercyFeeDueTotal = $student->trans_total - $mercy_fee_total;
            $mercyFeeDueTotal = $student->trans_total - $totalPaid;
        }

        return response()->json([
            'firstInstFeeDueTotal' => $firstInstFeeDueTotal,
            'secondInstFeeDueTotal' => $secondInstFeeDueTotal,
            'completeFeeDueTotal' => $completeFeeDueTotal,
            'mercyFeeDueTotal' => $mercyFeeDueTotal,
        ]);
    }


    /** Date 17-01-2026 */

    /**
     * Get student due fee report
     */
    public function getSMSStDueFeeReport(Request $request)
    {
        try {
            $currentSession = Session::get('fee_current_session')->id ?? null;

            if (empty($currentSession)) {
                return $this->errorResponse('Session not found', 200);
            }

            /* Validate request */
            $validator = Validator::make($request->all(), [
                'class_id' => 'required',
                'section_id' => 'required',
                'student' => 'required',
                'reportType' => 'required|in:firstInstDue,secondInstDue',
                'page' => 'nullable|integer|min:1',
                'per_page' => 'nullable|integer|min:1|max:500',
            ]);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors()->first(), 200);
            }
            $classId = $request->class_id;
            $sectionId = $request->section_id;
            $reportType = $request->reportType;
            $std = $request->student;
            /* Always use pagination */
            return $this->getPaginatedReport($request, $classId, $sectionId, $reportType, $std, $currentSession);

        } catch (\Exception $e) {
            Log::error('Fee Report Error: ' . $e->getMessage(), [
                'request' => $request->all()
            ]);

            return $this->errorResponse('Something went wrong, please try again later.', 200);
        }
    }


    /**
     * Get paginated report - OPTIMIZED VERSION
     */
    private function getPaginatedReport($request, $classId, $sectionId, $reportType, $std, $sessionId)
    {
        $page = (int) $request->input('page', 1);
        $perPage = (int) $request->input('per_page', 50);

        // Strategy: Load students in larger batches, filter for dues, until we have enough for current page
        $batchSize = 200; // Load students in batches
        $currentOffset = 0;
        $collectedRecords = [];
        $totalProcessed = 0;
        $targetStart = ($page - 1) * $perPage;
        $targetEnd = $targetStart + $perPage;

        while (count($collectedRecords) < $targetEnd && $currentOffset < 10000) { // Safety limit
            // Get batch of students
            $studentsBatch = $this->buildStudentsQuery($classId, $sectionId, $sessionId, $std)
                ->skip($currentOffset)
                ->take($batchSize)
                ->get();

            if ($studentsBatch->isEmpty()) {
                break; // No more students
            }

            // Get unique class IDs from batch
            $classIds = $studentsBatch->pluck('class')->unique()->toArray();

            // Load fee masters for these classes
            $feeMasters = $this->bulkLoadFeeMasters($classIds, $sessionId);

            // Get student SRNOs from batch
            $studentSrnos = $studentsBatch->pluck('srno')->toArray();

            // Load fee details for these students
            $feeDetailsMap = $this->bulkLoadFeeDetails($studentSrnos, $sessionId);

            // Process batch and get records with dues
            $batchRecords = $this->processStudentsData(
                $studentsBatch,
                $feeMasters,
                $feeDetailsMap,
                $reportType
            );

            // Add to collected records
            $collectedRecords = array_merge($collectedRecords, $batchRecords);

            $currentOffset += $batchSize;

            // If we have enough records for current page, we can stop
            if (count($collectedRecords) >= $targetEnd) {
                break;
            }
        }

        // Get total count (we need to continue until end to know total)
        $totalRecords = count($collectedRecords);

        // If we haven't processed all students yet, continue to get accurate total
        if (!empty($studentsBatch) && count($studentsBatch) == $batchSize) {
            while (true) {
                $moreBatch = $this->buildStudentsQuery($classId, $sectionId, $sessionId, $std)
                    ->skip($currentOffset)
                    ->take($batchSize)
                    ->get();

                if ($moreBatch->isEmpty()) {
                    break;
                }

                $classIds = $moreBatch->pluck('class')->unique()->toArray();
                $feeMasters = $this->bulkLoadFeeMasters($classIds, $sessionId);
                $studentSrnos = $moreBatch->pluck('srno')->toArray();
                $feeDetailsMap = $this->bulkLoadFeeDetails($studentSrnos, $sessionId);

                $moreRecords = $this->processStudentsData(
                    $moreBatch,
                    $feeMasters,
                    $feeDetailsMap,
                    $reportType
                );

                $collectedRecords = array_merge($collectedRecords, $moreRecords);
                $currentOffset += $batchSize;

                if (count($moreBatch) < $batchSize) {
                    break;
                }
            }
            $totalRecords = count($collectedRecords);
        }

        // Now slice for the current page
        $reportData = array_slice($collectedRecords, $targetStart, $perPage);

        if (empty($collectedRecords)) {
            return $this->errorResponse('No due fees found for the selected criteria', 200);
        }

        $lastPage = (int) ceil($totalRecords / $perPage);

        return response()->json([
            'status' => 'success',
            'data' => $reportData,
            'pagination' => [
                'total' => $totalRecords,
                'per_page' => $perPage,
                'current_page' => $page,
                'last_page' => $lastPage,
                'from' => $targetStart + 1,
                'to' => min($targetStart + count($reportData), $totalRecords),
                'next_page_url' => $page < $lastPage ? url()->current() . '?' . http_build_query(array_merge($request->except('page'), ['page' => $page + 1])) : null,
                'prev_page_url' => $page > 1 ? url()->current() . '?' . http_build_query(array_merge($request->except('page'), ['page' => $page - 1])) : null,
            ]
        ]);
    }


    /**
     * Build students query
     */
    private function buildStudentsQuery($classId, $sectionId, $sessionId, $studentSrno = null)
    {
        $query = DB::table('stu_main_srno')
            ->join('stu_detail', 'stu_main_srno.srno', '=', 'stu_detail.srno')
            ->join('parents_detail', 'stu_main_srno.srno', '=', 'parents_detail.srno')
            ->join('class_masters', 'stu_main_srno.class', '=', 'class_masters.id')
            ->join('section_masters', 'stu_main_srno.section', '=', 'section_masters.id')
            ->select([
                'stu_main_srno.srno',
                'stu_main_srno.session_id',
                'stu_main_srno.class',
                'stu_main_srno.section',
                'stu_main_srno.transport',
                'stu_main_srno.trans_1st_inst',
                'stu_main_srno.trans_2nd_inst',
                'stu_main_srno.trans_total',
                'stu_main_srno.prev_srno',
                'stu_main_srno.admission_date',
                'stu_detail.name',
                'parents_detail.f_name',
                'parents_detail.f_mobile',
                'class_masters.class as class_name',
                'section_masters.section as section_name',
            ])
            ->where('stu_main_srno.ssid', 1)
            ->where('stu_main_srno.session_id', $sessionId)
            ->where('stu_main_srno.active', 1)
            ->orderBy('stu_main_srno.class')
            ->orderBy('stu_main_srno.section')
            ->orderBy('stu_detail.name');

        if ($classId !== 'all') {
            $query->where('stu_main_srno.class', $classId);
        }

        if ($sectionId !== 'all') {
            $query->where('stu_main_srno.section', $sectionId);
        }
        if ($studentSrno && $studentSrno !== 'all') {
            $query->where('stu_main_srno.srno', $studentSrno);
        }


        return $query;
    }

    /**
     * Bulk load fee masters using DB
     */
    private function bulkLoadFeeMasters($classIds, $sessionId)
    {
        $feeMasters = DB::table('fee_masters')->whereIn('class_id', $classIds)->where('session_id', $sessionId)->where('active', 1)->get();
        /* Convert to keyed collection */
        return collect($feeMasters)->keyBy('class_id');
    }

    /**
     * Bulk load fee details using DB
     */
    private function bulkLoadFeeDetails($studentSrnos, $sessionId)
    {
        $feeDetails = DB::table('fee_details')->whereIn('srno', $studentSrnos)->where('session_id', $sessionId)->where('active', 1)->select(['srno', 'academic_trans', 'fee_of', 'amount', 'pay_date', 'recp_no', 'ref_slip_no', 'paid_mercy'])->get();
        /* Group by student srno */
        return collect($feeDetails)->groupBy('srno');
    }

    /**
     * Process students data and generate report records
     */
    private function processStudentsData($students, $feeMasters, $feeDetailsMap, $reportType)
    {
        $reportData = [];

        foreach ($students as $student) {
            $feeMaster = $feeMasters[$student->class] ?? null;
            if (!$feeMaster) {
                continue;
            }
            $studentFeeDetails = $feeDetailsMap[$student->srno] ?? collect();
            if ($reportType === 'firstInstDue') {
                $record = $this->generateFirstInstDueRecord($student, $feeMaster, $studentFeeDetails);
            } else {
                $record = $this->generateSecondInstDueRecord($student, $feeMaster, $studentFeeDetails);
            }

            // Only include records with due amount
            if ($record && $record['due_amount'] > 0) {
                $reportData[] = $record;
            }
        }

        return $reportData;
    }

    /**
     * Generate first installment due record
     */
    private function generateFirstInstDueRecord($student, $feeMaster, $feeDetails)
    {
        $isNewAdmission = $this->isNewAdmission($student);

        // Calculate academic fees payable
        $firstInstPayable = $feeMaster->inst_1 ?? 0;
        $admissionFeePayable = $isNewAdmission ? ($feeMaster->admission_fee ?? 0) : 0;
        $totalPayable = $firstInstPayable + $admissionFeePayable;

        // Get academic fee details
        $academicFees = $feeDetails->where('academic_trans', 1);

        // Calculate payments for first installment
        // fee_of = 1: Admission fee
        // fee_of = 2: First installment
        // fee_of = 4: Complete installment (paid_mercy = 1) OR Mercy (paid_mercy = 2)

        $admissionFeePaid = $academicFees->where('fee_of', 1)->sum('amount');
        $firstInstPaid = $academicFees->where('fee_of', 2)->sum('amount');

        // Check if complete installment or mercy is paid
        $completeInstPaid = $academicFees->where('fee_of', 4)->where('paid_mercy', 1)->sum('amount');
        $mercyPaid = $academicFees->where('fee_of', 4)->where('paid_mercy', 2)->sum('amount');

        // If complete installment is paid, it covers everything
        // If mercy is paid, it also covers remaining amount
        $hasCompletePayment = $completeInstPaid > 0 || $mercyPaid > 0;

        // Total paid is the sum of all payment types
        $totalPaid = $admissionFeePaid + $firstInstPaid + $completeInstPaid + $mercyPaid;

        // Calculate due amount
        $dueAmount = max(0, $totalPayable - $totalPaid);

        // Calculate transport fees
        $transportFees = $feeDetails->where('academic_trans', 2);
        $transFirstInstPayable = $student->trans_1st_inst ?? 0;

        // Transport payments
        // fee_of = 1: First transport installment
        // fee_of = 3: Complete transport (paid_mercy = 1) OR Mercy (paid_mercy = 2)

        $transFirstInstPaid = $transportFees->where('fee_of', 1)->sum('amount');
        $transCompleteInstPaid = $transportFees->where('fee_of', 3)->where('paid_mercy', 1)->sum('amount');
        $transMercyPaid = $transportFees->where('fee_of', 3)->where('paid_mercy', 2)->sum('amount');

        $hasTransCompletePayment = $transCompleteInstPaid > 0 || $transMercyPaid > 0;

        $totalTransPaid = $transFirstInstPaid + $transCompleteInstPaid + $transMercyPaid;
        $transDue = max(0, $transFirstInstPayable - $totalTransPaid);

        return [
            'srno' => $student->srno,
            'student_name' => $student->name,
            'father_name' => $student->f_name,
            'father_mobile' => $student->f_mobile,
            'class_name' => $student->class_name,
            'section_name' => $student->section_name,
            'session_id' => $student->session_id,
            'class_id' => $student->class,
            'section_id' => $student->section,


            // Academic fees
            'admission_fee' => $admissionFeePayable,
            'admission_fee_paid' => $admissionFeePaid,
            'first_inst_payable' => $firstInstPayable,
            'first_inst_paid' => $firstInstPaid,
            'complete_inst_paid' => $completeInstPaid,
            'mercy_paid' => $mercyPaid,
            'total_payable' => $totalPayable,
            'total_paid' => $totalPaid,
            'due_amount' => $dueAmount,
            'has_complete_payment' => $hasCompletePayment,

            // Transport fees
            'transport' => $student->transport ?? 0,
            'trans_first_inst_payable' => $transFirstInstPayable,
            'trans_first_inst_paid' => $transFirstInstPaid,
            'trans_complete_inst_paid' => $transCompleteInstPaid,
            'trans_mercy_paid' => $transMercyPaid,
            'trans_total_paid' => $totalTransPaid,
            'trans_due' => $transDue,
            'has_trans_complete_payment' => $hasTransCompletePayment,

            // Metadata
            'payment_count' => $academicFees->count() + $transportFees->count(),
            'last_payment_date' => $feeDetails->max('pay_date'),
            'is_new_admission' => $isNewAdmission,
        ];
    }

    /**
     * Generate second installment due record
     */
    private function generateSecondInstDueRecord($student, $feeMaster, $feeDetails)
    {
        // Calculate academic fees payable
        $secondInstPayable = $feeMaster->inst_2 ?? 0;

        // Get academic fee details
        $academicFees = $feeDetails->where('academic_trans', 1);

        // Calculate payments for second installment
        // fee_of = 3: Second installment
        // fee_of = 4: Complete installment (paid_mercy = 1) OR Mercy (paid_mercy = 2)

        $secondInstPaid = $academicFees->where('fee_of', 3)->sum('amount');

        // Check if complete installment or mercy is paid
        $completeInstPaid = $academicFees->where('fee_of', 4)->where('paid_mercy', 1)->sum('amount');
        $mercyPaid = $academicFees->where('fee_of', 4)->where('paid_mercy', 2)->sum('amount');

        $hasCompletePayment = $completeInstPaid > 0 || $mercyPaid > 0;

        // Total paid
        $totalPaid = $secondInstPaid + $completeInstPaid + $mercyPaid;

        // Calculate due amount
        $dueAmount = max(0, $secondInstPayable - $totalPaid);

        // Calculate transport fees
        $transportFees = $feeDetails->where('academic_trans', 2);
        $transSecondInstPayable = $student->trans_2nd_inst ?? 0;

        // Transport payments
        // fee_of = 2: Second transport installment
        // fee_of = 3: Complete transport (paid_mercy = 1) OR Mercy (paid_mercy = 2)

        $transSecondInstPaid = $transportFees->where('fee_of', 2)->sum('amount');
        $transCompleteInstPaid = $transportFees->where('fee_of', 3)->where('paid_mercy', 1)->sum('amount');
        $transMercyPaid = $transportFees->where('fee_of', 3)->where('paid_mercy', 2)->sum('amount');

        $hasTransCompletePayment = $transCompleteInstPaid > 0 || $transMercyPaid > 0;

        $totalTransPaid = $transSecondInstPaid + $transCompleteInstPaid + $transMercyPaid;
        $transDue = max(0, $transSecondInstPayable - $totalTransPaid);

        return [
            'srno' => $student->srno,
            'student_name' => $student->name,
            'father_name' => $student->f_name,
            'father_mobile' => $student->f_mobile,
            'class_name' => $student->class_name,
            'section_name' => $student->section_name,
            'session_id' => $student->session_id,
            'class_id' => $student->class,
            'section_id' => $student->section,

            // Academic fees
            'second_inst_payable' => $secondInstPayable,
            'second_inst_paid' => $secondInstPaid,
            'complete_inst_paid' => $completeInstPaid,
            'mercy_paid' => $mercyPaid,
            'total_paid' => $totalPaid,
            'due_amount' => $dueAmount,
            'has_complete_payment' => $hasCompletePayment,

            // Transport fees
            'transport' => $student->transport ?? 0,
            'trans_second_inst_payable' => $transSecondInstPayable,
            'trans_second_inst_paid' => $transSecondInstPaid,
            'trans_complete_inst_paid' => $transCompleteInstPaid,
            'trans_mercy_paid' => $transMercyPaid,
            'trans_total_paid' => $totalTransPaid,
            'trans_due' => $transDue,
            'has_trans_complete_payment' => $hasTransCompletePayment,

            // Metadata
            'payment_count' => $academicFees->count() + $transportFees->count(),
            'last_payment_date' => $feeDetails->max('pay_date'),
        ];
    }

    /**
     * Check if student is a new admission
     */
    private function isNewAdmission($student)
    {
        return (empty($student->prev_srno) || $student->prev_srno == null) && !empty($student->admission_date);
    }

    /**
     * Return error response
     */
    private function errorResponse($message, $code = 200)
    {
        return response()->json([
            'status' => 'error',
            'message' => $message
        ], $code);
    }

    /** Date - 24-01-2026 */
    public function dueFeeReportStds(Request $request)
    {
        try {
            $session = $request->session;
            $current_session = !empty($session) ? $session : Session::get('fee_current_session')->id;
            $class = $request->class;
            $section = $request->section;
            $std = $request->srno;
            $reportType = $request->reportType;

            // Validation
            if (empty($class) || empty($section) || empty($current_session) || empty($reportType) || empty($std)) {
                return response()->json([
                    'status' => 'error',
                    'message' => "Class, Section, Session, Report Type, and Student are required"
                ], 400);
            }
            $isExport = $request->boolean('export');

            $page = (int) ($request->page ?? 1);
            $perPage = $isExport ? null : (int) ($request->per_page ?? 50);

            // Define fields for selection
            $fields = [
                'stu_main_srno.srno',
                'stu_main_srno.ssid',
                'stu_main_srno.prev_srno',
                'stu_main_srno.admission_date',
                'stu_main_srno.class',
                'stu_main_srno.section',
                'stu_main_srno.session_id',
                'stu_main_srno.transport',
                'stu_main_srno.trans_1st_inst',
                'stu_main_srno.trans_2nd_inst',
                'stu_main_srno.trans_total',
                'stu_detail.name',
                'parents_detail.f_name',
                'parents_detail.f_mobile',
                'class_masters.class as class_name',
                'section_masters.section as section_name',
            ];
            $query = DB::table('stu_main_srno')
                     ->select($fields)
                     ->leftJoin('stu_detail', 'stu_main_srno.srno', 'stu_detail.srno')
                     ->leftJoin('parents_detail', 'stu_main_srno.srno', 'parents_detail.srno')
                     ->leftJoin('class_masters', 'stu_main_srno.class', 'class_masters.id')
                     ->leftJoin('section_masters', 'stu_main_srno.section', 'section_masters.id')
                     ->where('stu_main_srno.active', 1)
                     ->where('stu_main_srno.session_id', $current_session);

            // Build query
            // $query = StudentMasterController::getStdWithNames(false, $fields)->where('stu_main_srno.session_id', $current_session);

            // Apply filters
            if ($class !== 'all') {
                $classArray = is_array($class) ? $class : explode(',', $class);
                $query->whereIn('stu_main_srno.class', $classArray);
            }

            if ($section !== 'all') {
                $sectionArray = is_array($section) ? $section : explode(',', $section);
                $query->whereIn('stu_main_srno.section', $sectionArray);
            }

            if ($std !== 'all') {
                $stdArray = is_array($std) ? $std : explode(',', $std);
                $query->whereIn('stu_main_srno.srno', $stdArray);
            }

            // Process in chunks to avoid memory issues
            $result = collect();
            $studentSrnos = [];
            $classIds = [];

            // First, collect student SRNOs and class IDs
            $query->orderBy('stu_main_srno.class')
                ->chunk(500, function ($students) use (&$studentSrnos, &$classIds) {
                    foreach ($students as $st) {
                        $studentSrnos[] = $st->srno;
                        $classIds[] = $st->class;
                    }
                });

            if (empty($studentSrnos)) {
                return response()->json([
                    'status' => 'error',
                    'message' => "No students found for the given criteria"
                ], 404);
            }

            $classIds = array_unique($classIds);

            // Bulk load fee masters for all classes
            $feeMasters = FeeMaster::where('session_id', $current_session)->whereIn('class_id', $classIds)->where('active', 1)->get()->keyBy('class_id');

            // Bulk load all fee details for all students at once
            $allFeeDetails = FeeDetail::whereIn('srno', $studentSrnos)->where('session_id', $current_session)->where('active', 1)->select(['srno', 'fee_of', 'amount', 'academic_trans', 'paid_mercy'])->get()->groupBy('srno');

            // Process students again with loaded data
            $query->orderBy('stu_main_srno.class')
                ->chunk(500, function ($students) use (
                    &$result,
                    $feeMasters,
                    $allFeeDetails,
                    $reportType
                ) {
                    foreach ($students as $st) {
                        $feeMaster = $feeMasters[$st->class] ?? null;

                        if (!$feeMaster) {
                            continue;
                        }

                        $studentFeeDetails = $allFeeDetails[$st->srno] ?? collect();

                        // Check if new admission
                        $isNewAdmission = ($st->prev_srno == '' || $st->prev_srno == null) && $st->admission_date != '';

                        // Calculate fees
                        $admissionFee = $isNewAdmission ? ($feeMaster->admission_fee ?? 0) : 0;
                        $inst1Amount = $feeMaster->inst_1 ?? 0;
                        $inst2Amount = $feeMaster->inst_2 ?? 0;
                        $instTotal = $feeMaster->inst_total ?? 0;

                        // Get academic fees (academic_trans = 1)
                        $academicFees = $studentFeeDetails->where('academic_trans', 1);

                        // Admission fee paid
                        $admissionFeePaid = $academicFees->where('fee_of', 1)->sum('amount');

                        // First installment paid (fee_of = 2)
                        $firstInstPaid = $academicFees->where('fee_of', 2)->sum('amount');

                        // Second installment paid (fee_of = 3)
                        $secondInstPaid = $academicFees->where('fee_of', 3)->sum('amount');

                        // Complete fee paid (fee_of = 4, paid_mercy = 1)
                        $completeFeePaid = $academicFees->where('fee_of', 4)->where('paid_mercy', 1)->sum('amount');

                        // Mercy paid (fee_of = 4, paid_mercy = 2)
                        $mercyPaid = $academicFees->where('fee_of', 4)->where('paid_mercy', 2)->sum('amount');

                        // Total academic paid
                        $totalAcademicPaid = $academicFees->sum('amount');

                        // Get transport fees (academic_trans = 2)
                        $transportFees = $studentFeeDetails->where('academic_trans', 2);

                        $transInst1 = $st->trans_1st_inst ?? 0;
                        $transInst2 = $st->trans_2nd_inst ?? 0;
                        $transTotal = $st->trans_total ?? 0;

                        // Transport first installment (fee_of = 1)
                        $transFirstInstPaid = $transportFees->where('fee_of', 1)->sum('amount');

                        // Transport second installment (fee_of = 2)
                        $transSecondInstPaid = $transportFees->where('fee_of', 2)->sum('amount');

                        // Transport complete (fee_of = 3, paid_mercy = 1)
                        $transComplete = $transportFees->where('fee_of', 3)->where('paid_mercy', 1)->sum('amount');

                        // Transport mercy (fee_of = 3, paid_mercy = 2)
                        $transMercy = $transportFees->where('fee_of', 3)->where('paid_mercy', 2)->sum('amount');

                        // Total transport paid
                        $transTotalPaid = $transportFees->sum('amount');

                        // Calculate dues based on report type
                        $academicDue = 0;
                        $transportDue = 0;
                        $payableAmount = 0;
                        $shouldIncludeStudent = false;

                        switch ($reportType) {
                            case 'complete':
                                $payableAmount = $instTotal + $admissionFee;
                                $academicDue = $payableAmount - $totalAcademicPaid;
                                $transportDue = ($st->transport == 1) ? ($transTotal - $transTotalPaid) : 0;
                                $shouldIncludeStudent = ($academicDue > 0 || $transportDue > 0);
                                break;

                            case 'firstInstDue':
                                $firstInstPayable = $inst1Amount + $admissionFee;
                                $payableAmount = $firstInstPayable;
                                $firstInstOnlyPaid = $firstInstPaid + $admissionFeePaid;
                                $totalFirstInstCoverage = $firstInstOnlyPaid + $mercyPaid + $completeFeePaid;
                                $academicDue = max(0, $firstInstPayable - $totalFirstInstCoverage);

                                // Transport
                                $totalTransFirstCoverage = $transFirstInstPaid + $transMercy + $transComplete;
                                $transportDue = ($st->transport == 1) ? max(0, $transInst1 - $totalTransFirstCoverage) : 0;

                                $shouldIncludeStudent = ($academicDue > 0 || $transportDue > 0);
                                break;

                            case 'secondInstDue':
                                $secondInstPayable = $inst2Amount;
                                $payableAmount = $secondInstPayable;
                                $totalSecondInstCoverage = $secondInstPaid + $mercyPaid + $completeFeePaid;
                                $academicDue = max(0, $secondInstPayable - $totalSecondInstCoverage);

                                // Transport
                                $totalTransSecondCoverage = $transSecondInstPaid + $transMercy + $transComplete;
                                $transportDue = ($st->transport == 1) ? max(0, $transInst2 - $totalTransSecondCoverage) : 0;

                                $shouldIncludeStudent = ($academicDue > 0 || $transportDue > 0);
                                break;

                            case 'backcomplete':
                                $payableAmount = $instTotal + $admissionFee;
                                $academicDue = $payableAmount - $totalAcademicPaid;
                                $transportDue = ($st->transport == 1) ? ($transTotal - $transTotalPaid) : 0;
                                $shouldIncludeStudent = true;
                                break;

                            default:
                                $payableAmount = $instTotal + $admissionFee;
                                $academicDue = $payableAmount - $totalAcademicPaid;
                                $transportDue = ($st->transport == 1) ? ($transTotal - $transTotalPaid) : 0;
                                $shouldIncludeStudent = ($academicDue > 0 || $transportDue > 0);
                        }

                        $totalDue = $academicDue + $transportDue;

                        // Only include students with due amounts
                        if (!$shouldIncludeStudent || $totalDue <= 0) {
                            continue;
                        }

                        // Add to result
                        $result->push([
                            'student_srno' => $st->srno,
                            'ssid' => $st->ssid,
                            'section' => $st->section,
                            'class' => $st->class,
                            'session_id' => $st->session_id,
                            'student_name' => $st->name,
                            'father_name' => $st->f_name,
                            'father_mobile' => $st->f_mobile,
                            'class_name' => $st->class_name,
                            'section_name' => $st->section_name,
                            'academic_payable_amount' => $payableAmount,
                            'academic_paid_amount' => $totalAcademicPaid,
                            'academic_due_amount' => max(0, $academicDue),
                            'transport_payable_amount' => $st->transport == 1 ? $transTotal : 0,
                            'transport_paid_amount' => $st->transport == 1 ? $transTotalPaid : 0,
                            'transport_due_amount' => $st->transport == 1 ? max(0, $transportDue) : 0,
                            'total_due' => max(0, $totalDue),
                        ]);
                    }
                });

            if ($result->isEmpty()) {
                return response()->json([
                    'status' => 'error',
                    'message' => "No students with due fees found"
                ], 404);
            }

            // Apply pagination
            $total = $result->count();

            if ($isExport) {
                $paginatedData = $result->values(); // ALL records
                $response = [
                    'status' => 'success',
                    'data' => $paginatedData,
                ];
                return response()->json($response);
            } else {
                $offset = ($page - 1) * $perPage;
                $paginatedData = $result->slice($offset, $perPage)->values();
                $lastPage = (int) ceil($total / $perPage);
                return response()->json([
                    'status' => 'success',
                    'data' => $paginatedData,
                    'pagination' => [
                        'total' => $total,
                        'per_page' => $perPage,
                        'current_page' => $page,
                        'last_page' => $lastPage,
                        'from' => $offset + 1,
                        'to' => min($offset + $perPage, $total),
                        'next_page_url' => $page < $lastPage ? url()->current() . '?' . http_build_query(array_merge($request->except('page'), ['page' => $page + 1])) : null,
                        'prev_page_url' => $page > 1 ? url()->current() . '?' . http_build_query(array_merge($request->except('page'), ['page' => $page - 1])) : null,
                    ]
                ]);
            }




        } catch (\Exception $e) {
            Log::error('Due Fee Report Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'status' => 'error',
                'message' => "Failed to get Students"
            ], 500);
        }
    }
    /* Due Fee report export  */
    public function stexportDueFeeReport(Request $request)
    {
        try {
            // Get parameters from GET request
            $requestData = [
                'class' => $request->input('class'),
                'section' => $request->input('section'),
                'srno' => $request->input('srno'),
                'reportType' => $request->input('reportType'),
                'export' => true,
            ];

            // Create a new request object for internal use
            $exportRequest = new Request($requestData);
            $response = $this->dueFeeReportStds($exportRequest);

            if ($response->getStatusCode() !== 200) {
                return back()->with('error', 'Failed to generate report');
            }

            $decodedResponse = json_decode($response->getContent(), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return back()->with('error', 'Invalid data format');
            }

            $reportData = $decodedResponse['data'] ?? null;

            if (!$reportData || empty($reportData)) {
                return back()->with('error', 'No data found to export');
            }

            $fileName = 'due_fee_report_' . date('Y-m-d_His') . '.csv';

            // Use Laravel's response streaming for large files
            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
                'Pragma' => 'no-cache',
                'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
                'Expires' => '0'
            ];

            $callback = function() use ($reportData) {
                $file = fopen('php://output', 'w');

                // CSV headers
                fputcsv($file, [
                    'Class',
                    'Section',
                    'Name',
                    "Father's Name",
                    'Payable(Ac.)',
                    'Paid(Ac.)',
                    'Due(Ac.)',
                    'Payable(Tr.)',
                    'Paid(Tr.)',
                    'Due(Tr.)',
                    'Total Due'
                ]);

                // Write data
                foreach ($reportData as $row) {
                    fputcsv($file, [
                        $row['class_name'] ?? '',
                        $row['section_name'] ?? '',
                        $row['student_name'] ?? '',
                        $row['father_name'] ?? '',
                        $row['academic_payable_amount'] ?? 0,
                        $row['academic_paid_amount'] ?? 0,
                        $row['academic_due_amount'] ?? 0,
                        $row['transport_payable_amount'] ?? 0,
                        $row['transport_paid_amount'] ?? 0,
                        $row['transport_due_amount'] ?? 0,
                        $row['total_due'] ?? 0
                    ]);
                }

                fclose($file);
            };

            return response()->stream($callback, 200, $headers);

        } catch (\Exception $e) {
            Log::error('Export Due Fee Report Error: ' . $e->getMessage());
            return back()->with('error', 'Failed to export report');
        }
    }

    public function checkDueFeeData(Request $request)
    {
        try {
            // Get parameters from GET request
            $requestData = [
                'class' => $request->input('class'),
                'section' => $request->input('section'),
                'srno' => $request->input('srno'),
                'reportType' => $request->input('reportType'),
                'page' => 1,
                'per_page' => 1
            ];

            $checkRequest = new Request($requestData);
            $response = $this->dueFeeReportStds($checkRequest);

            if ($response->getStatusCode() !== 200) {
                return response()->json(['status' => 'error', 'message' => 'No data found'], 404);
            }

            $decoded = json_decode($response->getContent(), true);

            if (empty($decoded['data'])) {
                return response()->json(['status' => 'error', 'message' => 'No data found'], 404);
            }

            return response()->json(['status' => 'success'], 200);

        } catch (\Exception $e) {
            Log::error('Check Due Fee Data Error: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Error checking data'], 500);
        }
    }
    /* Due Fee report export - Back session  */
    public function backSessionStexportDueFeeReport(Request $request)
    {
        try {
            // Get parameters from GET request
            $requestData = [
                'class' => $request->input('class'),
                'section' => $request->input('section'),
                'srno' => $request->input('srno'),
                'reportType' => $request->input('reportType'),
                'session' => $request->input('session'),
                'export' => true,
            ];

            // Create a new request object for internal use
            $exportRequest = new Request($requestData);
            $response = $this->dueFeeReportStds($exportRequest);

            if ($response->getStatusCode() !== 200) {
                return back()->with('error', 'Failed to generate report');
            }

            $decodedResponse = json_decode($response->getContent(), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return back()->with('error', 'Invalid data format');
            }

            $reportData = $decodedResponse['data'] ?? null;

            if (!$reportData || empty($reportData)) {
                return back()->with('error', 'No data found to export');
            }

            $fileName = 'due_fee_report_' . date('Y-m-d_His') . '.csv';

            // Use Laravel's response streaming for large files
            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
                'Pragma' => 'no-cache',
                'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
                'Expires' => '0'
            ];

            $callback = function() use ($reportData) {
                $file = fopen('php://output', 'w');

                // CSV headers
                fputcsv($file, [
                    'Class',
                    'Section',
                    'Name',
                    "Father's Name",
                    'Payable(Ac.)',
                    'Paid(Ac.)',
                    'Due(Ac.)',
                    'Payable(Tr.)',
                    'Paid(Tr.)',
                    'Due(Tr.)',
                    'Total Due',
                    'Status'
                ]);

                // Write data
                foreach ($reportData as $row) {
                    fputcsv($file, [
                        $row['class_name'] ?? '',
                        $row['section_name'] ?? '',
                        $row['student_name'] ?? '',
                        $row['father_name'] ?? '',
                        $row['academic_payable_amount'] ?? 0,
                        $row['academic_paid_amount'] ?? 0,
                        $row['academic_due_amount'] ?? 0,
                        $row['transport_payable_amount'] ?? 0,
                        $row['transport_paid_amount'] ?? 0,
                        $row['transport_due_amount'] ?? 0,
                        $row['total_due'] ?? 0,
                        $this->getStudentStatus($row['ssid']) ?? ''
                    ]);
                }

                fclose($file);
            };

            return response()->stream($callback, 200, $headers);

        } catch (\Exception $e) {
            Log::error('Export Due Fee Report Error: ' . $e->getMessage());
            return back()->with('error', 'Failed to export report');
        }
    }

    public function backSessionCheckDueFeeData(Request $request)
    {
        try {
            // Get parameters from GET request
            $requestData = [
                'class' => $request->input('class'),
                'section' => $request->input('section'),
                'srno' => $request->input('srno'),
                'reportType' => $request->input('reportType'),
                'session' => $request->input('session'),
                'page' => 1,
                'per_page' => 1
            ];

            $checkRequest = new Request($requestData);
            $response = $this->dueFeeReportStds($checkRequest);

            if ($response->getStatusCode() !== 200) {
                return response()->json(['status' => 'error', 'message' => 'No data found'], 404);
            }

            $decoded = json_decode($response->getContent(), true);

            if (empty($decoded['data'])) {
                return response()->json(['status' => 'error', 'message' => 'No data found'], 404);
            }

            return response()->json(['status' => 'success'], 200);

        } catch (\Exception $e) {
            Log::error('Check Due Fee Data Error: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Error checking data'], 500);
        }
    }

    private function getStudentStatus($ssid)
    {
        $statuses = [
            1 => 'Active',
            2 => 'Class Promoted',
            3 => 'School Promoted',
            4 => 'TC',
            5 => 'Left Out',
        ];

        return $statuses[$ssid] ?? '';
    }

    /* Print due Receipt */
    public function printStDueReceipt(Request $request)
    {
        try {
            /* Validate request */
            $validator = Validator::make($request->all(), [
                'srno' => 'required',
                'class' => 'required',
                'section' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $validator->errors()->first()
                ], 200);
            }

            $currentSession = Session::get('fee_current_session')->id;
            $class = is_array($request->class) ? $request->class : explode(',', $request->class);
            $section = $request->section === 'all' ? null : (is_array($request->section) ? $request->section : explode(',', $request->section));
            $srno = $request->srno === 'all' ? null : (is_array($request->srno) ? $request->srno : explode(',', $request->srno));

            // Define fields for student selection
            $stdFields = [
                'stu_main_srno.srno',
                'stu_main_srno.ssid',
                'stu_main_srno.prev_srno',
                'stu_main_srno.admission_date',
                'stu_main_srno.class',
                'stu_main_srno.section',
                'stu_main_srno.session_id',
                'stu_main_srno.school',
                'stu_main_srno.rollno',
                'stu_main_srno.relation_code',
                'stu_main_srno.transport',
                'stu_main_srno.trans_1st_inst',
                'stu_main_srno.trans_2nd_inst',
                'stu_main_srno.trans_total',
                'stu_detail.name as student_name',
                'parents_detail.f_name',
                'parents_detail.m_name',
                'class_masters.class as class_name',
                'section_masters.section as section_name',
            ];

            $studentsQuery = DB::table('stu_main_srno')
                            ->select($stdFields)
                            ->leftJoin('stu_detail', 'stu_main_srno.srno', 'stu_detail.srno')
                            ->leftJoin('parents_detail', 'stu_main_srno.srno', 'parents_detail.srno')
                            ->leftJoin('class_masters', 'stu_main_srno.class', 'class_masters.id')
                            ->leftJoin('section_masters', 'stu_main_srno.section', 'section_masters.id')
                            ->where('stu_main_srno.session_id', $currentSession)
                            ->where('stu_main_srno.active', 1)
                            ->where('stu_main_srno.ssid', 1)
                            ->whereIn('stu_main_srno.class', $class);

            // Build query for students
            /* $studentsQuery = StudentMasterController::getStdWithNames(false, $stdFields)
                ->where('stu_main_srno.session_id', $currentSession)
                ->whereIn('stu_main_srno.class', $class); */

            if ($section !== null) {
                $studentsQuery->whereIn('stu_main_srno.section', $section);
            }

            if ($srno !== null) {
                $studentsQuery->whereIn('stu_main_srno.srno', $srno);
            }

            $students = $studentsQuery->get();

            if ($students->isEmpty()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No students found for the given criteria.'
                ], 200);
            }

            // Collect all student SRNOs and class IDs
            $allSrnos = $students->pluck('srno')->toArray();
            $allClassIds = $students->pluck('class')->unique()->toArray();

            // Bulk load fee masters
            $feeMasters = FeeMaster::where('session_id', $currentSession)->whereIn('class_id', $allClassIds)->where('active', 1)->get()->keyBy('class_id');

            /* Bulk load all fee details at once */
            $allFeeDetails = FeeDetail::whereIn('srno', $allSrnos)->where('session_id', $currentSession)->where('active', 1)->select(['srno', 'fee_of', 'amount', 'academic_trans', 'paid_mercy'])->get()->groupBy('srno');
            /* Process students */
            $finalResult = [];
            $processedSrnos = [];

            foreach ($students as $student) {
                if (in_array($student->srno, $processedSrnos)) {
                    continue;
                }

                $feeMaster = $feeMasters[$student->class] ?? null;
                if (!$feeMaster) {
                    continue;
                }

                $studentFeeDetails = $allFeeDetails[$student->srno] ?? collect();
                $isNewAdmission = ($student->prev_srno == '' || $student->prev_srno == null) && $student->admission_date != '';

                // Academic fees
                $academicFees = $studentFeeDetails->where('academic_trans', 1);
                $admissionFeePaid = $academicFees->where('fee_of', 1)->sum('amount');
                $firstInstPaid = $academicFees->where('fee_of', 2)->sum('amount');
                $secondInstPaid = $academicFees->where('fee_of', 3)->sum('amount');
                $completeFeePaid = $academicFees->where('fee_of', 4)->where('paid_mercy', 1)->sum('amount');
                $mercyPaid = $academicFees->where('fee_of', 4)->where('paid_mercy', 2)->sum('amount');

                $totalAcademicPaid = $isNewAdmission ? $academicFees->sum('amount') : $academicFees->where('fee_of', '!=', 1)->sum('amount');

                // Transport fees
                $transportFees = $studentFeeDetails->where('academic_trans', 2);
                $transFirstInstPaid = $transportFees->where('fee_of', 1)->sum('amount');
                $transSecondInstPaid = $transportFees->where('fee_of', 2)->sum('amount');
                $transCompletePaid = $transportFees->where('fee_of', 3)->where('paid_mercy', 1)->sum('amount');
                $transMercyPaid = $transportFees->where('fee_of', 3)->where('paid_mercy', 2)->sum('amount');
                $transportTotalPaid = $transportFees->sum('amount');

                // Calculate amounts
                $admissionFee = $isNewAdmission ? ($feeMaster->admission_fee ?? 0) : 0;
                $payableAmount = ($feeMaster->inst_total ?? 0) + $admissionFee;
                $transportPayableAmount = $student->trans_total ?? 0;

                $result = [
                    'school' => $student->school == 1 ? 'St. Vivekanand Play House' : 'St. Vivekanand Public Secondary School',
                    'srno' => $student->srno,
                    'student_name' => $student->student_name,
                    'father_name' => $student->f_name,
                    'mother_name' => $student->m_name,
                    'class' => $student->class_name,
                    'section' => $student->section_name,
                    'date' => date('d-M-Y'),
                    'academic_fee' => [
                        'admission_fee' => $admissionFee,
                        'admission_fee_paid' => $admissionFeePaid,
                        'inst_1' => $feeMaster->inst_1 ?? 0,
                        'inst_2' => $feeMaster->inst_2 ?? 0,
                        'total_payable' => $payableAmount,
                        'total_paid' => $totalAcademicPaid,
                        'total_due' => max(0, $payableAmount - $totalAcademicPaid),
                    ],
                    'transport_fee' => [
                        'transport' => $student->transport ?? 0,
                        'inst_1' => $student->trans_1st_inst ?? 0,
                        'inst_2' => $student->trans_2nd_inst ?? 0,
                        'total_payable' => $transportPayableAmount,
                        'total_paid' => $transportTotalPaid,
                        'total_due' => max(0, $transportPayableAmount - $transportTotalPaid),
                    ],
                    'grand_total_due' => max(0, ($payableAmount - $totalAcademicPaid) + ($transportPayableAmount - $transportTotalPaid)),
                ];

                $finalResult[] = $result;
                $processedSrnos[] = $student->srno;
            }

            if (empty($finalResult)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No data found'
                ], 200);
            }

            return response()->json([
                'status' => 'success',
                'data' => $finalResult
            ]);

        } catch (\Exception $e) {
            Log::error('Print Due Receipt Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'status' => 'error',
                'message' => "Failed to get Due Payment list"
            ], 500);
        }
    }


    /*  Date 30-01-2026 */
    public function relativeWiseDueFeeReport(Request $request)
    {
        try {
            /* Validate request */
            $validator = Validator::make($request->all(), [
                'srno' => 'required',
                'class' => 'required',
                'section' => 'required',
                'per_page' => 'nullable|integer|min:1',
                'page' => 'nullable|integer|min:1',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $validator->errors()->first()
                ], 200);
            }

            $currentSession = Session::get('fee_current_session')->id;
            $class = $request->class == 'all' ? null : (is_array($request->class) ? $request->class : explode(',', $request->class));
            $section = $request->section == 'all' ? null : (is_array($request->section) ? $request->section : explode(',', $request->section));
            $srno = $request->srno == 'all' ? null : (is_array($request->srno) ? $request->srno : explode(',', $request->srno));

            // Pagination parameters
            $perPage = (int) $request->input('per_page', 15);
            $currentPage = (int) $request->input('page', 1);

            // Define fields for student selection
            $stdFields = [
                'stu_main_srno.srno',
                'stu_main_srno.ssid',
                'stu_main_srno.prev_srno',
                'stu_main_srno.admission_date',
                'stu_main_srno.class',
                'stu_main_srno.section',
                'stu_main_srno.session_id',
                'stu_main_srno.school',
                'stu_main_srno.rollno',
                'stu_main_srno.relation_code',
                'stu_main_srno.transport',
                'stu_main_srno.trans_1st_inst',
                'stu_main_srno.trans_2nd_inst',
                'stu_main_srno.trans_total',
                'stu_detail.name as student_name',
                'parents_detail.f_name',
                'parents_detail.m_name',
                'class_masters.class as class_name',
                'section_masters.section as section_name',
            ];

            // Build base query for students
            $studentsQuery = DB::table('stu_main_srno')
                ->select($stdFields)
                ->leftJoin('stu_detail', 'stu_main_srno.srno', '=', 'stu_detail.srno')
                ->leftJoin('parents_detail', 'stu_main_srno.srno', '=', 'parents_detail.srno')
                ->leftJoin('class_masters', 'stu_main_srno.class', '=', 'class_masters.id')
                ->leftJoin('section_masters', 'stu_main_srno.section', '=', 'section_masters.id')
                ->where('stu_main_srno.session_id', $currentSession)
                ->where('stu_main_srno.active', 1)
                ->where('stu_main_srno.ssid', 1);

            if ($class !== null) {
                $studentsQuery->whereIn('stu_main_srno.class', $class);
            }

            if ($section !== null) {
                $studentsQuery->whereIn('stu_main_srno.section', $section);
            }

            if ($srno !== null) {
                $studentsQuery->whereIn('stu_main_srno.srno', $srno);
            }

            // Get total count
            $totalRecords = $studentsQuery->count();

            if ($totalRecords === 0) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No students found for the given criteria.'
                ], 200);
            }

            // Apply pagination and ordering
            $students = $studentsQuery
                ->orderBy('stu_main_srno.srno')
                ->offset(($currentPage - 1) * $perPage)
                ->limit($perPage)
                ->get();

            // Collect all student SRNOs and class IDs
            $allSrnos = $students->pluck('srno')->toArray();
            $allClassIds = $students->pluck('class')->unique()->toArray();
            $relationCodes = $students->pluck('relation_code')->filter()->unique()->toArray();

            // Bulk load fee masters - Create associative array
            $feeMastersCollection = FeeMaster::where('session_id', $currentSession)
                ->whereIn('class_id', $allClassIds)
                ->where('active', 1)
                ->get();

            $feeMasters = [];
            foreach ($feeMastersCollection as $fm) {
                $feeMasters[$fm->class_id] = $fm;
            }

            // Bulk load all fee details at once
            $allFeeDetails = FeeDetail::whereIn('srno', $allSrnos)
                ->select(['srno', 'fee_of', 'amount', 'academic_trans', 'paid_mercy'])
                ->where('session_id', $currentSession)
                ->where('active', 1)
                ->get()
                ->groupBy('srno');

            // Load relatives if relation codes exist
            $relatives = collect();
            if (!empty($relationCodes)) {
                $relatives = DB::table('stu_main_srno')
                    ->select($stdFields)
                    ->leftJoin('stu_detail', 'stu_main_srno.srno', '=', 'stu_detail.srno')
                    ->leftJoin('parents_detail', 'stu_main_srno.srno', '=', 'parents_detail.srno')
                    ->leftJoin('class_masters', 'stu_main_srno.class', '=', 'class_masters.id')
                    ->leftJoin('section_masters', 'stu_main_srno.section', '=', 'section_masters.id')
                    ->where('stu_main_srno.session_id', $currentSession)
                    ->where('stu_main_srno.active', 1)
                    ->where('stu_main_srno.ssid', 1)
                    ->whereIn('stu_main_srno.relation_code', $relationCodes)
                    ->whereNotIn('stu_main_srno.srno', $allSrnos)
                    ->get()
                    ->groupBy('relation_code');

                /* Load fee details for relatives */
                $relativeSrnos = $relatives->flatten(1)->pluck('srno')->toArray();
                if (!empty($relativeSrnos)) {
                    $relativeFeeDetails = FeeDetail::whereIn('srno', $relativeSrnos)
                        ->select(['srno', 'fee_of', 'amount', 'academic_trans', 'paid_mercy'])
                        ->where('session_id', $currentSession)
                        ->where('active', 1)
                        ->get()
                        ->groupBy('srno');

                    /* Merge with main fee details */
                    // $allFeeDetails = $allFeeDetails->merge($relativeFeeDetails);
                    foreach ($relativeFeeDetails as $srno => $fees) {
                        if ($allFeeDetails->has($srno)) {
                            $allFeeDetails[$srno] = $allFeeDetails[$srno]->merge($fees);
                        } else {
                            $allFeeDetails[$srno] = $fees;
                        }
                    }


                    /* Load fee masters for relative classes */
                    $relativeClassIds = $relatives->flatten(1)->pluck('class')->unique()->toArray();
                    $relativeFeeMastersCollection = FeeMaster::where('session_id', $currentSession)
                        ->whereIn('class_id', $relativeClassIds)
                        ->where('active', 1)
                        ->get();

                    foreach ($relativeFeeMastersCollection as $rfm) {
                        $feeMasters[$rfm->class_id] = $rfm;
                    }
                }
            }

            // Process students
            $finalResult = [];
            $processedSrnos = [];

            foreach ($students as $student) {
                if (in_array($student->srno, $processedSrnos)) {
                    continue;
                }

                // Check if fee master exists
                if (!isset($feeMasters[$student->class])) {
                    continue;
                }

                $feeMaster = $feeMasters[$student->class];
                $studentFeeDetails = $allFeeDetails->get($student->srno, collect());
                $isNewAdmission = empty($student->prev_srno) && !empty($student->admission_date);

                // Academic fees - optimized calculation
                $academicFees = $studentFeeDetails->where('academic_trans', 1);
                $admissionFeePaid = $academicFees->where('fee_of', 1)->sum('amount');
                $firstInstPaid = $academicFees->where('fee_of', 2)->sum('amount');
                $secondInstPaid = $academicFees->where('fee_of', 3)->sum('amount');
                $completeFeePaid = $academicFees->where('fee_of', 4)->where('paid_mercy', 1)->sum('amount');
                $mercyPaid = $academicFees->where('fee_of', 4)->where('paid_mercy', 2)->sum('amount');
                $totalAcademicPaid = $admissionFeePaid + $firstInstPaid + $secondInstPaid + $completeFeePaid + $mercyPaid;

                // Transport fees
                $transportFees = $studentFeeDetails->where('academic_trans', 2);
                $transFirstInstPaid = $transportFees->where('fee_of', 1)->sum('amount');
                $transSecondInstPaid = $transportFees->where('fee_of', 2)->sum('amount');
                $transCompletePaid = $transportFees->where('fee_of', 3)->where('paid_mercy', 1)->sum('amount');
                $transMercyPaid = $transportFees->where('fee_of', 3)->where('paid_mercy', 2)->sum('amount');
                $transportTotalPaid = $transFirstInstPaid + $transSecondInstPaid + $transCompletePaid + $transMercyPaid;

                // Calculate amounts
                $admissionFee = $isNewAdmission ? ($feeMaster->admission_fee ?? 0) : 0;
                $payableAmount = ($feeMaster->inst_total ?? 0) + $admissionFee;
                $transportPayableAmount = $student->trans_total ?? 0;

                $result = [
                    'school' => $student->school == 1 ? 'St. Vivekanand Play House' : 'St. Vivekanand Public Secondary School',
                    'srno' => $student->srno,
                    'student_name' => $student->student_name,
                    'father_name' => $student->f_name,
                    'mother_name' => $student->m_name,
                    'class' => $student->class_name,
                    'section' => $student->section_name,
                    'class_id' => $student->class,
                    'section_id' => $student->section,
                    'session_id' => $student->session_id,
                    'date' => date('d-M-Y'),
                    'academic_total_payable' => $payableAmount,
                    'academic_total_paid' => $totalAcademicPaid,
                    'academic_total_due' => max(0, $payableAmount - $totalAcademicPaid),
                    'transport_total_payable' => $transportPayableAmount,
                    'transport_total_paid' => $transportTotalPaid,
                    'transport_total_due' => max(0, $transportPayableAmount - $transportTotalPaid),
                    'grand_total_due' => max(0, ($payableAmount - $totalAcademicPaid) + ($transportPayableAmount - $transportTotalPaid)),
                    'relatives' => [],
                ];

                // Process relatives
                if ($student->relation_code && $relatives->has($student->relation_code)) {
                    foreach ($relatives->get($student->relation_code) as $relative) {
                        if (in_array($relative->srno, $processedSrnos)) {
                            continue;
                        }

                        if (!isset($feeMasters[$relative->class])) {
                            continue;
                        }

                        $relativeFeeMaster = $feeMasters[$relative->class];
                        $relativeFeeDetails = $allFeeDetails->get($relative->srno, collect());
                        $isRelativeNewAdmission = empty($relative->prev_srno) && !empty($relative->admission_date);

                        // Academic fees for relative
                        $relativeAcademicFees = $relativeFeeDetails->where('academic_trans', 1);
                        $relativeAdmissionFeePaid = $relativeAcademicFees->where('fee_of', 1)->sum('amount');
                        $relativeFirstInstPaid = $relativeAcademicFees->where('fee_of', 2)->sum('amount');
                        $relativeSecondInstPaid = $relativeAcademicFees->where('fee_of', 3)->sum('amount');
                        $relativeCompleteFeePaid = $relativeAcademicFees->where('fee_of', 4)->where('paid_mercy', 1)->sum('amount');
                        $relativeMercyPaid = $relativeAcademicFees->where('fee_of', 4)->where('paid_mercy', 2)->sum('amount');
                        $relativeTotalPaid = $relativeAdmissionFeePaid + $relativeFirstInstPaid + $relativeSecondInstPaid + $relativeCompleteFeePaid + $relativeMercyPaid;

                        // Transport fees for relative
                        $relativeTransportFees = $relativeFeeDetails->where('academic_trans', 2);
                        $relativeTransFirstInstPaid = $relativeTransportFees->where('fee_of', 1)->sum('amount');
                        $relativeTransSecondInstPaid = $relativeTransportFees->where('fee_of', 2)->sum('amount');
                        $relativeTransCompletePaid = $relativeTransportFees->where('fee_of', 3)->where('paid_mercy', 1)->sum('amount');
                        $relativeTransMercyPaid = $relativeTransportFees->where('fee_of', 3)->where('paid_mercy', 2)->sum('amount');
                        $relativeTransportPaid = $relativeTransFirstInstPaid + $relativeTransSecondInstPaid + $relativeTransCompletePaid + $relativeTransMercyPaid;

                        $relativeAdmissionFee = $isRelativeNewAdmission ? ($relativeFeeMaster->admission_fee ?? 0) : 0;
                        $relativePayable = ($relativeFeeMaster->inst_total ?? 0) + $relativeAdmissionFee;
                        $relativeTransportPayable = $relative->trans_total ?? 0;

                        $result['relatives'][] = [
                            'srno' => $relative->srno,
                            'student_name' => $relative->student_name,
                            'father_name' => $relative->f_name,
                            'class' => $relative->class_name,
                            'section' => $relative->section_name,
                            'class_id' => $relative->class,
                            'section_id' => $relative->section,
                            'session_id' => $relative->session_id,
                            'academic_total_payable' => $relativePayable,
                            'academic_total_paid' => $relativeTotalPaid,
                            'academic_total_due' => max(0, $relativePayable - $relativeTotalPaid),
                            'transport_total_payable' => $relativeTransportPayable,
                            'transport_total_paid' => $relativeTransportPaid,
                            'transport_total_due' => max(0, $relativeTransportPayable - $relativeTransportPaid),
                            'grand_total_due' => max(0, ($relativePayable - $relativeTotalPaid) + ($relativeTransportPayable - $relativeTransportPaid)),
                        ];

                        $processedSrnos[] = $relative->srno;
                    }
                }

                $finalResult[] = $result;
                $processedSrnos[] = $student->srno;
            }

            if (empty($finalResult)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No data found'
                ], 200);
            }

            // Calculate pagination metadata
            $lastPage = (int) ceil($totalRecords / $perPage);

            return response()->json([
                'status' => 'success',
                'data' => $finalResult,
                'pagination' => [
                    'total' => $totalRecords,
                    'per_page' => $perPage,
                    'current_page' => $currentPage,
                    'last_page' => $lastPage,
                    'from' => (($currentPage - 1) * $perPage) + 1,
                    'to' => min($currentPage * $perPage, $totalRecords),
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Relative-wise fee details error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'status' => 'error',
                'message' => "Something went wrong, please try again later."
            ], 200);
        }
    }

    /* Date 07-01-2026 */
    public function relativeWiseDueFeeExcelReport(Request $request)
    {
        try {
            /* Validate */
            $validator = Validator::make($request->all(), [
                'srno' => 'required',
                'class' => 'required',
                'section' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $validator->errors()->first()
                ], 400);
            }

            $currentSession = Session::get('fee_current_session')->id;
            $class = $request->class == 'all' ? null : (is_array($request->class) ? $request->class : explode(',', $request->class));
            $section = $request->section == 'all' ? null : (is_array($request->section) ? $request->section : explode(',', $request->section));
            $srno = $request->srno == 'all' ? null : (is_array($request->srno) ? $request->srno : explode(',', $request->srno));

            // Define fields for student selection
            $stdFields = [
                'stu_main_srno.srno',
                'stu_main_srno.ssid',
                'stu_main_srno.prev_srno',
                'stu_main_srno.admission_date',
                'stu_main_srno.class',
                'stu_main_srno.section',
                'stu_main_srno.session_id',
                'stu_main_srno.school',
                'stu_main_srno.rollno',
                'stu_main_srno.relation_code',
                'stu_main_srno.transport',
                'stu_main_srno.trans_1st_inst',
                'stu_main_srno.trans_2nd_inst',
                'stu_main_srno.trans_total',
                'stu_detail.name as student_name',
                'parents_detail.f_name',
                'parents_detail.m_name',
                'class_masters.class as class_name',
                'section_masters.section as section_name',
            ];

            /* =========================
            Load MAIN students
            ========================== */
            $students = DB::table('stu_main_srno')
                ->select($stdFields)
                ->leftJoin('stu_detail', 'stu_main_srno.srno', '=', 'stu_detail.srno')
                ->leftJoin('parents_detail', 'stu_main_srno.srno', '=', 'parents_detail.srno')
                ->leftJoin('class_masters', 'stu_main_srno.class', '=', 'class_masters.id')
                ->leftJoin('section_masters', 'stu_main_srno.section', '=', 'section_masters.id')
                ->where('stu_main_srno.session_id', $currentSession)
                ->where('stu_main_srno.active', 1)
                ->where('stu_main_srno.ssid', 1)
                ->when($class, fn($q) => $q->whereIn('stu_main_srno.class', $class))
                ->when($section, fn($q) => $q->whereIn('stu_main_srno.section', $section))
                ->when($srno, fn($q) => $q->whereIn('stu_main_srno.srno', $srno))
                ->orderBy('stu_main_srno.srno')
                ->get();

            if ($students->isEmpty()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No records found'
                ], 404);
            }

            // Collect student SRNOs and relation codes FIRST
            $studentSrnos = $students->pluck('srno')->toArray();
            $relationCodes = $students->pluck('relation_code')->filter()->unique()->toArray();

            /* =========================
            Load RELATIVES
            ========================== */
            $relatives = collect();
            if (!empty($relationCodes)) {
                $relatives = DB::table('stu_main_srno')
                    ->select($stdFields)
                    ->leftJoin('stu_detail', 'stu_main_srno.srno', '=', 'stu_detail.srno')
                    ->leftJoin('parents_detail', 'stu_main_srno.srno', '=', 'parents_detail.srno')
                    ->leftJoin('class_masters', 'stu_main_srno.class', '=', 'class_masters.id')
                    ->leftJoin('section_masters', 'stu_main_srno.section', '=', 'section_masters.id')
                    ->where('stu_main_srno.session_id', $currentSession)
                    ->where('stu_main_srno.active', 1)
                    ->where('stu_main_srno.ssid', 1)
                    ->whereIn('stu_main_srno.relation_code', $relationCodes)
                    ->whereNotIn('stu_main_srno.srno', $studentSrnos)  // Use studentSrnos here
                    ->get()
                    ->groupBy('relation_code');
            }

            /* =========================
            Load FEES - AFTER getting all SRNOs
            ========================== */
            $allSrnos = collect($studentSrnos)
                ->merge($relatives->flatten(1)->pluck('srno'))
                ->unique()
                ->toArray();

            // Bulk load fee masters - Create associative array
            $feeMastersCollection = FeeMaster::where('session_id', $currentSession)
                ->where('active', 1)
                ->get();

            $feeMasters = [];
            foreach ($feeMastersCollection as $fm) {
                $feeMasters[$fm->class_id] = $fm;
            }

            // Bulk load all fee details at once with proper selection
            $feeDetails = FeeDetail::whereIn('srno', $allSrnos)
                ->select(['srno', 'fee_of', 'amount', 'academic_trans', 'paid_mercy'])
                ->where('session_id', $currentSession)
                ->where('active', 1)
                ->get()
                ->groupBy('srno');

            /* =========================
            CSV STREAM
            ========================== */
            $fileName = 'relative_wise_due_fee_' . date('d_m_Y') . '.csv';

            return response()->stream(function () use ($students, $relatives, $feeMasters, $feeDetails) {

                $out = fopen('php://output', 'w');

                // Add BOM for UTF-8
                fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));

                // CSV Headers
                fputcsv($out, [
                    'SRNO',
                    'Student Name',
                    'Father Name',
                    'Mother Name',
                    'Class',
                    'Section',
                    'Academic Payable',
                    'Academic Paid',
                    'Academic Due',
                    'Transport Payable',
                    'Transport Paid',
                    'Transport Due',
                    'Grand Total Due',
                ]);

                $processedSrnos = [];

                foreach ($students as $student) {
                    if (in_array($student->srno, $processedSrnos)) {
                        continue;
                    }

                    // Check if fee master exists
                    if (!isset($feeMasters[$student->class])) {
                        continue;
                    }

                    $feeMaster = $feeMasters[$student->class];
                    $studentFeeDetails = $feeDetails->get($student->srno, collect());
                    $isNewAdmission = empty($student->prev_srno) && !empty($student->admission_date);

                    // Academic fees - with complete and mercy calculation
                    $academicFees = $studentFeeDetails->where('academic_trans', 1);
                    $admissionFeePaid = $academicFees->where('fee_of', 1)->sum('amount');
                    $firstInstPaid = $academicFees->where('fee_of', 2)->sum('amount');
                    $secondInstPaid = $academicFees->where('fee_of', 3)->sum('amount');
                    $completeFeePaid = $academicFees->where('fee_of', 4)->where('paid_mercy', 1)->sum('amount');
                    $mercyPaid = $academicFees->where('fee_of', 4)->where('paid_mercy', 2)->sum('amount');
                    $totalAcademicPaid = $admissionFeePaid + $firstInstPaid + $secondInstPaid + $completeFeePaid + $mercyPaid;

                    // Transport fees
                    $transportFees = $studentFeeDetails->where('academic_trans', 2);
                    $transFirstInstPaid = $transportFees->where('fee_of', 1)->sum('amount');
                    $transSecondInstPaid = $transportFees->where('fee_of', 2)->sum('amount');
                    $transCompletePaid = $transportFees->where('fee_of', 3)->where('paid_mercy', 1)->sum('amount');
                    $transMercyPaid = $transportFees->where('fee_of', 3)->where('paid_mercy', 2)->sum('amount');
                    $transportTotalPaid = $transFirstInstPaid + $transSecondInstPaid + $transCompletePaid + $transMercyPaid;

                    // Calculate amounts
                    $admissionFee = $isNewAdmission ? ($feeMaster->admission_fee ?? 0) : 0;
                    $payableAmount = ($feeMaster->inst_total ?? 0) + $admissionFee;
                    $transportPayableAmount = $student->trans_total ?? 0;

                    // Write main student row
                    fputcsv($out, [
                        $student->srno,
                        $student->student_name ?? '',
                        $student->f_name ?? '',
                        $student->m_name ?? '',
                        $student->class_name ?? '',
                        $student->section_name ?? '',
                        $payableAmount,
                        $totalAcademicPaid,
                        max(0, $payableAmount - $totalAcademicPaid),
                        $transportPayableAmount,
                        $transportTotalPaid,
                        max(0, $transportPayableAmount - $transportTotalPaid),
                        max(0, ($payableAmount - $totalAcademicPaid) + ($transportPayableAmount - $transportTotalPaid)),
                    ]);

                    $processedSrnos[] = $student->srno;

                    // Process relatives
                    if (!empty($student->relation_code) && $relatives->has($student->relation_code)) {
                        $relativesList = $relatives->get($student->relation_code);

                        foreach ($relativesList as $relative) {
                            if (in_array($relative->srno, $processedSrnos)) {
                                continue;
                            }

                            if (!isset($feeMasters[$relative->class])) {
                                continue;
                            }

                            $relativeFeeMaster = $feeMasters[$relative->class];
                            $relativeFeeDetails = $feeDetails->get($relative->srno, collect());
                            $isRelativeNewAdmission = empty($relative->prev_srno) && !empty($relative->admission_date);

                            // Academic fees for relative
                            $relativeAcademicFees = $relativeFeeDetails->where('academic_trans', 1);
                            $relativeAdmissionFeePaid = $relativeAcademicFees->where('fee_of', 1)->sum('amount');
                            $relativeFirstInstPaid = $relativeAcademicFees->where('fee_of', 2)->sum('amount');
                            $relativeSecondInstPaid = $relativeAcademicFees->where('fee_of', 3)->sum('amount');
                            $relativeCompleteFeePaid = $relativeAcademicFees->where('fee_of', 4)->where('paid_mercy', 1)->sum('amount');
                            $relativeMercyPaid = $relativeAcademicFees->where('fee_of', 4)->where('paid_mercy', 2)->sum('amount');
                            $relativeTotalPaid = $relativeAdmissionFeePaid + $relativeFirstInstPaid + $relativeSecondInstPaid + $relativeCompleteFeePaid + $relativeMercyPaid;

                            // Transport fees for relative
                            $relativeTransportFees = $relativeFeeDetails->where('academic_trans', 2);
                            $relativeTransFirstInstPaid = $relativeTransportFees->where('fee_of', 1)->sum('amount');
                            $relativeTransSecondInstPaid = $relativeTransportFees->where('fee_of', 2)->sum('amount');
                            $relativeTransCompletePaid = $relativeTransportFees->where('fee_of', 3)->where('paid_mercy', 1)->sum('amount');
                            $relativeTransMercyPaid = $relativeTransportFees->where('fee_of', 3)->where('paid_mercy', 2)->sum('amount');
                            $relativeTransportPaid = $relativeTransFirstInstPaid + $relativeTransSecondInstPaid + $relativeTransCompletePaid + $relativeTransMercyPaid;

                            $relativeAdmissionFee = $isRelativeNewAdmission ? ($relativeFeeMaster->admission_fee ?? 0) : 0;
                            $relativePayable = ($relativeFeeMaster->inst_total ?? 0) + $relativeAdmissionFee;
                            $relativeTransportPayable = $relative->trans_total ?? 0;

                            // Write relative row
                            fputcsv($out, [
                                $relative->srno,
                                $relative->student_name ?? '',
                                $relative->f_name ?? '',
                                $relative->m_name ?? '',
                                $relative->class_name ?? '',
                                $relative->section_name ?? '',
                                $relativePayable,
                                $relativeTotalPaid,
                                max(0, $relativePayable - $relativeTotalPaid),
                                $relativeTransportPayable,
                                $relativeTransportPaid,
                                max(0, $relativeTransportPayable - $relativeTransportPaid),
                                max(0, ($relativePayable - $relativeTotalPaid) + ($relativeTransportPayable - $relativeTransportPaid)),
                            ]);

                            $processedSrnos[] = $relative->srno;
                        }
                    }
                }

                fclose($out);

            }, 200, [
                'Content-Type'        => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
                'Cache-Control'       => 'no-cache, no-store, must-revalidate',
                'Pragma'              => 'no-cache',
                'Expires'             => '0',
            ]);

        } catch (\Exception $e) {
            Log::error('Relative-wise CSV export error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'status' => 'error',
                'message' => 'CSV export failed: ' . $e->getMessage()
            ], 500);
        }
    }

    public function stCompleteFeeDetails(Request $request)
    {
        try {
            /* Validate */
            $validator = Validator::make($request->all(), [
                'srno' => 'required',
                'class' => 'required',
                'section' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $validator->errors()->first()
                ], 200);
            }

            $currentSession = Session::get('fee_current_session')->id;
            $class = $request->class;
            $section = $request->section;
            $srno = $request->srno;

            $stdFields = [
                'stu_main_srno.srno',
                'stu_main_srno.ssid',
                'stu_main_srno.prev_srno',
                'stu_main_srno.admission_date',
                'stu_main_srno.class',
                'stu_main_srno.section',
                'stu_main_srno.session_id',
                'stu_main_srno.school',
                'stu_main_srno.rollno',
                'stu_main_srno.relation_code',
                'stu_main_srno.transport',
                'stu_main_srno.trans_1st_inst',
                'stu_main_srno.trans_2nd_inst',
                'stu_main_srno.trans_total',
                'stu_detail.name as student_name',
                'parents_detail.f_name',
                'parents_detail.m_name',
                'class_masters.sort',
                'class_masters.class as class_name',
                'section_masters.section as section_name',
                'session_masters.session',
            ];

            // Get the specific student for current session
            $student = DB::table('stu_main_srno')
                ->select($stdFields)
                ->leftJoin('stu_detail', 'stu_main_srno.srno', '=', 'stu_detail.srno')
                ->leftJoin('parents_detail', 'stu_main_srno.srno', '=', 'parents_detail.srno')
                ->leftJoin('class_masters', 'stu_main_srno.class', '=', 'class_masters.id')
                ->leftJoin('section_masters', 'stu_main_srno.section', '=', 'section_masters.id')
                ->leftJoin('session_masters', 'stu_main_srno.session_id', '=', 'session_masters.id')
                ->where('stu_main_srno.srno', $srno)
                ->where('stu_main_srno.class', $class)
                ->where('stu_main_srno.section', $section)
                ->where('stu_main_srno.session_id', $currentSession)
                ->where('stu_main_srno.active', 1)
                ->where('stu_main_srno.ssid', 1)
                ->first();

            if (empty($student)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Student not found for the given SRNO.'
                ], 200);
            }

            // Check if it's a new admission
            $isNewAdmission = (empty($student->prev_srno)) && !empty($student->admission_date);

            // Get fee master for current session and class
            $feeMaster = FeeMaster::where('session_id', $currentSession)->where('class_id', $student->class)->where('active', 1)->first();

            if (!$feeMaster) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Fee master not found for this class.'
                ], 200);
            }

            // Get all academic fee details for current session
            $academicFeeDetails = FeeDetail::where('srno', $student->srno)->where('session_id', $currentSession)->where('academic_trans', 1)->where('active', 1)->get();

            // Get admission fee payments
            $admissionFeePayments = $academicFeeDetails->where('fee_of', 1);
            $admissionFeePaid = $admissionFeePayments->sum('amount');

            // Calculate academic totals with complete and mercy (excluding admission fee)
            $academicFirstInst = $academicFeeDetails->where('fee_of', 2)->sum('amount');
            $academicSecondInst = $academicFeeDetails->where('fee_of', 3)->sum('amount');
            $academicComplete = $academicFeeDetails->where('fee_of', 4)->where('paid_mercy', 1)->sum('amount');
            $academicMercy = $academicFeeDetails->where('fee_of', 4)->where('paid_mercy', 2)->sum('amount');

            $totalAcademicPaid = $admissionFeePaid + $academicFirstInst + $academicSecondInst + $academicComplete + $academicMercy;

            // Calculate payable amount
            $admissionFee = $isNewAdmission ? ($feeMaster->admission_fee ?? 0) : 0;
            $payableAmount = ($feeMaster->inst_total ?? 0) + $admissionFee;

            // Get all transport fee details for current session
            $transportFeeDetails = FeeDetail::where('srno', $student->srno)->where('session_id', $currentSession)->where('active', 1)->where('academic_trans', 2)->get();

            // Calculate transport totals with complete and mercy
            $transportFirstInst = $transportFeeDetails->where('fee_of', 1)->sum('amount');
            $transportSecondInst = $transportFeeDetails->where('fee_of', 2)->sum('amount');
            $transportComplete = $transportFeeDetails->where('fee_of', 3)->where('paid_mercy', 1)->sum('amount');
            $transportMercy = $transportFeeDetails->where('fee_of', 3)->where('paid_mercy', 2)->sum('amount');

            $transportTotalPaid = $transportFirstInst + $transportSecondInst + $transportComplete + $transportMercy;
            $transportPayableAmount = $student->trans_total ?? 0;

            // Get installment details
            $academicInstallments = $this->calInstFeesForCompleteFee($student->srno, $currentSession, 1, $isNewAdmission);
            $transportInstallments = $this->calInstFeesForCompleteFee($student->srno, $currentSession, 2, false);

            $result = [
                'school' => $student->school == 1 ? 'St. Vivekanand Play House' : 'St. Vivekanand Public Secondary School',
                'srno' => $student->srno,
                'student_name' => $student->student_name ?? '',
                'father_name' => $student->f_name ?? '',
                'mother_name' => $student->m_name ?? '',
                'session_id' => $currentSession,
                'session' => $student->session ?? '',
                'prev_srno' => $student->prev_srno ?? '',
                'admission_date' => $student->admission_date ?? '',
                'class_id' => $student->class,
                'class' => $student->class_name ?? '',
                'section_id' => $student->section,
                'section' => $student->section_name ?? '',
                'rollno' => $student->rollno ?? '',
                'is_new_admission' => $isNewAdmission,
                'academic' => [
                    'admission_fee' => $feeMaster->admission_fee ?? 0,
                    'admission_fee_paid' => $admissionFeePaid,
                    'inst_1' => $feeMaster->inst_1 ?? 0,
                    'inst_2' => $feeMaster->inst_2 ?? 0,
                    'inst_total' => $feeMaster->inst_total ?? 0,
                    'payable_amount' => $payableAmount,
                    'paid_amount' => $totalAcademicPaid,
                    'due_amount' => max(0, $payableAmount - $totalAcademicPaid),
                    'installments' => $academicInstallments,
                ],
                'transport' => [
                    'transport' => $student->transport ?? 0,
                    'inst_1' => $student->trans_1st_inst ?? 0,
                    'inst_2' => $student->trans_2nd_inst ?? 0,
                    'inst_total' => $student->trans_total ?? 0,
                    'payable_amount' => $transportPayableAmount,
                    'paid_amount' => $transportTotalPaid,
                    'due_amount' => max(0, $transportPayableAmount - $transportTotalPaid),
                    'installments' => $transportInstallments,
                ],
            ];

            return response()->json([
                'status' => 'success',
                'data' => $result
            ], 200);

        } catch (\Exception $e) {
            Log::error('Complete fee details error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'status' => 'error',
                'message' => "Failed to get complete fee details"
            ], 200);
        }
    }

    private function calInstFeesForCompleteFee($srno, $sessionId, $academicTrans, $includeAdmission = false)
    {
        $query = FeeDetail::where('srno', $srno)
            ->where('session_id', $sessionId)
            ->where('academic_trans', $academicTrans)
            ->where('active', 1);

        // For academic fees, optionally exclude admission fee
        if ($academicTrans == 1 && !$includeAdmission) {
            $query->where('fee_of', '!=', 1);
        }

        $feeDetails = $query->get(['fee_of', 'amount', 'pay_date', 'recp_no', 'ref_slip_no', 'paid_mercy']);

        $installmentResults = [];

        if ($academicTrans == 1) {
            // Academic fees
            if ($includeAdmission) {
                $installmentResults['admission_fee'] = $feeDetails
                    ->where('fee_of', 1)
                    ->map(function($item) {
                        return [
                            'amount' => $item->amount,
                            'pay_date' => $item->pay_date,
                            'recp_no' => $item->recp_no,
                            'ref_slip_no' => $item->ref_slip_no,
                        ];
                    })
                    ->values()
                    ->toArray();
            }

            $installmentResults['first_inst'] = $feeDetails
                ->where('fee_of', 2)
                ->map(function($item) {
                    return [
                        'amount' => $item->amount,
                        'pay_date' => $item->pay_date,
                        'recp_no' => $item->recp_no,
                        'ref_slip_no' => $item->ref_slip_no,
                    ];
                })
                ->values()
                ->toArray();

            $installmentResults['second_inst'] = $feeDetails
                ->where('fee_of', 3)
                ->map(function($item) {
                    return [
                        'amount' => $item->amount,
                        'pay_date' => $item->pay_date,
                        'recp_no' => $item->recp_no,
                        'ref_slip_no' => $item->ref_slip_no,
                    ];
                })
                ->values()
                ->toArray();

            $installmentResults['complete_inst'] = $feeDetails
                ->where('fee_of', 4)
                ->where('paid_mercy', 1)
                ->map(function($item) {
                    return [
                        'amount' => $item->amount,
                        'pay_date' => $item->pay_date,
                        'recp_no' => $item->recp_no,
                        'ref_slip_no' => $item->ref_slip_no,
                    ];
                })
                ->values()
                ->toArray();

            $installmentResults['mercy'] = $feeDetails
                ->where('fee_of', 4)
                ->where('paid_mercy', 2)
                ->map(function($item) {
                    return [
                        'amount' => $item->amount,
                        'pay_date' => $item->pay_date,
                        'recp_no' => $item->recp_no,
                        'ref_slip_no' => $item->ref_slip_no,
                    ];
                })
                ->values()
                ->toArray();

        } else {
            // Transport fees
            $installmentResults['first_inst'] = $feeDetails
                ->where('fee_of', 1)
                ->map(function($item) {
                    return [
                        'amount' => $item->amount,
                        'pay_date' => $item->pay_date,
                        'recp_no' => $item->recp_no,
                        'ref_slip_no' => $item->ref_slip_no,
                    ];
                })
                ->values()
                ->toArray();

            $installmentResults['second_inst'] = $feeDetails
                ->where('fee_of', 2)
                ->map(function($item) {
                    return [
                        'amount' => $item->amount,
                        'pay_date' => $item->pay_date,
                        'recp_no' => $item->recp_no,
                        'ref_slip_no' => $item->ref_slip_no,
                    ];
                })
                ->values()
                ->toArray();

            $installmentResults['complete_inst'] = $feeDetails
                ->where('fee_of', 3)
                ->where('paid_mercy', 1)
                ->map(function($item) {
                    return [
                        'amount' => $item->amount,
                        'pay_date' => $item->pay_date,
                        'recp_no' => $item->recp_no,
                        'ref_slip_no' => $item->ref_slip_no,
                    ];
                })
                ->values()
                ->toArray();

            $installmentResults['mercy'] = $feeDetails
                ->where('fee_of', 3)
                ->where('paid_mercy', 2)
                ->map(function($item) {
                    return [
                        'amount' => $item->amount,
                        'pay_date' => $item->pay_date,
                        'recp_no' => $item->recp_no,
                        'ref_slip_no' => $item->ref_slip_no,
                    ];
                })
                ->values()
                ->toArray();
        }

        return $installmentResults;
    }

    /* Academic Fee Details Student - default Current student */
    public function stAcademicFeeDetails(Request $request)
    {
        try {
            /* Validate */
            $validator = Validator::make($request->all(), [
                'srno' => 'required',
                'class' => 'required',
                'section' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $validator->errors()->first()
                ], 200);
            }

            $currentSession = Session::get('fee_current_session')->id;
            $class = $request->class;
            $section = $request->section;
            $srno = $request->srno;

            $stdFields = [
                'stu_main_srno.srno',
                'stu_main_srno.ssid',
                'stu_main_srno.prev_srno',
                'stu_main_srno.admission_date',
                'stu_main_srno.class',
                'stu_main_srno.section',
                'stu_main_srno.session_id',
                'stu_main_srno.school',
                'stu_main_srno.rollno',
                'stu_main_srno.relation_code',
                'stu_main_srno.transport',
                'stu_main_srno.trans_1st_inst',
                'stu_main_srno.trans_2nd_inst',
                'stu_main_srno.trans_total',
                'stu_detail.name as student_name',
                'class_masters.sort',
                'class_masters.class as class_name',
                'section_masters.section as section_name',
            ];

            // Get the specific student
            $student = DB::table('stu_main_srno')
                ->select($stdFields)
                ->leftJoin('stu_detail', 'stu_main_srno.srno', '=', 'stu_detail.srno')
                ->leftJoin('class_masters', 'stu_main_srno.class', '=', 'class_masters.id')
                ->leftJoin('section_masters', 'stu_main_srno.section', '=', 'section_masters.id')
                ->where('stu_main_srno.srno', $srno)
                ->where('stu_main_srno.class', $class)
                ->where('stu_main_srno.section', $section)
                ->where('stu_main_srno.session_id', $currentSession)
                ->where('stu_main_srno.active', 1)
                ->where('stu_main_srno.ssid', 1)
                ->first();

            if (empty($student)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Student not found for the given SRNO.'
                ], 200);
            }
            $relativeStFields = [
                'stu_main_srno.srno',
                'stu_main_srno.ssid',
                'stu_main_srno.prev_srno',
                'stu_main_srno.admission_date',
                'stu_main_srno.class',
                'stu_main_srno.section',
                'stu_main_srno.session_id',
                'stu_main_srno.school',
                'stu_main_srno.rollno',
                'stu_main_srno.relation_code',
                'stu_main_srno.transport',
                'stu_main_srno.trans_1st_inst',
                'stu_main_srno.trans_2nd_inst',
                'stu_main_srno.trans_total',
                'stu_detail.name as student_name',
                'class_masters.sort',
                'class_masters.class as class_name',
                'section_masters.section as section_name',
                'parents_detail.f_name',
                'parents_detail.m_name',
            ];
            // Load relatives for current session only if relation code exists
            $relatives = collect();
            if (!empty($student->relation_code)) {
                $relatives = DB::table('stu_main_srno')
                    ->select($relativeStFields)
                    ->leftJoin('stu_detail', 'stu_main_srno.srno', '=', 'stu_detail.srno')
                    ->leftJoin('class_masters', 'stu_main_srno.class', '=', 'class_masters.id')
                    ->leftJoin('section_masters', 'stu_main_srno.section', '=', 'section_masters.id')
                    ->leftJoin('parents_detail', 'stu_main_srno.srno', '=', 'parents_detail.srno')
                    ->where('stu_main_srno.relation_code', $student->relation_code)
                    ->where('stu_main_srno.srno', '!=', $student->srno)
                    ->where('stu_main_srno.session_id', $currentSession)
                    ->where('stu_main_srno.active', 1)
                    ->where('stu_main_srno.ssid', 1)
                    ->get();
            }

            // Fields for session classes
            $fields = [
                'stu_main_srno.session_id',
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

            // Process main student (all sessions)
            $result = $this->processStudentAcademicFees($student, $fields, false);

            // Process relatives (current session only - direct data)
            $relativesData = [];
            foreach ($relatives as $relative) {
                $relativesData[] = $this->processRelativeAcademicFees($relative);
            }

            $result['relatives'] = $relativesData;

            return response()->json([
                'status' => 'success',
                'data' => $result
            ]);

        } catch (\Exception $e) {
            Log::error('Academic fee details error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'status' => 'error',
                'message' => "Failed to get academic fee details"
            ], 200);
        }
    }

    /**
     * Process student academic fees across sessions
     *
     * @param object $student Student object
     * @param array $fields Fields to select
     * @param bool $currentSessionOnly Whether to process only current session
     */
    private function processStudentAcademicFees($student, $fields, $currentSessionOnly = false)
    {
        $currentSession = Session::get('fee_current_session')->id;

        // Get session classes for this student
        $query = DB::table('stu_main_srno')
            ->leftJoin('class_masters', 'stu_main_srno.class', '=', 'class_masters.id')
            ->leftJoin('section_masters', 'stu_main_srno.section', '=', 'section_masters.id')
            ->leftJoin('session_masters', 'stu_main_srno.session_id', '=', 'session_masters.id')
            ->select($fields)
            ->where('stu_main_srno.srno', $student->srno)
            ->where('stu_main_srno.active', 1);

        // If current session only, filter by current session
        if ($currentSessionOnly) {
            $query->where('stu_main_srno.session_id', $currentSession);
        }

        $sessionClasses = $query->orderBy('stu_main_srno.session_id', 'desc')->get();

        $result = [
            'school' => $student->school == 1 ? 'St. Vivekanand Play House' : 'St. Vivekanand Public Secondary School',
            'srno' => $student->srno,
            'student_name' => $student->student_name ?? '',
            'relation_code' => $student->relation_code ?? '',
            'sessions' => []
        ];

        foreach ($sessionClasses as $sessionClass) {
            // Check if it's a new admission for this session
            $isNewAdmission = (empty($sessionClass->prev_srno)) && !empty($sessionClass->admission_date);

            // Get fee master for this session and class
            $feeMaster = FeeMaster::where('session_id', $sessionClass->session_id)
                ->where('class_id', $sessionClass->class_id)
                ->where('active', 1)
                ->first();

            if (empty($feeMaster)) {
                continue; // Skip if no fee master found
            }

            // Get all academic fee details for this session
            $academicFeeDetails = FeeDetail::where('srno', $student->srno)
                ->where('session_id', $sessionClass->session_id)
                ->where('academic_trans', 1)
                ->where('active', 1)
                ->get();

            // Get admission fee payments
            $admissionFeePayments = $academicFeeDetails->where('fee_of', 1);
            $admissionFeePaid = $admissionFeePayments->sum('amount');

            // Calculate academic totals with complete and mercy (excluding admission fee)
            $academicFirstInst = $academicFeeDetails->where('fee_of', 2)->sum('amount');
            $academicSecondInst = $academicFeeDetails->where('fee_of', 3)->sum('amount');
            $academicComplete = $academicFeeDetails->where('fee_of', 4)->where('paid_mercy', 1)->sum('amount');
            $academicMercy = $academicFeeDetails->where('fee_of', 4)->where('paid_mercy', 2)->sum('amount');

            $totalAcademicPaid = $admissionFeePaid + $academicFirstInst + $academicSecondInst + $academicComplete + $academicMercy;

            // Calculate payable amount
            $admissionFee = $isNewAdmission ? ($feeMaster->admission_fee ?? 0) : 0;
            $payableAmount = ($feeMaster->inst_total ?? 0) + $admissionFee;

            $result['sessions'][] = [
                'isCurrentSession' => $sessionClass->session_id == $currentSession,
                'session_id' => $sessionClass->session_id,
                'prev_srno' => $sessionClass->prev_srno ?? '',
                'admission_date' => $sessionClass->admission_date ?? '',
                'session' => $sessionClass->session ?? '',
                'class_id' => $sessionClass->class_id,
                'class' => $sessionClass->class_name ?? '',
                'section_id' => $sessionClass->section_id,
                'section' => $sessionClass->section_name ?? '',
                'rollno' => $sessionClass->rollno ?? '',
                'is_new_admission' => $isNewAdmission,
                'payable_amount' => $payableAmount,
                'paid_amount' => $totalAcademicPaid,
                'due_amount' => max(0, $payableAmount - $totalAcademicPaid),
            ];
        }

        return $result;
    }

    /**
     * Process relative academic fees for current session only (direct data without sessions array)
     *
     * @param object $relative Relative student object (already from current session)
     */
    private function processRelativeAcademicFees($relative)
    {
        // Check if it's a new admission for this session
        $isNewAdmission = (empty($relative->prev_srno)) && !empty($relative->admission_date);

        /* Get fee master for this session and class */
        $feeMaster = FeeMaster::where('session_id', $relative->session_id)->where('class_id', $relative->class)->where('active', 1)->first();

        if (empty($feeMaster)) {
            return null; // Skip if no fee master found
        }

        /* Get all academic fee details for this session */
        $academicFeeDetails = FeeDetail::where('srno', $relative->srno)->where('session_id', $relative->session_id)->where('academic_trans', 1)->where('active', 1)->get();

        /* Get admission fee payments */
        $admissionFeePayments = $academicFeeDetails->where('fee_of', 1);
        $admissionFeePaid = $admissionFeePayments->sum('amount');

        /* Calculate academic totals with complete and mercy */
        $academicFirstInst = $academicFeeDetails->where('fee_of', 2)->sum('amount');
        $academicSecondInst = $academicFeeDetails->where('fee_of', 3)->sum('amount');
        $academicComplete = $academicFeeDetails->where('fee_of', 4)->where('paid_mercy', 1)->sum('amount');
        $academicMercy = $academicFeeDetails->where('fee_of', 4)->where('paid_mercy', 2)->sum('amount');

        $totalAcademicPaid = $admissionFeePaid + $academicFirstInst + $academicSecondInst + $academicComplete + $academicMercy;

        // Calculate payable amount
        $admissionFee = $isNewAdmission ? ($feeMaster->admission_fee ?? 0) : 0;
        $payableAmount = ($feeMaster->inst_total ?? 0) + $admissionFee;

        return [
            'school' => $relative->school == 1 ? 'St. Vivekanand Play House' : 'St. Vivekanand Public Secondary School',
            'srno' => $relative->srno,
            'student_name' => $relative->student_name ?? '',
            'father_name' => $relative->f_name ?? '',
            'mother_name' => $relative->m_name ?? '',
            'relation_code' => $relative->relation_code ?? '',
            'session_id' => $relative->session_id,
            'prev_srno' => $relative->prev_srno ?? '',
            'admission_date' => $relative->admission_date ?? '',
            'class_id' => $relative->class,
            'class' => $relative->class_name ?? '',
            'section_id' => $relative->section,
            'section' => $relative->section_name ?? '',
            'rollno' => $relative->rollno ?? '',
            'is_new_admission' => $isNewAdmission,
            'payable_amount' => $payableAmount,
            'paid_amount' => $totalAcademicPaid,
            'due_amount' => max(0, $payableAmount - $totalAcademicPaid),
        ];
    }

    /**
     * Transport Fee Details Student - default Current student
     */
    public function stTransportFeeDetails(Request $request)
    {
        try {
            /* Validate */
            $validator = Validator::make($request->all(), [
                'srno' => 'required',
                'class' => 'required',
                'section' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $validator->errors()->first()
                ], 200);
            }

            $currentSession = Session::get('fee_current_session')->id;
            $class = $request->class;
            $section = $request->section;
            $srno = $request->srno;

            $stdFields = [
                'stu_main_srno.srno',
                'stu_main_srno.ssid',
                'stu_main_srno.prev_srno',
                'stu_main_srno.admission_date',
                'stu_main_srno.class',
                'stu_main_srno.section',
                'stu_main_srno.session_id',
                'stu_main_srno.school',
                'stu_main_srno.rollno',
                'stu_main_srno.relation_code',
                'stu_main_srno.transport',
                'stu_main_srno.trans_1st_inst',
                'stu_main_srno.trans_2nd_inst',
                'stu_main_srno.trans_total',
                'stu_detail.name as student_name',
                'class_masters.sort',
                'class_masters.class as class_name',
                'section_masters.section as section_name',
            ];

            // Get the specific student
            $student = DB::table('stu_main_srno')
                ->select($stdFields)
                ->leftJoin('stu_detail', 'stu_main_srno.srno', '=', 'stu_detail.srno')
                ->leftJoin('class_masters', 'stu_main_srno.class', '=', 'class_masters.id')
                ->leftJoin('section_masters', 'stu_main_srno.section', '=', 'section_masters.id')
                ->where('stu_main_srno.srno', $srno)
                ->where('stu_main_srno.class', $class)
                ->where('stu_main_srno.section', $section)
                ->where('stu_main_srno.session_id', $currentSession)
                ->where('stu_main_srno.active', 1)
                ->where('stu_main_srno.ssid', 1)
                ->first();

            if (empty($student)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Student not found for the given SRNO.'
                ], 200);
            }


            // Fields for session classes
            $fields = [
                'stu_main_srno.session_id',
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

            // Process main student (all sessions)
            $result = $this->processStudentTransportFees($student, $fields, false);

            return response()->json([
                'status' => 'success',
                'data' => $result
            ]);

        } catch (\Exception $e) {
            Log::error('Transport fee details error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'status' => 'error',
                'message' => "Failed to get transport fee details"
            ], 200);
        }
    }

    /**
     * Process student transport fees across sessions
     *
     * @param object $student Student object
     * @param array $fields Fields to select
     * @param bool $currentSessionOnly Whether to process only current session
     */
    private function processStudentTransportFees($student, $fields, $currentSessionOnly = false)
    {
        $currentSession = Session::get('fee_current_session')->id;

        // Get session classes for this student
        $query = DB::table('stu_main_srno')
            ->leftJoin('class_masters', 'stu_main_srno.class', '=', 'class_masters.id')
            ->leftJoin('section_masters', 'stu_main_srno.section', '=', 'section_masters.id')
            ->leftJoin('session_masters', 'stu_main_srno.session_id', '=', 'session_masters.id')
            ->select($fields)
            ->where('stu_main_srno.srno', $student->srno)
            ->where('stu_main_srno.active', 1);

        // If current session only, filter by current session
        if ($currentSessionOnly) {
            $query->where('stu_main_srno.session_id', $currentSession);
        }

        $sessionClasses = $query->orderBy('stu_main_srno.session_id', 'desc')->get();

        $result = [
            'school' => $student->school == 1 ? 'St. Vivekanand Play House' : 'St. Vivekanand Public Secondary School',
            'srno' => $student->srno,
            'student_name' => $student->student_name ?? '',
            'sessions' => []
        ];

        foreach ($sessionClasses as $sessionClass) {

            // Get all transport fee details for this session
            $transportFeeDetails = FeeDetail::where('srno', $student->srno)->where('session_id', $sessionClass->session_id)->where('academic_trans', 2)->where('active', 1)->get();


            // Calculate transport totals with complete and mercy (excluding admission fee)
            $transportFirstInst = $transportFeeDetails->where('fee_of', 1)->sum('amount');
            $transportSecondInst = $transportFeeDetails->where('fee_of', 2)->sum('amount');
            $transportComplete = $transportFeeDetails->where('fee_of', 3)->where('paid_mercy', 1)->sum('amount');
            $transportMercy = $transportFeeDetails->where('fee_of', 3)->where('paid_mercy', 2)->sum('amount');

            $totalTransportPaid = $transportFirstInst + $transportSecondInst + $transportComplete + $transportMercy;

            $payableAmount = $student->trans_total ?? 0;

            $result['sessions'][] = [
                'isCurrentSession' => $sessionClass->session_id == $currentSession,
                'session_id' => $sessionClass->session_id,
                'prev_srno' => $sessionClass->prev_srno ?? '',
                'session' => $sessionClass->session ?? '',
                'class_id' => $sessionClass->class_id,
                'class' => $sessionClass->class_name ?? '',
                'section_id' => $sessionClass->section_id,
                'section' => $sessionClass->section_name ?? '',
                'rollno' => $sessionClass->rollno ?? '',
                'payable_amount' => $payableAmount,
                'paid_amount' => $totalTransportPaid,
                'due_amount' => max(0, $payableAmount - $totalTransportPaid),
            ];
        }

        return $result;
    }

}
