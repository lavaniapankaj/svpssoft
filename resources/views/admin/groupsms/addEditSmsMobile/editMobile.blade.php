@extends('admin.index')
@section('sub-content')
    <div class="container">

        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">{{ __('Add Mobile Number') }}
                        <a href="{{ route('admin.add-edit-sms-group-mobile.index') }}" class="btn btn-warning"
                            style="float: right;">Back</a>

                    </div>

                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-12">
                                <form action="{{ route('admin.add-edit-sms-group-mobile.update', isset($data) ? $data->id : '') }}" method="POST">
                                    @csrf
                                    @method('put')
                                    <div class="form-group">
                                        <label for="group_id">{{ __('	Select Group') }}</label>
                                        <select id="group_id" name="group_id"
                                            class="form-control @error('group_id') is-invalid @enderror">
                                            <option value="">Select Group</option>
                                            @if (count($groups) > 0)
                                                @foreach ($groups as $key => $group)
                                                    <option value="{{ old('group_id', isset($data) ? $data->group_id : '')}}"
                                                        {{ old('group_id', isset($data) ? $data->group_id : '') == $group->id ? 'selected' : '' }}>
                                                        {{ $group->group_name }}
                                                    </option>
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
                                    <div class="form-group">
                                        <label for="name">{{ __('Name') }}</label>
                                        <input type="text" placeholder="Enter Name"
                                            value="{{ old('name', isset($data) ? $data->name : '') }}" id="name" name="name"
                                            class="form-control @error('name') is-invalid @enderror" required>
                                        @error('name')
                                            <span class="invalid-feedback form-invalid fw-bold"
                                                role="alert">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div class="form-group">
                                        <label for="mobile">{{ __('Mobile Number') }}</label>
                                        <input type="tel" placeholder="Enter Mobile Number"
                                            value="{{ old('mobile', isset($data) ? $data->mobile : '') }}" id="mobile" name="mobile"
                                            class="form-control @error('mobile') is-invalid @enderror" required>
                                        @error('mobile')
                                            <span class="invalid-feedback form-invalid fw-bold"
                                                role="alert">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="mt-3">
                                        <button type="submit" class="btn btn-primary float-right">{{ __('Update') }}</button>
                                    </div>
                                </form>
                            </div>

                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
