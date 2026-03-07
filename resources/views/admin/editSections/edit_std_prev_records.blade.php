@extends('admin.index')

@section('sub-content')
    <div class="container-fluid">

        @if (Session::has('success'))
            @push('swal-scripts')
                <script>swal("Successful", "{{ Session::get('success') }}", "success");</script>
            @endpush
        @endif
        @if (Session::has('error'))
            @push('swal-scripts')
                <script>swal("Error", "{{ Session::get('error') }}", "error");</script>
            @endpush
        @endif

        <div class="row">
            <div class="col-md-12">
                <div class="card border-0 bg-white">
                    <div class="card-header flex-wrap bg-white d-flex align-items-center justify-content-between">
                        <h5 class="mb-0 mt-0">{{ __('Edit Current/Previous Records') }}</h5>
                        <a href="{{ route('admin.editSection.index') }}" class="btn bg-light btn-sm">
                            <span class="mdi mdi-chevron-left me-2"></span>Back
                        </a>
                    </div>

                    <div class="card-body">

                        {{-- ─── Search Form ─── --}}
                        <form id="search-form" novalidate>
                            <div class="row">
                                <div class="form-group col-md-6">
                                    <label for="srno" class="mt-2">
                                        Enter SRNO <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" id="srno" name="srno" class="form-control" placeholder="Enter SRNO of Student">
                                </div>
                                <div class="form-group col-md-3 d-flex align-items-end mt-2">
                                    <div class="d-flex align-items-center gap-2">
                                        <button type="button" id="show-details" class="btn btn-primary">
                                            Show Details
                                        </button>
                                        <img src="{{ config('myconfig.myloader') }}" alt="Loading…" id="loader" class="loader" style="display:none; width:40px;">
                                    </div>
                                </div>
                                <div class="form-group col-md-3 d-flex align-items-end mt-2">
                                    <a href="{{ route('admin.student-master.search') }}" class="btn btn-sm btn-info" target="_blank" rel="noopener noreferrer">
                                        To Know SRNO Click Here
                                    </a>
                                </div>
                            </div>
                        </form>

                        {{-- ─── Student Edit Form ─── --}}
                        <div class="mt-4" id="std-form" style="display:none;">
                            <form action="{{ route('admin.editSection.editStdByPreSrno.store') }}"
                                method="POST" id="student-form" novalidate>
                                @csrf
                                <input type="hidden" name="std_srno" id="srno-inner">

                                <div class="row">
                                    <div class="form-group col-md-6">
                                        <label for="std_name" class="mt-2">
                                            Name <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" id="std_name" name="std_name" class="form-control @error('std_name') is-invalid @enderror" value="{{ old('std_name') }}" placeholder="Enter Student Name" required>
                                        @error('std_name')
                                            <span class="invalid-feedback fw-bold" role="alert">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label for="f_name" class="mt-2">
                                            Father's Name <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" id="f_name" name="f_name" class="form-control @error('f_name') is-invalid @enderror" value="{{ old('f_name') }}" placeholder="Enter Father's Name" required>
                                        @error('f_name')
                                            <span class="invalid-feedback fw-bold" role="alert">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="form-group col-md-6">
                                        <label for="m_name" class="mt-2">
                                            Mother's Name <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" id="m_name" name="m_name" class="form-control @error('m_name') is-invalid @enderror" value="{{ old('m_name') }}" placeholder="Enter Mother's Name" required>
                                        @error('m_name')
                                            <span class="invalid-feedback fw-bold" role="alert">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="form-group col-md-3">
                                        <label for="dob" class="mt-2">
                                            DOB <span class="text-danger">*</span>
                                        </label>
                                        <input type="date" id="dob" name="dob" class="form-control @error('dob') is-invalid @enderror" value="{{ old('dob') }}" required>
                                        @error('dob')
                                            <span class="invalid-feedback fw-bold" role="alert">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="form-group col-md-3">
                                        <label for="category" class="mt-2">
                                            Category <span class="text-danger">*</span>
                                        </label>
                                        <select name="category" id="category" class="form-control @error('category') is-invalid @enderror" required>
                                            <option value="">Select Category</option>
                                            <option value="1" {{ old('category') == 1 ? 'selected' : '' }}>General</option>
                                            <option value="2" {{ old('category') == 2 ? 'selected' : '' }}>OBC</option>
                                            <option value="3" {{ old('category') == 3 ? 'selected' : '' }}>SC</option>
                                            <option value="4" {{ old('category') == 4 ? 'selected' : '' }}>ST</option>
                                            <option value="5" {{ old('category') == 5 ? 'selected' : '' }}>BC</option>
                                        </select>
                                        @error('category')
                                            <span class="invalid-feedback fw-bold" role="alert">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="form-group col-md-12">
                                        <label for="address" class="mt-2">Address</label>
                                        <textarea name="address" id="address" rows="3" class="form-control @error('address') is-invalid @enderror" placeholder="Enter Address">{{ old('address') }}</textarea>
                                        @error('address')
                                            <span class="invalid-feedback fw-bold" role="alert">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>

                                <div class="mt-3">
                                    <button type="submit" class="btn btn-primary" id="update-std">Update</button>
                                </div>
                            </form>
                        </div>

                    </div>{{-- /.card-body --}}
                </div>{{-- /.card --}}
            </div>
        </div>
    </div>
