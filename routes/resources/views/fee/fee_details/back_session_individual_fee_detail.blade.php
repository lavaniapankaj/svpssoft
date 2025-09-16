@extends('fee.index')
@section('sub-content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card border-0 bg-white">
                    <div class="card-header flex-wrap bg-white d-flex align-items-center justify-content-between"><h5 class="mb-0 mt-0">{{ 'Fee Details' }}</h5>
                        <a  onclick="history.back()" class="btn bg-light btn-sm" ><span class="mdi mdi-chevron-left me-2"></span>Back</a>

                    </div>
                    <div class="card-body">
                        <input type="hidden" id="session" name="current_session" value="{{ $session }}">
                        <input type="hidden" id="std_id" name="std_id" value="{{ $st }}">
                        <input type="hidden" id="class_id" name="class" value="{{ $class }}">
                        <input type="hidden" id="section_id" name="section" value="{{ $section }}">
                        <div id="tables">
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
                        <div class="mt-3">
                            <button type="button" id="print-receipt" class="btn btn-primary print-receipt">Print Receipt</button>
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
            let session = $('#session').val();
            let std = $('#std_id').val();
            let classId = $('#class_id').val();
            let sectionId = $('#section_id').val();
            if (session !== '' && std !== '' && classId !== '' && sectionId !== '') {
                // adcademicAndTransportFeePopulateWithoutSSID(std, session, classId);
                adcademicAndTransportFeePopulate(
                std,
                session,
                classId,
                sectionId
                );
            }
            $('.print-receipt').click(function() {
                $('#tables').print();
            });
        });
    </script>
@endsection
