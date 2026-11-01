<?php
$host='127.0.0.1'; $port='3306'; $dbname='bella_imagen_db'; $username='root'; $password='';
try { $pdo=new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4",$username,$password,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false]); }
catch(PDOException $e){ http_response_code(500); exit('Error de conexión con MySQL. Revisa config/database.php y el servicio MySQL.'); }
