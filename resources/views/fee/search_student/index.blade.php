@extends('fee.index')
@section('sub-content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card border-0 bg-white">
                <div class="card-header flex-wrap bg-white d-flex align-items-center justify-content-between">
                    <h5 class="mb-0 mt-0">{{ __('Student Master') }}</h5>
                    <div class=" flex-column d-flex align-items-end ">
                        <div class="d-flex align-items-center gap-1 mt-2">
                            <form action="{{ route('fee.student.index') }}" method="get" class="d-flex">
                                <input type="text" name="search" id="search" class="form-control mx-2" placeholder="Search by Name or SR No." value="{{ old('search', request()->get('search') !== null ? request()->get('search') : '') }}" required>
                                <button type="submit" class="btn btn-sm btn-dark mx-2 d-flex align-items-center gap-1"><span class="mdi mdi-magnify "></span> Search</button>
                            </form>
                            <a href="{{ route('fee.student.index') }}" class="btn btn-dark mx-2">Reset</a>
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
                                    </tr>
                                @endforeach
                            @else
                            <tr>
                                <td colspan="7">No Student Found</td>
                            </tr>
                            @endif
                        </table>
                        @if (request()->get('search'))
                            {{ $data->appends(['search' => request()->get('search')])->links() }}
                        @else
                            {{ $data->links() }}
                        @endif
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
@section('fee-scripts')

@endsection