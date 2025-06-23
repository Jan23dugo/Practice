<style>
/* Enhanced Modal Styles */
.modal-header .close {
    position: absolute;
    top: 18px;
    right: 24px;
    width: 38px;
    height: 38px;
    background: transparent;
    color: #fff;
    border: none;
    border-radius: 50%;
    font-size: 28px;
    font-weight: bold;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    box-shadow: none;
    transition: color 0.2s;
    z-index: 2;
}
.modal-header .close:hover {
    background: transparent;
    color: #ececec;
}

.modal-footer .btn-primary, .modal-footer .btn-edit {
    background: linear-gradient(135deg, #75343A 0%, #8B4448 100%);
    color: #fff;
    border: none;
    border-radius: 50px;
    padding: 10px 32px;
    font-size: 1rem;
    font-weight: 600;
    box-shadow: 0 4px 12px rgba(117, 52, 58, 0.13);
    transition: background 0.2s, transform 0.2s, box-shadow 0.2s;
    margin-left: 8px;
}
.modal-footer .btn-primary:hover, .modal-footer .btn-edit:hover {
    background: linear-gradient(135deg, #8B4448 0%, #75343A 100%);
    transform: translateY(-2px) scale(1.04);
    box-shadow: 0 6px 18px rgba(117, 52, 58, 0.18);
}

.modal-footer .btn-secondary {
    background: #f5f5f5;
    color: #75343A;
    border: 1px solid #ddd;
    border-radius: 50px;
    padding: 10px 28px;
    font-size: 1rem;
    font-weight: 500;
    margin-right: 8px;
    transition: background 0.2s, color 0.2s, box-shadow 0.2s, transform 0.2s;
}
.modal-footer .btn-secondary:hover {
    background: #ececec;
    color: #8B4448;
    transform: translateY(-2px) scale(1.04);
    box-shadow: 0 4px 12px rgba(117, 52, 58, 0.10);
}

.modal-content.modal-lg {
    display: flex;
    flex-direction: column;
    max-height: 100vh;
    overflow: hidden;
}
.modal-content .modal-body {
    flex: 1 1 auto;
    overflow-y: auto;
    min-height: 0;
}
.modal-content .modal-footer {
    flex-shrink: 0;
}
</style>

<!-- Add/Edit Modal -->
<div id="formModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 class="modal-title" id="modalTitle">Add Item</h2>
            <button type="button" class="close" onclick="closeModal('formModal')">&times;</button>
        </div>
        <div class="modal-body">
            <form id="itemForm" method="POST" action="">
                <div class="form-group">
                    <label for="codeInput" class="form-label" id="codeLabel">Code:</label>
                    <input type="text" id="codeInput" name="code" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="nameInput" class="form-label" id="nameLabel">Name:</label>
                    <input type="text" id="nameInput" name="name" class="form-control" required>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn-secondary" onclick="closeModal('formModal')">Cancel</button>
            <button type="submit" form="itemForm" class="btn-primary" id="submitBtn">Save</button>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 class="modal-title">Confirm Deletion</h2>
            <button type="button" class="close" onclick="closeModal('deleteModal')">&times;</button>
        </div>
        <div class="modal-body">
            <p>Are you sure you want to delete this item? This action cannot be undone.</p>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn-secondary" onclick="closeModal('deleteModal')">Cancel</button>
            <button type="button" class="btn-primary" id="confirmDeleteBtn">Delete</button>
        </div>
    </div>
</div>

<!-- Add Grading System Modal -->
<div class="modal" id="addGradingModal">
    <div class="modal-content modal-lg">
        <div class="modal-header">
            <h5 class="modal-title">Add New Grading System</h5>
            <button type="button" class="btn-close" onclick="closeModal('addGradingModal')">&times;</button>
        </div>
        <form action="" method="POST" id="addGradingForm">
            <input type="hidden" name="action" value="add_bulk">
            <div class="modal-body">
                <div class="form-group mb-4">
                    <label class="form-label" for="university_name">University Name</label>
                    <input type="text" class="form-control" id="university_name" name="university_name" required
                           placeholder="Enter university name (e.g., University of the Philippines)">
                </div>

                <div class="form-group mb-4">
                    <label class="form-label" for="university_code">University Code</label>
                    <input type="text" class="form-control" id="university_code" name="university_code" required
                           placeholder="Enter university code (e.g., UP, DLSU, ADMU)"
                           pattern="[A-Za-z0-9]+" title="Please enter only letters and numbers">
                    <small class="form-text text-muted">Enter a unique code for the university (letters and numbers only)</small>
                </div>

                <div class="grades-container">
                    <div class="grades-header">
                        <h6>Regular Grades</h6>
                        <button type="button" class="btn-add-grade" onclick="addGradeRow()">
                            <i class="material-symbols-rounded">add</i>
                            Add Grade
                        </button>
                    </div>
                    <div class="grades-table">
                        <div class="grades-table-header">
                            <div class="col-grade">Grade Value</div>
                            <div class="col-desc">Description</div>
                            <div class="col-range">Percentage Range</div>
                            <div class="col-action">Action</div>
                        </div>
                        <div class="grades-rows-container" id="gradesRows">
                            <!-- Grade rows will be populated here -->
                        </div>
                    </div>
                </div>

                <div class="special-grades-section mt-4">
                    <h6>Special Grades</h6>
                    <div class="special-grades-grid">
                        <div class="special-grade-item">
                            <input type="checkbox" id="grade_drp" name="special_grades[DRP]" class="special-grade-check">
                            <label for="grade_drp">
                                <span class="special-grade-label">DRP</span>
                                <span class="special-grade-desc">Dropped</span>
                            </label>
                        </div>
                        <div class="special-grade-item">
                            <input type="checkbox" id="grade_od" name="special_grades[OD]" class="special-grade-check">
                            <label for="grade_od">
                                <span class="special-grade-label">OD</span>
                                <span class="special-grade-desc">Officially Dropped</span>
                            </label>
                        </div>
                        <div class="special-grade-item">
                            <input type="checkbox" id="grade_ud" name="special_grades[UD]" class="special-grade-check">
                            <label for="grade_ud">
                                <span class="special-grade-label">UD</span>
                                <span class="special-grade-desc">Unofficially Dropped</span>
                            </label>
                        </div>
                        <div class="special-grade-item">
                            <input type="checkbox" id="grade_na" name="special_grades[NA]" class="special-grade-check">
                            <label for="grade_na">
                                <span class="special-grade-label">*</span>
                                <span class="special-grade-desc">No Attendance</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-delete" onclick="closeModal('addGradingModal')">Cancel</button>
                <button type="submit" class="btn-edit">Save Grading System</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Grading System Modal -->
<div class="modal" id="editGradingModal">
    <div class="modal-content modal-lg">
        <div class="modal-header">
            <h5 class="modal-title">Edit Grading System</h5>
            <button type="button" class="btn-close" onclick="closeModal('editGradingModal')">&times;</button>
        </div>
        <form action="" method="POST" id="editGradingForm">
            <input type="hidden" name="action" value="edit_bulk">
            <input type="hidden" name="edit_original_university_name" id="edit_original_university_name">
            <div class="modal-body">
                <div class="form-group mb-4">
                    <label class="form-label" for="edit_university_name">University Name</label>
                    <input type="text" class="form-control" id="edit_university_name" name="university_name" required
                           placeholder="Enter university name (e.g., University of the Philippines)">
                </div>

                <div class="form-group mb-4">
                    <label class="form-label" for="edit_university_code">University Code</label>
                    <input type="text" class="form-control" id="edit_university_code" name="university_code" required
                           placeholder="Enter university code (e.g., UP, DLSU, ADMU)"
                           pattern="[A-Za-z0-9]+" title="Please enter only letters and numbers">
                    <small class="form-text text-muted">Enter a unique code for the university (letters and numbers only)</small>
                </div>

                <div class="grades-container">
                    <div class="grades-header">
                        <h6>Regular Grades</h6>
                        <button type="button" class="btn-add-grade" onclick="addEditGradeRow()">
                            <i class="material-symbols-rounded">add</i>
                            Add Grade
                        </button>
                    </div>
                    <div class="grades-table">
                        <div class="grades-table-header">
                            <div class="col-grade">Grade Value</div>
                            <div class="col-desc">Description</div>
                            <div class="col-range">Percentage Range</div>
                            <div class="col-action">Action</div>
                        </div>
                        <div class="grades-rows-container" id="editGradesRows">
                            <!-- Grade rows will be populated here -->
                        </div>
                    </div>
                </div>

                <div class="special-grades-section mt-4">
                    <h6>Special Grades</h6>
                    <div class="special-grades-grid">
                        <div class="special-grade-item">
                            <input type="checkbox" id="edit_grade_drp" name="special_grades[DRP]" class="special-grade-check">
                            <label for="edit_grade_drp">
                                <span class="special-grade-label">DRP</span>
                                <span class="special-grade-desc">Dropped</span>
                            </label>
                        </div>
                        <div class="special-grade-item">
                            <input type="checkbox" id="edit_grade_od" name="special_grades[OD]" class="special-grade-check">
                            <label for="edit_grade_od">
                                <span class="special-grade-label">OD</span>
                                <span class="special-grade-desc">Officially Dropped</span>
                            </label>
                        </div>
                        <div class="special-grade-item">
                            <input type="checkbox" id="edit_grade_ud" name="special_grades[UD]" class="special-grade-check">
                            <label for="edit_grade_ud">
                                <span class="special-grade-label">UD</span>
                                <span class="special-grade-desc">Unofficially Dropped</span>
                            </label>
                        </div>
                        <div class="special-grade-item">
                            <input type="checkbox" id="edit_grade_na" name="special_grades[NA]" class="special-grade-check">
                            <label for="edit_grade_na">
                                <span class="special-grade-label">*</span>
                                <span class="special-grade-desc">No Attendance</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-delete" onclick="closeModal('editGradingModal')">Cancel</button>
                <button type="submit" class="btn-edit">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<!-- View Grading System Modal -->
<div class="modal" id="viewGradingModal">
    <div class="modal-content modal-lg">
        <div class="modal-header">
            <h5 class="modal-title">View Grading System</h5>
            <button type="button" class="btn-close" onclick="closeModal('viewGradingModal')">&times;</button>
        </div>
        <div class="modal-body">
            <div class="view-info">
                <div class="info-group">
                    <label>University Name:</label>
                    <span id="view_university_name"></span>
                </div>
                <div class="info-group">
                    <label>University Code:</label>
                    <span id="view_university_code"></span>
                </div>
            </div>
            <div class="grades-container">
                <div class="grades-header">
                    <h6>Regular Grades</h6>
                </div>
                <div class="grades-table">
                    <div class="grades-table-header">
                        <div class="col-grade">Grade Value</div>
                        <div class="col-desc">Description</div>
                        <div class="col-range">Percentage Range</div>
                    </div>
                    <div class="grades-rows-container" id="viewGradesRows">
                        <!-- Grade rows will be populated here -->
                    </div>
                </div>
            </div>
            <div class="special-grades-section">
                <h6>Special Grades</h6>
                <div class="special-grades-grid" id="viewSpecialGrades">
                    <!-- Special grades will be populated here -->
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn-edit" onclick="closeModal('viewGradingModal')">Close</button>
        </div>
    </div>
</div>

<!-- Delete Grading System Modal -->
<div class="modal" id="deleteGradingModal">
    <div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title">Delete Grading System</h5>
            <button type="button" class="btn-close" onclick="closeModal('deleteGradingModal')">&times;</button>
        </div>
        <form action="" method="POST">
            <div class="modal-body">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="university_name" id="delete_university_name">
                <p>Are you sure you want to delete this grading system?</p>
                <p><strong>University Name:</strong> <span id="delete_university_name_display"></span></p>
                <p><strong>University Code:</strong> <span id="delete_university_code_display"></span></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-edit" onclick="closeModal('deleteGradingModal')">Cancel</button>
                <button type="submit" class="btn-delete">Delete</button>
            </div>
        </form>
    </div>
</div>

<!-- Add Tech Program Modal -->
<div id="addTechProgramModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 class="modal-title">Add New Tech Program</h2>
            <button type="button" class="close" onclick="closeModal('addTechProgramModal')">&times;</button>
        </div>
        <form action="" method="POST">
            <input type="hidden" name="action" value="add_tech_program">
            <div class="modal-body">
                <div class="form-group">
                    <label for="program_name" class="form-label">Program Name</label>
                    <input type="text" id="program_name" name="program_name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="program_code" class="form-label">Program Code</label>
                    <input type="text" id="program_code" name="program_code" class="form-control" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-secondary" onclick="closeModal('addTechProgramModal')">Cancel</button>
                <button type="submit" class="btn-primary">Add Program</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Tech Program Modal -->
<div id="editTechProgramModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 class="modal-title">Edit Tech Program</h2>
            <button type="button" class="close" onclick="closeModal('editTechProgramModal')">&times;</button>
        </div>
        <form action="" method="POST">
            <input type="hidden" name="action" value="update_tech_program">
            <input type="hidden" name="id" id="edit_tech_program_id">
            <div class="modal-body">
                <div class="form-group">
                    <label for="edit_program_name" class="form-label">Program Name</label>
                    <input type="text" id="edit_program_name" name="program_name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="edit_program_code" class="form-label">Program Code</label>
                    <input type="text" id="edit_program_code" name="program_code" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">
                        <input type="checkbox" name="is_active" id="edit_is_active">
                        Active
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-secondary" onclick="closeModal('editTechProgramModal')">Cancel</button>
                <button type="submit" class="btn-primary">Update Program</button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Tech Program Modal -->
<div id="deleteTechProgramModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 class="modal-title">Confirm Deletion</h2>
            <button type="button" class="close" onclick="closeModal('deleteTechProgramModal')">&times;</button>
        </div>
        <form action="" method="POST">
            <input type="hidden" name="action" value="delete_tech_program">
            <input type="hidden" name="id" id="delete_tech_program_id">
            <div class="modal-body">
                <p>Are you sure you want to delete this tech program? This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-secondary" onclick="closeModal('deleteTechProgramModal')">Cancel</button>
                <button type="submit" class="btn-primary">Delete</button>
            </div>
        </form>
    </div>
</div> 