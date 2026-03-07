@extends('fee.index')
@section('sub-content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card border-0 bg-white">
                <div class="card-header flex-wrap bg-white d-flex align-items-center justify-content-between"><h5 class="mb-0 mt-0">{{ 'Fee Details' }}</h5>
                    <a href="{{ route('fee.fee-detail') }}" class="btn bg-light btn-sm" ><span class="mdi mdi-chevron-left me-2"></span>Back</a>
                </div>
                <div class="card-body">
                    <form id="class-section-form">
                        <div class="row">
                           <div class="form-group col-md-6">
                                <label for="fee_class_id" class="mt-2">Class <span class="text-danger">*</span></label>
                                <select name="class" id="fee_class_id" class="form-control" {{ count($classes) == 0 ? 'disabled' : 'required' }}>
                                    @if (count($classes) > 0)
                                        <option value="">Select Class</option>
                                        @foreach ($classes as $key => $class)
                                            <option value="{{ $key }}" {{ request()->get('class') == $key ? 'selected' : '' }}>{{ $class }}</option>
                                        @endforeach
                                    @else
                                        <option value="" selected disabled>No Class Found</option>
                                    @endif
                                </select>
                                <span class="invalid-feedback form-invalid fw-bold" id="class-error" role="alert"></span>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="fee_section_id" class="mt-2">Section <span class="text-danger">*</span></label>
                                <select name="section" id="fee_section_id" class="form-control" required>
                                    <option value="">Select Section</option>
                                </select>
                                <span class="invalid-feedback form-invalid fw-bold" id="section-error" role="alert"></span>
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group col-md-6">
                                <label for="fee_student_id" class="mt-2">Student <span class="text-danger">*</span></label>
                                <select name="std_id" id="fee_student_id" class="form-control " required>
                                    <option value="">Select Student</option>
                                </select>
                                <span class="invalid-feedback form-invalid fw-bold" id="std-error" role="alert"></span>
                            </div>
                        </div>

                        <div class="mt-3">
                            <button type="button" id="show-details" class="btn btn-primary">Show Details</button>
                            <span><img src="{{ config('myconfig.myloader') }}" alt="Loading..." class="loader" id="loader" style="display:none; width:5%;"></span>
                        </div>

                    </form>

                    <div id="std-fee-due-table" class="table-responsive mt-5" style="display: none;">
                        <table class="table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th colspan="10">Academic Fee Due Details</th>
                                </tr>
                                <tr>

                                    <th></th>
                                    <th>Admission Fee</th>
                                    <th>Ist Installment</th>
                                    <th>IInd Installment</th>
                                    <th>Complete Fee</th>
                                    <th>Mercy Fee</th>
                                    <th>Status</th>
                                    <th>Pay Date</th>
                                    <th>Recp. No.</th>
                                    <th>Ref. Slip No.</th>
                                </tr>
                            </thead>
                            <tbody class="table-group-divider">
                            </tbody>
                            <tfoot class="footer table-group-divider">
                            </tfoot>
                        </table>
                    </div>
                    <div id="std-transport-fee-due-table" class="table-responsive" style="display: none;">
                        <table class="table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th colspan="9">Transport Fee Due Details</th>
                                </tr>
                                <tr>
                                    <th></th>
                                    <th>Ist Installment</th>
                                    <th>IInd Installment</th>
                                    <th>Complete Fee</th>
                                    <th>Mercy Fee</th>
                                    <th>Status</th>
                                    <th>Pay Date</th>
                                    <th>Recp. No.</th>
                                    <th>Ref. Slip No.</th>
                                </tr>
                            </thead>
                            <tbody>
                            </tbody>
                            <tfoot class="footerTrans table-group-divider">
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
@section('fee-scripts')
    <script>
        $(document).ready(function() {
            let classId = $('#fee_class_id');
            let sectionId = $('#fee_section_id');

            if (classId.val() && classId.val() != '') {
                getFeeWithoutAllSections(classId.val(), function() {
                    let selectedSection = sectionId.val();
                    if (!selectedSection || selectedSection == '') {
                        sectionId.val('');
                        selectedSection = '';
                    }
                    getFeeStudentsWithoutAll(classId.val(), selectedSection);
                });
            }

            classId.change(function() {
                let selectedClass = $(this).val();
                $('#std-fee-due-table').hide();
                $('#std-fee-due-table table tbody').html('');
                $('#std-transport-fee-due-table').hide();
                $('#std-transport-fee-due-table table tbody').html('');
                getFeeWithoutAllSections(selectedClass, function() {
                    sectionId.val('');
                    getFeeStudentsWithoutAll(selectedClass, '');
                });
            });

            sectionId.change(function() {
                $('#std-fee-due-table').hide();
                $('#std-fee-due-table table tbody').html('');
                $('#std-transport-fee-due-table').hide();
                $('#std-transport-fee-due-table table tbody').html('');
                getFeeStudentsWithoutAll(classId.val(), $(this).val());
            });

            $('#fee_student_id').change(function() {
                $('#std-fee-due-table').hide();
                $('#std-fee-due-table table tbody').html('');
                $('#std-transport-fee-due-table').hide();
                $('#std-transport-fee-due-table table tbody').html('');
            });

            function getFeeData(st, classId, sectionId) {
                $('#std-fee-due-table').hide();
                $('#std-fee-due-table table tbody').html('');
                $('#std-transport-fee-due-table').hide();
                $('#std-transport-fee-due-table table tbody').html('');

                if (st !== '' && classId !== '' && sectionId !== '') {
                    $('#std-fee-due-table').show();
                    $('#std-fee-due-table table tbody').html('');
                    $('#std-transport-fee-due-table').show();
                    $('#std-transport-fee-due-table table tbody').html('');

                    $.ajax({
                        url: '{{ route("fee.fee-detail.get") }}',
                        type: 'POST',
                        dataType: 'JSON',
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        data: {
                            srno: st,
                            class: classId,
                            section: sectionId,
                        },
                        success: function (response) {
                            if (response.status === 'success' && response.data) {
                                let stdHtml = '';
                                let footerstdHtml = '';
                                let transportHtml = '';
                                let footerTransHtml = '';

                                const student = response.data;
                                const academic = student.academic;
                                const transport = student.transport;

                                // Academic Fees Section
                                const admissionFeeDisplay = student.is_new_admission ? academic.admission_fee : 'Not Applicable';

                                // Get all academic installments
                                const admissionFeePayments = academic.installments.admission_fee || [];
                                const firstInst = academic.installments.first_inst || [];
                                const secondInst = academic.installments.second_inst || [];
                                const completeInst = academic.installments.complete_inst || [];
                                const mercy = academic.installments.mercy || [];

                                // Combine all installments with type
                                const allInstallments = [
                                    ...admissionFeePayments.map(inst => ({...inst, type: 'admission'})),
                                    ...firstInst.map(inst => ({...inst, type: 'first'})),
                                    ...secondInst.map(inst => ({...inst, type: 'second'})),
                                    ...completeInst.map(inst => ({...inst, type: 'complete'})),
                                    ...mercy.map(inst => ({...inst, type: 'mercy'}))
                                ];

                                // Payable Fee Row
                                stdHtml += `<tr>
                                                <td>Payable Fee</td>
                                                <td>${admissionFeeDisplay}</td>
                                                <td>${academic.inst_1}</td>
                                                <td>${academic.inst_2}</td>
                                                <td>${academic.inst_total}</td>
                                                <td>-</td>
                                                <td>-</td>
                                                <td>-</td>
                                                <td>-</td>
                                                <td>-</td>
                                            </tr>`;

                                // Paid Fee Rows - one row per payment
                                if (allInstallments.length > 0) {
                                    allInstallments.forEach((inst, index) => {
                                        stdHtml += `<tr>
                                                        <td>Paid Fee</td>
                                                        <td>${inst.type === 'admission' ? inst.amount : '-'}</td>
                                                        <td>${inst.type === 'first' ? inst.amount : '-'}</td>
                                                        <td>${inst.type === 'second' ? inst.amount : '-'}</td>
                                                        <td>${inst.type === 'complete' ? inst.amount : '-'}</td>
                                                        <td>${inst.type === 'mercy' ? inst.amount : '-'}</td>
                                                        <td>${inst.type === 'mercy' ? 'Mercy' : 'Paid'}</td>
                                                        <td>${inst.pay_date || '-'}</td>
                                                        <td>${inst.recp_no || '-'}</td>
                                                        <td>${inst.ref_slip_no || '-'}</td>
                                                    </tr>`;
                                    });
                                } else {
                                    stdHtml += `<tr>
                                                    <td>Paid Fee</td>
                                                    <td colspan="9" class="text-center">No payments made yet</td>
                                                </tr>`;
                                }

                                // Footer - Total Paid and Due
                                footerstdHtml += `<tr><td colspan="10" class="table-group-divider text-center fw-bold fs-5">Total</td></tr>`;
                                footerstdHtml += `<tr><td colspan="5" class="fw-bold">Paid</td><td colspan="5" class="fw-bold">Due</td></tr>`;
                                footerstdHtml += `<tr><td colspan="5">${academic.paid_amount}</td><td colspan="5">${academic.due_amount}</td></tr>`;

                                // Transport Fees Section
                                if (transport && transport.transport == 1) {
                                    const firstTransInst = transport.installments.first_inst || [];
                                    const secondTransInst = transport.installments.second_inst || [];
                                    const completeTransInst = transport.installments.complete_inst || [];
                                    const mercyTrans = transport.installments.mercy || [];

                                    const allTransInstallments = [
                                        ...firstTransInst.map(inst => ({...inst, type: 'first'})),
                                        ...secondTransInst.map(inst => ({...inst, type: 'second'})),
                                        ...completeTransInst.map(inst => ({...inst, type: 'complete'})),
                                        ...mercyTrans.map(inst => ({...inst, type: 'mercy'}))
                                    ];

                                    transportHtml += `<tr>
                                                        <td>Payable Transport Fee</td>
                                                        <td>${transport.inst_1}</td>
                                                        <td>${transport.inst_2}</td>
                                                        <td>${transport.inst_total}</td>
                                                        <td>-</td>
                                                        <td>-</td>
                                                        <td>-</td>
                                                        <td>-</td>
                                                        <td>-</td>
                                                    </tr>`;

                                    if (allTransInstallments.length > 0) {
                                        allTransInstallments.forEach((inst, index) => {
                                            transportHtml += `<tr>
                                                                <td>Paid Transport Fee</td>
                                                                <td>${inst.type === 'first' ? inst.amount : '-'}</td>
                                                                <td>${inst.type === 'second' ? inst.amount : '-'}</td>
                                                                <td>${inst.type === 'complete' ? inst.amount : '-'}</td>
                                                                <td>${inst.type === 'mercy' ? inst.amount : '-'}</td>
                                                                <td>${inst.type === 'mercy' ? 'Mercy' : 'Paid'}</td>
                                                                <td>${inst.pay_date || '-'}</td>
                                                                <td>${inst.recp_no || '-'}</td>
                                                                <td>${inst.ref_slip_no || '-'}</td>
                                                            </tr>`;
                                        });
                                    } else {
                                        transportHtml += `<tr>
                                                            <td>Paid Transport Fee</td>
                                                            <td colspan="8" class="text-center">No payments made yet</td>
                                                        </tr>`;
                                    }

                                    footerTransHtml += `<tr><td colspan="9" class="table-group-divider text-center fw-bold fs-5">Total</td></tr>`;
                                    footerTransHtml += `<tr><td colspan="5" class="fw-bold">Paid</td><td colspan="4" class="fw-bold">Due</td></tr>`;
                                    footerTransHtml += `<tr><td colspan="5">${transport.paid_amount}</td><td colspan="4">${transport.due_amount}</td></tr>`;
                                } else {
                                    transportHtml += '<tr><td colspan="9" class="text-center">No Transport Fee Applicable</td></tr>';
                                }

                                // Update tables
                                $('#std-fee-due-table table tbody').html(stdHtml);
                                $('.footer').html(footerstdHtml);
                                $('#std-transport-fee-due-table table tbody').html(transportHtml);
                                $('.footerTrans').html(footerTransHtml);

                            } else {
                                // Handle error response
                                $('#std-fee-due-table table tbody').html('<tr><td colspan="10" class="text-center text-danger">' + (response.message || 'No data found') + '</td></tr>');
                                $('#std-transport-fee-due-table table tbody').html('<tr><td colspan="9" class="text-center text-danger">' + (response.message || 'No data found') + '</td></tr>');
                            }
                        },
                        error: function (xhr) {
                            console.error(xhr.responseText);
                            let errorMsg = 'Failed to load fee details';
                            if (xhr.responseJSON && xhr.responseJSON.message) {
                                errorMsg = xhr.responseJSON.message;
                            }
                            $('#std-fee-due-table table tbody').html('<tr><td colspan="10" class="text-center text-danger">' + errorMsg + '</td></tr>');
                            $('#std-transport-fee-due-table table tbody').html('<tr><td colspan="9" class="text-center text-danger">' + errorMsg + '</td></tr>');
                        }
                    });
                } else {
                    $('#std-fee-due-table').hide();
                    $('#std-fee-due-table table tbody').html('');
                    $('#std-transport-fee-due-table').hide();
                    $('#std-transport-fee-due-table table tbody').html('');
                }
            }

            $('#show-details').click(function() {
                let selectedClassVal = classId.val();
                let selectedSectionVal = sectionId.val();
                let st = $('#fee_student_id').val();

                if(!selectedClassVal || !selectedSectionVal || !st) {
                    return;
                }

                getFeeData(st, selectedClassVal, selectedSectionVal);
            });


        });
    </script>
@endsection