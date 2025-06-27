<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\AttendanceSchedule;
use App\Models\Admin\SessionMaster;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Log;

class AttendanceScheduleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
        $data = AttendanceSchedule::where('active', 1)
            ->orderBy('created_at', 'DESC')
            ->paginate(10);

        return view('admin.attendance.index', compact('data'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
        $sessions = SessionMaster::where('active',1)->get();
        return view('admin.attendance.create', compact('sessions'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Validate incoming request
        $request->validate([
            'a_date' => [
                'required',
                'date_format:Y-m-d\TH:i',
                Rule::unique('attendance_schedule')->where(function ($query) use ($request) {
                    return $query->where('session_id', $request->session_id)->where('active', 1);
                })->ignore($request->id),
            ],
            'session_id' => 'required|exists:session_masters,id',
            'reason' => 'required|string',
        ], [
            'a_date.date_format' => 'The date must be in the format YYYY-MM-DDTHH:mm.',
            'session_id.required' => 'Please select a session.',
            'reason.required' => 'Please provide a reason for attendance.',
        ]);

        $user = Auth::user();

        // Prepare attendance data
        $attendanceData = [
            'a_date' => Carbon::createFromFormat('Y-m-d\TH:i', $request->a_date)->format('Y-m-d H:i:s'),
            'session_id' => $request->session_id,
            'reason' => $request->reason,
            'add_user_id' => $user->id,
            'edit_user_id' => $user->id,
            'active' => 1,
        ];

        // Store or update attendance data
        $attend = AttendanceSchedule::updateOrCreate(['id' => $request->id], $attendanceData);

        if ($attend) {
            return redirect()->route('admin.attendance-schedule-master.index')->with('success', $request->id ? 'Attendance-schedule updated successfully.' : 'Attendance-schedule saved successfully.');
        } else {
            // Log::error('Failed to save attendance record', ['request' => $request->all()]);
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
        $data = AttendanceSchedule::findOrFail($id);
        $sessions = SessionMaster::where('active',1)->get();
        // $data->a_date = \Carbon\Carbon::parse($data->a_date)->format('Y-m-d');
        return view('admin.attendance.create', compact('data', 'sessions'));
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
    // public function softDelete($id)
    // {
    //     //
    //     $attendanceData = ['active' => 0];

    //     $attend = AttendanceSchedule::find($id);
    //     if ($attend) {
    //         $attend->update($attendanceData);

    //         return redirect()->route('admin.attendance-schedule-master.index')->with('success', 'Attendance-schedule deleted successfully.');
    //     } else {
    //         return redirect()->back()->with('error', 'Failed to delete Section Master.');
    //     }

    // }
}
