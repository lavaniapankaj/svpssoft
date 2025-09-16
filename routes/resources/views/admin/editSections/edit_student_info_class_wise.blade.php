@extends('admin.index')
@section('sub-content')
    <div class="container-fluid">
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
        <div class="row">
            <div class="col-md-12">
                <div class="card border-0 bg-white">
           <div class="card-header flex-wrap bg-white d-flex align-items-center justify-content-between"><h5 class="mb-0 mt-0">{{ 'Update Student Info. Class Wise' }}</h5>
                        <a href="{{ route('admin.editSection.index') }}" class="btn bg-light btn-sm" ><span class="mdi mdi-chevron-left me-2"></span>Back</a>
                    </div>
                    <div class="card-body">
                        <form id="class-section-form">
                            <div class="row">
                                <div class="form-group col-md-6">
                                    <label for="class_id" class="mt-2">Class <span class="text-danger">*</span></label>
                                    <input type="hidden" id="initialClassId"
                                        value="{{ old('class', request()->get('class_id')) }}">
                                    <select name="class" id="class_id"
                                        class="form-control @error('class') is-invalid @enderror" required>
                                        <option value="">Select Class</option>
                                        @if (count($classes) > 0)
                                            @foreach ($classes as $key => $class)
                                                <option value="{{ $key }}"
                                                    {{ old('class', request()->get('class_id')) == $key ? 'selected' : '' }}>
                                                    {{ $class }}</option>
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
                                    <input type="hidden" id="initialSectionId"
                                        value="{{ old('section', request()->get('section_id')) }}">
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
                            <form action="{{ route('admin.editSection.editStdInfoClass.store') }}" method="POST"
                                id="std-form">
                                @csrf
                                <input type="hidden" name="class"
                                    value="{{ old('class', request()->get('class_id')) }}">
                                <input type="hidden" name="section"
                                    value="{{ old('section', request()->get('section_id')) }}">
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
            // Check if there are validation errors - if yes, keep form visible
            let hasValidationErrors = @json($errors->any());

            // Only hide form if there are no validation errors
            if (!hasValidationErrors) {
                $('#std-form').hide();
            }

            var loader = $('#loader');

            // Restore class and section values from old input or URL parameters
            const oldClass = @json(old('class'));
            const oldSection = @json(old('section'));
            const initialClassId = $('#initialClassId').val();
            const initialSectionId = $('#initialSectionId').val();

            // Set class value if available
            if (oldClass) {
                $('#class_id').val(oldClass);
            } else if (initialClassId) {
                $('#class_id').val(initialClassId);
            }

            // Load sections and set section value
            getClassSection($('#class_id').val(), oldSection || initialSectionId);

            const selectedClassId = $('#class_id');
            const selectedSectionId = $('#section_id');
            const sessionId = $('#current_session').val();
            const paginationContainer = $('#std-pagination');

            // If there are validation errors and both class and section are selected, auto-load students
            if (hasValidationErrors && $('#class_id').val() && oldSection) {
                // Wait a bit for sections to load, then auto-trigger show details
                setTimeout(function() {
                    if ($('#section_id').val()) {
                        $('#show-details').trigger('click');
                    }
                }, 1000);
            }

            // Track current class and section to detect changes
            let currentClassId = selectedClassId.val();
            let currentSectionId = selectedSectionId.val();

            // Listen for changes in class and section dropdowns
            selectedClassId.on('change', function() {
                currentClassId = $(this).val();
                currentSectionId = selectedSectionId.val();
            });

            selectedSectionId.on('change', function() {
                currentSectionId = $(this).val();
            });

            $('#show-details').on('click', function() {
                loader.show();

                // Get old inputs and validation errors from server
                let oldInputs = @json(old('students', []));
                let validationErrors = @json($errors->toArray());

                // Track the last loaded class/section combination
                let lastLoadedClassId = null;
                let lastLoadedSectionId = null;

                function stdDetails() {
                    const classId = selectedClassId.val();
                    const sectionId = selectedSectionId.val();

                    // Clear old inputs and errors if class or section has changed
                    if (lastLoadedClassId !== null && lastLoadedSectionId !== null) {
                        if (classId !== lastLoadedClassId || sectionId !== lastLoadedSectionId) {
                            oldInputs = [];
                            validationErrors = {};
                            console.log('Class or section changed, clearing old data');
                        }
                    }

                    // Update the last loaded values
                    lastLoadedClassId = classId;
                    lastLoadedSectionId = sectionId;

                    if (classId && sectionId && sessionId) {
                        // Always show the form when loading student details
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
                                    // Function to get old input value
                                    const getOldValue = (field) => {
                                        return oldInputs[index] ? (oldInputs[index][field] ?? '') : std[field];
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
                                                value="${getOldValue('g_f_name') ?? ''}"
                                                class="form-control ${hasError('g_f_name') ? 'is-invalid' : ''}"
                                                data-index="${index}">
                                            ${hasError('g_f_name') ? `<div class="invalid-feedback">${getErrorMessage('g_f_name')}</div>` : ''}
                                        </td>
                                        <td>
                                            <input type="date"
                                                name="students[${index}][dob]"
                                                value="${getOldValue('dob') ?? ''}"
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

                                if (stdHtml == '') {
                                    stdHtml =
                                        '<tr><td colspan="10" class="text-center">No Student found</td></tr>';
                                }

                                $('#std-container table tbody').html(stdHtml);
                            },
                            complete: function() {
                                loader.hide();
                            },
                            error: function(xhr) {
                                console.log(xhr);
                                console.error(xhr.responseText);
                                // Keep the form visible even if there's an error
                                $('#std-form').show();
                            }
                        });
                    } else {
                        // Hide form if required fields are not selected
                        $('#std-form').hide();
                        loader.hide();
                    }
                }

                stdDetails();
            });

            // Update hidden fields when class/section changes
            selectedClassId.on('change', function() {
                $('input[name="class"]').val($(this).val());
            });

            selectedSectionId.on('change', function() {
                $('input[name="section"]').val($(this).val());
            });

            // Set initial values for hidden fields
            if (selectedClassId.val()) {
                $('input[name="class"]').val(selectedClassId.val());
            }
            if (selectedSectionId.val()) {
                $('input[name="section"]').val(selectedSectionId.val());
            }
        });
    </script>
@endsection
