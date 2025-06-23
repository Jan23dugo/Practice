<?php
// Tech Programs Tab Content
$query5 = "SELECT * FROM tech_programs ORDER BY program_name ASC";
$result5 = mysqli_query($conn, $query5);
?>

<div class="tab-note info-box">
    <strong>Note:</strong> Manage the list of technical programs. You can add, edit, or delete tech program records here.
</div>
<div class="table-actions">
    <div class="search-container">
        <div class="search-box">
            <i class="material-symbols-rounded">search</i>
            <input type="text" id="search-tech-programs" placeholder="Search tech programs..." onkeyup="searchTable(this, 'tech-programs-table')">
        </div>
    </div>
    <a href="javascript:void(0);" onclick="openAddTechProgramModal()" class="add-btn">
        <span class="material-symbols-rounded">add</span>Add Tech Program
    </a>
</div>
<table class="styled-table" id="tech-programs-table">
    <thead>
        <tr>
            <th>Program Name</th>
            <th>Program Code</th>
            <th>Status</th>
            <th width="15%">Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php
        if (mysqli_num_rows($result5) > 0) {
            while ($row = mysqli_fetch_assoc($result5)) {
                echo "<tr>
                    <td>{$row['program_name']}</td>
                    <td>{$row['program_code']}</td>
                    <td>
                        <span class='status-badge " . ($row['is_active'] ? 'status-active' : 'status-inactive') . "'>
                            " . ($row['is_active'] ? 'Active' : 'Inactive') . "
                        </span>
                    </td>
                    <td class='action-buttons'>
                        <a href='javascript:void(0);' onclick=\"openEditTechProgram(" . htmlspecialchars(json_encode($row)) . ")\" class='edit-btn'>
                            <span class='material-symbols-rounded'>edit</span>Edit
                        </a>
                        <a href='javascript:void(0);' onclick=\"confirmDeleteTechProgram({$row['id']})\" class='delete-btn'>
                            <span class='material-symbols-rounded'>delete</span>Delete
                        </a>
                    </td>
                </tr>";
            }
        } else {
            echo "<tr><td colspan='4' class='empty-table-message'>No tech programs found. Click 'Add Tech Program' to create one.</td></tr>";
        }
        ?>
    </tbody>
</table> 