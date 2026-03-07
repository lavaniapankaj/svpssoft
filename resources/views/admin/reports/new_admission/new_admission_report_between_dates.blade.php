@extends('admin.index')

@section('sub-content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card border-0 bg-white">
                    <div class="card-header flex-wrap bg-white d-flex align-items-center justify-content-between">
                        <h5 class="mb-0 mt-0">{{ __('New Admission Report (By Date)') }}</h5>
                        <a href="{{ route('admin.reports.newAdmissionReport') }}" class="btn bg-light btn-sm">
                            <span class="mdi mdi-chevron-left me-2"></span>Back
                        </a>
                    </div>

                    <div class="card-body">
                        <form id="report-form" novalidate>
                            <div class="row mt-2">
                                <div class="form-group col-md-6">
                                    <label for="session_id" class="mt-2">
                                        Session <span class="text-danger">*</span>
                                    </label>
                                    <select name="session_id" id="session_id"
                                        class="form-control @error('session_id') is-invalid @enderror" required>
                                        <option value="">Select session</option>
                                        @forelse ($sessions as $key => $session)
                                            <option value="{{ $key }}"
                                                {{ old('session_id') == $key ? 'selected' : '' }}>
                                                {{ $session }}
                                            </option>
                                        @empty
                                            <option value="" disabled>No Session Found</option>
                                        @endforelse
                                    </select>
                                    @error('session_id')
                                        <span class="invalid-feedback fw-bold" role="alert">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="form-group col-md-6">
                                    <label for="admin_class_id" class="mt-2">
                                        Class <span class="text-danger">*</span>
                                    </label>
                                    <select name="class" id="admin_class_id"
                                        class="form-control @error('class') is-invalid @enderror"
                                        {{ count($classes) === 0 ? 'disabled' : 'required' }}>
                                        @if (count($classes) > 0)
                                            <option value="">— Select Class —</option>
                                            <option value="all" {{ request('class') === 'all' ? 'selected' : '' }}>
                                                All Classes
                                            </option>
                                            @foreach ($classes as $key => $class)
                                                <option value="{{ $key }}"
                                                    {{ request('class') == $key ? 'selected' : '' }}>
                                                    {{ $class }}
                                                </option>
                                            @endforeach
                                        @else
                                            <option value="" disabled selected>No Classes Found</option>
                                        @endif
                                    </select>
                                    @error('class')
                                        <span class="invalid-feedback fw-bold" role="alert">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="row mt-2">
                                <div class="form-group col-md-6">
                                    <label for="start-date" class="mt-2">Enter Start Date</label>
                                    <input type="date" name="startDate" id="start-date" class="form-control">
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="end-date" class="mt-2">Enter End Date</label>
                                    <input type="date" name="endDate" id="end-date" class="form-control">
                                </div>
                            </div>

                            <div class="mt-3 d-flex align-items-center gap-2">
                                <button class="btn btn-primary" type="button" id="show-report">
                                    Show Report
                                </button>
                                <img src="{{ config('myconfig.myloader') }}"
                                    alt="Loading…"
                                    id="loader"
                                    class="loader"
                                    style="display:none; width:5%;">
                            </div>
                        </form>

                        {{-- ─── Results Container ─── --}}
                        <div id="super-div" class="mt-3" style="display:none;">
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Class</th>
                                            <th>Gender</th>
                                            <th>Total</th>
                                        </tr>
                                    </thead>
                                    <tbody id="report-body">
                                        {{-- Populated via AJAX --}}
                                    </tbody>
                                </table>
                            </div>

                            <div id="export-div" class="mt-2" style="display:none;">
                                <button type="button" class="btn btn-info" id="export-button">
                                    <span class="mdi mdi-export me-1"></span>Export
                                </button>
                            </div>
                        </div>

                    </div>{{-- /.card-body --}}
                </div>{{-- /.card --}}
            </div>
        </div>
    </div>
@endsection

@section('admin-scripts')
<script>
$(function () {

    /* ── Helpers ──────────────────────────────────────────────── */
    function resetReport() {
        $('#super-div').hide();
        $('#report-body').html('');
        $('#export-div').hide();
    }

    function clearErrors() {
        $('#report-form .is-invalid').removeClass('is-invalid');
        $('#report-form .server-error').remove();
    }

    function showErrors(errors) {
        var fieldMap = {
            'session_id' : '#session_id',
            'class'      : '#admin_class_id',
            'startDate'  : '#start-date',
            'endDate'    : '#end-date'
        };

        $.each(errors, function (field, messages) {
            var selector = fieldMap[field] || null;
            if (!selector) return;

            var input = $(selector);
            input.addClass('is-invalid');
            input.after(
                '<span class="invalid-feedback server-error fw-bold" role="alert">' +
                    (Array.isArray(messages) ? messages[0] : messages) +
                '</span>'
            );
        });

        // Scroll to first error
        var firstInvalid = $('#report-form .is-invalid').first();
        if (firstInvalid.length) {
            $('html, body').animate({ scrollTop: firstInvalid.offset().top - 80 }, 400);
        }
    }

    function getFormValues() {
        return {
            session   : $('#session_id').val(),
            classId   : $('#admin_class_id').val(),
            startDate : $('#start-date').val(),
            endDate   : $('#end-date').val()
        };
    }

    function validateForm(values) {
        var errors = {};

        if (!values.session) {
            errors['session_id'] = ['Please select a session.'];
        }

        if (!values.classId) {
            errors['class'] = ['Please select a class.'];
        }

        // Date cross-validation (both provided and start > end)
        if (values.startDate && values.endDate && values.startDate > values.endDate) {
            errors['startDate'] = ['Start date must not be greater than end date.'];
        }

        // If only one date is provided, require the other
        if (values.startDate && !values.endDate) {
            errors['endDate'] = ['Please select an end date.'];
        }

        if (values.endDate && !values.startDate) {
            errors['startDate'] = ['Please select a start date.'];
        }

        return errors;
    }

    /* ── Table builder ────────────────────────────────────────── */
    function buildTableHtml(data) {
        if (!data || !data.length) {
            return '<tr><td colspan="3" class="text-center text-muted">No data found.</td></tr>';
        }

        var html = '';
        $.each(data, function (index, value) {
            html +=
                '<tr>' +
                    '<td rowspan="2" class="align-middle fw-semibold">' + value.class + '</td>' +
                    '<td>Boys &rarr;</td>' +
                    '<td class="text-center">' + (value.boys || 0) + '</td>' +
                '</tr>' +
                '<tr>' +
                    '<td>Girls &rarr;</td>' +
                    '<td class="text-center">' + (value.girls || 0) + '</td>' +
                '</tr>';
        });

        return html;
    }

    /* ── Core: fetch report ───────────────────────────────────── */
    function getReport() {
        clearErrors();

        var values = getFormValues();
        var errors = validateForm(values);

        if (Object.keys(errors).length > 0) {
            showErrors(errors);
            return;
        }

        resetReport();
        $('#super-div').show();
        $('#loader').show();

        $.ajax({
            url     : '{{ route('admin.reports.newAdmissionReportByBetweenDates') }}',
            type    : 'GET',
            dataType: 'json',
            data    : {
                session_id : values.session,
                class      : values.classId,
                startDate  : values.startDate,
                endDate    : values.endDate
            },

            success: function (response) {
                if (response.status === 'error') {
                    if (response.message && typeof response.message === 'object') {
                        showErrors(response.message);
                    } else {
                        $('#report-body').html(
                            '<tr><td colspan="3" class="text-center text-danger">' +
                                (response.message || 'Something went wrong.') +
                            '</td></tr>'
                        );
                    }
                    return;
                }

                var data = (response && response.data) ? response.data : [];
                $('#report-body').html(buildTableHtml(data));

                if (data.length > 0) {
                    $('#export-div').show();
                }
            },

            error: function (xhr) {
                console.error('Between Dates Report error:', xhr);
                $('#report-body').html(
                    '<tr><td colspan="3" class="text-center text-danger">Error loading data. Please try again.</td></tr>'
                );
            },

            complete: function () {
                $('#loader').hide();
            }
        });
    }

    /* ── Event: Show Report button ────────────────────────────── */
    $('#show-report').on('click', function () {
        getReport();
    });

    /* ── Event: filter/date change → clear field error + reset ── */
    $('#session_id, #admin_class_id, #start-date, #end-date').on('change', function () {
        $(this).removeClass('is-invalid');
        $(this).siblings('.server-error').remove();
        resetReport();
    });

    /* ── Event: export button ─────────────────────────────────── */
    $(document).on('click', '#export-button', function () {
        var values = getFormValues();

        var exportUrl = '{{ route('admin.reports.exportReportByBetweenDates') }}' +
            '?session_id=' + encodeURIComponent(values.session) +
            '&class='      + encodeURIComponent(values.classId) +
            '&startDate='  + encodeURIComponent(values.startDate) +
            '&endDate='    + encodeURIComponent(values.endDate);

        window.location.href = exportUrl;
    });

});
</script>
@endsection