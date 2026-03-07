@extends('admin.index')

@section('sub-content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card border-0 bg-white">
                    <div class="card-header flex-wrap bg-white d-flex align-items-center justify-content-between">
                        <h5 class="mb-0 mt-0">{{ __('New Admission Report By Date') }}</h5>
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
                                    <select name="session_id" id="session_id" class="form-control @error('session_id') is-invalid @enderror" required>
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
                                    <label for="admin_class_id" class="mt-2">Class <span class="text-danger">*</span>
                                    </label>
                                    <select name="class" id="admin_class_id" class="form-control @error('class') is-invalid @enderror" {{ count($classes) === 0 ? 'disabled' : 'required' }}>
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
                                    <label for="age_proof" class="mt-2">
                                        Admission By <span class="text-danger">*</span>
                                    </label>
                                    <select name="age_proof" id="age_proof"
                                        class="form-control @error('age_proof') is-invalid @enderror" required>
                                        <option value="1,2,3,4">All</option>
                                        <option value="1">Transfer Certificate (T.C.)</option>
                                        <option value="2">Birth Certificate</option>
                                        <option value="3">Affidavit</option>
                                        <option value="4">Aadhar Card</option>
                                    </select>
                                    @error('age_proof')
                                        <span class="invalid-feedback fw-bold" role="alert">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="form-group col-md-6">
                                    <label for="by_date" class="mt-2">Enter Date</label>
                                    <input type="date" name="by_date" id="by_date" class="form-control">
                                </div>
                            </div>

                            <div class="mt-3 d-flex align-items-center gap-2">
                                <button class="btn btn-primary" type="button" id="show-report">
                                    Show Report
                                </button>
                                <img src="{{ config('myconfig.myloader') }}" alt="Loading…" id="loader" class="loader" style="display:none; width:5%;">
                            </div>
                        </form>

                        {{-- ─── Results Container ─── --}}
                        <div id="super-div" class="mt-3" style="display:none;"></div>

                        {{-- ─── Export Button Container ─── --}}
                        <div id="export-div" class="mt-2" style="display:none;">
                            <button type="button" class="btn btn-info" id="export-button">
                                <span class="mdi mdi-export me-1"></span>Export
                            </button>
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
        $('#super-div').hide().html('');
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
            'age_proof'  : '#age_proof',
            'by_date'    : '#by_date'
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

    function validateForm(values) {
        var errors = {};

        if (!values.session) {
            errors['session_id'] = ['Please select a session.'];
        }

        if (!values.classId) {
            errors['class'] = ['Please select a class.'];
        }

        if (!values.ageProof) {
            errors['age_proof'] = ['Please select an admission type.'];
        }

        return errors;
    }

    function getFormValues() {
        return {
            session : $('#session_id').val(),
            classId : $('#admin_class_id').val(),
            ageProof: $('#age_proof').val(),
            byDate  : $('#by_date').val()
        };
    }

    /* ── Table builders ───────────────────────────────────────── */
    function buildSingleTable(data) {
        var html = '';
        $.each(data, function (i, value) {
            html +=
                '<div class="row mb-2 mt-4">' +
                    '<div class="col-12">' +
                        '<table class="table table-bordered mb-0">' +
                            '<tr>' +
                                '<td style="width:200px;" class="align-middle">' + value.class + '</td>' +
                                '<td class="p-0">' +
                                    '<table class="table table-bordered mb-0">' +
                                        '<tr>' +
                                            '<td style="width:200px;">Boys &rarr;</td>' +
                                            '<td style="width:100px;" class="text-end">' + value.boys + '</td>' +
                                        '</tr>' +
                                        '<tr>' +
                                            '<td>Girls &rarr;</td>' +
                                            '<td class="text-end">' + value.girls + '</td>' +
                                        '</tr>' +
                                    '</table>' +
                                '</td>' +
                            '</tr>' +
                        '</table>' +
                    '</div>' +
                '</div>';
        });
        return html;
    }

    function buildBeforeAfterTables(data) {
        var beforeRows = '';
        $.each(data, function (i, value) {
            beforeRows +=
                '<tr>' +
                    '<td style="width:200px;" class="align-middle">' + value.class + '</td>' +
                    '<td class="p-0">' +
                        '<table class="table table-bordered mb-0">' +
                            '<tr>' +
                                '<td style="width:200px;">Boys &rarr;</td>' +
                                '<td style="width:100px;" class="text-end">' + value.before.boys + '</td>' +
                            '</tr>' +
                            '<tr>' +
                                '<td>Girls &rarr;</td>' +
                                '<td class="text-end">' + value.before.girls + '</td>' +
                            '</tr>' +
                        '</table>' +
                    '</td>' +
                    '<td class="align-middle text-center" style="width:100px;">' + value.before.total + '</td>' +
                '</tr>';
        });

        var beforeSection =
            '<div class="row mt-4 mb-4">' +
                '<div class="col-12">' +
                    '<table class="table table-bordered">' +
                        '<thead>' +
                            '<tr><th colspan="3" class="bg-light">Before Section</th></tr>' +
                            '<tr><th>Class</th><th>Students</th><th>Total</th></tr>' +
                        '</thead>' +
                        '<tbody>' + beforeRows + '</tbody>' +
                    '</table>' +
                '</div>' +
            '</div>';

        var afterRows = '';
        $.each(data, function (i, value) {
            afterRows +=
                '<tr>' +
                    '<td style="width:200px;" class="align-middle">' + value.class + '</td>' +
                    '<td class="p-0">' +
                        '<table class="table table-bordered mb-0">' +
                            '<tr>' +
                                '<td style="width:200px;">Boys &rarr;</td>' +
                                '<td style="width:100px;" class="text-end">' + value.after.boys + '</td>' +
                            '</tr>' +
                            '<tr>' +
                                '<td>Girls &rarr;</td>' +
                                '<td class="text-end">' + value.after.girls + '</td>' +
                            '</tr>' +
                        '</table>' +
                    '</td>' +
                    '<td class="align-middle text-center" style="width:100px;">' + value.after.total + '</td>' +
                '</tr>';
        });

        var afterSection =
            '<div class="row mb-4">' +
                '<div class="col-12">' +
                    '<table class="table table-bordered">' +
                        '<thead>' +
                            '<tr><th colspan="3" class="bg-light">After Section</th></tr>' +
                            '<tr><th>Class</th><th>Students</th><th>Total</th></tr>' +
                        '</thead>' +
                        '<tbody>' + afterRows + '</tbody>' +
                    '</table>' +
                '</div>' +
            '</div>';

        return beforeSection + afterSection;
    }

    function buildReportHtml(data, byDate) {
        return byDate ? buildBeforeAfterTables(data) : buildSingleTable(data);
    }

    /* ── Event: Show Report ───────────────────────────────────── */
    $('#show-report').on('click', function () {
        clearErrors();
        resetReport();

        var values = getFormValues();
        var frontendErrors = validateForm(values);

        if (Object.keys(frontendErrors).length > 0) {
            showErrors(frontendErrors);
            return;
        }

        $('#super-div').show();
        $('#loader').show();

        $.ajax({
            url     : '{{ route('admin.reports.newAdmissionReportByDate') }}',
            type    : 'GET',
            dataType: 'json',
            data    : {
                session_id: values.session,
                class     : values.classId,
                age_proof : values.ageProof,
                by_date   : values.byDate
            },

            success: function (response) {
                if (response.status === 'error') {
                    resetReport();
                    if (response.message && typeof response.message === 'object') {
                        showErrors(response.message);
                    } else {
                        $('#super-div')
                            .show()
                            .html('<p class="text-center text-danger mt-3">' + (response.message || 'Something went wrong.') + '</p>');
                    }
                    return;
                }

                var data = (response && response.data) ? response.data : [];

                if (data.length > 0) {
                    $('#super-div').html(buildReportHtml(data, values.byDate));
                    $('#export-div').show();
                } else {
                    $('#super-div').html('<p class="text-center text-muted mt-3">No data found.</p>');
                    $('#export-div').hide();
                }
            },

            error: function (xhr) {
                console.error('New Admission Report error:', xhr);
                $('#super-div')
                    .show()
                    .html('<p class="text-center text-danger mt-3">Error loading data. Please try again.</p>');
                $('#export-div').hide();
            },

            complete: function () {
                $('#loader').hide();
            }
        });
    });

    /* ── Event: Filter change → clear errors + reset ─────────── */
    $('#session_id, #admin_class_id, #age_proof, #by_date').on('change', function () {
        // Only clear the error for the changed field
        var fieldMap = {
            'session_id'    : '#session_id',
            'admin_class_id': '#admin_class_id',
            'age_proof'     : '#age_proof',
            'by_date'       : '#by_date'
        };
        var input = $(this);
        input.removeClass('is-invalid');
        input.siblings('.server-error').remove();

        resetReport();
    });

    /* ── Event: Export button ─────────────────────────────────── */
    $(document).on('click', '#export-button', function () {
        var values = getFormValues();

        var exportUrl = '{{ route('admin.reports.exportReport') }}' +
            '?session_id=' + encodeURIComponent(values.session) +
            '&class='      + encodeURIComponent(values.classId) +
            '&age_proof='  + encodeURIComponent(values.ageProof) +
            '&by_date='    + encodeURIComponent(values.byDate);

        window.location.href = exportUrl;
    });

});
</script>
@endsection