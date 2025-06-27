@extends('admin.index')

@section('sub-content')
    <div class="container">

        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">{{ __('New Admission Report (By Date)') }}
                        <a href="{{ route('admin.reports.newAdmissionReport') }}" class="btn btn-warning btn-sm"
                            style="float: right;">Back</a>
                    </div>

                    <div class="card-body">
                        <form action="" method="get" id="report-form">
                            <div class="row mt-2">
                                <div class="form-group col-md-6">
                                    <label for="session_id" class="mt-2">Session <span
                                            class="text-danger">*</span></label>
                                    <select name="session_id" id="session_id"
                                        class="form-control @error('session_id') is-invalid @enderror" required>
                                        <option value="">Select session</option>
                                        @if (count($sessions) > 0)
                                            @foreach ($sessions as $key => $session)
                                                <option value="{{ $key }}"
                                                    {{ old('session_id') == $key ? 'selected' : '' }}>{{ $session }}
                                                </option>
                                            @endforeach
                                        @else
                                            <option value="">No Session Found</option>
                                        @endif
                                    </select>
                                    <span class="invalid-feedback form-invalid fw-bold session-error" role="alert"></span>
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="class_id" class="mt-2">Class <span class="text-danger">*</span></label>
                                    <input type="hidden" name="current_session" value='' id="current_session">
                                    <input type="hidden" id="initialClassId" name="initialClassId"
                                        value="{{ old('initialClassId', request()->get('class_id') !== null ? request()->get('class_id') : '') }}">
                                    <select name="class_id" id="class_id"
                                        class="form-control mx-1 @error('class_id') is-invalid @enderror">
                                        <option value="">All Class</option>
                                    </select>
                                    @error('class_id')
                                        <span class="invalid-feedback form-invalid fw-bold" role="alert">
                                            {{ $message }}
                                        </span>
                                    @enderror
                                    <img src="{{ config('myconfig.myloader') }}" alt="Loading..." class="loader"
                                        id="loader" style="display:none; width:10%;">
                                </div>
                            </div>

                            <div class="row">
                                <div class="form-group col-md-6">
                                    <label for="start-date" class="mt-2">Enter Start Date</label>
                                    <input type="date" name="startDate" id="start-date" class="form-control"
                                        value="">
                                    <span class="invalid-feedback form-invalid fw-bold start-date-error"
                                        role="alert"></span>
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="end-date" class="mt-2">Enter End Date</label>
                                    <input type="date" name="endDate" id="end-date" class="form-control" value="">
                                    <span class="invalid-feedback form-invalid fw-bold end-date-error"
                                        role="alert"></span>
                                </div>
                            </div>

                            <div class="mt-3">
                                <button class="btn btn-primary" type="button" id="show-report">Show Report</button>

                            </div>
                        </form>

                        <div class="super-div">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Class</th>
                                        <th>Gender</th>
                                        <th>Total</th>
                                    </tr>
                                </thead>
                                <tbody id="report-body">
                                </tbody>
                            </table>
                            <div class="export-div">
                                <button type="button" class="btn btn-info" id="export-button">Export</button>
                            </div>
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
           getClassDropDownWithAll();
            $('.super-div').hide();

            function getReport(sd = '', ed = '') {
                let sessionId = $('#session_id').val();
                let classId = $('#class_id').val();
                $.ajax({
                    url: '{{ route('admin.reports.newAdmissionReportByBetweenDates') }}',
                    type: 'GET',
                    dataType: 'JSON',
                    data: {
                        session_id: sessionId,
                        class: classId,
                        startDate: sd,
                        endDate: ed,
                    },
                    success: function(response) {
                        let tableHtml = '';
                        $.each(response.data, function(index, value) {

                            tableHtml += `
                                    <tr>
                                        <td rowspan="2">${value.class}</td>
                                                <td>Boys --> </td>
                                                <td>${value.boys}</td>
                                    </tr>`;
                            tableHtml += ` <tr>
                                                    <td>Girls --> </td>
                                                    <td>${value.girls}</td>
                                        </tr>`;
                        });
                        $('#report-body').html(tableHtml);
                    },
                    complete: function() {

                        loader.hide();
                    },
                    error: function(xhr) {
                        console.log(xhr);

                    },
                });

            }

            $('#show-report').click(function() {
                if ($('#report-form').valid() && $('.start-date-error').css('display') !== 'inline' && $(
                        '.end-date-error').css('display') !== 'inline') {

                    $('.super-div').show();
                    let stDate = $('#start-date').val();
                    let enDate = $('#end-date').val();
                    getReport(stDate, enDate);
                    getExcelReport(stDate, enDate);
                }


            });
            $('#start-date').on('change', function() {
                const startDate = $(this).val();
                const endDate = $('#end-date').val();
                if (startDate && endDate && startDate > endDate) {
                    $('.start-date-error').show().html('Start date must not be greater than end date');
                } else if (!endDate) {
                    $('.end-date-error').show().html('Please select the end date also');

                } else {
                    $('.start-date-error').hide().html('');
                }
            });

            $('#end-date').on('change', function() {
                const startDate = $('#start-date').val();
                const endDate = $(this).val();

                if (endDate && startDate && endDate < startDate) {
                    $('.end-date-error').show().html('End date must not be less than start date');
                } else if (!startDate) {
                    $('.end-date-error').show().html('Please select the satrt date also');

                } else {
                    $('.end-date-error').hide().html('');
                }
            });

            // Add event listeners for form field changes
            $('#class_id, #session_id, #start-date, #end-date').change(function() {
                $('.super-div').hide();
            });

            function getExcelReport(st = '', ed = '') {
                $('#export-button').on('click', function() {
                    const session = $('#session_id').val();
                    const classId = $('#class_id').val();
                    let startDate = st;
                    let endDate = ed;

                    const exportUrl =
                        "{{ route('admin.reports.exportReportByBetweenDates') }}?session_id=" +
                        session +
                        "&class=" + classId + "&startDate=" + startDate + "&endDate=" + endDate;
                    window.location.href = exportUrl;
                });
            }


        });
    </script>
@endsection
