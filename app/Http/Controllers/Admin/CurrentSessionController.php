<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\SessionMaster;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use App\Http\Controllers\Admin\SessionMasterController;

class CurrentSessionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
        $data = SessionMasterController::getSessions(['id','session'], ['admin_current_session'=> 1]);

        return view('admin.current_session.index', compact('data'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //

        return view('admin.current_session.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'session_id' => 'required|exists:session_masters,id,active,1',
        ]);
        $user = Auth::user();
        SessionMaster::where('admin_current_session', 1)->where('active', 1)->update(['admin_current_session' => 0]);
        $sessionData = [
            'admin_current_session' => 1,
            'edit_user_id' => Session::get('login_user'),
        ];

        $session = SessionMaster::where('id', $request->session_id)->update($sessionData);
        if ($session) {
            $current_session = SessionMaster::where('active', 1)->where('admin_current_session', 1)->first();
            Session::put('current_session', $current_session);
            return redirect()->route('admin.current-session.index')->with('success', 'Current Session updated successfully.');
        } else {
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
        // $sessions = SessionMaster::where('active', 1)->get();
        if ($id) {
            # code...
            $sessions = SessionMasterController::getSessions();
            $data = SessionMaster::findOrFail($id);
            return view('admin.current_session.create', compact('data','sessions'));
        }else {
            return redirect()->back()->with('error', 'Something went wrong, please try again.');
        }
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
