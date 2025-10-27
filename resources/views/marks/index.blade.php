@extends('layouts.app')
@section('title')
    Vivekanand - Marks Admin
@endsection
@section('content')
<nav class="sidebar">
    <div class="menu_content">
        <ul class="menu_items">
            <div class="menu_title menu_dahsboard"></div>
            <li class="item">
                <a href="{{ route('marks.changePass') }}" class="nav_link">
                  <span class="navlink_icon">
                    <span class="mdi mdi-key"></span>
                  </span>
                  <span class="navlink">Change Password</span>
                </a>
            </li>
            <li class="item">
                <a href="{{ route('marks.marks-entry.index') }}" class="nav_link">
                  <span class="navlink_icon">
                    <i class="mdi mdi-book"></i>
                  </span>
                  <span class="navlink">Marks Entry</span>
                </a>
            </li>
            <li class="item">
                <a href="{{ route('marks.marks-report') }}" class="nav_link">
                  <span class="navlink_icon">
                    <span class="mdi mdi-chart-areaspline-variant"></span>
                  </span>
                  <span class="navlink">Marks Report</span>
                </a>
            </li>
            <li class="item">

                <div href="#" class="nav_link  submenu_item">
                  <span class="navlink_icon">
                    <span class="mdi mdi-home-variant-outline"></span>
                  </span>
                  <span class="navlink">Marksheet</span>

                  <span class="mdi mdi-chevron-right arrow-left"></span>
                </div>

            <ul class="menu_items submenu">
                <a href="{{ route('marks.marks-report.public-exam-wise') }}" class="nav_link sublink"><span class="navlink_icon">
                 <span class="mdi mdi-menu-right"></span>
                </span>Exam Wise Report (Public School)</a>
                <a href="{{ route('marks.marks-report.play-exam-wise') }}" class="nav_link sublink"><span class="navlink_icon">
                 <span class="mdi mdi-menu-right"></span>
                </span>Exam Wise Report (Play School)</a>
                <a href="{{ route('marks.marks-report.pg-class-exam-wise') }}" class="nav_link sublink"><span class="navlink_icon">
                 <span class="mdi mdi-menu-right"></span>
                </span>Exam Wise Report (Only for PG)</a>
                <a href="{{ route('marks.marks-report.marksheet.pg.nursary') }}" class="nav_link sublink"><span class="navlink_icon">
                 <span class="mdi mdi-menu-right"></span>
                </span>Final Marksheet (Only for PG and Nursary)</a>
                <a href="{{ route('marks.marks-report.marksheet.kg') }}" class="nav_link sublink"><span class="navlink_icon">
                 <span class="mdi mdi-menu-right"></span>
                </span>Final Marksheet (Only for KG)</a>
                <a href="{{ route('marks.marks-report.marksheet.first.second') }}" class="nav_link sublink"><span class="navlink_icon">
                 <span class="mdi mdi-menu-right"></span>
                </span>Final Marksheet (Only for First And Second)</a>
                <a href="{{ route('marks.marks-report.marksheet.third.fifth') }}" class="nav_link sublink"><span class="navlink_icon">
                 <span class="mdi mdi-menu-right"></span>
                </span>Final Marksheet (Only for Third to Fifth)</a>
                <a href="{{ route('marks.marks-report.marksheet.six.eighth') }}" class="nav_link sublink"><span class="navlink_icon">
                 <span class="mdi mdi-menu-right"></span>
                </span>Final Marksheet (Only for Sixth to Eighth)</a>
            </ul>
            </li>
            <li class="item">
                <a href="{{ route('marks.rank-class-wise') }}" class="nav_link">
                  <span class="navlink_icon">
                     <i class="mdi mdi-medal"></i>
                  </span>
                  <span class="navlink">Rank Class Wise</span>
                </a>
            </li>
            <li class="item">
                <a href="{{ route('marks.current-session.index') }}" class="nav_link">
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
        @if (Session::has('marks_current_session'))
            {{ Session::get('marks_current_session')->session }}
            <input type="hidden" name="marks_current_session" id="marks_current_session"
                value="{{ Session::get('marks_current_session')->id }}">
        @else
            No current session found.
        @endif
    </li>
@endsection
@section('scripts')
    <script src="{{ asset('public/marks/assets/js/custom.js') }}" type="text/javascript"></script>
    @yield('marks-scripts')
@endsection
