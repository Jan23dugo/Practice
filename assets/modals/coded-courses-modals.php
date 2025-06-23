<!-- Add Coded Course Modal -->
<div id="addCodedCourseModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">Add Coded Course</h3>
            <button type="button" class="btn-close" onclick="closeModal('addCodedCourseModal')">&times;</button>
        </div>
        <div class="modal-body">
            <form id="addCodedCourseForm" method="POST">
                <input type="hidden" name="action" value="add">
                <div class="form-group">
                    <label for="subject_code">Subject Code:</label>
                    <input type="text" class="form-control" id="subject_code" name="subject_code" required>
                </div>
                <div class="form-group">
                    <label for="description">Description:</label>
                    <input type="text" class="form-control" id="description" name="description" required>
                </div>
                <div class="form-group">
                    <label for="program">Program:</label>
                    <select class="form-control" id="program" name="program" required>
                        <option value="">Select Program</option>
                        <option value="BSIT">BSIT</option>
                        <option value="BSCS">BSCS</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="units">Units:</label>
                    <input type="number" class="form-control" id="units" name="units" min="1" max="6" required>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-edit" onclick="closeModal('addCodedCourseModal')">Cancel</button>
                    <button type="submit" class="btn-edit">Add Course</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Coded Course Modal -->
<div id="editCodedCourseModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">Edit Coded Course</h3>
            <button type="button" class="btn-close" onclick="closeModal('editCodedCourseModal')">&times;</button>
        </div>
        <div class="modal-body">
            <form id="editCodedCourseForm" method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" id="edit_course_id" name="course_id">
                <div class="form-group">
                    <label for="edit_subject_code">Subject Code:</label>
                    <input type="text" class="form-control" id="edit_subject_code" name="subject_code" required>
                </div>
                <div class="form-group">
                    <label for="edit_description">Description:</label>
                    <input type="text" class="form-control" id="edit_description" name="description" required>
                </div>
                <div class="form-group">
                    <label for="edit_program">Program:</label>
                    <select class="form-control" id="edit_program" name="program" required>
                        <option value="">Select Program</option>
                        <option value="BSIT">BSIT</option>
                        <option value="BSCS">BSCS</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="edit_units">Units:</label>
                    <input type="number" class="form-control" id="edit_units" name="units" min="1" max="6" required>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-edit" onclick="closeModal('editCodedCourseModal')">Cancel</button>
                    <button type="submit" class="btn-edit">Update Course</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Coded Course Modal -->
<div id="deleteCodedCourseModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">Confirm Delete</h3>
            <button type="button" class="btn-close" onclick="closeModal('deleteCodedCourseModal')">&times;</button>
        </div>
        <div class="modal-body">
            <form id="deleteCodedCourseForm" method="POST">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" id="delete_course_id" name="course_id">
                <p>Are you sure you want to delete this coded course?</p>
                <div class="view-info">
                    <div class="info-group">
                        <label>Subject Code:</label>
                        <span id="delete_subject_code_display"></span>
                    </div>
                    <div class="info-group">
                        <label>Description:</label>
                        <span id="delete_description_display"></span>
                    </div>
                    <div class="info-group">
                        <label>Program:</label>
                        <span id="delete_program_display"></span>
                    </div>
                    <div class="info-group">
                        <label>Units:</label>
                        <span id="delete_units_display"></span>
                    </div>
                </div>
                <p class="text-danger">This action cannot be undone.</p>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn-edit" onclick="closeModal('deleteCodedCourseModal')">Cancel</button>
            <button type="submit" form="deleteCodedCourseForm" class="btn-delete">Delete</button>
        </div>
    </div>
</div> 