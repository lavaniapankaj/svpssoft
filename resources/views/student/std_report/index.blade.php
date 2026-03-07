@extends('student.index')
@section('sub-content')
    <div class="container-fluid">
        @if (Session::has('success'))
            @push('st-swal-scripts')
                <script>
                    swal("Successful", "{{ Session::get('success') }}", "success")
                </script>
            @endpush
        @endif

        @if (Session::has('error'))
            @push('st-swal-scripts')
                <script>
                    swal("Error", "{{ Session::get('error') }}", "error")
                </script>
            @endpush
        @endif

        <div class="row">
            <div class="col-md-12">
                <div class="card border-0 bg-white">
                    <div class="card-header flex-wrap bg-white d-flex align-items-center justify-content-between">
                        <h5 class="mb-0 mt-0">Student Details Class Wise</h5>
                        <a href="{{ route('student.st-report.index') }}" class="btn bg-light btn-sm">
                            <span class="mdi mdi-chevron-left me-2"></span>Back
                        </a>
                    </div>
                    <div class="card-body">
                        <form id="class-section-form" method="GET">
                            <div class="row">
                                {{-- Class --}}
                                <div class="form-group col-md-4">
                                    <label for="st_class_id" class="mt-2">Class <span class="text-danger">*</span></label>
                                    <select name="class" id="st_class_id" class="form-control"
                                        {{ count($classes) == 0 ? 'disabled' : 'required' }}>
                                        @if (count($classes) > 0)
                                            <option value="">Select Class</option>
                                            @foreach ($classes as $key => $class)
                                                <option value="{{ $key }}"
                                                    {{ request()->get('class') == $key ? 'selected' : '' }}>
                                                    {{ $class }}
                                                </option>
                                            @endforeach
                                        @else
                                            <option value="" selected disabled>No Class Found</option>
                                        @endif
                                    </select>
                                    <span class="invalid-feedback form-invalid fw-bold" id="class-error" role="alert"></span>
                                </div>

                                {{-- Section --}}
                                <div class="form-group col-md-4">
                                    <label for="st_section_id" class="mt-2">Section <span class="text-danger">*</span></label>
                                    <select name="section" id="st_section_id" class="form-control" required>
                                        <option value="">Select Section</option>
                                    </select>
                                    <span class="invalid-feedback form-invalid fw-bold" id="section-error" role="alert"></span>
                                </div>
                            </div>

                            <div class="mt-3">
                                <button type="button" id="show-report" class="btn btn-primary">Show Details</button>
                                <span class="text-danger fw-bold ms-2" id="no-data"></span>
                                <span>
                                    <img src="{{ config('myconfig.myloader') }}" alt="Loading..."
                                        id="loader" style="display:none; width:5%;">
                                </span>
                            </div>
                        </form>

                        <div class="table-responsive mt-3" id="report-table" style="display:none;">
                            <table id="report-excel" class="table table-striped table-bordered">
                                <thead>
                                    <tr>
                                        <th>Roll No.</th>
                                        <th>Class</th>
                                        <th>Section</th>
                                        <th>Admission Date</th>
                                        <th>SRNO</th>
                                        <th>Name</th>
                                        <th>Father's Name</th>
                                        <th>Mother's Name</th>
                                        <th>Grand Father's Name</th>
                                        <th>DOB</th>
                                        <th>Address</th>
                                        <th>Contact 1</th>
                                        <th>Contact 2</th>
                                        <th>Gender</th>
                                        <th>Religion</th>
                                        <th>Category</th>
                                    </tr>
                                </thead>
                                <tbody id="report-body"></tbody>
                            </table>

                            <div class="d-flex justify-content-between align-items-center mt-3">
                                <div id="export-div" style="display:none;">
                                    <button type="button" class="btn btn-info" id="export-button">
                                        <i class="bx bx-download"></i> Export to Excel
                                    </button>
                                </div>
                                <div id="std-pagination"></div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('std-scripts')
