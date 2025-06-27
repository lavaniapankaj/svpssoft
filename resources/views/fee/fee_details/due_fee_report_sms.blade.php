@extends('fee.index')
@section('sub-content')
    <div class="container">

        <div class="row justify-content-center">
            <div class="col-md-14">
                <div class="card">
                    <div class="card-header">
                        {{ 'Due Fee SMS' }}
                        <a href="{{ route('fee.due-fee-report-sms') }}" class="btn btn-warning btn-sm"
                            style="float: right;">Back</a>
                    </div>
                    <div class="card-body">
                        <form id="class-section-form">
                            <div class="row">

                                <div class="form-group col-md-6">
                                    <label for="back_class_id" class="mt-2">Class <span
                                            class="text-danger">*</span></label>
                                    <select name="class" id="back_class_id" class="form-control " required>
                                        <option value="">All Class</option>
                                    </select>
                                    <span class="invalid-feedback form-invalid fw-bold" id="class-error"
                                        role="alert"></span>
                                    <img src="{{ config('myconfig.myloader') }}" alt="Loading..." class="loader"
                                        id="loader" style="display:none; width:10%;">
                                </div>


                                <div class="form-group col-md-6">
                                    <label for="back_section_id" class="mt-2">Section <span
                                            class="text-danger">*</span></label>
                                    <select name="section" id="back_section_id" class="form-control  " required>
                                        <option value="">All Section</option>
                                    </select>
                                    <span class="invalid-feedback form-invalid fw-bold" id="section-error"
                                        role="alert"></span>
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
                                        <option value="1" selected>Ist Installment</option>
                                        <option value="2">IInd Installment</option>
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
                                </thead>
                                <tbody></tbody>
                            </table>
                            <div id="std-pagination"></div>

                            <div class="mt-3" id="sms-div">
                                <form action="" method="post">
                                    @csrf
                                    <div class="form-group col-md-12 message-box" style="display: none;">
                                        <label for="report" class="mt-2">Enter Your Message<span
                                                class="text-danger">*</span></label>
                                        <textarea name="message" id="message" cols="30" rows="10" class="form-control"
                                            placeholder="Enter your message. (Add {%student_name%} in place of the student name and at due amount add {%total_due%}.)"
                                            required></textarea>
                                    </div>
                                </form>
                                <button type="submit" id="sms-send" class="btn btn-success mt-3">SMS</button>
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
            function dueReportSection() {

                let classId = $('#back_class_id');

                let sectionId = $('#back_section_id');

                let sessionId = $('#current_session').val();
                let stdId = $('#back_std_id');

                let loader = $('#loader');

                $('#complete-fee-table').hide();

                classSectionWithAll(fetchStudentsForSession, fetchStudents);

                function fetchStudentsForSession() {

                    let selectedSession = sessionId;

                    let allClassesValue = classId.find('option:first').val();

                    let allSectionsValue = sectionId.find('option:first').val();

                    fetchStudents(allSectionsValue);

                }

                function studentTable(st, page = 1) {
                    let reportType = $('#report').val();
                    if(st){
                        $('#sms-div').show();
                        $('#std-pagination').show();
                        $.ajax({
                            url: siteUrl + '/fee/student-without-ssid',
                            type: 'GET',
                            dataType: 'JSON',
                            data: {
                                session: sessionId,
                                class: classId.val(),
                                section: sectionId.val(),
                                srno: st,
                                page: page,
                            },
                            success: function(data) {
                                let stdHtml = '';
                                const isAllStudents = st.includes(',');
                                const isAllSections = sectionId.val().includes(',');

                                if (data.data.length > 0) {
                                    let hasValidRecords = false;
                                    data.data.forEach(value => {
                                        let isValidateIsAll = isAllSections === false ?
                                            (st === value.student.srno.toString()) && (sectionId
                                                .val() == value.student.section.toString()) :
                                            (st === value.student.srno.toString());

                                        if (isAllStudents || isValidateIsAll) {
                                            const firstInst = value.installments.first_inst;
                                            const secondInst = value.installments.second_inst;
                                            const transFirstInst = value.trans_installments
                                                .first_inst;
                                            const transSecondInst = value.trans_installments
                                                .second_inst;

                                            // Calculate amounts for academic fee
                                            const academicInstAmount = reportType == 1 ?
                                                firstInst.reduce((total, inst) => total + (inst
                                                    .amount || 0), 0) :
                                                secondInst.reduce((total, inst) => total + (inst
                                                    .amount || 0), 0);

                                            // Calculate amounts for transport fee
                                            const transportInstAmount = value.transport == 1 ? (reportType == 1 ? transFirstInst.reduce((total, inst) => total + (inst.amount || 0), 0) : transSecondInst.reduce((total, inst) => total + (inst.amount || 0), 0)) : 0;
                                            // Calculate due amounts
                                            const academicDue = reportType == 1 ?
                                                value.inst_1 - academicInstAmount :
                                                value.inst_2 - academicInstAmount;

                                            const transportDue = value.transport == 1 ?
                                                (reportType == 1 ?
                                                    ((value.trans_inst_1 ?? 0) -
                                                        transportInstAmount) :
                                                    ((value.trans_inst_2 ?? 0) -
                                                        transportInstAmount)
                                                ) : 0;
                                            const totalDue = academicDue + transportDue;

                                            // Only include the student if it's a first due report or if there's a due amount
                                            if ((reportType == 1 || reportType == 2) && totalDue >
                                                0) {
                                                hasValidRecords = true;
                                                stdHtml += `
                                                    <tr>
                                                        <td>${value.class_name}</td>
                                                        <td>${value.section_name}</td>
                                                        <td>${value.student_name}</td>
                                                        <td>${value.father_name}</td>
                                                        <td>${reportType == 1 ? value.inst_1 : value.inst_2}</td>
                                                        <td>${academicInstAmount}</td>
                                                        <td>${academicDue}</td>
                                                        <td>${value.transport == 1 ? (reportType == 1 ? (value.trans_inst_1 ?? 0) : (value.trans_inst_2 ?? 0)) : 0}</td>
                                                        <td>${transportInstAmount}</td>
                                                        <td>${transportDue}</td>
                                                        <td>${totalDue}</td>
                                                        <td>
                                                            <a href='${siteUrl}/fee/back-session/individual-fee-details/${value.student.srno}/${value.student.session_id}/${value.student.class}/${value.student.section}' class="btn btn-sm btn-icon p-1">
                                                                <i class="mdi mdi-eye mx-1" data-bs-toggle="tooltip" data-bs-offset="0,4" data-bs-placement="top" title="View"></i>
                                                            </a>
                                                        </td>
                                                </tr>`;
                                            }
                                        }
                                    });

                                    if (!hasValidRecords) {
                                        stdHtml = '<tr><td colspan="12">No Student Record Found</td></tr>';
                                    }
                                } else {
                                    stdHtml = '<tr><td colspan="12">No Student Record Found</td></tr>';
                                }

                                $('#complete-fee-table table tbody').html(stdHtml);
                                updatePaginationControls(data.pagination);
                            },
                            complete: function() {
                                loader.hide();
                            },
                            error: function(data) {
                                console.error('Error fetching students:', data.responseJSON ?
                                    data.responseJSON.message : 'Unknown error');
                            }
                        });

                    }else{
                        $('#complete-fee-table table tbody').html('<tr><td colspan="12">No Student Record Found</td></tr>');
                        $('#sms-div').hide();
                        $('#std-pagination').hide();
                    }
                }

                function fetchStudents(sectionIds) {
                    loader.show();

                    $.ajax({
                        url: siteUrl + '/std-name-father',
                        type: 'GET',
                        dataType: 'JSON',
                        data: {
                            session_id: sessionId,
                            class_id: classId.val(),
                            section_id: sectionIds,
                        },
                        success: function(data) {
                            stdId.empty();
                            let allStdIds = [];
                            if (sectionIds.includes(',')) {
                                // Always populate allStdIds
                                $.each(data, function(id, value) {

                                    allStdIds.push(value.srno);
                                });
                                // Add "All Students" option

                                stdId.append('<option value="' + allStdIds.join(',') +

                                    '" selected>All Students</option>');

                            } else {
                                // Add individual student options
                                $.each(data, function(id, value) {
                                    allStdIds.push(value.srno);
                                    stdId.append('<option value="' + value.srno +
                                        '">' + value.rollno + '.' + value.student_name +
                                        '/SH. ' + value.f_name + '</option>');
                                });
                                stdId.prepend('<option value="' + allStdIds.join(',') +
                                    '" selected>All Students</option>');

                            }
                            // Fetch students for the initial selection
                            var selectedStValue = stdId.val();
                            studentTable(selectedStValue ? selectedStValue : allStdIds.join(','));
                        },

                        complete: function() {
                            loader.hide();
                        },
                        error: function(data) {
                            console.error('Error fetching students:', data.responseJSON ? data
                                .responseJSON.message : 'Unknown error');

                        }

                    });

                }



                $(document).on('click', '#std-pagination .page-link', function(e) {
                    e.preventDefault();
                    var st = stdId.val();
                    var page = $(this).data('page');
                    studentTable(st, page);

                });

                sectionId.change(function() {

                    fetchStudents($(this).val());

                });

                stdId.change(function() {

                    studentTable($(this).val());

                });

                // Add an event listener for the report dropdown

                $('#report').change(function() {

                    let selectedStudents = $('#back_std_id').val();

                    studentTable(selectedStudents);

                });
                // Modify the show-details button click event

                $('#show-details').click(function() {
                    $('#complete-fee-table').show();

                });

                $('#sms-send').click(function() {
                    let reportType = $('#report').val();
                    $('.message-box').show();
                    let message = $('#message').val();
                    if (!message) {
                        return alert('Please enter a message');
                    } else {
                        Swal.fire({
                            title: 'Send SMS',
                            text: `Are you sure you want to send SMS to students?`,
                            icon: 'question',
                            showCancelButton: true,
                            confirmButtonText: 'Yes, send SMS',
                            cancelButtonText: 'No, cancel',
                        }).then((result) => {
                            if (result.isConfirmed) {
                                $.ajax({
                                    url: "{{ route('fee.due-fee-report-send-sms') }}",
                                    type: 'POST',
                                    headers: {
                                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr(
                                            'content')
                                    },
                                    data: {
                                        session: sessionId,
                                        srno: stdId.val(),
                                        reportType: reportType,
                                        class: classId.val(),
                                        section: sectionId.val(),
                                        message: message,

                                    },
                                    success: function(response) {
                                        if (response.status == 'success') {
                                            Swal.fire({
                                                title: 'SMS Sent',
                                                text: response.message,
                                                icon: 'success',
                                            });

                                        }
                                    },
                                    error: function(xhr) {
                                        let errorMessage = xhr.responseJSON?.message ||
                                            'Something went wrong!';
                                        Swal.fire({
                                            title: 'Error',
                                            text: errorMessage,
                                            icon: 'error',
                                        });
                                    }
                                });
                            }
                        });
                    }

                });
            }
            dueReportSection();
        });
    </script>
@endsection
