<?php
$dbh = new PDO(
    'pgsql:host=ec2-52-20-160-44.compute-1.amazonaws.com port=5432 dbname=ddncq809usnsnn',
    'ktygjaizcczjhp',
    'ce53f437af57ef71e88ff29de1a0512f6a3a6604198e3eabae9536bcf819818a',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);
$sth = $dbh->query(
    'SELECT id, title, isbn, author, CASE WHEN publisher IS NOT NULL THEN \'[\' + publisher + \']\' END,'
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
                <th scope="col">著者（出版）
                <th scope="col">出版日（登録日）
                <th scope="col">ISBNコード
        <tbody>
    <?php   foreach($rows as $r): ?>
        <tr>
            <td><?php echo htmlspecialchars($r['id']); ?>
            <td style="font-weight:bold;color:#207275;"><?php echo htmlspecialchars($r['title']); ?><br><img src= <?php echo htmlspecialchars($r['thumbnail_url']); ?>>
            <td style="font-size:0.8em"><?php echo htmlspecialchars($r['description']); ?>
            <td style="font-size:0.8em"><?php echo htmlspecialchars($r['author']); ?><br><?php echo htmlspecialchars($r['publisher']); ?>
            <td style="font-size:0.8em"><?php echo htmlspecialchars($r['publishe_date']); ?><br><?php echo htmlspecialchars($r['entry_date']); ?>
            <td style="font-size:0.8em"><?php echo htmlspecialchars($r['isbn']); ?>
    <?php   endforeach; ?>
    </table>
</body>
<?php endif; ?>