<script>
    $(document).ready(function () {

        // ------------------------------------------------------------------ //
        //  Cached selectors
        // ------------------------------------------------------------------ //
        const reportTable      = $('#report-table');
        const reportBody       = $('#report-body');
        const noData           = $('#no-data');
        const loader           = $('#loader');
        const classSelect      = $('#st_class_id');
        const sectionSelect    = $('#st_section_id');
        const paginationContainer = $('#std-pagination');
        const exportDiv        = $('#export-div');

        // ------------------------------------------------------------------ //
        //  Helper: reset report — clear rows, hide table, hide export
        // ------------------------------------------------------------------ //
        function resetReport() {
            reportTable.hide();
            reportBody.html('');
            paginationContainer.html('');
            exportDiv.hide();
            noData.text('');
        }

        // ------------------------------------------------------------------ //
        //  On page load: restore dropdown chain from URL params if present
        // ------------------------------------------------------------------ //
        if (classSelect.val()) {
            getStudentWithoutAllSections(classSelect.val(), function () {
                var savedSection = '{{ request()->get("section") }}';
                if (savedSection) {
                    sectionSelect.val(savedSection).trigger('change');
                }
            });
        }

        // ------------------------------------------------------------------ //
        //  Class change
        // ------------------------------------------------------------------ //
        classSelect.on('change', function () {
            resetReport();
            sectionSelect.html('<option value="">Select Section</option>');

            if ($(this).val()) {
                getStudentWithoutAllSections($(this).val(), function () {});
            }
        });

        // ------------------------------------------------------------------ //
        //  Section change
        // ------------------------------------------------------------------ //
        sectionSelect.on('change', function () {
            resetReport();
        });

        // ------------------------------------------------------------------ //
        //  Show Report button click
        // ------------------------------------------------------------------ //
        $('#show-report').on('click', function () {
            const classVal   = classSelect.val();
            const sectionVal = sectionSelect.val();

            if (!classVal) {
                noData.text('Please select a class.');
                return;
            }
            if (!sectionVal) {
                noData.text('Please select a section.');
                return;
            }

            noData.text('');
            stdDetails(1); // always start from page 1
        });

        // ------------------------------------------------------------------ //
        //  Pagination click
        // ------------------------------------------------------------------ //
        $(document).on('click', '#std-pagination .page-link', function (e) {
            e.preventDefault();
            e.stopPropagation();

            const clickedPage = parseInt($(this).data('page'));
            if (clickedPage && clickedPage > 0) {
                $('html, body').animate({
                    scrollTop: reportTable.offset().top - 100
                }, 300);
                stdDetails(clickedPage);
            }
        });

        // ------------------------------------------------------------------ //
        //  Helpers: label mappings
        // ------------------------------------------------------------------ //
        function genderLabel(val) {
            const map = { 1: 'Male', 2: 'Female', 3: "Other's" };
            return map[val] ?? '';
        }

        function religionLabel(val) {
            const map = { 1: 'Hindu', 2: 'Muslim', 3: 'Christian', 4: 'Sikh' };
            return map[val] ?? '';
        }

        function categoryLabel(val) {
            const map = { 1: 'General', 2: 'OBC', 3: 'SC', 4: 'ST', 5: 'BC' };
            return map[val] ?? '';
        }

        // ------------------------------------------------------------------ //
        //  AJAX: fetch students
        // ------------------------------------------------------------------ //
        function stdDetails(page) {
            const classVal   = classSelect.val();
            const sectionVal = sectionSelect.val();

            loader.show();
            reportBody.html('');
            paginationContainer.html('');

            $.ajax({
                url      : '{{ route('student.get.student-report') }}',
                type     : 'POST',
                dataType : 'json',
                headers  : { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                data: {
                    class  : classVal,
                    section: sectionVal,
                    page   : page,
                },
                success: function (response) {
                    if (response.status === 'success' && response.data.length > 0) {
                        let html = '';

                        response.data.forEach(student => {
                            html += `
                                <tr>
                                    <td>${student.rollno          ?? ''}</td>
                                    <td>${student.class           ?? ''}</td>
                                    <td>${student.section         ?? ''}</td>
                                    <td>${student.admission_date  ?? ''}</td>
                                    <td>${student.srno            ?? ''}</td>
                                    <td>${student.student_name    ?? ''}</td>
                                    <td>${student.father_name     ?? ''}</td>
                                    <td>${student.mother_name     ?? ''}</td>
                                    <td>${student.grand_father_name ?? ''}</td>
                                    <td>${student.dob             ?? ''}</td>
                                    <td>${student.address         ?? ''}</td>
                                    <td>${student.father_mobile   ?? ''}</td>
                                    <td>${student.mother_mobile   ?? ''}</td>
                                    <td>${genderLabel(student.gender)}</td>
                                    <td>${religionLabel(student.religion)}</td>
                                    <td>${categoryLabel(student.category_id)}</td>
                                </tr>
                            `;
                        });

                        reportBody.html(html);
                        reportTable.show();
                        exportDiv.show();
                        studentUpdatePaginationControls(response.paginate);

                    } else {
                        reportBody.html('<tr><td colspan="16" class="text-center">No Student Found</td></tr>');
                        reportTable.show();
                        exportDiv.hide();
                        paginationContainer.html('');
                    }
                },
                error: function (xhr) {
                    noData.text('Server error, please try again.');
                    console.error('AJAX error:', xhr);
                },
                complete: function () {
                    loader.hide();
                }
            });
        }

        // ------------------------------------------------------------------ //
        //  Export to Excel
        // ------------------------------------------------------------------ //
        $('#export-button').on('click', function () {
            const classVal   = classSelect.val();
            const sectionVal = sectionSelect.val();
            if (!classVal || !sectionVal) {
                noData.text('Please select class and section before exporting.');
                return;
            }
            const exportUrl = "{{ route('student.get.student-report.csv') }}" + '?class=' + classVal + '&section=' + sectionVal;
            window.location.href = exportUrl;
        });

    });
</script>
@endsection