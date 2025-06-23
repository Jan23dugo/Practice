<!-- Tab 5: Tech Programs -->
<div id="tab5" class="tab-content">
    <div class="tab-note info-box">
        <strong>Note:</strong> Manage the list of technical programs. You can add, edit, or delete tech program records here.
    </div>
    <div class="table-actions">
        <div class="search-container">
            <div class="search-box">
                <i class="material-symbols-rounded">search</i>
                <input type="text" id="search-tech" placeholder="Search tech programs..." onkeyup="searchTable(this, 'tech-table')">
            </div>
        </div>
        <a href="javascript:void(0);" onclick="openAddTechProgramModal()" class="add-btn">
            <span class="material-symbols-rounded">add</span>Add Tech Program
        </a>
    </div>
    <table class="styled-table" id="tech-table">
        <thead>
            <tr>
                <th width="20%">Program Code</th>
                <th>Program Name</th>
                <th width="10%">Status</th>
                <th width="15%">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $query5 = "SELECT * FROM tech_programs ORDER BY program_code ASC";
            $result5 = mysqli_query($conn, $query5);
            if (mysqli_num_rows($result5) > 0) {
                while ($row = mysqli_fetch_assoc($result5)) {
                    $status_class = $row['is_active'] ? 'status-active' : 'status-inactive';
                    $status_text = $row['is_active'] ? 'Active' : 'Inactive';
                    echo "<tr>
                        <td>{$row['program_code']}</td>
                        <td>{$row['program_name']}</td>
                        <td><span class='status-badge {$status_class}'>{$status_text}</span></td>
                        <td class='action-buttons'>
                            <a href='javascript:void(0);' onclick=\"openEditTechProgramModal(" . json_encode($row) . ")\" class='edit-btn'>
                                <span class='material-symbols-rounded'>edit</span>Edit
                            </a>
                            <a href='javascript:void(0);' onclick=\"confirmDeleteTechProgram({$row['program_id']})\" class='delete-btn'>
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
</div> 