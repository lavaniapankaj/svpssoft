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
        // let sessionId = $('#current_session').val();
        let stdSelect = $('#std_id');
        if (classId && sectionId) {
            loader.show();
            $.ajax({
                // url: siteUrl + '/std-name-father',
                url: siteUrl + '/get-st',
                type: 'GET',
                dataType: 'JSON',
                data: {
                    class_id: classId,
                    section_id: sectionId,
                    // session_id: sessionId,
                },
                success: function (students) {
                    stdSelect.empty();
                    let options = '<option value="">Select Students</option>';

                    if (students.data && students.data.length > 0) {
                        $.each(students.data, function(index, student) {
                            options += '<option value="' + student.srno + '">' + student.display_name + '</option>';
                        });
                        stdSelect.html(options);
                    } else {
                        stdSelect.empty();
                        stdSelect.html('<option value="">No students found</option>');
                    }
                },
                complete: function () {
                    loader.hide();
                },
                error: function (xhr) {
                    stdSelect.empty();
                    stdSelect.html('<option value="">No students found</option>');
                }
            });
        }
    });
    $('#class_id').change(function(){
        $('#std_id').empty();
        $('#std_id').html('<option value="">Select Students</option>');
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
            type: 'POST',
            dataType: 'JSON',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
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
                            const admissionFee = session.admission_fee_paid == 0 ? 'Not Applicable' : session.admission_fee_paid;
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
                                const completeTransInst = session.transport.trans_installments.complete_inst || [];
                                const mercyTrans = session.transport.trans_installments.mercy || [];
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
                                const totalTransAmount = allTransInstallments.length > 0 ? allTransInstallments.reduce((total, inst) => total + (inst.amount || 0), 0) : 0;
                                const dueTransAmount = (session.transport.inst_total) - totalTransAmount;
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
                                footerTransHtml += `<tr><td colspan = "9" class="table-group-divider text-center fw-bold fs-5">Total</td></tr>`;
                                footerTransHtml += `<tr><td colspan = "4" class="fw-bold">Paid</td><td colspan="5" class="fw-bold">Due</td></tr>`;
                                footerTransHtml += `<tr><td colspan = "4">${totalTransAmount}</td><td colspan = "5">${dueTransAmount}</td></tr>`;
                            } else {
                                transportHtml += '<tr><td colspan = "9">No Transport Fee Applicable </td></tr>';
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




/* ================================================================= */
/* ================================================================= */

/** Date 17-01-2026 */

function getAdminAllSections(classId, callback) {
    const loader = $('#loader');
    const sectionSelect = $('#admin_section_id');

    // Reset section dropdown if no class selected
    if (!classId) {
        sectionSelect.prop('disabled', true).html('<option value="">Select class first</option>');
        return;
    }

    loader.show();
    sectionSelect.prop('disabled', true).html('<option value="">Loading sections...</option>');
    $.ajax({
        url: siteUrl + '/admin/sections',
        type: 'POST',
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        data: { class_id: classId },
        dataType: 'json',

        success: function (response) {
            sectionSelect.empty();
            if (response.status == 'success' && response.data == 'all') {
                sectionSelect.append('<option value="all">All Sections</option>');
                sectionSelect.prop('disabled', false);
            }else if (response.status == 'success' && response.data && Object.keys(response.data).length > 0) {
                sectionSelect.append('<option value="all">All Sections</option>');
                $.each(response.data, function (id, name) {
                    sectionSelect.append(`<option value="${id}">${name}</option>`);
                });
                sectionSelect.prop('disabled', false);
            } else {
                sectionSelect.html('<option value="">No sections found</option>').prop('disabled', true);
            }
            // Execute callback after sections are loaded
            if (typeof callback === 'function') {
                callback();
            }
        },
        error: function () {
            sectionSelect.html('<option value="">No sections found</option>').prop('disabled', true);
            if (typeof callback === 'function') {
                callback();
            }
        },
        complete: function () {
            loader.hide();
        }
    });
}

/** Get all students */
function getAdminAllStudents(classId, sectionId) {

    const loader = $('#loader');
    const studentSelect = $('#admin_student_id');

    // Reset student dropdown if no class or section selected
    if (!classId && !sectionId) {
        studentSelect.prop('disabled', true).html('<option value="">Select class and section first</option>');
        return;
    }

    loader.show();
    studentSelect.prop('disabled', true).html('<option value="">Loading students...</option>');
    $.ajax({
        url: siteUrl + '/admin/students',
        type: 'POST',
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        data: { class_id: classId, section_id: sectionId },
        dataType: 'json',

        success: function (response) {
            studentSelect.empty();
            if (response.status == 'success' && response.data == 'all') {
                studentSelect.append('<option value="all">All Students</option>');
                studentSelect.prop('disabled', false);
                return;
            }
            if (response.status == 'success' && response.data && response.data.length > 0) {
                studentSelect.append('<option value="all">All Students</option>');
                $.each(response.data, function (id, st) {
                    studentSelect.append(`<option value="${st.srno}">${st.display_name}</option>`);
                });
                studentSelect.prop('disabled', false);
            } else {
                studentSelect.html('<option value="">No students found</option>').prop('disabled', true);
            }
        },
        error: function () {
            studentSelect.html('<option value="">No students found</option>').prop('disabled', true);
        },
        complete: function () {
            loader.hide();
        }
    });
}


/* Update pagination controls function */
function adminUpdatePaginationControls(data) {
    var paginationHtml = '';
    var paginationContainer = $('#admin-pagination');

    if (data.last_page > 1) {
        paginationHtml += '<nav aria-label="Page navigation"><ul class="pagination justify-content-center">';

        // Previous button
        if (data.current_page > 1) {
            paginationHtml += `<li class="page-item">
                <a class="page-link" href="javascript:void(0);" data-page="${data.current_page - 1}">
                    <i class="tf-icon bx bx-chevron-left"></i>
                </a>
            </li>`;
        } else {
            paginationHtml += `<li class="page-item disabled">
                <span class="page-link">
                    <i class="tf-icon bx bx-chevron-left"></i>
                </span>
            </li>`;
        }

        // First page
        if (data.current_page > 3) {
            paginationHtml += `<li class="page-item">
                <a class="page-link" href="javascript:void(0);" data-page="1">1</a>
            </li>`;
            if (data.current_page > 4) {
                paginationHtml += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
            }
        }

        // Page numbers around current page
        let startPage = Math.max(1, data.current_page - 2);
        let endPage = Math.min(data.last_page, data.current_page + 2);

        for (let i = startPage; i <= endPage; i++) {
            if (i == data.current_page) {
                paginationHtml += `<li class="page-item active">
                    <span class="page-link">${i}</span>
                </li>`;
            } else {
                paginationHtml += `<li class="page-item">
                    <a class="page-link" href="javascript:void(0);" data-page="${i}">${i}</a>
                </li>`;
            }
        }

        // Last page
        if (data.current_page < data.last_page - 2) {
            if (data.current_page < data.last_page - 3) {
                paginationHtml += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
            }
            paginationHtml += `<li class="page-item">
                <a class="page-link" href="javascript:void(0);" data-page="${data.last_page}">${data.last_page}</a>
            </li>`;
        }

        // Next button
        if (data.current_page < data.last_page) {
            paginationHtml += `<li class="page-item">
                <a class="page-link" href="javascript:void(0);" data-page="${data.current_page + 1}">
                    <i class="tf-icon bx bx-chevron-right"></i>
                </a>
            </li>`;
        } else {
            paginationHtml += `<li class="page-item disabled">
                <span class="page-link">
                    <i class="tf-icon bx bx-chevron-right"></i>
                </span>
            </li>`;
        }

        paginationHtml += '</ul></nav>';

        // Add page info
        paginationHtml += `<div class="text-center mt-2">
            <small class="text-muted">Showing ${data.from} to ${data.to} of ${data.total} entries</small>
        </div>`;
    }

    paginationContainer.html(paginationHtml);
}

/* Get Sections - without all */
function getAdminWithoutAllSections(classId, callback) {
    const loader = $('#loader');
    const sectionSelect = $('#admin_section_id');

    // Reset section dropdown if no class selected
    if (!classId) {
        sectionSelect.prop('disabled', true).html('<option value="">Select class first</option>');
        return;
    }

    loader.show();
    sectionSelect.prop('disabled', true).html('<option value="">Loading sections...</option>');
    $.ajax({
        url: siteUrl + '/admin/sections',
        type: 'POST',
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        data: { class_id: classId },
        dataType: 'json',

        success: function (response) {
            sectionSelect.empty();
            if (response.status == 'success' && response.data == 'all') {
                sectionSelect.append('<option value="all">All Sections</option>');
                sectionSelect.prop('disabled', false);
            }else if (response.status == 'success' && response.data && Object.keys(response.data).length > 0) {
                sectionSelect.append('<option value="">Select Section</option>');
                $.each(response.data, function (id, name) {
                    sectionSelect.append(`<option value="${id}">${name}</option>`);
                });
                sectionSelect.prop('disabled', false);
            } else {
                sectionSelect.html('<option value="">No sections found</option>').prop('disabled', true);
            }
            // Execute callback after sections are loaded
            if (typeof callback === 'function') {
                callback();
            }
        },
        error: function () {
            sectionSelect.html('<option value="">No sections found</option>').prop('disabled', true);
            if (typeof callback === 'function') {
                callback();
            }
        },
        complete: function () {
            loader.hide();
        }
    });
}

/** Get without all students */
function getAdminStudentsWithoutAll(classId, sectionId) {

    const loader = $('#loader');
    const studentSelect = $('#admin_student_id');

    // Reset student dropdown if no class or section selected
    if (!classId && !sectionId) {
        studentSelect.prop('disabled', true).html('<option value="">Select class and section first</option>');
        return;
    }
    if (!sectionId) {
        studentSelect.prop('disabled', true).html('<option value="">Select section first</option>');
        return;
    }

    loader.show();
    studentSelect.prop('disabled', true).html('<option value="">Loading students...</option>');
    $.ajax({
        url: siteUrl + '/admin/students',
        type: 'POST',
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        data: { class_id: classId, section_id: sectionId },
        dataType: 'json',

        success: function (response) {
            studentSelect.empty();
            if (response.status == 'success' && response.data == 'all') {
                studentSelect.append('<option value="all">All Students</option>');
                studentSelect.prop('disabled', false);
                return;
            }
            if (response.status == 'success' && response.data && response.data.length > 0) {
                studentSelect.append('<option value="">Select Student</option>');
                $.each(response.data, function (id, st) {
                    studentSelect.append(`<option value="${st.srno}">${st.display_name}</option>`);
                });
                studentSelect.prop('disabled', false);
            } else {
                studentSelect.html('<option value="">No students found</option>').prop('disabled', true);
            }
        },
        error: function () {
            studentSelect.html('<option value="">No students found</option>').prop('disabled', true);
        },
        complete: function () {
            loader.hide();
        }
    });
}
