<?php
// Get all coded courses
$result = $conn->query("SELECT * FROM coded_courses ORDER BY subject_code");
$coded_courses = $result->fetch_all(MYSQLI_ASSOC);
?>

<div class="table-actions">
    <div class="search-container">
        <div class="search-box">
            <i class="material-symbols-rounded">search</i>
            <input type="text" id="codedCoursesSearch" placeholder="Search coded courses..." onkeyup="searchTable(this, 'codedCoursesTable')">
        </div>
    </div>
    <button class="add-btn" onclick="openAddCodedCourseModal()">
        <i class="material-symbols-rounded">add</i>
        Add Coded Course
    </button>
</div>

<table id="codedCoursesTable" class="styled-table">
    <thead>
        <tr>
            <th>Subject Code</th>
            <th>Subject Description</th>
            <th>Program</th>
            <th>Units</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($coded_courses)): ?>
            <tr>
                <td colspan="5" class="empty-table-message">No coded courses found.</td>
            </tr>
        <?php else: ?>
            <?php foreach ($coded_courses as $course): ?>
                <tr>
                    <td><?php echo htmlspecialchars($course['subject_code']); ?></td>
                    <td><?php echo htmlspecialchars($course['subject_description']); ?></td>
                    <td><?php echo htmlspecialchars($course['program'] ?? '-'); ?></td>
                    <td><?php echo htmlspecialchars($course['units']); ?></td>
                    <td class="action-buttons">
                        <button class="edit-btn" onclick="openEditCodedCourse(<?php echo htmlspecialchars(json_encode($course)); ?>)">
                            <i class="material-symbols-rounded">edit</i>
                            Edit
                        </button>
                        <button class="delete-btn" onclick="confirmDeleteCodedCourse(<?php echo $course['course_id']; ?>)">
                            <i class="material-symbols-rounded">delete</i>
                            Delete
                        </button>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>

<!-- Add/Edit Coded Course Modal -->
<div id="codedCourseModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 class="modal-title" id="codedCourseModalTitle">Add Coded Course</h2>
            <button type="button" class="close" onclick="closeModal('codedCourseModal')">&times;</button>
        </div>
        <div class="modal-body">
            <form id="codedCourseForm" method="POST" action="">
                <input type="hidden" name="action" value="add_coded_course">
                <input type="hidden" name="course_id" id="course_id">
                
                <div class="form-group">
                    <label for="subject_code" class="form-label">Subject Code:</label>
                    <input type="text" id="subject_code" name="subject_code" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="subject_description" class="form-label">Subject Description:</label>
                    <input type="text" id="subject_description" name="subject_description" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="program" class="form-label">Program:</label>
                    <input type="text" id="program" name="program" class="form-control">
                </div>
                
                <div class="form-group">
                    <label for="units" class="form-label">Units:</label>
                    <input type="number" id="units" name="units" class="form-control" step="0.01" min="0" required>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn-secondary" onclick="closeModal('codedCourseModal')">Cancel</button>
            <button type="submit" form="codedCourseForm" class="btn-primary" id="submitCodedCourseBtn">Save</button>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteCodedCourseModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 class="modal-title">Confirm Deletion</h2>
            <button type="button" class="close" onclick="closeModal('deleteCodedCourseModal')">&times;</button>
        </div>
        <div class="modal-body">
            <p>Are you sure you want to delete this coded course? This action cannot be undone.</p>
            <form id="deleteCodedCourseForm" method="POST" action="">
                <input type="hidden" name="action" value="delete_coded_course">
                <input type="hidden" name="course_id" id="delete_course_id">
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn-secondary" onclick="closeModal('deleteCodedCourseModal')">Cancel</button>
            <button type="submit" form="deleteCodedCourseForm" class="btn-primary">Delete</button>
        </div>
    </div>
</div>

<script>
function openAddCodedCourseModal() {
    const form = document.getElementById('codedCourseForm');
    const title = document.getElementById('codedCourseModalTitle');
    const submitBtn = document.getElementById('submitCodedCourseBtn');
    
    // Reset form
    form.reset();
    form.action = '?action=add_coded_course';
    title.textContent = 'Add Coded Course';
    submitBtn.textContent = 'Add';
    
    // Clear hidden fields
    document.getElementById('course_id').value = '';
    
    openModal('codedCourseModal');
}

function openEditCodedCourse(course) {
    const form = document.getElementById('codedCourseForm');
    const title = document.getElementById('codedCourseModalTitle');
    const submitBtn = document.getElementById('submitCodedCourseBtn');
    
    // Set form values
    document.getElementById('course_id').value = course.course_id;
    document.getElementById('subject_code').value = course.subject_code;
    document.getElementById('subject_description').value = course.subject_description;
    document.getElementById('program').value = course.program || '';
    document.getElementById('units').value = course.units;
    
    // Update form action and button text
    form.action = '?action=edit_coded_course';
    title.textContent = 'Edit Coded Course';
    submitBtn.textContent = 'Save Changes';
    
    openModal('codedCourseModal');
}

function confirmDeleteCodedCourse(courseId) {
    document.getElementById('delete_course_id').value = courseId;
    openModal('deleteCodedCourseModal');
}
</script> 