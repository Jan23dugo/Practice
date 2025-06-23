<?php
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
session_start();
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: admin_login.php");
    exit();
}
include('config/config.php');

$editRow = null;
$type = isset($_GET['type']) ? filter_var($_GET['type'], FILTER_SANITIZE_STRING) : null;
$action = isset($_GET['action']) ? filter_var($_GET['action'], FILTER_SANITIZE_STRING) : null;
=======
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
// Include admin session management
require_once 'config/admin_session.php';
include('config/config.php');

// Check admin session and handle timeout
checkAdminSession();

// Handle tech program form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'add_tech_program':
            $program_name = $_POST['program_name'];
            $program_code = $_POST['program_code'];
            $stmt = $conn->prepare("INSERT INTO tech_programs (program_name, program_code, is_active) VALUES (?, ?, 1)");
            $stmt->bind_param("ss", $program_name, $program_code);
            if ($stmt->execute()) {
                $_SESSION['success_message'] = "Tech program added successfully.";
            } else {
                $_SESSION['error_message'] = "Error adding tech program: " . $conn->error;
            }
            header("Location: manage_edit_registration_form.php#tab5");
            exit();
            break;

        case 'update_tech_program':
            $id = $_POST['id'];
            $program_name = $_POST['program_name'];
            $program_code = $_POST['program_code'];
            $is_active = isset($_POST['is_active']) ? 1 : 0;
            $stmt = $conn->prepare("UPDATE tech_programs SET program_name = ?, program_code = ?, is_active = ? WHERE id = ?");
            $stmt->bind_param("ssii", $program_name, $program_code, $is_active, $id);
            if ($stmt->execute()) {
                $_SESSION['success_message'] = "Tech program updated successfully.";
            } else {
                $_SESSION['error_message'] = "Error updating tech program: " . $conn->error;
            }
            header("Location: manage_edit_registration_form.php#tab5");
            exit();
            break;

        case 'delete_tech_program':
            $id = $_POST['id'];
            $stmt = $conn->prepare("DELETE FROM tech_programs WHERE id = ?");
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                $_SESSION['success_message'] = "Tech program deleted successfully.";
            } else {
                $_SESSION['error_message'] = "Error deleting tech program: " . $conn->error;
            }
            header("Location: manage_edit_registration_form.php#tab5");
            exit();
            break;

        case 'add_coded_course':
            $subject_code = $_POST['subject_code'];
            $subject_description = $_POST['subject_description'];
            $program = $_POST['program'];
            $units = $_POST['units'];
            
            $stmt = $conn->prepare("INSERT INTO coded_courses (subject_code, subject_description, program, units) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("sssd", $subject_code, $subject_description, $program, $units);
            
            if ($stmt->execute()) {
                $_SESSION['success_message'] = "Coded course added successfully.";
            } else {
                $_SESSION['error_message'] = "Error adding coded course: " . $conn->error;
            }
            header("Location: manage_edit_registration_form.php#tab6");
            exit();
            break;
            
        case 'edit_coded_course':
            $course_id = $_POST['course_id'];
            $subject_code = $_POST['subject_code'];
            $subject_description = $_POST['subject_description'];
            $program = $_POST['program'];
            $units = $_POST['units'];
            
            $stmt = $conn->prepare("UPDATE coded_courses SET subject_code = ?, subject_description = ?, program = ?, units = ? WHERE course_id = ?");
            $stmt->bind_param("sssdi", $subject_code, $subject_description, $program, $units, $course_id);
            
            if ($stmt->execute()) {
                $_SESSION['success_message'] = "Coded course updated successfully.";
            } else {
                $_SESSION['error_message'] = "Error updating coded course: " . $conn->error;
            }
            header("Location: manage_edit_registration_form.php#tab6");
            exit();
            break;
            
        case 'delete_coded_course':
            $course_id = $_POST['course_id'];
            
            $stmt = $conn->prepare("DELETE FROM coded_courses WHERE course_id = ?");
            $stmt->bind_param("i", $course_id);
            
            if ($stmt->execute()) {
                $_SESSION['success_message'] = "Coded course deleted successfully.";
            } else {
                $_SESSION['error_message'] = "Error deleting coded course: " . $conn->error;
            }
            header("Location: manage_edit_registration_form.php#tab6");
            exit();
            break;
    }
}

$editRow = null;
$type = isset($_GET['type']) ? htmlspecialchars($_GET['type'], ENT_QUOTES, 'UTF-8') : null;
$action = isset($_GET['action']) ? htmlspecialchars($_GET['action'], ENT_QUOTES, 'UTF-8') : null;
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$error_message = '';
$success_message = '';

