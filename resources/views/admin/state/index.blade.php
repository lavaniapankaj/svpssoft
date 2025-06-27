@extends('admin.index')

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
                    <div class="card-header">{{ __('State Master') }}
                        <a href="{{ route('admin.state-master.create') }}" class="btn btn-info" style="float: right;">Add</a>
                        <div class="col-lg-12 col-md-12">
                            <div class="right-item d-flex justify-content-end mt-4">
                                <form action="" method="get" class="d-flex">
                                    <input type="text" name="search" id="search" class="form-control mx-2"
                                        placeholder="Search by Name" value="{{ old('search', request()->get('search') !== null ? request()->get('search') : '') }}">
                                    <button type="submit" class="btn btn-sm btn-info mx-2">Search</button>
                                </form>
                                <a href="{{ route('admin.state-master.index') }}" class="btn btn-warning">Reset</a>
                            </div>
                        </div>
                    </div>

                    <div class="card-body">

                        <div class="table">
                            <table id="example" class="table table-striped table-bordered">
                                <thead>
                                    <tr>
                                        <th>S No.</th>
                                        <th>State</th>
                                        <th class="text-center">Action</th>
                                    </tr>
                                </thead>

                                @if (count($data) > 0)
                                    @foreach ($data as $key => $value)
                                        <tr data-entry-id="{{ $value->id }}">
                                            <td>{{ $data->firstItem() + $key ?? '' }}</td>
                                            <td>{{ $value->name ?? '' }}</td>


                                            <td class="text-center">

                                                <a href="{{ route('admin.state-master.edit', $value->id) }}"
                                                    class="btn btn-sm btn-icon p-1">
                                                    <i class="mdi mdi-pencil" data-bs-toggle="tooltip" data-bs-offset="0,4"
                                                        data-bs-placement="top" title="Edit"></i>
                                                </a>

                                                {{-- <form action="{{ route('admin.state-master.softDelete', $value->id) }}"
                                                    method="POST" style="display:inline;">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-icon p-1 delete-form-btn"
                                                        data-bs-toggle="tooltip" data-bs-offset="0,4"
                                                        data-bs-placement="top" data-bs-html="true" title="Delete">
                                                        <i class="mdi mdi-delete"></i>
                                                    </button>
                                                </form> --}}

                                            </td>
                                        </tr>
                                    @endforeach
                                @else
                                    <tr>
                                        <td colspan="5">No State Found</td>
                                    </tr>
                                @endif
                            </table>
                            @if (request()->get('search'))
                                {{ $data->appends(['search' => request()->get('search')])->links() }}
                            @else
                                {{ $data->links() }}
                            @endif



                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
