@extends('student.index')
@section('sub-content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card border-0 bg-white">
                    <div class="card-header flex-wrap bg-white d-flex align-items-center justify-content-between">
                        <h5 class="mb-0 mt-0">Attendance Report</h5>
                        <a href="{{ route('student.attendance.report') }}" class="btn bg-light btn-sm">
                            <span class="mdi mdi-chevron-left me-2"></span>Back
                        </a>
                    </div>
                    <div class="card-body">
                        <form id="class-section-form" method="GET">
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

                            <div class="row">
                                <div class="form-group col-md-6">
                                    <label for="start_date" class="mt-2">Enter Start Date <span class="text-danger">*</span></label>
                                    <input type="date" name="start_date" id="start_date"
                                        class="form-control @error('start_date') is-invalid @enderror" required>
                                    @error('start_date')
                                        <span class="invalid-feedback form-invalid fw-bold" role="alert">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="form-group col-md-6">
                                    <label for="end_date" class="mt-2">Enter End Date <span class="text-danger">*</span></label>
                                    <input type="date" name="end_date" id="end_date"
                                        class="form-control @error('end_date') is-invalid @enderror" required>
                                    @error('end_date')
                                        <span class="invalid-feedback form-invalid fw-bold" role="alert">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="mt-3">
                                <button type="button" id="show-report" class="btn btn-primary">Show Report</button>
                                <span class="text-danger fw-bold ms-2" id="no-data"></span>
                                <img src="{{ config('myconfig.myloader') }}" alt="Loading..."
                                    id="loader" style="display:none; width:5%;">
                            </div>
                        </form>

                        {{-- Report Table - hidden until data loads --}}
                        <div class="row mt-3" id="report-table" style="display:none;">
                            <div class="col-md-12">
                                <table class="table table-striped table-bordered" id="report-excel">
                                    <thead>
                                        <tr>
                                            <th colspan="5">Summary</th>
                                        </tr>
                                        <tr>
                                            <th colspan="3">Days</th>
                                            <th>Present</th>
                                            <th>Absent</th>
                                        </tr>
                                    </thead>
                                    <tbody id="summary-body">
                                        <tr>
                                            <td colspan="3"></td>
                                            <td id="present"></td>
                                            <td id="absent"></td>
                                        </tr>
                                    </tbody>
                                    <thead>
                                        <tr>
                                            <th colspan="5">Details</th>
                                        </tr>
                                        <tr>
                                            <th>Roll No.</th>
                                            <th>Name</th>
                                            <th colspan="2">Date</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody id="report-body"></tbody>
                                </table>

                                <button id="download-csv" type="button" class="btn btn-sm btn-primary mt-2">
                                    <i class="bx bx-download"></i> Download Excel
                                </button>
                            </div>
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

        // ------------------------------------------------------------------ //
        //  Helper: reset report
        // ------------------------------------------------------------------ //
        function resetReport() {
            reportTable.hide();
            reportBody.html('');
            $('#present').text('');
            $('#absent').text('');
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
        //  Student / date change
        // ------------------------------------------------------------------ //
        studentSelect.add('#start_date, #end_date').on('change input', function () {
            resetReport();
        });

        // ------------------------------------------------------------------ //
        //  Show Report button
        // ------------------------------------------------------------------ //
        $('#show-report').on('click', function () {
            const classVal   = classSelect.val();
            const sectionVal = sectionSelect.val();
            const stdVal     = studentSelect.val();
            const startDate  = $('#start_date').val();
            const endDate    = $('#end_date').val();

            // Client-side validation
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
            if (!startDate) {
                noData.text('Please enter a start date.');
                return;
            }
            if (!endDate) {
                noData.text('Please enter an end date.');
                return;
            }
            if (endDate < startDate) {
                noData.text('End date must be on or after start date.');
                return;
            }

            fetchAttendanceReport(classVal, sectionVal, stdVal, startDate, endDate);
        });

        // ------------------------------------------------------------------ //
        //  AJAX: fetch attendance report
        // ------------------------------------------------------------------ //
        function fetchAttendanceReport(classVal, sectionVal, stdVal, startDate, endDate) {
            resetReport();
            loader.show();

            $.ajax({
                url      : '{{ route('student.attendance.report.get') }}',
                type     : 'POST',
                dataType : 'json',
                headers  : { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                data: {
                    class      : classVal,
                    section    : sectionVal,
                    std        : stdVal,
                    start_date : startDate,
                    end_date   : endDate,
                },
                success: function (response) {
                    if (response.status === 'error') {
                        noData.text(response.message ?? 'Something went wrong.');
                        return;
                    }

                    if (!response.data || response.data.length === 0) {
                        noData.text('No attendance records found.');
                        return;
                    }

                    // Populate summary — from response.summary
                    $('#present').text(response.summary.present ?? 0);
                    $('#absent').text(response.summary.absent  ?? 0);

                    // Populate detail rows
                    let html = '';
                    response.data.forEach(record => {
                        html += `
                            <tr>
                                <td>${record.rollno       ?? 'N/A'}</td>
                                <td>${record.student_name ?? 'N/A'}</td>
                                <td colspan="2">${record.a_date ?? 'N/A'}</td>
                                <td>${record.status       ?? 'N/A'}</td>
                            </tr>
                        `;
                    });

                    reportBody.html(html);
                    reportTable.show();
                },
                error: function (xhr) {
                    noData.text('Server error, please try again.');
                    console.error('AJAX error:', xhr);
                },
                complete: function () {
                    loader.hide();
                }
            });
        }

        // ------------------------------------------------------------------ //
        //  Download CSV
        // ------------------------------------------------------------------ //
        $('#download-csv').on('click', function () {
            const classVal   = classSelect.val();
            const sectionVal = sectionSelect.val();
            const stdVal     = studentSelect.val();
            const startDate  = $('#start_date').val();
            const endDate    = $('#end_date').val();

            if (!classVal || !sectionVal || !stdVal || !startDate || !endDate) {
                noData.text('Please fill all fields before downloading.');
                return;
            }

            window.location.href = '{{ route('student.download.attendance.csv') }}'
                + '?class='      + classVal
                + '&section='    + sectionVal
                + '&std='        + stdVal
                + '&start_date=' + startDate
                + '&end_date='   + endDate;
        });

    });
</script>
@endsection