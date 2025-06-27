@extends('admin.index')

@section('sub-content')
    <div class="container">

        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        {{ 'Edit State' }}
                        <a href="{{ route('admin.state-master.index') }}" class="btn btn-warning btn-sm"
                            style="float: right;">Back</a>
                    </div>

                    <div class="card-body">
                        <form action="{{ route('admin.state-master.update', $stateMaster->id) }}" method="POST" id="basic-form">
                            @csrf
                            @method('put')
                            <input type="hidden" name="id" id="id"
                                value="{{ isset($stateMaster) ? $stateMaster->id : '' }}">
                            <div class="row">
                                <div class="form-group col-md-12">
                                    <label for="state" class="mt-2"> State <span class="text-danger">*</span></label>
                                    <input type="text" name="state"
                                        class="form-control @error('state') is-invalid @enderror" placeholder="State"
                                        value="{{ old('state', isset($stateMaster) ? $stateMaster->name : '') }}" required>
                                    @error('state')
                                        <span class="invalid-feedback form-invalid fw-bold" role="alert">
                                            {{ $message }}
                                        </span>
                                    @enderror
                                </div>

                            </div>
                            <div class="mt-3">
                                <input class="btn btn-primary" type="submit" value="Update">
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
