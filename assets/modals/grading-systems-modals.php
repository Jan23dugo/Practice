<!-- Add Grading System Modal -->
<div id="addGradingModal" class="modal">
    <div class="modal-content modal-lg">
        <div class="modal-header">
            <h3 class="modal-title">Add Grading System</h3>
            <button type="button" class="btn-close" onclick="closeModal('addGradingModal')">&times;</button>
        </div>
        <div class="modal-body">
            <form id="addGradingForm" method="POST">
                <input type="hidden" name="action" value="add_bulk">
                <div class="form-group">
                    <label for="university_name">University Name:</label>
                    <input type="text" class="form-control" id="university_name" name="university_name" required>
                </div>
                <div class="form-group">
                    <label for="university_code">University Code:</label>
                    <input type="text" class="form-control" id="university_code" name="university_code" required>
                </div>
                
                <div class="grades-container">
                    <div class="grades-header">
                        <h6>Regular Grades</h6>
                        <button type="button" class="btn-add-grade" onclick="addGradeRow()">
                            <i class="material-symbols-rounded">add</i> Add Grade
                        </button>
                    </div>
                    <div class="grades-table">
                        <div class="grades-table-header">
                            <div>Grade</div>
                            <div>Description</div>
                            <div>Range (%)</div>
                            <div>Action</div>
                        </div>
                        <div id="gradesRows"></div>
                    </div>
                </div>

                <div class="special-grades-section">
                    <h6>Special Grades</h6>
                    <div class="special-grades-grid">
                        <div class="special-grade-item">
                            <input type="checkbox" class="special-grade-check" id="grade_drp" name="special_grades[DRP]" value="1">
                            <span class="special-grade-label">DRP</span>
                            <span class="special-grade-desc">Dropped</span>
                        </div>
                        <div class="special-grade-item">
                            <input type="checkbox" class="special-grade-check" id="grade_od" name="special_grades[OD]" value="1">
                            <span class="special-grade-label">OD</span>
                            <span class="special-grade-desc">Officially Dropped</span>
                        </div>
                        <div class="special-grade-item">
                            <input type="checkbox" class="special-grade-check" id="grade_ud" name="special_grades[UD]" value="1">
                            <span class="special-grade-label">UD</span>
                            <span class="special-grade-desc">Unofficially Dropped</span>
                        </div>
                        <div class="special-grade-item">
                            <input type="checkbox" class="special-grade-check" id="grade_na" name="special_grades[NA]" value="1">
                            <span class="special-grade-label">*</span>
                            <span class="special-grade-desc">No Attendance</span>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn-edit" onclick="closeModal('addGradingModal')">Cancel</button>
                    <button type="submit" class="btn-edit">Add Grading System</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Grading System Modal -->
<div id="editGradingModal" class="modal">
    <div class="modal-content modal-lg">
        <div class="modal-header">
            <h3 class="modal-title">Edit Grading System</h3>
            <button type="button" class="btn-close" onclick="closeModal('editGradingModal')">&times;</button>
        </div>
        <div class="modal-body">
            <form id="editGradingForm" method="POST">
                <input type="hidden" name="action" value="edit_bulk">
                <input type="hidden" id="edit_original_university_name" name="edit_original_university_name">
                <div class="form-group">
                    <label for="edit_university_name">University Name:</label>
                    <input type="text" class="form-control" id="edit_university_name" name="university_name" required>
                </div>
                <div class="form-group">
                    <label for="edit_university_code">University Code:</label>
                    <input type="text" class="form-control" id="edit_university_code" name="university_code" required>
                </div>
                
                <div class="grades-container">
                    <div class="grades-header">
                        <h6>Regular Grades</h6>
                        <button type="button" class="btn-add-grade" onclick="addEditGradeRow()">
                            <i class="material-symbols-rounded">add</i> Add Grade
                        </button>
                    </div>
                    <div class="grades-table">
                        <div class="grades-table-header">
                            <div>Grade</div>
                            <div>Description</div>
                            <div>Range (%)</div>
                            <div>Action</div>
                        </div>
                        <div id="editGradesRows"></div>
                    </div>
                </div>

                <div class="special-grades-section">
                    <h6>Special Grades</h6>
                    <div class="special-grades-grid">
                        <div class="special-grade-item">
                            <input type="checkbox" class="special-grade-check" id="edit_grade_drp" name="special_grades[DRP]" value="1">
                            <span class="special-grade-label">DRP</span>
                            <span class="special-grade-desc">Dropped</span>
                        </div>
                        <div class="special-grade-item">
                            <input type="checkbox" class="special-grade-check" id="edit_grade_od" name="special_grades[OD]" value="1">
                            <span class="special-grade-label">OD</span>
                            <span class="special-grade-desc">Officially Dropped</span>
                        </div>
                        <div class="special-grade-item">
                            <input type="checkbox" class="special-grade-check" id="edit_grade_ud" name="special_grades[UD]" value="1">
                            <span class="special-grade-label">UD</span>
                            <span class="special-grade-desc">Unofficially Dropped</span>
                        </div>
                        <div class="special-grade-item">
                            <input type="checkbox" class="special-grade-check" id="edit_grade_na" name="special_grades[NA]" value="1">
                            <span class="special-grade-label">*</span>
                            <span class="special-grade-desc">No Attendance</span>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn-edit" onclick="closeModal('editGradingModal')">Cancel</button>
                    <button type="submit" class="btn-edit">Update Grading System</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View Grading System Modal -->
<div id="viewGradingModal" class="modal">
    <div class="modal-content modal-lg">
        <div class="modal-header">
            <h3 class="modal-title">View Grading System</h3>
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
                        <div>Grade</div>
                        <div>Description</div>
                        <div>Range (%)</div>
                    </div>
                    <div id="viewGradesRows"></div>
                </div>
            </div>

            <div class="special-grades-section">
                <h6>Special Grades</h6>
                <div class="special-grades-grid" id="viewSpecialGrades"></div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-edit" onclick="closeModal('viewGradingModal')">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Grading System Modal -->
<div id="deleteGradingModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">Confirm Delete</h3>
            <button type="button" class="btn-close" onclick="closeModal('deleteGradingModal')">&times;</button>
        </div>
        <div class="modal-body">
            <form id="deleteGradingForm" method="POST">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" id="delete_university_name" name="university_name">
                <p>Are you sure you want to delete the grading system for:</p>
                <div class="view-info">
                    <div class="info-group">
                        <label>University Name:</label>
                        <span id="delete_university_name_display"></span>
                    </div>
                    <div class="info-group">
                        <label>University Code:</label>
                        <span id="delete_university_code_display"></span>
                    </div>
                </div>
                <p class="text-danger">This action cannot be undone.</p>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn-edit" onclick="closeModal('deleteGradingModal')">Cancel</button>
            <button type="submit" form="deleteGradingForm" class="btn-delete">Delete</button>
        </div>
    </div>
</div> 