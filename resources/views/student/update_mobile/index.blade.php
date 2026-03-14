@extends('student.index')
@section('sub-content')
    <div class="container-fluid">
        @if (Session::has('success'))
            @push('st-swal-scripts')
                <script>
                    swal("Successful", "{{ Session::get('success') }}", "success")
                </script>
            @endpush
        @endif

        @if (Session::has('error'))
            @push('st-swal-scripts')
                <script>
                    swal("Error", "{{ Session::get('error') }}", "error")
                </script>
            @endpush
        @endif
        <div class="row justify-content-center">
            <div class="col-md-14">
                <div class="card border-0 bg-white">
                    <div class="card-header flex-wrap bg-white d-flex align-items-center justify-content-between"><h5 class="mb-0 mt-0">{{ 'Update Mobile No.' }}</h5>
                        <a href="{{ route('student.updateMobile.index') }}" class="btn bg-light btn-sm" ><span class="mdi mdi-chevron-left me-2"></span>Back</a>
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
                                    <img src="{{ config('myconfig.myloader') }}" alt="Loading..." class="loader" id="loader" style="display:none; width:5%;">
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
                                        <span class="invalid-feedback form-invalid fw-bold" role="alert">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="row">
                                <div class="form-group col-md-6">
                                    <label for="f_mobile" class="mt-2">Enter Father's Mob. No. <span class="text-danger">*</span></label>
                                    <input type="text" name="f_mobile" id="f_mobile" class="form-control @error('f_mobile') is-invalid @enderror" required>
                                    <span class="text-danger fw-bold f-mobile-error" role="alert"></span>
                                    @error('f_mobile')
                                        <span class="invalid-feedback form-invalid fw-bold" role="alert">{{ $message }}</span>
                                    @enderror
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="m_mobile" class="mt-2">Enter Mother's Mob. No.</label>
                                    <input type="text" name="m_mobile" id="m_mobile" class="form-control @error('m_mobile') is-invalid @enderror">
                                    <span class="text-danger fw-bold m-mobile-error" role="alert"></span>
                                    @error('m_mobile')
                                        <span class="invalid-feedback form-invalid fw-bold" role="alert">{{ $message }}</span>
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
        $(document).ready(function () {
            let initialClassId = $('#class_id').val();
            let initialSectionId = $('#initialSectionId').val();
            getClassSection(initialClassId, initialSectionId);
            var loader = $('#loader');

            // Section change — load students
            $('#section_id').change(function () {
                var classId   = $('#class_id').val();
                var sectionId = $(this).val();
                var sessionId = $('#current_session').val();
                var stdSelect = $('#std_id');

                $('#f_mobile').val('');
                $('#m_mobile').val('');
                clearErrors();

                if (classId && sectionId && sessionId) {
                    loader.show();
                    $.ajax({
                        url: '{{ route('stdNameFather.get') }}',
                        type: 'GET',
                        dataType: 'JSON',
                        data: { class_id: classId, section_id: sectionId, session_id: sessionId },
                        success: function (students) {
                            stdSelect.empty();
                            let options = '<option value="">Select Student</option>';
                            if (students.length > 0) {
                                $.each(students, function (index, student) {
                                    options += `<option value="${student.srno}">${student.rollno}. ${student.student_name} / ${student.f_name}</option>`;
                                });
                            } else {
                                options += '<option value="" disabled>No student found</option>';
                            }
                            stdSelect.html(options);

                            // Student change — fill mobile numbers
                            stdSelect.off('change').on('change', function () {
                                var selected = students.find(s => s.srno == $(this).val());
                                if (selected) {
                                    $('#f_mobile').val(selected.f_mobile);
                                    $('#m_mobile').val(selected.m_mobile);
                                } else {
                                    $('#f_mobile').val('');
                                    $('#m_mobile').val('');
                                }
                                clearErrors();
                            });
                        },
                        complete: function () { loader.hide(); },
                        error: function (xhr) { console.error(xhr.responseText); }
                    });
                }
            });
            function reloadStudents() {
                var classId    = $('#class_id').val();
                var sectionId  = $('#section_id').val();
                var sessionId  = $('#current_session').val();
                var stdSelect  = $('#std_id');
                var currentStd = stdSelect.val(); // Remember selected student

                if (!classId || !sectionId || !sessionId) return;

                loader.show();
                $.ajax({
                    url: '{{ route('stdNameFather.get') }}',
                    type: 'GET',
                    dataType: 'JSON',
                    data: { class_id: classId, section_id: sectionId, session_id: sessionId },
                    success: function (students) {
                        stdSelect.empty();
                        let options = '<option value="">Select Student</option>';
                        $.each(students, function (index, student) {
                            options += `<option value="${student.srno}">${student.rollno}. ${student.student_name} / ${student.f_name}</option>`;
                        });
                        stdSelect.html(options);

                        // Re-select the same student
                        stdSelect.val(currentStd);

                        // Fill updated mobile numbers for currently selected student
                        var selected = students.find(s => s.srno == currentStd);
                        if (selected) {
                            $('#f_mobile').val(selected.f_mobile);
                            $('#m_mobile').val(selected.m_mobile);
                        }

                        // Re-bind student change event with fresh data
                        stdSelect.off('change').on('change', function () {
                            var sel = students.find(s => s.srno == $(this).val());
                            if (sel) {
                                $('#f_mobile').val(sel.f_mobile);
                                $('#m_mobile').val(sel.m_mobile);
                            } else {
                                $('#f_mobile').val('');
                                $('#m_mobile').val('');
                            }
                            clearErrors();
                        });
                    },
                    complete: function () { loader.hide(); },
                    error: function (xhr) { console.error(xhr.responseText); }
                });
            }
            // AJAX form submit
            $('#update-btn').on('click', function (e) {
                e.preventDefault();
                clearErrors();

                // Basic client-side check
                var fMobile = $('#f_mobile').val().trim();
                var mMobile = $('#m_mobile').val().trim();
                var regex   = /^[0-9]{10}$/;
                var isValid = true;

                if (!$('#class_id').val()) {
                    showFieldError('.class-error', 'Please select a class.');
                    isValid = false;
                }
                if (!$('#section_id').val()) {
                    showFieldError('.section-error', 'Please select a section.');
                    isValid = false;
                }
                if (!$('#std_id').val()) {
                    showFieldError('.st-error', 'Please select a student.');
                    isValid = false;
                }
                if (!fMobile) {
                    showFieldError('.f-mobile-error', "Father's mobile number is required.");
                    isValid = false;
                } else if (!regex.test(fMobile)) {
                    showFieldError('.f-mobile-error', 'Please enter a valid 10-digit mobile number.');
                    isValid = false;
                }
                if (mMobile && !regex.test(mMobile)) {
                    showFieldError('.m-mobile-error', 'Please enter a valid 10-digit mobile number.');
                    isValid = false;
                }

                if (!isValid) return;

                $('#update-btn').prop('disabled', true).text('Updating...');

                $.ajax({
                    url: '{{ route('student.updateMobile.store') }}',
                    type: 'POST',
                    dataType: 'JSON',
                    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                    data: {
                        class:    $('#class_id').val(),
                        section:  $('#section_id').val(),
                        std_id:   $('#std_id').val(),
                        f_mobile: fMobile,
                        m_mobile: mMobile,
                    },
                    success: function (response) {
                        if (response.status === 'success') {
                            Swal.fire({
                                icon: 'success',
                                title: 'Updated!',
                                text: response.message,
                                confirmButtonColor: '#28a745'
                            }).then(() => {
                                // Refetch fresh student data after successful update
                                reloadStudents();
                            });
                        } else {
                            Swal.fire({ icon: 'error', title: 'Error', text: response.message });
                        }
                    },
                    error: function (xhr) {
                        if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                            var errors = xhr.responseJSON.errors;
                            if (errors.class)    showFieldError('.class-error',     errors.class[0]);
                            if (errors.section)  showFieldError('.section-error',   errors.section[0]);
                            if (errors.std_id)   showFieldError('.st-error',        errors.std_id[0]);
                            if (errors.f_mobile) showFieldError('.f-mobile-error',  errors.f_mobile[0]);
                            if (errors.m_mobile) showFieldError('.m-mobile-error',  errors.m_mobile[0]);
                        } else {
                            Swal.fire({ icon: 'error', title: 'Error', text: xhr.responseJSON?.message || 'Something went wrong.' });
                        }
                    },
                    complete: function () {
                        $('#update-btn').prop('disabled', false).text('Update');
                    }
                });
            });

            function showFieldError(selector, message) {
                $(selector).show().html(message);
            }

            function clearErrors() {
                $('.class-error, .section-error, .st-error, .f-mobile-error, .m-mobile-error')
                    .hide().html('');
            }
        });
    </script>
@endsection
