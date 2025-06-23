<!-- Tab 2: Universities -->
<div id="tab2" class="tab-content">
    <div class="tab-note info-box">
        <strong>Note:</strong> Manage the list of universities you have previously attended. You can add, edit, or delete university records here.
    </div>
    <div class="table-actions">
        <div class="search-container">
            <div class="search-box">
                <i class="material-symbols-rounded">search</i>
                <input type="text" id="search-universities" placeholder="Search universities..." onkeyup="searchTable(this, 'universities-table')">
            </div>
        </div>
        <a href="javascript:void(0);" onclick="openAddModal('universities')" class="add-btn">
            <span class="material-symbols-rounded">add</span>Add University
        </a>
    </div>
    <table class="styled-table" id="universities-table">
        <thead>
            <tr>
                <th width="20%">University Code</th>
                <th>University Name</th>
                <th width="15%">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $query2 = "SELECT * FROM universities ORDER BY university_code ASC";
            $result2 = mysqli_query($conn, $query2);
            if (mysqli_num_rows($result2) > 0) {
                while ($row = mysqli_fetch_assoc($result2)) {
                    echo "<tr>
                        <td>{$row['university_code']}</td>
                        <td>{$row['university_name']}</td>
                        <td class='action-buttons'>
                            <a href='javascript:void(0);' onclick=\"openEditModal('universities', {$row['university_id']}, '{$row['university_code']}', '{$row['university_name']}')\" class='edit-btn'>
                                <span class='material-symbols-rounded'>edit</span>Edit
                            </a>
                            <a href='javascript:void(0);' onclick=\"openDeleteModal('universities', {$row['university_id']})\" class='delete-btn'>
                                <span class='material-symbols-rounded'>delete</span>Delete
                            </a>
                        </td>
                    </tr>";
                }
            } else {
                echo "<tr><td colspan='3' class='empty-table-message'>No universities found. Click 'Add University' to create one.</td></tr>";
            }
            ?>
        </tbody>
    </table>
</div> 