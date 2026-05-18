<?php
$files = glob(__DIR__ . '/../Modules/Intelligence/Models/*.php');
foreach ($files as $f) {
    $c = file_get_contents($f);
    $c = str_replace('use HasFactory;', "use HasFactory;\n    protected \$guarded = [];", $c);
    file_put_contents($f, $c);
}
echo "Models updated\n";
