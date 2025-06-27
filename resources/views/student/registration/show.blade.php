@extends('student.index')
@section('sub-content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card">
                        <div class="card-header">
                            {{ __('View Student') }}
                            <a class="btn btn-warning btn-sm" style="float: right;" id="btn-back"
                                onclick="history.back()">Back</a>
                        </div>

                        <div class="card-body">
                            <h5>Student Main Information</h5>
                            <div class="row">
                                <div class="d-flex col-md-12 justify-content-around">
                                    <div class="p-2 flex-fill border border-warning rounded">
                                        <h6 class="fw-bold">SR No.</h6>
                                        <p class="mb-0">{{ isset($student) ? $student->srno : '' }}</p>
                                    </div>
                                    <div class="p-2 flex-fill border border-warning rounded">
                                        <h6 class="fw-bold">School</h6>
                                        <p class="mb-0">
                                            {{ isset($student) && $student->school == 1 ? 'Play House' : 'Public' }}</p>
                                    </div>
                                    <div class="p-2 flex-fill border border-warning rounded">
                                        <h6 class="fw-bold">Previous SR No.</h6>
                                        <p class="mb-0">{{ isset($student) ? $student->prev_srno : '-' }}</p>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="d-flex justify-content-around">
                                    <div class="p-2 flex-fill border border-warning rounded">
                                        <h6 class="fw-bold">Class</h6>
                                        {{ isset($class) ? $class->class : '' }}
                                    </div>
                                    <div class="p-2 flex-fill border border-warning rounded">
                                        <h6 class="fw-bold">Section</h6>{{ isset($section) ? $section->section : '' }}
                                    </div>
                                    <div class="p-2 flex-fill border border-warning rounded">
                                        <h6 class="fw-bold">Roll No.</h6>{{ isset($student) ? $student->rollno : '' }}
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="d-flex justify-content-around">
                                    <div class="p-2 flex-fill border border-warning rounded">
                                        <h6 class="fw-bold">Transport</h6>
                                        {{ isset($student) && $student->transport == 1 ? 'Yes' : 'No' }}
                                    </div>
                                    {{-- <div class="p-2 flex-fill border border-warning rounded">
                                        <h6 class="fw-bold">Age Proof</h6>
                                    </div> --}}
                                    <div class="p-2 flex-fill border border-warning rounded">
                                        <h6 class="fw-bold">Age Proof</h6>
                                        {{ isset($student)
                                            ? ($student->age_proof == 0
                                                ? 'No Age Proof'
                                                : ($student->age_proof == 1
                                                    ? 'Transfer Certificate (T.C.)'
                                                    : ($student->age_proof == 2
                                                        ? 'Birth Certificate'
                                                        : ($student->age_proof == 3
                                                            ? 'Affidavit'
                                                            : 'Aadhar Card'))))
                                            : 'Age Proof Not Available' }}
                                    </div>

                                    <div class="p-2 flex-fill border border-warning rounded">
                                        <h6 class="fw-bold">Gender</h6>
                                        {{ isset($student) ? ($student->gender == 1 ? 'Male' : ($student->gender == 2 ? 'Female' : ($student->gender == 3 ? 'Other' : ''))) : '' }}

                                    </div>

                                </div>
                            </div>
                            <div class="row">
                                <div class="d-flex justify-content-around">

                                    <div class="p-2 flex-fill border border-warning rounded">
                                        <h6 class="fw-bold">Religion</h6>
                                        {{ isset($student) ? ($student->religion == 1 ? 'Hindu' : ($student->religion == 2 ? 'Muslim' : ($student->religion == 3 ? 'Christian' : 'Sikh'))) : '' }}

                                    </div>
                                    <div class="p-2 flex-fill border border-warning rounded">
                                        <h6 class="fw-bold">Admission Date</h6>
                                        {{ isset($student) ? $student->admission_date : '' }}

                                    </div>


                                </div>
                            </div>
                            <div class="row">
                                <div class="d-flex justify-content-around">

                                    <div class="p-2 flex-fill border border-warning rounded">
                                        <h6 class="fw-bold">1st Installment</h6>
                                        {{ isset($student) ? $student->trans_1st_inst : '-' }}
                                    </div>
                                    <div class="p-2 flex-fill border border-warning rounded">
                                        <h6 class="fw-bold">2nd Installment</h6>
                                        {{ isset($student) ? $student->trans_2nd_inst : '-' }}
                                    </div>
                                    <div class="p-2 flex-fill border border-warning rounded">
                                        <h6 class="fw-bold">Discount</h6>
                                        {{ isset($student) ? $student->trans_discount : '-' }}
                                    </div>
                                    <div class="p-2 flex-fill border border-warning rounded">
                                        <h6 class="fw-bold">Total</h6>
                                        {{ isset($student) ? $student->trans_total : '-' }}
                                    </div>

                                    `
                                </div>
                            </div>
                            <h5 class="mt-4">Student Details</h5>
                            <div class="row">
                                <div class="d-flex justify-content-around">

                                    <div class="p-2 flex-fill border border-warning rounded">
                                        <h6 class="fw-bold">Name</h6>
                                        {{ isset($student_detail) ? $student_detail->name : '-' }}
                                    </div>
                                    <div class="p-2 flex-fill border border-warning rounded">
                                        <h6 class="fw-bold">Date of Birth</h6>
                                        {{ isset($student_detail) ? $student_detail->dob : '-' }}
                                    </div>
                                    <div class="p-2 flex-fill border border-warning rounded">
                                        <h6 class="fw-bold">Email</h6>
                                        {{ isset($student_detail) ? $student_detail->email : '-' }}
                                    </div>
                                    <div class="p-2 flex-fill border border-warning rounded">
                                        <h6 class="fw-bold">Mobile</h6>
                                        {{ isset($student_detail) ? $student_detail->mobile : '-' }}
                                    </div>
                                    <div class="p-2 flex-fill border border-warning rounded">
                                        <h6 class="fw-bold">Category</h6>
                                        {{ isset($student_detail) ? ($student_detail->category_id == 1 ? 'General' : ($student->category_id == 2 ? 'OBC' : ($student->category_id == 3 ? 'SC' : ($student->category_id == 4 ? 'ST' : 'BC')))) : '' }}
                                    </div>


                                </div>
                            </div>
                            <div class="row">
                                <div class="d-flex justify-content-around">
                                    <div class="p-2 flex-fill border border-warning rounded">
                                        <h6 class="fw-bold">Previous School</h6>
                                        {{ isset($student_detail) ? $student_detail->pre_school : '' }}
                                    </div>
                                    <div class="p-2 flex-fill border border-warning rounded">
                                        <h6 class="fw-bold">Previous Class</h6>
                                        {{ isset($student_detail) ? $student_detail->pre_class : '' }}
                                    </div>
                                    <div class="p-2 flex-fill border border-warning rounded">
                                        <h6 class="fw-bold">Reason</h6>
                                        {{ isset($student) ? $student->reason : '' }}
                                    </div>
                                    <div class="p-2 flex-fill border border-warning rounded">
                                        <h6 class="fw-bold">TC Reference No.</h6>
                                        {{ isset($student) ? $student->TCRefNo : '' }}
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="d-flex justify-content-around">
                                    <div class="p-2 flex-fill border border-warning rounded">
                                        <h6 class="fw-bold">State</h6>
                                        {{ isset($state) ? $state->name : '' }}
                                    </div>
                                    <div class="p-2 flex-fill border border-warning rounded">
                                        <h6 class="fw-bold">District</h6>
                                        {{ isset($district) ? $district->name : '' }}
                                    </div>
                                    <div class="p-2 flex-fill border border-warning rounded">
                                        <h6 class="fw-bold">Pincode</h6>
                                        {{ isset($student_detail) ? $student_detail->pincode : '' }}
                                    </div>
                                    <div class="p-2 flex-fill border border-warning rounded">
                                        <h6 class="fw-bold">Address</h6>
                                        {{ isset($student_detail) ? $student_detail->address : '' }}
                                    </div>
                                </div>
                            </div>
                            <h5 class="mt-4">Parents Details</h5>
                            <div class="row">
                                <div class="d-flex justify-content-around">

                                    <div class="p-2 flex-fill border border-warning rounded">
                                        <h6 class="fw-bold">Father's Name</h6>
                                        {{ isset($parent_detail) ? $parent_detail->f_name : '' }}
                                    </div>
                                    <div class="p-2 flex-fill border border-warning rounded">
                                        <h6 class="fw-bold">Grandfather's Name</h6>
                                        {{ isset($parent_detail) ? $parent_detail->g_father : '' }}
                                    </div>
                                    <div class="p-2 flex-fill border border-warning rounded">
                                        <h6 class="fw-bold">Father's Mobile</h6>
                                        {{ isset($parent_detail) ? $parent_detail->f_mobile : '' }}
                                    </div>

                                </div>
                            </div>
                            <div class="row">
                                <div class="d-flex justify-content-around">

                                    <div class="p-2 flex-fill border border-warning rounded">
                                        <h6 class="fw-bold">Mother's Name</h6>
                                        {{ isset($parent_detail) ? $parent_detail->m_name : '' }}
                                    </div>
                                    <div class="p-2 flex-fill border border-warning rounded">
                                        <h6 class="fw-bold">Mother's Mobile</h6>
                                        {{ isset($parent_detail) ? $parent_detail->m_mobile : '' }}
                                    </div>
                                    <div class="p-2 flex-fill border border-warning rounded">
                                        <h6 class="fw-bold">Category</h6>
                                        {{ isset($parent_detail) ? ($parent_detail->category_id == 1 ? 'General' : ($parent_detail->category_id == 2 ? 'OBC' : ($parent_detail->category_id == 3 ? 'SC' : ($parent_detail->category_id == 4 ? 'ST' : 'BC')))) : '' }}
                                    </div>

                                </div>
                            </div>
                            <div class="row">
                                <div class="d-flex justify-content-around">

                                    <div class="p-2 flex-fill border border-warning rounded">
                                        <h6 class="fw-bold">Father's Occupation</h6>
                                        {{ isset($parent_detail) ? ($parent_detail->f_occupation == 1 ? 'Private Service' : ($parent_detail->f_occupation == 2 ? 'Govt. Service' : ($parent_detail->f_occupation == 3 ? 'Farmer' : ($parent_detail->f_occupation == 4 ? 'Business' : 'Military Service')))) : '' }}
                                    </div>
                                    <div class="p-2 flex-fill border border-warning rounded">
                                        <h6 class="fw-bold">Mother's Occupation</h6>
                                        {{ isset($parent_detail) ? ($parent_detail->m_occupation == 1 ? 'Private Service' : ($parent_detail->m_occupation == 2 ? 'Govt. Service' : ($parent_detail->m_occupation == 3 ? 'House Wife' : ($parent_detail->m_occupation == 4 ? 'Business' : 'Military Service')))) : '' }}
                                    </div>
                                    <div class="p-2 flex-fill border border-warning rounded">
                                        <h6 class="fw-bold">Email</h6>
                                        {{ isset($parent_detail) ? $parent_detail->email : '' }}
                                    </div>

                                </div>
                            </div>
                            <div class="row">
                                <div class="d-flex justify-content-around">
                                    <div class="p-2 flex-fill border border-warning rounded">
                                        <h6 class="fw-bold">State</h6>
                                        {{ isset($state) ? $state->name : '' }}
                                    </div>
                                    <div class="p-2 flex-fill border border-warning rounded">
                                        <h6 class="fw-bold">District</h6>
                                        {{ isset($district) ? $district->name : '' }}
                                    </div>
                                    <div class="p-2 flex-fill border border-warning rounded">
                                        <h6 class="fw-bold">Pincode</h6>
                                        {{ isset($parent_detail) ? $parent_detail->pin_code : '' }}
                                    </div>
                                    <div class="p-2 flex-fill border border-warning rounded">
                                        <h6 class="fw-bold">Address</h6>
                                        {{ isset($parent_detail) ? $parent_detail->address : '' }}
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
@endsection
