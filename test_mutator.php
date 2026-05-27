<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

class DummyModel extends \Illuminate\Database\Eloquent\Model {
    protected $fillable = ['name', 'currency', 'currency_id'];
    public function setCurrencyAttribute($value) {
        $this->attributes['currency_id'] = $value;
    }
}

$m = new DummyModel(['name' => 'Test', 'currency' => 'USD']);
print_r($m->getAttributes());
