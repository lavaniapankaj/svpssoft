@extends('admin.index')
@section('sub-content')
<div class="container">
    @if (Session::has('success'))
    @section('scripts')
    <script>
        swal("Successful", "{{ Session::get('success') }}", "success")
    </script>
    @endsection
    @endif

    @if (Session::has('error'))
    @section('scripts')
    <script>
        swal("Error", "{{ Session::get('error') }}", "error");
    </script>
    @endsection
    @endif
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    {{ 'Edit Result Date' }}
                    <a href="{{ route('admin.editSection.index') }}" class="btn btn-warning btn-sm"
                        style="float: right;">Back</a>

                </div>
                <div class="card-body">
                    <form action="{{ route('admin.editSection.editResultStore') }}" method="post">
                        @csrf
                        <div class="row">
                            <div class="form-group col-md-6">
                                <label for="session_id" class="mt-2">Session <span
                                        class="text-danger">*</span></label>
                                <select name="session_id" id="session_id"
                                    class="form-control @error('session_id') is-invalid @enderror" required>
                                    <option value="">Select session</option>
                                    @if (count($sessions) > 0)
                                        @foreach ($sessions as $key => $session)
                                            <option value="{{ $key }}" {{ old('session_id') == $key ? 'selected' : ''}}>{{ $session }}</option>
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


                            <div class="form-group col-md-6">
                                <label for="resultDate" class="mt-2">Enter Result Date <span
                                        class="text-danger">*</span></label>
                                <input type="date" id="resultDate" name="resultDate" value="{{ old('resultDate') }}" class="form-control @error('resultDate') is-invalid @enderror" required>

                                @error('resultDate')
                                <span class="invalid-feedback form-invalid fw-bold"
                                    role="alert">{{ $message }}</span>
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
