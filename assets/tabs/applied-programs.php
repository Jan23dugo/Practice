<!-- Tab 3: Applied Programs -->
<div id="tab3" class="tab-content">
    <div class="tab-note info-box">
        <strong>Note:</strong> Manage the list of programs you have applied to. You can add, edit, or delete applied program records here.
    </div>
    <div class="table-actions">
        <div class="search-container">
            <div class="search-box">
                <i class="material-symbols-rounded">search</i>
                <input type="text" id="search-applied" placeholder="Search applied programs..." onkeyup="searchTable(this, 'applied-table')">
            </div>
        </div>
        <a href="javascript:void(0);" onclick="openAddAppliedProgramModal()" class="add-btn">
            <span class="material-symbols-rounded">add</span>Add Applied Program
        </a>
    </div>
    <table class="styled-table" id="applied-table">
        <thead>
            <tr>
                <th width="20%">Program Code</th>
                <th>Program Name</th>
                <th width="15%">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $query3 = "SELECT * FROM applied_programs ORDER BY program_code ASC";
            $result3 = mysqli_query($conn, $query3);
            if (mysqli_num_rows($result3) > 0) {
                while ($row = mysqli_fetch_assoc($result3)) {
                    echo "<tr>
                        <td>{$row['program_code']}</td>
                        <td>{$row['program_name']}</td>
                        <td class='action-buttons'>
                            <a href='javascript:void(0);' onclick=\"openEditModal('applied', {$row['program_id']}, '{$row['program_code']}', '{$row['program_name']}')\" class='edit-btn'>
                                <span class='material-symbols-rounded'>edit</span>Edit
                            </a>
                            <a href='javascript:void(0);' onclick=\"openDeleteModal('applied', {$row['program_id']})\" class='delete-btn'>
                                <span class='material-symbols-rounded'>delete</span>Delete
                            </a>
                        </td>
                    </tr>";
                }
            } else {
                echo "<tr><td colspan='3' class='empty-table-message'>No applied programs found. Click 'Add Applied Program' to create one.</td></tr>";
            }
            ?>
        </tbody>
    </table>
</div> 