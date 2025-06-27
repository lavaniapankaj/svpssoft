@extends('admin.index')
@section('sub-content')
    <div class="container">

        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        {{ 'SMS Panel' }}
                        <a href="{{ route('admin.sms-panel.index') }}" class="btn btn-warning btn-sm"
                            style="float: right;">Back</a>

                    </div>
                    <div class="card-body">
                        <form id="date-form">
                            @csrf
                            <div class="row">
                                <div class="form-group col-md-6">
                                    <label for="class_id" class="mt-2">Class <span class="text-danger">*</span></label>

                                    <select name="class" id="class_id"
                                        class="form-control @error('class') is-invalid @enderror" required>
                                        <option value="">Select Class</option>
                                        @if (count($classes) > 0)
                                            @foreach ($classes as $key => $class)
                                                <option value="{{ $key }}"
                                                    {{ request()->get('class_id') == $key ? 'selected' : '' }}>
                                                    {{ $class }}</option>
                                            @endforeach
                                        @else
                                            <option value="">No Class Found</option>
                                        @endif
                                    </select>
                                    @error('class')
                                        <span class="invalid-feedback form-invalid fw-bold"
                                            role="alert">{{ $message }}</span>
                                    @enderror

                                </div>


                                <div class="form-group col-md-6">
                                    <label for="section_id" class="mt-2">Section <span
                                            class="text-danger">*</span></label>
                                    <input type="hidden" id="initialSectionId"
                                        value="{{ request()->get('section_id') ?? '' }}">
                                    <select name="section" id="section_id"
                                        class="form-control @error('section') is-invalid @enderror" required>
                                        <option value="">Select Section</option>

                                    </select>
                                    @error('section')
                                        <span class="invalid-feedback form-invalid fw-bold"
                                            role="alert">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="row">
                                <div class="form-group col-md-12">
                                    <label for="floatingTextarea2">Message</label>
                                    <textarea class="form-control @error('message') is-invalid @enderror" name="message" placeholder="Enter Message"
                                        id="floatingTextarea2" style="height: 100px" required></textarea>
                                </div>
                                @error('message')
                                    <span class="invalid-feedback form-invalid fw-bold"
                                        role="alert">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="mt-3">
                                <button type="button" id="show-details" class="btn btn-primary">
                                    Show Details</button><span><img src="{{ config('myconfig.myloader') }}" alt="Loading..."
                                        class="loader" id="loader" style="display:none; width:10%;"></span>
                            </div>

                        </form>

                        <div id="std-container" class="mt-4">
                            <form action="" id="std-form">
                                <table class="table table-responsible">
                                    <input type="hidden" name="current_session" value='' id="current_session">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Name</th>
                                                <th>Mobile Number</th>
                                                <th>Select</th>
                                            </tr>
                                        </thead>
                                        <tbody>

                                        </tbody>
                                    </table>
                                    <div class="mt-3">
                                        <button type="submit" id="send-sms" class="btn btn-primary">Send Message</button>
                                    </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


@endsection
@section('admin-scripts')
    <script>
        $(document).ready(function() {
           function fetchSections(classIds) {
                const sectionId = $('#section_id');
                loader.show();
                let allSectionIds = [];
                $.ajax({
                    url: siteUrl + '/sections',
                    type: 'GET',
                    dataType: 'JSON',
                    data: {
                        class_id: classIds
                    },
                    success: function(data) {
                        sectionId.empty();
                        $.each(data.data, function(id, name) {
                             allSectionIds.push(id);

                        });
                        sectionId.append('<option value="' + allSectionIds.join(',') +

                                '" selected>All Section</option>');

                        $.each(data.data, function(id, name) {
                                sectionId.append('<option value="' + id + '">' + name + '</option>');
                        });
                    },
                    complete: function() {
                        loader.hide();
                    },
                    error: function(data) {
                            console.error('Error fetching sections:', data.responseJSON ? data.responseJSON.message : 'Unknown error');
                    }

                });

            }
            $('#class_id').change(function () {
                fetchSections($(this).val());
            });
            $('#std-form').hide();
            $('#show-details').on('click', function() {
                const classId = $('#class_id').val();
                const sectionId = $('#section_id').val();
                const sessionId = $('#current_session').val();
                loader.show();
                if (classId && sectionId && sessionId) {
                    $('#std-form').show();
                    $.ajax({
                        url: '{{ route('stdNameFather.get') }}',
                        type: 'GET',
                        data: {
                            class_id: classId,
                            section_id: sectionId,
                            session_id: sessionId,
                        },
                        success: function(students) {
                            let stdHtml = '';
                            $.each(students, function(index, std) {

                                stdHtml += `<tr>
                            <td>
                                ${std.student_name}
                            </td>
                            <td>${std.f_mobile ?? ''}</td>
                            <td>
                                <input type="checkbox" name="status" value="1" class="status-checkbox" data-index="${index}" checked>
                            </td>
                            </tr>`;
                            });

                            $('#std-container table tbody').html(stdHtml);
                        },
                        complete: function() {

                            loader.hide();
                        },
                        error: function(xhr) {
                            console.error(xhr.responseText);
                        }
                    });

                }
            });
            $('#session_id, #class_id').change(function() {
                $('#std-form').hide();
            });
            $('#sms-send').click(function() {
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
