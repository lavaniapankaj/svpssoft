@extends('admin.index')

@section('sub-content')
    <div class="container-fluid">

        <div class="row">
            <div class="col-md-12">
               <div class="card border-0 bg-white">
                    <div class="card-header bg-white d-flex align-items-center justify-content-between">
                        <h5 class="mb-0 mt-0">{{ isset($data) && isset($data->id) ? 'Edit State' : 'Add New State' }}</h5>
                        <a href="{{ route('admin.state-master.index') }}" class="btn bg-light btn-sm" ><span class="mdi mdi-chevron-left me-2"></span>Back</a>

                    </div>

                    <div class="card-body">
                        <form action="{{ route('admin.state-master.store') }}" method="POST" id="basic-form">
                            @csrf
                            <input type="hidden" name="id" id="id" value="{{ isset($data) ? $data->id : '' }}">
                            <div class="row">
                                <div class="form-group col-md-12">
                                    <label for="state" class="mt-2"> State <span class="text-danger">*</span></label>
                                    <input type="text" name="state"
                                        class="form-control @error('state') is-invalid @enderror" placeholder="State"
                                        value="{{ old('state') }}" required>
                                    @error('state')
                                        <span class="invalid-feedback form-invalid fw-bold" role="alert">
                                            {{ $message }}
                                        </span>
                                    @enderror
                                </div>

                            </div>
                            <div class="mt-5">
                                <input class="btn btn-primary" type="submit" value="Save">
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
