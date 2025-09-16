@extends('admin.index')
@section('sub-content')
    <div class="container-fluid">

        <div class="row">
            <div class="col-md-12">
                <div class="card border-0 bg-white">
           <div class="card-header flex-wrap bg-white d-flex align-items-center justify-content-between">
            <h5 class="mb-0 mt-0">{{ 'Group SMS Master' }}</h5>
                        <a href="{{ route('admin.group-sms-panel.index') }}" class="btn bg-light btn-sm" ><span class="mdi mdi-chevron-left me-2"></span>Back</a>

                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="form-group col-md-6">
                                <div class="mt-3">
                                    <a href="{{ route('admin.add-sms-group.index') }}" id="add-group" class="btn btn-sm btn-primary">Add Group</a>
                                    <a href="{{ route('admin.add-edit-sms-group-mobile.index') }}" id="add-edit-mobile" class="btn btn-sm btn-primary">Add/Edit Mobile Number</a>
                                    <a href="{{ route('admin.send-group-sms.index') }}" id="send-sms" class="btn btn-sm btn-primary">Send SMS</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endsection
