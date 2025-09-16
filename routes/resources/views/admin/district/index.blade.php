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
        <div class="row ">
            <div class="col-md-12">
                <div class="card border-0 bg-white">
                    <div class="card-header flex-wrap bg-white d-flex align-items-center justify-content-between">
                        <h5 class="mb-0 mt-0">{{ __('District Master') }}</h5>
                        

                        <div class=" flex-column d-flex align-items-end ">
                            <a href="{{ route('admin.district-master.create') }}" class="btn btn-success text-white"><span class="mdi mdi-plus-circle-outline me-2"></span>Add</a>
                             <div class="d-flex align-items-center gap-1 mt-2">
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
                                     <button type="submit" class="btn btn-sm btn-dark mx-2 d-flex align-items-center gap-1"><span class="mdi mdi-magnify "></span> Search</button>
                                </form>
                                <a href="{{ route('admin.district-master.index') }}" class="btn btn-dark">Reset</a>
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
                                                <div class="d-flex gap-2 align-items-center">

                                                <a href="{{ route('admin.district-master.edit', $value->id) }}"
                                                    class=" btn-icon editbtnGlobal">
                                                    <i class="mdi mdi-pencil" data-bs-toggle="tooltip" data-bs-offset="0,4"
                                                        data-bs-placement="top" title="Edit"></i>
                                                </a>

                                                {{-- <form action="{{ route('admin.district-master.softDelete', $value->id) }}"
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
