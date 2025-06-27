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
            <div class="col-md-14">
                <div class="card">
                    <div class="card-header">
                        {{ 'Update Student Info. Class Wise' }}
                        <a href="{{ route('admin.editSection.index') }}" class="btn btn-warning btn-sm"
                            style="float: right;">Back</a>

                    </div>
                    <div class="card-body">
                        <form id="class-section-form">
                            <div class="row">
                                <div class="form-group col-md-6">
                                    <label for="class_id" class="mt-2">Class <span class="text-danger">*</span></label>
                                    <input type="hidden" id="initialClassId" value="{{ old('initialClassId',request()->get('class_id') !== null ? request()->get('class_id') : '') }}">
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
                            <form action="{{ route('admin.editSection.editStdInfoClass.store') }}" method="POST" id="std-form">
                                @csrf
                                <table class="table table-responsible">
                                    <input type="hidden" name="current_session" value='' id="current_session">

                                    <thead>
                                        <tr>
                                            <th>Roll No.</th>
                                            <th>SRNO</th>
                                            <th>Name</th>
                                            <th>Father Name</th>
                                            <th>Mother Name</th>
                                            <th>Grand Father Name</th>
                                            <th>DOB</th>
                                            <th>Contact 1</th>
                                            <th>Contact 2</th>
                                            <th>Age Proof</th>
                                        </tr>
                                    </thead>
                                    <tbody>

                                    </tbody>
                                </table>
                                <div class="row">
                                    <div class="mt-3">
                                        <button type="submit" class="btn btn-primary"
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
@section('admin-scripts')
    <script>
        $(document).ready(function() {
            $('#std-form').hide();
            var loader = $('#loader');
            getClassSection($('#class_id').val(), $('#initialSectionId').val());
            $('#show-details').on('click', function() {

                const classId = $('#class_id').val();
                const sectionId = $('#section_id').val();
                const sessionId = $('#current_session').val();
                const paginationContainer = $('#std-pagination');
                loader.show();
                let oldInputs = @json(old('students', [])); // Get old input if any
                let validationErrors = @json($errors->toArray()); // Get validation errors if any


                function stdDetails() {
                       // Only hide the form initially if there are no validation errors
                 
                    if (classId && sectionId && sessionId) {
                        $('#std-form').show();
                        $.ajax({
                            url: '{{ route("stdNameFather.get") }}',
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
                                    // Function to get old input value
                                    const getOldValue = (field) => {
                                        return oldInputs[index] ? oldInputs[index][field] : std[field];
                                    };

                                    // Function to check for validation errors
                                    const hasError = (field) => {
                                        return validationErrors[`students.${index}.${field}`] !== undefined;
                                    };

                                    // Function to get error message
                                    const getErrorMessage = (field) => {
                                        return hasError(field) ? validationErrors[`students.${index}.${field}`][0] : '';
                                    };

                                    stdHtml += `<tr>
                                        <td>${std.rollno}</td>
                                        <td>${std.srno}</td>
                                        <td>
                                            <input type="hidden" name="students[${index}][srno]" value="${std.srno}">
                                            <input type="text"
                                                name="students[${index}][student_name]"
                                                value="${getOldValue('student_name')}"
                                                class="form-control ${hasError('student_name') ? 'is-invalid' : ''}"
                                                data-index="${index}">
                                            ${hasError('student_name') ? `<div class="invalid-feedback">${getErrorMessage('student_name')}</div>` : ''}
                                        </td>
                                        <td>
                                            <input type="text"
                                                name="students[${index}][f_name]"
                                                value="${getOldValue('f_name')}"
                                                class="form-control ${hasError('f_name') ? 'is-invalid' : ''}"
                                                data-index="${index}">
                                            ${hasError('f_name') ? `<div class="invalid-feedback">${getErrorMessage('f_name')}</div>` : ''}
                                        </td>
                                        <td>
                                            <input type="text"
                                                name="students[${index}][m_name]"
                                                value="${getOldValue('m_name')}"
                                                class="form-control ${hasError('m_name') ? 'is-invalid' : ''}"
                                                data-index="${index}">
                                            ${hasError('m_name') ? `<div class="invalid-feedback">${getErrorMessage('m_name')}</div>` : ''}
                                        </td>
                                        <td>
                                            <input type="text"
                                                name="students[${index}][g_f_name]"
                                                value="${getOldValue('g_f_name')}"
                                                class="form-control ${hasError('g_f_name') ? 'is-invalid' : ''}"
                                                data-index="${index}">
                                            ${hasError('g_f_name') ? `<div class="invalid-feedback">${getErrorMessage('g_f_name')}</div>` : ''}
                                        </td>
                                        <td>
                                            <input type="date"
                                                name="students[${index}][dob]"
                                                value="${getOldValue('dob')}"
                                                class="form-control ${hasError('dob') ? 'is-invalid' : ''}"
                                                data-index="${index}">
                                            ${hasError('dob') ? `<div class="invalid-feedback">${getErrorMessage('dob')}</div>` : ''}
                                        </td>
                                        <td>
                                            <input type="text"
                                                name="students[${index}][f_mobile]"
                                                value="${getOldValue('f_mobile') || ''}"
                                                class="form-control ${hasError('f_mobile') ? 'is-invalid' : ''}"
                                                data-index="${index}">
                                            ${hasError('f_mobile') ? `<div class="invalid-feedback">${getErrorMessage('f_mobile')}</div>` : ''}
                                        </td>
                                        <td>
                                            <input type="text"
                                                name="students[${index}][m_mobile]"
                                                value="${getOldValue('m_mobile') || ''}"
                                                class="form-control ${hasError('m_mobile') ? 'is-invalid' : ''}"
                                                data-index="${index}">
                                            ${hasError('m_mobile') ? `<div class="invalid-feedback">${getErrorMessage('m_mobile')}</div>` : ''}
                                        </td>
                                        <td>
                                            <select name="students[${index}][age_proof]"
                                                class="form-control ${hasError('age_proof') ? 'is-invalid' : ''}"
                                                data-index="${index}">
                                                <option value="">Select Age Proof</option>
                                                <option value="1" ${getOldValue('age_proof') == 1 ? 'selected' : ''}>Birth Certificate</option>
                                                <option value="2" ${getOldValue('age_proof') == 2 ? 'selected' : ''}>Transfer Certificate</option>
                                                <option value="3" ${getOldValue('age_proof') == 3 ? 'selected' : ''}>Affidavit</option>
                                                <option value="4" ${getOldValue('age_proof') == 4 ? 'selected' : ''}>Aadhar Card</option>
                                            </select>
                                            ${hasError('age_proof') ? `<div class="invalid-feedback">${getErrorMessage('age_proof')}</div>` : ''}
                                        </td>
                                    </tr>`;
                                });

                                if (stdHtml === '') {
                                    stdHtml = '<tr><td colspan="10" class="text-center">No Student found</td></tr>';
                                }
                                $('#std-container table tbody').html(stdHtml);
                            },
                            complete: function() {
                                loader.hide();

                            },
                            error: function(xhr) {
                                console.error(xhr.responseText);
                                // Keep the form visible even if there's an error
                                $('#std-form').show();
                            }
                        });
                    }
                }

                stdDetails();
            });

        });
    </script>
@endsection
