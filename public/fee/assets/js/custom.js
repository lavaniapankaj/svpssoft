//set current session(marks)

var currentSession = $('#fee_current_session').val();

$('#current_session').val(currentSession);



$('#section_id').change(function () {

    let classId = $('#class_id').val();

    let sectionId = $(this).val();

    let sessionId = $('#current_session').val();

    let stdSelect = $('#std_id');
    if (classId && sectionId && sessionId) {

        loader.show();

        $.ajax({

            url: siteUrl + '/std-name-father',

            type: 'GET',

            dataType: 'JSON',

            data: {

                class_id: classId,

                section_id: sectionId,

                session_id: sessionId,

            },

            success: function (students) {

                stdSelect.empty();

                let options = '<option value="">Select Students</option>';



                if (students.length > 0) {

                    $.each(students, function (index, student) {

                        options += '<option value="' + student.srno + '">' +

                            student.rollno + '. ' + student.student_name +

                            '/SH. ' +

                            student.f_name + '</option>';

                    });

                } else {

                    options += '<option value="">No students found</option>';

                }

                stdSelect.html(options);

            },

            complete: function () {

                loader.hide();

            },

            error: function (xhr) {

                console.error(xhr.responseText);

            }

        });

    }

});



$('#class-section-form').validate({

    rules: {

        std_id: {

            required: true,

        },

        class: {

            required: true,

        },

        section: {

            required: true,

        },

        fee_date: {

            required: true,

        },

        total_amount: {

            required: true,

        },

        ref_slip: {

            required: true,

        },

    },

    messages: {

        std_id: {

            required: "Please select a student.",

        },

        class: {

            required: "Please select a class.",

        },

        section: {

            required: "Please select a section.",

        },

        fee_date: {

            required: "Please enter date",

        },

        total_amount: {

            required: "Please enter total amount",

        },

        ref_slip: {

            required: "Please enter Ref. Slip No.",

        },

    },

});



$('#submit-fee').click(function (e) {

    if ($('#class-section-form').valid()) {

        e.preventDefault();

        const totalAmount = parseFloat($('#total_amount').val()) || 0;

        const admissionFee = parseFloat($('#admission_fee').val()) || 0;

        const firstInstFee = parseFloat($('#first_inst_fee').val()) || 0;

        const secondInstFee = parseFloat($('#second_inst_fee').val()) || 0;

        const completeFee = parseFloat($('#complete_fee').val()) || 0;

        const mercyFee = parseFloat($('#mercy_fee').val()) || 0;

        const total = admissionFee + firstInstFee + secondInstFee + completeFee + mercyFee;
        if (total > totalAmount) {

            $('#total-amount-error').show().html(

                'You have entered an amount greater than the total amount');

        } else {

            $('#total-amount-error').hide().html('');

            $.ajax({

                url: siteUrl + '/fee/academic-fee-entry',
                data: $('#class-section-form').serialize(),
                type: "POST",
                dataType: 'JSON',
                success: function (data) {
                    Swal.fire({

                        title: 'Successful',

                        text: data.message,

                        icon: 'success',

                        confirmButtonColor: 'rgb(122 190 255)',

                        showCancelButton: true,

                        confirmButtonText: 'Print Fee Slip',

                        cancelButtonText: 'Close'

                    }).then((result) => {

                        if (result.isConfirmed) {

                            if (data.print_url) {

                                window.open(data.print_url, '_blank');
                                 // Reload the current page after a short delay (e.g., 500ms)
                                setTimeout(() => {
                                    location.reload();
                                }, 500); // Adjust the delay as needed

                            } else {

                                Swal.fire({

                                    title: 'Error!',

                                    text: 'Failed to print fee slip',

                                    icon: 'error',

                                });

                            }

                            location.reload();
                        }


                    });

                },

                error: function (data) {
                    var message = data.responseJSON.message;
                    $('#class-error').hide().html('');

                    $('#section-error').hide().html('');

                    $('#session-error').hide().html('');

                    $('#std-error').hide().html('');

                    $('#fee-date-error').hide().html('');

                    $('#ref-slip-error').hide().html('');

                    $('#admission-fee-error').hide().html('');

                    $('#first-inst-fee-error').hide().html('');

                    $('#second-inst-fee-error').hide().html('');

                    $('#complete-fee-error').hide().html('');

                    $('#mercy-fee-error').hide().html('');

                    $('#not-applicable-error').hide().html();



                    if (message.class_id) {
                        $('#class-error').show().html(message.class_id);
                    }

                    if (message.section_id) {
                        $('#section-error').show().html(message.section_id);
                    }

                    if (message.std_id) {
                        $('#std-error').show().html(message.std_id);
                    }

                    if (message.fee_date) {
                        $('#fee-date-error').show().html(message.fee_date);
                    }

                    if (message.ref_slip) {
                        $('#ref-slip-error').show().html(message.ref_slip);
                    }

                    if (message.admission_fee) {
                        $('#admission-fee-error').show().html(message.admission_fee);

                    }

                    if (message.first_inst_fee) {
                        $('#first-inst-fee-error').show().html(message.first_inst_fee);
                    }

                    if (message.second_inst_fee) {
                        $('#second-inst-fee-error').show().html(message.second_inst_fee);

                    }

                    if (message.complete_fee) {
                         $('#complete-fee-error').show().html(message.complete_fee);

                    }

                    if (message.mercy_fee) {
                        $('#mercy-fee-error').show().html(message.mercy_fee);

                    }
                    if (message == 'No Transport Fee Applicable For This Student.') {
                        $('#not-applicable-error').show().html(message);
                    }

                }

            });

        }
    }

});





