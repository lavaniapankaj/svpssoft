@extends('admin.index')
@section('sub-content')
    <div class="container-fluid">
        <div class="row ">
            <div class="col-md-12">
                <div class="card border-0 bg-white">
                    <div class="card-header flex-wrap bg-white d-flex align-items-center justify-content-between"><h5 class="mb-0 mt-0">{{ 'Edit Fee Entry' }}</h5>
                        <a href="{{ route('admin.editSection.index') }}" class="btn bg-light btn-sm" ><span class="mdi mdi-chevron-left me-2"></span>Back</a>
                    </div>
                    <div class="card-body">
                        <form action="" method="get" id="slip-form">
                            <div class="row">
                                <div class="form-group col-md-6">
                                    <label for="fee-type" class="mt-2">Fee Type<span class="text-danger">*</span></label>
                                    <select name="fee-type" id="fee-type" class="form-control" required>
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
                                    <span class="invalid-feedback form-invalid fw-bold computer-slip-error" role="alert"></span>
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="school-slip" class="mt-2">Academic Fee Slip Number (School) <span class="text-danger">*</span></label>
                                    <input type="text" name="school_slip" id="school-slip" class="form-control">
                                    <span class="invalid-feedback form-invalid fw-bold school-slip-error" role="alert"></span>
                                </div>
                            </div>
                            <div class="mt-3">
                                <button type="button" id="show-details" class="btn btn-primary">Show Details</button>
                                <span class="invalid-feedback form-invalid fw-bold show-details-error" role="alert"></span>
                            </div>
                        </form>
                        <form id="edit_fee_form" method="POST">
                            @csrf
                            <div class="row">
                                <div class="form-group col-md-6">
                                    <label for="edit_class_id" class="mt-2">Class <span class="text-danger">*</span></label>
                                    <select name="class" id="edit_class_id" class="form-control " required>
                                        <option value="">Select Class</option>
                                        @if (count($classes) > 0)
                                            @foreach ($classes as $key => $class)
                                                <option value="{{ $key }}" {{ old('class') == $key ? 'selected' : ''}}>{{ $class }}</option>
                                            @endforeach
                                        @else
                                            <option value="">No Class Found</option>
                                        @endif
                                    </select>
                                    <span class="invalid-feedback form-invalid fw-bold" id="class-error" role="alert"></span>
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="edit_section_id" class="mt-2">Section <span class="text-danger">*</span></label>
                                    <select name="section" id="edit_section_id" class="form-control  " required>
                                        <option value="">Select Section</option>
                                    </select>
                                    <span class="invalid-feedback form-invalid fw-bold" id="section-error" role="alert"></span>
                                </div>
                            </div>
                            <div class="row">
                                <div class="form-group col-md-6">
                                    <label for="edit_fee_st" class="mt-2">Student <span class="text-danger">*</span></label>
                                    <select name="edit_fee_st" id="edit_fee_st" class="form-control " required>
                                        <option value="">Select Students</option>
                                    </select>
                                    <span class="invalid-feedback form-invalid fw-bold" id="std-error" role="alert"></span>
                                    <img src="{{ config('myconfig.myloader') }}" alt="Loading..." class="loader" id="loader" style="display:none; width:5%;">
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="fee_date" class="mt-2">Enter Date <span class="text-danger">*</span></label>
                                    <input type="date" name="fee_date" id="fee_date" class="form-control" value="{{ old('fee_date)') }}" required>
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
                                    <label for="payment_note" class="mt-2">Payment Note</label>
                                    <textarea name="payment_note" id="payment_note" class="form-control" rows="3">{{ old('payment_note') }}</textarea>
                                    <span class="invalid-feedback form-invalid fw-bold" id="payment-note-error" role="alert"></span>
                                </div>
                            </div>
                            <div class="row">
                                <div class="form-group col-md-6">
                                    <label for="total_amount" class="mt-2">Enter Total Amount <span class="text-danger">*</span></label>
                                    <input type="text" name="total_amount" id="total_amount" class="form-control" value="{{ old('total_amount') }}" required>
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="ref_slip" class="mt-2">Enter Ref. Slip No. <span class="text-danger">*</span></label>
                                    <input type="text" name="ref_slip" id="ref_slip" class="form-control" value="{{ old('ref_slip') }}" required>
                                    <span class="invalid-feedback form-invalid fw-bold" id="ref-slip-error" role="alert"></span>
                                </div>
                            </div>
                            <div class="mx-2 my-2 p-3 row bg-warning bg-opacity-10 border border-warning rounded">
                                <div class="row">
                                    <div class="form-group col-md-4 admission-div">
                                        <label for="admission_fee" class="mt-2">Admission Fee</label>
                                        <input type="text" name="admission_fee" id="admission_fee" class="form-control " value="{{ old('admission_fee') }}">
                                        <span class="invalid-feedback form-invalid fw-bold" id="admission-fee-error" role="alert"></span>
                                    </div>
                                    <div class="form-group col-md-4">
                                        <label for="first_inst_fee" class="mt-2">Ist Installment</label>
                                        <input type="text" name="first_inst_fee" id="first_inst_fee" class="form-control " value="{{ old('first_inst_fee') }}">
                                        <span class="invalid-feedback form-invalid fw-bold" id="first-inst-fee-error" role="alert"></span>
                                    </div>
                                    <div class="form-group col-md-4">
                                        <label for="second_inst_fee" class="mt-2">IInd Installment</label>
                                        <input type="text" name="second_inst_fee" id="second_inst_fee" class="form-control " value="{{ old('second_inst_fee') }}">
                                        <span class="invalid-feedback form-invalid fw-bold" id="second-inst-fee-error" role="alert"></span>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="form-group col-md-6">
                                        <input type="hidden" name="cSlip" id="cSlip" value="{{ old('cSlip') }}">
                                        <input type="hidden" name="transport" id="transport" value="{{ old('transport') }}">
                                        <label for="complete_fee" class="mt-2">Complete Fee</label>
                                        <input type="text" name="complete_fee" id="complete_fee" class="form-control " value="{{ old('complete_fee') }}">
                                        <span class="invalid-feedback form-invalid fw-bold" id="complete-fee-error" role="alert"></span>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-3">
                                <button type="button" id="submit-fee" class="btn btn-primary">Submit</button>
                                <span class="invalid-feedback form-invalid fw-bold" id="total-amount-error" role="alert"></span><span class="invalid-feedback form-invalid fw-bold" id="not-applicable-error" role="alert"></span>
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
            $('#edit_fee_form').hide();
            let classSelected = $('#edit_class_id');
            let sectionSelected = null;
            let stSelected = null;
            /* on change Fee type please hide the std-form and edit_fee_form */
            $('#fee-type').change(function() {
                $('#edit_fee_form').hide();
                $('.show-details-error').hide().html('');
            });

            /* Get section */
            function getSection(classId, selectedSectionId = null) {
                let sectionOptions = $('#edit_section_id');
                if (classId) {
                    loader.show();
                    $.ajax({
                        url: '{{ route('sections.get') }}',
                        type: 'GET',
                        dataType: 'JSON',
                        data: {
                            class_id: classId,
                        },
                        success: function(sections) {
                            if (sections.data && Object.keys(sections.data).length > 0) {
                                sectionOptions.empty();
                                let options = '<option value="">Select Section</option>';
                                $.each(sections.data, function(id, name) {
                                    options += '<option value="' + id + '">' + name + '</option>';
                                });
                                sectionOptions.html(options);

                                // Set the selected section if provided
                                if (selectedSectionId) {
                                    sectionOptions.val(selectedSectionId);
                                }
                            } else {
                                sectionOptions.empty();
                                sectionOptions.append('<option value="">No sections found</option>');
                            }
                        },

                        complete: function() {
                            loader.hide();
                        },
                        error: function(xhr) {
                            sectionOptions.empty();
                            sectionOptions.append('<option value="">No sections found</option>');
                        }
                    });
                } else {
                    sectionOptions.empty();
                    sectionOptions.append('<option value="">Select Section</option>');
                }
            }
            /* Get Student */
            function getStdDropdown(classId, sectionId, selectedStudentId) {
                let stdSelect = $('#edit_fee_st');
                if (classId && sectionId) {
                    loader.show();
                    $.ajax({
                        url: '{{ route('getStdForDropDown') }}',
                        type: 'GET',
                        dataType: 'JSON',
                        data: {
                            class_id: classId,
                            section_id: sectionId,
                        },
                        success: function(students) {
                            if (students.data && students.data.length > 0) {
                                stdSelect.empty();
                                let options = '<option value="">Select Students</option>';
                                $.each(students.data, function(index, student) {
                                    options += '<option value="' + student.srno + '">' + student.display_name + '</option>';
                                });
                                stdSelect.html(options);
                                // Set the selected student if provided
                                if (selectedStudentId) {
                                    stdSelect.val(selectedStudentId);
                                }
                            } else {
                                stdSelect.empty();
                                stdSelect.append('<option value="">No students found</option>');
                            }
                        },
                        complete: function() {
                            loader.hide();
                        },
                        error: function(xhr) {
                            stdSelect.empty();
                            stdSelect.append('<option value="">No students found</option>');
                        }
                    });
                } else {
                    stdSelect.empty();
                    stdSelect.append('<option value="">Select Students</option>');
                }
            }

            $('#show-details').click(function() {
                loader.show();
                let transport = $('#fee-type').val();
                let computerSlip = $('#computer-slip').val();
                let schoolSlip = $('#school-slip').val();

                if (!computerSlip && !schoolSlip) {
                    $('.show-details-error').show().html('Please enter at least one slip number.');
                    loader.hide();
                    return;
                }
                if (computerSlip && schoolSlip) {
                    $('.show-details-error').show().html('Please enter only one slip number.');
                    loader.hide();
                    return;
                }
                // let sessionId = $('#current_session').val();
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
                    },
                    success: function(response) {
                        $('#edit_fee_form').show();
                        if(response.class || response.section) {
                            classSelected.val(response.class);
                            sectionSelected = response.section ? response.section : null;
                            if(sectionSelected) {
                                getSection(response.class, sectionSelected);
                            }
                        }
                        if(response.srno) {
                            stSelected = response.srno;
                            getStdDropdown(response.class, response.section, stSelected);
                        }
                        $('#transport').val(transport);
                        if (response.data.length > 0) {
                            $.each(response.data, function(index, st) {
                                $('#cSlip').val(st.recp_no);
                                $('#fee_date').val(st.pay_date);
                                $('#fee_mode').val(st.fee_mode);
                                $('#payment_note').val(st.payment_note);
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
                        }
                    },
                    complete: function() {
                        loader.hide();
                    },
                    error: function(xhr, status, error) {
                        $('.show-details-error').hide().html('');
                        $('#edit_fee_form').hide();
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
                            $('.show-details-error').show().html('An error occurred. Please try again.');
                        }
                    },
                });
            });

            $('#edit_section_id').change(function() {
                let classId = $('#edit_class_id').val();
                let sectionId = $('#edit_section_id').val();
                sectionSelected = null;
                stSelected = null;
                getStdDropdown(classId, sectionId);
            });
            /* On change class */
            $('#edit_class_id').change(function() {
                let classId = $(this).val();
                sectionSelected = null;
                stSelected = null;
                getSection(classId, null);
                $('#edit_fee_st').empty().append('<option value="">Select Students</option>');
            });
            $('#submit-fee').click(function(e) {
                if ($('#edit_fee_form').valid()) {
                    e.preventDefault();
                    const totalAmount = parseFloat($('#total_amount').val()) || 0;
                    const admissionFee = parseFloat($('#admission_fee').val()) || 0;
                    const firstInstFee = parseFloat($('#first_inst_fee').val()) || 0;
                    const secondInstFee = parseFloat($('#second_inst_fee').val()) || 0;
                    const completeFee = parseFloat($('#complete_fee').val()) || 0;
                    const mercyFee = parseFloat($('#mercy_fee').val()) || 0;
                    const total = admissionFee + firstInstFee + secondInstFee + completeFee + mercyFee;
                    if (total > totalAmount) {
                        $('#total-amount-error').show().html('You have entered an amount greater than the total amount');
                    } else {
                        $('#total-amount-error').hide().html('');
                        $.ajax({
                            data: $('#edit_fee_form').serialize(),
                            url: '{{ route('admin.editSection.editStdFeeStore') }}',
                            type: "POST",
                            dataType: 'JSON',
                            success: function(data) {
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
                                    $('#admission-fee-error').show().html(message.admission_fee);
                                }
                                if (message.first_inst_fee) {
                                    $('#first-inst-fee-error').show().html(message.first_inst_fee);
                                }
                                if (message.second_inst_fee) {
                                    $('#second-inst-fee-error').show().html(message.second_inst_fee);
                                }
                                if (message.complete_fee) {
                                    $('#complete-fee-error').show().html(message.complete_fee);
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
