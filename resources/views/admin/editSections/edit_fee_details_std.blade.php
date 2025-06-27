@extends('admin.index')
@section('sub-content')
    <div class="container">
        @if (Session::has('success'))
            @section('scripts')
                <script>
                    swal("Successful", "{{ Session::get('success') }}", "success").then(() => {
                        $('#std-form').hide();
                        location.reload();
                    });
                </script>
            @endsection
        @endif

        @if (Session::has('error'))
            @section('scripts')
                <script>
                    swal("Error", "{{ Session::get('error') }}", "error").then(() => {
                        $('#std-form').hide();
                        location.reload();
                    });
                </script>
            @endsection
        @endif
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        {{ 'Edit Fee Entry' }}
                        <a href="{{ route('admin.editSection.index') }}" class="btn btn-warning btn-sm"
                            style="float: right;">Back</a>

                    </div>
                    <div class="card-body">
                        <form action="" method="get" id="slip-form">
                            <div class="row">
                                <div class="form-group col-md-6">
                                    <label for="fee-type" class="mt-2">Fee Type<span class="text-danger">*</span></label>
                                    <select name="fee-type" id="fee-type" class="form-control">
                                        <option value="1">Academic</option>
                                        <option value="2">Transport</option>
                                    </select>

                                </div>

                            </div>
                            <div class="row">
                                <div class="form-group col-md-6">
                                    <label for="computer-slip" class="mt-2">Academic Fee Slip Number (Computer) <span
                                            class="text-danger">*</span></label>
                                    <input type="text" name="computer_slip" id="computer-slip" class="form-control">
                                    <span class="invalid-feedback form-invalid fw-bold computer-slip-error"
                                        role="alert"></span>
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="school-slip" class="mt-2">Academic Fee Slip Number (School) <span
                                            class="text-danger">*</span></label>
                                    <input type="text" name="school_slip" id="school-slip" class="form-control">
                                    <span class="invalid-feedback form-invalid fw-bold school-slip-error"
                                        role="alert"></span>
                                </div>
                            </div>
                            <div class="mt-3">
                                <button type="button" id="show-details" class="btn btn-primary">
                                    Show Details</button>
                                <span class="invalid-feedback form-invalid fw-bold show-details-error"
                                    role="alert"></span>
                            </div>
                        </form>

                        <form id="class-section-form" method="POST">
                            @csrf
                            <div class="row">
                                <div class="form-group col-md-6">
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

                                </div>


                                <div class="form-group col-md-6">
                                    <label for="section_id" class="mt-2">Section <span
                                            class="text-danger">*</span></label>
                                    <input type="hidden" id="initialSectionId"
                                        value="{{ old('section', request()->get('section_id') !== null ? request()->get('section_id') : '') }}">
                                    <select name="section" id="section_id" class="form-control  " required>
                                        <option value="">Select Section</option>
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
                                    <label for="std_id" class="mt-2">Student <span class="text-danger">*</span></label>
                                    <select name="std_id" id="std_id" class="form-control " required>
                                        <option value="">Select Students</option>
                                    </select>
                                    <span class="invalid-feedback form-invalid fw-bold" id="std-error"
                                        role="alert"></span>
                                    <img src="{{ config('myconfig.myloader') }}" alt="Loading..." class="loader" id="loader" style="display:none; width:10%;">
                                </div>


                                <div class="form-group col-md-6">
                                    <label for="fee_date" class="mt-2">Enter Date <span
                                            class="text-danger">*</span></label>
                                    <input type="date" name="fee_date" id="fee_date" class="form-control "
                                        value="{{ old('fee_date)') }}" required>
                                    <span class="invalid-feedback form-invalid fw-bold" id="fee-date-error"
                                        role="alert"></span>
                                </div>
                            </div>
                            <div class="row">
                                <div class="form-group col-md-6">
                                    <label for="total_amount" class="mt-2">Enter Total Amount <span
                                            class="text-danger">*</span></label>
                                    <input type="text" name="total_amount" id="total_amount" class="form-control "
                                        value="{{ old('total_amount') }}" required>
                                    {{-- <span class="invalid-feedback form-invalid fw-bold" id="total-amount-error" role="alert"></span> --}}
                                </div>


                                <div class="form-group col-md-6">
                                    <label for="ref_slip" class="mt-2">Enter Ref. Slip No. <span
                                            class="text-danger">*</span></label>
                                    <input type="text" name="ref_slip" id="ref_slip" class="form-control "
                                        value="{{ old('ref_slip') }}" required>
                                    <span class="invalid-feedback form-invalid fw-bold" id="ref-slip-error"
                                        role="alert"></span>
                                </div>
                            </div>

                            <div class="mx-2 my-2 p-3 row bg-warning bg-opacity-10 border border-warning rounded">
                                <div class="row">

                                    <div class="form-group col-md-4 admission-div">
                                        <label for="admission_fee" class="mt-2">Admission Fee</label>
                                        <input type="text" name="admission_fee" id="admission_fee"
                                            class="form-control " value="{{ old('admission_fee') }}">
                                        <span class="invalid-feedback form-invalid fw-bold" id="admission-fee-error"
                                            role="alert"></span>
                                    </div>
                                    <div class="form-group col-md-4">
                                        <label for="first_inst_fee" class="mt-2">Ist Installment</label>
                                        <input type="text" name="first_inst_fee" id="first_inst_fee"
                                            class="form-control " value="{{ old('first_inst_fee') }}">
                                        <span class="invalid-feedback form-invalid fw-bold" id="first-inst-fee-error"
                                            role="alert"></span>
                                    </div>
                                    <div class="form-group col-md-4">
                                        <label for="second_inst_fee" class="mt-2">IInd Installment</label>
                                        <input type="text" name="second_inst_fee" id="second_inst_fee"
                                            class="form-control " value="{{ old('second_inst_fee') }}">
                                        <span class="invalid-feedback form-invalid fw-bold" id="second-inst-fee-error"
                                            role="alert"></span>
                                    </div>
                                </div>
                                <div class="row">

                                    <div class="form-group col-md-6">
                                        <input type="hidden" name="session" id="session"
                                            value="{{ old('session') }}">
                                        <input type="hidden" name="cSlip" id="cSlip"
                                            value="{{ old('cSlip') }}">
                                        <input type="hidden" name="transport" id="transport"
                                            value="{{ old('transport') }}">
                                        <label for="complete_fee" class="mt-2">Complete Fee</label>
                                        <input type="text" name="complete_fee" id="complete_fee"
                                            class="form-control " value="{{ old('complete_fee') }}">
                                        <span class="invalid-feedback form-invalid fw-bold" id="complete-fee-error"
                                            role="alert"></span>
                                    </div>
                                    {{-- <div class="form-group col-md-6">
                                        <label for="mercy_fee" class="mt-2">Mercy Fee</label>
                                        <input type="text" name="mercy_fee" id="mercy_fee" class="form-control "
                                            value="{{ old('mercy_fee') }}">
                                        <span class="invalid-feedback form-invalid fw-bold" id="mercy-fee-error"
                                            role="alert"></span>
                                    </div> --}}
                                </div>
                            </div>

                            <div class="mt-3">
                                <button type="button" id="submit-fee" class="btn btn-primary">
                                    Submit</button>
                                <span class="invalid-feedback form-invalid fw-bold" id="total-amount-error"
                                    role="alert"></span><span class="invalid-feedback form-invalid fw-bold"
                                    id="not-applicable-error" role="alert"></span>
                            </div>

                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('admin-scripts')
    <script>
        $(document).ready(function() {
            let initialSectionId = $('#section_id').val();
            let classSelected = $('#class_id');
            let sectionSelected = $('#section_id');
            $('#std-form').hide();
            $('#class-section-form').hide();
            getClassSection(classSelected.val(), initialSectionId);
            function getStdDropdown(classId, sectionId, selectedStudentId) {
                let sessionId = $('#current_session').val();
                let stdSelect = $('#std_id');

                if (classId && sectionId && sessionId) {
                    loader.show();
                    $.ajax({
                        url: siteUrl + '/std-name-father',
                        type: 'GET',
                        dataType: 'JSON',
                        data: {
                            class_id: classId,
                            section_id: sectionId,
                            session_id: sessionId,
                        },
                        success: function(students) {
                            stdSelect.empty();
                            let options = '<option value="">Select Students</option>';

                            if (students.length > 0) {
                                $.each(students, function(index, student) {
                                    options += '<option value="' + student.srno + '">' +
                                        student.rollno + '. ' + student.student_name +
                                        '/SH. ' + student.f_name + '</option>';
                                });
                            } else {
                                options += '<option value="">No students found</option>';
                            }
                            stdSelect.html(options);

                            // Set the selected student if provided
                            if (selectedStudentId) {
                                stdSelect.val(selectedStudentId);
                            }
                        },
                        complete: function() {
                            loader.hide();
                        },
                        error: function(xhr) {
                            console.error(xhr.responseText);
                        }
                    });
                } else {
                    stdSelect.empty();
                    stdSelect.append('<option value="">Select Students</option>');
                }
            }
            $('#section_id').change(function() {
                let classId = $('#class_id').val();
                let sectionId = $('#section_id').val();
                getStdDropdown(classId, sectionId);
            });

            $('#show-details').click(function() {
                let transport = $('#fee-type').val();
                let computerSlip = $('#computer-slip').val();
                let schoolSlip = $('#school-slip').val();
                let sessionId = $('#current_session').val();
                $('.show-details-error').hide().html('');
                if (transport == 2) {
                    $('.admission-div').remove();
                }else if (transport == 1) {
                    $('.admission-div').show();
                }
                $.ajax({
                    url: '{{ route('admin.editSection.getStdFeeInfo1') }}',
                    type: 'GET',
                    dataType: 'JSON',
                    data: {
                        transport: transport,
                        computer_slip: computerSlip,
                        school_slip: schoolSlip,
                        session: sessionId,
                    },
                    success: function(response) {
                        // Select the class
                        $('#class-section-form').show();
                        $('#cSlip').val(computerSlip);
                        $('#transport').val(transport);
                        classSelected.val(response.class).change();
                        // Fetch sections with the selected section id
                        getClassSection(response.class, response.section);
                        getStdDropdown(response.class, response.section);
                        $.each(response.data, function(index, st) {
                            getStdDropdown(response.class, response.section, st.srno);
                            $('#session').val(st.session_id);
                            if (st.fee_of == 1 && st.academic_trans == 1) {
                                $('#admission_fee').val(st.amount);
                            }
                            if (st.fee_of == (st.academic_trans == 1 ? 2 : 1) && st.paid_mercy == 1) {
                                $('#first_inst_fee').val(st.amount);
                            }
                            if (st.fee_of == (st.academic_trans == 1 ? 3 : 2) && st.paid_mercy == 1) {
                                $('#second_inst_fee').val(st.amount);
                            }
                            if (st.fee_of == (st.academic_trans == 1 ? 4 : 3) && st.paid_mercy == 1) {
                                $('#complete_fee').val(st.amount);
                            }
                        });
                    },
                    complete: function() {
                        loader.hide();
                    },
                    error: function(xhr, status, error) {
                        $('.show-details-error').hide().html('');
                        $('#class-section-form').hide();

                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            let message = xhr.responseJSON.message;

                            if (typeof message === 'object') {
                                // Handle multiple error messages
                                let errorMessage = '';
                                for (let key in message) {
                                    if (message.hasOwnProperty(key)) {
                                        errorMessage += message[key] + '<br>';
                                    }
                                }
                                $('.show-details-error').show().html(errorMessage);
                            } else {
                                // Handle single error message
                                $('.show-details-error').show().html(message);
                            }
                        } else {
                            // Generic error message if no specific message is available
                            $('.show-details-error').show().html(
                                'An error occurred. Please try again.');
                        }

                        console.error('Error:', xhr.responseText);
                    },
                });
            });

            $('#submit-fee').click(function(e) {
                if ($('#class-section-form').valid()) {
                    e.preventDefault();
                    const totalAmount = parseFloat($('#total_amount').val()) || 0;
                    const admissionFee = parseFloat($('#admission_fee').val()) || 0;
                    const firstInstFee = parseFloat($('#first_inst_fee').val()) || 0;
                    const secondInstFee = parseFloat($('#second_inst_fee').val()) || 0;
                    const completeFee = parseFloat($('#complete_fee').val()) || 0;
                    const mercyFee = parseFloat($('#mercy_fee').val()) || 0;
                    const total = admissionFee + firstInstFee + secondInstFee + completeFee + mercyFee;

                    if (total > totalAmount) {
                        $('#total-amount-error').show().html(
                            'You have entered an amount greater than the total amount');
                    } else {
                        $('#total-amount-error').hide().html('');
                        $.ajax({
                            data: $('#class-section-form').serialize(),
                            url: '{{ route('admin.editSection.editStdFeeStore') }}',
                            // url: siteUrl + '/fee/academic-fee-entry',
                            type: "POST",
                            dataType: 'JSON',
                            success: function(data) {
                                console.log(data);
                                Swal.fire({
                                    title: 'Successful',
                                    text: data.message,
                                    icon: 'success',
                                    confirmButtonColor: 'rgb(122 190 255)',
                                }).then(() => {
                                    location.reload();
                                });
                            },
                            error: function(data) {
                                console.log(data);
                                var message = data.responseJSON.message;

                                $('#class-error').hide().html('');
                                $('#section-error').hide().html('');
                                $('#session-error').hide().html('');
                                $('#std-error').hide().html('');
                                $('#fee-date-error').hide().html('');
                                $('#ref-slip-error').hide().html('');
                                $('#admission-fee-error').hide().html('');
                                $('#first-inst-fee-error').hide().html('');
                                $('#second-inst-fee-error').hide().html('');
                                $('#complete-fee-error').hide().html('');
                                $('#mercy-fee-error').hide().html('');
                                // $('#not-applicable-error').hide().html();

                                if (message.class_id) {
                                    $('#class-error').show().html(message.class_id);
                                }
                                if (message.section_id) {
                                    $('#section-error').show().html(message.section_id);
                                }
                                if (message.std_id) {
                                    $('#std-error').show().html(message.std_id);

                                }
                                if (message.fee_date) {
                                    $('#fee-date-error').show().html(message.fee_date);
                                }
                                if (message.ref_slip) {
                                    $('#ref-slip-error').show().html(message.ref_slip);
                                }
                                if (message.admission_fee) {
                                    $('#admission-fee-error').show().html(message
                                        .admission_fee);
                                }
                                if (message.first_inst_fee) {
                                    $('#first-inst-fee-error').show().html(message
                                        .first_inst_fee);
                                }
                                if (message.second_inst_fee) {
                                    $('#second-inst-fee-error').show().html(message
                                        .second_inst_fee);
                                }
                                if (message.complete_fee) {
                                    $('#complete-fee-error').show().html(message
                                        .complete_fee);
                                }
                                if (message.mercy_fee) {
                                    $('#mercy-fee-error').show().html(message.mercy_fee);
                                }

                                if (message == 'No Transport Fee Applicable For This Student.') {
                                    $('#total-amount-error').show().html(message);
                                }
                            }
                        });
                    }


                }
            });
        });
    </script>
@endsection
