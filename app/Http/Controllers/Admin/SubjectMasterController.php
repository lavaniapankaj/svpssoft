<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\ClassMaster;
use App\Models\Admin\SectionMaster;
use App\Models\Admin\SubjectMaster;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use App\Http\Controllers\Admin\ClassMasterController;

class SubjectMasterController extends Controller
{

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $fields = ['id', 'by_m_g', 'priority', 'subject', 'subject_id', 'class_id', 'active', 'created_at'];
        $whereAttb = 'subject_id';
        $where = isset($request->class_id) ? ['class_id' => $request->class_id] : [];
        $orderBy = ['created_at' => 'DESC'];
        if (!empty($fields) && !empty($whereAttb)) {
            # code...
            $classes = ClassMasterController::getClasses();
            $data = self::getAllSubjects($fields, $whereAttb,  $where, $orderBy, false, 10, true);
            return view('admin.subject.index', ['data' => $data, 'classes' => $classes]);
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
        return view('admin.subject.create', compact('classes'));
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'subject' => [
                'required',
                'string',
                Rule::unique('subject_masters')->where(function ($query) use ($request) {
                    return $query->where('class_id', $request->class_id)->where('active', 1);
                }),
            ],
            'class_id' => 'required|exists:class_masters,id,active,1',
            'by_m_g' => 'required',
        ]);
        $user = Auth::user();

        $subjectData = [
            'subject' => $request->subject,
            'by_m_g' => $request->by_m_g,
            'class_id' => $request->class_id,
            'priority' => 1,
            'add_user_id' => $user->id,
            'edit_user_id' => $user->id,
            'active' => 1,

        ];
        $subject = SubjectMaster::create($subjectData);
        if ($subject) {
            return redirect()->route('admin.subject-master.index')->with('success', 'Subject saved successfully.');
        } else {
            return redirect()->back()->with('error', 'Something went wrong, please try again.');
        }
    }


    /**
     * Display the specified resource.
     */
    public function show(SubjectMaster $subjectMaster)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(SubjectMaster $subjectMaster)
    {
        if (!empty($subjectMaster)) {
            # code...
            $classes = ClassMasterController::getClasses();
            return view('admin.subject.edit', compact('subjectMaster', 'classes'));
        } else {
            return redirect()->back()->with('error', 'Something went wrong, please try again.');
        }
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, SubjectMaster $subjectMaster)
    {
        //
        $request->validate([
            'subject' => [
                'required',
                'string',
                Rule::unique('subject_masters')->where(function ($query) use ($request) {
                    return $query->where('class_id', $request->class_id)->where('active', 1);
                })->ignore($request->id),
            ],
            'class_id' => 'required|exists:class_masters,id,active,1',
            'by_m_g' => 'required',
        ]);
        $user = Auth::user();

        $subjectData = [
            'subject' => $request->subject,
            'by_m_g' => $request->by_m_g,
            'class_id' => $request->class_id,
            'edit_user_id' => $user->id,

        ];
        $subject = $subjectMaster->update($subjectData);

        if ($subject) {
            return redirect()->route('admin.subject-master.index')->with('success', 'Subject updated successfully.');
        } else {
            return redirect()->back()->with('error', 'Something went wrong, please try again.');
        }
    }

    /**
     * Get the subjects according to the classes
     */

    public static function getAllSubjects($fields = [], $whereAttb = '',  $where = [], $orderBy = [], $isGet = false, $limit = 10, $paginate = false, $whereIn = [])
    {
        $query = SubjectMaster::query()->where('active', 1);

        if (!empty($fields) && is_array($fields)) {

            $query->select($fields);
        } else {
            $query->select('*');
        }
        if (!empty($whereAttb) && is_string($whereAttb)) {
            $query->whereNull($whereAttb);
        }
        if (!empty($where) && is_array($where) && !empty($whereAttb) && is_string($whereAttb)) {

            foreach ($where as $key => $value) {
                $query->where($key, $value)->whereNull($whereAttb);
            }
        } else {
            if (!empty($where) && is_array($where)) {
                foreach ($where as $key => $value) {
                    $query->where($key, $value);
                }
            }
        }
        if (!empty($whereIn) && is_array($whereIn)) {
            foreach ($whereIn as $key => $value) {
                $query->whereIn($key, $value);
            }
        }

        if (!empty($orderBy) && is_array($orderBy)) {
            foreach ($orderBy as $key => $value) {
                $query->orderBy($key, $value);
            }
        } else {
            $query->orderBy('subject', 'asc');
        }
        if ($isGet) {
            return $query->get();
        }
        if ($paginate) {
            $limit = (is_numeric($limit) && $limit > 0) ? $limit : 10;
            return $query->with('class')->paginate($limit);
        } else {
            return $query->pluck('subject', 'id')->toArray();
        }
    }
    /**
     * Get Subjects according to the class for ajax request.
     */
    public function getSubjectsAjax(Request $request)
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
            //code...

            if (filled($request->class_id)) {
                $whereAttb = isset($request->subject) ? 'subject_id' : '';
                $where = isset($request->class_id) ? ['class_id' => $request->class_id] : [];
                $data = self::getAllSubjects([], $whereAttb,  $where);

                if (!empty($data)) {
                    return response()->json([
                        'status' => 'success',
                        'message' => "All Subjects",
                        'data' => $data,
                    ], 200);
                } else {
                    return response()->json([
                        'status' => 'success',
                        'message' => "No Subjects are found",
                        'data' => [],
                    ], 200);
                }
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => "Failed to get subjects " . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    // public function softDelete($id)
    // {
    //     try {
    //         $subjectData = ['active' => 0];

    //         $subject = SubjectMaster::find($id);
    //         if ($subject) {
    //             $subject->update($subjectData);
    //             return response()->json(['status' => 'success', 'message' => 'Subject Master deleted successfully.']);
    //         } else {
    //             return response()->json(['status' => 'error', 'message' => 'Failed to delete Subject Master.'], 404);
    //         }
    //     } catch (\Exception $e) {
    //         return response()->json(['status' => 'error', 'message' => 'An error occurred while deleting.'], 500);
    //     }
    // }



}
