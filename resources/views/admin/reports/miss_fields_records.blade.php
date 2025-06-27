@extends('admin.index')

@section('sub-content')
    <div class="container">

        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="card">
                    <div class="card-header">{{ __('Search Miss Field Records') }}
                        <a href="{{ route('admin.reports') }}" class="btn btn-warning btn-sm" style="float: right;">Back</a>
                    </div>

                    <div class="card-body">
                        <form action="" method="get" id="class-form">
                            <div class="row mt-2">
                                <div class="form-group col-md-6">
                                    <label for="class_id" class="mt-2">Class <span class="text-danger">*</span></label>
                                    <input type="hidden" name="current_session" value='' id="current_session">
                                    <input type="hidden" id="initialClassId" name="initialClassId"
                                        value="{{ old('initialClassId', request()->get('class_id') !== null ? request()->get('class_id') : '') }}">
                                    <select name="class_id" id="class_id"
                                        class="form-control mx-1 @error('class_id') is-invalid @enderror" required>
                                        <option value="">All Class</option>
                                    </select>
                                    @error('class_id')
                                        <span class="invalid-feedback form-invalid fw-bold" role="alert">
                                            {{ $message }}
                                        </span>
                                    @enderror
                                    <img src="{{ config('myconfig.myloader') }}" alt="Loading..." class="loader"
                                        id="loader" style="display:none; width:10%;">
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="section_id" class="mt-2">Section <span
                                            class="text-danger">*</span></label>
                                    <select name="section_id" id="section_id" class="form-control mx-1" required>
                                        <option value="">All Sections</option>
                                    </select>
                                    <span class="invalid-feedback form-invalid fw-bold" role="alert"></span>

                                    <img src="{{ config('myconfig.myloader') }}" alt="Loading..." class="loader"
                                        id="loader" style="display:none; width:10%;">
                                </div>

                            </div>
                            <div class="row">
                                <div class="form-group col-md-6">
                                    <label for="fields" class="mt-2">Fields<span class="text-danger">*</span></label>
                                    <select name="fields" id="fields" class="form-control mx-1" required>
                                        <option value="1">Date of Birth</option>
                                        <option value="2">Admission Date</option>
                                        <option value="3">Mobile No.</option>
                                        <option value="4">Age Proof</option>
                                    </select>
                                    <span class="invalid-feedback form-invalid fw-bold" role="alert"></span>
                                </div>
                            </div>

                            <div class="mt-3">
                                <button class="btn btn-primary" type="button" id="show-report">Show Report</button>
                            </div>
                        </form>


                        <div class="super-div1 mt-4">
                            <span class="heading"></span>
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>R.N.</th>
                                        <th>REGNO</th>
                                        <th>Class</th>
                                        <th>Section</th>
                                        <th>Name</th>
                                        <th>Father's Name</th>
                                        <th>DOB</th>
                                        <th>Address</th>
                                        <th>Mobile No.</th>
                                    </tr>
                                </thead>
                                <tbody id="report-body1">
                                </tbody>
                            </table>
                            <div id="std-pagination"></div>
                            <div class="export-div">
                                <button type="button" class="btn btn-info" id="export-button1">Export</button>
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

            $('.super-div1').hide();

            function getReport(page = 1) {
                let sessionId = $('#current_session').val();
                let classId = $('#class_id').val();
                let sectionId = $('#section_id').val();
                let fieldId = $('#fields').val();
                if ($('#class-form').valid()) {
                    $('.super-div1').show();
                    $.ajax({
                        url: '{{ route('admin.reports.missFieldsReport') }}',
                        type: 'GET',
                        dataType: 'JSON',
                        data: {
                            session: sessionId,
                            class: classId,
                            section: sectionId,
                            field: fieldId,
                            page: page

                        },
                        success: function(response) {
                            let tableHtml = '';
                            if (response.data.data.length > 0) {

                                $.each(response.data.data, function(index, studentData) {
                                    tableHtml += `
                                            <tr>
                                                <td>${studentData.rollno}</td>
                                                <td>${studentData.srno}</td>
                                                <td>${studentData.class_name}</td>
                                                <td>${studentData.section_name}</td>
                                                <td>${studentData.student_name}</td>
                                                <td>${studentData.f_name}</td>
                                                <td>${studentData.dob ?? ''}</td>
                                                <td>${studentData.address}</td>
                                                <td>${studentData.f_mobile ?? ''}</td>
                                            </tr>`;

                                });
                                $('.heading').html('Report of : ' + response.heading);
                            }else{
                                tableHtml = '<tr><td colspan="9" class="text-center">No records found</td></tr>';
                                $('.heading').html('');
                            }
                            $('#report-body1').html(tableHtml);
                            updatePaginationControls(response.data);
                        },
                        complete: function() {

                            loader.hide();
                        },
                        error: function(data, xhr) {
                            var message = data.responseJSON.message;
                            $('.cdate-error').hide().html('');
                            if (message.date) {
                                $('.cdate-error').show().html(message.date);
                            }
                            console.log(xhr);

                        },
                    });
                }
            }



            $('#show-report').click(function() {
                getReport();

            });

            $('#class_id, #fields, #section_id').change(function() {
                $('.super-div1').hide();

            });

            $(document).on('click', '#std-pagination .page-link', function(e) {
                e.preventDefault();
                let page = $(this).data('page');
                getReport(page);
            });

            $('#export-button1').on('click', function() {
                const session = $('#current_session').val();
                const classId = $('#class_id').val();
                const sectionId = $('#section_id').val();
                const fieldId = $('#fields').val();

                const exportUrl = "{{ route('admin.reports.exportReportByMissFields') }}?session=" +
                    session +
                    "&class=" + classId + "&section=" + sectionId + "&field=" + fieldId;
                window.location.href = exportUrl;
            });



        });
    </script>
@endsection
