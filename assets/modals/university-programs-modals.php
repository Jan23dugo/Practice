<!-- Add University Program Modal -->
<div id="formModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title" id="modalTitle">Add University Program</h3>
            <button type="button" class="btn-close" onclick="closeModal('formModal')">&times;</button>
        </div>
        <div class="modal-body">
            <form id="itemForm" method="POST">
                <div class="form-group">
                    <label for="codeInput" id="codeLabel">Program Code:</label>
                    <input type="text" class="form-control" id="codeInput" name="code" required>
                </div>
                <div class="form-group">
                    <label for="nameInput" id="nameLabel">Program Name:</label>
                    <input type="text" class="form-control" id="nameInput" name="name" required>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-edit" onclick="closeModal('formModal')">Cancel</button>
                    <button type="submit" class="btn-edit" id="submitBtn">Add</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">Confirm Delete</h3>
            <button type="button" class="btn-close" onclick="closeModal('deleteModal')">&times;</button>
        </div>
        <div class="modal-body">
            <p>Are you sure you want to delete this record? This action cannot be undone.</p>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn-edit" onclick="closeModal('deleteModal')">Cancel</button>
            <button type="button" class="btn-delete" id="confirmDeleteBtn">Delete</button>
        </div>
    </div>
</div> 