<?php
require_once __DIR__ . '/../inc/api.php';

authenticate();
$records = getBody();
respond(200, receiveBatch('discharge', $records));
