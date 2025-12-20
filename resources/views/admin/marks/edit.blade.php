@extends('admin.index')

@section('sub-content')
<div class="container-fluid">

    <div class="row ">
        <div class="col-md-12">
            <div class="card border-0 bg-white">
                <div class="card-header flex-wrap bg-white d-flex align-items-center justify-content-between">
                    <h5 class="mb-0 mt-0">{{ 'Edit Marks' }}</h5>
                        <a href="{{ route('admin.marks-master.index') }}" class="btn bg-light btn-sm" ><span class="mdi mdi-chevron-left me-2"></span>Back</a>

                    </div>

                    <div class="card-body">
                        <form action="{{ route('admin.marks-master.update', $marksMaster->id ?? '') }}" method="POST" id="basic-form">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="id" id="id" value="{{ isset($marksMaster) ? $marksMaster->id : '' }}">

                            <div class="row">
                                <div class="form-group col-md-4">
                                    <label for="class_id" class="mt-2">Class <span
                                            class="text-danger">*</span></label>
                                    <input type="hidden" id="initialClassId"
                                        value="{{ old('class_id', isset($marksMaster) ? $marksMaster->class_id : '') }}">
                                    <select name="class_id" id="class_id"
                                        class="form-control @error('class_id') is-invalid @enderror" required>
                                        <option value="">Select Class</option>
                                        @if (count($classes) > 0)
                                        @foreach ($classes as $key => $class)
                                        <option value="{{ $key }}"
                                            {{ isset($marksMaster) && $marksMaster->class_id == $key ? 'selected' : '' }}>
                                            {{ $class }}
                                        </option>
                                        @endforeach
                                        @else
                                        <option value="">No Class Found</option>
                                        @endif
                                    </select>
                                    @error('class_id')
                                    <span class="invalid-feedback form-invalid fw-bold" role="alert">
                                        {{ $message }}
                                    </span>
                                    @enderror
                                </div>
                                <div class="form-group col-md-4">

                                    <label for="subject_id" class="mt-2">Subject <span
                                            class="text-danger">*</span></label>
                                    <input type="hidden" id="initialSubjectId"
                                        value="{{ old('subject_id',isset($marksMaster) ? $marksMaster->subject_id : '') }}">
                                    <select name="subject_id" id="subject_id"
                                        class="form-control @error('subject_id') is-invalid @enderror" required>
                                        <option value="">Select Subject</option>
                                    </select>
                                    @error('subject_id')
                                    <span class="invalid-feedback form-invalid fw-bold" role="alert">
                                        {{ $message }}
                                    </span>
                                    @enderror
                                </div>
                                <div class="form-group col-md-4">
                                    <label for="exam_id" class="mt-2">Exam <span
                                            class="text-danger">*</span></label>
                                    <input type="hidden" id="initialExamId"
                                        value="{{ isset($marksMaster) ? $marksMaster->exam_id : '' }}">
                                    <select name="exam_id" id="exam_id"
                                        class="form-control @error('exam_id') is-invalid @enderror" required>
                                        <option value="">Select Exam</option>
                                        @if (count($exams) > 0)
                                        @foreach ($exams as $key => $exam)
                                        <option value="{{ $key }}"
                                            {{ isset($marksMaster) && $marksMaster->exam_id == $key ? 'selected' : '' }}>
                                            {{ $exam }}
                                        </option>
                                        @endforeach
                                        @else
                                        <option value="">No Exam Found</option>
                                        @endif
                                    </select>
                                    @error('exam_id')
                                    <span class="invalid-feedback form-invalid fw-bold" role="alert">
                                        {{ $message }}
                                    </span>
                                    @enderror
                                </div>

                            </div>

                            <div class="row">
                                <div class="form-group col-md-6">
                                    <label for="min_marks" class="mt-2">Enter Minimum Marks <span class="text-danger">*</span></label>
                                    <input type="number" min="1" name="min_marks" class="form-control @error('min_marks') is-invalid @enderror" placeholder="Minimum Marks" value="{{ old('min_marks', isset($marksMaster) ? $marksMaster->min_marks : '') }}" id="min_marks" required>
                                    @error('min_marks')
                                    <span class="invalid-feedback form-invalid fw-bold" role="alert">
                                        {{ $message }}
                                    </span>
                                    @enderror
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="max_marks" class="mt-2">Enter Maximum Marks <span class="text-danger">*</span></label>
                                    <input type="number" min="1" name="max_marks" class="form-control @error('max_marks') is-invalid @enderror" placeholder="Maximum Marks" value="{{ old('max_marks', isset($marksMaster) ? $marksMaster->max_marks : '') }}" id="max_marks" required>
                                    @error('max_marks')
                                    <span class="invalid-feedback form-invalid fw-bold" role="alert">
                                        {{ $message }}
                                    </span>
                                    @enderror
                                </div>
                            </div>
                            {{-- Subject Grades --}}
                            <div class="grades-section">
                                <label class="form-label fw-semibold">Grades</label>
                                <table class="table table-sm table-bordered" id="gradesTable">
                                    <thead>
                                        <tr>
                                            <th>Grade</th>
                                            <th>Min</th>
                                            <th>Max</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                         @forelse($subjectGrades as $index => $grade)
                                            <tr>
                                                <td>
                                                    <input type="text" name="grades[{{ $grade->id }}][name]" value="{{ old("grades.{$grade->id}.name", $grade->grade_name) }}" class="form-control @error("grades.{$grade->id}.name") is-invalid @enderror">
                                                    @error("grades.{$grade->id}.name")
                                                        <span class="invalid-feedback d-block">{{ $message }}</span>
                                                    @enderror
                                                </td>
                                                <td>
                                                    <input type="number" name="grades[{{ $grade->id }}][min]" value="{{ old("grades.{$grade->id}.min", $grade->min_marks) }}" class="form-control @error("grades.{$grade->id}.min") is-invalid @enderror">
                                                    @error("grades.{$grade->id}.min")
                                                        <span class="invalid-feedback d-block">{{ $message }}</span>
                                                    @enderror
                                                </td>
                                                <td>
                                                    <input type="number" name="grades[{{ $grade->id }}][max]" value="{{ old("grades.{$grade->id}.max", $grade->max_marks) }}" class="form-control @error("grades.{$grade->id}.max") is-invalid @enderror">
                                                    @error("grades.{$grade->id}.max")
                                                        <span class="invalid-feedback d-block">{{ $message }}</span>
                                                    @enderror
                                                </td>
                                                <td>
                                                    <button type="button" class="btn btn-danger btn-sm remove-grade">Remove</button>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr id="noGradesRow">
                                                <td colspan="4" class="text-center text-muted">No grades added yet.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                                <button type="button" class="btn btn-success btn-sm mt-2" id="addGradeBtn">Add Grade</button>
                            </div>






                            <div class="mt-4">
                                <input class="btn btn-primary" type="submit" value="Update"><span><img src="{{ config('myconfig.myloader') }}" alt="Loading..." class="loader" id="loader" style="display:none; width:5%;"></span>
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
        getClassSubject($('#class_id').val(), $('#initialSubjectId').val());

        let gradeIndex = 1000; // Start index for new grades

        // Add new grade row
        $('#addGradeBtn').click(function() {
            let row = `
            <tr>
                <td>
                    <input type="text" name="grades[new_${gradeIndex}][name]" class="form-control" placeholder="Grade Name" required>
                </td>
                <td>
                    <input type="number" name="grades[new_${gradeIndex}][min]" class="form-control" placeholder="Min Marks" min="0" required>
                </td>
                <td>
                    <input type="number" name="grades[new_${gradeIndex}][max]" class="form-control" placeholder="Max Marks" min="0" required>
                </td>
                <td>
                    <button type="button" class="btn btn-danger btn-sm remove-grade">Remove</button>
                </td>
            </tr>`;
            $('#gradesTable tbody').append(row);
            gradeIndex++;
        });

        // Remove grade row
        $(document).on('click', '.remove-grade', function() {
            $(this).closest('tr').remove();
        });
    });
</script>
@endsection