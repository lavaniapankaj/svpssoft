@extends('admin.index')

@section('sub-content')
    <div class="container">

        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">{{ __('Issue TC to Student') }}
                        <a href="{{ route('admin.reports') }}" class="btn btn-warning btn-sm" style="float: right;">Back</a>
                    </div>

                    <div class="card-body">
                        <form action="" method="get" id="class-form">
                            <div class="row mt-2">
                                <input type="hidden" name="current_session" value='' id="current_session">
                                <div class="form-group col-md-6">
                                    <label for="srno" class="mt-2">SRNO <span class="text-danger">*</span></label>
                                    <input type="text" name="srno" id="srno" class="form-control" value=""
                                        required>
                                    <span class="invalid-feedback form-invalid fw-bold srno-error" role="alert"></span>
                                </div>
                                <div class="form-group col-md-6 mt-2">
                                    <a href="{{ route('admin.student-master.search') }}" class="btn btn-sm btn-info mt-4"
                                        target="_blank" rel="noopener noreferrer">To Know SRNO Click Here</a>
                                </div>
                            </div>
                            <div class="mt-3">
                                <button class="btn btn-primary" type="button" id="show-report">Show Details</button>
                                <span class="text-danger error-tc"></span>
                            </div>
                        </form>

                        {{-- previous records  --}}
                        <div class="mt-4" id='status-message'></div>

                        <div class="table mt-4" id="prev_record">
                            <h4 class="text-danger fw-bold">Previous Details</h4>
                            <table id="example" class="table table-striped table-bordered">
                                <thead id="previous-header">

                                </thead>
                                <tbody id="previous-body"></tbody>

                            </table>


                        </div>

                        {{-- Current Details   --}}

                        <div class="table table-responsive" id="current_details">
                            <h4 class="text-danger fw-bold">Details</h4>
                            <table id="example" class="table table-striped table-bordered">
                                <thead id="st-details-body">

                                </thead>
                                <tbody id="st-details-body"></tbody>

                            </table>
                            <table id="example" class="table table-striped table-bordered">
                                <thead id="parent-details-body">

                                </thead>
                                <tbody id="parent-details-body"></tbody>

                            </table>
                            <table id="example" class="table table-striped table-bordered">
                                <thead id="academic-details-body">
                                </thead>
                                <tbody id="academic-details-body"></tbody>

                            </table>
                            <table id="example" class="table table-striped table-bordered">
                                <thead id="last-attendance">
                                    <tr>
                                        <th>Last Attendance(Present) Date</th>
                                        <th id="last-attendance-date"></th>
                                    </tr>
                                </thead>

                            </table>


                        </div>
                        <div id="student-details">

                        </div>
                        <div id="parents-details"></div>
                        <div id="attendance-date"></div>

                        <div class="row mt-4 tc-form">
                            <form action="" method="post" id="tc-form">
                                @csrf
                                <div class="row">

                                    <div class="form-group col-md-4">
                                        <label for="reason" class="mt-2">Enter Reason <span
                                                class="text-danger">*</span></label>
                                        <input type="text" name="reason" id="reason" class="form-control"
                                            value="" required>
                                        <span class="invalid-feedback form-invalid fw-bold reason-error"
                                            role="alert"></span>
                                    </div>
                                    <div class="form-group col-md-4">
                                        <label for="ref_no" class="mt-2">Enter Ref TC No. <span
                                                class="text-danger">*</span></label>
                                        <input type="text" name="ref_no" id="ref_no" class="form-control"
                                            value="" required>
                                        <span class="invalid-feedback form-invalid fw-bold ref-no-error"
                                            role="alert"></span>
                                    </div>
                                    <div class="form-group col-md-4">
                                        <label for="tc-date" class="mt-2">TC Issue Date<span
                                                class="text-danger">*</span></label>
                                        <input type="date" name="tc-date" id="tc-date" class="form-control"
                                            value="" required>
                                        <span class="invalid-feedback form-invalid fw-bold tc-date-error"
                                            role="alert"></span>
                                    </div>
                                </div>
                                <div class="mt-3 d-flex justify-content-evenly">
                                    <button class="btn btn-primary" type="button" id="tc-btn-1">Issue TC (Last
                                        Passed)</button>
                                    <button class="btn btn-primary" type="button" id="tc-btn-2">Issue TC
                                        (Passed)</button>
                                    <button class="btn btn-primary" type="button" id="tc-btn-3">Issue TC
                                        (Studying)</button>
                                    <button class="btn btn-primary" type="button" id="tc-btn-4">Issue
                                        (Reprint)</button>
                                </div>
                                <div class="row mt-4">
                                    <div class="col-md-3 text-center fw-bold">
                                        <h5>^</h5>
                                    </div>
                                    <div class="col-md-3 text-center fw-bold">
                                        <h5>^</h5>
                                    </div>
                                    <div class="col-md-3 text-center fw-bold">
                                        <h5>^</h5>
                                    </div>
                                    <div class="col-md-3 text-center fw-bold">
                                        <h5>^</h5>
                                    </div>
                                </div>
                                <div class="row mt-4 text-center text-danger">
                                    <div class="col-md-3 text-wrap">
                                        <p>Issue TC to student which is present/absent in new class but want to issue
                                            previous passed class TC.
                                            OR
                                            to left out student.</p>
                                    </div>
                                    <div class="col-md-3 text-wrap">
                                        <p>Issue TC to student which have passed last class.</p>
                                    </div>
                                    <div class="col-md-3 text-wrap">
                                        <p>Issue TC to student which is present/absent in new class but want to issue
                                            studying class TC.
                                            OR
                                            to left out student.</p>
                                    </div>
                                    <div class="col-md-3 text-wrap">
                                        <p>Reprint / ReIssue TC to student.(if issued already).</p>
                                    </div>
                                </div>
                            </form>
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

            let globalPrevSrno = null;

            let prevRecord = $('#prev_record');
            let currentDetail = $('#current_details');
            let tcForm = $('.tc-form');
            let tcError = $('.error-tc');
            prevRecord.hide();
            currentDetail.hide();
            tcForm.hide();


            // Current Details

            function getStCurrentDetails(srno) {
                $.ajax({
                    url: '{{ route('admin.reports.tcStCurrentDetails') }}',
                    method: 'GET',
                    data: {
                        srno: srno
                    },
                    dataType: 'JSON',
                    success: function(response) {
                        if (response.status === 'success') {
                            // Process each table in the response
                            response.tables.forEach(table => {
                                let tableHtml = '';
                                let headerHtml = '';

                                // Create table headers
                                headerHtml = '<tr>';
                                table.headers.forEach(header => {
                                    headerHtml += `<th>${header}</th>`;
                                });
                                headerHtml += '</tr>';

                                // Create table rows with data
                                if (table.title === 'Attendance') {
                                    // Handle attendance date separately
                                    if (table.data && table.data.date) {
                                        $('#last-attendance-date').text(table.data.date);
                                    } else {
                                        $('#last-attendance-date').text('No date available');
                                    }
                                    // Skip the rest of the processing for attendance
                                    return;
                                }
                                tableHtml = '<tr>';
                                if (table.data.length > 0) {
                                    const rowData = table.data[0];
                                    switch (table.title) {
                                        case 'Student Details':
                                            tableHtml += `
                                                <td>${rowData.srno}</td>
                                                <td>${rowData.name}</td>
                                                <td>${rowData.dob ?? 'N/A'}</td>
                                                <td>${rowData.address}</td>
                                                <td>${rowData.category}</td>
                                                <td>${rowData.email}</td>
                                                <td>${rowData.mobile ?? 'N/A'}</td>
                                            `;
                                            break;

                                        case 'Parent Details':
                                            tableHtml += `
                                                    <td>${rowData.father_name}</td>
                                                    <td>${rowData.mother_name}</td>
                                                    <td>${rowData.address}</td>
                                                    <td>${rowData.father_mobile ?? 'N/A'}</td>
                                                    <td>${rowData.mother_mobile ?? 'N/A'}</td>
                                                    <td>${rowData.father_occupation}</td>
                                                    <td>${rowData.mother_occupation}</td>
                                                `;
                                            break;

                                        case 'Academic Details':
                                            tableHtml += `
                                                <td>${rowData.session}</td>
                                                <td>${rowData.class}</td>
                                                <td>${rowData.section}</td>
                                                <td>${rowData.rollno}</td>
                                                <td>${rowData.gender}</td>
                                                <td>${rowData.religion}</td>
                                                <td>${rowData.admission_date ?? 'N/A'}</td>
                                            `;
                                            break;
                                           
                                    }
                                    tableHtml += '</tr>';
                                }

                                // Insert the headers and data into appropriate tables
                                switch (table.title) {
                                    case 'Student Details':
                                        $('#st-details-body').html(headerHtml + tableHtml);
                                        break;
                                    case 'Parent Details':
                                        $('#parent-details-body').html(headerHtml + tableHtml);
                                        break;
                                    case 'Academic Details':
                                        $('#academic-details-body').html(headerHtml +
                                            tableHtml);
                                        break;

                                }
                            });
                        } else {
                            console.error('Error in API response:', response);
                        }
                    },
                    error: function(xhr) {
                        alert('Failed to retrieve data. Please try again.');
                    }
                });
            }

            // Previous Records

            function getPreviousRecords(srno) {
                let sessionId = $('#current_session').val();
                $.ajax({
                    url: '{{ route('admin.reports.tcStPreviousDetails') }}', // The API endpoint
                    method: 'GET',
                    data: {
                        srno: srno,
                        session: sessionId,
                    }, // Pass srno as part of the GET request
                    dataType: 'JSON',
                    success: function(response) {
                        let tblheaders = '';
                        let tblbody = '';

                        // Ensure the response is structured as expected
                        if (response.status === 'success') {
                            if(response.tables[2])
                            {
                                let headers = response.tables[2].headers;
                                let data = response.tables[2].data;

                                // Generate table headers
                                tblheaders = '<tr>';
                                $.each(headers, function(index, header) {
                                    tblheaders +=
                                        `<th>${header}</th>`; // Wrap headers with <th>
                                });
                                tblheaders += '</tr>';

                                // Generate table body rows
                                $.each(data, function(index, item) {
                                    tblbody += `<tr>
                                        <td>${item.session}</td>
                                        <td>${item.class}</td>
                                        <td>${item.section}</td>
                                        <td>${item.rollno}</td>
                                        <td>${item.gender}</td>
                                        <td>${item.religion}</td>
                                        <td>${item.admission_date ?? 'N/A'}</td>
                                    </tr>`;
                                });

                                // Populate the table in HTML
                                $('#previous-header').html(tblheaders); // Append table headers
                                $('#previous-body').html(tblbody); // Append table body
                            }else{
                               $('#previous-header').html("No previous record.");
                            }
                        } else {
                            console.log('No data found for the given SRNO');
                        }
                    },
                    error: function(xhr) {
                        alert('Failed to retrieve data. Please try again.');
                    }
                });
            }
            // Previous Records

            function getStatusMessage(srno) {
                $.ajax({
                    url: '{{ route('admin.reports.tcStudentStatusMessages') }}', // The API endpoint
                    method: 'GET',
                    data: {
                        srno: srno
                    }, // Pass srno as part of the GET request
                    dataType: 'JSON',
                    success: function(response) {
                        let message = null;
                        if (response.data.error) {
                            message = response.data.error;
                            prevRecord.hide();
                            currentDetail.hide();
                            tcForm.hide();
                        } else {

                            message = response.data.message;
                        }

                        $('#status-message').html(
                            `<span class="text-danger fw-bold text-center">${message}</span>`);

                    },
                    error: function(xhr) {
                        alert('Failed to retrieve data. Please try again.');
                    }
                });
            }
            $('#show-report').click(function() {
                if ($('#class-form').valid()) {
                    tcError.hide().html('');
                    prevRecord.show();
                    currentDetail.show();
                    tcForm.show();

                    try {
                        let srno = $('#srno').val(); // Make sure to get the srno value
                        if (!srno) {
                            throw new Error('SRNO is not defined');
                        }

                        getStatusMessage(srno);
                        getPreviousRecords(srno);
                        getStCurrentDetails(srno);
                    } catch (error) {
                        console.error('Error in show-report click handler:', error);
                    }
                }
            });



            // TC Button 1 Click

            $('#tc-btn-1').click(function() {

                let srno = $('#srno').val();
                let tcReason = $('#reason').val();
                let tcRefNo = $('#ref_no').val();
                let tcDate = $('#tc-date').val();
                if (!srno) {
                    return;
                }
                loader.show();
                $.ajax({
                    url: '{{ route('admin.reports.tcToTheStudent') }}',
                    type: 'POST',
                    data: {
                        _token: $('meta[name=csrf-token]').attr('content'),
                        srno: srno,
                        reason: tcReason,
                        ref_no: tcRefNo,
                        tc_date: tcDate,
                    },
                    success: function(response) {
                        console.log('TC 1 Issued successfully:', response);
                        loader.hide();
                        window.location.href = response.print_url;
                    },
                    error: function(data, xhr) {
                        let message = data.responseJSON.message;
                        let errorMessages = data.responseJSON.errorMessage;
                        $('.reason-error').hide().html('');
                        $('.ref-no-error').hide().html('');
                        $('.tc-date-error').hide().html('');
                        if (message) {
                            if (message.reason) {
                                $('.reason-error').show().html(message.reason);
                            }
                            if (message.ref_no) {
                                $('.ref-no-error').show().html(message.ref_no);
                            }
                            if (message.tc_date) {
                                $('.tc-date-error').show().html(message.tc_date);
                            }
                        }

                        if (errorMessages) {
                            tcError.show().html(errorMessages);
                            $('#status-message').html('');
                            prevRecord.hide();
                            currentDetail.hide();
                            tcForm.hide();
                        } else {
                            tcError.hide().html('');
                            prevRecord.show();
                            currentDetail.show();
                            tcForm.show();

                        }
                        console.error(xhr);
                    }


                });
            });

            // TC Button 2 Click

            $('#tc-btn-2').click(function() {

                let srno = $('#srno').val();
                let tcReason = $('#reason').val();
                let tcRefNo = $('#ref_no').val();
                let tcDate = $('#tc-date').val();
                if (!srno) {
                    return;
                }
                loader.show();
                $.ajax({
                    url: '{{ route('admin.reports.tcToTheStudentBtn2') }}',
                    type: 'POST',
                    data: {
                        _token: $('meta[name=csrf-token]').attr('content'),
                        srno: srno,
                        reason: tcReason,
                        ref_no: tcRefNo,
                        tc_date: tcDate,
                    },
                    success: function(response) {
                        console.log('TC 2 Issued successfully:', response);
                        loader.hide();
                        window.location.href = response.print_url;
                    },
                    error: function(data, xhr) {
                        let message = data.responseJSON.message;
                        let errorMessages = data.responseJSON.errorMessage;
                        $('.reason-error').hide().html('');
                        $('.ref-no-error').hide().html('');
                        $('.tc-date-error').hide().html('');
                        if (message) {
                            if (message.reason) {
                                $('.reason-error').show().html(message.reason);
                            }
                            if (message.ref_no) {
                                $('.ref-no-error').show().html(message.ref_no);
                            }
                            if (message.tc_date) {
                                $('.tc-date-error').show().html(message.tc_date);
                            }
                        }

                        if (errorMessages) {
                            tcError.show().html(errorMessages);
                            $('#status-message').html('');
                            prevRecord.hide();
                            currentDetail.hide();
                            tcForm.hide();
                        } else {
                            tcError.hide().html('');
                            prevRecord.show();
                            currentDetail.show();
                            tcForm.show();
                        }
                        console.error(xhr);
                    }


                });
            });

            // TC Button 3 Click

            $('#tc-btn-3').click(function() {

                let srno = $('#srno').val();
                let tcReason = $('#reason').val();
                let tcRefNo = $('#ref_no').val();
                let tcDate = $('#tc-date').val();
                if (!srno) {
                    return;
                }
                loader.show();
                $.ajax({
                    url: '{{ route('admin.reports.tcToTheStudentBtn3') }}',
                    type: 'POST',
                    data: {
                        _token: $('meta[name=csrf-token]').attr('content'),
                        srno: srno,
                        reason: tcReason,
                        ref_no: tcRefNo,
                        tc_date: tcDate,
                    },
                    success: function(response) {
                        loader.hide();
                         window.location.href = response.print_url;
                    },
                    error: function(data, xhr) {
                        let message = data.responseJSON.message;
                        let errorMessages = data.responseJSON.errorMessage;
                        $('.reason-error').hide().html('');
                        $('.ref-no-error').hide().html('');
                        $('.tc-date-error').hide().html('');
                        if (message) {
                            if (message.reason) {
                                $('.reason-error').show().html(message.reason);
                            }
                            if (message.ref_no) {
                                $('.ref-no-error').show().html(message.ref_no);
                            }
                            if (message.tc_date) {
                                $('.tc-date-error').show().html(message.tc_date);
                            }
                        }

                        if (errorMessages) {
                            tcError.show().html(errorMessages);
                            $('#status-message').html('');
                            prevRecord.hide();
                            currentDetail.hide();
                            tcForm.hide();
                        } else {
                            tcError.hide().html('');
                            prevRecord.show();
                            currentDetail.show();
                            tcForm.show();
                        }
                        console.error(xhr);
                    }


                });
            });

            // TC Button 4 Click

            $('#tc-btn-4').click(function() {

                let srno = $('#srno').val();
                let tcReason = $('#reason').val();
                let tcRefNo = $('#ref_no').val();
                let tcDate = $('#tc-date').val();
                if (!srno) {
                    return;
                }
                loader.show();
                $.ajax({
                    url: '{{ route('admin.reports.tcToTheStudentBtn4') }}',
                    type: 'GET',
                    data: {
                        srno: srno,
                    },
                    success: function(response) {
                        loader.hide();
                        window.location.href = response.print_url;
                    },
                    error: function(data, xhr) {
                        let message = data.responseJSON.message;
                        let errorMessages = data.responseJSON.errorMessage;
                        $('.reason-error').hide().html('');
                        $('.ref-no-error').hide().html('');
                        $('.tc-date-error').hide().html('');
                        if (message) {
                            if (message.reason) {
                                $('.reason-error').show().html(message.reason);
                            }
                            if (message.ref_no) {
                                $('.ref-no-error').show().html(message.ref_no);
                            }
                            if (message.tc_date) {
                                $('.tc-date-error').show().html(message.tc_date);
                            }
                        }

                        if (errorMessages) {
                            tcError.show().html(errorMessages);
                            $('#status-message').html('');
                            prevRecord.hide();
                            currentDetail.hide();
                            tcForm.hide();
                        } else {
                            tcError.hide().html('');
                            prevRecord.show();
                            currentDetail.show();
                            tcForm.show();
                        }
                        console.error(xhr);
                    }


                });
            });
        });
    </script>
@endsection
