@extends('layouts.app')
@section('content')
    <div class="d-flex">
        <div class="py-4 col-md-2 navbar-light bg-white shadow-sm"
            style="height: 900px !important; background-color:#fe7c3e !important; color:white ;">
            <ul class="navbar-nav me-auto px-3">
                <li class="nav-item">
                    <a class="nav-link fs-5 fw-medium" href="{{ route('marks.changePass') }}">
                        <i class="mdi mdi-lock-reset"></i>
                        <span class="menu-title">Change Password</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link fs-5 fw-medium" href="{{ route('marks.marks-entry.index') }}">
                        <i class="mdi mdi-book"></i>
                        <span class="menu-title">Marks Entry</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link fs-5 fw-medium" href="{{ route('marks.marks-report') }}">
                        <i class="mdi mdi-book"></i>
                        <span class="menu-title">Marks Report</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link fs-5 fw-medium" href="{{ route('marks.marksheet') }}">
                        <i class="mdi mdi-book"></i>
                        <span class="menu-title">Marksheet</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link fs-5 fw-medium" href="{{ route('marks.rank-class-wise') }}">
                        <i class="mdi mdi-medal"></i>
                        <span class="menu-title">Rank Class Wise</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link fs-5 fw-medium" href="{{ route('marks.current-session.index') }}">
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
