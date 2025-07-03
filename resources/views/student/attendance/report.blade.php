@extends('student.index')
@section('sub-content')
    <div class="container">

        <div class="row justify-content-center">
            <div class="col-md-14">
                <div class="card">
                    <div class="card-header">
                        {{ 'Attendance Report' }}
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


                                <div class="form-group col-md-4">
                                    <label for="section_id" class="mt-2">Section <span
                                            class="text-danger">*</span></label>
                                    <input type="hidden" id="initialSectionId"
                                        value="{{ old('section') }}">
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
                            <div class="row">
                                <div class="form-group col-md-6">
                                    <label for="start_date" class="mt-2">Enter Start Date <span
                                            class="text-danger">*</span></label>
                                    <input type="date" name="start_date" id="start_date"
                                        class="form-control @error('start_date') is-invalid @enderror" required>
                                    @error('start_date')
                                        <span class="invalid-feedback form-invalid fw-bold"
                                            role="alert">{{ $message }}</span>
                                    @enderror
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="end_date" class="mt-2">Enter End Date <span
                                            class="text-danger">*</span></label>
                                    <input type="date" name="end_date" id="end_date"
                                        class="form-control @error('end_date') is-invalid @enderror" required>
                                    @error('end_date')
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
                        <div class="row table mt-2" id="report-table">

                            <table id="report-excel" class="table table-striped table-bordered">
                                <thead>
                                    <tr>
                                        <th colspan="3">Summary</th>
                                    </tr>
                                    <tr>
                                        <th colspan="3">Days</th>
                                        <th>Present</th>
                                        <th>Absent</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td colspan="3"></td>
                                        <td id="present"></td>
                                        <td id="absent"></td>
                                    </tr>
                                    <tr>
                                        <th colspan="5">Details</th>
                                    </tr>
                                    <tr id="details-row">
                                        <th>Roll No.</th>
                                        <th>Name</th>
                                        <th colspan="2">Date</th>
                                        <th>Status</th>
                                    </tr>

                                </tbody>

                            </table>
                            <button id="download-csv" type="button" class="btn btn-sm btn-primary mt-2 col-2">Download Excel</button>
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
            var reportTable = $('#report-table');
            reportTable.hide();
            $('#no-data').hide();
            var loader = $('#loader');
            let initialClassId = $('#class_id').val();
            let initialSectionId = $('#initialSectionId').val();
            getClassSection(initialClassId, initialSectionId);
            $('#class-section-form').validate({
                rules: {
                    start_date: {
                        required: true,
                    },
                    end_date: {
                        required: true,
                    },
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
                    start_date: {
                        required: "Please select start date.",
                    },
                    end_date: {
                        required: "Please select end date.",
                    },
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
                    const classId = $('#class_id').val();
                    const section = $('#section_id').val();
                    const startDate = $('#start_date').val();
                    const endDate = $('#end_date').val();
                    const std = $('#std_id').val();
                    const session = $('#current_session').val();

                    $.ajax({
                        url: '{{ route('student.attendance.report.get') }}',
                        type: 'GET',
                        dataType: 'JSON',
                        data: {
                            class: classId,
                            section: section,
                            start_date: startDate,
                            end_date: endDate,
                            std_id: std,
                            current_session: session,
                        },
                        success: function(data) {
                            $('#details-row').nextAll().remove();
                            if (data.message === 'No Record Found') {
                                console.log(data.message);
                                $('#no-data').show().text(data.message);
                                reportTable.hide();
                            } else {
                                $('#present').text(data.present);
                                $('#absent').text(data.absent);
                                $.each(data.data, function(index, value) {
                                    var rowHtml = `
                                     <tr>
                                        <td>${value.rollno ?? 'N/A'}</td>
                                        <td>${value.name ?? 'N/A'}</td>
                                        <td colspan="2">${value.a_date ?? 'N/A'}</td>
                                        <td>${(value.status == 1 ? 'P' : 'A') ?? 'N/A'}</td>
                                     </tr>
                                    `;
                                    $('#details-row').after(rowHtml);
                                });

                                reportTable.show();
                                $('#no-data').hide();
                            }
                        },
                        complete: function() {
                            loader.hide();
                        },
                        error: function(xhr) {
                            console.error(xhr.responseText);
                        }
                    });
                } else {
                    reportTable.hide();
                }
            });

            $('#download-csv').on('click', function() {
                const classId = $('#class_id').val();
                const section = $('#section_id').val();
                const startDate = $('#start_date').val();
                const endDate = $('#end_date').val();
                const std = $('#std_id').val();
                const session = $('#current_session').val();

                // Redirect to the download route with parameters
                window.location.href = '{{ route('student.download.attendance.csv') }}?class=' + classId +
                    '&section=' + section + '&start_date=' + startDate + '&end_date=' + endDate +
                    '&std_id=' + std + '&current_session=' + session;
            });




        });
    </script>
@endsection
