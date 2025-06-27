@extends('admin.index')
@section('sub-content')
    <div class="container">

        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        {{ 'Add New Section' }}
                        <a href="{{ route('admin.section-master.index') }}" class="btn btn-warning btn-sm"
                            style="float: right;">Back</a>

                    </div>

                    <div class="card-body">
                        <form action="{{ route('admin.section-master.store') }}" method="POST" id="basic-form">
                            @csrf
                            <div class="row">
                                <div class="form-group col-md-12">
                                    <label for="class_id" class="mt-2">Class <span class="text-danger">*</span></label>
                                    <select name="class_id" id="class_id"
                                        class="form-control @error('class_id') is-invalid @enderror" required>
                                        <option value="">Select Class</option>
                                        @if (count($classes) > 0)
                                            @foreach ($classes as $key => $class)
                                                <option value="{{ $key }}"
                                                    {{ old('class_id') == $key ? 'selected' : '' }}>
                                                    {{ $class }}</option>
                                            @endforeach
                                        @else
                                            <option value="">No Classes Found</option>
                                        @endif
                                    </select>
                                    @error('class_id')
                                        <span class="invalid-feedback form-invalid fw-bold" role="alert">
                                            {{ $message }}
                                        </span>
                                    @enderror
                                </div>
                            </div>
                            <div class="row">
                                <div class="form-group col-md-12">
                                    <label for="section" class="mt-2"> Section <span class="text-danger">*</span></label>
                                    <input type="text" name="section"
                                        class="form-control @error('section') is-invalid @enderror" placeholder="Section"
                                        value="{{ old('section') }}" required>
                                    @error('section')
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

