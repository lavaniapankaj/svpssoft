@extends('admin.index')
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
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        {{ 'Edit/Remove Relative Students' }}
                        <a href="{{ route('admin.editSection.index') }}" class="btn btn-warning btn-sm"
                            style="float: right;">Back</a>

                    </div>
                    <div class="card-body">
                        <form method="get" action="">
                            <div class="row">
                                <div class="form-group col-md-4">
                                    <label for="class_id" class="mt-2">Class <span class="text-danger">*</span></label>
                                    <input type="hidden" name="current_session" value='' id="current_session">
                                    <select name="class_id" id="class_id"
                                        class="form-control mx-1 @error('class_id') is-invalid @enderror" required>
                                        <option value="">Select Class</option>
                                        @if (count($classes) > 0)
                                            @foreach ($classes as $key => $class)
                                                <option value="{{ $key }}"
                                                    {{ old('class ') == $key ? 'selected' : '' }}>{{ $class }}
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
                                    <label for="section_id" class="mt-2">Section<span class="text-danger">*</span></label>
                                    <input type="hidden" id="initialSectionId" name="initialSectionId"
                                        value="{{ old('section_id') }}">
                                    <select name="section_id" id="section_id"
                                        class="form-control mx-1 @error('section_id') is-invalid @enderror" required>
                                        <option value="">Select Section</option>
                                    </select>
                                    @error('section_id')
                                        <span class="invalid-feedback form-invalid fw-bold" role="alert">
                                            {{ $message }}
                                        </span>
                                    @enderror
                                </div>
                                <div class="form-group col-md-4">
                                    <label for="std_id" class="mt-2">Student<span class="text-danger">*</span></label>
                                    <input type="hidden" id="initialStdId" name="initialStdId"
                                        value="{{ old('std_id') }}">
                                    <select name="std_id" id="std_id"
                                        class="form-control mx-1 @error('std_id') is-invalid @enderror" required>
                                        <option value="">Select Student</option>
                                    </select>
                                    @error('std_id')
                                        <span class="invalid-feedback form-invalid fw-bold" role="alert">
                                            {{ $message }}
                                        </span>
                                    @enderror
                                </div>

                            </div>
                            <div class="row mt-4">
                                <div class="table" id="std-container" style="display: none;">
                                    <table id="example" class="table table-striped table-bordered">
                                        <thead>
                                            <tr>
                                                <th>SRNO</th>
                                                <th>Name</th>
                                                <th>Father's Name</th>
                                                <th>Mother's Name</th>
                                                <th>Class</th>
                                                <th>Section</th>
                                                <th class="text-center">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody class="">
                                        </tbody>
                                    </table>

                                </div>
                            </div>
                        </form>
                        <form action="{{ route('admin.editSection.editRemoveRelativeStd.store') }}" method="POST"
                            id="add-relative-form">
                            @csrf
                            <div class="row">
                                <div class="form-group col-md-4 ">
                                    <label for="second_class_id" class="mt-2">Class <span
                                            class="text-danger">*</span></label>
                                    <select name="second_class_id" id="second_class_id"
                                        class="form-control mx-1 @error('second_class_id') is-invalid @enderror" required>
                                        <option value="">Select Class</option>
                                        @if (count($classes) > 0)
                                            @foreach ($classes as $key => $class)
                                                <option value="{{ $key }}"
                                                    {{ old('second_class_id ') == $key ? 'selected' : '' }}>
                                                    {{ $class }}</option>
                                            @endforeach
                                        @else
                                            <option value="">No Class Found</option>
                                        @endif
                                    </select>
                                    @error('second_class_id')
                                        <span class="invalid-feedback form-invalid fw-bold" role="alert">
                                            {{ $message }}
                                        </span>
                                    @enderror
                                </div>
                                <div class="form-group col-md-4">
                                    <input type="hidden" id="initialSecondSectionId" name="initialSecondSectionId"
                                        value="{{ old('second_section_id') }}">
                                    <label for="section_id" class="mt-2">Section<span class="text-danger">*</span></label>
                                    <select name="second_section_id" id="second_section_id"
                                        class="form-control mx-1 @error('second_section_id') is-invalid @enderror" required>
                                        <option value="">Select Section</option>
                                    </select>
                                    @error('second_section_id')
                                        <span class="invalid-feedback form-invalid fw-bold" role="alert">
                                            {{ $message }}
                                        </span>
                                    @enderror
                                </div>
                                <div class="form-group col-md-4">
                                    <input type="hidden" id="initialSecondStdId" name="initialSecondStdId"
                                        value="{{ old('second_std_id') }}">
                                    <label for="second_std_id" class="mt-2">Student<span
                                            class="text-danger">*</span></label>
                                    <select name="second_std_id" id="second_std_id"
                                        class="form-control mx-1 @error('second_std_id') is-invalid @enderror" required>
                                        <option value="">Select Student</option>
                                    </select>
                                    <span class="invalid-feedback form-invalid fw-bold second-std-select-error"
                                        id="second-std-select-error" role="alert"></span>
                                    @error('second_std_id')
                                        <span class="invalid-feedback form-invalid fw-bold second-std-select-error"
                                            id="second-std-select-error" role="alert">
                                            {{ $message }}
                                        </span>
                                    @enderror
                                    {{-- <input type="hidden" id="hidden_class_id" name="class_id" value="">
                                    <input type="hidden" id="hidden_section_id" name="section_id" value=""> --}}
                                    <input type="hidden" id="hidden_std_id" name = "std_id" value="">

                                </div>

                            </div>
                            <div class="mt-3">
                                <button type="submit" id="relative-std-btn" class="btn btn-primary">Add as Relative
                                    Student</button> <img src="{{ config('myconfig.myloader') }}" alt="Loading..."
                                    class="loader" id="loader" style="display:none; width:10%;">
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
            let initialSecondSectionId = $('#initialSecondSectionId').val();
            let initialSectionId = $('#initialSectionId').val();
            const classFirst = $('#class_id');
            const classSecond = $('#second_class_id');
            const sectionFirst = $('#section_id');
            const sectionSecond = $('#second_section_id');
            const sessionSelect = $('#current_session').val();
            const stdSelect = $('#std_id');
            const stdSecondSelect = $('#second_std_id');
            getClassSection(classFirst.val(), initialSectionId, classFirst, sectionFirst);
            getClassSection(classSecond.val(), initialSecondSectionId, classSecond, sectionSecond);

            function populateDropdowns(data, dropdown) {
                dropdown.empty();
                if (data.length > 0) {

                    dropdown.append('<option value="">Select Student</option>');
                    $.each(data, function(id, value) {
                        dropdown.append('<option value="' + value.srno + '">' +
                            value.rollno + '. ' + value.student_name + '/SH. ' + value.f_name +
                            '</option>');


                    });
                } else {
                    dropdown.append('<option value="">No Student Found</option>');
                }
            }


            function fetchStdNameFather(classId, sectionId, stdDropdown) {
                if (classId && sectionId) {
                    $.ajax({
                        url: '{{ route('stdNameFather.get') }}',
                        type: 'GET',
                        dataType: 'JSON',
                        data: {
                            class_id: classId,
                            section_id: sectionId,
                            session_id: sessionSelect,
                        },
                        success: function(data) {
                            populateDropdowns(data, stdDropdown);
                            stdWithRelative(data);
                        },
                        error: function(xhr) {
                            console.error('Error fetching student details:', xhr);
                        }
                    });

                }
            }

            function stdWithRelative(data) {

                stdSelect.change(function(e) {
                    $('#hidden_std_id').val($(this).val());
                    const selectedStdId = $(this).val();

                    var rowsHtml = '';
                    $.ajax({
                        url: '{{ route('admin.getStdWithRelativeStd') }}',
                        type: 'GET',
                        dataType: 'JSON',
                        data: {
                            srno: selectedStdId
                        },
                        success: function(data) {
                            $.each(data.data, function(id, value) {
                                if (value) {

                                    rowsHtml += `
                                                    <tr>
                                                        <td>${value.srno}</td>
                                                        <td>${value.student_name}</td>
                                                        <td>SH. ${value.f_name}</td>
                                                        <td>${value.m_name}</td>
                                                        <td>${value.class_name}</td>
                                                        <td>${value.section_name}</td>
                                                        <td>

                                                                ${value.srno === selectedStdId ?
                                                                    `<a href="#" class="btn btn-sm btn-icon p-1 edit-section-editBtn" disabled>
                                                                        <i class="mdi mdi-delete edit-section-editBtn" data-bs-toggle="tooltip" data-bs-offset="0,4" data-bs-placement="top" title="Delete" disabled></i>
                                                                        </a>` :
                                                                    `<form action="${siteUrl}/admin/edit-section/edit-relative-std/${value.srno}/remove" method="POST" style="display:inline;">
                                                                                    @csrf
                                                                                    <button type="submit" class="btn btn-sm btn-icon p-1 delete-form-btn"
                                                                                        data-bs-toggle="tooltip" data-bs-offset="0,4"
                                                                                        data-bs-placement="top" data-bs-html="true" title="Delete">
                                                                                        <i class="mdi mdi-delete"></i>
                                                                                    </button>
                                                                                </form>`

                                                                }
                                                        </td>
                                                    </tr>`;
                                    $('#std-container table tbody').html(rowsHtml);
                                    $('#std-container').show();
                                } else {
                                    $('#std-container').hide();
                                }
                            });
                        },
                        error: function(xhr) {

                            console.error('Error fetching student details:', xhr);
                        }
                    });
                    if (selectedStdId === stdSecondSelect.val()) {
                        e.preventDefault();
                        console.log('error');
                        $('#second-std-select-error').show().text(
                            "Please Select different student for relation");
                    } else {
                        $('#second-std-select-error').hide();
                    }


                });
            }

            sectionFirst.change(function() {
                fetchStdNameFather(classFirst.val(), sectionFirst.val(), stdSelect);
            });
            sectionSecond.change(function() {
                fetchStdNameFather(classSecond.val(), sectionSecond.val(), stdSecondSelect);
            });
            stdSecondSelect.change(function(e) {
                if ($(this).val() === stdSelect.val()) {
                    e.preventDefault();
                    console.log('error');
                    $('#second-std-select-error').show().text(
                        "Please Select different student for relation");
                } else {
                    $('#second-std-select-error').hide();
                }
            });
            $('#relative-std-btn').on('click', function(event) {
                if ($('#second-std-select-error').is(':visible')) {
                    event.preventDefault();
                } else {
                    location.reload(true);
                }
            });
            $('#add-relative-form').validate();

        });
    </script>
@endsection
