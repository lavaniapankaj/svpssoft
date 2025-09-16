@extends('fee.index')
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
                        <h5 class="mb-0 mt-0">{{ 'Change Password' }}</h5>
                            <a href="{{ route('fee.dashboard') }}" class="btn bg-light btn-sm" ><span class="mdi mdi-chevron-left me-2"></span>Back</a>

                    </div>

                        <div class="card-body">
                            <form action="{{ route('fee.changePass.store') }}" method="POST">
                                @csrf
                                <input type="hidden" name="id" id="id" value="{{ Auth::id() }}">

                                <div class="row">
                                    <div class="form-group col-md-6">
                                        <label for="old_user_name" class="mt-2">Enter Old User Name<span
                                                class="text-danger">*</span></label>
                                        <input type="text" id="old_user_name" name="old_user_name" value="{{ old('old_user_name') }}"
                                            class="form-control @error('old_user_name') is-invalid @enderror">
                                        @error('old_user_name')
                                            <span class="invalid-feedback form-invalid fw-bold" role="alert">
                                                {{ $message }}
                                            </span>
                                        @enderror

                                    </div>
                                    <div class="form-group col-md-6">
                                        <label for="old_user_pass" class="mt-2">Enter Old Password<span
                                                class="text-danger">*</span></label>
                                        <input type="password" id="old_user_pass" name="old_user_pass" value=""
                                            class="form-control @error('old_user_pass') is-invalid @enderror">
                                        @error('old_user_pass')
                                            <span class="invalid-feedback form-invalid fw-bold" role="alert">
                                                {{ $message }}
                                            </span>
                                        @enderror

                                    </div>

                                </div>
                                <div class="row">
                                    <div class="form-group col-md-6">
                                        <label for="user_name" class="mt-2">Enter New User Name<span
                                                class="text-danger">*</span></label>
                                        <input type="text" id="user_name" name="user_name" value="{{ old('user_name') }}"
                                            class="form-control @error('user_name') is-invalid @enderror">
                                        @error('user_name')
                                            <span class="invalid-feedback form-invalid fw-bold" role="alert">
                                                {{ $message }}
                                            </span>
                                        @enderror

                                    </div>
                                    <div class="form-group col-md-6">
                                        <label for="user_pass" class="mt-2">Enter New Password<span
                                                class="text-danger">*</span></label>
                                        <input type="password" id="user_pass" name="user_pass" value=""
                                            class="form-control @error('user_pass') is-invalid @enderror">
                                        @error('user_pass')
                                            <span class="invalid-feedback form-invalid fw-bold" role="alert">
                                                {{ $message }}
                                            </span>
                                        @enderror

                                    </div>
                                    <div class="form-group col-md-6">
                                        <label for="user_pass_confirmation" class="mt-2">Confirm New Password<span
                                                class="text-danger">*</span></label>
                                        <input type="password" id="user_pass_confirmation" name="user_pass_confirmation" value=""
                                            class="form-control @error('user_pass_confirmation') is-invalid @enderror">
                                        @error('user_pass_confirmation')
                                            <span class="invalid-feedback form-invalid fw-bold" role="alert">
                                                {{ $message }}
                                            </span>
                                        @enderror

                                    </div>

                                </div>
                                <div class="mt-3">
                                    <input class="btn btn-primary" type="submit" value="Change">
                                </div>
                            </form>
                        </div>
                    </div>
            </div>
        </div>
    </div>
@endsection
