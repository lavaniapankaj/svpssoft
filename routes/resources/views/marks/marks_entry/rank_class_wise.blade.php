@extends('marks.index')
@section('sub-content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card border-0 bg-white">
                    <div class="card-header flex-wrap bg-white d-flex align-items-center justify-content-between">
                        <h5 class="mb-0 mt-0">{{ 'Rank Report' }}</h5>
                        <a href="{{ route('marks.rank-class-wise') }}" class="btn bg-light btn-sm" ><span class="mdi mdi-chevron-left me-2"></span>Back</a>
                    </div>
                    <div class="card-body">
                        <form id="class-section-form">
                            <div class="row">
                                <div class="form-group col-md-6">
                                    <label for="class_id" class="mt-2">Class <span class="text-danger">*</span></label>
                                    <input type="hidden" name="current_session" value='' id="current_session">
                                    <select name="class" id="class_id"
                                        class="form-control @error('class') is-invalid @enderror" required>
                                        <option value="">Select Class</option>
                                        @if (count($classes) > 0)
                                        @foreach ($classes as $key => $class)
                                        <option value="{{ $key }}" {{ old('class') == $key ? 'selected' : ''}}>{{ $class }}</option>
                                        @endforeach
                                        @else
                                        <option value="">No Class Found</option>
                                        @endif
                                    </select>
                                    @error('class')
                                        <span class="invalid-feedback form-invalid fw-bold"
                                            role="alert">{{ $message }}</span>
                                    @enderror

                                </div>
                            </div>
                            <div class="mt-3">
                                <button type="button" id="show-std" class="btn btn-primary">Show Student</button><img src="{{ config('myconfig.myloader') }}" alt="Loading..." class="loader" id="loader" style="display:none; width:10%;">
                            </div>
                        </form>
                        <div id="std-container" class="mt-4">
                            <table class="table table-responsible">
                                <thead>
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
                                <tbody>
                                </tbody>
                            </table>
                            <div class="export-div">
                                <button type="button" class="btn btn-info" id="export-button">Excel</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('marks-scripts')
    <script>
        $(document).ready(function() {
            $('#std-container').hide();
            $('#class_id').change(() => {
                $('#std-container').hide();
                $('#export-button').hide();
            });
            $('#show-std').click(function() {
                let classId = $('#class_id').val();
                let sessionId = $('#current_session').val();
                $('#std-container').hide();
                $('#export-button').hide();
                loader.show();
                $.ajax({
                    url: '{{ route('marks.class-wise-rank-report') }}',
                    type: 'GET',
                    dataType: 'JSON',
                    data: {
                        class: classId,
                        session: sessionId,
                    },
                    success: function(response) {
                        let stdHtml = '';
                        if (response.data && response.data.length > 0) {
                            $('#export-button').show();
                            $.each(response.data, function(index, std) {
                            stdHtml += `<tr>
                                <td>${index + 1}</td>
                                <td>${std.class}</td>
                                <td>${std.section}</td>
                                <td>${std.srno}</td>
                                <td>${std.name}</td>
                                <td>${std.total_marks}</td>
                                <td>${std.rank}</td>
                                <td>${std.total_meeting}</td>
                                <td>${std.meeting_attended}</td>
                            </tr>`;
                            });
                        } else {
                            stdHtml = '<tr><td colspan="9">No Student found</td></tr>';
                            $('#export-button').hide();
                        }
                        $('#std-container table tbody').html(stdHtml);
                    },
                    complete: function() {
                        loader.hide();
                        $('#std-container').show();
                    },
                    error: function(xhr) {
                        console.error(xhr.responseText);
                    }
                });
            });
            $('#export-button').on('click', function() {
                const classId = $('#class_id').val();
                const sessionId = $('#current_session').val();
                const exportUrl = "{{ route('marks.class-wise-rank-report-excel') }}?class=" +
                classId + "&session=" + sessionId;
                window.location.href = exportUrl;
            });
        });
    </script>
@endsection
