<?php
require_once 'config.php';

// Clear session and redirect to login
session_destroy();
header('Location: login.php');
exit;
