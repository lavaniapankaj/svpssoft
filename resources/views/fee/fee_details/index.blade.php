@extends('fee.index')
@section('sub-content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-14">
            <div class="card">
                <div class="card-header">
                    {{ 'Fee Details' }}
                    <a href="{{ route('fee.fee-detail') }}" class="btn btn-warning btn-sm" style="float: right;">Back</a>

                </div>
                <div class="card-body">


                    <form id="class-section-form">
                        <div class="row">
                            <div class="form-group col-md-6">
                                <label for="class_id" class="mt-2">Class <span class="text-danger">*</span></label>
                                <select name="class" id="class_id" class="form-control " required>
                                    <option value="">Select Class</option>
                                    @if (count($classes) > 0)
                                    @foreach ($classes as $key => $class)
                                    <option value="{{ $key }}" {{ old('class') == $key ? 'selected' : ''}}>{{ $class }}</option>
                                    @endforeach
                                    @else
                                    <option value="">No Class Found</option>
                                    @endif
                                </select>
                                <span class="invalid-feedback form-invalid fw-bold" id="class-error"
                                    role="alert"></span>
                                <img src="{{ config('myconfig.myloader') }}" alt="Loading..." class="loader"
                                    id="loader" style="display:none; width:10%;">
                            </div>


                            <div class="form-group col-md-6">
                                <label for="section_id" class="mt-2">Section <span
                                        class="text-danger">*</span></label>
                                <input type="hidden" id="initialSectionId"
                                    value="{{ old('section') }}">
                                <select name="section" id="section_id" class="form-control  " required>
                                    <option value="">Select Section</option>
                                </select>
                                <span class="invalid-feedback form-invalid fw-bold" id="section-error"
                                    role="alert"></span>
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group col-md-6">
                                <input type="hidden" name="current_session" value='' id="current_session">
                                <label for="std_id" class="mt-2">Student <span class="text-danger">*</span></label>
                                <select name="std_id" id="std_id" class="form-control " required>
                                    <option value="">Select Students</option>
                                </select>
                                <span class="invalid-feedback form-invalid fw-bold" id="std-error"
                                    role="alert"></span>
                            </div>

                        </div>

                        <div class="mt-3">
                            <button type="button" id="show-details" class="btn btn-primary">
                                Show Details</button>

                        </div>

                    </form>

                    <div id="std-fee-due-table" class="table-responsive mt-5">
                        <table class="table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th colspan="10">Academic Fee Due Details</th>
                                </tr>
                                <tr>

                                    <th></th>
                                    <th>Admission Fee</th>
                                    <th>Ist Installment</th>
                                    <th>IInd Installment</th>
                                    <th>Complete Fee</th>
                                    <th>Mercy Fee</th>
                                    <th>Status</th>
                                    <th>Pay Date</th>
                                    <th>Recp. No.</th>
                                    <th>Ref. Slip No.</th>
                                </tr>
                            </thead>
                            <tbody class="table-group-divider">
                            </tbody>
                            <tfoot class="footer table-group-divider">
                            </tfoot>
                        </table>
                    </div>
                    <div id="std-transport-fee-due-table" class="table-responsive">
                        <table class="table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th colspan="9">Transport Fee Due Details</th>
                                </tr>
                                <tr>

                                    <th></th>
                                    <th>Ist Installment</th>
                                    <th>IInd Installment</th>
                                    <th>Complete Fee</th>
                                    <th>Mercy Fee</th>
                                    <th>Status</th>
                                    <th>Pay Date</th>
                                    <th>Recp. No.</th>
                                    <th>Ref. Slip No.</th>
                                </tr>
                            </thead>
                            <tbody>
                            </tbody>
                            <tfoot class="footerTrans table-group-divider">
                            </tfoot>
                        </table>
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
        let initialClassId = $('#class_id').val();
        let initialSectionId = $('#initialSectionId').val();
        let stdFeeDueTable = $('#std-fee-due-table');
        let transportStdFeeDueTable = $('#std-transport-fee-due-table');
        let std = $('#std_id').val();
        let sessionID = $('#current_session').val();
        getClassSection(initialClassId, initialSectionId);
        stdFeeDueTable.hide();
        transportStdFeeDueTable.hide();
        $('#show-details').on('click', function() {
            adcademicAndTransportFeePopulate(
                $('#std_id').val(),
                $('#current_session').val(),
                $('#class_id').val(),
                $('#section_id').val()
            );
        });
        $('#std_id, #class_id, #section_id').change(() => {
            stdFeeDueTable.hide();
            transportStdFeeDueTable.hide();
        });
    });
</script>
@endsection