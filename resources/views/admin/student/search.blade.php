@extends('admin.index')

@section('sub-content')
    <div class="container">
        @if (Session::has('success'))
            @section('scripts')
                <script>
                    swal("Successful", "{{ Session::get('success') }}", "success").then(() => {
                        location.reload();
                    });
                </script>
            @endsection
        @endif

        @if (Session::has('error'))
            @section('scripts')
                <script>
                    swal("Error", "{{ Session::get('error') }}", "error").then(() => {
                        location.reload();
                    });
                </script>
            @endsection
        @endif
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">{{ __('Search & Update Student (Promote Details)') }}

                        <div class="col-lg-14 col-md-14">
                            <div class="right-item d-flex justify-content-end mt-5">

                                <form action="{{ route('admin.student-master.search') }}" method="get" class="d-flex">
                                    <input type="text" name="search" id="search" class="form-control mx-2"
                                        placeholder="Search by Name"
                                        value="{{ old('search', request()->get('search') !== null ? request()->get('search') : '') }}">
                                    <button type="submit" class="btn btn-sm btn-info">Search</button>
                                </form>
                                <a href="{{ route('admin.student-master.search') }}" class="btn btn-warning mx-2">Reset</a>


                            </div>
                        </div>
                    </div>

                    <div class="card-body">
                        @if (request('search'))

                            <div class="table">
                                <table id="example" class="table table-striped table-bordered">
                                    <thead>
                                        <tr>
                                            <th>S No.</th>
                                            <th>SR No.</th>
                                            <th>Student Name</th>
                                            <th>Class</th>
                                            <th>Section</th>
                                            <th>Father's Name</th>
                                            <th>Mother's Name</th>
                                            <th class="text-center">Action</th>
                                        </tr>
                                    </thead>

                                    @if (count($data) > 0)
                                        @foreach ($data as $key => $value)
                                            <tr data-entry-id="{{ $value->id }}">
                                                <td>{{ $data->firstItem() + $key ?? '' }}</td>
                                                <td>{{ $value->srno ?? '-' }}</td>
                                                <td>{{ $value->student_name ?? '-' }}</td>
                                                <td>{{ $value->class_name ?? '-' }}</td>
                                                <td>{{ $value->section_name ?? '-' }}</td>
                                                <td>{{ $value->f_name ?? '-' }}</td>
                                                <td>{{ $value->m_name ?? '-' }}</td>


                                                <td class="text-center">
                                                    <a href="#" class="btn btn-sm btn-icon p-1 show"
                                                        data-id="{{ $value->prev_srno }}"
                                                        data-id-srno="{{ $value->srno }}"
                                                        data-id-session="{{ $value->session_id }}"
                                                        data-ssid="{{ $value->ssid ?? '' }}" id="show">
                                                        <i class="mdi mdi-eye mx-1" data-bs-toggle="tooltip"
                                                            data-bs-offset="0,4" data-bs-placement="top" title="View"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        @endforeach
                                    @else
                                        <tr>
                                            <td colspan="8">No Student Found</td>
                                        </tr>
                                    @endif
                                </table>

                                @if (request()->get('search'))
                                    {{ $data->appends(['search' => request()->get('search')])->links() }}
                                @else
                                    {{ $data->links() }}
                                @endif

                            </div>



                            <div class="table mt-4" id="prev_record">
                                <h4 class="text-danger fw-bold">Previous Details</h4>
                                <table id="example" class="table table-striped table-bordered">
                                    <thead id="previous-header">

                                    </thead>
                                    <tbody id="previous-body"></tbody>

                                </table>


                            </div>
                            <div class="table mt-4" id="fee_record">
                                <h4 class="text-danger fw-bold">Fee Details</h4>
                                <table id="example" class="table table-striped table-bordered">
                                    <thead id="fee-header">

                                    </thead>
                                    <tbody id="fee-body"></tbody>

                                </table>
                                <h4 class="text-danger fw-bold">Transport Fee Details</h4>
                                <table id="example" class="table table-striped table-bordered">
                                    <thead id="transport-details-header">
                                    </thead>
                                    <tbody id="transport-details-body"></tbody>
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

                            {{-- Form Updation  --}}

                            <div id="std-form-container" class="mt-4">
                                <form action="{{ route('admin.student-master.search.store') }}" method="POST"
                                    enctype="multipart/form-data" id="std-form">
                                    @csrf

                                    <div class="row">
                                        <div class="form-group col-md-6">
                                            <input type="hidden" name="srno" value="" id="srno-form">
                                            <input type="hidden" name="ssid" id="ssid" value="">
                                            <label for="admission_date" class="mt-2">New Admission Date<span
                                                    class="text-danger">*</span></label>
                                            <input type="date" class="form-control" name="admission_date"
                                                id="admission_date" value="" required>
                                            @error('admission_date')
                                                <span class="invalid-feedback form-invalid fw-bold"
                                                    role="alert">{{ $message }}</span>
                                            @enderror
                                        </div>
                                        <div class="form-group col-md-6">
                                            <label for="session_id" class="mt-2">New Session<span
                                                    class="text-danger">*</span></label>
                                            <select name="session_id" id="session_id" class="form-control" required>
                                                <option value="" required>Select Session</option>
                                                @foreach ($sessions as $id => $session)
                                                    <option value="{{ $id }}"
                                                        {{ old('session_id') == $id ? 'selected' : '' }}>
                                                        {{ $session }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('session_id')
                                                <span class="invalid-feedback form-invalid fw-bold"
                                                    role="alert">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="form-group col-md-6">
                                            <label for="class_id" class="mt-2">New Class<span
                                                    class="text-danger">*</span></label>
                                            <select name="class_id" id="class_id" class="form-control" required>
                                                <option value="" required>Select Class</option>
                                                @if (count($classes) > 0)
                                                    @foreach ($classes as $key => $class)
                                                        <option value="{{ $key }}"
                                                            {{ old('class_id') == $key ? 'selected' : '' }}>
                                                            {{ $class }}</option>
                                                    @endforeach
                                                @else
                                                    <option value="">No Class Found</option>
                                                @endif
                                            </select>
                                            @error('class_id')
                                                <span class="invalid-feedback form-invalid fw-bold"
                                                    role="alert">{{ $message }}</span>
                                            @enderror
                                        </div>
                                        <div class="form-group col-md-6">
                                            <label for="section_id" class="mt-2">New Section<span
                                                    class="text-danger">*</span></label>
                                            <select name="section_id" id="section_id" class="form-control" required>
                                                <option value="" required>Select Class</option>
                                            </select>
                                            @error('section_id')
                                                <span class="invalid-feedback form-invalid fw-bold"
                                                    role="alert">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="mt-3">
                                        <button type="submit" class="btn btn-primary">Update</button>
                                    </div>
                                </form>
                            </div>
                        @endif

                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('admin-scripts')
    <script>
        var initialClassId =
            '{{ old('class_id', request()->get('class_id') !== null ? request()->get('class_id') : '') }}';
        var initialSectionId =
            '{{ old('section_id', request()->get('section_id') !== null ? request()->get('section_id') : '') }}';
        getClassSection(initialClassId, initialSectionId);
        $(document).ready(function() {
            var view = $('.show');
            var prevRecord = $('#prev_record');
            var feeRecord = $('#fee_record');
            var currentDetail = $('#current_details');
            var stdFormConatiner = $('#std-form-container');
            prevRecord.hide();
            feeRecord.hide();
            currentDetail.hide();
            stdFormConatiner.hide();
            let loader = $('#loader');
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
                                                <td>${rowData.dob  ?? 'N/A'}</td>
                                                <td>${rowData.address  ?? 'N/A'}</td>
                                                <td>${rowData.category}</td>
                                                <td>${rowData.email}</td>
                                                <td>${rowData.mobile  ?? 'N/A'}</td>
                                            `;
                                            break;

                                        case 'Parent Details':
                                            tableHtml += `
                                                    <td>${rowData.father_name}</td>
                                                    <td>${rowData.mother_name}</td>
                                                    <td>${rowData.address  ?? 'N/A'}</td>
                                                    <td>${rowData.father_mobile  ?? 'N/A'}</td>
                                                    <td>${rowData.mother_mobile  ?? 'N/A'}</td>
                                                    <td>${rowData.father_occupation  ?? 'N/A'}</td>
                                                    <td>${rowData.mother_occupation  ?? 'N/A'}</td>
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
                                                <td>${rowData.admission_date}</td>
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
                                        // case 'Attendance':
                                        //     $('#last-attendance-date').text(attendanceHtml);
                                        //     break;
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
            function getPreviousRecords(srno, sessionID) {
                $.ajax({
                    url: '{{ route('admin.reports.tcStPreviousDetails') }}', // The API endpoint
                    method: 'GET',
                    data: {
                        srno: srno,
                        session: sessionID,
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
                                        <td>${item.admission_date}</td>
                                    </tr>`;
                                });

                                // Populate the table in HTML
                                $('#previous-header').html(tblheaders); // Append table headers
                                $('#previous-body').html(tblbody); // Append table body
                            } else {
                                $('#previous-header').html('No any previous record.');
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
            // academic fee records
            function getStFeeRecords(srno) {
                $.ajax({
                    url: '{{ route('admin.student-master.search-fee-details') }}', // The API endpoint
                    method: 'GET',
                    data: {
                        srno: srno
                    }, // Pass srno as part of the GET request
                    dataType: 'JSON',
                    success: function(response) {
                        let tblheaders = '';
                        let tblbody = '';

                        // Ensure the response is structured as expected
                        if (response.status === 'success') {
                            let headers = response.tables[0].headers;
                            let data = response.tables[0].data;

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
                                    <td>${item.classname}</td>
                                    <td>${item.payable_amount}</td>
                                    <td>${item.paid_amount ?? 0}</td>
                                    <td>${item.due_amount}</td>
                                </tr>`;
                            });

                            // Populate the table in HTML
                            $('#fee-header').html(tblheaders); // Append table headers
                            $('#fee-body').html(tblbody); // Append table body
                        } else {
                            console.error('No data found for the given SRNO');
                        }
                    },
                    error: function(xhr) {
                        console.error('Failed to retrieve data. Please try again.');
                    }
                });
            }
            // transport fee records
            function getStTransportFeeRecords(srno) {
                $.ajax({
                    url: '{{ route('admin.student-master.search-transport-fee-details') }}', // The API endpoint
                    method: 'GET',
                    data: {
                        srno: srno
                    }, // Pass srno as part of the GET request
                    dataType: 'JSON',
                    success: function(response) {
                        let tblheaders = '';
                        let tblbody = '';

                        // Ensure the response is structured as expected
                        if (response.status ==='success') {
                            let headers = response.tables.headers;
                            let data = response.tables.data;

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
                                    <td>${item.classname}</td>
                                    <td>${item.payable_amount}</td>
                                    <td>${item.paid_amount}</td>
                                    <td>${item.due_amount}</td>
                                </tr>`;
                            });

                            // Populate the table in HTML
                            $('#transport-details-header').html(tblheaders); // Append table headers
                            $('#transport-details-body').html(tblbody); // Append table body
                        } else {
                            console.error('No data found for the given SRNO');
                        }
                    },
                    error: function(xhr) {
                        console.error('Failed to retrieve data. Please try again.');
                    }

                });
            }
            view.click(function() {
                var prevsrno = $(this).data("id");
                var srno = $(this).data("id-srno");
                let stdSsid = $(this).data("ssid");
                let sessionID = $(this).data("id-session");
               $('#ssid').val(stdSsid);
                stdFormConatiner.show();
                $('#srno-form').val(srno);
                loader.show();
                prevRecord.show();
                feeRecord.show();
                currentDetail.show();

                getPreviousRecords(srno, sessionID);
                getStFeeRecords(srno);
                getStTransportFeeRecords(srno);
                getStCurrentDetails(srno);
                $('#std-form').validate();
            });
        });
    </script>
@endsection
