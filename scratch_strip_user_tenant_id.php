<?php

$dir = new RecursiveIteratorIterator(new RecursiveDirectoryIterator("Modules/Booking/tests"));
$count = 0;
foreach ($dir as $file) {
    if ($file->isFile() && $file->getExtension() === "php") {
        $content = file_get_contents($file->getPathname());
        $original = $content;

        // Strip tenant_id from User creations (User::forceCreate, User::create, User::factory()->create)
        $content = preg_replace_callback("/User::(forceCreate|create|factory\(\)->create|factory\(\)->make)\(\[(.*?)\]\)/s", function($m) {
            $inner = $m[2];
            $inner = preg_replace("/'tenant_id'\s*=>\s*[^,\]]+,?\s*/", "", $inner);
            return "User::" . $m[1] . "([" . $inner . "])";
        }, $content);

        // Strip from User::factory(2)->create(['tenant_id' => ...])
        $content = preg_replace_callback("/User::factory\(\d+\)->create\(\[(.*?)\]\)/s", function($m) {
            $inner = $m[1];
            $inner = preg_replace("/'tenant_id'\s*=>\s*[^,\]]+,?\s*/", "", $inner);
            return "User::factory(2)->create([" . $inner . "])";
        }, $content);

        if ($content !== $original) {
            file_put_contents($file->getPathname(), $content);
            echo "Updated User tenant_id: " . $file->getPathname() . "\n";
            $count++;
        }
    }
}
echo "Total updated: $count\n";
