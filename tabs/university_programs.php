<?php
// University Programs Tab Content
$query1 = "SELECT * FROM university_programs ORDER BY program_code ASC";
$result1 = mysqli_query($conn, $query1);
?>

<div class="tab-note info-box">
    <strong>Note:</strong> Manage the list of university programs you have previously attended. You can add, edit, or delete program records here.
</div>
<div class="table-actions">
    <div class="search-container">
        <div class="search-box">
            <i class="material-symbols-rounded">search</i>
            <input type="text" id="search-programs" placeholder="Search programs..." onkeyup="searchTable(this, 'programs-table')">
        </div>
    </div>
    <a href="javascript:void(0);" onclick="openAddModal('university_programs')" class="add-btn">
        <span class="material-symbols-rounded">add</span>Add Program
    </a>
</div>
<table class="styled-table" id="programs-table">
    <thead>
        <tr>
            <th width="20%">Program Code</th>
            <th>Program Name</th>
            <th width="15%">Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php
        if (mysqli_num_rows($result1) > 0) {
            while ($row = mysqli_fetch_assoc($result1)) {
                echo "<tr>
                    <td>{$row['program_code']}</td>
                    <td>{$row['program_name']}</td>
                    <td class='action-buttons'>
                        <a href='javascript:void(0);' onclick=\"openEditModal('university_programs', {$row['university_program_id']}, '{$row['program_code']}', '{$row['program_name']}')\" class='edit-btn'>
                            <span class='material-symbols-rounded'>edit</span>Edit
                        </a>
                        <a href='javascript:void(0);' onclick=\"openDeleteModal('university_programs', {$row['university_program_id']})\" class='delete-btn'>
                            <span class='material-symbols-rounded'>delete</span>Delete
                        </a>
                    </td>
                </tr>";
            }
        } else {
            echo "<tr><td colspan='3' class='empty-table-message'>No university programs found. Click 'Add Program' to create one.</td></tr>";
        }
        ?>
    </tbody>
</table> 