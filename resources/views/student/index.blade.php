@extends('layouts.app')
@section('title')
    Vivekanand - Student Admin
@endsection
@section('styles')
    <link rel="stylesheet" href="{{ asset('public/student/assets/css/custom.css') }}" type="text/css" />
@endsection
@section('content')
  @php
      function isActiveStd(...$routeNames) {
          foreach ($routeNames as $route) {
              if (request()->routeIs($route)) return 'active';
          }
          return '';
      }
  @endphp

  <nav class="sidebar">
      <div class="menu_content">
          <ul class="menu_items">
            <div class="menu_title menu_dahsboard"></div>

              {{-- Change Password --}}
              <li class="item">
                  <a href="{{ route('student.changePass') }}" class="nav_link {{ isActiveStd('student.changePass') }}">
                    <span class="navlink_icon"><span class="mdi mdi-key"></span></span>
                    <span class="navlink">Change Password</span>
                  </a>
              </li>

              {{-- Student Master --}}
              <li class="item">
                  <a href="{{ route('student.student-master.index') }}" class="nav_link {{ isActiveStd('student.student-master.*') }}">
                    <span class="navlink_icon"><span class="mdi mdi-account-school-outline"></span></span>
                    <span class="navlink">Student Master</span>
                  </a>
              </li>

              {{-- Student Attendance --}}
              <li class="item">
                  <a href="{{ route('student.attendance.index') }}" class="nav_link {{ isActiveStd('student.attendance.index') }}">
                    <span class="navlink_icon"><span class="mdi mdi-list-status"></span></span>
                    <span class="navlink">Student Attendance</span>
                  </a>
              </li>

              {{-- Attendance Report --}}
              <li class="item">
                  <a href="{{ route('student.attendance.report') }}" class="nav_link {{ isActiveStd('student.attendance.report') }}">
                    <span class="navlink_icon"><span class="mdi mdi-clipboard-list-outline"></span></span>
                    <span class="navlink">Attendance Report</span>
                  </a>
              </li>

              {{-- Student Report --}}
              <li class="item">
                  <a href="{{ route('student.st-report.index') }}" class="nav_link {{ isActiveStd('student.st-report.*') }}">
                    <span class="navlink_icon"><span class="mdi mdi-badge-account-alert-outline"></span></span>
                    <span class="navlink">Student Report</span>
                  </a>
              </li>

              {{-- Update Mobile No. --}}
              <li class="item">
                  <a href="{{ route('student.updateMobile.index') }}" class="nav_link {{ isActiveStd('student.updateMobile.*') }}">
                    <span class="navlink_icon"><span class="mdi mdi-cellphone"></span></span>
                    <span class="navlink">Update Mobile No.</span>
                  </a>
              </li>

              {{-- Relative Report --}}
              <li class="item">
                  <a href="{{ route('student.student-report-relative-wise') }}" class="nav_link {{ isActiveStd('student.student-report-relative-wise') }}">
                    <span class="navlink_icon"><span class="mdi mdi-file-chart-outline"></span></span>
                    <span class="navlink">Relative Report</span>
                  </a>
              </li>

              {{-- Cumulative Attendance --}}
              <li class="item">
                  <a href="{{ route('student.cumulative-attendance.index') }}" class="nav_link {{ isActiveStd('student.cumulative-attendance.*') }}">
                    <span class="navlink_icon"><span class="mdi mdi-list-status"></span></span>
                    <span class="navlink">Cumulative Attendance</span>
                  </a>
              </li>

              {{-- Blank Form --}}
              <li class="item">
                  <a href="{{ route('student.blank.index') }}" class="nav_link {{ isActiveStd('student.blank.*') }}">
                    <span class="navlink_icon"><span class="mdi mdi-book-open-page-variant-outline"></span></span>
                    <span class="navlink">Blank Form</span>
                  </a>
              </li>

              {{-- Setting --}}
              <li class="item">
                  <a href="{{ route('student.current-session.index') }}" class="nav_link {{ isActiveStd('student.current-session.*') }}">
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
    @yield('std-scripts')
    @stack('st-swal-scripts')
@endsection


