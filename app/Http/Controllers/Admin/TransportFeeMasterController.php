<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Student\StudentMaster;
use Illuminate\Http\Request;
use App\Http\Controllers\Admin\ClassMasterController;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class TransportFeeMasterController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $classes = ClassMasterController::getClasses();
        return view('admin.transport_fee.index', compact('classes'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $classes = ClassMasterController::getClasses();
        return view('admin.transport_fee.create', compact('classes'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $sessionId = Session::get('current_session')->id;
        $request->validate([
            'class_id' => 'required|exists:class_masters,id,active,1',
            'section_id' => 'required|exists:section_masters,id,active,1',
            'std_id' => 'required|exists:stu_main_srno,srno',
            'trans_1st_inst' => 'required|numeric|min:0',
            'trans_2nd_inst' => 'required|numeric|min:0',
            'trans_discount' => 'required|numeric|min:0',
            'trans_total' => 'required|numeric|min:0',

        ],[
            'std_id.exists' => 'The selected student ID is invalid.',
            'class_id.exists' => 'The selected class is invalid.',
            'section_id.exists' => 'The selected section is invalid.',
            'trans_1st_inst.required' => 'The first installment field is required.',
            'trans_1st_inst.numeric' => 'The first installment must be a number.',
            'trans_1st_inst.min' => 'The first installment must be at least 0.',
            'trans_2nd_inst.required' => 'The second installment field is required.',
            'trans_2nd_inst.numeric' => 'The second installment must be a number.',
            'trans_2nd_inst.min' => 'The second installment must be at least 0.',
            'trans_discount.required' => 'The discount field is required.',
            'trans_discount.numeric' => 'The discount must be a number.',
            'trans_discount.min' => 'The discount must be at least 0.',
            'trans_total.required' => 'The total field is required.',
            'trans_total.numeric' => 'The total must be a number.',
            'trans_total.min' => 'The total must be at least 0.'
        ]);
        if (!empty($request->std_id)) {
            $q = StudentMaster::where('srno',$request->std_id)->where('class', $request->class_id)->where('section',$request->section_id)->where('session_id', $sessionId)->where('ssid', 1)->where('active', 1);
            $std = $q->update([
                'transport' => ($request->trans_total == 0) ? 0 : 1,
                'trans_1st_inst' => $request->trans_1st_inst,
                'trans_2nd_inst' => $request->trans_2nd_inst,
                'trans_total' => $request->trans_total,
                'trans_discount' => $request->trans_discount ?? 0,
            ]);
            if ($std > 0) {
                // return redirect()->route('admin.transport-fee-master.index')->with('success', 'Transport Fee updated successfully.');
                return redirect()->back()->withInput(['class_id' => $request->class_id, 'section_id' => $request->section_id])->with('success', 'Transport Fee updated successfully.');
            } else {
                return redirect()->back()->with('error', 'Something went wrong, please try again.');
            }
        }else {
            return redirect()->back()->with('error', 'Something went wrong, please try again.');
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
    public function edit(string $id)
    {
        //
        // if ($id) {
        //     # code...
        //     $fee = StudentMaster::findOrFail($id);
        //     $classes = ClassMasterController::getClasses();
        //     return view('admin.transport_fee.create', compact('fee', 'classes'));
        // }else{
        //     return redirect()->back()->with('error', 'Something went wrong, please try again.');
        // }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //

    }

    /* Get Student with transaport fee for drop-down in transaport fee section (SSid-1)  >>>>>>>> Only currect session*/
    public function getStudents(Request $request)
    {
        try {
            $sessionId = Session::get('current_session')->id;
            $validator = Validator::make($request->all(), [
                'class_id' => 'required|exists:class_masters,id,active,1',
                'section_id' => 'required|exists:section_masters,id,active,1',
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $validator->errors()
                ], 200);
            }
            $currentSession = $sessionId;
            $baseQuery = DB::table('stu_main_srno')
                ->leftJoin('stu_detail', 'stu_main_srno.srno', '=', 'stu_detail.srno')
                ->leftJoin('parents_detail as parents', 'stu_main_srno.srno', '=', 'parents.srno')
                ->select(
                    'stu_main_srno.srno',
                    'stu_main_srno.rollno',
                    'stu_main_srno.transport',
                    'stu_main_srno.trans_1st_inst',
                    'stu_main_srno.trans_2nd_inst',
                    'stu_main_srno.trans_discount',
                    'stu_main_srno.trans_total',
                    'stu_detail.name as student_name',
                    'parents.f_name as father_name',
                )->where('stu_main_srno.class', $request->class_id)->where('stu_main_srno.section', $request->section_id)->where('stu_main_srno.active', 1)->where('stu_main_srno.session_id', $currentSession)->where('stu_main_srno.ssid', 1)->orderBy('stu_main_srno.rollno', 'asc');
            $data = $baseQuery->get();
            if ($data->isEmpty()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No student found for the selected class and section.',
                    'data' => []
                ], 200);
            }else {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Students fetched successfully.',
                    'data' => $data
                ], 200);
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => "Failed to get students",
                'data' => []
            ], 200);
        }
    }





}
