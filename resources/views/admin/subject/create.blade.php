@extends('admin.index')

@section('sub-content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card">
                        <div class="card-header">
                            {{ 'Add New Subject' }}
                            <a href="{{ route('admin.subject-master.index') }}" class="btn btn-warning btn-sm"
                                style="float: right;">Back</a>
                        </div>

                        <div class="card-body">
                            <form action="{{ route('admin.subject-master.store') }}" method="POST" id="basic-form">
                                @csrf
                                <div class="row">
                                    <div class="form-group col-md-12">
                                        <label for="class_id" class="mt-2">Class <span
                                                class="text-danger">*</span></label>
                                        <select name="class_id" id="class_id"
                                            class="form-control @error('class_id') is-invalid @enderror" required>
                                            <option value="">Select Class</option>
                                            @if (count($classes) > 0)
                                                @foreach ($classes as $key => $class)
                                                    <option value="{{ $key }}"
                                                        {{ old('class_id') == $key ? 'selected' : '' }}>
                                                        {{ $class }}</option>
                                                @endforeach
                                            @else
                                                <option value="">No Classes Found</option>
                                            @endif

                                        </select>
                                        @error('class_id')
                                            <span class="invalid-feedback form-invalid fw-bold" role="alert">
                                                {{ $message }}
                                            </span>
                                        @enderror
                                    </div>

                                </div>

                                <div class="row">
                                    <div class="form-group col-md-12">
                                        <label for="subject" class="mt-2"> Subject <span
                                                class="text-danger">*</span></label>
                                        <input type="text" name="subject"
                                            class="form-control @error('subject') is-invalid @enderror"
                                            placeholder="Subject" value="{{ old('subject') }}" required>
                                        @error('subject')
                                            <span class="invalid-feedback form-invalid fw-bold" role="alert">
                                                {{ $message }}
                                            </span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="form-group form-check col-md-6">
                                        <div class="form-check">
                                            <input class="form-check-input @error('by_m_g') is-invalid @enderror"
                                                value="1" type="radio" name="by_m_g" id="by_marks"
                                                {{ old('by_m_g') == 1 ? 'checked=' . '"' . 'checked' . '"' : '' }}>
                                            <label class="form-check-label" for="by_marks">
                                                Result By Marks
                                            </label>
                                        </div>
                                    </div>
                                    <div class="form-group form-check col-md-6">
                                        <div class="form-check">
                                            <input class="form-check-input @error('by_m_g') is-invalid @enderror"
                                                value="2" type="radio" name="by_m_g" id="by_grade"
                                                {{ old('by_m_g') == 2 ? 'checked=' . '"' . 'checked' . '"' : '' }}>
                                            <label class="form-check-label" for="by_grade">
                                                Result By Grade
                                            </label>
                                        </div>
                                    </div>
                                    @error('by_m_g')
                                        <span class="invalid-feedback form-invalid fw-bold" role="alert">
                                            {{ $message }}
                                        </span>
                                    @enderror
                                </div>

                                <div class="mt-3">
                                    <input class="btn btn-primary" type="submit" value="Save">
                                </div>
                            </form>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
@endsection
