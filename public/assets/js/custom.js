document.addEventListener('DOMContentLoaded', function () {
    var deleteButtons = document.querySelectorAll('.delete-form-btn');
    deleteButtons.forEach(function (button) {
        button.addEventListener('click', function (event) {
            event.preventDefault();
            Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    var form = this.closest('form');
                    var url = form.action;
                    var token = form.querySelector('input[name="_token"]').value;
                    fetch(url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': token
                        }
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.status === 'success') {
                                Swal.fire(
                                    'Deleted!',
                                    data.message,
                                    'success'
                                ).then(() => {
                                    location.reload();
                                });
                            } else {
                                Swal.fire(
                                    'Error!',
                                    data.message,
                                    'error'
                                );
                            }
                        })
                        .catch(error => {
                            Swal.fire(
                                'Error!',
                                'An error occurred while deleting.',
                                'error'
                            );
                        });
                }
            });
        });
    });
});
var loader = $('.loader');
//get class and section
function getClassSection(initialClassId, initialSectionId = '', classSelect = '', sectionSelect = '') {
    var classSelected = classSelect || $('#class_id');
    var sectionSelected = sectionSelect || $('#section_id');
    var loader = $('#loader');

    // Store the initial section ID - will be cleared on class change
    var storedInitialSectionId = initialSectionId;

    function fetchSections(classId, shouldSelectInitial = false) {
        if (!classId) {
            sectionSelected.empty().append('<option value="">Select Section</option>');
            return;
        }

        if (loader.length) loader.show();
        sectionSelected.prop('disabled', true);

        $.ajax({
            url: siteUrl + '/sections',
            type: 'GET',
            dataType: 'JSON',
            data: { class_id: classId },
            success: function(data) {
                sectionSelected.empty();

                if (data.status === "success" && data.data && Object.keys(data.data).length > 0) {
                    sectionSelected.append('<option value="">Select Section</option>');
                    $.each(data.data, function(id, name) {
                        sectionSelected.append('<option value="' + id + '">' + name + '</option>');
                    });

                    // Only set initial section if flag is true and we have an initial value
                    if (shouldSelectInitial && storedInitialSectionId) {
                        sectionSelected.val(storedInitialSectionId);
                    }
                } else if (data.status === "error") {
                    sectionSelected.append('<option value="">No sections available</option>');
                } else {
                    sectionSelected.append('<option value="">No sections available</option>');
                }
            },
            error: function(xhr) {
                sectionSelected.empty().append('<option value="">No sections available</option>');
                console.error('Error fetching sections:', xhr.responseJSON?.message || xhr.statusText);
            },
            complete: function() {
                if (loader.length) loader.hide();
                sectionSelected.prop('disabled', false);
            }
        });
    }

    // Initialize sections on page load
    var selectedClassId = classSelected.val();
    if (selectedClassId) {
        fetchSections(selectedClassId, true);
    }

    // Handle class change event
    classSelected.off('change.getClassSection').on('change.getClassSection', function() {
        var classId = $(this).val();
        // Clear the stored initial section when class changes
        storedInitialSectionId = '';
        fetchSections(classId, false);
    });
}
// get session
function getSession(initialSessionId) {
    var sessionSelect = $('#session_id');
    var initialSessionId = initialSessionId;
    function fetchSession() {
        loader.show();
        $.ajax({
            url: siteUrl + '/sessions',
            type: 'GET',
            dataType: 'JSON',
            success: function (data) {
                sessionSelect.empty();
                sessionSelect.append('<option value="">Select Session</option>');
                $.each(data.data, function (id, name) {
                    sessionSelect.append('<option value="' + id + '">' + name + '</option>')
                });
                if (initialSessionId) {
                    sessionSelect.val(initialSessionId);
                }
            },
            complete: function () {
                loader.hide();
            },
            error: function (data) {
                $.each(data.message, function (error) {
                    console.error('Error fetching sections:', error);
                });
            }
        });
    }
    // sessionSelect.change(function () {
    //     loader.show();
    // });
    fetchSession();
}
// get subject on the basis of class
function getClassSubject(initialClassesId, initialSubjectId, subjectGroupSection = '') {
    var classSelect = $('#class_id');
    var subjectSelect = $('#subject_id');
    var classId = initialClassesId;
    var subjectId = initialSubjectId;
    var subjectGroupSection = $(subjectGroupSection);
    function fetchSubjects(classId, subjectsG = '') {
        if (classId) {
            loader.show();
            $.ajax({
                url: siteUrl + '/subjects',
                type: 'GET',
                dataType: 'JSON',
                data: {
                    class_id: classId,
                    subject: subjectsG,
                },
                success: function (data) {
                    subjectSelect.empty();
                    if (data.data && Object.keys(data.data).length > 0) {
                        subjectSelect.append('<option value="">Select Subject</option>');
                        $.each(data.data, function (id, name) {
                            subjectSelect.append('<option value="' + id + '">' + name + '</option>');
                        });
                    } else {
                        subjectSelect.append('<option value="">No subjects available</option>');
                    }
                    if (initialSubjectId) {
                        subjectSelect.val(initialSubjectId);
                    }
                },
                complete: function () {
                    loader.hide();
                },
                error: function (data) {
                    $.each(data.message, function (error) {
                        console.error('Error fetching sections:', error);
                    });
                }
            });
        } else {
            subjectSelect.empty();
            subjectSelect.append('<option value="">Select Subject</option>');
        }
    }
    var subjectsGValue = subjectGroupSection.val();
    fetchSubjects(classId, subjectsGValue);
   /*  var selectedSubjectId = classSelect.val();
    if (selectedSubjectId) {
        fetchSubjects(selectedSubjectId);
    } */
    classSelect.change(function () {
        var classId = $(this).val();
        var subjectsGValue = subjectGroupSection.val();
        loader.show();
        fetchSubjects(classId, subjectsGValue);
    });
}
//get exam
function getExams(initialExamId) {
    var examSelected = $('#exam_id');
    loader.show();
    $.ajax({
        url: siteUrl + '/exams',
        type: 'GET',
        dataType: 'JSON',
        success: function (data) {
            examSelected.empty();
            examSelected.append('<option value="">Select Exam</option>');
            $.each(data.data, function (id, name) {
                examSelected.append('<option value="' + id + '">' + name + '</option>');
            });
            if (initialExamId) {
                examSelected.val(initialExamId);
            }
        },
        complete: function () {
            loader.hide();
        },
        error: function (data) {
            $.each(data.message, function (error) {
                console.error('Error fetching exams:', error);
            })
        }
    });
}
// get state and district
function getStateDistrict(stateSelect, initialStateId, districtSelct = '', initialDistrictId = '') {
    var stateSelect = stateSelect;
    var districtSelect = districtSelct;
    var initialDistrictId = initialDistrictId;
    var initialStateId = initialStateId;
    var stateId = initialStateId;
    fetchDistricts(stateId);
    function fetchDistricts(stateId) {
        if (stateId) {
            loader.show();
            $.ajax({
                url: siteUrl + '/districts',
                type: 'GET',
                dataType: 'JSON',
                data: {
                    state_id: stateId
                },
                success: function (data) {
                    districtSelect.empty();
                    districtSelect.append('<option value="">Select District</option>');
                    if (data.data && Object.keys(data.data).length > 0) {
                        $.each(data.data, function (id, name) {
                            districtSelect.append('<option value="' + id + '">' + name + '</option>');
                        });
                    } else{
                        districtSelect.append('<option value="">No District found</option>');
                    }
                    if (initialDistrictId) {
                        districtSelect.val(initialDistrictId);
                    }
                },
                complete: function () {
                    loader.hide();
                },
                error: function (data) {
                    $.each(data.message, function (error) {
                        console.error('Error fetching sections:', error);
                    });
                }
            });
        } else {
            districtSelect.empty();
            districtSelect.append('<option value="">Select District</option>');
        }
    }
    var selectedStateId = stateSelect.val();
    if (selectedStateId) {
        fetchDistricts(selectedStateId);
    }
    stateSelect.change(function () {
        var stateId = $(this).val();
        loader.show();
        fetchDistricts(stateId);
    });
}

        document.addEventListener("DOMContentLoaded", function() {
            const header = document.querySelector('.header'); // change to your class
            const headerHeight = header.offsetHeight;
            document.body.style.paddingTop = headerHeight + 'px';
        });

document.addEventListener("DOMContentLoaded", function () {
const body = document.querySelector("body");
const sidebar = document.querySelector(".sidebar");
const submenuItems = document.querySelectorAll(".submenu_item");
const sidebarOpen = document.querySelector("#sidebarOpen");
const sidebarClose = document.querySelector(".collapse_sidebar");
const sidebarExpand = document.querySelector(".expand_sidebar");
sidebarOpen.addEventListener("click", () => sidebar.classList.toggle("close"));






submenuItems.forEach((item, index) => {
  item.addEventListener("click", () => {
    item.classList.toggle("show_submenu");
    submenuItems.forEach((item2, index2) => {
      if (index !== index2) {
        item2.classList.remove("show_submenu");
      }
    });
  });
});

if (window.innerWidth < 768) {
  sidebar.classList.add("close");
} else {
  sidebar.classList.remove("close");
}
});