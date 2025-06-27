@extends('fee.index')
@section('sub-content')
    <div class="container">

        <div class="row justify-content-center">
            <div class="col-md-14">
                <div class="card">
                    <div class="card-header">
                        {{ 'Due Fee Report' }}
                        <a href="{{ route('fee.due-fee-report') }}" class="btn btn-warning btn-sm"
                            style="float: right;">Back</a>
                    </div>
                    <div class="card-body">
                        <form id="class-section-form">
                            <div class="row">

                                <div class="form-group col-md-6">
                                    <label for="back_class_id" class="mt-2">Class <span
                                            class="text-danger">*</span></label>
                                    <select name="class" id="back_class_id" class="form-control " required>
                                        <option value="">All Class</option>
                                    </select>
                                    <span class="invalid-feedback form-invalid fw-bold" id="class-error"
                                        role="alert"></span>
                                    <img src="{{ config('myconfig.myloader') }}" alt="Loading..." class="loader"
                                        id="loader" style="display:none; width:10%;">
                                </div>


                                <div class="form-group col-md-6">
                                    <label for="back_section_id" class="mt-2">Section <span
                                            class="text-danger">*</span></label>
                                    <select name="section" id="back_section_id" class="form-control  " required>
                                        <option value="">All Section</option>
                                    </select>
                                    <span class="invalid-feedback form-invalid fw-bold" id="section-error"
                                        role="alert"></span>
                                </div>
                            </div>
                            <div class="row">
                                <div class="form-group col-md-6">
                                    <input type="hidden" name="current_session" value='' id="current_session">
                                    <label for="back_std_id" class="mt-2">Student <span
                                            class="text-danger">*</span></label>
                                    <select name="std_id" id="back_std_id" class="form-control " required>
                                        <option value="">All Students</option>
                                    </select>
                                    <span class="invalid-feedback form-invalid fw-bold" id="std-error"
                                        role="alert"></span>
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="report" class="mt-2">Select Report <span
                                            class="text-danger">*</span></label>
                                    <select name="report" id="report" class="form-control " required>
                                        {{-- <option value="">Select Report</option> --}}
                                        <option value="complete" selected>Complete Report</option>
                                        <option value="firstInstDue">Ist Installment</option>
                                    </select>
                                </div>
                            </div>

                            <div class="mt-3">
                                <button type="button" id="show-details" class="btn btn-primary">Show Details</button>
                                <div id="no-records-message" class="text-danger" style="display:none;">
                                    <p>Please select properly</p>
                                </div>

                            </div>

                        </form>

                        <div id="complete-fee-table" class="table-responsive mt-5">
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
                            <div class="export-div">
                                <button type="button" class="btn btn-info" id="export-button">Export</button>
                            </div>
                            <div id="std-pagination" class="mt-3"></div>
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
            dueReportSection();
            $('#complete-fee-table').hide();

            let stdId = $('#back_std_id');
            function getExcelReport(std = stdId.val()) {

                let session = $('#current_session').val();
                let classId = $('#back_class_id').val();
                let sectionId = $('#back_section_id').val();
                let st = std.val();
                let report = $('#report').val();
                const exportUrl = "{{ route('fee.due-fee-report-excel') }}?session=" +
                    session + "&srno=" + st + "&reportType=" + report + "&class=" + classId + "&section=" + sectionId;
                window.location.href = exportUrl;

            }
            let currentPage = 1;
            $(document).on('click', '#std-pagination .page-link', function() {
                currentPage = $(this).data('page');
            });
            $('#export-button').on('click', function() {
                if(!stdId.val())
                {
                    return;
                }
                getExcelReport(stdId);
            });
            $('#back_std_id, #back_class_id, #back_section_id, #report').on('change', function(){
                $('#complete-fee-table').hide();
            });
        });
    </script>
@endsection
