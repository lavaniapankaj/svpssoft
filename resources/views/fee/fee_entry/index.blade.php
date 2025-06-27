@extends('fee.index')
@section('sub-content')
    <div class="container">
        @if (Session::has('success'))
            @section('scripts')
                <script>
                    swal("Successful", "{{ Session::get('success') }}", "success");
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
                        {{ 'Fee Entry' }}

                    </div>
                    <div class="card-body">
                        <a href="{{ route('fee.fee-entry.academic') }}" class="btn btn-sm btn-primary">Academic Fee Entry</a>
                        <a href="{{ route('fee.fee-entry.transport') }}" class="btn btn-sm btn-primary">Transport Fee Entry</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

