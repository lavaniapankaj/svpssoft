@extends('marks.index')



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

            <div class="col-md-12">

                <div class="card">

                    <div class="card-header">

                        {{ 'Select Exams' }}

                        <a href="{{ route('marks.marks-report.marksheet.six.eighth') }}" class="btn btn-warning btn-sm"
                            style="float: right;">Back</a>

                    </div>

                    <div class="card-body">

                        <form id="submitForm" action="{{ route('marks.marks-report.select.exam.six.eighth.store') }}"
                            method="POST">

                            @csrf

                            <input type="hidden" id="class" name="class"
                                value="{{ old('class', isset($class) ? $class : '') }}">

                            <input type="hidden" id="section" name="section"
                                value="{{ old('section', isset($section) ? $section : '') }}">

                            <input type="hidden" id="students" name="students"
                                value="{{ old('students', isset($students) ? $students : '') }}">

                            <input type="hidden" id="session-message" name="sessionMessage"
                                value="{{ old('sessionMessage', isset($sessionMessage) ? $sessionMessage : '') }}">

                            <input type="hidden" id="date-message" name="dateMessage"
                                value="{{ old('dateMessage', isset($dateMessage) ? $dateMessage : '') }}">

                            <input type="hidden" id="exams" name="exams" value="">

                            <input type="hidden" id="withExam" name="withExam" value="">

                            <input type="hidden" id="withoutExam" name="withoutExam" value="">

                            <table class="table">

                                <thead>

                                    <tr>

                                        <th>Examinition</th>

                                        <th>Select Exam (without W+O)</th>

                                        <th>Select Exam (with W+O)</th>

                                    </tr>

                                </thead>

                                <tbody>

                                    @if ($data)
                                        @foreach ($data as $key => $value)
                                            <tr data-entry-id="{{ $key }}" id="exam-row">

                                                <td>{{ $value}}</td>

                                                <td><input type="checkbox" id="without" name="without"
                                                        value={{ $key }}></td>

                                                <td><input type="checkbox" id="with" name="with"
                                                        value={{ $key }}></td>

                                            </tr>
                                        @endforeach
                                    @else
                                        <tr>

                                            <td colspan="5">No Exam Found</td>

                                        </tr>
                                    @endif



                                </tbody>

                            </table>



                            <div class="form-group">

                                <button type="submit" class="btn btn-primary" id="show">Show MarkSheet</button>

                            </div>

                        </form>

                    </div>

                </div>

            </div>

        </div>

    </div>

@endsection

@section('marks-scripts')
    <script>
        $(document).ready(function() {
            $('#submitForm').on('submit', function(e) {
                e.preventDefault();

                // Validation checks
                let isValid = true;
                let errorMessages = [];

                // 1. Check if class is selected
                if (!$('#class').val()) {
                    isValid = false;
                    errorMessages.push('Please select a class');
                }

                // 2. Check if section is selected
                if (!$('#section').val()) {
                    isValid = false;
                    errorMessages.push('Please select a section');
                }

                // 3. Check if students are selected
                if (!$('#students').val()) {
                    isValid = false;
                    errorMessages.push('Please select at least one student');
                }

                // 4. Check if at least one exam is selected
                let hasExamSelection = false;
                $('tr[data-entry-id]').each(function() {
                    let withCheckbox = $(this).find('input[name="with"]');
                    let withoutCheckbox = $(this).find('input[name="without"]');

                    if (withCheckbox.is(':checked') || withoutCheckbox.is(':checked')) {
                        hasExamSelection = true;
                        return false; // Break the loop
                    }
                });

                if (!hasExamSelection) {
                    isValid = false;
                    errorMessages.push('Please select at least one exam');
                }

                // 5. Check for invalid combinations (both checkboxes checked)
                let hasInvalidSelection = false;
                $('tr[data-entry-id]').each(function() {
                    let withCheckbox = $(this).find('input[name="with"]');
                    let withoutCheckbox = $(this).find('input[name="without"]');

                    if (withCheckbox.is(':checked') && withoutCheckbox.is(':checked')) {
                        hasInvalidSelection = true;
                        return false; // Break the loop
                    }
                });

                if (hasInvalidSelection) {
                    isValid = false;
                    errorMessages.push(
                        'For each exam, you can select either "With" or "Without" checkbox, but not both'
                    );
                }

                // If validation fails, show errors and stop submission
                if (!isValid) {
                    // You can customize how to display errors
                    let errorHTML = '<ul>';
                    errorMessages.forEach(function(msg) {
                        errorHTML += '<li>' + msg + '</li>';
                    });
                    errorHTML += '</ul>';

                    // Create or update error display
                    let errorDiv = $('#validation-errors');
                    if (errorDiv.length === 0) {
                        errorDiv = $('<div id="validation-errors" class="alert alert-danger"></div>');
                        $(this).prepend(errorDiv);
                    }
                    errorDiv.html(errorHTML);
                    return false;
                }

                // If validation passes, process the form data
                let withIds = [];
                let withoutIds = [];

                $('input[name="with"]:checked').each(function() {
                    withIds.push($(this).val());
                });

                $('input[name="without"]:checked').each(function() {
                    withoutIds.push($(this).val());
                });

                // Join the selected values into comma-separated strings
                let withId = withIds.join(',');
                let withoutId = withoutIds.join(',');
                let examId = (withId && withoutId) ? withId + "," + withoutId : (withId ? withId :
                    withoutId);

                // Set the hidden field values
                $('#exams').val(examId);
                $('#withExam').val(withId);
                $('#withoutExam').val(withoutId);

                // Clear any previous error messages
                $('#validation-errors').remove();

                // Submit the form
                this.submit();
            });

            // Optional: Add visual feedback when checkboxes are clicked
            $('input[type="checkbox"]').on('change', function() {
                let row = $(this).closest('tr');
                let otherCheckbox = row.find('input[type="checkbox"]').not(this);

                if ($(this).is(':checked')) {
                    row.addClass('selected-row');
                    // Optionally disable the other checkbox
                    otherCheckbox.prop('disabled', true);
                } else {
                    row.removeClass('selected-row');
                    // Re-enable the other checkbox
                    otherCheckbox.prop('disabled', false);
                }
            });
        });
    </script>
@endsection
