@extends('admin.index')

@section('sub-content')
    <div class="container">

        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="card">
                    <div class="card-header">{{ __('RTE Student') }}
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

                            </div>

                            <div class="mt-3">
                                <button class="btn btn-primary" type="button" id="show-report">Show Report</button>

                            </div>
                        </form>

                        <div class="super-div">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>S.No.</th>
                                        <th>SRNO</th>
                                        <th>Name</th>
                                        <th>Gurdian Name</th>
                                        <th>Category(WS/DG)</th>
                                        <th>Sign. of Certifier </th>
                                        <th>Remark</th>
                                    </tr>
                                </thead>
                                <tbody id="report-body">
                                </tbody>
                            </table>
                            <div id="std-pagination"></div>
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
            getClassDropDownWithAll();
            let superDiv = $('.super-div');
            superDiv.hide();
            function getReport(page = 1) {

                superDiv.show();
                let classId = $('#class_id').val();
                console.log(classId);
                let sessionId = $('#current_session').val();
                if (classId) {
                    $.ajax({
                        url: '{{ route('admin.reports.rteStudentReport') }}',
                        type: 'GET',
                        dataType: 'JSON',
                        data: {
                            class: classId,
                            session: sessionId,
                            page: page,

                        },
                        success: function(response) {
                            console.log(response);

                            let tableHtml = '';
                            updatePaginationControls(response.pagination);
                            if (response.data.length > 0) {
                                let displayIndex = (response.pagination.current_page - 1) * response.pagination
                                .per_page + 1;
                                $.each(response.data, function(index, std) {
                                    // Create row for boys
                                    tableHtml += `
                                            <tr>
                                                 <td>${displayIndex}</td>
                                                 <td>${std.srno}</td>
                                                 <td>${std.name}</td>
                                                 <td>${std.f_name}</td>
                                                 <td>-</td>
                                                 <td>-</td>
                                                 <td>-</td>
                                            </tr>`;

                                });
                            }else {
                                tableHtml += `
                                            <tr>
                                                 <td colspan="7" class='text-center'>No Students Found</td>
                                            </tr>`;
                            }
                            $('#report-body').html(tableHtml);
                        },
                        complete: function() {

                            loader.hide();
                        },
                        error: function(data, xhr) {
                            console.log(xhr);

                        },
                    });
                }
            }
            $('#show-report').click(() => {
                getReport();
            });

            // Pagination controls
            $(document).on('click', '#std-pagination .page-link', function(e) {
                e.preventDefault();
                let page = $(this).data('page');
                getReport(page);
            });
            $('#class_id').change(() => {
                superDiv.hide();
            });
            $('#export-button').on('click', function() {
                let session = $('#current_session').val();
                let classId = $('#class_id').val();

                const exportUrl = "{{ route('admin.reports.rteStudentReport.excel') }}?class=" + classId + "&session=" + session;
                window.location.href = exportUrl;
            });


        });
    </script>
@endsection
