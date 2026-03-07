//set current session(std)
var currentSession = $('#student_current_session').val();
$('#current_session').val(currentSession);
//get transport fee total
function calculateTotal() {
    let firstInstall = parseFloat($('#trans_1st_inst').val()) || 0;
    let secondInstall = parseFloat($('#trans_2nd_inst').val()) || 0;
    let discount = parseFloat($('#trans_discount').val()) || 0;
    let total = firstInstall + secondInstall - discount;
    $('#trans_total').val(total.toFixed(2));
}
$('#trans_1st_inst ,#trans_2nd_inst, #trans_discount').on('input', calculateTotal);
calculateTotal();
//all student dropDown
function getStudentDropdown() {
    $('#section_id').change(function() {
        var classId = $('#class_id').val();
        var sectionId = $(this).val();
        var sessionId = $('#current_session').val();
        var stdSelect = $('#std_id');
        if (classId && sectionId && sessionId) {
            loader.show();
            $.ajax({
                // url: '{{ route('stdNameFather.get') }}',
                url: siteUrl + '/std-name-father',
                type: 'GET',
                dataType: 'JSON',
                data: {
                    class_id: classId,
                    section_id: sectionId,
                    session_id: sessionId,
                },
                success: function(students) {
                   stdSelect.empty();
                    let options = '<option value="" selected>All Students</option>';
                    const allStudentSrnos = [];
                    if (students.length > 0) {
                        $.each(students, function(index, student) {
                            allStudentSrnos.push(student.srno);
                            options += '<option value="' + student.srno + '">' +
                                student.rollno + '. ' + student.student_name +
                                '/' +
                                student.f_name + '</option>';
                        });
                    } else {
                        options += '<option value="">No students found</option>';
                    }
                    stdSelect.html(options);
                    stdSelect.find('option[value=""]').val(allStudentSrnos);
                },
                complete: function() {
                    loader.hide();
                },
                error: function(xhr) {
                    console.error(xhr.responseText);
                }
            });
        }
    });
}
function admissionDateGet(srno) {
    return new Promise((resolve, reject) => {
        if (srno) {
            $.ajax({
                url: siteUrl + '/admin/std/srno/admission/promotion',
                type: 'GET',
                dataType: 'JSON',
                data: { srno: srno },
                success: function (students) {
                    let admissionDate = '';
                    $.each(students.data, function (index, std) {
                        if (std.admission_date !== null && std.form_submit_date !== null) {
                            let date = new Date(std.admission_date);
                            admissionDate = date.getDate() + '-' +
                            date.toLocaleString('default', { month: 'short' }) + '-' +
                            date.getFullYear();
                            return false;
                        }
                    });
                    resolve(admissionDate);
                },
                error: function (xhr) {
                    console.log('Request failed:', xhr);
                    reject(null);
                }
            });
        } else {
            resolve(''); // Return empty string if srno is not provided
        }
    });
}
function updatePaginationControls(data) {
    let paginationHtml = '';
    const paginationContainer = $('#std-pagination');
    if (data.last_page > 1) {
        paginationHtml += '<ul class="pagination">';
        if (data.current_page > 1) {
            paginationHtml += `<li class="page-item"><a class="page-link" href="#" data-page="${data.current_page - 1}">Previous</a></li>`;
        }
        for (let i = 1; i <= data.last_page; i++) {
            paginationHtml += `<li class="page-item ${i == data.current_page ? 'active' : ''}">
                <a class="page-link" href="#" data-page="${i}">${i}</a>
            </li>`;
        }
        if (data.current_page < data.last_page) {
            paginationHtml += `<li class="page-item"><a class="page-link" href="#" data-page="${data.current_page + 1}">Next</a></li>`;
        }
        paginationHtml += '</ul>';
    }
    paginationContainer.html(paginationHtml);
}



/** Date 13-02-2026 */

function getStudentAllSections(classId, callback) {
    const loader = $('#loader');
    const sectionSelect = $('#st_section_id');

    // Reset section dropdown if no class selected
    if (!classId) {
        sectionSelect.prop('disabled', true).html('<option value="">Select class first</option>');
        return;
    }

    loader.show();
    sectionSelect.prop('disabled', true).html('<option value="">Loading sections...</option>');
    $.ajax({
        url: siteUrl + '/student/sections',
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
function getStAllStudents(classId, sectionId) {

    const loader = $('#loader');
    const studentSelect = $('#st_student_id');

    // Reset student dropdown if no class or section selected
    if (!classId && !sectionId) {
        studentSelect.prop('disabled', true).html('<option value="">Select class and section first</option>');
        return;
    }

    loader.show();
    studentSelect.prop('disabled', true).html('<option value="">Loading students...</option>');
    $.ajax({
        url: siteUrl + '/student/students',
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
function studentUpdatePaginationControls(data) {
    var paginationHtml = '';
    var paginationContainer = $('#std-pagination');

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
function getStudentWithoutAllSections(classId, callback) {
    const loader = $('#loader');
    const sectionSelect = $('#st_section_id');

    // Reset section dropdown if no class selected
    if (!classId) {
        sectionSelect.prop('disabled', true).html('<option value="">Select class first</option>');
        return;
    }
    loader.show();
    sectionSelect.prop('disabled', true).html('<option value="">Loading sections...</option>');
    $.ajax({
        url: siteUrl + '/student/sections',
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
function getStudentsWithoutAll(classId, sectionId) {

    const loader = $('#loader');
    const studentSelect = $('#st_student_id');

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
        url: siteUrl + '/student/students',
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
