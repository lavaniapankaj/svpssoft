@extends('admin.index')
@section('sub-content')
    <div class="container">

        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">{{ __('Student Full Detail') }}
                        <a class="btn btn-warning btn-sm" style="float: right;" onclick="history.back()">Back</a>
                    </div>

                    <div class="card-body">
                        <input type="hidden" name="prev_srno" id="prev-srno" value="{{ $prevSrno !== 'null' ? $prevSrno : $srno }}">
                        <input type="hidden" name="srno" id="srno" value="{{ $srno }}">
                        <input type="hidden" name="current_session" value='' id="current_session">
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

                            <h4 class="text-danger fw-bold">Current Details</h4>
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
            let prevRecord = $('#prev_record');
            let currentDetail = $('#current_details');
            let prevsrno = $('#prev-srno').val();
            let srno = $('#srno').val();
            let session = $('#current_session').val();
            if (prevsrno) {
                // prevRecord.show();

                $.ajax({

                    url: '{{ route('admin.reports.tcStPreviousDetails') }}', // The API endpoint

                    method: 'GET',

                    data: {

                        srno: srno,
                        session: session,

                    }, // Pass srno as part of the GET request

                    dataType: 'JSON',

                    success: function(response) {

                        let tblheaders = '';

                        let tblbody = '';



                        // Ensure the response is structured as expected

                        if (response.status === 'success') {
                            if (response.tables[2]) {
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
                            } else {
                                $('#previous-header').html("No previous record.");
                                $('#previous-body').html("");
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

            if (srno) {

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

                                                <td>${rowData.dob}</td>

                                                <td>${rowData.address}</td>

                                                <td>${rowData.category}</td>

                                                <td>${rowData.email}</td>

                                                <td>${rowData.mobile}</td>

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
        });
    </script>
@endsection
