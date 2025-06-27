@extends('admin.index')
@section('sub-content')
    <div class="container">
        @if (Session::has('success'))
            @section('scripts')
                <script>
                    swal("Successful", "{{ Session::get('success') }}", "success").then(() => {
                        location.reload();
                    });
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
                        {{ 'Upload Principle Signature For Marksheet' }}
                        <a href="{{ route('admin.signature') }}" class="btn btn-warning btn-sm" style="float: right;">Back</a>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <form action="{{ route('admin.signature.upload') }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                <div class="input-group mb-3 mt-5 col-md-6">
                                    <input type="file" class="form-control @error('signature') is-invalid @enderror" id="image" name="signature" accept="image/*">
                                    @error('signature')
                                        <span class="invalid-feedback form-invalid fw-bold" role="alert">
                                            {{ $message }}
                                        </span>
                                    @enderror
                                    <button class="btn btn-outline-secondary" type="submit">Upload</button>
                                </div>
                                <span class="form-invalid text-danger" role="alert">Only PNG files are allowed, and the file size should be between 400 KB and 500 KB.</span>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

