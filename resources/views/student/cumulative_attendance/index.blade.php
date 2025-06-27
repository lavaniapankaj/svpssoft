@extends('student.index')
@section('sub-content')
    <div class="container">
        @if (Session::has('success'))
            @section('scripts')
                <script>
                    swal("Successful", "{{ Session::get('success') }}", "success").then(() => {
                        $('#std-form').hide();
                        location.reload();
                    });
                </script>
            @endsection
        @endif

        @if (Session::has('error'))
            @section('scripts')
                <script>
                    swal("Error", "{{ Session::get('error') }}", "error");
                </script>
            @endsection
        @endif
        <div class="row justify-content-center">
            <div class="col-md-14">
                <div class="card">
                    <div class="card-header">
                        {{ 'Cumulative Attendance Report' }}
                        <a href="{{ route('student.attendance.report') }}" class="btn btn-warning btn-sm"
                            style="float: right;">Back</a>

                    </div>
                    <div class="card-body">
                        <form id="class-section-form" method="GET">

                            <div class="row">
                                <div class="form-group col-md-4">
                                    <label for="class_id" class="mt-2">Class <span class="text-danger">*</span></label>
                                    <select name="class" id="class_id"
                                        class="form-control @error('class') is-invalid @enderror" required>
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
                                    @error('class')
                                        <span class="invalid-feedback form-invalid fw-bold"
                                            role="alert">{{ $message }}</span>
                                    @enderror

                                </div>


                                <div class="form-group col-md-4">
                                    <label for="section_id" class="mt-2">Section <span
                                            class="text-danger">*</span></label>
                                    <input type="hidden" id="initialSectionId" value="{{ old('section') }}">
                                    <select name="section" id="section_id"
                                        class="form-control @error('section') is-invalid @enderror" required>
                                        <option value="">Select Section</option>

                                    </select>
                                    <input type="hidden" name="current_session" value='' id="current_session">
                                    @error('section')
                                        <span class="invalid-feedback form-invalid fw-bold"
                                            role="alert">{{ $message }}</span>
                                    @enderror
                                    <img src="{{ config('myconfig.myloader') }}" alt="Loading..." class="loader"
                                        id="loader" style="display:none; width:10%;">
                                </div>
                                <div class="form-group col-md-4">
                                    <label for="std_id" class="mt-2">Student <span class="text-danger">*</span></label>
                                    <select name="std_id" id="std_id"
                                        class="form-control @error('std_id') is-invalid @enderror" required>
                                        <option value="">All Students</option>
                                    </select>
                                    @error('std_id')
                                        <span class="invalid-feedback form-invalid fw-bold"
                                            role="alert">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="mt-3">
                                <button type="button" id="show-report" class="btn btn-primary"> Show Report</button>
                                <span class="text-danger fw-bold" id="no-data"></span>
                            </div>

                        </form>
                        <div id="report-table">

                            <div class="row table mt-2 table-responsive" id="">
                                <table id="report-excel" class="table table-striped table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Rollno</th>
                                            <th>Student </th>
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
                                            <td></td>
                                            <td></td>
                                            <th>P</th>
                                            <th>A</th>
                                            <th>Cum.</th>
                                            <th>P</th>
                                            <th>A</th>
                                            <th>Cum.</th>
                                            <th>P</th>
                                            <th>A</th>
                                            <th>Cum.</th>
                                            <th>P</th>
                                            <th>A</th>
                                            <th>Cum.</th>
                                            <th>P</th>
                                            <th>A</th>
                                            <th>Cum.</th>
                                            <th>P</th>
                                            <th>A</th>
                                            <th>Cum.</th>
                                            <th>P</th>
                                            <th>A</th>
                                            <th>Cum.</th>
                                            <th>P</th>
                                            <th>A</th>
                                            <th>Cum.</th>
                                            <th>P</th>
                                            <th>A</th>
                                            <th>Cum.</th>
                                            <th>P</th>
                                            <th>A</th>
                                            <th>Cum.</th>
                                            <th>P</th>
                                            <th>A</th>
                                            <th>Cum.</th>
                                            <th>P</th>
                                            <th>A</th>
                                            <th>Cum.</th>

                                        </tr>
                                    </thead>
                                    <tbody id="report-body">


                                    </tbody>

                                </table>
                            </div>
                            <button id="download-csv" class="btn btn-primary mt-2">Download Excel</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('std-scripts')
    <script>
        $(document).ready(function() {
            var loader = $('#loader');
            var reportTable = $('#report-table');
            let initialClassId = $('#class_id').val();
            let initialSectionId = $('#initialSectionId').val();
            getClassSection(initialClassId, initialSectionId);
            reportTable.hide();
            $('#no-data').hide();
            $('#class-section-form').validate({
                rules: {
                    std_id: {
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
                    std_id: {
                        required: "Please select student.",
                    },
                    class: {
                        required: "Please select a class.",
                    },
                    section: {
                        required: "Please select a section.",
                    },
                },
            });
            getStudentDropdown();

            $('#show-report').on('click', function() {
                if ($('#class-section-form').valid()) {
                    let classId = $('#class_id').val();
                    let sectionId = $('#section_id').val();
                    let sessionId = $('#current_session').val();
                    let stdSelect = $('#std_id').val();
                    if (classId && sectionId && sessionId && stdSelect) {
                        loader.show();
                        reportTable.show();
                        $.ajax({
                            url: '{{ route('student.cumulative-attendance.report') }}',
                            type: 'GET',
                            dataType: 'JSON',
                            data: {
                                class: classId,
                                section: sectionId,
                                session: sessionId,
                                std_id: stdSelect,
                            },
                            success: function(data) {
                                let row = '';
                                if (data.data) {
                                    $.each(data.data, function(index, student) {
                                        // Check if Attendance is not empty or undefined
                                        if (student.Attendance && Object.keys(student
                                                .Attendance).length > 0) {
                                            const rollno = student.Rollno;
                                            const name = student.Name;

                                            row += `<tr>
                                    <td>${rollno}</td>
                                    <td>${name}</td>`;

                                            const months = ["Apr", "May", "Jun", "Jul",
                                                "Aug", "Sep", "Oct", "Nov", "Dec",
                                                "Jan", "Feb", "Mar"
                                            ];

                                            months.forEach(month => {
                                                if (student.Attendance[month]) {
                                                    const attendance = student
                                                        .Attendance[month];
                                                    row += `
                                            <td class="fw-bold text-success">${attendance.P || 0}</td>
                                            <td class="fw-bold text-danger">${attendance.A || 0}</td>
                                            <td class="fw-bold text-primary">${attendance.C || 0}</td>`;
                                                } else {
                                                    row += `
                                            <td class="fw-bold text-success">0</td>
                                            <td class="fw-bold text-danger">0</td>
                                            <td class="fw-bold text-primary">0</td>`;
                                                }
                                            });

                                            row += `</tr>`;
                                        }
                                    });

                                    // Check if rows were added; if none, display "No Students Found"
                                    if (row === '') {
                                        row +=
                                            `<tr><td colspan="37" class="text-center">No Students with Attendance Found</td></tr>`;
                                    }

                                    $("#report-body").html(row);
                                } else {
                                    $("#report-body").html(
                                        '<tr><td colspan="37" class="text-center">No Student Found</td></tr>'
                                        );
                                }
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



            $('#std_id, #class_id, #section_id').change(function() {
                reportTable.hide();

            });

            $('#download-csv').on('click', function() {
                const classId = $('#class_id').val();
                const section = $('#section_id').val();
                const std = $('#std_id').val();
                const session = $('#current_session').val();

                // Redirect to the download route with parameters
                window.location.href = '{{ route('student.cumulative-attendance.csv') }}?class=' +
                    classId +
                    '&section=' + section +
                    '&std_id=' + std + '&session=' + session;
            });
        });
    </script>
@endsection
