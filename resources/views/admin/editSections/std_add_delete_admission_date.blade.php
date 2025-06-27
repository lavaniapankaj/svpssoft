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
                        {{ 'Add / Delete Admission Date' }}
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
                                    <span class="invalid-feedback form-invalid fw-bold" id="srno-error"
                                        role="alert"></span>
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

                                    </tr>
                                </thead>
                                <tbody>

                                </tbody>
                            </table>

                        </div>
                        <div class="mt-4" id="change-date-container">
                            <form action="{{ route('admin.editSection.editStdAdmissionDate.store') }}" method="post" id="a_date_form">
                                @csrf
                                <div class="row">
                                    <div class="form-group col-md-6">
                                        <input type="hidden" name="hidden_srno" value="" id="hidden_srno">
                                        <label for="a_date" class="mt-2">Change Date<span
                                                class="text-danger">*</span></label>
                                        <input type="date" id="a_date" name="a_date"
                                            class="form-group @error('a_date') is-invalid @enderror"
                                            value="{{ old('a_date') }}" placeholder="Select Date">
                                        @error('a_date')
                                            <span class="invalid-feedback form-invalid fw-bold select-date-error"
                                                role="alert" id="select-date-error">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="form-group col-md-6">
                                        <button type="submit" id="change-date" class="btn btn-primary">Update</button>
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
            $('#show-details').on('click', function() {
                $('#srno-error').hide();
                const srno = $('#srno').val();
                var stdHtml = '';
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

                            $.each(students.data, function(index, std) {
                                if (std.admission_date !== null && std
                                    .admission_date !== '') {
                                    stdHtml += `<tr>
                                        <td>${std.srno}</td>
                                        <td>${std.student_name}</td>
                                        <td>${std.f_name}</td>
                                        <td>${std.m_name}</td>
                                        <td>${std.class_name}</td>
                                        <td>${std.section_name}</td>
                                        <td>${std.admission_date}</td>
                                        </tr>`;

                                    $('#a_date').val(std.admission_date);
                                    $('#hidden_srno').val(std.id);
                                }
                            });
                            if (stdHtml === '') {
                                stdHtml = '<tr><td colspan="7">No Student found</td></tr>';
                            }

                            $('#change-date-container').show();
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
                                        // console.log(`${key}: ${message}`);
                                        $('#srno-error').show().text(message);
                                    });
                                });
                            } else {
                                console.error('Request failed:', xhr);
                            }

                        }
                    });

                }
            });
            // Modify form submission to keep change-date-container visible on error
            $('a_date_form').on('submit', function(e) {
                // Check if there are any validation errors before form submission
                if ($('#select-date-error').is(':visible')) {
                   e.preventDefault();
                   $('#std-container').show();
                    $('#change-date-container').show();
                }
            });
        });
    </script>
@endsection
