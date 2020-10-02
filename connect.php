<?php
$dbh = new PDO(
    'pgsql:host=ec2-52-20-160-44.compute-1.amazonaws.com port=5432 dbname=pgadmin',
    'ktygjaizcczjhp',
    'ce53f437af57ef71e88ff29de1a0512f6a3a6604198e3eabae9536bcf819818a',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);
?>
<!DOCUTYPE html>
<title>データベース接続テスト</title>
<p>データベースの接続に成功しました</p>