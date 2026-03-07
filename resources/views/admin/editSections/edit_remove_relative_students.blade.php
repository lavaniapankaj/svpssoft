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
        <div class="row ">
            <div class="col-md-12">
                <div class="card border-0 bg-white">
                    <div class="card-header flex-wrap bg-white d-flex align-items-center justify-content-between">
                        <h5 class="mb-0 mt-0">{{ 'Edit/Remove Relative Students' }}</h5>
                        <a href="{{ route('admin.editSection.index') }}" class="btn bg-light btn-sm">
                            <span class="mdi mdi-chevron-left me-2"></span>Back
                        </a>
                    </div>
                    <div class="card-body">
                        <!-- First Student Selection -->
                        <form method="get" action="">
                            <div class="row">
                                <div class="form-group col-md-4">
                                    <label for="class_id" class="mt-2">Class <span class="text-danger">*</span></label>
                                    <select name="class_id" id="class_id" class="form-control mx-1 @error('class_id') is-invalid @enderror" required>
                                        <option value="">Select Class</option>
                                        @if (count($classes) > 0)
                                            @foreach ($classes as $key => $class)
                                                <option value="{{ $key }}" {{ old('class') == $key ? 'selected' : '' }}>
                                                    {{ $class }}
                                                </option>
                                            @endforeach
                                        @else
                                            <option value="">No Class Found</option>
                                        @endif
                                    </select>
                                    @error('class_id')
                                        <span class="invalid-feedback form-invalid fw-bold" role="alert">{{ $message }}</span>
                                    @enderror
                                </div>
                                <div class="form-group col-md-4">
                                    <label for="section_id" class="mt-2">Section<span class="text-danger">*</span></label>
                                    <select name="section_id" id="section_id" class="form-control mx-1 @error('section_id') is-invalid @enderror" required>
                                        <option value="">Select Section</option>
                                    </select>
                                    @error('section_id')
                                        <span class="invalid-feedback form-invalid fw-bold" role="alert">{{ $message }}</span>
                                    @enderror
                                </div>
                                <div class="form-group col-md-4">
                                    <label for="std_id" class="mt-2">Student<span class="text-danger">*</span></label>
                                    <select name="std_id" id="std_id" class="form-control mx-1 @error('std_id') is-invalid @enderror" required>
                                        <option value="">Select Student</option>
                                    </select>
                                    @error('std_id')
                                        <span class="invalid-feedback form-invalid fw-bold" role="alert">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <!-- Table Display -->
                            <div class="row mt-4">
                                <div class="table-responsive" id="std-container" style="display: none;">
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
                                        <tbody></tbody>
                                    </table>
                                </div>
                            </div>
                        </form>

                        <!-- Add Relative Form -->
                        <form action="{{ route('admin.editSection.editRemoveRelativeStd.store') }}" method="POST" id="add-relative-form">
                            @csrf
                            <div class="row">
                                <div class="form-group col-md-4">
                                    <label for="second_class_id" class="mt-2">Class <span class="text-danger">*</span></label>
                                    <select name="second_class_id" id="second_class_id" class="form-control mx-1 @error('second_class_id') is-invalid @enderror" required>
                                        <option value="">Select Class</option>
                                        @if (count($classes) > 0)
                                            @foreach ($classes as $key => $class)
                                                <option value="{{ $key }}" {{ old('second_class_id') == $key ? 'selected' : '' }}>
                                                    {{ $class }}
                                                </option>
                                            @endforeach
                                        @else
                                            <option value="">No Class Found</option>
                                        @endif
                                    </select>
                                    @error('second_class_id')
                                        <span class="invalid-feedback form-invalid fw-bold" role="alert">{{ $message }}</span>
                                    @enderror
                                </div>
                                <div class="form-group col-md-4">
                                    <label for="second_section_id" class="mt-2">Section<span class="text-danger">*</span></label>
                                    <select name="second_section_id" id="second_section_id" class="form-control mx-1 @error('second_section_id') is-invalid @enderror" required>
                                        <option value="">Select Section</option>
                                    </select>
                                    @error('second_section_id')
                                        <span class="invalid-feedback form-invalid fw-bold" role="alert">{{ $message }}</span>
                                    @enderror
                                </div>
                                <div class="form-group col-md-4">
                                    <label for="second_std_id" class="mt-2">Student<span class="text-danger">*</span></label>
                                    <select name="second_std_id" id="second_std_id" class="form-control mx-1 @error('second_std_id') is-invalid @enderror" required>
                                        <option value="">Select Student</option>
                                    </select>
                                    <span class="invalid-feedback form-invalid fw-bold d-block" id="second-std-select-error" style="display:none !important;" role="alert"></span>
                                    @error('second_std_id')
                                        <span class="invalid-feedback form-invalid fw-bold d-block" role="alert">{{ $message }}</span>
                                    @enderror

                                    <!-- Hidden field for first student -->
                                    <input type="hidden" id="hidden_std_id" name="std_id" value="">
                                </div>
                            </div>

                            <div class="mt-3">
                                <button type="submit" id="relative-std-btn" class="btn btn-primary">
                                    Add as Relative Student
                                </button>
                                <span>
                                    <img src="{{ config('myconfig.myloader') }}" alt="Loading..." class="loader" id="loader" style="display:none; width:10%;">
                                </span>
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
            const classFirst = $('#class_id');
            const classSecond = $('#second_class_id');
            const sectionFirst = $('#section_id');
            const sectionSecond = $('#second_section_id');
            const stdSelect = $('#std_id');
            const stdSecondSelect = $('#second_std_id');

            // Initialize sections if old values exist
            getClassSection(classFirst.val(), '{{ old("section_id") }}', classFirst, sectionFirst);
            getClassSection(classSecond.val(), '{{ old("second_section_id") }}', classSecond, sectionSecond);


            function populateDropdowns(students, dropdown) {
                dropdown.empty();
                if (students.data && students.data.length > 0) {
                    dropdown.append('<option value="">Select Student</option>');
                    $.each(students.data, function(index, student) {
                        dropdown.append('<option value="' + student.srno + '">' + student.display_name + '</option>');
                    });
                } else {
                    dropdown.append('<option value="">No Student Found</option>');
                }
            }

            function fetchStdNameFather(classId, sectionId, stdDropdown) {
                if (classId && sectionId) {
                    $.ajax({
                        url: '{{ route('getStdForDropDown') }}',
                        type: 'GET',
                        dataType: 'JSON',
                        data: {
                            class_id: classId,
                            section_id: sectionId,
                        },
                        success: function(response) {
                            populateDropdowns(response, stdDropdown);
                        },
                        error: function(xhr) {
                            console.error('Error fetching student details:', xhr);
                        }
                    });
                }
            }

            function loadStudentWithRelatives(selectedStdId) {
                $('#std-container table tbody').empty();
                $('#std-container').hide();

                if (!selectedStdId) {
                    return;
                }

                $.ajax({
                    url: '{{ route('admin.getStdWithRelativeStd') }}',
                    type: 'GET',
                    dataType: 'JSON',
                    data: { srno: selectedStdId },
                    success: function(response) {
                        if (response.status === 'success' && response.data && response.data.length > 0) {
                            var rowsHtml = '';
                            $.each(response.data, function(id, value) {
                                const isSelectedStudent = value.srno == selectedStdId;
                                const rowClass = isSelectedStudent ? 'table-warning' : '';
                                const badgeHtml = isSelectedStudent ? '<span class="badge bg-success ms-2">Selected</span>' : '';

                                rowsHtml += `
                                    <tr class="${rowClass}">
                                        <td>${value.srno}</td>
                                        <td>${value.student_name || ''}${badgeHtml}</td>
                                        <td>${value.f_name || ''}</td>
                                        <td>${value.m_name || ''}</td>
                                        <td>${value.class_name || ''}</td>
                                        <td>${value.section_name || ''}</td>
                                        <td class="text-center">
                                            ${isSelectedStudent ? '<span class="text-muted">-</span>' :
                                                `<form action="${siteUrl}/admin/edit-section/edit-relative-std/${value.srno}/remove" method="POST" class="delete-relative-form" data-student-name="${value.name || ''}" style="display:inline;">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-danger btn-icon p-1 delete-relative-btn">
                                                        <i class="mdi mdi-delete"></i>
                                                    </button>
                                                </form>`

                                            }
                                        </td>
                                    </tr>`;
                            });

                            $('#std-container table tbody').html(rowsHtml);
                            $('#std-container').show();
                        } else {
                            $('#std-container').hide();
                        }
                    },
                    error: function(xhr) {
                        console.error('Error fetching student details:', xhr);
                        $('#std-container').hide();
                    }
                });
            }

            // Event handlers
            stdSelect.change(function() {
                const selectedStdId = $(this).val();
                $('#hidden_std_id').val(selectedStdId);
                loadStudentWithRelatives(selectedStdId);
                validateStudentSelection();
            });

            sectionFirst.change(function() {
                stdSelect.val('').trigger('change');
                $('#std-container').hide();
                fetchStdNameFather(classFirst.val(), sectionFirst.val(), stdSelect);
            });

            sectionSecond.change(function() {
                fetchStdNameFather(classSecond.val(), sectionSecond.val(), stdSecondSelect);
            });

            stdSecondSelect.change(function() {
                validateStudentSelection();
            });

            function validateStudentSelection() {
                const firstStd = stdSelect.val();
                const secondStd = stdSecondSelect.val();

                if (firstStd && secondStd && firstStd === secondStd) {
                    $('#second-std-select-error').show().text('Please select different students for relation');
                    return false;
                } else {
                    $('#second-std-select-error').text('');
                    $('#second-std-select-error').hide();
                    return true;
                }
            }

            // Form submission
            $('#add-relative-form').on('submit', function(e) {
                const firstStd = $('#hidden_std_id').val();
                const secondStd = $('#second_std_id').val();

                if (!firstStd) {
                    e.preventDefault();
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Please select the first student'
                    });
                    return false;
                }

                if (!secondStd) {
                    e.preventDefault();
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Please select the second student to add as relative'
                    });
                    return false;
                }

                if (!validateStudentSelection()) {
                    e.preventDefault();
                    /* Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Both students cannot be the same. Please select different students.'
                    }); */
                    return false;
                }

                $('#loader').show();
                $('#relative-std-btn').prop('disabled', true);
            });


            $(document).on('click', '.delete-relative-btn', function(e) {
                e.preventDefault();
                const form = $(this).closest('form');
                const studentName = form.data('student-name');

                Swal.fire({
                    title: 'Are you sure?',
                    text: `Do you want to remove ${studentName ? studentName + ' as a' : 'this'} relative?`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Yes, remove it!',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });
        });
    </script>
@endsection