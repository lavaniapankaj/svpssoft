@extends('admin.index')

@section('sub-content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card border-0 bg-white">
                    <div class="card-header flex-wrap bg-white d-flex align-items-center justify-content-between">
                        <h5 class="mb-0 mt-0">{{ __('Issue TC to Student') }}</h5>
                        <a href="{{ route('admin.reports') }}" class="btn bg-light btn-sm">
                            <span class="mdi mdi-chevron-left me-2"></span>Back
                        </a>
                    </div>

                    <div class="card-body">

                        {{-- ─── Search Form ─── --}}
                        <form id="search-form" novalidate>
                            <div class="row mt-2">
                                <input type="hidden" id="current_session" value="">
                                <div class="form-group col-md-6">
                                    <label for="srno" class="mt-2">SRNO <span class="text-danger">*</span></label>
                                    <input type="text" name="srno" id="srno" class="form-control" required>
                                </div>
                                <div class="form-group col-md-6 mt-2">
                                    <a href="{{ route('admin.student-master.search') }}"
                                        class="btn btn-sm btn-info mt-4"
                                        target="_blank" rel="noopener noreferrer">
                                        To Know SRNO Click Here
                                    </a>
                                </div>
                            </div>
                            <div class="mt-3 d-flex align-items-center gap-2">
                                <button class="btn btn-primary" type="button" id="show-report">Show Details</button>
                                <span class="text-danger fw-bold" id="error-tc"></span>
                                <img src="{{ config('myconfig.myloader') }}"
                                    alt="Loading…"
                                    id="loader"
                                    class="loader"
                                    style="display:none; width:5%;">
                            </div>
                        </form>

                        {{-- ─── Status Message ─── --}}
                        <div class="mt-4" id="status-message"></div>

                        {{-- ─── Previous Details ─── --}}
                        <div id="prev_record" class="mt-4" style="display:none;">
                            <h4 class="text-danger fw-bold">Previous Details</h4>
                            <div class="table-responsive">
                                <table class="table table-striped table-bordered">
                                    <thead id="previous-header"></thead>
                                    <tbody id="previous-body"></tbody>
                                </table>
                            </div>
                        </div>

                        {{-- ─── Current Details ─── --}}
                        <div id="current_details" class="mt-4" style="display:none;">
                            <h4 class="text-danger fw-bold">Details</h4>

                            <div class="table-responsive mb-3">
                                <table class="table table-striped table-bordered">
                                    <thead id="st-details-head"></thead>
                                    <tbody id="st-details-body"></tbody>
                                </table>
                            </div>

                            <div class="table-responsive mb-3">
                                <table class="table table-striped table-bordered">
                                    <thead id="parent-details-head"></thead>
                                    <tbody id="parent-details-body"></tbody>
                                </table>
                            </div>

                            <div class="table-responsive mb-3">
                                <table class="table table-striped table-bordered">
                                    <thead id="academic-details-head"></thead>
                                    <tbody id="academic-details-body"></tbody>
                                </table>
                            </div>

                            <div class="table-responsive mb-3">
                                <table class="table table-striped table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Last Attendance (Present) Date</th>
                                            <th id="last-attendance-date"></th>
                                        </tr>
                                    </thead>
                                </table>
                            </div>
                        </div>

                        {{-- ─── TC Form ─── --}}
                        <div id="tc-form-wrapper" class="row mt-4" style="display:none;">
                            <form id="tc-form" novalidate>
                                @csrf
                                <div class="row">
                                    <div class="form-group col-md-4">
                                        <label for="reason" class="mt-2">
                                            Enter Reason <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" name="reason" id="reason" class="form-control" required>
                                    </div>
                                    <div class="form-group col-md-4">
                                        <label for="ref_no" class="mt-2">
                                            Enter Ref TC No. <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" name="ref_no" id="ref_no" class="form-control" required>
                                    </div>
                                    <div class="form-group col-md-4">
                                        <label for="tc-date" class="mt-2">
                                            TC Issue Date <span class="text-danger">*</span>
                                        </label>
                                        <input type="date" name="tc-date" id="tc-date" class="form-control" required>
                                    </div>
                                </div>

                                <div class="mt-3 d-flex justify-content-evenly">
                                    <button class="btn btn-primary tc-btn" type="button"
                                        id="tc-btn-1"
                                        data-url="{{ route('admin.reports.tcToTheStudent') }}"
                                        data-method="POST">
                                        Issue TC (Last Passed)
                                    </button>
                                    <button class="btn btn-primary tc-btn" type="button"
                                        id="tc-btn-2"
                                        data-url="{{ route('admin.reports.tcToTheStudentBtn2') }}"
                                        data-method="POST">
                                        Issue TC (Passed)
                                    </button>
                                    <button class="btn btn-primary tc-btn" type="button"
                                        id="tc-btn-3"
                                        data-url="{{ route('admin.reports.tcToTheStudentBtn3') }}"
                                        data-method="POST">
                                        Issue TC (Studying)
                                    </button>
                                    <button class="btn btn-primary tc-btn" type="button"
                                        id="tc-btn-4"
                                        data-url="{{ route('admin.reports.tcToTheStudentBtn4') }}"
                                        data-method="GET">
                                        Issue (Reprint)
                                    </button>
                                </div>

                                <div class="row mt-4">
                                    <div class="col-md-3 text-center fw-bold"><h5>^</h5></div>
                                    <div class="col-md-3 text-center fw-bold"><h5>^</h5></div>
                                    <div class="col-md-3 text-center fw-bold"><h5>^</h5></div>
                                    <div class="col-md-3 text-center fw-bold"><h5>^</h5></div>
                                </div>

                                <div class="row mt-4 text-center text-danger">
                                    <div class="col-md-3 text-wrap">
                                        <p>Issue TC to student which is present/absent in new class but want to issue
                                            previous passed class TC. OR to left out student.</p>
                                    </div>
                                    <div class="col-md-3 text-wrap">
                                        <p>Issue TC to student which have passed last class.</p>
                                    </div>
                                    <div class="col-md-3 text-wrap">
                                        <p>Issue TC to student which is present/absent in new class but want to issue
                                            studying class TC. OR to left out student.</p>
                                    </div>
                                    <div class="col-md-3 text-wrap">
                                        <p>Reprint / ReIssue TC to student. (if issued already).</p>
                                    </div>
                                </div>
                            </form>
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

    var csrfToken = $('meta[name="csrf-token"]').attr('content');

    /* ── UI state helpers ─────────────────────────────────────── */
    function showDetails() {
        $('#prev_record').show();
        $('#current_details').show();
        $('#tc-form-wrapper').show();
    }

    function hideDetails() {
        $('#prev_record').hide();
        $('#current_details').hide();
        $('#tc-form-wrapper').hide();
        // $('#status-message').html('');
    }

    function clearTcErrors() {
        $('#tc-form .is-invalid').removeClass('is-invalid');
        $('#tc-form .server-error').remove();
    }

    function showTcErrors(errors) {
        var fieldMap = {
            'reason'  : '#reason',
            'ref_no'  : '#ref_no',
            'tc_date' : '#tc-date'
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
    }

    function validateSearchForm() {
        var srno = $('#srno').val().trim();
        if (!srno) {
            $('#srno').addClass('is-invalid');
            if (!$('#srno').siblings('.server-error').length) {
                $('#srno').after('<span class="invalid-feedback server-error fw-bold" role="alert">Please enter a SRNO.</span>');
            }
            return false;
        }
        $('#srno').removeClass('is-invalid').siblings('.server-error').remove();
        return true;
    }

    /* ── Fetch current student details ───────────────────────── */
    function getStCurrentDetails(srno) {
        $.ajax({
            url     : '{{ route('admin.reports.tcStCurrentDetails') }}',
            type    : 'GET',
            dataType: 'json',
            data    : { srno: srno },

            success: function (response) {
                if (response.status !== 'success') {
                    console.error('Current details error:', response);
                    return;
                }

                $.each(response.tables, function (i, table) {
                    if (table.title === 'Attendance') {
                        var date = (table.data && table.data.date) ? table.data.date : 'No date available';
                        $('#last-attendance-date').text(date);
                        return;
                    }

                    if (!table.data || !table.data.length) return;

                    var rowData    = table.data[0];
                    var headerHtml = '<tr>';
                    $.each(table.headers, function (j, header) {
                        headerHtml += '<th>' + header + '</th>';
                    });
                    headerHtml += '</tr>';

                    var rowHtml = '<tr>';

                    switch (table.title) {
                        case 'Student Details':
                            rowHtml +=
                                '<td>' + (rowData.srno      || '')    + '</td>' +
                                '<td>' + (rowData.name      || '')    + '</td>' +
                                '<td>' + (rowData.dob       || 'N/A') + '</td>' +
                                '<td>' + (rowData.address   || '')    + '</td>' +
                                '<td>' + (rowData.category  || '')    + '</td>' +
                                '<td>' + (rowData.email     || '')    + '</td>' +
                                '<td>' + (rowData.mobile    || 'N/A') + '</td>';
                            rowHtml += '</tr>';
                            $('#st-details-head').html(headerHtml);
                            $('#st-details-body').html(rowHtml);
                            break;

                        case 'Parent Details':
                            rowHtml +=
                                '<td>' + (rowData.father_name       || '')    + '</td>' +
                                '<td>' + (rowData.mother_name       || '')    + '</td>' +
                                '<td>' + (rowData.address           || '')    + '</td>' +
                                '<td>' + (rowData.father_mobile     || 'N/A') + '</td>' +
                                '<td>' + (rowData.mother_mobile     || 'N/A') + '</td>' +
                                '<td>' + (rowData.father_occupation || '')    + '</td>' +
                                '<td>' + (rowData.mother_occupation || '')    + '</td>';
                            rowHtml += '</tr>';
                            $('#parent-details-head').html(headerHtml);
                            $('#parent-details-body').html(rowHtml);
                            break;

                        case 'Academic Details':
                            rowHtml +=
                                '<td>' + (rowData.session        || '')    + '</td>' +
                                '<td>' + (rowData.class          || '')    + '</td>' +
                                '<td>' + (rowData.section        || '')    + '</td>' +
                                '<td>' + (rowData.rollno         || '')    + '</td>' +
                                '<td>' + (rowData.gender         || '')    + '</td>' +
                                '<td>' + (rowData.religion       || '')    + '</td>' +
                                '<td>' + (rowData.admission_date || 'N/A') + '</td>';
                            rowHtml += '</tr>';
                            $('#academic-details-head').html(headerHtml);
                            $('#academic-details-body').html(rowHtml);
                            break;
                    }
                });
            },

            error: function () {
                alert('Failed to retrieve student details. Please try again.');
            }
        });
    }

    /* ── Fetch previous records ───────────────────────────────── */
    function getPreviousRecords(srno) {
        var sessionId = $('#current_session').val();

        $.ajax({
            url     : '{{ route('admin.reports.tcStPreviousDetails') }}',
            type    : 'GET',
            dataType: 'json',
            data    : { srno: srno, session: sessionId },

            success: function (response) {
                if (response.status !== 'success') {
                    console.error('Previous records error:', response);
                    return;
                }

                var table = (response.tables && response.tables[2]) ? response.tables[2] : null;

                if (!table) {
                    $('#previous-header').html('<tr><th>No previous record.</th></tr>');
                    $('#previous-body').html('');
                    return;
                }

                var headerHtml = '<tr>';
                $.each(table.headers, function (i, header) {
                    headerHtml += '<th>' + header + '</th>';
                });
                headerHtml += '</tr>';

                var bodyHtml = '';
                $.each(table.data, function (i, item) {
                    bodyHtml +=
                        '<tr>' +
                            '<td>' + (item.session        || '')    + '</td>' +
                            '<td>' + (item.class          || '')    + '</td>' +
                            '<td>' + (item.section        || '')    + '</td>' +
                            '<td>' + (item.rollno         || '')    + '</td>' +
                            '<td>' + (item.gender         || '')    + '</td>' +
                            '<td>' + (item.religion       || '')    + '</td>' +
                            '<td>' + (item.admission_date || 'N/A') + '</td>' +
                        '</tr>';
                });

                $('#previous-header').html(headerHtml);
                $('#previous-body').html(bodyHtml || '<tr><td colspan="7" class="text-center">No records found.</td></tr>');
            },

            error: function () {
                alert('Failed to retrieve previous records. Please try again.');
            }
        });
    }

    /* ── Fetch status message ─────────────────────────────────── */
    // getStatusMessage is the gatekeeper — details are only shown
    // if the student exists. getPreviousRecords + getStCurrentDetails
    // are fired from inside the success callback, not in parallel,
    // so there is no blink and no showing data for an invalid SRNO.
    function getStatusMessage(srno) {
        $('#loader').show();
        hideDetails();
        $('#status-message').html('');

        $.ajax({
            url     : '{{ route('admin.reports.tcStudentStatusMessages') }}',
            type    : 'GET',
            dataType: 'json',
            data    : { srno: srno },

            success: function (response) {
                var data = response.data || {};
                // Backend returns error string when student not found,
                // empty string '' when valid — check explicitly for non-empty error
                if (data.error) {
                    $('#status-message').html(
                        '<div class="alert alert-danger fw-bold">' + data.error + '</div>'
                    );
                    hideDetails();
                    return;
                }

                // Show message — warn if backend returned no message (shouldn't happen after fix)
                var message = (data.message && data.message !== '')
                    ? data.message
                    : 'Student record found.';

                $('#status-message').html(
                    '<div class="alert alert-warning fw-bold">' + message + '</div>'
                );

                showDetails();
                getPreviousRecords(srno);
                getStCurrentDetails(srno);
            },

            error: function () {
                $('#status-message').html(
                    '<div class="alert alert-danger">Failed to retrieve student status. Please try again.</div>'
                );
                hideDetails();
            },

            complete: function () {
                $('#loader').hide();
            }
        });
    }

    /* ── Shared TC AJAX handler ───────────────────────────────── */
    function submitTc(url, method) {
        var srno    = $('#srno').val().trim();
        var reason  = $('#reason').val().trim();
        var refNo   = $('#ref_no').val().trim();
        var tcDate  = $('#tc-date').val();

        if (!srno) return;

        clearTcErrors();
        $('#loader').show();

        var requestData = { srno: srno };

        if (method === 'POST') {
            requestData.reason  = reason;
            requestData.ref_no  = refNo;
            requestData.tc_date = tcDate;
            requestData._token  = csrfToken;
        }

        $.ajax({
            url     : url,
            type    : method,
            data    : requestData,
            dataType: 'json',

            success: function (response) {
                $('#loader').hide();
                if (response.print_url) {
                    window.location.href = response.print_url;
                }
            },

            error: function (xhr) {
                $('#loader').hide();
                var json         = xhr.responseJSON || {};
                var errors       = json.message      || {};
                var errorMessage = json.errorMessage  || null;

                clearTcErrors();

                if (errors && typeof errors === 'object') {
                    showTcErrors(errors);
                }

                if (errorMessage) {
                    $('#error-tc').text(errorMessage).show();
                    $('#status-message').html('');
                    hideDetails();
                } else {
                    $('#error-tc').text('').hide();
                    showDetails();
                }

                console.error('TC error:', xhr);
            }
        });
    }

    /* ── Event: Show Details button ───────────────────────────── */
    $('#show-report').on('click', function () {
        if (!validateSearchForm()) return;

        var srno = $('#srno').val().trim();
        $('#error-tc').text('').hide();

        // getStatusMessage is the gatekeeper:
        // it shows/hides details and fires sub-requests internally
        getStatusMessage(srno);

        // Scroll to status area once loader appears
        $('html, body').animate({ scrollTop: $('#status-message').offset().top - 20 }, 400);
    });

    /* ── Event: SRNO input change → clear error ───────────────── */
    $('#srno').on('input', function () {
        $(this).removeClass('is-invalid').siblings('.server-error').remove();
        $('#error-tc').text('').hide();
    });

    /* ── Event: TC buttons (single delegated handler) ─────────── */
    $(document).on('click', '.tc-btn', function () {
        var url    = $(this).data('url');
        var method = $(this).data('method');
        submitTc(url, method);
    });

});
</script>
@endsection