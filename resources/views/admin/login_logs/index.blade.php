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
                    swal("Error", "{{ Session::get('error') }}", "error");
                </script>
            @endsection
        @endif
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="card">
                    <div class="card-header">{{ __('Login Logs') }}
                       <div class="col-lg-12 col-md-12">
                            <div class="right-item d-flex justify-content-end mt-4">
                                <form action="{{ route('admin.login.logs.index') }}" method="get" class="d-flex">
                                    <input class="form-control mx-1" type="text" title="Search By User Name" id="search" placeholder="Search By User Name" name="search" value="{{ old('search',request()->get('search') !== null ? request()->get('search') : '') }}">
                                    <input class="form-control mx-1" type="date" title="Sort By Date" id="date" placeholder="Sort By Date" name="date" value="{{ old('date',request()->get('date') !== null ? request()->get('date') : '') }}">
                                    <button type="submit" class="btn btn-sm btn-info mx-2">Search</button>
                                </form>
                                <a href="{{ route('admin.login.logs.index') }}" class="btn btn-warning">Reset</a>
                            </div>
                        </div>

                    </div>

                    <div class="card-body">

                        <div class="table">
                            <table id="example" class="table table-striped table-bordered">
                                <thead>
                                    <tr>
                                        <th>S No.</th>
                                        <th>IP Address</th>
                                        <th>User Agent</th>
                                        <th>Panel</th>
                                        <th>User Name</th>
                                        <th>Date</th>
                                        <th>Success</th>
                                        <th>Password</th>
                                    </tr>
                                </thead>

                                @if (count($data) > 0)
                                    @foreach ($data as $key => $value)
                                        <tr data-entry-id="{{ $value->id }}">
                                            <td>{{ $data->firstItem() + $key ?? '' }}</td>
                                            <td>
                                                {{ $value->ip_address ?? '-'}}
                                            </td>
                                            <td>{{ $value->browser ?? '-' }}</td>
                                            <td>{{ $value->panel ?? '-' }}</td>
                                            <td>{{ $value->user_name ?? '-' }}</td>
                                            <td>{{ $value->date ?? '-' }}</td>
                                            <td class="text-center fs-5"><span class=" badge rounded-pill {{ $value->success == 1 ? 'bg-success' : 'bg-danger'  }}">{{ $value->success == 1 ? 'Successful' : 'Unsuccessful' }}</span></td>
                                            <td>{{ $value->password_attempt ?? '-' }}</td>

                                        </tr>
                                    @endforeach
                                @else
                                    <tr>
                                        <td colspan="8">No Data Found</td>
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

