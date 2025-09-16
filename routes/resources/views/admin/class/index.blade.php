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
                    swal("Error", "{{ Session::get('error') }}", "error").then(() => {
                                    location.reload();
                                });
                </script>
            @endsection
        @endif
        <div class="row">
            <div class="col-md-12">
                 <div class="card border-0 bg-white">
                    <div class="card-header bg-white d-flex align-items-center justify-content-between">
                        <h5 class="mb-0 mt-0">{{ __('Class Master') }}</h5>
                        <a href="{{ route('admin.class-master.create') }}" class="btn btn-success text-white">
                            <span class="mdi mdi-plus-circle-outline me-2"></span>Add</a>
                    </div>

                    <div class="card-body">

                        <div class="table">
                            <table id="example" class="table table-striped table-bordered">
                                <thead>
                                    <tr>
                                        <th>S No.</th>
                                        <th>Class</th>
                                        <th>Class Sorting</th>
                                        <th class="text-center">Action</th>
                                    </tr>
                                </thead>

                                @if (count($data) > 0)
                                    @foreach ($data as $key => $value)
                                        <tr data-entry-id="{{ $value->id }}">
                                            <td>{{ $data->firstItem() + $key ?? '' }}</td>
                                            <td>{{ $value->class ?? '' }}</td>
                                            <td>{{ $value->sort ?? '' }}</td>


                                            <td class="text-center">
                                                <div class="d-flex gap-2 align-items-center">
                                                    <a href="{{ route('admin.class-master.edit', $value->id) }}"
                                                        class=" btn-icon editbtnGlobal">
                                                        <i class="mdi mdi-pencil" data-bs-toggle="tooltip" data-bs-offset="0,4"
                                                            data-bs-placement="top" title="Edit"></i>
                                                    </a>

                                                    {{-- <form action="{{ route('admin.class-master.softDelete', $value->id) }}"
                                                        method="POST" style="display:inline;">
                                                        @csrf
                                                        <button type="submit" class=" btn-icon  delete-form-btn deletebtnGlobal"
                                                            data-bs-toggle="tooltip" data-bs-offset="0,4"
                                                            data-bs-placement="top" data-bs-html="true" title="Delete">
                                                            <i class="mdi mdi-delete"></i>
                                                        </button>
                                                    </form> --}}
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                @else
                                    <tr>
                                        <td colspan="5">No Class Found</td>
                                    </tr>
                                @endif
                            </table>

                            {{ $data->links() }}

                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
