@extends('student.index')
@section('sub-content')
    <div class="container">
        @if (Session::has('success'))
            @section('scripts')
                <script>
                    swal("Good job!", "{{ Session::get('success') }}", "success").then(() => {
                        $('#std-form').hide();
                        location.reload();
                    });
                </script>
            @endsection
        @endif

        @if (Session::has('error'))
            @section('scripts')
                <script>
                    swal("Oops...", "{{ Session::get('error') }}", "error");
                </script>
            @endsection
        @endif
        <div class="row justify-content-center">
            <div class="col-md-14">
                <div class="card">
                    <div class="card-header">
                        {{ 'Relative Wise' }}
                        <a href="{{ route('student.student-report-relative-wise') }}" class="btn btn-warning btn-sm"
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
                                                    {{ old('class') == $key ? 'selected' : '' }}>{{ $class }}
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
                                        value="{{ old('initialStdId', request()->get('std_id') !== null ? request()->get('std_id') : '') }}">
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
                                <img src="{{ config('myconfig.myloader') }}" alt="Loading..." class="loader" id="loader"
                                    style="display:none; width:10%;">
                                <div class="mt-3">
                                    <button type="button" id="show-deatils" class="btn btn-sm btn-primary">Show
                                        Details</button>
                                </div>
                            </div>
                            <div class="row mt-4">
                                <div class="table" id="std-container" style="display: none;">
                                    <table id="example" class="table table-striped table-bordered">
                                        <thead>
                                            <tr>
                                                <th>Class</th>
                                                <th>Section</th>
                                                <th>SRNO</th>
                                                <th>Name</th>
                                                <th>Father's Name</th>
                                                <th>Mother's Name</th>
                                                <th>Father's Mobile</th>
                                                <th>Address</th>
                                                <th>State</th>
                                                <th>District</th>
                                                <th>Phone Number</th>
                                            </tr>
                                        </thead>
                                        <tbody class="">
                                        </tbody>
                                    </table>

                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('std-scripts')
    <script>
        $(document).ready(function() {
            const classFirst = $('#class_id');
            const sectionFirst = $('#section_id');
            const sessionSelect = $('#current_session').val();
            const stdSelect = $('#std_id');
            const stdContainer = $('#std-container');
            const stdTableBody = $('#std-container table tbody');
            const showDetailsButton = $('#show-deatils');

            // Initial Setup
            getClassSection(classFirst.val(), $('#initialSectionId').val());

            // Helper: Populate Dropdown
            function populateDropdowns(data, dropdown) {
                dropdown.empty();
                // dropdown.empty().append('<option value="">Select Student</option>');
                if (data && data.length) {
                    let allStdIds = [];
                    $.each(data, function(id, value) {
                        allStdIds.push(value.srno);
                        dropdown.append(
                            `<option value="${value.srno}">${value.rollno}. ${value.student_name}/SH. ${value.f_name}</option>`
                        );
                    });
                    dropdown.prepend(`<option value="${allStdIds}" selected>All</option>`);
                }
            }

            // Fetch Student Name & Father Details
            function fetchStdNameFather(classId, sectionId) {
                if (classId && sectionId) {
                    $.ajax({
                        url: '{{ route('stdNameFather.get') }}',
                        type: 'GET',
                        dataType: 'JSON',
                        data: {
                            class_id: classId,
                            section_id: sectionId,
                            session_id: sessionSelect
                        },
                        success: function(data) {
                            populateDropdowns(data, stdSelect);
                        },
                        error: function(xhr) {
                            console.error('Error fetching student details:', xhr);
                        }
                    });
                }
            }

            // Populate Student & Relative Details Table
            function populateStudentTable(data) {
                if (Array.isArray(data) && data.length > 0) {
                    let rowsHtml = '';

                    // Iterate over the data array
                    data.forEach(entry => {
                        const student = entry.student; // Main student object
                        const relatives = entry.relatives ||
                    []; // Relatives array (fallback to empty array if null/undefined)

                        // Add the main student's row
                        rowsHtml += `
                            <tr>
                                <td>${student.class_name || 'N/A'}</td>
                                <td>${student.section_name || 'N/A'}</td>
                                <td>${student.srno || 'N/A'}</td>
                                <td>${student.student_name || 'N/A'}</td>
                                <td>SH. ${student.f_name || 'N/A'}</td>
                                <td>${student.m_name || 'N/A'}</td>
                                <td>${student.f_mobile || 'N/A'}</td>
                                <td>${student.address || 'N/A'}</td>
                                <td>${student.state_name || 'N/A'}</td>
                                <td>${student.district_name || 'N/A'}</td>
                                <td>${student.m_mobile || 'N/A'}</td>
                            </tr>
                        `;

                        // Add rows for relatives (if any)
                        relatives.forEach(relative => {
                            rowsHtml += `
                                <tr class="relative-row table-warning">
                                    <td>${relative.class_name || 'N/A'}</td>
                                    <td>${relative.section_name || 'N/A'}</td>
                                    <td>${relative.srno || 'N/A'}</td>
                                    <td>${relative.student_name || 'N/A'}</td>
                                    <td>SH. ${relative.f_name || 'N/A'}</td>
                                    <td>${relative.m_name || 'N/A'}</td>
                                    <td>${relative.f_mobile || 'N/A'}</td>
                                    <td>${relative.address || 'N/A'}</td>
                                    <td>${relative.state_name || 'N/A'}</td>
                                    <td>${relative.district_name || 'N/A'}</td>
                                    <td>${relative.m_mobile || 'N/A'}</td>
                                </tr>
                            `;
                        });
                    });

                    // Populate the table body with the generated rows
                    stdTableBody.html(rowsHtml);

                    // Show the container
                    stdContainer.show();
                } else {
                    // Hide the container if no data is available
                    stdContainer.hide();
                }
            }



            // Fetch Student & Relative Details
            function fetchStdWithRelative(selectedStdId) {
                if (selectedStdId) {
                    // selectedStdId.forEach(st => {

                    $.ajax({
                        url: '{{ route('student.getStdWithRelativeStd') }}',
                        type: 'GET',
                        dataType: 'JSON',
                        data: {
                            srno: selectedStdId
                        },
                        success: function(data) {
                            populateStudentTable(data.data);
                        },
                        error: function(xhr) {
                            console.error('Error fetching student and relative details:', xhr);
                        }
                    });
                    // });
                }
            }

            // Event: Class Change
            classFirst.change(function() {
                stdContainer.hide();
                stdSelect.empty().append('<option value="">Select Student</option>');
            });

            // Event: Section Change
            sectionFirst.change(function() {
                fetchStdNameFather(classFirst.val(), sectionFirst.val());
                stdContainer.hide();
            });

            // Event: Show Details Button Click
            showDetailsButton.click(function() {
                const selectedStdId = stdSelect.val();
                fetchStdWithRelative(selectedStdId);
            });
        });
    </script>
@endsection
