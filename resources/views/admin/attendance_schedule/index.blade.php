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
                    <div class="card-header">{{ __('Attendance-schedule') }}
                        <div class="col-lg-12 col-md-12">
                            <div class="right-item d-flex justify-content-end mt-4">
                                <form action="{{ route('admin.attendance_schedule.index') }}" method="get" class="d-flex">
                                    <input type="date" name="a_date" id="a_date" class="form-control"
                                        value="{{ old('a_date', request()->get('a_date') !== null ? request()->get('a_date') : '') }}"
                                        required>
                                    <input type="hidden" name="current_session" value='' id="current_session">
                                    <img src="{{ config('myconfig.myloader') }}" alt="Loading..." class="loader"
                                        id="loader" style="display:none; width:10%;">
                                    <button type="submit" class="btn btn-sm btn-info mx-2">Search</button>
                                </form>
                                <a href="{{ route('admin.attendance_schedule.index') }}" class="btn btn-warning">Reset</a>
                            </div>
                        </div>
                    </div>

                    <div class="card-body">

                        <a href="{{ route('admin.attendance_schedule.create') }}" class="btn btn-info">Create Attendance
                            Schedule</a>
                        <a href="{{ route('admin.attendance_schedule.editDateView') }}" class="btn btn-info">Edit Attendance
                            Schedule</a>
                        {{-- @if (request('a_date')) --}}
                        <div class="table my-2 table-responsive">
                            <input type="hidden" name="current_session" value='' id="current_session">
                            <table id="example" class="table table-striped table-bordered">
                                <thead>
                                    <tr>
                                        <th>S No.</th>
                                        <th>Date</th>
                                        <th>Reason</th>
                                    </tr>
                                </thead>
                                <?php $i = ($data->currentpage()-1)* $data->perpage() + 1;?>

                                @if (count($data) > 0)
                                    @foreach ($data as $key => $value)
                                        <tr data-entry-id="{{ $value->id }}">
                                            <td>{{ $i++ ?? '' }}</td>
                                            <td>{{ $value->a_date ?? '' }}</td>
                                            <td>{{ $value->reason ?? '-' }}</td>
                                        </tr>
                                    @endforeach
                                @else
                                    <tr>
                                        <td colspan="5">No Data Found</td>
                                    </tr>
                                @endif
                            </table>

                            @if (request()->get('a_date') || request()->get('session_id'))
                                {{ $data->appends(['a_date' => request()->get('a_date'), 'session' => request()->get('session_id')])->links() }}
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
