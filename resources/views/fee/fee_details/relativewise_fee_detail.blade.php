@extends('fee.index')
@section('sub-content')
    <div class="container">

        <div class="row justify-content-center">
            <div class="col-md-14">
                <div class="card">
                    <div class="card-header">
                        {{ 'Fee Details (Relative Wise)' }}
                        <a href="{{ route('fee.fee-detail-relaive-wise') }}" class="btn btn-warning btn-sm"
                            style="float: right;">Back</a>

                    </div>
                    <div class="card-body">
                        <form id="class-section-form">
                            <div class="row">
                                <div class="form-group col-md-6">
                                    <label for="back_class_id" class="mt-2">Class <span
                                            class="text-danger">*</span></label>
                                    <input type="hidden" id="initialClassId"
                                        value="{{ old('initialClassId', request()->get('class_id') !== null ? request()->get('class_id') : '') }}">
                                    <select name="class" id="back_class_id" class="form-control " required>
                                        <option value="">All Class</option>
                                    </select>
                                    <span class="invalid-feedback form-invalid fw-bold" id="class-error"
                                        role="alert"></span>

                                </div>


                                <div class="form-group col-md-6">
                                    <label for="back_section_id" class="mt-2">Section <span
                                            class="text-danger">*</span></label>
                                    <input type="hidden" id="initialSectionId"
                                        value="{{ old('initialSectionId', request()->get('section_id') !== null ? request()->get('section_id') : '') }}">
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
                            </div>
                            <div class="mt-3">
                                <button type="button" class="btn btn-info" id="show-btn">Show</button><img src="{{ config('myconfig.myloader') }}" alt="Loading..." class="loader"
                                id="loader" style="display:none; width:10%;">
                            </div>

                        </form>
                        <div class="table mt-4">
                            <table class="table table-striped table-bordered">
                                <thead>
                                    <tr>
                                        <th colspan="3">Academic</th>
                                        <th colspan="3">Transport</th>
                                    </tr>
                                    <tr>
                                        <th>Total</th>
                                        <th>Paid</th>
                                        <th>Due</th>
                                        <th>Total</th>
                                        <th>Paid</th>
                                        <th>Due</th>
                                    </tr>

                                </thead>
                                <tbody>
                                    <tr>
                                        <td>{{ $feeMaster }}</td>
                                        <td>{{ $totalAcademic }}</td>
                                        <td>{{ $academicDue }}</td>
                                        <td>{{ $std }}</td>
                                        <td>{{ $totalTrans }}</td>
                                        <td>{{ $transDue }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div id="std-fee-due-table" class="table-responsive">
                            <table class="table table-striped table-bordered">
                                <thead>
                                    <th>Class</th>
                                    <th>Section</th>
                                    <th>Name</th>
                                    <th>Father's Name</th>
                                    <th>Payable Amount(Ac.)</th>
                                    <th>Paid Amount(Ac.)</th>
                                    <th>Due Amount(Ac.)</th>
                                    <th>Payable Amount(Tr.)</th>
                                    <th>Paid Amount(Tr.)</th>
                                    <th>Due Amount(Tr.)</th>
                                    <th>Payable Amount(St.)</th>
                                    <th>Paid Amount(St.)</th>
                                    <th>Due Amount(St.)</th>
                                    <th>Details</th>
                                </thead>
                                <tbody>

                                </tbody>
                            </table>
                            <div class="export-div">
                                <button type="button" class="btn btn-info" id="export-button">Export</button>
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
        // var initialClassId = '{{ old('class') }}';
        // var initialSectionId = '{{ old('section') }}';

        $(document).ready(function() {
            let sessionId = $('#current_session').val();
            let classId = $('#back_class_id');
            let sectionId = $('#back_section_id');
            let loader = $('#loader');
            let stdId = $('#back_std_id');


            classSectionWithAll(fetchStudentsForSession, fetchStudents);

            function fetchStudentsForSession() {
                let selectedSession = sessionId;
                let allClassesValue = classId.find('option:first').val();
                let allSectionsValue = sectionId.find('option:first').val();
                fetchStudents(allSectionsValue);
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
                        // Clear the dropdown at the start
                        stdId.empty();

                        let allStdIds = [];
                        if (data && data.length > 0) {
                            if (sectionIds.includes(',')) {
                                // Populate allStdIds
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
                                    stdId.append('<option value="' + value.srno + '">' + value
                                        .rollno + '.' + value.student_name + '/SH. ' + value
                                        .f_name + '</option>');
                                });

                                // Add "All Students" option as the first option
                                stdId.prepend('<option value="' + allStdIds.join(',') +
                                    '" selected>All Students</option>');
                            }
                        } else {
                            // If no students are found, add "No Student Found" message
                            stdId.empty();
                            stdId.append('<option value="">No Student Found</option>');
                        }
                    },
                    complete: function() {
                        loader.hide();
                    },
                    error: function(data) {
                        console.error('Error fetching students:', data.responseJSON ? data.responseJSON
                            .message : 'Unknown error');
                    }
                });
            }
            // Fetch students on class and section change
            classId.on('change', fetchStudentsForSession);
            sectionId.on('change', function() {
                let selectedSession = sessionId;
                let allClassesValue = classId.val();
                let allSectionsValue = sectionId.val();
                fetchStudents(allSectionsValue);
                // fetchStudents()
            });
            sectionId.on('change', fetchStudents());


            // Export table to Excel

            let stdSelect = $('#back_std_id');
            let stdFeeDueTable = $('#std-fee-due-table');
            let relativeStdFeeDueTable = $('#relative-std-fee-due-table');
            stdFeeDueTable.hide();
            relativeStdFeeDueTable.hide();

            $('#show-btn').on('click', function() {
                let sessionID = $('#current_session').val();
                if (sessionID && classId.val() && sectionId.val() && stdSelect.val()) {

                    stdFeeDueTable.show();
                    $.ajax({
                        url: '{{ route('fee.fee-entry.academicFeeDueAmount') }}',
                        type: 'GET',
                        dataType: 'JSON',
                        data: {
                            srno: stdSelect.val(),
                            current_session: sessionID,
                            class: classId.val(),
                            section: sectionId.val(),
                        },
                        success: function(response) {
                            let stdHtml = '';
                            let relativestdHtml = '';

                            // Process student data
                            const students = response.data;
                            if (students.length > 0) {

                                $.each(students, function(index, student) {
                                    $.each(student.sessions, function(index, session) {
                                        if (session.session_id == sessionId) {
                                            stdHtml += `<tr>
                                            <td>${session.class}</td>
                                            <td>${session.section}</td>
                                            <td>${student.student_name}</td>
                                            <td>${student.father_name}</td>
                                            <td>${session.payable_amount}</td>
                                            <td>${session.paid_amount}</td>
                                            <td>${session.due_amount}</td>
                                            <td>${session.transport.payable_amount}</td>
                                            <td>${session.transport.paid_amount}</td>
                                            <td>${session.transport.due_amount}</td>
                                            <td>N/A</td>
                                            <td>N/A</td>
                                            <td>N/A</td>
                                            <td><a href='${siteUrl}/fee/individual-fee-details/${student.srno}/${session.session_id}/${session.class_id}/${session.section_id}'  class="btn btn-sm btn-icon p-1"> <i class="mdi mdi-eye mx-1" data-bs-toggle="tooltip"
                                                                data-bs-offset="0,4" data-bs-placement="top" title="View"></i></a></td>
                                        </tr>`;
                                        }
                                    });


                                    // Process relatives data
                                    if (student.relatives && student.relatives.length > 0) {
                                        $.each(student.relatives, function(index,
                                            relative) {
                                            // const encodedSrno = encodeURIComponent(relative.srno);
                                            stdHtml += `<tr class="table-warning">
                                        <td>${relative.class}</td>
                                        <td>${relative.section}</td>
                                        <td>${relative.student_name}</td>
                                        <td>${relative.father_name}</td>
                                        <td>${relative.payable_amount}</td>
                                        <td>${relative.paid_amount}</td>
                                        <td>${relative.due_amount}</td>
                                        <td>${relative.transport.payable_amount}</td>
                                        <td>${relative.transport.paid_amount}</td>
                                        <td>${relative.transport.due_amount}</td>
                                        <td>N/A</td>
                                        <td>N/A</td>
                                        <td>N/A</td>
                                        <td><a href='${siteUrl}/fee/individual-fee-details/${relative.srno}/${relative.session_id}/${relative.class_id}/${relative.section_id}' class="btn btn-sm btn-icon p-1"> <i class="mdi mdi-eye mx-1" data-bs-toggle="tooltip"
                                                                data-bs-offset="0,4" data-bs-placement="top" title="View"></i></a></td>
                                        </tr>`;
                                        });
                                    }
                                });
                                if (stdHtml === '') {
                                    stdHtml = '<tr><td colspan="14">No Student found</td></tr>';
                                }
                            } else {
                                stdHtml = '<tr><td colspan="14">No Student found</td></tr>';
                            }
                            $('#std-fee-due-table table tbody').html(stdHtml);
                        },
                        error: function(xhr) {
                            console.error(xhr.responseText);
                        }
                    });
                }else{
                    stdFeeDueTable.hide();
                }
            });

            $('#export-button').on('click', function() {
                let session = $('#current_session').val();
                let stdSelect = $('#back_std_id').val();
                const exportUrl = "{{ route('fee.fee-detail-relaive-wise-excel') }}?srno=" +
                    stdSelect + "&current_session=" + session + "&class=" + classId.val() + "&section=" +
                    sectionId.val();
                window.location.href = exportUrl;
            });

            $('#back_std_id, #back_class_id, #back_section_id').on('change', function() {
                stdFeeDueTable.hide();
            });
        });
    </script>
@endsection
