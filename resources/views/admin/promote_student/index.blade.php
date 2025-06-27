@extends('admin.index')
@section('sub-content')
    <div class="container">

        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        {{ 'Promote or Demote Student' }}
                        <a href="{{ route('admin.promote-std.index') }}" class="btn btn-warning btn-sm"
                    style="float: right;">Back</a>

                    </div>
                    <div class="card-body">
                        <table id="example" class="table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th colspan="2">From</th>
                                </tr>
                            </thead>
                            <tbody>
                                <form action="" method="POST" id='promote-form'>
                                    @csrf
                                    <tr>
                                        <td>Session</td>
                                        <td>
                                            Current Session
                                            <input type="hidden" name="current_session" value=''
                                                id="current_session">
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Select Class</td>
                                        <td>
                                            <input type="hidden" id="initialClassId" name="initialClassId"
                                                value="{{ old('initialClassId', request()->get('class_id') !== null ? request()->get('class_id') : '') }}">
                                            <select name="class_id" id="class_id" class="form-control mx-1" required>
                                                <option value="">Select Class</option>
                                                @if (count($classes) > 0)
                                                    @foreach ($classes as $key => $class)
                                                        <option value="{{ $key }}"
                                                            {{ old('class_id') == $key ? 'selected' : '' }}>
                                                            {{ $class }}
                                                        </option>
                                                    @endforeach
                                                @else
                                                    <option value="">No Class Found</option>
                                                @endif
                                            </select>
                                            <span class="invalid-feedback form-invalid fw-bold first-class-error"
                                                role="alert"></span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Select Section</td>
                                        <td>
                                            <input type="hidden" id="initialSectionId" name="initialSectionId"
                                                value="{{ old('section_id') }}">
                                            <select name="section_id" id="section_id" class="form-control mx-1" required>
                                                <option value="">Select Section</option>
                                            </select>
                                            <span class="invalid-feedback form-invalid fw-bold first-section-error"
                                                role="alert"></span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Select Student</td>
                                        <td>
                                            <input type="hidden" id="initialStdId" name="initialStdId"
                                                value="{{ old('initialStdId', request()->get('std_id') !== null ? request()->get('std_id') : '') }}">
                                            <select name="std_id[]" id="std_id" class="form-control" multiple required>
                                                <option value="">Select Student</option>
                                            </select>
                                            <span class="invalid-feedback form-invalid fw-bold first-std-error"
                                                role="alert"></span>
                                        </td>
                                    </tr>

                                    <th colspan="2">To</th>

                                    <tr>
                                        <td>Entry Date</td>
                                        <td><input type="date" name="promote_date" id="promote_date" class="form-control"
                                                required>
                                            <span class="invalid-feedback form-invalid fw-bold promote-date-error"
                                                role="alert"></span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Session</td>
                                        <td>
                                            <select name="session_id" id="session_id" class="form-control">
                                                <option value="" required>Select Session</option>
                                                @foreach ($sessions as $id => $session)
                                                    <option value="{{ $id }}"
                                                        {{ old('session_id') == $id ? 'selected' : '' }}>
                                                        {{ $session }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <span class="invalid-feedback form-invalid fw-bold session-error"
                                                role="alert"></span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Select Class</td>
                                        <td>
                                            <select name="second_class_id" id="second_class_id" class="form-control mx-1">
                                                <option value="">Select Class</option>
                                                @if (count($classes) > 0)
                                                    @foreach ($classes as $key => $class)
                                                        <option value="{{ $key }}"
                                                            {{ old('second_class_id') == $key ? 'selected' : '' }}>
                                                            {{ $class }}</option>
                                                    @endforeach
                                                @else
                                                    <option value="">No Class Found</option>
                                                @endif
                                            </select>
                                            <span class="invalid-feedback form-invalid fw-bold class-error"
                                                role="alert"></span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Select Section</td>
                                        <td>
                                            <input type="hidden" id="initialSecondSectionId" name="initialSecondSectionId"
                                                value="{{ old('second_section_id') }}">
                                            <select name="second_section_id" id="second_section_id" class="form-control">
                                                <option value="">Select Section</option>
                                            </select>
                                            <span class="invalid-feedback form-invalid fw-bold section-error"
                                                role="alert"></span>
                                        </td>
                                    </tr>
                                    <tr class="promote-school-srno">
                                        <td>Enter School SRNO</td>
                                        <td>
                                            <input type="text" name="srno" id="srno"
                                                class="form-control @error('srno') is-invalid @enderror">
                                            <span class="invalid-feedback form-invalid fw-bold srno-error"
                                                role="alert"></span>
                                            @error('srno')
                                                <span class="invalid-feedback form-invalid fw-bold"
                                                    role="alert">{{ $message }}</span>
                                            @enderror
                                        </td>
                                    </tr>
                                    <tr class="promote-school-srno">
                                        <td>Public School Last SRNO</td>
                                        <td>
                                            {{ $publicSchoolSrnoLatest ?? '-' }}
                                        </td>

                                    </tr>
                                    <tr class="promote-school-srno">

                                        <td>Play School Last SRNO</td>
                                        <td>
                                            {{ $playSchoolSrnoLatest ?? '-' }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Select Student</td>
                                        <td>
                                            <select name="second_std_id[]" id="second_std_id" class="form-control" multiple>
                                                <option value="">Select Student</option>
                                            </select>

                                        </td>
                                    </tr>
                                    <tr>
                                        <td colspan="2">
                                            <button type="submit" class="btn btn-sm btn-primary"
                                                id="promote">Promote</button>
                                            <button type="submit" class="btn btn-sm btn-primary" id="promoteSchool">Promote
                                                St. School</button>
                                            <button type="submit" class="btn btn-sm btn-primary"
                                                id="promoteSchool2">Promote St. School</button>
                                            <input type="hidden" name="tc" value="" id="tc-input">
                                            <button type="submit" class="btn btn-sm btn-primary" id="tc">TC to
                                                Student</button>
                                            <input type="hidden" name="leftOut" value="" id="left-input">
                                            <button type="submit" class="btn btn-sm btn-primary" id="left">Left
                                                Out</button>
                                            <input type="hidden" name="allStd" value="" id="allSt-input">
                                            <button type="submit" class="btn btn-sm btn-primary" id="full-class">Promote
                                                Full Class</button>
                                        </td>
                                    </tr>
                                </form>
                            </tbody>

                        </table>


                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('admin-scripts')
    <script>

        $(document).ready(function() {
            var classSelect = $('#class_id');
            var sectionSelect = $('#section_id');
            var sessionSelect = $('#current_session');
            var stdSelect = $('#std_id');
            var secondClassSelect = $('#second_class_id');
            var secondSectionSelect = $('#second_section_id');
            var stdSecondSelect = $('#second_std_id');
            let initialStdId = $('#initialStdId').val();
            let initialSectionId = $('#initialSectionId').val();
            let initialSecondClassId = secondClassSelect.val();
            let initialSecondSectionId = secondSectionSelect.val();
            let initialSecondStdId = $('#second_std_id').val();
            getClassSection(classSelect.val(), initialSectionId);
            getClassSection(initialSecondClassId, $('#initialSecondSectionId').val(), secondClassSelect, secondSectionSelect);
            $('.srno-error').hide();
            $('.promote-school-srno').hide();
            $('#promoteSchool2').hide();
            stdSelect.select2();
            stdSecondSelect.select2();


            function fetchStdNameFather(classId, sectionId, sessionId, stdDropdown, callback) {
                if (classId && sectionId && sessionId) {
                    $.ajax({
                        url: '{{ route('stdNameFather.get') }}',
                        type: 'GET',
                        data: {
                            class_id: classId,
                            section_id: sectionId,
                            session_id: sessionId,
                        },
                        success: function(data) {
                            populateDropdowns(data, stdDropdown);
                            if (callback) callback(data);
                        },
                        error: function(xhr) {
                            console.error('Error fetching student details:', xhr);
                        }
                    });
                } else {
                    stdDropdown.empty();
                    stdDropdown.append('<option value="">Select Student</option>');
                }
            }

            function handlePrimaryDropdowns() {
                var classId = classSelect.val();
                var sectionId = sectionSelect.val();
                var sessionId = sessionSelect.val();
                fetchStdNameFather(classId, sectionId, sessionId, stdSelect, function(data) {
                    if (initialStdId) {
                        stdSelect.val(initialStdId).trigger('change');
                    }
                });
            }

            function handleSecondaryDropdowns() {
                var classId = secondClassSelect.val();
                var sectionId = secondSectionSelect.val();
                var sessionId = $('#session_id').val();
                fetchStdNameFather(classId, sectionId, sessionId, stdSecondSelect, function(data) {
                    if (initialSecondStdId) {
                        stdSecondSelect.val(initialSecondStdId).trigger('change');
                    }
                });
            }

            function populateDropdowns(data, dropdown) {
                dropdown.empty();
                dropdown.append('<option value="">Select Student</option>');
                $.each(data, function(id, value) {
                    if (value.ssid == 1) {
                        dropdown.append('<option value="' + value.srno + '">' +
                            value.rollno + '. ' + value.student_name + '/SH. ' + value.f_name +
                            '</option>');

                    }
                });
            }
            handlePrimaryDropdowns();
            handleSecondaryDropdowns();

            classSelect.change(handlePrimaryDropdowns);
            sectionSelect.change(handlePrimaryDropdowns);
            secondClassSelect.change(handleSecondaryDropdowns);
            secondSectionSelect.change(handleSecondaryDropdowns);

            $('#class_id').change(function() {
                $('#hidden_class_id').val($(this).val());
            });
            $('#section_id').change(function() {
                $('#hidden_section_id').val($(this).val());
            });

            $('#session_id').change(function() {
                getClassSection(initialSecondClassId, initialSecondSectionId, secondClassSelect,
                    secondSectionSelect);
            });

            $('#promoteSchool').on('click', function(e) {
                e.preventDefault();
                $('.promote-school-srno').show();
                $('#srno').attr('required');

            });
            $('#srno').on('input', function() {
                if ($('#srno').val() !== '') {
                    $('#promoteSchool').hide();
                    $('#promoteSchool2').show();
                } else {
                    $('#promoteSchool').show();
                    $('#promoteSchool2').hide();
                }
            });

            function formSubmit(e) {
                $('.first-std-error').hide().html('');
                $('.first-class-error').hide().html('');
                $('.first-section-error').hide().html('');
                $('.class-error').hide().html('');
                $('.section-error').hide().html('');
                $('.session-error').hide().html('');
                $('.srno-error').hide().html('');
                e.preventDefault();
                $.ajax({
                    data: $('#promote-form').serialize(),
                    url: "{{ route('admin.promote-std.store') }}",
                    type: "POST",
                    dataType: 'JSON',
                    success: function(data) {
                        Swal.fire({
                            title: 'Successful',
                            text: data.success,
                            icon: 'success',
                            confirmButtonColor: 'rgb(122 190 255)',
                        }).then(() => {
                            location.reload();
                        });

                    },
                    error: function(data) {
                        let message = data.responseJSON.message;

                        $('.first-class-error').hide().html('');
                        $('.first-section-error').hide().html('');
                        $('.class-error').hide().html('');
                        $('.section-error').hide().html('');
                        $('.session-error').hide().html('');
                        $('.srno-error').hide().html('');

                        if (message.std_id) {
                            $('.first-std-error').show().html(message.std_id);

                        }
                        if (message.class_id) {
                            $('.first-class-error').show().html(message.class_id);
                        }
                        if (message.section_id) {
                            $('.first-section-error').show().html(message.section_id);
                        }
                        if (message.second_class_id) {
                            $('.class-error').show().html(message.second_class_id);
                        }
                        if (message.second_section_id) {
                            $('.section-error').show().html(message.second_section_id);
                        }
                        if (message.session_id) {
                            $('.session-error').show().html(message.session_id);
                        }
                        if (message.promote_date) {
                            $('.promote-date-error').show().html(message.promote_date);
                        }
                        if (message.srno) {
                            $('.srno-error').show().html(message.srno);
                        }
                    }
                });
            }

            $('#tc').on('click', function(e) {
                $('.class-error').hide().html('');
                $('.section-error').hide().html('');
                $('.session-error').hide().html('');
                $('.srno-error').hide().html('');
                $('#tc-input').val('4');
                formSubmit(e);
            });
            $('#left').on('click', function(e) {
                $('.class-error').hide().html('');
                $('.section-error').hide().html('');
                $('.session-error').hide().html('');
                $('.srno-error').hide().html('');
                $('#left-input').val('5');
                formSubmit(e);
            });
            $('#full-class').on('click', function(e) {
                $('#std_id').find('option').each(function() {
                    if ($(this).val() !== '') {
                        $(this).attr('selected', true);
                    }
                });
                $('#allSt-input').val('2');
                formSubmit(e);

            });
            $('#promoteSchool2').click(function(e) {
                // $('.srno-error').hide().html('');
                $('.class-error').html('').hide();
                $('.section-error').html('').hide();
                $('.session-error').html('').hide();
                formSubmit(e);
            });
            $('#promote').click(function(e) {
                formSubmit(e);
            });

        });
    </script>
@endsection
