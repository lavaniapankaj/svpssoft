@extends('admin.index')

@section('sub-content')
    <div class="container">

        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">{{ __('Fee Report (Mercy)') }}
                        <a href="{{ route('admin.reports') }}" class="btn btn-warning btn-sm" style="float: right;">Back</a>
                    </div>

                    <div class="card-body">
                        <form action="" method="get" id="class-form">
                            <div class="row mt-2">
                                <div class="form-group col-md-6">
                                    <label for="session_id" class="mt-2">Session <span
                                            class="text-danger">*</span></label>
                                    <select name="session_id" id="session_id" class="form-control" required>
                                        <option value="">Select session</option>
                                        @if (count($sessions) > 0)
                                            @foreach ($sessions as $key => $session)
                                                <option value="{{ $key }}"
                                                    {{ old('session_id') == $key ? 'selected' : '' }}>{{ $session }}
                                                </option>
                                            @endforeach
                                        @else
                                            <option value="">No Session Found</option>
                                        @endif
                                    </select>

                                    <span class="invalid-feedback form-invalid fw-bold session-error" role="alert">

                                    </span>

                                    <img src="{{ config('myconfig.myloader') }}" alt="Loading..." class="loader"
                                        id="loader" style="display:none; width:10%;">
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="fee_type" class="mt-2">Fee Type <span class="text-danger">*</span></label>
                                    <select name="fee_type" id="fee_type" class="form-control mx-1" required>
                                        <option value="">Select Fee Type</option>
                                        <option value="1">Academic Fee</option>
                                        <option value="2">transport Fee</option>
                                    </select>
                                    <span class="invalid-feedback form-invalid fw-bold fee-type-error"
                                        role="alert"></span>
                                </div>
                            </div>
                            <div class="row">
                                <div class="form-group col-md-4">
                                    <label for="report_type" class="mt-2">Report Type <span
                                            class="text-danger">*</span></label>
                                    <select name="report_type" id="report_type" class="form-control mx-1" required>
                                        <option value="">Select Report Type</option>
                                        <option value="1">Summary Report</option>
                                        <option value="2">Detailed Report</option>
                                    </select>
                                    <span class="invalid-feedback form-invalid fw-bold report-type-error"
                                        role="alert"></span>
                                </div>
                                <div class="form-group col-md-4">
                                    <label for="start-date" class="mt-2">Start Date<span
                                            class="text-danger">*</span></label>
                                    <input type="date" value="" id="start-date" class="form-control mx-1">
                                    <span class="invalid-feedback form-invalid fw-bold start-date-error"
                                        role="alert"></span>
                                </div>
                                <div class="form-group col-md-4">
                                    <label for="end-date" class="mt-2">End Date<span class="text-danger">*</span></label>
                                    <input type="date" value="" id="end-date" class="form-control mx-1">
                                    <span class="invalid-feedback form-invalid fw-bold end-date-error"
                                        role="alert"></span>
                                </div>
                            </div>

                            <div class="mt-3">
                                <button class="btn btn-primary" type="button" id="show-report">Show Fee Report</button>
                                <span class="fst-italic fw-bold text-decoration-underline" id="summary-report"></span>
                            </div>
                        </form>
                        <div class="super-div">
                            <table class="table">
                                <thead>
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
                                <tbody id="report-body">
                                </tbody>
                            </table>
                            <div id="std-pagination" class="mt-2"></div>
                            <div class="export-div">
                                <button type="button" class="btn btn-info" id="export-button">Export</button>
                            </div>
                        </div>

                    </div>

                </div>
            </div>
        </div>
    </div>
