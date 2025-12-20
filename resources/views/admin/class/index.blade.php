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
                    <div class="card-header bg-white d-flex align-items-center justify-content-between">
                        <h5 class="mb-0 mt-0">{{ __('Class Master') }}</h5>
                        <button type="button" class="btn btn-success text-white" id="toggleFormBtn">
                            <span class="mdi mdi-plus-circle-outline me-2"></span>Add New Class
                        </button>
                    </div>
                    <!-- Inline Create Form (Hidden by default) -->
                    <div class="card-body border-bottom bg-light" id="createFormSection" style="display: none;">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <h6 class="mb-1 fw-bold text-dark">
                                    <i class="mdi mdi-plus-circle text-success me-2"></i>Add New Class
                                </h6>
                                <small class="text-muted">Fill in the details below to create a new class</small>
                            </div>
                        </div>

                        <form action="{{ route('admin.class-master.store') }}" method="POST" id="inline-create-form">
                            @csrf
                            <div class="row g-3">
                                <!-- Class Name Input -->
                                <div class="col-md-5">
                                    <label for="class" class="form-label fw-semibold text-dark mb-2">
                                        <i class="mdi mdi-school text-primary me-1"></i>Class Name
                                        <span class="text-danger">*</span>
                                    </label>
                                    <div class="input-group has-validation">
                                        <span class="input-group-text bg-white border-end-0">
                                            <i class="mdi mdi-format-title text-secondary"></i>
                                        </span>
                                        <input type="text" name="class" id="class" class="form-control border-start-0 @error('class') is-invalid @enderror" placeholder="e.g., Play Group, KG, 1st Class" value="{{ old('class') }}" required autocomplete="off">
                                        @error('class')
                                            <div class="invalid-feedback">
                                                <i class="mdi mdi-alert-circle-outline fw-bold me-1"></i>{{ $message }}
                                            </div>
                                        @enderror
                                    </div>
                                    <div class="form-text">
                                        <i class="mdi mdi-information-outline me-1"></i>Enter a unique class name
                                    </div>
                                </div>

                                <!-- Class Sorting Input -->
                                <div class="col-md-4">
                                    <label for="sort" class="form-label fw-semibold text-dark mb-2">
                                        <i class="mdi mdi-sort-numeric-ascending text-info me-1"></i>Sorting Order
                                        <span class="text-danger">*</span>
                                    </label>
                                    <div class="input-group has-validation">
                                        <span class="input-group-text bg-white border-end-0">
                                            <i class="mdi mdi-numeric text-secondary"></i>
                                        </span>
                                        <input type="number" name="sort" id="sort" class="form-control border-start-0 @error('sort') is-invalid @enderror" placeholder="e.g., 1, 2, 3" value="{{ old('sort') }}" min="1" required autocomplete="off">
                                        @error('sort')
                                            <div class="invalid-feedback">
                                                <i class="mdi mdi-alert-circle-outline me-1 fw-bold"></i>{{ $message }}
                                            </div>
                                        @enderror
                                    </div>
                                    <div class="form-text">
                                        <i class="mdi mdi-information-outline me-1"></i>Display order in lists
                                    </div>
                                </div>

                                <!-- Action Buttons -->
                                <div class="col-md-3">
                                    <label class="form-label d-block opacity-0">Action</label>
                                    <div class="d-flex gap-2">
                                        <button class="btn btn-success shadow-sm" type="submit">
                                            <i class="mdi mdi-content-save me-2"></i>Save Class
                                        </button>
                                        <button class="btn btn-outline-secondary" type="button" id="cancelFormBtn">
                                            <i class="mdi mdi-close-circle-outline me-2"></i>Cancel
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
                                        <th>Class</th>
                                        <th>Class Sorting</th>
                                        <th class="text-center">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @if (count($data) > 0)
                                        @foreach ($data as $key => $value)
                                            <tr data-entry-id="{{ $value->id }}">
                                                <td>{{ $data->firstItem() + $key ?? '' }}</td>
                                                <td>{{ $value->class ?? '' }}</td>
                                                <td>{{ $value->sort ?? '' }}</td>
                                                <td class="text-center">
                                                    <div class="d-flex gap-2 justify-content-center">
                                                        <a href="{{ route('admin.class-master.edit', $value->id) }}"
                                                            class="btn btn-sm btn-icon editbtnGlobal">
                                                            <i class="mdi mdi-pencil" data-bs-toggle="tooltip"
                                                                data-bs-offset="0,4" data-bs-placement="top"
                                                                title="Edit"></i>
                                                        </a>
                                                        {{-- Uncomment if delete functionality is needed
                                                        <form action="{{ route('admin.class-master.softDelete', $value->id) }}" method="POST" style="display:inline;">
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
                                            <td colspan="4" class="text-center text-muted">No Classes Found</td>
                                        </tr>
                                    @endif
                                </tbody>
                            </table>
                            <div class="mt-3">
                                {{ $data->links() }}
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
        /* Toggle create form visibility */
        document.getElementById('toggleFormBtn').addEventListener('click', function() {
            const formSection = document.getElementById('createFormSection');
            const btn = this;
            if (formSection.style.display === 'none') {
                formSection.style.display = 'block';
                btn.innerHTML = '<span class="mdi mdi-minus-circle-outline me-2"></span>Cancel';
                btn.classList.remove('btn-success');
                btn.classList.add('btn-secondary');
                /* Focus on first input */
                setTimeout(() => {
                    document.getElementById('class').focus();
                }, 100);
            } else {
                formSection.style.display = 'none';
                btn.innerHTML = '<span class="mdi mdi-plus-circle-outline me-2"></span>Add New Class';
                btn.classList.remove('btn-secondary');
                btn.classList.add('btn-success');

                /* Reset form - Clear all values */
                const form = document.getElementById('inline-create-form');
                form.reset();

                /* Clear input values manually */
                document.getElementById('class').value = '';
                document.getElementById('sort').value = '';

                /* Remove validation error classes */
                document.querySelectorAll('.is-invalid').forEach(el => {
                    el.classList.remove('is-invalid');
                });

                /* Remove error messages */
                document.querySelectorAll('.invalid-feedback').forEach(el => {
                    el.style.display = 'none';
                });
            }
        });

        /* Cancel button handler */
        document.getElementById('cancelFormBtn').addEventListener('click', function() {
            document.getElementById('toggleFormBtn').click();
        });

        /* Show form if there are validation errors */
        @if ($errors->any())
            document.getElementById('createFormSection').style.display = 'block';
            const btn = document.getElementById('toggleFormBtn');
            btn.innerHTML = '<span class="mdi mdi-minus-circle-outline me-2"></span>Cancel';
            btn.classList.remove('btn-success');
            btn.classList.add('btn-secondary');

            /* Focus on first error field */
            setTimeout(() => {
                const firstError = document.querySelector('.is-invalid');
                if (firstError) {
                    firstError.focus();
                }
            }, 100);
        @endif

        /* Clear individual field error on input */
        document.querySelectorAll('.form-control').forEach(input => {
            input.addEventListener('input', function() {
                if (this.classList.contains('is-invalid')) {
                    this.classList.remove('is-invalid');
                    const feedback = this.parentElement.querySelector('.invalid-feedback');
                    if (feedback) {
                        feedback.style.display = 'none';
                    }
                }
            });
        });
    </script>
@endsection