function adcademicAndTransportFeePopulate(st, sessionID, classID, sectionID) {

    var stdSelect = st;

    var stdFeeDueTable = $('#std-fee-due-table');

    var transportStdFeeDueTable = $('#std-transport-fee-due-table');

    let sessionId = sessionID;

    let classId = classID;

    let sectionId = sectionID;

    if (stdSelect !== '') {

        stdFeeDueTable.show();

        transportStdFeeDueTable.show();

        $.ajax({

            url: siteUrl + '/fee/fee-entry-due',

            type: 'GET',

            dataType: 'JSON',

            data: {

                srno: stdSelect,

                current_session: sessionId,

                class: classId,

                section: sectionId,

            },

            success: function (response) {

                let stdHtml = '';

                let footerstdHtml = '';

                let transportHtml = '';

                let footerTransHtml = '';

                // Process student data

                const students = response.data;

                $.each(students, function (index, student) {

                $.each(student.sessions, function (index, session) {

                    if (session.session_id == sessionId && session.class_id == classId && session.section_id == sectionId) {
                        const admissionFee = ((session.admission_date == '' || session.admission_date == null) && (session.prev_srno != '' || session.prev_srno != null)) ? 'Not Applicable' : session.admission_fee;
                        const firstInst = session.installments.first_inst;
                        const secondInst = session.installments.second_inst;
                        const completeInst = session.installments.complete_inst;
                        const mercy = session.installments.mercy;
                        const allInstallments = [

                            ...firstInst.map(inst => ({
                                ...inst,
                                type: 'first'

                            })),

                            ...secondInst.map(inst => ({
                                ...inst,
                                type: 'second'

                            })),

                            ...completeInst.map(inst => ({

                                ...inst,

                                type: 'complete'

                            })),

                            ...mercy.map(inst => ({

                                ...inst,

                                type: 'mercy'

                            }))

                        ];



                        // Admission Fees Section

                        stdHtml += `<tr>

                                        <td>Payable Fee</td>

                                        <td>${session.admission_fee}</td>

                                        <td>${session.inst_1}</td>

                                        <td>${session.inst_2}</td>

                                        <td>${session.inst_total}</td>

                                        <td>-</td>

                                        <td>-</td>

                                        <td>-</td>

                                        <td>-</td>

                                        <td>-</td>

                                    </tr>`;



                        const totalAmount = allInstallments.reduce((

                            total, inst) => {

                            return total + (inst.amount ||

                                0

                            ); // Use 0 as a fallback if amount is undefined

                        }, 0);

                        const dueAmount = session.inst_total - totalAmount;

                        allInstallments.forEach((inst, index) => {

                            stdHtml += `

                                        <tr>

                                            <td>Paid Fee</td>

                                            <td>${index === 0 ? admissionFee : ''}</td>

                                            <td>${inst.type === 'first' ? inst.amount : '0'}</td>

                                            <td>${inst.type === 'second' ? inst.amount : '0'}</td>

                                            <td>${inst.type === 'complete' ? inst.amount : '0'}</td>

                                            <td>${inst.type === 'mercy' ? inst.amount : '0'}</td>

                                            <td>${inst.type === 'mercy' ? 'Mercy' : 'Paid'}</td>

                                            <td>${inst.pay_date || '-'}</td>

                                            <td>${inst.recp_no || '-'}</td>

                                            <td>${inst.ref_slip_no || '-'}</td>

                                        </tr>

                                    `;

                        });

                        footerstdHtml +=

                            `<tr><td colspan = "10" class="table-group-divider text-center fw-bold fs-5">Total</td></tr>`;

                        footerstdHtml +=

                            `<tr><td colspan = "5" class="fw-bold">Paid</td><td colspan="5" class="fw-bold">Due</td></tr>`;

                        footerstdHtml +=

                            `<tr><td colspan = "5">${totalAmount}</td><td colspan = "5">${dueAmount}</td></tr>`;

                        // Transport Fees Section

                        if (session.transport && session.transport.transport == 1) {

                            const firstTransInst = session.transport.trans_installments.first_inst || [];

                            const secondTransInst = session.transport.trans_installments.second_inst || [];

                            const completeTransInst = session.transport

                                .trans_installments.complete_inst || [];

                            const mercyTrans = session.transport

                                .trans_installments.mercy || [];



                            const allTransInstallments = [

                                ...firstTransInst.map(inst => ({

                                    ...inst,

                                    type: 'first'

                                })),

                                ...secondTransInst.map(inst => ({

                                    ...inst,

                                    type: 'second'

                                })),

                                ...completeTransInst.map(inst => ({

                                    ...inst,

                                    type: 'complete'

                                })),

                                ...mercyTrans.map(inst => ({

                                    ...inst,

                                    type: 'mercy'

                                }))

                            ];

                            transportHtml += `<tr>

                                                <td>Payable Transport Fee</td>

                                                <td>${session.transport.inst_1}</td>

                                                <td>${session.transport.inst_2}</td>

                                                <td>${session.transport.inst_total}</td>

                                                <td>-</td>

                                                <td>-</td>

                                                <td>-</td>

                                                <td>-</td>
                                                <td>-</td>



                                            </tr>`;

                            const totalTransAmount = allTransInstallments

                                .length > 0 ?

                                allTransInstallments.reduce((total, inst) =>

                                    total + (inst.amount || 0), 0) :

                                0;

                            const dueTransAmount = (session.transport

                                .inst_total) - totalTransAmount;



                            allTransInstallments.forEach((inst, index) => {



                                transportHtml += `<tr>

                                            <td>Paid Transport Fee</td>

                                             <td>${inst.type === 'first' ? inst.amount : '0'}</td>

                                                <td>${inst.type === 'second' ? inst.amount : '0'}</td>

                                                <td>${inst.type === 'complete' ? inst.amount : '0'}</td>

                                                <td>${inst.type === 'mercy' ? inst.amount : '0'}</td>

                                                <td>${inst.type === 'mercy' ? 'Mercy' : 'Paid'}</td>

                                                <td>${inst.pay_date || '-'}</td>

                                                <td>${inst.recp_no || '-'}</td>

                                                <td>${inst.ref_slip_no || '-'}</td>

                                        </tr>`;

                            });

                            footerTransHtml +=

                                `<tr><td colspan = "9" class="table-group-divider text-center fw-bold fs-5">Total</td></tr>`;

                            footerTransHtml +=

                                `<tr><td colspan = "5" class="fw-bold">Paid</td><td colspan="4" class="fw-bold">Due</td></tr>`;

                            footerTransHtml +=

                                `<tr><td colspan = "5">${totalTransAmount}</td><td colspan = "4">${dueTransAmount}</td></tr>`;

                        } else {

                            transportHtml +=

                                '<tr><td colspan = "9">No Transport Fee Applicable </td></tr>';

                        }

                    }

                });

                });

                if (stdHtml === '') {

                    stdHtml += '<tr><td colspan = "10">No Academic Fee Found</td></tr>';

                }

                $('#std-fee-due-table table tbody').html(stdHtml);

                $('.footer').html(footerstdHtml);

                if (transportHtml === '') {

                    transportHtml +=

                        '<tr><td colspan = "9">No Transport Fee Found</td></tr>';

                }

                $('#std-transport-fee-due-table table tbody').html(transportHtml);

                $('.footerTrans').html(footerTransHtml);



            },

            error: function (xhr) {

                console.error(xhr.responseText);

            }

        });

    } else {

        stdFeeDueTable.hide();

        transportStdFeeDueTable.hide();

    }

}





