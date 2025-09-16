<?php

namespace App\Http\Controllers\Fee;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class FeeSearchStudentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $current_session = Session::get('fee_current_session');
        $search = $request->search;
        $baseQuery = DB::table('stu_main_srno')
            ->select(
                'stu_main_srno.id',
                'stu_main_srno.srno',
                'stu_main_srno.school',
                'stu_main_srno.class',
                'stu_main_srno.section',
                'stu_detail.name as student_name',
                'class_masters.class as class_name',
                'section_masters.section as section_name',
                'parents_detail.f_name',
                'parents_detail.m_name'
            )
            ->leftJoin('stu_detail', 'stu_main_srno.srno', '=', 'stu_detail.srno')
            ->leftJoin('parents_detail', 'stu_main_srno.srno', '=', 'parents_detail.srno')
            ->leftJoin('class_masters', 'stu_main_srno.class', '=', 'class_masters.id')
            ->leftJoin('section_masters', 'stu_main_srno.section', '=', 'section_masters.id')
            ->where('stu_main_srno.session_id', $current_session->id)
            ->whereIn('stu_main_srno.ssid', [1, 2]);
        if (!empty($search)) {
            $baseQuery->where(function ($q) use ($search) {
                $q->where('stu_main_srno.srno', 'LIKE', "%{$search}%")
                    ->orWhere('stu_detail.name', 'LIKE', "%{$search}%");
            });
        }
        $data = $baseQuery->orderBy('stu_main_srno.created_at', 'DESC')->paginate(10);
        return view('fee.search_student.index', compact('data'));
    }



}
