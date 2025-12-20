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
                    @if($errors->has('grades') || $errors->has('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            @if($errors->has('grades'))
                                {{ $errors->first('grades') }}
                            @endif
                            @if($errors->has('error'))
                                {{ $errors->first('error') }}
                            @endif
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <form action="{{ route('admin.globalOverAllMarksGrade.store') }}" method="POST" id="overallGradeForm">
                        @csrf

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

                        {{-- Grades Section - Hidden by default --}}
                        <div class="grades-container mb-3 {{ old('class_id') ? '' : 'd-none' }}" id="gradesSection">
                            <label class="form-label fw-semibold">Overall Grades</label>

                            <table class="table table-sm table-bordered" id="gradesTable">
                                <thead>
                                    <tr>
                                        <th>Grade</th>
                                        <th>Min Marks</th>
                                        <th>Max Marks</th>
                                        <th width="50"></th>
                                    </tr>
                                </thead>
                                <tbody id="gradesBody">
                                    @if(old('grades'))
                                        @foreach(old('grades') as $index => $grade)
                                            <tr>
                                                <td>
                                                    <input type="text"
                                                           name="grades[{{ $index }}][name]"
                                                           class="form-control @error('grades.'.$index.'.name') is-invalid @enderror"
                                                           value="{{ $grade['name'] ?? '' }}"
                                                           placeholder="Grade name"
                                                           required>
                                                    @error('grades.'.$index.'.name')
                                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                                    @enderror
                                                </td>
                                                <td>
                                                    <input type="number"
                                                           name="grades[{{ $index }}][min]"
                                                           class="form-control @error('grades.'.$index.'.min') is-invalid @enderror"
                                                           min="0"
                                                           step="0.01"
                                                           value="{{ $grade['min'] ?? '' }}"
                                                           placeholder="Min"
                                                           required>
                                                    @error('grades.'.$index.'.min')
                                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                                    @enderror
                                                </td>
                                                <td>
                                                    <input type="number"
                                                           name="grades[{{ $index }}][max]"
                                                           class="form-control @error('grades.'.$index.'.max') is-invalid @enderror"
                                                           min="0"
                                                           step="0.01"
                                                           value="{{ $grade['max'] ?? '' }}"
                                                           placeholder="Max"
                                                           required>
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
                            <button type="button" class="btn btn-sm btn-secondary" id="addGradeBtn">
                                <i class="mdi mdi-plus"></i> Add Grade
                            </button>
                        </div>

                        {{-- Submit --}}
                        <div class="mt-4 {{ old('class_id') ? '' : 'd-none' }}" id="saveSection">
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
    let gradeIndex = {{ old('grades') ? count(old('grades')) : 0 }};
    let skipInitialLoad = {{ old('grades') ? 'true' : 'false' }};

    // Load grades when both exam & class selected
    $('#classSelect').change(function () {
        const classId = $('#classSelect').val();

        // Hide grades section if either is not selected
        if (!classId) {
            $('#gradesSection').addClass('d-none');
            $('#saveSection').addClass('d-none');
            if (!skipInitialLoad) {
                $('#gradesBody').html('<tr><td colspan="4" class="text-center text-muted">No grades added yet</td></tr>');
            }
            return;
        }

        // Show grades section and load data
        $('#gradesSection').removeClass('d-none');

        // Don't load from server if we have old input (validation errors)
        if (skipInitialLoad) {
            skipInitialLoad = false;
            $('#saveSection').removeClass('d-none');
            return;
        }

        $('#gradesBody').html('<tr><td colspan="4" class="text-center text-muted">Loading...</td></tr>');
        $('#saveSection').addClass('d-none');

        loadGrades(classId);
    });

    // Trigger on page load if both are selected (for validation errors or pre-selected values)
    const initialClass = $('#classSelect').val();

    if (initialClass && !skipInitialLoad) {
        $('#gradesSection').removeClass('d-none');
        loadGrades(initialClass);
    }

    function loadGrades(classId) {
        $.ajax({
            // url: "{{ route('admin.overAllGrade.get') }}",
            url: "{{ route('admin.get.globalOverAllMarksGrade') }}",
            type: "GET",
            data: {class: classId },
            success: function (res) {
                $('#gradesBody').empty();
                gradeIndex = 0;

                if (res.status === 'success' && res.data.length > 0) {
                    res.data.forEach((g) => {
                        appendGradeRow(g.grade_name, g.min_marks, g.max_marks);
                    });
                } else {
                    $('#gradesBody').html('<tr><td colspan="4" class="text-center text-muted">No grades found. Add new ones below.</td></tr>');
                }

                $('#saveSection').removeClass('d-none');
            },
            error: function () {
                $('#gradesBody').html('<tr><td colspan="4" class="text-center text-danger">Error loading grades.</td></tr>');
            }
        });
    }

    // Add grade
    $('#addGradeBtn').click(function () {
        appendGradeRow('', '', '');
        $('#saveSection').removeClass('d-none');
    });

    // Remove grade
    $(document).on('click', '.removeGrade', function () {
        $(this).closest('tr').remove();

        if ($('#gradesBody tr').length === 0) {
            $('#gradesBody').html('<tr><td colspan="4" class="text-center text-muted">No grades added yet</td></tr>');
        }
    });

    function appendGradeRow(name = '', min = '', max = '') {
        $('#gradesBody').find('td[colspan="4"]').closest('tr').remove(); // remove placeholder

        const currentIndex = gradeIndex;
        $('#gradesBody').append(`
            <tr>
                <td>
                    <input type="text"
                           name="grades[${currentIndex}][name]"
                           class="form-control"
                           value="${name}"
                           placeholder="Grade name"
                           required>
                </td>
                <td>
                    <input type="number"
                           name="grades[${currentIndex}][min]"
                           class="form-control"
                           min="0"
                           step="0.01"
                           value="${min}"
                           placeholder="Min"
                           required>
                </td>
                <td>
                    <input type="number"
                           name="grades[${currentIndex}][max]"
                           class="form-control"
                           min="0"
                           step="0.01"
                           value="${max}"
                           placeholder="Max"
                           required>
                </td>
                <td>
                    <button type="button" class="btn btn-sm btn-danger removeGrade">
                        <i class="mdi mdi-delete"></i>
                    </button>
                </td>
            </tr>
        `);
        gradeIndex++;
    }
});
</script>
@endsection