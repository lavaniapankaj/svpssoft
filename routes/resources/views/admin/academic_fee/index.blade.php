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
        <div class="row ">
            <div class="col-md-12">
                <div class="card border-0 bg-white">
                    <div class="card-header flex-wrap bg-white d-flex align-items-center justify-content-between">
                        <h5 class="mb-0 mt-0">{{ __('Academic Fee Master') }}</h5>
                       
                        <div class=" flex-column d-flex align-items-end ">
                             <a href="{{ route('admin.academic-fee-master.create') }}" class="btn btn-success text-white"><span class="mdi mdi-plus-circle-outline me-2"></span>Add</a>
                           <div class="d-flex align-items-center gap-1 mt-2">
                                <form action="" method="get" class="d-flex">
                                    <select name="session_id" id="session_id" class="form-control mx-1" required>
                                        <option value="">Select Session</option>
                                        @if (count($sessions) > 0)
                                            @foreach ($sessions as $key => $session)
                                                <option value="{{ $key }}" {{ request()->get('session_id') == $key ? 'selected' : ''}}>{{ $session }}</option>
                                            @endforeach
                                        @else
                                            <option value="">No Session Found</option>
                                        @endif
                                    </select>
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
                                    <button type="submit" class="btn btn-sm btn-dark mx-2 d-flex align-items-center gap-1"><span class="mdi mdi-magnify "></span> Search</button>
                                </form>
                                <a href="{{ route('admin.academic-fee-master.index') }}" class="btn btn-dark">Reset</a>
                            </div>
                        </div>
                    </div>

                    <div class="card-body">

                        <div class="table col-lg-12 col-md-12">
                            <table id="example" class="table table-striped table-bordered">
                                <thead>
                                    <tr>
                                        <th>S No.</th>
                                        <th>Session</th>
                                        <th>Class</th>
                                        <th>Admission + Security Fee</th>
                                        <th>Ist Installment</th>
                                        <th>IInd Installment</th>
                                        <th>Discount</th>
                                        <th>Total</th>
                                        <th class="text-center">Action</th>
                                    </tr>
                                </thead>

                                @if (count($data) > 0)
                                    @foreach ($data as $key => $value)
                                        <tr data-entry-id="{{ $value->id }}">
                                            <td>{{ $data->firstItem() + $key ?? '' }}</td>
                                            <td>{{ $value->session->session ?? '' }}</td>
                                            <td>{{ $value->class->class ?? '' }}</td>
                                            <td>{{ $value->admission_fee ?? '' }}</td>
                                            <td>{{ $value->inst_1 ?? '' }}</td>
                                            <td>{{ $value->inst_2 ?? '' }}</td>
                                            <td>{{ $value->ins_discount ?? '' }}</td>
                                            <td>{{ $value->inst_total ?? '' }}</td>


                                            <td class="text-center">
                                                <a href="{{ route('admin.academic-fee-master.edit', $value->id) }}"
                                                    class=" btn-icon editbtnGlobal">
                                                    <i class="mdi mdi-pencil" data-bs-toggle="tooltip" data-bs-offset="0,4"
                                                        data-bs-placement="top" title="Edit"></i>
                                                </a>


                                            </td>
                                        </tr>
                                    @endforeach
                                @else
                                    <tr>
                                        <td colspan="10">No Academic Fee Found</td>
                                    </tr>
                                @endif
                            </table>

                            @if (request()->get('session_id') || request()->get('class_id'))
                                {{ $data->appends(['session_id' => request()->get('session_id'), 'class_id' => request()->get('class_id')])->links() }}
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
<!-- @section('admin-scripts')
    <script>
        var initialClassId =
        '{{ old('class_id', request()->get('class_id') !== null ? request()->get('class_id') : '') }}';
        var initialSessionId =
            '{{ old('session_id', request()->get('session_id') !== null ? request()->get('session_id') : '') }}';
        getClassSection(initialClassId);
        getSession(initialSessionId);
    </script>
@endsection -->
