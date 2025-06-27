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
                    swal("Error", "{{ Session::get('error') }}", "error");
                </script>
            @endsection
        @endif
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">{{ __('SR Number Login Zone(Edit Student)') }}
                        <a href="{{ route('admin.editSection.index') }}" class="btn btn-warning btn-sm"
                            style="float: right;">Back</a>
                        <div class="col-lg-12 col-md-12">
                            <div class="right-item d-flex justify-content-end mt-4">
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
                                    <button type="submit" class="btn btn-sm btn-info mx-2" id="search">Search</button>
                                </form>
                                <a href="{{ route('admin.editSection.std') }}" class="btn btn-warning">Reset</a>
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
                                                        class="btn btn-sm btn-icon p-1 edit-section-editBtn"
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

