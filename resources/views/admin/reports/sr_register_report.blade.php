@extends('admin.index')

@section('sub-content')
    <div class="container">



        <div class="row justify-content-center">

            <div class="col-md-8">

                <div class="card">

                    <div class="card-header">{{ __('SR Register') }}

                        <a href="{{ route('admin.reports') }}" class="btn btn-warning btn-sm" style="float: right;">Back</a>

                    </div>



                    <div class="card-body">


                        <form id="report-form">
                            <div class="row mt-2">

                                <div class="form-group col-md-6">

                                    <label for="session_id" class="mt-2">Session <span
                                            class="text-danger">*</span></label>

                                    <select name="session_id" id="session_id"
                                        class="form-control @error('session_id') is-invalid @enderror" required>

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

                                    @error('session_id')
                                        <span class="invalid-feedback form-invalid fw-bold" role="alert">

                                            {{ $message }}

                                        </span>
                                    @enderror
                                </div>



                                <div class="form-group col-md-6">

                                    <label for="std-type" class="mt-2">Student Type <span
                                            class="text-danger">*</span></label>

                                    <select name="std_type" id="std-type" class="form-control" required>

                                        <option value="1,2,4,5">All</option>

                                        <option value="1">Present</option>

                                        <option value="4">TC Issued</option>

                                        <option value="5">Left Out</option>

                                    </select>

                                    <span class="invalid-feedback form-invalid fw-bold" role="alert"></span>

                                </div>



                            </div>

                            <div class="mt-3">

                                <button class="btn btn-primary" type="button" id="show-report">Show Report</button>

                            </div>
                        </form>



                        <div id="super-div">

                            <table class="table">

                                <thead>

                                    <tr>
                                        <th>Sr. No.</th>
                                        <th>Class</th>
                                        <th>Student Name</th>
                                        <th>Father's Name</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody id="student-report-body">
                                    <!-- Student Report Data will be loaded here -->
                                </tbody>
                            </table>

                            <div id="std-pagination"></div>
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

    </div>
@endsection

@section('admin-scripts')
    <script>
        $(document).ready(function() {

           let view = $('#edit-section-editBtn');

            // let view = $('.show');

            let prevRecord = $('#prev_record');

            let currentDetail = $('#current_details');

            let stdFormConatiner = $('#super-div');

            var loader = $('#loader');

            prevRecord.hide();

            currentDetail.hide();

            stdFormConatiner.hide();



            function getStdTbale(page = 1) {

                let stdType = $('#std-type').val();

                let sessionId = $('#session_id').val();

                stdFormConatiner.show();

                $.ajax({

                    url: '{{ route('admin.reports.reportSrRegisterWise') }}',

                    type: 'GET',

                    dataType: 'JSON',

                    data: {

                        session_id: sessionId,

                        type: stdType,

                        page: page,

                    },

                    success: function(response) {

                        let tableHtml = '';



                        if (response.data.data && Array.isArray(response.data.data)) {

                            response.data.data.forEach(function(student, index) {

                                tableHtml += `<tr>

                            <td>${student.srno ?? ''}</td>

                            <td>${student.class_name ?? ''}</td>

                            <td>${student.name ?? ''}</td>

                            <td>${student.f_name ?? ''}</td>

                            <td>

                                <a href="#" class="btn btn-sm btn-icon p-1" id="edit-section-editBtn" data-id-session="${sessionId}" data-id="${student.prev_srno}" data-id-srno="${student.srno}">

                                    <i class="mdi mdi-eye" data-bs-toggle="tooltip" data-bs-offset="0,4" data-bs-placement="top" title="View" id="view-btn"></i>

                                </a>

                            </td>

                        </tr>`;

                            });

                        }

                        if (tableHtml === '') {

                            tableHtml = '<tr><td colspan="5">No data found</td></tr>';
                            prevRecord.hide();
                            currentDetail.hide();

                        }

                        $('#student-report-body').html(tableHtml);

                        updatePaginationControls(response.data);

                    },

                    complete: function() {
                        loader.hide();

                    },

                    error: function(xhr) {

                        console.log(xhr);



                    },



                });

            }

            $('#show-report').click(function() {
                if ($('#report-form').valid()) {
                    getStdTbale();
                } else {
                    stdFormConatiner.hide();
                }
            });
            $('#session_id, #std-type').change(() => {
                stdFormConatiner.hide();
            });
            $(document).on('click', '#std-pagination .page-link', function(e) {

                e.preventDefault();

                let page = $(this).data('page');

                getStdTbale(page);
                prevRecord.hide();
                currentDetail.hide();

            });



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



            // Previous Records



            function getPreviousRecords(srno, session) {

                $.ajax({

                    url: '{{ route('admin.reports.tcStPreviousDetails') }}', // The API endpoint

                    method: 'GET',

                    data: {

                        srno: srno,
                        session : session,

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
            $(document).on('click', '#edit-section-editBtn', function() {
                var prevsrno = $(this).data("id");
                var srno = $(this).data("id-srno");
                var session = $(this).data("id-session");
                prevRecord.show();
                currentDetail.show();
                getPreviousRecords(srno, session);
                getStCurrentDetails(srno);
            });
        });
    </script>
@endsection
