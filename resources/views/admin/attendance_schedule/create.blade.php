@extends('admin.index')

@section('sub-content')
    <div class="container">

        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        {{ isset($attendanceSchedule) && isset($attendanceSchedule->id) ? 'Edit Attendance Schedule' : 'Add New Attendance Schedule' }}
                        <a href="{{ route('admin.attendance_schedule.index') }}" class="btn btn-warning btn-sm"
                            style="float: right;">Back</a>

                    </div>


                    <div class="card-body">
                        <form id="date-form">
                            @csrf
                            <div class="row">
                                <div class="form-group col-md-6">
                                    <label for="start_date" class="mt-2">Start Date <span
                                            class="text-danger">*</span></label>
                                    <input type="date" name="start_date" id="start_date"
                                        class="form-control @error('start_date') is-invalid @enderror"
                                        value="{{ old('start_date', $startDate) }}" required>
                                    @error('start_date')
                                        <span class="invalid-feedback form-invalid fw-bold" role="alert">
                                            {{ $message }}
                                        </span>
                                    @enderror
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="end_date" class="mt-2">End Date <span class="text-danger">*</span></label>
                                    <input type="date" name="end_date" id="end_date"
                                        class="form-control @error('end_date') is-invalid @enderror"
                                        value="{{ old('end_date', $endDate) }}" required>
                                    @error('end_date')
                                        <span class="invalid-feedback form-invalid fw-bold" role="alert">
                                            {{ $message }}
                                        </span>
                                    @enderror
                                    <span id="date-error" class="text-danger" style="display:none;">End date cannot be less
                                        than start date.</span>
                                </div>
                            </div>

                            <div class="mt-3">
                                <button type="button" id="generate-button" class="btn btn-primary">Generate
                                    Schedule</button>
                            </div>
                        </form>
                        <img src="{{ config('myconfig.myloader') }}" alt="Loading..." class="loader" id="loader"
                            style="display:none; width:10%;">

                        <div id="schedule-container" class="mt-4">
                            <form action="{{ route('admin.attendance_schedule.store') }}" method="POST" id="schedule-form">
                                @csrf
                                <input type="hidden" name="id"
                                    value="{{ isset($attendanceSchedule) ? $attendanceSchedule->id : '' }}">
                                <table class="table">
                                    <input type="hidden" name="current_session" value='' id="current_session">
                                    <input type="hidden" name="session_id" value="{{ isset($current_session) ? $current_session : '' }}">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Date</th>
                                                <th>Day</th>
                                                <th>Status (Working Day)</th>
                                                <th>Leave Reason</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($dates as $index => $date)
                                                <tr>
                                                    <td>
                                                        {{ $date['a_date'] }}
                                                        <input type="hidden" name="dates[{{ $index }}][a_date]"
                                                            value="{{ $date['a_date'] }}">
                                                    </td>
                                                    <td>{{ $date['day'] }}</td>
                                                    <td>
                                                        @php
                                                            $isChecked =
                                                                $date['status'] == 1 && empty($date['reason'])
                                                                    ? 'checked'
                                                                    : '';
                                                        @endphp
                                                        <input type="checkbox" name="dates[{{ $index }}][status]"
                                                            value="1" {{ $isChecked }} class="status-checkbox"
                                                            data-index="{{ $index }}">
                                                    </td>
                                                    <td>
                                                        <input type="text" name="dates[{{ $index }}][reason]"
                                                            class="form-control @error('dates.' . $index . '.reason') is-invalid @enderror"
                                                            value="{{ $date['reason'] ?? '' }}"
                                                            {{ $isChecked ? 'disabled' : '' }}
                                                            data-index="{{ $index }}">
                                                        @error('dates.' . $index . '.reason')
                                                            <span class="invalid-feedback form-invalid fw-bold" role="alert">
                                                                {{ $message }}
                                                            </span>
                                                        @enderror
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                    <div class="mt-3">
                                        <button type="submit" class="btn btn-primary">Save Schedule</button>
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
            $('#generate-button').on('click', function() {
                const startDate = $('#start_date').val();
                const endDate = $('#end_date').val();
                var errorElement = $('#date-error');
                if (endDate < startDate) {
                    errorElement.show();
                    return false;
                } else {
                    errorElement.hide();
                }
                loader.show();
                $.ajax({
                    url: '{{ route('admin.attendance_schedule.generate') }}',
                    type: 'POST',
                    data: {
                        start_date: startDate,
                        end_date: endDate,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(dates) {
                        let scheduleHtml = '';
                        $.each(dates, function(index, date) {
                            const isChecked = date.status && !date.reason ? 'checked' :
                                '';
                            const isDisabled = date.status && !date.reason ?
                                'disabled' : '';

                            scheduleHtml += `<tr>
                        <td>
                            ${date.a_date}
                            <input type="hidden" name="dates[${index}][a_date]" value="${date.a_date}">
                        </td>
                        <td>${date.day}</td>
                        <td>
                            <input type="checkbox" name="dates[${index}][status]" value="1" ${isChecked} class="status-checkbox" data-index="${index}">
                        </td>
                        <td>
                            <input type="text" name="dates[${index}][reason]" class="form-control" value="${date.reason || ''}" ${isDisabled} data-index="${index}">
                        </td>
                        </tr>`;
                        });

                        $('#schedule-container table tbody').html(scheduleHtml);
                    },
                    complete: function() {

                        loader.hide();
                    },
                    error: function(xhr) {
                        console.error(xhr.responseText);
                    }
                });
            });

            $(document).on('change', '.status-checkbox', function() {
                const index = $(this).data('index');
                const reasonInput = $(`input[name="dates[${index}][reason]"]`);

                if ($(this).is(':checked')) {
                    reasonInput.prop('disabled', true).val('');
                } else {
                    reasonInput.prop('disabled', false);
                }
            });
            $('#schedule-form').on('submit', function(event) {
                var confirmed = confirm('Are you sure you want to submit this form?');

                if (!confirmed) {
                    event.preventDefault();
                    window.history.back();
                }
            });
        });
    </script>
@endsection
