@extends('admin.index')

@section('sub-content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card border-0 bg-white shadow-sm">

                {{-- Card Header --}}
                <div class="card-header flex-wrap bg-white d-flex align-items-center justify-content-between py-3">
                    <h5 class="mb-0 mt-0">{{ __('Student Report (Transport / Without Transport)') }}</h5>
                    <a href="{{ route('admin.reports') }}" class="btn bg-light btn-sm">
                        <span class="mdi mdi-chevron-left me-1"></span>Back
                    </a>
                </div>

                <div class="card-body">

                    {{-- Filter Form --}}
                    <form id="filter-form" method="get" novalidate>
                        <div class="row mt-2">

                            {{-- Class --}}
                            <div class="form-group col-md-4 mb-3">
                                <label for="admin_class_id" class="form-label fw-semibold">
                                    Class <span class="text-danger">*</span>
                                </label>
                                <select name="class" id="admin_class_id" class="form-control" {{ count($classes) === 0 ? 'disabled' : 'required' }} >
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

                            {{-- Section --}}
                            <div class="form-group col-md-4 mb-3">
                                <label for="admin_section_id" class="form-label fw-semibold">
                                    Section <span class="text-danger">*</span>
                                </label>
                                <select name="section" id="admin_section_id" class="form-control" required>
                                    <option value="">— Select Section —</option>
                                </select>
                                <div class="invalid-feedback" id="section-error"></div>
                            </div>

                            {{-- Transport --}}
                            <div class="form-group col-md-4 mb-3">
                                <label for="transport" class="form-label fw-semibold">
                                    Transport <span class="text-danger">*</span>
                                </label>
                                <select name="transport" id="transport" class="form-control" required>
                                    <option value="0,1" {{ request('transport') === '0,1' || !request('transport') ? 'selected' : '' }}>All</option>
                                    <option value="1"   {{ request('transport') === '1'   ? 'selected' : '' }}>Transport Occupied</option>
                                    <option value="0"   {{ request('transport') === '0'   ? 'selected' : '' }}>Without Transport</option>
                                </select>
                                <div class="invalid-feedback"></div>
                            </div>

                        </div>{{-- /.row --}}

                        <div class="mt-2 d-flex align-items-center gap-3">
                            <button type="button" class="btn btn-primary px-4" id="show-report">
                                <span class="mdi mdi-magnify me-1"></span>Show Report
                            </button>
                            <img src="{{ config('myconfig.myloader') }}" alt="Loading…" id="loader" class="loader" style="display:none; width:10%;">
                        </div>
                    </form>
                    {{-- /Filter Form --}}


                    {{-- Report Container (hidden until data loads) --}}
                    <div id="report-container" class="mt-4" style="display:none;">

                        <div class="table-responsive">
                            <table class="table table-sm table-hover align-middle" id="report-table">
                                <thead class="table-light">
                                    <tr>
                                        <th>R.N.</th>
                                        <th>Reg No.</th>
                                        <th>Class</th>
                                        <th>Section</th>
                                        <th>Student Name</th>
                                        <th>Father's Name</th>
                                        <th>DOB</th>
                                        <th>Address</th>
                                        <th>Mobile No.</th>
                                    </tr>
                                </thead>
                                <tbody id="report-body"></tbody>
                            </table>
                        </div>

                        <div id="admin-pagination"></div>
                        <div class="mt-3" id="export-wrapper" style="display:none;">
                            <button type="button" class="btn btn-success" id="export-btn">
                                <span class="mdi mdi-file-excel me-1"></span>Export to Excel
                            </button>
                        </div>

                    </div>
                    {{-- /Report Container --}}

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
    const classSelect     = $('#admin_class_id');
    const sectionSelect   = $('#admin_section_id');
    const transportSelect = $('#transport');
    const showBtn         = $('#show-report');
    const exportBtn       = $('#export-btn');
    const loader          = $('#loader');
    const container       = $('#report-container');
    const tbody           = $('#report-body');
    const pagination      = $('#admin-pagination');
    const exportWrapper   = $('#export-wrapper');

    // =========================================================================
    //  State
    // =========================================================================
    let hasData = false;

    // =========================================================================
    //  Utility helpers
    // =========================================================================
    function esc(value) {
        return $('<div>').text(value ?? '').html();
    }

    function showLoader()  { loader.show(); }
    function hideLoader()  { loader.hide(); }

    function disableControls(state) {
        showBtn.prop('disabled', state);
        classSelect.prop('disabled', state || (classSelect.find('option').length <= 1));
    }

    function resetReportArea() {
        hasData = false;
        tbody.empty();
        pagination.empty();
        container.hide();
        exportWrapper.hide();
    }

    // =========================================================================
    //  Form validation
    // =========================================================================
    function validateForm() {
        let valid = true;

        if (!classSelect.val()) {
            $('#class-error').text('Please select a class.').closest('.form-group').find('select').addClass('is-invalid');
            valid = false;
        } else {
            $('#class-error').text('').closest('.form-group').find('select').removeClass('is-invalid');
        }

        if (!sectionSelect.val()) {
            $('#section-error').text('Please select a section.').closest('.form-group').find('select').addClass('is-invalid');
            valid = false;
        } else {
            $('#section-error').text('').closest('.form-group').find('select').removeClass('is-invalid');
        }

        return valid;
    }

    // =========================================================================
    //  Section loader
    // =========================================================================
    function loadSections(classId, callback) {
        sectionSelect.html('<option value="">Loading…</option>').prop('disabled', true);

        getAdminAllSections(classId, function () {
            sectionSelect.prop('disabled', false);

            if (sectionSelect.find('option[value=""]').length === 0) {
                sectionSelect.prepend('<option value="">— Select Section —</option>');
            }
            sectionSelect.val('');

            if (typeof callback === 'function') callback();
        });
    }

    // =========================================================================
    //  On page load: restore state from URL params
    // =========================================================================
    const savedClass   = '{{ request()->get("class") }}';
    const savedSection = '{{ request()->get("section") }}';

    if (savedClass && classSelect.val(savedClass).val()) {
        loadSections(savedClass, function () {
            if (savedSection) {
                sectionSelect.val(savedSection);
            }
        });
    }

    // =========================================================================
    //  Class change → reload sections
    // =========================================================================
    classSelect.on('change', function () {
        resetReportArea();
        sectionSelect.html('<option value="">— Select Section —</option>');
        const classId = $(this).val();
        if (classId) {
            loadSections(classId);
        }
    });

    // =========================================================================
    //  Section / transport change → hide stale report
    // =========================================================================
    sectionSelect.add(transportSelect).on('change', function () {
        resetReportArea();
    });

    // =========================================================================
    //  Fetch & render report
    // =========================================================================
    function getReport(page) {
        page = page || 1;

        if (!validateForm()) return;

        showLoader();
        disableControls(true);

        const payload = {
            class    : classSelect.val(),
            section  : sectionSelect.val(),
            transport: transportSelect.val(),
            page     : page
        };

        $.ajax({
            url     : '{{ route("admin.reports.reportTransportWise") }}',
            type    : 'POST',
            dataType: 'json',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            data    : payload,
        })
        .done(function (response) {
            const pageData = response.data;
            const students = response.data?.data;

            if (!students || students.length === 0) {
                renderEmptyState();
                return;
            }

            let html = '';
            students.forEach(function (s) {
                html += `
                    <tr>
                        <td>${esc(s.rollno)}</td>
                        <td>${esc(s.srno)}</td>
                        <td>${esc(s.class_name)}</td>
                        <td>${esc(s.section_name)}</td>
                        <td>${esc(s.student_name)}</td>
                        <td>${esc(s.f_name)}</td>
                        <td>${esc(s.dob)}</td>
                        <td>${esc(s.address)}</td>
                        <td>${esc(s.f_mobile)}</td>
                    </tr>`;
            });

            tbody.html(html);
            adminUpdatePaginationControls(pageData);  // ← global function, targets #admin-pagination
            hasData = true;
            exportWrapper.show();
            container.show();
        })
        .fail(function (xhr) {
            const msg = 'Something went wrong while fetching the report. Please try again.';
            renderError(msg);
        })
        .always(function () {
            hideLoader();
            disableControls(false);
        });
    }

    function renderEmptyState() {
        tbody.html(`
            <tr>
                <td colspan="9" class="text-center text-muted py-4">
                    <span class="mdi mdi-information-outline me-1"></span>No records found for the selected filters.
                </td>
            </tr>
        `);
        pagination.empty();
        exportWrapper.hide();
        container.show();
        hasData = false;
    }

    function renderError(message) {
        tbody.html(`
            <tr>
                <td colspan="9" class="text-center text-danger py-4">
                    <span class="mdi mdi-alert-circle-outline me-1"></span>${esc(message)}
                </td>
            </tr>
        `);
        pagination.empty();
        exportWrapper.hide();
        container.show();
        hasData = false;
    }

    // =========================================================================
    //  Event: Show Report button
    // =========================================================================
    showBtn.on('click', function () {
        getReport(1);
    });

    // =========================================================================
    //  Event: Pagination clicks — delegated to #admin-pagination
    //  Works with the anchor tags that adminUpdatePaginationControls() injects.
    // =========================================================================
    container.on('click', '#admin-pagination .page-link[data-page]', function (e) {
        e.preventDefault();
        const page = $(this).data('page');
        if (page) getReport(page);
    });

    // =========================================================================
    //  Event: Export button
    // =========================================================================
    exportBtn.on('click', function () {

        if (!hasData) {
            alert('No data to export. Please run the report first.');
            return;
        }

        if (!validateForm()) return;

        const params = new URLSearchParams({
            class    : classSelect.val(),
            section  : sectionSelect.val(),
            transport: transportSelect.val(),
        });
        window.location.href = '{{ route("admin.reports.exportReportByTransportWise") }}?' + params.toString();
    });

});
</script>
@endsection
