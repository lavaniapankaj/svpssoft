@extends('admin.index')

@section('sub-content')
    <div class="container">

        <div class="row justify-content-center">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">{{ __('Age-Wise Student Report') }}
                        <a href="{{ route('admin.reports') }}" class="btn btn-warning btn-sm"
                            style="float: right;">Back</a>
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
                                    <label for="date" class="mt-2">Calculation Date <span
                                            class="text-danger">*</span></label>
                                    <input type="date" name="date" id="cdate" class="form-control mx-1"
                                        value="" required>
                                    <span class="invalid-feedback form-invalid fw-bold cdate-error" role="alert"></span>
                                </div>
                            </div>

                            <div class="mt-3">
                                <button class="btn btn-primary" type="button" id="show-report">Show Report</button>
                                <button class="btn btn-primary" type="button" id="show-report2">Student-Wise
                                    Report</button>
                            </div>
                        </form>

                        <div class="super-div">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th></th>
                                        <th></th>
                                        <th><5</th>
                                        <th>5</th>
                                        <th>6</th>
                                        <th>7</th>
                                        <th>8</th>
                                        <th>9</th>
                                        <th>10</th>
                                        <th>11</th>
                                        <th>12</th>
                                        <th>13</th>
                                        <th>14</th>
                                        <th>15</th>
                                        <th>16</th>
                                        <th>>16</th>

                                    </tr>
                                </thead>
                                <tbody id="report-body1">
                                </tbody>
                            </table>
                            <div class="export-div">
                                <button type="button" class="btn btn-info" id="export-button1">Export</button>
                            </div>
                        </div>
                        <div class="super-div2">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>S.No.</th>
                                        <th>Class</th>
                                        <th>Section</th>
                                        <th>SRNO</th>
                                        <th>Name</th>
                                        <th>Father's Name</th>
                                        <th>Mother's Name</th>
                                        <th>DOB</th>
                                        <th>AGE</th>
                                        <th>Mobile No.</th>
                                    </tr>
                                </thead>
                                <tbody id="report-body2">
                                </tbody>
                            </table>
                            <div id="std-pagination"></div>
                            <div class="export-div">
                                <button type="button" class="btn btn-info" id="export-button2">Export</button>
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
            $('.super-div2').hide();

            function getReport() {
                let sessionId = $('#current_session').val();
                let classId = $('#class_id').val();
                let date = $('#cdate').val();
                if ($('#class-form').valid()) {
                    $('.super-div').show();
                    $.ajax({
                        url: '{{ route('admin.reports.reportAgeWise') }}',
                        type: 'GET',
                        dataType: 'JSON',
                        data: {
                            session_id: sessionId,
                            class: classId,
                            date: date,

                        },
                        success: function(response) {
                            console.log(response);

                            let tableHtml = '';
                            $.each(response.data, function(index, classData) {
                                // Create row for boys
                                tableHtml += `
                                    <tr>
                                        <td rowspan="2">${classData.class}</td>
                                        <td>Boys --></td>
                                        <td>${classData.ageGroups.lessThanFive.boys}</td>
                                        <td>${classData.ageGroups.equalToFive.boys}</td>
                                        <td>${classData.ageGroups.equalToSix.boys}</td>
                                        <td>${classData.ageGroups.equalToSeven.boys}</td>
                                        <td>${classData.ageGroups.equalToEight.boys}</td>
                                        <td>${classData.ageGroups.equalToNine.boys}</td>
                                        <td>${classData.ageGroups.equalToTen.boys}</td>
                                        <td>${classData.ageGroups.equalToEleven.boys}</td>
                                        <td>${classData.ageGroups.equalToTwelve.boys}</td>
                                        <td>${classData.ageGroups.equalToThirteen.boys}</td>
                                        <td>${classData.ageGroups.equalToFourteen.boys}</td>
                                        <td>${classData.ageGroups.equalToFifteen.boys}</td>
                                        <td>${classData.ageGroups.equalToSixteen.boys}</td>
                                        <td>${classData.ageGroups.aboveToSixteen.boys}</td>
                                    </tr>`;

                                // Create row for girls
                                tableHtml += `
                                    <tr>
                                        <td>Girls --></td>
                                         <td>${classData.ageGroups.lessThanFive.girls}</td>
                                        <td>${classData.ageGroups.equalToFive.girls}</td>
                                        <td>${classData.ageGroups.equalToSix.girls}</td>
                                        <td>${classData.ageGroups.equalToSeven.girls}</td>
                                        <td>${classData.ageGroups.equalToEight.girls}</td>
                                        <td>${classData.ageGroups.equalToNine.girls}</td>
                                        <td>${classData.ageGroups.equalToTen.girls}</td>
                                        <td>${classData.ageGroups.equalToEleven.girls}</td>
                                        <td>${classData.ageGroups.equalToTwelve.girls}</td>
                                        <td>${classData.ageGroups.equalToThirteen.girls}</td>
                                        <td>${classData.ageGroups.equalToFourteen.girls}</td>
                                        <td>${classData.ageGroups.equalToFifteen.girls}</td>
                                        <td>${classData.ageGroups.equalToSixteen.girls}</td>
                                        <td>${classData.ageGroups.aboveToSixteen.girls}</td>
                                    </tr>`;
                            });
                            $('#report-body1').html(tableHtml);
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

            function getReportSecond(page = 1) {
                let sessionId = $('#current_session').val();
                let classId = $('#class_id').val();
                let date = $('#cdate').val();
                if ($('#class-form').valid()) {
                    $('.super-div2').show();
                    $.ajax({
                        url: '{{ route('admin.reports.reportAgeWiseWithDetails') }}',
                        type: 'GET',
                        dataType: 'JSON',
                        data: {
                            session_id: sessionId,
                            class: classId,
                            date: date,
                            page: page,
                        },
                        success: function(response) {
                            console.log(response);

                            let tableHtml = '';
                            let i = 0;
                            let displayIndex = (response.data.current_page - 1) * response.data
                                .per_page + 1;
                            $.each(response.data.data, function(index, studentData) {
                                tableHtml += `
                                        <tr>
                                            <td>${displayIndex}</td>
                                            <td>${studentData.class}</td>
                                            <td>${studentData.section}</td>
                                            <td>${studentData.srno}</td>
                                            <td>${studentData.name}</td>
                                            <td>${studentData.f_name}</td>
                                            <td>${studentData.m_name}</td>
                                            <td>${studentData.dob ?? 'N/A'}</td>
                                            <td>${studentData.age ?? 'N/A'}</td>
                                            <td>${studentData.mobile ?? 'N/A'}</td>
                                            <!-- Add other columns as needed -->
                                        </tr>`;
                                displayIndex++;
                            });
                            $('#report-body2').html(tableHtml);
                            updatePaginationControls(response.data);
                        },
                        error: function(data, xhr) {
                            var message = data.responseJSON.message;
                            $('.cdate-error').hide().html('');
                            if (message.date) {
                                $('.cdate-error').show().html(message.date);
                            }
                            console.log(xhr);

                        }
                    });
                }

            }

            $('#show-report').click(function() {
                getReport();
                $('.super-div2').hide();
                // getExcelReport();
            });
            $('#show-report2').click(function() {
                getReportSecond();
                $('.super-div').hide();
            });
            $('#class_id, #cdate').change(function() {
                $('.super-div').hide();
                $('.super-div2').hide();
            });

            $(document).on('click', '#std-pagination .page-link', function(e) {
                e.preventDefault();
                let page = $(this).data('page');
                getReportSecond(page);
            });

            $('#export-button1').on('click', function() {
                let session = $('#current_session').val();
                let classId = $('#class_id').val();
                let date = $('#cdate').val();

                const exportUrl = "{{ route('admin.reports.exportReportByAge') }}?session_id=" +
                    session +
                    "&class=" + classId + "&date=" + date;
                window.location.href = exportUrl;
            });


            $('#export-button2').on('click', function() {
                let session = $('#current_session').val();
                let classId = $('#class_id').val();
                let date = $('#cdate').val();

                const exportUrl = "{{ route('admin.reports.exportReportByAgeWithDetails') }}?session_id=" +
                    session +
                    "&class=" + classId + "&date=" + date;
                window.location.href = exportUrl;
            });



        });
    </script>
@endsection
