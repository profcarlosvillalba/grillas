<?php
// Ejecutar desde una terminal: php instalar.php
if (PHP_SAPI !== 'cli') { http_response_code(403); exit('Ejecutá este archivo desde una terminal.'); }
chdir(__DIR__);
function fallarInstalacion(string $mensaje): never { fwrite(STDERR,$mensaje."\n"); exit(1); }
if (version_compare(PHP_VERSION,'8.3.0','<')) { fallarInstalacion("Necesitás PHP 8.3 o superior. Tu versión: ".PHP_VERSION); }
foreach(['ctype','curl','dom','fileinfo','mbstring','openssl','pdo','tokenizer','xml','zip'] as $extension) {
    if(!extension_loaded($extension)) { fallarInstalacion("Falta la extensión PHP: $extension. Revisá el php.ini indicado por php --ini."); }
}
if(!is_file(__DIR__.'/vendor/autoload.php')) { fallarInstalacion('Falta vendor/. Ejecutá composer install y repetí php instalar.php.'); }
foreach(['bootstrap/cache','storage/app/private','storage/app/public','storage/app/exports','storage/framework/cache/data','storage/framework/sessions','storage/framework/views','storage/logs','database'] as $dir) {
    if(!is_dir($dir) && !mkdir($dir,0775,true)) { fallarInstalacion("No se pudo crear $dir. Revisá los permisos."); }
}
if(!is_file('.env') && !copy('.env.example','.env')) { fallarInstalacion('No se pudo crear .env. Revisá los permisos.'); }
$env=file_get_contents('.env');
if(preg_match('/^APP_KEY=\s*$/m',$env)) {
    $env=preg_replace('/^APP_KEY=\s*$/m','APP_KEY=base64:'.base64_encode(random_bytes(32)),$env);
    file_put_contents('.env',$env);
}
if(!is_file('database/database.sqlite')) { touch('database/database.sqlite'); }
require __DIR__.'/vendor/autoload.php';
$app=require __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
try {
    $driver=config('database.connections.'.config('database.default').'.driver');
    if(in_array($driver,['sqlite','mysql'],true) && !in_array($driver,\PDO::getAvailableDrivers(),true)) {
        throw new \RuntimeException("Falta el controlador PDO para $driver. Habilitá pdo_$driver en el php.ini indicado por php --ini.");
    }
    foreach(['config:clear'=>[],'migrate'=>['--force'=>true],'db:seed'=>['--force'=>true]] as $comando=>$argumentos) {
        $codigo=\Illuminate\Support\Facades\Artisan::call($comando,$argumentos);
        echo \Illuminate\Support\Facades\Artisan::output();
        if($codigo!==0) { throw new \RuntimeException("El comando $comando terminó con un error. Revisá el mensaje anterior."); }
    }
    echo "\nLISTO. Iniciá con: php iniciar.php\nAbrí http://127.0.0.1:8000.
En una instalación nueva: admin@grillas.test / Admin12345!
Si ya tenías usuarios, sus credenciales se conservan.
Para ver las grillas de ejemplo elegí 26/07/2025.\n";
} catch(\Throwable $e) {
    fwrite(STDERR,"No se pudo preparar el sistema: ".$e->getMessage()."\n");
    exit(1);
}
