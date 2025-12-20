@extends('admin.index')

@section('sub-content')
    <div class="container-fluid">

        {{-- Alerts --}}
        @if (Session::has('success'))
            @push('swal-scripts')
                <script>
                    swal("Success", "{{ Session::get('success') }}", "success");
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
                                <h5 class="mb-0">{{ __('Subject Group Master') }}</h5>
                            </div>
                            <div class="col-md-8">
                                <div class="d-flex align-items-center justify-content-end gap-2">
                                    <!-- Filter Form -->
                                    <form action="{{ route('admin.subject-group-master.index') }}" method="get"
                                        class="d-flex gap-2">
                                        <select name="class_id" id="filter_class_id" class="form-control" required>
                                            <option value="">Select Class</option>
                                            @foreach ($classes as $key => $class)
                                                <option value="{{ $key }}" {{ request('class_id') == $key ? 'selected' : '' }}>
                                                    {{ $class }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <input type="hidden" id="filterInitialSubjectId" value="{{ request('subject_id') }}">
                                        <select name="subject_id" id="filter_subject_id" class="form-control" required>
                                            <option value="">Select Subject</option>
                                        </select>
                                        <button class="btn btn-dark">Search</button>
                                        <a href="{{ route('admin.subject-group-master.index') }}" class="btn btn-secondary">Reset</a>
                                    </form>
                                    <!-- Add Button -->
                                    <button type="button" class="btn btn-success text-white" id="toggleFormBtn">
                                        <i class="mdi mdi-plus-circle-outline me-2"></i>Add New Subject
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- INLINE CREATE FORM --}}
                    <div class="card-body bg-light border-bottom" id="createFormSection" style="display:none;">
                        <h6 class="fw-bold mb-3">Add New Subject Group</h6>

                        <form action="{{ route('admin.subject-group-master.store') }}" method="POST"
                            id="inline-create-form">
                            @csrf

                            <div class="row">

                                <div class="col-md-6 mb-3">
                                    <label>Class <span class="text-danger">*</span></label>
                                    <select name="class_id" id="create_class_id" class="form-control @error('class_id') is-invalid @enderror">
                                        <option value="">Select Class</option>
                                        @foreach ($classes as $key => $class)
                                            <option value="{{ $key }}"
                                                {{ old('class_id') == $key ? 'selected' : '' }}>
                                                {{ $class }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('class_id')
                                        <span class="invalid-feedback d-block">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label>Subject <span class="text-danger">*</span></label>
                                    <input type="hidden" id="createInitialSubjectId" value="{{ old('subject_id') }}">
                                    <select name="subject_id" id="create_subject_id" class="form-control @error('subject_id') is-invalid @enderror">
                                        <option value="">Select Subject</option>
                                    </select>
                                    @error('subject_id')
                                        <span class="invalid-feedback d-block">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label>Sub Subject Name <span class="text-danger">*</span></label>
                                    <input type="text" name="subject" class="form-control @error('subject') is-invalid @enderror" value="{{ old('subject') }}">
                                    @error('subject')
                                        <span class="invalid-feedback d-block">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label>Priority <span class="text-danger">*</span></label>
                                    <input type="number" name="priority" min="1" class="form-control @error('priority') is-invalid @enderror" value="{{ old('priority') }}" autocomplete="off">
                                    @error('priority')
                                        <span class="invalid-feedback d-block">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="col-md-12 mb-3">
                                    <label>Result Type <span class="text-danger">*</span></label><br>

                                    <label class="me-3">
                                        <input type="radio" name="by_m_g" value="1" {{ old('by_m_g') == 1 ? 'checked' : '' }}>
                                        Result By Marks
                                    </label>

                                    <label>
                                        <input type="radio" name="by_m_g" value="2" {{ old('by_m_g') == 2 ? 'checked' : '' }}>
                                        Result By Grade
                                    </label>

                                    @error('by_m_g')
                                        <span class="invalid-feedback d-block">{{ $message }}</span>
                                    @enderror
                                </div>

                            </div>

                            <button class="btn btn-primary">Save</button>
                        </form>
                    </div>

                    {{-- TABLE --}}
                    <div class="card-body">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Class</th>
                                    <th>Subject</th>
                                    <th>Sub Subject</th>
                                    <th>Priority</th>
                                    <th class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($data as $key => $value)
                                    <tr>
                                        <td>{{ $data->firstItem() + $key }}</td>
                                        <td>{{ $value->class->class ?? '' }}</td>
                                        <td>{{ $value->subjectGroup->subject ?? '' }}</td>
                                        <td>
                                            {{ $value->subject }}
                                            ({{ $value->by_m_g == 1 ? 'Marks' : 'Grade' }})
                                        </td>
                                        <td>{{ $value->priority }}</td>
                                        <td class="text-center">
                                            <a href="{{ route('admin.subject-group-master.edit', $value->id) }}">
                                                <i class="mdi mdi-pencil"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6">No Subject Group Found</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>

                        {{ $data->links() }}
                    </div>

                </div>
            </div>
        </div>
    </div>
@endsection

@section('admin-scripts')
    <script>
        $(document).ready(function() {
            // Get subjects on the basis of class
            function getClassWithSubject(classId, selectedSubjectId = null, subjectGroupSelector = null,
                subjectSelectSelector) {
                let $subjectSelect = $(subjectSelectSelector);

                function fetchSubjects(classId, subjectGroupVal = null) {
                    if (!classId) {
                        $subjectSelect.html('<option value="">Select Subject</option>');
                        return;
                    }
                    loader.show();
                    $.ajax({
                        url: "{{ route('main.subjects.get') }}",
                        type: 'GET',
                        dataType: 'json',
                        data: {
                            class_id: classId,
                        },
                        success: function(response) {

                            $subjectSelect.empty();
                            if (response.data && Object.keys(response.data).length > 0) {
                                $subjectSelect.append('<option value="">Select Subject</option>');

                                $.each(response.data, function(id, name) {
                                    $subjectSelect.append(
                                        `<option value="${id}">${name}</option>`
                                    );
                                });

                                if (selectedSubjectId) {
                                    $subjectSelect.val(selectedSubjectId);
                                }

                            } else {
                                $subjectSelect.append(
                                '<option value="">No subjects available</option>');
                            }
                        },
                        complete: function() {
                            loader.hide();
                        },
                        error: function() {
                            loader.hide();
                            console.error('Failed to fetch subjects');
                        }
                    });
                }

                let subjectGroupVal = subjectGroupSelector ? $(subjectGroupSelector).val() : null;
                fetchSubjects(classId, subjectGroupVal);
            }

            /* FILTER SUBJECT LOAD */
            getClassWithSubject(
                $('#filter_class_id').val(),
                $('#filterInitialSubjectId').val(),
                null,
                '#filter_subject_id'
            );

            /* CREATE SUBJECT LOAD */
            getClassWithSubject($('#create_class_id').val(), $('#createInitialSubjectId').val(), null,
                '#create_subject_id');

            $('#filter_class_id').change(function() {
                getClassWithSubject($(this).val(), null, null, '#filter_subject_id');
            });

            $('#create_class_id').change(function() {
                getClassWithSubject($(this).val(), null, null, '#create_subject_id');
            });

            /* TOGGLE CREATE FORM */
            $('#toggleFormBtn').click(function() {
                const $form = $('#createFormSection');

                if ($form.is(':hidden')) {
                    $form.slideDown();
                    $(this).removeClass('btn-success').addClass('btn-secondary').html(
                        '<span class="mdi mdi-minus-circle-outline me-2"></span>Cancel');
                } else {
                    resetCreateForm();
                }
            });

            function resetCreateForm() {
                $('#createFormSection').slideUp();
                $('#toggleFormBtn').removeClass('btn-secondary').addClass('btn-success').html(
                    '<span class="mdi mdi-plus-circle-outline me-2"></span>Add');

                $('#inline-create-form')[0].reset();
                $('.is-invalid').removeClass('is-invalid');
                $('.invalid-feedback').hide();
            }

            /* SHOW FORM ON VALIDATION ERROR */
            @if ($errors->any())
                $('#createFormSection').show();
                $('#toggleFormBtn').removeClass('btn-success').addClass('btn-secondary').html(
                    '<span class="mdi mdi-minus-circle-outline me-2"></span>Cancel');
            @endif

        });
    </script>
@endsection
