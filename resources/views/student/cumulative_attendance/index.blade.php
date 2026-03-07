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

        <div class="row">
            <div class="col-md-12">
                <div class="card border-0 bg-white">
                    <div class="card-header flex-wrap bg-white d-flex align-items-center justify-content-between">
                        <h5 class="mb-0 mt-0">{{ 'Cumulative Attendance Report' }}</h5>
                        <a href="{{ route('student.cumulative-attendance.index') }}" class="btn bg-light btn-sm">
                            <span class="mdi mdi-chevron-left me-2"></span>Back
                        </a>
                    </div>

                    <div class="card-body">
                        <form id="class-section-form" method="GET">
                            <div class="row">
                                {{-- Class --}}
                                <div class="form-group col-md-6">
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
                                <div class="form-group col-md-6">
                                    <label for="st_section_id" class="mt-2">Section <span class="text-danger">*</span></label>
                                    <select name="section" id="st_section_id" class="form-control" required>
                                        <option value="">Select Section</option>
                                    </select>
                                    <span class="invalid-feedback form-invalid fw-bold" id="section-error" role="alert"></span>
                                </div>
                            </div>

                            <div class="row">
                                {{-- Student --}}
                                <div class="form-group col-md-6">
                                    <label for="st_student_id" class="mt-2">Student <span class="text-danger">*</span></label>
                                    <select name="std" id="st_student_id" class="form-control" required>
                                        {{-- name="std" matches what the API validator expects --}}
                                        <option value="">Select Student</option>
                                    </select>
                                    <span class="invalid-feedback form-invalid fw-bold" id="std-error" role="alert"></span>
                                </div>
                            </div>

                            <div class="mt-3">
                                <button type="button" id="show-report" class="btn btn-primary">Show Report</button>
                                <span class="text-danger fw-bold ms-2" id="no-data"></span>
                            </div>
                        </form>

                        {{-- Hidden by default; revealed only after a successful AJAX response --}}
                        <div id="report-table" style="display:none;">
                            <div class="row table mt-2 table-responsive" id="cumulative-attendance">
                                <table id="report-excel" class="table table-striped table-bordered">
                                    <thead>
                                        <tr>
                                            <th rowspan="2">Rollno</th>
                                            <th rowspan="2">Student</th>
                                            <th colspan="3">April</th>
                                            <th colspan="3">May</th>
                                            <th colspan="3">June</th>
                                            <th colspan="3">July</th>
                                            <th colspan="3">August</th>
                                            <th colspan="3">September</th>
                                            <th colspan="3">October</th>
                                            <th colspan="3">November</th>
                                            <th colspan="3">December</th>
                                            <th colspan="3">January</th>
                                            <th colspan="3">February</th>
                                            <th colspan="3">March</th>
                                        </tr>
                                        <tr>
                                            {{-- 12 months x 3 columns = 36 sub-headers --}}
                                            @for ($i = 0; $i < 12; $i++)
                                                <th>P</th>
                                                <th>A</th>
                                                <th>Cum.</th>
                                            @endfor
                                        </tr>
                                    </thead>
                                    <tbody id="report-body"></tbody>
                                </table>
                            </div>

                            <button id="download-csv" class="btn btn-success mt-2">
                                <span class="mdi mdi-file-excel me-1"></span>Download Excel
                            </button>
                        </div>
                        {{-- /.report-table --}}
                    </div>
                    {{-- /.card-body --}}
                </div>
            </div>
        </div>
    </div>
@endsection

