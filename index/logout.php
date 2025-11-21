<?php
session_start();        // Start session
session_unset();        // Remove all session variables
session_destroy();      // Destroy the session

// Redirect back to login page
header("Location: auth.php");
exit();
?>