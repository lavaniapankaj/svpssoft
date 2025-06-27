@extends('admin.index')

@section('sub-content')
    <div class="container">

        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        {{ 'Edit Attendance Schedule' }}
                    </div>

                    <div class="card-body">
                        <form action="{{ route('admin.attendance_schedule.updateSpecifiDate') }}" method="POST"
                            id="basic-form">
                            @csrf
                            <input type="hidden" name="current_session" value='' id="current_session">
                            <div class="row">
                                <div class="form-group col-md-16">
                                    <label for="a_date" class="mt-2">Select Date <span
                                            class="text-danger">*</span></label>
                                    <input type="date" name="a_date" id="a_date"
                                        class="form-control @error('a_date') is-invalid @enderror"
                                        value="{{ old('a_date') }}" required>
                                    @error('a_date')
                                        <span class="invalid-feedback form-invalid fw-bold" role="alert">
                                            {{ $message }}
                                        </span>
                                    @enderror
                                </div>
                                <div class="form-group col-md-16">
                                    <label for="a_reason" class="mt-2">Enter Reason<span
                                            class="text-danger">*</span></label>
                                    <input type="text" name="reason" id="a_reason"
                                        class="form-control @error('reason') is-invalid @enderror" value="{{ old('reason') }}">
                                    @error('reason')
                                        <span class="invalid-feedback form-invalid fw-bold" role="alert">
                                            {{ $message }}
                                        </span>
                                    @enderror
                                </div>

                            </div>
                            <div class="mt-3">
                                <input class="btn btn-primary" type="submit" value="Update">
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('admin-scripts')
    <script>
        $(document).ready(function() {
            var dateSelect = $('#a_date');
            var reason = $('#a_reason');
            var sessionSelect = $('#current_session');

            function fetchReason(aDate, sessionId) {
                if (aDate && sessionId) {
                    $.ajax({
                        url: '{{ route('admin.attendance_schedule.editSpecificDate') }}',
                        type: 'GET',
                        data: {
                            a_date: aDate,
                            session_id: sessionId,
                        },
                        success: function(data) {
                            reason.val(data.reason || '');
                        },
                        error: function(xhr) {
                            console.error('Error fetching attendance detail:', xhr);
                        }
                    });
                } else {
                    reason.val('');
                }
            }

            dateSelect.on('change', function() {
                var date = $(this).val();
                var sessionId = sessionSelect.val();
                fetchReason(date, sessionId);
            });

        });
    </script>
@endsection
