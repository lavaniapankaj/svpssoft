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
                    swal("Error", "{{ Session::get('error') }}", "error");
                </script>
            @endsection
        @endif
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        {{ 'Set New Roll No. and Section' }}
                        <a href="{{ route('admin.editSection.index') }}" class="btn btn-warning btn-sm"
                            style="float: right;">Back</a>

                    </div>
                    <div class="card-body">
                        <form id="class-section-form">
                            <div class="row">
                                <div class="form-group col-md-6">
                                    <label for="class_id" class="mt-2">Class <span class="text-danger">*</span></label>
                                    <select name="class" id="class_id"
                                        class="form-control @error('class') is-invalid @enderror" required>
                                        <option value="">Select Class</option>
                                        @if (count($classes) > 0)
                                            @foreach ($classes as $key => $class)
                                                <option value="{{ $key }}" {{ old('class ') == $key ? 'selected' : ''}}>{{ $class }}</option>
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


                                <div class="form-group col-md-6">
                                    <label for="section_id" class="mt-2">Section <span
                                            class="text-danger">*</span></label>
                                    <input type="hidden" id="initialSectionId" value="{{ old('section') }}">
                                    <select name="section" id="section_id"
                                        class="form-control @error('section') is-invalid @enderror" required>
                                        <option value="">Select Section</option>

                                    </select>
                                    @error('section')
                                        <span class="invalid-feedback form-invalid fw-bold"
                                            role="alert">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>


                            <div class="mt-3">
                                <button type="button" id="show-details" class="btn btn-primary">
                                    Show Details</button>
                            </div>

                        </form>
                        <div id="std-container" class="mt-4">
                            <form action="{{ route('admin.editSection.editStdRollSection.store') }}" method="POST"
                                enctype="multipart/form-data" id="std-form">
                                @csrf
                                <table class="table table-responsible">
                                    <input type="hidden" name="current_session" value='' id="current_session">

                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>New Roll No.</th>
                                            <th>Select for Section</th>
                                        </tr>
                                    </thead>
                                    <tbody>

                                    </tbody>
                                </table>
                                <div class="row">

                                    <div class="form-group col-md-6" id="update-section-container">
                                        <label for="section_id2" class="mt-2">Section <span
                                                class="text-danger">*</span></label>
                                        <input type="hidden" id="initialSectionId" value="{{ $student->section ?? '' }}">
                                        
                                        @error('sectionSecond')
                                            <span class="invalid-feedback form-invalid fw-bold"
                                                role="alert">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="mt-3">
                                        <button type="submit" class="btn btn-primary"
                                            id="section-updateBtn">Update</button>
                                        <span id="error" class="invalid-feedback form-invalid fw-bold">Select at least
                                            one Student for Section Change</span>
                                    </div>
                                </div>
                            </form>
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
            let initialClassId = $('#class_id').val();
            let initialSectionId = $('#initialSectionId').val();
            getClassSection(initialClassId, initialSectionId);
            $('#std-form').hide();
            $('#error').hide();
            var loader = $('#loader');
            $('#class_id, #section_id').change(()=>{
                $('#std-form').hide();
                $('#error').hide();
            })
            $('#show-details').on('click', function() {
                const classId = $('#class_id').val();
                const sectionId = $('#section_id').val();
                const sessionId = $('#current_session').val();
                loader.show();
                $('#class-section-form').validate();

                const previousFormData = $('#std-form').find('input, select').serialize();

                if (classId && sectionId && sessionId) {
                    $('#std-form').show();
                    $.ajax({
                        url: '{{ route('stdNameFather.get') }}',
                        type: 'GET',
                        dataType: 'JSON',
                        data: {
                            class_id: classId,
                            section_id: sectionId,
                            session_id: sessionId,
                        },
                        success: function(students) {
                            let stdHtml = '';
                            $.each(students, function(index, std) {
                                stdHtml += `<tr>
                                            <td>${std.student_name}</td>
                                            <td>
                                                <input type="hidden" name="students[${index}][srno]" value="${std.srno}" class="std-srno" id="std-srno" data-index="${index}">
                                                <input type="text" name="students[${index}][rollno]" value='${std.rollno}' class="form-control @error('students[${index}][rollno]') is-invalid @enderror std-rollno" data-index="${index}">
                                                    <span class="invalid-feedback form-invalid fw-bold error" id="roll-error" role="alert">
                                                    </span>
                                                @error('students[${index}][rollno]')
                                                    <span class="invalid-feedback form-invalid fw-bold roll-error error" role="alert">
                                                        {{ meaasge }}
                                                    </span>
                                                    @enderror
                                            </td>
                                            <td>
                                                <input type="checkbox" name="students[${index}][sectionCheck]" value="1" class="section-checkbox" data-index="${index}">
                                                <input type="hidden" name="students[${index}][sectionSecond]" value="" class="section-hidden" data-index="${index}">
                                            </td>
                                            </tr>`;
                            });
                            if (stdHtml === '') {
                                stdHtml =
                                    '<tr><td colspan="3">No Student found</td></tr>';
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
                }
                $('#update-section-container').find("select").remove();
                var ddl = $("#section_id").clone();
                ddl.attr("name", "sectionSecond");
                ddl.attr("id", "section_id2");
                const selectedValue = $("#section_id").val();
                ddl.find("option").each(function() {
                    if ($(this).val() == selectedValue) {
                        $(this).prop("selected", true);
                    } else {
                        $(this).prop("selected", false);
                    }
                });
                $("#update-section-container").append(ddl);
                $('#std-container').on('change', '.section-checkbox', function(event) {
                    if ($(this).is(':checked') && $('#section_id2').val() == '') {
                        // $(this).val('1');
                        $('#error').show();
                        $('#error').text('Select Section');
                        event.preventDefault();

                    } else {
                        $('#error').hide();
                    }
                    if ($(this).is(':checked')) {
                        const sectionSecondValue = $('#section_id2').prop("selected", true).val();
                        $('section-hidden').val(sectionSecondValue);
                    }
                });
                $('#section_id2').on('change', function(event) {
                    const selectedSectionId = $(this).val();
                    $('.section-checkbox').each(function() {
                        const index = $(this).data('index');
                        $(`input[name="students[${index}][sectionSecond]"]`).val(
                            selectedSectionId);
                    });

                });
                $('#std-container').on('input', '.std-rollno', function(event) {
                    if (!(/^\d+$/.test($(this).val()))) {
                        event.preventDefault();
                        $(this).siblings('#roll-error').show().text('Only Numbers are allowed.');
                    } else {
                        $(this).siblings('#roll-error').hide();
                    }
                });


                $('#std-container').on('submit', function(event) {
                    if ($('#error').is(':visible') || $('#roll-error').is(':visible')) {
                        event.preventDefault();
                    } else {
                        location.reload(true);
                    }

                });
            });

        });
    </script>
@endsection
