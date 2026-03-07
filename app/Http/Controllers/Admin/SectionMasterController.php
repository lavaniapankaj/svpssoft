<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\ClassMasterController;
use App\Http\Controllers\Controller;
use App\Models\Admin\ClassMaster;
use App\Models\Admin\SectionMaster;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

class SectionMasterController extends Controller
{

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {

        $fields = ['id', 'section', 'class_id', 'active', 'created_at'];
        $whereAttb = isset($request->class_id) ? 'where' : '';
        $where = isset($request->class_id) ? ['class_id' => $request->class_id] : [];
        $orderBy = ['created_at' => 'DESC'];
        if (!empty($fields)) {
            $classes = ClassMasterController::getClasses();
            $data = self::getAllSection($fields, $whereAttb,  $where, $orderBy, 15, true);
            return view('admin.section.index', compact('data', 'classes'));
        } else {
            return redirect()->back()->with('error', 'Something went wrong, please try again.');
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $classes = ClassMasterController::getClasses();
        return view('admin.section.create', compact('classes'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'section' => [
                'required',
                'string',
                Rule::unique('section_masters')->where(function ($query) use ($request) {
                    return $query->where('class_id', $request->class_id)->where('active', 1);
                }),
            ],
            'class_id' => 'required|exists:class_masters,id,active,1',
        ], [
            'section.required' => 'Please enter the section name.',
            'section.string'   => 'The section name must be valid text.',
            'section.unique'   => 'This section already exists for the selected class.',
            'class_id.required' => 'Please select a class.',
            'class_id.exists'   => 'The selected class is invalid or inactive.',
        ]);

        $sectionData = [
            'section' => $request->section,
            'class_id' => $request->class_id,
            'add_user_id' => Session::get('login_user'),
            'edit_user_id' => Session::get('login_user'),
            'active' => 1,

        ];

        $section = SectionMaster::create($sectionData);
        if ($section) {
            return redirect()->route('admin.section-master.index')->with('success', 'Section saved successfully.');
        } else {
            return redirect()->back()->with('error', 'Something went wrong, please try again.');
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(SectionMaster $sectionMaster)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(SectionMaster $sectionMaster)
    {
        if (!empty($sectionMaster)) {
            $classes = ClassMasterController::getClasses();
            return view('admin.section.edit', compact('sectionMaster', 'classes'));
        } else {
            return redirect()->back()->with('error', 'Something went wrong, please try again.');
        }
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, SectionMaster $sectionMaster)
    {
        $request->validate([
            'section' => [
                'required',
                'string',
                Rule::unique('section_masters')->where(function ($query) use ($request) {
                    return $query->where('class_id', $request->class_id)->where('active', 1);
                })->ignore($request->id),
            ],
            'class_id' => 'required|exists:class_masters,id,active,1',
        ],[
            'section.required' => 'Please enter the section name.',
            'section.string'   => 'The section name must contain valid text.',
            'section.unique'   => 'This section name is already assigned to the selected class.',
            'class_id.required' => 'Please select a class.',
            'class_id.exists'   => 'The selected class is invalid or inactive.',
        ]);

        $sectionData = [
            'section' => $request->section,
            'class_id' => $request->class_id,
            'edit_user_id' => Session::get('login_user'),
        ];

        $section = $sectionMaster->update($sectionData);
        if ($section) {
            return redirect()->route('admin.section-master.index')->with('success', 'Section updated successfully.');
        } else {
            return redirect()->back()->with('error', 'Something went wrong, please try again.');
        }
    }

    /**
     * get the sections according to the class
     */
    public static function getAllSection($fields = [], $whereAttb = '',  $where = [], $orderBy = [], $limit = 10, $paginate = false)
    {
        // $query = SectionMaster::query()->where('active', 1);
        $query = DB::table('section_masters')->where('active', 1);

        if (!empty($fields) && is_array($fields)) {

            $query->select($fields);
        } else {
            $query->select('*');
        }
        if (!empty($where) && is_array($where) && !empty($whereAttb) && is_string($whereAttb)) {

            foreach ($where as $key => $value) {
                $query->$whereAttb($key, $value);
            }
        }

        if (!empty($orderBy) && is_array($orderBy)) {
            foreach ($orderBy as $key => $value) {
                $query->orderBy($key, $value);
            }
        } else {
            $query->orderBy('section', 'asc');
        }
        if ($paginate) {
            $limit = (is_numeric($limit) && $limit > 0) ? $limit : 10;
            return $query->paginate($limit);
        } else {
            return $query->pluck('section', 'id')->toArray();
        }
    }
    /**
     * Get Section according to the class for ajax request.
     */
    public function getSectionsAjax(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'class_id' => 'required|exists:class_masters,id,active,1',
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $validator->errors()
                ], 400);
            }

            $classIds = explode(",", $request->class_id);
            if (!empty($classIds)) {
                // Get sections for multiple class IDs

                $whereAttb = isset($classIds) ? 'whereIn' : '';
                $where = isset($classIds) ? ['class_id' => $classIds] : [];
                $sections = self::getAllSection([], $whereAttb,  $where, []);
                if (!empty($sections)) {
                    return response()->json([
                        'status' => 'success',
                        'message' => "All class sections",
                        'data' => $sections,
                    ], 200);
                } else {
                    return response()->json([
                        'status' => 'error',
                        'message' => "No sections found for the provided classes.",
                    ], 404);
                }
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => "Please Select the class",
                ], 400);
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => "Failed to get sections"
            ], 500);
        }
    }

    /**
     * Get Section according to the class for ajax request (In case of all class).
     */
    public static function getAllClassSectionsAjax(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'class_id' => 'required',
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $validator->errors()
                ], 200);
            }
            $classId = $request->class_id;
            $fields = ['id', 'section'];
            if (!empty($classId)) {
                /* In case if the classId as all */
                if ($classId == 'all') {
                    $sections = self::getAllSection();
                    if (!empty($sections)) {
                        return response()->json([
                            'status' => 'success',
                            'message' => "All class sections",
                            'data' => 'all',
                        ], 200);
                    } else {
                        return response()->json([
                            'status' => 'error',
                            'message' => "No sections found for the provided classes.",
                        ], 200);
                    }
                }else {
                    $classExists = DB::table('class_masters')->where('id', $classId)->where('active', 1)->exists();
                    if (!$classExists) {
                        return response()->json([
                            'status' => 'error',
                            'message' => "The selected class is invalid or inactive.",
                        ], 200);
                    }else {
                        $where['class_id'] = $classId;
                        $sections = self::getAllSection($fields, 'where',  $where, []);
                        if (!empty($sections)) {
                            return response()->json([
                                'status' => 'success',
                                'message' => "Class sections",
                                'data' => $sections,
                            ], 200);
                        } else {
                            return response()->json([
                                'status' => 'error',
                                'message' => "No sections found for the provided class.",
                            ], 200);
                        }
                    }
                }
            }else {
                return response()->json([
                    'status' => 'error',
                    'message' => "Something went wrong, please try again.",
                ], 200);
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => "Something went wrong, please try again."
            ], 200);
        }
    }


    /**
     * Remove the specified resource from storage.
     */

    // public function softDelete($id)
    // {
    //     try {
    //         $sectionData = ['active' => 0];

    //         $section = SectionMaster::find($id);
    //         if ($section) {
    //         $section->update($sectionData);
    //             return response()->json(['status' => 'success', 'message' => 'Section Master deleted successfully.']);
    //         } else {
    //             return response()->json(['status' => 'error', 'message' => 'Failed to delete Section Master.'], 404);
    //         }
    //     } catch (\Exception $e) {
    //         return response()->json(['status' => 'error', 'message' => 'An error occurred while deleting.'], 500);
    //     }
    // }
}
