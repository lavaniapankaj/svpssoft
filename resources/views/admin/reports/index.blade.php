@extends('admin.index')

@section('sub-content')
    <div class="container">

        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">{{ __('Reports Section') }}
                    </div>

                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-12">

                                <a href="{{ route('admin.reports.newAdmissionReport') }}" class="btn btn-primary">New Admission Report</a>
                                <a href="{{ route('admin.reports.stdregisterView.index') }}" class="btn btn-primary">SR Register</a>
                                <a href="{{ route('admin.reports.reportAgeWiseView.index') }}" class="btn btn-primary">Report (Age Wise)</a>
                                <a href="{{ route('admin.reports.tcIssueView') }}" class="btn btn-primary" >Issue TC (New or Reprint)</a>
                            </div>

                        </div>
                        <div class="row mt-2">
                            <div class="col-md-12">
                                <a href="{{ route('admin.reports.reprintFeeSlipView') }}" class="btn btn-primary" >Reprint Fee Slip (Both)</a>
                                <a href="{{ route('admin.reports.transportWiseReportView.index') }}" class="btn btn-primary">Student Report (Transport)</a>
                                <a href="{{ route('admin.reports.rteStudentReport.view') }}" class="btn btn-primary">RTE Student Report</a>
                            </div>

                        </div>
                        <div class="row mt-2">
                            <div class="col-md-12">
                                <a href="{{ route('admin.reports.missFieldsReportView.view') }}" class="btn btn-primary">Miss Field Records</a>
                                <a href="{{ route('admin.reports.srRegisterView.view') }}" class="btn btn-primary">SR Register (Full)</a>
                                <a href="{{ route('admin.reports.feeReportAdminView.view') }}" class="btn btn-primary">Fee Report Admin</a>
                            </div>
                        </div>
                        <div class="row mt-2">
                            <div class="col-md-12">
                                <a href="{{ route('admin.reports.feeReportMercyAdminView.view') }}" class="btn btn-primary">Fee Report Admin (Mercy Fee)</a>
                                <a href="#" class="btn btn-primary">Stock Cash Report</a>

                            </div>
                        </div>

                    </div>

                </div>
            </div>
        </div>
    </div>
@endsection



