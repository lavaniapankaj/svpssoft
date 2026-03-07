@extends('marks.index')
@section('sub-content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card border-0 bg-white">
           <div class="card-header flex-wrap bg-white d-flex align-items-center justify-content-between"><h5 class="mb-0 mt-0">{{ 'Print Report Exam Wise (PG Class)' }}</h5>
                    <a href="{{ route('marks.marksheet') }}" class="btn bg-light btn-sm" ><span class="mdi mdi-chevron-left me-2"></span>Back</a>
                </div>
                <div class="card-body">
                    <form id="class-section-form" action="{{ route('marks.marks-report.pg-class-exam-wise.store') }}" method="POST">
                        @csrf
                        <div class="row">
                                {{-- Exam --}}
                                <div class="form-group col-md-6">
                                    <label for="marks_exam_id" class="mt-2"> Exam <span class="text-danger">*</span></label>
                                    <select name="exam" id="marks_exam_id" class="form-control" required>
                                        @if (count($exams) > 0)
                                            <option value="">Select Exam</option>
                                            @foreach ($exams as $key => $exam)
                                            <option value="{{ $key }}" {{ old('exam') == $key ? 'selected' : '' }}>
                                                {{ $exam }}
                                            </option>
                                            @endforeach
                                        @else
                                            <option value="" disabled selected>No Exam Found</option>
                                        @endif
                                    </select>
                                    <span class="invalid-feedback fw-bold" id="exam-error" role="alert"></span>
                                </div>
                                {{-- Class --}}
                                <div class="form-group col-md-6">
                                    <label for="marks_class_id" class="mt-2">
                                        Class <span class="text-danger">*</span>
                                    </label>
                                    <select name="class" id="marks_class_id" class="form-control"
                                        {{ count($classes) === 0 ? 'disabled' : '' }} required>
                                        @if (count($classes) > 0)
                                            <option value="">Select Class</option>
                                            @foreach ($classes as $key => $class)
                                                <option value="{{ $key }}" {{ request('class') == $key ? 'selected' : '' }}>
                                                    {{ $class }}
                                                </option>
                                            @endforeach
                                        @else
                                            <option value="" disabled selected>No Class Found</option>
                                        @endif
                                    </select>
                                    <span class="invalid-feedback fw-bold" id="class-error" role="alert"></span>
                                </div>

                        </div>
                        <div class="row">
                                {{-- Section --}}
                                <div class="form-group col-md-6">
                                    <label for="marks_section_id" class="mt-2">Section <span class="text-danger">*</span>
                                    </label>
                                    <select name="section" id="marks_section_id" class="form-control" required>
                                        <option value="">Select Section</option>
                                    </select>
                                    <span class="invalid-feedback fw-bold" id="section-error" role="alert"></span>
                                </div>

                                {{-- Student --}}
                                <div class="form-group col-md-6">
                                    <label for="marks_student_id" class="mt-2">Student <span class="text-danger">*</span>
                                    </label>
                                    {{-- Starts as "Select Student" until a section is chosen --}}
                                    <select name="std" id="marks_student_id" class="form-control" required>
                                        <option value="">Select Student</option>
                                    </select>
                                    <span class="invalid-feedback fw-bold" id="std-error" role="alert"></span>
                                </div>
                        </div>
                        <div class="mt-3">
                            <button type="submit" id="show-details" class="btn btn-primary">Show Details</button><span><img src="{{ config('myconfig.myloader') }}" alt="Loading..." class="loader" id="loader" style="display:none; width:10%;"></span>
                        </div>

                    </form>

                </div>
            </div>
        </div>
    </div>
</div>
@endsection
@section('marks-scripts')
    <script>
       $(document).ready(function() {
             // ── Selectors ──────────────────────────────────────────────────────────────
            const classSelect   = $('#marks_class_id');
            const sectionSelect = $('#marks_section_id');
            const studentSelect = $('#marks_student_id');
            const examSelect    = $('#marks_exam_id');


            // Class change → reload sections & subjects, reset student to placeholder
            classSelect.on('change', function () {
                const classId = $(this).val();
                if (classId) {
                    getMarksWithoutAllSections(classId, function () {});
                } else {
                    sectionSelect.html('<option value="">Select Section</option>');
                }
                studentSelect.html('<option value="">Select Student</option>');
            });

            // Section change → reload students via global function, reset table
            sectionSelect.on('change', function () {
                const classId   = classSelect.val();
                const sectionId = $(this).val();

                studentSelect.html('<option value="">Select Student</option>');

                if (classId && sectionId) {
                    getMarksAllStudents(classId, sectionId);
                }
            });
        });
    </script>
@endsection