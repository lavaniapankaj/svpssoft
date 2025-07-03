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
                    <div class="card-header">{{ __('Transport Fee Master') }}
                        <a href="{{ route('admin.transport-fee-master.create') }}" class="btn btn-info"
                            style="float: right;">Add</a>
                        <div class="col-lg-12 col-md-12">
                            <div class="right-item d-flex justify-content-end mt-4">
                                <form action="" method="get" class="d-flex">
                                    <input type="hidden" name="current_session" value='' id="current_session">
                                    <select name="class_id" id="class_id" class="form-control mx-1" required>
                                        <option value="">Select Class</option>
                                        @if (count($classes) > 0)
                                            @foreach ($classes as $key => $class)
                                                <option value="{{ $key }}" {{ old('section_id', request()->get('class_id') !== null ? request()->get('class_id') : '') == $key ? 'selected' : ''}}>{{ $class }}</option>
                                            @endforeach
                                        @else
                                            <option value="">No Class Found</option>
                                        @endif
                                    </select>
                                    <input type="hidden" id="initialSectionId"
                                        value="{{ old('section_id', request()->get('section_id') !== null ? request()->get('section_id') : '') }}">
                                    <select name="section_id" id="section_id" class="form-control mx-1" required>
                                        <option value="">Select Section</option>
                                    </select>
                                    <input type="hidden" id="initialStdId"
                                        value="{{ old('std_id', request()->get('std_id') !== null ? request()->get('std_id') : '') }}">
                                    <select name="std_id" id="std_id" class="form-control mx-1" required>
                                        <option value="">Select Student</option>
                                    </select>
                                    <img src="{{ config('myconfig.myloader') }}" alt="Loading..." class="loader"
                                        id="loader" style="display:none; width:10%;">
                                    <button type="submit" class="btn btn-sm btn-info mx-2" id="search">Search</button>
                                </form>
                                <a href="{{ route('admin.transport-fee-master.index') }}" class="btn btn-warning">Reset</a>
                            </div>
                        </div>
                    </div>

                    <div class="card-body">

                        <div class="table" id="std-container" style="display: none;">
                            <table id="example" class="table table-striped table-bordered">
                                <thead>
                                    <tr>
                                        <th>Ist Installment</th>
                                        <th>IInd Installment</th>
                                        <th>Discount</th>
                                        <th>Total</th>

                                    </tr>
                                </thead>
                                <tbody class="">
                                </tbody>
                            </table>

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
            let classSelect = $('#class_id');
            let sectionSelect = $('#section_id');
            let sessionSelect = $('#current_session');
            let stdSelect = $('#std_id');
            let initialSectionId = $('#initialSectionId').val();
            let initialStdId = $('#initialStdId').val();
            getClassSection(classSelect.val(), initialSectionId);

            function fetchStdNameFather(classId, sectionId, sessionId) {
                if (classId && sectionId && sessionId) {
                    $.ajax({
                        url: '{{ route('stdNameFather.get') }}',
                        type: 'GET',
                        dataType: 'JSON',
                        data: {
                            class_id: classId,
                            section_id: sectionId,
                            session_id: sessionId,
                        },
                        success: function(data) {
                            stdSelect.empty();
                            stdSelect.append('<option value="">Select Student</option>');
                            $.each(data, function(id, value) {
                                stdSelect.append('<option value="' + value.srno + '">' +
                                    ++id + '. ' + value.student_name + '/' + value.f_name +
                                    '</option>');
                            });
                            $('#search').on('click', function(e) {
                                e.preventDefault();
                                var selectedStdId = stdSelect.val();
                                var selectedStudent = data.find(student => student.srno ===
                                    selectedStdId);
                                var scheduleHtml = ''
                                if (selectedStudent) {
                                    scheduleHtml += `<tr>
                                    <td>
                                        ${selectedStudent.trans_1st_inst ?? '-'}
                                     </td>
                                    <td>${selectedStudent.trans_2nd_inst  ?? '-'}</td>
                                    <td>
                                        ${selectedStudent.trans_discount ?? '-'}
                                    </td>
                                    <td>
                                        ${selectedStudent.trans_total ?? '-'}
                                    </td>
                                    </tr>`;
                                    $('#std-container table tbody').html(scheduleHtml);

                                }
                                $('#std-container').show();
                            });

                            if (typeof initialStdId !== 'undefined') {
                                stdSelect.val(initialStdId);
                            }
                        },
                        error: function(xhr) {
                            console.error('Error fetching student detail:', xhr);
                        }
                    });
                } else {
                    stdSelect.empty();
                    stdSelect.append('<option value="">Select Student</option>');
                }
            }

            var selectedClassId = classSelect.val();
            var selectedSectionId = sectionSelect.val();
            var selectedSessionId = sessionSelect.val();
            if (selectedClassId && selectedSectionId && selectedSessionId) {
                fetchStdNameFather(selectedClassId, selectedSectionId, selectedSessionId);
            }
            classSelect.change(()=>{
                stdSelect.empty();
                stdSelect.append('<option value="">Select Student</option>');
            });
            sectionSelect.change(function() {
                var sectionId = $(this).val();
                var classId = classSelect.val();
                var sessionId = sessionSelect.val();
                fetchStdNameFather(classId, sectionId, sessionId);
            });

        });
    </script>
@endsection
