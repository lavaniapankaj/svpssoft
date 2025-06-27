@extends('admin.index')
@section('sub-content')
    <div class="container">

        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">{{ __('Edit SMS Group') }}
                        <a href="{{ route('admin.add-sms-group.index') }}" class="btn btn-warning"
                            style="float: right;">Back</a>

                    </div>

                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-12">
                                <form action="{{ route('admin.add-sms-group.update', $data->id ?? '') }}" method="POST">
                                    @csrf
                                    @method('PUT')
                                    <div class="form-group">
                                        <label for="group_name">{{ __('	Enter Group Name') }}</label>
                                        <input type="text" placeholder="Enter Group Name"
                                            value="{{ old('group_name', isset($data) ? $data->group_name : '') }}" id="group_name" name="group_name"
                                            class="form-control @error('group_name') is-invalid @enderror" required>
                                        @error('group_name')
                                            <span class="invalid-feedback form-invalid fw-bold"
                                                role="alert">{{ $message }}</span>
                                        @enderror
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
        </div>
    </div>
@endsection
