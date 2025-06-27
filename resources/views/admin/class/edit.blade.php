@extends('admin.index')

@section('sub-content')
    <div class="container">

        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        {{ 'Edit Class Master' }}
                        <a href="{{ route('admin.class-master.index') }}" class="btn btn-warning btn-sm" style="float: right;">Back</a>

                    </div>

                    <div class="card-body">
                        <form action="{{ route('admin.class-master.update', $classMaster->id) }}" method="POST" id="basic-form">
                            @csrf
                            @method('put')
                            <input type="hidden" name="id" id="id" value="{{ isset($classMaster) ? $classMaster->id : '' }}">
                            <div class="row">
                                <div class="form-group col-md-6">
                                    <label for="class" class="mt-2"> Class <span class="text-danger">*</span></label>
                                    <input type="text" name="class" class="form-control @error('class') is-invalid @enderror" placeholder="Class" value="{{ old('class', isset($classMaster) ? $classMaster->class : '') }}" required>
                                    @error('class')
                                        <span class="invalid-feedback form-invalid fw-bold" role="alert">
                                            {{ $message }}
                                        </span>
                                    @enderror
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="sort" class="mt-2">Class sorting<span class="text-danger">*</span></label>
                                    <input type="text" name="sort" class="form-control @error('sort') is-invalid @enderror" placeholder="Class Sorting" value="{{ old('sort', isset($classMaster) ? $classMaster->sort : '') }}" required>
                                    @error('sort')
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
