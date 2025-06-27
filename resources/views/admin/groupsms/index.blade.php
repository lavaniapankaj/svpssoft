@extends('admin.index')
@section('sub-content')
    <div class="container">

        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        {{ 'Group SMS Master' }}
                        <a href="{{ route('admin.group-sms-panel.index') }}" class="btn btn-warning btn-sm"
                            style="float: right;">Back</a>

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
