@extends('admin.index')

@section('sub-content')
    <div class="container">
        @if (Session::has('success'))
            @section('scripts')
                <script>
                    swal("Good job!", "{{ Session::get('success') }}", "success").then(() => {
                                    location.reload();
                                });
                </script>
            @endsection
        @endif

        @if (Session::has('error'))
            @section('scripts')
                <script>
                    swal("Oops...", "{{ Session::get('error') }}", "error");
                </script>
            @endsection
        @endif
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">{{ __('Message') }}
                        <a href="{{ route('admin.website-message.create') }}" class="btn btn-info" style="float: right;">Add</a>
                        <div class="col-lg-12 col-md-12">
                            <div class="right-item d-flex justify-content-end mt-4">
                                <form action="{{ route('admin.website-message.index') }}" method="get" class="d-flex">
                                    <input type="text" name="title" id="title" class="form-control mx-2"
                                    placeholder="Search by Title" value="{{ old('title', request()->get('title') !== null ? request()->get('title') : '') }}">
                                  <button type="submit" class="btn btn-sm btn-info mx-2">Search</button>
                                </form>
                                <a href="{{ route('admin.website-message.index') }}" class="btn btn-warning">Reset</a>
                            </div>
                        </div>

                    </div>

                    <div class="card-body">

                        <div class="table">
                            <table id="example" class="table table-striped table-bordered">
                                <thead>
                                    <tr>
                                        <th>S No.</th>
                                        <th>Title</th>
                                        <th>Message</th>
                                        <th class="text-center">Action</th>
                                    </tr>
                                </thead>

                                @if (count($data) > 0)
                                    @foreach ($data as $key => $value)
                                        <tr data-entry-id="{{ $value->id }}">
                                            <td>{{ $data->firstItem() + $key ?? '' }}</td>
                                            <td>
                                                {{ $value->title }}
                                            </td>
                                            <td>
                                                {{ Str::limit($value->message, 20) }}
                                            </td>
                                            <td class="text-center">
                                                {{-- <a href="{{ route('admin.website-message.show', $value->id) }}"
                                                    class="btn btn-sm btn-icon p-1">
                                                    <i class="mdi mdi-eye mx-1" data-bs-toggle="tooltip"
                                                        data-bs-offset="0,4" data-bs-placement="top" title="View"></i>
                                                </a> --}}
                                                <a href="{{ route('admin.website-message.edit', $value->id) }}"
                                                    class="btn btn-sm btn-icon p-1">
                                                    <i class="mdi mdi-pencil" data-bs-toggle="tooltip" data-bs-offset="0,4"
                                                        data-bs-placement="top" title="Edit"></i>
                                                </a>

                                                {{-- <form action="{{ route('admin.subject-master.softDelete', $value->id) }}"
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
                                        <td colspan="4">No Messages Found</td>
                                    </tr>
                                @endif
                            </table>

                            @if (request()->get('title'))
                                {{ $data->appends(['title' => request()->get('title')])->links() }}
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


