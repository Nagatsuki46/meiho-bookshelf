<?php

    //セッションを使って検索条件を保持する。
    session_cache_expire(60);
    session_start();

    if(!isset($_POST['isbn'])){
        $_POST['isbn'] = "";
        $_POST['title'] = "";
        $_POST['description'] ="";
    }

    if (isset($_SESSION['edit_flg']) && $_SESSION['edit_flg']==="1"){

        if (!isset($_SESSION['isbn'])){
            $_SESSION['isbn'] = "";
            $_SESSION['title'] = "";
            $_SESSION['description'] ="";
        }
        $_POST['isbn'] = $_SESSION['isbn'];
        $_POST['title'] = $_SESSION['title'];
        $_POST['description'] = $_SESSION['description'];
        $_SESSION['edit_flg'] = "";
    }else{
        $_SESSION['isbn'] = $_POST['isbn'];
        $_SESSION['title'] = $_POST['title'];
        $_SESSION['description'] = $_POST['description'];
    }
    
    $url = parse_url(getenv('DATABASE_URL'));
    $dsn = sprintf('pgsql:host=%s;dbname=%s',$url['host'],substr($url['path'],1));
    $dbh = new PDO(
            $dsn,
            $url['user'],
            $url['pass'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    $where = " WHERE 1=1";

    if($_POST['isbn'] != ""){$where = $where . " AND a.isbn LIKE '" . $_POST['isbn'] ."%'";}
    if($_POST['title'] != ""){$where = $where . " AND a.title LIKE '%" . $_POST['title'] ."%'";}
    if($_POST['description'] != ""){$where = $where . " AND a.description LIKE '%" . $_POST['description'] ."%'";}

    $sth = $dbh->query(
        /*'SELECT id, title, isbn, author, publisher,'
        . 'publishe_date, description, entry_date, thumbnail_url,'
        . 'checkout_flg, checkout_date, employee_id, exp_return_date' */
        'SELECT a.*,b.avg_rate,c.cnt_review'
        . ' FROM bookshelf AS a'
        . ' LEFT JOIN'
        . ' (SELECT id,AVG(rate) AS avg_rate FROM history WHERE rate>0 GROUP BY id) AS b'
        . ' ON a.id=b.id'
        . ' LEFT JOIN'
        . ' (SELECT id,COUNT(*) AS cnt_review FROM history GROUP BY id) AS c'
        . ' ON a.id=c.id'
        .  $where
        . ' ORDER BY a.checkout_ts DESC NULLS LAST,a.id'
    );
    $rows = $sth->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<head>
    <title>図書の検索</title>
    <link rel="stylesheet" href="../css/list.css">
    <link rel="stylesheet" href="../css/stardisp.css">
</head>

<body>
    <hr class="hr01">
    <form class="form_search" action="index.php" method="post">
        ISBN CD: <input type="text" name="isbn" maxlength='13' value="<?php echo $_POST['isbn']?>">
        Title: <input type="text" name="title" value="<?php echo $_POST['title']?>">
        Description: <input type="text" name="description" value="<?php echo $_POST['description']?>">
        <input class="button" type="submit" value="Search">
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
                <th scope="col">No.
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
                        <?php if ($r['checkout_flg']===1): ?>
                            <!-- <a class="td_details" href="return.php?id=<?php echo rawurlencode($r['id']); ?>">貸出中…</a> -->
                            <form action="return.php" method="post">
                                <input type="hidden" name="id" value="<?php echo rawurlencode($r['id']); ?>"> 
                                <input class="return_button" type="submit" value="返却">
                                <div class="td_rtn"><?php echo htmlspecialchars($r['employee_id']); ?></div>
                                <div class="td_rtn">貸出日:</div>
                                <div class="td_rtn"><?php echo htmlspecialchars($r['checkout_date']); ?></div>
                                <div class="td_rtn">返却予定日:</div>
                                <div class="td_rtn"><?php echo htmlspecialchars($r['exp_return_date']); ?></div>
                            </form>
                        <?php else: ?>
                            <form action="checkout.php" method="post">
                                <input type="hidden" name="id" value="<?php echo rawurlencode($r['id']); ?>"> 
                                <input class="checkout_button" type="submit" value="貸出">
                            </form>
                        <?php endif; ?>
                        <?php if ($r['avg_rate']>0): 
                            $avg_rate = round($r['avg_rate'],1);
                            $star_rate = round($avg_rate*2)/2
                        ?>
                            <p><span class="star5_rating" data-rate=<?php echo rawurlencode($star_rate); ?>></span><?php echo rawurlencode($avg_rate); ?></p>
                        <?php else: ?>
                            <p><span class="star5_rating" data-rate=0></span></p>
                        <?php endif; ?>
                        <?php if ($r['cnt_review']>0): ?>
                            <form name=form<?php echo $id; ?> action=<?php echo ($r['checkout_flg']===1)? "return.php":"checkout.php"; ?> method="post">
                                <a href="javascript:document.form<?php echo $id; ?>.submit();"><?php echo rawurlencode($r['cnt_review']); ?>件</a>
                                <input type="hidden" name="id" value="<?php echo rawurlencode($r['id']); ?>"> 
                            </form>
                        <?php endif; ?>
                        <?php $img_src = 'data:images/jpeg;base64,'.base64_encode(stream_get_contents($r['cover_image'])); ?>
                    <!-- <td class="td_title"><?php echo htmlspecialchars($r['title']); ?><br><img src= <?php echo htmlspecialchars($r['thumbnail_url']); ?>> -->
                    <td class="td_title"><?php echo htmlspecialchars($r['title']); ?><br><img src= <?php echo $img_src; ?>>
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
