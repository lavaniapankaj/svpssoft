@extends('admin.index')

@section('sub-content')
    <div class="container">
       <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card">
                        <div class="card-header">
                            {{ isset($data) && isset($data->id) ? 'Edit Message' : 'Add New Mesage' }}
                            @if (isset($data) && isset($data->id))
                                <a href="{{ route('admin.website-message.index') }}" class="btn btn-warning btn-sm"
                                    style="float: right;">Back</a>
                            @endif
                        </div>

                        <div class="card-body">
                            <form action="{{ route('admin.website-message.store') }}" method="POST"
                                enctype="multipart/form-data" id="basic-form">
                                @csrf
                                <input type="hidden" name="id" id="id"
                                    value="{{ isset($data) ? $data->id : '' }}">

                                <div class="row">
                                    <div class="form-group col-md-12">
                                        <label for="title" class="mt-2">Enter Title <span
                                                class="text-danger">*</span></label>
                                        <input type="text" id="title" value="{{ isset($data) ? $data->title : '' }}" class="form-control @error('title') is-invalid @enderror">
                                        @error('title')
                                            <span class="invalid-feedback form-invalid fw-bold" role="alert">
                                                {{ $message }}
                                            </span>
                                        @enderror
                                    </div>
                                </div>


                                <div class="row">
                                    <div class="form-group col-md-12">
                                        <label for="message">Enter Message</label>
                                        <textarea name="message" id="message"
                                            class="form-control @error('message') is-invalid @enderror" rows="3" required>{{ old('message', isset($data) ? $data->message : '') }}</textarea>
                                        @error('message')
                                            <span class="invalid-feedback form-invalid fw-bold"
                                                role="alert">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>

                                <div class="mt-3">
                                    <input class="btn btn-primary" type="submit"
                                        value="{{ isset($data) && isset($data->id) ? 'Update' : 'Save' }}">
                                </div>
                            </form>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
@endsection


