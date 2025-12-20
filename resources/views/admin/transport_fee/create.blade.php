@extends('admin.index')

@section('sub-content')
<div class="container-fluid">
    @if (Session::has('success'))
        @push('swal-scripts')
            <script>
                swal("Successful", "{{ Session::get('success') }}", "success");
            </script>
        @endpush
    @endif

    @if (Session::has('error'))
        @push('swal-scripts')
            <script>
                swal("Error", "{{ Session::get('error') }}", "error");
            </script>
        @endpush
    @endif
    <div class="row">
        <div class="col-md-12">
            <div class="card border-0 bg-white">
                <div class="card-header flex-wrap bg-white d-flex align-items-center justify-content-between">
                    <h5 class="mb-0 mt-0">{{ 'Add Transport Fee' }}</h5>
                        <a href="{{ route('admin.transport-fee-master.index') }}" class="btn bg-light btn-sm" ><span class="mdi mdi-chevron-left me-2"></span>Back</a>
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
                            </div>
                            <div class="row">
                                <div class="form-group col-md-6 mt-2">
                                    <input type="hidden" name="current_session" value='' id="current_session">
                                    <label for="std_id" class="mt-2">Student<span class="text-danger">*</span></label>
                                    <input type="hidden" id="initialStdId" value="{{ old('std_id') }}">
                                    <select name="std_id" id="std_id" class="form-control @error('std_id') is-invalid @enderror" required>
                                        <option value="">Select Student</option>
                                    </select>
                                    @error('std_id')
                                    <span class="invalid-feedback form-invalid fw-bold" role="alert">
                                        {{ $message }}
                                    </span>
                                    @enderror
                                </div>

                                <div class="form-group col-md-6 mt-2">
                                    <label for="trans_1st_inst">1st Installment</label>
                                    <input type="text" name="trans_1st_inst" id="trans_1st_inst" class="form-control @error('trans_1st_inst') is-invalid @enderror" value="{{ old('trans_1st_inst') }}" required>
                                    @error('trans_1st_inst')
                                    <span class="invalid-feedback form-invalid fw-bold"
                                        role="alert">{{ $message }}</span>
                                    @enderror
                                </div>
                                <div class="form-group col-md-6 mt-2">
                                    <label for="trans_2nd_inst">2nd Installment</label>
                                    <input type="text" name="trans_2nd_inst" id="trans_2nd_inst" class="form-control @error('trans_2nd_inst') is-invalid @enderror" value="{{ old('trans_2nd_inst') }}" required>
                                    @error('trans_2nd_inst')
                                        <span class="invalid-feedback form-invalid fw-bold" role="alert">{{ $message }}</span>
                                    @enderror
                                </div>
                                <div class="form-group col-md-6 mt-2">
                                    <label for="trans_discount">Discount</label>
                                    <input type="text" name="trans_discount" id="trans_discount" class="form-control @error('trans_discount') is-invalid @enderror" value="{{ old('trans_discount') }}" required>
                                    @error('trans_discount')
                                        <span class="invalid-feedback form-invalid fw-bold" role="alert">{{ $message }}</span>
                                    @enderror
                                </div>
                                <div class="form-group col-md-6 mt-2">
                                    <label for="trans_total">Total</label>
                                    <input type="text" name="trans_total" id="trans_total" class="form-control @error('trans_total') is-invalid @enderror" value="{{ old('trans_total') }}" readonly>
                                    @error('trans_total')
                                        <span class="invalid-feedback form-invalid fw-bold" role="alert">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="mt-2">
                                <input class="btn btn-primary" type="submit" value="Save"> <span><img src="{{ config('myconfig.myloader') }}" alt="Loading..." class="loader" id="loader" style="display:none; width:5%;"></span>
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
        const classSelect = $('#class_id');
        const sectionSelect = $('#section_id');
        const stdSelect = $('#std_id');
        const initialSectionId = $('#initialSectionId').val();
        const initialStdId = $('#initialStdId').val();
        getClassSection(classSelect.val(), initialSectionId);
        function populateStudents(response) {
            stdSelect.empty();

            // If no students found
            if (response.status == 'error' || !response.data || response.data.length == 0) {
                stdSelect.append('<option value="">No student found</option>');

                // Reset transport fee fields
                $('#trans_1st_inst').val('');
                $('#trans_2nd_inst').val('');
                $('#trans_discount').val('');
                $('#trans_total').val('');
                return;
            }

            // Students exist
            stdSelect.append('<option value="">Select Student</option>');

            response.data.forEach((student, index) => {
                stdSelect.append(`<option value="${student.srno}">${student.rollno}. ${student.student_name}/${student.father_name}</option>`);
            });

            if (initialStdId) {
                stdSelect.val(initialStdId);
            }

            stdSelect.off('change').on('change', function() {
                const selectedStdId = $(this).val();
                const selectedStudent = response.data.find(student => student.srno === selectedStdId);

                if (selectedStudent) {
                    $('#trans_1st_inst').val(selectedStudent.trans_1st_inst);
                    $('#trans_2nd_inst').val(selectedStudent.trans_2nd_inst);
                    $('#trans_discount').val(selectedStudent.trans_discount);
                    $('#trans_total').val(selectedStudent.trans_total);
                }else {
                    // Reset transport fee fields if no student is selected
                    $('#trans_1st_inst').val('');
                    $('#trans_2nd_inst').val('');
                    $('#trans_discount').val('');
                    $('#trans_total').val('');
                }
            });
        }

        function fetchStudents(classId, sectionId) {
            if (classId && sectionId) {
                $.ajax({
                    // url: '{{ route('stdNameFather.get') }}',
                    url: '{{ route('admin.transport-fee-master.getStudents') }}',
                    type: 'GET',
                    dataType: 'JSON',
                    data: { class_id: classId, section_id: sectionId},
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

            if (selectedClassId && initialSectionId) {
                fetchStudents(selectedClassId, initialSectionId);
            }

            classSelect.on('change', function() {
                stdSelect.empty().append('<option value="">Select Student</option>');
            });

            sectionSelect.on('change', function() {
                fetchStudents(classSelect.val(), $(this).val());
            });
        }
        initialize();
    });
</script>
@endsection
