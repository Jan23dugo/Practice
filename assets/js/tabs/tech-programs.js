// Tech Programs Tab Functionality
function openAddTechProgramModal() {
    document.getElementById('addTechProgramForm').reset();
    openModal('addTechProgramModal');
}

function openEditTechProgramModal(program) {
    document.getElementById('edit_program_id').value = program.id;
    document.getElementById('edit_program_name').value = program.name;
    document.getElementById('edit_program_code').value = program.code;
    document.getElementById('edit_is_active').checked = program.is_active == 1;
    openModal('editTechProgramModal');
}

function confirmDeleteTechProgram(id) {
    document.getElementById('delete_program_id').value = id;
    openModal('deleteTechProgramModal');
}

// Add event listeners for form submissions
document.addEventListener('DOMContentLoaded', function() {
    // Add Tech Program Form
    const addTechProgramForm = document.getElementById('addTechProgramForm');
    if (addTechProgramForm) {
        addTechProgramForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            formData.append('action', 'add_tech_program');
            
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
                alert('An error occurred while adding the tech program.');
            });
        });
    }

    // Edit Tech Program Form
    const editTechProgramForm = document.getElementById('editTechProgramForm');
    if (editTechProgramForm) {
        editTechProgramForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            formData.append('action', 'update_tech_program');
            
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
                alert('An error occurred while updating the tech program.');
            });
        });
    }

    // Delete Tech Program Form
    const deleteTechProgramForm = document.getElementById('deleteTechProgramForm');
    if (deleteTechProgramForm) {
        deleteTechProgramForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            formData.append('action', 'delete_tech_program');
            
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
                alert('An error occurred while deleting the tech program.');
            });
        });
    }
}); 