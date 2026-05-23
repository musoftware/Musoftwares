<?php
$c = file_get_contents('config/tools.php');
$c = preg_replace('/\\s*\\\'active_users\\\' => \\d+,/', '', $c);
file_put_contents('config/tools.php', $c);
echo "Removed active_users from config/tools.php\n";
