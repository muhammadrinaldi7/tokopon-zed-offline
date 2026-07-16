<?php
$models = [];
foreach(glob(app_path('Models/*.php')) as $file) {
    $class = 'App\\Models\\' . basename($file, '.php');
    if(class_exists($class)) {
        try {
            $model = new $class;
            $table = $model->getTable();
            $columns = \Illuminate\Support\Facades\Schema::getColumnListing($table);
            if(!empty($columns)) {
                $models[$class] = [
                    'table' => $table,
                    'columns' => $columns
                ];
            }
        } catch(\Exception $e) {
            // ignore
        }
    }
}
file_put_contents('schema_dump.json', json_encode($models));
