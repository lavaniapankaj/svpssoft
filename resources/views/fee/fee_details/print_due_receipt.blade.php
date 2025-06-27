@extends('fee.index')
@section('sub-content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-14">
                <div class="card">
                    <div class="card-header">
                        {{ 'Print Due Receipt (With Message)' }}
                        <a href="{{ route('fee.print-due-receipt') }}" class="btn btn-warning btn-sm"
                            style="float: right;">Back</a>

                    </div>
                    <div class="card-body">


                        <form id="class-section-form">
                            <div class="row">
                                <div class="form-group col-md-4">
                                    <label for="date" class="mt-2">Date <span class="text-danger">*</span></label>
                                    <input type="date" name="date" id="date" class="form-control" required>
                                </div>
                                <div class="form-group col-md-4">
                                    <label for="class_id" class="mt-2">Class <span class="text-danger">*</span></label>
                                    <select name="class" id="class_id" class="form-control " required>
                                        <option value="">Select Class</option>
                                        @if (count($classes) > 0)
                                            @foreach ($classes as $key => $class)
                                                <option value="{{ $key }}" {{ old('class') == $key ? 'selected' : ''}}>{{ $class }}</option>
                                            @endforeach
                                        @else
                                        <option value="">No Class Found</option>
                                        @endif
                                    </select>
                                    <span class="invalid-feedback form-invalid fw-bold" id="class-error"
                                        role="alert"></span>
                                    <img src="{{ config('myconfig.myloader') }}" alt="Loading..." class="loader"
                                        id="loader" style="display:none; width:10%;">
                                </div>


                                <div class="form-group col-md-4">
                                    <label for="section_id" class="mt-2">Section <span
                                            class="text-danger">*</span></label>
                                    <input type="hidden" id="initialSectionId"
                                        value="{{ old('section') }}">
                                    <select name="section" id="section_id" class="form-control  " required>
                                        <option value="">Select Section</option>
                                    </select>
                                    <span class="invalid-feedback form-invalid fw-bold" id="section-error"
                                        role="alert"></span>
                                </div>
                            </div>
                            <div class="row">
                                <div class="form-group col-md-6">
                                    <input type="hidden" name="current_session" value='' id="current_session">
                                    <label for="std_id" class="mt-2">Student <span class="text-danger">*</span></label>
                                    <select name="std_id[]" id="std_id" class="form-control" multiple required>
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
                                <button type="button" id="show-details" class="btn btn-primary">
                                    Show-details</button>
                                <span class="invalid-feedback form-invalid fw-bold" id="total-amount-error"
                                    role="alert"></span>
                            </div>

                        </form>

                        <div id="receipt-div">

                            <div class="mt-2 receipt-table" id="print-receipt-div">
                            </div>
                            <div class="mt-3">
                                <button type="button" id="print-receipt" class="btn btn-primary print-receipt">Print Receipt</button>
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
            getClassSection(initialClassId, initialSectionId);
            stdSelect.select2();
            $('#class_id').change(()=>{
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
                    let sectionId = $('#section_id').val();
                    $.ajax({
                        url: '{{ route('fee.fee-entry.academicFeeDueAmount') }}',
                        type: 'GET',
                        dataType: 'JSON',
                        data: {
                            srno: studentId,
                            current_session: sessionId,
                            class: classId,
                            section: sectionId,
                        },
                        success: function(response) {
                            const students = response.data;
                            students.forEach(function(student,index){

                                const currentSession = student.sessions.find(session =>
                                    session.session_id == sessionId && session.class_id == classId && session.section_id == sectionId);
                                let getdate = date.val();
                                let getInfo = message.val();

                                if (currentSession) {
                                   tablePrint(student, currentSession, getdate, getInfo);
                                } else {
                                    console.error('Session not found for student:', studentId);
                                }
                            });
                        },
                        error: function(xhr) {
                            console.error(xhr.responseText);
                        }
                    });
                });
            }
            // Print receipt table
            function tablePrint(student, session, date, info) {
                let selectedDate = new Date(date);
                let formattedDate = selectedDate.getDate() + '-' +
                    selectedDate.toLocaleString('default', {
                        month: 'short'
                    }) + '-' +
                    selectedDate.getFullYear();
                let tableHtml = ` <div class="student-receipt">
                                <table class="table table-border border border-black">
                                <thead>
                                    <tr>
                                        <th colspan="7" class="text-center">${student.school}, Chirawa</th>
                                    </tr>
                                    <tr>
                                        <th colspan="7">Date:-<span id="get-date" class="mx-2 fw-normal">${formattedDate}</span></th>
                                    </tr>
                                    <tr>
                                        <th colspan="7">Information:
                                            <span id="get-info" class="mx-2 fw-normal">${info}</span>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <th colspan="3">Student Name:
                                            <span id="get-student-name" class="mx-2 fw-normal">${student.student_name}</span>
                                        </th>
                                        <th colspan="3">Father's Name:
                                            <span id="get-father-name" class="mx-2 fw-normal">${student.father_name}</span>
                                        </th>
                                    </tr>
                                    <tr>
                                        <th colspan="3">Class:
                                            <span id="get-class" class="mx-2 fw-normal">${session.class}</span>
                                        </th>
                                        <th colspan="3">Section:
                                            <span id="get-section" class="mx-2 fw-normal">${session.section}</span>
                                        </th>
                                    </tr>
                                    <tr>
                                        <th></th>
                                        <th>Admission Fee</th>
                                        <th>Ist Installment</th>
                                        <th>IInd Installment</th>
                                        {{-- <th>Mercy</th> --}}
                                        <th>Received</th>
                                        <th colspan="2">Due</th>
                                    </tr>
                                    <tr>
                                        <th>Academic Fee</th>
                                        <td id="get-admission-fee">${((session.admission_date == '' || session.admission_date == null) && (session.prev_srno != '' || session.prev_srno != null)) ? 'Not Applicable' : session.admission_fee}</td>
                                        <td id="get-first-inst-fee">${session.inst_1}</td>
                                        <td id="get-second-inst-fee">${session.inst_2}</td>
                                        {{-- <td id="get-mercy-fee"></td> --}}
                                        <td id="get-total-fee">${session.paid_amount}</td>
                                        <td id="get-due-fee" colspan="2">${session.due_amount}</td>
                                    </tr>
                                    <tr>
                                        <th>Transport Fee</th>
                                        <td></td>
                                        <td id="get-trans-first-inst">${session.transport.inst_1}</td>
                                        <td id="get-trans-second-inst">${session.transport.inst_2}</td>
                                        {{-- <td id="get-trans-mercy"></td> --}}
                                        <td id="get-trans-total-fee">${session.transport.paid_amount}</td>
                                        <td id="get-trans-due-fee" colspan="2">${session.transport.due_amount}</td>
                                    </tr>
                                    <tr>
                                        <th>Books & Stationary</th>
                                        <td></td>
                                        <td>0</td>
                                        <td>0</td>
                                        <td>0</td>
                                        {{-- <td>0</td> --}}
                                        <td colspan="2">0</td>
                                    </tr>
                                </tbody>
                            </table> </div>`;


                // Create a new div for each student's table
                let tableDiv = $('<div>').addClass('student-receipt-table');
                $('#print-receipt-div').append(tableDiv);
                $('#print-receipt-div').show();
                tableDiv.html(tableHtml);


            }

           // Add page-break logic using CSS
            $('head').append(`
                <style>
                    /* Force each receipt to stay on its own page */
                    .page-container {
                        page-break-inside: avoid;
                        break-inside: avoid;
                    }
                    .student-receipt-table:nth-child(4n) {
                        page-break-after: always;
                    }
                    table {
                        page-break-inside: avoid;
                    }
                    @media print {
                        .receipt-table {
                            page-break-inside: avoid;
                        }
                    }
                </style>
            `);

            // Print button logic
            $('.print-receipt').click(function () {
                $('.receipt-table').print();
            });
            $('#std_id, #class_id, #section_id, #date, #message').on('change', function() {
                $('#receipt-div').hide();

            });


        });
    </script>
@endsection
