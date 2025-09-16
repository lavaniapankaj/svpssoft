@extends('layouts.app')
@section('styles')
    <style>
        nav.navbar.navbar-expand-md.navbar-light.bg-white.shadow-sm {
            display: none;
        }
        .cardmax{
            max-width: 400px;
        }
        .btnminwidth{
            min-width: 100%;
            margin: auto;
        }
        .minheight{
            min-height: 80vh;
        }
    </style>
    <script>
        // Prevent form resubmission on refresh and back button
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
    </script>
@endsection
@section('content')
    <div class="container-fluid ">
        <div class="row justify-content-center align-items-center minheight">
            <div class="col-md-12">
                <div class="card m-auto cardmax shadow mt-5 border-0 bg-white">
                    <div class="card-header flex-wrap bg-white d-flex align-items-center justify-content-center"><h5 class="mb-0 mt-0 text-center">{{ __('Login') }}</h5></div>

                    <div class="card-body">
                        <form method="POST" action="{{ route('login') }}">
                            @csrf
                            <input type="hidden" name="loginPath" value="{{ request()->path() }}">
                            {{--<div class="row mb-3">
                                    <div class="col-md-12">
                                        <label for="email" class="form-label">{{ __('Email Address') }}</label>
                                        <input id="email" type="email"
                                            class="form-control @error('email') is-invalid @enderror" name="email"
                                            value="{{ old('email') }}" required autocomplete="email" autofocus>

                                        @error('email')
                                        <span class="invalid-feedback" role="alert">
                                                <strong>{{ $message }}</strong>
                                            </span>
                                        @enderror
                                    </div>
                                </div> --}}
                            <div class="row mb-3">
                                

                                <div class="col-md-12">
                                    <label class="form-label" for="username"
                                    class=" ">{{ __('User Name') }}</label>
                                    <input id="username" type="text"
                                        class="form-control @error('username') is-invalid @enderror" name="username"
                                        value="{{ old('username') }}" required autocomplete="username" autofocus>

                                    @error('username')
                                    <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-12">
                                    <label for="password" class="form-label">{{ __('Password') }}</label>
                                    <input id="password" type="password"
                                        class="form-control @error('password') is-invalid @enderror" name="password"
                                        required autocomplete="current-password">

                                    @error('password')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-12">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="remember" id="remember"
                                            {{ old('remember') ? 'checked' : '' }}>

                                        <label class="form-check-label" for="remember">
                                            {{ __('Remember Me') }}
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="row mb-0">
                                <div class="col-md-12 text-center">
                                    <button type="submit" class="btn btnminwidth btn-primary">
                                        {{ __('Login') }}
                                    </button>

                                    <!--@if (Route::has('password.request'))-->
                                    <!--    <a class="btn btn-link" href="{{ route('password.request') }}">-->
                                    <!--        {{ __('Forgot Your Password?') }}-->
                                    <!--    </a>-->
                                    <!--@endif-->
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
