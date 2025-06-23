<!-- Tab 4: Grading Systems -->
<div id="tab4" class="tab-content">
    <div class="tab-note info-box">
        <strong>Note:</strong> Manage the grading systems for each university. You can add, edit, or delete grading systems here.
    </div>
    <div class="table-actions">
        <div class="search-container">
            <div class="search-box">
                <i class="material-symbols-rounded">search</i>
                <input type="text" id="search-grading" placeholder="Search grading systems..." onkeyup="searchTable(this, 'grading-table')">
            </div>
        </div>
        <a href="javascript:void(0);" onclick="openAddGradingModal()" class="add-btn">
            <span class="material-symbols-rounded">add</span>Add Grading System
        </a>
    </div>
    <table class="styled-table" id="grading-table">
        <thead>
            <tr>
                <th>University Name</th>
                <th width="15%">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $query4 = "SELECT * FROM grading_systems ORDER BY university_name ASC";
            $result4 = mysqli_query($conn, $query4);
            if (mysqli_num_rows($result4) > 0) {
                while ($row = mysqli_fetch_assoc($result4)) {
                    echo "<tr>
                        <td>{$row['university_name']}</td>
                        <td class='action-buttons'>
                            <a href='javascript:void(0);' onclick=\"viewGradingSystem('{$row['university_name']}')\" class='view-btn'>
                                <span class='material-symbols-rounded'>visibility</span>View
                            </a>
                            <a href='javascript:void(0);' onclick=\"openEditGradingModal('{$row['university_name']}')\" class='edit-btn'>
                                <span class='material-symbols-rounded'>edit</span>Edit
                            </a>
                            <a href='javascript:void(0);' onclick=\"openDeleteGradingModal('{$row['university_name']}')\" class='delete-btn'>
                                <span class='material-symbols-rounded'>delete</span>Delete
                            </a>
                        </td>
                    </tr>";
                }
            } else {
                echo "<tr><td colspan='2' class='empty-table-message'>No grading systems found. Click 'Add Grading System' to create one.</td></tr>";
            }
            ?>
        </tbody>
    </table>
</div> 