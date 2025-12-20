@extends('admin.index')

@section('sub-content')
<div class="container-fluid py-4">
    @if (Session::has('success'))
        @push('swal-scripts')
            <script>
                swal("Successful", "{{ Session::get('success') }}", "success")
            </script>
        @endpush
    @endif

    @if (Session::has('error'))
        @push('swal-scripts')
            <script>
                swal("Error", "{{ Session::get('error') }}", "error")
            </script>
        @endpush
    @endif
    <div class="row">
        <div class="col-md-12">
            <div class="card border-0 bg-white">
                <div class="card-header flex-wrap bg-white d-flex align-items-center justify-content-between gap-2 flex-wrap">
                    <div class="d-flex align-items-center justify-content-between gap-2">
                    <a href="{{ route('admin.marks-master.create') }}" class="btn {{ request()->routeIs('admin.marks-master.create') ? 'mianbtn activebtn ' : 'mianbtn defbtn' }}">
                        Manage Subject-wise Marks & Grade
                    </a>

                    <a href="{{ route('admin.subjectWiseOverallGradeIndex') }}"
                       class="btn {{ request()->routeIs('admin.subjectWiseOverallGradeIndex') ? 'mianbtn activebtn ' : 'mianbtn defbtn'}}">
                        Manage Exam-wise Overall Grade
                    </a>

                    <a href="{{ route('admin.overAllGrade.index') }}" class="btn {{ request()->routeIs('admin.overAllGrade.index') ? 'mianbtn activebtn ' : 'mianbtn defbtn' }}">
                        Manage Overall Grade
                    </a>
                </div>
                    <a href="{{ route('admin.marks-master.index') }}" class="btn bg-light btn-sm" ><span class="mdi mdi-chevron-left me-2"></span>Back</a>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.marks-master.store') }}" method="POST" id="basic-form">
                        @csrf

                        {{-- Hidden field to identify edit mode --}}
                        @if(isset($isEdit) && $isEdit)
                            <input type="hidden" name="is_edit" value="1">
                            <input type="hidden" name="mark_id" value="{{ $markId ?? '' }}">
                        @endif

                        {{-- Exam Selection --}}
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Select Exam</label>
                            <select name="exam_id" id="examSelect" class="form-control @error('exam_id') is-invalid @enderror" required>
                                <option value="">Select Exam</option>
                                @if (count($exams) > 0)
                                    @foreach ($exams as $key => $exam)
                                        <option value="{{ $key }}" {{ old('exam_id', $selectedExam ?? '') == $key ? 'selected' : '' }}>{{ $exam }}</option>
                                    @endforeach
                                @else
                                    <option value="">No Exam Found</option>
                                @endif
                            </select>
                            @error('exam_id')
                                <span class="invalid-feedback form-invalid fw-bold" role="alert">{{ $message }}</span>
                            @enderror
                        </div>

                        {{-- Class Selection --}}
                        <div class="mb-4">
                            <label class="form-label fw-semibold">Select Class</label>
                            <select name="class_id" id="classSelect" class="form-select @error('class_id') is-invalid @enderror">
                                <option value="">Select Class</option>
                                @if (count($classes) > 0)
                                    @foreach ($classes as $key => $class)
                                        <option value="{{ $key }}" {{ old('class_id', $selectedClass ?? '') == $key ? 'selected' : '' }}>{{ $class }}</option>
                                    @endforeach
                                @else
                                    <option value="">No Class Found</option>
                                @endif
                            </select>
                            <span class="invalid-feedback d-none" id="classError" role="alert">Please select an exam first!</span>
                            @error('class_id')
                                <span class="invalid-feedback form-invalid fw-bold d-block" role="alert">{{ $message }}</span>
                            @enderror
                        </div>

                        {{-- Subjects Container --}}
                        @error('subjects')
                            <div class="alert alert-danger mb-3">{{ $message }}</div>
                        @enderror
                        <div id="subjectsContainer" class="row g-3"></div>

                        {{-- Submit Button --}}
                        <div class="mt-4 d-none" id="saveSection">
                            <button type="submit" id="saveMarks" class="btn btn-primary">
                                {{ isset($isEdit) && $isEdit ? 'Update Marks' : 'Save Marks' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('admin-scripts')
<script>
$(document).ready(function() {
    const oldExamId = "{{ old('exam_id', $selectedExam ?? '') }}";
    const oldClassId = "{{ old('class_id', $selectedClass ?? '') }}";
    const oldSubjects = @json(old('subjects', []));
    const subjectErrors = @json($errors->get('subjects.*'));
    const isEdit = {{ isset($isEdit) && $isEdit ? 'true' : 'false' }};

    let gradeIndexes = {};

    // Page load with validation errors or edit mode
    if (oldExamId && oldClassId) {
        $('#examSelect').val(oldExamId);
        $('#classSelect').val(oldClassId);
        loadSubjects(oldClassId, oldSubjects, subjectErrors);
    }

    // When exam changes
    $('#examSelect').change(function() {
        const examId = $(this).val();
        const classId = $('#classSelect').val();

        $('#subjectsContainer').empty();
        $('#saveSection').addClass('d-none');

        // If class is already selected, reload subjects
        if (examId && classId) {
            loadSubjects(classId);
        }
    });

    // When class changes
    $('#classSelect').change(function() {
        const classId = $(this).val();
        const examId = $('#examSelect').val();

        $('#subjectsContainer').empty();
        $('#saveSection').addClass('d-none');

        if (!examId) {
            $('#classSelect').addClass('is-invalid');
            $('#classError').removeClass('d-none');
            $('#classSelect').val('');
            return;
        }

        $('#classSelect').removeClass('is-invalid');
        $('#classError').addClass('d-none');

        if (classId) {
            loadSubjects(classId);
        }
    });

    function loadSubjects(classId, oldValues = {}, validationErrors = {}) {
        $.ajax({
            url: "{{ route('admin.subjectMarks') }}",
            type: "GET",
            data: { class: classId, exam: $('#examSelect').val() },
            beforeSend: function() {
                $('#subjectsContainer').html('<p class="text-center text-muted">Loading subjects...</p>');
            },
            success: function(response) {
                console.log(response);

                $('#subjectsContainer').empty();

                if (response.data && Array.isArray(response.data) && response.data.length > 0) {
                    response.data.forEach(subject => {
                        // Check if we have old form data, otherwise use API response data
                        const minValue = Object.keys(oldValues).length > 0
                            ? (oldValues[subject.id]?.min ?? subject.min ?? '')
                            : (subject.min ?? '');

                        const maxValue = Object.keys(oldValues).length > 0
                            ? (oldValues[subject.id]?.max ?? subject.max ?? '')
                            : (subject.max ?? '');

                        // Build grade rows - prioritize old form data, then API data
                        let gradesData = [];
                        if (Object.keys(oldValues).length > 0 && oldValues[subject.id]?.grades) {
                            gradesData = oldValues[subject.id].grades;
                        } else if (subject.grades && subject.grades.length > 0) {
                            // Map API response grade structure to form structure
                            gradesData = subject.grades.map(g => ({
                                name: g.grade_name,
                                min: g.min_marks,
                                max: g.max_marks
                            }));
                        }

                        gradeIndexes[subject.id] = gradesData.length ? gradesData.length - 1 : -1;

                        let gradesRows = '';
                        gradesData.forEach((grade, index) => {
                            const gradeNameError = validationErrors[`subjects.${subject.id}.grades.${index}.name`];
                            const gradeMinError = validationErrors[`subjects.${subject.id}.grades.${index}.min`];
                            const gradeMaxError = validationErrors[`subjects.${subject.id}.grades.${index}.max`];

                            gradesRows += `
                                <tr>
                                    <td>
                                        <input type="text"
                                            name="subjects[${subject.id}][grades][${index}][name]"
                                            class="form-control ${gradeNameError ? 'is-invalid' : ''}"
                                            placeholder="Grade name"
                                            value="${grade.name || ''}">
                                        ${gradeNameError ? `<span class="invalid-feedback d-block">${gradeNameError}</span>` : ''}
                                    </td>
                                    <td>
                                        <input type="number"
                                            name="subjects[${subject.id}][grades][${index}][min]"
                                            class="form-control ${gradeMinError ? 'is-invalid' : ''}"
                                            placeholder="Min"
                                            value="${grade.min ?? ''}"
                                            min="0">
                                        ${gradeMinError ? `<span class="invalid-feedback d-block">${gradeMinError}</span>` : ''}
                                    </td>
                                    <td>
                                        <input type="number"
                                            name="subjects[${subject.id}][grades][${index}][max]"
                                            class="form-control ${gradeMaxError ? 'is-invalid' : ''}"
                                            placeholder="Max"
                                            value="${grade.max ?? ''}"
                                            min="0">
                                        ${gradeMaxError ? `<span class="invalid-feedback d-block">${gradeMaxError}</span>` : ''}
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-danger removeGrade">
                                            <i class="mdi mdi-delete"></i>
                                        </button>
                                    </td>
                                </tr>`;
                        });

                        // Validation errors for min/max fields
                        const hasMinError = validationErrors[`subjects.${subject.id}.min`];
                        const hasMaxError = validationErrors[`subjects.${subject.id}.max`];

                        // Adding the subject card with grades and min/max inputs
                        $('#subjectsContainer').append(`
                            <div class="col-md-4">
                                <div class="card shadow-sm border-0 h-100">
                                    <div class="card-body">
                                        <h5 class="card-title">${subject.name}</h5>

                                        <div class="mb-2">
                                            <label class="form-label">Min Marks</label>
                                            <input type="number"
                                                name="subjects[${subject.id}][min]"
                                                class="form-control ${hasMinError ? 'is-invalid' : ''}"
                                                placeholder="Enter Min Marks"
                                                value="${minValue}"
                                                min="0">
                                            ${hasMinError ? `<span class="invalid-feedback d-block">${hasMinError}</span>` : ''}
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Max Marks</label>
                                            <input type="number"
                                                name="subjects[${subject.id}][max]"
                                                class="form-control ${hasMaxError ? 'is-invalid' : ''}"
                                                placeholder="Enter Max Marks"
                                                value="${maxValue}"
                                                min="0">
                                            ${hasMaxError ? `<span class="invalid-feedback d-block">${hasMaxError}</span>` : ''}
                                        </div>

                                        <div class="grades-section mt-3">
                                            <label class="form-label fw-semibold">Grades for ${subject.name}</label>
                                            <table class="table table-sm table-bordered mb-2" id="gradesTable_${subject.id}">
                                                <thead>
                                                    <tr>
                                                        <th>Grade</th>
                                                        <th>Min</th>
                                                        <th>Max</th>
                                                        <th width="50"></th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    ${gradesRows || '<tr><td colspan="4" class="text-center text-muted">No grades added yet</td></tr>'}
                                                </tbody>
                                            </table>
                                            <button type="button" class="btn btn-sm btn-secondary addGradeBtn" data-subject="${subject.id}">
                                                <i class="mdi mdi-plus"></i> Add Grade
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `);
                    });
                    $('#saveSection').removeClass('d-none');
                } else {
                    $('#subjectsContainer').html('<p class="text-muted text-center">No subjects found for this class.</p>');
                }
            },
            error: function() {
                $('#subjectsContainer').html('<p class="text-danger text-center">Something went wrong. Please try again.</p>');
            }
        });
    }

    // Add grade row
    $(document).on('click', '.addGradeBtn', function() {
        const subjectId = $(this).data('subject');
        const tableBody = $(`#gradesTable_${subjectId} tbody`);

        // Remove "no grades" message if it exists
        tableBody.find('td[colspan="4"]').closest('tr').remove();

        if (gradeIndexes[subjectId] == undefined) {
            gradeIndexes[subjectId] = 0;
        } else {
            gradeIndexes[subjectId]++;
        }

        const newRow = `
            <tr>
                <td><input type="text" name="subjects[${subjectId}][grades][${gradeIndexes[subjectId]}][name]" class="form-control" placeholder="Grade name"></td>
                <td><input type="number" name="subjects[${subjectId}][grades][${gradeIndexes[subjectId]}][min]" class="form-control" min="0" placeholder="Min"></td>
                <td><input type="number" name="subjects[${subjectId}][grades][${gradeIndexes[subjectId]}][max]" class="form-control" min="0" placeholder="Max"></td>
                <td><button type="button" class="btn btn-sm btn-danger removeGrade"><i class="mdi mdi-delete"></i></button></td>
            </tr>`;
        tableBody.append(newRow);
    });

    // Remove grade row
    $(document).on('click', '.removeGrade', function() {
        const row = $(this).closest('tr');
        const tbody = row.closest('tbody');
        row.remove();

        // If no rows left, show "no grades" message
        if (tbody.find('tr').length == 0) {
            tbody.html('<tr><td colspan="4" class="text-center text-muted">No grades added yet</td></tr>');
        }
    });
});
</script>
@endsection