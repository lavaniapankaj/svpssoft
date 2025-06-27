@extends('admin.index')
@section('sub-content')
    <div class="container">
        @if (Session::has('success'))
            @section('scripts')
                <script>
                    swal("Successful", "{{ Session::get('success') }}", "success");
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
                    <div class="card-header">{{ __('Add SMS Group') }}
                        <a href="{{ route('admin.group-sms-panel.index') }}" class="btn btn-warning"
                            style="float: right;">Back</a>

                    </div>

                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-12">
                                <form action="{{ route('admin.add-sms-group.store') }}" method="POST">
                                    @csrf
                                    <div class="form-group">
                                        <label for="group_name">{{ __('	Enter Group Name') }}</label>
                                        <input type="text" placeholder="Enter Group Name"
                                            value="{{ old('group_name') }}" id="group_name" name="group_name"
                                            class="form-control @error('group_name') is-invalid @enderror" required>
                                        @error('group_name')
                                            <span class="invalid-feedback form-invalid fw-bold"
                                                role="alert">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="mt-3">
                                        <button type="submit"
                                            class="btn btn-primary float-right">{{ __('Add Group') }}</button>
                                    </div>
                                </form>
                            </div>

                        </div>
                        <div class="row mt-4">
                            <div class="table">
                                <table id="example" class="table table-striped table-bordered">
                                    <thead>
                                        <tr>
                                            <th>S No.</th>
                                            <th>Group Name</th>
                                            <th class="text-center">Action</th>
                                        </tr>
                                    </thead>

                                    @if (count($data) > 0)
                                        @foreach ($data as $key => $value)
                                            <tr data-entry-id="{{ $value->id }}">
                                                <td>{{ $key + 1 ?? '' }}</td>
                                                <td>{{ $value->group_name ?? '' }}</td>
                                                <td class="text-center">

                                                    <a href="{{ route('admin.add-sms-group.edit', $value->id) }}"
                                                        class="btn btn-sm btn-icon p-1">
                                                        <i class="mdi mdi-pencil" data-bs-toggle="tooltip"
                                                            data-bs-offset="0,4" data-bs-placement="top" title="Edit"></i>
                                                    </a>
                                                    <form
                                                        action="{{ route('admin.add-sms-group.softDelete', $value->id) }}"
                                                        method="POST" style="display:inline;">
                                                        @csrf
                                                        <button type="submit"
                                                            class="btn btn-sm btn-icon p-1 delete-form-btn"
                                                            data-bs-toggle="tooltip" data-bs-offset="0,4"
                                                            data-bs-placement="top" data-bs-html="true" title="Delete">
                                                            <i class="mdi mdi-delete"></i>
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                        @endforeach
                                    @else
                                        <tr>
                                            <td colspan="3">No SMS Group Found</td>
                                        </tr>
                                    @endif
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
