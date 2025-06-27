@extends('admin.index')

@section('sub-content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card">
                    <div class="card-header">
                        {{ 'Add New Academic Fee' }}
                        <a href="{{ route('admin.academic-fee-master.index') }}" class="btn btn-warning btn-sm"
                            style="float: right;">Back</a>

                    </div>

                    <div class="card-body">
                        <form action="{{ route('admin.academic-fee-master.store') }}" method="POST" id="basic-form">
                            @csrf
                            <div class="row">
                                <div class="form-group col-md-6">
                                    <label for="session_id" class="mt-2"> Session <span
                                            class="text-danger">*</span></label>
                                    <!-- <input type="hidden" id="initialSectionId" value="{{ $fee->session_id ?? '' }}"> -->
                                    <select name="session_id" id="session_id"
                                        class="form-control @error('session_id') is-invalid @enderror" required>
                                        <option value="">Select Session</option>
                                        @if (count($sessions) > 0)
                                        @foreach ($sessions as $key => $session)
                                        <option value="{{ $key }}"
                                            {{ old('session_id') == $key ? 'selected' : '' }}>
                                            {{ $session }}
                                        </option>
                                        @endforeach
                                        @else
                                        <option value="">No Session Found</option>
                                        @endif
                                    </select>
                                    @error('session_id')
                                    <span class="invalid-feedback form-invalid fw-bold" role="alert">
                                        {{ $message }}
                                    </span>
                                    @enderror
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="class_id" class="mt-2">Class <span
                                            class="text-danger">*</span></label>
                                    <!-- <input type="hidden" id="initialClassId" value="{{ isset($fee) ? $fee->class_id : '' }}"> -->
                                    <select name="class_id" id="class_id"
                                        class="form-control @error('class_id') is-invalid @enderror" required>
                                        <option value="">Select Class</option>
                                        @if (count($classes) > 0)
                                        @foreach ($classes as $key => $class)
                                        <option value="{{ $key }}"
                                            {{ old('class_id') == $key ? 'selected' : '' }}>
                                            {{ $class }}
                                        </option>
                                        @endforeach
                                        @else
                                        <option value="">No Class Found</option>
                                        @endif
                                    </select>
                                    @error('class_id')
                                    <span class="invalid-feedback form-invalid fw-bold" role="alert">
                                        {{ $message }}
                                    </span>
                                    @enderror
                                </div>
                                <!-- <img src="{{ config('myconfig.myloader') }}" alt="Loading..." class="loader"
                                        id="loader" style="display:none; width:10%;"> -->
                            </div>

                            <div class="row">

                                <div class="row">
                                    <div class="form-group col-md-2">
                                        <label for="admission_fee">Admission + Security Fee</label>
                                        <input type="text" name="admission_fee" id="admission_fee"
                                            class="form-control @error('admission_fee') is-invalid @enderror"
                                            value="{{ old('admission_fee') }}"
                                            required>
                                        @error('admission_fee')
                                        <span class="invalid-feedback form-invalid fw-bold"
                                            role="alert">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="form-group col-md-2">
                                        <label for="inst_1">1st Installment</label>
                                        <input type="text" name="inst_1" id="inst_1"
                                            class="form-control @error('inst_1') is-invalid @enderror"
                                            value="{{ old('inst_1') }}" required>
                                        @error('inst_1')
                                        <span class="invalid-feedback form-invalid fw-bold"
                                            role="alert">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="form-group col-md-2">
                                        <label for="inst_2">2nd Installment</label>
                                        <input type="text" name="inst_2" id="inst_2"
                                            class="form-control @error('inst_2') is-invalid @enderror"
                                            value="{{ old('inst_2') }}" required>
                                        @error('inst_2')
                                        <span class="invalid-feedback form-invalid fw-bold"
                                            role="alert">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="form-group col-md-2">
                                        <label for="ins_discount">Discount</label>
                                        <input type="text" name="ins_discount" id="ins_discount"
                                            class="form-control @error('ins_discount') is-invalid @enderror"
                                            value="{{ old('ins_discount') }}"
                                            required>
                                        @error('ins_discount')
                                        <span class="invalid-feedback form-invalid fw-bold"
                                            role="alert">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="form-group col-md-2">
                                        <label for="inst_total">Total</label>
                                        <input type="text" name="inst_total" id="inst_total"
                                            class="form-control @error('inst_total') is-invalid @enderror"
                                            value="{{ old('inst_total') }}"
                                            readonly>
                                        @error('inst_total')
                                        <span class="invalid-feedback form-invalid fw-bold"
                                            role="alert">{{ $message }}</span>
                                        @enderror
                                    </div>

                                </div>
                            </div>

                            <div class="mt-3">
                                <input class="btn btn-primary" type="submit"
                                    value="Save">
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
    // document.addEventListener("DOMContentLoaded", (event) => {

    //     var initialSessionId = '{{ old('session_id', isset($fee) ? $fee->session_id : '') }}';
    //     var initialClassId = '{{ old('class_id', isset($fee) ? $fee->class_id : '') }}';
    //     getClassSection(initialClassId);
    //     getSession(initialSessionId);
    // });
    $(document).ready(function() {

        function calculateTotal() {
            let admission = parseFloat($('#inst_admission').val()) || 0;
            let firstInstall = parseFloat($('#inst_1').val()) || 0;
            let secondInstall = parseFloat($('#inst_2').val()) || 0;
            let discount = parseFloat($('#inst_discount').val()) || 0;

            let total = admission + firstInstall + secondInstall - discount;

            $('#inst_total').val(total.toFixed(2));
        }

        $('#inst_admission, #inst_1 ,#inst_2, #inst_discount').on('input', calculateTotal);

        calculateTotal();
    });
</script>
@endsection