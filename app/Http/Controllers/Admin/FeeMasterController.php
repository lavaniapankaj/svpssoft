<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\FeeMaster;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;
use App\Http\Controllers\Admin\SessionMasterController;
use App\Http\Controllers\Admin\ClassMasterController;

class FeeMasterController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query =  FeeMaster::query();
        if ($query) {
            $sessions = SessionMasterController::getSessions();
            $classes = ClassMasterController::getClasses();
            if (filled($request->session_id) && filled($request->class_id)) {
                $query->where('session_id', $request->session_id)->where('class_id', $request->class_id);
            }
            $data = $query->where('active', 1)->orderBy('created_at', 'DESC')->paginate(15);
            return view('admin.academic_fee.index', compact('data','classes','sessions'));
        } else {
            return redirect()->back()->with('error', 'Something went wrong, please try again.');
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $sessions = SessionMasterController::getSessions();
        $classes = ClassMasterController::getClasses();
        return view('admin.academic_fee.create', compact('classes','sessions'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'session_id' => 'required|exists:session_masters,id,active,1',
            'admission_fee' => 'required|numeric|min:0',
            'inst_1' => 'required|numeric|min:0',
            'inst_2' => 'required|numeric|min:0',
            'ins_discount' => 'required|numeric|min:0',
            'inst_total' => 'required|numeric|min:0',
            'class_id' => [
                'required',
                'exists:class_masters,id,active,1',
                Rule::unique('fee_masters')->where(function ($query) use ($request) {
                    return $query->where('class_id', $request->class_id)->where('session_id', $request->session_id)->where('active', 1);
                }),
            ],
        ], [
            'session_id.required' => 'Select Session.',
            'session_id.exists' => 'Selected Session is invalid.',
            'class_id.required' => 'Select Class',
            'class_id.exists' => 'Selected Class is invalid.',
            'class_id.unique' => 'Fee already exists for the selected class and session.',
            'admission_fee.required' => 'Enter Admission Fee',
            'inst_1.required' => 'Enter First Installment',
            'inst_2.required' => 'Enter Second Installment',
            'ins_discount.required' => 'Enter Discount',
            'inst_total.required' => 'Enter Total',
            'numeric' => 'The :attribute must be a number.',
            'min' => 'The :attribute must be at least :min.',
        ]);

       $feeData = [
            'session_id' => $request->session_id,
            'class_id' => $request->class_id,
            'admission_fee' => $request->admission_fee,
            'inst_1' => $request->inst_1,
            'inst_2' => $request->inst_2,
            'ins_discount' => $request->ins_discount ?? 0,
            'inst_total' => $request->inst_total,
            'add_user_id' => Session::get('login_user'),
            'active' => 1,

        ];
        if (!empty($feeData)) {
            $fee = FeeMaster::create($feeData);
            if ($fee) {
                return redirect()->back()->withInput(['session_id' => $request->session_id])->with('success', 'Academic Fee saved successfully.');
            } else {
                return redirect()->back()->with('error', 'Something went wrong, please try again.');
            }
        } else {
            return redirect()->back()->with('error', 'Something went wrong, please try again.');
        }
    }




    /**
     * Display the specified resource.
     */
    public function show(FeeMaster $academic_fee_master)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(FeeMaster $academic_fee_master)
    {
        if (!empty($academic_fee_master)) {
            $sessions = SessionMasterController::getSessions();
            $classes = ClassMasterController::getClasses();
            $feeMaster = $academic_fee_master;
            return view('admin.academic_fee.edit', compact('feeMaster', 'classes', 'sessions'));
        } else {
            return redirect()->back()->with('error', 'Something went wrong, please try again.');
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, FeeMaster $academic_fee_master)
    {
        $request->validate([
            'session_id' => 'required|exists:session_masters,id,active,1',
            'admission_fee' => 'required|numeric|min:0',
            'inst_1' => 'required|numeric|min:0',
            'inst_2' => 'required|numeric|min:0',
            'ins_discount' => 'required|numeric|min:0',
            'inst_total' => 'required|numeric|min:0',
            'class_id' => [
                'required',
                'exists:class_masters,id,active,1',
                Rule::unique('fee_masters')->where(function ($query) use ($request) {
                    return $query->where('class_id', $request->class_id)->where('session_id', $request->session_id)->where('active', 1);
                })->ignore($request->id),
            ],
        ], [
            'session_id.required' => 'Select Session.',
            'session_id.exists' => 'Selected Session is invalid.',
            'class_id.required' => 'Select Class.',
            'class_id.exists' => 'Selected Class is invalid.',
            'admission_fee.required' => 'Enter Admission Fee',
            'inst_1.required' => 'Enter First Installment',
            'inst_2.required' => 'Enter Second Installment',
            'ins_discount.required' => 'Enter Discount',
            'inst_total.required' => 'Enter Total',
            'numeric' => 'The :attribute must be a number.',
            'min' => 'The :attribute must be at least :min.',
        ]);

        $feeData = [
            'session_id' => $request->session_id,
            'class_id' => $request->class_id,
            'admission_fee' => $request->admission_fee,
            'inst_1' => $request->inst_1,
            'inst_2' => $request->inst_2,
            'ins_discount' => $request->ins_discount ?? 0,
            'inst_total' => $request->inst_total,
            'edit_user_id' => Session::get('login_user'),
        ];
        $fee = $academic_fee_master->update($feeData);
        if ($fee) {
            return redirect()->route('admin.academic-fee-master.index')->with('success', 'Academic Fee updated successfully.');
        } else {
            return redirect()->back()->with('error', 'Something went wrong, please try again.');
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(FeeMaster $academic_fee_master)
    {
        //
    }
}