function adcademicAndTransportFeePopulateWithoutSSID(st, sessionID, classId) {

    var stdSelect = st;

    var stdFeeDueTable = $('#std-fee-due-table');

    var transportStdFeeDueTable = $('#std-transport-fee-due-table');

    let sessionId = sessionID;

    if (stdSelect !== '') {

        stdFeeDueTable.show();

        transportStdFeeDueTable.show();

        $.ajax({

            // url: '{{ route('fee.fee - entry.academicFeeDueAmount') }}',

            url: siteUrl + '/fee/student-without-ssid',
            type: 'POST',
            // type: 'GET',
            dataType: 'JSON',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            data: {
                srno: stdSelect,
                session: sessionId,
            },

            success: function (response) {

                let stdHtml = '';

                let footerstdHtml = '';

                let transportHtml = '';

                let footerTransHtml = '';

                // Process student data

                const student = response.data;

                // console.log(student);



                $.each(student, function (index, session) {

                    console.log(session);



                    if (session.student.session_id == sessionId && session.student.class == classId) {

                        const admissionFee = ((session.admission_date == '' || session.admission_date == null) && (session.prev_srno != '' || session.prev_srno != null)) ?

                            'Not Applicable' : session.admission_fee;

                        const firstInst = session.installments.first_inst;

                        const secondInst = session.installments.second_inst;

                        const completeInst = session.installments.complete_inst;

                        const mercy = session.installments.mercy;



                        const allInstallments = [

                            ...firstInst.map(inst => ({

                                ...inst,

                                type: 'first'

                            })),

                            ...secondInst.map(inst => ({

                                ...inst,

                                type: 'second'

                            })),

                            ...completeInst.map(inst => ({

                                ...inst,

                                type: 'complete'

                            })),

                            ...mercy.map(inst => ({

                                ...inst,

                                type: 'mercy'

                            }))

                        ];



                        // Admission Fees Section

                        stdHtml += `<tr>

                                        <td>Payable Fee</td>

                                        <td>${session.admission_fee}</td>

                                        <td>${session.inst_1}</td>

                                        <td>${session.inst_2}</td>

                                        <td>${session.inst_total}</td>

                                        <td>-</td>

                                        <td>-</td>

                                        <td>-</td>

                                        <td>-</td>

                                        <td>-</td>

                                    </tr>`;



                        const totalAmount = allInstallments.reduce((

                            total, inst) => {

                            return total + (inst.amount ||

                                0

                            ); // Use 0 as a fallback if amount is undefined

                        }, 0);

                        const dueAmount = session.inst_total - totalAmount;

                        allInstallments.forEach((inst, index) => {

                            stdHtml += `

                                        <tr>

                                            <td>Paid Fee</td>

                                            <td>${index === 0 ? admissionFee : ''}</td>

                                            <td>${inst.type === 'first' ? inst.amount : '0'}</td>

                                            <td>${inst.type === 'second' ? inst.amount : '0'}</td>

                                            <td>${inst.type === 'complete' ? inst.amount : '0'}</td>

                                            <td>${inst.type === 'mercy' ? inst.amount : '0'}</td>

                                            <td>${inst.type === 'mercy' ? 'Mercy' : 'Paid'}</td>

                                            <td>${inst.pay_date || '-'}</td>

                                            <td>${inst.recp_no || '-'}</td>

                                            <td>${inst.ref_slip_no || '-'}</td>

                                        </tr>

                                    `;

                        });

                        footerstdHtml +=

                            `<tr><td colspan = "10" class="table-group-divider text-center fw-bold fs-5">Total</td></tr>`;

                        footerstdHtml +=

                            `<tr><td colspan = "5" class="fw-bold">Paid</td><td colspan="5" class="fw-bold">Due</td></tr>`;

                        footerstdHtml +=

                            `<tr><td colspan = "5">${totalAmount}</td><td colspan = "5">${dueAmount}</td></tr>`;

                        // Transport Fees Section

                        if (session.transport == 1) {

                            const firstTransInst = session.trans_installments.first_inst || [];

                            const secondTransInst = session.trans_installments.second_inst || [];

                            const completeTransInst = session.trans_installments.complete_inst || [];

                            const mercyTrans = session.trans_installments.mercy || [];



                            const allTransInstallments = [

                                ...firstTransInst.map(inst => ({

                                    ...inst,

                                    type: 'first'

                                })),

                                ...secondTransInst.map(inst => ({

                                    ...inst,

                                    type: 'second'

                                })),

                                ...completeTransInst.map(inst => ({

                                    ...inst,

                                    type: 'complete'

                                })),

                                ...mercyTrans.map(inst => ({

                                    ...inst,

                                    type: 'mercy'

                                }))

                            ];

                            transportHtml += `<tr>

                                                <td>Payable Transport Fee</td>

                                                <td>${session.trans_inst_1}</td>

                                                <td>${session.trans_inst_2}</td>

                                                <td>${session.trans_inst_total}</td>

                                                <td>-</td>
                                                <td>-</td>

                                                <td>-</td>

                                                <td>-</td>

                                                <td>-</td>

                                            </tr>`;

                            const totalTransAmount = allTransInstallments

                                .length > 0 ?

                                allTransInstallments.reduce((total, inst) =>

                                    total + (inst.amount || 0), 0) :

                                0;

                            const dueTransAmount = (session.trans_inst_total) - totalTransAmount;



                            allTransInstallments.forEach((inst, index) => {



                                transportHtml += `<tr>

                                            <td>Paid Transport Fee</td>

                                             <td>${inst.type === 'first' ? inst.amount : '0'}</td>

                                                <td>${inst.type === 'second' ? inst.amount : '0'}</td>

                                                <td>${inst.type === 'complete' ? inst.amount : '0'}</td>

                                                <td>${inst.type === 'mercy' ? inst.amount : '0'}</td>

                                                <td>${inst.type === 'mercy' ? 'Mercy' : 'Paid'}</td>

                                                <td>${inst.pay_date || '-'}</td>

                                                <td>${inst.recp_no || '-'}</td>

                                                <td>${inst.ref_slip_no || '-'}</td>

                                        </tr>`;

                            });

                            footerTransHtml +=

                                `<tr><td colspan = "8" class="table-group-divider text-center fw-bold fs-5">Total</td></tr>`;

                            footerTransHtml +=

                                `<tr><td colspan = "4" class="fw-bold">Paid</td><td colspan="4" class="fw-bold">Due</td></tr>`;

                            footerTransHtml +=

                                `<tr><td colspan = "4">${totalTransAmount}</td><td colspan = "4">${dueTransAmount}</td></tr>`;

                        } else {

                            transportHtml +=

                                '<tr><td colspan = "8">No Transport Fee Applicable </td></tr>';

                        }

                    }

                });

                if (stdHtml === '') {

                    stdHtml += '<tr><td colspan = "10">No Academic Fee Found</td></tr>';

                }

                $('#std-fee-due-table table tbody').html(stdHtml);

                $('.footer').html(footerstdHtml);

                if (transportHtml === '') {

                    transportHtml +=

                        '<tr><td colspan = "9">No Transport Fee Found</td></tr>';

                }

                $('#std-transport-fee-due-table table tbody').html(transportHtml);

                $('.footerTrans').html(footerTransHtml);



            },

            error: function (xhr) {

                console.error(xhr.responseText);

            }

        });

    } else {

        stdFeeDueTable.hide();

        transportStdFeeDueTable.hide();

    }

}





