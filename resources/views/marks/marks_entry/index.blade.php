@extends('marks.index')

@section('sub-content')
<div class="container-fluid">

    {{-- Flash Messages via SweetAlert --}}
    @if (Session::has('success'))
        @push('marks-swal-scripts')
            <script>
                swal("Successful", "{{ Session::get('success') }}", "success");
            </script>
        @endpush
    @endif

    @if (Session::has('error'))
        @push('marks-swal-scripts')
            <script>
                swal("Error", "{{ Session::get('error') }}", "error");
            </script>
        @endpush
    @endif

    <div class="row">
        <div class="col-md-12">
            <div class="card border-0 bg-white">
                <div class="card-header flex-wrap bg-white d-flex align-items-center justify-content-between">
                    <h5 class="mb-0 mt-0">Marks Entry</h5>
                    <a href="{{ route('marks.marks-entry.index') }}" class="btn bg-light btn-sm">
                        <span class="mdi mdi-chevron-left me-2"></span>Back
                    </a>
                </div>
                <div class="card-body">

                    {{-- Filter Form --}}
                    <form id="class-section-form" novalidate>
                        <div class="row">
                            {{-- Class --}}
                            <div class="form-group col-md-6">
                                <label for="marks_class_id" class="mt-2">
                                    Class <span class="text-danger">*</span>
                                </label>
                                <select name="class" id="marks_class_id" class="form-control" {{ count($classes) === 0 ? 'disabled' : 'required' }}>
                                    @if (count($classes) > 0)
                                        <option value="">Select Class</option>
                                        @foreach ($classes as $key => $class)
                                            <option value="{{ $key }}" {{ request()->get('class') == $key ? 'selected' : '' }}>
                                                {{ $class }}
                                            </option>
                                        @endforeach
                                    @else
                                        <option value="" disabled selected>No Class Found</option>
                                    @endif
                                </select>
                                <span class="invalid-feedback form-invalid fw-bold" id="class-error" role="alert"></span>
                            </div>
                            {{-- Section --}}
                            <div class="form-group col-md-6">
                                <label for="marks_section_id" class="mt-2">
                                    Section <span class="text-danger">*</span>
                                </label>
                                <select name="section" id="marks_section_id" class="form-control" required>
                                    <option value="">Select Section</option>
                                </select>
                                <span class="invalid-feedback form-invalid fw-bold" id="section-error" role="alert"></span>
                            </div>
                        </div>

                        <div class="row">
                            {{-- Subject --}}
                            <div class="form-group col-md-6">
                                <label for="marks_subject_id" class="mt-2">
                                    Subject <span class="text-danger">*</span>
                                </label>
                                <select name="subject" id="marks_subject_id" class="form-control @error('subject') is-invalid @enderror" required>
                                    <option value="">Select Subject</option>
                                </select>
                                @error('subject')
                                    <span class="invalid-feedback form-invalid fw-bold" role="alert">
                                        {{ $message }}
                                    </span>
                                @enderror
                            </div>
                            {{-- Exam --}}
                            <div class="form-group col-md-6">
                                <label for="exam_id" class="mt-2">
                                    Exam <span class="text-danger">*</span>
                                </label>
                                <select name="exam" id="exam_id" class="form-control @error('exam') is-invalid @enderror" required>
                                    @if (count($exams) > 0)
                                        <option value="">Select Exam</option>
                                        @foreach ($exams as $key => $exam)
                                            <option value="{{ $key }}"
                                                {{ old('exam') == $key ? 'selected' : '' }}>
                                                {{ $exam }}
                                            </option>
                                        @endforeach
                                    @else
                                        <option value="" disabled selected>No Exam Found</option>
                                    @endif
                                </select>
                                @error('exam')
                                    <span class="invalid-feedback form-invalid fw-bold" role="alert">
                                        {{ $message }}
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="mt-3 d-flex align-items-center gap-3">
                            <button type="button" id="show-details" class="btn btn-primary">
                                Show Details
                            </button>
                            <span id="marksExistsMsg" class="text-danger fw-bold"></span>
                            <img src="{{ config('myconfig.myloader') }}" alt="Loading..." id="loader" style="display:none; width:40px;">
                        </div>
                    </form>

                    {{-- Student Marks Form --}}
                    <div id="std-container" class="mt-4" style="display:none;">
                        <form id="std-form">
                            @csrf

                            <div class="table-responsive">
                                <table class="table table-bordered table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Roll No.</th>
                                            <th>Student</th>
                                            <th>Obtained Marks</th>
                                            <th>Attendance</th>
                                        </tr>
                                    </thead>
                                    <tbody id="std-table-body"></tbody>
                                </table>
                            </div>

                            <div class="mt-3">
                                <button type="submit" class="btn btn-primary" id="section-updateBtn">
                                    Update
                                </button>
                            </div>
                        </form>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('marks-scripts')
