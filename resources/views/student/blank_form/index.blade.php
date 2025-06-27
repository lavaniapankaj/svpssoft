@extends('student.index')
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
                        {{ 'Blank Admission Form' }}
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <a class="btn btn-sm btn-primary" href="{{ route('student.blank.play')}}" target="_blank" rel="noopener noreferrer">Play School</a>
                            </div>
                            <div class="col-md-6">
                                <a class="btn btn-sm btn-primary" href="{{ route('student.blank.public')}}" target="_blank" rel="noopener noreferrer">Public School</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

