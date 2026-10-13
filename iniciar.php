<?php
if(PHP_SAPI!=='cli') { http_response_code(403); exit('Ejecutá php iniciar.php desde una terminal.'); }
chdir(__DIR__);
if(!is_file('.env') || !is_file('vendor/autoload.php') || !is_file('database/database.sqlite')) {
    exit("Primero ejecutá: php instalar.php\n");
}
require __DIR__.'/vendor/autoload.php';
$app=require __DIR__.'/bootstrap/app.php';
echo "Control de equipos: http://127.0.0.1:8000\nDejá esta terminal abierta. Para detener, presioná Ctrl+C.\n\n";
exit($app->handleCommand(new \Symfony\Component\Console\Input\ArrayInput(['command'=>'serve','--host'=>'127.0.0.1','--port'=>'8000'])));