// Handle grading system form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'add_bulk':
            $university_name = $_POST['university_name'];
            $university_code = $_POST['university_code'];
            $success = true;
            $conn->begin_transaction();

            try {
                // Add regular grades
                if (isset($_POST['grades']) && is_array($_POST['grades'])) {
                    foreach ($_POST['grades'] as $grade) {
                        if (!isset($grade['value'], $grade['description'], $grade['min'], $grade['max'])) {
                            throw new Exception("Invalid grade data provided");
                        }

                        $stmt = $conn->prepare("INSERT INTO university_grading_systems 
                            (university_name, university_code, grade_value, description, min_percentage, max_percentage, is_special_grade) 
                            VALUES (?, ?, ?, ?, ?, ?, 0)");
                        
                        if (!$stmt) {
                            throw new Exception("Prepare failed: " . $conn->error);
                        }

                        $stmt->bind_param("ssssdd", 
                            $university_name, 
                            $university_code,
                            $grade['value'], 
                            $grade['description'], 
                            $grade['min'], 
                            $grade['max']
                        );
                        
                        if (!$stmt->execute()) {
                            throw new Exception("Error adding grade: " . $stmt->error);
                        }
                    }
                }

                // Add special grades if any are selected
                if (isset($_POST['special_grades']) && is_array($_POST['special_grades'])) {
                    $specialGradesMap = [
                        'DRP' => ['value' => 'DRP', 'desc' => 'Dropped'],
                        'OD' => ['value' => 'OD', 'desc' => 'Officially Dropped'],
                        'UD' => ['value' => 'UD', 'desc' => 'Unofficially Dropped'],
                        'NA' => ['value' => '*', 'desc' => 'No Attendance']
                    ];

                    foreach ($_POST['special_grades'] as $code => $value) {
                        if (isset($specialGradesMap[$code])) {
                            $stmt = $conn->prepare("INSERT INTO university_grading_systems 
                                (university_name, university_code, grade_value, description, is_special_grade) 
                                VALUES (?, ?, ?, ?, 1)");
                            
                            if (!$stmt) {
                                throw new Exception("Prepare failed: " . $conn->error);
                            }

                            $gradeValue = $specialGradesMap[$code]['value'];
                            $description = $specialGradesMap[$code]['desc'];
                            
                            $stmt->bind_param("ssss", $university_name, $university_code, $gradeValue, $description);
                            
                            if (!$stmt->execute()) {
                                throw new Exception("Error adding special grade: " . $stmt->error);
                            }
                        }
                    }
                }

                $conn->commit();
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
                $success_message = "Grading system added successfully.";
            } catch (Exception $e) {
                $conn->rollback();
                error_log("Error in transaction: " . $e->getMessage());
                $error_message = $e->getMessage();
            }
=======
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
                $_SESSION['success_message'] = "Grading system added successfully.";
            } catch (Exception $e) {
                $conn->rollback();
                error_log("Error in transaction: " . $e->getMessage());
                $_SESSION['error_message'] = $e->getMessage();
            }
            header("Location: manage_edit_registration_form.php#tab4");
            exit();
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
            break;

        case 'edit_bulk':
            $university_name = $_POST['university_name'];
            $original_university_name = $_POST['edit_original_university_name'];
            $success = true;
            $conn->begin_transaction();

            try {
                // Delete existing grades for this university
                $stmt = $conn->prepare("DELETE FROM university_grading_systems WHERE university_name = ?");
                $stmt->bind_param("s", $original_university_name);
                
                if (!$stmt->execute()) {
                    throw new Exception("Error deleting existing grades: " . $stmt->error);
                }

                // Add regular grades
                if (isset($_POST['edit_grades']) && is_array($_POST['edit_grades'])) {
                    foreach ($_POST['edit_grades'] as $grade) {
                        if (!isset($grade['value'], $grade['description'], $grade['min'], $grade['max'])) {
                            throw new Exception("Invalid grade data provided");
                        }

                        $stmt = $conn->prepare("INSERT INTO university_grading_systems 
                            (university_name, university_code, grade_value, description, min_percentage, max_percentage, is_special_grade) 
                            VALUES (?, ?, ?, ?, ?, ?, 0)");
                        
                        if (!$stmt) {
                            throw new Exception("Prepare failed: " . $conn->error);
                        }

                        $stmt->bind_param("ssssdd", 
                            $university_name,
                            $grade['code'],
                            $grade['value'],
                            $grade['description'],
                            $grade['min'],
                            $grade['max']
                        );
                        
                        if (!$stmt->execute()) {
                            throw new Exception("Error adding grade: " . $stmt->error);
                        }
                    }
                }

                // Add special grades
                if (isset($_POST['special_grades']) && is_array($_POST['special_grades'])) {
                    $specialGradesMap = [
                        'DRP' => ['value' => 'DRP', 'desc' => 'Dropped'],
                        'OD' => ['value' => 'OD', 'desc' => 'Officially Dropped'],
                        'UD' => ['value' => 'UD', 'desc' => 'Unofficially Dropped'],
                        'NA' => ['value' => '*', 'desc' => 'No Attendance']
                    ];

                    foreach ($_POST['special_grades'] as $code => $value) {
                        if (isset($specialGradesMap[$code])) {
                            $stmt = $conn->prepare("INSERT INTO university_grading_systems 
                                (university_name, university_code, grade_value, description, is_special_grade) 
                                VALUES (?, ?, ?, ?, 1)");
                            
                            if (!$stmt) {
                                throw new Exception("Prepare failed: " . $conn->error);
                            }

                            $gradeValue = $specialGradesMap[$code]['value'];
                            $description = $specialGradesMap[$code]['desc'];
                            
                            $stmt->bind_param("ssss", $university_name, $grade['code'], $gradeValue, $description);
                            
                            if (!$stmt->execute()) {
                                throw new Exception("Error adding special grade: " . $stmt->error);
                            }
                        }
                    }
                }

                $conn->commit();
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
                $success_message = "Grading system updated successfully.";
            } catch (Exception $e) {
                $conn->rollback();
                error_log("Error in transaction: " . $e->getMessage());
                $error_message = $e->getMessage();
            }
=======
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
                $_SESSION['success_message'] = "Grading system updated successfully.";
            } catch (Exception $e) {
                $conn->rollback();
                error_log("Error in transaction: " . $e->getMessage());
                $_SESSION['error_message'] = $e->getMessage();
            }
            header("Location: manage_edit_registration_form.php#tab4");
            exit();
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
            break;

        case 'delete':
            if (!isset($_POST['university_name'])) {
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
                $error_message = "No university name provided for deletion.";
                break;
=======
                $_SESSION['error_message'] = "No university name provided for deletion.";
                header("Location: manage_edit_registration_form.php#tab4");
                exit();
>>>>>>> Stashed changes
=======
                $_SESSION['error_message'] = "No university name provided for deletion.";
                header("Location: manage_edit_registration_form.php#tab4");
                exit();
>>>>>>> Stashed changes
=======
                $_SESSION['error_message'] = "No university name provided for deletion.";
                header("Location: manage_edit_registration_form.php#tab4");
                exit();
>>>>>>> Stashed changes
=======
                $_SESSION['error_message'] = "No university name provided for deletion.";
                header("Location: manage_edit_registration_form.php#tab4");
                exit();
>>>>>>> Stashed changes
=======
                $_SESSION['error_message'] = "No university name provided for deletion.";
                header("Location: manage_edit_registration_form.php#tab4");
                exit();
>>>>>>> Stashed changes
=======
                $_SESSION['error_message'] = "No university name provided for deletion.";
                header("Location: manage_edit_registration_form.php#tab4");
                exit();
>>>>>>> Stashed changes
=======
                $_SESSION['error_message'] = "No university name provided for deletion.";
                header("Location: manage_edit_registration_form.php#tab4");
                exit();
>>>>>>> Stashed changes
=======
                $_SESSION['error_message'] = "No university name provided for deletion.";
                header("Location: manage_edit_registration_form.php#tab4");
                exit();
>>>>>>> Stashed changes
            }

            $university_name = $_POST['university_name'];
            $stmt = $conn->prepare("DELETE FROM university_grading_systems WHERE university_name = ?");
            $stmt->bind_param("s", $university_name);
            
            if ($stmt->execute()) {
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
                $success_message = "Grading system deleted successfully.";
            } else {
                $error_message = "Error deleting grading system: " . $conn->error;
            }
=======
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
                $_SESSION['success_message'] = "Grading system deleted successfully.";
            } else {
                $_SESSION['error_message'] = "Error deleting grading system: " . $conn->error;
            }
            header("Location: manage_edit_registration_form.php#tab4");
            exit();
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
            break;
    }
}

// Get all grading systems for JavaScript use
$result = $conn->query("SELECT * FROM university_grading_systems ORDER BY university_name, is_special_grade, grade_value DESC");
$grading_systems = $result->fetch_all(MYSQLI_ASSOC);

// Group grading systems by university name
$systems = [];
foreach ($grading_systems as $system) {
    $systems[$system['university_name']][] = $system;
}

$tableMap = [
    'universities' => ['table' => 'universities', 'id' => 'university_id', 'code' => 'university_code', 'name' => 'university_name'],
    'university_programs' => ['table' => 'university_programs', 'id' => 'university_program_id', 'code' => 'program_code', 'name' => 'program_name'],
    'programs' => ['table' => 'programs', 'id' => 'program_id', 'code' => 'program_code', 'name' => 'program_name']
];

if ($type && isset($tableMap[$type])) {
    $table = $tableMap[$type]['table'];
    $idKey = $tableMap[$type]['id'];
    $codeKey = $tableMap[$type]['code'];
    $nameKey = $tableMap[$type]['name'];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Sanitize inputs
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
        $code = filter_var($_POST['code'], FILTER_SANITIZE_STRING);
        $name = filter_var($_POST['name'], FILTER_SANITIZE_STRING);

        if (empty($code) || empty($name)) {
            $error_message = "Both code and name are required.";
        } else {
            try {
                if ($action === 'edit' && $id) {
                    // Use prepared statement to prevent SQL injection
                    $stmt = $conn->prepare("UPDATE `$table` SET `$codeKey`=?, `$nameKey`=? WHERE $idKey=?");
                    $stmt->bind_param("ssi", $code, $name, $id);
                    
                    if ($stmt->execute()) {
                        $success_message = "Record updated successfully!";
                        // Redirect after a delay using JavaScript
                        echo "<script>
                            setTimeout(function() {
                                window.location.href = 'manage_edit_registration_form.php';
                            }, 1500);
                        </script>";
                    } else {
                        $error_message = "Error updating record.";
                    }
                } else {
                    // Check if code already exists
=======
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
        $code = htmlspecialchars($_POST['code'], ENT_QUOTES, 'UTF-8');
        $name = htmlspecialchars($_POST['name'], ENT_QUOTES, 'UTF-8');

        if (empty($code) || empty($name)) {
            $_SESSION['error_message'] = "Both code and name are required.";
            header("Location: manage_edit_registration_form.php#tab1");
            exit();
        } else {
            try {
                if ($action === 'edit' && $id) {
                    $stmt = $conn->prepare("UPDATE `$table` SET `$codeKey`=?, `$nameKey`=? WHERE $idKey=?");
                    $stmt->bind_param("ssi", $code, $name, $id);
                    if ($stmt->execute()) {
                        $_SESSION['success_message'] = "Record updated successfully!";
                    } else {
                        $_SESSION['error_message'] = "Error updating record.";
                    }
                    header("Location: manage_edit_registration_form.php#tab1");
                    exit();
                } else {
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
                    $check_stmt = $conn->prepare("SELECT * FROM `$table` WHERE `$codeKey` = ?");
                    $check_stmt->bind_param("s", $code);
                    $check_stmt->execute();
                    $result = $check_stmt->get_result();
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
                    
                    if ($result->num_rows > 0) {
                        $error_message = "Code already exists. Please use a different code.";
                    } else {
                        $stmt = $conn->prepare("INSERT INTO `$table` (`$codeKey`, `$nameKey`) VALUES (?, ?)");
                        $stmt->bind_param("ss", $code, $name);
                        
                        if ($stmt->execute()) {
                            $success_message = "Record added successfully!";
                            // Redirect after a delay using JavaScript
                            echo "<script>
                                setTimeout(function() {
                                    window.location.href = 'manage_edit_registration_form.php';
                                }, 1500);
                            </script>";
                        } else {
                            $error_message = "Error adding record.";
                        }
                    }
                }
            } catch (Exception $e) {
                // Log the error for administrators
                error_log("Database error: " . $e->getMessage());
                $error_message = "An error occurred while processing your request. Please try again later.";
=======
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
                    if ($result->num_rows > 0) {
                        $_SESSION['error_message'] = "Code already exists. Please use a different code.";
                        header("Location: manage_edit_registration_form.php#tab1");
                        exit();
                    } else {
                        $stmt = $conn->prepare("INSERT INTO `$table` (`$codeKey`, `$nameKey`) VALUES (?, ?)");
                        $stmt->bind_param("ss", $code, $name);
                        if ($stmt->execute()) {
                            $_SESSION['success_message'] = "Record added successfully!";
                        } else {
                            $_SESSION['error_message'] = "Error adding record.";
                        }
                        header("Location: manage_edit_registration_form.php#tab1");
                        exit();
                    }
                }
            } catch (Exception $e) {
                error_log("Database error: " . $e->getMessage());
                $_SESSION['error_message'] = "An error occurred while processing your request. Please try again later.";
                header("Location: manage_edit_registration_form.php#tab1");
                exit();
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
            }
        }
    }

    if ($action === 'delete' && $id) {
        try {
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
            // Check if the record exists and can be deleted (no dependencies)
            $stmt = $conn->prepare("DELETE FROM `$table` WHERE $idKey = ?");
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                $success_message = "Record deleted successfully!";
                // Redirect after a delay using JavaScript
                echo "<script>
                    setTimeout(function() {
                        window.location.href = 'manage_edit_registration_form.php';
                    }, 1500);
                </script>";
            } else {
                $error_message = "Error deleting record. It may be referenced by other records.";
            }
        } catch (Exception $e) {
            // Log the error for administrators
            error_log("Database error: " . $e->getMessage());
            $error_message = "An error occurred while deleting the record. It might be referenced by other records.";
=======
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
            $stmt = $conn->prepare("DELETE FROM `$table` WHERE $idKey = ?");
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                $_SESSION['success_message'] = "Record deleted successfully!";
            } else {
                $_SESSION['error_message'] = "Error deleting record. It may be referenced by other records.";
            }
            header("Location: manage_edit_registration_form.php#tab1");
            exit();
        } catch (Exception $e) {
            error_log("Database error: " . $e->getMessage());
            $_SESSION['error_message'] = "An error occurred while deleting the record. It might be referenced by other records.";
            header("Location: manage_edit_registration_form.php#tab1");
            exit();
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
        }
    }

    if ($action === 'edit' && $id) {
        try {
            $stmt = $conn->prepare("SELECT * FROM `$table` WHERE $idKey = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $editRow = $result->fetch_assoc();
            
            if (!$editRow) {
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
                $error_message = "Record not found.";
            }
        } catch (Exception $e) {
            // Log the error for administrators
            error_log("Database error: " . $e->getMessage());
            $error_message = "An error occurred while retrieving the record.";
        }
    }
}
=======
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
                $_SESSION['error_message'] = "Record not found.";
            }
        } catch (Exception $e) {
            error_log("Database error: " . $e->getMessage());
            $_SESSION['error_message'] = "An error occurred while retrieving the record.";
            header("Location: manage_edit_registration_form.php#tab1");
            exit();
        }
    }
}

