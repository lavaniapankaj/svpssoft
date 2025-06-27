@extends('admin.index')

@section('sub-content')
    <div class="container">

        <div class="row justify-content-center">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">{{ __('SR Register (Full Details)') }}
                        <a href="{{ route('admin.reports') }}" class="btn btn-warning btn-sm" style="float: right;">Back</a>
                    </div>

                    <div class="card-body">
                        <form action="" method="get" id="class-form">
                            <div class="row mt-2">
                                <div class="form-group col-md-6">
                                    <label for="class_id" class="mt-2">Class <span class="text-danger">*</span></label>
                                    <input type="hidden" name="current_session" value='' id="current_session">
                                    <input type="hidden" id="initialClassId" name="initialClassId"
                                        value="{{ old('class_id') }}">
                                    <select name="class_id" id="class_id" class="form-control mx-1" required>
                                        <option value="">All Class</option>
                                    </select>
                                    <span class="invalid-feedback form-invalid fw-bold class-error" role="alert"></span>
                                    <img src="{{ config('myconfig.myloader') }}" alt="Loading..." class="loader"
                                        id="loader" style="display:none; width:10%;">
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="section_id" class="mt-2">Section <span
                                            class="text-danger">*</span></label>
                                    <select name="section_id" id="section_id" class="form-control mx-1" required>
                                        <option value="">All Sections</option>
                                    </select>
                                    <span class="invalid-feedback form-invalid fw-bold section-error" role="alert"></span>

                                    <img src="{{ config('myconfig.myloader') }}" alt="Loading..." class="loader"
                                        id="loader" style="display:none; width:10%;">
                                </div>

                            </div>
                            <div class="row">
                                <div class="form-group col-md-6">
                                    <label for="srno-type" class="mt-2">Select SRNO Type<span
                                            class="text-danger">*</span></label>
                                    <select name="srno-type" id="srno-type" class="form-control mx-1" required>
                                        <option value="1">General Student SRNO</option>
                                        <option value="2">Junior Student SRNO</option>
                                        <option value="3">RTE Student SRNO</option>
                                    </select>
                                    <span class="invalid-feedback form-invalid fw-bold srnoType-error"
                                        role="alert"></span>
                                </div>
                            </div>
                            <div class="row">
                                <div class="form-group col-md-6">
                                    <label for="start-srno" class="mt-2">Starting SRNO<span
                                            class="text-danger">*</span></label>
                                    <input type="text" name="start-srno" id="start-srno" class="form-control mx-1">
                                    <span class="invalid-feedback form-invalid fw-bold startSrno-error"
                                        role="alert"></span>


                                </div>
                                <div class="form-group col-md-6">
                                    <label for="end-srno" class="mt-2">End SRNO<span class="text-danger">*</span></label>
                                    <input type="text" name="end-srno" id="end-srno" class="form-control mx-1">
                                    <span class="invalid-feedback form-invalid fw-bold endSrno-error" role="alert"></span>

                                </div>
                                <div class="mt-3">
                                    <button class="btn btn-primary" type="button" id="show-report">Show Report</button>
                                </div>
                        </form>


                        <div class="super-div mt-4">
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
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
                                            <th>Mother's Occupation </th>
                                            <th>View</th>
                                        </tr>
                                    </thead>
                                    <tbody id="report-body">
                                    </tbody>
                                </table>
                            </div>
                            <div id="std-pagination" class="mt-2"></div>
                            <div class="export-div mt-2">
                                <button type="button" class="btn btn-info" id="export-button">Excel</button>
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
            getClassDropDownWithAll();

            $('.super-div').hide();

            function getReport(page = 1) {
                let sessionId = $('#current_session').val();
                let classId = $('#class_id').val();
                let sectionId = $('#section_id').val();
                let srnoTypeId = $('#srno-type').val();
                let startSrnoId = $('#start-srno').val();
                let endSrnoId = $('#end-srno').val();
                $('.class-error').hide().html('');
                $('.section-error').hide().html('');
                $('.srnoType-error').hide().html('');
                $('.startSrno-error').hide().html('');
                $('.endSrno-error').hide().html('');
                if ($('#class-form').valid()) {
                    $('.super-div').show();
                    $.ajax({
                        url: '{{ route('admin.reports.srRegisterFullDetails') }}',
                        type: 'GET',
                        dataType: 'JSON',
                        data: {
                            class: classId,
                            section: sectionId,
                            session: sessionId,
                            srnoType: srnoTypeId,
                            startSrno: startSrnoId,
                            endSrno: endSrnoId,
                            page: page,

                        },
                        success: function(response) {
                            let tableHtml = '';
                            // console.log(response.data.current_page);

                            let displayIndex = (response.data.current_page - 1) * response.data
                                .per_page + 1;
                            if (response.data.data.length > 0) {

                                $.each(response.data.data, function(index, studentData) {
                                    tableHtml += `<tr>
                                        <td>${displayIndex + index}</td>
                                        <td>${studentData.srno}</td>
                                        <td>${studentData.class_name}</td>
                                        <td>${studentData.section_name}</td>
                                        <td>${studentData.rollno}</td>
                                        <td>${
                                                studentData.ssid == 1 ? 'Active' :
                                                studentData.ssid == 2 ? 'Class Promoted' :
                                                studentData.ssid == 3 ? 'School Promoted' :
                                                studentData.ssid == 4 ? 'Tc' :
                                                studentData.ssid == 5 ? 'Left Out' : ''
                                            }</td>
                                        <td>${studentData.student_name}</td>
                                        <td>${studentData.f_name}</td>
                                        <td>${studentData.m_name}</td>
                                        <td>${studentData.f_mobile ?? 'N/A'}</td>
                                        <td>${studentData.m_mobile ?? 'N/A'}</td>
                                        <td>${studentData.address}</td>
                                        <td>${studentData.gender == 1 ? 'Male' : studentData.gender == 2 ? 'Female' : studentData.gender == 3 ? "Othre's" : ''}</td>
                                        <td>${studentData.dob  ?? 'N/A'}</td>
                                        <td>${studentData.form_submit_date}</td>
                                        <td>${
                                            studentData.age_proof == 0 ? 'N/A' :
                                            studentData.age_proof == 1 ? 'Transfer Certificate (T.C.)' :
                                            studentData.age_proof == 2 ? 'Birth Certificate' :
                                            studentData.age_proof == 3 ? 'Affidavit' :
                                            studentData.age_proof == 4 ? 'Aadhar Card' : ''
                                        }</td>
                                        <td>${studentData.prev_srno ?? ''}</td>
                                        <td>${
                                            studentData.religion == 1 ? 'Hindu' :
                                            studentData.religion == 2 ? 'Muslim' :
                                            studentData.religion == 3 ? 'Christian' :
                                            studentData.religion == 4 ? 'Sikh' : ''
                                        }</td>

                                        <td>${
                                            studentData.transport == 0 ? 'No' :
                                            studentData.transport == 1 ? 'Yes' : ''
                                        }</td>
                                        <td>${
                                            studentData.category == 1 ? 'General' :
                                            studentData.category == 2 ? 'OBC' :
                                            studentData.category == 3 ? 'SC' :
                                            studentData.category == 4 ? 'ST' :
                                            studentData.category == 5 ? 'BC' : ''
                                        }</td>
                                        <td>${
                                            studentData.f_occupation == 1 ? 'Private Service' :
                                            studentData.f_occupation == 2 ? 'Govt. Service' :
                                            studentData.f_occupation == 3 ? 'Farmer' :
                                            studentData.f_occupation == 4 ? 'Business' :
                                            studentData.f_occupation == 5 ? 'Military Service' : ''
                                        }</td>
                                        <td>${
                                            studentData.m_occupation == 1 ? 'Private Service' :
                                            studentData.m_occupation == 2 ? 'Govt. Service' :
                                            studentData.m_occupation == 3 ? 'House Wife' :
                                            studentData.m_occupation == 4 ? 'Business' :
                                            studentData.m_occupation == 5 ? 'Military Service' : ''
                                        }</td>
                                        <td>   <a href="${siteUrl}/admin/full-detail-student/?prevSrno=${studentData.prev_srno}&srno=${studentData.srno}" class="btn btn-sm btn-icon p-1" id="edit-section-editBtn">
                                                <i class="mdi mdi-eye" data-bs-toggle="tooltip" data-bs-offset="0,4"
                                                    data-bs-placement="top" title="View"></i>
                                            </a></td>
                                        </tr>`;

                                });
                            } else {
                                tableHtml =
                                    '<tr><td colspan="24" class="text-center fst-italic fw-bold text-decoration-underline text-danger">No Records Found</td></tr>';
                            }
                            updatePaginationControls(response.data);
                            $('#report-body').html(tableHtml);
                        },
                        complete: function() {

                            loader.hide();
                        },
                        error: function(data, xhr) {
                            let message = data.responseJSON.message;

                            if (message.class) {
                                $('.class-error').show().html(message.class);
                            }
                            if (message.section) {
                                $('.section-error').show().html(message.section);
                            }
                            if (message.srnoType) {
                                $('.srnoType-error').show().html(message.srnoType);
                            }
                            if (message.startSrno) {
                                $('.startSrno-error').show().html(message.startSrno);
                            }
                            if (message.endSrno) {
                                $('.endSrno-error').show().html(message.endSrno);
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
                let sessionId = $('#current_session').val();
                let classId = $('#class_id').val();
                let sectionId = $('#section_id').val();
                let srnoTypeId = $('#srno-type').val();
                let startSrnoId = $('#start-srno').val();
                let endSrnoId = $('#end-srno').val();
                const exportUrl = "{{ route('admin.reports.srRegisterFullReport.excel') }}?class=" +
                    classId +
                    "&section=" + sectionId + "&session=" + sessionId + "&srnoType=" + srnoTypeId +
                    "&startSrno=" + startSrnoId + "&endSrno=" + endSrnoId;
                window.location.href = exportUrl;
            });



        });
    </script>
@endsection
