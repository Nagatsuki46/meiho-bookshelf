<?php
$dbh = new PDO(
    'pgsql:host=localhost port=5432 dbname=pgadmin',
    'postgres',
    'postgres',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);
$sth = $dbh->query(
    'SELECT id, title, isbn, author, publisher,'
    . 'publishe_date, description, entry_date, thumbnail_url'
    . ' FROM bookshelf'
    . ' ORDER BY id'
);
$rows = $sth->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<title>図書の一覧</title>
<link rel="stylesheet" href="list.css">
<?php if (!$rows): ?>
<div>アイテムが見つかりませんでした</div>
<?php else: ?>
<table class="Slist">
    <thead>
        <tr>
            <th scope="col">ID
            <th scope="col">タイトル
            <th scope="col">ISBNコード
            <th scope="col">著者
            <th scope="col">出版社
            <th scope="col">出版日
            <th scope="col">説明
            <th scope="col">登録日
            <th scope="col">表紙イメージ
    <tbody>
<?php   foreach($rows as $r): ?>
    <tr>
        <td><?php echo htmlspecialchars($r['id']); ?>
        <td><?php echo htmlspecialchars($r['title']); ?>
        <td><?php echo htmlspecialchars($r['isbn']); ?>
        <td><?php echo htmlspecialchars($r['author']); ?>
        <td><?php echo htmlspecialchars($r['publisher']); ?>
        <td><?php echo htmlspecialchars($r['publishe_date']); ?>
        <td><?php echo htmlspecialchars($r['description']); ?>
        <td><?php echo htmlspecialchars($r['entry_date']); ?>
        <td><img src= <?php echo htmlspecialchars($r['thumbnail_url']); ?>>
<?php   endforeach; ?>
</table>
<?php endif; ?>