@extends('marks.index')
@section('sub-content')
    <div class="container-fluid">
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
        <div class="row">
            <div class="col-md-12">
                <div class="card border-0 bg-white">
                    <div class="card-header flex-wrap bg-white d-flex align-items-center justify-content-between">
                        <h5 class="mb-0 mt-0">{{ 'Marks Report' }}</h5>
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
                                                <option value="{{ $key }}"
                                                    {{ old('class') == $key ? 'selected' : '' }}>{{ $class }}
                                                </option>
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
                                    <input type="hidden" id="initialSectionId" value="{{ old('section') }}">
                                    <select name="section" id="section_id"
                                        class="form-control @error('section') is-invalid @enderror" required>
                                        <option value="">Select Section</option>
                                    </select>
                                    @error('section')
                                        <span class="invalid-feedback form-invalid fw-bold"
                                            role="alert">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="row">
                                <div class="form-group col-md-4">
                                    <input type="hidden" name="current_session" value='' id="current_session">
                                    <label for="exam_id" class="mt-2">Exam <span class="text-danger">*</span></label>
                                    <select name="exam" id="exam_id"
                                        class="form-control @error('exam') is-invalid @enderror" required>
                                        <option value="">Select Exam</option>
                                        @if (count($exams) > 0)
                                            @foreach ($exams as $key => $exam)
                                                <option value="{{ $key }}"
                                                    {{ old('exam') == $key ? 'selected' : '' }}>{{ $exam }}
                                                </option>
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
                                <div class="form-group col-md-4">
                                    <label for="subject_id" class="mt-2">Subject <span
                                            class="text-danger">*</span></label>
                                    <input type="hidden" id="initialSubjectId" value="{{ old('subject[]') }}">
                                    <select name="subject[]" id="subject_id" {{-- class="form-control @error('subject') is-invalid @enderror" multiple required> --}}
                                        class="form-control @error('subject') is-invalid @enderror" required>
                                        <option value="">All Subject</option>
                                    </select>
                                    @error('subject')
                                        <span class="invalid-feedback form-invalid fw-bold"
                                            role="alert">{{ $message }}</span>
                                    @enderror
                                </div>
                                <div class="form-group col-md-4">
                                    <label for="std_id" class="mt-2">Student <span class="text-danger">*</span></label>
                                    <select name="std_id" id="std_id"
                                        class="form-control @error('std_id') is-invalid @enderror" required>
                                        <option value="">All Students</option>
                                    </select>
                                    @error('std_id')
                                        <span class="invalid-feedback form-invalid fw-bold"
                                            role="alert">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="mt-3">
                                <button type="button" id="show-details" class="btn btn-primary">Show Details</button><img src="{{ config('myconfig.myloader') }}" alt="Loading..." class="loader" id="loader" style="display:none; width:10%;">
                            </div>
                        </form>
                        <div id="std-container" class="mt-4">
                            <form>
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead id="std-table-head">
                                        </thead>
                                        <tbody id="std-table-body">
                                        </tbody>
                                    </table>
                                </div>
                                <div class="row">
                                    <div class="mt-3">
                                        <button type="button" class="btn btn-primary" id="export-button">Download Excel</button>
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
            let initialSubjectId = $('#initialSubjectId').val();
            getClassSection(initialClassId, initialSectionId);

            function getSubjects(initialClassesId, initialSubjectId) {
                var classSelect = $('#class_id');
                var subjectSelect = $('#subject_id');
                var classId = initialClassesId;
                var subjectId = initialSubjectId;

                function fetchSubjects(classId) {
                    if (classId) {
                        loader.show();
                        $.ajax({
                            url: siteUrl + '/subjects',
                            type: 'GET',
                            dataType: 'JSON',
                            data: {
                                class_id: classId,
                            },
                            success: function(data) {
                                /* subjectSelect.empty();
                                if (data.data && Object.keys(data.data).length > 0) {
                                    subjectSelect.append('<option value="">Select Subject</option>');
                                    $.each(data.data, function (id, name) {
                                        subjectSelect.append('<option value="' + id + '">' + name + '</option>');
                                    });
                                } else {
                                    subjectSelect.append('<option value="">No subjects available</option>');
                                } */
                                subjectSelect.empty();
                                if (data.data && Object.keys(data.data).length > 0) {
                                    // Collect all IDs
                                    let allIds = Object.keys(data.data).join(',');
                                    // Add "All Subjects" with all IDs as its value
                                    subjectSelect.append('<option value="' + allIds +
                                        '">All Subjects</option>');
                                    // Add individual subjects
                                    $.each(data.data, function(id, name) {
                                        subjectSelect.append('<option value="' + id + '">' +
                                            name + '</option>');
                                    });
                                } else {
                                    subjectSelect.append(
                                        '<option value="">No subjects available</option>');
                                }

                                if (initialSubjectId) {
                                    subjectSelect.val(initialSubjectId);
                                }
                            },
                            complete: function() {
                                loader.hide();

                            },
                            error: function(data) {
                                $.each(data.message, function(error) {
                                    console.error('Error fetching sections:', error);
                                });
                            }
                        });
                    } else {
                        subjectSelect.empty();
                        subjectSelect.append('<option value="">Select Subject</option>');
                    }
                }
                fetchSubjects(classId);
                classSelect.change(function() {
                    var classId = $(this).val();
                    loader.show();
                    fetchSubjects(classId);
                });
            }
            getSubjects(initialClassId, initialSubjectId);
            $('#std-form').hide();
            $('#std-container').hide();
            $('#class-section-form').validate({
                rules: {
                    exam: {
                        required: true,
                    },
                    subject: {
                        required: true,
                    },
                    std: {
                        required: true,
                    },
                    class: {
                        required: true,
                    },
                    section: {
                        required: true,
                    },
                },
                messages: {
                    exam: {
                        required: "Please select a exam.",
                    },
                    subject: {
                        required: "Please select a subject.",
                    },
                    std: {
                        required: "Please select a student.",
                    },
                    class: {
                        required: "Please select a class.",
                    },
                    section: {
                        required: "Please select a section.",
                    },
                },
            });
            var stdSelect = $('#std_id');
            // $('#subject_id').select2();
            getStd();
            $('#show-details').on('click', function() {
                $('#export-button').hide();
                if ($('#class-section-form').valid()) {
                    const classId = $('#class_id').val();
                    const sessionId = $('#current_session').val();
                    const exam = $('#exam_id').val();
                    const subject = $('#subject_id').val();
                    const std = stdSelect.val();
                    loader.show();
                    $('#std-container').show();
                    $('#std-table-head').html('');
                    if (classId && sessionId && exam && subject && std) {
                        $('#std-form').show();
                        $.ajax({
                            url: '{{ route('marks.marks-report.get') }}',
                            type: 'GET',
                            dataType: 'JSON',
                            data: {
                                class: classId,
                                session: sessionId,
                                exam: exam,
                                subject: subject,
                                std_id: std,
                            },
                            success: function(data) {
                                let subjects = [];
                                let studentsData = {};
                                if (data.data && data.data.length > 0) {
                                    // First pass: collect all subjects and initialize student data
                                    $.each(data.data, function(index, subjectData) {
                                        subjects.push(subjectData.subject);
                                        $.each(subjectData.students, function(j, student) {
                                            if (!studentsData[student
                                                    .roll_number]) {
                                                studentsData[student
                                                    .roll_number] = {
                                                    name: student.name,
                                                    marks: {}
                                                };
                                            }
                                            studentsData[student.roll_number].marks[
                                                    subjectData.subject] = student
                                                .marks;
                                        });
                                    });
                                    // Generate table header
                                    let headerHtml = '<tr><th>Student</th>';
                                    subjects.forEach(subject => {
                                        headerHtml += `<th>${subject}</th>`;
                                    });
                                    headerHtml += '<th>Total</th></tr>';
                                    $('#std-table-head').html(headerHtml);
                                    // Generate table body
                                    let bodyHtml = '';
                                    Object.keys(studentsData).forEach(rollNumber => {
                                        let student = studentsData[rollNumber];
                                        let totalMarks = 0;
                                        bodyHtml +=
                                            `<tr><td>${rollNumber}. ${student.name}</td>`;
                                        subjects.forEach(subject => {
                                            let mark = student.marks[subject];
                                            totalMarks += (mark == null || mark ==
                                                'N/A') ? 0 : mark;
                                            bodyHtml +=
                                                `<td>${mark == null ? 'N/A' : mark}</td>`;
                                        });
                                        bodyHtml += `<td>${totalMarks}</td></tr>`;
                                    });
                                    $('#std-table-body').html(bodyHtml);
                                    $('#export-button').show();
                                }else {
                                    // If no data, show a "No records found" row
                                    $('#std-table-head').html('');
                                    $('#std-table-body').html('<tr><td colspan="100%">No records found</td></tr>');

                                    // Hide export button if no data
                                    $('#export-button').hide();
                                }
                            },
                            complete: function() {
                                loader.hide();

                            },
                            error: function(xhr) {
                                console.error(xhr.responseText);
                            }
                        });
                    }
                }
            });

            function getExcelReport() {
                const classId = $('#class_id').val();
                const sessionId = $('#current_session').val();
                const exam = $('#exam_id').val();
                const std = $('#std_id').val();
                const subjects = $('#subject_id').val();
                // Start with the base URL
                let exportUrl = "{{ route('marks.marks-report.excel') }}?class=" + classId +
                    "&session=" + sessionId +
                    "&exam=" + exam +
                    "&subject=" + subjects +
                    "&std_id=" + std;
                // Redirect to the export URL
                window.location.href = exportUrl;
            }
            $('#export-button').on('click', function() {
                loader.show();
                getExcelReport();
                setTimeout(() => loader.hide(), 3000); // hide after 3 sec
            });
            $('#class_id, #section_id, #subject_id, #std_id, #exam_id').change(() => {
                $('#std-container').hide();
                $('#std-table-head').html('');
                $('#std-form').hide();
            });
        });
    </script>
@endsection
