@extends('admin.index')
@section('sub-content')
    <div class="container-fluid">
        <div class="row ">
            <div class="col-md-12">
                <div class="card border-0 bg-white">
                    <div class="card-header bg-white d-flex align-items-center justify-content-between">
                        <h5 class="mb-0 mt-0">{{ 'Add New Class' }}</h5>
                        <a href="{{ route('admin.class-master.index') }}" class="btn bg-light btn-sm" ><span class="mdi mdi-chevron-left me-2"></span>Back</a>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('admin.class-master.store') }}" method="POST" id="basic-form">
                            @csrf
                            <div class="row">
                                <div class="form-group col-md-6">
                                    <label for="class" class="mt-2"> Class <span class="text-danger">*</span></label>
                                    <input type="text" name="class" class="form-control @error('class') is-invalid @enderror" placeholder="Class" value="{{ old('class') }}" required>
                                    @error('class')
                                        <span class="invalid-feedback form-invalid fw-bold" role="alert">
                                            {{ $message }}
                                        </span>
                                    @enderror
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="sort" class="mt-2">Class sorting<span class="text-danger">*</span></label>
                                    <input type="text" name="sort" class="form-control @error('sort') is-invalid @enderror" placeholder="Class Sorting" value="{{ old('sort') }}" required>
                                    @error('sort')
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
