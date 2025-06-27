@extends('fee.index')
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
            <div class="col-md-10">
                <div class="card">
                    <div class="card-header">
                        {{ 'Transport Fee Entry' }}
                        <a href="{{ route('fee.fee-entry.index') }}" class="btn btn-warning btn-sm"
                            style="float: right;">Back</a>

                    </div>
                    <div class="card-body">
                        <div id="std-fee-due-table" class="table-responsive">
                            <table class="table table-striped table-bordered">
                                <thead>
                                    <th>Session</th>
                                    <th>Class</th>
                                    <th>Payable Amount(RS.)</th>
                                    <th>Paid Amount(RS.)</th>
                                    <th>Due Amount(RS.)</th>
                                    <th>Click to Submit</th>
                                </thead>
                                <tbody>

                                </tbody>
                            </table>
                        </div>
                        <form id="class-section-form" method="POST">
                            @csrf
                            <div class="row">
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
                                    <label for="section_id" class="mt-2">Section <span
                                            class="text-danger">*</span></label>
                                    <input type="hidden" id="initialSectionId" value="{{ old('section') }}">
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

                                    <div class="form-group col-md-4">
                                        <input type="hidden" name="transport" value="2">
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
                                        <label for="complete_fee" class="mt-2">Complete Fee</label>
                                        <input type="text" name="complete_fee" id="complete_fee"
                                            class="form-control " value="{{ old('complete_fee') }}">
                                        <span class="invalid-feedback form-invalid fw-bold" id="complete-fee-error"
                                            role="alert"></span>
                                    </div>

                                </div>
                            </div>

                            <div class="mt-3">
                                <button type="button" id="submit-fee" class="btn btn-primary">
                                    Submit</button>
                                <span class="invalid-feedback form-invalid fw-bold" id="total-amount-error"
                                    role="alert"></span>
                                <span class="invalid-feedback form-invalid fw-bold" id="not-applicable-error"
                                    role="alert"></span>
                            </div>

                        </form>

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
            getClassSection(initialClassId, initialSectionId);
            var stdSelect = $('#std_id');
            var stdFeeDueTable = $('#std-fee-due-table');
            stdFeeDueTable.hide();
            stdSelect.change(function() {
                let session = $('#current_session').val();
                let classSelect = $('#class_id').val();
                let sectionSelect = $('#section_id').val();
                stdFeeDueTable.show();
                $.ajax({
                    url: '{{ route('fee.fee-entry.academicFeeDueAmount') }}',
                    type: 'GET',
                    dataType: 'JSON',
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
                        if (stdHtml === '') {
                            stdHtml = '<tr><td colspan = "6">No Student found</td></tr>';
                        }
                        $('#std-fee-due-table table tbody').html(stdHtml);

                    },

                    error: function(xhr) {
                        console.error(xhr.responseText);
                    }
                });
            });

        });
    </script>
@endsection
