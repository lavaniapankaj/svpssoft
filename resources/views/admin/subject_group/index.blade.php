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
                    <div class="card-header">{{ __('Subject Group Master') }}
                        <a href="{{ route('admin.subject-group-master.create') }}" class="btn btn-info" style="float: right;">Add</a>
                        <div class="col-lg-12 col-md-12">
                            <div class="right-item d-flex justify-content-end mt-4">
                                <form action="{{ route('admin.subject-group-master.index') }}" method="get" class="d-flex">
                                    <input type="hidden" id="initialClassId" value="{{ old('initialClassId',request()->get('class_id') != null ? request()->get('class_id') : '') }}">
                                    <select name="class_id" id="class_id" class="form-control mx-1" required>
                                        <option value="">Select Class</option>
                                        @if (count($classes) > 0)
                                            @foreach ($classes as $key => $class)
                                                <option value="{{ $key }}" {{ request()->get('class_id') == $key ? 'selected' : ''}}>{{ $class }}</option>
                                            @endforeach
                                        @else
                                            <option value="">No Classes Found</option>
                                        @endif
                                    </select>
                                    <input type="hidden" name="subjectGroup_controller" id="subjectGroup-controller" value="SubjectGroupSection">
                                    <input type="hidden" id="initialSubjectId" name="initialSubjectId" value="{{ old('initialSubjectId',request()->get('subject_id') != null ? request()->get('subject_id') : '') }}">
                                    <select name="subject_id" id="subject_id" class="form-control mx-1" required>
                                        <option value="">Select Subject</option>
                                    </select>
                                  <img src="{{ config('myconfig.myloader') }}" alt="Loading..." class="loader" id="loader" style="display:none; width:10%;">
                                    <button type="submit" class="btn btn-sm btn-info mx-2">Search</button>
                                </form>
                                <a href="{{ route('admin.subject-group-master.index') }}" class="btn btn-warning">Reset</a>
                            </div>
                        </div>

                    </div>

                    <div class="card-body">

                        <div class="table">
                            <table id="example" class="table table-striped table-bordered">
                                <thead>
                                    <tr>
                                        <th>S No.</th>
                                        <th>Class</th>
                                        <th>Subject</th>
                                        <th>Sub Subject</th>
                                        <th>Priority</th>
                                        <th class="text-center">Action</th>
                                    </tr>
                                </thead>

                                @if (count($data) > 0)
                                    @foreach ($data as $key => $value)
                                        <tr data-entry-id="{{ $value->id }}">
                                            <td>{{ $data->firstItem() + $key ?? '' }}</td>
                                            <td>{{ $value->class->class ?? '' }}</td>
                                            <td>{{ $value->subjectGroup->subject ?? '' }}</td>
                                            <td>
                                                {{ $value->by_m_g == 1 ? $value->subject . ' (Result By Marks)' : $value->subject . ' (Result By Grade)' }}
                                            </td>
                                            <td>{{ $value->priority }}</td>
                                            <td class="text-center">

                                                <a href="{{ route('admin.subject-group-master.edit', $value->id) }}"
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
                                        <td colspan="6">No Subject Group Found</td>
                                    </tr>
                                @endif
                            </table>

                            @if (request()->get('class_id') || request()->get('subject_id'))
                                {{ $data->appends(['class_id' => request()->get('class_id'),'subejct_id' => request()->get('subject_id')])->links() }}
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
@section('admin-scripts')
    <script>
        $(document).ready(function () {
           getClassSubject($('#class_id').val(),$('#initialSubjectId').val(),$('#subjectGroup-controller'));
        });

    </script>
@endsection
