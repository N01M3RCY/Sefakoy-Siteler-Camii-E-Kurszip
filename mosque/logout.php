<?php
require_once '../config/db.php';
session_start_safe();
session_destroy();
redirect('../index.php');
