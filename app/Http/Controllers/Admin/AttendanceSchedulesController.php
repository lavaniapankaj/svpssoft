<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\AttendanceSchedule;
use App\Models\Admin\SessionMaster;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AttendanceSchedulesController extends Controller
{
    //

    public function index(Request $request)
    {
        $currentSession = session('current_session')->id;
        $query = AttendanceSchedule::query();
        if ($request->filled('a_date')) {
            $query->where('a_date', $request->a_date);
        }
        $d = $query->where('session_id',$currentSession)->orderBy('updated_at','desc')->take(30)->get();
        $data = $d->paginate(10);
        return view('admin.attendance_schedule.index', compact('data'));
    }


    public function create(Request $request)
    {

        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', Carbon::now()->endOfMonth()->toDateString());

        $dates = $this->generateDateRange($startDate, $endDate, true);

        return view('admin.attendance_schedule.create', compact('dates', 'startDate', 'endDate'));
    }

    public function generate(Request $request)
    {

        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $dates = $this->generateDateRange($startDate, $endDate, true);

        return response()->json($dates);
    }
    private function generateDateRange($startDate, $endDate, $includeExisting = false)
    {
        $dates = [];
        $currentDate = Carbon::parse($startDate);
        $endDate = Carbon::parse($endDate);

        $existingSchedules = [];
        if ($includeExisting) {
            $existingSchedules = AttendanceSchedule::whereBetween('a_date', [$startDate, $endDate])
                ->get()
                ->keyBy('a_date')
                ->toArray();  // Convert to array
        }

        while ($currentDate <= $endDate) {
            $dateString = $currentDate->toDateString();
            $existingSchedule = $existingSchedules[$dateString] ?? null;

            // Determine status and reason
            $isSunday = $currentDate->isSunday();
            $status = $isSunday ? false : ($existingSchedule ? $existingSchedule['status'] : true);
            $reason = $isSunday ? 'Sunday' : ($existingSchedule ? $existingSchedule['reason'] : null);

            $dates[] = [
                'a_date' => $dateString,
                'day' => $currentDate->format('l'),
                'status' => $status,
                'reason' => $reason,
            ];

            $currentDate->addDay();
        }

        return $dates;
    }





    public function store(Request $request)
    {
        $data = $request->validate([
            'dates' => 'required|array',
            'dates.*.a_date' => 'required|date',
            'dates.*.status' => 'boolean',
            'dates.*.reason' => 'nullable|string',
        ]);

        $user = Auth::user();

        foreach ($data['dates'] as $date) {
            $status = isset($date['status']) ? 1 : 0;

            $reason = ($status === 0 && isset($date['reason'])) ? $date['reason'] : null;

            AttendanceSchedule::updateOrCreate(
                [
                    'session_id' => $request->current_session,
                    'a_date' => $date['a_date']
                ],
                [
                    'status' => $status,
                    'reason' => $reason, 
                    'session_id' => $request->current_session,
                    'add_user_id' => $user->id,
                    'edit_user_id' => $user->id,
                    'active' => 1,
                ]
            );
        }
        return redirect()->route('admin.attendance_schedule.index')->with('success', 'Attendance schedule updated successfully.');
    }





    public function edit($id)
    {
        $attendanceSchedule = AttendanceSchedule::findOrFail($id);
        $startDate = Carbon::parse($attendanceSchedule->a_date)->startOfMonth()->toDateString();
        $endDate = Carbon::parse($attendanceSchedule->a_date)->endOfMonth()->toDateString();
        $current_session = SessionMaster::where('admin_current_session', 1)->value('id');

        $dates = $this->generateDateRange($startDate, $endDate);

        // Get the dates that are part of this schedule
        $editableDates = $attendanceSchedule->pluck('a_date')->toArray();

        // Populate the dates array with existing schedule data
        foreach ($dates as &$date) {
            $existingSchedule = $attendanceSchedule->where('a_date', $date['a_date'])->first();
            if ($existingSchedule) {
                $date['status'] = $existingSchedule->status;
                $date['reason'] = $existingSchedule->reason;
                $date['editable'] = true;
            } else {
                $date['editable'] = false;
            }
        }

        return view('admin.attendance_schedule.create', compact('dates', 'startDate', 'endDate', 'attendanceSchedule', 'editableDates', 'current_session'));
    }

    public function editSpecificDate(Request $request)
    {
        $attendanceSchedule = AttendanceSchedule::where('session_id', $request->session_id)
            ->where('a_date', $request->a_date)
            ->first();

        return response()->json([
            'reason' => $attendanceSchedule ? $attendanceSchedule->reason : ''
        ]);
    }

    public  function editDateView()
    {
        return view('admin.attendance_schedule.specificDate');
    }

    public function updateSpecifiDate(Request $request)
    {
        $request->validate([
            'a_date' => 'required|date',
            'reason' => 'nullable|string',
        ]);
        $attend = AttendanceSchedule::where('session_id', $request->current_session)->where('a_date', $request->a_date)->update(['reason' => $request->reason]);
        // dd($request->all());
        if ($attend) {
            return redirect()->route('admin.attendance_schedule.index')->with('success', 'Attendance schedule updated successfully.');
        } else {
            return redirect()->back()->with('error', 'Something went wrong, please try again.');
        }
    }
}
