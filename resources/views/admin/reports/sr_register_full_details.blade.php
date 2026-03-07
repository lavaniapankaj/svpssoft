@extends('admin.index')

@section('sub-content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card border-0 bg-white">

                <div class="card-header flex-wrap bg-white d-flex align-items-center justify-content-between">
                    <h5 class="mb-0 mt-0">{{ __('SR Register (Full Details)') }}</h5>
                    <a href="{{ route('admin.reports') }}" class="btn bg-light btn-sm">
                        <span class="mdi mdi-chevron-left me-2"></span>Back
                    </a>
                </div>

                <div class="card-body">

                    {{-- ── Filter Form ─────────────────────────────────────── --}}
                    <form id="sr-report-form" novalidate>
                        <input type="hidden" id="current_session" value="">

                        <div class="row mt-2">

                            {{-- Class --}}
                            <div class="form-group col-md-6">
                                <label for="class_id" class="mt-2">
                                    Class <span class="text-danger">*</span>
                                </label>
                                <select name="class_id" id="class_id" class="form-control" required>
                                    <option value="">All Classes</option>
                                </select>
                                <span class="invalid-feedback fw-bold" id="class-error" role="alert"></span>
                            </div>

                            {{-- Section --}}
                            <div class="form-group col-md-6">
                                <label for="section_id" class="mt-2">
                                    Section <span class="text-danger">*</span>
                                </label>
                                <select name="section_id" id="section_id" class="form-control" required>
                                    <option value="">All Sections</option>
                                </select>
                                <span class="invalid-feedback fw-bold" id="section-error" role="alert"></span>
                            </div>

                        </div>

                        <div class="row">

                            {{-- SRNO Type --}}
                            <div class="form-group col-md-6">
                                <label for="srno_type" class="mt-2">
                                    Select SRNO Type <span class="text-danger">*</span>
                                </label>
                                <select name="srno_type" id="srno_type" class="form-control" required>
                                    <option value="1">General Student SRNO</option>
                                    <option value="2">Junior Student SRNO</option>
                                    <option value="3">RTE Student SRNO</option>
                                </select>
                                <span class="invalid-feedback fw-bold" id="srno-type-error" role="alert"></span>
                            </div>

                        </div>

                        <div class="row">

                            {{-- Start SRNO --}}
                            <div class="form-group col-md-6">
                                <label for="start_srno" class="mt-2">Starting SRNO</label>
                                <input type="text" id="start_srno" class="form-control">
                                <span class="invalid-feedback fw-bold" id="start-srno-error" role="alert"></span>
                            </div>

                            {{-- End SRNO --}}
                            <div class="form-group col-md-6">
                                <label for="end_srno" class="mt-2">End SRNO</label>
                                <input type="text" id="end_srno" class="form-control">
                                <span class="invalid-feedback fw-bold" id="end-srno-error" role="alert"></span>
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
                                        <th>Class</th>
                                        <th>Section</th>
                                        <th>Roll No.</th>
                                        <th>Status</th>
                                        <th>Name</th>
                                        <th>Father's Name</th>
                                        <th>Mother's Name</th>
                                        <th>Contact No.</th>
                                        <th>Contact 2</th>
                                        <th>Address</th>
                                        <th>Gender</th>
                                        <th>DOB</th>
                                        <th>Admission Date</th>
                                        <th>Age Proof</th>
                                        <th>Prev. SRNO</th>
                                        <th>Religion</th>
                                        <th>Transport</th>
                                        <th>Category</th>
                                        <th>Father's Occupation</th>
                                        <th>Mother's Occupation</th>
                                        <th>View</th>
                                    </tr>
                                </thead>
                                <tbody id="report-body"></tbody>
                            </table>
                        </div>
                        <div id="admin-pagination" class="mt-2"></div>
                        <div class="mt-3">
                            <button type="button" id="btn-export" class="btn btn-info">Excel</button>
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

    // ── Init ───────────────────────────────────────────────────────────────────
    getClassDropDownWithAll();

    // ── Selectors ──────────────────────────────────────────────────────────────
    const loader          = $('#loader');
    const reportContainer = $('#report-container');
    const reportBody      = $('#report-body');
    const pagination      = $('#admin-pagination');

    // ── Label Maps ────────────────────────────────────────────────────────────
    const statusMap = {
        1: 'Active', 2: 'Class Promoted', 3: 'School Promoted', 4: 'TC', 5: 'Left Out',
    };
    const genderMap = {
        1: 'Male', 2: 'Female', 3: "Other's",
    };
    const ageProofMap = {
        0: 'N/A', 1: 'Transfer Certificate (T.C.)', 2: 'Birth Certificate',
        3: 'Affidavit', 4: 'Aadhar Card',
    };
    const religionMap = {
        1: 'Hindu', 2: 'Muslim', 3: 'Christian', 4: 'Sikh',
    };
    const transportMap = {
        0: 'No', 1: 'Yes',
    };
    const categoryMap = {
        1: 'General', 2: 'OBC', 3: 'SC', 4: 'ST', 5: 'BC',
    };
    const occupationMap = {
        1: 'Private Service', 2: 'Govt. Service', 3: 'Farmer',
        4: 'Business', 5: 'Military Service',
    };
    const mOccupationMap = {
        1: 'Private Service', 2: 'Govt. Service', 3: 'House Wife',
        4: 'Business', 5: 'Military Service',
    };

    // ── Helpers ────────────────────────────────────────────────────────────────
    function showLoader() { loader.show(); }
    function hideLoader() { loader.hide(); }

    function clearErrors() {
        ['#class-error', '#section-error', '#srno-type-error', '#start-srno-error', '#end-srno-error']
            .forEach(id => $(id).text('').hide());
    }

    function showError(id, message) {
        $(id).text(message).show();
    }

    function getFilters() {
        return {
            class:     $('#class_id').val(),
            section:   $('#section_id').val(),
            session:   $('#current_session').val(),
            srnoType:  $('#srno_type').val(),
            startSrno: $('#start_srno').val(),
            endSrno:   $('#end_srno').val(),
        };
    }

    function validateFilters(filters) {
        let valid = true;
        clearErrors();

        if (!filters.srnoType) {
            showError('#srno-type-error', 'Please select a SRNO type.');
            valid = false;
        }
        if (filters.startSrno && filters.endSrno && parseInt(filters.startSrno) > parseInt(filters.endSrno)) {
            showError('#start-srno-error', 'Starting SRNO must be less than or equal to End SRNO.');
            valid = false;
        }

        return valid;
    }

    // ── Build Table HTML ───────────────────────────────────────────────────────
    function buildTableHtml(data) {
        if (!data.data || data.data.length === 0) {
            return '<tr><td colspan="23" class="text-center fst-italic fw-bold text-danger">No Records Found</td></tr>';
        }

        const startIndex = (data.current_page - 1) * data.per_page;
        let html = '';

        data.data.forEach((s, index) => {
            html += `
                <tr>
                    <td>${startIndex + index + 1}</td>
                    <td>${s.srno          ?? '-'}</td>
                    <td>${s.class_name    ?? '-'}</td>
                    <td>${s.section_name  ?? '-'}</td>
                    <td>${s.rollno        ?? '-'}</td>
                    <td>${statusMap[s.ssid]         ?? '-'}</td>
                    <td>${s.student_name  ?? '-'}</td>
                    <td>${s.f_name        ?? '-'}</td>
                    <td>${s.m_name        ?? '-'}</td>
                    <td>${s.f_mobile      ?? 'N/A'}</td>
                    <td>${s.m_mobile      ?? 'N/A'}</td>
                    <td>${s.address       ?? '-'}</td>
                    <td>${genderMap[s.gender]       ?? '-'}</td>
                    <td>${s.dob           ?? 'N/A'}</td>
                    <td>${s.form_submit_date ?? '-'}</td>
                    <td>${ageProofMap[s.age_proof]  ?? '-'}</td>
                    <td>${s.prev_srno     ?? '-'}</td>
                    <td>${religionMap[s.religion]   ?? '-'}</td>
                    <td>${transportMap[s.transport] ?? '-'}</td>
                    <td>${categoryMap[s.category]   ?? '-'}</td>
                    <td>${occupationMap[s.f_occupation]  ?? '-'}</td>
                    <td>${mOccupationMap[s.m_occupation] ?? '-'}</td>
                    <td>
                        <a href="${siteUrl}/admin/full-detail-student/?prevSrno=${s.prev_srno ?? ''}&srno=${s.srno}"
                            class="btn btn-sm btn-icon p-1">
                            <i class="mdi mdi-eye"
                                data-bs-toggle="tooltip"
                                data-bs-placement="top"
                                title="View"></i>
                        </a>
                    </td>
                </tr>`;
        });

        return html;
    }

    // ── Fetch Report ───────────────────────────────────────────────────────────
    function getReport(page = 1) {
        const filters = getFilters();
        if (!validateFilters(filters)) return;

        showLoader();
        reportBody.html('');
        reportContainer.hide();
        pagination.html('');

        $.ajax({
            url: '{{ route('admin.reports.srRegisterFullDetails') }}',
            type: 'GET',
            dataType: 'JSON',
            data: { ...filters, page },
            success(response) {
                reportBody.html(buildTableHtml(response.data));
                adminUpdatePaginationControls(response.data);
                reportContainer.show();
            },
            error(xhr) {
                const message = xhr.responseJSON?.message ?? {};

                if (typeof message === 'object') {
                    if (message.class)     showError('#class-error',      message.class[0]);
                    if (message.section)   showError('#section-error',    message.section[0]);
                    if (message.srnoType)  showError('#srno-type-error',  message.srnoType[0]);
                    if (message.startSrno) showError('#start-srno-error', message.startSrno[0]);
                    if (message.endSrno)   showError('#end-srno-error',   message.endSrno[0]);
                } else {
                    reportBody.html(
                        '<tr><td colspan="23" class="text-center text-danger">Server error. Please try again.</td></tr>'
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
            class:     filters.class,
            section:   filters.section,
            session:   filters.session,
            srnoType:  filters.srnoType,
            startSrno: filters.startSrno,
            endSrno:   filters.endSrno,
        });

        window.location.href = `{{ route('admin.reports.srRegisterFullReport.excel') }}?${params.toString()}`;
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
    $('#class_id, #section_id, #srno_type').on('change', function () {
        reportContainer.hide();
        reportBody.html('');
        pagination.html('');
        clearErrors();
    });

});
</script>
@endsection