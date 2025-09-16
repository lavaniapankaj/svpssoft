@extends('admin.index')
@section('sub-content')
    <div class="container-fluid">
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
        <div class="row">
            <div class="col-md-12">
                <div class="card border-0 bg-white">
           <div class="card-header flex-wrap bg-white d-flex align-items-center justify-content-between">
            <h5 class="mb-0 mt-0">{{ 'Upload Principle Signature For Marksheet' }}</h5>
                        <a href="{{ route('admin.signature') }}" class="btn bg-light btn-sm" ><span class="mdi mdi-chevron-left me-2"></span>Back</a>
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

