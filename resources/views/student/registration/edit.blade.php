@extends('student.index')
@section('styles')
    <style>
        .form-step {
            display: none;
            /* Hide all steps by default */
        }

        .form-navigation {
            margin-top: 20px;
        }
    </style>
@endsection
@section('sub-content')
    <div class="container">

        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card">
                        <div class="card-header">
                            {{ 'Edit Student'}}
                            <a class="btn btn-warning btn-sm" style="float: right;" id="btn-back"
                                onclick="history.back()">Back</a>
                        </div>

                        <div class="card-body">
                            <form action="{{ route('student.student-master.update', isset($student) ? $student->id : '') }}" method="POST" id="multiStepForm">
                                @csrf
                                @method('put')
                                <input type="hidden" name="id" id="id"
                                    value="{{ isset($student) ? $student->id : '' }}">

                                <!-- Student Main Information -->
                                <div class="form-step" id="step1">
                                    <h5>Student Main Information</h5>
                                    <div class="row">
                                        <div class="form-group col-md-6">
                                            <input type="hidden" name="session" value='{{ old('session', isset($student) ? $student->session_id : '') }}' id="session">
                                            <label for="srno">SR No. <span class="text-danger">*</span></label>
                                            <input type="text" name="srno" id="srno" placeholder="Enter SR No." class="form-control @error('srno') is-invalid @enderror" value="{{ old('srno', isset($student) ? $student->srno : '') }}" required>
                                            @error('srno')
                                                <span class="invalid-feedback form-invalid fw-bold" role="alert">{{ $message }}</span>
                                            @enderror
                                        </div>
                                        <div class="form-group col-md-6">
                                            <label for="school">School</label>
                                            <select name="school" id="school" class="form-control @error('school') is-invalid @enderror" required>
                                                <option value="">Select School</option>
                                                <option value="1" {{ old('school', isset($student) ? $student->school : '') == 1 ? 'selected' : '' }}>Play House</option>
                                                <option value="2" {{ old('school', isset($student) ? $student->school : '') == 2 ? 'selected' : '' }}>Public</option>
                                            </select>
                                            @error('school')
                                                <span class="invalid-feedback form-invalid fw-bold" role="alert">{{ $message }}</span>
                                            @enderror

                                        </div>
                                        {{-- <div class="form-group col-md-4">
                                            <label for="prev_srno">Previous SR No.</label>
                                            <input type="text" name="prev_srno" id="prev_srno"
                                                class="form-control @error('prev_srno') is-invalid @enderror"
                                                value="{{ old('prev_srno', isset($student) ? $student->prev_srno : '') }}"
                                                required>
                                            @error('prev_srno')
                                                <span class="invalid-feedback form-invalid fw-bold"
                                                    role="alert">{{ $message }}</span>
                                            @enderror
                                        </div> --}}

                                    </div>

                                    <div class="row">
                                        <div class="form-group col-md-4">
                                            <label for="class_id" class="mt-2">Class <span class="text-danger">*</span></label>
                                            <input type="hidden" id="initialClassId" value="{{ isset($student) ? $student->class : '' }}">
                                            <select name="class" id="class_id" class="form-control @error('class') is-invalid @enderror" required>
                                                <option value="">Select Class</option>
                                                @if (count($classes) > 0)
                                                    @foreach ($classes as $key => $class)
                                                        <option value="{{ $key }}" {{ old('class', isset($student) ? $student->class : '') == $key ? 'selected' : ''}}>{{ $class }}</option>
                                                    @endforeach
                                                @else
                                                    <option value="">No Class Found</option>
                                                @endif
                                            </select>
                                            @error('class')
                                                <span class="invalid-feedback form-invalid fw-bold" role="alert">{{ $message }}</span>
                                            @enderror

                                        </div>
                                        <div class="form-group col-md-4">
                                            <label for="section_id" class="mt-2">Section <span class="text-danger">*</span></label>
                                            <input type="hidden" id="initialSectionId" value="{{ $student->section ?? '' }}">
                                            <select name="section" id="section_id" class="form-control @error('section') is-invalid @enderror" required>
                                                <option value="">Select Section</option>
                                            </select>
                                            @error('section')
                                                <span class="invalid-feedback form-invalid fw-bold" role="alert">{{ $message }}</span>
                                            @enderror
                                            <img src="{{ config('myconfig.myloader') }}" alt="Loading..." class="loader" id="loader" style="display:none; width:10%;">
                                        </div>

                                        <div class="form-group col-md-4">
                                            <label for="rollno">Roll No. <span class="text-danger">*</span></label>
                                            <input type="text" name="rollno" id="rollno" placeholder="Enter Roll No." class="form-control @error('rollno') is-invalid @enderror" value="{{ old('rollno', isset($student) ? $student->rollno : '') }}">
                                            @error('rollno')
                                                <span class="invalid-feedback form-invalid fw-bold" role="alert">{{ $message }}</span>
                                            @enderror
                                        </div>

                                    </div>

                                    <div class="row">

                                        <div class="form-group col-md-4">
                                            <label for="transport">Transport <span class="text-danger">*</span></label>
                                            <select name="transport" id="transport"
                                                class="form-control @error('transport') is-invalid @enderror" required>
                                                <option value="">Select Transport</option>
                                                <option value="1" {{ old('transport', isset($student) ? $student->transport : '') == 1 ? 'selected' : '' }}> Yes</option>
                                                <option value="0" {{ old('transport', isset($student) ? $student->transport : '') == 0 ? 'selected' : '' }}> No</option>

                                            </select>
                                            @error('transport')
                                                <span class="invalid-feedback form-invalid fw-bold" role="alert">
                                                    {{ $message }}
                                                </span>
                                            @enderror
                                        </div>
                                        <div class="form-group col-md-4">
                                            <label for="age_proof">Age Proof <span class="text-danger">*</span></label>
                                            <select name="age_proof" id="age_proof"
                                                class="form-control @error('age_proof') is-invalid @enderror" required>
                                                <option value="0">Select Age Proof</option>
                                                <option value="1" {{ old('age_proof', isset($student) ? $student->age_proof : '') == 1 ? 'selected' : '' }}>Transfer Certificate (T.C.)</option>
                                                <option value="2" {{ old('age_proof', isset($student) ? $student->age_proof : '') == 2 ? 'selected' : '' }}>Birth Certificate</option>
                                                <option value="3" {{ old('age_proof', isset($student) ? $student->age_proof : '') == 3 ? 'selected' : '' }}>Affidavit</option>
                                                <option value="4" {{ old('age_proof', isset($student) ? $student->age_proof : '') == 4 ? 'selected' : '' }}>Aadhar Card</option>
                                            </select>
                                            @error('age_proof')
                                                <span class="invalid-feedback form-invalid fw-bold"
                                                    role="alert">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="form-group col-md-4">
                                            <label for="gender">Gender <span class="text-danger">*</span></label>
                                            <select name="gender" id="gender"
                                                class="form-control @error('gender') is-invalid @enderror" required>
                                                <option value="">Select Gender</option>
                                                <option value="1" {{ old('gender', isset($student) ? $student->gender : '') == 1 ? 'selected' : '' }}>Male</option>
                                                <option value="2" {{ old('gender', isset($student) ? $student->gender : '') == 2 ? 'selected' : '' }}>Female</option>
                                                <option value="3" {{ old('gender', isset($student) ? $student->gender : '') == 3 ? 'selected' : '' }}>Other</option>
                                            </select>
                                            @error('gender')
                                                <span class="invalid-feedback form-invalid fw-bold"role="alert">{{ $message }}</span>
                                            @enderror
                                        </div>
                                        <div class="form-group col-md-4">
                                            <label for="religion">Religion <span class="text-danger">*</span></label>
                                            <select name="religion" id="religion" class="form-control @error('religion') is-invalid @enderror" required>
                                                <option value="">Select Religion</option>
                                                <option value="1" {{ old('religion', isset($student) ? $student->religion : '') == 1 ? 'selected' : '' }}>Hindu</option>
                                                <option value="2" {{ old('religion', isset($student) ? $student->religion : '') == 2 ? 'selected' : '' }}>Muslim</option>
                                                <option value="3" {{ old('religion', isset($student) ? $student->religion : '') == 3 ? 'selected' : '' }}>Christian</option>
                                                <option value="4" {{ old('religion', isset($student) ? $student->religion : '') == 4 ? 'selected' : '' }}>Sikh</option>
                                            </select>
                                            @error('religion')
                                                <span class="invalid-feedback form-invalid fw-bold" role="alert">{{ $message }}</span>
                                            @enderror
                                        </div>
                                        <div class="form-group col-md-4">
                                            <label for="admission_date">Admission Date <span class="text-danger">*</span></label>
                                            <input type="date" name="admission_date" id="admission_date" class="form-control @error('admission_date') is-invalid @enderror" value="{{ old('admission_date', isset($student) ? $student->admission_date : '') }}" required>
                                            @error('admission_date')
                                                <span class="invalid-feedback form-invalid fw-bold" role="alert">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>



                                    <div class="row" id="transportFeeDiv">
                                        <div class="form-group col-md-3">
                                            <label for="trans_1st_inst">1st Installment</label>
                                            <input type="text" name="trans_1st_inst" id="trans_1st_inst" placeholder="Enter 1st Installment" class="form-control @error('trans_1st_inst') is-invalid @enderror" value="{{ old('trans_1st_inst', isset($student) ? $student->trans_1st_inst : '') }}" required>
                                            @error('trans_1st_inst')
                                                <span class="invalid-feedback form-invalid fw-bold" role="alert">{{ $message }}</span>
                                            @enderror
                                        </div>
                                        <div class="form-group col-md-3">
                                            <label for="trans_2nd_inst">2nd Installment</label>
                                            <input type="text" name="trans_2nd_inst" id="trans_2nd_inst" placeholder="Enter 2nd Installment" class="form-control @error('trans_2nd_inst') is-invalid @enderror" value="{{ old('trans_2nd_inst', isset($student) ? $student->trans_2nd_inst : '') }}" required>
                                            @error('trans_2nd_inst')
                                                <span class="invalid-feedback form-invalid fw-bold" role="alert">{{ $message }}</span>
                                            @enderror
                                        </div>
                                        <div class="form-group col-md-3">
                                            <label for="trans_discount">Discount</label>
                                            <input type="text" name="trans_discount" id="trans_discount" placeholder="Enter Discount" class="form-control @error('trans_discount') is-invalid @enderror" value="{{ old('trans_discount', isset($student) ? $student->trans_discount : '') }}" required>
                                            @error('trans_discount')
                                                <span class="invalid-feedback form-invalid fw-bold" role="alert">{{ $message }}</span>
                                            @enderror
                                        </div>
                                        <div class="form-group col-md-3">
                                            <label for="trans_total">Total <span class="text-danger">*</span></label>
                                            <input type="text" name="trans_total" id="trans_total" placeholder="Enter Total Amount" class="form-control @error('trans_total') is-invalid @enderror" value="{{ old('trans_total', isset($student) ? $student->trans_total : '') }}" readonly>
                                            @error('trans_total')
                                                <span class="invalid-feedback form-invalid fw-bold" role="alert">{{ $message }}</span>
                                            @enderror
                                        </div>

                                    </div>
                                </div>

                                <div class="form-step" id="step2" style="display: none;">
                                    <!-- Student Details -->
                                    <h5 class="mt-4">Student Details</h5>
                                    <div class="row">
                                        <div class="form-group col-md-6">
                                            <label for="name">Name <span class="text-danger">*</span></label>
                                            <input type="text" name="name" id="name" placeholder="Enter Student Name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', isset($student_detail) ? $student_detail->name : '') }}" required>
                                            @error('name')
                                                <span class="invalid-feedback form-invalid fw-bold" role="alert">{{ $message }}</span>
                                            @enderror
                                        </div>
                                        <div class="form-group col-md-6">
                                            <label for="dob">Date of Birth <span class="text-danger">*</span></label>
                                            <input type="date" name="dob" id="dob" class="form-control @error('dob') is-invalid @enderror" value="{{ old('dob', isset($student_detail) ? $student_detail->dob : '') }}" required>
                                            @error('dob')
                                                <span class="invalid-feedback form-invalid fw-bold" role="alert">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="form-group col-md-4">
                                            <label for="std_email">Email <span class="text-danger">*</span></label>
                                            <input type="email" name="std_email" id="std_email" placeholder="Enter email" class="form-control @error('std_email') is-invalid @enderror" value="{{ old('std_email', isset($student_detail) ? $student_detail->email : '') }}" required>
                                            @error('std_email')
                                                <span class="invalid-feedback form-invalid fw-bold"
                                                    role="alert">{{ $message }}</span>
                                            @enderror
                                        </div>
                                        <div class="form-group col-md-4">
                                            <label for="mobile">Mobile <span class="text-danger">*</span></label>
                                            <input type="tel" name="mobile" id="mobile" placeholder="Enter mobile number" class="form-control @error('mobile') is-invalid @enderror" value="{{ old('mobile', isset($student_detail) ? $student_detail->mobile : '') }}" required>
                                            @error('mobile')
                                                <span class="invalid-feedback form-invalid fw-bold" role="alert">{{ $message }}</span>
                                            @enderror
                                        </div>
                                        <div class="form-group col-md-4">
                                            <label for="category_id">Category <span class="text-danger">*</span></label>
                                            <select name="category_id" id="category_id" class="form-control @error('category_id') is-invalid @enderror" required>
                                                <option value="">Select Category</option>
                                                <option value="1" {{ old('category_id', isset($student_detail) ? $student_detail->category_id : '') == 1 ? 'selected' : '' }}>General</option>
                                                <option value="2" {{ old('category_id', isset($student_detail) ? $student_detail->category_id : '') == 2 ? 'selected' : '' }}>OBC</option>
                                                <option value="3" {{ old('category_id', isset($student_detail) ? $student_detail->category_id : '') == 3 ? 'selected' : '' }}>SC</option>
                                                <option value="4" {{ old('category_id', isset($student_detail) ? $student_detail->category_id : '') == 4 ? 'selected' : '' }}>ST</option>
                                                <option value="5" {{ old('category_id', isset($student_detail) ? $student_detail->category_id : '') == 5 ? 'selected' : '' }}>BC</option>
                                            </select>
                                            @error('category_id')
                                                <span class="invalid-feedback form-invalid fw-bold" role="alert">{{ $message }}</span>
                                            @enderror
                                        </div>

                                    </div>

                                    <div class="row">
                                        <div class="form-group col-md-6">
                                            <label for="pre_school">Previous School</label>
                                            <input type="text" name="pre_school" id="pre_school" placeholder="Enter Previous School" class="form-control @error('pre_school') is-invalid @enderror" value="{{ old('pre_school', isset($student_detail) ? $student_detail->pre_school : '') }}">
                                            @error('pre_school')
                                                <span class="invalid-feedback form-invalid fw-bold" role="alert">{{ $message }}</span>
                                            @enderror
                                        </div>
                                        <div class="form-group col-md-6">
                                            <label for="pre_class">Previous Class</label>
                                            <input type="text" name="pre_class" id="pre_class" placeholder="Enter Previous Class" class="form-control @error('pre_class') is-invalid @enderror" value="{{ old('pre_class', isset($student_detail) ? $student_detail->pre_class : '') }}">
                                            @error('pre_class')
                                                <span class="invalid-feedback form-invalid fw-bold" role="alert">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="form-group col-md-6">
                                            <label for="reason">Reason</label>
                                            <input type="text" name="reason" id="reason" placeholder="Enter Reason" class="form-control @error('reason') is-invalid @enderror" value="{{ old('reason', isset($student) ? $student->reason : '') }}">
                                            @error('reason')
                                                <span class="invalid-feedback form-invalid fw-bold" role="alert">{{ $message }}</span>
                                            @enderror
                                        </div>
                                        <div class="form-group col-md-6">
                                            <label for="TCRefNo">TC Reference No.</label>
                                            <input type="text" name="TCRefNo" placeholder="Enter TC Reference No." id="TCRefNo" class="form-control @error('TCRefNo') is-invalid @enderror" value="{{ old('TCRefNo', isset($student) ? $student->TCRefNo : '') }}">
                                            @error('TCRefNo')
                                                <span class="invalid-feedback form-invalid fw-bold" role="alert">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="form-group col-md-4">
                                            <label for="state_id">State <span class="text-danger">*</span></label>
                                            <input type="hidden" name="initialStateId" id="initialStateId" value="{{ old('state_id', isset($student_detail) ? $student_detail->state_id : '') }}">
                                            <select name="state_id" id="state_id" class="form-control state_id @error('state_id') is-invalid @enderror" required>
                                                <option value="">Select State</option>
                                                @if (count($states) > 0)
                                                    @foreach ($states as $key => $state)
                                                        <option value="{{ $key }}" {{ old('state_id', isset($student_detail) ? $student_detail->state_id : '') == $key ? 'selected' : ''}}>{{ $state }}</option>
                                                    @endforeach
                                                @else
                                                    <option value="">No State Found</option>
                                                @endif

                                            </select>
                                            @error('state_id')
                                                <span class="invalid-feedback form-invalid fw-bold" role="alert">{{ $message }}</span>
                                            @enderror
                                            <img src="{{ config('myconfig.myloader') }}" alt="Loading..." class="loader" id="loader" style="display:none; width:10%;">
                                        </div>

                                        <div class="form-group col-md-4">
                                            <label for="district_id">District <span class="text-danger">*</span></label>
                                            <input type="hidden" name="initialDistrictId" id="initialDistrictId" value="{{ old('district_id', isset($student_detail) ? $student_detail->district_id : '') }}">
                                            <select name="district_id" id="district_id" class="form-control district_id @error('district_id') is-invalid @enderror" required>
                                                <option value="">Select District</option>
                                            </select>
                                            @error('district_id')
                                                <span class="invalid-feedback form-invalid fw-bold" role="alert">{{ $message }}</span>
                                            @enderror
                                        </div>
                                        <div class="form-group col-md-4">
                                            <label for="pincode">Pincode <span class="text-danger">*</span></label>
                                            <input type="text" name="pincode" id="pincode" placeholder="Enter Pincode" class="form-control @error('pincode') is-invalid @enderror" value="{{ old('pincode', isset($student_detail) ? $student_detail->pincode : '') }}" required>
                                            @error('pincode')
                                                <span class="invalid-feedback form-invalid fw-bold" role="alert">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="form-group col-md-12">
                                            <label for="address">Address <span class="text-danger">*</span></label>
                                            <textarea name="address" id="address" placeholder="Enter Address" class="form-control @error('address') is-invalid @enderror" rows="3" required>{{ old('address', isset($student_detail) ? $student_detail->address : '') }}</textarea>
                                            @error('address')
                                                <span class="invalid-feedback form-invalid fw-bold" role="alert">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>

                                </div>
                                <div class="form-step" id="step3" style="display: none;">
                                    <!-- Parents Details -->
                                    <h5 class="mt-4">Parents Details</h5>
                                    <div class="row">
                                        <div class="form-group col-md-4">
                                            <label for="f_name">Father's Name <span class="text-danger">*</span></label>
                                            <input type="text" name="f_name" id="f_name" placeholder="Enter Father's Name" class="form-control @error('f_name') is-invalid @enderror" value="{{ old('f_name', isset($parent_detail) ? $parent_detail->f_name : '') }}" required>
                                            @error('f_name')
                                                <span class="invalid-feedback form-invalid fw-bold" role="alert">{{ $message }}</span>
                                            @enderror
                                        </div>
                                        <div class="form-group col-md-4">
                                            <label for="g_father">Grandfather's Name <span class="text-danger">*</span></label>
                                            <input type="text" name="g_father" id="g_father" placeholder="Enter Grandfather's Name" class="form-control @error('g_father') is-invalid @enderror" value="{{ old('g_father', isset($parent_detail) ? $parent_detail->g_father : '') }}" required>
                                            @error('g_father')
                                                <span class="invalid-feedback form-invalid fw-bold" role="alert">{{ $message }}</span>
                                            @enderror
                                        </div>
                                        <div class="form-group col-md-4">
                                            <label for="f_mobile">Father's Mobile <span class="text-danger">*</span></label>
                                            <input type="tel" name="f_mobile" id="f_mobile" placeholder="Enter Father's Mobile" class="form-control @error('f_mobile') is-invalid @enderror" value="{{ old('f_mobile', isset($parent_detail) ? $parent_detail->f_mobile : '') }}">
                                            @error('f_mobile')
                                                <span class="invalid-feedback form-invalid fw-bold" role="alert">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="form-group col-md-4">
                                            <label for="m_name">Mother's Name <span class="text-danger">*</span></label>
                                            <input type="text" name="m_name" id="m_name" placeholder="Enter Mother's Name" class="form-control @error('m_name') is-invalid @enderror" value="{{ old('m_name', isset($parent_detail) ? $parent_detail->m_name : '') }}">
                                            @error('m_name')
                                                <span class="invalid-feedback form-invalid fw-bold" role="alert">{{ $message }}</span>
                                            @enderror
                                        </div>
                                        <div class="form-group col-md-4">
                                            <label for="m_mobile">Mother's Mobile <span class="text-danger">*</span></label>
                                            <input type="tel" name="m_mobile" id="m_mobile" placeholder="Enter Mother's Mobile" class="form-control @error('m_mobile') is-invalid @enderror" value="{{ old('m_mobile', isset($parent_detail) ? $parent_detail->m_mobile : '') }}">
                                            @error('m_mobile')
                                                <span class="invalid-feedback form-invalid fw-bold" role="alert">{{ $message }}</span>
                                            @enderror
                                        </div>
                                        <div class="form-group col-md-4">
                                            <label for="parent_category_id">Category <span class="text-danger">*</span></label>
                                            <select name="parent_category_id" id="parent_category_id" class="form-control @error('parent_category_id') is-invalid @enderror" required>
                                                <option value="">Select Category</option>
                                                <option value="1" {{ old('parent_category_id', isset($parent_detail) ? $parent_detail->category_id : '') == 1 ? 'selected' : '' }}>General</option>
                                                <option value="2" {{ old('parent_category_id', isset($parent_detail) ? $parent_detail->category_id : '') == 2 ? 'selected' : '' }}>OBC</option>
                                                <option value="3" {{ old('parent_category_id', isset($parent_detail) ? $parent_detail->category_id : '') == 3 ? 'selected' : '' }}>SC</option>
                                                <option value="4" {{ old('parent_category_id', isset($parent_detail) ? $parent_detail->category_id : '') == 4 ? 'selected' : '' }}>ST</option>
                                                <option value="5" {{ old('parent_category_id', isset($parent_detail) ? $parent_detail->category_id : '') == 5 ? 'selected' : '' }}>BC</option>
                                            </select>
                                            @error('parent_category_id')
                                                <span class="invalid-feedback form-invalid fw-bold" role="alert">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="form-group col-md-4">
                                            <label for="f_occupation">Father's Occupation <span class="text-danger">*</span></label>
                                            {{-- <input type="text" name="f_occupation" id="f_occupation"
                                                class="form-control @error('f_occupation') is-invalid @enderror"
                                                value="{{ old('f_occupation', isset($parent_detail) ? $parent_detail->f_occupation : '') }}"
                                                required> --}}
                                            <select name="f_occupation" id="f_occupation" class="form-control @error('f_occupation') is-invalid @enderror" required>
                                                <option value="">Select Father's Occupation</option>
                                                <option value="1" {{ old('f_occupation', isset($parent_detail) ? $parent_detail->f_occupation : '') == 1 ? 'selected' : '' }}>Private Service</option>
                                                <option value="2" {{ old('f_occupation', isset($parent_detail) ? $parent_detail->f_occupation : '') == 2 ? 'selected' : '' }}>Govt. Service</option>
                                                <option value="3" {{ old('f_occupation', isset($parent_detail) ? $parent_detail->f_occupation : '') == 3 ? 'selected' : '' }}>Farmer</option>
                                                <option value="4" {{ old('f_occupation', isset($parent_detail) ? $parent_detail->f_occupation : '') == 4 ? 'selected' : '' }}>Business</option>
                                                <option value="5" {{ old('f_occupation', isset($parent_detail) ? $parent_detail->f_occupation : '') == 5 ? 'selected' : '' }}>Military Service</option>
                                            </select>
                                            @error('f_occupation')
                                                <span class="invalid-feedback form-invalid fw-bold" role="alert">{{ $message }}</span>
                                            @enderror
                                        </div>
                                        <div class="form-group col-md-4">
                                            <label for="m_occupation">Mother's Occupation <span class="text-danger">*</span></label>
                                            {{-- <input type="text" name="m_occupation" id="m_occupation"
                                                class="form-control @error('m_occupation') is-invalid @enderror"
                                                value="{{ old('m_occupation', isset($parent_detail) ? $parent_detail->m_occupation : '') }}"
                                                required> --}}
                                            <select name="m_occupation" id="m_occupation" class="form-control @error('m_occupation') is-invalid @enderror" required>
                                                <option value="">Select Mother's Occupation</option>
                                                <option value="1" {{ old('m_occupation', isset($parent_detail) ? $parent_detail->m_occupation : '') == 1 ? 'selected' : '' }}>Private Service</option>
                                                <option value="2" {{ old('m_occupation', isset($parent_detail) ? $parent_detail->m_occupation : '') == 2 ? 'selected' : '' }}>Govt. Service</option>
                                                <option value="3" {{ old('m_occupation', isset($parent_detail) ? $parent_detail->m_occupation : '') == 3 ? 'selected' : '' }}>House Wife</option>
                                                <option value="4" {{ old('m_occupation', isset($parent_detail) ? $parent_detail->m_occupation : '') == 4 ? 'selected' : '' }}>Business</option>
                                                <option value="5" {{ old('m_occupation', isset($parent_detail) ? $parent_detail->m_occupation : '') == 5 ? 'selected' : '' }}> Military Service</option>
                                            </select>
                                            @error('m_occupation')
                                                <span class="invalid-feedback form-invalid fw-bold" role="alert">{{ $message }}</span>
                                            @enderror
                                        </div>
                                        <div class="form-group col-md-4">
                                            <label for="parent_email">Email <span class="text-danger">*</span></label>
                                            <input type="email" name="parent_email" id="parent_email" placeholder="Enter Email" class="form-control @error('parent_email') is-invalid @enderror" value="{{ old('parent_email', isset($parent_detail) ? $parent_detail->email : '') }}" required>
                                            @error('parent_email')
                                                <span class="invalid-feedback form-invalid fw-bold"
                                                    role="alert">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="form-group col-md-4">
                                            <label for="parent_state_id">State <span class="text-danger">*</span></label>
                                            <select name="parent_state_id" id="parent_state_id" class="form-control state_id @error('parent_state_id') is-invalid @enderror" required>
                                                <option value="">Select State</option>
                                                @if (count($states) > 0)
                                                    @foreach ($states as $key => $state)
                                                        <option value="{{ $key }}" {{ old('parent_state_id', isset($parent_detail) ? $parent_detail->state_id : '') == $key ? 'selected' : ''}}>{{ $state }}</option>
                                                    @endforeach
                                                @else
                                                    <option value="">No State Found</option>
                                                @endif
                                            </select>
                                            @error('parent_state_id')
                                                <span class="invalid-feedback form-invalid fw-bold" role="alert">{{ $message }}</span>
                                            @enderror
                                            <img src="{{ config('myconfig.myloader') }}" alt="Loading..." class="loader" id="loader" style="display:none; width:10%;">
                                        </div>

                                        <div class="form-group col-md-4">
                                            <label for="parent_district_id">District <span class="text-danger">*</span></label>
                                            <input type="hidden" name="initialParentDistrictId" id="initialParentDistrictId" value="{{ old('parent_district_id', isset($parent_detail) ? $parent_detail->district_id : '') }}">
                                            <select name="parent_district_id" id="parent_district_id" class="form-control district_id @error('parent_district_id') is-invalid @enderror" required>
                                                <option value="">Select District</option>
                                            </select>
                                            @error('parent_district_id')
                                                <span class="invalid-feedback form-invalid fw-bold"
                                                    role="alert">{{ $message }}</span>
                                            @enderror
                                        </div>
                                        <div class="form-group col-md-4">
                                            <label for="pin_code">Pin Code <span class="text-danger">*</span></label>
                                            <input type="text" name="pin_code" id="pin_code" placeholder="Enter Pin Code" class="form-control @error('pin_code') is-invalid @enderror" value="{{ old('pin_code', isset($parent_detail) ? $parent_detail->pin_code : '') }}" required>
                                            @error('pin_code')
                                                <span class="invalid-feedback form-invalid fw-bold" role="alert">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="form-group col-md-12">
                                            <label for="parent_address">Address <span class="text-danger">*</span></label>
                                            <textarea name="parent_address" placeholder="Enter Address" id="parent_address" class="form-control @error('parent_address') is-invalid @enderror" rows="3" required>{{ old('parent_address', isset($parent_detail) ? $parent_detail->address : '') }}</textarea>
                                            @error('parent_address')
                                                <span class="invalid-feedback form-invalid fw-bold" role="alert">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>

                                </div>
                                <!-- Navigation Buttons -->
                                <div class="form-navigation">
                                    <button type="button" id="prevBtn" class="btn btn-secondary" style="display: none;">Previous</button>
                                    <button type="button" id="nextBtn" class="btn btn-primary mx-2 next-btn-std" style="float: right;">Next</button>
                                    <button type="button" id="saveNextBtn" class="btn btn-primary save-next-btn" style="float: right;">Save & Next</button>
                                </div>
                            </form>
                        </div>
                    </div>


                </div>
            </div>

        </div>
    </div>
