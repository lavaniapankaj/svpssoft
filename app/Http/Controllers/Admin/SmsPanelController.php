<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\ClassMasterController;
use App\Http\Controllers\Controller;
use App\Models\Admin\GroupSMS;
use App\Models\Admin\GroupSMSMobile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;

class SmsPanelController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
        $classes = ClassMasterController::getClasses();
        return view('admin.smspanel.index', compact('classes'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
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
        //
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

    /**
     * Group SMS Panel Index
     */
    public function groupIndex()
    {
        return view('admin.groupsms.index');
    }
    /**
     * Add Group SMS Panel Index
     */
    public function addGroupIndex()
    {
        $data = GroupSMS::where('active', 1)->get();
        return view('admin.groupsms.addGroup', compact('data'));
    }
    /**
     * Store SMS Group
     */
    public function addGroupStore(Request $request)
    {
        $request->validate([
            'group_name' => 'required|string|max:255|unique:sms_group,group_name,NULL,id,active,1',
        ]);

        $grouSms = GroupSMS::create([
            'group_name' => $request->group_name,
            'active' => 1,
            'add_user_id' => Session::get('login_user'),
            'edit_user_id' => Session::get('login_user'),
        ]);
        if ($grouSms) {
            return redirect()->route('admin.add-sms-group.index')->with('success', 'SMS Group created successfully.');
        } else {
            return redirect()->back()->with('error', 'Something went wrong, please try again.');
        }
    }
    /**
     * Edit SMS Group
     */
    public function addGroupEdit(int $id)
    {
        $data = GroupSMS::find($id);
        if ($data) {
            return view('admin.groupsms.editSmsGroup', compact('data'));
        } else {
            return redirect()->back()->with('error', 'Something went wrong, please try again.');
        }
    }
    /**
     * Update SMS Group
     */
    public function addGroupUpdate(Request $request, int $id)
    {
        $request->validate([
            'group_name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('sms_group', 'group_name')
                    ->ignore($id) // Ignore current record
                    ->where('active', 1), // Check only active records
            ],
        ]);

        $grouSms = GroupSMS::find($id);
        if ($grouSms) {
            $grouSms->group_name = $request->group_name;
            $grouSms->edit_user_id = Session::get('login_user');
            if ($grouSms->save()) {
                return redirect()->route('admin.add-sms-group.index')->with('success', 'SMS Group updated successfully.');
            } else {
                return redirect()->back()->with('error', 'Something went wrong, please try again.');
            }
        } else {
            return redirect()->back()->with('error', 'Something went wrong, please try again.');
        }
    }
    /**
     * Soft Delete SMS Group
     */
    public function addGroupSoftDelete(int $id)
    {
        try {
            $grouSms = GroupSMS::find($id);
            if ($grouSms) {
                $grouSms->update(['active' => 0]);
                return response()->json(['status' => 'success', 'message' => 'SMS Group deleted successfully.']);
            } else {
                return response()->json(['status' => 'error', 'message' => 'Failed to delete SMS Group.'], 404);
            }
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'An error occurred while deleting.'], 500);
        }
    }


    /**
     * Add/Edit Group SMS Mobile Index
     */
    public function addGroupMobileIndex(Request $request)
    {
        $data = GroupSMSMobile::query();
        $groups = GroupSMS::where('active', 1)->get();
        if ($request->has('group_id')) {
            $data = $data->where('group_id', $request->group_id)->where('active', 1);
        }
        $data = $data->where('active', 1)->paginate(10);
        return view('admin.groupsms.addEditSmsMobile.index', compact('data', 'groups'));
    }
    /**
     * Add/Edit Group SMS Mobile Create
     */
    public function addGroupMobileCreate()
    {
        $groups = GroupSMS::where('active', 1)->get();
        return view('admin.groupsms.addEditSmsMobile.addMobile', compact('groups'));
    }
    /**
     * Store SMS Group
     */
    public function addGroupMobileStore(Request $request)
    {
        $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('mobile_number', 'name')
                    ->where('active', 1)->where('group_id', $request->group_id), // Check only active records
            ],
            'mobile' => 'required|regex:/^[0-9]{10}$/',
            'group_id' => 'required|exists:sms_group,id,active,1',
        ]);

        $grouSms = GroupSMSMobile::create([
            'name' => $request->name,
            'mobile' => $request->mobile,
            'group_id' => $request->group_id,
            'active' => 1,
            'add_user_id' => Session::get('login_user'),
            'edit_user_id' => Session::get('login_user'),
        ]);
        if ($grouSms) {
            return redirect()->route('admin.add-edit-sms-group-mobile.index')->with('success', 'SMS Group created successfully.');
        } else {
            return redirect()->back()->with('error', 'Something went wrong, please try again.');
        }
    }
    /**
     * Edit SMS Group
     */
    public function addGroupMobileEdit(int $id)
    {
        $data = GroupSMSMobile::find($id);
        $groups = GroupSMS::where('active', 1)->get();
        if ($data) {
            return view('admin.groupsms.addEditSmsMobile.editMobile', compact('data', 'groups'));
        } else {
            return redirect()->back()->with('error', 'Something went wrong, please try again.');
        }
    }
    /**
     * Update SMS Group
     */
    public function addGroupMobileUpdate(Request $request, int $id)
    {
        $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('mobile_number', 'name')
                    ->ignore($id) // Ignore current record
                    ->where('active', 1)->where('group_id', $request->group_id), // Check only active records
            ],
            'mobile' => 'required|regex:/^[0-9]{10}$/',
            'group_id' => 'required|exists:sms_group,id,active,1',
        ]);

        $grouSms = GroupSMSMobile::find($id);
        if ($grouSms) {
            $grouSms->name = $request->name;
            $grouSms->mobile = $request->mobile;
            $grouSms->group_id = $request->group_id;
            $grouSms->edit_user_id = Session::get('login_user');
            if ($grouSms->save()) {
                return redirect()->route('admin.add-edit-sms-group-mobile.index')->with('success', 'SMS Group updated successfully.');
            } else {
                return redirect()->back()->with('error', 'Something went wrong, please try again.');
            }
        } else {
            return redirect()->back()->with('error', 'Something went wrong, please try again.');
        }
    }
    /**
     * Soft Delete SMS Group
     */
    public function addGroupMobileSoftDelete(int $id)
    {
        try {
            $grouSms = GroupSMSMobile::find($id);
            if ($grouSms) {
                $grouSms->update(['active' => 0]);
                return response()->json(['status' => 'success', 'message' => 'Group Meamber deleted successfully.']);
            } else {
                return response()->json(['status' => 'error', 'message' => 'Failed to delete SMS Group.'], 404);
            }
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'An error occurred while deleting.'], 500);
        }
    }

    /**
     * SMS Group Send SMS Message Index
     */
    public function sendGroupSmsIndex(Request $request)
    {
        $groups = GroupSMS::where('active', 1)->get();
        $data = [];

        if ($request->has('group_id') && $request->group_id != '') {
            $data = GroupSMSMobile::where('group_id', $request->group_id)
                ->where('active', 1)
                ->get();
        }

        return view('admin.groupsms.sendSms.index', compact('groups', 'data'));
    }

    public function sendGroupSmsStore(Request $request)
    {
        $request->validate([
            'group_id' => 'required',
            'message' => 'required|string|max:255',
        ]);

        // Logic to send SMS to the selected group members
        $groupMembers = GroupSMSMobile::where('group_id', $request->group_id)
            ->where('active', 1)
            ->get();

        foreach ($groupMembers as $member) {
            // Send SMS logic (e.g., via an SMS API)
        }

        return redirect()->back()->with('success', 'SMS sent successfully!');
    }
}
