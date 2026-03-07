@extends('admin.index')
@section('sub-content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card border-0 bg-white">
                    <div class="card-header flex-wrap bg-white d-flex align-items-center justify-content-between">
                        <h5 class="mb-0 mt-0">{{ 'Day-wise Fee Collections' }}</h5>
                        <a onclick="history.back()" class="btn bg-light btn-sm">
                            <span class="mdi mdi-chevron-left me-2"></span>Back
                        </a>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('admin.exportdDayWiseCollectionReprt') }}" method="POST">
                            @csrf
                            {{-- Filters Section --}}
                            <div class="row mb-4">

                                {{-- Fee Type --}}
                                <div class="col-md-4">
                                    <label for="to_date" class="form-label">Fee Type</label>
                                    <select id="academic_trans" name="academic_trans" class="form-control" required>
                                        <option value="all" {{ old('academic_trans') === 'all' ? 'selected' : '' }}>All</option>
                                        <option value="1" {{ old('academic_trans') == '1' ? 'selected' : '' }}>Academic</option>
                                        <option value="2" {{ old('academic_trans') == '2' ? 'selected' : '' }}>Transport</option>
                                        <option value="3" {{ old('academic_trans') == '3' ? 'selected' : '' }}>TC</option>
                                    </select>
                                </div>
                                {{-- From Date --}}
                                <div class="col-md-4">
                                    <label for="from_date" class="form-label">From Date <span class="text-danger">*</span></label>
                                    <input type="date" id="from_date" name="from_date" class="form-control" value="{{ old('from_date') }}" required>
                                </div>

                                {{-- To Date --}}
                                <div class="col-md-4">
                                    <label for="to_date" class="form-label">To Date <span class="text-danger">*</span></label>
                                    <input type="date" id="to_date" name="to_date" class="form-control" value="{{ old('to_date') }}" required>
                                </div>

                            </div>
                            <div class="row mb-4">

                                {{-- Payment Mode --}}
                                <div class="col-md-4">
                                    <label for="to_date" class="form-label">Payment Mode</label>
                                    <select id="fee_mode" name="fee_mode" class="form-control" required>
                                        <option value="all" {{ old('fee_mode') === 'all' ? 'selected' : '' }}>All</option>
                                        <option value="1" {{ old('fee_mode') == '1' ? 'selected' : '' }}>Cash</option>
                                        <option value="2" {{ old('fee_mode') == '2' ? 'selected' : '' }}>UPI</option>
                                        <option value="3" {{ old('fee_mode') == '3' ? 'selected' : '' }}>Bank Transfer</option>
                                    </select>
                                </div>

                                {{-- Class --}}
                                <div class="col-md-4">
                                    <label for="class_id" class="form-label">Class</label>
                                    <select id="class_id" name="class_id" class="form-control" required>
                                        <option value="all" selected>All</option>
                                        @foreach ($classes as $id => $name)
                                            <option value="{{ $id }}">{{ $name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                {{-- Section --}}
                                <div class="col-md-4">
                                    <label for="section_id" class="form-label">Section</label>
                                    <select id="section_id" name="section_id" class="form-control" required>
                                        <option value="all" selected>All Section</option>
                                    </select>
                                </div>
                            </div>

                            {{-- Filter Button --}}
                            <div class="row mb-4">
                                <div class="col-md-3 d-flex align-items-end">
                                    <button type="submit" id="filter-report" class="btn btn-primary w-100">
                                        <i class="mdi mdi-filter me-1"></i>Download Report
                                    </button>

                                </div>
                                <div class="col-md-3 d-flex align-items-end">
                                    @if(session('error'))
                                        <span class="text-danger fw-bold" id="filter-report-error" role="alert">
                                            {{ session('error') }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('admin-scripts')
    <script>
        $(document).ready(function() {
            let sectionId = $("#section_id");
            sectionId.empty().append('<option value="all" selected>All Section</option>');
            // When Class changes → fetch/update Section
            $("#class_id").on("change", function() {
                let classId = $(this).val();
                console.log(classId);
                sectionId.empty();
                if (!classId) {
                    sectionId.append('<option value="">Select Class</option>');
                    return;
                }
                if (classId == "all") {
                    sectionId.append('<option value="all" selected>All Section</option>');
                    return;
                }

                fetchSections(classId);
            });

            // Function to fetch sections via AJAX
            function fetchSections(classId) {
                loader.show();
                $.ajax({
                    url: "{{ route('admin.allSections') }}",
                    type: 'GET',
                    dataType: 'JSON',
                    data: {
                        class_id: classId
                    },
                    success: function(data) {
                        sectionId.empty().append('<option value="">Select Section</option>');

                        if (data.data && Object.keys(data.data).length > 0) {
                            $.each(data.data, function(id, name) {
                                sectionId.append('<option value="' + id + '">' + name + '</option>');
                            });
                        } else {
                            sectionId.append('<option value="">No sections found</option>');
                        }
                    },
                    complete: function() {
                        loader.hide();
                    },
                    error: function(xhr) {
                        console.error('Error fetching sections:', xhr.responseJSON ? xhr.responseJSON.message : 'Unknown error');
                        sectionId.empty().append('<option value="">No sections found</option>');
                    }
                });
            }
        });
    </script>
@endsection
