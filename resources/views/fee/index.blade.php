@extends('layouts.app')
@section('content')
    <div class="d-flex">
        <div class="py-4 col-md-2 navbar-light bg-white shadow-sm"
            style="height: 900px !important; background-color:#fe7c3e !important; color:white ;">
            <ul class="navbar-nav me-auto px-3">
                <li class="nav-item">
                    <a class="nav-link fs-5 fw-medium" href="{{ route('fee.changePass') }}">
                        <i class="mdi mdi-lock-reset"></i>
                        <span class="menu-title">Change Password</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link fs-5 fw-medium" href="{{ route('fee.fee-entry.index') }}">
                        <i class="mdi mdi-book"></i>
                        <span class="menu-title">Fee Entry</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link fs-5 fw-medium" href="{{ route('fee.fee-detail') }}">
                        <i class="mdi mdi-book"></i>
                        <span class="menu-title">Fee Detail</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link fs-5 fw-medium" href="{{ route('fee.fee-detail-relaive-wise') }}">
                        <i class="mdi mdi-book"></i>
                        <span class="menu-title">Relative Wise Fee Report</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link fs-5 fw-medium" href="{{ route('fee.back-session-fee-detail') }}">
                        <i class="mdi mdi-book"></i>
                        <span class="menu-title">Fee Details Back Sessions</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link fs-5 fw-medium" href="{{ route('fee.print-due-receipt') }}">
                        <i class="mdi mdi-printer"></i>
                        <span class="menu-title">Print Due Receipt</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link fs-5 fw-medium" href="{{ route('fee.due-fee-report') }}">
                        <i class="mdi mdi-receipt"></i>
                        <span class="menu-title">Due Fee Report</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link fs-5 fw-medium" href="{{ route('fee.due-fee-report-sms') }}">
                        <i class="mdi mdi-message"></i>
                        <span class="menu-title">Due Fee SMS</span>
                    </a>
                </li>
                
                <li class="nav-item">
                    <a class="nav-link fs-5 fw-medium" href="{{ route('fee.current-session.index') }}">
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
        @if (Session::has('fee_current_session'))
            {{ Session::get('fee_current_session')->session }}
            <input type="hidden" name="fee_current_session" id="fee_current_session" value="{{ Session::get('fee_current_session')->id }}">
        @else
            No current session found.
        @endif
    </li>
@endsection
@section('scripts')
    <script src="{{ asset('public/fee/assets/js/custom.js') }}" type="text/javascript"></script>
    @yield('fee-scripts')
@endsection
