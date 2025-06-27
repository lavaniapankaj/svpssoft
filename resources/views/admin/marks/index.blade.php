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
                <div class="card-header">{{ __('Marks Master') }}
                    <a href="{{ route('admin.marks-master.create') }}" class="btn btn-info" style="float: right;">Add</a>
                    <div class="col-lg-12 col-md-12">
                        <div class="right-item d-flex justify-content-end mt-4">
                            <form action="{{ route('admin.marks-master.index') }}" method="get" class="d-flex">
                                <select name="class_id" id="class_id" class="form-control mx-1" required>
                                    <option value="">Select Class</option>
                                    @if (count($classes) > 0)
                                    @foreach ($classes as $key => $class)
                                    <option value="{{ $key }}" {{ request()->get('class_id') == $key ? 'selected' : ''}}>{{ $class }}</option>
                                    @endforeach
                                    @else
                                    <option value="">No Class Found</option>
                                    @endif
                                </select>

                                <input type="hidden" id="initialSubjectId" value="{{ old('subject_id',request()->get('subject_id') !== null ? request()->get('subject_id') : '') }}">
                                <select name="subject_id" id="subject_id" class="form-control mx-1" required>
                                    <option value="">Select Subject</option>
                                </select>
                                <img src="{{ config('myconfig.myloader') }}" alt="Loading..." class="loader" id="loader" style="display:none; width:10%;">
                                <button type="submit" class="btn btn-sm btn-info mx-2">Search</button>
                            </form>
                            <a href="{{ route('admin.marks-master.index') }}" class="btn btn-warning">Reset</a>
                        </div>
                    </div>

                </div>

                <div class="card-body">
                    @if (request('class_id') && request('subject_id'))
                    <div class="table">
                        <table id="example" class="table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th>S No.</th>
                                    <th>Exam</th>
                                    <th>Minimum Marks</th>
                                    <th>Maximum Marks</th>
                                    <th class="text-center">Action</th>
                                </tr>
                            </thead>

                            @if (count($data) > 0)
                            @foreach ($data as $key => $value)
                            <tr data-entry-id="{{ $value->id }}">
                                <td>{{ $data->firstItem() + $key ?? '' }}</td>
                                <td>{{ $value->exam->exam ?? '' }}</td>
                                <td>{{ $value->min_marks ?? '' }}</td>
                                <td>{{ $value->max_marks ?? '' }}</td>
                                <td class="text-center">

                                    <a href="{{ route('admin.marks-master.edit', $value->id) }}"
                                        class="btn btn-sm btn-icon p-1">
                                        <i class="mdi mdi-pencil" data-bs-toggle="tooltip" data-bs-offset="0,4"
                                            data-bs-placement="top" title="Edit"></i>
                                    </a>


                                </td>
                            </tr>
                            @endforeach
                            @else
                            <tr>
                                <td colspan="6">No Marks Found</td>
                            </tr>
                            @endif
                        </table>

                        @if (request()->get('class_id') || request()->get('subject_id'))
                        {{ $data->appends(['class_id' => request()->get('class_id'),'subejct_id' => request()->get('subject_id')])->links() }}
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
@section('admin-scripts')
<script>
    $(document).ready(function() {
        getClassSubject($('#class_id').val(), $('#initialSubjectId').val());
    });
</script>
@endsection