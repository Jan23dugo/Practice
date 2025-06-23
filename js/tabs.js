// Tab functionality
document.addEventListener('DOMContentLoaded', function() {
    const tabButtons = document.querySelectorAll('.tab-btn');
    const tabContents = document.querySelectorAll('.tab-content');

    tabButtons.forEach(button => {
        button.addEventListener('click', () => {
            // Remove active class from all buttons and contents
            tabButtons.forEach(btn => btn.classList.remove('active'));
            tabContents.forEach(content => content.classList.remove('active'));

            // Add active class to clicked button and corresponding content
            button.classList.add('active');
            const tabId = button.getAttribute('data-tab');
            document.getElementById(tabId).classList.add('active');
        });
    });
});

// Modal functionality
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (!modal) return;
    // Remove any lingering hidden inputs from forms inside the modal
    const forms = modal.querySelectorAll('form');
    forms.forEach(form => {
        const hiddenInputs = form.querySelectorAll('input[type="hidden"]');
        hiddenInputs.forEach(input => input.remove());
    });
    modal.style.display = 'block';
    // Optionally add a class for animation
    modal.classList.add('show');
    document.body.style.overflow = 'hidden'; // Prevent background scroll
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (!modal) return;
    modal.style.display = 'none';
    modal.classList.remove('show');
    document.body.style.overflow = '';
    // Remove any dynamically added hidden inputs from forms inside the modal
    const forms = modal.querySelectorAll('form');
    forms.forEach(form => {
        const hiddenInputs = form.querySelectorAll('input[type="hidden"]');
        hiddenInputs.forEach(input => input.remove());
        if (typeof form.reset === 'function') form.reset();
    });
}

// Close modal when clicking outside
window.onclick = function(event) {
    if (event.target.classList && event.target.classList.contains('modal')) {
        event.target.style.display = 'none';
        event.target.classList.remove('show');
        document.body.style.overflow = '';
    }
}

// Search functionality
function searchTable(input, tableId) {
    const filter = input.value.toUpperCase();
    const table = document.getElementById(tableId);
    const tr = table.getElementsByTagName('tr');

    for (let i = 1; i < tr.length; i++) {
        const td = tr[i].getElementsByTagName('td');
        let found = false;

        for (let j = 0; j < td.length - 1; j++) {
            const cell = td[j];
            if (cell) {
                const txtValue = cell.textContent || cell.innerText;
                if (txtValue.toUpperCase().indexOf(filter) > -1) {
                    found = true;
                    break;
                }
            }
        }

        tr[i].style.display = found ? '' : 'none';
    }
}

// Add/Edit functionality
function openAddModal(type) {
    const modal = document.getElementById('formModal');
    const title = document.getElementById('modalTitle');
    const form = document.getElementById('itemForm');
    const codeLabel = document.getElementById('codeLabel');
    const nameLabel = document.getElementById('nameLabel');

    title.textContent = `Add ${type.charAt(0).toUpperCase() + type.slice(1).replace('_', ' ')}`;
    codeLabel.textContent = `${type.charAt(0).toUpperCase() + type.slice(1).replace('_', ' ')} Code:`;
    nameLabel.textContent = `${type.charAt(0).toUpperCase() + type.slice(1).replace('_', ' ')} Name:`;
    form.action = `?action=add_${type}`;

    openModal('formModal');
}

function openEditModal(type, id, code, name) {
    const modal = document.getElementById('formModal');
    const title = document.getElementById('modalTitle');
    const form = document.getElementById('itemForm');
    const codeInput = document.getElementById('codeInput');
    const nameInput = document.getElementById('nameInput');
    const codeLabel = document.getElementById('codeLabel');
    const nameLabel = document.getElementById('nameLabel');

    title.textContent = `Edit ${type.charAt(0).toUpperCase() + type.slice(1).replace('_', ' ')}`;
    codeLabel.textContent = `${type.charAt(0).toUpperCase() + type.slice(1).replace('_', ' ')} Code:`;
    nameLabel.textContent = `${type.charAt(0).toUpperCase() + type.slice(1).replace('_', ' ')} Name:`;
    form.action = `?action=edit_${type}`;

    codeInput.value = code;
    nameInput.value = name;
    form.innerHTML += `<input type="hidden" name="id" value="${id}">`;

    openModal('formModal');
}

// Delete functionality
function openDeleteModal(type, id) {
    const modal = document.getElementById('deleteModal');
    const form = document.getElementById('deleteForm');
    form.action = `?action=delete_${type}`;
    form.innerHTML += `<input type="hidden" name="id" value="${id}">`;
    openModal('deleteModal');
}

// Grading System functionality
function addGradeRow() {
    const container = document.getElementById('gradesRows');
    const row = document.createElement('div');
    row.className = 'grade-row';
    row.innerHTML = `
        <div class="col-grade">
            <input type="text" name="grades[value][]" class="form-control" required>
        </div>
        <div class="col-desc">
            <input type="text" name="grades[description][]" class="form-control" required>
        </div>
        <div class="col-range">
            <input type="text" name="grades[range][]" class="form-control" required>
        </div>
        <div class="col-action">
            <button type="button" class="btn-delete" onclick="removeGradeRow(this)">
                <i class="material-symbols-rounded">delete</i>
            </button>
        </div>
    `;
    container.appendChild(row);
}