@endsection

@section('admin-scripts')
<script>
$(function () {

    var categories = {
        1: 'General',
        2: 'OBC',
        3: 'SC',
        4: 'ST',
        5: 'BC'
    };

    /* ── Helpers ──────────────────────────────────────────────── */
    function clearSearchErrors() {
        $('#srno').removeClass('is-invalid').siblings('.server-error').remove();
    }

    function showSearchError(message) {
        $('#srno').addClass('is-invalid');
        if (!$('#srno').siblings('.server-error').length) {
            $('#srno').after(
                '<span class="invalid-feedback server-error fw-bold" role="alert">' + message + '</span>'
            );
        }
    }

    function validateSearch() {
        var srno = $('#srno').val().trim();
        if (!srno) {
            showSearchError('Please enter a SRNO.');
            return false;
        }
        clearSearchErrors();
        return true;
    }

    function populateForm(std) {
        $('#srno-inner').val(std.srno);
        $('#std_name').val(std.student_name || '');
        $('#f_name').val(std.f_name        || '');
        $('#m_name').val(std.m_name        || '');
        $('#dob').val(std.dob              || '');
        $('#address').val(std.address      || '');

        // Build category options with correct selected state
        var optionsHtml = '<option value="">Select Category</option>';
        $.each(categories, function (val, label) {
            optionsHtml += '<option value="' + val + '"' +
                (std.category == val ? ' selected' : '') + '>' + label + '</option>';
        });
        $('#category').html(optionsHtml);
    }

    /* ── Event: Show Details ──────────────────────────────────── */
    $('#show-details').on('click', function () {
        clearSearchErrors();

        if (!validateSearch()) return;

        var srno = $('#srno').val().trim();

        $('#std-form').hide();
        $('#loader').show();

        $.ajax({
            url     : '{{ route('admin.getStdWithSrno') }}',
            type    : 'GET',
            dataType: 'json',
            data    : { srno: srno },

            success: function (response) {
                var data = (response && response.data && response.data.length) ? response.data[0] : null;

                if (data) {
                    populateForm(data);
                    $('#std-form').show();

                    // Scroll to form
                    $('html, body').animate({ scrollTop: $('#std-form').offset().top - 20 }, 400);
                } else {
                    showSearchError('No student found for the entered SRNO.');
                    $('#std-form').hide();
                }
            },

            error: function (xhr) {
                var json = xhr.responseJSON || {};

                if (xhr.status === 400 && json.message && typeof json.message === 'object') {
                    // Field-level validation errors from server
                    var firstMessage = null;
                    $.each(json.message, function (key, messages) {
                        if (!firstMessage && messages.length) {
                            firstMessage = messages[0];
                        }
                    });
                    showSearchError(firstMessage || 'Invalid request.');
                } else {
                    showSearchError('Failed to retrieve student. Please try again.');
                    console.error('getStdWithSrno error:', xhr);
                }

                $('#std-form').hide();
            },

            complete: function () {
                $('#loader').hide();
            }
        });
    });

    /* ── Event: SRNO input change → clear errors + hide form ──── */
    $('#srno').on('input', function () {
        clearSearchErrors();
        $('#std-form').hide();
    });

    /* ── Restore form on validation error (POST redirect back) ── */
    @if ($errors->any())
        $('#std-form').show();
    @endif

});
</script>
@endsection