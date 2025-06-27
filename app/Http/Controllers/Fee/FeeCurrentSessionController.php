<?php

namespace App\Http\Controllers\Fee;

use App\Http\Controllers\Admin\SessionMasterController;
use App\Http\Controllers\Controller;
use App\Models\Admin\SessionMaster;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class FeeCurrentSessionController extends Controller
{
    //

    public function index()
    {
        //
        $data = SessionMasterController::getSessions([], ['fee_current_session'=> 1]);
        return view('fee.current_session.index', compact('data'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('fee.current_session.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'session_id' => 'required|exists:session_masters,id,active,1',
        ]);
        SessionMaster::where('fee_current_session', 1)->where('active', 1)->update(['fee_current_session' => 0]);
        $sessionData = [
            'fee_current_session' => 1,
            'edit_user_id' => Session::get('login_user'),
        ];

        $session = SessionMaster::where('id', $request->session_id)->update($sessionData);
        if ($session) {
            $current_session = SessionMasterController::getSessions(['id','session', 'fee_current_session'], ['fee_current_session' => 1]);
            $currentSession = [
                'id' => array_keys($current_session)[0],
                'session' => array_values($current_session)[0]
            ];
            Session::put('fee_current_session', $currentSession);
            return redirect()->route('fee.current-session.index')->with('success', 'Current Session updated successfully.');
        } else {
            return redirect()->back()->with('error', 'Something went wrong, please try again.');
        }
    }


    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        if ($id) {
            # code...
            $data = SessionMaster::findOrFail($id);
            $sessions = SessionMasterController::getSessions(['id','session']);
            return view('fee.current_session.create', compact('data', 'sessions'));
        }else {
            return redirect()->back()->with('error', 'Something went wrong, please try again.');
        }
    }
}
