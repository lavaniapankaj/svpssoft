<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\StateMaster;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class StateMasterController extends Controller
{

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        //
        $fields = ['id', 'name', 'active', 'created_at'];
        $where = !empty($request->search) ? ['name' => $request->search] : [];
        $orderBy = [
            'created_at' => 'DESC'
        ];
        $data = self::getAllStates($fields, $where, $orderBy, 10, true);

        if ($data !== null) {
            return view('admin.state.index', compact('data'));
        } else {
            return redirect()->back()->with('error', 'Something went wrong, please try again.');
        }

    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
        return view('admin.state.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
        $request->validate([
            'state' => 'required|string|unique:state_masters,name,NULL,id,active,1',

        ]);

        $stateData = [
            'name' => $request->state,
            'add_user_id' => Session::get('login_user'),
            'edit_user_id' => Session::get('login_user'),
            'active' => 1,

        ];

        $state = StateMaster::create($stateData);

        if ($state) {
            return redirect()->route('admin.state-master.index')->with('success', 'State saved successfully.');
        } else {
            return redirect()->back()->with('error', 'Something went wrong, please try again.');
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(StateMaster $stateMaster)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(StateMaster $stateMaster)
    {
        //
        if (!empty($stateMaster)) {
            # code...
            return view('admin.state.edit', compact('stateMaster'));
        } else {
            return redirect()->back()->with('error', 'Something went wrong, please try again.');
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, StateMaster $stateMaster)
    {
        //
        $request->validate([
            'state' => 'required|string|unique:state_masters,name,' . $request->id . ',id,active,1',

        ]);

        $stateData = [
            'name' => $request->state,
            'edit_user_id' => Session::get('login_user'),
        ];
        $state = $stateMaster->update($stateData);

        if ($state) {
            return redirect()->route('admin.state-master.index')->with('success', 'State updated successfully.');
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
    //         $stateData = ['active' => 0];

    //         $state = StateMaster::find($id);
    //         if ($state) {
    //             $state->update($stateData);
    //             return response()->json(['status' => 'success', 'message' => 'State Master deleted successfully.']);
    //         } else {
    //             return response()->json(['status' => 'error', 'message' => 'Failed to delete State Master.'], 404);
    //         }
    //     } catch (\Exception $e) {
    //         return response()->json(['status' => 'error', 'message' => 'An error occurred while deleting.'], 500);
    //     }
    // }

    /**
     * Get All The States
     */
    public static function getAllStates($fields = [], $where = [], $orderBy = [], $limit = 10, $paginate = false)
    {
        $query = StateMaster::query()->where('active', 1);

        if (!empty($fields) && is_array($fields)) {

            $query->select($fields);
        } else {
            $query->select('*');
        }
        if (!empty($where)) {
            foreach ($where as $key => $value) {
                $query->where($key, 'LIKE', "%{$value}%");
            }
        }
        if (!empty($orderBy) && is_array($orderBy)) {
            foreach ($orderBy as $key => $value) {
                $query->orderBy($key, $value);
            }

        } else {
            $query->orderBy('name', 'asc');
        }
        if ($paginate) {
            $limit = (is_numeric($limit) && $limit > 0) ? $limit : 10;
            return $query->paginate($limit);
        } else {
            return $query->pluck('name', 'id')->toArray();
        }
    }
    /**
     * getting states using ajax
     */
    public function getStatesAjax()
    {
        try {
            $states = self::getAllStates();
            return response()->json([
                'status' => 'success',
                'message' => "All States",
                'data' => $states,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => "Failed to get states"
            ], 500);
        }
    }
}
