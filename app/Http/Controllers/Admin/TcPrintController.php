<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\ClassMaster;
use App\Models\Admin\DistrictMaster;
use App\Models\Admin\SessionMaster;
use App\Models\Admin\StateMaster;
use App\Models\Student\StudentMaster;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use NumberFormatter;

class TcPrintController extends Controller
{
    //
    /**
     * index file
     */
    public function index()
    {

        return view('admin.reports.print_tc', ['image' => config('myconfig.mysignature')]);
    }
    public function fillDetails(Request $request)
    {
        try {

            $srno = $request->srno;
            $session_id = $request->session;
            $type_id = $request->tid;


            $image = config('myconfig.mysignature');
            // Fetch student details
            $studentDetail = DB::table('stu_detail')->where('srno', $srno)->where('active', 1)->first();
            // dd($studentDetail);
            if ($studentDetail) {
                // Fetch parent details
                $parentDetail = DB::table('parents_detail')->where('srno', $srno)->where('active', 1)->first();

                if ($parentDetail) {
                    // Fetch student main details
                    $studentMainSrno = StudentMaster::where('srno', $srno)->where('ssid', 4)->first();
                    // return $parentDetail;
                    // return $studentMainSrno;

                    if ($studentMainSrno) {
                        // Ref Slip No
                        $ltrRefSlipNo = $studentMainSrno->TCRefNo;

                        // Student Name
                        $studentName = "" . $studentDetail->name . "";
                        $stGender = $studentMainSrno->gender;
                        // Father and Mother Name
                        $fName = $parentDetail->f_name;
                        $mName = $parentDetail->m_name;
                        $parentNames = $studentMainSrno->gender == 1 ?
                            "S/o " . $parentDetail->f_name . " and " . $parentDetail->m_name . "" :
                            "D/o " . $parentDetail->f_name . " and " . $parentDetail->m_name . "";

                        // Address and District
                        $district = DistrictMaster::find($studentDetail->district_id);
                        // dd($district);
                        $state = StateMaster::find($studentDetail->state_id);
                        $stateName = $state->name;
                        $districtName = $district->name;
                        $stAddress = $studentDetail->address;
                        $address = "Resident of : " . $studentDetail->address . " District : " . $district->name . " State : " . $state->name . "";

                        // Date of Birth
                        $dob = new \DateTime($studentDetail->dob);
                        $dobFormatted = $dob->format('d-M-Y');
                        $dobInWords = $this->convertDateToWords($dob->format('d'), $dob->format('m'), $dob->format('Y'));
                        $litDob = "Born on : " . $dobFormatted . " (" . $dobInWords . ")";
                        $stDob = $dobFormatted . " (" . $dobInWords . ")";
                        // Class and Admission Date
                        $classMaster = ClassMaster::find($studentMainSrno->class);
                        $admissionDate = new \DateTime($studentMainSrno->admission_date);
                        $className = $classMaster->class;
                        $adDate = $admissionDate->format('d-M-Y');
                        $stSrno = $srno;
                        $classText = "Joined this school in class : " . $classMaster->class . " on : " . $admissionDate->format('d-M-Y') . " Vide admission No. : " . $srno . " ";

                        // Reason for leaving
                        $reasonText = $this->getReasonForLeaving($type_id, $studentMainSrno, $session_id, $srno);
                        // dd($reasonText);
                        // Payment Status
                        $paymentStatus = $studentMainSrno->gender == 1 ? "He has paid all School dues." : "She has paid all School dues.";

                        // Date of Issue
                        $dateIssued = now()->format('d-M-Y');


                        return view('admin.reports.print_tc', compact(
                            'ltrRefSlipNo',
                            'studentName',
                            'stGender',
                            'fName',
                            'mName',
                            // 'parentNames',
                            'stAddress',
                            'stateName',
                            'districtName',
                            // 'address',
                            // 'litDob',
                            'stDob',
                            // 'classText',
                            'className',
                            'adDate',
                            'stSrno',
                            'reasonText',
                            'paymentStatus',
                            'dateIssued',
                            'image'
                        ));
                    } else {
                        return response()->json(['message' => 'Details Not Found, Try Again.']);
                    }
                } else {
                    return response()->json(['message' => 'Details Not Found, Try Again.']);
                }
            } else {
                return response()->json(['message' => 'Details Not Found, Try Again.']);
            }
        } catch (\Exception $e) {
            return response()->json(['message' => 'Access Denied.']);
        }
    }



    private function convertDateToWords($day, $month, $year)
    {
        // Create a Carbon instance from the components
        $date = Carbon::createFromDate($year, $month, $day);

        // Convert day number to word format using Carbon's translatedFormat
        $dayInWords = $date->format('jS'); // Gets day with suffix (e.g., '1st', '2nd', '3rd', '4th')
        // Or use this if you want just the number in words
        // $dayInWords = $date->formatLocalized('%e'); // Gets day number without leading zeros

        // Get full month name
        $monthName = $date->format('F'); // Gets full month name

        // Get year in words using Carbon's translatedFormat
        $yearInWords = $date->format('Y'); // Gets full year

        return "{$dayInWords} of {$monthName} {$yearInWords}";
    }



    private function getReasonForLeaving($type_id, $studentMainSrno, $session_id, $srno)
    {
        switch ($type_id) {
            case 1:
                // Query for type_id = 1 (Leaving after final result)
                $classMaster = ClassMaster::find($studentMainSrno->class);
                $resultDate = SessionMaster::find($session_id)->result_date;
                // Ensure $resultDate is a DateTime object
                $resultDate = new \DateTime($resultDate);
                return "left from class " . $classMaster->class . " on " . $resultDate->format('d-M-Y') . " due to " . $studentMainSrno->reason . ".";

            case 2:
                // Query for type_id = 2 (Leaving after a certain session)
                $previousSession = StudentMaster::where('srno', $srno)->orderBy('id', 'desc')->skip(1)->first();
                $classMaster = ClassMaster::find($previousSession->class);
                $resultDates = SessionMaster::find($previousSession->session_id)->result_date;
                // Convert $resultDates to a timestamp
                $resultDate = strtotime($resultDates);
                return "left from class " . $classMaster->class . " on " . date('d-M-Y', $resultDate) . " due to " . $studentMainSrno->reason . ".";

            case 3:
                // Query for type_id = 3 (Leaving within the same session)
                $classMaster = ClassMaster::find($studentMainSrno->class);
                // Ensure $studentMainSrno->editdate is a DateTime object
                $editDate = new \DateTime($studentMainSrno->editdate);
                return "left from class " . $classMaster->class . " on " . $editDate->format('d-M-Y') . " in order to " . $studentMainSrno->reason . ".";

            default:
                return '';
        }
    }

}
