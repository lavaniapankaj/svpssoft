@extends('admin.index')
@section('sub-content')
<div class="container">

    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    {{ 'Edit Student Marks' }}
                    <a href="{{ route('admin.editSection.index') }}" class="btn btn-warning btn-sm"
                        style="float: right;">Back</a>

                </div>
                <div class="card-body">
                    <form method="POST" action="" id="update-form">
                        @csrf
                        <div class="row">
                            <div class="form-group col-md-4">
                                <label for="class_id" class="mt-2">Class <span class="text-danger">*</span></label>
                                <input type="hidden" name="current_session" value='' id="current_session">
                                <select name="class_id" id="class_id"
                                    class="form-control mx-1 @error('class_id') is-invalid @enderror">
                                    <option value="">Select Class</option>
                                    @if (count($classes) > 0)
                                    @foreach ($classes as $key => $class)
                                    <option value="{{ $key }}" {{ old('class_id') == $key ? 'selected' : ''}}>{{ $class }}</option>
                                    @endforeach
                                    @else
                                    <option value="">No Class Found</option>
                                    @endif
                                </select>
                                <span class="invalid-feedback form-invalid fw-bold class-error" role="alert"></span>

                            </div>
                            <div class="form-group col-md-4">
                                <label for="section_id" class="mt-2">Section<span class="text-danger">*</span></label>
                                <input type="hidden" id="initialSectionId" name="initialSectionId"
                                    value="{{ old('initialSectionId', request()->get('section_id') !== null ? request()->get('section_id') : '') }}">
                                <select name="section_id" id="section_id"
                                    class="form-control mx-1 @error('section_id') is-invalid @enderror">
                                    <option value="">Select Section</option>
                                </select>

                                <span class="invalid-feedback form-invalid fw-bold section-error" role="alert"> </span>

                            </div>
                            <div class="form-group col-md-4">
                                <label for="section_id" class="mt-2">Student<span class="text-danger">*</span></label>
                                <input type="hidden" id="initialStdId" name="initialStdId"
                                    value="{{ old('initialStdId', request()->get('std_id') !== null ? request()->get('std_id') : '') }}">
                                <select name="std_id" id="std_id"
                                    class="form-control mx-1 @error('std_id') is-invalid @enderror">
                                    <option value="">Select Student</option>
                                </select>

                                <span class="invalid-feedback form-invalid fw-bold student-error" role="alert"></span>

                            </div>

                        </div>
                        <div class="row">
                            <div class="form-group col-md-6">
                                <label for="subject_id" class="mt-2">Subject <span
                                        class="text-danger">*</span></label>
                                <input type="hidden" id="initialSubjectId"
                                    value="{{ old('initialSubjectId', request()->get('subject_id') !== null ? request()->get('subject_id') : '') }}">
                                <select name="subject" id="subject_id"
                                    class="form-control @error('subject') is-invalid @enderror">
                                    <option value="">Select Subject</option>
                                </select>

                                <span class="invalid-feedback form-invalid fw-bold subject-error" role="alert"></span>


                            </div>
                            <div class="form-group col-md-6">
                                <label for="exam_id" class="mt-2">Exam <span class="text-danger">*</span></label>
                                <select name="exam" id="exam_id"
                                    class="form-control @error('exam') is-invalid @enderror">
                                    <option value="">Select Exam</option>
                                    @if (count($exams) > 0)
                                    @foreach ($exams as $key => $exam)
                                    <option value="{{ $key }}" {{ old('exam_id') == $key ? 'selected' : ''}}>{{ $exam }}</option>
                                    @endforeach
                                    @else
                                    <option value="">No Exam Found</option>
                                    @endif
                                </select>

                                <span class="invalid-feedback form-invalid fw-bold exam-error" role="alert"></span>

                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group col-md-6">
                                <label for="marks" class="mt-2">Enter Marks <span
                                        class="text-danger">*</span></label>
                                <input type="text" name="marks" id="marks"
                                    class="form-control @error('marks') is-invalid @enderror">

                                <span class="invalid-feedback form-invalid fw-bold marks-error" role="alert"></span>

                            </div>
                        </div>
                        <div class="row">

                            <div class="form-group col-md-6">
                                <label class="form-check-label mt-4" for="flexCheckDefault">Attendance(Check for
                                    Present) <span class="text-danger">*</span></label>
                                <input type="checkbox" name="attendance" id="flexCheckDefault" value="1"
                                    class="form-check-input mt-4 border border-black @error('attendance') is-invalid @enderror">

                                <span class="invalid-feedback form-invalid fw-bold attendance-error"
                                    role="alert"></span>

                            </div>
                        </div>
                        <div class="mt-3">
                            <button type="button" class="btn btn-primary" id="marks-updateBtn">Update Marks</button>
                            <span><img src="{{ config('myconfig.myloader') }}" alt="Loading..." class="loader" id="loader"
                                    style="display:none; width:10%;"></span>
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
        let initialClassId = $('#class_id').val();
        let initialSectionId = $('#section_id').val();
        let initialStdId = $('#std_id').val();
        getClassSection(initialClassId, initialSectionId);
        getStdDropdown();
        let initialSubjectId = $('#subject_id').val();
        getClassSubject(initialClassId, initialSubjectId);
        
        $('#marks-updateBtn').on('click', function() {
            $.ajax({
                url: "{{ route('admin.editSection.editStdMarks.store') }}",
                type: "POST",
                dataType: 'JSON',
                data: $('#update-form').serialize(),
                success: function(data) {
                    if (data.status == 'success') {

                        Swal.fire({
                            title: 'Successful',
                            text: data.message,
                            icon: 'success',
                            confirmButtonColor: 'rgb(122 190 255)',
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire({
                            title: 'Error',
                            text: data.message,
                            icon: 'error',
                            confirmButtonColor: 'rgb(122 190 255)',
                        });
                    }

                },
                error: function(data) {
                    let message = data.responseJSON.message;
                    $('.marks-error').hide().html('');
                    $('.subject-error').hide().html('');
                    $('.class-error').hide().html('');
                    $('.section-error').hide().html('');
                    $('.student-error').hide().html('');
                    $('.exam-error').hide().html('');
                    $('.attendance-error').hide().html('');


                    if (message.class_id) {
                        $('.class-error').show().html(message.class_id);
                    }
                    if (message.section_id) {
                        $('.section-error').show().html(message.section_id);
                    }
                    if (message.std_id) {
                        $('.student-error').show().html(message.std_id);
                    }
                    if (message.subject) {
                        $('.subject-error').show().html(message.subject);
                    }
                    if (message.exam) {
                        $('.exam-error').show().html(message.exam);
                    }
                    if (message.marks) {
                        $('.marks-error').show().html(message.marks);
                    }
                    if (message.attendance) {
                        $('.attendance-error').show().html(message.attendance);
                    }
                }
            });

        });

    });
</script>
@endsection