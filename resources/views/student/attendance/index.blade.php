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

        <div class="row">
            <div class="col-md-12">
                <div class="card border-0 bg-white">
                    <div class="card-header flex-wrap bg-white d-flex align-items-center justify-content-between">
                        <h5 class="mb-0 mt-0">Attendance Entry</h5>
                        <a href="{{ route('student.attendance.index') }}" class="btn bg-light btn-sm">
                            <span class="mdi mdi-chevron-left me-2"></span>Back
                        </a>
                    </div>
                    <div class="card-body">

                        {{-- Filter Form --}}
                        <form id="class-section-form">
                            <div class="row">
                                <div class="form-group col-md-12">
                                    <label for="a_date" class="mt-2">Enter Date <span class="text-danger">*</span></label>
                                    <input type="date" name="a_date" id="a_date"
                                        class="form-control @error('a_date') is-invalid @enderror" required>
                                    @error('a_date')
                                        <span class="invalid-feedback form-invalid fw-bold" role="alert">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="row">
                                {{-- Class --}}
                                <div class="form-group col-md-6">
                                    <label for="st_class_id" class="mt-2">Class <span class="text-danger">*</span></label>
                                    <select name="class" id="st_class_id" class="form-control"
                                        {{ count($classes) == 0 ? 'disabled' : 'required' }}>
                                        @if (count($classes) > 0)
                                            <option value="">Select Class</option>
                                            @foreach ($classes as $key => $class)
                                                <option value="{{ $key }}"
                                                    {{ request()->get('class') == $key ? 'selected' : '' }}>
                                                    {{ $class }}
                                                </option>
                                            @endforeach
                                        @else
                                            <option value="" selected disabled>No Class Found</option>
                                        @endif
                                    </select>
                                    <span class="invalid-feedback form-invalid fw-bold" id="class-error" role="alert"></span>
                                </div>

                                {{-- Section --}}
                                <div class="form-group col-md-6">
                                    <label for="st_section_id" class="mt-2">Section <span class="text-danger">*</span></label>
                                    <select name="section" id="st_section_id" class="form-control" required>
                                        <option value="">Select Section</option>
                                    </select>
                                    <span class="invalid-feedback form-invalid fw-bold" id="section-error" role="alert"></span>
                                </div>
                            </div>

                            <div class="mt-3">
                                <button type="button" id="show-details" class="btn btn-primary">Show Details</button>
                                <span class="text-danger fw-bold ms-2" id="no-data"></span>
                                <span>
                                    <img src="{{ config('myconfig.myloader') }}" alt="Loading..." id="loader" style="display:none; width:5%;">
                                </span>
                            </div>
                        </form>

                        {{-- Student Attendance Form — hidden until data loads --}}
                        <div id="std-container" class="mt-4" style="display:none;">
                            <form method="POST" id="std-form">
                                @csrf
                                {{-- No hidden inputs needed --}}
                                {{-- class, section, a_date read live from original fields on submit --}}
                                <div class="table-responsive">
                                    <table class="table table-striped table-bordered">
                                        <thead>
                                            <tr>
                                                <th>Roll No.</th>
                                                <th>Name</th>
                                                <th>Father's Name</th>
                                                <th>Attendance</th>
                                            </tr>
                                        </thead>
                                        <tbody id="std-table-body"></tbody>
                                    </table>
                                </div>

                                <div class="mt-3">
                                    <button type="button" class="btn btn-primary" id="section-updateBtn">Update</button>
                                </div>
                            </form>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('std-scripts')
