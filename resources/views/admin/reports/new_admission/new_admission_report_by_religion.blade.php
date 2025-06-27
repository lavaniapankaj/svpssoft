@extends('admin.index')

@section('sub-content')
    <div class="container">

        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">{{ __('New Admission Report By Religion') }}
                        <a href="{{ route('admin.reports.newAdmissionReport') }}" class="btn btn-warning btn-sm"
                            style="float: right;">Back</a>
                    </div>

                    <div class="card-body">
                        <form id="basicform">

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
                                    <img src="{{ config('myconfig.myloader') }}" alt="Loading..." class="loader"
                                        id="loader" style="display:none; width:10%;">
                                </div>
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
                            </div>

                            <div class="mt-3">
                                <button class="btn btn-primary" type="button" id="show-report">Report (New & Old)</button>
                                <button class="btn btn-primary" type="button" id="show-report-new"
                                    value='NewAdmissionOnly'>Report (New Admission Only)</button>
                            </div>
                        </form>

                        <div class="super-div">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th></th>
                                        <th></th>
                                        <th>Hindu</th>
                                        <th>Muslim</th>
                                        <th>Christian</th>
                                        <th>Sikh</th>

                                    </tr>
                                </thead>
                                <tbody id="report-body">
                                </tbody>
                            </table>
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
            let initialSessionId = $('#initialSesstionId').val();
            getSession(initialSessionId);
            getClassDropDownWithAll();

            $('.super-div').hide();

            function getReport(newAdmission = '') {
                let sessionId = $('#session_id').val();
                let classId = $('#class_id').val();
                let newAdmissionId = newAdmission;
                $.ajax({
                    url: '{{ route('admin.reports.newAdmissionReportByReligion') }}',
                    type: 'GET',
                    dataType: 'JSON',
                    data: {
                        session_id: sessionId,
                        class: classId,
                        new_admission: newAdmissionId,
                    },
                    success: function(response) {
                        let tableHtml = '';
                        if (response.data.length > 0) {
                            $.each(response.data, function(index, classData) {
                                // Initialize variables to store category values
                                let boysData = {
                                    'Hindu': 0,
                                    'Muslim': 0,
                                    'Christian': 0,
                                    'Sikh': 0

                                };
                                let girlsData = {
                                    'Hindu': 0,
                                    'Muslim': 0,
                                    'Christian': 0,
                                    'Sikh': 0
                                };

                                // Map the categories data to our structure
                                classData.religions.forEach(religion => {
                                    boysData[religion.religion_name] = religion.boys;
                                    girlsData[religion.religion_name] = religion.girls;
                                });

                                // Create row for boys
                                tableHtml += `
                                        <tr>
                                            <td rowspan="2">${classData.class}</td>
                                            <td>Boys --></td>
                                            <td>${boysData['Hindu']}</td>
                                            <td>${boysData['Muslim']}</td>
                                            <td>${boysData['Christian']}</td>
                                            <td>${boysData['Sikh']}</td>
                                        </tr>`;

                                // Create row for girls
                                tableHtml += `
                                        <tr>
                                            <td>Girls --></td>
                                            <td>${girlsData['Hindu']}</td>
                                            <td>${girlsData['Muslim']}</td>
                                            <td>${girlsData['Christian']}</td>
                                            <td>${girlsData['Sikh']}</td>
                                        </tr>`;
                            });
                        } else {
                            tableHtml = '<tr><td colspan="6">No data found.</td></tr>';
                        }
                        $('#report-body').html(tableHtml);
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
                if ($('#basicform').valid()) {
                    $('.super-div').show();
                    getReport();
                    getExcelReport();

                }
            });
            $('#show-report-new').click(function() {
                if ($('#basicform').valid()) {
                    $('.super-div').show();
                    getReport($(this).val());
                    getExcelReport($(this).val());
                }
            });
            // Add event listeners for form field changes
            $('#class_id, #session_id').change(function() {
                $('.super-div').hide();
            });

            function getExcelReport(newAdmission = '') {
                $('#export-button').on('click', function() {
                    const session = $('#session_id').val();
                    const classId = $('#class_id').val();
                    let newAdmissionId = newAdmission;

                    const exportUrl = "{{ route('admin.reports.exportReportByReligion') }}?session_id=" +
                        session +
                        "&class=" + classId + "&new_admission=" + newAdmissionId;
                    window.location.href = exportUrl;
                });
            }


        });
    </script>
@endsection
