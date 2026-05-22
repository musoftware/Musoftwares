<?php
$db = new PDO('sqlite:storage/app/db/All Arab.db');
$res = $db->query('SELECT name, sql FROM sqlite_master WHERE type="table"');
print_r($res->fetchAll(PDO::FETCH_ASSOC));
