<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\ExamMaster;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class ExamMasterController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $fields = ['id', 'exam', 'order', 'show_y_n', 'created_at'];
        $orderBy = ['order' => 'asc'];
        if (!empty($fields) && count($fields) > 0 && !empty($orderBy) && count($orderBy) > 0) {
            $data = self::getAllExam($fields, [], $orderBy, 10, true);
            return view('admin.exam.index', compact('data'));
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
        return view('admin.exam.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
        $request->validate([
            'exam' => 'required|unique:exam_masters,exam,NULL,id,active,1',
            'show_y_n' => 'required',
            'order' => 'required',
        ]);

        $examData = [
            'exam' => $request->exam,
            'order' => $request->order,
            'show_y_n' => $request->show_y_n,
            'add_user_id' => Session::get('login_user'),
            'edit_user_id' => Session::get('login_user'),
            'active' => 1,

        ];
        if (!empty($examData)) {
            $exam = ExamMaster::create($examData);
            if ($exam) {
                return redirect()->route('admin.exam-master.index')->with('success', 'Exam saved successfully.');
            } else {
                return redirect()->back()->with('error', 'Something went wrong, please try again.');
            }
        } else {
            return redirect()->back()->with('error', 'Something went wrong, please try again.');
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(ExamMaster $examMaster)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ExamMaster $examMaster)
    {
        //
        if (!empty($examMaster)) {
            # code...
            return view('admin.exam.edit', compact('examMaster'));
        } else {
            return redirect()->back()->with('error', 'Something went wrong, please try again.');
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ExamMaster $examMaster)
    {
        //
        $request->validate([
            'exam' => 'required|unique:exam_masters,exam,' . $request->id . ',id,active,1',
            'show_y_n' => 'required',
            'order' => 'required',
        ]);

        $examData = [
            'exam' => $request->exam,
            'order' => $request->order,
            'show_y_n' => $request->show_y_n,
            'edit_user_id' => Session::get('login_user'),

        ];

        if (!empty($examData)) {
            $exam = $examMaster->update($examData);

            if ($exam) {
                return redirect()->route('admin.exam-master.index')->with('success', 'Exam updated successfully.');
            } else {
                return redirect()->back()->with('error', 'Something went wrong, please try again.');
            }
        } else {
            return redirect()->back()->with('error', 'Something went wrong, please try again.');
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ExamMaster $examMaster)
    {
        //
    }

    //get exams

    public static function getAllExam($fields = [], $where = [], $orderBy = [], $limit = 10, $paginate = false, $whereIn = [])
    {
        $query = ExamMaster::query()->where('active', 1);

        if (!empty($fields) && is_array($fields)) {

            $query->select($fields);
        } else {
            $query->select('*');
        }
        if (!empty($where) && is_array($where)) {
            foreach ($where as $key => $value) {
                $query->where($key, $value);
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
            $query->orderBy('order', 'asc');
        }
        if ($paginate) {
            $limit = (is_numeric($limit) && $limit > 0) ? $limit : 10;
            return $query->paginate($limit);
        } else {
            return $query->pluck('exam', 'id')->toArray();
        }
    }
    /**
     * get exams for ajax request
     */
    public function getExamAjax()
    {
        try {
            $exams = self::getAllExam();
            if (!empty($exams)) {
                return response()->json([
                    'status' => 'success',
                    'message' => "All Exams",
                    'data' => $exams,
                ], 200);
            } else {
                return response()->json([
                    'status' => 'success',
                    'message' => "No Exam found",
                    'data' => [],
                ], 200);
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => "Failed to get exams " . $e->getMessage()
            ], 500);
        }
    }

    // public function softDelete($id)
    // {
    //     try {
    //         $examData = ['active' => 0];

    //         $exam = ExamMaster::find($id);
    //         if ($exam) {
    //             $exam->update($examData);
    //             return response()->json(['status' => 'success', 'message' => 'Exam deleted successfully.']);
    //         } else {
    //             return response()->json(['status' => 'error', 'message' => 'Failed to delete Exam.'], 404);
    //         }
    //     } catch (\Exception $e) {
    //         return response()->json(['status' => 'error', 'message' => 'An error occurred while deleting.'], 500);
    //     }
    // }
}