@endsection
@section('std-scripts')
    <script>
        $(document).ready(function() {
            let initialClassId = $('#class_id').val();
            let initialSectionId = $('#initialSectionId').val();
            let initialStDistrictId = $('#initialDistrictId').val();
            let initialParentDistrictId = $('#initialParentDistrictId').val();
            let stateStSelect = $('#state_id');
            let districtStSelect = $('#district_id');
            let stateParentSelect = $('#parent_state_id');
            let districtParentSelect = $('#parent_district_id');

            getClassSection(initialClassId, initialSectionId);
            getStateDistrict(stateStSelect, stateStSelect.val(), districtStSelect, initialStDistrictId);
            getStateDistrict(stateParentSelect, stateParentSelect.val(), districtParentSelect, initialParentDistrictId);


            if ($('#transport').val() == "0") {
                $("#trans_discount").removeAttr("required");
                $("#trans_2nd_inst").removeAttr("required");
                $("#trans_1st_inst").removeAttr("required");
                $("#transportFeeDiv").hide();
            } else {
                $("#transportFeeDiv").show();
            }
            $('#transport').on('change', function() {
                if ($(this).val() === "0") {
                    $("#transportFeeDiv").hide();
                    $("#trans_discount").removeAttr("required");
                    $("#trans_discount").val('');
                    $("#trans_2nd_inst").removeAttr("required");
                    $("#trans_2nd_inst").val('');
                    $("#trans_1st_inst").removeAttr("required");
                    $("#trans_1st_inst").val('');
                } else {
                    $("#transportFeeDiv").show();
                }
            });
            //get installment fee total
            function calculateTotal() {
                let firstInstall = parseFloat($('#trans_1st_inst').val()) || 0;
                let secondInstall = parseFloat($('#trans_2nd_inst').val()) || 0;
                let discount = parseFloat($('#trans_discount').val()) || 0;

                let total = firstInstall + secondInstall - discount;

                $('#trans_total').val(total.toFixed(2));
            }

            $('#trans_1st_inst ,#trans_2nd_inst, #trans_discount').on('input', calculateTotal);

            const form = $('#multiStepForm');
            const id = $('#id');
            const totalSteps = 3;
            let currentStep = 1;

            const showStep = (step) => {
                $('.form-step').each((index, element) => {
                    $(element).toggle(index + 1 == step);
                });
                $('#prevBtn').toggle(step > 1);
                $('#saveNextBtn').toggle(step < totalSteps);

                $('#nextBtn').text(step == totalSteps ? (id.val().trim() !== '' ? 'Update' : 'Submit') :
                    'Next');
            };

            const validateStep = (step) => {
                let isValid = true;

                $(`#step${step} input, #step${step} select, #step${step} textarea`).each((index, field) => {
                    if (!validateField($(field))) {
                        isValid = false;
                    }
                });

                return isValid;
            };

            const validateField = ($field) => {
                let isValid = true;
                clearErrors($field);

                if ($field.prop('required') && !$field.val()) {
                    isValid = false;
                    showError($field, 'This field is required.');
                } else if ($field.attr('type') == 'email' && !validateEmail($field.val())) {
                    isValid = false;
                    showError($field, 'Please enter a valid email address.');
                } else if ($field.attr('type') == 'tel' && $field.attr('id') !== 'm_mobile' && $field.attr('id') !== 'f_mobile' && !
                    validatePhoneNumber($field.val())) {
                    isValid = false;
                    showError($field, 'Please enter a valid phone number.');
                } else if ($field.attr('id') == 'rollno' && !/^\d+$/.test($field.val())) {
                    isValid = false;
                    showError($field, 'Only numbers are allowed.');
                } else if ($field.attr('id') == 'trans_1st_inst' && !/^-?\d*(\.\d+)?$/.test($field.val())) {
                    isValid = false;
                    showError($field, 'Only numbers are allowed.');
                } else if ($field.attr('id') == 'trans_2nd_inst' && !/^-?\d*(\.\d+)?$/.test($field.val())) {
                    isValid = false;
                    showError($field, 'Only numbers are allowed.');
                } else if ($field.attr('id') == 'trans_discount' && !/^-?\d*(\.\d+)?$/.test($field.val())) {
                    isValid = false;
                    showError($field, 'Only numbers are allowed.');
                } else if ($field.attr('id') == 'trans_total' && !/^-?\d*(\.\d+)?$/.test($field.val())) {
                    isValid = false;
                    showError($field, 'Only numbers are allowed.');
                } else if (($field.attr('id') == 'pincode' || $field.attr('id') == 'pin_code') && !
                    /^[0-9]{6}$/.test($field.val())) {
                    isValid = false;
                    showError($field, 'Please Enter valid pincode');
                } else if ($field.attr('type') == 'text' && $field.attr('maxlength') > 0 && $field.val()
                    .length > $field.attr('maxlength')) {
                    isValid = false;
                    showError($field, `Maximum length is ${$field.attr('maxlength')} characters.`);
                }

                return isValid;
            };

            const showError = ($field, message) => {
                $field.addClass('is-invalid');
                let $errorSpan = $field.next('.invalid-feedback');
                if (!$errorSpan.length) {
                    $errorSpan = $('<span>', {
                        class: 'invalid-feedback form-invalid fw-bold'
                    });
                    $field.after($errorSpan);
                }
                $errorSpan.text(message);
            };

            const clearErrors = ($field) => {
                $field.removeClass('is-invalid');
                $field.next('.invalid-feedback').text('');
            };

            const validateEmail = (email) => {
                const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                return regex.test(email);
            };

            const validatePhoneNumber = (phone) => {
                const regex = /^[0-9]{10}$/;
                return regex.test(phone);
            };


            const addRealTimeValidation = (step) => {
                $(`#step${step} input, #step${step} select, #step${step} textarea`).on('input', function() {
                    validateField($(this));
                });
            };

            $('#nextBtn').on('click', function() {
                if (validateStep(currentStep)) {
                    if (currentStep < totalSteps) {
                        currentStep++;
                        showStep(currentStep);
                        addRealTimeValidation(currentStep);
                    } else {
                        form.submit();
                    }
                }
            });
            $('#saveNextBtn').on('click', function(e) {
                e.preventDefault();
                if (validateStep(currentStep)) {
                    form.submit();
                } else {
                    console.log("Validation failed. Please correct the errors.");
                }
            });

            $('#prevBtn').on('click', function() {
                if (currentStep > 1) {
                    currentStep--;
                    showStep(currentStep);
                    addRealTimeValidation(currentStep);
                }
            });

            showStep(currentStep);
            addRealTimeValidation(currentStep);
        });
    </script>
@endsection
