<?php
$dbh = new PDO(
    'pgsql:host=ec2-52-20-160-44.compute-1.amazonaws.com port=5432 dbname=ddncq809usnsnn',
    'ktygjaizcczjhp',
    'ce53f437af57ef71e88ff29de1a0512f6a3a6604198e3eabae9536bcf819818a',
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
<head>
    <title>図書の一覧</title>
    <link rel="stylesheet" href="list.css">
    <link href="https://fonts.googleapis.com/css2?family=Lato:wght@400;700&family=Noto+Sans+JP:wght@400;700&display=swap" rel="stylesheet">
</head>

<body>
    <?php if (!$rows): ?>
    <div>アイテムが見つかりませんでした</div>
    <?php else: ?>
    <table class="Slist">
        <thead>
            <tr>
                <th scope="col">ID
                <th scope="col">タイトル（表紙）
                <th scope="col">説明
                <th scope="col">ISBNコード
                <th scope="col">著者
                <th scope="col">出版社
                <th scope="col">出版日
                <th scope="col">登録日
        <tbody>
    <?php   foreach($rows as $r): ?>
        <tr>
            <td><?php echo htmlspecialchars($r['id']); ?>
            <td><?php echo htmlspecialchars($r['title']); ?><br><img src= <?php echo htmlspecialchars($r['thumbnail_url']); ?>>
            <td><?php echo htmlspecialchars($r['description']); ?>
            <td><?php echo htmlspecialchars($r['isbn']); ?>
            <td><?php echo htmlspecialchars($r['author']); ?>
            <td><?php echo htmlspecialchars($r['publisher']); ?>
            <td><?php echo htmlspecialchars($r['publishe_date']); ?>
            <td><?php echo htmlspecialchars($r['entry_date']); ?>
    <?php   endforeach; ?>
    </table>
</body>
<?php endif; ?>