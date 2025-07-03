<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\SessionMaster;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Session;

class SessionMasterController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $fields = ['id', 'session'];
        if (!empty($fields)) {
            $data = self::getSessions($fields,[], 10, true);
            return view('admin.session.index', compact('data'));
        } else {
            return redirect()->back()->with('error', 'Something went wrong, please try again.');
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.session.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Validate start_year and end_year
        $request->validate([
            'start_year' => 'required|integer|digits:4|between:2004,2050',
            'end_year' => [
                'required',
                'integer',
                'digits:4',
                'between:2005,2051',
                'gt:start_year',
            ],
        ]);

        $uniqueRule = Rule::unique('session_masters')
            ->where(function ($query) use ($request) {
                return $query->where('active', 1)->where('start_year', $request->start_year)
                    ->where('end_year', $request->end_year);
            })
            ->ignore($request->id);

        $request->validate([
            'start_year' => [$uniqueRule],
        ]);
        $sessionData = [
            'start_year' => $request->start_year,
            'end_year' => $request->end_year,
            'session' => $request->start_year . '-' . $request->end_year,
            'add_user_id' => Session::get('login_user'),
            'edit_user_id' => Session::get('login_user'),
            'active' => 1,
        ];

        $session = SessionMaster::Create($sessionData);

        if ($session) {
            return redirect()->route('admin.session-master.index')->with('success', 'Session saved successfully.');
        } else {
            return redirect()->back()->with('error', 'Something went wrong, please try again.');
        }
    }


    /**
     * Show the form for editing the specified resource.
     */
    public function edit(SessionMaster $sessionMaster)
    {
        if ($sessionMaster) {
            # code...
            return view('admin.session.edit', compact('sessionMaster'));
        } else {
            return redirect()->back()->with('error', 'Something went wrong, please try again.');
        }
    }


    /**
     * Display the specified resource.
     */
    public function show(SessionMaster $sessionMaster)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, SessionMaster $sessionMaster)
    {
        // Validate start_year and end_year
        $request->validate([
            'start_year' => 'required|integer|digits:4|between:2004,2050',
            'end_year' => [
                'required',
                'integer',
                'digits:4',
                'between:2005,2051',
                'gt:start_year',
            ],
        ]);

        $uniqueRule = Rule::unique('session_masters')
            ->where(function ($query) use ($request) {
                return $query->where('active', 1)->where('start_year', $request->start_year)
                    ->where('end_year', $request->end_year);
            })
            ->ignore($request->id);

        $request->validate([
            'start_year' => [$uniqueRule],
        ]);
        $sessionData = [
            'start_year' => $request->start_year,
            'end_year' => $request->end_year,
            'session' => $request->start_year . '-' . $request->end_year,
            'edit_user_id' => Session::get('login_user'),
            'active' => 1,
        ];


        $session = $sessionMaster->update($sessionData);

        if ($session) {
            return redirect()->route('admin.session-master.index')->with('success', 'Session updated successfully.');
        } else {
            return redirect()->back()->with('error', 'Something went wrong, please try again.');
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    // public function softDelete($id)
    // {
    //     try {
    //         $sessionData = ['active' => 0];

    //         // Find the session by ID and update its 'active' status
    //         $session = SessionMaster::find($id);
    //         if ($session) {
    //             $session->update($sessionData);
    //             return response()->json(['status' => 'success', 'message' => 'Session Master deleted successfully.']);
    //         } else {
    //             return response()->json(['status' => 'error', 'message' => 'Failed to delete Session Master.'], 404);
    //         }
    //     } catch (\Exception $e) {
    //         return response()->json(['status' => 'error', 'message' => 'An error occurred while deleting.'], 500);
    //     }
    // }

    /**
     * Get all the sessions.
     */
    public static function getSessions($fields = [], $where = [], $limit = 10, $paginate = false)
    {
        $query = SessionMaster::query()->where('active', 1);

        if (!empty($fields) && is_array($fields)) {

            $query->select($fields);
        } else {
            $query->select('*');
        }
        if (!empty($where) && is_array($where)) {
            foreach ($where as $field => $value) {
                $query->where($field, $value);
            }
        }
        if ($paginate) {
            $limit = (is_numeric($limit) && $limit > 0) ? $limit : 10;
            return $query->paginate($limit);
        } else {
            return $query->pluck('session', 'id')->toArray();
        }
    }
    /**
     * Get Session for ajax request.
     */
    public function getSessionsAjax()
    {
        try {
            //code...
            $sessions = self::getSessions();
            if (!empty($sessions)) {
                return response()->json([
                    'status' => 'success',
                    'message' => "All Sessions",
                    'data' => $sessions,
                ], 200);
            } else {
                return response()->json([
                    'status' => 'success',
                    'message' => "No Sessions are found",
                    'data' => [],
                ], 200);
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => "Failed to get sessions "
            ], 500);
        }
    }
}
