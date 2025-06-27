@extends('admin.index')

@section('sub-content')
    <div class="container">

        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">{{ __('New Admission Report') }}
                        <a href="{{ route('admin.reports') }}" class="btn btn-warning btn-sm" style="float: right;">Back</a>
                    </div>

                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-12">
                                <a href="{{ route('admin.reports.newAdmissionReport.index') }}" class="btn btn-primary">New Admission Report (By Date)</a>
                                <a href="{{ route('admin.reports.newAdmissionReportByDate.index') }}" class="btn btn-primary">Admission Report (By Category)</a>
                                <a href="{{ route('admin.reports.newAdmissionReportByReligion.index') }}" class="btn btn-primary">Religon Wise Report</a>
                            </div>

                        </div>
                        <div class="row mt-2">
                            <div class="col-md-12">
                                <a href="{{ route('admin.reports.newAdmissionReportByAgeProof.index') }}" class="btn btn-primary">Age Proof Wise Report</a>
                                <a href="{{ route('admin.reports.newAdmissionReportByBetweenDates.index') }}" class="btn btn-primary">New Admission Report (Between Dates)</a>

                            </div>

                        </div>


                    </div>

                </div>
            </div>
        </div>
    </div>
@endsection

