@extends('fee.index')
@section('sub-content')
    <div class="container-fluid">

        <div class="row">
            <div class="col-md-12">
                <div class="card border-0 bg-white">
           <div class="card-header flex-wrap bg-white d-flex align-items-center justify-content-between"><h5 class="mb-0 mt-0">{{ 'Back Session Fee Details' }}</h5>
                        <a href="{{ route('fee.back-session-fee-detail') }}" class="btn bg-light btn-sm" ><span class="mdi mdi-chevron-left me-2"></span>Back</a>
                    </div>
                    <div class="card-body">
                        <form id="class-section-form">
                            <div class="row">
                                <div class="form-group col-md-6">
                                    <label for="session_id" class="mt-2">Session <span class="text-danger">*</span></label>
                                    <select name="session_id" id="session_id" class="form-control " required>
                                        <option value="">Select Session</option>
                                        @if (count($sessions) > 0)
                                            @foreach ($sessions as $key => $session)
                                                <option value="{{ $key }}"
                                                    {{ old('session_id') == $key ? 'selected' : '' }}>{{ $session }}
                                                </option>
                                            @endforeach
                                        @else
                                            <option value="">No Session Found</option>
                                        @endif
                                    </select>
                                    <span class="text-danger fw-bold" id="session-error" role="alert"></span>
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="fee_class_id" class="mt-2">Class <span class="text-danger">*</span></label>
                                    <select name="class" id="fee_class_id" class="form-control" {{ count($classes) == 0 ? 'disabled' : 'required' }}>
                                        @if (count($classes) > 0)
                                            <option value="all">All Class</option>
                                            @foreach ($classes as $key => $class)
                                                <option value="{{ $key }}" {{ request()->get('class') == $key ? 'selected' : '' }}>{{ $class }}</option>
                                            @endforeach
                                        @else
                                            <option value="" selected disabled>No Class Found</option>
                                        @endif
                                    </select>
                                    <span class="text-danger fw-bold" id="class-error" role="alert"></span>

                                </div>


                                <div class="form-group col-md-6">
                                    <label for="fee_section_id" class="mt-2">Section <span class="text-danger">*</span></label>
                                    <select name="section" id="fee_section_id" class="form-control" required></select>
                                    <span class="text-danger fw-bold" id="section-error" role="alert"></span>

                                </div>

                                <div class="form-group col-md-6">
                                    <label for="fee_student_id" class="mt-2">Student <span class="text-danger">*</span></label>
                                    <select name="std_id" id="fee_student_id" class="form-control " required></select>
                                    <span class="text-danger fw-bold" id="std-error" role="alert"></span>
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="report" class="mt-2">Select Report <span class="text-danger">*</span></label>
                                    <select name="report" id="report" class="form-control " required>
                                        <option value="backcomplete" selected>Complete Report</option>
                                        <option value="due">Due Report</option>
                                    </select>
                                </div>
                            </div>

                            <div class="mt-3">
                                <button type="button" id="show-details" class="btn btn-primary">Show Details</button>
                                <span><img src="{{ config('myconfig.myloader') }}" alt="Loading..." class="loader" id="loader" style="display:none; width:5%;"></span>
                            </div>

                        </form>

                        <div id="complete-fee-table" class="table-responsive mt-5" style="display: none;">
                            <table class="table table-striped table-bordered">
                                <thead>
                                    <th>Class</th>
                                    <th>Section</th>
                                    <th>Name</th>
                                    <th>F. Name</th>
                                    <th>Payable(Ac.)</th>
                                    <th>Paid(Ac.)</th>
                                    <th>Due(Ac.)</th>
                                    <th>Payable(Tr.)</th>
                                    <th>Paid(Tr.)</th>
                                    <th>Due(Tr.)</th>
                                    <th>Total Due</th>
                                    <th>Details</th>
                                    <th>Status</th>
                                </thead>
                                <tbody></tbody>
                            </table>
                            <div class="d-flex justify-content-between align-items-center mt-3">
                                <div class="export-div" style="display: none;">
                                    <button type="button" class="btn btn-info" id="export-button">
                                        <i class="bx bx-download"></i> Export to Excel
                                    </button>
                                </div>
                                <div id="due-std-pagination"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('fee-scripts')
    <script>
        $(document).ready(function() {
            let page = 1;
            let classId = $('#fee_class_id');
            let sectionId = $('#fee_section_id');
            let session = $('#session_id');
            let sessionId = $('#session_id').val();

            /** Get all students */
            function getFeeSessionAllStudents(classId, sectionId, sessionId) {

                const loader = $('#loader');
                const studentSelect = $('#fee_student_id');

                // Reset student dropdown if no class or section selected
                if (!classId && !sectionId && !sessionId) {
                    studentSelect.prop('disabled', true).html('<option value="">Select session, class and section first</option>');
                    return;
                }

                loader.show();
                studentSelect.prop('disabled', true).html('<option value="">Loading students...</option>');
                $.ajax({
                    url: siteUrl + '/fee/students',
                    type: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    data: { class_id: classId, section_id: sectionId , session_id: sessionId},
                    dataType: 'json',

                    success: function (response) {
                        studentSelect.empty();
                        if (response.status == 'success' && response.data == 'all') {
                            studentSelect.append('<option value="all">All Students</option>');
                            studentSelect.prop('disabled', false);
                            return;
                        }
                        if (response.status == 'success' && response.data && response.data.length > 0) {
                            studentSelect.append('<option value="all">All Students</option>');
                            $.each(response.data, function (id, st) {
                                studentSelect.append(`<option value="${st.srno}">${st.display_name}</option>`);
                            });
                            studentSelect.prop('disabled', false);
                        } else {
                            studentSelect.html('<option value="">No students found</option>').prop('disabled', true);
                        }
                    },
                    error: function () {
                        studentSelect.html('<option value="">No students found</option>').prop('disabled', true);
                    },
                    complete: function () {
                        loader.hide();
                    }
                });
            }
            if (classId.val() && classId.val() != '') {
                getFeeAllSections(classId.val(), function() {
                    let selectedSection = sectionId.val();
                    if (!selectedSection || selectedSection == '') {
                        sectionId.val('all');
                        selectedSection = 'all';
                    }
                    getFeeSessionAllStudents(classId.val(), selectedSection);
                });
            }

            classId.change(function() {
                let selectedClass = $(this).val();
                page = 1;
                $('#complete-fee-table').hide();
                $('#complete-fee-table table tbody').html('');
                $('#due-std-pagination').html('');
                $('.export-div').hide();

                getFeeAllSections(selectedClass, function() {
                    sectionId.val('all');
                    getFeeSessionAllStudents(selectedClass, 'all');
                });
            });

            sectionId.change(function() {
                page = 1;
                $('#complete-fee-table').hide();
                $('#complete-fee-table table tbody').html('');
                $('#due-std-pagination').html('');
                $('.export-div').hide();

                getFeeSessionAllStudents(classId.val(), $(this).val());
            });
            session.change(function() {
                page = 1;
                $('#complete-fee-table').hide();
                $('#complete-fee-table table tbody').html('');
                $('#due-std-pagination').html('');
                $('.export-div').hide();
                sessionId = $(this).val();

                getFeeSessionAllStudents(classId.val(), $(this).val());
            });

            $('#report').change(function() {
                page = 1;
                $('#complete-fee-table').hide();
                $('#complete-fee-table table tbody').html('');
                $('#due-std-pagination').html('');
                $('.export-div').hide();
            });

            $('#fee_student_id').change(function() {
                page = 1;
                $('#complete-fee-table').hide();
                $('#complete-fee-table table tbody').html('');
                $('#due-std-pagination').html('');
                $('.export-div').hide();
            });

            function loadFeeReport(pageNum) {
                page = pageNum;
                let selectedClassVal = classId.val();
                let selectedSectionVal = sectionId.val();
                let st = $('#fee_student_id').val();
                let reportType = $('#report').val();
                let hasError = false;

                // Reset errors
                $('#class-error, #section-error, #std-error, #session-error').text('');

                if (!selectedClassVal) {
                    $('#class-error').text('Please select a class.');
                    hasError = true;
                }

                if (!selectedSectionVal) {
                    $('#section-error').text('Please select a section.');
                    hasError = true;
                }

                if (!st) {
                    $('#std-error').text('Please select a student.');
                    hasError = true;
                }
                console.log(sessionId);

                if (!sessionId) {
                    $('#session-error').text('Please select a session.');
                    hasError = true;
                }

                if (hasError) {
                    return;
                }
                if(selectedClassVal && selectedSectionVal && st && reportType && sessionId) {
                    $('#complete-fee-table').show();

                    if (typeof loader !== 'undefined') {
                        loader.show();
                    }

                    $.ajax({
                        url: "{{ route('fee.std-due-fee-report') }}",
                        type: 'POST',
                        dataType: 'JSON',
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        data: {
                            class: selectedClassVal,
                            section: selectedSectionVal,
                            srno: st,
                            session: sessionId,
                            reportType: reportType,
                            page: page,
                        },
                        success: function(response) {
                            let stdHtml = '';

                            if (response.status === 'success' && response.data && response.data.length > 0) {
                                response.data.forEach(value => {
                                    stdHtml += `<tr>
                                        <td>${value.class_name}</td>
                                        <td>${value.section_name}</td>
                                        <td>${value.student_name}</td>
                                        <td>${value.father_name}</td>
                                        <td>${value.academic_payable_amount}</td>
                                        <td>${value.academic_paid_amount}</td>
                                        <td>${value.academic_due_amount}</td>
                                        <td>${value.transport_payable_amount}</td>
                                        <td>${value.transport_paid_amount}</td>
                                        <td>${value.transport_due_amount}</td>
                                        <td>${value.total_due}</td>
                                        <td>
                                            <a href='${siteUrl}/fee/back-session/individual-fee-details/${value.student_srno}/${value.session_id}/${value.class}/${value.section}' class="btn btn-sm btn-icon p-1">
                                                <i class="mdi mdi-eye mx-1" data-bs-toggle="tooltip" data-bs-placement="top" title="View"></i>
                                            </a>
                                        </td>
                                        <td>${getStudentStatus(value.ssid)}</td
                                    </tr>`;
                                });

                                // Show export button when data is available
                                $('.export-div').show();
                            } else {
                                stdHtml = '<tr><td colspan="13" class="text-center">No Student Record Found</td></tr>';
                                // Hide export button when no data
                                $('.export-div').hide();
                            }

                            $('#complete-fee-table table tbody').html(stdHtml);

                            if (response.pagination) {
                                dueUpdatePaginationControls(response.pagination);
                            } else {
                                $('#due-std-pagination').html('');
                            }
                        },
                        complete: function() {
                            if (typeof loader !== 'undefined') {
                                loader.hide();
                            }
                        },
                        error: function(xhr) {
                            if (typeof loader !== 'undefined') {
                                loader.hide();
                            }
                            let errorMsg = 'Error loading data';
                            if (xhr.responseJSON && xhr.responseJSON.message) {
                                errorMsg = xhr.responseJSON.message;
                            }
                            $('#complete-fee-table table tbody').html(`<tr><td colspan="13" class="text-center text-danger">${errorMsg}</td></tr>`);
                            $('#due-std-pagination').html('');
                            $('.export-div').hide();
                        }
                    });
                }
            }

            function getStudentStatus(ssid) {
                return ssid == 1 ? 'Active' : ssid == 2 ? 'Class Promoted' : ssid == 3 ? 'School Promoted' : ssid == 4 ? 'Tc' : ssid == 5 ? 'Left Out' : '';
            }

            $('#show-details').click(function() {
                page = 1;
                loadFeeReport(1);
            });

            $(document).on('click', '#due-std-pagination .page-link', function(e) {
                e.preventDefault();
                e.stopPropagation();

                let clickedPage = parseInt($(this).data('page'));
                if (clickedPage && clickedPage > 0) {
                    $('html, body').animate({
                        scrollTop: $("#complete-fee-table").offset().top - 100
                    }, 300);

                    loadFeeReport(clickedPage);
                }

                return false;
            });

            // Export button click handler
            $('#export-button').click(function() {
                let selectedClassVal = classId.val();
                let selectedSectionVal = sectionId.val();
                let st = $('#fee_student_id').val();
                let reportType = $('#report').val();

                if(!selectedClassVal || !selectedSectionVal || !st || !reportType) {
                    return;
                }

                // Show loading state
                let $btn = $(this);
                let originalText = $btn.html();
                $btn.prop('disabled', true).html('<i class="bx bx-loader bx-spin"></i> Exporting...');

                // Build export URL with parameters
                let exportUrl = "{{ route('fee.back.session.std-due-fee-report-excel') }}" +
                    '?class=' + encodeURIComponent(selectedClassVal) +
                    '&section=' + encodeURIComponent(selectedSectionVal) +
                    '&session=' + encodeURIComponent(sessionId) +
                    '&srno=' + encodeURIComponent(st) +
                    '&reportType=' + encodeURIComponent(reportType);

                // First check if data exists
                $.ajax({
                    url: "{{ route('fee.back.session.check-due-fee-data') }}",
                    type: 'GET',
                    data: {
                        class: selectedClassVal,
                        section: selectedSectionVal,
                        srno: st,
                        sessionId: sessionId,
                        reportType: reportType
                    },
                    success: function(response) {
                        if (response.status === 'success') {
                            // Data exists, proceed with download
                            window.location.href = exportUrl;

                            // Reset button after a delay
                            setTimeout(function() {
                                $btn.prop('disabled', false).html(originalText);
                            }, 2000);
                        } else {
                            $btn.prop('disabled', false).html(originalText);
                        }
                    },
                    error: function(xhr) {
                        let errorMsg = 'No data available to export';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMsg = xhr.responseJSON.message;
                        }
                        $btn.prop('disabled', false).html(originalText);
                    }
                });
            });
        });



    </script>
@endsection
