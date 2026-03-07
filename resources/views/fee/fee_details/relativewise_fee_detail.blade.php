@extends('fee.index')
@section('sub-content')
    <div class="container-fluid">

        <div class="row">
            <div class="col-md-12">
                <div class="card border-0 bg-white">
           <div class="card-header flex-wrap bg-white d-flex align-items-center justify-content-between"><h5 class="mb-0 mt-0">{{ 'Fee Details (Relative Wise)' }}</h5>
                        <a href="{{ route('fee.fee-detail-relaive-wise') }}" class="btn bg-light btn-sm" ><span class="mdi mdi-chevron-left me-2"></span>Back</a>

                    </div>
                    <div class="card-body">
                        <form id="class-section-form">
                            <div class="row">
                                <div class="form-group col-md-6">
                                     <label for="fee_class_id" class="mt-2">Class <span class="text-danger">*</span></label>
                                    <select name="class" id="fee_class_id" class="form-control" {{ count($classes) == 0 ? 'disabled' : 'required' }}>
                                        @if (count($classes) > 0)
                                            <option value="all">All Class</option>
                                            @foreach ($classes as $key => $class)
                                                <option value="{{ $key }}" {{ request()->get('class') == $key ? 'selected' : '' }}>{{ $class }}</option>
                                            @endforeach
                                        @else
                                            <option value="" selected disabled>No Class Found</option>
                                        @endif
                                    </select>
                                    <span class="invalid-feedback form-invalid fw-bold" id="class-error" role="alert"></span>

                                </div>


                                <div class="form-group col-md-6">
                                     <label for="fee_section_id" class="mt-2">Section <span class="text-danger">*</span></label>
                                    <select name="section" id="fee_section_id" class="form-control" required></select>
                                    <span class="invalid-feedback form-invalid fw-bold" id="section-error" role="alert"></span>
                                </div>
                            </div>
                            <div class="row">
                                <div class="form-group col-md-6">
                                    <label for="fee_student_id" class="mt-2">Student <span class="text-danger">*</span></label>
                                    <select name="std_id" id="fee_student_id" class="form-control " required></select>
                                    <span class="invalid-feedback form-invalid fw-bold" id="std-error" role="alert"></span>
                                </div>
                            </div>
                            <div class="mt-3">
                                <button type="button" class="btn btn-primary" id="show-details">Show</button>
                                <span>
                                    <img src="{{ config('myconfig.myloader') }}" alt="Loading..." class="loader" id="loader" style="display:none; width:5%;">
                                </span>
                            </div>

                        </form>
                        <div id="complete-fee-table" class="table-responsive mt-5" style="display: none;">
                            <table class="table table-striped table-bordered">
                                <thead>
                                    <th>Class</th>
                                    <th>Section</th>
                                    <th>Name</th>
                                    <th>Father's Name</th>
                                    <th>Payable Amount(Ac.)</th>
                                    <th>Paid Amount(Ac.)</th>
                                    <th>Due Amount(Ac.)</th>
                                    <th>Payable Amount(Tr.)</th>
                                    <th>Paid Amount(Tr.)</th>
                                    <th>Due Amount(Tr.)</th>
                                    <th>Payable Amount(St.)</th>
                                    <th>Paid Amount(St.)</th>
                                    <th>Due Amount(St.)</th>
                                    <th>Details</th>
                                </thead>
                                <tbody>

                                </tbody>
                            </table>
                            <div class="d-flex justify-content-between align-items-center mt-3">
                                <div class="export-div" style="display: none;">
                                    <button type="button" class="btn btn-info" id="export-button">
                                        <i class="bx bx-download"></i> Export to Excel
                                    </button>
                                </div>
                                <div id="due-std-pagination"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('fee-scripts')
    <script>
        $(document).ready(function() {

            let page = 1;
            let classId = $('#fee_class_id');
            let sectionId = $('#fee_section_id');

            if (classId.val() && classId.val() != '') {
                getFeeAllSections(classId.val(), function() {
                    let selectedSection = sectionId.val();
                    if (!selectedSection || selectedSection == '') {
                        sectionId.val('all');
                        selectedSection = 'all';
                    }
                    getFeeAllStudents(classId.val(), selectedSection);
                });
            }

            classId.change(function() {
                let selectedClass = $(this).val();
                page = 1;
                $('#complete-fee-table').hide();
                $('#complete-fee-table table tbody').html('');
                $('#due-std-pagination').html('');
                $('.export-div').hide();

                getFeeAllSections(selectedClass, function() {
                    sectionId.val('all');
                    getFeeAllStudents(selectedClass, 'all');
                });
            });

            sectionId.change(function() {
                page = 1;
                $('#complete-fee-table').hide();
                $('#complete-fee-table table tbody').html('');
                $('#due-std-pagination').html('');
                $('.export-div').hide();

                getFeeAllStudents(classId.val(), $(this).val());
            });

            $('#fee_student_id').change(function() {
                page = 1;
                $('#complete-fee-table').hide();
                $('#complete-fee-table table tbody').html('');
                $('#due-std-pagination').html('');
                $('.export-div').hide();
            });


            function loadFeeReport(pageNum) {
                page = pageNum;
                let selectedClassVal = classId.val();
                let selectedSectionVal = sectionId.val();
                let st = $('#fee_student_id').val();

                if(selectedClassVal && selectedSectionVal && st) {
                    $('#complete-fee-table').show();

                    if (typeof loader !== 'undefined') {
                        loader.show();
                    }

                    $.ajax({
                        url: "{{ route('fee.std-due-fee-report.relaive-wise.get') }}",
                        type: 'POST',
                        dataType: 'JSON',
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        data: {
                            class: selectedClassVal,
                            section: selectedSectionVal,
                            srno: st,
                            page: page,
                        },
                        success: function(response) {
                            let stdHtml = '';

                            if (response.status === 'success' && response.data && response.data.length > 0) {
                                response.data.forEach(value => {
                                    stdHtml += `<tr>
                                        <td>${value.class}</td>
                                        <td>${value.section}</td>
                                        <td>${value.student_name}</td>
                                        <td>${value.father_name}</td>
                                        <td>${value.academic_total_payable}</td>
                                        <td>${value.academic_total_paid}</td>
                                        <td>${value.academic_total_due}</td>
                                        <td>${value.transport_total_payable}</td>
                                        <td>${value.transport_total_paid}</td>
                                        <td>${value.transport_total_due}</td>
                                        <td>N/A</td>
                                        <td>N/A</td>
                                        <td>N/A</td>
                                        <td><a href='${siteUrl}/fee/individual-fee-details/${value.srno}/${value.session_id}/${value.class_id}/${value.section_id}'  class="btn btn-sm btn-icon p-1"> <i class="mdi mdi-eye mx-1" data-bs-toggle="tooltip"
                                                                data-bs-offset="0,4" data-bs-placement="top" title="View"></i></a></td>
                                    </tr>`;

                                    /* Process relatives data*/
                                    if (value.relatives && value.relatives.length > 0) {
                                        value.relatives.forEach(relative => {
                                            stdHtml += `<tr class="table-warning">
                                                <td>${relative.class}</td>
                                                <td>${relative.section}</td>
                                                <td>${relative.student_name}</td>
                                                <td>${relative.father_name}</td>
                                                <td>${relative.academic_total_payable}</td>
                                                <td>${relative.academic_total_paid}</td>
                                                <td>${relative.academic_total_due}</td>
                                                <td>${relative.transport_total_payable}</td>
                                                <td>${relative.transport_total_paid}</td>
                                                <td>${relative.transport_total_due}</td>
                                                <td>N/A</td>
                                                <td>N/A</td>
                                                <td>N/A</td>
                                                <td><a href='${siteUrl}/fee/individual-fee-details/${relative.srno}/${relative.session_id}/${relative.class_id}/${relative.section_id}' class="btn btn-sm btn-icon p-1"> <i class="mdi mdi-eye mx-1" data-bs-toggle="tooltip"
                                                               data-bs-offset="0,4" data-bs-placement="top" title="View"></i></a></td>
                                        </tr>`;
                                        });
                                    }
                                });

                                // Show export button when data is available
                                $('.export-div').show();
                            } else {
                                stdHtml = '<tr><td colspan="15" class="text-center">No Student Record Found</td></tr>';
                                // Hide export button when no data
                                $('.export-div').hide();
                            }

                            $('#complete-fee-table table tbody').html(stdHtml);

                            if (response.pagination) {
                                dueUpdatePaginationControls(response.pagination);
                            } else {
                                $('#due-std-pagination').html('');
                            }
                        },
                        complete: function() {
                            if (typeof loader !== 'undefined') {
                                loader.hide();
                            }
                        },
                        error: function(xhr) {
                            if (typeof loader !== 'undefined') {
                                loader.hide();
                            }
                            let errorMsg = 'Error loading data';
                            if (xhr.responseJSON && xhr.responseJSON.message) {
                                errorMsg = xhr.responseJSON.message;
                            }
                            $('#complete-fee-table table tbody').html(`<tr><td colspan="12" class="text-center text-danger">${errorMsg}</td></tr>`);
                            $('#due-std-pagination').html('');
                            $('.export-div').hide();
                        }
                    });
                }
            }

            $('#show-details').click(function() {
                page = 1;
                loadFeeReport(1);
            });

            $(document).on('click', '#due-std-pagination .page-link', function(e) {
                e.preventDefault();
                e.stopPropagation();

                let clickedPage = parseInt($(this).data('page'));
                if (clickedPage && clickedPage > 0) {
                    $('html, body').animate({
                        scrollTop: $("#complete-fee-table").offset().top - 100
                    }, 300);

                    loadFeeReport(clickedPage);
                }

                return false;
            });

            // Export button click handler
            $('#export-button').click(function() {
                let selectedClassVal = classId.val();
                let selectedSectionVal = sectionId.val();
                let st = $('#fee_student_id').val();

                if(!selectedClassVal || !selectedSectionVal || !st) {
                    return;
                }

                // Show loading state
                let $btn = $(this);
                let originalText = $btn.html();
                $btn.prop('disabled', true).html('<i class="bx bx-loader bx-spin"></i> Exporting...');
                // Build export URL with parameters
                let exportUrl = "{{ route('fee.fee-detail-relaive-wise-excel') }}" +
                    '?class=' + encodeURIComponent(selectedClassVal) +
                    '&section=' + encodeURIComponent(selectedSectionVal) +
                    '&srno=' + encodeURIComponent(st);

                // Trigger download
                window.location.href = exportUrl;

                // Reset button after delay
                setTimeout(function() {
                    $btn.prop('disabled', false).html(originalText);
                }, 2000);
                // First check if data exists
                /* $.ajax({
                    url: "{{ route('fee.fee-detail-relaive-wise-excel') }}",
                    type: 'GET',
                    data: {
                        class: selectedClassVal,
                        section: selectedSectionVal,
                        srno: st,
                    },
                    success: function(response) {
                        if (response.status === 'success') {
                            // Data exists, proceed with download
                            // Build export URL with parameters
                            let exportUrl = "{{ route('fee.fee-detail-relaive-wise-excel') }}" +
                                '?class=' + encodeURIComponent(selectedClassVal) +
                                '&section=' + encodeURIComponent(selectedSectionVal) +
                                '&srno=' + encodeURIComponent(st);
                                window.location.href = exportUrl;
                            // Reset button after a delay
                            setTimeout(function() {
                                $btn.prop('disabled', false).html(originalText);
                            }, 2000);
                        } else {
                            $btn.prop('disabled', false).html(originalText);
                        }
                    },
                    error: function(xhr) {
                        let errorMsg = 'No data available to export';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMsg = xhr.responseJSON.message;
                        }
                        $btn.prop('disabled', false).html(originalText);
                    }
                }); */
            });
        });
    </script>
@endsection