// Show messages from session and clear them
if (isset($_SESSION['error_message'])) {
    $error_message = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}
if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Exam Registration Form</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,0,0" />
    <style>
        .edit-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f0f0f0;
        }

        .edit-title {
            font-size: 36px;
            color: #75343A;
            font-weight: 700;
            letter-spacing: 0.5px;
            text-shadow: 0 1px 1px rgba(0,0,0,0.1);
        }

        .edit-date {
            font-size: 18px;
            color: #555;
            font-weight: 500;
        }

        /* Notification Messages */
        .notification {
            padding: 12px;
            margin-bottom: 20px;
            border-radius: 6px;
            font-weight: 500;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .notification.error {
            background-color: #ffebee;
            color: #d32f2f;
            border-left: 4px solid #d32f2f;
        }

        .notification.success {
            background-color: #e8f5e9;
            color: #2e7d32;
            border-left: 4px solid #2e7d32;
        }

        .notification-close {
            cursor: pointer;
            font-weight: bold;
            padding: 0 5px;
        }

        .tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }

        .tab-btn {
            padding: 10px 20px;
            background: #f3f3f3;
            border: none;
            cursor: pointer;
            font-weight: 600;
            border-radius: 5px;
            transition: all 0.3s ease;
        }

        .tab-btn:hover {
            background: #e0e0e0;
        }

        .tab-btn.active {
            background: #75343A;
            color: white;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        .table-actions {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            align-items: center;
            gap: 20px;
        }

        .search-container {
            flex: 1;
            max-width: 400px;
        }

        .search-box {
            position: relative;
            width: 100%;
        }

        .search-box i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #75343A;
        }

        .search-box input {
            width: 100%;
            padding: 10px 20px 10px 45px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 15px;
            transition: all 0.3s ease;
            background: white;
        }

        .search-box input:focus {
            border-color: #75343A;
            box-shadow: 0 0 0 4px rgba(117, 52, 58, 0.1);
            outline: none;
        }

        .search-box input::placeholder {
            color: #999;
        }

        .add-btn {
            padding: 8px 15px;
            background-color: #75343A;
            color: white;
            border: none;
            border-radius: 5px;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: all 0.3s ease;
        }

        .add-btn:hover {
            background-color: #622c31;
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .action-buttons {
            display: flex;
            gap: 8px;
        }

        .edit-btn,
        .delete-btn {
            color: white;
            border: none;
            padding: 6px 12px;
            cursor: pointer;
            border-radius: 4px;
            transition: all 0.3s ease;
            font-weight: 500;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 4px;
            font-size: 13px;
        }

        .edit-btn {
            background-color: #2e7d32;
        }

        .edit-btn:hover {
            background-color: #1b5e20;
        }

        .delete-btn {
            background-color: #75343A;
        }

        .delete-btn:hover {
            background-color: #622c31;
        }

        /* Table Styling */
        .styled-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            box-shadow: 0px 2px 5px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            overflow: hidden;
            margin-top: 20px;
        }

        /* Table Header */
        th {
            background: #75343A;
            color: white;
            padding: 12px 15px;
            text-align: left;
            font-weight: 500;
            font-size: 14px;
            text-transform: uppercase;
        }

        /* Table Rows */
        td {
            padding: 15px;
            border-bottom: 1px solid #eef0f3;
            color: #333;
            font-size: 14px;
            vertical-align: middle;
        }

        /* Alternate Row Color */
        tbody tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        /* Hover Effect */
        tbody tr:hover {
            background-color: #f5f5f5;
            transition: background-color 0.2s ease;
        }

        /* Empty Table Message */
        .empty-table-message {
            text-align: center;
            padding: 40px;
            color: #777;
            font-style: italic;
        }

        /* Enhanced Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .modal.show {
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 1;
        }

        .modal-content {
            display: flex;
            flex-direction: column;
            max-height: 90vh;
            background: white;
            border-radius: 20px;
            width: 90%;
            max-width: 500px;
            position: relative;
            transform: translateY(-20px);
            transition: transform 0.3s ease;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            flex: 1 1 auto;
            min-height: 0;
        }

        .modal-content.modal-lg {
            max-width: 800px !important;
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
            max-height: 90vh !important;
=======
            max-height: 100vh !important;
>>>>>>> Stashed changes
=======
            max-height: 100vh !important;
>>>>>>> Stashed changes
=======
            max-height: 100vh !important;
>>>>>>> Stashed changes
=======
            max-height: 100vh !important;
>>>>>>> Stashed changes
=======
            max-height: 100vh !important;
>>>>>>> Stashed changes
=======
            max-height: 100vh !important;
>>>>>>> Stashed changes
=======
            max-height: 100vh !important;
>>>>>>> Stashed changes
=======
            max-height: 100vh !important;
>>>>>>> Stashed changes
            margin: 20px auto;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        .modal.show .modal-content {
            transform: translateY(0);
        }

        .modal-header {
            padding: 20px 25px;
            border-bottom: 1px solid #f0f0f0;
            background: linear-gradient(135deg, #75343A 0%, #8B4448 100%);
            border-top-left-radius: 20px;
            border-top-right-radius: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-shrink: 0;
        }

        .modal-title {
            margin: 0;
            color: white;
            font-size: 20px;
            font-weight: 600;
        }

        .btn-close {
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: white;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s ease;
            font-size: 20px;
            padding: 0;
        }

        .btn-close:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: rotate(90deg);
        }

        .modal-body {
            max-height: calc(90vh - 120px);
            overflow-y: auto;
            padding: 25px;
            background: #f8f9fa;
            min-height: 0;
            flex: 1 1 auto;
        }

        .modal-footer {
            padding: 15px 25px;
            border-top: 1px solid #f0f0f0;
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            background: white;
            border-bottom-left-radius: 20px;
            border-bottom-right-radius: 20px;
            flex-shrink: 0;
        }

        .btn-edit, .btn-delete {
            padding: 8px 16px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            border: none;
        }

        .btn-edit {
            background: #75343A;
            color: white;
        }

        .btn-edit:hover {
            background: #8B4448;
            transform: translateY(-1px);
        }

        .btn-delete {
            background: #dc3545;
            color: white;
        }

        .btn-delete:hover {
            background: #c82333;
            transform: translateY(-1px);
        }

        /* Form Elements in Modal */
        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
            font-size: 14px;
        }

        .form-control {
            width: 100%;
            padding: 10px 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.2s ease;
            background: white;
        }

        .form-control:focus {
            border-color: #75343A;
            box-shadow: 0 0 0 3px rgba(117, 52, 58, 0.1);
            outline: none;
        }

        .form-text {
            font-size: 12px;
            color: #666;
            margin-top: 4px;
        }

        /* Scrollbar Styling for Modal Body */
        .modal-body::-webkit-scrollbar {
            width: 6px;
        }

        .modal-body::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 3px;
        }

        .modal-body::-webkit-scrollbar-thumb {
            background: #75343A;
            border-radius: 3px;
        }

        .modal-body::-webkit-scrollbar-thumb:hover {
            background: #8B4448;
        }

        /* Delete Modal Specific Styles */
        #deleteModal .modal-body {
            text-align: center;
            padding: 30px 25px;
        }

        #deleteModal .modal-body p {
            margin: 0;
            color: #333;
            font-size: 15px;
            line-height: 1.5;
        }

        #deleteModal .btn-primary {
            background: #dc3545;
        }

        #deleteModal .btn-primary:hover {
            background: #c82333;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .edit-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            
            .tabs {
                flex-wrap: wrap;
            }
            
            .table-actions {
                flex-direction: column;
                gap: 10px;
                align-items: stretch;
            }
            
            .search-container {
                width: 100%;
            }
            
            .add-btn {
                align-self: flex-end;
            }
        }

        /* Grading System Styles */
        .grades-container {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
        }

        .grades-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 20px;
            border-bottom: 1px solid #f0f0f0;
        }

        .grades-header h6 {
            margin: 0;
            font-size: 16px;
            color: #333;
            font-weight: 600;
        }

        .btn-add-grade {
            background: #75343A;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 14px;
            transition: all 0.2s ease;
        }

        .btn-add-grade:hover {
            background: #622c31;
            transform: translateY(-1px);
        }

        .grades-table {
            padding: 15px;
        }

        .grades-table-header {
            display: grid;
            grid-template-columns: 1fr 2fr 2fr 0.5fr;
            gap: 15px;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 6px;
            margin-bottom: 10px;
            font-weight: 500;
            color: #555;
        }

        .grade-row {
            display: grid;
            grid-template-columns: 1fr 2fr 2fr 0.5fr;
            gap: 15px;
            padding: 10px;
            border-bottom: 1px solid #f0f0f0;
            align-items: center;
        }

        .grade-row:last-child {
            border-bottom: none;
        }

        .special-grades-section {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .special-grades-section h6 {
            margin: 0 0 15px 0;
            font-size: 16px;
            color: #333;
            font-weight: 600;
        }

        .special-grades-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }

        .special-grade-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 6px;
            transition: all 0.2s ease;
        }

        .special-grade-item:hover {
            background: #f0f0f0;
        }

        .special-grade-check {
            width: 18px;
            height: 18px;
            margin: 0;
        }

        .special-grade-label {
            font-weight: 600;
            color: #75343A;
            min-width: 40px;
        }

        .special-grade-desc {
            color: #666;
            font-size: 14px;
        }

        .empty-grades-message {
            text-align: center;
            padding: 30px;
            color: #666;
            font-style: italic;
            background: #f8f9fa;
            border-radius: 6px;
        }

        .view-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }

        .info-group {
            display: flex;
            gap: 10px;
            margin-bottom: 10px;
        }

        .info-group:last-child {
            margin-bottom: 0;
        }

        .info-group label {
            font-weight: 600;
            color: #555;
            min-width: 120px;
        }

        .info-group span {
            color: #333;
        }

        /* Responsive adjustments for grading system */
        @media (max-width: 768px) {
            .grades-table-header,
            .grade-row {
                grid-template-columns: 1fr 1.5fr 1.5fr 0.5fr;
                gap: 10px;
            }

            .special-grades-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Add info box styling */
        .info-box {
            background: #f8f9fa;
            border-left: 4px solid #75343A;
            padding: 14px 18px;
            margin-bottom: 18px;
            border-radius: 6px;
            color: #444;
            font-size: 15px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
        }
    </style>
</head>
<body>
    <div class="container">
        <?php include 'sidebar.php'; ?>

        <div class="main">
            <div class="edit-header">
                <h1 class="edit-title">Manage Exam Registration Form</h1>
            </div>

            <?php if (!empty($error_message)): ?>
            <div class="notification error" id="error-notification">
                <span><?php echo $error_message; ?></span>
                <span class="notification-close" onclick="this.parentElement.style.display='none'">×</span>
            </div>
            <?php endif; ?>

            <?php if (!empty($success_message)): ?>
            <div class="notification success" id="success-notification">
                <span><?php echo $success_message; ?></span>
                <span class="notification-close" onclick="this.parentElement.style.display='none'">×</span>
            </div>
            <?php endif; ?>

            <div class="tabs-container">
                <div class="tabs">
                    <button class="tab-btn active" data-tab="tab1">Previous University Programs</button>
                    <button class="tab-btn" data-tab="tab2">Previous Universities</button>
                    <button class="tab-btn" data-tab="tab3">Applied Programs</button>
                    <button class="tab-btn" data-tab="tab4">Grading Systems</button>
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
=======
                    <button class="tab-btn" data-tab="tab5">Tech Programs</button>
                    <button class="tab-btn" data-tab="tab6">Coded Courses</button>
>>>>>>> Stashed changes
=======
                    <button class="tab-btn" data-tab="tab5">Tech Programs</button>
                    <button class="tab-btn" data-tab="tab6">Coded Courses</button>
>>>>>>> Stashed changes
=======
                    <button class="tab-btn" data-tab="tab5">Tech Programs</button>
                    <button class="tab-btn" data-tab="tab6">Coded Courses</button>
>>>>>>> Stashed changes
=======
                    <button class="tab-btn" data-tab="tab5">Tech Programs</button>
                    <button class="tab-btn" data-tab="tab6">Coded Courses</button>
>>>>>>> Stashed changes
=======
                    <button class="tab-btn" data-tab="tab5">Tech Programs</button>
                    <button class="tab-btn" data-tab="tab6">Coded Courses</button>
>>>>>>> Stashed changes
=======
                    <button class="tab-btn" data-tab="tab5">Tech Programs</button>
                    <button class="tab-btn" data-tab="tab6">Coded Courses</button>
>>>>>>> Stashed changes
=======
                    <button class="tab-btn" data-tab="tab5">Tech Programs</button>
                    <button class="tab-btn" data-tab="tab6">Coded Courses</button>
>>>>>>> Stashed changes
=======
                    <button class="tab-btn" data-tab="tab5">Tech Programs</button>
                    <button class="tab-btn" data-tab="tab6">Coded Courses</button>
>>>>>>> Stashed changes
                </div>

                <!-- Tab 1: University Programs -->
                <div id="tab1" class="tab-content active">
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
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
                            $query1 = "SELECT * FROM university_programs ORDER BY program_code ASC";
                            $result1 = mysqli_query($conn, $query1);
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
=======
                    <?php include 'tabs/university_programs.php'; ?>
>>>>>>> Stashed changes
=======
                    <?php include 'tabs/university_programs.php'; ?>
>>>>>>> Stashed changes
=======
                    <?php include 'tabs/university_programs.php'; ?>
>>>>>>> Stashed changes
=======
                    <?php include 'tabs/university_programs.php'; ?>
>>>>>>> Stashed changes
=======
                    <?php include 'tabs/university_programs.php'; ?>
>>>>>>> Stashed changes
=======
                    <?php include 'tabs/university_programs.php'; ?>
>>>>>>> Stashed changes
=======
                    <?php include 'tabs/university_programs.php'; ?>
>>>>>>> Stashed changes
=======
                    <?php include 'tabs/university_programs.php'; ?>
>>>>>>> Stashed changes
                </div>

                <!-- Tab 2: Universities -->
                <div id="tab2" class="tab-content">
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
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
=======
                    <?php include 'tabs/universities.php'; ?>
>>>>>>> Stashed changes
=======
                    <?php include 'tabs/universities.php'; ?>
>>>>>>> Stashed changes
=======
                    <?php include 'tabs/universities.php'; ?>
>>>>>>> Stashed changes
=======
                    <?php include 'tabs/universities.php'; ?>
>>>>>>> Stashed changes
=======
                    <?php include 'tabs/universities.php'; ?>
>>>>>>> Stashed changes
=======
                    <?php include 'tabs/universities.php'; ?>
>>>>>>> Stashed changes
=======
                    <?php include 'tabs/universities.php'; ?>
>>>>>>> Stashed changes
=======
                    <?php include 'tabs/universities.php'; ?>
>>>>>>> Stashed changes
                </div>

                <!-- Tab 3: Applied Programs -->
                <div id="tab3" class="tab-content">
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
                    <div class="tab-note info-box">
                        <strong>Note:</strong> Manage the programs you have applied for. You can add, edit, or delete applied program records here.
                    </div>
                    <div class="table-actions">
                        <div class="search-container">
                            <div class="search-box">
                                <i class="material-symbols-rounded">search</i>
                                <input type="text" id="search-applied" placeholder="Search applied programs..." onkeyup="searchTable(this, 'applied-table')">
                            </div>
                        </div>
                        <a href="javascript:void(0);" onclick="openAddModal('programs')" class="add-btn">
                            <span class="material-symbols-rounded">add</span>Add Program
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
                            $query3 = "SELECT * FROM programs ORDER BY program_code ASC";
                            $result3 = mysqli_query($conn, $query3);
                            if (mysqli_num_rows($result3) > 0) {
                                while ($row = mysqli_fetch_assoc($result3)) {
                                    echo "<tr>
                                        <td>{$row['program_code']}</td>
                                        <td>{$row['program_name']}</td>
                                        <td class='action-buttons'>
                                            <a href='javascript:void(0);' onclick=\"openEditModal('programs', {$row['program_id']}, '{$row['program_code']}', '{$row['program_name']}')\" class='edit-btn'>
                                                <span class='material-symbols-rounded'>edit</span>Edit
                                            </a>
                                            <a href='javascript:void(0);' onclick=\"openDeleteModal('programs', {$row['program_id']})\" class='delete-btn'>
                                                <span class='material-symbols-rounded'>delete</span>Delete
                                            </a>
                                        </td>
                                    </tr>";
                                }
                            } else {
                                echo "<tr><td colspan='3' class='empty-table-message'>No applied programs found. Click 'Add Program' to create one.</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
=======
                    <?php include 'tabs/applied_programs.php'; ?>
>>>>>>> Stashed changes
=======
                    <?php include 'tabs/applied_programs.php'; ?>
>>>>>>> Stashed changes
=======
                    <?php include 'tabs/applied_programs.php'; ?>
>>>>>>> Stashed changes
=======
                    <?php include 'tabs/applied_programs.php'; ?>
>>>>>>> Stashed changes
=======
                    <?php include 'tabs/applied_programs.php'; ?>
>>>>>>> Stashed changes
=======
                    <?php include 'tabs/applied_programs.php'; ?>
>>>>>>> Stashed changes
=======
                    <?php include 'tabs/applied_programs.php'; ?>
>>>>>>> Stashed changes
=======
                    <?php include 'tabs/applied_programs.php'; ?>
>>>>>>> Stashed changes
                </div>

                <!-- Tab 4: Grading Systems -->
                <div id="tab4" class="tab-content">
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
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
                            // Get all grading systems, grouped by university
                            $query = "SELECT university_name, university_code, MAX(updated_at) as last_modified FROM university_grading_systems GROUP BY university_name, university_code ORDER BY university_name ASC";
                            $result = mysqli_query($conn, $query);
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
                </div>
            </div>
        </div>
    </div>
                <!-- Add/Edit Modal -->
                <div id="formModal" class="modal">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2 class="modal-title" id="modalTitle">Add Item</h2>
                        <button type="button" class="close" onclick="closeModal('formModal')">&times;</button>
                    </div>
                    <div class="modal-body">
                        <form id="itemForm" method="POST" action="">
                            <div class="form-group">
                                <label for="codeInput" class="form-label" id="codeLabel">Code:</label>
                                <input type="text" id="codeInput" name="code" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label for="nameInput" class="form-label" id="nameLabel">Name:</label>
                                <input type="text" id="nameInput" name="name" class="form-control" required>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn-secondary" onclick="closeModal('formModal')">Cancel</button>
                        <button type="submit" form="itemForm" class="btn-primary" id="submitBtn">Save</button>
                    </div>
                </div>
            </div>

            <!-- Delete Confirmation Modal -->
            <div id="deleteModal" class="modal">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2 class="modal-title">Confirm Deletion</h2>
                        <button type="button" class="close" onclick="closeModal('deleteModal')">&times;</button>
                    </div>
                    <div class="modal-body">
                        <p>Are you sure you want to delete this item? This action cannot be undone.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn-secondary" onclick="closeModal('deleteModal')">Cancel</button>
                        <button type="button" class="btn-primary" id="confirmDeleteBtn">Delete</button>
                    </div>
                </div>
            </div>

            <!-- Add Grading System Modal -->
            <div class="modal" id="addGradingModal">
                <div class="modal-content modal-lg">
                    <div class="modal-header">
                        <h5 class="modal-title">Add New Grading System</h5>
                        <button type="button" class="btn-close" onclick="closeModal('addGradingModal')">&times;</button>
                    </div>
                    <form action="" method="POST" id="addGradingForm">
                        <input type="hidden" name="action" value="add_bulk">
                        <div class="modal-body">
                            <div class="form-group mb-4">
                                <label class="form-label" for="university_name">University Name</label>
                                <input type="text" class="form-control" id="university_name" name="university_name" required
                                       placeholder="Enter university name (e.g., University of the Philippines)">
                            </div>

                            <div class="form-group mb-4">
                                <label class="form-label" for="university_code">University Code</label>
                                <input type="text" class="form-control" id="university_code" name="university_code" required
                                       placeholder="Enter university code (e.g., UP, DLSU, ADMU)"
                                       pattern="[A-Za-z0-9]+" title="Please enter only letters and numbers">
                                <small class="form-text text-muted">Enter a unique code for the university (letters and numbers only)</small>
                            </div>

                            <div class="grades-container">
                                <div class="grades-header">
                                    <h6>Regular Grades</h6>
                                    <button type="button" class="btn-add-grade" onclick="addGradeRow()">
                                        <i class="material-symbols-rounded">add</i>
                                        Add Grade
                                    </button>
                                </div>
                                <div class="grades-table">
                                    <div class="grades-table-header">
                                        <div class="col-grade">Grade Value</div>
                                        <div class="col-desc">Description</div>
                                        <div class="col-range">Percentage Range</div>
                                        <div class="col-action">Action</div>
                                    </div>
                                    <div class="grades-rows-container" id="gradesRows">
                                        <!-- Grade rows will be populated here -->
                                    </div>
                                </div>
                            </div>

                            <div class="special-grades-section mt-4">
                                <h6>Special Grades</h6>
                                <div class="special-grades-grid">
                                    <div class="special-grade-item">
                                        <input type="checkbox" id="grade_drp" name="special_grades[DRP]" class="special-grade-check">
                                        <label for="grade_drp">
                                            <span class="special-grade-label">DRP</span>
                                            <span class="special-grade-desc">Dropped</span>
                                        </label>
                                    </div>
                                    <div class="special-grade-item">
                                        <input type="checkbox" id="grade_od" name="special_grades[OD]" class="special-grade-check">
                                        <label for="grade_od">
                                            <span class="special-grade-label">OD</span>
                                            <span class="special-grade-desc">Officially Dropped</span>
                                        </label>
                                    </div>
                                    <div class="special-grade-item">
                                        <input type="checkbox" id="grade_ud" name="special_grades[UD]" class="special-grade-check">
                                        <label for="grade_ud">
                                            <span class="special-grade-label">UD</span>
                                            <span class="special-grade-desc">Unofficially Dropped</span>
                                        </label>
                                    </div>
                                    <div class="special-grade-item">
                                        <input type="checkbox" id="grade_na" name="special_grades[NA]" class="special-grade-check">
                                        <label for="grade_na">
                                            <span class="special-grade-label">*</span>
                                            <span class="special-grade-desc">No Attendance</span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn-delete" onclick="closeModal('addGradingModal')">Cancel</button>
                            <button type="submit" class="btn-edit">Save Grading System</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Edit Grading System Modal -->
            <div class="modal" id="editGradingModal">
                <div class="modal-content modal-lg">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Grading System</h5>
                        <button type="button" class="btn-close" onclick="closeModal('editGradingModal')">&times;</button>
                    </div>
                    <form action="" method="POST" id="editGradingForm">
                        <input type="hidden" name="action" value="edit_bulk">
                        <input type="hidden" name="edit_original_university_name" id="edit_original_university_name">
                        <div class="modal-body">
                            <div class="form-group mb-4">
                                <label class="form-label" for="edit_university_name">University Name</label>
                                <input type="text" class="form-control" id="edit_university_name" name="university_name" required
                                       placeholder="Enter university name (e.g., University of the Philippines)">
                            </div>

                            <div class="form-group mb-4">
                                <label class="form-label" for="edit_university_code">University Code</label>
                                <input type="text" class="form-control" id="edit_university_code" name="university_code" required
                                       placeholder="Enter university code (e.g., UP, DLSU, ADMU)"
                                       pattern="[A-Za-z0-9]+" title="Please enter only letters and numbers">
                                <small class="form-text text-muted">Enter a unique code for the university (letters and numbers only)</small>
                            </div>

                            <div class="grades-container">
                                <div class="grades-header">
                                    <h6>Regular Grades</h6>
                                    <button type="button" class="btn-add-grade" onclick="addEditGradeRow()">
                                        <i class="material-symbols-rounded">add</i>
                                        Add Grade
                                    </button>
                                </div>
                                <div class="grades-table">
                                    <div class="grades-table-header">
                                        <div class="col-grade">Grade Value</div>
                                        <div class="col-desc">Description</div>
                                        <div class="col-range">Percentage Range</div>
                                        <div class="col-action">Action</div>
                                    </div>
                                    <div class="grades-rows-container" id="editGradesRows">
                                        <!-- Grade rows will be populated here -->
                                    </div>
                                </div>
                            </div>

                            <div class="special-grades-section mt-4">
                                <h6>Special Grades</h6>
                                <div class="special-grades-grid">
                                    <div class="special-grade-item">
                                        <input type="checkbox" id="edit_grade_drp" name="special_grades[DRP]" class="special-grade-check">
                                        <label for="edit_grade_drp">
                                            <span class="special-grade-label">DRP</span>
                                            <span class="special-grade-desc">Dropped</span>
                                        </label>
                                    </div>
                                    <div class="special-grade-item">
                                        <input type="checkbox" id="edit_grade_od" name="special_grades[OD]" class="special-grade-check">
                                        <label for="edit_grade_od">
                                            <span class="special-grade-label">OD</span>
                                            <span class="special-grade-desc">Officially Dropped</span>
                                        </label>
                                    </div>
                                    <div class="special-grade-item">
                                        <input type="checkbox" id="edit_grade_ud" name="special_grades[UD]" class="special-grade-check">
                                        <label for="edit_grade_ud">
                                            <span class="special-grade-label">UD</span>
                                            <span class="special-grade-desc">Unofficially Dropped</span>
                                        </label>
                                    </div>
                                    <div class="special-grade-item">
                                        <input type="checkbox" id="edit_grade_na" name="special_grades[NA]" class="special-grade-check">
                                        <label for="edit_grade_na">
                                            <span class="special-grade-label">*</span>
                                            <span class="special-grade-desc">No Attendance</span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn-delete" onclick="closeModal('editGradingModal')">Cancel</button>
                            <button type="submit" class="btn-edit">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- View Grading System Modal -->
            <div class="modal" id="viewGradingModal">
                <div class="modal-content modal-lg">
                    <div class="modal-header">
                        <h5 class="modal-title">View Grading System</h5>
                        <button type="button" class="btn-close" onclick="closeModal('viewGradingModal')">&times;</button>
                    </div>
                    <div class="modal-body">
                        <div class="view-info">
                            <div class="info-group">
                                <label>University Name:</label>
                                <span id="view_university_name"></span>
                            </div>
                            <div class="info-group">
                                <label>University Code:</label>
                                <span id="view_university_code"></span>
                            </div>
                        </div>
                        <div class="grades-container">
                            <div class="grades-header">
                                <h6>Regular Grades</h6>
                            </div>
                            <div class="grades-table">
                                <div class="grades-table-header">
                                    <div class="col-grade">Grade Value</div>
                                    <div class="col-desc">Description</div>
                                    <div class="col-range">Percentage Range</div>
                                </div>
                                <div class="grades-rows-container" id="viewGradesRows">
                                    <!-- Grade rows will be populated here -->
                                </div>
                            </div>
                        </div>
                        <div class="special-grades-section">
                            <h6>Special Grades</h6>
                            <div class="special-grades-grid" id="viewSpecialGrades">
                                <!-- Special grades will be populated here -->
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn-edit" onclick="closeModal('viewGradingModal')">Close</button>
                    </div>
                </div>
            </div>

            <!-- Delete Grading System Modal -->
            <div class="modal" id="deleteGradingModal">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Delete Grading System</h5>
                        <button type="button" class="btn-close" onclick="closeModal('deleteGradingModal')">&times;</button>
                    </div>
                    <form action="" method="POST">
                        <div class="modal-body">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="university_name" id="delete_university_name">
                            <p>Are you sure you want to delete this grading system?</p>
                            <p><strong>University Name:</strong> <span id="delete_university_name_display"></span></p>
                            <p><strong>University Code:</strong> <span id="delete_university_code_display"></span></p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn-edit" onclick="closeModal('deleteGradingModal')">Cancel</button>
                            <button type="submit" class="btn-delete">Delete</button>
                        </div>
                    </form>
                </div>
            </div>
=======
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
                    <?php include 'tabs/grading_systems.php'; ?>
                </div>

                <!-- Tab 5: Tech Programs -->
                <div id="tab5" class="tab-content">
                    <?php include 'tabs/tech_programs.php'; ?>
                </div>

                <!-- Tab 6: Coded Courses -->
                <div id="tab6" class="tab-content">
                    <?php include 'tabs/coded_courses.php'; ?>
                </div>
            </div>

            <!-- Include all modals -->
            <?php include 'tabs/modals.php'; ?>

            <!-- Include JavaScript -->
            <script src="js/tabs.js"></script>
        </div>
    </div>
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
    <script src="assets/js/side.js"></script>
    <script>
        // Initialize grading systems data
        const gradingSystemsData = <?php 
            $result = $conn->query("SELECT * FROM university_grading_systems ORDER BY university_name, is_special_grade, grade_value DESC");
            $grading_systems = $result->fetch_all(MYSQLI_ASSOC);
            
            // Group grading systems by university name
            $systems = [];
            foreach ($grading_systems as $system) {
                $systems[$system['university_name']][] = $system;
            }
            echo json_encode($systems);
        ?>;

        // Tab handling
        const tabButtons = document.querySelectorAll('.tab-btn');
        const tabContents = document.querySelectorAll('.tab-content');

        tabButtons.forEach(btn => {
            btn.addEventListener('click', () => {
                tabButtons.forEach(b => b.classList.remove('active'));
                tabContents.forEach(tc => tc.classList.remove('active'));

                btn.classList.add('active');
                document.getElementById(btn.dataset.tab).classList.add('active');
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
            });
        });

=======
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
                // Save active tab to localStorage
                localStorage.setItem('activeTab', btn.dataset.tab);
            });
        });

        // Restore last active tab on page load
        document.addEventListener('DOMContentLoaded', function() {
            const lastTab = localStorage.getItem('activeTab');
            if (lastTab) {
                tabButtons.forEach(b => b.classList.remove('active'));
                tabContents.forEach(tc => tc.classList.remove('active'));
                const btn = document.querySelector(`.tab-btn[data-tab='${lastTab}']`);
                const content = document.getElementById(lastTab);
                if (btn && content) {
                    btn.classList.add('active');
                    content.classList.add('active');
                }
            }
        });

