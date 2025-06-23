// University Programs Tab Functionality
function openAddModal(type) {
    const modal = document.getElementById('formModal');
    const form = document.getElementById('itemForm');
    const title = document.getElementById('modalTitle');
    const codeLabel = document.getElementById('codeLabel');
    const nameLabel = document.getElementById('nameLabel');
    const submitBtn = document.getElementById('submitBtn');

    // Set up modal based on type
    if (type === 'universities') {
        title.textContent = 'Add University';
        codeLabel.textContent = 'University Code:';
        nameLabel.textContent = 'University Name:';
    } else if (type === 'university_programs') {
        title.textContent = 'Add University Program';
        codeLabel.textContent = 'Program Code:';
        nameLabel.textContent = 'Program Name:';
    } else if (type === 'programs') {
        title.textContent = 'Add Applied Program';
        codeLabel.textContent = 'Program Code:';
        nameLabel.textContent = 'Program Name:';
    }
    
    // Reset form fields
    form.reset();
    
    // Set form action for adding
    form.action = `?action=add&type=${type}`;
    
    // Set button text
    submitBtn.textContent = 'Add';
    
    // Show modal
    openModal('formModal');
}

function openEditModal(type, id, code, name) {
    const modal = document.getElementById('formModal');
    const form = document.getElementById('itemForm');
    const title = document.getElementById('modalTitle');
    const codeInput = document.getElementById('codeInput');
    const nameInput = document.getElementById('nameInput');
    const codeLabel = document.getElementById('codeLabel');
    const nameLabel = document.getElementById('nameLabel');
    const submitBtn = document.getElementById('submitBtn');
    
    // Set up modal based on type
    if (type === 'universities') {
        title.textContent = 'Edit University';
        codeLabel.textContent = 'University Code:';
        nameLabel.textContent = 'University Name:';
    } else if (type === 'university_programs') {
        title.textContent = 'Edit University Program';
        codeLabel.textContent = 'Program Code:';
        nameLabel.textContent = 'Program Name:';
    } else if (type === 'programs') {
        title.textContent = 'Edit Applied Program';
        codeLabel.textContent = 'Program Code:';
        nameLabel.textContent = 'Program Name:';
    }
    
    // Set form values
    codeInput.value = code;
    nameInput.value = name;
    
    // Set form action for editing
    form.action = `?action=edit&type=${type}&id=${id}`;
    
    // Set button text
    submitBtn.textContent = 'Save Changes';
    
    // Show modal
    openModal('formModal');
}

function openDeleteModal(type, id) {
    const confirmBtn = document.getElementById('confirmDeleteBtn');
    
    // Set up delete button action
    confirmBtn.onclick = function() {
        window.location.href = `?action=delete&type=${type}&id=${id}`;
    };
    
    // Show modal
    openModal('deleteModal');
}

// Form validation
document.getElementById('itemForm').addEventListener('submit', function(e) {
    const codeInput = document.getElementById('codeInput');
    const nameInput = document.getElementById('nameInput');
    const submitBtn = document.getElementById('submitBtn');
    let isValid = true;

    // Clear previous errors
    clearFormErrors(this);

    // Validate code
    if (!codeInput.value.trim()) {
        showFormError(codeInput, 'This field is required');
        isValid = false;
    }

    // Validate name
    if (!nameInput.value.trim()) {
        showFormError(nameInput, 'This field is required');
        isValid = false;
    }

    if (isValid) {
        setLoading(submitBtn, true);
    } else {
        e.preventDefault();
    }
});

window.openAddUniversityProgramModal = function() {
    openAddModal('university_programs');
};
window.openAddAppliedProgramModal = function() {
    openAddModal('programs');
}; 