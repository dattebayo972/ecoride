<?php
require_once __DIR__ . '/../src/helpers/auth.php';
require_once __DIR__ . '/../src/helpers/functions.php';
logoutUser();
redirect(BASE_URL . '/index.php');
