@extends('admin.index')
@section('sub-content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card border-0 bg-white">
                <div class="card-header flex-wrap bg-white d-flex align-items-center justify-content-between"><h5 class="mb-0 mt-0">{{ 'Edit Attendance Entry' }}</h5>
                    <a href="{{ route('admin.editSection.index') }}" class="btn bg-light btn-sm" ><span class="mdi mdi-chevron-left me-2"></span>Back</a>
                </div>
                <div class="card-body">
                    <form id="class-section-form">
                        <div class="row">
                            <div class="form-group col-md-12">
                                <label for="a_date" class="mt-2">Enter Date <span class="text-danger">*</span></label>
                                <input type="date" name="a_date" id="a_date" class="form-control @error('a_date') is-invalid @enderror" required>
                                @error('a_date')
                                    <span class="invalid-feedback form-invalid fw-bold" role="alert">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group col-md-6">
                                <label for="class_id" class="mt-2">Class <span class="text-danger">*</span></label>
                                <input type="hidden" id="initialClassId" value="{{ old('initialClassId', request()->get('class_id') !== null ? request()->get('class_id') : '') }}">
                                <select name="class" id="class_id" class="form-control @error('class') is-invalid @enderror" required>
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
                                <label for="section_id" class="mt-2">Section <span class="text-danger">*</span></label>
                                <input type="hidden" id="initialSectionId" value="{{ old('section') }}">
                                <select name="section" id="section_id" class="form-control @error('section') is-invalid @enderror" required>
                                    <option value="">Select Section</option>
                                </select>
                                @error('section')
                                <span class="invalid-feedback form-invalid fw-bold" role="alert">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                        <div class="mt-3">
                            <button type="button" id="show-details" class="btn btn-primary">Show Details</button>
                            <span><img src="{{ config('myconfig.myloader') }}" alt="Loading..." class="loader" id="loader" style="display:none; width:5%;"></span>
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
                            <div id="std-pagination"></div>
                            <div class="row">
                                <div class="mt-3">
                                    <button type="button" class="btn btn-primary" id="section-updateBtn">Update</button>
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
@section('admin-scripts')
<script>
    $(document).ready(function() {
        let initialClassId = $('#class_id').val();
        let initialSectionId = $('#initialSectionId').val();
        getClassSection(initialClassId, initialSectionId);

        // Hide initially
        $('#std-form').hide();

        // Reset function
        function resetAttendanceForm() {
            $('#std-form').hide();
            $('#std-container table tbody').html(''); // clear students
            $('#hidden_class').val('');
            $('#hidden_section').val('');
            $('#hidden_a_date').val('');
        }

        // Reset whenever these fields change
        $('#class_id, #section_id, #a_date').on('change input', function() {
            resetAttendanceForm();
        });

        $('#class-section-form').validate({
            rules: {
                a_date: { required: true },
                class: { required: true },
                section: { required: true },
            },
            messages: {
                a_date: { required: "Please select a date." },
                class: { required: "Please select a class." },
                section: { required: "Please select a section." },
            },
        });

        $('#show-details').on('click', function() {
            if ($('#class-section-form').valid()) {
                const classId = $('#class_id').val();
                const sectionId = $('#section_id').val();
                // const sessionId = $('#current_session').val();
                const date = $('#a_date').val();

                $('#hidden_class').val(classId);
                $('#hidden_section').val(sectionId);
                $('#hidden_a_date').val(date);

                if (classId && sectionId) {
                    $('#std-form').show();
                    $.ajax({
                        // url: '{{ route('stdNameFather.get') }}',
                        url: '{{ route('getStdForDropDown') }}',
                        type: 'GET',
                        dataType: 'JSON',
                        data: {
                            class_id: classId,
                            section_id: sectionId,
                        },
                        success: function(students) {
                            let stdHtml = '';
                            if (students.data && students.data.length > 0) {
                                $.each(students.data, function(index, std) {
                                    stdHtml += `<tr>
                                        <td>${std.rollno}</td>
                                        <td>
                                            <input type="hidden" name="students[${index}][srno]" value="${std.srno}" class="std-srno" data-index="${index}">
                                            ${std.student_name}
                                        </td>
                                        <td>${std.father_name}</td>
                                        <td>
                                            <input type="checkbox" name="students[${index}][status]" value="1" class="status-checkbox" data-index="${index}" checked>
                                        </td>
                                    </tr>`;
                                });
                                $('#section-updateBtn').show();
                            }
                            if (stdHtml === '') {
                                stdHtml = '<tr><td colspan="4">No Student found</td></tr>';
                                $('#section-updateBtn').hide();
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
        });

        $('#section-updateBtn').click(function() {
            $.ajax({
                url: "{{ route('admin.editSection.editStdAttendance.store') }}",
                type: "POST",
                data: $('#std-form').serialize(),
                dataType: 'JSON',
                success: function(data) {
                    if (data.status == 'success') {
                        Swal.fire({
                            title: 'Successful',
                            text: data.message,
                            icon: 'success',
                            confirmButtonColor: 'rgb(122 190 255)',
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
                error: function(xhr) {
                    // Handle Laravel validation / duplicate attendance JSON
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        Swal.fire({
                            title: 'Error',
                            text: xhr.responseJSON.message,
                            icon: 'error',
                            confirmButtonColor: 'rgb(122 190 255)',
                        });
                    } else {
                        Swal.fire({
                            title: 'Error',
                            text: 'Something went wrong!',
                            icon: 'error',
                            confirmButtonColor: 'rgb(122 190 255)',
                        });
                    }
                }
            });
        });
    });
</script>
@endsection
