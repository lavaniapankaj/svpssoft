@extends('layouts.app')
@section('content')
    <div class="d-flex">
        <div class="py-4 col-md-2 navbar-light bg-white shadow-sm" style="height: 900px !important; background-color:#fe7c3e !important; color:white ;">
            <ul class="navbar-nav me-auto px-3">
                <li class="nav-item">
                    <a class="nav-link fs-5 fw-medium" href="{{ route('student.changePass') }}">
                        <i class="mdi mdi-lock-reset"></i>
                        <span class="menu-title">Change Password</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link fs-5 fw-medium" href="{{ route('student.student-master.index') }}">
                        <i class="mdi mdi-message-bulleted menu-icon"></i>
                        <span class="menu-title">Student Master</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link fs-5 fw-medium" href="{{ route('student.attendance.index') }}">
                        <i class="mdi mdi-presentation"></i>
                        <span class="menu-title">Student Attendance</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link fs-5 fw-medium" href="{{ route('student.attendance.report') }}">
                        <i class="mdi mdi-notebook"></i>
                        <span class="menu-title">Attendance Report</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link fs-5 fw-medium" href="{{ route('student.st-report.index') }}">
                        <i class="mdi mdi-book"></i>
                        <span class="menu-title">Student Report</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link fs-5 fw-medium" href="{{ route('student.updateMobile.index') }}">
                        <i class="mdi mdi-phone"></i>
                        <span class="menu-title">Update Mobile No.</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link fs-5 fw-medium" href="{{ route('student.student-report-relative-wise') }}">
                        <i class="mdi mdi-book"></i>
                        <span class="menu-title">Relative Report</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link fs-5 fw-medium" href="{{ route('student.cumulative-attendance.index') }}">
                        <i class="mdi mdi-notebook"></i>
                        <span class="menu-title">Cumulative Attendance</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link fs-5 fw-medium" href="{{ route('student.blank.index') }}">
                        <i class="mdi mdi-notebook"></i>
                        <span class="menu-title">Blank Form</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link fs-5 fw-medium" href="{{ route('student.current-session.index') }}">
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
    @if(Session::has('std_current_session'))
        {{ Session::get('std_current_session')->session }}
        <input type="hidden" name="student_current_session" id="student_current_session" value="{{ Session::get('std_current_session')->id }}">
    @else
        No current session found.
    @endif
</li>
@endsection
@section('scripts')
<script src="{{ asset('public/student/assets/js/custom.js') }}" type="text/javascript"></script>
    @yield('std-scripts')
@endsection


