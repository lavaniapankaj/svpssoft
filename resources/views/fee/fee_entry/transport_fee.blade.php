@extends('fee.index')
@section('sub-content')
    <div class="container-fluid">
        @if (Session::has('success'))
            @push('fee-swal-scripts')
                <script>
                    swal("Successful", "{{ Session::get('success') }}", "success")
                </script>
            @endpush
        @endif

        @if (Session::has('error'))
            @push('fee-swal-scripts')
                <script>
                    swal("Error", "{{ Session::get('error') }}", "error")
                </script>
            @endpush
        @endif
        <div class="row">
            <div class="col-md-12">
                <div class="card border-0 bg-white">
                    <div class="card-header flex-wrap bg-white d-flex align-items-center justify-content-between"><h5 class="mb-0 mt-0">{{ 'Transport Fee Entry' }}</h5>
                        <a href="{{ route('fee.fee-entry.index') }}" class="btn bg-light btn-sm" ><span class="mdi mdi-chevron-left me-2"></span>Back</a>
                    </div>
                    <div class="card-body">
                        <!-- Student Fee History Table -->
                        <div id="std-fee-due-table" class="table-responsive" style="display: none;">
                            <table class="table table-striped table-bordered">
                                <thead>
                                    <th>Session</th>
                                    <th>Class</th>
                                    <th>Payable Amount (₹)</th>
                                    <th>Paid Amount (₹)</th>
                                    <th>Due Amount (₹)</th>
                                    <th>Click to Submit</th>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                        <form id="class-section-form" method="POST">
                            @csrf
                            <div class="row">
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
                                    <span class="invalid-feedback form-invalid fw-bold" id="class-error" role="alert"></span>
                                </div>

                                <div class="form-group col-md-6">
                                    <label for="fee_section_id" class="mt-2">Section <span class="text-danger">*</span></label>
                                    <select name="section" id="fee_section_id" class="form-control" required>
                                        <option value="">Select Section</option>
                                    </select>
                                    <span class="invalid-feedback form-invalid fw-bold" id="section-error" role="alert"></span>

                                </div>
                            </div>
                            <div class="row">
                                <div class="form-group col-md-6">
                                    <label for="fee_student_id" class="mt-2">Student <span class="text-danger">*</span></label>
                                    <select name="std_id" id="fee_student_id" class="form-control" required>
                                        <option value="">Select Student</option>
                                    </select>
                                    <span class="invalid-feedback form-invalid fw-bold" id="std-error" role="alert"></span>
                                </div>
                                <div class="form-group col-md-3">
                                    <label for="fee_date" class="mt-2">Enter Date <span class="text-danger">*</span></label>
                                    <input type="date" name="fee_date" id="fee_date" class="form-control " value="{{ old('fee_date') }}" required>
                                    <span class="invalid-feedback form-invalid fw-bold" id="fee-date-error" role="alert"></span>
                                </div>
                            </div>
                            <div class="row">
                                <div class="form-group col-md-6">
                                    <label for="fee_mode" class="mt-2">Payment Mode <span class="text-danger">*</span></label>
                                    <select name="fee_mode" id="fee_mode" class="form-control" required>
                                        <option value="">Select Payment Mode</option>
                                        <option value="1" {{ old('fee_mode') == 1 ? 'selected' : '' }}>Cash</option>
                                        <option value="2" {{ old('fee_mode') == 2 ? 'selected' : '' }}>UPI</option>
                                        <option value="3" {{ old('fee_mode') == 3 ? 'selected' : '' }}>Bank Transfer</option>
                                        <option value="4" {{ old('fee_mode') == 4 ? 'selected' : '' }}>Other</option>
                                    </select>
                                    <span class="invalid-feedback form-invalid fw-bold" id="fee-mode-error" role="alert"></span>
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="payment_note" class="mt-2">Payment Note <span class="text-danger">*</span></label>
                                    <textarea name="payment_note" id="payment_note" class="form-control" rows="3" required>{{ old('payment_note') }}</textarea>
                                    <span class="invalid-feedback form-invalid fw-bold" id="payment-note-error" role="alert"></span>
                                </div>
                            </div>
                            <div class="row">
                                <div class="form-group col-md-6">
                                    <label for="total_amount" class="mt-2">Enter Total Amount <span class="text-danger">*</span></label>
                                    <input type="text" name="total_amount" id="total_amount" class="form-control " value="{{ old('total_amount') }}" required>
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="ref_slip" class="mt-2">Enter Ref. Slip No. <span class="text-danger">*</span></label>
                                    <input type="text" name="ref_slip" id="ref_slip" class="form-control " value="{{ old('ref_slip') }}" required>
                                    <span class="invalid-feedback form-invalid fw-bold" id="ref-slip-error" role="alert"></span>
                                </div>
                            </div>
                            <div class="mx-2 my-2 p-3 row bg-warning bg-opacity-10 border border-warning rounded">
                                <div class="row">

                                    <div class="form-group col-md-4">
                                        <input type="hidden" name="transport" value="2">
                                        <label for="first_inst_fee" class="mt-2">Ist Installment</label>
                                        <input type="text" name="first_inst_fee" id="first_inst_fee"
                                            class="form-control " value="{{ old('first_inst_fee') }}">
                                        <span class="text-danger fw-bold" id="first-inst-fee-due"></span>
                                        <span class="invalid-feedback form-invalid fw-bold" id="first-inst-fee-error" role="alert"></span>
                                    </div>
                                    <div class="form-group col-md-4">
                                        <label for="second_inst_fee" class="mt-2">IInd Installment</label>
                                        <input type="text" name="second_inst_fee" id="second_inst_fee"
                                            class="form-control " value="{{ old('second_inst_fee') }}">
                                        <span class="text-danger fw-bold" id="second-inst-fee-due"></span>
                                        <span class="invalid-feedback form-invalid fw-bold" id="second-inst-fee-error" role="alert"></span>
                                    </div>
                                </div>
                                <div class="row">

                                    <div class="form-group col-md-6">
                                        <label for="complete_fee" class="mt-2">Complete Fee</label>
                                        <input type="text" name="complete_fee" id="complete_fee" class="form-control " value="{{ old('complete_fee') }}">
                                        <span class="text-danger fw-bold" id="complete-fee-due"></span>
                                        <span class="invalid-feedback form-invalid fw-bold" id="complete-fee-error" role="alert"></span>
                                    </div>

                                </div>
                            </div>
                            <div class="mt-3 d-flex align-items-center">
                                <button type="button" id="submit-transport-fee" class="btn btn-primary me-3">
                                    <i class="mdi mdi-check-circle me-1"></i> Submit Fee Entry
                                </button>
                                <button type="button" id="reset-form-btn" class="btn btn-secondary">
                                    <i class="mdi mdi-refresh me-1"></i> Reset Form
                                </button>
                                <span class="ms-3">
                                    <img src="{{ config('myconfig.myloader') }}" alt="Loading..." class="loader" id="loader" style="display:none; width:40px;">
                                </span>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
{{-- @section('fee-scripts')
    <script>
        $(document).ready(function() {
            let initialClassId = $('#class_id').val();
            let initialSectionId = $('#initialSectionId').val();
            getClassSection(initialClassId, initialSectionId);
            var stdSelect = $('#std_id');
            var stdFeeDueTable = $('#std-fee-due-table');
            stdFeeDueTable.hide();
            stdSelect.change(function() {
                // Hide and clear table
                $('#std-fee-due-table').hide();
                $('#std-fee-due-table table tbody').html('');
                // Optionally clear fee input fields and due labels
                $('#first_inst_fee, #second_inst_fee, #complete_fee').val('');
                $('#class-error').hide().html('');
                $('#section-error').hide().html('');
                $('#session-error').hide().html('');
                $('#std-error').hide().html('');
                $('#fee-date-error').hide().html('');
                $('#ref-slip-error').hide().html('');
                $('#first-inst-fee-error').hide().html('');
                $('#second-inst-fee-error').hide().html('');
                $('#complete-fee-error').hide().html('');
                $('#not-applicable-error').hide().html();

                let session = $('#current_session').val();
                let classSelect = $('#class_id').val();
                let sectionSelect = $('#section_id').val();
                SingleStTransportFeeDue(classSelect, sectionSelect, stdSelect.val());
                stdFeeDueTable.show();
                $.ajax({
                    url: '{{ route('fee.fee-entry.academicFeeDueAmount') }}',
                    type: 'POST',
                    dataType: 'JSON',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    data: {
                        srno: stdSelect.val(),
                        current_session: session,
                        class: classSelect,
                        section: sectionSelect,
                    },

                    success: function(response) {
                        let stdHtml = '';
                        // Process student data
                        const students = response.data;
                        $.each(students, function(index, student) {
                            $.each(student.sessions, function(index, session) {
                                stdHtml += `<tr>
                                    <td>${session.session}</td>
                                    <td>${session.class}</td>
                                    <td>${session.transport.payable_amount}</td>
                                    <td>${session.transport.paid_amount}</td>
                                    <td>${session.transport.due_amount}</td>
                                    <td><a href='${session.session_id == $('#current_session').val() ? '#' :`${siteUrl}/fee/back-session-transport-fee-entry/${session.session_id}/${student.srno}/${session.class_id}/${session.section_id}`}'>Click to Submit Now</a></td>
                                </tr>`;
                            });
                        });
                        if (stdHtml == '') {
                            stdHtml = '<tr><td colspan = "6">No Student found</td></tr>';
                        }
                        $('#std-fee-due-table table tbody').html(stdHtml);

                    },
                    error: function(xhr) {
                        console.error(xhr.responseText);
                    }
                });
            });

            // Reset student dropdown and table when class or section changes
            $('#class_id, #section_id').change(function() {
                // Clear student dropdown
                $('#std_id').html('<option value="">Select Students</option>');

                // Hide and clear table
                $('#std-fee-due-table').hide();
                $('#std-fee-due-table table tbody').html('');

                // Optionally clear fee input fields and due labels
                $('#first_inst_fee, #second_inst_fee, #complete_fee').val('');
                $('#first-inst-fee-due, #second-inst-fee-due, #complete-fee-due').text('');
            });


            function SingleStTransportFeeDue(classSelect, sectionSelect, stsrno) {
                // Clear previous content
                $('#first-inst-fee-due').text('');
                $('#second-inst-fee-due').text('');
                $('#complete-fee-due').text('');
                $.ajax({
                    url: '{{ route('fee.single.st.transport.feeDue') }}',
                    type: 'POST',
                    dataType: 'JSON',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    data: {
                        std_id: stsrno,
                        class: classSelect,
                        section: sectionSelect,
                    },
                    success: function(response) {
                        // Insert new due values
                        if (response.firstInstFeeDueTotal !== undefined) {
                            $('#first-inst-fee-due').text(`Due: ₹${response.firstInstFeeDueTotal}`);
                        }
                        if (response.secondInstFeeDueTotal !== undefined) {
                            $('#second-inst-fee-due').text(`Due: ₹${response.secondInstFeeDueTotal}`);
                        }
                        if (response.completeFeeDueTotal > 0) {
                            $('#complete-fee-due').text(`Due: ₹${response.completeFeeDueTotal}`);
                        }

                    },
                    error: function(xhr) {
                        console.error(xhr.responseText);
                    }
                });
            }

        });
    </script>
@endsection --}}



