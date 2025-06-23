<!-- Tab 6: Coded Courses -->
<div id="tab6" class="tab-content">
    <div class="tab-note info-box">
        <strong>Note:</strong> Manage the list of coded courses. You can add, edit, or delete coded course records here.
    </div>
    <div class="table-actions">
        <div class="search-container">
            <div class="search-box">
                <i class="material-symbols-rounded">search</i>
                <input type="text" id="search-coded" placeholder="Search coded courses..." onkeyup="searchTable(this, 'coded-table')">
            </div>
        </div>
        <div class="filter-container">
            <select id="program-filter" onchange="filterCodedCourses()">
                <option value="">All Programs</option>
                <option value="BSIT">BSIT</option>
                <option value="BSCS">BSCS</option>
            </select>
        </div>
        <a href="javascript:void(0);" onclick="openAddCodedCourseModal()" class="add-btn">
            <span class="material-symbols-rounded">add</span>Add Coded Course
        </a>
    </div>
    <table class="styled-table" id="coded-table">
        <thead>
            <tr>
                <th width="15%">Subject Code</th>
                <th>Subject Description</th>
                <th width="15%">Program</th>
                <th width="10%">Units</th>
                <th width="15%">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $query6 = "SELECT * FROM coded_courses ORDER BY subject_code ASC";
            $result6 = mysqli_query($conn, $query6);
            if (mysqli_num_rows($result6) > 0) {
                while ($row = mysqli_fetch_assoc($result6)) {
                    echo "<tr>
                        <td>{$row['subject_code']}</td>
                        <td>{$row['subject_description']}</td>
                        <td>{$row['program']}</td>
                        <td>{$row['units']}</td>
                        <td class='action-buttons'>
                            <a href='javascript:void(0);' onclick=\"openEditCodedCourseModal(" . json_encode($row) . ")\" class='edit-btn'>
                                <span class='material-symbols-rounded'>edit</span>Edit
                            </a>
                            <a href='javascript:void(0);' onclick=\"confirmDeleteCodedCourse({$row['course_id']})\" class='delete-btn'>
                                <span class='material-symbols-rounded'>delete</span>Delete
                            </a>
                        </td>
                    </tr>";
                }
            } else {
                echo "<tr><td colspan='5' class='empty-table-message'>No coded courses found. Click 'Add Coded Course' to create one.</td></tr>";
            }
            ?>
        </tbody>
    </table>
</div> 