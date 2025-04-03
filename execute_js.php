<?php
function executeJSCode($code) {
    // Return code wrapped in a script to be executed in the browser
    return ['type' => 'js', 'code' => $code];
}
?> 