<script>
    $(document).ready(function () {

        // ------------------------------------------------------------------ //
        //  Cached selectors
        // ------------------------------------------------------------------ //
        const loader        = $('#loader');
        const noData        = $('#no-data');
        const classSelect   = $('#st_class_id');
        const sectionSelect = $('#st_section_id');
        const stdContainer  = $('#std-container');
        const stdTableBody  = $('#std-table-body');

        // ------------------------------------------------------------------ //
        //  Helper: reset student table and hide container
        // ------------------------------------------------------------------ //
        function resetStudentTable() {
            stdContainer.hide();
            stdTableBody.html('');
            noData.text('');
        }

        // ------------------------------------------------------------------ //
        //  On page load: restore dropdown chain from URL params if present
        // ------------------------------------------------------------------ //
        if (classSelect.val()) {
            getStudentWithoutAllSections(classSelect.val(), function () {
                var savedSection = '{{ request()->get("section") }}';
                if (savedSection) {
                    sectionSelect.val(savedSection).trigger('change');
                }
            });
        }

        // ------------------------------------------------------------------ //
        //  Class change
        // ------------------------------------------------------------------ //
        classSelect.on('change', function () {
            resetStudentTable();
            sectionSelect.html('<option value="">Select Section</option>');

            if ($(this).val()) {
                getStudentWithoutAllSections($(this).val(), function () {});
            }
        });

        // ------------------------------------------------------------------ //
        //  Section change
        // ------------------------------------------------------------------ //
        sectionSelect.on('change', function () {
            resetStudentTable();
        });

        // ------------------------------------------------------------------ //
        //  Date change
        // ------------------------------------------------------------------ //
        /* $('#a_date').on('change', function () {
            resetStudentTable();
        }); */

        // ------------------------------------------------------------------ //
        //  Show Details button
        // ------------------------------------------------------------------ //
        $('#show-details').on('click', function () {
            const classVal   = classSelect.val();
            const sectionVal = sectionSelect.val();
            const dateVal    = $('#a_date').val();

            if (!dateVal) {
                noData.text('Please enter a date.');
                return;
            }
            if (!classVal) {
                noData.text('Please select a class.');
                return;
            }
            if (!sectionVal) {
                noData.text('Please select a section.');
                return;
            }

            noData.text('');
            fetchStudents(classVal, sectionVal, dateVal);
        });

        // ------------------------------------------------------------------ //
        //  AJAX: fetch students for attendance
        // ------------------------------------------------------------------ //
        function fetchStudents(classVal, sectionVal, dateVal) {
            resetStudentTable();
            loader.show();

            $.ajax({
                url      : '{{ route('student.students') }}',
                type     : 'POST',
                dataType : 'json',
                headers  : { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                data: {
                    class_id   : classVal,
                    section_id : sectionVal,
                },
                success: function (response) {
                    if (response.status === 'error') {
                        noData.text(response.message ?? 'No students found.');
                        return;
                    }

                    if (!response.data || response.data.length === 0) {
                        noData.text('No students found for selected class and section.');
                        return;
                    }

                    // Build student rows
                    let html = '';
                    response.data.forEach((std, index) => {
                        html += `
                            <tr>
                                <td>${std.rollno ?? 'N/A'}</td>
                                <td>
                                    <input type="hidden"
                                        name="students[${index}][srno]"
                                        value="${std.srno}">
                                    ${std.student_name ?? 'N/A'}
                                </td>
                                <td>${std.father_name ?? 'N/A'}</td>
                                <td>
                                    <input type="checkbox"
                                        name="students[${index}][status]"
                                        value="1"
                                        class="status-checkbox"
                                        data-index="${index}"
                                        checked>
                                </td>
                            </tr>
                        `;
                    });

                    stdTableBody.html(html);
                    stdContainer.show();
                },
                error: function (xhr) {
                    noData.text('Server error, please try again.');
                    console.error('AJAX error:', xhr);
                },
                complete: function () {
                    loader.hide();
                }
            });
        }

        // ------------------------------------------------------------------ //
        //  Update attendance — reads directly from original fields, no hidden inputs
        // ------------------------------------------------------------------ //
        $('#section-updateBtn').on('click', function () {
            const classVal   = classSelect.val();
            const sectionVal = sectionSelect.val();
            const dateVal    = $('#a_date').val();

            // Validate original fields still have values
            if (!classVal || !sectionVal || !dateVal) {
                noData.text('Class, section and date are required.');
                return;
            }

            // Collect student srno + status directly from table rows
            const students = [];
            stdTableBody.find('tr').each(function () {
                const srno    = $(this).find('input[type="hidden"]').val();
                const checked = $(this).find('input[type="checkbox"]').is(':checked');

                if (srno) {
                    students.push({
                        srno  : srno,
                        status: checked ? 1 : 0,
                    });
                }
            });

            if (students.length === 0) {
                noData.text('No student data to submit.');
                return;
            }

            $.ajax({
                url         : '{{ route('student.attendance.store') }}',
                type        : 'POST',
                dataType    : 'json',
                contentType : 'application/json',
                headers     : { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                data        : JSON.stringify({
                    class   : classVal,
                    section : sectionVal,
                    a_date  : dateVal,
                    students: students,
                }),
                success: function (response) {
                    if (response.status === 'success') {
                        Swal.fire({
                            title             : 'Successful',
                            text              : response.message,
                            icon              : 'success',
                            confirmButtonColor: 'rgb(122 190 255)',
                        });
                    } else {
                        Swal.fire({
                            title             : 'Error',
                            text              : response.message,
                            icon              : 'error',
                            confirmButtonColor: 'rgb(122 190 255)',
                        });
                    }
                },
                error: function (xhr) {
                    const msg = xhr.responseJSON?.message ?? 'Something went wrong!';
                    Swal.fire({
                        title             : 'Error',
                        text              : msg,
                        icon              : 'error',
                        confirmButtonColor: 'rgb(122 190 255)',
                    });
                }
            });
        });

    });
</script>
@endsection