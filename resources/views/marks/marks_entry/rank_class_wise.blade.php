@extends('marks.index')
@section('sub-content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card border-0 bg-white">
                    <div class="card-header flex-wrap bg-white d-flex align-items-center justify-content-between">
                        <h5 class="mb-0 mt-0">Rank Report</h5>
                        <a href="{{ route('marks.rank-class-wise') }}" class="btn bg-light btn-sm">
                            <span class="mdi mdi-chevron-left me-2"></span>Back
                        </a>
                    </div>

                    <div class="card-body">

                        {{-- Filter Form --}}
                        <form id="class-section-form" autocomplete="off">
                            <div class="row">
                                <div class="form-group col-md-6">
                                    <label for="class_id" class="mt-2">
                                        Class <span class="text-danger">*</span>
                                    </label>
                                    <select name="class" id="class_id"
                                        class="form-control @error('class') is-invalid @enderror"
                                        required>
                                        <option value="">Select Class</option>
                                        @if (count($classes) > 0)
                                            @foreach ($classes as $key => $class)
                                                <option value="{{ $key }}"
                                                    {{ old('class') == $key ? 'selected' : '' }}>
                                                    {{ $class }}
                                                </option>
                                            @endforeach
                                        @else
                                            <option value="" disabled>No Class Found</option>
                                        @endif
                                    </select>
                                    {{-- Inline validation message --}}
                                    <span class="invalid-feedback form-invalid fw-bold d-none"
                                          id="class-error" role="alert">
                                        Please select a class.
                                    </span>
                                </div>
                            </div>

                            <div class="mt-3 d-flex align-items-center gap-2">
                                <button type="button" id="show-std" class="btn btn-primary">
                                    Show Students
                                </button>
                                {{-- Loader sits next to the button, hidden until needed --}}
                                <img src="{{ config('myconfig.myloader') }}"
                                     alt="Loading..."
                                     id="loader"
                                     style="display:none; width:32px;">
                            </div>
                        </form>

                        {{-- Results: hidden until a successful response arrives --}}
                        <div id="std-container" class="mt-4" style="display:none;">
                            <div class="table-responsive">
                                <table class="table table-striped table-bordered align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>S.No.</th>
                                            <th>Class</th>
                                            <th>Section</th>
                                            <th>SRNO</th>
                                            <th>Name</th>
                                            <th>Total Obt. Marks</th>
                                            <th>Rank</th>
                                            <th>Total Meetings</th>
                                            <th>Meetings Attended</th>
                                        </tr>
                                    </thead>
                                    <tbody id="std-tbody"></tbody>
                                </table>
                            </div>

                            {{-- Export button: only visible when rows exist --}}
                            <div id="export-div" style="display:none;" class="mt-2">
                                <button type="button" id="export-button" class="btn btn-success">
                                    <span class="mdi mdi-file-excel me-1"></span>Export Excel
                                </button>
                            </div>
                        </div>
                        {{-- /.std-container --}}

                    </div>
                    {{-- /.card-body --}}
                </div>
            </div>
        </div>
    </div>
@endsection

@section('marks-scripts')
<script>
$(document).ready(function () {

    // ------------------------------------------------------------------ //
    //  Helper: reset validation state on the class dropdown
    // ------------------------------------------------------------------ //
    function clearClassError() {
        $('#class_id').removeClass('is-invalid');
        $('#class-error').addClass('d-none');
    }

    function showClassError() {
        $('#class_id').addClass('is-invalid');
        $('#class-error').removeClass('d-none');
    }

    // ------------------------------------------------------------------ //
    //  Helper: reset the results area
    // ------------------------------------------------------------------ //
    function resetResults() {
        $('#std-container').hide();
        $('#std-tbody').html('');
        $('#export-div').hide();
    }

    // ------------------------------------------------------------------ //
    //  Class dropdown change — clear results and any previous error
    // ------------------------------------------------------------------ //
    $('#class_id').on('change', function () {
        clearClassError();
        resetResults();
    });

    // ------------------------------------------------------------------ //
    //  Show Students button
    // ------------------------------------------------------------------ //
    $('#show-std').on('click', function () {
        if (!$('#class_id').val()) {
            showClassError();
            return;
        }

        clearClassError();
        resetResults();
        $('#loader').show();

        $.ajax({
            url     : '{{ route('marks.class-wise-rank-report') }}',
            type    : 'GET',
            dataType: 'json',
            data    : {
                class: $('#class_id').val(),
            },
            success: function (response) {
                var rows = '';

                if (response.data && response.data.length > 0) {
                    $.each(response.data, function (index, std) {
                        rows += '<tr>'
                              + '<td>' + (index + 1)          + '</td>'
                              + '<td>' + std.class            + '</td>'
                              + '<td>' + std.section          + '</td>'
                              + '<td>' + std.srno             + '</td>'
                              + '<td>' + std.name             + '</td>'
                              + '<td>' + std.total_marks      + '</td>'
                              + '<td>' + std.rank             + '</td>'
                              + '<td>' + std.total_meeting    + '</td>'
                              + '<td>' + std.meeting_attended + '</td>'
                              + '</tr>';
                    });
                    $('#export-div').show();
                } else {
                    rows = '<tr><td colspan="9" class="text-center">No students found.</td></tr>';
                    $('#export-div').hide();
                }

                $('#std-tbody').html(rows);
                $('#std-container').show();
            },
            error: function (xhr) {
                $('#std-tbody').html(
                    '<tr><td colspan="9" class="text-center text-danger">Something went wrong. Please try again.</td></tr>'
                );
                $('#std-container').show();
                console.error('Rank report error:', xhr.responseText);
            },
            complete: function () {
                $('#loader').hide();
            }
        });
    });

    // ------------------------------------------------------------------ //
    //  Export to Excel
    // ------------------------------------------------------------------ //
    $('#export-button').on('click', function () {
        if (!$('#class_id').val()) {
            showClassError();
            return;
        }

        window.location.href = '{{ route('marks.class-wise-rank-report-excel') }}'
            + '?class=' + $('#class_id').val();
    });

});
</script>
@endsection