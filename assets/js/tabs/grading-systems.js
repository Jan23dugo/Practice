// Grading Systems Tab Functionality
let gradeRowCount = 0;
let editGradeRowCount = 0;

function openAddGradingModal() {
    document.getElementById('addGradingForm').reset();
    document.getElementById('gradesRows').innerHTML = '';
    gradeRowCount = 0;
    openModal('addGradingModal');
}

function openEditGradingModal(university_name) {
    // Get all grades for this system
    const grades = gradingSystemsData[university_name] || [];
    // Separate regular and special grades
    const regularGrades = grades.filter(g => g.is_special_grade == 0);
    const specialGrades = grades.filter(g => g.is_special_grade == 1);

    // Set grading system name and code
    document.getElementById('edit_university_name').value = university_name;
    document.getElementById('edit_university_code').value = grades[0]?.university_code || '';
    document.getElementById('edit_original_university_name').value = university_name;

    // Clear and repopulate grade rows
    const editGradesRows = document.getElementById('editGradesRows');
    editGradesRows.innerHTML = '';
    let editGradeRowCount = 0;
    regularGrades.forEach(grade => {
        const template = `
            <div class="grade-row" data-index="${editGradeRowCount}">
                <div class="col-grade">
                    <input type="text" class="form-control" 
                        name="edit_grades[${editGradeRowCount}][value]" value="${grade.grade_value}" required
                        pattern="^([1-5](\\.00|\\.25|\\.50|\\.75)?|[A-Ea-e])$"
                        oninput="validateGradeValue(this)">
                </div>
                <div class="col-desc">
                    <input type="text" class="form-control" 
                        name="edit_grades[${editGradeRowCount}][description]" value="${grade.description}" required>
                </div>
                <div class="col-range">
                    <div class="range-inputs" style="display: flex; gap: 5px; align-items: center;">
                        <input type="number" step="0.01" class="form-control" 
                            name="edit_grades[${editGradeRowCount}][min]" value="${grade.min_percentage}" required
                            min="0" max="100" style="width: 45%">
                        <span>-</span>
                        <input type="number" step="0.01" class="form-control" 
                            name="edit_grades[${editGradeRowCount}][max]" value="${grade.max_percentage}" required
                            min="0" max="100" style="width: 45%">
                    </div>
                </div>
                <div class="col-action">
                    <button type="button" class="btn-delete" onclick="removeEditGradeRow(this)">
                        <i class="material-symbols-rounded">delete</i>
                    </button>
                </div>
            </div>
        `;
        editGradesRows.insertAdjacentHTML('beforeend', template);
        editGradeRowCount++;
    });
    window.editGradeRowCount = editGradeRowCount;

    // Set special grades checkboxes
    document.getElementById('edit_grade_drp').checked = specialGrades.some(g => g.grade_value === 'DRP');
    document.getElementById('edit_grade_od').checked = specialGrades.some(g => g.grade_value === 'OD');
    document.getElementById('edit_grade_ud').checked = specialGrades.some(g => g.grade_value === 'UD');
    document.getElementById('edit_grade_na').checked = specialGrades.some(g => g.grade_value === '*');

    openModal('editGradingModal');
}

function openDeleteGradingModal(university_name) {
    document.getElementById('delete_university_name').value = university_name;
    document.getElementById('delete_university_name_display').textContent = university_name;
    
    // Get university code from the data
    const grades = gradingSystemsData[university_name] || [];
    const university_code = grades[0]?.university_code || '-';
    document.getElementById('delete_university_code_display').textContent = university_code;
    
    openModal('deleteGradingModal');
}

function viewGradingSystem(university_name) {
    const grades = gradingSystemsData[university_name] || [];
    const regularGrades = grades.filter(g => g.is_special_grade == 0);
    const specialGrades = grades.filter(g => g.is_special_grade == 1);

    // Set main info
    document.getElementById('view_university_name').textContent = university_name;
    document.getElementById('view_university_code').textContent = grades[0]?.university_code || '-';

    // Populate regular grades
    const viewGradesRows = document.getElementById('viewGradesRows');
    viewGradesRows.innerHTML = '';
    regularGrades.forEach(grade => {
        const template = `
            <div class="grade-row">
                <div class="col-grade">${grade.grade_value}</div>
                <div class="col-desc">${grade.description}</div>
                <div class="col-range">${grade.min_percentage} - ${grade.max_percentage}%</div>
            </div>
        `;
        viewGradesRows.insertAdjacentHTML('beforeend', template);
    });

    // Populate special grades
    const viewSpecialGrades = document.getElementById('viewSpecialGrades');
    viewSpecialGrades.innerHTML = '';
    specialGrades.forEach(grade => {
        const template = `
            <div class="special-grade-item">
                <span class="special-grade-label">${grade.grade_value}</span>
                <span class="special-grade-desc">${grade.description}</span>
            </div>
        `;
        viewSpecialGrades.insertAdjacentHTML('beforeend', template);
    });

    openModal('viewGradingModal');
}

