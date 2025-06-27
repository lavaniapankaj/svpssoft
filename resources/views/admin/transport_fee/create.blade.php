@extends('admin.index')

@section('sub-content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card">
                    <div class="card-header">
                        {{ 'Add Transport Fee' }}
                        <a href="{{ route('admin.transport-fee-master.index') }}" class="btn btn-warning btn-sm" style="float: right;">Back</a>
                    </div>

                    <div class="card-body">
                        <form action="{{ route('admin.transport-fee-master.store') }}" method="POST" id="basic-form">
                            @csrf
                            <div class="row">
                                <div class="form-group col-md-6">
                                    <label for="class_id" class="mt-2">Class <span
                                            class="text-danger">*</span></label>
                                    <select name="class_id" id="class_id"
                                        class="form-control @error('class_id') is-invalid @enderror" required>
                                        <option value="">Select Class</option>
                                        @if (count($classes) > 0)
                                            @foreach ($classes as $key => $class)
                                                <option value="{{ $key }}" {{ old('class_id') == $key ? 'selected' : ''}}>{{ $class }}</option>
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
                                <div class="form-group col-md-6">
                                    <label for="section_id" class="mt-2">Section<span
                                            class="text-danger">*</span></label>
                                    <input type="hidden" id="initialSectionId" value="{{ old('section_id') }}">
                                    <select name="section_id" id="section_id" class="form-control @error('section_id') is-invalid @enderror" required>
                                        <option value="">Select Section</option>
                                    </select>
                                    @error('section_id')
                                    <span class="invalid-feedback form-invalid fw-bold" role="alert">
                                        {{ $message }}
                                    </span>
                                    @enderror
                                </div>
                                <img src="{{ config('myconfig.myloader') }}" alt="Loading..." class="loader"
                                    id="loader" style="display:none; width:10%;">
                            </div>
                            <div class="row">

                                <div class="form-group col-md-6">
                                    <input type="hidden" name="current_session" value='' id="current_session">
                                    <label for="std_id" class="mt-2">Student<span
                                            class="text-danger">*</span></label>
                                    <input type="hidden" id="initialStdId" value="{{ old('std_id') }}">
                                    <select name="std_id" id="std_id"
                                        class="form-control @error('std_id') is-invalid @enderror" required>
                                        <option value="">Select Student</option>
                                    </select>
                                    @error('std_id')
                                    <span class="invalid-feedback form-invalid fw-bold" role="alert">
                                        {{ $message }}
                                    </span>
                                    @enderror
                                </div>
                            </div>

                            <div class="row">

                                <div class="row">
                                    <div class="form-group col-md-2">
                                        <label for="trans_1st_inst">1st Installment</label>
                                        <input type="text" name="trans_1st_inst" id="trans_1st_inst"
                                            class="form-control @error('trans_1st_inst') is-invalid @enderror"
                                            value="{{ old('trans_1st_inst') }}"
                                            required>
                                        @error('trans_1st_inst')
                                        <span class="invalid-feedback form-invalid fw-bold"
                                            role="alert">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="form-group col-md-2">
                                        <label for="trans_2nd_inst">2nd Installment</label>
                                        <input type="text" name="trans_2nd_inst" id="trans_2nd_inst"
                                            class="form-control @error('trans_2nd_inst') is-invalid @enderror"
                                            value="{{ old('trans_2nd_inst') }}"
                                            required>
                                        @error('trans_2nd_inst')
                                        <span class="invalid-feedback form-invalid fw-bold"
                                            role="alert">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="form-group col-md-2">
                                        <label for="trans_discount">Discount</label>
                                        <input type="text" name="trans_discount" id="trans_discount"
                                            class="form-control @error('trans_discount') is-invalid @enderror"
                                            value="{{ old('trans_discount') }}"
                                            required>
                                        @error('trans_discount')
                                        <span class="invalid-feedback form-invalid fw-bold"
                                            role="alert">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="form-group col-md-2">
                                        <label for="trans_total">Total</label>
                                        <input type="text" name="trans_total" id="trans_total"
                                            class="form-control @error('trans_total') is-invalid @enderror"
                                            value="{{ old('trans_total') }}"
                                            readonly>
                                        @error('trans_total')
                                        <span class="invalid-feedback form-invalid fw-bold"
                                            role="alert">{{ $message }}</span>
                                        @enderror
                                    </div>

                                </div>
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
{{-- @section('admin-scripts')
<script>
    $(document).ready(function() {
        let classSelect = $('#class_id');
        let sectionSelect = $('#section_id');
        let sessionSelect = $('#current_session');
        let stdSelect = $('#std_id');
        let initialSectionId = $('#initialSectionId').val();
        let initialStdId = $('#initialStdId').val();
        getClassSection(classSelect.val(), initialSectionId);

        function fetchStdNameFather(classId, sectionId, sessionId) {
            if (classId && sectionId && sessionId) {
                $.ajax({
                    url: '{{ route('stdNameFather.get') }}',
                    type: 'GET',
                    dataType: 'JSON',
                    data: {
                        class_id: classId,
                        section_id: sectionId,
                        session_id: sessionId,
                    },
                    success: function(data) {
                        stdSelect.empty();
                        stdSelect.append('<option value="">Select Student</option>');
                        $.each(data, function(id, value) {
                            stdSelect.append('<option value="' + value.srno + '">' +
                                ++id + '. ' + value.student_name + '/SH. ' + value.f_name +
                                '</option>');
                        });
                        stdSelect.change(function() {
                            var selectedStdId = $(this).val();
                            var selectedStudent = data.find(student => student.srno ===
                                selectedStdId);

                            if (selectedStudent) {
                                $('#trans_1st_inst').val(selectedStudent.trans_1st_inst);
                                $('#trans_2nd_inst').val(selectedStudent.trans_2nd_inst);
                                $('#trans_discount').val(selectedStudent.trans_discount);
                                $('#trans_total').val(selectedStudent.trans_total);
                            }
                        });

                        if (initialStdId) {
                            stdSelect.val(initialStdId);
                        }
                    },
                    error: function(xhr) {
                        console.error('Error fetching student detail:', xhr);
                    }
                });
            } else {
                stdSelect.empty();
                stdSelect.append('<option value="">Select Student</option>');
            }
        }

        let selectedClassId = classSelect.val();
        let selectedSectionId = initialSectionId;
        let selectedSessionId = sessionSelect.val();
        console.log(selectedClassId);
        console.log(selectedSectionId);
        console.log(selectedSessionId);
        if (classSelect.val() && initialSectionId && sessionSelect.val()) {
            fetchStdNameFather(classSelect.val(), initialSectionId, sessionSelect.val());
        }
        classSelect.change(()=>{
            stdSelect.empty();
            stdSelect.append('<option value="">Select Student</option>');
        });
        sectionSelect.change(function() {
            var sectionId = $(this).val();
            var classId = classSelect.val();
            var sessionId = sessionSelect.val();
            fetchStdNameFather(classId, sectionId, sessionId);
        });

    });
</script>
@endsection --}}


@section('admin-scripts')
<script>
    $(document).ready(function() {
        const classSelect = $('#class_id');
        const sectionSelect = $('#section_id');
        const sessionSelect = $('#current_session');
        const stdSelect = $('#std_id');
        const initialSectionId = $('#initialSectionId').val();
        const initialStdId = $('#initialStdId').val();
        getClassSection(classSelect.val(), initialSectionId);
        function populateStudents(data) {
            stdSelect.empty().append('<option value="">Select Student</option>');

            data.forEach((student, index) => {
                stdSelect.append(`<option value="${student.srno}">${index + 1}. ${student.student_name}/SH. ${student.f_name}</option>`);
            });

            if (initialStdId) {
                stdSelect.val(initialStdId);
            }

            stdSelect.off('change').on('change', function() {
                const selectedStdId = $(this).val();
                const selectedStudent = data.find(student => student.srno === selectedStdId);

                if (selectedStudent) {
                    $('#trans_1st_inst').val(selectedStudent.trans_1st_inst);
                    $('#trans_2nd_inst').val(selectedStudent.trans_2nd_inst);
                    $('#trans_discount').val(selectedStudent.trans_discount);
                    $('#trans_total').val(selectedStudent.trans_total);
                }
            });
        }

        function fetchStudents(classId, sectionId, sessionId) {
            if (classId && sectionId && sessionId) {
                $.ajax({
                    url: '{{ route('stdNameFather.get') }}',
                    type: 'GET',
                    dataType: 'JSON',
                    data: { class_id: classId, section_id: sectionId, session_id: sessionId },
                    success: populateStudents,
                    error: function(xhr) {
                        console.error('Error fetching student details:', xhr);
                    }
                });
            } else {
                stdSelect.empty().append('<option value="">Select Student</option>');
            }
        }

        function initialize() {
            const selectedClassId = classSelect.val();
            const selectedSessionId = sessionSelect.val();

            if (selectedClassId && initialSectionId && selectedSessionId) {
                fetchStudents(selectedClassId, initialSectionId, selectedSessionId);
            }

            classSelect.on('change', function() {
                stdSelect.empty().append('<option value="">Select Student</option>');
            });

            sectionSelect.on('change', function() {
                fetchStudents(classSelect.val(), $(this).val(), sessionSelect.val());
            });
        }

        initialize();
    });
</script>
@endsection
