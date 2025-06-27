@extends('admin.index')

@section('sub-content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        {{ 'Add New District' }}
                        <a href="{{ route('admin.district-master.index') }}" class="btn btn-warning btn-sm"
                            style="float: right;">Back</a>
                    </div>

                    <div class="card-body">
                        <form action="{{ route('admin.district-master.store') }}" method="POST" id="basic-form">
                            @csrf
                            <div class="row">
                                <div class="form-group col-md-12">
                                    <label for="state_id" class="mt-2"> State <span class="text-danger">*</span></label>
                                    <select name="state_id" id="state_id"
                                        class="form-control @error('state_id') is-invalid @enderror" required>
                                        <option value="">Select State</option>
                                        @if (count($states) > 0)
                                            @foreach ($states as $key => $state)
                                                <option value="{{ $key }}"
                                                    {{ old('state_id') == $key ? 'selected' : '' }}>
                                                    {{ $state }}</option>
                                            @endforeach
                                        @else
                                            <option value="">No State Found</option>
                                        @endif
                                    </select>
                                    @error('state_id')
                                        <span class="invalid-feedback form-invalid fw-bold" role="alert">
                                            {{ $message }}
                                        </span>
                                    @enderror
                                </div>
                            </div>
                            <div class="row">
                                <div class="form-group col-md-12">
                                    <label for="name" class="mt-2"> District <span
                                            class="text-danger">*</span></label>
                                    <input type="text" name="name"
                                        class="form-control @error('name') is-invalid @enderror" placeholder="District"
                                        value="{{ old('name') }}" required>
                                    @error('name')
                                        <span class="invalid-feedback form-invalid fw-bold" role="alert">
                                            {{ $message }}
                                        </span>
                                    @enderror
                                </div>
                            </div>
                            <div class="mt-3">
                                <input class="btn btn-primary" type="submit" value="Save">
                            </div>
                        </form>

                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
