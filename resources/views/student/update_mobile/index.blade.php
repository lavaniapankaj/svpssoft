@extends('student.index')
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
            <div class="col-md-14">
                <div class="card">
                    <div class="card-header">
                        {{ 'Update Mobile No.' }}
                        <a href="{{ route('student.updateMobile.index') }}" class="btn btn-warning btn-sm"
                            style="float: right;">Back</a>

                    </div>
                    <div class="card-body">
                        <form id="st-form" action="{{ route('student.updateMobile.store') }}" method="POST">
                            @csrf
                            <div class="row">
                                <div class="form-group col-md-4">
                                    <label for="class_id" class="mt-2">Class <span class="text-danger">*</span></label>
                                    <select name="class" id="class_id"
                                        class="form-control @error('class') is-invalid @enderror" required>
                                        <option value="">Select Class</option>
                                        @if (count($classes) > 0)
                                            @foreach ($classes as $key => $class)
                                                <option value="{{ $key }}" {{ old('class') == $key ? 'selected' : ''}}>{{ $class }}</option>
                                            @endforeach
                                        @else
                                            <option value="">No Class Found</option>
                                        @endif
                                    </select>
                                    <span class="text-danger fw-bold class-error" role="alert"></span>
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
                                    <input type="hidden" id="initialSectionId"
                                        value="{{ old('section') }}">
                                    <select name="section" id="section_id"
                                        class="form-control @error('section') is-invalid @enderror" required>
                                        <option value="">Select Section</option>

                                    </select>
                                    <span class="text-danger fw-bold section-error" role="alert"></span>
                                    <input type="hidden" name="current_session" value='' id="current_session">
                                    @error('section')
                                        <span class="invalid-feedback form-invalid fw-bold"
                                            role="alert">{{ $message }}</span>
                                    @enderror
                                </div>
                                <div class="form-group col-md-4">
                                    <label for="std_id" class="mt-2">Student <span class="text-danger">*</span></label>
                                    <select name="std_id" id="std_id"
                                        class="form-control @error('std_id') is-invalid @enderror" required>
                                        <option value="">Select Student</option>
                                    </select>
                                    <span class="text-danger fw-bold st-error" role="alert"></span>
                                    @error('std_id')
                                        <span class="invalid-feedback form-invalid fw-bold"
                                            role="alert">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="row">
                                <div class="form-group col-md-6">
                                    <label for="f_mobile" class="mt-2">Enter Father's Mob. No. <span
                                            class="text-danger">*</span></label>
                                    <input type="text" name="f_mobile" id="f_mobile"
                                        class="form-control @error('f_mobile') is-invalid @enderror" required>
                                    <span class="text-danger fw-bold f-mobile-error" role="alert"></span>
                                    @error('f_mobile')
                                        <span class="invalid-feedback form-invalid fw-bold"
                                            role="alert">{{ $message }}</span>
                                    @enderror
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="m_mobile" class="mt-2">Enter Mother's Mob. No. <span
                                            class="text-danger">*</span></label>
                                    <input type="text" name="m_mobile" id="m_mobile"
                                        class="form-control @error('m_mobile') is-invalid @enderror">
                                    <span class="text-danger fw-bold m-mobile-error" role="alert"></span>
                                    @error('m_mobile')
                                        <span class="invalid-feedback form-invalid fw-bold"
                                            role="alert">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="mt-3">
                                <button type="submit" id="update-btn" class="btn btn-primary">Update</button>
                            </div>

                        </form>

                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('std-scripts')
    <script>

        $(document).ready(function() {
            let initialClassId = $('#class_id').val();
            let initialSectionId = $('#initialSectionId').val();
            getClassSection(initialClassId, initialSectionId);
            var loader = $('#loader');

            $('#section_id').change(function() {
                var classId = $('#class_id').val();
                var sectionId = $(this).val();
                var sessionId = $('#current_session').val();
                var stdSelect = $('#std_id');
                var fMobile = $('#f_mobile');
                var mMobile = $('#m_mobile');
                if (classId && sectionId && sessionId) {
                    loader.show();
                    $.ajax({
                        url: '{{ route('stdNameFather.get') }}',
                        type: 'GET',
                        dataType: 'JSON',
                        data: {
                            class_id: classId,
                            section_id: sectionId,
                            session_id: sessionId,
                        },
                        success: function(students) {
                            stdSelect.empty();

                            let options = '<option value="">Select Student</option>';

                            if (students.length > 0) {
                                $.each(students, function(index, student) {
                                    options += '<option value="' + student.srno + '">' +
                                        student.rollno + '. ' + student.student_name +
                                        '/' +
                                        student.f_name + '</option>';

                                });
                                stdSelect.change(function() {
                                    var st = students.filter(student => student.srno ===
                                        stdSelect.val());
                                    // console.log(st);
                                    $.each(st, function(index, std) {
                                        fMobile.val(std.f_mobile);
                                        mMobile.val(std.m_mobile);
                                    });

                                });
                            } else {
                                options += '<option value="">No student found</option>';
                            }

                            stdSelect.html(options);
                        },
                        complete: function() {
                            loader.hide();
                        },
                        error: function(xhr) {
                            console.error(xhr.responseText);
                        }
                    });
                }
            });
            $('#update-btn').on('click', function() {
                $('#st-form').validate();
                $('.f-mobile-error, .m-mobile-error').hide();

                var regex = /^[0-9]{10}$/;

                function validateMobileInput(input, errorClass) {
                    var value = $(input).val();
                    if (value === '') {
                        $(errorClass).hide();
                    } else if (!regex.test(value)) {
                        $(errorClass).show().html('Please Enter a valid Phone Number');
                    } else {
                        $(errorClass).hide();
                    }
                }

                 $('#f_mobile').on('input', function() {
                    validateMobileInput(this, '.f-mobile-error');
                });

                $('#m_mobile').on('input', function() {
                   // validateMobileInput(this, '.m-mobile-error');
                   if ($(this) == '') {
                        $('.m-mobile-error').hide().html('');
                   }
                   $('.m-mobile-error').show().html('Please Enter a valid Phone Number');
                });

                $('.f-mobile-error, .m-mobile-error').each(function() {
                    if ($(this).is(':visible')) {
                        $(this).hide().html('');
                    }
                });
            });



        });
    </script>
@endsection
