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
                        {{ 'Edit Current/Previous Records' }}
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
                                        class="form-control @error('srno') is-invalid @enderror" value=""
                                        placeholder="Enter SRNO. of Student ">
                                    @error('srno')
                                        <span class="invalid-feedback form-invalid fw-bold"
                                            role="alert">{{ $message }}</span>
                                    @enderror
                                </div>
                                <div class="form-group col-md-3">
                                    <button type="button" id="show-details" class="btn btn-primary mt-4">
                                        Show Details</button>
                                    <img src="{{ config('myconfig.myloader') }}" alt="Loading..." class="loader"
                                        id="loader" style="display:none; width:10%;">
                                </div>
                                <div class="form-group col-md-3">
                                    <a href="{{ route('admin.student-master.search') }}" class="btn btn-sm btn-info mt-4"
                                        target="_blank" rel="noopener noreferrer">To Know SRNO Click Here</a>
                                </div>
                                <p>
                                    <span id='no-std' class="invalid-feedback form-invalid fw-bold">No Student
                                        Found</span>
                                    <span class="invalid-feedback form-invalid fw-bold" id="srno-error"
                                        role="alert"></span>
                                </p>
                            </div>
                        </form>

                        <div class="mt-4" id="std-form">
                            <form action="{{ route('admin.editSection.editStdByPreSrno.store') }}" method="post"
                                id="student-form">
                                @csrf
                                <div class="row">
                                    <div class="form-group col-md-6">
                                        <input type="hidden" name="std_srno" id="srno-inner" value="">
                                        <label for="std_name" class="mt-2">Name<span class="text-danger">*</span></label>
                                        <input type="text" id="std_name" name="std_name"
                                            class="form-control @error('std_name') is-invalid @enderror" value=""
                                            placeholder="Enter Student Name" required>
                                        @error('std_name')
                                            <span class="invalid-feedback form-invalid fw-bold"
                                                role="alert">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label for="f_name" class="mt-2">Father's Name<span
                                                class="text-danger">*</span></label>
                                        <input type="text" id="f_name" name="f_name"
                                            class="form-control @error('f_name') is-invalid @enderror" value=""
                                            placeholder="Enter Father's Name" required>
                                        @error('f_name')
                                            <span class="invalid-feedback form-invalid fw-bold"
                                                role="alert">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="form-group col-md-6">
                                        <label for="m_name" class="mt-2">Mother's Name<span
                                                class="text-danger">*</span></label>
                                        <input type="text" id="m_name" name="m_name"
                                            class="form-control @error('m_name') is-invalid @enderror" value=""
                                            placeholder="Enter Mother's Name" required>
                                        @error('m_name')
                                            <span class="invalid-feedback form-invalid fw-bold"
                                                role="alert">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="form-group col-md-3">
                                        <label for="dob" class="mt-2">DOB<span class="text-danger">*</span></label>
                                        <input type="date" id="dob" name="dob"
                                            class="form-control @error('dob') is-invalid @enderror" value=""
                                            placeholder="Enter DOB" required>
                                        @error('dob')
                                            <span class="invalid-feedback form-invalid fw-bold"
                                                role="alert">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="form-group col-md-3">
                                        <label for="category" class="mt-2">Category<span
                                                class="text-danger">*</span></label>
                                        <select name="category" id="category"
                                            class="form-control @error('category') is-invalid @enderror" required>
                                        </select>
                                        @error('category')
                                            <span class="invalid-feedback form-invalid fw-bold"
                                                role="alert">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="form-group col-md-12">
                                        <label for="address">Address</label>
                                        <textarea name="address" id="address" class="form-control @error('address') is-invalid @enderror" rows="3"
                                            required></textarea>
                                        @error('address')
                                            <span class="invalid-feedback form-invalid fw-bold"
                                                role="alert">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <button type="submit" class="btn btn-primary" id="update-std">Update</button>
                                </div>
                            </form>
                        </div>
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
            $('#std-form').hide();
            $('#change-date-container').hide();
            $('#no-std').hide();
            var loader = $('#loader');
            $('#show-details').on('click', function() {
                const srno = $('#srno').val();
                loader.show();
                $('#srno-error').hide();
                $('#std-form').hide();
                if (srno) {

                    $.ajax({
                        url: '{{ route('admin.getStdWithSrno') }}',
                        type: 'GET',
                        dataType: 'JSON',
                        data: {
                            srno: srno,
                        },
                        success: function(students) {
                            let stdHtml = '';
                            if (students.data.length > 0 && students.data[0]) {
                                let std = students.data[0];

                                $('#srno-inner').val(std.srno);
                                $('#std_name').val(std.student_name);
                                $('#f_name').val(std.f_name);
                                $('#m_name').val(std.m_name);
                                $('#dob').val(std.dob);
                                stdHtml +=
                                    `<option value="">Select Age Proof</option>
                                     <option value="1" ${std.category && std.category == 1 ? 'selected' : ''}>General</option>
                                     <option value="2" ${std.category && std.category == 2 ? 'selected' : ''}>OBC</option>
                                     <option value="3" ${std.category && std.category == 3 ? 'selected' : ''}>SC</option>
                                     <option value="4" ${std.category && std.category == 4 ? 'selected' : ''}>ST</option>
                                     <option value="5" ${std.category && std.category == 5 ? 'selected' : ''}>BC</option>`;
                                $('#category').html(stdHtml);
                                $('#address').val(std.address);


                                $('#no-std').hide();
                                $('#std-form').show();
                            } else {
                                $('#std-form').hide();
                                $('#no-std').show();
                            }
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
                            } else {
                                console.error('Request failed:', xhr);
                                $('#std-form').hide();
                            }

                        },
                    });

                    $('#student-form').validate();
                }
            });


        });
    </script>
@endsection
