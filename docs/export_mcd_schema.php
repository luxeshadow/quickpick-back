<?php
require dirname(__DIR__).'/vendor/autoload.php';
$app = require dirname(__DIR__).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$schema = Illuminate\Support\Facades\Schema::getFacadeRoot();
$result = [];
foreach ($schema->getTables() as $table) {
    $name = $table['name'];
    $result[$name] = [
        'columns' => array_map(fn($c) => array_intersect_key($c, array_flip(['name','type_name','nullable'])), $schema->getColumns($name)),
        'foreign_keys' => $schema->getForeignKeys($name),
        'indexes' => $schema->getIndexes($name),
    ];
}
file_put_contents(__DIR__.'/quickpick-schema-metadata.json', json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo count($result)." tables inspected; no user records exported.\n";
