@extends('admin.index')

@section('sub-content')
    <div class="container-fluid">
        @if (Session::has('success'))
            @push('swal-scripts')
                <script>
                    swal("Successful", "{{ Session::get('success') }}", "success");
                </script>
            @endpush
        @endif

        @if (Session::has('error'))
            @push('swal-scripts')
                <script>
                    swal("Error", "{{ Session::get('error') }}", "error");
                </script>
            @endpush
        @endif

        <div class="row">
            <div class="col-md-12">
                <div class="card border-0 bg-white">
                    <div class="card-header bg-white">
                        <div class="row align-items-center">
                            <div class="col-md-4">
                                <h5 class="mb-0">{{ __('Section Master') }}</h5>
                            </div>
                            <div class="col-md-8">
                                <div class="d-flex align-items-center justify-content-end gap-2">
                                    <!-- Filter Form -->
                                    <form action="{{ route('admin.section-master.index') }}" method="get"
                                        class="d-flex align-items-center gap-2">
                                        <select name="class_id" id="filter_class_id" class="form-select"
                                            style="width: 180px;">
                                            <option value="">All Classes</option>
                                            @if (count($classes) > 0)
                                                @foreach ($classes as $key => $class)
                                                    <option value="{{ $key }}"
                                                        {{ request()->get('class_id') == $key ? 'selected' : '' }}>
                                                        {{ $class }}
                                                    </option>
                                                @endforeach
                                            @endif
                                        </select>
                                        <button type="submit" class="btn btn-dark">
                                            <i class="mdi mdi-magnify me-1"></i>Filter
                                        </button>
                                        @if (request()->get('class_id'))
                                            <a href="{{ route('admin.section-master.index') }}" class="btn btn-dark" title="Reset Filter">
                                                Reset
                                            </a>
                                        @endif
                                    </form>

                                    <!-- Add Button -->
                                    <button type="button" class="btn btn-success text-white" id="toggleFormBtn">
                                        <i class="mdi mdi-plus-circle-outline me-2"></i>Add New Section
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Inline Create Form (Hidden by default) -->
                    <div class="card-body border-bottom bg-light" id="createFormSection" style="display: none;">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <h6 class="mb-1 fw-bold">Add New Section</h6>
                                <small class="text-muted">Fill in the details below to create a new section</small>
                            </div>
                        </div>

                        <form action="{{ route('admin.section-master.store') }}" method="POST" id="inline-create-form">
                            @csrf
                            <div class="row g-3">
                                <!-- Class Selection -->
                                <div class="col-md-5">
                                    <label for="class_id" class="form-label fw-semibold">
                                        Class <span class="text-danger">*</span>
                                    </label>
                                    <select name="class_id" id="class_id"
                                        class="form-select @error('class_id') is-invalid @enderror" required>
                                        <option value="">Select Class</option>
                                        @if (count($classes) > 0)
                                            @foreach ($classes as $key => $class)
                                                <option value="{{ $key }}"
                                                    {{ old('class_id') == $key ? 'selected' : '' }}>
                                                    {{ $class }}
                                                </option>
                                            @endforeach
                                        @else
                                            <option value="" disabled>No Classes Found</option>
                                        @endif
                                    </select>
                                    @error('class_id')
                                        <div class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>

                                <!-- Section Name Input -->
                                <div class="col-md-4">
                                    <label for="section" class="form-label fw-semibold">
                                        Section Name <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" name="section" id="section" class="form-control @error('section') is-invalid @enderror" placeholder="e.g., A, B, C" value="{{ old('section') }}" required autocomplete="off">
                                    @error('section')
                                        <div class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>

                                <!-- Action Buttons -->
                                <div class="col-md-3">
                                    <label class="form-label d-block opacity-0">Action</label>
                                    <div class="d-flex gap-2">
                                        <button class="btn btn-success" type="submit">
                                            Save Section
                                        </button>
                                        <button class="btn btn-outline-secondary" type="button" id="cancelFormBtn">
                                            Cancel
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>

                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="example" class="table table-striped table-bordered">
                                <thead>
                                    <tr>
                                        <th>S No.</th>
                                        <th>Section</th>
                                        <th>Class</th>
                                        <th class="text-center">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @if (count($data) > 0)
                                        @foreach ($data as $key => $value)
                                            <tr data-entry-id="{{ $value->id }}">
                                                <td>{{ $data->firstItem() + $key ?? '' }}</td>
                                                <td>{{ $value->section ?? '' }}</td>
                                                <td>
                                                    {{ $value->class->class ?? '' }}
                                                </td>
                                                <td class="text-center">
                                                    <div class="d-flex gap-2 justify-content-center">
                                                        <a href="{{ route('admin.section-master.edit', $value->id) }}" class="btn btn-sm btn-icon editbtnGlobal">
                                                            <i class="mdi mdi-pencil" data-bs-toggle="tooltip" data-bs-offset="0,4" data-bs-placement="top" title="Edit"></i>
                                                        </a>
                                                        {{-- Uncomment if delete functionality is needed
                                                        <form action="{{ route('admin.section-master.softDelete', $value->id) }}"
                                                              method="POST" style="display:inline;">
                                                            @csrf
                                                            <button type="submit" class="btn btn-sm btn-icon delete-form-btn deletebtnGlobal"
                                                                data-bs-toggle="tooltip" data-bs-offset="0,4"
                                                                data-bs-placement="top" data-bs-html="true" title="Delete">
                                                                <i class="mdi mdi-delete"></i>
                                                            </button>
                                                        </form>
                                                        --}}
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    @else
                                        <tr>
                                            <td colspan="4" class="text-center text-muted py-4">
                                                <i class="mdi mdi-information-outline me-2"></i>No Sections Found
                                            </td>
                                        </tr>
                                    @endif
                                </tbody>
                            </table>

                            <div class="mt-3">
                                @if (request()->get('class_id'))
                                    {{ $data->appends(['class_id' => request()->get('class_id')])->links() }}
                                @else
                                    {{ $data->links() }}
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('admin-scripts')
    <script>
        // Toggle create form visibility
        document.getElementById('toggleFormBtn').addEventListener('click', function() {
            const formSection = document.getElementById('createFormSection');
            const btn = this;

            if (formSection.style.display === 'none') {
                formSection.style.display = 'block';
                btn.innerHTML = '<i class="mdi mdi-minus-circle-outline me-2"></i>Cancel';
                btn.classList.remove('btn-success');
                btn.classList.add('btn-secondary');

                // Focus on first input
                setTimeout(() => {
                    document.getElementById('class_id').focus();
                }, 100);
            } else {
                formSection.style.display = 'none';
                btn.innerHTML = '<i class="mdi mdi-plus-circle-outline me-2"></i>Add New Section';
                btn.classList.remove('btn-secondary');
                btn.classList.add('btn-success');

                // Reset form and clear errors
                const form = document.getElementById('inline-create-form');
                form.reset();

                // Clear values manually
                document.getElementById('class_id').value = '';
                document.getElementById('section').value = '';

                // Remove validation error classes
                document.querySelectorAll('.is-invalid').forEach(el => {
                    el.classList.remove('is-invalid');
                });

                // Remove error messages
                document.querySelectorAll('.invalid-feedback').forEach(el => {
                    el.style.display = 'none';
                });
            }
        });

        // Cancel button handler
        document.getElementById('cancelFormBtn').addEventListener('click', function() {
            document.getElementById('toggleFormBtn').click();
        });

        // Show form if there are validation errors
        @if ($errors->any())
            document.getElementById('createFormSection').style.display = 'block';
            const btn = document.getElementById('toggleFormBtn');
            btn.innerHTML = '<i class="mdi mdi-minus-circle-outline me-2"></i>Cancel';
            btn.classList.remove('btn-success');
            btn.classList.add('btn-secondary');

            // Focus on first error field
            setTimeout(() => {
                const firstError = document.querySelector('.is-invalid');
                if (firstError) {
                    firstError.focus();
                }
            }, 100);
        @endif

        // Clear individual field error on input/change
        document.querySelectorAll('.form-control, .form-select').forEach(input => {
            input.addEventListener('input', function() {
                clearFieldError(this);
            });
            input.addEventListener('change', function() {
                clearFieldError(this);
            });
        });

        function clearFieldError(element) {
            if (element.classList.contains('is-invalid')) {
                element.classList.remove('is-invalid');
                const feedback = element.parentElement.querySelector('.invalid-feedback');
                if (feedback) {
                    feedback.style.display = 'none';
                }
            }
        }
    </script>
@endsection
