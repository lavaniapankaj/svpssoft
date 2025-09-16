@extends('layouts.app')
@section('title')
    Vivekanand - Student Admin
@endsection
@section('content')
        <nav class="sidebar">
            <div class="menu_content">
                <ul class="menu_items">
                  <div class="menu_title menu_dahsboard"></div>
                    <li class="item">
                        <a href="{{ route('student.changePass') }}" class="nav_link">
                          <span class="navlink_icon">
                            <span class="mdi mdi-key"></span>
                          </span>
                          <span class="navlink">Change Password</span>
                        </a>
                    </li>
                    <li class="item">
                        <a href="{{ route('student.student-master.index') }}" class="nav_link">
                          <span class="navlink_icon">
                            <span class="mdi mdi-account-school-outline"></span>
                          </span>
                          <span class="navlink">Student Master</span>
                        </a>
                    </li>
                    <li class="item">
                        <a href="{{ route('student.attendance.index') }}" class="nav_link">
                          <span class="navlink_icon">
                            <span class="mdi mdi-list-status"></span>
                          </span>
                          <span class="navlink">Student Attendance</span>
                        </a>
                    </li>
                    <li class="item">
                        <a href="{{ route('student.attendance.report') }}" class="nav_link">
                          <span class="navlink_icon">
                            <span class="mdi mdi-clipboard-list-outline"></span>

                          </span>
                          <span class="navlink">Attendance Report</span>
                        </a>
                    </li>
                    <li class="item">
                        <a href="{{ route('student.st-report.index') }}" class="nav_link">
                          <span class="navlink_icon">
                            <span class="mdi mdi-badge-account-alert-outline"></span>
                          </span>
                          <span class="navlink">Student Report</span>
                        </a>
                    </li>
                    <li class="item">
                        <a href="{{ route('student.updateMobile.index') }}" class="nav_link">
                          <span class="navlink_icon">
                            <span class="mdi mdi-cellphone"></span>
                          </span>
                          <span class="navlink">Update Mobile No.</span>
                        </a>
                    </li>
                    <li class="item">
                        <a href="{{ route('student.student-report-relative-wise') }}" class="nav_link">
                          <span class="navlink_icon">
                                <span class="mdi mdi-file-chart-outline"></span>
                          </span>
                          <span class="navlink">Relative Report</span>
                        </a>
                    </li>
                    <li class="item">
                        <a href="{{ route('student.cumulative-attendance.index') }}" class="nav_link">
                          <span class="navlink_icon">
                            <span class="mdi mdi-list-status"></span>
                          </span>
                          <span class="navlink">Cumulative Attendance</span>
                        </a>
                    </li>
                    <li class="item">
                        <a href="{{ route('student.blank.index') }}" class="nav_link">
                          <span class="navlink_icon">
                            <span class="mdi mdi-book-open-page-variant-outline"></span>
                          </span>
                          <span class="navlink">Blank Form</span>
                        </a>
                    </li>
                    <li class="item">
                        <a href="{{ route('student.current-session.index') }}" class="nav_link">
                          <span class="navlink_icon">
                            <span class="mdi mdi-cog-outline"></span>
                          </span>
                          <span class="navlink">Setting</span>
                        </a>
                    </li>
                </ul>
            </div>
        </nav>

        <div class="main_body">

            @yield('sub-content')
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


