@extends('marks.index')
@section('sub-content')
<div class="container-fluid">

    {{-- Flash Messages via SweetAlert --}}
    @if (Session::has('success'))
        @push('marks-swal-scripts')
            <script>swal("Successful", "{{ Session::get('success') }}", "success");</script>
        @endpush
    @endif
    @if (Session::has('error'))
        @push('marks-swal-scripts')
            <script>swal("Error", "{{ Session::get('error') }}", "error");</script>
        @endpush
    @endif

    <div class="row">
        <div class="col-md-12">
            <div class="card border-0 bg-white">

                <div class="card-header flex-wrap bg-white d-flex align-items-center justify-content-between">
                    <h5 class="mb-0 mt-0">Marks Report</h5>
                </div>

                <div class="card-body">

                    {{-- ── Filter Form ───────────────────────────────────────── --}}
                    <form id="marks-report-form" novalidate>
                        <div class="row">
                            {{-- Class --}}
                            <div class="form-group col-md-6">
                                <label for="marks_class_id" class="mt-2">
                                    Class <span class="text-danger">*</span>
                                </label>
                                <select name="class" id="marks_class_id" class="form-control"
                                    {{ count($classes) === 0 ? 'disabled' : '' }} required>
                                    @if (count($classes) > 0)
                                        <option value="">Select Class</option>
                                        @foreach ($classes as $key => $class)
                                            <option value="{{ $key }}" {{ request('class') == $key ? 'selected' : '' }}>
                                                {{ $class }}
                                            </option>
                                        @endforeach
                                    @else
                                        <option value="" disabled selected>No Class Found</option>
                                    @endif
                                </select>
                                <span class="invalid-feedback fw-bold" id="class-error" role="alert"></span>
                            </div>

                            {{-- Section --}}
                            <div class="form-group col-md-6">
                                <label for="marks_section_id" class="mt-2">
                                    Section <span class="text-danger">*</span>
                                </label>
                                <select name="section" id="marks_section_id" class="form-control" required>
                                    <option value="">Select Section</option>
                                </select>
                                <span class="invalid-feedback fw-bold" id="section-error" role="alert"></span>
                            </div>

                        </div>

                        <div class="row">
                            {{-- Exam --}}
                            <div class="form-group col-md-4">
                                <label for="marks_exam_id" class="mt-2"> Exam <span class="text-danger">*</span>
                                </label>
                                <select name="exam" id="marks_exam_id" class="form-control" required>
                                    @if (count($exams) > 0)
                                        <option value="">Select Exam</option>
                                        @foreach ($exams as $key => $exam)
                                            <option value="{{ $key }}" {{ old('exam') == $key ? 'selected' : '' }}>
                                                {{ $exam }}
                                            </option>
                                        @endforeach
                                    @else
                                        <option value="" disabled selected>No Exam Found</option>
                                    @endif
                                </select>
                                <span class="invalid-feedback fw-bold" id="exam-error" role="alert"></span>
                            </div>

                            {{-- Subject --}}
                            <div class="form-group col-md-4">
                                <label for="marks_subject_id" class="mt-2">
                                    Subject <span class="text-danger">*</span>
                                </label>
                                {{-- Starts as "Select Subject" until a class is chosen --}}
                                <select name="subject" id="marks_subject_id" class="form-control" required>
                                    <option value="">Select Subject</option>
                                </select>
                                <span class="invalid-feedback fw-bold" id="subject-error" role="alert"></span>
                            </div>

                            {{-- Student --}}
                            <div class="form-group col-md-4">
                                <label for="marks_student_id" class="mt-2">
                                    Student <span class="text-danger">*</span>
                                </label>
                                {{-- Starts as "Select Student" until a section is chosen --}}
                                <select name="std" id="marks_student_id" class="form-control" required>
                                    <option value="">Select Student</option>
                                </select>
                                <span class="invalid-feedback fw-bold" id="std-error" role="alert"></span>
                            </div>

                        </div>

                        <div class="mt-3 d-flex align-items-center gap-2">
                            <button type="button" id="btn-show-details" class="btn btn-primary">
                                Show Details
                            </button>
                            <img src="{{ config('myconfig.myloader') }}" alt="Loading…" id="loader" style="display:none; width:40px;">
                        </div>
                    </form>

                    {{-- ── Results Table ──────────────────────────────────────── --}}
                    <div id="std-container" class="mt-4" style="display:none;">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover align-middle">
                                <thead id="std-table-head" class="table-light"></thead>
                                <tbody id="std-table-body"></tbody>
                            </table>
                        </div>
                        <div class="mt-3">
                            <button type="button" id="btn-export" class="btn btn-success" style="display:none;">
                                Download Excel
                            </button>
                        </div>
                    </div>

                </div>{{-- /card-body --}}
            </div>
        </div>
    </div>
