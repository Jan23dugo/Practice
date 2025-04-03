<?php
/**
 * Display standardized alert messages across the application
 * 
 * @param string $type    Alert type: 'success', 'error', 'warning', 'info'
 * @param string $message The message text to display
 * @param bool   $dismissible Whether the alert can be dismissed
 * @return string HTML for the alert
 */
function displayAlert($type, $message, $dismissible = true) {
    // Define alert classes based on type
    $alertClasses = [
        'success' => 'alert-success',
        'error'   => 'alert-danger',
        'warning' => 'alert-warning',
        'info'    => 'alert-info'
    ];
    
    // Get the appropriate class
    $alertClass = isset($alertClasses[$type]) ? $alertClasses[$type] : 'alert-info';
    
    // Create dismissible button if needed
    $dismissButton = $dismissible ? 
        '<button type="button" class="close-alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>' : '';
    
    // Generate alert HTML
    $alertHTML = "
    <div class='alert {$alertClass}' role='alert'>
        {$message}
        {$dismissButton}
    </div>";
    
    return $alertHTML;
}

/**
 * Sets a flash message to be displayed on the next page load
 * 
 * @param string $type    Alert type: 'success', 'error', 'warning', 'info'
 * @param string $message The message text to display
 */
function setFlashAlert($type, $message) {
    if (!isset($_SESSION)) {
        session_start();
    }
    
    $_SESSION['flash_alert'] = [
        'type' => $type,
        'message' => $message
    ];
}

/**
 * Displays and clears any flash messages
 */
function showFlashAlerts() {
    if (!isset($_SESSION)) {
        session_start();
    }
    
    if (isset($_SESSION['flash_alert'])) {
        $alert = $_SESSION['flash_alert'];
        echo displayAlert($alert['type'], $alert['message']);
        unset($_SESSION['flash_alert']);
    }
}
?>
