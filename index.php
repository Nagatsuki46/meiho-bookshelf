<?php
$dbh = new PDO(
    'pgsql:host=ec2-52-20-160-44.compute-1.amazonaws.com port=5432 dbname=ddncq809usnsnn',
    'ktygjaizcczjhp',
    'ce53f437af57ef71e88ff29de1a0512f6a3a6604198e3eabae9536bcf819818a',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);
$sth = $dbh->query(
//    'SELECT id, title, isbn, author, CASE WHEN publisher IS NOT NULL THEN \'[\' || publisher || \']\' END AS publisher,'
//    . 'publishe_date, description, \'[\' || entry_date || \']\' AS entry_date, thumbnail_url'
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
</head>

<body>
    <?php if (!$rows): ?>
    <div>アイテムが見つかりませんでした</div>
    <?php else: ?>
    <table class="Slist">
        <thead>
            <tr>
                <th scope="col">ID
                <th scope="col">Title
                <th scope="col">Description
                <th scope="col">Author<br><div class="tr_div">Publisher</div>
                <th scope="col">Publishe date<br><div class="tr_div">Entry date</div>
                <th scope="col">ISBN CD
        <tbody>
    <?php   foreach($rows as $r): ?>
        <tr>
            <td><?php echo htmlspecialchars($r['id']); ?>
            <td class="td_title"><?php echo htmlspecialchars($r['title']); ?><br><img src= <?php echo htmlspecialchars($r['thumbnail_url']); ?>>
            <td class="td_details"><?php echo htmlspecialchars($r['description']); ?>
            <td class="td_details"><?php echo htmlspecialchars($r['author']); ?><br><div class="td_div"><?php echo htmlspecialchars($r['publisher']); ?></div>
            <td class="td_details"><?php echo htmlspecialchars($r['publishe_date']); ?><br><div class="td_div"><?php echo htmlspecialchars($r['entry_date']); ?></div>
            <td class="td_details"><?php echo htmlspecialchars($r['isbn']); ?>
    <?php   endforeach; ?>
    </table>
</body>
<?php endif; ?>