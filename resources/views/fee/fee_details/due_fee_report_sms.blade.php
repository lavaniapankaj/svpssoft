@extends('fee.index')
@section('sub-content')
    <div class="container-fluid">

        <div class="row justify-content-center">
            <div class="col-md-12">
                <div class="card border-0 bg-white">
                    <div class="card-header flex-wrap bg-white d-flex align-items-center justify-content-between">
                        <h5 class="mb-0 mt-0">{{ 'Due Fee SMS' }}</h5>
                        <a href="{{ route('fee.due-fee-report-sms') }}" class="btn bg-light btn-sm" ><span class="mdi mdi-chevron-left me-2"></span>Back</a>
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
                                <div class="form-group col-md-6">
                                    <label for="report" class="mt-2">Select Report <span class="text-danger">*</span></label>
                                    <select name="report" id="report" class="form-control" required>
                                        <option value="firstInstDue" selected>Ist Installment</option>
                                        <option value="secondInstDue">IInd Installment</option>
                                    </select>
                                </div>
                            </div>

                            <div class="mt-3">
                                <button type="button" id="show-details" class="btn btn-primary">Show Details</button>
                                <span><img src="{{ config('myconfig.myloader') }}" alt="Loading..." class="loader" id="loader" style="display:none; width:5%;"></span>
                            </div>

                        </form>

                        <div id="complete-fee-table" class="table-responsive mt-5" style="display: none">
                            <table class="table table-striped table-bordered">
                                <thead>
                                    <th>Class</th>
                                    <th>Section</th>
                                    <th>Name</th>
                                    <th>F. Name</th>
                                    <th>Payable(Ac.)</th>
                                    <th>Paid(Ac.)</th>
                                    <th>Due(Ac.)</th>
                                    <th>Payable(Tr.)</th>
                                    <th>Paid(Tr.)</th>
                                    <th>Due(Tr.)</th>
                                    <th>Total Due</th>
                                    <th>Details</th>
                                </thead>
                                <tbody></tbody>
                            </table>
                            <div id="due-std-pagination"></div>

                            {{-- <div class="mt-3" id="sms-div">
                                <form action="" method="post">
                                    @csrf
                                    <div class="form-group col-md-12 message-box" style="display: none;">
                                        <label for="report" class="mt-2">Enter Your Message<span class="text-danger">*</span></label>
                                        <textarea name="message" id="message" cols="30" rows="10" class="form-control" placeholder="Enter your message. (Add {%student_name%} in place of the student name and at due amount add {%total_due%}.)" required></textarea>
                                    </div>
                                </form>
                                <button type="submit" id="sms-send" class="btn btn-success mt-3">SMS</button>
                            </div> --}}

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
                // Hide table and clear content when class changes
                $('#complete-fee-table').hide();
                $('#complete-fee-table table tbody').html('');
                $('#due-std-pagination').html('');

                getFeeAllSections(selectedClass, function() {
                    sectionId.val('all');
                    getFeeAllStudents(selectedClass, 'all');
                });
            });

            sectionId.change(function() {
                page = 1;
                // Hide table and clear content when section changes
                $('#complete-fee-table').hide();
                $('#complete-fee-table table tbody').html('');
                $('#due-std-pagination').html('');

                getFeeAllStudents(classId.val(), $(this).val());
            });

            $('#report').change(function() {
                page = 1;
                // Hide table and clear content when report type changes
                $('#complete-fee-table').hide();
                $('#complete-fee-table table tbody').html('');
                $('#due-std-pagination').html('');
            });

            $('#fee_student_id').change(function() {
                page = 1;
                // Hide table and clear content when student changes
                $('#complete-fee-table').hide();
                $('#complete-fee-table table tbody').html('');
                $('#due-std-pagination').html('');
            });

            function loadFeeReport(pageNum) {
                page = pageNum;
                let selectedClassVal = classId.val();
                let selectedSectionVal = sectionId.val();
                let st = $('#fee_student_id').val();
                let reportType = $('#report').val();

                if(selectedClassVal && selectedSectionVal && st && reportType) {
                    $('#complete-fee-table').show();

                    if (typeof loader !== 'undefined') {
                        loader.show();
                    }

                    $.ajax({
                        // url: "{{ route('fee.due-fee-report-sms.get') }}",
                        url: "{{ route('fee.std-due-fee-report') }}",
                        type: 'POST',
                        dataType: 'JSON',
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        data: {
                            /* class_id: selectedClassVal,
                            section_id: selectedSectionVal,
                            student: st, */
                            class: selectedClassVal,
                            section: selectedSectionVal,
                            srno: st,
                            reportType: reportType,
                            page: page,
                        },
                        success: function(response) {
                            let stdHtml = '';

                            if (response.status === 'success' && response.data && response.data.length > 0) {
                                response.data.forEach(value => {
                                    /* stdHtml += `<tr>
                                        <td>${value.class_name}</td>
                                        <td>${value.section_name}</td>
                                        <td>${value.student_name}</td>
                                        <td>${value.father_name}</td>
                                        <td>${reportType == 'firstInstDue' ? value.first_inst_payable : value.second_inst_payable}</td>
                                        <td>${reportType == 'firstInstDue' ? value.first_inst_paid : value.second_inst_paid}</td>
                                        <td>${value.due_amount}</td>
                                        <td>${reportType == 'firstInstDue' ? value.trans_first_inst_payable : value.trans_second_inst_payable}</td>
                                        <td>${reportType == 'firstInstDue' ? value.trans_first_inst_paid : value.trans_second_inst_paid}</td>
                                        <td>${value.trans_due}</td>
                                        <td>${parseInt(value.due_amount) + parseInt(value.trans_due)}</td>
                                        <td>
                                            <a href='${siteUrl}/fee/back-session/individual-fee-details/${value.srno}/${value.session_id}/${value.class_id}/${value.section_id}' class="btn btn-sm btn-icon p-1" target="_blank">
                                                <i class="mdi mdi-eye mx-1" data-bs-toggle="tooltip" data-bs-offset="0,4" data-bs-placement="top" title="View"></i>
                                            </a>
                                        </td>
                                    </tr>`; */

                                    stdHtml += `<tr>
                                            <td>${value.class_name}</td>
                                            <td>${value.section_name}</td>
                                            <td>${value.student_name}</td>
                                            <td>${value.father_name}</td>
                                            <td>${value.academic_payable_amount}</td>
                                            <td>${value.academic_paid_amount}</td>
                                            <td>${value.academic_due_amount}</td>
                                            <td>${value.transport_payable_amount}</td>
                                            <td>${value.transport_paid_amount}</td>
                                            <td>${value.transport_due_amount}</td>
                                            <td>${value.total_due}</td>
                                            <td>
                                                <a href='${siteUrl}/fee/back-session/individual-fee-details/${value.student_srno}/${value.session_id}/${value.class}/${value.section}'
                                                class="btn btn-sm btn-icon p-1" target="_blank">
                                                    <i class="mdi mdi-eye mx-1" data-bs-toggle="tooltip" data-bs-offset="0,4" data-bs-placement="top" title="View"></i>
                                                </a>
                                            </td>
                                        </tr>`;
                                });
                            } else {
                                stdHtml = '<tr><td colspan="12" class="text-center">No Student Record Found</td></tr>';
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
                        }
                    });
                } else {
                    console.log('Please select all required fields');
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
        });
    </script>
@endsection
