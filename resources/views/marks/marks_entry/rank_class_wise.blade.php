@extends('marks.index')
@section('sub-content')
    <div class="container">

        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        {{ 'Rank Report' }}
                        <a href="{{ route('marks.rank-class-wise') }}" class="btn btn-warning btn-sm"
                            style="float: right;">Back</a>

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
                                    <img src="{{ config('myconfig.myloader') }}" alt="Loading..." class="loader"
                                        id="loader" style="display:none; width:10%;">
                                </div>
                            </div>

                            <div class="mt-3">
                                <button type="button" id="show-std" class="btn btn-primary">
                                    Show Student</button>
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
            $('#show-std').click(function() {
                let classId = $('#class_id').val();
                let sessionId = $('#current_session').val();
                $('#std-container').show();
                $.ajax({
                    url: '{{ route('marks.class-wise-rank-report') }}',
                    type: 'GET',
                    dataType: 'JSON',
                    data: {
                        class: classId,
                        session: sessionId,
                    },
                    success: function(response) {
                        console.log(response.data);

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