function classSectionWithAll(fetchStudentsForSession, fetchStudents) {

    let classId = $('#back_class_id');

    let sectionId = $('#back_section_id');

    let loader = $('#loader');

    let initialClassId = $('#initialClassId').val();

    let allClassIds = []; // Array to hold all class IDs

    let allSectionIds = [];

    function fetchClasses() {

        loader.show();

        $.ajax({

            url: siteUrl + '/classes',

            type: 'GET',

            dataType: 'JSON',

            success: function (data) {

                classId.empty();

                allClassIds = Object.keys(data.data); // Store all class IDs

                classId.append('<option value="' + allClassIds.join(',') +

                    '" selected>All Class</option>');

                $.each(data.data, function (id, name) {

                    classId.append('<option value="' + id + '">' + name + '</option>');

                });

                if (initialClassId) {

                    classId.val(initialClassId);

                }

                // Fetch sections for the initial selection

                fetchSections(classId.val());

                // var selectedStValue = classId.val();

                // studentTable(selectedStValue ? selectedStValue : allClassIds.join(','));

                fetchStudentsForSession();

            },

            complete: function () {

                loader.hide();

            },

            error: function (data) {

                console.error('Error fetching classes:', data.message);

            }

        });

    }



    function fetchSections(classIds) {

        loader.show();

        $.ajax({

            url: siteUrl + '/sections',

            type: 'GET',

            dataType: 'JSON',

            data: {

                class_id: classIds

            },

            success: function (data) {

                sectionId.empty();



                // allSectionIds = Object.keys(data.data);

                if (classIds.includes(',')) {

                    $.each(data.data, function (id, name) {

                        allSectionIds.push(id);

                    });

                    sectionId.append('<option value="' + allSectionIds.join(',') +

                        '" selected>All Section</option>');

                } else {

                    $.each(data.data, function (id, name) {

                        sectionId.append('<option value="' + id + '">' + name +

                            '</option>');

                        // allSectionIds.push(id);

                    });

                }

                // Fetch students for the initial selection

                // sectionId.find('option:first').val(allSectionIds.join(','));

                //  fetchStudents(sectionId.val());



                // Fetch students for the initial selection

                var selectedSectionValue = sectionId.val();

                fetchStudents(selectedSectionValue ? selectedSectionValue : allSectionIds.join(

                    ','));



            },

            complete: function () {

                loader.hide();

            },

            error: function (data) {

                console.error('Error fetching sections:', data.responseJSON ? data.responseJSON

                    .message : 'Unknown error');

            }

        });

    }



    fetchClasses();

    classId.change(function () {

        fetchSections($(this).val());

    });

}



