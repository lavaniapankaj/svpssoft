@extends('student.index')
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
           <div class="card-header flex-wrap bg-white d-flex align-items-center justify-content-between"><h5 class="mb-0 mt-0">{{ __('Student Master') }}</h5>
                
                        <div class=" flex-column d-flex align-items-end ">
                            <a href="{{ route('student.student-master.create') }}" class="btn btn-success text-white"><span class="mdi mdi-plus-circle-outline me-2"></span>Add</a>
                            <div class="d-flex align-items-center gap-1 mt-2">
                                <form action="{{ route('student.student-master.index') }}" method="get" class="d-flex">
                                    <input type="text" name="search" id="search" class="form-control mx-2"
                                        placeholder="Search by Name or SR No."
                                        value="{{ old('search', request()->get('search') !== null ? request()->get('search') : '') }}"
                                        required>
                                    <button type="submit" class="btn btn-sm btn-dark mx-2 d-flex align-items-center gap-1"><span class="mdi mdi-magnify "></span> Search</button>
                                </form>
                                <a href="{{ route('student.student-master.index') }}" class="btn btn-dark mx-2">Reset</a>
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
                                                        class=" btn-icon editbtnGlobal">
                                                        <i class="mdi mdi-eye mx-1" data-bs-toggle="tooltip"
                                                            data-bs-offset="0,4" data-bs-placement="top" title="View"></i>
                                                    </a>

                                                    <a href="{{ route('student.student-master.edit', $value->id) }}"
                                                        class=" btn-icon editbtnGlobal">
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
