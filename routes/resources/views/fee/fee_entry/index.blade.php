@extends('fee.index')
@section('sub-content')
    <div class="container-fluid">
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
        <div class="row">
            <div class="col-md-12">
                <div class="card border-0 bg-white">
           <div class="card-header flex-wrap bg-white d-flex align-items-center justify-content-between"><h5 class="mb-0 mt-0">{{ 'Fee Entry' }}</h5>

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

