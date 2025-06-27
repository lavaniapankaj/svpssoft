@extends('layouts.app')
@section('styles')
    <link rel="stylesheet" href="{{ asset('public/admin/assets/css/custom.css') }}" type="text/css" />
@endsection
@section('content')
    <div class="d-flex">
        <div class="py-4 col-md-2 navbar-light bg-white shadow-sm" style="background-color:#fe7c3e !important; color:white ;">
            <ul class="navbar-nav me-auto px-3">
                <li class="nav-item">
                    <a class="nav-link fs-5 fw-medium" href="{{ route('admin.changePass') }}">
                        <i class="mdi mdi-lock-reset"></i>
                        <span class="menu-title">Change Password</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link fs-5 fw-medium" href="{{ route('admin.session-master.index') }}">
                        <i class="mdi mdi-calendar menu-icon"></i>
                        <span class="menu-title">Session Master</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link fs-5 fw-medium" href="{{ route('admin.class-master.index') }}">
                        <i class="mdi mdi-account-school"></i>
                        <span class="menu-title">Class Master</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link fs-5 fw-medium" href="{{ route('admin.section-master.index') }}">
                        <i class="mdi mdi-message-bulleted menu-icon"></i>
                        <span class="menu-title">Section Master</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link fs-5 fw-medium" href="{{ route('admin.subject-master.index') }}">
                        <i class="mdi mdi mdi-book-open-variant"></i>
                        <span class="menu-title">Subject Master</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link fs-5 fw-medium" href="{{ route('admin.subject-group-master.index') }}">
                        <i class="mdi mdi mdi-bookshelf"></i>
                        <span class="menu-title">Subject Group Master</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link fs-5 fw-medium" href="{{ route('admin.state-master.index') }}">
                        <i class="mdi mdi-message-bulleted menu-icon"></i>
                        <span class="menu-title">State Master</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link fs-5 fw-medium" href="{{ route('admin.district-master.index') }}">
                        <i class="mdi mdi-message-bulleted menu-icon"></i>
                        <span class="menu-title">District Master</span>
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link fs-5 fw-medium" href="{{ route('admin.attendance_schedule.index') }}">
                        <i class="mdi mdi-calendar-arrow-right"></i>
                        <span class="menu-title">Attendance Schedule</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link fs-5 fw-medium" href="{{ route('admin.editSection.index') }}">
                        <i class="mdi mdi-message-bulleted menu-icon"></i>
                        <span class="menu-title">Edit Section</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link fs-5 fw-medium" href="{{ route('admin.reports') }}">
                        <i class="mdi mdi-notebook-multiple"></i>
                        <span class="menu-title">Reports</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link fs-5 fw-medium" href="{{ route('admin.academic-fee-master.index') }}">
                        <i class="mdi mdi-message-bulleted menu-icon"></i>
                        <span class="menu-title">Academic Fee Master </span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link fs-5 fw-medium" href="{{ route('admin.transport-fee-master.index') }}">
                        <i class="mdi mdi-bus-school"></i>
                        <span class="menu-title">Transport Fee Master </span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link fs-5 fw-medium" href="{{ route('admin.exam-master.index') }}">
                        <i class="mdi mdi-message-bulleted menu-icon"></i>
                        <span class="menu-title">Exam Master </span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link fs-5 fw-medium" href="{{ route('admin.marks-master.index') }}">
                        <i class="mdi mdi-message-bulleted menu-icon"></i>
                        <span class="menu-title">Marks Master </span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link fs-5 fw-medium" href="{{ route('admin.sms-panel.index') }}">
                        <i class="mdi mdi-message-processing"></i>
                        <span class="menu-title">SMS Panel</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link fs-5 fw-medium" href="{{ route('admin.group-sms-panel.index') }}">
                        <i class="mdi mdi-message-processing"></i>
                        <span class="menu-title">Group SMS</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link fs-5 fw-medium" href="{{ route('admin.student-master.search') }}">
                        <i class="mdi mdi-search-web"></i>
                        <span class="menu-title">Search Student</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link fs-5 fw-medium" href="{{ route('admin.promote-std.index') }}">
                        <i class="mdi mdi-message-processing menu-icon"></i>
                        <span class="menu-title">Promote Student</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link fs-5 fw-medium" href="{{ route('admin.left-out-std.index') }}">
                        <i class="mdi mdi-message-processing menu-icon"></i>
                        <span class="menu-title">Left Out Student</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link fs-5 fw-medium" href="{{ route('admin.login.logs.index') }}">
                        <i class="mdi mdi-laptop-account"></i>
                        <span class="menu-title">Login Logs</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link fs-5 fw-medium" href="{{ route('admin.signature') }}">
                        <i class="mdi mdi-signature-freehand"></i>
                        <span class="menu-title">Principal Signature Upload</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link fs-5 fw-medium" href="{{ route('admin.current-session.index') }}">
                        <i class="mdi mdi-cog"></i>
                        <span class="menu-title">Setting</span>
                    </a>
                </li>
            </ul>
        </div>
        <main class="py-4 col-md-10">

            @yield('sub-content')
        </main>
    </div>
@endsection
@section('current-session')
<li class="mx-2 my-2 fw-bold border border-1 py-2 px-2 rounded-pill bg-black-subtle">
    @if(Session::has('current_session'))
        {{ Session::get('current_session')->session }}
        <input type="hidden" name="admin_current_session" id="admin_current_session" value="{{ Session::get('current_session')->id }}">
    @else
        No current session found.
    @endif
</li>
@endsection
@section('scripts')
<script src="{{ asset('public/admin/assets/js/custom.js') }}" type="text/javascript"></script>

    @yield('admin-scripts')
@endsection

