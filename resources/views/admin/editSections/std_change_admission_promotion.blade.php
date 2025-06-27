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
                    swal("Error", "{{ Session::get('error') }}", "error");
                </script>
            @endsection
        @endif
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        {{ 'Edit Admission / Promotion Date' }}
                        <a href="{{ route('admin.editSection.index') }}" class="btn btn-warning btn-sm"
                            style="float: right;">Back</a>

                    </div>
                    <div class="card-body">
                        <form id="date-form">
                            <div class="row">
                                <div class="form-group col-md-6">
                                    <label for="srno" class="mt-2">Enter SRNO. <span
                                            class="text-danger">*</span></label>
                                    <input type="text" id="srno" name="srno"
                                        class="form-group @error('srno') is-invalid @enderror" value=""
                                        placeholder="Enter SRNO. of Student ">
                                    @error('srno')
                                        <span class="invalid-feedback form-invalid fw-bold"
                                            role="alert">{{ $message }}</span>
                                    @enderror
                                </div>
                                <div class="form-group col-md-6">
                                    <button type="button" id="show-details" class="btn btn-primary">
                                        Show Details</button>
                                    <img src="{{ config('myconfig.myloader') }}" alt="Loading..." class="loader"
                                        id="loader" style="display:none; width:10%;">
                                    <span class="invalid-feedback form-invalid fw-bold" id="srno-error" role="alert"></span>
                                </div>
                            </div>
                        </form>

                        <div id="std-container" class="mt-4">
                            <table class="table table-responsible">
                                <input type="hidden" name="current_session" value='' id="current_session">
                                <thead>
                                    <tr>
                                        <th>SRNO</th>
                                        <th>Name</th>
                                        <th>Father's Name</th>
                                        <th>Mother's Name</th>
                                        <th>Class</th>
                                        <th>Section</th>
                                        <th>Admission Date</th>
                                        <th>Promotion Date</th>
                                    </tr>
                                </thead>
                                <tbody>

                                </tbody>
                            </table>

                        </div>
                        <div class="mt-4" id="change-date-container">
                            <form action="{{ route('admin.editSection.editStdAdmissionPromotion.store') }}" method="POST" id="chanage-date-form">
                                @csrf
                                <div class="row">
                                    <div class="form-group col-md-6">
                                        <input type="hidden" id="hidden_srno" name="hidden_srno" value="">
                                        <input type="hidden" id="hidden_a_date" name="hidden_a_date" value="">
                                        <input type="hidden" id="hidden_p_date" name="hidden_p_date" value="">
                                        <label for="a_p_date" class="mt-2">Select Date for Change<span
                                                class="text-danger">*</span></label>
                                        <input type="date" id="a_p_date" name="a_p_date"
                                            class="form-group @error('a_p_date') is-invalid @enderror" value=""
                                            placeholder="Select Date" required>
                                        @error('a_p_date')
                                            <span class="invalid-feedback form-invalid fw-bold"
                                                role="alert">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="form-group col-md-6">
                                        <button type="submit" id="change-date" class="btn btn-primary">Change Date</button>
                                    </div>
                                </div>
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
            $('#std-container').hide();
            $('#change-date-container').hide();
            var loader = $('#loader');
            $('#show-details').click(function() {
                $('#srno-error').hide();
                const srno = $('#srno').val();
                loader.show();
                if (srno) {
                    $('#std-container').show();
                    $.ajax({
                        url: '{{ route('admin.getStdWithSrno') }}',
                        type: 'GET',
                        dataType: 'JSON',
                        data: {
                            srno: srno,
                        },
                        success: function(students) {
                            console.log(students);

                            let stdHtml = '';
                            $.each(students.data, function(index, std) {

                                stdHtml += `<tr>
                            <td>${std.srno}</td>
                            <td>${std.student_name}</td>
                            <td>${std.f_name}</td>
                            <td>${std.m_name}</td>
                            <td>${std.class_name}</td>
                            <td>${std.section_name}</td>
                            <td><a data-id='${std.id}' href="#" class="admission_date">${std.admission_date ?? ''}</a></td>
                            <td><a data-id='${std.id}' href="#" class="promotion_date">${std.form_submit_date ?? '-'}</a></td>
                            </tr>`;
                            });

                            $('#std-container table tbody').html(stdHtml);
                        },
                        complete: function() {

                            loader.hide();
                        },
                        error: function(xhr) {
                            if (xhr.status === 400) {
                                const response = xhr.responseJSON;
                                $.each(response.message, function(key, messages) {
                                    $.each(messages, function(index, message) {
                                        $('#srno-error').show().text(message);
                                    });
                                });
                                $('#std-container').hide();
                            } else {
                                console.log('Request failed:', xhr);
                            }
                        }
                    });

                }
            });

            $('#std-container').on('click', '.admission_date', function() {
                const date = $(this).html();
                const srno = $('#srno').val();
               $('#change-date-container').show();
                $('#hidden_a_date').val(date);
                $('#hidden_srno').val($(this).data("id"));
                $('#a_p_date').val(date);
                return false;
            });
            $('#std-container').on('click', '.promotion_date', function() {
                const date = $(this).html();
                const srno = $('#srno').val();
                $('#change-date-container').show();
                $('#hidden_p_date').val(date);
                $('#hidden_srno').val($(this).data("id"));
                $('#a_p_date').val(date);
                return false;
            });
            $('#chanage-date-form').validate();



        });
    </script>
@endsection