function addGradeRow() {
    const template = `
        <div class="grade-row" data-index="${gradeRowCount}">
            <div class="col-grade">
                <input type="text" class="form-control" 
                    name="grades[${gradeRowCount}][value]" placeholder="e.g., 1.00 or A" required
                    pattern="^([1-5](\\.00|\\.25|\\.50|\\.75)?|[A-Ea-e])$"
                    oninput="validateGradeValue(this)">
            </div>
            <div class="col-desc">
                <input type="text" class="form-control" 
                    name="grades[${gradeRowCount}][description]" 
                    placeholder="e.g., Excellent, Satisfactory, etc." required>
            </div>
            <div class="col-range">
                <div class="range-inputs" style="display: flex; gap: 5px; align-items: center;">
                    <input type="number" step="0.01" class="form-control" 
                        name="grades[${gradeRowCount}][min]" placeholder="e.g., 75" required
                        min="0" max="100" style="width: 45%">
                    <span>-</span>
                    <input type="number" step="0.01" class="form-control" 
                        name="grades[${gradeRowCount}][max]" placeholder="e.g., 100" required
                        min="0" max="100" style="width: 45%">
                </div>
            </div>
            <div class="col-action">
                <button type="button" class="btn-delete" onclick="removeGradeRow(this)">
                    <i class="material-symbols-rounded">delete</i>
                </button>
            </div>
        </div>
    `;
    const gradesRows = document.getElementById('gradesRows');
    // Remove empty state if present
    const emptyMessage = gradesRows.querySelector('.empty-grades-message');
    if (emptyMessage) emptyMessage.remove();
    gradesRows.insertAdjacentHTML('beforeend', template);
    gradeRowCount++;
}

function addEditGradeRow() {
    let editGradeRowCount = window.editGradeRowCount || 0;
    const template = `
        <div class="grade-row" data-index="${editGradeRowCount}">
            <div class="col-grade">
                <input type="text" class="form-control" 
                    name="edit_grades[${editGradeRowCount}][value]" placeholder="e.g., 1.00 or A" required
                    pattern="^([1-5](\\.00|\\.25|\\.50|\\.75)?|[A-Ea-e])$"
                    oninput="validateGradeValue(this)">
            </div>
            <div class="col-desc">
                <input type="text" class="form-control" 
                    name="edit_grades[${editGradeRowCount}][description]" 
                    placeholder="e.g., Excellent, Satisfactory, etc." required>
            </div>
            <div class="col-range">
                <div class="range-inputs" style="display: flex; gap: 5px; align-items: center;">
                    <input type="number" step="0.01" class="form-control" 
                        name="edit_grades[${editGradeRowCount}][min]" placeholder="e.g., 75" required
                        min="0" max="100" style="width: 45%">
                    <span>-</span>
                    <input type="number" step="0.01" class="form-control" 
                        name="edit_grades[${editGradeRowCount}][max]" placeholder="e.g., 100" required
                        min="0" max="100" style="width: 45%">
                </div>
            </div>
            <div class="col-action">
                <button type="button" class="btn-delete" onclick="removeEditGradeRow(this)">
                    <i class="material-symbols-rounded">delete</i>
                </button>
            </div>
        </div>
    `;
    const editGradesRows = document.getElementById('editGradesRows');
    // Remove empty state if present
    const emptyMessage = editGradesRows.querySelector('.empty-grades-message');
    if (emptyMessage) emptyMessage.remove();
    editGradesRows.insertAdjacentHTML('beforeend', template);
    window.editGradeRowCount = editGradeRowCount + 1;
}

function removeGradeRow(button) {
    const row = button.closest('.grade-row');
    row.remove();
    // Show empty state message if no rows left
    const rows = document.querySelectorAll('#gradesRows .grade-row');
    if (rows.length === 0) {
        document.getElementById('gradesRows').innerHTML = `<div class='empty-grades-message'><p>No grades added yet. Click "Add Grade" to begin.</p></div>`;
    }
}

function removeEditGradeRow(button) {
    const row = button.closest('.grade-row');
    row.remove();
    // Show empty state message if no rows left
    const rows = document.querySelectorAll('#editGradesRows .grade-row');
    if (rows.length === 0) {
        document.getElementById('editGradesRows').innerHTML = `<div class='empty-grades-message'><p>No grades added yet. Click "Add Grade" to begin.</p></div>`;
    }
}

function validateGradeValue(input) {
    const value = input.value;
    const pattern = /^([1-5](\.00|\.25|\.50|\.75)?|[A-Ea-e])$/;
    if (!pattern.test(value)) {
        input.setCustomValidity('Please enter a valid grade (e.g., 1.00, 1.25, 1.50, 1.75, 2.00, etc. or A, B, C, D, E)');
    } else {
        input.setCustomValidity('');
    }
}

// Initialize empty state for grade rows
document.addEventListener('DOMContentLoaded', function() {
    const gradesRows = document.getElementById('gradesRows');
    if (gradesRows && gradesRows.children.length === 0) {
        gradesRows.innerHTML = `<div class='empty-grades-message'><p>No grades added yet. Click "Add Grade" to begin.</p></div>`;
    }
}); 