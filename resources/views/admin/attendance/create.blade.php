@extends('layouts.app')

@section('content')
    <div class="container">
        @push('scripts')
            @if (Session::has('success'))
                <script>
                    Swal.fire("Good job!", "{{ Session::get('success') }}", "success");
                </script>
            @endif

            @if (Session::has('error'))
                <script>
                    Swal.fire("Oops...", "{{ Session::get('error') }}", "error");
                </script>
            @endif
        @endpush
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        {{ isset($data) && isset($data->id) ? 'Edit Attendance Schedule Master' : 'Create Attendance Schedule Master' }}
                    </div>

                    <div class="card-body">
                        <form action="{{ route('admin.attendance-schedule-master.store') }}" method="POST"
                            enctype="multipart/form-data" id="basic-form">
                            @csrf
                            <input type="hidden" name="id" id="id" value="{{ isset($data) ? $data->id : '' }}">
                            <div class="row">
                                <div class="form-group col-md-6">
                                    <label for="session_id" class="mt-2">Session <span
                                            class="text-danger">*</span></label>
                                    <select name="session_id" id="session_id"
                                        class="form-control @error('session_id') is-invalid @enderror" required>
                                        <option value="">Select Session</option>
                                        @foreach ($sessions as $session)
                                            <option value="{{ $session->id }}"
                                                {{ old('session_id', isset($data) ? $data->session_id : '') == $session->id ? 'selected' : '' }}>
                                                {{ $session->session }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('session_id')
                                        <span class="invalid-feedback form-invalid fw-bold" role="alert">
                                            {{ $message }}
                                        </span>
                                    @enderror
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="a_date">Attendance Date</label>
                                    <input type="datetime-local" name="a_date" id="a_date"
                                        class="form-control @error('a_date') is-invalid @enderror"
                                        value="{{ old('a_date', isset($data) ? (is_string($data->a_date) ? $data->a_date : $data->a_date->format('Y-m-d\TH:i')) : '') }}">
                                    @error('a_date')
                                        <span class="invalid-feedback form-invalid fw-bold"
                                            role="alert">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="row">
                                <div class="form-group col-md-12">
                                    <label for="reason">Reason</label>
                                    <textarea name="reason" id="reason" class="form-control @error('reason') is-invalid @enderror" rows="3">{{ old('reason', isset($data) ? $data->reason : '') }}</textarea>
                                    @error('reason')
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
@endsection