</div>
@endsection
@section('marks-scripts')
<script>
$(document).ready(function () {

    // ── Selectors ──────────────────────────────────────────────────────────────
    const loader        = $('#loader');
    const classSelect   = $('#marks_class_id');
    const sectionSelect = $('#marks_section_id');
    const subjectSelect = $('#marks_subject_id');
    const studentSelect = $('#marks_student_id');
    const examSelect    = $('#marks_exam_id');
    const container     = $('#std-container');
    const tableHead     = $('#std-table-head');
    const tableBody     = $('#std-table-body');
    const exportBtn     = $('#btn-export');

    // ── Helpers ────────────────────────────────────────────────────────────────
    function showLoader() { loader.show(); }
    function hideLoader() { loader.hide(); }

    function clearError(id) { $(id).text('').hide(); }

    function clearAllErrors() {
        ['#class-error', '#section-error', '#exam-error', '#subject-error', '#std-error'].forEach(id => clearError(id));
    }

    function resetTable() {
        container.hide();
        tableHead.html('');
        tableBody.html('');
        exportBtn.hide();
    }

    function showTableError(message) {
        container.show();
        tableHead.html('');
        tableBody.html(
            `<tr><td colspan="100%" class="text-center text-muted py-3">${message}</td></tr>`
        );
        exportBtn.hide();
    }

    // ── Check if select has real loaded options (not just placeholder) ─────────
    function hasRealOptions(select) {
        return select.find('option').filter(function () {
            return $(this).val() !== '' && $(this).val() !== 'all';
        }).length > 0;
    }

    // ── Load Subjects ──────────────────────────────────────────────────────────
    function loadSubjects(classId) {
        if (!classId) {
            subjectSelect.html('<option value="">Select Subject</option>');
            return;
        }
        subjectSelect.html('<option value="">Loading…</option>');
        showLoader();
        $.ajax({
            url: siteUrl + '/subjects',
            type: 'GET',
            dataType: 'JSON',
            data: { class_id: classId },
            success(data) {
                subjectSelect.empty().append('<option value="all">All Subjects</option>');
                if (data.data && Object.keys(data.data).length) {
                    $.each(data.data, (id, name) => {
                        subjectSelect.append(`<option value="${id}">${name}</option>`);
                    });
                } else {
                    subjectSelect.append('<option value="" disabled>No Subjects Found</option>');
                }
            },
            error(xhr) {
                console.error('Subjects error:', xhr.responseText);
                subjectSelect.html('<option value="">Error loading subjects</option>');
            },
            complete() { hideLoader(); }
        });
    }

    // ── Render Table ───────────────────────────────────────────────────────────
    function renderTable(subjects, students) {
        if (!students || students.length === 0) {
            showTableError('No records found.');
            return;
        }

        // Header: # | Student | Subject1 | Subject2 | … | Total
        let headerHtml = '<tr><th>#</th><th>Student</th>';
        subjects.forEach(subject => {
            headerHtml += `<th>${subject.name}</th>`;
        });
        headerHtml += '<th>Total</th></tr>';

        // Body: one row per student ordered by roll number (API already sorted)
        let bodyHtml = '';
        students.forEach((student, index) => {
            let total = 0;
            let cells = '';

            subjects.forEach(subject => {
                const entry       = student.subjects ? student.subjects[subject.id] : null;
                const marks       = entry ? entry.marks : 'N/A';
                const numericMark = (marks !== 'N/A' && marks !== null) ? parseFloat(marks) : 0;
                total += numericMark;
                cells += `<td>${(marks === null || marks === undefined) ? 'N/A' : marks}</td>`;
            });

            bodyHtml += `
                <tr>
                    <td>${index + 1}</td>
                    <td>${student.name}</td>
                    ${cells}
                    <td><strong>${total}</strong></td>
                </tr>`;
        });

        tableHead.html(headerHtml);
        tableBody.html(bodyHtml);
        container.show();
        exportBtn.show();
    }

    // ── Validate Filters ───────────────────────────────────────────────────────
    function validateFilters() {
        let valid = true;
        clearAllErrors();

        if (!classSelect.val()) {
            $('#class-error').text('Please select a class.').show();
            valid = false;
        }
        if (!sectionSelect.val()) {
            $('#section-error').text('Please select a section.').show();
            valid = false;
        }
        if (!examSelect.val()) {
            $('#exam-error').text('Please select an exam.').show();
            valid = false;
        }
        if (!hasRealOptions(studentSelect)) {
            $('#std-error').text('No students available for the selected section.').show();
            valid = false;
        }

        return valid;
    }

    // ── Fetch Report ───────────────────────────────────────────────────────────
    function fetchReport() {
        if (!validateFilters()) return;

        const classId   = classSelect.val();
        const sectionId = sectionSelect.val();
        const examId    = examSelect.val();
        const subjectId = subjectSelect.val() || 'all';
        const studentId = (studentSelect.val() && studentSelect.val() !== '') ? studentSelect.val() : 'all';

        resetTable();
        showLoader();

        $.ajax({
            url: '{{ route('marks.marks-report.get') }}',
            type: 'GET',
            dataType: 'JSON',
            data: {
                class:   classId,
                section: sectionId,
                exam:    examId,
                subject: subjectId,
                std_id:  studentId,
            },
            success(response) {
                if (response.status === 200) {
                    renderTable(response.subjects, response.data);
                } else {
                    const msg = typeof response.message === 'object'
                        ? Object.values(response.message).flat().join(' ')
                        : response.message;
                    showTableError(msg || 'Something went wrong.');
                }
            },
            error(xhr) {
                console.error('Report error:', xhr.responseText);
                showTableError('Server error. Please try again.');
            },
            complete() { hideLoader(); }
        });
    }

    // ── Excel Export ───────────────────────────────────────────────────────────
    function exportExcel() {
        const params = new URLSearchParams({
            class:   classSelect.val(),
            section: sectionSelect.val(),
            exam:    examSelect.val(),
            subject: subjectSelect.val() || 'all',
            std_id:  (studentSelect.val() && studentSelect.val() !== '') ? studentSelect.val() : 'all',
        });
        window.location.href = `{{ route('marks.marks-report.excel') }}?${params.toString()}`;
    }

    // ── Event Bindings ─────────────────────────────────────────────────────────

    // Class change → reload sections & subjects, reset student to placeholder
    classSelect.on('change', function () {
        resetTable();
        clearAllErrors();
        const classId = $(this).val();

        if (classId) {
            getMarksWithoutAllSections(classId, function () {});
        } else {
            sectionSelect.html('<option value="">Select Section</option>');
        }

        loadSubjects(classId);
        studentSelect.html('<option value="">Select Student</option>');
    });

    // Section change → reload students via global function, reset table
    sectionSelect.on('change', function () {
        resetTable();
        clearAllErrors();
        const classId   = classSelect.val();
        const sectionId = $(this).val();

        studentSelect.html('<option value="">Select Student</option>');

        if (classId && sectionId) {
            getMarksAllStudents(classId, sectionId);
        }
    });

    // Exam / Subject / Student change → reset table (re-fetch required)
    examSelect.add(subjectSelect).add(studentSelect).on('change', function () {
        resetTable();
        clearAllErrors();
    });

    // Show Details button
    $('#btn-show-details').on('click', fetchReport);

    // Export button
    exportBtn.on('click', function () {
        showLoader();
        exportExcel();
        setTimeout(hideLoader, 3000);
    });

    // ── On Page Load: restore state from URL params if present ────────────────
    const initialClass   = classSelect.val();
    const initialSection = '{{ request('section') }}';

    if (initialClass) {
        getMarksWithoutAllSections(initialClass, function () {
            if (initialSection) {
                sectionSelect.val(initialSection);
                if (sectionSelect.val()) {
                    getMarksAllStudents(initialClass, initialSection);
                }
            }
        });
        loadSubjects(initialClass);
    }

});
</script>
@endsection