@extends('layouts.app')
@section('title')
    Vivekanand - Marks Admin
@endsection
@section('styles')
    <link rel="stylesheet" href="{{ asset('public/marks/assets/css/custom.css') }}" type="text/css" />
@endsection
@section('content')

@php
    function isActiveMarks(...$routeNames) {
        foreach ($routeNames as $route) {
            if (request()->routeIs($route)) return 'active';
        }
        return '';
    }

    // Marksheet submenu routes
    $marksheetRoutes = [
        'marks.marks-report.public-exam-wise',
        'marks.marks-report.play-exam-wise',
        'marks.marks-report.pg-class-exam-wise',
        'marks.marks-report.marksheet.pg.nursary',
        'marks.marks-report.marksheet.kg',
        'marks.marks-report.marksheet.first.second',
        'marks.marks-report.marksheet.third.fifth',
        'marks.marks-report.marksheet.six.eighth',
        'marks.marks-report.marksheet.ninth',
    ];
    $isMarksheetActive = isActiveMarks(...$marksheetRoutes);
@endphp

<nav class="sidebar">
    <div class="menu_content">
        <ul class="menu_items">
            <div class="menu_title menu_dahsboard"></div>

            {{-- Change Password --}}
            <li class="item">
                <a href="{{ route('marks.changePass') }}" class="nav_link {{ isActiveMarks('marks.changePass') }}">
                    <span class="navlink_icon"><span class="mdi mdi-key"></span></span>
                    <span class="navlink">Change Password</span>
                </a>
            </li>

            {{-- Marks Entry --}}
            <li class="item">
                <a href="{{ route('marks.marks-entry.index') }}" class="nav_link {{ isActiveMarks('marks.marks-entry.*') }}">
                    <span class="navlink_icon"><i class="mdi mdi-book"></i></span>
                    <span class="navlink">Marks Entry</span>
                </a>
            </li>

            {{-- Marks Report --}}
            <li class="item">
                <a href="{{ route('marks.marks-report') }}" class="nav_link {{ isActiveMarks('marks.marks-report') }}">
                    <span class="navlink_icon"><span class="mdi mdi-chart-areaspline-variant"></span></span>
                    <span class="navlink">Marks Report</span>
                </a>
            </li>

            {{-- Marksheet Submenu --}}
            <li class="item">
                <div class="nav_link submenu_item {{ $isMarksheetActive ? 'active-parent' : '' }}">
                    <span class="navlink_icon"><span class="mdi mdi-home-variant-outline"></span></span>
                    <span class="navlink">Marksheet</span>
                    <span class="mdi mdi-chevron-right arrow-left"></span>
                </div>
                <ul class="menu_items submenu" style="{{ $isMarksheetActive ? 'display:block;' : '' }}">
                    <a href="{{ route('marks.marks-report.public-exam-wise') }}"
                       class="nav_link sublink {{ isActiveMarks('marks.marks-report.public-exam-wise') }}">
                        <span class="navlink_icon"><span class="mdi mdi-menu-right"></span></span>
                        Exam Wise Report (Public School)
                    </a>
                    <a href="{{ route('marks.marks-report.play-exam-wise') }}"
                       class="nav_link sublink {{ isActiveMarks('marks.marks-report.play-exam-wise') }}">
                        <span class="navlink_icon"><span class="mdi mdi-menu-right"></span></span>
                        Exam Wise Report (Play School)
                    </a>
                    <a href="{{ route('marks.marks-report.pg-class-exam-wise') }}"
                       class="nav_link sublink {{ isActiveMarks('marks.marks-report.pg-class-exam-wise') }}">
                        <span class="navlink_icon"><span class="mdi mdi-menu-right"></span></span>
                        Exam Wise Report (Only for PG)
                    </a>
                    <a href="{{ route('marks.marks-report.marksheet.pg.nursary') }}"
                       class="nav_link sublink {{ isActiveMarks('marks.marks-report.marksheet.pg.nursary') }}">
                        <span class="navlink_icon"><span class="mdi mdi-menu-right"></span></span>
                        Final Marksheet (Only for PG and Nursary)
                    </a>
                    <a href="{{ route('marks.marks-report.marksheet.kg') }}"
                       class="nav_link sublink {{ isActiveMarks('marks.marks-report.marksheet.kg') }}">
                        <span class="navlink_icon"><span class="mdi mdi-menu-right"></span></span>
                        Final Marksheet (Only for KG)
                    </a>
                    <a href="{{ route('marks.marks-report.marksheet.first.second') }}"
                       class="nav_link sublink {{ isActiveMarks('marks.marks-report.marksheet.first.second') }}">
                        <span class="navlink_icon"><span class="mdi mdi-menu-right"></span></span>
                        Final Marksheet (Only for First And Second)
                    </a>
                    <a href="{{ route('marks.marks-report.marksheet.third.fifth') }}"
                       class="nav_link sublink {{ isActiveMarks('marks.marks-report.marksheet.third.fifth') }}">
                        <span class="navlink_icon"><span class="mdi mdi-menu-right"></span></span>
                        Final Marksheet (Only for Third to Fifth)
                    </a>
                    <a href="{{ route('marks.marks-report.marksheet.six.eighth') }}"
                       class="nav_link sublink {{ isActiveMarks('marks.marks-report.marksheet.six.eighth') }}">
                        <span class="navlink_icon"><span class="mdi mdi-menu-right"></span></span>
                        Final Marksheet (Only for Sixth to Eighth)
                    </a>
                    <a href="{{ route('marks.marks-report.marksheet.ninth') }}"
                       class="nav_link sublink {{ isActiveMarks('marks.marks-report.marksheet.ninth') }}">
                        <span class="navlink_icon"><span class="mdi mdi-menu-right"></span></span>
                        Final Marksheet (Only for Ninth)
                    </a>
                </ul>
            </li>

            {{-- Rank Class Wise --}}
            <li class="item">
                <a href="{{ route('marks.rank-class-wise') }}" class="nav_link {{ isActiveMarks('marks.rank-class-wise') }}">
                    <span class="navlink_icon"><i class="mdi mdi-medal"></i></span>
                    <span class="navlink">Rank Class Wise</span>
                </a>
            </li>

            {{-- Setting --}}
            <li class="item">
                <a href="{{ route('marks.current-session.index') }}" class="nav_link {{ isActiveMarks('marks.current-session.*') }}">
                    <span class="navlink_icon"><span class="mdi mdi-cog-outline"></span></span>
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
    <script>
        document.addEventListener("DOMContentLoaded", function() {

            // Auto-open submenu if active-parent class is set (done server-side)
            // Handle click toggle for submenus
            document.querySelectorAll('.submenu_item').forEach(function(item) {
                item.addEventListener('click', function() {
                    const submenu = this.nextElementSibling;
                    const arrow = this.querySelector('.arrow-left');
                    const isOpen = submenu && submenu.style.display === 'block';

                    // Close all other submenus first
                    document.querySelectorAll('.submenu').forEach(function(sm) {
                        sm.style.display = 'none';
                    });
                    document.querySelectorAll('.arrow-left').forEach(function(ar) {
                        ar.style.transform = '';
                    });
                    document.querySelectorAll('.submenu_item').forEach(function(si) {
                        // Keep active-parent if it was server-set
                        if (!si.classList.contains('active-parent')) {
                            si.classList.remove('open');
                        }
                    });

                    // Toggle clicked submenu
                    if (submenu && !isOpen) {
                        submenu.style.display = 'block';
                        if (arrow) arrow.style.transform = 'rotate(90deg)';
                    }
                });
            });

            // Header height padding
            const header = document.querySelector('.header');
            if (header) {
                document.body.style.paddingTop = header.offsetHeight + 'px';
            }
        });
    </script>
    @yield('marks-scripts')
    @stack('marks-swal-scripts')
@endsection
