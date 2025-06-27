@extends('admin.index')
@section('sub-content')
    <div class="container">
        @if (Session::has('success'))
            @section('scripts')
                <script>
                    swal("Successful", "{{ Session::get('success') }}", "success").then(() => {
                        location.reload();
                    });
                </script>
            @endsection
        @endif

        @if (Session::has('error'))
            @section('scripts')
                <script>
                    swal("Error", "{{ Session::get('error') }}", "error").then(() => {
                        location.reload();
                    });
                </script>
            @endsection
        @endif
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        {{ 'Edit/Remove Fee Entry' }}
                        <a href="{{ route('admin.editSection.index') }}" class="btn btn-warning btn-sm"
                            style="float: right;">Back</a>

                    </div>
                    <div class="card-body">
                        <form id="class-section-form">

                            <div class="row">
                                <div class="form-group col-md-4">
                                    <label for="class_id" class="mt-2">Class <span class="text-danger">*</span></label>
                                    <select name="class" id="class_id"
                                        class="form-control @error('class') is-invalid @enderror" required>
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
                                    @error('class')
                                        <span class="invalid-feedback form-invalid fw-bold"
                                            role="alert">{{ $message }}</span>
                                    @enderror
                                    <img src="{{ config('myconfig.myloader') }}" alt="Loading..." class="loader"
                                        id="loader" style="display:none; width:10%;">
                                </div>


                                <div class="form-group col-md-4">
                                    <label for="section_id" class="mt-2">Section <span
                                            class="text-danger">*</span></label>
                                    <input type="hidden" id="initialSectionId" value="{{ old('section') }}">
                                    <select name="section" id="section_id"
                                        class="form-control @error('section') is-invalid @enderror" required>
                                        <option value="">Select Section</option>

                                    </select>
                                    @error('section')
                                        <span class="invalid-feedback form-invalid fw-bold"
                                            role="alert">{{ $message }}</span>
                                    @enderror
                                </div>
                                <div class="form-group col-md-4">
                                    <input type="hidden" name="current_session" value='' id="current_session">
                                    <label for="std_id" class="mt-2">Student<span class="text-danger">*</span></label>
                                    <select name="std_id" id="std_id"
                                        class="form-control mx-1 @error('std_id') is-invalid @enderror">
                                        <option value="">Select Student</option>
                                    </select>
                                    <span class="invalid-feedback form-invalid fw-bold student-error" role="alert"></span>

                                </div>
                            </div>


                            <div class="mt-3">
                                <button type="button" id="show-details" class="btn btn-primary">
                                    Show Details</button>
                            </div>

                        </form>


                        <div id="std-container" class="mt-4">
                            <table class="table table-responsible">
                                <thead>
                                    <tr>
                                        <th>Slip No.</th>
                                        <th>Entry Date</th>
                                        <th>Type</th>
                                        <th>Fee of</th>
                                        <th>Amount</th>
                                        <th>Paid / Mercy</th>
                                        <th>Show</th>
                                    </tr>
                                </thead>
                                <tbody>

                                </tbody>
                            </table>
                        </div>
                        <div id="edit-std-container" class="mt-4">
                            <form action="" method="post">
                                @csrf
                                <input type="hidden" id="hidden-srno" name="srno" value="">
                                <input type="hidden" id="hidden-session" name="session" value="">
                                <input type="hidden" id="hidden-feeOf" name="feeOf" value="">
                                <input type="hidden" id="hidden-academicTrans" name="academicTrans" value="">
                                <input type="hidden" id="hidden-paidMercy" name="paidMercy" value="">
                                <input type="hidden" id="hidden-refSlip" name="refSlip" value="">
                                <table class="table table-responsible">
                                    <thead>
                                        <tr>
                                            <th>Slip No.</th>
                                            <th>Entry Date</th>
                                            <th>Type</th>
                                            <th>Fee of</th>
                                            <th>Amount</th>
                                            <th>Paid / Mercy</th>
                                            <th>Show</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    </tbody>
                                </table>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('admin-scripts')
    <script>
        $(document).ready(function() {
            // Initialize containers and form validation
            const stdContainer = $('#std-container');
            const editStdContainer = $('#edit-std-container');
            const classSectionForm = $('#class-section-form');
            const stdTableBody = $('#std-container table tbody');

            let initialClassId = $('#class_id').val();
            let initialSectionId = $('#initialSectionId').val();

            getClassSection(initialClassId, initialSectionId);
            getStdDropdown();

            // Hide containers initially
            stdContainer.hide();
            editStdContainer.hide();

            // Form validation
            classSectionForm.validate({
                rules: {
                    class: {
                        required: true
                    },
                    section: {
                        required: true
                    },
                    std_id: {
                        required: true
                    }
                },
                messages: {
                    class: {
                        required: "Please select a class."
                    },
                    section: {
                        required: "Please select a section."
                    },
                    std_id: {
                        required: "Please select a student."
                    }
                }
            });

            // Reset containers on change
            $('#class_id, #section_id, #std_id').change(() => {
                stdContainer.hide();
                editStdContainer.hide();
            });

            // Show student details
            $('#show-details').on('click', function() {
                if (classSectionForm.valid()) {
                    fetchStudentDetails();
                }
            });

            // Event delegation for edit buttons
            stdContainer.on('click', '.edit-btn', function() {
                const data = $(this).data();
                fillEditForm(data);
            });

            // Event delegation for delete buttons
            $(document).on('click', '.delete-form-btn', function(event) {
                event.preventDefault();
                confirmAndDelete($(this).closest('form'));
            });

            /**
             * Fetch student fee details via AJAX
             */
            function fetchStudentDetails() {
                const stdId = $('#std_id').val();
                const sessionId = $('#current_session').val();
                $.ajax({
                    url: '{{ route('admin.editSection.stdFeeDetailFetch') }}',
                    type: 'GET',
                    dataType: 'JSON',
                    data: {
                        srno: stdId,
                        session: sessionId
                    },
                    success: function(response) {
                        renderStudentDetails(response.data);
                    },
                    error: function(xhr) {
                        console.error('Error fetching student details:', xhr);
                    }
                });
            }

            /**
             * Render student fee details in the table
             */
            function renderStudentDetails(data) {
                let stdHtml = '';

                if (data && data.length > 0) {
                    $.each(data, function(id, value) {
                        stdHtml += `<tr>
                        <td>${value.ref_slip_no ?? ''}</td>
                        <td>${value.pay_date}</td>
                        <td>${value.academic_trans == 1 ? 'Academic' : 'Transport'}</td>
                        <td>${getFeeDescription(value)}</td>
                        <td>${value.amount}</td>
                        <td>${value.fee_of == 4 && value.paid_mercy == 2 ? 'Mercy' : 'Paid'}</td>
                        <td>
                            <button class="btn btn-sm btn-icon p-1 edit-btn"
                                data-srno="${value.srno}"
                                data-session="${$('#current_session').val()}"
                                data-refslip="${value.ref_slip_no}"
                                data-fee-of="${value.fee_of}"
                                data-academic-trans="${value.academic_trans}"
                                data-paid-mercy="${value.paid_mercy}"
                                data-pay-date="${value.pay_date}">
                                <i class="mdi mdi-pencil" data-bs-toggle="tooltip" title="Edit"></i>
                            </button>
                            <form action="${siteUrl}/admin/edit-section/std-fee-edit-remove/${value.id}" method="POST" style="display:inline;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-icon p-1 delete-form-btn" data-bs-toggle="tooltip" title="Delete">
                                    <i class="mdi mdi-delete"></i>
                                </button>
                            </form>
                        </td>
                    </tr>`;
                    });
                } else {
                    stdHtml = '<tr><td colspan="7" class="text-center">No Fee Details Found</td></tr>';
                    editStdContainer.hide();
                }

                stdTableBody.html(stdHtml);
                stdContainer.show();
            }

            /**
             * Get fee description based on conditions
             */
            function getFeeDescription(value) {
                if (value.fee_of == 1) return 'Admission Fee';
                if (value.fee_of == 2) return 'Ist Installment';
                if (value.fee_of == 3) return 'IInd Installment';
                if (value.fee_of == 4) return value.paid_mercy == 1 ? 'Complete' : 'Mercy Fee';
                return '';
            }

            /**
             * Fill the edit form with data
             */
            function fillEditForm(data) {
                $('#hidden-srno').val(data.srno);
                $('#hidden-session').val(data.session);
                $('#hidden-feeOf').val(data.feeOf);
                $('#hidden-academicTrans').val(data.academicTrans);
                $('#hidden-paidMercy').val(data.paidMercy);
                $('#hidden-refSlip').val(data.refslip);

                editFee(data.srno, data.session, data.feeOf, data.academicTrans, data.paidMercy, data.payDate, data
                    .refslip);
            }

            /**
             * Confirm and delete a record via AJAX
             */
            function confirmAndDelete(form) {
                Swal.fire({
                    title: 'Are you sure?',
                    text: "You won't be able to revert this!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, delete it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        const url = form.attr('action');
                        const token = form.find('input[name="_token"]').val();

                        $.ajax({
                            url: url,
                            type: 'DELETE',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': token
                            },
                            success: function(data) {
                                if (data.status === 'success') {
                                    Swal.fire('Deleted!', data.message, 'success').then(() =>
                                        location.reload());
                                } else {
                                    Swal.fire('Error!', data.message, 'error');
                                }
                            },
                            error: function() {
                                Swal.fire('Error!', 'An error occurred while deleting.',
                                    'error');
                            }
                        });
                    }
                });
            }
        });
    </script>
@endsection
