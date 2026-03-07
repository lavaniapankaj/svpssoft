@extends('admin.index')

@section('sub-content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card border-0 bg-white">
                <div class="card-header flex-wrap bg-white d-flex align-items-center justify-content-between">
                    <h5 class="mb-0 mt-0">{{ __('Edit Attendance Entry') }}</h5>
                    <a href="{{ route('admin.editSection.index') }}" class="btn bg-light btn-sm">
                        <span class="mdi mdi-chevron-left me-2"></span>Back
                    </a>
                </div>

                <div class="card-body">

                    {{-- ─── Filter Form ─── --}}
                    <form id="filter-form" novalidate>
                        <div class="row">
                            <div class="form-group col-md-12">
                                <label for="a_date" class="mt-2">
                                    Enter Date <span class="text-danger">*</span>
                                </label>
                                <input type="date" name="a_date" id="a_date"
                                    class="form-control @error('a_date') is-invalid @enderror">
                                @error('a_date')
                                    <span class="invalid-feedback fw-bold" role="alert">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="row mt-2">
                            <div class="form-group col-md-6">
                                <label for="admin_class_id" class="mt-2">
                                    Class <span class="text-danger">*</span>
                                </label>
                                <select name="class" id="admin_class_id"
                                    class="form-control @error('class') is-invalid @enderror"
                                    {{ count($classes) === 0 ? 'disabled' : 'required' }}>
                                    @if (count($classes) > 0)
                                        <option value="">Select Class</option>
                                        @foreach ($classes as $key => $class)
                                            <option value="{{ $key }}"
                                                {{ old('class') == $key ? 'selected' : '' }}>
                                                {{ $class }}
                                            </option>
                                        @endforeach
                                    @else
                                        <option value="" disabled selected>No Class Found</option>
                                    @endif
                                </select>
                                @error('class')
                                    <span class="invalid-feedback fw-bold" role="alert">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="form-group col-md-6">
                                <label for="admin_section_id" class="mt-2">
                                    Section <span class="text-danger">*</span>
                                </label>
                                <select name="section" id="admin_section_id"
                                    class="form-control @error('section') is-invalid @enderror" required>
                                    <option value="">Select Section</option>
                                </select>
                                @error('section')
                                    <span class="invalid-feedback fw-bold" role="alert">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="mt-3 d-flex align-items-center gap-2">
                            <button type="button" id="show-details" class="btn btn-primary">Show Details</button>
                            <img src="{{ config('myconfig.myloader') }}" alt="Loading…" id="loader" class="loader" style="display:none; width:5%;">
                        </div>
                    </form>

                    {{-- ─── Attendance Form ─── --}}
                    <div id="std-container" class="mt-4">
                        <form id="std-form" style="display:none;" novalidate>
                            @csrf
                            <input type="hidden" name="current_session" id="current_session" value="">

                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Roll No.</th>
                                            <th>Name</th>
                                            <th>Father's Name</th>
                                            <th>Attendance</th>
                                        </tr>
                                    </thead>
                                    <tbody id="std-tbody"></tbody>
                                </table>
                            </div>

                            <div id="std-pagination" class="mb-3"></div>

                            <div class="mt-3">
                                <button type="button" class="btn btn-primary" id="section-updateBtn" style="display:none;">
                                    Update
                                </button>
                            </div>
                        </form>
                    </div>

                </div>{{-- /.card-body --}}
            </div>{{-- /.card --}}
        </div>
    </div>
</div>
@endsection

@section('admin-scripts')
<script>
$(function () {

    var oldClass   = @json(old('class'));
    var oldSection = @json(old('section'));

    /* ── Helpers ──────────────────────────────────────────────── */
    function clearErrors() {
        $('#filter-form .is-invalid').removeClass('is-invalid');
        $('#filter-form .server-error').remove();
    }

    function showFieldError(selector, message) {
        var input = $(selector);
        input.addClass('is-invalid');
        if (!input.siblings('.server-error').length) {
            input.after(
                '<span class="invalid-feedback server-error fw-bold" role="alert">' + message + '</span>'
            );
        }
    }

    function validateForm() {
        var valid = true;

        if (!$('#a_date').val()) {
            showFieldError('#a_date', 'Please select a date.');
            valid = false;
        }
        if (!$('#admin_class_id').val()) {
            showFieldError('#admin_class_id', 'Please select a class.');
            valid = false;
        }
        if (!$('#admin_section_id').val()) {
            showFieldError('#admin_section_id', 'Please select a section.');
            valid = false;
        }

        return valid;
    }

    function resetStudentForm() {
        $('#std-form').hide();
        $('#std-tbody').html('');
        $('#section-updateBtn').hide();
    }


    /* ── Load students ────────────────────────────────────────── */
    function loadStudents() {
        var classId   = $('#admin_class_id').val();
        var sectionId = $('#admin_section_id').val();

        $('#loader').show();
        $('#std-form').show();

        $.ajax({
            url     : '{{ route('getStdForDropDown') }}',
            type    : 'GET',
            dataType: 'json',
            data    : {
                class_id   : classId,
                section_id : sectionId,
            },

            success: function (response) {
                var students = (response && response.data) ? response.data : [];
                var html = '';

                if (students.length) {
                    $.each(students, function (index, std) {
                        html +=
                            '<tr>' +
                                '<td>' + (std.rollno || '') + '</td>' +
                                '<td>' +
                                    '<input type="hidden" name="students[' + index + '][srno]" value="' + std.srno + '">' +
                                    (std.student_name || '') +
                                '</td>' +
                                '<td>' + (std.father_name || '') + '</td>' +
                                '<td>' +
                                    '<input type="checkbox"' +
                                        ' name="students[' + index + '][status]"' +
                                        ' value="1"' +
                                        ' class="status-checkbox"' +
                                        ' checked>' +
                                '</td>' +
                            '</tr>';
                    });
                    $('#section-updateBtn').show();
                } else {
                    html = '<tr><td colspan="4" class="text-center text-muted">No students found.</td></tr>';
                    $('#section-updateBtn').hide();
                }

                $('#std-tbody').html(html);
            },

            error: function (xhr) {
                console.error('Load students error:', xhr);
                $('#std-tbody').html(
                    '<tr><td colspan="4" class="text-center text-danger">Failed to load students. Please try again.</td></tr>'
                );
                $('#section-updateBtn').hide();
            },

            complete: function () {
                $('#loader').hide();
            }
        });
    }

    /* ── Event: class change → load sections via global fn ───── */
    $('#admin_class_id').on('change', function () {
        $('#admin_class_id').removeClass('is-invalid').siblings('.server-error').remove();
        resetStudentForm();

        getAdminWithoutAllSections($(this).val(), function () {
            if (oldSection && $('#admin_class_id').val() == oldClass) {
                $('#admin_section_id').val(oldSection);
            }
        });
    });

    /* ── Event: section / date change → reset ─────────────────── */
    $('#admin_section_id, #a_date').on('change', function () {
        $(this).removeClass('is-invalid').siblings('.server-error').remove();
        resetStudentForm();
    });

    /* ── Event: Show Details ──────────────────────────────────── */
    $('#show-details').on('click', function () {
        clearErrors();
        if (!validateForm()) return;
        loadStudents();
    });

    /* ── Collect student checkbox data for submit ─────────── */
    function getStudentsData() {
        var students = [];
        $('#std-tbody tr').each(function (index) {
            var srno   = $(this).find('input[type="hidden"]').val();
            var status = $(this).find('.status-checkbox').is(':checked') ? 1 : 0;
            students.push({ srno: srno, status: status });
        });
        return students;
    }

    /* ── Event: Update button ─────────────────────────────────── */
    $('#section-updateBtn').on('click', function () {
        $('#loader').show();

        $.ajax({
            url     : '{{ route('admin.editSection.editStdAttendance.store') }}',
            type    : 'POST',
            data    : {
                _token         : $('meta[name="csrf-token"]').attr('content'),
                current_session: $('#current_session').val(),
                class          : $('#admin_class_id').val(),
                section        : $('#admin_section_id').val(),
                a_date         : $('#a_date').val(),
                students       : getStudentsData(),
            },
            dataType: 'json',

            success: function (data) {
                Swal.fire({
                    title             : data.status === 'success' ? 'Successful' : 'Error',
                    text              : data.message || 'Something went wrong.',
                    icon              : data.status === 'success' ? 'success' : 'error',
                    confirmButtonColor: 'rgb(122 190 255)',
                });
            },

            error: function (xhr) {
                var message = (xhr.responseJSON && xhr.responseJSON.message)
                    ? xhr.responseJSON.message
                    : 'Something went wrong!';

                Swal.fire({
                    title             : 'Error',
                    text              : message,
                    icon              : 'error',
                    confirmButtonColor: 'rgb(122 190 255)',
                });

                console.error('Update attendance error:', xhr);
            },

            complete: function () {
                $('#loader').hide();
            }
        });
    });

    /* ── On page load: restore class → load sections ─────────── */
    if (oldClass) {
        $('#admin_class_id').val(oldClass);

        getAdminWithoutAllSections(oldClass, function () {
            if (oldSection) {
                $('#admin_section_id').val(oldSection);
            }
        });
    }

});
</script>
@endsection