<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
        // Search functionality
        function searchTable(input, tableId) {
            const filter = input.value.toLowerCase();
            const table = document.getElementById(tableId);
            const rows = table.querySelectorAll("tbody tr");

            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(filter) ? "" : "none";
            });

            // Check if there are any visible rows after filtering
            const visibleRows = Array.from(rows).filter(row => row.style.display !== "none");
            const emptyMessage = table.querySelector(".empty-search-message");
            
            if (visibleRows.length === 0 && !emptyMessage) {
                const tbody = table.querySelector("tbody");
                const tr = document.createElement("tr");
                tr.className = "empty-search-message";
                tr.innerHTML = `<td colspan="3" class="empty-table-message">No matching records found.</td>`;
                tbody.appendChild(tr);
            } else if (visibleRows.length > 0 && emptyMessage) {
                emptyMessage.remove();
            }
        }

        // Enhanced Modal functions
        function openModal(modalId) {
            const modal = document.getElementById(modalId);
            modal.classList.add('show');
            document.body.style.overflow = 'hidden'; // Prevent background scrolling
            
            // Add keyboard event listener for Escape key
            document.addEventListener('keydown', handleEscapeKey);
            
            // Add click outside listener
            modal.addEventListener('click', handleOutsideClick);
        }

        function closeModal(modalId) {
            const modal = document.getElementById(modalId);
            modal.classList.remove('show');
            document.body.style.overflow = ''; // Restore scrolling
            
            // Remove event listeners
            document.removeEventListener('keydown', handleEscapeKey);
            modal.removeEventListener('click', handleOutsideClick);
            
            // Reset form if exists
            const form = modal.querySelector('form');
            if (form) {
                form.reset();
                clearFormErrors(form);
            }
        }

        function handleEscapeKey(e) {
            if (e.key === 'Escape') {
                const openModal = document.querySelector('.modal.show');
                if (openModal) {
                    closeModal(openModal.id);
                }
            }
        }

        function handleOutsideClick(e) {
            if (e.target.classList.contains('modal')) {
                closeModal(e.target.id);
            }
        }

        function clearFormErrors(form) {
            const inputs = form.querySelectorAll('.form-control');
            inputs.forEach(input => {
                input.classList.remove('error');
            });
            const errorMessages = form.querySelectorAll('.error-message');
            errorMessages.forEach(message => {
                message.style.display = 'none';
            });
        }

        function showFormError(input, message) {
            input.classList.add('error');
            const errorDiv = input.nextElementSibling;
            if (errorDiv && errorDiv.classList.contains('error-message')) {
                errorDiv.textContent = message;
                errorDiv.style.display = 'block';
            }
        }

        function setLoading(button, isLoading) {
            if (isLoading) {
                button.classList.add('loading');
                button.disabled = true;
            } else {
                button.classList.remove('loading');
                button.disabled = false;
            }
        }

        function openAddModal(type) {
            const modal = document.getElementById('formModal');
            const form = document.getElementById('itemForm');
            const title = document.getElementById('modalTitle');
            const codeLabel = document.getElementById('codeLabel');
            const nameLabel = document.getElementById('nameLabel');
            const submitBtn = document.getElementById('submitBtn');

            // Set up modal based on type
            if (type === 'universities') {
                title.textContent = 'Add University';
                codeLabel.textContent = 'University Code:';
                nameLabel.textContent = 'University Name:';
            } else if (type === 'university_programs') {
                title.textContent = 'Add University Program';
                codeLabel.textContent = 'Program Code:';
                nameLabel.textContent = 'Program Name:';
            } else if (type === 'programs') {
                title.textContent = 'Add Applied Program';
                codeLabel.textContent = 'Program Code:';
                nameLabel.textContent = 'Program Name:';
            }
            
            // Reset form fields
            form.reset();
            
            // Set form action for adding
            form.action = `?action=add&type=${type}`;
            
            // Set button text
            submitBtn.textContent = 'Add';
            
            // Show modal
            openModal('formModal');
        }
        
        function openEditModal(type, id, code, name) {
            const modal = document.getElementById('formModal');
            const form = document.getElementById('itemForm');
            const title = document.getElementById('modalTitle');
            const codeInput = document.getElementById('codeInput');
            const nameInput = document.getElementById('nameInput');
            const codeLabel = document.getElementById('codeLabel');
            const nameLabel = document.getElementById('nameLabel');
            const submitBtn = document.getElementById('submitBtn');
            
            // Set up modal based on type
            if (type === 'universities') {
                title.textContent = 'Edit University';
                codeLabel.textContent = 'University Code:';
                nameLabel.textContent = 'University Name:';
            } else if (type === 'university_programs') {
                title.textContent = 'Edit University Program';
                codeLabel.textContent = 'Program Code:';
                nameLabel.textContent = 'Program Name:';
            } else if (type === 'programs') {
                title.textContent = 'Edit Applied Program';
                codeLabel.textContent = 'Program Code:';
                nameLabel.textContent = 'Program Name:';
            }
            
            // Set form values
            codeInput.value = code;
            nameInput.value = name;
            
            // Set form action for editing
            form.action = `?action=edit&type=${type}&id=${id}`;
            
            // Set button text
            submitBtn.textContent = 'Save Changes';
            
            // Show modal
            openModal('formModal');
        }
        
        function openDeleteModal(type, id) {
            const confirmBtn = document.getElementById('confirmDeleteBtn');
            
            // Set up delete button action
            confirmBtn.onclick = function() {
                window.location.href = `?action=delete&type=${type}&id=${id}`;
            };
            
            // Show modal
            openModal('deleteModal');
        }

        // Form validation
        document.getElementById('itemForm').addEventListener('submit', function(e) {
            const codeInput = document.getElementById('codeInput');
            const nameInput = document.getElementById('nameInput');
            const submitBtn = document.getElementById('submitBtn');
            let isValid = true;

            // Clear previous errors
            clearFormErrors(this);

            // Validate code
            if (!codeInput.value.trim()) {
                showFormError(codeInput, 'This field is required');
                isValid = false;
            }

            // Validate name
            if (!nameInput.value.trim()) {
                showFormError(nameInput, 'This field is required');
                isValid = false;
            }

            if (isValid) {
                setLoading(submitBtn, true);
            } else {
                e.preventDefault();
            }
        });

        // Grading System Functions
        let gradeRowCount = 0;
        let editGradeRowCount = 0;

        function openAddGradingModal() {
            document.getElementById('addGradingForm').reset();
            document.getElementById('gradesRows').innerHTML = '';
            gradeRowCount = 0;
            openModal('addGradingModal');
        }

        function openEditGradingModal(university_name) {
            // Get all grades for this system
            const grades = gradingSystemsData[university_name] || [];
            // Separate regular and special grades
            const regularGrades = grades.filter(g => g.is_special_grade == 0);
            const specialGrades = grades.filter(g => g.is_special_grade == 1);

            // Set grading system name and code
            document.getElementById('edit_university_name').value = university_name;
            document.getElementById('edit_university_code').value = grades[0]?.university_code || '';
            document.getElementById('edit_original_university_name').value = university_name;

            // Clear and repopulate grade rows
            const editGradesRows = document.getElementById('editGradesRows');
            editGradesRows.innerHTML = '';
            let editGradeRowCount = 0;
            regularGrades.forEach(grade => {
                const template = `
                    <div class="grade-row" data-index="${editGradeRowCount}">
                        <div class="col-grade">
                            <input type="text" class="form-control" 
                                name="edit_grades[${editGradeRowCount}][value]" value="${grade.grade_value}" required
                                pattern="^([1-5](\\.00|\\.25|\\.50|\\.75)?|[A-Ea-e])$"
                                oninput="validateGradeValue(this)">
                        </div>
                        <div class="col-desc">
                            <input type="text" class="form-control" 
                                name="edit_grades[${editGradeRowCount}][description]" value="${grade.description}" required>
                        </div>
                        <div class="col-range">
                            <div class="range-inputs" style="display: flex; gap: 5px; align-items: center;">
                                <input type="number" step="0.01" class="form-control" 
                                    name="edit_grades[${editGradeRowCount}][min]" value="${grade.min_percentage}" required
                                    min="0" max="100" style="width: 45%">
                                <span>-</span>
                                <input type="number" step="0.01" class="form-control" 
                                    name="edit_grades[${editGradeRowCount}][max]" value="${grade.max_percentage}" required
                                    min="0" max="100" style="width: 45%">
                            </div>
                        </div>
                        <div class="col-action">
                            <button type="button" class="btn-delete" onclick="removeEditGradeRow(this)">
                                <i class="material-symbols-rounded">delete</i>
                            </button>
                        </div>
                    </div>
                `;
                editGradesRows.insertAdjacentHTML('beforeend', template);
                editGradeRowCount++;
            });
            window.editGradeRowCount = editGradeRowCount;

            // Set special grades checkboxes
            document.getElementById('edit_grade_drp').checked = specialGrades.some(g => g.grade_value === 'DRP');
            document.getElementById('edit_grade_od').checked = specialGrades.some(g => g.grade_value === 'OD');
            document.getElementById('edit_grade_ud').checked = specialGrades.some(g => g.grade_value === 'UD');
            document.getElementById('edit_grade_na').checked = specialGrades.some(g => g.grade_value === '*');

            openModal('editGradingModal');
        }

        function openDeleteGradingModal(university_name) {
            document.getElementById('delete_university_name').value = university_name;
            document.getElementById('delete_university_name_display').textContent = university_name;
            
            // Get university code from the data
            const grades = gradingSystemsData[university_name] || [];
            const university_code = grades[0]?.university_code || '-';
            document.getElementById('delete_university_code_display').textContent = university_code;
            
            openModal('deleteGradingModal');
        }

        function viewGradingSystem(university_name) {
            const grades = gradingSystemsData[university_name] || [];
            const regularGrades = grades.filter(g => g.is_special_grade == 0);
            const specialGrades = grades.filter(g => g.is_special_grade == 1);

            // Set main info
            document.getElementById('view_university_name').textContent = university_name;
            document.getElementById('view_university_code').textContent = grades[0]?.university_code || '-';

            // Populate regular grades
            const viewGradesRows = document.getElementById('viewGradesRows');
            viewGradesRows.innerHTML = '';
            regularGrades.forEach(grade => {
                const template = `
                    <div class="grade-row">
                        <div class="col-grade">${grade.grade_value}</div>
                        <div class="col-desc">${grade.description}</div>
                        <div class="col-range">${grade.min_percentage} - ${grade.max_percentage}%</div>
                    </div>
                `;
                viewGradesRows.insertAdjacentHTML('beforeend', template);
            });

            // Populate special grades
            const viewSpecialGrades = document.getElementById('viewSpecialGrades');
            viewSpecialGrades.innerHTML = '';
            specialGrades.forEach(grade => {
                const template = `
                    <div class="special-grade-item">
                        <span class="special-grade-label">${grade.grade_value}</span>
                        <span class="special-grade-desc">${grade.description}</span>
                    </div>
                `;
                viewSpecialGrades.insertAdjacentHTML('beforeend', template);
            });

            openModal('viewGradingModal');
        }

        function addGradeRow() {
            const template = `
                <div class="grade-row" data-index="${gradeRowCount}">
                    <div class="col-grade">
                        <input type="text" class="form-control" 
                            name="grades[${gradeRowCount}][value]" placeholder="e.g., 1.00 or A" required
                            pattern="^([1-5](\\.00|\\.25|\\.50|\\.75)?|[A-Ea-e])$"
                            oninput="validateGradeValue(this)">
                    </div>
                    <div class="col-desc">
                        <input type="text" class="form-control" 
                            name="grades[${gradeRowCount}][description]" 
                            placeholder="e.g., Excellent, Satisfactory, etc." required>
                    </div>
                    <div class="col-range">
                        <div class="range-inputs" style="display: flex; gap: 5px; align-items: center;">
                            <input type="number" step="0.01" class="form-control" 
                                name="grades[${gradeRowCount}][min]" placeholder="e.g., 75" required
                                min="0" max="100" style="width: 45%">
                            <span>-</span>
                            <input type="number" step="0.01" class="form-control" 
                                name="grades[${gradeRowCount}][max]" placeholder="e.g., 100" required
                                min="0" max="100" style="width: 45%">
                        </div>
                    </div>
                    <div class="col-action">
                        <button type="button" class="btn-delete" onclick="removeGradeRow(this)">
                            <i class="material-symbols-rounded">delete</i>
                        </button>
                    </div>
                </div>
            `;
            const gradesRows = document.getElementById('gradesRows');
            // Remove empty state if present
            const emptyMessage = gradesRows.querySelector('.empty-grades-message');
            if (emptyMessage) emptyMessage.remove();
            gradesRows.insertAdjacentHTML('beforeend', template);
            gradeRowCount++;
        }

        function addEditGradeRow() {
            let editGradeRowCount = window.editGradeRowCount || 0;
            const template = `
                <div class="grade-row" data-index="${editGradeRowCount}">
                    <div class="col-grade">
                        <input type="text" class="form-control" 
                            name="edit_grades[${editGradeRowCount}][value]" placeholder="e.g., 1.00 or A" required
                            pattern="^([1-5](\\.00|\\.25|\\.50|\\.75)?|[A-Ea-e])$"
                            oninput="validateGradeValue(this)">
                    </div>
                    <div class="col-desc">
                        <input type="text" class="form-control" 
                            name="edit_grades[${editGradeRowCount}][description]" 
                            placeholder="e.g., Excellent, Satisfactory, etc." required>
                    </div>
                    <div class="col-range">
                        <div class="range-inputs" style="display: flex; gap: 5px; align-items: center;">
                            <input type="number" step="0.01" class="form-control" 
                                name="edit_grades[${editGradeRowCount}][min]" placeholder="e.g., 75" required
                                min="0" max="100" style="width: 45%">
                            <span>-</span>
                            <input type="number" step="0.01" class="form-control" 
                                name="edit_grades[${editGradeRowCount}][max]" placeholder="e.g., 100" required
                                min="0" max="100" style="width: 45%">
                        </div>
                    </div>
                    <div class="col-action">
                        <button type="button" class="btn-delete" onclick="removeEditGradeRow(this)">
                            <i class="material-symbols-rounded">delete</i>
                        </button>
                    </div>
                </div>
            `;
            const editGradesRows = document.getElementById('editGradesRows');
            // Remove empty state if present
            const emptyMessage = editGradesRows.querySelector('.empty-grades-message');
            if (emptyMessage) emptyMessage.remove();
            editGradesRows.insertAdjacentHTML('beforeend', template);
            window.editGradeRowCount = editGradeRowCount + 1;
        }

        function removeGradeRow(button) {
            const row = button.closest('.grade-row');
            row.remove();
            // Show empty state message if no rows left
            const rows = document.querySelectorAll('#gradesRows .grade-row');
            if (rows.length === 0) {
                document.getElementById('gradesRows').innerHTML = `<div class='empty-grades-message'><p>No grades added yet. Click "Add Grade" to begin.</p></div>`;
            }
        }

        function removeEditGradeRow(button) {
            const row = button.closest('.grade-row');
            row.remove();
            // Show empty state message if no rows left
            const rows = document.querySelectorAll('#editGradesRows .grade-row');
            if (rows.length === 0) {
                document.getElementById('editGradesRows').innerHTML = `<div class='empty-grades-message'><p>No grades added yet. Click "Add Grade" to begin.</p></div>`;
            }
        }

        function validateGradeValue(input) {
            const value = input.value;
            const pattern = /^([1-5](\.00|\.25|\.50|\.75)?|[A-Ea-e])$/;
            if (!pattern.test(value)) {
                input.setCustomValidity('Please enter a valid grade (e.g., 1.00, 1.25, 1.50, 1.75, 2.00, etc. or A, B, C, D, E)');
            } else {
                input.setCustomValidity('');
            }
        }

        // Initialize empty state for grade rows
        document.addEventListener('DOMContentLoaded', function() {
            const gradesRows = document.getElementById('gradesRows');
            if (gradesRows && gradesRows.children.length === 0) {
                gradesRows.innerHTML = `<div class='empty-grades-message'><p>No grades added yet. Click "Add Grade" to begin.</p></div>`;
            }
        });
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
    </script>
    </body>
    </html>
