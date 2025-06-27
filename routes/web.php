<?php


use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\AttendanceSchedulesController;
use App\Http\Controllers\Admin\ClassMasterController;
use App\Http\Controllers\Admin\CurrentSessionController;
use App\Http\Controllers\Admin\DistrictMasterController;
use App\Http\Controllers\Admin\EditSectionsController;
use App\Http\Controllers\Admin\ExamMasterController;
use App\Http\Controllers\Admin\FeeMasterController;
use App\Http\Controllers\Admin\FeePrintController;
use App\Http\Controllers\Admin\MarksMasterController;
use App\Http\Controllers\Admin\PromoteController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\SectionMasterController;
use App\Http\Controllers\Admin\SessionMasterController;
use App\Http\Controllers\Admin\SmsPanelController;
use App\Http\Controllers\Admin\StateMasterController;
use App\Http\Controllers\Admin\SubjectGroupMasterController;
use App\Http\Controllers\Admin\SubjectMasterController;
use App\Http\Controllers\Admin\TcPrintController;
use App\Http\Controllers\Admin\TransportFeeMasterController;
use App\Http\Controllers\Admin\WebsiteMessageController;
use App\Http\Controllers\Fee\FeeController;
use App\Http\Controllers\Fee\FeeCurrentSessionController;
use App\Http\Controllers\Fee\FeeEntryController;
use App\Http\Controllers\Fee\FeeSectionFeePrintController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Inventory\InventoryController;
use App\Http\Controllers\Marks\MarksController;
use App\Http\Controllers\Marks\MarksCurrentSessionController;
use App\Http\Controllers\Marks\StdMarksController;
use App\Http\Controllers\Student\StdAttendanceController;
use App\Http\Controllers\Student\StdCurrentSessionController;
use App\Http\Controllers\Student\StudentController;
use App\Http\Controllers\Student\StudentMasterController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

// Route::get('/', function () {
//     return view('index');
// })->name('index.page');

Route::get('/', function () {
    session()->flush();
    $view = view('index');
    return response($view)
        ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
        ->header('Pragma', 'no-cache')
        ->header('Expires', '0');
})->name('index.page');

Auth::routes();

// Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

Route::get('/admin/login', [AdminController::class, 'login'])->name('admin.login');
Route::get('/student-admin/login', [StudentController::class, 'login'])->name('student.login');
Route::get('/marks-admin/login', [MarksController::class, 'login'])->name('marks.login');
Route::get('/fee-admin/login', [FeeController::class, 'login'])->name('fee.login');
Route::get('/inventory-admin/login', [InventoryController::class, 'login'])->name('inventory.login');

Route::group(['middleware' => 'auth'], function () {

    Route::get('sessions', [SessionMasterController::class, 'getSessionsAjax'])->name('sessions.get');
    Route::get('classes', [ClassMasterController::class, 'getClassesAjax'])->name('classes.get');
    Route::get('sections', [SectionMasterController::class, 'getSectionsAjax'])->name('sections.get');
    Route::get('states', [StateMasterController::class, 'getStatesAjax'])->name('states.get');
    Route::get('districts', [DistrictMasterController::class, 'getDistricts'])->name('districts.get');
    Route::get('subjects', [SubjectMasterController::class, 'getSubjectsAjax'])->name('subjects.get');
    Route::get('exams', [ExamMasterController::class, 'getExamAjax'])->name('exams.get');
    Route::get('/std-name-father', [StudentMasterController::class, 'getStdNameFather'])->name('stdNameFather.get');
});


