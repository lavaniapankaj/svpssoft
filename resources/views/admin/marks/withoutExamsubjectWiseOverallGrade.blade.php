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
                    <a href="{{ route('admin.marks-master.create') }}"
                       class="btn {{ request()->routeIs('admin.marks-master.create') ? 'mianbtn activebtn ' : 'mianbtn defbtn' }}">
                        Manage Subject-wise Marks & Grade
                    </a>
                   <a href="{{ route('admin.subjectWiseOverallGradeIndex') }}"
                       class="btn {{ request()->routeIs('admin.subjectWiseOverallGradeIndex') ? 'mianbtn activebtn ' : 'mianbtn defbtn' }}">
                        Manage Exam-wise Overall Grade
                    </a>

                    <a href="{{ route('admin.overAllGrade.index') }}"
                       class="btn {{ request()->routeIs('admin.overAllGrade.index') ? 'mianbtn activebtn ' : 'mianbtn defbtn' }}">
                        Manage Overall Grade
                    </a>
                    </div>
                    <a href="{{ route('admin.marks-master.index') }}" class="btn bg-light btn-sm">
                        <span class="mdi mdi-chevron-left me-2"></span>Back
                    </a>
                </div>

                <div class="card-body">
                    {{-- Display general validation errors --}}
                    @if($errors->any())
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <ul class="mb-0">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <form action="{{ route('admin.globalOverAllMarksGrade.store') }}" method="POST" id="overallGradeForm">
                        @csrf

                        {{-- Grade Type Selection --}}
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Select Grade Type</label>
                            <select name="grade_type" id="gradeTypeSelect" class="form-control @error('grade_type') is-invalid @enderror" required>
                                <option value="">Select Grade Type</option>
                                <option value="overall" {{ old('grade_type', $gradeType ?? '') == 'overall' ? 'selected' : '' }}>Overall Grade</option>
                                <option value="subject_wise" {{ old('grade_type', $gradeType ?? '') == 'subject_wise' ? 'selected' : '' }}>Subject-wise Overall Grade</option>
                            </select>
                            @error('grade_type')
                                <span class="invalid-feedback d-block">{{ $message }}</span>
                            @enderror
                        </div>

                        {{-- Class --}}
                        <div class="mb-4">
                            <label class="form-label fw-semibold">Select Class</label>
                            <select name="class_id" id="classSelect" class="form-select @error('class_id') is-invalid @enderror" required>
                                <option value="">Select Class</option>
                                @foreach ($classes as $key => $class)
                                    <option value="{{ $key }}" {{ old('class_id', $selectedClass ?? '') == $key ? 'selected' : '' }}>
                                        {{ $class }}
                                    </option>
                                @endforeach
                            </select>
                            @error('class_id')
                                <span class="invalid-feedback d-block">{{ $message }}</span>
                            @enderror
                        </div>

                        {{-- Overall Grades Section --}}
                        <div class="grades-container mb-3 {{ old('grade_type') == 'overall' && old('class_id') ? '' : 'd-none' }}" id="overallGradesSection">
                            <label class="form-label fw-semibold">Overall Grades</label>

                            <table class="table table-sm table-bordered" id="overallGradesTable">
                                <thead>
                                    <tr>
                                        <th>Grade</th>
                                        <th>Min Marks</th>
                                        <th>Max Marks</th>
                                        <th width="50"></th>
                                    </tr>
                                </thead>
                                <tbody id="overallGradesBody">
                                    @if(old('grades'))
                                        @foreach(old('grades') as $index => $grade)
                                            <tr>
                                                <td>
                                                    <input type="text" name="grades[{{ $index }}][name]"
                                                           class="form-control @error('grades.'.$index.'.name') is-invalid @enderror"
                                                           value="{{ $grade['name'] ?? '' }}" placeholder="Grade name" required>
                                                    @error('grades.'.$index.'.name')
                                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                                    @enderror
                                                </td>
                                                <td>
                                                    <input type="number" name="grades[{{ $index }}][min]"
                                                           class="form-control @error('grades.'.$index.'.min') is-invalid @enderror"
                                                           min="0" step="0.01" value="{{ $grade['min'] ?? '' }}" placeholder="Min" required>
                                                    @error('grades.'.$index.'.min')
                                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                                    @enderror
                                                </td>
                                                <td>
                                                    <input type="number" name="grades[{{ $index }}][max]"
                                                           class="form-control @error('grades.'.$index.'.max') is-invalid @enderror"
                                                           min="0" step="0.01" value="{{ $grade['max'] ?? '' }}" placeholder="Max" required>
                                                    @error('grades.'.$index.'.max')
                                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                                    @enderror
                                                </td>
                                                <td>
                                                    <button type="button" class="btn btn-sm btn-danger removeGrade">
                                                        <i class="mdi mdi-delete"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    @else
                                        <tr><td colspan="4" class="text-center text-muted">No grades added yet</td></tr>
                                    @endif
                                </tbody>
                            </table>
                            <button type="button" class="btn btn-sm btn-secondary" id="addOverallGradeBtn">
                                <i class="mdi mdi-plus"></i> Add Grade
                            </button>
                        </div>

                        {{-- Subject-wise Overall Grades Section --}}
                        <div class="grades-container mb-3 {{ old('grade_type') == 'subject_wise' && old('class_id') ? '' : 'd-none' }}" id="subjectWiseGradesSection">
                            <label class="form-label fw-semibold">Subject-wise Overall Grades</label>
                            <div id="subjectWiseContainer">
                                @if(old('subjects'))
                                    {{-- Will be populated by JS if old data exists --}}
                                @else
                                    <p class="text-center text-muted">Select class to load subjects</p>
                                @endif
                            </div>
                        </div>

                        {{-- Submit --}}
                        <div class="mt-4 {{ (old('grade_type') && old('class_id')) ? '' : 'd-none' }}" id="saveSection">
                            <button type="submit" class="btn btn-primary">Save Grades</button>
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
$(document).ready(function () {
    let overallGradeIndex = {{ old('grades') ? count(old('grades')) : 0 }};
    let hasOldInput = {{ old('grade_type') && old('class_id') ? 'true' : 'false' }};

    // On page load, if there's old input (validation errors), load the appropriate data
    if (hasOldInput) {
        const gradeType = $('#gradeTypeSelect').val();
        const classId = $('#classSelect').val();

        if (gradeType === 'subject_wise' && classId) {
            // Load subject-wise data on validation error
            loadSubjectWiseGrades(classId, true);
        }
    }

    // Grade type change handler
    $('#gradeTypeSelect').change(function () {
        const gradeType = $(this).val();

        // Hide both sections
        $('#overallGradesSection').addClass('d-none');
        $('#subjectWiseGradesSection').addClass('d-none');
        $('#saveSection').addClass('d-none');

        if (!gradeType) return;

        // Check if class are selected
        const classId = $('#classSelect').val();

        if (classId) {
            loadGradesByType(gradeType, classId);
        }
    });

    // Load grades when class selected
    $('#classSelect').change(function () {
        const classId = $('#classSelect').val();
        const gradeType = $('#gradeTypeSelect').val();

        // Hide sections if any required field is not selected
        if (!classId || !gradeType) {
            $('#overallGradesSection').addClass('d-none');
            $('#subjectWiseGradesSection').addClass('d-none');
            $('#saveSection').addClass('d-none');
            return;
        }

        loadGradesByType(gradeType, classId);
    });

    function loadGradesByType(gradeType, classId) {
        if (gradeType === 'overall') {
            loadOverallGrades(classId);
        } else if (gradeType === 'subject_wise') {
            loadSubjectWiseGrades(classId, false);
        }
    }

    function loadOverallGrades(classId) {
        $('#overallGradesSection').removeClass('d-none');
        $('#subjectWiseGradesSection').addClass('d-none');

        // Don't reload if we have old input
        if (hasOldInput && $('#overallGradesBody tr').length > 0 && !$('#overallGradesBody tr td[colspan]').length) {
            $('#saveSection').removeClass('d-none');
            hasOldInput = false;
            return;
        }

        $('#overallGradesBody').html('<tr><td colspan="4" class="text-center text-muted">Loading...</td></tr>');
        $('#saveSection').addClass('d-none');

        $.ajax({
            // url: "{{ route('admin.overAllGrade.get') }}",
            url: "{{ route('admin.get.globalOverAllMarksGrade') }}",
            type: "GET",
            data: { class: classId },
            success: function (res) {
                $('#overallGradesBody').empty();
                overallGradeIndex = 0;

                if (res.status === 'success' && res.data.length > 0) {
                    res.data.forEach((g) => {
                        appendOverallGradeRow(g.grade_name, g.min_marks, g.max_marks);
                    });
                } else {
                    $('#overallGradesBody').html('<tr><td colspan="4" class="text-center text-muted">No grades found. Add new ones below.</td></tr>');
                }

                $('#saveSection').removeClass('d-none');
            },
            error: function () {
                $('#overallGradesBody').html('<tr><td colspan="4" class="text-center text-danger">Error loading grades.</td></tr>');
                $('#saveSection').removeClass('d-none');
            }
        });
    }

    function loadSubjectWiseGrades(classId, skipLoad = false) {
        $('#subjectWiseGradesSection').removeClass('d-none');
        $('#overallGradesSection').addClass('d-none');

        $('#subjectWiseContainer').html('<p class="text-center text-muted">Loading subjects...</p>');
        $('#saveSection').addClass('d-none');

        $.ajax({
            url: "{{ route('admin.global.subjectWiseOverallGrade.get') }}",
            type: "GET",
            data: { class: classId },
            success: function (res) {
                $('#subjectWiseContainer').empty();

                if (res.status === 'success' && res.data.length > 0) {
                    res.data.forEach((subject) => {
                        appendSubjectGradeSection(subject);
                    });
                    // Show save button when subjects are found
                    $('#saveSection').removeClass('d-none');
                    hasOldInput = false;
                } else {
                    $('#subjectWiseContainer').html('<p class="text-center text-muted">No subjects found with child subjects.</p>');
                    // Hide save button if no subjects found
                    $('#saveSection').addClass('d-none');
                }
            },
            error: function (xhr) {
                const errorMsg = xhr.responseJSON?.message || 'Error loading subjects.';
                $('#subjectWiseContainer').html(`<p class="text-center text-danger">${errorMsg}</p>`);
                // Hide save button on error
                $('#saveSection').addClass('d-none');
            }
        });
    }

    function appendSubjectGradeSection(subject) {
        const subjectId = subject.id;
        const subjectName = subject.name;
        const grades = subject.grades || [];

        // Check if we have old input for this subject
        const oldSubjectData = @json(old('subjects'));
        const oldGrades = oldSubjectData && oldSubjectData[subjectId] ? oldSubjectData[subjectId].grades : null;

        let gradesHtml = '';

        // Use old input if available, otherwise use fetched grades
        const gradesToDisplay = oldGrades || grades;

        if (gradesToDisplay && gradesToDisplay.length > 0) {
            Object.keys(gradesToDisplay).forEach((index) => {
                const grade = gradesToDisplay[index];
                const gradeName = grade.grade_name || grade.name || '';
                const minMarks = grade.min_marks ?? grade.min ?? '';
                const maxMarks = grade.max_marks ?? grade.max ?? '';

                gradesHtml += `
                    <tr>
                        <td>
                            <input type="text" name="subjects[${subjectId}][grades][${index}][name]"
                                   class="form-control" value="${gradeName}" placeholder="Grade name" required>
                        </td>
                        <td>
                            <input type="number" name="subjects[${subjectId}][grades][${index}][min]"
                                   class="form-control" min="0" step="0.01" value="${minMarks}" placeholder="Min" required>
                        </td>
                        <td>
                            <input type="number" name="subjects[${subjectId}][grades][${index}][max]"
                                   class="form-control" min="0" step="0.01" value="${maxMarks}" placeholder="Max" required>
                        </td>
                        <td>
                            <button type="button" class="btn btn-sm btn-danger removeSubjectGrade">
                                <i class="mdi mdi-delete"></i>
                            </button>
                        </td>
                    </tr>
                `;
            });
        } else {
            gradesHtml = '<tr><td colspan="4" class="text-center text-muted">No grades added yet</td></tr>';
        }

        const sectionHtml = `
            <div class="subject-grade-section mb-4 border rounded p-3" data-subject-id="${subjectId}">
                <h5 class="mb-3">${subjectName}</h5>
                <input type="hidden" name="subjects[${subjectId}][subject_id]" value="${subjectId}">
                <table class="table table-sm table-bordered subject-grades-table">
                    <thead>
                        <tr>
                            <th>Grade</th>
                            <th>Min Marks</th>
                            <th>Max Marks</th>
                            <th width="50"></th>
                        </tr>
                    </thead>
                    <tbody class="subject-grades-body">
                        ${gradesHtml}
                    </tbody>
                </table>
                <button type="button" class="btn btn-sm btn-secondary addSubjectGradeBtn" data-subject-id="${subjectId}">
                    <i class="mdi mdi-plus"></i> Add Grade
                </button>
            </div>
        `;

        $('#subjectWiseContainer').append(sectionHtml);
    }

    // Add overall grade
    $('#addOverallGradeBtn').click(function () {
        appendOverallGradeRow('', '', '');
        $('#saveSection').removeClass('d-none');
    });

    // Remove overall grade
    $(document).on('click', '.removeGrade', function () {
        $(this).closest('tr').remove();

        if ($('#overallGradesBody tr').length === 0) {
            $('#overallGradesBody').html('<tr><td colspan="4" class="text-center text-muted">No grades added yet</td></tr>');
        }
    });

    // Add subject grade
    $(document).on('click', '.addSubjectGradeBtn', function () {
        const subjectId = $(this).data('subject-id');
        const tbody = $(this).siblings('.subject-grades-table').find('.subject-grades-body');

        // Remove placeholder if exists
        tbody.find('td[colspan="4"]').closest('tr').remove();

        const currentIndex = tbody.find('tr').length;
        const rowHtml = `
            <tr>
                <td>
                    <input type="text" name="subjects[${subjectId}][grades][${currentIndex}][name]" class="form-control" placeholder="Grade name" required>
                </td>
                <td>
                    <input type="number" name="subjects[${subjectId}][grades][${currentIndex}][min]" class="form-control" min="0" step="0.01" placeholder="Min" required>
                </td>
                <td>
                    <input type="number" name="subjects[${subjectId}][grades][${currentIndex}][max]" class="form-control" min="0" step="0.01" placeholder="Max" required>
                </td>
                <td>
                    <button type="button" class="btn btn-sm btn-danger removeSubjectGrade">
                        <i class="mdi mdi-delete"></i>
                    </button>
                </td>
            </tr>
        `;
        tbody.append(rowHtml);
    });

    // Remove subject grade
    $(document).on('click', '.removeSubjectGrade', function () {
        const tbody = $(this).closest('tbody');
        $(this).closest('tr').remove();

        if (tbody.find('tr').length === 0) {
            tbody.html('<tr><td colspan="4" class="text-center text-muted">No grades added yet</td></tr>');
        }
    });

    function appendOverallGradeRow(name = '', min = '', max = '') {
        $('#overallGradesBody').find('td[colspan="4"]').closest('tr').remove();

        const currentIndex = overallGradeIndex;
        $('#overallGradesBody').append(`
            <tr>
                <td>
                    <input type="text" name="grades[${currentIndex}][name]" class="form-control" value="${name}" placeholder="Grade name" required>
                </td>
                <td>
                    <input type="number" name="grades[${currentIndex}][min]" class="form-control" min="0" step="0.01" value="${min}" placeholder="Min" required>
                </td>
                <td>
                    <input type="number" name="grades[${currentIndex}][max]" class="form-control" min="0" step="0.01" value="${max}" placeholder="Max" required>
                </td>
                <td>
                    <button type="button" class="btn btn-sm btn-danger removeGrade">
                        <i class="mdi mdi-delete"></i>
                    </button>
                </td>
            </tr>
        `);
        overallGradeIndex++;
    }
});
</script>
@endsection