=======
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes

        // Tech Program Functions
        function openAddTechProgramModal() {
            openModal('addTechProgramModal');
        }

        function openEditTechProgram(program) {
            document.getElementById('edit_tech_program_id').value = program.id;
            document.getElementById('edit_program_name').value = program.program_name;
            document.getElementById('edit_program_code').value = program.program_code;
            document.getElementById('edit_is_active').checked = program.is_active == 1;
            openModal('editTechProgramModal');
        }

        function confirmDeleteTechProgram(id) {
            document.getElementById('delete_tech_program_id').value = id;
            openModal('deleteTechProgramModal');
        }

        // Add status badge styles
        const style = document.createElement('style');
        style.textContent = `
            .status-badge {
                padding: 5px 10px;
                border-radius: 15px;
                font-size: 12px;
                font-weight: 500;
            }
            .status-active {
                background-color: #d4edda;
                color: #155724;
            }
            .status-inactive {
                background-color: #f8d7da;
                color: #721c24;
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
</html>
>>>>>>> Stashed changes
=======
</html>
>>>>>>> Stashed changes
=======
</html>
>>>>>>> Stashed changes
=======
</html>
>>>>>>> Stashed changes
=======
</html>
>>>>>>> Stashed changes
=======
</html>
>>>>>>> Stashed changes
=======
</html>
>>>>>>> Stashed changes
=======
</html>
>>>>>>> Stashed changes