<script>
$(document).ready(function () {

    // ------------------------------------------------------------------ //
    //  Cached selectors
    // ------------------------------------------------------------------ //
    const loader         = $('#loader');
    const classSelect    = $('#marks_class_id');
    const sectionSelect  = $('#marks_section_id');
    const subjectSelect  = $('#marks_subject_id');
    const examSelect     = $('#exam_id');
    const stdContainer   = $('#std-container');
    const stdTableBody   = $('#std-table-body');
    const marksExistsMsg = $('#marksExistsMsg');

    // In-memory store for marks/attendance changes across pagination or re-renders
    let studentMarks = {};

    // ------------------------------------------------------------------ //
    //  Helper: reset student table and hide container
    // ------------------------------------------------------------------ //
    function resetStudentTable() {
        stdContainer.hide();
        stdTableBody.html('');
        marksExistsMsg.text('');
        studentMarks = {};
    }

    // ------------------------------------------------------------------ //
    //  Fetch subjects for a given class
    // ------------------------------------------------------------------ //
    function getSubjects(classId) {
        subjectSelect.html('<option value="">Loading…</option>');
        if (!classId) {
            subjectSelect.html('<option value="">Select Subject</option>');
            return;
        }
        loader.show();
        $.ajax({
            url: siteUrl + '/subjects',
            type: 'GET',
            dataType: 'JSON',
            data: { class_id: classId },
            success: function (data) {
                subjectSelect.empty();
                if (data.data && Object.keys(data.data).length > 0) {
                    subjectSelect.append('<option value="">Select Subject</option>');
                    $.each(data.data, function (id, name) {
                        subjectSelect.append('<option value="' + id + '">' + name + '</option>');
                    });
                } else {
                    subjectSelect.append('<option value="">No Subjects Available</option>');
                }
            },
            error: function (xhr) {
                console.error('Error fetching subjects:', xhr.responseText);
                subjectSelect.html('<option value="">Error loading subjects</option>');
            },
            complete: function () {
                loader.hide();
            }
        });
    }

    // ------------------------------------------------------------------ //
    //  On page load: restore dropdown chain from URL params if present
    // ------------------------------------------------------------------ //
    const initialClass   = classSelect.val();
    const initialSection = '{{ request()->get("section") }}';
    if (initialClass) {
        getMarksWithoutAllSections(initialClass, function () {
            if (initialSection) {
                sectionSelect.val(initialSection);
            }
        });
        getSubjects(initialClass);
    }

    // ------------------------------------------------------------------ //
    //  Class change → reload sections & subjects, reset table
    // ------------------------------------------------------------------ //
    classSelect.on('change', function () {
        resetStudentTable();
        const classId = $(this).val();
        getMarksWithoutAllSections(classId, function () {});
        getSubjects(classId);
    });

    // ------------------------------------------------------------------ //
    //  Section change → always reset table (different students)
    // ------------------------------------------------------------------ //
    sectionSelect.on('change', function () {
        resetStudentTable();
    });

    // ------------------------------------------------------------------ //
    //  Subject / Exam change → check if marks exist first:
    //    • marks exist  → show message, hide & reset the table
    //    • no marks yet → leave the table as-is (don’t reset)
    // ------------------------------------------------------------------ //
    function onSubjectOrExamChange() {
        const allSelected = classSelect.val() && sectionSelect.val() && subjectSelect.val() && examSelect.val();
        if (stdContainer.is(':visible') && allSelected) {
            checkExistingMarks({ onExistsReset: true, onNotExists: false });
        } else {
            marksExistsMsg.text('');
        }
    }

    subjectSelect.on('change', onSubjectOrExamChange);
    examSelect.on('change', onSubjectOrExamChange);

    // ------------------------------------------------------------------ //
    //  jQuery Validate: filter form
    // ------------------------------------------------------------------ //
    $('#class-section-form').validate({
        rules: {
            class:   { required: true },
            section: { required: true },
            subject: { required: true },
            exam:    { required: true },
        },
        messages: {
            class:   { required: 'Please select a class.'   },
            section: { required: 'Please select a section.' },
            subject: { required: 'Please select a subject.' },
            exam:    { required: 'Please select an exam.'   },
        },
        errorPlacement: function (error, element) {
            // Place errors in the dedicated span next to each select
            const errorSpan = element.siblings('.invalid-feedback');
            if (errorSpan.length) {
                errorSpan.html(error);
            } else {
                error.insertAfter(element);
            }
        },
        highlight: function (element) {
            $(element).addClass('is-invalid');
        },
        unhighlight: function (element) {
            $(element).removeClass('is-invalid');
        }
    });

    // ------------------------------------------------------------------ //
    //  "Show Details" button → validate → check existing → load students
    // ------------------------------------------------------------------ //
    $('#show-details').on('click', function () {
        marksExistsMsg.text('');
        if ($('#class-section-form').valid()) {
            checkExistingMarks();
        }
    });

    // ------------------------------------------------------------------ //
    //  Check whether marks already exist for this combination
    // ------------------------------------------------------------------ //
    // options.onExistsReset  {bool} - if true, hide & reset table when marks exist
    // options.onNotExists    {bool} - if false, skip reloading students (subject-change flow)
    function checkExistingMarks(options) {
        options = options || { onExistsReset: false, onNotExists: true };
        loader.show();
        $.ajax({
            url: '{{ route("marks.check-existing") }}',
            type: 'POST',
            dataType: 'JSON',
            headers:  { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            data: {
                class:   classSelect.val(),
                section: sectionSelect.val(),
                exam:    examSelect.val(),
                subject: subjectSelect.val(),
            },
            success: function (response) {
                if (response.studentsCount !== undefined && response.studentsCount === 0) {
                    marksExistsMsg.text('No students found in the selected class and section.');
                    if (options.onExistsReset) resetStudentTable();
                    return;
                }
                if (response.exists) {
                    marksExistsMsg.text(response.message);
                    // Subject was changed and marks already exist: hide the stale table
                    if (options.onExistsReset) resetStudentTable();
                } else {
                    marksExistsMsg.text('');
                    // Only auto-load students when triggered by "Show Details" button
                    if (options.onNotExists !== false) {
                        loadStudentsForMarks();
                    }
                }
            },
            error: function (xhr) {
                console.error('check-existing error:', xhr.responseText);
                marksExistsMsg.text('An error occurred. Please try again.');
            },
            complete: function () {
                loader.hide();
            }
        });
    }

    // ------------------------------------------------------------------ //
    //  Load students into the marks table
    // ------------------------------------------------------------------ //
    function loadStudentsForMarks() {
        const classId   = classSelect.val();
        const sectionId = sectionSelect.val();

        loader.show();
        $.ajax({
            url:      '{{ route("marks.students") }}',
            type:     'POST',
            dataType: 'JSON',
            headers:  { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            data: {
                class_id:   classId,
                section_id: sectionId,
            },
            success: function (response) {
                let html = '';

                // Server-side error or empty result
                if (response.status === 'error') {
                    html = '<tr><td colspan="4" class="text-center text-danger">' + (response.message || 'No students found.') + '</td></tr>';
                    stdTableBody.html(html);
                    stdContainer.show();
                    return;
                }

                const students = response.data;

                if (!students || students.length === 0) {
                    html = '<tr><td colspan="4" class="text-center">No students found for the selected class and section.</td></tr>';
                } else {
                    $.each(students, function (index, std) {
                        const savedMarks  = (studentMarks[std.srno] && studentMarks[std.srno].marks !== undefined) ? studentMarks[std.srno].marks : '';
                        const savedStatus = studentMarks[std.srno] !== undefined ? studentMarks[std.srno].status : true;

                        html += `
                        <tr>
                            <td>${std.rollno}</td>
                            <td>
                                <input type="hidden" name="students[${index}][srno]" value="${std.srno}" data-srno="${std.srno}">
                                ${std.student_name}
                            </td>
                            <td>
                                <input type="text" name="students[${index}][marks]" value="${savedMarks}" class="form-control std-marks" inputmode="numeric" data-index="${index}" data-srno="${std.srno}">
                                <span class="invalid-feedback fw-bold marks-error" role="alert" style="display:none;"></span>
                            </td>
                            <td class="text-center">
                                <input type="checkbox" name="students[${index}][status]" value="1"  class="status-checkbox" data-index="${index}" data-srno="${std.srno}"  ${savedStatus ? 'checked' : ''}>
                            </td>
                        </tr>`;
                    });
                }

                stdTableBody.html(html);
                stdContainer.show();
            },
            error: function (xhr) {
                console.error('Error loading students:', xhr.responseText);
                stdTableBody.html('<tr><td colspan="4" class="text-danger text-center">Failed to load students. Please try again.</td></tr>');
                stdContainer.show();
            },
            complete: function () {
                loader.hide();
            }
        });
    }

    // ------------------------------------------------------------------ //
    //  Live: save marks input to memory
    // ------------------------------------------------------------------ //
    $(document).on('input', '.std-marks', function () {
        const srno  = $(this).data('srno');
        const value = $(this).val();
        studentMarks[srno] = studentMarks[srno] || {};
        studentMarks[srno].marks = value;
    });

    // ------------------------------------------------------------------ //
    //  Live: save attendance checkbox to memory
    // ------------------------------------------------------------------ //
    $(document).on('change', '.status-checkbox', function () {
        const srno    = $(this).data('srno');
        const checked = $(this).prop('checked');
        studentMarks[srno] = studentMarks[srno] || {};
        studentMarks[srno].status = checked;
    });

    // ------------------------------------------------------------------ //
    //  Numeric-only keypress guard on marks inputs
    // ------------------------------------------------------------------ //
    $(document).on('keypress', '.std-marks', function (e) {
        const char = String.fromCharCode(e.which);
        if (!/^\d$/.test(char)) {
            $(this).siblings('.marks-error').show().text('Only numbers are allowed.');
            e.preventDefault();
        } else {
            $(this).siblings('.marks-error').hide().text('');
        }
    });

    // ------------------------------------------------------------------ //
    //  Submit marks form
    // ------------------------------------------------------------------ //
    $('#std-form').on('submit', function (e) {
        e.preventDefault();

        const updatedStudents = [];
        $.each(studentMarks, function (srno, changes) {
            updatedStudents.push({
                srno:   srno,
                marks:  changes.marks  !== undefined ? changes.marks  : null,
                status: changes.status !== undefined ? (changes.status ? 1 : 0) : 1,
            });
        });

        if (updatedStudents.length === 0) {
            Swal.fire({
                icon: 'info',
                title: 'No Changes Made',
                text:  "No changes were made to the students' marks or attendance.",
                confirmButtonText: 'OK'
            });
            return;
        }

        $('#section-updateBtn').prop('disabled', true);
        loader.show();

        $.ajax({
            url:    '{{ route("marks.marks-entry.store") }}',
            method: 'POST',
            dataType: 'JSON',
            headers:  { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            data: {
                class_id:         classSelect.val(),
                section_id:       sectionSelect.val(),
                subject_id:       subjectSelect.val(),
                exam_id:          examSelect.val(),
                updated_students: JSON.stringify(updatedStudents),
            },
            success: function (response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Successful',
                        text:  'Student marks and attendance have been updated.',
                        confirmButtonText: 'OK'
                    });
                    /* .then(function () {
                        // Reset UI without full page reload
                        studentMarks = {};
                        stdContainer.hide();
                        stdTableBody.html('');
                    }); */
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text:  response.message || 'Something went wrong. Please try again.',
                        confirmButtonText: 'OK'
                    });
                }
            },
            error: function (xhr) {
                console.error('Submit error:', xhr.responseText);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text:  'Something went wrong while updating. Please try again.',
                    confirmButtonText: 'OK'
                });
            },
            complete: function () {
                $('#section-updateBtn').prop('disabled', false);
                loader.hide();
            }
        });
    });

});
</script>
@endsection