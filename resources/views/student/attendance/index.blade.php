@extends('student.index')
@section('sub-content')
    <div class="container">
        @if (Session::has('success'))
            @section('scripts')
                <script>
                    swal("Successful", "{{ Session::get('success') }}", "success").then(() => {
                        location.reload();
                    });
                </script>
            @endsection
        @endif

        @if (Session::has('error'))
            @section('scripts')
                <script>
                    swal("Error", "{{ Session::get('error') }}", "error").then(() => {
                        location.reload();
                    });
                </script>
            @endsection
        @endif
        <div class="row justify-content-center">
            <div class="col-md-14">
                <div class="card">
                    <div class="card-header">
                        {{ 'Attendance Entry' }}
                        <a href="{{ route('student.attendance.index') }}" class="btn btn-warning btn-sm"
                            style="float: right;">Back</a>

                    </div>
                    <div class="card-body">
                        <form id="class-section-form">
                            <div class="row">
                                <div class="form-group col-md-12">
                                    <label for="a_date" class="mt-2">Enter Date <span
                                            class="text-danger">*</span></label>
                                    <input type="date" name="a_date" id="a_date"
                                        class="form-control @error('a_date') is-invalid @enderror" required>
                                    @error('a_date')
                                        <span class="invalid-feedback form-invalid fw-bold"
                                            role="alert">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="row">
                                <div class="form-group col-md-6">
                                    <label for="class_id" class="mt-2">Class <span class="text-danger">*</span></label>
                                    <select name="class" id="class_id"
                                        class="form-control @error('class') is-invalid @enderror" required>
                                        <option value="">Select Class</option>
                                        @if (count($classes) > 0)
                                            @foreach ($classes as $key => $class)
                                                <option value="{{ $key }}" {{ old('class') == $key ? 'selected' : ''}}>{{ $class }}</option>
                                            @endforeach
                                        @else
                                            <option value="">No Class Found</option>
                                        @endif
                                    </select>
                                    @error('class')
                                        <span class="invalid-feedback form-invalid fw-bold"
                                            role="alert">{{ $message }}</span>
                                    @enderror

                                </div>


                                <div class="form-group col-md-6">
                                    <label for="section_id" class="mt-2">Section <span
                                            class="text-danger">*</span></label>
                                    <input type="hidden" id="initialSectionId" value="{{ old('section') }}">
                                    <select name="section" id="section_id"
                                        class="form-control @error('section') is-invalid @enderror" required>
                                        <option value="">Select Section</option>

                                    </select>
                                    @error('section')
                                        <span class="invalid-feedback form-invalid fw-bold"
                                            role="alert">{{ $message }}</span>
                                    @enderror
                                    <img src="{{ config('myconfig.myloader') }}" alt="Loading..." class="loader"
                                        id="loader" style="display:none; width:10%;">
                                </div>
                            </div>


                            <div class="mt-3">
                                <button type="button" id="show-details" class="btn btn-primary">
                                    Show Details</button>
                            </div>

                        </form>


                        <div id="std-container" class="mt-4">
                            <form action="" method="POST" id="std-form">
                                @csrf
                                <table class="table table-responsible">
                                    <input type="hidden" name="current_session" value='' id="current_session">
                                    <input type="hidden" name="hidden_class" value='' id="hidden_class">
                                    <input type="hidden" name="hidden_section" value='' id="hidden_section">
                                    <input type="hidden" name="hidden_a_date" value='' id="hidden_a_date">
                                    {{-- <input type="hidden" name="current_session" value='' id="current_session"> --}}

                                    <thead>
                                        <tr>
                                            <th>Roll No.</th>
                                            <th>Name</th>
                                            <th>Father's Name</th>
                                            <th>Attendance</th>
                                        </tr>
                                    </thead>
                                    <tbody>

                                    </tbody>
                                </table>
                               <div class="row">
                                    <div class="mt-3">
                                        <button type="button" class="btn btn-primary"
                                            id="section-updateBtn">Update</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('std-scripts')
<script>
    $(document).ready(function () {
        let initialClassId = $('#class_id').val();
        let initialSectionId = $('#initialSectionId').val();
        getClassSection(initialClassId, initialSectionId);
        $('#std-form').hide();
        $('#class-section-form').validate({
                rules: {
                    a_date: {
                        required: true,
                    },
                    class: {
                        required: true,
                    },
                    section: {
                        required: true,
                    },
                },
                messages: {
                    a_date: {
                        required: "Please select a date.",
                    },
                    class: {
                        required: "Please select a class.",
                    },
                    section: {
                        required: "Please select a section.",
                    },
                },
            });

            $('#show-details').on('click', function() {
                if ($('#class-section-form').valid()) {

                    const classId = $('#class_id').val();
                    const sectionId = $('#section_id').val();
                    const sessionId = $('#current_session').val();
                    const date = $('#a_date').val();
                    const $paginationContainer = $('#std-pagination');
                    $('#hidden_class').val(classId);
                    $('#hidden_section').val(sectionId);
                    $('#hidden_a_date').val(date);

                    function stdDetails() {
                        if (classId && sectionId && sessionId) {
                            $('#std-form').show();
                            $.ajax({
                                url: '{{ route('stdNameFather.get') }}',
                                type: 'GET',
                                dataType: 'JSON',
                                data: {
                                    class_id: classId,
                                    section_id: sectionId,
                                    session_id: sessionId,

                                },
                                success: function(students) {
                                    let stdHtml = '';
                                    $.each(students, function(index, std) {


                                        stdHtml += `<tr>
                                                <td>${std.rollno}</td>
                                                <td>
                                                    <input type="hidden" name="students[${index}][srno]" value="${std.srno}" class="std-srno" id="std-srno" data-index="${index}">
                                                    ${std.student_name}
                                                </td>
                                                <td>
                                                   ${std.f_name}
                                                </td>
                                                 <td>
                                                    <input type="checkbox" name="students[${index}][status]" value="1" class="status-checkbox" data-index="${index}" checked>
                                                </td>

                                                </tr>`;

                                    });
                                    if (stdHtml === '') {
                                        stdHtml =
                                            '<tr><td colspan="4">No Student found</td></tr>';
                                    }
                                    $('#std-container table tbody').html(stdHtml);

                                },
                                complete: function() {
                                    loader.hide();
                                },
                                error: function(xhr) {
                                    console.error(xhr.responseText);

                                }
                            });
                        }
                    }


                    stdDetails();
                }
            });

            $('#section-updateBtn').click(function(){
                $.ajax({
                    url: "{{ route('student.attendance.store') }}",
                    type: "POST",
                    data: $('#std-form').serialize(),
                    dataType: 'JSON',
                    success: function(data) {
                        console.log(data);
                        if (data.status == 'success') {

                            Swal.fire({
                                title: 'Successful',
                                text: data.message,
                                icon: 'success',
                                confirmButtonColor: 'rgb(122 190 255)',
                            }).then(() => {
                                location.reload();
                            });
                        }else{
                            Swal.fire({
                                title: 'Error',
                                text: data.message,
                                icon: 'error',
                                confirmButtonColor: 'rgb(122 190 255)',
                            });
                        }

                    },
                    error: function(xhr) {
                        console.log(xhr);

                    }
                });
            });

    });
</script>
@endsection

