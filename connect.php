<?php
$dbh = new PDO(
    'pgsql:host=localhost port=5432 dbname=pgadmin',
    'postgres',
    'postgres',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);
?>
<!DOCUTYPE html>
<title>データベース接続テスト</title>
<p>データベースの接続に成功しました</p>