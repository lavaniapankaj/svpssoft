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
                    swal("Error", "{{ Session::get('error') }}", "error").then(() => {
                        location.reload();
                    });
                </script>
            @endsection
        @endif
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="card">
                    <div class="card-header">
                        {{ 'Marks Report' }}

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
                                    <img src="{{ config('myconfig.myloader') }}" alt="Loading..." class="loader"
                                        id="loader" style="display:none; width:10%;">
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
                                <div class="form-group col-md-4">
                                    <label for="subject_id" class="mt-2">Subject <span
                                            class="text-danger">*</span></label>
                                    <input type="hidden" id="initialSubjectId"
                                        value="{{ old('subject[]') }}">
                                    <select name="subject[]" id="subject_id"
                                        class="form-control @error('subject') is-invalid @enderror" multiple required>
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
                                <button type="button" id="show-details" class="btn btn-primary">
                                    Show Details</button>
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
                                        <button type="button" class="btn btn-primary" id="export-button">Download
                                            Excel</button>
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
            getClassSubject(initialClassId, initialSubjectId);
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
            $('#subject_id').select2();
            getStd();
            $('#show-details').on('click', function() {
                if ($('#class-section-form').valid()) {
                    const classId = $('#class_id').val();
                    const sessionId = $('#current_session').val();
                    const exam = $('#exam_id').val();
                    const subject = $('#subject_id').val();
                    const std = stdSelect.val();

                    loader.show();
                    $('#std-container').show();

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
                    "&std_id=" + std;

                // Append subjects[] as separate query parameters
                if (subjects && Array.isArray(subjects)) {
                    subjects.forEach(function(subject) {
                        exportUrl += "&subject[]=" + subject;
                    });
                }

                // Redirect to the export URL
                window.location.href = exportUrl;
            }


            $('#export-button').on('click', function() {
                getExcelReport();
            });
            $('#class_id, #section_id, #subject_id, #std_id, #exam_id').change(()=>{
                $('#std-container').hide();
            });

        });
    </script>
@endsection
