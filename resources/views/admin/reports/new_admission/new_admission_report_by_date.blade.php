@extends('admin.index')

@section('sub-content')
    <div class="container">

        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">{{ __('New Admission Report By Date') }}
                        <a href="{{ route('admin.reports.newAdmissionReport') }}" class="btn btn-warning btn-sm"
                            style="float: right;">Back</a>
                    </div>

                    <div class="card-body">

                        <div class="row mt-2">
                            <div class="form-group col-md-6">
                                <label for="session_id" class="mt-2">Session <span class="text-danger">*</span></label>
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
                                <label for="class_id" class="mt-2">Class <span class="text-danger">*</span></label>
                                <input type="hidden" name="current_session" value='' id="current_session">
                                <input type="hidden" id="initialClassId" name="initialClassId"
                                    value="{{ old('initialClassId', request()->get('class_id') !== null ? request()->get('class_id') : '') }}">
                                <select name="class_id" id="class_id"
                                    class="form-control mx-1 @error('class_id') is-invalid @enderror">
                                    <option value="">All Class</option>
                                </select>
                                @error('class_id')
                                    <span class="invalid-feedback form-invalid fw-bold" role="alert">
                                        {{ $message }}
                                    </span>
                                @enderror
                                <img src="{{ config('myconfig.myloader') }}" alt="Loading..." class="loader" id="loader"
                                    style="display:none; width:10%;">
                            </div>
                        </div>
                        <div class="row mt-2">
                            <div class="form-group col-md-6">
                                <label for="age_proof" class="mt-2">
                                    Admission By <span class="text-danger">*</span></label>
                                <select name="age_proof" id="age_proof"
                                    class="form-control @error('age_proof') is-invalid @enderror" required>
                                    <option value="1,2,3,4">All</option>
                                    <option value="1">Transfer Certificate(T.C.)</option>
                                    <option value="2">Birth Certificate</option>
                                    <option value="3">Affidavit</option>
                                    <option value="4">Aadhar Card</option>
                                </select>
                                @error('age_proof')
                                    <span class="invalid-feedback form-invalid fw-bold" role="alert">
                                        {{ $message }}
                                    </span>
                                @enderror

                            </div>
                            <div class="form-group col-md-6">
                                <label for="by_date" class="mt-2">Enter Date</label>
                                <input type="date" name="by_date" id="by_date" class="form-control">
                            </div>
                        </div>
                        <div class="mt-3">
                            <button class="btn btn-primary" type="button" id="show-report">Show Report</button>
                        </div>
                        {{-- <div class="export-div">
                             <a href="{{ route('admin.reports.exportReport') }}" class="btn btn-info" id="export-button">Export</a>
                        </div> --}}
                        <div class="super-div">
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

            const loader = $('#loader');

            // Function to clear existing report content
            function clearReportContent() {
                // $('#show-report').nextAll().remove();
                $('.super-div').nextAll().remove();
            }

            // Initial clear
            clearReportContent();
            $('.super-div').hide();
            // Function to generate table HTML based on byDate condition
            function generateTableHtml(data, byDate) {
                if (!byDate) {
                    return generateSingleTable(data);
                }

                let beforeSection = `
                            <div class="row mt-4 mb-4">
                                <div class="col-12">
                                    <table class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th colspan="3" class="bg-light">Before Section</th>
                                            </tr>
                                            <tr>
                                                <th>Class</th>
                                                <th>Students</th>
                                                <th>Total</th>
                                            </tr>
                                        </thead>
                                        <tbody>`;

                // Generate all "Before" rows
                data.forEach(value => {
                    beforeSection += `
                        <tr>
                            <td style="width: 200px;" class="align-middle">${value.class}</td>
                            <td class="p-0">
                                <table class="table table-bordered mb-0">
                                    <tr>
                                        <td style="width: 200px;">Boys --></td>
                                        <td style="width: 100px;" class="text-end">${value.before.boys}</td>
                                    </tr>
                                    <tr>
                                        <td>Girls --></td>
                                        <td class="text-end">${value.before.girls}</td>
                                    </tr>
                                </table>
                            </td>
                            <td class="align-middle text-center" style="width: 100px;">
                                ${value.before.total}
                            </td>
                        </tr>`;
                });

                beforeSection += `
                    </tbody>
                </table>
            </div>
        </div>`;

                let afterSection = `
                        <div class="row mb-4">
                            <div class="col-12">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th colspan="3" class="bg-light">After Section</th>
                                        </tr>
                                        <tr>
                                            <th>Class</th>
                                            <th>Students</th>
                                            <th>Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>`;

                // Generate all "After" rows
                data.forEach(value => {
                    afterSection += `
                            <tr>
                                <td style="width: 200px;" class="align-middle">${value.class}</td>
                                <td class="p-0">
                                    <table class="table table-bordered mb-0">
                                        <tr>
                                            <td style="width: 200px;">Boys --></td>
                                            <td style="width: 100px;" class="text-end">${value.after.boys}</td>
                                        </tr>
                                        <tr>
                                            <td>Girls --></td>
                                            <td class="text-end">${value.after.girls}</td>
                                        </tr>
                                    </table>
                                </td>
                                <td class="align-middle text-center" style="width: 100px;">
                                    ${value.after.total}
                                </td>
                            </tr>`;
                });

                afterSection += `
                                    </tbody>
                                </table>
                            </div>
                        </div>`;

                return beforeSection + afterSection;
            }

            // Helper function for non-dated tables
            function generateSingleTable(data) {
                let tableHtml = '';
                data.forEach(value => {
                    tableHtml += `
                        <div class="row mb-2 mt-4">
                            <div class="col-12">
                                <table class="table table-bordered mb-0">
                                    <tr>
                                        <td style="width: 200px;" class="align-middle">${value.class}</td>
                                        <td class="p-0">
                                            <table class="table table-bordered mb-0">
                                                <tr>
                                                    <td style="width: 200px;">Boys --></td>
                                                    <td style="width: 100px;" class="text-end">${value.boys}</td>
                                                </tr>
                                                <tr>
                                                    <td>Girls --></td>
                                                    <td class="text-end">${value.girls}</td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>`;
                });
                return tableHtml;
            }

            // Handle show report click
            $('#show-report').click(function() {
                const session = $('#session_id').val();
                const classId = $('#class_id').val();
                const ageProof = $('#age_proof').val();
                const byDate = $('#by_date').val();

                // Clear existing report content
                clearReportContent();
                $('.super-div').show();

                loader.show();

                $.ajax({
                    url: "{{ route('admin.reports.newAdmissionReportByDate') }}",
                    type: "GET",
                    data: {
                        session_id: session,
                        class: classId,
                        age_proof: ageProof,
                        by_date: byDate
                    },
                    success: function(response) {
                        if (response.data.length > 0) {
                            const byDate = $('#by_date').val();
                            const tableHtml = generateTableHtml(response.data, byDate);

                            // $('#show-report').after(tableHtml);
                            $('.super-div').html(tableHtml);
                            $('.super-div').after(`<div class="export-div">
                             <button type="button" class="btn btn-info" id="export-button">Export</button>
                            </div>`);
                        } else {
                            $('.super-div').html(
                                '<p class="text-center">No data found.</p>');
                        }
                    },
                    complete: function() {
                        loader.hide();
                    },
                    error: function(xhr) {
                        console.error(xhr.responseText);
                        $('.super-div').html(
                            '<p class="text-center text-danger">Error loading data.</p>');
                    }
                });
            });

            // Add event listeners for form field changes
            $('#class_id, #session_id, #age_proof, #by_date').change(function() {
                clearReportContent();
                $('.super-div').hide();
            });

            $(document).on('click', '#export-button', function() {
                const session = $('#session_id').val();
                const classId = $('#class_id').val();
                const ageProof = $('#age_proof').val();
                const byDate = $('#by_date').val();

                const exportUrl = "{{ route('admin.reports.exportReport') }}?session_id=" + session +
                    "&class=" + classId + "&age_proof=" + ageProof + "&by_date=" + byDate;
                window.location.href = exportUrl;
            });

        });
    </script>
@endsection