Route::group(['prefix' => 'admin', 'as' => 'admin.', 'middleware' => ['auth', 'is_validate:admin']], function () {

    Route::controller(AdminController::class)->group(function () {

        Route::get('/', 'index')->name('dashboard');
        Route::get('change-password', 'changePass')->name('changePass');
        Route::post('change-password', 'changePassStore')->name('changePass.store');
        Route::get('principal-sign', 'signature')->name('signature');
        Route::post('principal-sign', 'uploadPrincipleSignature')->name('signature.upload');
        Route::get('left-out-std', 'leftOutStd')->name('left-out-std.index');
        Route::post('left-out-std/{id}/edit', 'leftOutStdEdit')->where('id', '.*')->name('left-out-std.edit');
        Route::get('login-logs', 'loginLogs')->name('login.logs.index');
    });

    Route::resource('session-master', SessionMasterController::class)->except(['destroy']);
    // Route::resource('current-session', CurrentSessionController::class);
    Route::controller(CurrentSessionController::class)->group(function () {

        Route::get('current-session', 'index')->name('current-session.index');
        Route::post('current-session', 'store')->name('current-session.store');
        Route::get('current-session/edit/{id}', 'edit')->name('current-session.edit');
        Route::post('current-session/{id}/soft-delete', 'softDelete')->name('current-session.softDelete');
    });


    Route::controller(EditSectionsController::class)->group(function () {
        Route::post('student-master', 'editStdStore')->name('student-master.store');
        Route::get('student-master/{id}/edit', 'editStdEdit')->name('student-master.edit');
        Route::get('student-master/add-relative', 'editStdAddRelative')->name('student-master.add.relative');
        Route::get('edit-section', 'index')->name('editSection.index');
        Route::get('edit-section/std', 'editStd')->name('editSection.std');
        Route::get('edit-section/set-section-rollno', 'editStdRollSection')->name('editSection.editStdRollSection');
        Route::post('edit-section/set-section-rollno', 'editStdRollSectionStore')->name('editSection.editStdRollSection.store');
        Route::get('edit-section/change-admission-promotion-date', 'editStdAdmissionPromotion')->name('editSection.editStdAdmissionPromotion');
        Route::post('edit-section/change-admission-promotion-date', 'editStdAdmissionPromotionStore')->name('editSection.editStdAdmissionPromotion.store');
        Route::get('edit-section/edit-result', 'editResult')->name('editSection.editResult');
        Route::post('edit-section/edit-result', 'editResultStore')->name('editSection.editResultStore');
        Route::get('edit-section/edit-relative-std', 'editRemoveRelativeStd')->name('editSection.editRemoveRelativeStd');
        Route::post('edit-section/edit-relative-std', 'editRemoveRelativeStdStore')->name('editSection.editRemoveRelativeStd.store');
        Route::post('edit-section/edit-relative-std/{id}/remove', 'editRemoveRelativeStdRemove')->where('id', '.*')->name('editSection.editRemoveRelativeStd.remove');
        Route::get('edit-section/edit-std-info', 'editStdInfoClass')->name('editSection.editStdInfoClass');
        Route::post('edit-section/edit-std-info', 'editStdInfoClassStore')->name('editSection.editStdInfoClass.store');
        Route::get('edit-section/edit-std-admission-date', 'editStdAdmissionDate')->name('editSection.editStdAdmissionDate');
        Route::post('edit-section/edit-std-admission-date', 'editStdAdmissionDateStore')->name('editSection.editStdAdmissionDate.store');
        Route::get('edit-section/edit-std-previous-record', 'editStdByPreSrno')->name('editSection.editStdByPreSrno');
        Route::post('edit-section/edit-std-previous-record', 'editStdByPreSrnoStore')->name('editSection.editStdByPreSrno.store');
        Route::get('edit-section/edit-std-marks', 'editStdMarks')->name('editSection.editStdMarks');
        Route::post('edit-section/edit-std-marks', 'editStdMarksStore')->name('editSection.editStdMarks.store');
        Route::get('edit-section/edit-std-attendance', 'editStdAttendance')->name('editSection.editStdAttendance');
        Route::get('edit-section/std-fee-details', 'stdFeeDetailFetch')->where('srno', '.*')->name('editSection.stdFeeDetailFetch');
        Route::get('edit-section/std-fee-edit-remove', 'editRemoveStdFee')->name('editSection.editRemoveStdFee');
        Route::post('edit-section/std-fee-edit-remove/edit', 'stdFeeEdit')->name('editSection.stdFeeEdit');
        Route::delete('edit-section/std-fee-edit-remove/{id}', 'stdFeeRemove')->name('editSection.stdFeeRemove');
        Route::get('edit-section/std-mercy-fee', 'mercyFeeBoth')->name('editSection.mercyFeeBoth');
        Route::post('edit-section/std-mercy-fee', 'mercyFeeBothStore')->name('editSection.mercyFeeBothStore');

        // Route::get('edit-section/std-fee-edit','editStdFeeDetailsView')->name('editSection.editStdFeeDetailsView');
        // Route::get('edit-section/std-fee-edit/info','editStdFeeGetInfo')->name('editSection.editStdFeeGetInfo');
        Route::get('edit-section/edit-std-fee','editStdFee')->name('editSection.editStdFee');
        Route::get('edit-section/get-std-fee-details','getStdFeeInfo1')->name('editSection.getStdFeeInfo1');
        Route::post('edit-section/std-fee-details/store','editStdFeeStore')->name('editSection.editStdFeeStore');
    });

    Route::controller(ReportController::class)->group(function () {
        Route::get('reports', 'index')->name('reports');
        Route::get('reports/new-admission-report', 'newAdmissionReport')->name('reports.newAdmissionReport');
        Route::get('reports/new-admission-report/index', 'newAdmissionReportByDateView')->name('reports.newAdmissionReport.index');
        Route::get('reports/new-admission-report/by-date', 'newAdmissionReportByDate')->name('reports.newAdmissionReportByDate');
        Route::get('reports/new-admission-report/by-date/index', 'newAdmissionReportByCategoryView')->name('reports.newAdmissionReportByDate.index');
        Route::get('reports/new-admission-report/by-category', 'newAdmissionReportByCategory')->name('reports.newAdmissionReportByCategory');
        Route::get('reports/new-admission-report/by-religion/index', 'newAdmissionReportByReligionView')->name('reports.newAdmissionReportByReligion.index');
        Route::get('reports/new-admission-report/by-religion', 'newAdmissionReportByReligion')->name('reports.newAdmissionReportByReligion');

        Route::get('reports/new-admission-report/by-age-proof', 'newAdmissionReportByAgeProof')->name('reports.newAdmissionReportByAgeProof');
        Route::get('reports/new-admission-report/by-age-proof/index', 'newAdmissionReportByAgeProofView')->name('reports.newAdmissionReportByAgeProof.index');

        Route::get('reports/new-admission-report/between-dates', 'newAdmissionReportByBetweenDates')->name('reports.newAdmissionReportByBetweenDates');
        Route::get('reports/new-admission-report/between-dates/index', 'newAdmissionReportBetwwenDatesView')->name('reports.newAdmissionReportByBetweenDates.index');

        Route::get('reports/age-wise-report', 'reportAgeWise')->name('reports.reportAgeWise');
        Route::get('reports/age-wise-report/details', 'reportAgeWiseWithDetails')->name('reports.reportAgeWiseWithDetails');
        Route::get('reports/age-wise-report/index', 'reportAgeWiseView')->name('reports.reportAgeWiseView.index');

        Route::get('reports/transport-wise-report', 'reportTransportWise')->name('reports.reportTransportWise');
        Route::get('reports/transport-wise-report/index', 'transportWiseReportView')->name('reports.transportWiseReportView.index');

        Route::get('reports/sr-register-report/index', 'stdregisterView')->name('reports.stdregisterView.index');
        Route::get('reports/sr-register-report', 'reportSrRegisterWise')->name('reports.reportSrRegisterWise');

        Route::get('reports/RTE-std-report', 'rteStudentReportView')->name('reports.rteStudentReport.view');
        Route::get('reports/RTE-std-report/get', 'rteStudentReport')->name('reports.rteStudentReport');

        Route::get('reports/sr-register-full-report', 'srRegisterView')->name('reports.srRegisterView.view');
        Route::get('reports/sr-register-full-report/get', 'srRegisterFullDetails')->name('reports.srRegisterFullDetails');

        Route::get('reports/fee-report-admin', 'feeReportAdminView')->name('reports.feeReportAdminView.view');
        Route::get('reports/fee-report-admin/get', 'feeReportAdmin')->name('reports.feeReportAdmin');

        Route::get('reports/fee-report-mercy-admin', 'feeReportMercyAdminView')->name('reports.feeReportMercyAdminView.view');
        Route::get('reports/fee-report-mercy-admin/get', 'feeReportMercyAdmin')->name('reports.feeReportMercyAdmin');

        Route::get('reports/miss-field-report', 'missFieldsReportView')->name('reports.missFieldsReportView.view');
        Route::get('reports/miss-field-report/get', 'missFieldsReport')->name('reports.missFieldsReport');

        Route::get('reports/reprint-fee-slip', 'reprintFeeSlipView')->name('reports.reprintFeeSlipView');
        Route::get('reports/reprint-fee-slip/details', 'reprintFeeSlip')->name('reports.reprintFeeSlip');

        Route::get('reports/tc-issue-report/index', 'tcIssueView')->name('reports.tcIssueView');
        Route::get('reports/tc-student-report', 'tcStudentDetails')->name('reports.tcStudentDetails');
        Route::get('reports/tc-student-current-report', 'tcStCurrentDetails')->name('reports.tcStCurrentDetails');
        Route::get('reports/tc-student-previous-report', 'tcStPreviousDetails')->name('reports.tcStPreviousDetails');
        Route::get('reports/tc-student-status-message', 'tcStudentStatusMessages')->name('reports.tcStudentStatusMessages');
        Route::post('reports/tc-issue-report/button1', 'tcToTheStudent')->name('reports.tcToTheStudent');
        Route::post('reports/tc-issue-report/button2', 'tcToTheStudentBtn2')->name('reports.tcToTheStudentBtn2');
        Route::post('reports/tc-issue-report/button3', 'tcToTheStudentBtn3')->name('reports.tcToTheStudentBtn3');
        Route::get('reports/tc-issue-report/button4', 'tcToTheStudentBtn4')->name('reports.tcToTheStudentBtn4');

        Route::get('export-report', 'exportReport')->name('reports.exportReport');
        Route::get('export-report/by-category', 'exportReportByCategory')->name('reports.exportReportByCategory');
        Route::get('export-report/by-religion', 'exportReportByReligion')->name('reports.exportReportByReligion');
        Route::get('export-report/by-age-proof', 'exportReportByAgeProof')->name('reports.exportReportByAgeProof');
        Route::get('export-report/between-dates', 'exportReportByBetweenDates')->name('reports.exportReportByBetweenDates');
        Route::get('export-report/age-wise', 'exportReportByAge')->name('reports.exportReportByAge');
        Route::get('export-report/age-wise-with-details', 'exportReportByAgeWithDetails')->name('reports.exportReportByAgeWithDetails');
        Route::get('export-report/transport-wise', 'exportReportByTransportWise')->name('reports.exportReportByTransportWise');
        Route::get('export-report/miss-fields', 'exportReportByMissFields')->name('reports.exportReportByMissFields');
        Route::get('export-report/RTE-st-report', 'rteStudentReportExcel')->name('reports.rteStudentReport.excel');
        Route::get('export-report/sr-register-full-report', 'srRegisterFullDetailsExcel')->name('reports.srRegisterFullReport.excel');
        Route::get('export-report/fee-report-admin', 'feeReportAdminExcel')->name('reports.adminFullFee.excel');
        Route::get('export-report/fee-report-mercy-admin', 'feeReportMercyAdminExcel')->name('reports.adminMercyFee.excel');
    });

    // Route::get('full-detail-student/{prevSrno?}/{srno?}', function(?string $prevSrno=null, ?string $srno=null){
    //     dd(['prevSrno' => $prevSrno, 'srno' => $srno]);
    //     return view('admin.reports.individual_std_report',['prevSrno' => $prevSrno, 'srno' => $srno]);
    // })->name('individual.stdreports');

    Route::get('full-detail-student/{prevSrno?}/{srno?}', function(Request $request, ?string $prevSrno = null, ?string $srno = null) {
        // First check URL parameters, if not found then check query parameters
        $prevSrno = $prevSrno ?? $request->query('prevSrno');
        $srno = $srno ?? $request->query('srno');

        return view('admin.reports.individual_std_report', [
            'prevSrno' => $prevSrno,
            'srno' => $srno
        ]);
    })->name('individual.stdreports');

    Route::resource('academic-fee-master', FeeMasterController::class)->except(['destroy']);
    Route::resource('transport-fee-master', TransportFeeMasterController::class)->except(['destroy']);
    Route::resource('exam-master', ExamMasterController::class)->except(['destroy']);
    Route::resource('marks-master', MarksMasterController::class)->except(['destroy']);
    Route::resource('promote-std', PromoteController::class)->except(['destroy']);
    Route::resource('website-message', WebsiteMessageController::class)->except(['destroy']);
    Route::resource('section-master', SectionMasterController::class)->except(['destroy']);
    Route::resource('class-master', ClassMasterController::class)->except(['destroy']);
    Route::resource('subject-master', SubjectMasterController::class)->except(['destroy']);
    Route::resource('subject-group-master', SubjectGroupMasterController::class)->except(['destroy']);
    Route::resource('state-master', StateMasterController::class)->except(['destroy']);
    Route::resource('district-master', DistrictMasterController::class)->except(['destroy']);
    /*Route::resources([
        'academic-fee-master' => FeeMasterController::class,
        'transport-fee-master' => TransportFeeMasterController::class,
        'exam-master' => ExamMasterController::class,
        'marks-master' => MarksMasterController::class,
        'sms-panel' => SmsPanelController::class,
        'promote-std' => PromoteController::class,
        'website-message' => WebsiteMessageController::class,
        'section-master' => SectionMasterController::class,
        'class-master' => ClassMasterController::class,
        'subject-master' => SubjectMasterController::class,
        'subject-group-master' => SubjectGroupMasterController::class,
        'state-master' => StateMasterController::class,
        'district-master' => DistrictMasterController::class,
    ]);*/

    Route::controller(TcPrintController::class)->group(function () {
        Route::get('print-tc', 'fillDetails')->name('fillDetails');
    });
    Route::controller(SmsPanelController::class)->group(function () {
        Route::get('sms-panel', 'index')->name('sms-panel.index');
        Route::get('group-sms-panel', 'groupIndex')->name('group-sms-panel.index');
        Route::get('add-sms-group', 'addGroupIndex')->name('add-sms-group.index');
        Route::post('add-sms-group', 'addGroupStore')->name('add-sms-group.store');
        Route::get('edit-sms-group/{id}/edit', 'addGroupEdit')->name('add-sms-group.edit');
        Route::put('edit-sms-group/{id}', 'addGroupUpdate')->name('add-sms-group.update');
        Route::post('add-sms-group/{id}/soft-delete', 'addGroupSoftDelete')->name('add-sms-group.softDelete');

        Route::get('add-edit-sms-group-mobile', 'addGroupMobileIndex')->name('add-edit-sms-group-mobile.index');
        Route::get('add-sms-group-mobile', 'addGroupMobileCreate')->name('add-edit-sms-group-mobile.create');
        Route::post('add-edit-sms-group-mobile', 'addGroupMobileStore')->name('add-edit-sms-group-mobile.store');
        Route::get('edit-sms-group-mobile/{id}/edit', 'addGroupMobileEdit')->name('add-edit-sms-group-mobile.edit');
        Route::put('edit-sms-group-mobile/{id}', 'addGroupMobileUpdate')->name('add-edit-sms-group-mobile.update');
        Route::post('add-edit-sms-group-mobile/{id}/soft-delete', 'addGroupMobileSoftDelete')->name('add-edit-sms-group-mobile.softDelete');
        //Send Group Sms
        Route::get('send-group-sms', 'sendGroupSmsIndex')->name('send-group-sms.index');
    });

    Route::controller(FeePrintController::class)->group(function () {
        Route::get('/print-fee-slip-no', 'fillDetails')->name('print-fee-slip.index');
    });

    Route::controller(StudentMasterController::class)->group(function () {
        Route::get('search/student/fee-details', 'searchStFeeDetails')->name('student-master.search-fee-details');
        Route::get('search/student', 'search')->name('student-master.search');
        Route::post('search/student', 'searchStore')->name('student-master.search.store');
        Route::get('std/relative',  'getStdWithRelativeStd')->where('srno', '.*')->name('getStdWithRelativeStd');
        Route::get('std/srno/admission/promotion',  'getStdWithSrno')->name('getStdWithSrno');
        Route::get('std/withoout-ssid/srno/',  'getStdWithNamesWithoutSSID')->name('getStdWithNamesWithoutSSID');
    });
    Route::controller(AttendanceSchedulesController::class)->group(function () {
        Route::get('attendance-schedule', 'index')->name('attendance_schedule.index');
        Route::get('attendance-schedule/create', 'create')->name('attendance_schedule.create');
        Route::post('attendance-schedule', 'store')->name('attendance_schedule.store');
        Route::post('attendance-schedule/update-specific-date', 'updateSpecifiDate')->name('attendance_schedule.updateSpecifiDate');
        Route::post('attendance-schedule/generate', 'generate')->name('attendance_schedule.generate');
        Route::get('attendance-schedule/edit/{id}', 'edit')->name('attendance_schedule.edit');
        Route::get('attendance-schedule/edit-date', 'editDateView')->name('attendance_schedule.editDateView');
        Route::get('attendance-schedule/edit-specific-date', 'editSpecificDate')->name('attendance_schedule.editSpecificDate');
        Route::post('attendance-schedule/update', 'store')->name('attendance_schedule.update');
    });

    Route::post('session-master/{id}/soft-delete', [SessionMasterController::class, 'softDelete'])->name('session-master.softDelete');
    Route::post('section-masterr/{id}/soft-delete', [SectionMasterController::class, 'softDelete'])->name('section-master.softDelete');
    Route::post('class-master/{id}/soft-delete', [ClassMasterController::class, 'softDelete'])->name('class-master.softDelete');
    Route::post('subject-master/{id}/soft-delete', [SubjectMasterController::class, 'softDelete'])->name('subject-master.softDelete');
    Route::post('state-master/{id}/soft-delete', [StateMasterController::class, 'softDelete'])->name('state-master.softDelete');
    Route::post('district-master/{id}/soft-delete', [DistrictMasterController::class, 'softDelete'])->name('district-master.softDelete');
});
// Route::group(['prefix' => 'student', 'as' => 'student.', 'middleware' => ['auth', 'is_st_admin']], function () {
Route::group(['prefix' => 'student', 'as' => 'student.', 'middleware' => ['auth', 'is_validate:student']], function () {

    Route::controller(StudentController::class)->group(function () {
        Route::get('/student-admin', 'index')->name('dashboard');
        Route::get('change-password', 'changePass')->name('changePass');
        Route::post('change-password', 'changePassStore')->name('changePass.store');
        Route::get('student-report', 'stReport')->name('st-report.index');
        Route::get('update-mobile', 'updateMobile')->name('updateMobile.index');
        Route::post('update-mobile', 'updateMobileStore')->name('updateMobile.store');

    });

    Route::controller(StdCurrentSessionController::class)->group(function () {
        Route::get('current-session', 'index')->name('current-session.index');
        Route::post('current-session', 'store')->name('current-session.store');
        Route::get('current-session/edit/{id}', 'edit')->name('current-session.edit');
    });
    Route::controller(StdAttendanceController::class)->group(function () {

        Route::get('attendance', 'index')->name('attendance.index');
        Route::post('attendance', 'store')->name('attendance.store');
        Route::get('attendance/edit/{id}', 'edit')->name('attendance.edit');
        Route::get('attendance-report', 'report')->name('attendance.report');
        Route::get('attendance-report-get', 'getReport')->name('attendance.report.get');
        Route::get('download-csv', 'downloadCsv')->name('download.attendance.csv');
        Route::get('cumulative-attendance', 'cumulativeAttendReport')->name('cumulative-attendance.index');
        Route::get('cumulative-attendance/report', 'cumulativeReportData')->name('cumulative-attendance.report');
        Route::get('cumulative-attendance/report/excel', 'cumulativeAttendExcel')->name('cumulative-attendance.csv');
    });
    Route::resource('student-master', StudentMasterController::class)->except(['destroy']);

    Route::get('student-report-class-wise', [StudentMasterController::class, 'stdReportClassWiseExcel'])->name('student-report-class-wise-excel');

    Route::get('student-report-relative-wise', [StudentMasterController::class, 'stdRelativeWiseView'])->name('student-report-relative-wise');
    Route::get('std/relative',  [StudentMasterController::class, 'getStdsWithRelativeStd'])->where('srno', '.*')->name('getStdWithRelativeStd');
    // Route::get('std/relative',  [StudentMasterController::class, 'getStdWithRelativeStd'])->where('srno', '.*')->name('getStdWithRelativeStd');

    Route::get('blank-form', function () {
        return view('student.blank_form.index');
    })->name('blank.index');
    Route::get('blank-form/play-form', function () {
        return view('student.blank_form.playSchoolForm');
    })->name('blank.play');
    Route::get('blank-form/public-form', function () {
        return view('student.blank_form.publicSchoolForm');
    })->name('blank.public');
});
Route::group(['prefix' => 'marks', 'as' => 'marks.', 'middleware' => ['auth', 'is_validate:marks']], function () {
    // Route::group(['prefix' => 'marks', 'as' => 'marks.', 'middleware' => ['auth', 'is_marks_admin']], function () {
    Route::controller(MarksController::class)->group(function () {
        Route::get('/marks-admin', 'index')->name('dashboard');
        Route::get('change-password', 'changePass')->name('changePass');
        Route::post('change-password', 'changePassStore')->name('changePass.store');
    });

    Route::controller(MarksCurrentSessionController::class)->group(function () {
        Route::get('current-session', 'index')->name('current-session.index');
        Route::post('current-session', 'store')->name('current-session.store');
        Route::get('current-session/edit/{id}', 'edit')->name('current-session.edit');
    });

    Route::controller(StdMarksController::class)->group(function () {
        Route::get('marks-entry', 'marksEntry')->name('marks-entry.index');
        Route::post('marks-entry', 'marksEntryStore')->name('marks-entry.store');

        Route::get('marks-report', 'marksReport')->name('marks-report');
        Route::get('std-marks-report', 'getMarksReport')->name('marks-report.get');
        Route::get('std-marks-report-excel', 'marksReportExcel')->name('marks-report.excel');

        Route::get('marksheet',  'marksheet')->name('marksheet');
        Route::get('marksheet-final-pg-nursary', 'finalMarksheetOnlyForClassPGAndNursary')->name('marks-report.marksheet.pg.nursary');
        Route::post('marksheet-final-pg-nursary', 'finalMarksheetPGNURStore')->name('marks-report.marksheet.pg.nursary.store');
        Route::get('marksheet-final-pg-nursary/report', 'finalMarksheetClassPGAndNursaryReport')->name('marks-report.marksheet.pg.nursary.get');
        Route::get('marksheet-final-pg-nursary/print', 'finalMarksheetPGNURPrint')->name('marks-report.marksheet.pg.nursary.print');
        Route::get('marksheet-final-kg/report', 'finalMarksheetClassKGReport')->name('marks-report.marksheet.kg.get');
        Route::get('marksheet-final-kg', 'finalMarksheetOnlyForClassKG')->name('marks-report.marksheet.kg');
        Route::post('marksheet-final-kg', 'finalMarksheetOnlyForClassKGStore')->name('marks-report.marksheet.kg.store');
        Route::get('marksheet-final-kg/print', 'finalMarksheetKGPrint')->name('marks-report.marksheet.kg.print');
        Route::get('marksheet-final-first-second', 'finalMarksheetOnlyForClassFirstSecond')->name('marks-report.marksheet.first.second');
        Route::post('marksheet-final-first-second', 'finalMarksheetFirstSecondStore')->name('marks-report.marksheet.first.second.store');
        Route::get('marksheet-final-first-second/report', 'finalMarksheetClassFirstSecond')->name('marks-report.marksheet.first.second.get');
        Route::get('marksheet-final-first-second/print', 'finalMarksheetFirstSecondPrint')->name('marks-report.marksheet.first.second.print');
        Route::get('marksheet-select/exam', 'selectExamWithOrWithout')->name('marks-report.select.exam');
        Route::post('marksheet-select/exam', 'selectExamWithOrWithoutStore')->name('marks-report.select.exam.store');
        Route::get('marksheet-final-third-fifth', 'finalMarksheetThirdToFifth')->name('marks-report.marksheet.third.fifth');
        Route::post('marksheet-final-third-fifth', 'finalMarksheetThirdToFifthStore')->name('marks-report.marksheet.third.fifth.store');
        Route::get('marksheet-final-third-fifth/print', 'finalMarksheetThirdToFifthPrint')->name('marks-report.marksheet.third.fifth.print');
        Route::get('marksheet-final-third-fifth/report', 'finalMarksheetThirdtoFiveReport')->name('marks-report.marksheet.third.fifth.get');

        Route::get('marksheet-final-six-eighth-select/exam', 'selectExamWithOrWithoutSixEighth')->name('marks-report.select.exam.six.eighth');
        Route::post('marksheet-final-six-eighth-select/exam', 'selectExamWithOrWithoutSixEighthStore')->name('marks-report.select.exam.six.eighth.store');
        Route::get('marksheet-final-six-eighth', 'finalMarksheetSixToEighth')->name('marks-report.marksheet.six.eighth');
        Route::post('marksheet-final-six-eighth', 'finalMarksheetSixToEighthStore')->name('marks-report.marksheet.six.eighth.store');
        Route::get('marksheet-final-six-eighth/print', 'finalMarksheetSixToEighthPrint')->name('marks-report.marksheet.six.eighth.print');
        Route::get('marksheet-final-six-eighth/report', 'finalMarksheetSixtoEighthReport')->name('marks-report.marksheet.six.eighth.get');

        Route::get('exam-wise/public-school', 'publicSchoolExamWise')->name('marks-report.public-exam-wise');
        Route::post('exam-wise/public-school', 'publicSchoolExamWisePrintStore')->name('marks-report.public-exam-wise.store');
        Route::get('exam-wise/public-school/print', 'publicSchoolExamWisePrint')->name('marks-report.public-exam-wise.print');
        Route::get('exam-wise/play-school', 'playSchoolExamWise')->name('marks-report.play-exam-wise');
        Route::post('exam-wise/play-school', 'playSchoolExamWisePrintStore')->name('marks-report.play-exam-wise.store');
        Route::get('exam-wise/play-school/print', 'playSchoolExamWisePrint')->name('marks-report.play-exam-wise.print');
        Route::get('marksheet-report', 'getMarkSheetReport')->name('marksheet-report');

        Route::get('rank-report', 'rankReport')->name('rank-class-wise');

        Route::get('class-wise-rank-report', 'classWiseRankReport')->name('class-wise-rank-report');
        Route::get('class-wise-rank-report/excel', 'classWiseRankReportExcel')->name('class-wise-rank-report-excel');
    });
});
Route::group(['prefix' => 'fee', 'as' => 'fee.', 'middleware' => ['auth', 'is_validate:fee']], function () {
    // Route::group(['prefix' => 'fee', 'as' => 'fee.', 'middleware' => ['auth', 'is_fee_admin']], function () {
    Route::controller(FeeController::class)->group(function () {
        Route::get('/fee-admin', 'index')->name('dashboard');
        Route::get('change-password', 'changePass')->name('changePass');
        Route::post('change-password', 'changePassStore')->name('changePass.store');
    });
    Route::controller(FeeCurrentSessionController::class)->group(function () {
        Route::get('current-session', 'index')->name('current-session.index');
        Route::post('current-session', 'store')->name('current-session.store');
        Route::get('current-session/edit/{id}', 'edit')->name('current-session.edit');
    });

    Route::controller(FeeEntryController::class)->group(function () {

        Route::get('fee-entry', 'index')->name('fee-entry.index');
        Route::get('academic-fee-entry', 'academicFee')->name('fee-entry.academic');
        Route::post('academic-fee-entry', 'academicFeeStore')->name('fee-entry.academic.store');
        Route::get('transport-fee-entry', 'transportFee')->name('fee-entry.transport');
        Route::get('back-session-fee-entry/{session_id}/{srno}/{class}/{section}', 'academicBackSessionFeeEntry')->where('srno', '.*')->name('fee-entry.academicBackFee');
        Route::get('back-session-transport-fee-entry/{session_id}/{srno}/{class}/{section}', 'transBackSessionFeeEntry')->where('srno', '.*')->name('fee-entry.academicBackTransFee');
        Route::get('fee-entry-due', 'academicFeeDueAmount')->where('srno', '.*')->name('fee-entry.academicFeeDueAmount');
        Route::get('fee-details', 'feeDetail')->name('fee-detail');
        Route::get('relative-wise-fee-details', 'relativewiseFeeDetails')->name('fee-detail-relaive-wise');
        Route::get('relative-wise-fee-details/excel', 'exportRelativeWiseFeeReport')->name('fee-detail-relaive-wise-excel');

        Route::get('individual-fee-details/{st}/{session}/{class}/{section}', 'individualFeeDetail')->where('st', '.*')->name('individual-fee-detail');

        Route::get('back-session-fee-details', 'backSessionFeeDetails')->name('back-session-fee-detail');
        Route::get('back-session-fee-details/excel', 'exportBackSessionFeeReport')->name('back-session-fee-detail-excel');

        Route::get('student-without-ssid', 'studentWithoutSsid')->name('studentWithoutSsid');
        Route::get('back-session/individual-fee-details/{st}/{session}/{class}/{section}', 'backSessionIndividualFeeDetail')->where('st', '.*')->name('back-session-individual-fee-detail');
        Route::get('print-due-receipt', 'printDueReceipt')->name('print-due-receipt');

        Route::get('due-fee-report', 'dueFeeReport')->name('due-fee-report');
        Route::get('due-fee-report/excel', 'exportDueFeeReport')->name('due-fee-report-excel');

        Route::get('due-fee-report-sms', 'dueFeeReportSMS')->name('due-fee-report-sms');
        Route::post('due-fee-report-send-sms', 'sendSMSSt')->name('due-fee-report-send-sms');
    });

    Route::controller(FeeSectionFeePrintController::class)->group(function () {
        // Route::get('print-tc', 'index')->name('print-tc');
        Route::get('print-fee-slip', 'fillDetails')->name('fillDetails');
        // Route::get('tc-details', 'fillDetails')->name('fillDetails');
    });

});
// Route::middleware(['auth', 'is_inventory_admin'])->group(function () {
Route::middleware(['auth', 'is_validate:inventory'])->group(function () {
    Route::get('/inventory-admin', [InventoryController::class, 'index'])->name('inventory.dashboard');
});
