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
                    swal("Error", "{{ Session::get('error') }}", "error");
                </script>
            @endsection
        @endif
        <div class="row ">
            <div class="col-md-12">
                <div class="card border-0 bg-white">
           <div class="card-header flex-wrap bg-white d-flex align-items-center justify-content-between"><h5 class="mb-0 mt-0">{{ __('SR Number Login Zone(Edit Student)') }}</h5>
                        
                        <div class=" flex-column d-flex align-items-end ">
                            <a href="{{ route('admin.editSection.index') }}" class="btn bg-light btn-sm" ><span class="mdi mdi-chevron-left me-2"></span>Back</a>
                            <div class="d-flex align-items-center gap-1 mt-2">
                                <form action="{{ route('admin.editSection.std') }}" method="get" class="d-flex">
                                     <select name="session_id" id="session_id" class="form-control mx-1" required>
                                        <option value="">Select Session</option>
                                        @if (count($sessions) > 0)
                                            @foreach ($sessions as $key => $session)
                                                <option value="{{ $key }}"
                                                    {{ old('session_id', request()->get('session_id') !== null ? request()->get('session_id') : '') == $key ? 'selected' : '' }}>{{ $session }}
                                                </option>
                                            @endforeach
                                        @else
                                            <option value="">No Session Found</option>
                                        @endif
                                    </select>
                                    <button type="submit" class="btn btn-sm btn-dark mx-2 d-flex align-items-center gap-1"><span class="mdi mdi-magnify "></span> Search</button>
                                </form>
                                <a href="{{ route('admin.editSection.std') }}" class="btn btn-dark">Reset</a>
                            </div>
                        </div>
                    </div>

                    <div class="card-body">
                        <div class="table" id="std-container">
                            <table id="example" class="table table-striped table-bordered">
                                <thead>
                                    <tr>
                                        <th>S No.</th>
                                        <th>SRNO</th>
                                        <th>Student Name</th>
                                        <th>Father's Name</th>
                                        <th>Class</th>
                                        <th>Section</th>
                                        <th class="text-center">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @if (count($data) > 0)
                                        @foreach ($data as $key => $st)
                                            <tr>
                                                <td>{{ $data->firstItem() + $key ?? '' }}</td>
                                                <td>{{ $st->srno }}</td>
                                                <td>{{ $st->name }}</td>
                                                <td>{{ $st->f_name }}</td>
                                                <td>{{ $st->class_name }}</td>
                                                <td>{{ $st->section_name }}</td>
                                                <td class="text-center">
                                                    <a href="{{ route('admin.student-master.edit', $st->id) }}"
                                                        class=" btn-icon editbtnGlobal"
                                                        id="edit-section-editBtn">
                                                        <i class="mdi mdi-pencil edit-section-editBtn"
                                                            data-bs-toggle="tooltip" data-bs-offset="0,4"
                                                            data-bs-placement="top" title="Edit"
                                                            id="edit-section-editBtn"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        @endforeach
                                        @else
                                        <tr>
                                            <td colspan="7">No Student Found</td>
                                        </tr>
                                    @endif
                                </tbody>

                            </table>
                            @if (request()->get('session_id'))
                                {{ $data->appends(['session_id' => request()->get('session_id')])->links() }}
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

