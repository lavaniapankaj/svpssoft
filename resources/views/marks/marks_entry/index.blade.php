@extends('marks.index')
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
                    {{ 'Marks Entry' }}
                    <a href="{{ route('marks.marks-entry.index') }}" class="btn btn-warning btn-sm"
                        style="float: right;">Back</a>

                </div>
                <div class="card-body">
                    <form id="class-section-form">

                        <div class="row">
                            <div class="form-group col-md-6">
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
                                @error('class')
                                <span class="invalid-feedback form-invalid fw-bold"
                                    role="alert">{{ $message }}</span>
                                @enderror

                            </div>


                            <div class="form-group col-md-6">
                                <label for="section_id" class="mt-2">Section <span
                                        class="text-danger">*</span></label>
                                <input type="hidden" id="initialSectionId"
                                    value="{{ old('section') }}">
                                <select name="section" id="section_id"
                                    class="form-control @error('section') is-invalid @enderror" required>
                                    <option value="">Select Section</option>

                                </select>
                                @error('section')
                                <span class="invalid-feedback form-invalid fw-bold"
                                    role="alert">{{ $message }}</span>
                                @enderror
                                <img src="{{ config('myconfig.myloader') }}" alt="Loading..." class="loader"
                                    id="loader" style="display:none; width:10%;">
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group col-md-6">
                                <label for="subject_id" class="mt-2">Subject <span
                                        class="text-danger">*</span></label>
                                <input type="hidden" id="initialSubjectId"
                                    value="{{ old('subject') }}">
                                <select name="subject" id="subject_id"
                                    class="form-control @error('subject') is-invalid @enderror" required>
                                    <option value="">Select Subject</option>
                                </select>
                                @error('subject')
                                <span class="invalid-feedback form-invalid fw-bold"
                                    role="alert">{{ $message }}</span>
                                @enderror
                                <img src="{{ config('myconfig.myloader') }}" alt="Loading..." class="loader"
                                    id="loader" style="display:none; width:10%;">
                            </div>
                            <div class="form-group col-md-6">
                                <label for="exam_id" class="mt-2">Exam <span class="text-danger">*</span></label>
                                <select name="exam" id="exam_id"
                                    class="form-control @error('exam') is-invalid @enderror" required>
                                    <option value="">Select Exam</option>
                                    @if (count($exams) > 0)
                                        @foreach ($exams as $key => $exam)
                                            <option value="{{ $key }}" {{ old('exam') == $key ? 'selected' : ''}}>{{ $exam }}</option>
                                        @endforeach
                                    @else
                                        <option value="">No Exam Found</option>
                                    @endif
                                </select>
                                @error('exam')
                                <span class="invalid-feedback form-invalid fw-bold"
                                    role="alert">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                        <div class="mt-3">
                            <button type="button" id="show-details" class="btn btn-primary">
                                Show Details</button>
                        </div>

                    </form>


                    <div id="std-container" class="mt-4">
                        <form id="std-form">
                            <!--<form action="{{ route('marks.marks-entry.store') }}" method="POST" id="std-form">-->
                            @csrf
                            <table class="table table-responsible">
                                <input type="hidden" name="current_session" value='' id="current_session">
                                <input type="hidden" name="hidden_class" value='' id="hidden_class">
                                <input type="hidden" name="hidden_section" value='' id="hidden_section">
                                <input type="hidden" name="hidden_subject" value='' id="hidden_subject">
                                <input type="hidden" name="hidden_exam" value='' id="hidden_exam">

                                <thead>
                                    <tr>
                                        <th>Roll No.</th>
                                        <th>Student</th>
                                        <th>Obtained Marks</th>
                                        <th>Attendance</th>
                                    </tr>
                                </thead>
                                <tbody>

                                </tbody>
                            </table>
                            <div id="std-pagination"></div>
                            <div class="row">
                                <div class="mt-3">
                                    <button type="submit" class="btn btn-primary"
                                        id="section-updateBtn">Update</button>
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
@section('marks-scripts')
<script>
    $(document).ready(function() {
        let initialClassId = $('#class_id').val();
        let initialSectionId = $('#initialSectionId').val();
        getClassSection(initialClassId, initialSectionId);
        let initialSubjectId = $('#initialSubjectId').val();
        getClassSubject(initialClassId, initialSubjectId);
        $('#std-form').hide();
        let studentMarks = {};

        $('#class-section-form').validate({
            rules: {
                exam: {
                    required: true
                },
                subject: {
                    required: true
                },
                class: {
                    required: true
                },
                section: {
                    required: true
                },
            },
            messages: {
                exam: {
                    required: "Please select an exam."
                },
                subject: {
                    required: "Please select a subject."
                },
                class: {
                    required: "Please select a class."
                },
                section: {
                    required: "Please select a section."
                },
            },
        });

        $('#show-details').on('click', function() {
            if ($('#class-section-form').valid()) {
                const classId = $('#class_id').val();
                const sectionId = $('#section_id').val();
                const sessionId = $('#current_session').val();
                const exam = $('#exam_id').val();
                const subject = $('#subject_id').val();
                const $paginationContainer = $('#std-pagination');
                let page = 1;
                loader.show();
                $('#hidden_class').val(classId);
                $('#hidden_section').val(sectionId);
                $('#hidden_subject').val(subject);
                $('#hidden_exam').val(exam);

                function stdDetails(page) {
                    if (classId && sectionId && sessionId) {
                        $('#std-form').show();
                        $.ajax({
                            url: '{{ route('stdNameFather.get') }}',
                            type: 'GET',
                            dataType: 'JSON',
                            data: {
                                class_id: classId,
                                section_id: sectionId,
                                session_id: sessionId,
                                page: page,
                            },
                            success: function(students) {
                                let stdHtml = '';
                                $.each(students.data, function(index, std) {
                                    // Retrieve previously saved marks and status for this student
                                    const savedMarks = studentMarks[std.srno] ? studentMarks[std.srno].marks : '';
                                    const savedStatus = studentMarks[std.srno] ? studentMarks[std.srno].status : true;

                                    stdHtml += `<tr>
                                    <td>${std.rollno}</td>
                                    <td>
                                        <input type="hidden" name="students[${index}][srno]" value="${std.srno}" class="std-srno" id="std-srno" data-index="${index}" data-srno="${std.srno}">
                                        ${std.student_name}
                                    </td>
                                    <td>
                                       <input type="text" name="students[${index}][marks]" value="${savedMarks}" class="std-marks" id="stdMarks" data-index="${index}" data-srno="${std.srno}">
                                       <span class="invalid-feedback form-invalid fw-bold" id="marks-error" role="alert"></span>
                                    </td>
                                     <td>
                                        <input type="checkbox" name="students[${index}][status]" value="1" class="status-checkbox" data-index="${index}" data-srno="${std.srno}" ${savedStatus ? 'checked' : ''}>
                                    </td>
                                </tr>`;
                                });

                                if (stdHtml === '') {
                                    stdHtml = '<tr><td colspan="4">No Student found</td></tr>';
                                }
                                $('#std-container table tbody').html(stdHtml);
                                updatePaginationControls(students);
                            },
                            complete: function() {
                                loader.hide();
                            },
                            error: function(xhr) {
                                console.log(xhr);
                                console.error(xhr.responseText);
                            }
                        });
                    }
                }

                // Pagination click event
                $(document).on('click', '#std-pagination .page-link', function(e) {
                    e.preventDefault();
                    var page = $(this).data('page');
                    stdDetails(page);
                });

                // Event to save marks when they change
                $(document).on('input', '.std-marks', function() {
                    const srno = $(this).data('srno');
                    const value = $(this).val();
                    if (!studentMarks[srno]) {
                        studentMarks[srno] = {};
                    }
                    studentMarks[srno].marks = value;
                });

                // Event to save checkbox status
                $(document).on('change', '.status-checkbox', function() {
                    const srno = $(this).data('srno');
                    const isChecked = $(this).prop('checked');
                    if (!studentMarks[srno]) {
                        studentMarks[srno] = {};
                    }
                    studentMarks[srno].status = isChecked;
                });

                function updatePaginationControls(data) {
                    var paginationHtml = '';
                    if (data.last_page > 1) {
                        paginationHtml += '<ul class="pagination">';

                        if (data.current_page > 1) {
                            paginationHtml += `<li class="page-item"><a class="page-link" href="#" data-page="${data.current_page - 1}">Previous</a></li>`;
                        }

                        for (let i = 1; i <= data.last_page; i++) {
                            if (i == data.current_page) {
                                paginationHtml += `<li class="page-item active"><span class="page-link">${i}</span></li>`;
                            } else {
                                paginationHtml += `<li class="page-item"><a class="page-link" href="#" data-page="${i}">${i}</a></li>`;
                            }
                        }

                        if (data.current_page < data.last_page) {
                            paginationHtml += `<li class="page-item"><a class="page-link" href="#" data-page="${data.current_page + 1}">Next</a></li>`;
                        }

                        paginationHtml += '</ul>';
                    }
                    $paginationContainer.html(paginationHtml);
                }

                stdDetails(page);
            }
        });

        // Numeric validation for marks input
        $(document).on('keypress', '.std-marks', function(event) {
            const char = String.fromCharCode(event.which);
            if (isNaN(char) || char.trim() === '') {
                $(this).siblings('.invalid-feedback').show().text("Only numbers are allowed.");
                event.preventDefault(); // Prevent non-numeric input
            } else {
                $(this).siblings('.invalid-feedback').hide();
            }
        });

        // Submit the form and pass all changes from all pages
        $('#std-form').on('submit', function(e) {
            e.preventDefault();

            const updatedStudents = [];

            // Loop through the studentMarks object and push the updated student data
            $.each(studentMarks, function(srno, changes) {
                // Ensure that marks and status are included in the submission
                updatedStudents.push({
                    srno: srno,
                    marks: changes.marks !== undefined ? changes.marks : null, // Use null if marks are undefined
                    status: changes.status !== undefined ? changes.status : 1 // Use 1 if status is undefined
                });
            });

            // Check if there are any updates to submit
            if (updatedStudents.length > 0) {
                // AJAX request to submit the data

                $.ajax({
                    url: '{{ route("marks.marks-entry.store") }}', // Ensure the route is correct
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        hidden_class: $('#hidden_class').val(),
                        hidden_section: $('#hidden_section').val(),
                        hidden_subject: $('#hidden_subject').val(),
                        hidden_exam: $('#hidden_exam').val(),
                        current_session: $('#current_session').val(),
                        updated_students: JSON.stringify(updatedStudents) // Send the updated students data as JSON
                    },
                    success: function(response) {
                        if (response.success == true) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Successful',
                                text: 'The students marks and attendance have been updated.',
                                confirmButtonText: 'OK'
                            }).then(() => {
                                location.reload();
                            });
                        }
                    },
                    error: function(response) {
                        if (response.error == true) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: 'Something went wrong while updating the students data. Please try again.',
                                confirmButtonText: 'OK'
                            }).then(() => {
                                location.reload();
                            });
                        }
                    }
                });
            } else {
                Swal.fire({
                    icon: 'info',
                    title: 'No Changes Made',
                    text: 'No changes were made to the students\' marks or attendance.',
                    confirmButtonText: 'OK'
                }).then(() => {
                                location.reload();
                });
            }
        });

    });
</script>
@endsection