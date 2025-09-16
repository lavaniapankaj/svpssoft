@extends('admin.index')

@section('sub-content')
    <div class="container-fluid">

        <div class="row ">
            <div class="col-md-12">
                <div class="card border-0 bg-white">
                    <div class="card-header flex-wrap bg-white d-flex align-items-center justify-content-between">
                        <h5 class="mb-0 mt-0">{{'Edit Exam'}}</h5>
                           <a href="{{ route('admin.exam-master.index') }}" class="btn bg-light btn-sm" ><span class="mdi mdi-chevron-left me-2"></span>Back</a>

                        </div>

                        <div class="card-body">
                            <form action="{{ route('admin.exam-master.update', $examMaster->id ?? '') }}" method="POST" id="basic-form">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="id" id="id" value="{{ isset($examMaster) ? $examMaster->id : '' }}">
                                <div class="row">
                                    <div class="form-group col-md-12">
                                        <label for="exam" class="mt-2"> Exam <span
                                                class="text-danger">*</span></label>
                                        <input type="text" name="exam"
                                            class="form-control @error('exam') is-invalid @enderror"
                                            placeholder="Exam"
                                            value="{{ old('exam', isset($examMaster) ? $examMaster->exam : '') }}" id="exam" required>
                                        @error('exam')
                                            <span class="invalid-feedback form-invalid fw-bold" role="alert">
                                                {{ $message }}
                                            </span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="row mt-2">
                                    <div class="form-group col-md-12">
                                        <label for="order" class="mt-2"> Order <span
                                                class="text-danger">*</span></label>
                                        <input type="text" name="order"
                                            class="form-control @error('order') is-invalid @enderror"
                                            placeholder="Order"
                                            value="{{ old('order', isset($examMaster) ? $examMaster->order : '') }}" id="order" required>
                                        @error('order')
                                            <span class="invalid-feedback form-invalid fw-bold" role="alert">
                                                {{ $message }}
                                            </span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="row justify-content-start  mt-3">
                                    <div class="form-group form-check col-md-1">
                                        <div class="form-check">
                                            <input class="form-check-input @error('show_y_n') is-invalid @enderror" value="0" type="radio" name="show_y_n"
                                                id="no"
                                                {{ old('show_y_n', isset($examMaster) && $examMaster->show_y_n == 0 ? 'checked=' . '"' . 'checked' . '"' : '') }}>
                                            <label class="form-check-label" for="no">
                                                No
                                            </label>
                                        </div>
                                    </div>
                                    <div class="form-group form-check col-md-1">
                                        <div class="form-check">
                                            <input class="form-check-input @error('show_y_n') is-invalid @enderror" value="1" type="radio" name="show_y_n"
                                                id="yes"
                                                {{ old('show_y_n', isset($examMaster) && $examMaster->show_y_n == 1 ? 'checked=' . '"' . 'checked' . '"' : '') }}>
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

                                <div class="mt-5">
                                    <input class="btn btn-primary" type="submit" value="Update">
                                </div>
                            </form>
                        </div>
                    </div>
            </div>
        </div>
    </div>
@endsection

