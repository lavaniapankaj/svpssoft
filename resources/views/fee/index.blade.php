@extends('layouts.app')
@section('title')
    Vivekanand - Fee Admin
@endsection
@section('content')
<nav class="sidebar">
    <div class="menu_content">
        <ul class="menu_items">
            <div class="menu_title menu_dahsboard"></div>
            <li class="item">
            <a href="{{ route('fee.changePass') }}" class="nav_link">
              <span class="navlink_icon">
                <span class="mdi mdi-key"></span>
              </span>
              <span class="navlink">Change Password</span>
            </a>
            </li>
            <li class="item">

            <div href="#" class="nav_link  submenu_item">
              <span class="navlink_icon">
                <span class="mdi mdi-file-plus-outline"></span>
              </span>
              <span class="navlink">Fee Entry</span>
              <span class="mdi mdi-chevron-right arrow-left"></span>
            </div>

            <ul class="menu_items submenu">
              <a href="{{ route('fee.fee-entry.academic') }}" class="nav_link sublink"><span class="navlink_icon">
                 <span class="mdi mdi-menu-right"></span>
                </span>Academic Fee Entry</a>
                <a href="{{ route('fee.fee-entry.transport') }}" class="nav_link sublink"><span class="navlink_icon">
                 <span class="mdi mdi-menu-right"></span>
                </span>Transport Fee Entry</a>
            </ul>
            </li>
            <li class="item">
                <a href="{{ route('fee.fee-detail') }}" class="nav_link">
                <span class="navlink_icon">
                    <span class="mdi mdi-account-details"></span>
                </span>
                <span class="navlink">Fee Detail</span>
                </a>
            </li>
            <li class="item">
                <a href="{{ route('fee.fee-detail-relaive-wise') }}" class="nav_link">
                <span class="navlink_icon">
                <span class="mdi mdi-chart-pie"></span>
                </span>
                <span class="navlink">Relative Wise Fee Report</span>
                </a>
            </li>
            <li class="item">
                <a href="{{ route('fee.back-session-fee-detail') }}" class="nav_link">
                <span class="navlink_icon">
                    <span class="mdi mdi-chart-bell-curve-cumulative"></span>
                </span>
                <span class="navlink">Fee Details Back Sessions</span>
                </a>
            </li>
            <li class="item">
                <a href="{{ route('fee.print-due-receipt') }}" class="nav_link">
                <span class="navlink_icon">
                    <span class="mdi mdi-printer-outline"></span>
                </span>
                <span class="navlink">Print Due Receipt</span>
                </a>
            </li>
            <li class="item">
                <a href="{{ route('fee.due-fee-report') }}" class="nav_link">
                <span class="navlink_icon">
                <span class="mdi mdi-file-chart"></span>
                </span>
                <span class="navlink">Due Fee Report</span>
                </a>
            </li>
            <li class="item">
                <a href="{{ route('fee.due-fee-report-sms') }}" class="nav_link">
                <span class="navlink_icon">
                <span class="mdi mdi-email-outline"></span>
                </span>
                <span class="navlink">Due Fee SMS</span>
                </a>
            </li>
            <li class="item">
                <a href="{{ route('fee.student.index') }}" class="nav_link">
                <span class="navlink_icon">
                 <span class="mdi mdi-account-details"></span>
                </span>
                <span class="navlink">Search Student</span>
                </a>
            </li>
            <li class="item">
                <a href="{{ route('fee.current-session.index') }}" class="nav_link">
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
    @stack('fee-swal-scripts')
@endsection
