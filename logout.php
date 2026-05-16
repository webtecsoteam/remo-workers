<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/classes/Auth.php';

Auth::logout();
header("Location: index.php");
exit;
