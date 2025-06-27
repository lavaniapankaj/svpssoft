@extends('admin.index')
@section('sub-content')
    <div class="container">

        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="card">
                    <div class="card-header">
                        {{ 'Edit Mercy Fee (Both)' }}
                        <a href="{{ route('admin.editSection.index') }}" class="btn btn-warning btn-sm"
                            style="float: right;">Back</a>

                    </div>
                    <div class="card-body">
                        <form id="class-section-form">

                            <div class="row">
                                <div class="form-group col-md-4">
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
                                    <img src="{{ config('myconfig.myloader') }}" alt="Loading..." class="loader"
                                        id="loader" style="display:none; width:10%;">
                                </div>


                                <div class="form-group col-md-4">
                                    <label for="section_id" class="mt-2">Section <span
                                            class="text-danger">*</span></label>
                                    <input type="hidden" id="initialSectionId"
                                        value="{{ old('section') }}">
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
                                    <input type="hidden" name="current_session" value='' id="current_session">
                                    <label for="std_id" class="mt-2">Student<span class="text-danger">*</span></label>
                                    <select name="std_id" id="std_id"
                                        class="form-control mx-1 @error('std_id') is-invalid @enderror">
                                        <option value="">Select Student</option>
                                    </select>
                                    <span class="invalid-feedback form-invalid fw-bold student-error" role="alert"></span>

                                </div>
                            </div>


                            {{-- <div class="mt-3">
                                <button type="button" id="show-details" class="btn btn-primary">
                                    Show Details</button>
                            </div> --}}

                        </form>
                        <div id="std-fee-due-table" class="table-responsive mt-5">
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
                        <div id="std-transport-fee-due-table" class="table-responsive mt-4">
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

                        <div class="mercy-fee-form">
                            <form action="" method="post">
                                @csrf
                                <div class="row">
                                    <div class="form-group col-md-6">
                                        <label for="mercy_date" class="mt-2">Mercy Date <span
                                                class="text-danger">*</span></label>
                                        <input type="date" name="mercy_date" id="mercy_date" class="form-control" required>

                                        <span class="invalid-feedback form-invalid fw-bold mercy-date"
                                            role="alert"></span>


                                    </div>
                                    <div class="form-group col-md-6">
                                        <label for="amount" class="mt-2">Mercy Amount <span
                                                class="text-danger">*</span></label>
                                        <input type="text" name="amount" id="amount" class="form-control" required>
                                        <span class="invalid-feedback form-invalid fw-bold mercy-amount"
                                            role="alert"></span>


                                    </div>
                                </div>
                                <div class="mt-3">
                                    <button type="button" id="mercy-academic" class="btn btn-primary">Mercy Academic
                                        Fee</button>
                                    <button type="button" id="mercy-transport" value="" class="btn btn-primary">Mercy Transport
                                        Fee</button>
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
        $(document).ready(function() {
            let initialClassId = $('#class_id').val();
            let initialSectionId = $('#initialSectionId').val();
            getClassSection(initialClassId, initialSectionId);
            $('#std-form').hide();
            getStdDropdown();
            var stdFeeDueTable = $('#std-fee-due-table');
            var transportStdFeeDueTable = $('#std-transport-fee-due-table');
            stdFeeDueTable.hide();
            transportStdFeeDueTable.hide();
            let std = $('#std_id');
            let sessionID = $('#current_session').val();
            $('#class_id, #section_id').change(()=>{
                stdFeeDueTable.hide();
                transportStdFeeDueTable.hide();
            });
            std.change(function() {
                let st = $(this).val();
                let classID = $('#class_id').val();
                let sectionID = $('#section_id').val();
                adcademicAndTransportFeePopulate(st, sessionID, classID, sectionID);
            });
            $('#class-section-form').validate({
                rules: {
                    class: {
                        required: true,
                    },
                    section: {
                        required: true,
                    },
                    std_id: {
                        required: true,
                    },
                },
                messages: {
                    class: {
                        required: "Please select a class.",
                    },
                    section: {
                        required: "Please select a section.",
                    },
                    std_id: {
                        required: "Please select a student.",
                    },
                },
            });

            function submitForm(transport = '') {
                let classId = $('#class_id').val();
                let sectionId = $('#section_id').val();
                let stdId = $('#std_id').val();
                let sessionId = $('#current_session').val();
                let mercyDate = $('#mercy_date').val();
                let amount = $('#amount').val();
                if ($('#class-section-form').valid()) {
                    $.ajax({
                        url: '{{ route('admin.editSection.mercyFeeBothStore') }}',
                        type: 'POST',
                        dataType: 'JSON',
                        data: {
                            class: classId,
                            section: sectionId,
                            std_id: stdId,
                            session: sessionId,
                            transport: transport,
                            mercy_date: mercyDate,
                            amount: amount,
                            _token: '{{ csrf_token() }}'

                        },
                        success: function(response) {
                            if (response.status == 'success') {

                                Swal.fire({
                                    title: 'Successful',
                                    text: response.message,
                                    icon: 'success',
                                    confirmButtonColor: 'rgb(122 190 255)',
                                }).then(() => {
                                    location.reload();
                                });
                            } else {
                                Swal.fire({
                                    title: 'Error',
                                    text: response.message,
                                    icon: 'error',
                                    confirmButtonColor: 'rgb(122 190 255)',
                                }).then(() => {
                                    location.reload();
                                });
                            }
                        },
                        error: function(data,xhr) {
                            let message = data.responseJSON.message;
                            $('.mercy-date').hide().html('');
                            $('.mercy-amount').hide().html('');
                            if(message.mercy_date){
                                $('.mercy-date').show().html(message.mercy_date);
                            }
                            if(message.amount){
                                $('.mercy-amount').show().html(message.amount);
                            }
                            console.error('Error fetching student details:', xhr);


                        }
                    });
                }
            }


            $('#mercy-academic').click(function() {
                submitForm();
            });
            $('#mercy-transport').click(function() {
                $(this).val(2);
                submitForm($(this).val());
            });


        });
    </script>
@endsection
