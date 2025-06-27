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
                    <div class="card-header">
                        {{ 'Activate to the Left Out Student' }}
                        <a href="{{ route('admin.left-out-std.index') }}" class="btn btn-warning btn-sm"
                            style="float: right;">Back</a>

                    </div>
                    <div class="card-body">
                        <table id="example" class="table table-striped table-bordered">

                            <tbody>
                                <form action="{{ route('admin.left-out-std.index') }}" method="get">
                                    <tr>
                                        <td>Select Session</td>
                                        <td>

                                            <select type="hidden" name="session" id="session_id" value=''
                                                class="form-control mx-1" required>
                                                <option value="">Select Session</option>
                                                @if (count($sessions) > 0)
                                                    @foreach ($sessions as $key => $session)
                                                        <option value="{{ $key }}"
                                                            {{ request()->get('session') == $key ? 'selected' : '' }}>
                                                            {{ $session }}</option>
                                                    @endforeach
                                                @else
                                                    <option value="">No Session Found</option>
                                                @endif
                                            </select>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Select Class</td>
                                        <td>
                                            <select name="class" id="class_id" class="form-control mx-1" required>
                                                <option value="">Select Class</option>
                                                @if (count($classes) > 0)
                                                    @foreach ($classes as $key => $class)
                                                        <option value="{{ $key }}"
                                                            {{ request()->get('class') == $key ? 'selected' : '' }}>
                                                            {{ $class }}</option>
                                                    @endforeach
                                                @else
                                                    <option value="">No Class Found</option>
                                                @endif
                                            </select>
                                            <span class="invalid-feedback form-invalid fw-bold first-class-error"
                                                role="alert"></span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Select Section</td>
                                        <td>
                                            <input type="hidden" id="initialSectionId" name="initialSectionId"
                                                value="{{ old('section', request()->get('section') !== null ? request()->get('section') : '') }}">
                                            <select name="section" id="section_id" class="form-control mx-1" required>
                                                <option value="">Select Section</option>
                                            </select>
                                            <span class="invalid-feedback form-invalid fw-bold first-section-error"
                                                role="alert"></span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td colspan="2">
                                            <button type="submit" class="btn btn-sm btn-primary" id="left-out-std">Show
                                                Student</button><span><img src="{{ config('myconfig.myloader') }}"
                                                    alt="Loading..." class="loader" id="loader"
                                                    style="width: 10%; display: none;"></span>

                                        </td>
                                    </tr>
                                </form>
                            </tbody>

                        </table>

                        @if (request()->get('session') || request()->get('class') || request()->get('section'))

                            <div class="table" id="table-div">
                                <table id="example" class="table table-striped table-bordered">
                                    <thead>
                                        <tr>
                                            <th>S NO.</th>
                                            <th>SRNO</th>
                                            <th>Name</th>
                                            <th>Father's Name</th>
                                            <th>Class</th>
                                            <th>Section</th>
                                            <th class="text-center">Action</th>
                                        </tr>
                                    </thead>

                                    @if (count($student) > 0)
                                        @foreach ($student as $key => $value)
                                            <tr data-entry-id="{{ $value->id }}">
                                                <td>{{ $student->firstItem() + $key ?? '' }}</td>
                                                <td>{{ $value->srno ?? '' }}</td>
                                                <td>{{ $value->student_name ?? '' }}</td>
                                                <td>{{ $value->f_name ?? '' }}</td>
                                                <td>{{ $value->class_name ?? '' }}</td>
                                                <td>{{ $value->section_name ?? '' }}</td>


                                                <td class="text-center">

                                                    <form action="{{ route('admin.left-out-std.edit', $value->srno) }}"
                                                        method="POST" style="display:inline;">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-icon p-1"
                                                            data-bs-toggle="tooltip" data-bs-offset="0,4"
                                                            data-bs-placement="top" data-bs-html="true"
                                                            title="Active to the Student">
                                                            <i class="mdi mdi-account-check"></i>
                                                        </button>
                                                    </form>

                                                </td>
                                            </tr>
                                        @endforeach
                                    @else
                                        <tr>
                                            <td colspan="7">No Students Available</td>
                                        </tr>
                                    @endif
                                </table>
                                @if (request()->get('session') || request()->get('class') || request()->get('section'))
                                    {{ $student->appends(['session' => request()->get('session'), 'class' => request()->get('class'), 'section' => request()->get('section')])->links() }}
                                @else
                                    {{ $student->links() }}
                                @endif

                            </div>
                        @else
                            <div class="table">
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
            let initialClassId = $('#class_id').val();
            let initialSectionId = $('#initialSectionId').val();
            getClassSection(initialClassId, initialSectionId);
            $('#session_id, #class_id, #section_id').change(function() {
                $('#table-div').hide();
            });

        });
    </script>
@endsection
