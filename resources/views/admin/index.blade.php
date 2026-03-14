@extends('layouts.app')
@section('title')
    Vivekanand - Admin
@endsection
@section('styles')
    <link rel="stylesheet" href="{{ asset('public/admin/assets/css/custom.css') }}" type="text/css" />
    <style>
        .nav_link.active {
            background-color: rgba(255, 255, 255, 0.15);
            color: #fff;
            border-radius: 4px;
        }
        .submenu_item.active-parent {
            background-color: rgba(255, 255, 255, 0.10);
            border-radius: 4px;
        }
        .submenu_item.active-parent .arrow-left {
            transform: rotate(90deg);
        }
        .submenu_item.active-parent + .submenu {
            display: block !important;
        }
        .nav_link.sublink.active {
            background-color: rgba(255, 255, 255, 0.20);
            font-weight: bold;
        }
    </style>
@endsection
@section('content')

  @php
      function isActive(...$routeNames) {
          foreach ($routeNames as $route) {
              if (request()->routeIs($route)) return 'active';
          }
          return '';
      }
  @endphp

  <nav class="sidebar">
          <div class="menu_content">

              {{-- Masters --}}
              <ul class="menu_items">
                <div class="menu_title menu_master"></div>

                <li class="item">
                  <a href="{{ route('admin.session-master.index') }}" class="nav_link {{ isActive('admin.session-master.*') }}">
                    <span class="navlink_icon"><span class="mdi mdi-database-outline"></span></span>
                    <span class="navlink">Session Master</span>
                  </a>
                </li>

                <li class="item">
                  <a href="{{ route('admin.class-master.index') }}" class="nav_link {{ isActive('admin.class-master.*') }}">
                    <span class="navlink_icon"><span class="mdi mdi-google-classroom"></span></span>
                    <span class="navlink">Class Master</span>
                  </a>
                </li>

                <li class="item">
                  <a href="{{ route('admin.section-master.index') }}" class="nav_link {{ isActive('admin.section-master.*') }}">
                    <span class="navlink_icon"><span class="mdi mdi-hexagon-slice-2"></span></span>
                    <span class="navlink">Section Master</span>
                  </a>
                </li>

                <li class="item">
                  <a href="{{ route('admin.subject-master.index') }}" class="nav_link {{ isActive('admin.subject-master.*') }}">
                    <span class="navlink_icon"><span class="mdi mdi-book-open-page-variant-outline"></span></span>
                    <span class="navlink">Subject Master</span>
                  </a>
                </li>

                <li class="item">
                  <a href="{{ route('admin.subject-group-master.index') }}" class="nav_link {{ isActive('admin.subject-group-master.*') }}">
                    <span class="navlink_icon"><span class="mdi mdi-page-layout-sidebar-right"></span></span>
                    <span class="navlink">Subject Group Master</span>
                  </a>
                </li>

                <li class="item">
                  <a href="{{ route('admin.state-master.index') }}" class="nav_link {{ isActive('admin.state-master.*') }}">
                    <span class="navlink_icon"><span class="mdi mdi-account-card-outline"></span></span>
                    <span class="navlink">State Master</span>
                  </a>
                </li>

                <li class="item">
                  <a href="{{ route('admin.district-master.index') }}" class="nav_link {{ isActive('admin.district-master.*') }}">
                    <span class="navlink_icon"><span class="mdi mdi-account-cowboy-hat-outline"></span></span>
                    <span class="navlink">District Master</span>
                  </a>
                </li>

                <li class="item">
                  <a href="{{ route('admin.academic-fee-master.index') }}" class="nav_link {{ isActive('admin.academic-fee-master.*') }}">
                    <span class="navlink_icon"><span class="mdi mdi-monitor-account"></span></span>
                    <span class="navlink">Academic Fee Master</span>
                  </a>
                </li>

                <li class="item">
                  <a href="{{ route('admin.transport-fee-master.index') }}" class="nav_link {{ isActive('admin.transport-fee-master.*') }}">
                    <span class="navlink_icon"><span class="mdi mdi-car-back"></span></span>
                    <span class="navlink">Transport Fee Master</span>
                  </a>
                </li>

                <li class="item">
                  <a href="{{ route('admin.exam-master.index') }}" class="nav_link {{ isActive('admin.exam-master.*') }}">
                    <span class="navlink_icon"><span class="mdi mdi-briefcase-check"></span></span>
                    <span class="navlink">Exam Master</span>
                  </a>
                </li>

                <li class="item">
                  <a href="{{ route('admin.marks-master.index') }}" class="nav_link {{ isActive('admin.marks-master.*') }}">
                    <span class="navlink_icon"><span class="mdi mdi-checkbox-multiple-marked-circle-outline"></span></span>
                    <span class="navlink">Marks Master</span>
                  </a>
                </li>
              </ul>

              {{-- Students --}}
              <ul class="menu_items">
                <div class="menu_title menu_student"></div>

                <li class="item">
                  <a href="{{ route('admin.left-out-std.index') }}" class="nav_link {{ isActive('admin.left-out-std.*') }}">
                    <span class="navlink_icon"><span class="mdi mdi-exit-run"></span></span>
                    <span class="navlink">Left Out Student</span>
                  </a>
                </li>

                <li class="item">
                  <a href="{{ route('admin.promote-std.index') }}" class="nav_link {{ isActive('admin.promote-std.*') }}">
                    <span class="navlink_icon"><span class="mdi mdi-bullhorn-variant-outline"></span></span>
                    <span class="navlink">Promote Student</span>
                  </a>
                </li>

                <li class="item">
                  <a href="{{ route('admin.student-master.search') }}" class="nav_link {{ isActive('admin.student-master.*') }}">
                    <span class="navlink_icon"><span class="mdi mdi-account-search"></span></span>
                    <span class="navlink">Search Student</span>
                  </a>
                </li>
              </ul>

              {{-- Miscellaneous --}}
              <ul class="menu_items">
                <div class="menu_title menu_miscellaneous"></div>

                <li class="item">
                  <a href="{{ route('admin.attendance_schedule.index') }}" class="nav_link {{ isActive('admin.attendance_schedule.*') }}">
                    <span class="navlink_icon"><span class="mdi mdi-calendar-month-outline"></span></span>
                    <span class="navlink">Attendance Schedule</span>
                  </a>
                </li>

                {{-- Edit Section Submenu --}}
                @php
                    $editSectionRoutes = [
                        'admin.editSection.std',
                        'admin.editSection.editStdFee',
                        'admin.editSection.editStdMarks',
                        'admin.editSection.editStdRollSection',
                        'admin.editSection.editStdAdmissionPromotion',
                        'admin.editSection.editStdAttendance',
                        'admin.editSection.editResult',
                        'admin.editSection.editRemoveRelativeStd',
                        'admin.editSection.editRemoveStdFee',
                        'admin.editSection.editStdAdmissionDate',
                        'admin.editSection.editStdByPreSrno',
                        'admin.editSection.mercyFeeBoth',
                        'admin.editSection.editStdInfoClass',
                    ];
                    $isEditSectionActive = isActive(...$editSectionRoutes);
                @endphp
                <li class="item">
                  <div class="nav_link submenu_item {{ $isEditSectionActive ? 'active-parent' : '' }}">
                    <span class="navlink_icon"><span class="mdi mdi-application-edit-outline"></span></span>
                    <span class="navlink">Edit Section</span>
                    <span class="mdi mdi-chevron-right arrow-left"></span>
                  </div>
                  <ul class="menu_items submenu" style="{{ $isEditSectionActive ? 'display:block;' : '' }}">
                    <a href="{{ route('admin.editSection.std') }}" class="nav_link sublink {{ isActive('admin.editSection.std') }}">
                      <span class="navlink_icon"><span class="mdi mdi-menu-right"></span></span>Edit Student</a>
                    <a href="{{ route('admin.editSection.editStdFee') }}" class="nav_link sublink {{ isActive('admin.editSection.editStdFee') }}">
                      <span class="navlink_icon"><span class="mdi mdi-menu-right"></span></span>Edit Fee Details</a>
                    <a href="{{ route('admin.editSection.editStdMarks') }}" class="nav_link sublink {{ isActive('admin.editSection.editStdMarks') }}">
                      <span class="navlink_icon"><span class="mdi mdi-menu-right"></span></span>Edit Marks</a>
                    <a href="{{ route('admin.editSection.editStdRollSection') }}" class="nav_link sublink {{ isActive('admin.editSection.editStdRollSection') }}">
                      <span class="navlink_icon"><span class="mdi mdi-menu-right"></span></span>Set New Section & Roll No.</a>
                    <a href="{{ route('admin.editSection.editStdAdmissionPromotion') }}" class="nav_link sublink {{ isActive('admin.editSection.editStdAdmissionPromotion') }}">
                      <span class="navlink_icon"><span class="mdi mdi-menu-right"></span></span>Edit Admission/Promotion Date</a>
                    <a href="{{ route('admin.editSection.editStdAttendance') }}" class="nav_link sublink {{ isActive('admin.editSection.editStdAttendance') }}">
                      <span class="navlink_icon"><span class="mdi mdi-menu-right"></span></span>Edit Attendance</a>
                    <a href="{{ route('admin.editSection.editResult') }}" class="nav_link sublink {{ isActive('admin.editSection.editResult') }}">
                      <span class="navlink_icon"><span class="mdi mdi-menu-right"></span></span>Edit Result Date</a>
                    <a href="{{ route('admin.editSection.editRemoveRelativeStd') }}" class="nav_link sublink {{ isActive('admin.editSection.editRemoveRelativeStd') }}">
                      <span class="navlink_icon"><span class="mdi mdi-menu-right"></span></span>Edit/Remove Relative</a>
                    <a href="{{ route('admin.editSection.editRemoveStdFee') }}" class="nav_link sublink {{ isActive('admin.editSection.editRemoveStdFee') }}">
                      <span class="navlink_icon"><span class="mdi mdi-menu-right"></span></span>Edit / Remove Fee Entry</a>
                    <a href="{{ route('admin.editSection.editStdAdmissionDate') }}" class="nav_link sublink {{ isActive('admin.editSection.editStdAdmissionDate') }}">
                      <span class="navlink_icon"><span class="mdi mdi-menu-right"></span></span>Add / Delete Admission Date</a>
                    <a href="{{ route('admin.editSection.editStdByPreSrno') }}" class="nav_link sublink {{ isActive('admin.editSection.editStdByPreSrno') }}">
                      <span class="navlink_icon"><span class="mdi mdi-menu-right"></span></span>Edit Previous Records</a>
                    <a href="{{ route('admin.editSection.mercyFeeBoth') }}" class="nav_link sublink {{ isActive('admin.editSection.mercyFeeBoth') }}">
                      <span class="navlink_icon"><span class="mdi mdi-menu-right"></span></span>Mercy Fee (Both)</a>
                    <a href="{{ route('admin.editSection.editStdInfoClass') }}" class="nav_link sublink {{ isActive('admin.editSection.editStdInfoClass') }}">
                      <span class="navlink_icon"><span class="mdi mdi-menu-right"></span></span>Edit Student Information (Class Wise)</a>
                  </ul>
                </li>

                {{-- Reports Section Submenu --}}
                @php
                    $reportRoutes = [
                        'admin.reports.newAdmissionReport',
                        'admin.reports.stdregisterView.index',
                        'admin.reports.srRegisterView.view',
                        'admin.reports.reportAgeWiseView.index',
                        'admin.reports.tcIssueView',
                        'admin.reports.reprintFeeSlipView',
                        'admin.reports.transportWiseReportView.index',
                        'admin.reports.rteStudentReport.view',
                        'admin.reports.missFieldsReportView.view',
                        'admin.reports.feeReportAdminView.view',
                        'admin.reports.feeReportMercyAdminView.view',
                        'admin.dayWiseCollectionIndex',
                    ];
                    $isReportActive = isActive(...$reportRoutes);
                @endphp
                <li class="item">
                  <div class="nav_link submenu_item {{ $isReportActive ? 'active-parent' : '' }}">
                    <span class="navlink_icon"><span class="mdi mdi-chart-pie"></span></span>
                    <span class="navlink">Reports Section</span>
                    <span class="mdi mdi-chevron-right arrow-left"></span>
                  </div>
                  <ul class="menu_items submenu" style="{{ $isReportActive ? 'display:block;' : '' }}">
                    <a href="{{ route('admin.reports.newAdmissionReport') }}" class="nav_link sublink {{ isActive('admin.reports.newAdmissionReport') }}">
                      <span class="navlink_icon"><span class="mdi mdi-menu-right"></span></span>New Admission Report</a>
                    <a href="{{ route('admin.reports.stdregisterView.index') }}" class="nav_link sublink {{ isActive('admin.reports.stdregisterView.*') }}">
                      <span class="navlink_icon"><span class="mdi mdi-menu-right"></span></span>SR Register</a>
                    <a href="{{ route('admin.reports.srRegisterView.view') }}" class="nav_link sublink {{ isActive('admin.reports.srRegisterView.*') }}">
                      <span class="navlink_icon"><span class="mdi mdi-menu-right"></span></span>SR Register (Full)</a>
                    <a href="{{ route('admin.reports.reportAgeWiseView.index') }}" class="nav_link sublink {{ isActive('admin.reports.reportAgeWiseView.*') }}">
                      <span class="navlink_icon"><span class="mdi mdi-menu-right"></span></span>Report (Age Wise)</a>
                    <a href="{{ route('admin.reports.tcIssueView') }}" class="nav_link sublink {{ isActive('admin.reports.tcIssueView') }}">
                      <span class="navlink_icon"><span class="mdi mdi-menu-right"></span></span>Issue TC (New or Reprint)</a>
                    <a href="{{ route('admin.reports.reprintFeeSlipView') }}" class="nav_link sublink {{ isActive('admin.reports.reprintFeeSlipView') }}">
                      <span class="navlink_icon"><span class="mdi mdi-menu-right"></span></span>Reprint Fee Slip (Both)</a>
                    <a href="{{ route('admin.reports.transportWiseReportView.index') }}" class="nav_link sublink {{ isActive('admin.reports.transportWiseReportView.*') }}">
                      <span class="navlink_icon"><span class="mdi mdi-menu-right"></span></span>Student Report (Transport)</a>
                    <a href="{{ route('admin.reports.rteStudentReport.view') }}" class="nav_link sublink {{ isActive('admin.reports.rteStudentReport.*') }}">
                      <span class="navlink_icon"><span class="mdi mdi-menu-right"></span></span>RTE Student Report</a>
                    <a href="{{ route('admin.reports.missFieldsReportView.view') }}" class="nav_link sublink {{ isActive('admin.reports.missFieldsReportView.*') }}">
                      <span class="navlink_icon"><span class="mdi mdi-menu-right"></span></span>Miss Field Records</a>
                    <a href="{{ route('admin.reports.feeReportAdminView.view') }}" class="nav_link sublink {{ isActive('admin.reports.feeReportAdminView.*') }}">
                      <span class="navlink_icon"><span class="mdi mdi-menu-right"></span></span>Fee Report Admin</a>
                    <a href="{{ route('admin.reports.feeReportMercyAdminView.view') }}" class="nav_link sublink {{ isActive('admin.reports.feeReportMercyAdminView.*') }}">
                      <span class="navlink_icon"><span class="mdi mdi-menu-right"></span></span>Fee Report Admin (Mercy Fee)</a>
                    <a href="{{ route('admin.dayWiseCollectionIndex') }}" class="nav_link sublink {{ isActive('admin.dayWiseCollectionIndex') }}">
                      <span class="navlink_icon"><span class="mdi mdi-menu-right"></span></span>Daywise Fee Collections</a>
                    <a href="#" class="nav_link sublink">
                      <span class="navlink_icon"><span class="mdi mdi-menu-right"></span></span>Stock Cash Report</a>
                  </ul>
                </li>

                <li class="item">
                  <a href="{{ route('admin.signature') }}" class="nav_link {{ isActive('admin.signature') }}">
                    <span class="navlink_icon"><span class="mdi mdi-draw"></span></span>
                    <span class="navlink">Principal Signature</span>
                  </a>
                </li>

                <li class="item">
                  <a href="{{ route('admin.login.logs.index') }}" class="nav_link {{ isActive('admin.login.logs.*') }}">
                    <span class="navlink_icon"><span class="mdi mdi-format-list-group"></span></span>
                    <span class="navlink">Login Logs</span>
                  </a>
                </li>

                <li class="item">
                  <a href="{{ route('admin.current-session.index') }}" class="nav_link {{ isActive('admin.current-session.*') }}">
                    <span class="navlink_icon"><span class="mdi mdi-cog-outline"></span></span>
                    <span class="navlink">Settings</span>
                  </a>
                </li>

                <li class="item">
                  <a href="{{ route('admin.changePass') }}" class="nav_link {{ isActive('admin.changePass') }}">
                    <span class="navlink_icon"><span class="mdi mdi-key"></span></span>
                    <span class="navlink">Change Password</span>
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
<div class="sessions">
    @if(Session::has('current_session'))
        {{ Session::get('current_session')->session }}
        <input type="hidden" name="admin_current_session" id="admin_current_session" value="{{ Session::get('current_session')->id }}">
    @else
        No current session found.
    @endif
</div>
@endsection

@section('scripts')
  <script src="{{ asset('public/admin/assets/js/custom.js') }}" type="text/javascript"></script>
  <script>
      document.addEventListener("DOMContentLoaded", function () {

          // Auto-open submenu if active-parent class is set (done server-side)
          // Handle click toggle for submenus
          document.querySelectorAll('.submenu_item').forEach(function (item) {
              item.addEventListener('click', function () {
                  const submenu = this.nextElementSibling;
                  const arrow   = this.querySelector('.arrow-left');
                  const isOpen  = submenu && submenu.style.display === 'block';

                  // Close all other submenus first
                  document.querySelectorAll('.submenu').forEach(function (sm) {
                      sm.style.display = 'none';
                  });
                  document.querySelectorAll('.arrow-left').forEach(function (ar) {
                      ar.style.transform = '';
                  });
                  document.querySelectorAll('.submenu_item').forEach(function (si) {
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

  @yield('admin-scripts')
  @stack('swal-scripts')
@endsection