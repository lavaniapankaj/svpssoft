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
                    <div class="card-header">{{ __('Send SMS Master') }}
                        <a href="{{ route('admin.group-sms-panel.index') }}" class="btn btn-warning"
                            style="float: right;">Back</a>
                    </div>

                    <div class="card-body">
                        <div class="row">
                            <!-- Form for selecting group -->
                            <form action="{{ route('admin.send-group-sms.index') }}" method="get" id="group-select-form">
                               <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label for="group_name">{{ __('Select Group') }}</label>
                                            <select name="group_id" id="group_id"
                                                class="form-control @error('group_id') is-invalid @enderror"
                                                onchange="document.getElementById('group-select-form').submit()" required>
                                                <option value="">Select Group</option>
                                                @if (count($groups) > 0)
                                                    @foreach ($groups as $key => $group)
                                                        <option value="{{ $group->id }}"
                                                            {{ request('group_id') == $group->id ? 'selected' : '' }}>
                                                            {{ $group->group_name }}</option>
                                                    @endforeach
                                                @else
                                                    <option value="">No Group Found</option>
                                                @endif
                                            </select>
                                            @error('group_id')
                                                <span class="invalid-feedback form-invalid fw-bold"
                                                    role="alert">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>

                        <!-- Form for sending SMS -->
                        <form action="#">
                            <!-- Hidden Input to Pass group_id -->
                            <input type="hidden" name="group_id" value="{{ request('group_id') }}" />
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label for="message">{{ __('Message') }}</label>
                                        <textarea id="message" name="message" class="form-control @error('message') is-invalid @enderror" rows="3"
                                            required>{{ old('message') }}</textarea>
                                        @error('message')
                                            <span class="invalid-feedback form-invalid fw-bold"
                                                role="alert">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                            <div class="mt-3">
                                <button type="button" class="btn btn-primary sms-send">{{ __('Send SMS') }}</button>
                            </div>


                            <!-- Table displaying group data -->
                            @if (!empty(request('group_id')))
                                <div class="row mt-4">
                                    <div class="table">
                                        <table id="example" class="table table-striped table-bordered">
                                            <thead>
                                                <tr>
                                                    <th>S No.</th>
                                                    <th>Name</th>
                                                    <th>Mobile Number</th>
                                                    <th>Select</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @if ($data->count() > 0)
                                                    @foreach ($data as $key => $value)
                                                        <tr data-entry-id="{{ $value->id }}">
                                                            <td>{{ $key + 1 }}</td>
                                                            <td>{{ $value->name ?? '' }}</td>
                                                            <td>{{ $value->mobile ?? '' }}</td>
                                                            <td><input type="checkbox" name="check" id="check"
                                                                    checked></td>
                                                        </tr>
                                                    @endforeach
                                                @else
                                                    <tr>
                                                        <td colspan="4">No Data Found</td>
                                                    </tr>
                                                @endif
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <button type="button" class="btn btn-primary sms-send">{{ __('Send SMS') }}</button>
                                </div>
                            @endif
                        </form>
                    </div>

                </div>
            </div>
        </div>
    </div>
@endsection
@section('admin-scripts')
<script>
    // Example of how to initialize DataTable
    $(document).ready(function () {
        $('.sms-send').click(function() {
                // Show a confirmation message
                Swal.fire({
                    title: 'Send SMS',
                    text: 'Are you sure you want to send SMS to the selected students?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, send SMS',
                    cancelButtonText: 'No, cancel',
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Call the API to send the SMS
                        // Replace the following URL with the actual API endpoint
                        $.ajax({
                            url: '/api/send-sms',
                            type: 'POST',
                            data: {
                                // Collect the necessary data for sending the SMS, such as student details
                            },
                            success: function(response) {
                                // Show a success message
                                Swal.fire({
                                    title: 'SMS Sent',
                                    text: 'SMS has been sent to the selected students.',
                                    icon: 'success',
                                });
                            },
                            error: function(xhr, status, error) {
                                // Show an error message
                                Swal.fire({
                                    title: 'Error',
                                    text: 'Failed to send SMS. Please try again later.',
                                    icon: 'error',
                                });
                            }
                        });
                    }
                });
            });
    });
</script>
@endsection
