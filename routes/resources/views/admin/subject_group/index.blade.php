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
        <div class="row">
            <div class="col-md-12">
                <div class="card border-0 bg-white">
                    <div class="card-header bg-white d-flex align-items-center justify-content-between">
                        <h5 class="mb-0 mt-0">{{ __('Subject Group Master') }}</h5>
                        
                         <div class=" flex-column d-flex align-items-end ">
                            <a href="{{ route('admin.subject-group-master.create') }}" class="btn btn-success text-white"><span class="mdi mdi-plus-circle-outline me-2"></span>Add</a>
                             <div class="d-flex align-items-center gap-1 mt-2">
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
                                    <button type="submit" class="btn btn-sm btn-dark mx-2 d-flex align-items-center gap-1"><span class="mdi mdi-magnify "></span> Search</button>
                                </form>
                                <a href="{{ route('admin.subject-group-master.index') }}" class="btn btn-dark">Reset</a>
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
                                                <div class="d-flex gap-2 align-items-center">
                                                    <a href="{{ route('admin.subject-group-master.edit', $value->id) }}"
                                                        class=" btn-icon editbtnGlobal">
                                                        <i class="mdi mdi-pencil" data-bs-toggle="tooltip" data-bs-offset="0,4"
                                                            data-bs-placement="top" title="Edit"></i>
                                                    </a>

                                                    {{-- <form action="{{ route('admin.subject-master.softDelete', $value->id) }}"
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
