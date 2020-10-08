<?php

$url = parse_url(getenv('DATABASE_URL'));
$dsn = sprintf('pgsql:host=%s;dbname=%s',$url['host'],substr($url['path'],1));
$dbh = new PDO(
        $dsn,
        $url['user'],
        $url['pass'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$where = " WHERE 1=1";
if($_POST['isbn'] != ""){$where = $where . " AND isbn LIKE '" . $_POST['isbn'] ."%'";}
if($_POST['title'] != ""){$where = $where . " AND title LIKE '%" . $_POST['title'] ."%'";}
if($_POST['description'] != ""){$where = $where . " AND description LIKE '%" . $_POST['description'] ."%'";}

$sth = $dbh->query(
    'SELECT id, title, isbn, author, publisher,'
    . 'publishe_date, description, entry_date, thumbnail_url,'
    . 'checkout_flg, employee_id, exp_return_date'
    . ' FROM bookshelf'
    .  $where
    . ' ORDER BY id'
);
$rows = $sth->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<head>
    <title>図書の検索</title>
    <link rel="stylesheet" href="list.css">
</head>

<body>
    <hr class="hr01">
    <form action="index.php" method="post">
            ISBN CD: <input type="text" name="isbn" value="<?php echo $_POST['isbn']?>">
            Title: <input type="text" name="title" value="<?php echo $_POST['title']?>">
            Description: <input type="text" name="description" value="<?php echo $_POST['description']?>">
            <input type="submit" value="Search">
            <?php
            //if(!empty($_POST['isbn']) and !preg_match("/[0-9]{13}/", $_POST['isbn'])){
            //    echo "ISBNコードは0~9の数字のみの13桁を入力してください！";
            //}
            if(preg_match("/[^0-9]/", $_POST['isbn'])){
                echo "ISBNコードは0~9の数字のみです！";
            }
            ?>
    </form>

    <table class="Slist">
        <thead>
            <tr>
                <th scope="col">ID
                <th scope="col">
                <th scope="col">Title<br>
                <th scope="col">Description
                <th scope="col">etc.
        <tbody>
        <?php if (!$rows): ?>
            <tr><td colspan="3"><div class="td_notfound">該当する図書が見つかりませんでした</div>
                <br><div>検索条件を変更して検索しなおしてください。それぞれの検索条件はAND条件となっています。</div>
        <?php else: ?>
            <?php   foreach($rows as $id => $r): ?>
                <tr>
                    <!-- <td><?php echo htmlspecialchars($r['id']); ?> -->
                    <td class="td_id"><?php echo $id+1; ?>
                    <td>
                        <!-- <a href="checkout.php?isbn=<?php echo rawurlencode($r['isbn']); ?>">貸出</a> -->
                        <form action="checkout.php" method="post">
                            <input type="hidden" name="id" value="<?php echo rawurlencode($r['id']); ?>"> 

                            <?php if ($r['checkout_flg']===1): ?>
                                <a class="td_details" href="return.php?id=<?php echo rawurlencode($r['id']); ?>">貸出中…</a>
                                <div class="td_rtn"><?php echo htmlspecialchars($r['employee_id']); ?></div>
                                <div class="td_rtn">返却予定日:</div>
                                <div class="td_rtn"><?php echo htmlspecialchars($r['exp_return_date']); ?></div>
                            <?php else: ?>
                                <input class="button" type="submit" value="貸出">
                            <?php endif; ?>

                        </form>
                    <td class="td_title"><?php echo htmlspecialchars($r['title']); ?><br><img src= <?php echo htmlspecialchars($r['thumbnail_url']); ?>>
                        <br><div class="td_isbn">ISBN:<?php echo htmlspecialchars($r['isbn']); ?></div>
                    <td class="td_details"><?php echo htmlspecialchars($r['description']); ?>
                    <td class="td_details"><?php echo htmlspecialchars($r['author']); ?>
                        <div class="td_div"><?php echo htmlspecialchars($r['publisher']); ?></div>
                        <div class="td_div">出版日:<?php echo htmlspecialchars($r['publishe_date']); ?></div>
                        <div class="td_div">登録日:<?php echo htmlspecialchars($r['entry_date']); ?></div>
            <?php   endforeach; ?>
        <?php endif; ?>
    </table>
</body>
