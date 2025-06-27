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
                    swal("Error", "{{ Session::get('error') }}", "error").then(() => {
                        location.reload();
                    });
                </script>
            @endsection
        @endif
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">{{ __('District Master') }}
                        <a href="{{ route('admin.district-master.create') }}" class="btn btn-info"
                            style="float: right;">Add</a>
                        <div class="col-lg-12 col-md-12">
                            <div class="right-item d-flex justify-content-end mt-4">
                                <form action="{{ route('admin.district-master.index') }}" method="get" class="d-flex">
                                    <select name="state_id" id="state_id" class="form-control mx-1" required>
                                        <option value="">Select State</option>
                                        @if (count($states) > 0)
                                            @foreach ($states as $key => $state)
                                                <option value="{{ $key }}"
                                                    {{ old('state_id', request()->get('state_id') !== null ? request()->get('state_id') : '') == $key ? 'selected' : '' }}>
                                                    {{ $state }}</option>
                                            @endforeach
                                        @else
                                            <option value="">No State Found</option>
                                        @endif
                                    </select>
                                    <button type="submit" class="btn btn-sm btn-info mx-2">Search</button>
                                </form>
                                <a href="{{ route('admin.district-master.index') }}" class="btn btn-warning">Reset</a>
                            </div>
                        </div>
                    </div>

                    <div class="card-body">

                        <div class="table">
                            <table id="example" class="table table-striped table-bordered">
                                <thead>
                                    <tr>
                                        <th>S No.</th>
                                        <th>District</th>
                                        <th>State</th>
                                        <th class="text-center">Action</th>
                                    </tr>
                                </thead>

                                @if (count($data) > 0)
                                    @foreach ($data as $key => $value)
                                        <tr data-entry-id="{{ $value->id }}">
                                            <td>{{ $data->firstItem() + $key ?? '' }}</td>
                                            <td>{{ $value->name ?? '' }}</td>
                                            <td>{{ $value->state->name ?? '' }}</td>


                                            <td class="text-center">


                                                <a href="{{ route('admin.district-master.edit', $value->id) }}"
                                                    class="btn btn-sm btn-icon p-1">
                                                    <i class="mdi mdi-pencil" data-bs-toggle="tooltip" data-bs-offset="0,4"
                                                        data-bs-placement="top" title="Edit"></i>
                                                </a>

                                                {{-- <form action="{{ route('admin.district-master.softDelete', $value->id) }}"
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
                                        <td colspan="5">No District Found</td>
                                    </tr>
                                @endif
                            </table>

                            @if (request()->get('state_id'))
                                {{ $data->appends(['state_id' => request()->get('state_id')])->links() }}
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
