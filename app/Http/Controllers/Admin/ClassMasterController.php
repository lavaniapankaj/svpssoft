<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\ClassMaster;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class ClassMasterController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        # code...
        $fields = ['id', 'class', 'sort'];
        if (!empty($fields)) {
            $data = self::getClasses($fields, 10, true);
            return view('admin.class.index', compact('data'));
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
        return view('admin.class.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'class' => 'required|string|unique:class_masters,class,NULL,id,active,1',
            'sort' => 'required|integer',

        ]);
        $classData = [
            'class' => $request->class,
            'sort' => $request->sort,
            'add_user_id' => Session::get('login_user'),
            'edit_user_id' => Session::get('login_user'),
            'active' => 1,

        ];
        $class = ClassMaster::create($classData);

        if ($class) {
            return redirect()->route('admin.class-master.index')->with('success', 'Class saved successfully.');
        } else {
            return redirect()->back()->with('error', 'Something went wrong, please try again.');
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(ClassMaster $classMaster)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ClassMaster $classMaster)
    {
        # code...
        if ($classMaster) {
            return view('admin.class.edit', compact('classMaster'));
        } else {
            return redirect()->back()->with('error', 'Something went wrong, please try again.');
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ClassMaster $classMaster)
    {
        //
        $request->validate([
            'class' => 'required|string|unique:class_masters,class,' . $request->id . ',id,active,1',
            'sort' => 'required|integer',

        ]);
        $classData = [
            'class' => $request->class,
            'sort' => $request->sort,
            'edit_user_id' => Session::get('login_user'),
        ];

        $class = $classMaster->update($classData);

        if ($class) {
            return redirect()->route('admin.class-master.index')->with('success', 'Class updated successfully.');
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
    //         $classData = ['active' => 0];

    //         $class = ClassMaster::find($id);
    //         if ($class) {
    //             $class->update($classData);
    //             return response()->json(['status' => 'success', 'message' => 'Class Master deleted successfully.']);
    //         } else {
    //             return response()->json(['status' => 'error', 'message' => 'Failed to delete Class Master.'], 404);
    //         }
    //     } catch (\Exception $e) {
    //         return response()->json(['status' => 'error', 'message' => 'An error occurred while deleting.'], 500);
    //     }
    // }

    /**
     * Get all the classes
     */

    public static function getClasses($fields = [], $limit = 10, $paginate = false, $where = [], $whereAttr = '', $isGet = false)
    {
        $query = ClassMaster::query()->where('active', 1);
        if (!empty($fields) && is_array($fields)) {

            $query->select($fields);
        } else {
            $query->select('*');
        }
        if (!empty($where) && is_array($where) && is_string($whereAttr)) {
            foreach ($where as $field => $value) {
                $query->$whereAttr($field, $value);
            }
        } elseif (!empty($where) && is_array($where)) {
            # code...
            foreach ($where as $field => $value) {
                $query->where($field, $value);
            }
        }
        $query->orderBy('sort', 'ASC');
        if ($isGet) {
            return $query->get();
        }

        if ($paginate) {
            $limit = (is_numeric($limit) && $limit > 0) ? $limit : 10;
            return $query->paginate($limit);
        } else {
            return $query->pluck('class', 'id')->toArray();
        }
    }
    /**
     * getting classes for ajax request
     */
    public function getClassesAjax()
    {
        try {
            $classes = self::getClasses();
            if (!empty($classes)) {
                return response()->json([
                    'status' => 'success',
                    'message' => "All Classes",
                    'data' => $classes,
                ], 200);
            } else {
                return response()->json([
                    'status' => 'success',
                    'message' => "No Classes found",
                    'data' => [],
                ], 200);
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => "Failed to get classes"
            ], 500);
        }
    }
}
