// Coded Courses Tab Functionality
function openAddCodedCourseModal() {
    document.getElementById('addCodedCourseForm').reset();
    openModal('addCodedCourseModal');
}

function openEditCodedCourseModal(course) {
    document.getElementById('edit_course_id').value = course.id;
    document.getElementById('edit_subject_code').value = course.subject_code;
    document.getElementById('edit_subject_description').value = course.subject_description;
    document.getElementById('edit_program').value = course.program;
    document.getElementById('edit_units').value = course.units;
    openModal('editCodedCourseModal');
}

function confirmDeleteCodedCourse(id) {
    document.getElementById('delete_course_id').value = id;
    openModal('deleteCodedCourseModal');
}

function filterCodedCourses() {
    const programFilter = document.getElementById('programFilter').value;
    const searchText = document.getElementById('searchInput').value.toLowerCase();
    const table = document.getElementById('codedCoursesTable');
    const rows = table.getElementsByTagName('tr');
    let hasVisibleRows = false;

    for (let i = 1; i < rows.length; i++) {
        const row = rows[i];
        const programCell = row.cells[2]; // Program column
        const program = programCell.textContent.trim();
        const rowText = row.textContent.toLowerCase();

        const matchesProgram = programFilter === 'all' || program === programFilter;
        const matchesSearch = searchText === '' || rowText.includes(searchText);

        if (matchesProgram && matchesSearch) {
            row.style.display = '';
            hasVisibleRows = true;
        } else {
            row.style.display = 'none';
        }
    }

    // Show/hide no results message
    const noResults = document.getElementById('noResults');
    if (noResults) {
        noResults.style.display = hasVisibleRows ? 'none' : 'block';
    }
}

// Add event listeners for form submissions
document.addEventListener('DOMContentLoaded', function() {
    // Add Coded Course Form
    const addCodedCourseForm = document.getElementById('addCodedCourseForm');
    if (addCodedCourseForm) {
        addCodedCourseForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            formData.append('action', 'add_coded_course');
            
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(() => {
                window.location.reload();
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while adding the coded course.');
            });
        });
    }

    // Edit Coded Course Form
    const editCodedCourseForm = document.getElementById('editCodedCourseForm');
    if (editCodedCourseForm) {
        editCodedCourseForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            formData.append('action', 'update_coded_course');
            
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(() => {
                window.location.reload();
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while updating the coded course.');
            });
        });
    }

    // Delete Coded Course Form
    const deleteCodedCourseForm = document.getElementById('deleteCodedCourseForm');
    if (deleteCodedCourseForm) {
        deleteCodedCourseForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            formData.append('action', 'delete_coded_course');
            
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(() => {
                window.location.reload();
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while deleting the coded course.');
            });
        });
    }

    // Add event listener for program filter
    const programFilter = document.getElementById('programFilter');
    if (programFilter) {
        programFilter.addEventListener('change', filterCodedCourses);
    }

    // Add event listener for search input
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('input', filterCodedCourses);
    }
}); 