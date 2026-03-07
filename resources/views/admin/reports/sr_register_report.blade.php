@extends('admin.index')

@section('sub-content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card border-0 bg-white">
                    <div class="card-header flex-wrap bg-white d-flex align-items-center justify-content-between">
                        <h5 class="mb-0 mt-0">{{ __('SR Register') }}</h5>
                        <a href="{{ route('admin.reports') }}" class="btn bg-light btn-sm">
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
                                    <label for="std-type" class="mt-2">
                                        Student Type <span class="text-danger">*</span>
                                    </label>
                                    <select name="std_type" id="std-type" class="form-control" required>
                                        <option value="1,2,4,5">All</option>
                                        <option value="1">Present</option>
                                        <option value="4">TC Issued</option>
                                        <option value="5">Left Out</option>
                                    </select>
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
                        <div id="super-div" class="mt-2" style="display:none;">
                            <p id="total-records" class="mb-1">Total Records: 0</p>

                            <div class="table-responsive">
                                <table class="table table-bordered table-hover mb-1">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Sr. No.</th>
                                            <th>Class</th>
                                            <th>Student Name</th>
                                            <th>Father's Name</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="student-report-body">
                                        {{-- Populated via AJAX --}}
                                    </tbody>
                                </table>
                            </div>

                            <div id="admin-pagination" class="mb-4"></div>

                            {{-- ─── Previous Details ─── --}}
                            <div id="prev_record" style="display:none;">
                                <h4 class="text-danger fw-bold">Previous Details</h4>
                                <div id="prev-loader" class="text-center py-3" style="display:none;">
                                    <img src="{{ config('myconfig.myloader') }}" alt="Loading…" style="width:5%;">
                                </div>
                                <div id="prev-content">
                                    <div class="table-responsive">
                                        <table class="table table-striped table-bordered">
                                            <thead id="previous-header"></thead>
                                            <tbody id="previous-body"></tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            {{-- ─── Current Details ─── --}}
                            <div id="current_details" style="display:none;">
                                <h4 class="text-danger fw-bold">Current Details</h4>
                                <div id="current-loader" class="text-center py-3" style="display:none;">
                                    <img src="{{ config('myconfig.myloader') }}" alt="Loading…" style="width:5%;">
                                </div>
                                <div id="current-content">
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
                                </div>
                            </div>
                        </div>{{-- /#super-div --}}

                    </div>{{-- /.card-body --}}
                </div>{{-- /.card --}}
            </div>
        </div>
    </div>
@endsection

@section('admin-scripts')
<script>
$(function () {

    /* ── Fetch student list ───────────────────────────────────── */
    function getStdTable(page) {
        page = page || 1;
        var sessionId = $('#session_id').val();
        var stdType   = $('#std-type').val();

        $('#super-div').show();
        $('#loader').show();

        $.ajax({
            url      : '{{ route('admin.reports.reportSrRegisterWise') }}',
            type     : 'GET',
            dataType : 'json',
            data     : { session_id: sessionId, type: stdType, page: page },

            success: function (response) {
                var students = (response && response.data && response.data.data) ? response.data.data : [];
                var html = '';

                if (students.length) {
                    $.each(students, function (index, student) {
                        html +=
                            '<tr>' +
                                '<td>' + (student.srno       || '') + '</td>' +
                                '<td>' + (student.class_name || '') + '</td>' +
                                '<td>' + (student.name       || '') + '</td>' +
                                '<td>' + (student.f_name     || '') + '</td>' +
                                '<td>' +
                                    '<a href="#"' +
                                        ' class="btn btn-sm btn-icon p-1 view-student-btn"' +
                                        ' data-session="' + sessionId + '"' +
                                        ' data-srno="'    + student.srno + '">' +
                                        '<i class="mdi mdi-eye"' +
                                            ' data-bs-toggle="tooltip"' +
                                            ' data-bs-placement="top"' +
                                            ' title="View"></i>' +
                                    '</a>' +
                                '</td>' +
                            '</tr>';
                    });
                } else {
                    html = '<tr><td colspan="5" class="text-center">No data found</td></tr>';
                    $('#prev_record').hide();
                    $('#current_details').hide();
                }

                var total = (response && response.data && response.data.total) ? response.data.total : 0;
                $('#total-records').text('Total Records: ' + total);
                $('#student-report-body').html(html);

                // Global pagination helper
                adminUpdatePaginationControls(response.data);

                // Reset detail panels on new list load
                $('#prev_record').hide();
                $('#current_details').hide();
            },

            error: function (xhr) {
                console.error('SR Register list error:', xhr);
                $('#student-report-body').html(
                    '<tr><td colspan="5" class="text-center text-danger">Failed to load data. Please try again.</td></tr>'
                );
            },

            complete: function () {
                $('#loader').hide();
            }
        });
    }

    /* ── Fetch current student details ───────────────────────── */
    function getStCurrentDetails(srno) {
        $('#current-loader').show();
        $('#current-content').hide();

        $.ajax({
            url      : '{{ route('admin.reports.tcStCurrentDetails') }}',
            type     : 'GET',
            dataType : 'json',
            data     : { srno: srno },

            success: function (response) {
                if (response.status !== 'success') {
                    console.error('Current details error:', response);
                    return;
                }

                $.each(response.tables, function (i, table) {
                    if (table.title === 'Attendance') {
                        var date = (table.data && table.data.date) ? table.data.date : 'No date available';
                        $('#last-attendance-date').text(date);
                        return; // continue $.each
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
                                '<td>' + (rowData.srno      || '') + '</td>' +
                                '<td>' + (rowData.name      || '') + '</td>' +
                                '<td>' + (rowData.dob       || '') + '</td>' +
                                '<td>' + (rowData.address   || '') + '</td>' +
                                '<td>' + (rowData.category  || '') + '</td>' +
                                '<td>' + (rowData.email     || '') + '</td>' +
                                '<td>' + (rowData.mobile    || '') + '</td>';
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

            error: function (xhr) {
                console.error('Current details AJAX error:', xhr);
                alert('Failed to retrieve current details. Please try again.');
            },

            complete: function () {
                $('#current-loader').hide();
                $('#current-content').show();
            }
        });
    }

    /* ── Fetch previous records ───────────────────────────────── */
    function getPreviousRecords(srno, session) {
        $('#prev-loader').show();
        $('#prev-content').hide();

        $.ajax({
            url      : '{{ route('admin.reports.tcStPreviousDetails') }}',
            type     : 'GET',
            dataType : 'json',
            data     : { srno: srno, session: session },

            success: function (response) {
                if (response.status !== 'success') {
                    console.error('Previous records error:', response);
                    $('#previous-header').html('');
                    $('#previous-body').html('<tr><td class="text-center">No previous record found.</td></tr>');
                    return;
                }

                var table = (response.tables && response.tables[2]) ? response.tables[2] : null;

                if (!table) {
                    $('#previous-header').html('');
                    $('#previous-body').html('<tr><td class="text-center">No previous record.</td></tr>');
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

            error: function (xhr) {
                console.error('Previous records AJAX error:', xhr);
                alert('Failed to retrieve previous records. Please try again.');
            },

            complete: function () {
                $('#prev-loader').hide();
                $('#prev-content').show();
            }
        });
    }

    /* ── Event: Show Report button ────────────────────────────── */
    $('#show-report').on('click', function () {
        if ($('#session_id').val() === '') {
            alert('Please select a session.');
            return;
        }
        getStdTable(1);
    });

    /* ── Event: Filter change → reset results ─────────────────── */
    $('#session_id, #std-type').on('change', function () {
        $('#super-div').hide();
        $('#prev_record').hide();
        $('#current_details').hide();
    });

    /* ── Event: Pagination click ──────────────────────────────── */
    $(document).on('click', '#admin-pagination .page-link', function (e) {
        e.preventDefault();
        var page = $(this).data('page');
        if (page) {
            getStdTable(page);
        }
    });

    /* ── Event: View student button ───────────────────────────── */
    $(document).on('click', '.view-student-btn', function (e) {
        e.preventDefault();
        var srno    = $(this).data('srno');
        var session = $(this).data('session');

        $('#prev_record').show();
        $('#current_details').show();

        getPreviousRecords(srno, session);
        getStCurrentDetails(srno);

        // Scroll smoothly to the previous details section
        $('html, body').animate({
            scrollTop: $('#prev_record').offset().top - 20
        }, 500);
    });

});
</script>
@endsection