<?php
session_start();
require __DIR__ . '/utils.php';
cors();

$_SESSION = [];
session_destroy();
json_response(['ok' => true]);

