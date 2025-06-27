@extends('admin.index')

@section('sub-content')
    <div class="container">
       
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">{{ __('Edit Section') }}
                    </div>

                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-12">

                                <a href="{{ route('admin.editSection.std') }}" class="btn btn-primary" id="editStdEditSection">Edit Student</a>
                                <a href="{{ route('admin.editSection.editStdFee') }}" class="btn btn-primary">Edit Fee Details</a>
                                <a href="{{ route('admin.editSection.editStdMarks') }}" class="btn btn-primary">Edit Marks</a>
                                <a href="{{ route('admin.editSection.editStdRollSection') }}" class="btn btn-primary" >Set New Section & Roll No.</a>
                            </div>

                        </div>
                        <div class="row mt-2">
                            <div class="col-md-12">
                                <a href="{{ route('admin.editSection.editStdAdmissionPromotion') }}" class="btn btn-primary" >Edit Admission/Promotion Date</a>
                                <a href="{{ route('admin.editSection.editStdAttendance') }}" class="btn btn-primary">Edit Attendance</a>
                                <a href="{{ route('admin.editSection.editResult') }}" class="btn btn-primary">Edit Result Date</a>
                            </div>

                        </div>
                        <div class="row mt-2">
                            <div class="col-md-12">
                                <a href="{{ route('admin.editSection.editRemoveRelativeStd') }}" class="btn btn-primary">Edit/Remove Relative</a>
                                <a href="{{ route('admin.editSection.editRemoveStdFee') }}" class="btn btn-primary">Edit / Remove Fee Entry</a>
                                <a href="{{ route('admin.editSection.editStdAdmissionDate') }}" class="btn btn-primary">Add / Delete Admission Date</a>
                            </div>
                        </div>
                        <div class="row mt-2">
                            <div class="col-md-12">
                                <a href="{{ route('admin.editSection.editStdByPreSrno') }}" class="btn btn-primary">Edit Previous Records</a>
                                <a href="{{ route('admin.editSection.mercyFeeBoth') }}" class="btn btn-primary">Mercy Fee (Both)</a>

                            </div>
                        </div>
                        <div class="row mt-2">
                            <div class="col-md-12">
                                <a href="#" class="btn btn-primary">Delete Stationay Fee Entry</a>
                                <a href="{{ route('admin.editSection.editStdInfoClass') }}" class="btn btn-primary">Edit Student Information (Class Wise)</a>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
@endsection



