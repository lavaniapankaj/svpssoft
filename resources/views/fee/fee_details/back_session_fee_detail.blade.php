@extends('fee.index')
@section('sub-content')
    <div class="container">

        <div class="row justify-content-center">
            <div class="col-md-14">
                <div class="card">
                    <div class="card-header">
                        {{ 'Back Session Fee Details' }}
                        <a href="{{ route('fee.back-session-fee-detail') }}" class="btn btn-warning btn-sm"
                            style="float: right;">Back</a>
                    </div>
                    <div class="card-body">
                        <form id="class-section-form">
                            <div class="row">
                                <div class="form-group col-md-4">
                                    <label for="session_id" class="mt-2">Session <span
                                            class="text-danger">*</span></label>
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
                                    <span class="invalid-feedback form-invalid fw-bold" id="session-error"
                                        role="alert"></span>
                                </div>
                                <div class="form-group col-md-4">
                                    <label for="back_class_id" class="mt-2">Class <span
                                            class="text-danger">*</span></label>
                                    <input type="hidden" id="initialClassId" value="{{ old('class') }}">
                                    <select name="class" id="back_class_id" class="form-control " required>
                                        <option value="">All Class</option>
                                    </select>
                                    <span class="invalid-feedback form-invalid fw-bold" id="class-error"
                                        role="alert"></span>

                                </div>


                                <div class="form-group col-md-4">
                                    <label for="back_section_id" class="mt-2">Section <span
                                            class="text-danger">*</span></label>
                                    <input type="hidden" id="initialSectionId" value="{{ old('section') }}">
                                    <select name="section" id="back_section_id" class="form-control  " required>
                                        <option value="">All Section</option>
                                    </select>
                                    <span class="invalid-feedback form-invalid fw-bold" id="section-error"
                                        role="alert"></span>
                                    <img src="{{ config('myconfig.myloader') }}" alt="Loading..." class="loader"
                                        id="loader" style="display:none; width:10%;">
                                </div>
                            </div>
                            <div class="row">
                                <div class="form-group col-md-6">
                                    <input type="hidden" name="current_session" value='' id="current_session">
                                    <label for="back_std_id" class="mt-2">Student <span
                                            class="text-danger">*</span></label>
                                    <select name="std_id" id="back_std_id" class="form-control " required>
                                        <option value="">All Students</option>
                                    </select>
                                    <span class="invalid-feedback form-invalid fw-bold" id="std-error"
                                        role="alert"></span>
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="report" class="mt-2">Select Report <span
                                            class="text-danger">*</span></label>
                                    <select name="report" id="report" class="form-control " required>
                                        {{-- <option value="">Select Report</option> --}}
                                        <option value="complete" selected>Complete Report</option>
                                        <option value="due">Due Report</option>
                                    </select>
                                </div>
                            </div>

                            <div class="mt-3">
                                <button type="button" id="show-details" class="btn btn-primary">Show Details</button>
                                {{-- <span class="invalid-feedback form-invalid fw-bold" id="std-error"role="alert"></span> --}}
                            </div>

                        </form>

                        <div id="complete-fee-table" class="table-responsive mt-5">
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
                            <div class="export-div">
                                <button type="button" class="btn btn-info" id="export-button">Export</button>
                            </div>
                            <div id="std-pagination" class="mt-4"></div>
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
            const classId = $('#back_class_id');
            const sectionId = $('#back_section_id');
            const sessionId = $('#session_id');
            const stdId = $('#back_std_id');
            const loader = $('#loader');
            const reportTypeDropdown = $('#report');
            const feeTable = $('#complete-fee-table');
            const sessionError = $('#session-error');
            let currentPage = 1;

            feeTable.hide();
            classSectionWithAll(fetchStudentsForSession, fetchStudents);

            function fetchStudentsForSession() {
                const allSectionsValue = sectionId.find('option:first').val();
                fetchStudents(allSectionsValue);
            }

            function updateStudentDropdown(data, isAllSections) {
                stdId.empty();
                const allStdIds = data.map(student => student.srno);
                if (data.length > 0) {

                    if (isAllSections) {
                        stdId.append(`<option value="${allStdIds.join(',')}" selected>All Students</option>`);
                    } else {
                        stdId.append(`<option value="${allStdIds.join(',')}" selected>All Students</option>`);
                        data.forEach(value => {
                            stdId.append(
                                `<option value="${value.srno}">${value.student_name}/SH. ${value.f_name}</option>`
                            );
                        });
                    }

                    const selectedStValue = stdId.val() || allStdIds.join(',');
                    studentTable(selectedStValue);
                    $('#std-pagination').show();
                } else {
                    stdId.append(`<option value="">No Student Found</option>`);
                    $('#complete-fee-table table tbody').html(`
                                <tr>
                                    <td colspan="13" class="text-center">No student found for the selected session and class.</td>
                                </tr>
                    `);
                    feeTable.hide();
                    $('#std-pagination').hide();
                }
            }

            function fetchStudents(sectionIds) {
                if (!sectionIds) return;

                loader.show();
                $.ajax({
                    url: siteUrl + '/std-name-father',
                    type: 'GET',
                    dataType: 'JSON',
                    data: {
                        class_id: classId.val(),
                        section_id: sectionIds,
                        session_id: sessionId.val(),
                    },
                    success: function(data) {

                        const isAllSections = sectionIds.includes(',');
                        updateStudentDropdown(data, isAllSections);
                    },
                    complete: function() {
                        loader.hide();
                    },
                    error: function(data) {
                        console.error('Error fetching students:', data.responseJSON?.message ||
                            'Unknown error');
                    }
                });
            }

            function studentTable(st, page = 1) {
                const reportType = reportTypeDropdown.val();
                loader.show();
                if (st && page) {

                    $.ajax({
                        url: '{{ route('fee.studentWithoutSsid') }}',
                        type: 'GET',
                        dataType: 'JSON',
                        data: {
                            session: sessionId.val(),
                            class: classId.val(),
                            section: sectionId.val(),
                            srno: st,
                            page
                        },
                        success: function(data) {
                            const isAllStudents = st.includes(',');
                            const isAllSections = sectionId.val().includes(',');

                            const rows = data.data.map(value => {
                                const totalDue = value.due_amount + value.trans_due_amount;
                                const isValid = isAllSections ?
                                    st === value.student.srno.toString() :
                                    (st === value.student.srno.toString() && sectionId.val() ==
                                        value.student.section.toString());

                                if ((isAllStudents || isValid) && (reportType === 'complete' ||
                                        totalDue > 0)) {
                                    return `
                                    <tr>
                                        <td>${value.class_name}</td>
                                        <td>${value.section_name}</td>
                                        <td>${value.student_name}</td>
                                        <td>${value.father_name}</td>
                                        <td>${value.payable_amount}</td>
                                        <td>${value.paid_amount}</td>
                                        <td>${value.due_amount}</td>
                                        <td>${value.trans_payable_amount}</td>
                                        <td>${value.trans_paid_amount}</td>
                                        <td>${value.trans_due_amount}</td>
                                        <td>${totalDue}</td>
                                        <td>
                                            <a href='${siteUrl}/fee/back-session/individual-fee-details/${value.student.srno}/${value.student.session_id}/${value.student.class}/${value.student.section}' class="btn btn-sm btn-icon p-1">
                                                <i class="mdi mdi-eye mx-1" data-bs-toggle="tooltip" data-bs-placement="top" title="View"></i>
                                            </a>
                                        </td>
                                        <td>${getStudentStatus(value.student.ssid)}</td>
                                    </tr>`;
                                }
                            }).join('');
                            if (data.data.length === 0) {
                                rows =
                                    '<tr><td colspan="13" class="text-center">No data found.</td></tr>';
                            }

                            $('#complete-fee-table table tbody').html(rows);
                            updatePaginationControls(data.pagination);
                        },
                        complete: function() {
                            loader.hide();
                        },
                        error: function(data) {
                            console.error('Error fetching students:', data.responseJSON?.message ||
                                'Unknown error');
                        }
                    });
                }
            }

            function getStudentStatus(ssid) {
                return ssid === 1 ? 'Active' :
                    ssid === 2 ? 'Class Promoted' :
                    ssid === 3 ? 'School Promoted' :
                    ssid === 4 ? 'Tc' :
                    ssid === 5 ? 'Left Out' : '';
            }

            function getExcelReport() {
                if(!stdId.val()){
                    return;
                }
                const st = stdId.val();
                const report = reportTypeDropdown.val();
                const classID = classId.val();
                const sectionID = sectionId.val();
                const exportUrl =
                    `{{ route('fee.back-session-fee-detail-excel') }}?session=${sessionId.val()}&srno=${st}&reportType=${report}&class=${classID}&section=${sectionID}`;
                window.location.href = exportUrl;
            }

            // Event Listeners
            sectionId.change(() => fetchStudents(sectionId.val()));
            sessionId.change(() => {
                classId.val(classId.find('option:first').val());
                sectionId.val(sectionId.find('option:first').val());

                if (!sessionId.val()) {
                    feeTable.hide();
                } else {
                    fetchStudentsForSession();
                }
            });

            stdId.change(() => studentTable(stdId.val()));
            reportTypeDropdown.change(() => studentTable(stdId.val()));
            $('#show-details').click(() => {
                if (!sessionId.val()) {
                    sessionError.show().text('Select the Session');
                    feeTable.hide();
                } else {
                    sessionError.hide();
                    feeTable.show();
                }
            });
            $('#export-button').click(getExcelReport);
            $(document).on('click', '#std-pagination .page-link', function(e) {
                e.preventDefault();
                const page = $(this).data('page');
                currentPage = page;
                studentTable(stdId.val(), page);
            });
            $('#session_id, #back_class_id, #back_section_id, #back_std_id, #report').change(() => {
                feeTable.hide();
            });
        });
    </script>
@endsection
