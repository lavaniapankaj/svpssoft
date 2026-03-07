@extends('admin.index')
@section('sub-content')
    <div class="container-fluid">
        @if (Session::has('success'))
            @push('swal-scripts')
                <script>
                    swal("Successful", "{{ Session::get('success') }}", "success");
                </script>
            @endpush
        @endif
        @if (Session::has('error'))
            @push('swal-scripts')
                <script>
                    swal("Error", "{{ Session::get('error') }}", "error");
                </script>
            @endpush
        @endif
        <div class="row ">
            <div class="col-md-12">
                <div class="card border-0 bg-white">
                    <div class="card-header flex-wrap bg-white d-flex align-items-center justify-content-between">
                        <h5 class="mb-0 mt-0">{{ 'Add / Delete Admission Date' }}</h5>
                        <a href="{{ route('admin.editSection.index') }}" class="btn bg-light btn-sm">
                            <span class="mdi mdi-chevron-left me-2"></span>Back
                        </a>
                    </div>
                    <div class="card-body">
                        <form id="date-form">
                            <div class="row align-items-end">
                                <div class="form-group col-md-6">
                                    <label for="srno" class="mt-2">Enter SRNO. <span class="text-danger">*</span></label>
                                    <input type="text" id="srno" name="srno" class="form-control @error('srno') is-invalid @enderror" value="{{ old('srno') }}" placeholder="Enter SRNO. of Student">
                                    @error('srno')
                                        <span class="invalid-feedback form-invalid fw-bold" role="alert">{{ $message }}</span>
                                    @enderror
                                </div>
                                <div class="form-group col-md-3 d-flex gap-2">
                                    <button type="button" id="show-details" class="btn btn-primary">Show Details</button>
                                    <img src="{{ config('myconfig.myloader') }}" alt="Loading..." class="loader" id="loader" style="display:none; width:5%;">
                                </div>
                            </div>
                            <span class="invalid-feedback form-invalid fw-bold" id="srno-error" role="alert" style="display: none;"></span>
                        </form>

                        <div id="std-container" class="mt-4" style="display: {{ old('hidden_st_id') ? 'block' : 'none' }};">
                            <table class="table table-responsive">
                                <input type="hidden" name="current_session" value='' id="current_session">
                                <thead>
                                    <tr>
                                        <th>SRNO</th>
                                        <th>Name</th>
                                        <th>Father's Name</th>
                                        <th>Mother's Name</th>
                                        <th>Class</th>
                                        <th>Section</th>
                                        <th>Admission Date</th>
                                    </tr>
                                </thead>
                                <tbody id="student-table-body">
                                    <!-- Data will be loaded here -->
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-4" id="change-date-container" style="display: {{ old('hidden_st_id') ? 'block' : 'none' }};">
                            <form action="{{ route('admin.editSection.editStdAdmissionDate.store') }}" method="post" id="a_date_form">
                                @csrf
                                <div class="row align-items-end">
                                    <div class="form-group col-md-6">
                                        <input type="hidden" name="hidden_st_id" value="{{ old('hidden_st_id') }}" id="hidden_st_id">
                                        <input type="hidden" name="srno" value="{{ old('srno') }}" id="hidden_srno">
                                        <label for="a_date" class="mt-2">Change Date<span class="text-danger">*</span></label>
                                        <input type="date" id="a_date" name="a_date" class="form-control @error('a_date') is-invalid @enderror" value="{{ old('a_date') }}" placeholder="Select Date">
                                        @error('a_date')
                                            <span class="invalid-feedback form-invalid fw-bold d-block" role="alert">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="form-group col-md-6">
                                        <button type="submit" id="change-date" class="btn btn-primary">Update</button>
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
        $(document).ready(function () {
            const loader = $('#loader');
            const stdContainer = $('#std-container');
            const changeDateContainer = $('#change-date-container');
            const srnoError = $('#srno-error');

            // Check if there's old data (validation error occurred)
            @if(old('hidden_st_id') && old('srno'))
                // Reload student data if validation error occurred
                const oldSrno = '{{ old('srno') }}';
                if (oldSrno) {
                    loadStudentData(oldSrno);
                }
            @endif

            $('#show-details').on('click', function () {
                const srno = $('#srno').val().trim();

                srnoError.hide();
                stdContainer.hide();
                changeDateContainer.hide();

                if (!srno) {
                    srnoError.text('Please enter SRNO.').show();
                    return;
                }

                loadStudentData(srno);
            });

            function loadStudentData(srno) {
                loader.show();

                $.ajax({
                    url: '{{ route('admin.editSection.admissionDate.st') }}',
                    type: 'GET',
                    dataType: 'JSON',
                    data: { srno: srno },

                    success: function (response) {
                        let html = '';
                        if (response.data) {
                            html += `
                                <tr>
                                    <td>${response.data.srno}</td>
                                    <td>${response.data.student_name || '-'}</td>
                                    <td>${response.data.f_name || '-'}</td>
                                    <td>${response.data.m_name || '-'}</td>
                                    <td>${response.data.class_name || '-'}</td>
                                    <td>${response.data.section_name || '-'}</td>
                                    <td>${response.data.admission_date || '-'}</td>
                                </tr>
                            `;

                            // Only update the date input if there's no old value (no validation error)
                            if (!$('#a_date').val()) {
                                $('#a_date').val(response.data.admission_date);
                            }
                            $('#hidden_st_id').val(response.data.id);
                            $('#hidden_srno').val(response.data.srno);

                            stdContainer.show();
                            changeDateContainer.show();
                        } else {
                            html = `<tr><td colspan="7" class="text-center">No student found</td></tr>`;
                            stdContainer.show();
                            changeDateContainer.hide();
                        }

                        $('#student-table-body').html(html);
                    },

                    error: function (xhr) {
                        if (xhr.status === 400 && xhr.responseJSON?.message) {
                            srnoError.text(Object.values(xhr.responseJSON.message)[0][0]).show();
                        } else if (xhr.status === 200 && xhr.responseJSON?.status === 'error') {
                            // Handle custom error response
                            const errors = xhr.responseJSON.message;
                            if (typeof errors === 'object') {
                                srnoError.text(Object.values(errors)[0][0]).show();
                            } else {
                                srnoError.text(errors).show();
                            }
                        } else {
                            srnoError.text('Something went wrong. Please try again.').show();
                        }
                    },
                    complete: function () {
                        loader.hide();
                    }
                });
            }

            // Add SRNO to form on submit to preserve it after validation error
            $('#a_date_form').on('submit', function () {
                const srno = $('#srno').val();
                $('#hidden_srno').val(srno);
            });
        });
    </script>
@endsection