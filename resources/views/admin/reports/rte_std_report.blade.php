@extends('admin.index')

@section('sub-content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card border-0 bg-white">

                <div class="card-header flex-wrap bg-white d-flex align-items-center justify-content-between">
                    <h5 class="mb-0 mt-0">{{ __('RTE Student') }}</h5>
                    <a href="{{ route('admin.reports') }}" class="btn bg-light btn-sm">
                        <span class="mdi mdi-chevron-left me-2"></span>Back
                    </a>
                </div>

                <div class="card-body">

                    {{-- ── Filter Form ─────────────────────────────────────── --}}
                    <form id="rte-report-form" novalidate>
                        <input type="hidden" id="current_session" value="">

                        <div class="row mt-2">

                            {{-- Class --}}
                            <div class="form-group col-md-6">
                                <label for="class_id" class="mt-2">
                                    Class <span class="text-danger">*</span>
                                </label>
                                <select name="class_id" id="class_id" class="form-control">
                                    <option value="">All Classes</option>
                                </select>
                                <span class="invalid-feedback fw-bold" id="class-error" role="alert"></span>
                            </div>

                        </div>

                        <div class="mt-3 d-flex align-items-center gap-2">
                            <button type="button" id="btn-show-report" class="btn btn-primary">
                                Show Report
                            </button>
                            <img src="{{ config('myconfig.myloader') }}" alt="Loading…" id="loader"
                                style="display:none; width:40px;">
                        </div>
                    </form>

                    {{-- ── Results ─────────────────────────────────────────── --}}
                    <div id="report-container" class="mt-4" style="display:none;">
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>S.No.</th>
                                        <th>SRNO</th>
                                        <th>Name</th>
                                        <th>Guardian Name</th>
                                        <th>Category (WS/DG)</th>
                                        <th>Sign. of Certifier</th>
                                        <th>Remark</th>
                                    </tr>
                                </thead>
                                <tbody id="report-body"></tbody>
                            </table>
                        </div>
                        <div id="admin-pagination" class="mt-2"></div>
                    </div>

                    {{-- Export — only shown when records exist --}}
                    <div id="export-container" class="mt-3" style="display:none;">
                        <button type="button" id="btn-export" class="btn btn-info">Export</button>
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

    // ── Init ───────────────────────────────────────────────────────────────────
    getClassDropDownWithAll();

    // ── Selectors ──────────────────────────────────────────────────────────────
    const loader          = $('#loader');
    const reportContainer = $('#report-container');
    const exportContainer = $('#export-container');
    const reportBody      = $('#report-body');
    const pagination      = $('#admin-pagination');

    // ── Helpers ────────────────────────────────────────────────────────────────
    function showLoader() { loader.show(); }
    function hideLoader() { loader.hide(); }

    function clearErrors() {
        $('#class-error').text('').hide();
    }

    function showError(id, message) {
        $(id).text(message).show();
    }

    function getFilters() {
        return {
            class:   $('#class_id').val(),
            session: $('#current_session').val(),
        };
    }

    // ── Build Table HTML ───────────────────────────────────────────────────────
    function buildTableHtml(data) {
        if (!data.data || data.data.length === 0) {
            return '<tr><td colspan="7" class="text-center fst-italic fw-bold text-danger">No Records Found</td></tr>';
        }

        const startIndex = (data.current_page - 1) * data.per_page;
        let html = '';

        data.data.forEach((s, index) => {
            html += `
                <tr>
                    <td>${startIndex + index + 1}.</td>
                    <td>${s.srno   ?? '-'}</td>
                    <td>${s.name   ?? '-'}</td>
                    <td>${s.f_name ?? '-'}</td>
                    <td>-</td>
                    <td>-</td>
                    <td>-</td>
                </tr>`;
        });

        return html;
    }

    // ── Fetch Report ───────────────────────────────────────────────────────────
    function getReport(page = 1) {
        const filters = getFilters();
        clearErrors();

        showLoader();
        reportBody.html('');
        reportContainer.hide();
        exportContainer.hide();
        pagination.html('');

        $.ajax({
            url: '{{ route('admin.reports.rteStudentReport') }}',
            type: 'GET',
            dataType: 'JSON',
            data: { ...filters, page },
            success(response) {
                const hasRecords = response.data?.data?.length > 0;

                reportBody.html(buildTableHtml(response.data));
                adminUpdatePaginationControls(response.data);
                reportContainer.show();

                // Show export only when records exist
                if (hasRecords) {
                    exportContainer.show();
                } else {
                    exportContainer.hide();
                }
            },
            error(xhr) {
                const message = xhr.responseJSON?.message ?? {};

                if (typeof message === 'object') {
                    if (message.class)   showError('#class-error', message.class[0]);
                    if (message.session) showError('#class-error', message.session[0]);
                } else {
                    reportBody.html(
                        '<tr><td colspan="7" class="text-center text-danger">Server error. Please try again.</td></tr>'
                    );
                    reportContainer.show();
                }

                exportContainer.hide();
            },
            complete() { hideLoader(); }
        });
    }

    // ── Export ─────────────────────────────────────────────────────────────────
    function exportExcel() {
        const filters = getFilters();

        const params = new URLSearchParams({
            class:   filters.class,
            session: filters.session,
        });

        window.location.href = `{{ route('admin.reports.rteStudentReport.excel') }}?${params.toString()}`;
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
    $('#class_id').on('change', function () {
        reportContainer.hide();
        exportContainer.hide();
        reportBody.html('');
        pagination.html('');
        clearErrors();
    });

});
</script>
@endsection