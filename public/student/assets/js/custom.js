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
