@extends('admin.index')
@section('sub-content')
    <div class="container">

        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">{{ __('Reprint Fee Slip') }}
                        <a class="btn btn-warning btn-sm" style="float: right;" onclick="history.back()">Back</a>
                    </div>

                    <div class="card-body">

                        <form action="" method="get" id="class-form">
                            <div class="row mt-2">
                                <div class="form-group col-md-6">
                                    <label for="fee_type" class="mt-2">Fee Type <span class="text-danger">*</span></label>
                                    <input type="hidden" name="current_session" value='' id="current_session">
                                    <select name="fee_type" id="fee_type" class="form-control mx-1" required>
                                        <option value="1">Academic Fee</option>
                                        <option value="2">Transport Fee</option>
                                    </select>
                                    <span class="invalid-feedback form-invalid fw-bold fee-type-error" role="alert">
                                    </span>
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="feeNo" class="mt-2">Enter Fee Slip No. <span
                                            class="text-danger">*</span></label>
                                    <input type="text" name="feeNo" id="feeNo" class="form-control mx-1"
                                        value="" required>
                                    <span class="invalid-feedback form-invalid fw-bold feeNo-error" role="alert"></span>
                                </div>
                            </div>

                            <div class="mt-3">
                                <button class="btn btn-primary" type="button" id="print">Print</button>
                            </div>
                        </form>

                    </div>

                </div>
            </div>
        </div>
    </div>
@endsection
@section('admin-scripts')
    <script>
        $(document).ready(function() {
            $('#print').click(function() {
                let feeType = $('#fee_type').val(); // Get the value, not the jQuery object
                let feeNo = $('#feeNo').val(); // Get the value, not the jQuery object
                let session = $('#current_session').val(); // Get the value, not the jQuery object

                // if (feeType && feeNo && session) { // Check if all required fields have values
                    $.ajax({
                        url: '{{ route('admin.reports.reprintFeeSlip') }}',
                        type: 'GET',
                        data: {
                            academic_trans_value: feeType,
                            session: session,
                            slip_no: feeNo,
                        },
                        success: function(response) {
                            console.log(response); // Log the response, not 'student'
                            // Handle the successful response here
                            // For example, you might want to open a new window with the printed slip
                            if (response.print_url) {
                                // window.open(response.print_url, '_blank');
                                window.location.href = response.print_url;
                            }
                        },
                        error: function(data, xhr) {
                            let message = data.responseJSON.message;
                            $('.feeNo-error').hide().html('');
                            if (message) {
                                if (message) {
                                    $('.feeNo-error').show().html(message);
                                }

                            }
                            console.error(xhr.responseText);

                        }
                    });
                // }
            });
        });
    </script>
@endsection
