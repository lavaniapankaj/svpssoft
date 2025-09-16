@extends('fee.index')
@section('sub-content')
    <div class="container-fluid">
        <div class="row ">
            <div class="col-md-12">
                <div class="card border-0 bg-white">
                    <div class="card-header flex-wrap bg-white d-flex align-items-center justify-content-between">
                        <h5 class="mb-0 mt-0">{{ 'Print Due Receipt (With Message)' }}</h5>
                        <a href="{{ route('fee.print-due-receipt') }}" class="btn bg-light btn-sm"><span
                                class="mdi mdi-chevron-left me-2"></span>Back</a>
                    </div>
                    <div class="card-body">
                        <form id="class-section-form">
                            <div class="row">
                                <div class="form-group col-md-6">
                                    <label for="date" class="mt-2">Print Date <span
                                            class="text-danger">*</span></label>
                                    <input type="date" name="date" id="date" class="form-control" required>
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="class_id" class="mt-2">Class <span class="text-danger">*</span></label>
                                    <select name="class" id="class_id" class="form-control " required>
                                        <option value="">Select Class</option>
                                        @if (count($classes) > 0)
                                            @foreach ($classes as $key => $class)
                                                <option value="{{ $key }}"
                                                    {{ old('class') == $key ? 'selected' : '' }}>{{ $class }}
                                                </option>
                                            @endforeach
                                        @else
                                            <option value="">No Class Found</option>
                                        @endif
                                    </select>
                                    <span class="invalid-feedback form-invalid fw-bold" id="class-error"
                                        role="alert"></span>
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="fee_section_id" class="mt-2">Section <span
                                            class="text-danger">*</span></label>
                                    <input type="hidden" id="initialSectionId" value="{{ old('section') }}">
                                    <select name="section" id="fee_section_id" class="form-control" required>
                                        <option value="">Select Section</option>
                                    </select>
                                    <span class="invalid-feedback form-invalid fw-bold" id="section-error"
                                        role="alert"></span>
                                </div>
                                <div class="form-group col-md-6">
                                    <input type="hidden" name="current_session" value='' id="current_session">
                                    <label for="std_id" class="mt-2">Student <span class="text-danger">*</span></label>
                                    <select name="std_id[]" id="std_id" class="form-control" required>
                                        {{-- <select name="std_id[]" id="std_id" class="form-control" multiple required> --}}
                                        <option value="">Select Students</option>
                                    </select>
                                    <span class="invalid-feedback form-invalid fw-bold" id="std-error"
                                        role="alert"></span>
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="message" class="mt-2">Enter Message</label>
                                    <textarea name="message" id="message" cols="30" rows="10" class="form-control"></textarea>
                                </div>
                            </div>
                            <div class="mt-3">
                                <button type="button" id="show-details" class="btn btn-primary">Show-details</button>
                                <img src="{{ config('myconfig.myloader') }}" alt="Loading..." class="loader" id="loader"
                                    style="display:none; width:5%;">
                                <span class="invalid-feedback form-invalid fw-bold" id="total-amount-error"
                                    role="alert"></span>
                            </div>
                        </form>
                        <div id="receipt-div">
                            <div class="mt-2 receipt-table" id="print-receipt-div">
                            </div>
                            <div class="mt-3">
                                <button type="button" id="print-receipt" class="btn btn-primary print-receipt">Print
                                    Receipt</button>
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
            let initialClassId = $('#class_id').val();
            let initialSectionId = $('#initialSectionId').val();
            let date = $('#date');
            let getDate = $('#get-date');
            let message = $('#message');
            let getInfo = $('#get-info');
            let stdSelect = $('#std_id');
            var loader = $('.loader');
            //get class and section
            function getFeeClassSection(initialClassId, initialSectionId = '', classSelect = '', sectionSelect =
            '') {
                var classSelected = classSelect;
                var sectionSelected = sectionSelect;
                var initialClassesId = $('#initialClassId').val();
                var initialSectionId = initialSectionId;
                var initialClassId = initialClassId;
                if (classSelect == '' || sectionSelect == '') {
                    classSelected = $('#class_id');
                    sectionSelected = $('#fee_section_id');
                }
                var classId = initialClassesId;
                fetchSections(classId);
                function fetchSections(classId) {
                    if (classId) {
                        $.ajax({
                            url: siteUrl + '/sections',
                            type: 'GET',
                            dataType: 'JSON',
                            data: {
                                class_id: classId
                            },
                            success: function(data) {
                                sectionSelected.empty(); // Clear the dropdown
                                if (data.status === "success" && data.data && Object.keys(data.data)
                                    .length > 0) {
                                    sectionSelected.append('<option value="">Select Section</option>');
                                    $.each(data.data, function(id, name) {
                                        sectionSelected.append('<option value="' + id + '">' +
                                            name + '</option>');
                                    });
                                } else if (data.status === "error") {
                                    // Handle error message from server
                                    sectionSelected.append(
                                        '<option value="">No sections available</option>');
                                    console.warn(data.message);
                                } else {
                                    sectionSelected.append(
                                        '<option value="">No sections available</option>');
                                }
                                // Set initial section if provided
                                if (initialSectionId) {
                                    sectionSelected.val(initialSectionId);
                                }
                            },
                            complete: function() {
                                loader.hide();
                            },
                            error: function(data) {
                                sectionSelected.empty();
                                sectionSelected.append(
                                    '<option value="">No sections available</option>');
                                console.error('Error fetching sections:', data.responseJSON?.message ||
                                    data.statusText);
                            }
                        });
                    } else {
                        sectionSelected.empty();
                        sectionSelected.append('<option value="">Select Section</option>');
                    }
                }
                var selectedClassId = classSelected.val();
                if (selectedClassId) {
                    fetchSections(selectedClassId);
                }
                classSelected.change(function() {
                    var classId = $(this).val();
                    loader.show();
                    fetchSections(classId);
                });
            }
            getFeeClassSection(initialClassId, initialSectionId);


            // stdSelect.select2();
            $('#class_id').change(() => {
                stdSelect.empty().html('<option value="">No Student Found</option>');
            });
            // Initially hide the receipt table
            $('#receipt-div').hide();
            // Initialize form validation
            $('#class-section-form').validate({
                rules: {
                    date: "required",
                    class: "required",
                    section: "required",
                    std_id: "required",
                },
                messages: {
                    date: "Please select a date",
                    class: "Please select a class",
                    section: "Please select a section",
                    std_id: "Please select at least one student",
                },
            });

            var currentSession = $('#fee_current_session').val();
            $('#current_session').val(currentSession);
            $('#fee_section_id').change(function() {
                let classId = $('#class_id').val();
                let sectionId = $(this).val();
                let sessionId = $('#current_session').val();
                let stdSelect = $('#std_id');
                if (classId && sectionId && sessionId) {
                    loader.show();
                    $.ajax({
                        url: siteUrl + '/std-name-father',
                        type: 'GET',
                        dataType: 'JSON',
                        data: {
                            session_id: sessionId,
                            class_id: classId,
                            section_id: sectionId,
                        },
                        success: function(data) {
                            stdSelect.empty();
                            let allStdIds = [];
                            // Add individual student options
                            $.each(data, function(id, value) {
                                allStdIds.push(value.srno);
                                stdSelect.append('<option value="' + value.srno + '">' +
                                    value.rollno + '.' + value.student_name +
                                    '/SH. ' + value.f_name + '</option>');
                            });
                            stdSelect.prepend('<option value="' + allStdIds.join(',') +
                                '" selected>All Students</option>');
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
            });
            // Show details button click handler
            $('#show-details').click(function() {
                if ($('#class-section-form').valid()) {
                    // If the form is valid, show the table and fetch student details
                    $('#receipt-div').show();
                    $('.receipt-table').show();
                    $('.student-receipt-table').remove();
                    fetchStudentDetails();
                }
            });
            function fetchStudentDetails() {
                let sessionId = $('#current_session').val();
                let selectedStudents = $('#std_id').val();
                if (!Array.isArray(selectedStudents)) {
                    selectedStudents = [selectedStudents];
                }
                // Clear previous tables
                $('.student-receipt-table').not(':first').remove();
                selectedStudents.forEach(function(studentId, index) {
                    let classId = $('#class_id').val();
                    let sectionId = $('#fee_section_id').val();
                    loader.show();
                    $('#print-receipt').hide();
                    $('#print-receipt-div').empty(); // Clear previous content
                    $.ajax({
                        url: '{{ route('fee.fee-entry.academicFeeDueAmount') }}',
                        type: 'POST',
                        dataType: 'JSON',
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        data: {
                            srno: studentId,
                            current_session: sessionId,
                            class: classId,
                            section: sectionId,
                        },
                        success: function(response) {
                            const students = response.data;
                            let hasReceipt = false; // track if we printed any table

                            students.forEach(function(student, index) {
                                const currentSession = student.sessions.find(session =>
                                    session.session_id == sessionId && session
                                    .class_id == classId && session.section_id ==
                                    sectionId);
                                let getdate = date.val();
                                let getInfo = message.val();
                                if (currentSession) {
                                    let printed = tablePrint(student, currentSession, getdate, getInfo, selectedStudents.length);
                                    if (printed) {
                                        hasReceipt = true;
                                    }
                                    // tablePrint(student, currentSession, getdate,getInfo);
                                } else {
                                    console.error('Session not found for student:', studentId);
                                }
                            });
                            // If single student and nothing printed
                            if (!hasReceipt && selectedStudents.length === 1) {
                                $('#print-receipt-div').html('<p class="text-danger fw-bold">No dues available for this student.</p>');
                                $('#print-receipt').hide();
                            }
                        },
                        complete: function() {
                            loader.hide();
                            if ($('#print-receipt-div').find('.student-receipt').length > 0) {
                                $('#print-receipt').show();
                            }
                        },
                        error: function(xhr) {
                            console.error(xhr.responseText);
                        }
                    });
                });
            }
            // Print receipt table
            function tablePrint(student, session, date, info, totalSelected) {
                let selectedDate = new Date(date);
                let formattedDate = selectedDate.getDate() + '-' + selectedDate.toLocaleString('default', {
                    month: 'short'
                }) + '-' + selectedDate.getFullYear();
                let totalDue = (session.due_amount || 0) + (session.transport?.due_amount || 0);
                if (totalDue > 0) {
                    let tableHtml = ` <div class="student-receipt">
                                <table class="table table-border border border-black">
                                <thead>
                                    <tr>
                                        <th colspan="6" class="text-center name_f_sv border-bottom-0 pb-4 border-top-0 pt-4">${student.school}, Chirawa</th>
                                    </tr>
                                    <tr>
                                        <th colspan="7" class="border-top-0 border-bottom-0">Information:
                                            <span id="get-info" class="mx-2 fw-normal">${info}</span>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td colspan="3" class="fw-bold border-end-0 border-bottom-0 border-top-0 pb-0">Student Name:
                                            <span id="get-student-name" class="mx-2 fw-normal">${student.student_name}</span>
                                        </td>
                                        <td colspan="3" class="fw-bold border-start-0 border-bottom-0 border-top-0 pb-0">Father's Name:
                                            <span id="get-father-name" class="mx-2 fw-normal">${student.father_name}</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td colspan="3" class="fw-bold border-top-0 border-end-0 pb-0 border-bottom-0">Class:
                                            <span id="get-class" class="mx-2 fw-normal">${session.class}</span>
                                        </td>
                                        <td colspan="3" class="fw-bold border-start-0 border-top-0 pb-0 border-bottom-0">Section:
                                            <span id="get-section" class="mx-2 fw-normal">${session.section}</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td colspan="3" class="fw-bold border-top-0 border-end-0 pb-4">Date:
                                            <span id="get-date" class="mx-2 fw-normal">${formattedDate}</span>
                                        </td>
                                        <td colspan="3" class="fw-bold border-start-0 border-top-0 pb-4">  </td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold">Fee Type</td>
                                        <td class="fw-bold">Admission Fee</td>
                                        <td class="fw-bold">Ist Installment</td>
                                        <td class="fw-bold">IInd Installment</td>
                                        {{-- <td class="fw-bold">Mercy</td> --}}
                                        <td class="fw-bold">Received</td>
                                        <td colspan="2" class="fw-bold">Due</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold">Academic Fee</td>
                                        <td id="get-admission-fee">${((session.admission_date == '' || session.admission_date == null) && (session.prev_srno != '' || session.prev_srno != null)) ? 'Not Applicable' : session.admission_fee}</td>
                                        <td id="get-first-inst-fee">${session.inst_1}</td>
                                        <td id="get-second-inst-fee">${session.inst_2}</td>
                                        {{-- <td id="get-mercy-fee"></td> --}}
                                        <td id="get-total-fee">${session.paid_amount}</td>
                                        <td id="get-due-fee" colspan="2">${session.due_amount}</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold">Transport Fee</td>
                                        <td></td>
                                        <td id="get-trans-first-inst">${session.transport.inst_1}</td>
                                        <td id="get-trans-second-inst">${session.transport.inst_2}</td>
                                        {{-- <td id="get-trans-mercy"></td> --}}
                                        <td id="get-trans-total-fee">${session.transport.paid_amount}</td>
                                        <td id="get-trans-due-fee" colspan="2">${session.transport.due_amount}</td>
                                    </tr>
                                    <tr>
                                        <td colspan="5" class="fw-bold">Total</td>
                                        <td>${session.due_amount + session.transport.due_amount}</td>
                                    </tr>
                                </tbody>
                            </table> </div>`;
                    // Create a new div for each student's table
                    let tableDiv = $('<div>').addClass('student-receipt-table');
                    $('#print-receipt-div').append(tableDiv);
                    $('#print-receipt-div').show();
                    tableDiv.html(tableHtml);
                    return true; // means printed
                }
                return false; // means skipped
                /* else {
                    tableDiv.html('<p>No receipt available</p>');
                    $('#print-receipt').hide();
                } */

            }
            // Add page-break logic using CSS
            $('head').append(`
                <style>
                    /* Force each receipt to stay on its own page */
                    .page-container {
                        page-break-inside: avoid;
                        break-inside: avoid;
                    }
                    .student-receipt-table:nth-child(3n) {
                        page-break-after: always;
                    }
                    table {
                        page-break-inside: avoid;
                    }
                    @media print {
                        .receipt-table {
                            page-break-inside: avoid;
                        }
                        .student-receipt{
                            zoom: 0.85 !important;
                        }
                    }
                    
                </style>
            `);
            // Print button logic
            $('.print-receipt').click(function() {
                $('.receipt-table').print();
            });
            $('#std_id, #class_id, #fee_section_id, #date, #message').on('change', function() {
                $('#receipt-div').hide();
            });
        });
    </script>
@endsection
