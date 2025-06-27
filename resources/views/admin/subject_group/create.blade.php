@extends('admin.index')

@section('sub-content')
    <div class="container">

        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card">
                        <div class="card-header">
                            {{ 'Create New Subject Group' }}
                            <a href="{{ route('admin.subject-group-master.index') }}" class="btn btn-warning btn-sm"
                                style="float: right;">Back</a>
                        </div>

                        <div class="card-body">
                            <form action="{{ route('admin.subject-group-master.store') }}" method="POST" id="basic-form">
                                @csrf
                                <div class="row">
                                    <div class="form-group col-md-6">
                                        <label for="class_id" class="mt-2">Class <span
                                                class="text-danger">*</span></label>
                                        <select name="class_id" id="class_id" class="form-control" required>
                                            <option value="">Select Class</option>
                                            @if (count($classes) > 0)
                                                @foreach ($classes as $key => $class)
                                                    <option value="{{ $key }}"
                                                        {{ old('class_id') == $key ? 'selected' : '' }}>
                                                        {{ $class }}</option>
                                                @endforeach
                                            @else
                                                <option value="">No Classes Found</option>
                                            @endif
                                        </select>
                                    </div>
                                    <div class="form-group col-md-6">
                                        <input type="hidden" name="subjectGroup_controller" id="subjectGroup-controller"
                                            value="SubjectGroupSection">
                                        <label for="subject_id" class="mt-2">Subject <span
                                                class="text-danger">*</span></label>
                                        <input type="hidden" id="initialSubjectId"
                                            value="{{ old('subject_id') }}">
                                        <select name="subject_id" id="subject_id" class="form-control" required>
                                            <option value="">Select Subject</option>

                                        </select>
                                    </div>
                                    <img src="{{ config('myconfig.myloader') }}" alt="Loading..." class="loader"
                                        id="loader" style="display:none; width:10%;">
                                </div>

                                <div class="row">
                                    <div class="form-group col-md-6">
                                        <label for="subject" class="mt-2">Enter Sub Subject Name <span
                                                class="text-danger">*</span></label>
                                        <input type="text" name="subject"
                                            class="form-control @error('subject') is-invalid @enderror"
                                            placeholder="Sub Subject" value="{{ old('subject') }}" id="subject" required>
                                        @error('subject')
                                            <span class="invalid-feedback form-invalid fw-bold" role="alert">
                                                {{ $message }}
                                            </span>
                                        @enderror
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label for="priority" class="mt-2">Enter Display Priority <span
                                                class="text-danger">*</span></label>
                                        <input type="text" name="priority"
                                            class="form-control @error('priority') is-invalid @enderror"
                                            placeholder="Priority" value="{{ old('priority') }}" id="priority" required>
                                        @error('priority')
                                            <span class="invalid-feedback form-invalid fw-bold" role="alert">
                                                {{ $message }}
                                            </span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="form-group form-check col-md-6">
                                        <div class="form-check">
                                            <input class="form-check-input @error('by_m_g') is-invalid @enderror"
                                                value="1" type="radio" name="by_m_g" id="by_marks"
                                                {{ old('by_m_g' == 1 ? 'checked=' . '"' . 'checked' . '"' : '') }}>
                                            <label class="form-check-label" for="by_marks">
                                                Result By Marks
                                            </label>
                                        </div>
                                    </div>
                                    <div class="form-group form-check col-md-6">
                                        <div class="form-check">
                                            <input class="form-check-input @error('by_m_g') is-invalid @enderror"
                                                value="2" type="radio" name="by_m_g" id="by_grade"
                                                {{ old('by_m_g' == 2 ? 'checked=' . '"' . 'checked' . '"' : '') }}>
                                            <label class="form-check-label" for="by_grade">
                                                Result By Grade
                                            </label>
                                        </div>
                                    </div>
                                    @error('by_m_g')
                                        <span class="invalid-feedback form-invalid fw-bold" role="alert">
                                            {{ $message }}
                                        </span>
                                    @enderror
                                </div>

                                <div class="mt-3">
                                    <input class="btn btn-primary" type="submit" value="Save">
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
            getClassSubject($('#class_id').val(), $('#initialSubjectId').val(), $('#subjectGroup-controller'));
        });
    </script>
@endsection
