<?php
session_start();

if (isset($_POST['set_session'])) {
    $_SESSION['last_registration'] = true;
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false]);
}
?> 