function addEditGradeRow() {
    const container = document.getElementById('editGradesRows');
    const row = document.createElement('div');
    row.className = 'grade-row';
    row.innerHTML = `
        <div class="col-grade">
            <input type="text" name="grades[value][]" class="form-control" required>
        </div>
        <div class="col-desc">
            <input type="text" name="grades[description][]" class="form-control" required>
        </div>
        <div class="col-range">
            <input type="text" name="grades[range][]" class="form-control" required>
        </div>
        <div class="col-action">
            <button type="button" class="btn-delete" onclick="removeGradeRow(this)">
                <i class="material-symbols-rounded">delete</i>
            </button>
        </div>
    `;
    container.appendChild(row);
}

function removeGradeRow(button) {
    button.closest('.grade-row').remove();
}

// Tech Program functionality
function openAddTechProgramModal() {
    openModal('addTechProgramModal');
}

function openEditTechProgram(program) {
    const modal = document.getElementById('editTechProgramModal');
    document.getElementById('edit_tech_program_id').value = program.id;
    document.getElementById('edit_program_name').value = program.program_name;
    document.getElementById('edit_program_code').value = program.program_code;
    document.getElementById('edit_is_active').checked = program.is_active === '1';
    openModal('editTechProgramModal');
}

function confirmDeleteTechProgram(id) {
    document.getElementById('delete_tech_program_id').value = id;
    openModal('deleteTechProgramModal');
}

// Grading System view/edit/delete functionality
function viewGradingSystem(universityName) {
    // Fetch grading system data via AJAX
    fetch(`get_grading_system.php?university_name=${encodeURIComponent(universityName)}`)
        .then(response => response.json())
        .then(data => {
            document.getElementById('view_university_name').textContent = data.university_name;
            document.getElementById('view_university_code').textContent = data.university_code;
            
            // Populate regular grades
            const gradesContainer = document.getElementById('viewGradesRows');
            gradesContainer.innerHTML = '';
            data.grades.forEach(grade => {
                const row = document.createElement('div');
                row.className = 'grade-row';
                row.innerHTML = `
                    <div class="col-grade">${grade.grade_value}</div>
                    <div class="col-desc">${grade.description}</div>
                    <div class="col-range">${grade.percentage_range}</div>
                `;
                gradesContainer.appendChild(row);
            });

            // Populate special grades
            const specialGradesContainer = document.getElementById('viewSpecialGrades');
            specialGradesContainer.innerHTML = '';
            data.special_grades.forEach(grade => {
                const item = document.createElement('div');
                item.className = 'special-grade-item';
                item.innerHTML = `
                    <span class="special-grade-label">${grade.grade_value}</span>
                    <span class="special-grade-desc">${grade.description}</span>
                `;
                specialGradesContainer.appendChild(item);
            });

            openModal('viewGradingModal');
        });
}

function openEditGradingModal(universityName) {
    // Fetch grading system data via AJAX
    fetch(`get_grading_system.php?university_name=${encodeURIComponent(universityName)}`)
        .then(response => response.json())
        .then(data => {
            document.getElementById('edit_original_university_name').value = data.university_name;
            document.getElementById('edit_university_name').value = data.university_name;
            document.getElementById('edit_university_code').value = data.university_code;
            
            // Populate regular grades
            const gradesContainer = document.getElementById('editGradesRows');
            gradesContainer.innerHTML = '';
            data.grades.forEach(grade => {
                const row = document.createElement('div');
                row.className = 'grade-row';
                row.innerHTML = `
                    <div class="col-grade">
                        <input type="text" name="grades[value][]" class="form-control" value="${grade.grade_value}" required>
                    </div>
                    <div class="col-desc">
                        <input type="text" name="grades[description][]" class="form-control" value="${grade.description}" required>
                    </div>
                    <div class="col-range">
                        <input type="text" name="grades[range][]" class="form-control" value="${grade.percentage_range}" required>
                    </div>
                    <div class="col-action">
                        <button type="button" class="btn-delete" onclick="removeGradeRow(this)">
                            <i class="material-symbols-rounded">delete</i>
                        </button>
                    </div>
                `;
                gradesContainer.appendChild(row);
            });

            // Populate special grades
            data.special_grades.forEach(grade => {
                const checkbox = document.getElementById(`edit_grade_${grade.grade_value.toLowerCase()}`);
                if (checkbox) {
                    checkbox.checked = true;
                }
            });

            openModal('editGradingModal');
        });
}

function openDeleteGradingModal(universityName) {
    // Fetch university code via AJAX
    fetch(`get_university_code.php?university_name=${encodeURIComponent(universityName)}`)
        .then(response => response.json())
        .then(data => {
            document.getElementById('delete_university_name').value = universityName;
            document.getElementById('delete_university_name_display').textContent = universityName;
            document.getElementById('delete_university_code_display').textContent = data.university_code;
            openModal('deleteGradingModal');
        });
} 