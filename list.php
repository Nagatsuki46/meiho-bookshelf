<?php
$dbh = new PDO(
    'pgsql:host=localhost port=5432 dbname=pgadmin',
    'postgres',
    'postgres',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);
$sth = $dbh->query(
    'SELECT id, name, price'
    . ' FROM item'
    . ' ORDER BY id'
);
$rows = $sth->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<title>アイテムの一覧</title>
<link rel="stylesheet" href="list.css">
<?php if (!$rows): ?>
<div>アイテムが見つかりませんでした</div>
<?php else: ?>
<table class="Slist">
    <thead>
        <tr>
            <th scope="col">ID
            <th scope="col">名称
            <th scope="col">価格
    <tbody>
<?php   foreach($rows as $r): ?>
    <tr>
        <td><?php echo htmlspecialchars($r['id']); ?>
        <td><?php echo htmlspecialchars($r['name']); ?>
        <td><?php echo htmlspecialchars(number_format($r['price'])); ?>
<?php   endforeach; ?>
</table>
<?php endif; ?>