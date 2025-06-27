@extends('admin.index')

@section('sub-content')
    <div class="container">

        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        {{ 'Edit Current Session' }}
                       <a href="{{ route('admin.current-session.index') }}" class="btn btn-warning btn-sm" style="float: right;">Back</a>

                    </div>

                    <div class="card-body">
                        <form action="{{ route('admin.current-session.store') }}" method="POST" id="basic-form">
                            @csrf
                            {{-- <input type="hidden" name="id" id="id" value="{{ isset($data) ? $data->id : '' }}"> --}}
                            <div class="row">
                                <div class="form-group col-md-12">
                                    <label for="session_id" class="mt-2">Session <span
                                            class="text-danger">*</span></label>
                                    <select name="session_id" id="session_id"
                                        class="form-control @error('session_id') is-invalid @enderror" required>
                                        <option value="">Select session</option>
                                        @if (count($sessions) > 0)
                                                @foreach ($sessions as $key => $session)
                                                    <option value="{{ $key }}"
                                                        {{ isset($data) && $data->id == $key ? 'selected' : '' }}>
                                                        {{ $session }}</option>
                                                @endforeach
                                            @else
                                                <option value="">No Session Found</option>
                                            @endif
                                    </select>
                                    @error('session_id')
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