//pagination



function updatePaginationControls(data) {
    var paginationHtml = '';
    var paginationContainer = $('#std-pagination');
    if (data.last_page > 1) {
        paginationHtml += '<ul class="pagination">';
        if (data.current_page > 1) {
            paginationHtml += `<li class="page-item"><a class="page-link" href="#" data-page="${data.current_page - 1}">Previous</a></li>`;

        }
        for (let i = 1; i <= data.last_page; i++) {
            if (i == 1 || i == data.last_page || Math.abs(i - data.current_page) <= 2) {
                if (i == data.current_page) {
                    paginationHtml += `<li class="page-item active"><span class="page-link">${i}</span></li>`;
                } else {
                    paginationHtml += `<li class="page-item"><a class="page-link" href="#" data-page="${i}">${i}</a></li>`;

                }
            } else if (i == 2 || i == data.last_page - 1 || i == data.current_page - 3 || i == data.current_page + 3) {
                paginationHtml += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
            }
        }
        if (data.current_page < data.last_page) {
            paginationHtml += `<li class="page-item"><a class="page-link" href="#" data-page="${data.current_page + 1}">Next</a></li>`;
        }
        paginationHtml += '</ul>';

    }
    paginationContainer.html(paginationHtml);
}



// for due report section



function dueReportSection() {
    let classId = $('#back_class_id');
    let sectionId = $('#back_section_id');
    let sessionId = $('#current_session').val();
    let stdId = $('#back_std_id');
    let loader = $('#loader');

    $('#complete-fee-table').hide();

    classSectionWithAll(fetchStudentsForSession, fetchStudents);
    function fetchStudentsForSession() {
        let allSectionsValue = sectionId.find('option:first').val();
        fetchStudents(allSectionsValue);

    }
    function studentTable(st, page = 1) {
        let reportType = $('#report').val();
        if (!classId.val() || !sectionId.val() || !st || !reportType) {
            console.warn('Missing required field: class, section, student, or report.');
            $('#complete-fee-table').hide(); // Hide the table if any field is missing
            $('#no-records-message').show(); // Show "No Records Found" message
            return;
        } else {
            $('#no-records-message').hide(); // Hide "No Records Found" message if data is available

            $.ajax({
                url: siteUrl + '/fee/student-without-ssid',
                type: 'POST',
                // type: 'GET',
                dataType: 'JSON',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                data: {
                    session: sessionId,
                    class: classId.val(),
                    section: sectionId.val(),
                    srno: st,
                    page: page,
                },

                success: function (data) {
                    let stdHtml = '';
                    const isAllStudents = st.includes(',');
                    const isAllSections = sectionId.val().includes(',');
                    if (data.data.length > 0) {

                        let hasValidRecords = false;

                        data.data.forEach(value => {

                            let isValidateIsAll = isAllSections === false ? (st === value

                                .student.srno.toString()) && (sectionId.val() == value

                                    .student.section.toString()) : (st === value.student

                                        .srno

                                        .toString());

                            if (isAllStudents || isValidateIsAll) {
                                const firstInst = value.installments.first_inst;
                                const transFirstInst = value.trans_installments
                                    .first_inst || [];

                                // Calculate amounts for academic fee
                                const academicInstAmount = firstInst.reduce((total, inst) => total + (inst.amount || 0), 0);

                                // Calculate amounts for transport fee
                                const transportInstAmount = transFirstInst.reduce((total, inst) => total + (inst.amount || 0), 0);
                                // Calculate due amounts
                                const academicDue = reportType == 'complete' ? (value.payable_amount ?? 0) - (value.paid_amount ?? 0) : (value.inst_1 ?? 0) - academicInstAmount;

                                const transportDue = value.transport == 1 ?
                                    (reportType == 'complete' ? (value.trans_payable_amount ?? 0) - (value.trans_paid_amount ?? 0) : (value.trans_inst_1 ?? 0) - transportInstAmount) : 0;

                                const totalDue = academicDue + transportDue;
                                // Only include the student if it's a complete report or if there's a due amount

                                if ((reportType === 'complete' || totalDue > 0) || (reportType === 'firstInstDue' && totalDue > 0)) {

                                    hasValidRecords = true;

                                    stdHtml += `<tr>

                                        <td>${value.class_name}</td>

                                        <td>${value.section_name}</td>

                                        <td>${value.student_name}</td>

                                        <td>${value.father_name}</td>

                                        <td>${reportType == 'firstInstDue' ? value.inst_1 : value.payable_amount}</td>
                                        <td>${reportType == 'firstInstDue' ? academicInstAmount : value.paid_amount}</td>
                                        <td>${academicDue}</td>

                                        <td>${value.transport == 1  ? (reportType == 'firstInstDue' ? (value.trans_1st_inst ?? 0) : (value.trans_payable_amount ?? 0)) : 0}</td>
                                        <td>${value.transport == 1 ? (reportType == 'firstInstDue' ? transportInstAmount : (value.trans_paid_amount ?? 0)) : 0}</td>
                                        <td>${transportDue}</td>
                                        <td>${totalDue}</td>
                                        <td><a href='${siteUrl}/fee/back-session/individual-fee-details/${value.student.srno}/${value.student.session_id}/${value.student.class}/${value.student.section}' class="btn btn-sm btn-icon p-1"> <i class="mdi mdi-eye mx-1" data-bs-toggle="tooltip"
                                        data-bs-offset="0,4" data-bs-placement="top" title="View"></i></a></td>

                                    </tr>`;

                                }

                            }

                        });
                        if (!hasValidRecords) {
                            stdHtml = '<tr><td colspan="12">No Student Record Found</td></tr>';
                        }

                    } else {
                        stdHtml = '<tr><td colspan="12">No Student Record Found</td></tr>';

                    }
                    $('#complete-fee-table table tbody').html(stdHtml);

                    updatePaginationControls(data.pagination);

                },

                complete: function () {
                    loader.hide();

                },

                error: function (data) {
                    console.error('Error fetching students:', data.responseJSON ? data.responseJSON.message : 'Unknown error');

                }

            });

        }

    }
    function fetchStudents(sectionIds) {
        loader.show();
        $.ajax({
            url: siteUrl + '/std-name-father',
            type: 'GET',
            dataType: 'JSON',
            data: {
                class_id: classId.val(),
                section_id: sectionIds,
                session_id: sessionId,

            },
            success: function (data) {
                stdId.empty();
                let allStdIds = [];
                if (sectionIds.includes(',')) {
                    // Always populate allStdIds
                    $.each(data, function (id, value) {
                        allStdIds.push(value.srno);
                    });
                    // Add "All Students" option
                    stdId.append('<option value="' + allStdIds.join(',') +

                        '" selected>All Students</option>');

                } else {
                    // Add individual student options
                    $.each(data, function (id, value) {
                        allStdIds.push(value.srno);
                        stdId.append('<option value="' + value.srno + '">'+ value.rollno + '. ' + value.student_name + '/SH. ' + value.f_name + '</option>');
                    });
                    stdId.prepend('<option value="' + allStdIds.join(',') + '" selected>All Students</option>');

                }
                // Fetch students for the initial selection
                var selectedStValue = stdId.val();
                studentTable(selectedStValue ? selectedStValue : allStdIds.join(','));
            },

            complete: function () {
                loader.hide();
            },
            error: function (data) {
                console.error('Error fetching students:', data.responseJSON ? data.responseJSON.message : 'Unknown error');
            }

        });

    }
    $(document).on('click', '#std-pagination .page-link', function (e) {
        e.preventDefault();
        var st = stdId.val();
        var page = $(this).data('page');
        studentTable(st, page);
    });

    sectionId.change(function () {
        fetchStudents($(this).val());
    });

    stdId.change(function () {
        studentTable($(this).val());
    });
    // Add an event listener for the report dropdown
    $('#report').change(function () {
        let selectedStudents = $('#back_std_id').val();
        studentTable(selectedStudents);
    });
    // Modify the show-details button click event

    $('#show-details').click(function () {
        let reportType = $('#report').val();
        if (!classId.val() || !sectionId.val() || !stdId.val() || !reportType) {
            $('#complete-fee-table').hide(); // Hide the table if any field is missing
            $('#no-records-message').show(); // Show "No Records Found" message
            return;
        } else {
            $('#no-records-message').hide(); // Hide "No Records Found" message if data is available
            $('#complete-fee-table').show();
        }
    });
}