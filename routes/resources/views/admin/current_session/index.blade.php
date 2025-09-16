@extends('admin.index')

@section('sub-content')
    <div class="container-fluid">
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
        <div class="row ">
            <div class="col-md-12">
                <div class="card border-0 bg-white">
           <div class="card-header flex-wrap bg-white d-flex align-items-center justify-content-between">
            <h5 class="mb-0 mt-0">{{ __('Setting') }}</h5>
                        {{-- <a href="{{ route('admin.signature') }}" class="btn btn-success text-white" ><span class="mdi mdi-plus-circle-outline me-2"></span>Principal Signature Upload</a> --}}
                    </div>

                    <div class="card-body">

                        <div class="table">
                            <table id="example" class="table table-striped table-bordered">
                                <thead>
                                    <tr>
                                        <th>S No.</th>
                                        <th>Current Session</th>
                                        <th class="text-center">Action</th>
                                    </tr>
                                </thead>

                                @if (count($data) > 0)
                                    @foreach ($data as $key => $value)
                                        <tr data-entry-id="{{ $key }}">
                                            <td>1</td>

                                            <td>{{ $value ?? '' }}</td>
                                            <td class="text-center">


                                                <a href="{{ route('admin.current-session.edit', $key) }}"
                                                    class=" btn-icon editbtnGlobal">
                                                    <i class="mdi mdi-pencil" data-bs-toggle="tooltip" data-bs-offset="0,4"
                                                        data-bs-placement="top" title="Edit"></i>
                                                </a>


                                            </td>
                                        </tr>
                                    @endforeach
                                @else
                                    <tr>
                                        <td colspan="5">No Current Session Found</td>
                                    </tr>
                                @endif
                            </table>



                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
