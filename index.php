<?php

$url = parse_url(getenv('DATABASE_URL'));
$dsn = sprintf('pgsql:host=%s;dbname=%s',$url['host'],substr($url['path'],1));
$pdo = new PDO(
        $dsn,
        $url['user'],
        $url['pass'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

/*
$pdo = new PDO(

    //ローカルDB接続用
    'pgsql:host=localhost port=5432 dbname=pgadmin',
    'postgres',
    'postgres',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION] 
);
*/

$sth = $pdo->query(
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
                <th scope="col">Title<br>
                <th scope="col">Description
                <th scope="col">Author<br><div class="tr_div">Publisher</div>
                <th scope="col">Publishe date<br><div class="tr_div">Entry date</div>
        <tbody>
    <?php   foreach($rows as $r): ?>
        <tr>
            <td><?php echo htmlspecialchars($r['id']); ?>
            <td class="td_title"><?php echo htmlspecialchars($r['title']); ?><br><img src= <?php echo htmlspecialchars($r['thumbnail_url']); ?>>
                <br><div class="td_isbn">ISBN:<?php echo htmlspecialchars($r['isbn']); ?></div>
            <td class="td_details"><?php echo htmlspecialchars($r['description']); ?>
            <td class="td_details"><?php echo htmlspecialchars($r['author']); ?><br><div class="td_div"><?php echo htmlspecialchars($r['publisher']); ?></div>
            <td class="td_details"><?php echo htmlspecialchars($r['publishe_date']); ?><br><div class="td_div"><?php echo htmlspecialchars($r['entry_date']); ?></div>
    <?php   endforeach; ?>
    </table>
</body>
<?php endif; ?>