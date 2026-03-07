@extends('student.index')
@section('sub-content')
    <div class="container-fluid">
        @if (Session::has('success'))
            @push('st-swal-scripts')
                <script>
                    swal("Successful", "{{ Session::get('success') }}", "success")
                </script>
            @endpush
        @endif

        @if (Session::has('error'))
            @push('st-swal-scripts')
                <script>
                    swal("Error", "{{ Session::get('error') }}", "error")
                </script>
            @endpush
        @endif

        <div class="row justify-content-center">
            <div class="col-md-14">
                <div class="card border-0 bg-white">
                    <div class="card-header flex-wrap bg-white d-flex align-items-center justify-content-between">
                        <h5 class="mb-0 mt-0">Relative Wise</h5>
                        <a href="{{ route('student.student-report-relative-wise') }}" class="btn bg-light btn-sm">
                            <span class="mdi mdi-chevron-left me-2"></span>Back
                        </a>
                    </div>
                    <div class="card-body">
                        <form method="get" action="">
                            <div class="row">
                                {{-- Class --}}
                                <div class="form-group col-md-4">
                                    <label for="st_class_id" class="mt-2">Class <span class="text-danger">*</span></label>
                                    <select name="class" id="st_class_id" class="form-control"
                                        {{ count($classes) == 0 ? 'disabled' : 'required' }}>
                                        @if (count($classes) > 0)
                                            <option value="">Select Class</option>
                                            @foreach ($classes as $key => $class)
                                                <option value="{{ $key }}"
                                                    {{ request()->get('class') == $key ? 'selected' : '' }}>
                                                    {{ $class }}
                                                </option>
                                            @endforeach
                                        @else
                                            <option value="" selected disabled>No Class Found</option>
                                        @endif
                                    </select>
                                    <span class="invalid-feedback form-invalid fw-bold" id="class-error" role="alert"></span>
                                </div>

                                {{-- Section --}}
                                <div class="form-group col-md-4">
                                    <label for="st_section_id" class="mt-2">Section <span class="text-danger">*</span></label>
                                    <select name="section" id="st_section_id" class="form-control" required>
                                        <option value="">Select Section</option>
                                    </select>
                                    <span class="invalid-feedback form-invalid fw-bold" id="section-error" role="alert"></span>
                                </div>
                                {{-- Student --}}
                                <div class="form-group col-md-4">
                                    <label for="st_student_id" class="mt-2">Student <span class="text-danger">*</span></label>
                                    <select name="std" id="st_student_id" class="form-control" required>
                                        <option value="">Select Student</option>
                                    </select>
                                    <span class="invalid-feedback form-invalid fw-bold" id="std-error" role="alert"></span>
                                </div>
                            </div>

                            <div class="mt-3">
                                <button type="button" id="show-report" class="btn btn-primary">Show Report</button>
                                <span class="text-danger fw-bold ms-2" id="no-data"></span>
                                <img src="{{ config('myconfig.myloader') }}" alt="Loading..."
                                    id="loader" style="display:none; width:5%;">
                            </div>

                            <div class="table mt-3" id="report-table" style="display: none;">
                                <table id="example" class="table table-striped table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Class</th>
                                            <th>Section</th>
                                            <th>SRNO</th>
                                            <th>Roll No</th>
                                            <th>Name</th>
                                            <th>Father's Name</th>
                                            <th>Mother's Name</th>
                                            <th>Father's Mobile</th>
                                            <th>Mother's Mobile</th>
                                            <th>Address</th>
                                            <th>State</th>
                                            <th>District</th>
                                        </tr>
                                    </thead>
                                    <tbody id="report-body"></tbody>
                                </table>
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
        $(document).ready(function () {

            // ------------------------------------------------------------------ //
            //  Cached selectors
            // ------------------------------------------------------------------ //
            const reportTable   = $('#report-table');
            const reportBody    = $('#report-body');
            const noData        = $('#no-data');
            const loader        = $('#loader');
            const classSelect   = $('#st_class_id');
            const sectionSelect = $('#st_section_id');
            const studentSelect = $('#st_student_id');
            let dataTable       = null; // DataTable instance

            // ------------------------------------------------------------------ //
            //  Helper: destroy DataTable, hide report, clear rows
            // ------------------------------------------------------------------ //
            function resetReport() {
                if (dataTable) {
                    dataTable.destroy();
                    dataTable = null;
                }
                reportTable.hide();
                reportBody.html('');
                noData.text('');
            }

            // ------------------------------------------------------------------ //
            //  On page load: restore dropdown chain from URL params if present
            // ------------------------------------------------------------------ //
            if (classSelect.val()) {
                getStudentWithoutAllSections(classSelect.val(), function () {
                    var savedSection = '{{ request()->get("section") }}';
                    if (savedSection) {
                        sectionSelect.val(savedSection).trigger('change');
                    }
                });
            }

            // ------------------------------------------------------------------ //
            //  Class change
            // ------------------------------------------------------------------ //
            classSelect.on('change', function () {
                resetReport();
                sectionSelect.html('<option value="">Select Section</option>');
                studentSelect.html('<option value="">Select Student</option>');

                if ($(this).val()) {
                    getStudentWithoutAllSections($(this).val(), function () {});
                }
            });

            // ------------------------------------------------------------------ //
            //  Section change
            // ------------------------------------------------------------------ //
            sectionSelect.on('change', function () {
                resetReport();
                studentSelect.html('<option value="">Select Student</option>');

                if (classSelect.val() && $(this).val()) {
                    getStAllStudents(classSelect.val(), $(this).val());
                }
            });

            // ------------------------------------------------------------------ //
            //  Student change
            // ------------------------------------------------------------------ //
            studentSelect.on('change', function () {
                resetReport();
            });

            // ------------------------------------------------------------------ //
            //  Show Report button click
            // ------------------------------------------------------------------ //
            $('#show-report').on('click', function () {
                const classVal   = classSelect.val();
                const sectionVal = sectionSelect.val();
                const stdVal     = studentSelect.val();

                if (!classVal) {
                    noData.text('Please select a class.');
                    return;
                }
                if (!sectionVal) {
                    noData.text('Please select a section.');
                    return;
                }
                if (!stdVal) {
                    noData.text('Please select a student.');
                    return;
                }

                fetchStdWithRelative();
            });

            // ------------------------------------------------------------------ //
            //  Build table rows from API response
            // ------------------------------------------------------------------ //
            function buildRow(record, isRelative) {
                const rowClass = isRelative ? 'class="relative-row table-warning"' : '';
                return `
                    <tr ${rowClass}>
                        <td>${record.class        ?? 'N/A'}</td>
                        <td>${record.section      ?? 'N/A'}</td>
                        <td>${record.srno         ?? 'N/A'}</td>
                        <td>${record.rollno       ?? 'N/A'}</td>
                        <td>${record.student_name ?? 'N/A'}</td>
                        <td>${record.father_name  ?? 'N/A'}</td>
                        <td>${record.mother_name  ?? 'N/A'}</td>
                        <td>${record.father_mobile ?? 'N/A'}</td>
                        <td>${record.mother_mobile ?? 'N/A'}</td>
                        <td>${record.address      ?? 'N/A'}</td>
                        <td>${record.state_name   ?? 'N/A'}</td>
                        <td>${record.district_name ?? 'N/A'}</td>
                    </tr>
                `;
            }

            // ------------------------------------------------------------------ //
            //  Populate table
            // ------------------------------------------------------------------ //
            function populateStudentTable(data) {
                resetReport();

                if (!Array.isArray(data) || data.length === 0) {
                    noData.text('No records found.');
                    return;
                }

                let rowsHtml = '';

                data.forEach(entry => {
                    // Main student row
                    rowsHtml += buildRow(entry.student, false);

                    // Relative rows (highlighted in yellow)
                    (entry.relatives ?? []).forEach(relative => {
                        rowsHtml += buildRow(relative, true);
                    });
                });

                reportBody.html(rowsHtml);
                reportTable.show();

                // Init DataTable after rows are injected
                dataTable = $('#example').DataTable({
                    pageLength : 25,
                    responsive : true,
                    order      : [],
                    columnDefs : [{ orderable: false, targets: '_all' }]
                });
            }

            // ------------------------------------------------------------------ //
            //  AJAX: fetch students + relatives
            // ------------------------------------------------------------------ //
            function fetchStdWithRelative() {
                resetReport();
                loader.show();
                noData.text('');

                $.ajax({
                    url      : '{{ route('student.get.student-report-relative-wise') }}',
                    type     : 'POST',
                    dataType : 'json',
                    headers  : { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                    data: {
                        class  : classSelect.val(),
                        section: sectionSelect.val(),
                        std    : studentSelect.val(),
                    },
                    success: function (response) {
                        loader.hide();
                        if (response.status === 'success') {
                            populateStudentTable(response.data);
                        } else {
                            noData.text(response.message ?? 'Something went wrong.');
                        }
                    },
                    error: function (xhr) {
                        loader.hide();
                        noData.text('Server error, please try again.');
                        console.error('AJAX error:', xhr);
                    }
                });
            }
        });
    </script>
@endsection