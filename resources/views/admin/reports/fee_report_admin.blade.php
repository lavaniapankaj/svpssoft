@extends('admin.index')

@section('sub-content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card border-0 bg-white">

                <div class="card-header flex-wrap bg-white d-flex align-items-center justify-content-between">
                    <h5 class="mb-0 mt-0">{{ __('Fee Report') }}</h5>
                    <a href="{{ route('admin.reports') }}" class="btn bg-light btn-sm">
                        <span class="mdi mdi-chevron-left me-2"></span>Back
                    </a>
                </div>

                <div class="card-body">

                    {{-- ── Filter Form ─────────────────────────────────────── --}}
                    <form id="fee-report-form" novalidate>
                        <div class="row mt-2">

                            {{-- Session --}}
                            <div class="form-group col-md-6">
                                <label for="session_id" class="mt-2">
                                    Session <span class="text-danger">*</span>
                                </label>
                                <select name="session_id" id="session_id" class="form-control" required>
                                    <option value="">Select Session</option>
                                    @if (count($sessions) > 0)
                                        @foreach ($sessions as $key => $session)
                                            <option value="{{ $key }}" {{ old('session_id') == $key ? 'selected' : '' }}>
                                                {{ $session }}
                                            </option>
                                        @endforeach
                                    @else
                                        <option value="" disabled>No Session Found</option>
                                    @endif
                                </select>
                                <span class="invalid-feedback fw-bold" id="session-error" role="alert"></span>
                            </div>

                            {{-- Fee Type --}}
                            <div class="form-group col-md-6">
                                <label for="fee_type" class="mt-2">
                                    Fee Type <span class="text-danger">*</span>
                                </label>
                                <select name="fee_type" id="fee_type" class="form-control" required>
                                    <option value="">Select Fee Type</option>
                                    <option value="1">Academic Fee</option>
                                    <option value="2">Transport Fee</option>
                                </select>
                                <span class="invalid-feedback fw-bold" id="fee-type-error" role="alert"></span>
                            </div>

                        </div>

                        <div class="row">

                            {{-- Report Type --}}
                            <div class="form-group col-md-4">
                                <label for="report_type" class="mt-2">
                                    Report Type <span class="text-danger">*</span>
                                </label>
                                <select name="report_type" id="report_type" class="form-control" required>
                                    <option value="">Select Report Type</option>
                                    <option value="1">Summary Report</option>
                                    <option value="2">Detailed Report</option>
                                </select>
                                <span class="invalid-feedback fw-bold" id="report-type-error" role="alert"></span>
                            </div>

                            {{-- Start Date --}}
                            <div class="form-group col-md-4">
                                <label for="start_date" class="mt-2">Start Date</label>
                                <input type="date" id="start_date" class="form-control">
                                <span class="invalid-feedback fw-bold" id="start-date-error" role="alert"></span>
                            </div>

                            {{-- End Date --}}
                            <div class="form-group col-md-4">
                                <label for="end_date" class="mt-2">End Date</label>
                                <input type="date" id="end_date" class="form-control">
                                <span class="invalid-feedback fw-bold" id="end-date-error" role="alert"></span>
                            </div>

                        </div>

                        <div class="mt-3 d-flex align-items-center gap-2">
                            <button type="button" id="btn-show-report" class="btn btn-primary">
                                Show Fee Report
                            </button>
                            <img src="{{ config('myconfig.myloader') }}" alt="Loading…" id="loader"
                                style="display:none; width:40px;">
                        </div>

                        {{-- Summary result --}}
                        <div id="summary-report" class="mt-2 fst-italic fw-bold text-decoration-underline"
                            style="display:none;"></div>
                    </form>

                    {{-- ── Results ─────────────────────────────────────────── --}}
                    <div id="report-container" class="mt-4" style="display:none;">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>S.No.</th>
                                        <th>Pay Date</th>
                                        <th>Ref. Slip No.</th>
                                        <th>C. Slip No.</th>
                                        <th>SRNO</th>
                                        <th>Class</th>
                                        <th>Section</th>
                                        <th>Name</th>
                                        <th>Father's Name</th>
                                        <th>Amount (Rs.)</th>
                                    </tr>
                                </thead>
                                <tbody id="report-body"></tbody>
                            </table>
                        </div>
                        <div id="admin-pagination" class="mt-2"></div>
                        <div class="mt-3">
                            <button type="button" id="btn-export" class="btn btn-info">
                                Export Excel
                            </button>
                        </div>
                    </div>

                </div>{{-- /card-body --}}
            </div>
        </div>
    </div>
</div>
@endsection

@section('admin-scripts')
<script>
$(document).ready(function () {

    // ── Selectors ──────────────────────────────────────────────────────────────
    const loader          = $('#loader');
    const summaryReport   = $('#summary-report');
    const reportContainer = $('#report-container');
    const reportBody      = $('#report-body');
    const pagination      = $('#admin-pagination');

    // ── Helpers ────────────────────────────────────────────────────────────────
    function showLoader() { loader.show(); }
    function hideLoader() { loader.hide(); }

    function clearErrors() {
        ['#session-error', '#fee-type-error', '#report-type-error', '#start-date-error', '#end-date-error']
            .forEach(id => $(id).text('').hide());
    }

    function showError(id, message) {
        $(id).text(message).show();
    }

    function getFilters() {
        return {
            session:    $('#session_id').val(),
            feeType:    $('#fee_type').val(),
            reportType: $('#report_type').val(),
            startDate:  $('#start_date').val(),
            endDate:    $('#end_date').val(),
        };
    }

    function validateFilters(filters) {
        let valid = true;
        clearErrors();

        if (!filters.session) {
            showError('#session-error', 'Please select a session.');
            valid = false;
        }
        if (!filters.feeType) {
            showError('#fee-type-error', 'Please select a fee type.');
            valid = false;
        }
        if (!filters.reportType) {
            showError('#report-type-error', 'Please select a report type.');
            valid = false;
        }
        if (filters.startDate && filters.endDate && filters.startDate > filters.endDate) {
            showError('#start-date-error', 'Start date must be less than or equal to end date.');
            valid = false;
        }

        return valid;
    }

    // ── Build Table HTML ───────────────────────────────────────────────────────
    function buildTableHtml(data, paginationData) {
        const startIndex = paginationData?.current_page
            ? (paginationData.current_page - 1) * paginationData.per_page
            : 0;

        let juniorStudents = [];
        let seniorStudents = [];
        let juniorTotal    = 0;
        let seniorTotal    = 0;

        // Split into junior/senior in one pass
        Object.keys(data).forEach(key => {
            if (key === 'grandTotal') return;
            const row = data[key];
            if (row.school == 1) {
                juniorStudents.push(row);
                juniorTotal += parseFloat(row.feeDetails?.amount || 0);
            } else {
                seniorStudents.push(row);
                seniorTotal += parseFloat(row.feeDetails?.amount || 0);
            }
        });

        let counter = startIndex + 1;
        let html    = '';

        const buildRow = (studentData) => {
            const row = `
                <tr>
                    <td>${counter}</td>
                    <td>${studentData.feeDetails?.pay_date    || '-'}</td>
                    <td>${studentData.feeDetails?.ref_slip_no || '-'}</td>
                    <td>${studentData.feeDetails?.recp_no     || '-'}</td>
                    <td>${studentData.feeDetails?.srno        || '-'}</td>
                    <td>${studentData.class_name              || '-'}</td>
                    <td>${studentData.section_name            || '-'}</td>
                    <td>${studentData.name                    || '-'}</td>
                    <td>${studentData.f_name                  || '-'}</td>
                    <td>${studentData.feeDetails?.amount      || 0}</td>
                </tr>`;
            counter++;
            return row;
        };

        // Junior Section
        if (juniorStudents.length > 0) {
            html += `<tr class="fw-bold">
                        <td colspan="10" class="text-start text-danger">Junior Section</td>
                     </tr>`;
            juniorStudents.forEach(row => { html += buildRow(row); });
            html += `<tr class="fw-bold">
                        <td colspan="9" class="text-end">Junior Section Total:</td>
                        <td>${juniorTotal.toFixed(2)}</td>
                     </tr>`;
        }

        // Senior Section
        if (seniorStudents.length > 0) {
            html += `<tr class="fw-bold">
                        <td colspan="10" class="text-start text-danger">Senior Section</td>
                     </tr>`;
            seniorStudents.forEach(row => { html += buildRow(row); });
            html += `<tr class="fw-bold">
                        <td colspan="9" class="text-end">Senior Section Total:</td>
                        <td>${seniorTotal.toFixed(2)}</td>
                     </tr>`;
        }

        // Grand Total
        if (data.grandTotal !== undefined) {
            html += `<tr class="fw-bold table-warning">
                        <td colspan="9" class="text-end">Grand Total:</td>
                        <td>${data.grandTotal}</td>
                     </tr>`;
        }

        return html || `<tr>
            <td colspan="10" class="text-center fst-italic fw-bold text-danger">No Records Found</td>
        </tr>`;
    }

    // ── Fetch Report ───────────────────────────────────────────────────────────
    function getReport(page = 1) {
        const filters = getFilters();
        if (!validateFilters(filters)) return;

        showLoader();
        reportBody.html('');
        summaryReport.hide().html('');
        reportContainer.hide();
        pagination.html('');

        $.ajax({
            url: '{{ route('admin.reports.feeReportAdmin') }}',
            type: 'GET',
            dataType: 'JSON',
            data: { ...filters, page },
            success(response) {
                if (!response.data) {
                    reportBody.html(
                        '<tr><td colspan="10" class="text-center text-danger fw-bold">No Records Found</td></tr>'
                    );
                    reportContainer.show();
                    return;
                }

                if (filters.reportType == 1) {
                    summaryReport
                        .html('Summary Amount: <strong>' + (response.data[0]?.summeryAmount ?? 'N/A') + '</strong>')
                        .show();
                    reportContainer.hide();
                } else {
                    reportBody.html(buildTableHtml(response.data, response.pagination));
                    adminUpdatePaginationControls(response.pagination);
                    reportContainer.show();
                }
            },
            error(xhr) {
                const message = xhr.responseJSON?.message ?? {};

                if (typeof message === 'object') {
                    if (message.session)    showError('#session-error',     message.session[0]);
                    if (message.feeType)    showError('#fee-type-error',    message.feeType[0]);
                    if (message.reportType) showError('#report-type-error', message.reportType[0]);
                    if (message.startDate)  showError('#start-date-error',  message.startDate[0]);
                    if (message.endDate)    showError('#end-date-error',    message.endDate[0]);
                } else {
                    reportBody.html(
                        '<tr><td colspan="10" class="text-center text-danger">Server error. Please try again.</td></tr>'
                    );
                    reportContainer.show();
                }
            },
            complete() { hideLoader(); }
        });
    }

    // ── Export Excel ───────────────────────────────────────────────────────────
    function exportExcel() {
        const filters = getFilters();
        if (!validateFilters(filters)) return;

        const params = new URLSearchParams({
            session:    filters.session,
            feeType:    filters.feeType,
            reportType: filters.reportType,
            startDate:  filters.startDate,
            endDate:    filters.endDate,
        });

        window.location.href = `{{ route('admin.reports.adminFullFee.excel') }}?${params.toString()}`;
    }

    // ── Event Bindings ─────────────────────────────────────────────────────────
    $('#btn-show-report').on('click', () => getReport(1));

    $(document).on('click', '#admin-pagination .page-link', function (e) {
        e.preventDefault();
        const page = $(this).data('page');
        if (page) getReport(page);
    });

    $('#btn-export').on('click', exportExcel);

    // Reset on filter change
    $('#session_id, #fee_type, #report_type, #start_date, #end_date').on('change', function () {
        reportContainer.hide();
        reportBody.html('');
        summaryReport.hide().html('');
        pagination.html('');
        clearErrors();
    });

});
</script>
@endsection