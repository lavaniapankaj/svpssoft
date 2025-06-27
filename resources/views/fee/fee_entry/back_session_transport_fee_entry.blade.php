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
                    swal("Error", "{{ Session::get('error') }}", "error");
                </script>
            @endsection
        @endif
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        {{ 'Back Session Transport Fee Entry' }}
                        <a href="{{ route('fee.fee-entry.transport') }}" class="btn btn-warning btn-sm"
                            style="float: right;">Back</a>

                    </div>
                    <div class="card-body">
                        <form id="class-section-form" method="POST">
                            @csrf
                            <div class="row">
                                <div class="form-group col-md-4">
                                    <label for="back_session" class="mt-2">Session</label>
                                    <input type="text" id="back_session" value="" class="form-control" disabled>
                                    <input type="hidden" id="session" name="session" value="{{ $session }}">
                                </div>
                                <div class="form-group col-md-4">
                                    <label for="back_class" class="mt-2">Class</label>
                                    <input type="text" id="back_class" value="" class="form-control" disabled>
                                    <input type="hidden" id="class" name="class" value="{{$class}}">
                                </div>
                                <div class="form-group col-md-4">
                                    <label for="back_section" class="mt-2">Section</label>
                                    <input type="text" id="back_section" value="" class="form-control" disabled>
                                    <input type="hidden" id="section" name="section" value="{{$section}}">
                                </div>
                            </div>
                            <div class="row">
                                <div class="form-group col-md-6">
                                    <label for="back_std" class="mt-2">Student</label>
                                    <input type="text" id="back_std" value="" class="form-control" disabled>
                                    <input type="hidden" id="std_id" name="std_id" value="{{ $srno }}">
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="total_amount" class="mt-2">Enter Total Amount <span
                                            class="text-danger">*</span></label>
                                    <input type="text" name="total_amount" id="total_amount" class="form-control"
                                        value="{{ old('total_amount') }}" required>
                                </div>
                            </div>
                            <div class="row">
                                <div class="form-group col-md-6">
                                    <label for="fee_date" class="mt-2">Enter Date <span
                                            class="text-danger">*</span></label>
                                    <input type="date" name="fee_date" id="fee_date" class="form-control "
                                        value="{{ old('fee_date)') }}" required>
                                    <span class="invalid-feedback form-invalid fw-bold" id="fee-date-error"
                                        role="alert"></span>
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
                                    <div class="form-group col-md-6">
                                        <label for="mercy_fee" class="mt-2">Mercy Fee</label>
                                        <input type="text" name="mercy_fee" id="mercy_fee" class="form-control "
                                            value="{{ old('mercy_fee') }}">
                                        <span class="invalid-feedback form-invalid fw-bold" id="mercy-fee-error"
                                            role="alert"></span>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-3">
                                <button type="button" id="submit-fee" class="btn btn-primary">
                                    Submit</button>
                                <span class="invalid-feedback form-invalid fw-bold" id="total-amount-error"
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
            var stdSelect = $('#std_id').val();
            let classSelect = $('#class').val();
            let sectionSelect = $('#section').val();
            let session = $('#session').val();
            $.ajax({
                url: '{{ route('fee.fee-entry.academicFeeDueAmount') }}',
                type: 'GET',
                dataType: 'JSON',
                data: {
                    srno: stdSelect,
                    current_session: session,
                    class: classSelect,
                    section: sectionSelect,
                },

                success: function(response) {
                    const students = response.data;
                    students.forEach((student,index) =>{

                        let filteredSessions = student.sessions.filter(function(value) {
                            console.log(value);
                            return value.session_id === Number(session);
                        });
                        $('#back_std').val(student.student_name);
                        console.log(filteredSessions);
                        $.each(filteredSessions, function(index,st){
                            $('#back_session').val(st.session);
                            $('#class').val(st.class_id);
                            $('#section').val(st.section_id);
                            $('#back_class').val(st.class);
                            $('#back_section').val(st.section);
                        });
                    });
                },

                error: function(xhr) {
                    console.error(xhr.responseText);
                }
            });


        });
    </script>
@endsection