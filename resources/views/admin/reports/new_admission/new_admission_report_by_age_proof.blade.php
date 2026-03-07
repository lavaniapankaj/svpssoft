@extends('admin.index')

@section('sub-content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card border-0 bg-white">
                    <div class="card-header flex-wrap bg-white d-flex align-items-center justify-content-between">
                        <h5 class="mb-0 mt-0">{{ __('New Admission Report By Age Proofs') }}</h5>
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
                                    <label for="admin_class_id" class="mt-2"> Class <span class="text-danger">*</span>
                                    </label>
                                    <select name="class" id="admin_class_id" class="form-control @error('class') is-invalid @enderror"
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

                            <div class="mt-3 d-flex align-items-center gap-2">
                                <button class="btn btn-primary" type="button" id="show-report">
                                    Report (New &amp; Old)
                                </button>
                                <button class="btn btn-primary" type="button" id="show-report-new" data-value="NewAdmissionOnly">
                                    Report (New Admission Only)
                                </button>
                                <img src="{{ config('myconfig.myloader') }}" alt="Loading…" id="loader" class="loader" style="display:none; width:5%;">
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
                                            <th>By TC</th>
                                            <th>By Birth Cert.</th>
                                            <th>By Affidavit</th>
                                            <th>By Aadhar Card</th>
                                            <th>Without Proof</th>
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

    // Tracks the last used newAdmission value for the export button
    var lastNewAdmission = '';

    /* ── Helpers ──────────────────────────────────────────────── */
    function resetReport() {
        $('#super-div').hide();
        $('#report-body').html('');
        $('#export-div').hide();
        lastNewAdmission = '';
    }

    function clearErrors() {
        $('#report-form .is-invalid').removeClass('is-invalid');
        $('#report-form .server-error').remove();
    }

    function showErrors(errors) {
        var fieldMap = {
            'session_id' : '#session_id',
            'class'      : '#admin_class_id'
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

    function validateForm() {
        var errors = {};
        if (!$('#session_id').val()) {
            errors['session_id'] = ['Please select a session.'];
        }
        if (!$('#admin_class_id').val()) {
            errors['class'] = ['Please select a class.'];
        }
        return errors;
    }

    function getFormValues() {
        return {
            session : $('#session_id').val(),
            classId : $('#admin_class_id').val()
        };
    }

    /* ── Table builder ────────────────────────────────────────── */
    function buildTableHtml(data) {
        var ageProofs = ['By TC', 'By Birth Cert.', 'By Affidavit', 'By Aadhar Card', 'Without Proof'];
        var html = '';

        if (!data || !data.length) {
            return '<tr><td colspan="7" class="text-center text-muted">No data found.</td></tr>';
        }

        $.each(data, function (index, classData) {
            var boysData  = { 'By TC': 0, 'By Birth Cert.': 0, 'By Affidavit': 0, 'By Aadhar Card': 0, 'Without Proof': 0 };
            var girlsData = { 'By TC': 0, 'By Birth Cert.': 0, 'By Affidavit': 0, 'By Aadhar Card': 0, 'Without Proof': 0 };

            $.each(classData.ageProofs, function (i, ageProof) {
                if (boysData.hasOwnProperty(ageProof.age_proof_name)) {
                    boysData[ageProof.age_proof_name]  = ageProof.boys;
                    girlsData[ageProof.age_proof_name] = ageProof.girls;
                }
            });

            // Boys row
            html += '<tr>' +
                '<td rowspan="2" class="align-middle fw-semibold">' + classData.class + '</td>' +
                '<td>Boys &rarr;</td>';
            $.each(ageProofs, function (i, proof) {
                html += '<td class="text-center">' + (boysData[proof] || 0) + '</td>';
            });
            html += '</tr>';

            // Girls row
            html += '<tr><td>Girls &rarr;</td>';
            $.each(ageProofs, function (i, proof) {
                html += '<td class="text-center">' + (girlsData[proof] || 0) + '</td>';
            });
            html += '</tr>';
        });

        return html;
    }

    /* ── Core: fetch report ───────────────────────────────────── */
    function getReport(newAdmission) {
        newAdmission = newAdmission || '';

        clearErrors();
        var errors = validateForm();

        if (Object.keys(errors).length > 0) {
            showErrors(errors);
            return;
        }

        var values = getFormValues();
        lastNewAdmission = newAdmission;

        resetReport();
        $('#super-div').show();
        $('#loader').show();

        $.ajax({
            url     : '{{ route('admin.reports.newAdmissionReportByAgeProof') }}',
            type    : 'GET',
            dataType: 'json',
            data    : {
                session_id    : values.session,
                class         : values.classId,
                new_admission : newAdmission
            },

            success: function (response) {
                if (response.status === 'error') {
                    if (response.message && typeof response.message === 'object') {
                        showErrors(response.message);
                    } else {
                        $('#report-body').html(
                            '<tr><td colspan="7" class="text-center text-danger">' +
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
                console.error('Age Proof Report error:', xhr);
                $('#report-body').html(
                    '<tr><td colspan="7" class="text-center text-danger">Error loading data. Please try again.</td></tr>'
                );
            },

            complete: function () {
                $('#loader').hide();
            }
        });
    }

    /* ── Events: report buttons ───────────────────────────────── */
    $('#show-report').on('click', function () {
        getReport('');
    });

    $('#show-report-new').on('click', function () {
        getReport($(this).data('value'));
    });

    /* ── Event: filter change → reset ────────────────────────── */
    $('#session_id, #admin_class_id').on('change', function () {
        $(this).removeClass('is-invalid');
        $(this).siblings('.server-error').remove();
        resetReport();
    });

    /* ── Event: export button ─────────────────────────────────── */
    $(document).on('click', '#export-button', function () {
        var values = getFormValues();

        var exportUrl = '{{ route('admin.reports.exportReportByAgeProof') }}' +
            '?session_id='    + encodeURIComponent(values.session) +
            '&class='         + encodeURIComponent(values.classId) +
            '&new_admission=' + encodeURIComponent(lastNewAdmission);

        window.location.href = exportUrl;
    });

});
</script>
@endsection