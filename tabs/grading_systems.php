<?php
// Grading Systems Tab Content
$query = "SELECT university_name, university_code, MAX(updated_at) as last_modified FROM university_grading_systems GROUP BY university_name, university_code ORDER BY university_name ASC";
$result = mysqli_query($conn, $query);
?>

<div class="tab-note info-box">
    <strong>Note:</strong> Manage the grading systems for each university. You can add a new grading system, view details, edit existing systems, or delete them.
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
            <th>University Code</th>
            <th>Type</th>
            <th>Number of Ranges</th>
            <th>Last Modified</th>
            <th width="15%">Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php
        if (mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
                // Get type and number of ranges for this university
                $grades_query = "SELECT grade_value FROM university_grading_systems WHERE university_name = '" . mysqli_real_escape_string($conn, $row['university_name']) . "' AND is_special_grade = 0 ORDER BY grade_value ASC";
                $grades_result = mysqli_query($conn, $grades_query);
                $grades = [];
                while ($g = mysqli_fetch_assoc($grades_result)) {
                    $grades[] = $g['grade_value'];
                }
                $type = count($grades) > 0 ? $grades[0] . '–' . $grades[count($grades)-1] : '-';
                $num_ranges = count($grades);
                echo "<tr>
                    <td>{$row['university_name']}</td>
                    <td>{$row['university_code']}</td>
                    <td>{$type}</td>
                    <td>{$num_ranges}</td>
                    <td>" . ($row['last_modified'] ? date('d/m/y', strtotime($row['last_modified'])) : '-') . "</td>
                    <td class='action-buttons'>
                        <a href='javascript:void(0);' onclick=\"viewGradingSystem('{$row['university_name']}')\" class='edit-btn'>
                            <span class='material-symbols-rounded'>visibility</span>
                        </a>
                        <a href='javascript:void(0);' onclick=\"openEditGradingModal('{$row['university_name']}')\" class='edit-btn'>
                            <span class='material-symbols-rounded'>edit</span>
                        </a>
                        <a href='javascript:void(0);' onclick=\"openDeleteGradingModal('{$row['university_name']}')\" class='delete-btn'>
                            <span class='material-symbols-rounded'>delete</span>
                        </a>
                    </td>
                </tr>";
            }
        } else {
            echo "<tr><td colspan='6' class='empty-table-message'>No grading systems found. Click 'Add Grading System' to create one.</td></tr>";
        }
        ?>
    </tbody>
</table> 