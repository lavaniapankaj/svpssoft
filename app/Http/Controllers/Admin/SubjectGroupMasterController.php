<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\SubjectMaster;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Session;
use App\Http\Controllers\Admin\ClassMasterController;

class SubjectGroupMasterController extends Controller
{

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        //
        $query = SubjectMaster::select('id','by_m_g', 'priority', 'subject', 'subject_id', 'class_id','active', 'created_at');
        if ($query) {
            # code...
            $classes =  ClassMasterController::getClasses();
            if ($request->class_id && $request->subject_id) {
                $query->where('class_id', $request->class_id)->where('subject_id', $request->subject_id)->whereNotNull('subject_id')->where('active', 1);
            }
            $data = $query->whereNotNull('subject_id')->where('active', 1)->orderBy('created_at', 'DESC')->paginate(10);
            if ($data !== null) {
                # code...
                return view('admin.subject_group.index', ['data' => $data, 'classes' => $classes]);
            } else {
                return redirect()->back()->with('error', 'Something went wrong, please try again.');
            }
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
        $classes = ClassMasterController::getClasses();
        return view('admin.subject_group.create', compact('classes'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
        $request->validate([
            'subject' => [
                'required',
                'string',
                Rule::unique('subject_masters')->where(function ($query) use ($request) {
                    return $query->where('class_id', $request->class_id)->where('subject_id', $request->subject_id)->where('active', 1);
                }),
            ],
            'class_id' => 'required|exists:class_masters,id,active,1',
            'subject_id' => 'required|exists:subject_masters,id,active,1',
            'by_m_g' => 'required',
            'priority' => [
                'required',
                'integer',
                'in:2,3',
                Rule::unique('subject_masters')->where(function ($query) use ($request) {
                    return $query->where('class_id', $request->class_id)->where('subject_id', $request->subject_id)->where('priority', $request->priority)->where('active', 1);
                }),
            ],
        ]);

        $subjectData = [
            'class_id' => $request->class_id,
            'subject_id' => $request->subject_id,
            'subject' => $request->subject,
            'by_m_g' => $request->by_m_g,
            'priority' => $request->priority,
            'add_user_id' => Session::get('login_user'),
            'edit_user_id' => Session::get('login_user'),
            'active' => 1,

        ];

        $subject = SubjectMaster::create($subjectData);
        if ($subject) {
            return redirect()->route('admin.subject-group-master.index')->with('success', 'Subject Group created successfully.');
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
    public function edit(string $id)
    {

        if ($id) {
            # code...
            $subjectMaster = SubjectMaster::findOrFail($id);
            if ($subjectMaster !== null) {
                $classes = ClassMasterController::getClasses();
                return view('admin.subject_group.edit', compact('subjectMaster', 'classes'));
            }
        } else {
            return redirect()->back()->with('error', 'Something went wrong, please try again.');
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $request->validate([
            'subject' => [
                'required',
                'string',
                Rule::unique('subject_masters')->where(function ($query) use ($request) {
                    return $query->where('class_id', $request->class_id)->where('subject_id', $request->subject_id)->where('active', 1);
                })->ignore($request->id),
            ],
            'class_id' => 'required|exists:class_masters,id,active,1',
            'subject_id' => 'required|exists:subject_masters,id,active,1',
            'by_m_g' => 'required',
            'priority' => [
                'required',
                'integer',
                'in:2,3',
                Rule::unique('subject_masters')->where(function ($query) use ($request) {
                    return $query->where('class_id', $request->class_id)->where('subject_id', $request->subject_id)->where('priority', $request->priority)->where('active', 1);
                })->ignore($request->id),
            ],
        ]);

        $subjectData = [
            'class_id' => $request->class_id,
            'subject_id' => $request->subject_id,
            'subject' => $request->subject,
            'by_m_g' => $request->by_m_g,
            'priority' => $request->priority,
            'edit_user_id' => Session::get('login_user'),
        ];

        $subject = SubjectMaster::find($id);

        if ($subject) {
            $subject->update($subjectData);
            return redirect()->route('admin.subject-group-master.index')->with('success', 'Subject Group updated successfully.');
        } else {
            return redirect()->back()->with('error', 'Subject Group not found.');
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    //  public function softDelete($id)
    // {
    //     try {
    //         $subjectData = ['active' => 0];

    //         $subject = SubjectMaster::find($id);
    //         if ($subject) {
    //             $subject->update($subjectData);
    //             return response()->json(['status' => 'success', 'message' => 'Subject Group deleted successfully.']);
    //         } else {
    //             return response()->json(['status' => 'error', 'message' => 'Failed to delete Subject Group .'], 404);
    //         }
    //     } catch (\Exception $e) {
    //         return response()->json(['status' => 'error', 'message' => 'An error occurred while deleting.'], 500);
    //     }
    // }
}