@section('std-scripts')
    <script>
        $(document).ready(function () {

            var MONTHS = ['Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec','Jan','Feb','Mar'];

            // ------------------------------------------------------------------ //
            //  Helper: hide report and clear rows
            // ------------------------------------------------------------------ //
            function resetReport() {
                $('#report-table').hide();
                $('#report-body').html('');
                $('#no-data').text('');
            }

            // ------------------------------------------------------------------ //
            //  On page load: restore dropdown chain from URL params if present
            // ------------------------------------------------------------------ //
            if ($('#st_class_id').val()) {
                getStudentWithoutAllSections($('#st_class_id').val(), function () {
                    var savedSection = '{{ request()->get("section") }}';
                    if (savedSection) {
                        $('#st_section_id').val(savedSection).trigger('change');
                    }
                });
            }

            // ------------------------------------------------------------------ //
            //  Class change
            // ------------------------------------------------------------------ //
            $('#st_class_id').on('change', function () {
                resetReport();
                $('#st_section_id').html('<option value="">Select Section</option>');
                $('#st_student_id').html('<option value="">Select Student</option>');

                if ($(this).val()) {
                    getStudentWithoutAllSections($(this).val(), function () {});
                }
            });

            // ------------------------------------------------------------------ //
            //  Section change
            // ------------------------------------------------------------------ //
            $('#st_section_id').on('change', function () {
                resetReport();
                $('#st_student_id').html('<option value="">Select Student</option>');

                if ($('#st_class_id').val() && $(this).val()) {
                    getStAllStudents($('#st_class_id').val(), $(this).val());
                }
            });

            // ------------------------------------------------------------------ //
            //  Student change
            // ------------------------------------------------------------------ //
            $('#st_student_id').on('change', function () {
                resetReport();
            });

            // ------------------------------------------------------------------ //
            //  Show Report
            // ------------------------------------------------------------------ //
            $('#show-report').on('click', function () {
                $('#no-data').text('');

                if (!$('#st_class_id').val() || !$('#st_section_id').val() || !$('#st_student_id').val()) {
                    $('#no-data').text('Please select Class, Section and Student.');
                    return;
                }

                $('#loader').show();
                resetReport();

                $.ajax({
                    url      : '{{ route('student.cumulative-attendance.report') }}',
                    type     : 'POST',
                    dataType : 'json',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    data: {
                        class  : $('#st_class_id').val(),
                        section: $('#st_section_id').val(),
                        std    : $('#st_student_id').val(),
                    },
                    success: function (response) {
                        if (response.status !== 'success' || !response.data) {
                            $('#no-data').text(response.message || 'No data found.');
                            return;
                        }

                        var rows = '';

                        $.each(response.data, function (srno, student) {
                            var attendance = student.Attendance || {};

                            if (!Object.keys(attendance).length) return;

                            rows += '<tr>'
                                + '<td>' + student.Rollno + '</td>'
                                + '<td>' + student.Name   + '</td>';

                            $.each(MONTHS, function (i, month) {
                                var m = attendance[month];
                                if (m) {
                                    rows += '<td class="fw-bold text-success">' + (m.P   || 0) + '</td>'
                                        + '<td class="fw-bold text-danger">'  + (m.A   || 0) + '</td>'
                                        + '<td class="fw-bold text-primary">' + (m.Cum || 0) + '</td>';
                                } else {
                                    rows += '<td class="fw-bold text-success">0</td>'
                                        + '<td class="fw-bold text-danger">0</td>'
                                        + '<td class="fw-bold text-primary">0</td>';
                                }
                            });

                            rows += '</tr>';
                        });

                        if (rows === '') {
                            rows = '<tr><td colspan="38" class="text-center">No attendance records found.</td></tr>';
                        }

                        $('#report-body').html(rows);
                        $('#report-table').show();
                    },
                    error: function (xhr) {
                        $('#no-data').text('Something went wrong. Please try again.');
                        console.error('Cumulative report error:', xhr.responseText);
                    },
                    complete: function () {
                        $('#loader').hide();
                    }
                });
            });

            // ------------------------------------------------------------------ //
            //  Download CSV
            // ------------------------------------------------------------------ //
            $('#download-csv').on('click', function () {
                if (!$('#st_class_id').val() || !$('#st_section_id').val() || !$('#st_student_id').val()) {
                    $('#no-data').text('Please select Class, Section and Student before downloading.');
                    return;
                }
                window.location.href = '{{ route('student.cumulative-attendance.csv') }}'
                    + '?class='   + $('#st_class_id').val()
                    + '&section=' + $('#st_section_id').val()
                    + '&std='     + $('#st_student_id').val();
            });

        });
    </script>
@endsection