@section('fee-scripts')
    <script>
        $(document).ready(function() {
            let classId = $('#fee_class_id');
            let sectionId = $('#fee_section_id');
            let studentId = $('#fee_student_id');

            let studentFeeTable = $('#std-fee-due-table');
            let studentFeeTableBody = $('#std-fee-due-table table tbody');

            // Initialize on page load
            if (classId.val() && classId.val() != '') {
                getFeeWithoutAllSections(classId.val(), function() {
                    let selectedSection = sectionId.val();
                    if (!selectedSection || selectedSection == '') {
                        sectionId.val('');
                        selectedSection = '';
                    }
                    getFeeStudentsWithoutAll(classId.val(), selectedSection);
                });
            }

            // Class change event
            classId.change(function() {
                let selectedClass = $(this).val();
                clearErrors();
                resetTables();
                resetFeeInputs();
                resetDueAmounts();
                studentId.val('').html('<option value="">Select Student</option>');
                if (selectedClass) {
                    getFeeWithoutAllSections(selectedClass, function() {
                        sectionId.val('');
                        getFeeStudentsWithoutAll(selectedClass, '');
                    });
                }
            });

            // Section change event
            sectionId.change(function() {
                clearErrors();
                resetTables();
                resetFeeInputs();
                resetDueAmounts();
                studentId.val('').html('<option value="">Select Student</option>');

                if ($(this).val() && classId.val()) {
                    getFeeStudentsWithoutAll(classId.val(), $(this).val());
                }
            });

            // Student change event
            studentId.change(function() {
                clearErrors();
                resetTables();
                resetFeeInputs();
                resetDueAmounts();

                let selectedStudent = $(this).val();
                if (selectedStudent && classId.val() && sectionId.val()) {
                    getFeeData(selectedStudent, classId.val(), sectionId.val());
                    SingleStFeeDue(classId.val(), sectionId.val(), selectedStudent);
                }
            });

            // Auto-calculate total amount
            $('#first_inst_fee, #second_inst_fee, #complete_fee').on('input', function() {
                calculateTotalAmount();
            });

            // Reset button
            $('#reset-form-btn').click(function() {
                fullFormReset();
            });

            function calculateTotalAmount() {
                const firstInstFee = parseFloat($('#first_inst_fee').val()) || 0;
                const secondInstFee = parseFloat($('#second_inst_fee').val()) || 0;
                const completeFee = parseFloat($('#complete_fee').val()) || 0;

                const total = firstInstFee + secondInstFee + completeFee;
                $('#total_amount').val(total > 0 ? total.toFixed(2) : '');
            }

            function getFeeData(st, classId, sectionId) {
                if (st === '' || classId === '' || sectionId === '') {
                    resetTables();
                    return;
                }

                $.ajax({
                    url: '{{ route("fee.fee-entry.transport.get") }}',
                    type: 'POST',
                    dataType: 'JSON',
                    headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                    data: {srno: st, class: classId, section: sectionId},
                    success: function(response) {
                        if (response.status === 'success' && response.data) {
                            displayStudentFeeData(response.data);
                        } else {
                            resetTables();
                            showError('Failed to load fee data: ' + (response.message || 'Unknown error'));
                        }
                    },
                    error: function(xhr) {
                        resetTables();
                        showError('Error loading fee data. Please try again.');
                        console.error('Fee data error:', xhr);
                    }
                });
            }

            function displayStudentFeeData(data) {
                let stdHtml = '';
                const currentSessionId = {{ Session::get('fee_current_session')->id ?? 'null' }};

                if (data.sessions && data.sessions.length > 0) {
                    $.each(data.sessions, function(index, session) {
                        const isCurrentSession = session.session_id == currentSessionId;
                        const rowClass = isCurrentSession ? 'table-warning' : '';
                        const actionLink = isCurrentSession ? '#' : `${siteUrl}/fee/back-session-transport-fee-entry/${session.session_id}/${data.srno}/${session.class_id}/${session.section_id}`;
                        const actionText = isCurrentSession ? '<span class="badge bg-warning text-dark">Current Session</span>' : `<a href="${actionLink}" class="btn btn-sm btn-primary">Click to Submit</a>`;

                        stdHtml += `<tr class="${rowClass}">
                            <td>${session.session || '-'}</td>
                            <td>${session.class || '-'}</td>
                            <td class="text-end">₹${parseFloat(session.payable_amount || 0).toFixed(2)}</td>
                            <td class="text-end">₹${parseFloat(session.paid_amount || 0).toFixed(2)}</td>
                            <td class="text-end">
                                <span style="font-weight:bold; color:${session.due_amount > 0 ? 'red' : 'green'}">
                                    ₹${parseFloat(session.due_amount || 0).toFixed(2)}
                                </span>
                            </td>
                            <td>${actionText}</td>
                        </tr>`;
                    });
                    studentFeeTableBody.html(stdHtml);
                    studentFeeTable.show();
                } else {
                    studentFeeTable.hide();
                    studentFeeTableBody.html('');
                }
            }

            function SingleStFeeDue(classSelect, sectionSelect, stsrno) {
                resetDueAmounts();

                $.ajax({
                    url: '{{ route('fee.single.st.transport.feeDue') }}',
                    type: 'POST',
                    dataType: 'JSON',
                    headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                    data: {std_id: stsrno, class: classSelect, section: sectionSelect},
                    success: function(response) {
                        if (response.firstInstFeeDueTotal !== undefined) {
                            $('#first-inst-fee-due').html(`<i class="mdi mdi-danger-outline"></i> Due: ₹${parseFloat(response.firstInstFeeDueTotal).toFixed(2)}`);
                        }
                        if (response.secondInstFeeDueTotal !== undefined) {
                            $('#second-inst-fee-due').html(`<i class="mdi mdi-danger-outline"></i> Due: ₹${parseFloat(response.secondInstFeeDueTotal).toFixed(2)}`);
                        }
                        if (response.completeFeeDueTotal > 0) {
                            $('#complete-fee-due').html(`<i class="mdi mdi-danger-outline"></i> Due: ₹${parseFloat(response.completeFeeDueTotal).toFixed(2)}`);
                        }
                    },
                    error: function(xhr) {
                        console.error('Due amount error:', xhr);
                        resetDueAmounts();
                    }
                });
            }

            function validationsCheck() {
                clearErrors();
                let isValid = true;

                // Required field validations
                const validations = [
                    {field: '#fee_class_id', error: '#class-error', message: 'Please select a class'},
                    {field: '#fee_section_id', error: '#section-error', message: 'Please select a section'},
                    {field: '#fee_student_id', error: '#std-error', message: 'Please select a student'},
                    {field: '#fee_date', error: '#fee-date-error', message: 'Please enter a fee date'},
                    {field: '#fee_mode', error: '#fee-mode-error', message: 'Please select a payment mode'},
                    {field: '#payment_note', error: '#payment-note-error', message: 'Please enter a payment note'},
                    {field: '#ref_slip', error: '#ref-slip-error', message: 'Please enter a reference slip number'}
                ];

                validations.forEach(function(validation) {
                    if (!$(validation.field).val()) {
                        $(validation.error).show().html(validation.message);
                        isValid = false;
                    }
                });

                // Total amount validation
                const totalAmount = parseFloat($('#total_amount').val()) || 0;
                if (totalAmount <= 0) {
                    $('#total-amount-error').show().html('Please enter a valid total amount');
                    isValid = false;
                }

                // Fee breakup validation
                const firstInstFee = parseFloat($('#first_inst_fee').val()) || 0;
                const secondInstFee = parseFloat($('#second_inst_fee').val()) || 0;
                const completeFee = parseFloat($('#complete_fee').val()) || 0;
                const totalFees = firstInstFee + secondInstFee + completeFee;

                if (totalFees === 0) {
                    $('#admission-fee-error').show().html('Please enter at least one fee amount');
                    isValid = false;
                } else if (totalFees > totalAmount) {
                    $('#total-amount-error').show().html(`Fee breakup total (₹${totalFees.toFixed(2)}) exceeds total amount (₹${totalAmount.toFixed(2)})`);
                    isValid = false;
                }

                return isValid;
            }

            function clearErrors() {
                $('.invalid-feedback, .text-danger.fw-bold').hide().html('');
            }

            function resetTables() {
                studentFeeTable.hide();
                studentFeeTableBody.html('');
            }

            function resetFeeInputs() {
                $('#admission_fee, #first_inst_fee, #second_inst_fee, #complete_fee, #mercy_fee, #total_amount, #payment_note, #ref_slip, #fee_date, #fee_mode').val('');
            }

            function resetDueAmounts() {
                $('#admission-fee-due, #first-inst-fee-due, #second-inst-fee-due, #complete-fee-due, #mercy-fee-due').html('');
            }

            function fullFormReset() {
                clearErrors();
                resetTables();
                resetFeeInputs();
                resetDueAmounts();
                classId.val('');
                sectionId.val('').html('<option value="">Select Section</option>');
                studentId.val('').html('<option value="">Select Student</option>');
            }

            function showError(message) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: message,
                    confirmButtonColor: '#d33'
                });
            }

            $('#submit-transport-fee').click(function(e) {
                e.preventDefault();

                if (!validationsCheck()) {
                    return;
                }

                $('#loader').show();
                $('#submit-transport-fee').prop('disabled', true).html('<i class="mdi mdi-loading mdi-spin me-1"></i> Processing...');

                let formData = {
                    transport: 2,
                    class: classId.val(),
                    section: sectionId.val(),
                    std_id: studentId.val(),
                    fee_date: $('#fee_date').val(),
                    fee_mode: $('#fee_mode').val(),
                    payment_note: $('#payment_note').val(),
                    total_amount: $('#total_amount').val(),
                    ref_slip: $('#ref_slip').val(),
                    first_inst_fee: $('#first_inst_fee').val(),
                    second_inst_fee: $('#second_inst_fee').val(),
                    complete_fee: $('#complete_fee').val(),
                };

                $.ajax({
                    url: siteUrl + '/fee/academic-fee-entry',
                    data: formData,
                    type: "POST",
                    dataType: 'JSON',
                    headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                    success: function(response) {
                        $('#loader').hide();
                        $('#submit-transport-fee').prop('disabled', false).html('<i class="mdi mdi-check-circle me-1"></i> Submit Fee Entry');

                        if (response.status == 'success') {
                            Swal.fire({
                                title: 'Success!',
                                text: response.message,
                                icon: 'success',
                                confirmButtonColor: '#28a745',
                                showCancelButton: true,
                                confirmButtonText: '<i class="mdi mdi-printer me-1"></i> Print Fee Slip',
                                cancelButtonText: 'Close'
                            }).then((result) => {
                                if (result.isConfirmed && response.print_url) {
                                    window.open(response.print_url, '_blank');
                                }
                                // Reload student data
                                getFeeData(studentId.val(), classId.val(), sectionId.val());
                                SingleStFeeDue(classId.val(), sectionId.val(), studentId.val());
                                resetFeeInputs();
                            });
                        }else {

                            // response.message could be a string OR an object with field errors
                            if (response.message && typeof response.message === 'object') {
                                // It's a validation errors object - map to form fields
                                const errors = response.message;
                                const errorMap = {
                                    'class': '#class-error',
                                    'section': '#section-error',
                                    'std_id': '#std-error',
                                    'fee_date': '#fee-date-error',
                                    'fee_mode': '#fee-mode-error',
                                    'payment_note': '#payment-note-error',
                                    'ref_slip': '#ref-slip-error',
                                    'total_amount': '#total-amount-error',
                                    'first_inst_fee': '#first-inst-fee-error',
                                    'second_inst_fee': '#second-inst-fee-error',
                                    'complete_fee': '#complete-fee-error',
                                };

                                Object.keys(errors).forEach(function(field) {
                                    if (errorMap[field]) {
                                        $(errorMap[field]).show().html(errors[field][0]);
                                    }
                                });
                            } else {
                                // It's a plain string message
                                showError('Failed to submit fee entry');
                            }
                        }
                    },
                    error: function(xhr) {
                        $('#loader').hide();
                        $('#submit-transport-fee').prop('disabled', false).html('<i class="mdi mdi-check-circle me-1"></i> Submit Fee Entry');

                        /* if (xhr.responseJSON && xhr.responseJSON.errors) {
                            const errors = xhr.responseJSON.errors;
                            const errorMap = {
                                'class': '#class-error',
                                'section': '#section-error',
                                'std_id': '#std-error',
                                'fee_date': '#fee-date-error',
                                'fee_mode': '#fee-mode-error',
                                'payment_note': '#payment-note-error',
                                'ref_slip': '#ref-slip-error',
                                'total_amount': '#total-amount-error',
                                'first_inst_fee': '#first-inst-fee-error',
                                'second_inst_fee': '#second-inst-fee-error',
                                'complete_fee': '#complete-fee-error',
                            };

                            Object.keys(errors).forEach(function(field) {
                                if (errorMap[field]) {
                                    $(errorMap[field]).show().html(errors[field][0]);
                                }
                            });
                        } else {
                            showError(xhr.responseJSON?.message || 'An error occurred while submitting the fee entry');
                        } */

                        if (xhr.responseJSON) {
                            const response = xhr.responseJSON;
                            if (response.message && typeof response.message === 'object') {
                                // Validation errors object
                                const errors = response.message;
                                const errorMap = {
                                    'class': '#class-error',
                                    'section': '#section-error',
                                    'std_id': '#std-error',
                                    'fee_date': '#fee-date-error',
                                    'fee_mode': '#fee-mode-error',
                                    'payment_note': '#payment-note-error',
                                    'ref_slip': '#ref-slip-error',
                                    'total_amount': '#total-amount-error',
                                    'first_inst_fee': '#first-inst-fee-error',
                                    'second_inst_fee': '#second-inst-fee-error',
                                    'complete_fee': '#complete-fee-error',
                                };
                                Object.keys(errors).forEach(function(field) {
                                    if (errorMap[field]) {
                                        $(errorMap[field]).show().html(errors[field][0]);
                                    }
                                });
                            } else {
                                showError(response.message || 'An error occurred');
                            }
                        } else {
                            showError('An error occurred while submitting the fee entry');
                        }
                    }
                });
            });
        });
    </script>
@endsection
