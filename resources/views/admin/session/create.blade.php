@extends('admin.index')

@section('sub-content')
    <div class="container">

        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        {{ 'Add New Session' }}
                        <a href="{{ route('admin.session-master.index') }}" class="btn btn-warning btn-sm" style="float: right;">Back</a>

                    </div>
                    <div class="card-body">
                        <form action="{{ route('admin.session-master.store') }}" method="POST" id="basic-form">
                            @csrf
                           <div class="row">
                                <div class="form-group col-md-6">
                                    <label for="start_year" class="mt-2">Start Year <span class="text-danger">*</span></label>
                                    <select name="start_year" id="start_year" class="form-control @error('start_year') is-invalid @enderror" required>
                                        <option value="">Select Start Year</option>
                                        @for ($year = 2004; $year <= 2050; $year++)
                                            <option value="{{ $year }}"
                                                {{ old('start_year') == $year ? 'selected' : '' }}>
                                                {{ $year }}
                                            </option>
                                        @endfor
                                    </select>
                                    @error('start_year')
                                        <span class="invalid-feedback form-invalid fw-bold" role="alert">
                                            {{ $message }}
                                        </span>
                                    @enderror
                                </div>

                                <div class="form-group col-md-6">
                                    <label for="end_year" class="mt-2">End Year <span class="text-danger">*</span></label>
                                    <select name="end_year" id="end_year" class="form-control @error('end_year') is-invalid @enderror" required>
                                        <option value="">Select End Year</option>
                                        @for ($year = 2005; $year <= 2051; $year++)
                                            <option value="{{ $year }}"
                                                {{ old('end_year') == $year ? 'selected' : '' }}>
                                                {{ $year }}
                                            </option>
                                        @endfor
                                    </select>
                                    @error('end_year')
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
