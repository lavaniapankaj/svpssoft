@extends('admin.index')

@section('sub-content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card border-0 bg-white shadow-sm">

                {{-- Card Header --}}
                <div class="card-header flex-wrap bg-white d-flex align-items-center justify-content-between py-3">
                    <h5 class="mb-0 mt-0">{{ __('Age-Wise Student Report') }}</h5>
                    <a href="{{ route('admin.reports') }}" class="btn bg-light btn-sm">
                        <span class="mdi mdi-chevron-left me-1"></span>Back
                    </a>
                </div>

                <div class="card-body">

                    {{-- Filter Form --}}
                    <form id="filter-form" novalidate>
                        <div class="row mt-2">

                            {{-- Class --}}
                            <div class="form-group col-md-4 mb-3">
                                <label for="admin_class_id" class="form-label fw-semibold">
                                    Class <span class="text-danger">*</span>
                                </label>
                                <select name="class" id="admin_class_id" class="form-control" {{ count($classes) === 0 ? 'disabled' : 'required' }}>
                                    @if (count($classes) > 0)
                                        <option value="">— Select Class —</option>
                                        <option value="all" {{ request('class') === 'all' ? 'selected' : '' }}>All Classes</option>
                                        @foreach ($classes as $key => $class)
                                            <option value="{{ $key }}" {{ request('class') == $key ? 'selected' : '' }}>
                                                {{ $class }}
                                            </option>
                                        @endforeach
                                    @else
                                        <option value="" disabled selected>No Classes Found</option>
                                    @endif
                                </select>
                                <div class="invalid-feedback" id="class-error"></div>
                            </div>

                            {{-- Calculation Date --}}
                            <div class="form-group col-md-4 mb-3">
                                <label for="cdate" class="form-label fw-semibold">
                                    Calculation Date <span class="text-danger">*</span>
                                </label>
                                <input type="date" name="date" id="cdate" class="form-control" value="{{ request('date') }}" required>
                                <div class="invalid-feedback" id="date-error"></div>
                            </div>

                        </div>

                        <div class="mt-2 d-flex align-items-center gap-2 flex-wrap">
                            <button type="button" class="btn btn-primary px-4" id="show-report">
                                <span class="mdi mdi-chart-bar me-1"></span>Show Report
                            </button>
                            <button type="button" class="btn btn-secondary px-4" id="show-report2">
                                <span class="mdi mdi-account-group me-1"></span>Student-Wise Report
                            </button>
                            <img src="{{ config('myconfig.myloader') }}" alt="Loading…" id="loader" class="loader" style="display:none; height:15px;">
                        </div>
                    </form>
                    {{-- /Filter Form --}}


                    {{-- Age-Group Summary Table --}}
                    <div id="summary-container" class="mt-4" style="display:none;">
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered align-middle" id="summary-table">
                                <thead class="table-light">
                                    <tr>
                                        <th>Class</th>
                                        <th>Gender</th>
                                        <th>&lt;5</th>
                                        <th>5</th>
                                        <th>6</th>
                                        <th>7</th>
                                        <th>8</th>
                                        <th>9</th>
                                        <th>10</th>
                                        <th>11</th>
                                        <th>12</th>
                                        <th>13</th>
                                        <th>14</th>
                                        <th>15</th>
                                        <th>16</th>
                                        <th>&gt;16</th>
                                    </tr>
                                </thead>
                                <tbody id="summary-body"></tbody>
                            </table>
                        </div>
                        <div class="mt-3">
                            <button type="button" class="btn btn-success" id="export-btn1">
                                <span class="mdi mdi-file-excel me-1"></span>Export
                            </button>
                        </div>
                    </div>
                    {{-- /Summary Table --}}


                    {{-- Student-Wise Detail Table --}}
                    <div id="detail-container" class="mt-4" style="display:none;">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover align-middle" id="detail-table">
                                <thead class="table-light">
                                    <tr>
                                        <th>S.No.</th>
                                        <th>Class</th>
                                        <th>Section</th>
                                        <th>SR No.</th>
                                        <th>Name</th>
                                        <th>Father's Name</th>
                                        <th>Mother's Name</th>
                                        <th>DOB</th>
                                        <th>Age</th>
                                        <th>Mobile No.</th>
                                    </tr>
                                </thead>
                                <tbody id="detail-body"></tbody>
                            </table>
                        </div>
                        <div id="admin-pagination" class="mt-2"></div>
                        <div class="mt-3">
                            <button type="button" class="btn btn-success" id="export-btn2">
                                <span class="mdi mdi-file-excel me-1"></span>Export
                            </button>
                        </div>
                    </div>
                    {{-- /Detail Table --}}

                </div>{{-- /.card-body --}}
            </div>{{-- /.card --}}
        </div>
    </div>
</div>
@endsection


@section('admin-scripts')
<script>
$(function () {

    // =========================================================================
    //  Selectors
    // =========================================================================
    const classSelect       = $('#admin_class_id');
    const dateInput         = $('#cdate');
    const showSummaryBtn    = $('#show-report');
    const showDetailBtn     = $('#show-report2');
    const exportBtn1        = $('#export-btn1');
    const exportBtn2        = $('#export-btn2');
    const loader            = $('#loader');
    const summaryContainer  = $('#summary-container');
    const summaryBody       = $('#summary-body');
    const detailContainer   = $('#detail-container');
    const detailBody        = $('#detail-body');
    const pagination        = $('#admin-pagination');

    // =========================================================================
    //  State
    // =========================================================================
    let hasSummaryData = false;
    let hasDetailData  = false;

    // =========================================================================
    //  Utility
    // =========================================================================
    function esc(value) {
        return $('<div>').text(value ?? '').html();
    }

    function showLoader() { loader.show(); }
    function hideLoader() { loader.hide(); }

    function setButtonsDisabled(state) {
        showSummaryBtn.prop('disabled', state);
        showDetailBtn.prop('disabled', state);
    }

    // =========================================================================
    //  Form validation
    // =========================================================================
    function validateForm() {
        let valid = true;

        if (!classSelect.val()) {
            $('#class-error').text('Please select a class.');
            classSelect.addClass('is-invalid');
            valid = false;
        } else {
            $('#class-error').text('');
            classSelect.removeClass('is-invalid');
        }

        if (!dateInput.val()) {
            $('#date-error').text('Please select a calculation date.');
            dateInput.addClass('is-invalid');
            valid = false;
        } else {
            $('#date-error').text('');
            dateInput.removeClass('is-invalid');
        }

        return valid;
    }

    // =========================================================================
    //  Build common payload
    // =========================================================================
    function getPayload(extra) {
        return Object.assign({
            class : classSelect.val(),
            date  : dateInput.val(),
        }, extra || {});
    }

    // =========================================================================
    //  Reset helpers
    // =========================================================================
    function resetSummary() {
        hasSummaryData = false;
        summaryBody.empty();
        summaryContainer.hide();
    }

    function resetDetail() {
        hasDetailData = false;
        detailBody.empty();
        pagination.empty();
        detailContainer.hide();
    }

    // =========================================================================
    //  Render helpers
    // =========================================================================
    function ageCell(group, key) {
        return esc(group?.[key] ?? 0);
    }

    function renderSummaryRow(classData, gender) {
        const g   = gender.toLowerCase();   // 'boys' | 'girls'
        const ag  = classData.ageGroups;
        return `
            <tr>
                ${gender === 'Boys'
                    ? `<td rowspan="2" class="fw-semibold align-middle">${esc(classData.class)}</td>`
                    : ''}
                <td>${esc(gender)}</td>
                <td>${ageCell(ag.lessThanFive,     g)}</td>
                <td>${ageCell(ag.equalToFive,       g)}</td>
                <td>${ageCell(ag.equalToSix,        g)}</td>
                <td>${ageCell(ag.equalToSeven,      g)}</td>
                <td>${ageCell(ag.equalToEight,      g)}</td>
                <td>${ageCell(ag.equalToNine,       g)}</td>
                <td>${ageCell(ag.equalToTen,        g)}</td>
                <td>${ageCell(ag.equalToEleven,     g)}</td>
                <td>${ageCell(ag.equalToTwelve,     g)}</td>
                <td>${ageCell(ag.equalToThirteen,   g)}</td>
                <td>${ageCell(ag.equalToFourteen,   g)}</td>
                <td>${ageCell(ag.equalToFifteen,    g)}</td>
                <td>${ageCell(ag.equalToSixteen,    g)}</td>
                <td>${ageCell(ag.aboveToSixteen,    g)}</td>
            </tr>`;
    }

    function renderEmptySummary() {
        summaryBody.html(`
            <tr>
                <td colspan="16" class="text-center text-muted py-4">
                    <span class="mdi mdi-information-outline me-1"></span>No records found.
                </td>
            </tr>`);
        summaryContainer.show();
    }

    function renderEmptyDetail() {
        detailBody.html(`
            <tr>
                <td colspan="10" class="text-center text-muted py-4">
                    <span class="mdi mdi-information-outline me-1"></span>No records found.
                </td>
            </tr>`);
        pagination.empty();
        detailContainer.show();
    }

    function renderError(target, colspan, message) {
        target.html(`
            <tr>
                <td colspan="${colspan}" class="text-center text-danger py-4">
                    <span class="mdi mdi-alert-circle-outline me-1"></span>${esc(message)}
                </td>
            </tr>`);
    }

    // =========================================================================
    //  Fetch: Summary (age-group) report
    // =========================================================================
    function getSummaryReport() {
        if (!validateForm()) return;

        resetSummary();
        showLoader();
        setButtonsDisabled(true);

        $.ajax({
            url     : '{{ route("admin.reports.reportAgeWise") }}',
            type    : 'GET',
            dataType: 'json',
            data    : getPayload(),
        })
        .done(function (response) {
            const data = response?.data;

            if (!data || !data.length) {
                renderEmptySummary();
                return;
            }

            let html = '';
            data.forEach(function (classData) {
                html += renderSummaryRow(classData, 'Boys');
                html += renderSummaryRow(classData, 'Girls');
            });

            summaryBody.html(html);
            hasSummaryData = true;
            summaryContainer.show();
        })
        .fail(function (xhr) {
            const msg = xhr.responseJSON?.message?.date
                ?? xhr.responseJSON?.message
                ?? 'Failed to load report. Please try again.';
            renderError(summaryBody, 16, typeof msg === 'object' ? JSON.stringify(msg) : msg);
            summaryContainer.show();
        })
        .always(function () {
            hideLoader();
            setButtonsDisabled(false);
        });
    }

    // =========================================================================
    //  Fetch: Student-wise detail report
    // =========================================================================
    function getDetailReport(page) {
        page = page || 1;

        if (!validateForm()) return;

        resetDetail();
        showLoader();
        setButtonsDisabled(true);

        $.ajax({
            url     : '{{ route("admin.reports.reportAgeWiseWithDetails") }}',
            type    : 'GET',
            dataType: 'json',
            data    : getPayload({ page: page }),
        })
        .done(function (response) {
            const pageData = response?.data;
            const rows     = pageData?.data;

            // Normalise array vs keyed-object (Laravel paginator offset keys)
            const students = Array.isArray(rows) ? rows : Object.values(rows ?? {});

            if (!students.length) {
                renderEmptyDetail();
                return;
            }

            const startIndex = (pageData.current_page - 1) * pageData.per_page + 1;
            let html = '';

            students.forEach(function (s, i) {
                html += `
                    <tr>
                        <td>${esc(startIndex + i)}</td>
                        <td>${esc(s.class)}</td>
                        <td>${esc(s.section)}</td>
                        <td>${esc(s.srno)}</td>
                        <td>${esc(s.name)}</td>
                        <td>${esc(s.f_name)}</td>
                        <td>${esc(s.m_name)}</td>
                        <td>${esc(s.dob) || 'N/A'}</td>
                        <td>${esc(s.age) || 'N/A'}</td>
                        <td>${esc(s.mobile) || 'N/A'}</td>
                    </tr>`;
            });

            detailBody.html(html);
            adminUpdatePaginationControls(pageData);
            hasDetailData = true;
            detailContainer.show();
        })
        .fail(function (xhr) {
            const msg = xhr.responseJSON?.message?.date
                ?? xhr.responseJSON?.message
                ?? 'Failed to load report. Please try again.';
            renderError(detailBody, 10, typeof msg === 'object' ? JSON.stringify(msg) : msg);
            pagination.empty();
            detailContainer.show();
        })
        .always(function () {
            hideLoader();
            setButtonsDisabled(false);
        });
    }

    // =========================================================================
    //  Events: Report buttons
    // =========================================================================
    showSummaryBtn.on('click', function () {
        resetDetail();
        getSummaryReport();
    });

    showDetailBtn.on('click', function () {
        resetSummary();
        getDetailReport(1);
    });

    // =========================================================================
    //  Events: Filter changes → hide stale results
    // =========================================================================
    classSelect.add(dateInput).on('change', function () {
        resetSummary();
        resetDetail();
    });

    // =========================================================================
    //  Events: Pagination (delegated to static ancestor)
    // =========================================================================
    detailContainer.on('click', '#admin-pagination .page-link[data-page]', function (e) {
        e.preventDefault();
        const page = $(this).data('page');
        if (page) getDetailReport(page);
    });

    // =========================================================================
    //  Events: Export buttons
    // =========================================================================
    exportBtn1.on('click', function () {
        if (!hasSummaryData) {
            alert('Please run the report first before exporting.');
            return;
        }
        if (!validateForm()) return;

        const params = new URLSearchParams(getPayload());
        window.location.href = '{{ route("admin.reports.exportReportByAge") }}?' + params.toString();
    });

    exportBtn2.on('click', function () {
        if (!hasDetailData) {
            alert('Please run the student-wise report first before exporting.');
            return;
        }
        if (!validateForm()) return;

        const params = new URLSearchParams(getPayload());
        window.location.href = '{{ route("admin.reports.exportReportByAgeWithDetails") }}?' + params.toString();
    });

});
</script>
@endsection