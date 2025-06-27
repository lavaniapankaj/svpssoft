@extends('admin.index')

@section('sub-content')
<div class="container">

    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card">
                    <div class="card-header">
                        {{ 'Add New Exam' }}
                        <a href="{{ route('admin.exam-master.index') }}" class="btn btn-warning btn-sm" style="float: right;">Back</a>
                    </div>

                    <div class="card-body">
                        <form action="{{ route('admin.exam-master.store') }}" method="POST" id="basic-form">
                            @csrf
                            <div class="row">
                                <div class="form-group col-md-12">
                                    <label for="exam" class="mt-2"> Exam <span
                                            class="text-danger">*</span></label>
                                    <input type="text" name="exam"
                                        class="form-control @error('exam') is-invalid @enderror"
                                        placeholder="Exam"
                                        value="{{ old('exam') }}" id="exam" required>
                                    @error('exam')
                                    <span class="invalid-feedback form-invalid fw-bold" role="alert">
                                        {{ $message }}
                                    </span>
                                    @enderror
                                </div>
                            </div>
                            <div class="row">
                                <div class="form-group col-md-12">
                                    <label for="order" class="mt-2"> Order <span
                                            class="text-danger">*</span></label>
                                    <input type="text" name="order"
                                        class="form-control @error('order') is-invalid @enderror"
                                        placeholder="Order"
                                        value="{{ old('order') }}" id="order" required>
                                    @error('order')
                                    <span class="invalid-feedback form-invalid fw-bold" role="alert">
                                        {{ $message }}
                                    </span>
                                    @enderror
                                </div>
                            </div>
                            <div class="row">
                                <div class="form-group form-check col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input @error('show_y_n') is-invalid @enderror" value="0" type="radio" name="show_y_n"
                                            id="no"
                                            {{ old('show_y_n' == 0 ? 'checked=' . '"' . 'checked' . '"' : '') }}>
                                        <label class="form-check-label" for="no">
                                            No
                                        </label>
                                    </div>
                                </div>
                                <div class="form-group form-check col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input @error('show_y_n') is-invalid @enderror" value="1" type="radio" name="show_y_n"
                                            id="yes"
                                            {{ old('show_y_n' == 1 ? 'checked=' . '"' . 'checked' . '"' : '') }}>
                                        <label class="form-check-label" for="yes">
                                            Yes
                                        </label>
                                    </div>
                                </div>
                                @error('show_y_n')
                                <span class="invalid-feedback form-invalid fw-bold" role="alert">
                                    {{ $message }}
                                </span>
                                @enderror
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
</div>
@endsection