@endsection
@section('admin-scripts')
    <script>
        $(document).ready(function() {
           $('.super-div').hide();

            function getReport(page = 1) {
                let sessionId = $('#session_id').val();
                let feeTypeId = $('#fee_type').val();
                let reportTypeId = $('#report_type').val();
                let startDateId = $('#start-date').val();
                let endDateId = $('#end-date').val();

                if ($('#class-form').valid()) {
                    // $('.super-div').show();
                    $.ajax({
                        url: '{{ route('admin.reports.feeReportMercyAdmin') }}',
                        type: 'GET',
                        dataType: 'JSON',
                        data: {
                            session: sessionId,
                            feeType: feeTypeId,
                            reportType: reportTypeId,
                            startDate: startDateId,
                            endDate: endDateId,
                            page: page,

                        },
                        success: function(response) {
                            let tableHtml = '';
                            if (response.data) {
                                if (reportTypeId == 1) {
                                    $('.super-div').hide();
                                    $('#summary-report').show().html('Summery Amount is :' + response
                                        .data[0].summeryAmount);
                                } else {
                                    $('#summary-report').hide().html('');
                                    $('.super-div').show();
                                    const startIndex = (response.pagination.current_page - 1) * response
                                        .pagination.per_page;

                                    // Separate students into junior and senior sections
                                    let juniorStudents = [];
                                    let seniorStudents = [];
                                    let juniorTotal = 0;
                                    let seniorTotal = 0;

                                    // Group students
                                    Object.keys(response.data).forEach(key => {
                                        if (key === 'grandTotal') return;
                                        const studentData = response.data[key];
                                        if (studentData.school == 1) {
                                            juniorStudents.push(studentData);
                                            juniorTotal += parseFloat(studentData.feeDetails
                                                .amount || 0);
                                        } else {
                                            seniorStudents.push(studentData);
                                            seniorTotal += parseFloat(studentData.feeDetails
                                                .amount || 0);
                                        }
                                    });

                                    // Display Junior Section
                                    if (juniorStudents.length > 0) {
                                        tableHtml += `<tr class="fw-bold">
                                            <td colspan="10" class="text-start text-danger">Junior Section</td>
                                        </tr>`;

                                        juniorStudents.forEach((studentData, index) => {
                                            tableHtml += `<tr>
                                                <td>${startIndex + index + 1}</td>
                                                <td>${studentData.feeDetails.pay_date || '-'}</td>
                                                <td>${studentData.feeDetails.ref_slip_no || '-'}</td>
                                                <td>${studentData.feeDetails.recp_no || '-'}</td>
                                                <td>${studentData.feeDetails.srno || '-'}</td>
                                                <td>${studentData.class_name || '-'}</td>
                                                <td>${studentData.section_name || '-'}</td>
                                                <td>${studentData.name || '-'}</td>
                                                <td>${studentData.f_name || '-'}</td>
                                                <td>${studentData.feeDetails.amount || 0}</td>
                                            </tr>`;
                                        });
                                        // Add Junior Section Total
                                        tableHtml += `<tr class="fw-bold">
                                                <td colspan="9" class="text-end">Junior Section Total:</td>
                                                <td>${juniorTotal}</td>
                                            </tr>`;
                                    }

                                    // Display Senior Section
                                    if (seniorStudents.length > 0) {
                                        tableHtml += `<tr class="fw-bold">
                                            <td colspan="10" class="text-start text-danger">Senior Section</td>
                                        </tr>`;

                                        seniorStudents.forEach((studentData, index) => {
                                            tableHtml += `<tr>
                                            <td>${startIndex + juniorStudents.length + index + 1}</td>
                                            <td>${studentData.feeDetails.pay_date || '-'}</td>
                                            <td>${studentData.feeDetails.ref_slip_no || '-'}</td>
                                            <td>${studentData.feeDetails.recp_no || '-'}</td>
                                            <td>${studentData.feeDetails.srno || '-'}</td>
                                            <td>${studentData.class_name || '-'}</td>
                                            <td>${studentData.section_name || '-'}</td>
                                            <td>${studentData.name || '-'}</td>
                                            <td>${studentData.f_name || '-'}</td>
                                            <td>${studentData.feeDetails.amount || 0}</td>
                                        </tr>`;
                                        });
                                        // Add Senior Section Total
                                        tableHtml += `<tr class="fw-bold">
                                                <td colspan="9" class="text-end">Senior Section Total:</td>
                                                <td>${seniorTotal}</td>
                                            </tr>`;
                                    }

                                    // Add grand total row
                                    tableHtml += `<tr class="fw-bold">
                                        <td colspan="9" class="text-end">Grand Total:</td>
                                        <td>${response.data.grandTotal}</td>
                                    </tr>`;
                                }

                            } else {
                                tableHtml =
                                    '<tr><td colspan="10" class="text-center fst-italic fw-bold text-decoration-underline text-danger">No Records Found</td></tr>';
                            }

                            // Update pagination using the pagination object
                            updatePaginationControls(response.pagination);
                            $('#report-body').html(tableHtml);
                        },
                        complete: function() {
                            loader.hide();
                        },
                        error: function(data, xhr) {
                            let message = data.responseJSON.message;

                            if (message.session) {
                                $('.session-error').show().html(message.session);
                            }
                            if (message.feeType) {
                                $('.fee-type-error').show().html(message.feeType);
                            }
                            if (message.reportType) {
                                $('.report-type-error').show().html(message.reportType);
                            }
                            if (message.startDate) {
                                $('.start-date-error').show().html(message.startDate);
                            }
                            if (message.endDate) {
                                $('.end-date-error').show().html(message.endDate);
                            }
                            console.log(xhr);

                        },
                    });
                }
            }

            $('#show-report').click(function() {
                getReport();

            });

            $('#class_id, #srno-type, #section_id').change(function() {
                $('.super-div').hide();

            });

            $(document).on('click', '#std-pagination .page-link', function(e) {
                e.preventDefault();
                let page = $(this).data('page');
                getReport(page);
            });

            /*
             * excel file
             */

            $('#export-button').on('click', function() {
                let sessionId = $('#session_id').val();
                let feeTypeId = $('#fee_type').val();
                let reportTypeId = $('#report_type').val();
                let startDateId = $('#start-date').val();
                let endDateId = $('#end-date').val();
                const exportUrl = "{{ route('admin.reports.adminMercyFee.excel') }}?session=" +
                    sessionId +
                    "&feeType=" + feeTypeId + "&reportType=" + reportTypeId + "&startDate=" + startDateId +
                    "&endDate=" + endDateId;
                window.location.href = exportUrl;
            });


        });
    </script>
@endsection
