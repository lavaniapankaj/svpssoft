@extends('fee.index')
@section('sub-content')
    <div class="container-fluid">
        <div class="row ">
            <div class="col-md-12">
                <div class="card border-0 bg-white">
                    <div class="card-header flex-wrap bg-white d-flex align-items-center justify-content-between">
                        <h5 class="mb-0 mt-0">{{ 'Print Due Receipt (With Message)' }}</h5>
                        <a href="{{ route('fee.print-due-receipt') }}" class="btn bg-light btn-sm"><span class="mdi mdi-chevron-left me-2"></span>Back</a>
                    </div>
                    <div class="card-body">
                        <form id="class-section-form">
                            <div class="row">
                                <div class="form-group col-md-6">
                                    <label for="date" class="mt-2">Print Date <span class="text-danger">*</span></label>
                                    <input type="date" name="date" id="date" class="form-control" required>
                                    <span class="text-danger fw-bold" id="date-error" role="alert"></span>
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="fee_class_id" class="mt-2">Class <span class="text-danger">*</span></label>
                                    <select name="class" id="fee_class_id" class="form-control" {{ count($classes) == 0 ? 'disabled' : 'required' }}>
                                        @if (count($classes) > 0)
                                            <option value="">Select Class</option>
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
                                    <label for="message" class="mt-2">Enter Message</label>
                                    <textarea name="message" id="message" cols="30" rows="10" class="form-control"></textarea>
                                </div>
                            </div>
                            <div class="mt-3">
                                <button type="button" id="show-details" class="btn btn-primary">Show-details</button>
                                <span><img src="{{ config('myconfig.myloader') }}" alt="Loading..." class="loader" id="loader" style="display:none; width:5%;"></span>
                                <span class="invalid-feedback form-invalid fw-bold" id="total-amount-error" role="alert"></span>
                            </div>
                        </form>
                        <div id="receipt-div" style="display: none">
                            <div class="mt-2 receipt-table" id="print-receipt-div"></div>
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
            let classId = $('#fee_class_id');
            let sectionId = $('#fee_section_id');

            if (classId.val() && classId.val() != '') {
                getFeeAllSections(classId.val(), function() {
                    let selectedSection = sectionId.val();
                    if (!selectedSection || selectedSection == '') {
                        sectionId.val('all');
                        selectedSection = 'all';
                    }
                    getFeeAllStudents(classId.val(), selectedSection);
                });
            }

            classId.change(function() {
                let selectedClass = $(this).val();
                $('#receipt-div').hide();
                $('#print-receipt-div').html('');

                getFeeAllSections(selectedClass, function() {
                    sectionId.val('all');
                    getFeeAllStudents(selectedClass, 'all');
                });
            });

            sectionId.change(function() {
                $('#receipt-div').hide();
                $('#print-receipt-div').html('');
                getFeeAllStudents(classId.val(), $(this).val());
            });

            $('#fee_student_id').change(function() {
                $('#receipt-div').hide();
                $('#print-receipt-div').html('');
            });

            $('#date').val(new Date().toISOString().split('T')[0]);

            function loadFeeReport() {
                let selectedClassVal = classId.val();
                let selectedSectionVal = sectionId.val();
                let st = $('#fee_student_id').val();
                let selectedDate = $('#date').val();

                let hasError = false;

                // Reset errors
                $('#class-error, #section-error, #std-error, #date-error').text('');

                if (!selectedClassVal) {
                    $('#class-error').text('Please select a class');
                    hasError = true;
                }

                if (!selectedSectionVal) {
                    $('#section-error').text('Please select a section');
                    hasError = true;
                }

                if (!st) {
                    $('#std-error').text('Please select a student');
                    hasError = true;
                }

                if (!selectedDate) {
                    $('#date-error').text('Please select a date');
                    hasError = true;
                }

                if (hasError) {
                    return;
                }

                if (typeof loader !== 'undefined') {
                    $('#loader').show();
                }

                $.ajax({
                    url: "{{ route('fee.print-due-receipt.get') }}",
                    type: 'POST',
                    dataType: 'JSON',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    data: {
                        class: selectedClassVal,
                        section: selectedSectionVal,
                        srno: st,
                    },
                    success: function(response) {
                        if (response.status === 'success' && response.data && response.data.length > 0) {
                            $('#print-receipt-div').html('');
                            let message = $('#message').val() || '';
                            let printedCount = 0;

                            response.data.forEach(student => {
                                // Print main student receipt
                                let printed = tablePrint(student, selectedDate, message);
                                if (printed) {
                                    printedCount++;
                                }
                            });

                            if (printedCount > 0) {
                                $('#receipt-div').show();
                            } else {
                                $('#receipt-div').hide();
                            }
                        } else {
                            $('#receipt-div').hide();
                        }
                    },
                    complete: function() {
                        $('#loader').hide();
                    },
                    error: function(xhr) {
                        $('#loader').hide();
                        let errorMsg = 'Error loading data';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMsg = xhr.responseJSON.message;
                        }
                        $('#receipt-div').hide();
                    }
                });
            }

            $('#show-details').click(function() {
                loadFeeReport();
            });

            // Print receipt for main student
            function tablePrint(student, date, info) {
                    let selectedDate = new Date(date);
                    let formattedDate = selectedDate.getDate() + '-' + selectedDate.toLocaleString('default', {month: 'short'}) + '-' + selectedDate.getFullYear();

                    let academicDue = student.academic_fee.total_due || 0;
                    let transportDue = student.transport_fee.total_due || 0;
                    let totalDue = academicDue + transportDue;

                    if (totalDue <= 0) {
                        return false;
                    }

                    let isNewAdmission = student.academic_fee.admission_fee > 0;
                    let admissionFeeDisplay = isNewAdmission ? student.academic_fee.admission_fee : 'Not Applicable';

                    let tableHtml = `
                        <div class="student-receipt">
                            <table class="table table-border border border-black">
                                <thead>
                                    <tr>
                                        <th colspan="6" class="text-center name_f_sv border-bottom-0 pb-4 border-top-0 pt-4">
                                            ${student.school}, Chirawa
                                        </th>
                                    </tr>
                                    ${info ? `<tr>
                                        <th colspan="7" class="border-top-0 border-bottom-0">
                                            Information: <span id="get-info" class="mx-2 fw-normal">${info}</span>
                                        </th>
                                    </tr>` : ''}
                                </thead>
                                <tbody>
                                    <tr>
                                        <td colspan="3" class="fw-bold border-end-0 border-bottom-0 border-top-0 pb-0">
                                            Student Name: <span id="get-student-name" class="mx-2 fw-normal">${student.student_name}</span>
                                        </td>
                                        <td colspan="3" class="fw-bold border-start-0 border-bottom-0 border-top-0 pb-0">
                                            Father's Name: <span id="get-father-name" class="mx-2 fw-normal">${student.father_name}</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td colspan="3" class="fw-bold border-top-0 border-end-0 pb-0 border-bottom-0">
                                            Class: <span id="get-class" class="mx-2 fw-normal">${student.class}</span>
                                        </td>
                                        <td colspan="3" class="fw-bold border-start-0 border-top-0 pb-0 border-bottom-0">
                                            Section: <span id="get-section" class="mx-2 fw-normal">${student.section}</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td colspan="3" class="fw-bold border-top-0 border-end-0 pb-4">
                                            Date: <span id="get-date" class="mx-2 fw-normal">${formattedDate}</span>
                                        </td>
                                        <td colspan="3" class="fw-bold border-start-0 border-top-0 pb-4"></td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold">Fee Type</td>
                                        <td class="fw-bold">Admission Fee</td>
                                        <td class="fw-bold">Ist Installment</td>
                                        <td class="fw-bold">IInd Installment</td>
                                        <td class="fw-bold">Received</td>
                                        <td colspan="2" class="fw-bold">Due</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold">Academic Fee</td>
                                        <td id="get-admission-fee">${admissionFeeDisplay}</td>
                                        <td id="get-first-inst-fee">${student.academic_fee.inst_1}</td>
                                        <td id="get-second-inst-fee">${student.academic_fee.inst_2}</td>
                                        <td id="get-total-fee">${student.academic_fee.total_paid}</td>
                                        <td id="get-due-fee" colspan="2">${student.academic_fee.total_due}</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold">Transport Fee</td>
                                        <td></td>
                                        <td id="get-trans-first-inst">${student.transport_fee.inst_1}</td>
                                        <td id="get-trans-second-inst">${student.transport_fee.inst_2}</td>
                                        <td id="get-trans-total-fee">${student.transport_fee.total_paid}</td>
                                        <td id="get-trans-due-fee" colspan="2">${student.transport_fee.total_due}</td>
                                    </tr>
                                    <tr>
                                        <td colspan="5" class="fw-bold">Total</td>
                                        <td>${totalDue}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    `;

                    let tableDiv = $('<div>').addClass('student-receipt-table');
                    $('#print-receipt-div').append(tableDiv);
                    $('#print-receipt-div').show();
                    tableDiv.html(tableHtml);
                    return true;
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
                            .student-receipt {
                                zoom: 0.85 !important;
                            }
                        }
                    </style>
                `);

                // Print button logic
                $('.print-receipt').click(function() {
                    $('.receipt-table').print();
                });

                $('#fee_student_id, #fee_class_id, #fee_section_id, #date, #message').on('change', function() {
                    $('#receipt-div').hide();
                });
        });
    </script>
@endsection
