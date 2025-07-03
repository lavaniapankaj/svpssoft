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
                    swal("Error", "{{ Session::get('error') }}", "error").then(() => {
                        location.reload();
                    });
                </script>
            @endsection
        @endif
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="card">
                    <div class="card-header">{{ __('Student Master') }}

                        <a href="{{ route('student.student-master.create') }}" class="btn btn-info my-3 mx-2"
                            style="float: right;">Add</a>
                        <div class="col-lg-14 col-md-14">
                            <div class="right-item d-flex justify-content-end mt-5">
                                <form action="{{ route('student.student-master.index') }}" method="get" class="d-flex">
                                    <input type="text" name="search" id="search" class="form-control mx-2"
                                        placeholder="Search by Name or SR No."
                                        value="{{ old('search', request()->get('search') !== null ? request()->get('search') : '') }}"
                                        required>
                                    <button type="submit" class="btn btn-sm btn-info">Search</button>
                                </form>
                                <a href="{{ route('student.student-master.index') }}" class="btn btn-warning mx-2">Reset</a>
                            </div>
                        </div>
                    </div>

                    <div class="card-body">
                        @if (request('search'))
                            <div class="table">
                                <table id="example" class="table table-striped table-bordered">
                                    <thead>
                                        <tr>
                                            <th>S No.</th>
                                            <th>SR No.</th>
                                            <th>Student Name</th>
                                            <th>Class</th>
                                            <th>Section</th>
                                            <th>Father's Name</th>
                                            <th>Mother's Name</th>
                                            <th class="text-center">Action</th>
                                        </tr>
                                    </thead>

                                    @if (count($data) > 0)
                                        @foreach ($data as $key => $value)
                                            <tr data-entry-id="{{ $value->id }}">
                                                <td>{{ $data->firstItem() + $key ?? '' }}</td>
                                                <td>{{ $value->srno ?? '-' }}</td>
                                                <td>{{ $value->student_name ?? '-' }}</td>
                                                <td>{{ $value->class_name ?? '-' }}</td>
                                                <td>{{ $value->section_name ?? '-' }}</td>
                                                <td>{{ $value->f_name ?? '-' }}</td>
                                                <td>{{ $value->m_name ?? '-' }}</td>


                                                <td class="text-center">
                                                    <a href="{{ route('student.student-master.show', $value->id) }}"
                                                        class="btn btn-sm btn-icon p-1">
                                                        <i class="mdi mdi-eye mx-1" data-bs-toggle="tooltip"
                                                            data-bs-offset="0,4" data-bs-placement="top" title="View"></i>
                                                    </a>

                                                    <a href="{{ route('student.student-master.edit', $value->id) }}"
                                                        class="btn btn-sm btn-icon p-1">
                                                        <i class="mdi mdi-pencil" data-bs-toggle="tooltip"
                                                            data-bs-offset="0,4" data-bs-placement="top" title="Edit"></i>
                                                    </a>


                                                </td>
                                            </tr>
                                        @endforeach
                                    @else
                                        <tr>
                                            <td colspan="8">No Student Found</td>
                                        </tr>
                                    @endif
                                </table>

                                @if (request()->get('search'))
                                    {{ $data->appends(['search' => request()->get('search')])->links() }}
                                @else
                                    {{ $data->links() }}
                                @endif

                            </div>
                        @else
                            <div class="table">
                                <table id="example" class="table table-striped table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Previous Completed Record(Play School)</th>
                                            <th>Previous Completed Record(Public School)</th>

                                        </tr>
                                    </thead>
                                    <tbody>

                                        <tr>
                                            <td class="text-primary">
                                                <table id="example" class="table table-striped table-bordered">
                                                    <thead>
                                                        <tr>
                                                            <th>SR No.</th>
                                                            <th>Student Name</th>
                                                            <th>Father's Name</th>

                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <tr>
                                                            <td>{{ $playSchoolLatestSrno ?? '-' }}</td>
                                                            <td>{{ $playSchoolLatestName ?? '-' }}</td>
                                                            <td>{{ isset($playSchoolLatestFatherName) ? 'SH.' . $playSchoolLatestFatherName : '-' }}
                                                            </td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </td>
                                            <td class="text-primary">
                                                <table id="example" class="table table-striped table-bordered">
                                                    <thead>
                                                        <tr>
                                                            <th>SR No.</th>
                                                            <th>Student Name</th>
                                                            <th>Father's Name</th>

                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <tr>
                                                            <td>{{ $publicSchoolLatestSrno ?? '-' }}</td>
                                                            <td>{{ $publicSchoolLatestName ?? '-' }}</td>
                                                            <td>{{ isset($publicSchoolLatestFatherName) ? 'SH.' . $publicSchoolLatestFatherName : '-' }}
                                                            </td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </td>

                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
