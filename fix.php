<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$article = App\Models\BlogArticle::where('slug', 'mnbmnm')->first();
if ($article) {
    if (!$article->published_at && $article->is_published) {
        $article->published_at = now();
        $article->save();
        echo "Fixed published_at for article 'mnbmnm'.\n";
    } else {
        echo "Article 'mnbmnm' is already fine or not published.\n";
    }
} else {
    echo "Article 'mnbmnm' NOT FOUND.\n";
}
