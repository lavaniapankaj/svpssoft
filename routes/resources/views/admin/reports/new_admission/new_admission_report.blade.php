@extends('admin.index')

@section('sub-content')
    <div class="container-fluid">

        <div class="row">
            <div class="col-md-12">
                <div class="card border-0 bg-white">
                    <div class="card-header flex-wrap bg-white d-flex align-items-center justify-content-between"><h5 class="mb-0 mt-0">{{ __('New Admission Report') }}</h5>
                        <a href="{{ route('admin.reports') }}" class="btn bg-light btn-sm" ><span class="mdi mdi-chevron-left me-2"></span>Back</a>
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

