@extends('student.index')
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
            <div class="col-md-14">
                <div class="card">
                    <div class="card-header">
                        {{ 'Student Details Class Wise' }}
                        <a href="{{ route('student.st-report.index') }}" class="btn btn-warning btn-sm"
                            style="float: right;">Back</a>

                    </div>
                    <div class="card-body">
                        <form id="class-section-form" method="GET">

                            <div class="row">
                                <div class="form-group col-md-6">
                                    <label for="class_id" class="mt-2">Class <span class="text-danger">*</span></label>
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


                                <div class="form-group col-md-6">
                                    <label for="section_id" class="mt-2">Section <span
                                            class="text-danger">*</span></label>
                                    <input type="hidden" id="initialSectionId"
                                        value="{{ old('section') }}">
                                    <select name="section" id="section_id"
                                        class="form-control @error('section') is-invalid @enderror" required>
                                        <option value="">Select Section</option>

                                    </select>
                                    <input type="hidden" name="current_session" value='' id="current_session">
                                    @error('section')
                                        <span class="invalid-feedback form-invalid fw-bold"
                                            role="alert">{{ $message }}</span>
                                    @enderror
                                    <img src="{{ config('myconfig.myloader') }}" alt="Loading..." class="loader"
                                        id="loader" style="display:none; width:10%;">
                                </div>

                            </div>

                            <div class="mt-3">
                                <button type="button" id="show-report" class="btn btn-primary"> Show Details</button>
                                <span class="text-danger fw-bold" id="no-data"></span>
                            </div>

                        </form>
                        <div class="row table mt-2" id="report-table">
                            <div class="table-responsive" id="st-table">

                                <table id="report-excel" class="table table-striped table-bordered">
                                    <thead>

                                        <tr>
                                            <th>Rollno.</th>
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
                                    <tbody>


                                    </tbody>

                                </table>
                            </div>
                            <div id="std-pagination"></div>
                            <button id="download-csv" type="button"
                                class="btn btn-primary mt-2 col-md-3 d-grid gap-2 col-6 mx-auto">Download Excel</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('std-scripts')
    <script>
        $(document).ready(function() {
            var loader = $('#loader');
            var reportTable = $('#report-table');
            var paginationContainer = $('#std-pagination');
            let initialClassId = $('#class_id').val();
            let initialSectionId = $('#initialSectionId').val();
            getClassSection(initialClassId, initialSectionId);
            reportTable.hide();
            $('#no-data').hide();
            $('#class-section-form').validate({
                rules: {
                    class: {
                        required: true,
                    },
                    section: {
                        required: true,
                    },
                },
                messages: {
                    class: {
                        required: "Please select a class.",
                    },
                    section: {
                        required: "Please select a section.",
                    },
                },
            });

            function stdDetails(page) {
                var classId = $('#class_id').val();
                var sectionId = $('#section_id').val();
                var sessionId = $('#current_session').val();
                if (classId && sectionId && sessionId) {
                    reportTable.show();
                    loader.show();
                    $.ajax({
                        url: '{{ route('stdNameFather.get') }}',
                        type: 'GET',
                        dataType: 'JSON',
                        data: {
                            class_id: classId,
                            section_id: sectionId,
                            session_id: sessionId,
                            page: page,
                        },
                        success: function(students) {
                            let stdHtml = '';
                            if (students.data.length > 0) {
                                students.data.forEach(student => {
                                    stdHtml += `<tr>
                                                    <td>${student.rollno ?? ''}</td>
                                                    <td>${student.class_name ?? ''}</td>
                                                    <td>${student.section_name ?? ''}</td>
                                                    <td>${student.admission_date || ''}</td>
                                                    <td>${student.srno ?? ''}</td>
                                                    <td>${student.student_name ?? ''}</td>
                                                    <td>${student.f_name ?? ''}</td>
                                                    <td>${student.m_name ?? ''}</td>
                                                    <td>${student.g_f_name ?? ''}</td>
                                                    <td>${student.dob ?? ''}</td>
                                                    <td>${student.address ?? ''}</td>
                                                    <td>${student.f_mobile ?? ''}</td>
                                                    <td>${student.m_mobile ?? ''}</td>
                                                    <td>${(student.gender == 1 ? 'Male' : (student.gender == 2 ? 'Female' : (student.gender == 3 ? "Other's" : ''))) ?? ''}</td>
                                                    <td>${(student.religion == 1 ? 'Hindu' : (student.religion == 2 ? 'Muslim' : (student.religion == 3 ? 'Christian' : 'Sikh'))) ?? ''}</td>
                                                    <td>${(student.category == 1 ? 'General' : (student.category == 2 ? 'OBC' : (student.category == 3 ? 'SC' : (student.category == 4 ? 'ST' : 'BC')))) ?? ''}</td>
                                                </tr>`;
                                });
                                $('#st-table table tbody').html(stdHtml);
                                updatePaginationControls(students);
                            } else {
                                stdHtml = `<tr><td colspan="16">No Student Found</td></tr>`;
                                $('#st-table table tbody').html(stdHtml);
                            }
                        },
                        complete: function() {
                            loader.hide();
                        },
                        error: function(xhr) {
                            alert('An error occurred while fetching the student data. Please try again.');
                            console.error(xhr.responseText);
                        }
                    });
                }
            }

            $('#show-report').click(function() {
                if ($('#class-section-form').valid()) {
                    let page = 1;
                    stdDetails(page);
                }
            });

            function getExcelReport() {
                let classId = $('#class_id').val();
                let sectionId = $('#section_id').val();
                let sessionId = $('#current_session').val();

                const exportUrl = "{{ route('student.student-report-class-wise-excel') }}?class_id=" +
                classId + "&section_id=" + sectionId + "&session_id=" + sessionId;
                window.location.href = exportUrl;
            }

            let currentPage = 1;
            $(document).on('click', '#std-pagination .page-link', function(e) {
                e.preventDefault();
                const page = $(this).data('page');
                stdDetails(page);

                currentPage = $(this).data('page');
            });

            $('#download-csv').on('click', function() {
                getExcelReport();
            });
        });
    </script>
@endsection

