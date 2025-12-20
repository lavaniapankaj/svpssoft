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
                        <h5 class="mb-0 mt-0">{{ __('Transport Fee Master') }}</h5>

                        <div class=" flex-column d-flex align-items-end ">
                            <a href="{{ route('admin.transport-fee-master.create') }}" class="btn btn-success text-white"><span class="mdi mdi-plus-circle-outline me-2"></span>Add</a>
                            <div class="d-flex align-items-center gap-1 mt-2">
                                <form action="" method="get" class="d-flex">
                                    <input type="hidden" name="current_session" value='' id="current_session">
                                    <select name="class_id" id="class_id" class="form-control mx-1" required>
                                        <option value="">Select Class</option>
                                        @if (count($classes) > 0)
                                            @foreach ($classes as $key => $class)
                                                <option value="{{ $key }}" {{ old('section_id', request()->get('class_id') !== null ? request()->get('class_id') : '') == $key ? 'selected' : ''}}>{{ $class }}</option>
                                            @endforeach
                                        @else
                                            <option value="">No Class Found</option>
                                        @endif
                                    </select>
                                    <input type="hidden" id="initialSectionId"
                                        value="{{ old('section_id', request()->get('section_id') !== null ? request()->get('section_id') : '') }}">
                                    <select name="section_id" id="section_id" class="form-control mx-1" required>
                                        <option value="">Select Section</option>
                                    </select>
                                    <input type="hidden" id="initialStdId" value="{{ old('std_id', request()->get('std_id') !== null ? request()->get('std_id') : '') }}">
                                    <select name="std_id" id="std_id" class="form-control mx-1" required>
                                        <option value="">Select Student</option>
                                    </select>
                                    <button id="search" type="submit" class="btn btn-sm btn-dark mx-2 d-flex align-items-center gap-1"><span class="mdi mdi-magnify "></span> Search</button>
                                </form>
                                <a href="{{ route('admin.transport-fee-master.index') }}" class="btn btn-dark">Reset</a>
                                <span><img src="{{ config('myconfig.myloader') }}" alt="Loading..." class="loader" id="loader" style="display:none; width:5%;"></span>
                            </div>
                        </div>
                    </div>

                    <div class="card-body">

                        <div class="table" id="std-container" style="display: none;">
                            <table id="example" class="table table-striped table-bordered">
                                <thead>
                                    <tr>
                                        <th>Ist Installment</th>
                                        <th>IInd Installment</th>
                                        <th>Discount</th>
                                        <th>Total</th>
                                    </tr>
                                </thead>
                                <tbody class="">
                                </tbody>
                            </table>

                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('admin-scripts')
<script>
$(document).ready(function () {
    let classSelect   = $('#class_id');
    let sectionSelect = $('#section_id');
    let stdSelect     = $('#std_id');

    let initialSectionId = $('#initialSectionId').val();
    let initialStdId     = $('#initialStdId').val();

    // ----------------------------------
    // HELPERS
    // ----------------------------------
    function resetTable() {
        $('#std-container').hide();
        $('#std-container table tbody').empty();
    }

    function resetStudents() {
        stdSelect.empty().append('<option value="">Select Student</option>');
        resetTable();
    }

    // ----------------------------------
    // INITIAL LOAD (class → section)
    // ----------------------------------
    getClassSection(classSelect.val(), initialSectionId);

    // ----------------------------------
    // FETCH STUDENTS
    // ----------------------------------
    function fetchStdNameFather(classId, sectionId) {

        resetStudents();

        if (!classId || !sectionId) return;

        $.ajax({
            url: '{{ route('admin.transport-fee-master.getStudents') }}',
            type: 'GET',
            dataType: 'json',
            data: {
                class_id: classId,
                section_id: sectionId
            },
            success: function (response) {

                stdSelect.empty();

                // NO STUDENT CASE
                if (response.status === 'error' || !response.data || response.data.length === 0) {
                    stdSelect.append('<option value="">No Student Found</option>');
                    return;
                }

                // STUDENT FOUND
                stdSelect.append('<option value="">Select Student</option>');

                $.each(response.data, function (i, student) {
                    stdSelect.append(
                        `<option value="${student.srno}">
                            ${student.rollno}. ${student.student_name}/${student.father_name}
                        </option>`
                    );
                });

                if (initialStdId) {
                    stdSelect.val(initialStdId);
                }

                // ----------------------------------
                // SEARCH BUTTON (remove old, add new)
                // ----------------------------------
                $('#search').off('click').on('click', function (e) {
                    e.preventDefault();

                    resetTable();

                    let selectedStdId = stdSelect.val();
                    if (!selectedStdId) return;

                    let selectedStudent = response.data.find(
                        s => s.srno == selectedStdId
                    );

                    if (!selectedStudent) return;

                    let rowHtml = `
                        <tr>
                            <td>${selectedStudent.trans_1st_inst ?? '-'}</td>
                            <td>${selectedStudent.trans_2nd_inst ?? '-'}</td>
                            <td>${selectedStudent.trans_discount ?? '-'}</td>
                            <td>${selectedStudent.trans_total ?? '-'}</td>
                        </tr>
                    `;

                    $('#std-container table tbody').html(rowHtml);
                    $('#std-container').show();
                });
            },
            error: function () {
                resetStudents();
            }
        });
    }

    // ----------------------------------
    // EVENTS
    // ----------------------------------
    classSelect.on('change', function () {
        resetStudents();
    });

    sectionSelect.on('change', function () {
        fetchStdNameFather(classSelect.val(), $(this).val());
    });

    stdSelect.on('change', function () {
        resetTable();
    });

    // ----------------------------------
    // AUTO LOAD (redirect back with input)
    // ----------------------------------
    if (classSelect.val() && sectionSelect.val()) {
        fetchStdNameFather(classSelect.val(), sectionSelect.val());
    }

});
</script>
@endsection

