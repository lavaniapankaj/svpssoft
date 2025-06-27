<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\DistrictMaster;
use App\Models\Admin\StateMaster;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use App\Http\Controllers\Admin\StateMasterController;
use Illuminate\Support\Facades\Session;

class DistrictMasterController extends Controller
{


    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        //
        // $query = DistrictMaster::select('id', 'name', 'state_id', 'active', 'created_at');
        // if ($query) {
            # code...
            $states = StateMasterController::getAllStates();
            // if (!empty($request->state_id)) {
            //     $query->where('state_id', $request->state_id)->where('active', 1);
            // }
            // $data = $query->where('active', 1)->orderBy('created_at', 'DESC')->paginate(10);
            $fields = ['id', 'name', 'state_id', 'active', 'created_at'];
            $where = !empty($request->state_id) ? ['state_id'=>$request->state_id] : [];
            $data = self::getAllDistrict($fields, $where, [], 10, true);
            return view('admin.district.index', compact('data', 'states'));
        // } else {
        //     return redirect()->back()->with('error', 'Something went wrong, please try again.');
        // }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
        $states = StateMasterController::getAllStates();
        return view('admin.district.create', compact('states'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
        $request->validate([
            'name' => [
                'required',
                'string',
                Rule::unique('district_masters')->where(function ($query) use ($request) {
                    return $query->where('state_id', $request->state_id)->where('active', 1);
                }),
            ],
            'state_id' => 'required|exists:state_masters,id,active,1',
        ]);
        $districtData = [
            'name' => $request->name,
            'state_id' => $request->state_id,
            'add_user_id' => Session::get('login_user'),
            'edit_user_id' => Session::get('login_user'),
            'active' => 1,

        ];
        if (!empty($districtData)) {
            # code...
            $district = DistrictMaster::create($districtData);

            if ($district) {
                return redirect()->route('admin.district-master.index')->with('success', 'District saved successfully.');
            }
        } else {
            return redirect()->back()->with('error', 'Something went wrong, please try again.');
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(DistrictMaster $districtMaster)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(DistrictMaster $districtMaster)
    {
        //
        if (!empty($districtMaster)) {
            # code...
            $states = StateMasterController::getAllStates();
            return view('admin.district.edit', compact('districtMaster', 'states'));
        } else {
            return redirect()->back()->with('error', 'Something went wrong, please try again.');
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, DistrictMaster $districtMaster)
    {
        //
        $request->validate([
            'name' => [
                'required',
                'string',
                Rule::unique('district_masters')->where(function ($query) use ($request) {
                    return $query->where('state_id', $request->state_id)->where('active', 1);
                })->ignore($request->id),
            ],
            'state_id' => 'required|exists:state_masters,id,active,1',
        ]);

        $districtData = [
            'name' => $request->name,
            'state_id' => $request->state_id,
            'edit_user_id' => Session::get('login_user'),

        ];
        if (!empty($districtData)) {
            # code...

            $district = $districtMaster->update($districtData);

            if ($district) {
                return redirect()->route('admin.district-master.index')->with('success', 'District updated successfully.');
            } else {
                return redirect()->back()->with('error', 'Something went wrong, please try again.');
            }
        } else {
            return redirect()->back()->with('error', 'Something went wrong, please try again.');
        }
    }

    public static function getAllDistrict($fields = [], $where = [], $orderBy = [], $limit = 10, $paginate = false)
    {
        $query = DistrictMaster::query()->where('active', 1);

        if (!empty($fields) && is_array($fields)) {

            $query->select($fields);
        } else {
            $query->select('*');
        }
        if (!empty($where)) {
            foreach ($where as $key => $value) {
                $query->where($key, $value);
            }
        }
        if (!empty($orderBy) && is_array($orderBy)) {
            foreach ($orderBy as $key => $value) {
                $query->orderBy($key, $value)->orderBy('name', 'asc');
            }

        } else {
            $query->orderBy('state_id', 'asc')->orderBy('name', 'asc');
        }
        if ($paginate) {
            $limit = (is_numeric($limit) && $limit > 0) ? $limit : 10;
            return $query->paginate($limit);
        } else {
            return $query->pluck('name', 'id')->toArray();
        }
    }
    public function getDistricts(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'state_id' => 'required|exists:state_masters,id,active,1',
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $validator->errors()
                ], 400);
            }
            $stateId = $request->input('state_id');
            if (!empty($stateId)) {
                $districts = self::getAllDistrict([], ['state_id' => $stateId], []);
                // $districts = DistrictMaster::where('state_id', $stateId)->where('active', 1)->pluck('name', 'id');
                if (!empty($districts)) {
                    return response()->json([
                        'status' => 'success',
                        'message' => "All districts according to the states",
                        'data' => $districts,
                    ], 200);

                } else {
                    return response()->json([
                        'status' => 'success',
                        'message' => "No districts are found",
                        'data' => [],
                    ], 200);

                }
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => "Please Select the state",
                ], 400);
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => "Failed to get districts " . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */


    // public function softDelete($id)
    // {
    //     try {
    //         $districtData = ['active' => 0];

    //         $district = DistrictMaster::find($id);
    //         if ($district) {
    //             $district->update($districtData);
    //             return response()->json(['status' => 'success', 'message' => 'District Master deleted successfully.']);
    //         } else {
    //             return response()->json(['status' => 'error', 'message' => 'Failed to delete District Master.'], 404);
    //         }
    //     } catch (\Exception $e) {
    //         return response()->json(['status' => 'error', 'message' => 'An error occurred while deleting.'], 500);
    //     }
    // }
}
