@extends('marks.index')

@section('sub-content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    {{ 'Print Final Marksheet (1st & 2nd)' }}
                    <a href="{{ route('marks.marksheet') }}" class="btn btn-warning btn-sm" style="float: right;">Back</a>
                </div>
                <div class="card-body">
                    <form id="class-section-form" action="{{ route('marks.marks-report.marksheet.first.second.store') }}" method="POST">
                        @csrf
                        <div class="row">
                            <div class="form-group col-md-4">
                                <input type="hidden" name="current_session" value='' id="current_session">
                                <label for="class_id" class="mt-2">Class <span class="text-danger">*</span></label>
                                <select name="class" id="class_id"
                                    class="form-control @error('class') is-invalid @enderror" required>
                                    <option value="">Select Class</option>
                                    @if (count($classes) > 0)
                                    @foreach ($classes as $key => $class)
                                    <option value="{{ $key }}" {{ old('class') == $key ? 'selected' : ''}}>{{ $class }}</option>
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
                            <div class="form-group col-md-4">
                                <label for="section_id" class="mt-2">Section <span
                                        class="text-danger">*</span></label>
                                <input type="hidden" id="initialSectionId"
                                    value="{{ old('initialSectionId', request()->get('section_id') !== null ? request()->get('section_id') : '') }}">
                                <select name="section" id="section_id"
                                    class="form-control @error('section') is-invalid @enderror" required>
                                    <option value="">Select Section</option>

                                </select>
                                @error('section')
                                <span class="invalid-feedback form-invalid fw-bold"
                                    role="alert">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="form-group col-md-4">
                                <label for="std_id" class="mt-2">Student <span class="text-danger">*</span></label>
                                <select name="std_id" id="std_id"
                                    class="form-control @error('std_id') is-invalid @enderror" required>
                                    <option value="">All Students</option>
                                </select>
                                @error('std_id')
                                <span class="invalid-feedback form-invalid fw-bold"
                                    role="alert">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group col-md-6">
                                <label for="result-date-message" class="mt-2">Result Date Message <span class="text-danger">*</span></label>
                                <input type="text" name="dateMessage" value="" id="result-date-message" class="form-control">
                                <span class="invalid-feedback form-invalid fw-bold result-date-message-error" role="alert"></span>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="session-start-message" class="mt-2">Session Start Message<span class="text-danger">*</span></label>
                                <input type="text" name="sessionMessage" value="" id="session-start-message" class="form-control">
                                <span class="invalid-feedback form-invalid fw-bold session-start-message-error" role="alert"></span>
                            </div>
                        </div>
                        <div class="mt-3">
                            <button type="submit" id="show-details" class="btn btn-primary">
                                Show Details</button><span><img src="{{ config('myconfig.myloader') }}" alt="Loading..." class="loader"
                                    id="loader" style="display:none; width:10%;"></span>
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
        let initialClassId = $('#class_id').val();
        let initialSectionId = $('#initialSectionId').val();
        getClassSection(initialClassId, initialSectionId);
        getStd();
    });
</script>
@endsection