@extends('layouts.app')

@section('content')
    <div class="container">
        @if (Session::has('success'))
            @section('scripts')
                <script>
                    swal("Good job!", "{{ Session::get('success') }}", "success");
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
                    <div class="card-header">{{ __('Attendance-schedule') }}
                        <a href="{{ route('admin.attendance-schedule-master.create') }}" class="btn btn-info" style="float: right;">Add</a>
                    </div>

                    <div class="card-body">

                        <div class="table">
                            <table id="example" class="table table-striped table-bordered">
                                <thead>
                                    <tr>
                                        <th>S No.</th>
                                        <th>Session</th>
                                        <th>Date</th>
                                        <th>Reason</th>
                                        <th class="text-center">Action</th>
                                    </tr>
                                </thead>

                                @if (count($data) > 0)
                                    @foreach ($data as $key => $value)
                                        <tr data-entry-id="{{ $value->id }}">
                                            <td>{{ $key + 1 ?? '' }}</td>
                                            <td>{{ $value->session->session ?? '' }}</td>
                                            <td>{{ $value->a_date ?? '' }}</td>
                                            <td>{{ $value->reason ?? '' }}</td>


                                            <td class="text-center">
                                                <a href="{{ route('admin.attendance-schedule-master.show', $value->id) }}"
                                                    class="btn btn-sm btn-icon p-1">
                                                    <i class="mdi mdi-eye mx-1" data-bs-toggle="tooltip"
                                                        data-bs-offset="0,4" data-bs-placement="top" title="View"></i>
                                                </a>

                                                <a href="{{ route('admin.attendance-schedule-master.edit', $value->id) }}"
                                                    class="btn btn-sm btn-icon p-1">
                                                    <i class="mdi mdi-pencil" data-bs-toggle="tooltip" data-bs-offset="0,4"
                                                        data-bs-placement="top" title="Edit"></i>
                                                </a>

                                                <form action="{{ route('admin.attendance-schedule-master.softDelete', $value->id) }}"
                                                    method="POST" style="display:inline;">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-icon p-1"
                                                        data-bs-toggle="tooltip" data-bs-offset="0,4"
                                                        data-bs-placement="top" data-bs-html="true" title="Delete">
                                                        <i class="mdi mdi-delete"></i>
                                                    </button>
                                                </form>

                                            </td>
                                        </tr>
                                    @endforeach
                                @else
                                    <tr>
                                        <td colspan="5">No Data Found</td>
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
