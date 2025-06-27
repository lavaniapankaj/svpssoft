<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Student\StudentMaster;
use Illuminate\Http\Request;
use App\Http\Controllers\Admin\ClassMasterController;
use Illuminate\Support\Facades\Session;

class TransportFeeMasterController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
        $classes = ClassMasterController::getClasses();
        return view('admin.transport_fee.index', compact('classes'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
        $classes = ClassMasterController::getClasses();
        return view('admin.transport_fee.create', compact('classes'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
        $request->validate([
            'class_id' => 'required|exists:class_masters,id,active,1',
            'section_id' => 'required|exists:section_masters,id,active,1',
            'std_id' => 'required|exists:stu_main_srno,srno',
            'trans_1st_inst' => 'required|numeric|min:0',
            'trans_2nd_inst' => 'required|numeric|min:0',
            'trans_discount' => 'required|numeric|min:0',
            'trans_total' => 'required|numeric|min:0',

        ]);
        if (!empty($request->std_id)) {
            # code...
            $q = StudentMaster::where('srno',$request->std_id)->where('class', $request->class_id)->where('section',$request->section_id)->where('session_id', session('current_session')->id)->whereIn('ssid', [1,2,3,4,5]);
            $std = $q->update([
                'transport' => ($request->trans_total == 0) ? 0 : 1,
                'trans_1st_inst' => $request->trans_1st_inst,
                'trans_2nd_inst' => $request->trans_2nd_inst,
                'trans_total' => $request->trans_total,
                'trans_discount' => $request->trans_discount ?? 0,
            ]);
            if ($std > 0) {
                return redirect()->route('admin.transport-fee-master.index')->with('success', 'Transport Fee updated successfully.');
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
}
