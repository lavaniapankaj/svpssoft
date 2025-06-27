//set current section

var currentSession = $('#admin_current_session').val();

$('#current_session').val(currentSession);



//get installment fee total

function calculateTotal() {

    let firstInstall = parseFloat($('#trans_1st_inst').val()) || 0;

    let secondInstall = parseFloat($('#trans_2nd_inst').val()) || 0;

    // let discount = parseFloat($('#trans_discount').val()) || 0;



    let total = firstInstall + secondInstall;



    $('#trans_total').val(total.toFixed(2));

}



$('#trans_1st_inst ,#trans_2nd_inst').on('input', calculateTotal);





function editSectionStdRedirect() {

    var editSectionEditBtn = $('#edit-section-editBtn');

    var $controller = $('#editSection-stdEdit');

    editSectionEditBtn.on('click', function () {

        $controller.val('EditSectionsController');

        localStorage.setItem('editSectionController', 'EditSectionsController');

        window.location.href = $(this).attr('href');

    });

}



// get students in dropdown



function getStdDropdown() {

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
    $('#class_id').change(function(){
        $('#std_id').empty();
        $('#std_id').append('<option value="">Select Students</option>');
    });

}



function editFee(stdId, sessionId, feeOf, academic, paidMercy, PayDate, refSlip) {

    // let stdId = stdID;

    // let sessionId = sessionID;

    $.ajax({

        url: siteUrl + '/admin/edit-section/std-fee-details',
        type: 'GET',
        dataType: 'JSON',
        data: {
            srno: stdId,
            session: sessionId,
        },

        success: function (response) {
            let stdHtml = '';
            if (response.data && response.data.length > 0) {
                $.each(response.data, function (id, value) {

                    if (value.ref_slip_no == refSlip && value.fee_of == feeOf && value.paid_mercy == paidMercy && value.academic_trans == academic && value.pay_date == PayDate) {
                            stdHtml += `<tr>

                                    <td>${value.ref_slip_no ?? ''}</td>

                                    <td><input type="date" value="${value.pay_date}" name="pay_date"></td>

                                    <td>${value.academic_trans == 1 ? 'Academic' : 'Transport'}</td>

                                    <td>${value.fee_of == 1 && value.academic_trans == 1 ? 'Admission Fee' : (value.paid_mercy == 1 && value.fee_of == (value.academic_trans == 1 ? 2 : 1) ? 'Ist Installment' : (value.paid_mercy == 1 && value.fee_of == (value.academic_trans == 1 ? 3 : 2) ? 'IInd Installment' : (value.fee_of == (value.academic_trans == 1 ? 4 : 3) && value.paid_mercy == 1 ? 'Complete' : 'Mercy Fee')))}</td >

                                    <td><input type="text" value="${value.amount}" name="amount"></td>

                                    <td>${value.fee_of == (value.academic_trans == 1 ? 4 : 3) && value.paid_mercy == 2 ? 'Mercy' : 'Paid'}</td>

                                    <td>

                                    <button type="button" onclick="editFeeSave();" class="btn btn-sm btn-info p-1 edit-update-btn">Update</button>

                                    </td>

                            </tr > `;

                    }

                });

            } else {

                stdHtml = '<tr><td colspan="7" class="text-center">No Fee Details Found</td></tr>';

            }



            $('#edit-std-container table tbody').html(stdHtml);

            $('#edit-std-container').show();

        },



        error: function (xhr) {

            console.error('Error fetching student details:', xhr);



        }

    });

};



function editFeeSave() {

    let form = $('#edit-std-container form');

    let formData = form.serializeArray();

    $.ajax({

        url: siteUrl + '/admin/edit-section/std-fee-edit-remove/edit',

        type: 'POST',

        data: formData,

        success: function (response) {

            console.log(response);

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

                    text: data.message,

                    icon: 'error',

                    confirmButtonColor: 'rgb(122 190 255)',

                });

            }

        },

        error: function (xhr) {

            console.error('Error updating fee details:', xhr);

            alert('Failed to Update Fee Details');

        }

    });

}





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

                        if (session.session_id == sessionId) {

                            console.log(session);

                            const admissionFee = session.admission_fee_paid == 0 ?

                                'Not Applicable' : session.admission_fee_paid;

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

                            if (session.transport && session.transport.transport ==

                                1) {

                                const firstTransInst = session.transport

                                    .trans_installments.first_inst || [];

                                const secondTransInst = session.transport

                                    .trans_installments.second_inst || [];

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

                                    `<tr><td colspan = "8" class="table-group-divider text-center fw-bold fs-5">Total</td></tr>`;

                                footerTransHtml +=

                                    `<tr><td colspan = "4" class="fw-bold">Paid</td><td colspan="4" class="fw-bold">Due</td></tr>`;

                                footerTransHtml +=

                                    `<tr><td colspan = "4">${totalTransAmount}</td><td colspan = "4">${dueTransAmount}</td></tr>`;

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



// get class Dropdown with all option

function getClassDropDownWithAll() {

    let classId = $('#class_id');

    let sectionId = $('#section_id');

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



                    });

                }

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
// update pagination
function updatePaginationControls(data) {

    var paginationHtml = '';

    let paginationContainer = $('#std-pagination');

    if (data.last_page > 1) {

        paginationHtml += '<ul class="pagination">';



        if (data.current_page > 1) {

            paginationHtml +=

                `<li class="page-item"><a class="page-link" href="#" data-page="${data.current_page - 1}">Previous</a></li>`;

        }



       for (let i = 1; i <= data.last_page; i++) {

            if (i == data.current_page) {

                paginationHtml +=

                    `<li class="page-item active"><span class="page-link">${i}</span></li>`;

            } else {

                paginationHtml +=

                    `<li class="page-item"><a class="page-link" href="#" data-page="${i}">${i}</a></li>`;

            }

        }



         if (data.current_page < data.last_page) {

            paginationHtml +=

                `<li class="page-item"><a class="page-link" href="#" data-page="${data.current_page + 1}">Next</a></li>`;

        }



        paginationHtml += '</ul>';

    }

    paginationContainer.html(paginationHtml);

}







