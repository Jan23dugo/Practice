<!-- Add Tech Program Modal -->
<div id="addTechProgramModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">Add Tech Program</h3>
            <button type="button" class="btn-close" onclick="closeModal('addTechProgramModal')">&times;</button>
        </div>
        <div class="modal-body">
            <form id="addTechProgramForm" method="POST">
                <input type="hidden" name="action" value="add">
                <div class="form-group">
                    <label for="program_code">Program Code:</label>
                    <input type="text" class="form-control" id="program_code" name="program_code" required>
                </div>
                <div class="form-group">
                    <label for="program_name">Program Name:</label>
                    <input type="text" class="form-control" id="program_name" name="program_name" required>
                </div>
                <div class="form-group">
                    <label for="is_active">Status:</label>
                    <select class="form-control" id="is_active" name="is_active" required>
                        <option value="1">Active</option>
                        <option value="0">Inactive</option>
                    </select>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-edit" onclick="closeModal('addTechProgramModal')">Cancel</button>
                    <button type="submit" class="btn-edit">Add Program</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Tech Program Modal -->
<div id="editTechProgramModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">Edit Tech Program</h3>
            <button type="button" class="btn-close" onclick="closeModal('editTechProgramModal')">&times;</button>
        </div>
        <div class="modal-body">
            <form id="editTechProgramForm" method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" id="edit_program_id" name="program_id">
                <div class="form-group">
                    <label for="edit_program_code">Program Code:</label>
                    <input type="text" class="form-control" id="edit_program_code" name="program_code" required>
                </div>
                <div class="form-group">
                    <label for="edit_program_name">Program Name:</label>
                    <input type="text" class="form-control" id="edit_program_name" name="program_name" required>
                </div>
                <div class="form-group">
                    <label for="edit_is_active">Status:</label>
                    <select class="form-control" id="edit_is_active" name="is_active" required>
                        <option value="1">Active</option>
                        <option value="0">Inactive</option>
                    </select>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-edit" onclick="closeModal('editTechProgramModal')">Cancel</button>
                    <button type="submit" class="btn-edit">Update Program</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Tech Program Modal -->
<div id="deleteTechProgramModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">Confirm Delete</h3>
            <button type="button" class="btn-close" onclick="closeModal('deleteTechProgramModal')">&times;</button>
        </div>
        <div class="modal-body">
            <form id="deleteTechProgramForm" method="POST">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" id="delete_program_id" name="program_id">
                <p>Are you sure you want to delete this tech program?</p>
                <div class="view-info">
                    <div class="info-group">
                        <label>Program Code:</label>
                        <span id="delete_program_code_display"></span>
                    </div>
                    <div class="info-group">
                        <label>Program Name:</label>
                        <span id="delete_program_name_display"></span>
                    </div>
                </div>
                <p class="text-danger">This action cannot be undone.</p>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn-edit" onclick="closeModal('deleteTechProgramModal')">Cancel</button>
            <button type="submit" form="deleteTechProgramForm" class="btn-delete">Delete</button>
        </div>
    </div>
</div> 