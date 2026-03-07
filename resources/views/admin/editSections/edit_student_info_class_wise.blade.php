@extends('admin.index')

@section('sub-content')
    <div class="container-fluid">

        @if (Session::has('success'))
            @push('swal-scripts')
                <script>swal("Successful", "{{ Session::get('success') }}", "success");</script>
            @endpush
        @endif
        @if (Session::has('error'))
            @push('swal-scripts')
                <script>swal("Error", "{{ Session::get('error') }}", "error");</script>
            @endpush
        @endif

        <div class="row">
            <div class="col-md-12">
                <div class="card border-0 bg-white">
                    <div class="card-header flex-wrap bg-white d-flex align-items-center justify-content-between">
                        <h5 class="mb-0 mt-0">{{ __('Update Student Info. Class Wise') }}</h5>
                        <a href="{{ route('admin.editSection.index') }}" class="btn bg-light btn-sm">
                            <span class="mdi mdi-chevron-left me-2"></span>Back
                        </a>
                    </div>

                    <div class="card-body">
                        <form id="class-section-form" novalidate>
                            <div class="row">
                                {{-- Class --}}
                                <div class="form-group col-md-4 mb-3">
                                    <label for="admin_class_id" class="form-label fw-semibold">
                                        Class <span class="text-danger">*</span>
                                    </label>
                                    <select name="class" id="admin_class_id"
                                        class="form-control @error('class') is-invalid @enderror"
                                        {{ count($classes) === 0 ? 'disabled' : 'required' }}>
                                        @if (count($classes) > 0)
                                            <option value="">— Select Class —</option>
                                            @foreach ($classes as $key => $class)
                                                <option value="{{ $key }}"
                                                    {{ old('class', request('class')) == $key ? 'selected' : '' }}>
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

                                {{-- Section --}}
                                <div class="form-group col-md-4 mb-3">
                                    <label for="admin_section_id" class="form-label fw-semibold">
                                        Section <span class="text-danger">*</span>
                                    </label>
                                    <select name="section" id="admin_section_id" class="form-control" required>
                                        <option value="">— Select Section —</option>
                                    </select>
                                    @error('section')
                                        <span class="invalid-feedback fw-bold" role="alert">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="mt-3 d-flex align-items-center gap-2">
                                <button type="button" id="show-details" class="btn btn-primary">
                                    Show Details
                                </button>
                                <img src="{{ config('myconfig.myloader') }}"
                                    alt="Loading…"
                                    id="loader"
                                    class="loader"
                                    style="display:none; width:5%;">
                            </div>
                        </form>

                        {{-- ─── Student Form ─── --}}
                        <div id="std-container" class="mt-4">
                            <form action="{{ route('admin.editSection.editStdInfoClass.store') }}"
                                method="POST" id="std-form" style="display:none;">
                                @csrf
                                <input type="hidden" name="class"   id="hidden-class">
                                <input type="hidden" name="section" id="hidden-section">
                                <input type="hidden" name="current_session" id="current_session" value="">

                                <div class="table-responsive">
                                    <table class="table table-bordered">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Roll No.</th>
                                                <th>SRNO</th>
                                                <th>Name</th>
                                                <th>Father Name</th>
                                                <th>Mother Name</th>
                                                <th>Grand Father Name</th>
                                                <th>DOB</th>
                                                <th>Contact 1</th>
                                                <th>Contact 2</th>
                                                <th>Age Proof</th>
                                            </tr>
                                        </thead>
                                        <tbody id="std-tbody"></tbody>
                                    </table>
                                </div>

                                <div class="mt-3">
                                    <button type="submit" class="btn btn-primary" id="section-updateBtn">
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

    var hasValidationErrors = @json($errors->any());
    var oldInputs           = @json(old('students', []));
    var validationErrors    = @json($errors->toArray());
    var oldClass            = @json(old('class',   request('class')));
    var oldSection          = @json(old('section', request('section')));

    /* ── Helpers ──────────────────────────────────────────────── */
    function validateFilterForm() {
        var errors = false;

        if (!$('#admin_class_id').val()) {
            $('#admin_class_id').addClass('is-invalid');
            if (!$('#admin_class_id').siblings('.server-error').length) {
                $('#admin_class_id').after('<span class="invalid-feedback server-error fw-bold" role="alert">Please select a class.</span>');
            }
            errors = true;
        } else {
            $('#admin_class_id').removeClass('is-invalid').siblings('.server-error').remove();
        }

        if (!$('#admin_section_id').val()) {
            $('#admin_section_id').addClass('is-invalid');
            if (!$('#admin_section_id').siblings('.server-error').length) {
                $('#admin_section_id').after('<span class="invalid-feedback server-error fw-bold" role="alert">Please select a section.</span>');
            }
            errors = true;
        } else {
            $('#admin_section_id').removeClass('is-invalid').siblings('.server-error').remove();
        }

        return !errors;
    }

    function syncHiddenFields() {
        $('#hidden-class').val($('#admin_class_id').val());
        $('#hidden-section').val($('#admin_section_id').val());
    }

    /* ── Build student table rows ─────────────────────────────── */
    function buildStudentRows(students) {
        if (!students || !students.length) {
            $('#section-updateBtn').hide();
            return '<tr><td colspan="10" class="text-center text-muted">No students found.</td></tr>';
        }

        var ageProofOptions = {
            1: 'Birth Certificate',
            2: 'Transfer Certificate',
            3: 'Affidavit',
            4: 'Aadhar Card'
        };

        var html = '';

        $.each(students, function (index, std) {

            function val(field) {
                return (oldInputs[index] !== undefined && oldInputs[index][field] !== undefined)
                    ? oldInputs[index][field]
                    : (std[field] !== null && std[field] !== undefined ? std[field] : '');
            }

            function errClass(field) {
                return validationErrors['students.' + index + '.' + field] ? ' is-invalid' : '';
            }

            function errMsg(field) {
                var key = 'students.' + index + '.' + field;
                return validationErrors[key]
                    ? '<div class="invalid-feedback">' + validationErrors[key][0] + '</div>'
                    : '';
            }

            function textInput(field) {
                return '<input type="text"' +
                    ' name="students[' + index + '][' + field + ']"' +
                    ' value="' + (val(field) || '') + '"' +
                    ' class="form-control' + errClass(field) + '">' +
                    errMsg(field);
            }

            // Age proof select
            var ageProofVal = val('age_proof');
            var ageProofSelect = '<select name="students[' + index + '][age_proof]"' +
                ' class="form-control' + errClass('age_proof') + '">' +
                '<option value="">Select Age Proof</option>';
            $.each(ageProofOptions, function (optVal, optLabel) {
                ageProofSelect += '<option value="' + optVal + '"' +
                    (ageProofVal == optVal ? ' selected' : '') + '>' + optLabel + '</option>';
            });
            ageProofSelect += '</select>' + errMsg('age_proof');

            // DOB input
            var dobVal = val('dob') || '';
            var dobInput = '<input type="date"' +
                ' name="students[' + index + '][dob]"' +
                ' value="' + dobVal + '"' +
                ' class="form-control' + errClass('dob') + '">' +
                errMsg('dob');

            html +=
                '<tr>' +
                    '<td>' + (std.rollno || '') + '</td>' +
                    '<td>' + (std.srno   || '') + '</td>' +
                    '<td>' +
                        '<input type="hidden" name="students[' + index + '][srno]" value="' + std.srno + '">' +
                        textInput('student_name') +
                    '</td>' +
                    '<td>' + textInput('f_name')   + '</td>' +
                    '<td>' + textInput('m_name')   + '</td>' +
                    '<td>' + textInput('g_f_name') + '</td>' +
                    '<td>' + dobInput              + '</td>' +
                    '<td>' + textInput('f_mobile') + '</td>' +
                    '<td>' + textInput('m_mobile') + '</td>' +
                    '<td>' + ageProofSelect        + '</td>' +
                '</tr>';
        });

        $('#section-updateBtn').show();
        return html;
    }

    /* ── Load students ────────────────────────────────────────── */
    function loadStudents() {
        var classId   = $('#admin_class_id').val();
        var sectionId = $('#admin_section_id').val();
        var sessionId = $('#current_session').val();

        if (!classId || !sectionId || !sessionId) {
            $('#std-form').hide();
            return;
        }

        syncHiddenFields();
        $('#loader').show();
        $('#std-form').show();

        $.ajax({
            url     : '{{ route('stdNameFather.get') }}',
            type    : 'GET',
            dataType: 'json',
            data    : {
                class_id   : classId,
                section_id : sectionId,
                session_id : sessionId,
            },

            success: function (students) {
                $('#std-tbody').html(buildStudentRows(students));
            },

            error: function (xhr) {
                console.error('Load students error:', xhr);
                $('#std-tbody').html(
                    '<tr><td colspan="10" class="text-center text-danger">Error loading students. Please try again.</td></tr>'
                );
            },

            complete: function () {
                $('#loader').hide();
            }
        });
    }

    /* ── Event: class change → load sections via global fn ───── */
    $('#admin_class_id').on('change', function () {
        var classId = $(this).val();

        // Clear section error
        $('#admin_section_id').removeClass('is-invalid').siblings('.server-error').remove();

        // Hide student form until new selection is confirmed
        $('#std-form').hide();
        $('#section-updateBtn').hide();
        $('#std-tbody').html('');

        // Use global function (without "All" option)
        getAdminWithoutAllSections(classId, function () {
            // After sections load: if restoring old section, set it
            if (oldSection && classId == oldClass) {
                $('#admin_section_id').val(oldSection);
            }
            syncHiddenFields();
        });
    });

    /* ── Event: section change → sync hidden field ────────────── */
    $('#admin_section_id').on('change', function () {
        $('#admin_section_id').removeClass('is-invalid').siblings('.server-error').remove();
        $('#std-form').hide();
        $('#section-updateBtn').hide();
        $('#std-tbody').html('');
        syncHiddenFields();
    });

    /* ── Event: Show Details button ───────────────────────────── */
    $('#show-details').on('click', function () {
        // Clear old inputs/errors if filter has changed
        var classId   = $('#admin_class_id').val();
        var sectionId = $('#admin_section_id').val();

        if (classId != oldClass || sectionId != oldSection) {
            oldInputs        = [];
            validationErrors = {};
        }

        if (!validateFilterForm()) return;

        loadStudents();
    });

    /* ── On page load: restore class → load sections → restore section ── */
    if (oldClass) {
        $('#admin_class_id').val(oldClass);

        getAdminWithoutAllSections(oldClass, function () {
            if (oldSection) {
                $('#admin_section_id').val(oldSection);
                syncHiddenFields();

                // Auto-load students if restoring after validation error
                if (hasValidationErrors) {
                    loadStudents();
                }
            }
        });
    }

});
</script>
@endsection