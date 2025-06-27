@extends('admin.index')

@section('sub-content')
<div class="container">

    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card">
                    <div class="card-header">
                        {{ 'Edit Marks' }}
                        <a href="{{ route('admin.marks-master.index') }}" class="btn btn-warning btn-sm" style="float: right;">Back</a>

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
                                    <label for="min_marks" class="mt-2">Enter Minimum Marks <span
                                            class="text-danger">*</span></label>
                                    <input type="text" name="min_marks"
                                        class="form-control @error('min_marks') is-invalid @enderror"
                                        placeholder="Minimum Marks"
                                        value="{{ old('min_marks', isset($marksMaster) ? $marksMaster->min_marks : '') }}" id="min_marks" required>
                                    @error('min_marks')
                                    <span class="invalid-feedback form-invalid fw-bold" role="alert">
                                        {{ $message }}
                                    </span>
                                    @enderror
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="max_marks" class="mt-2">Enter Maximum Marks <span
                                            class="text-danger">*</span></label>
                                    <input type="text" name="max_marks"
                                        class="form-control @error('max_marks') is-invalid @enderror"
                                        placeholder="Maximum Marks"
                                        value="{{ old('max_marks', isset($marksMaster) ? $marksMaster->max_marks : '') }}" id="max_marks" required>
                                    @error('max_marks')
                                    <span class="invalid-feedback form-invalid fw-bold" role="alert">
                                        {{ $message }}
                                    </span>
                                    @enderror
                                </div>
                            </div>
                            <div class="mt-3">
                                <input class="btn btn-primary" type="submit" value="Update"><span><img src="{{ config('myconfig.myloader') }}" alt="Loading..." class="loader"
                                id="loader" style="display:none; width:10%;"></span>
                            </div>
                        </form>
                    </div>
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
    });
</script>
@endsection