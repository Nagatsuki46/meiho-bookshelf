<?php

    //セッションを使って検索条件を保持する。
    session_cache_expire(30);
    session_start();
    $_SESSION['delete'] = ""; 

    if(!isset($_POST['isbn'])){
        $_POST['isbn'] = "";
        $_POST['keyword'] = "";
        $_POST['category'] = 0;
        //$_POST['status'] = "ID降順";
        $_POST['status'] = "未返却順";
    }

    //検索結果に表示するページ番号の設定
    if(!isset($_SESSION['offset'])){
        $_SESSION['offset'] = 0;
    }

    if (isset($_SESSION['edit_flg']) && $_SESSION['edit_flg']==="1"){

        if (!isset($_SESSION['isbn'])){
            $_SESSION['isbn'] = "";
            $_SESSION['keyword'] = "";
            $_SESSION['category'] = 0;
            //$_SESSION['status'] = "ID降順";
            $_SESSION['status'] = "未返却順";
        }
        $_POST['isbn'] = $_SESSION['isbn'];
        $_POST['keyword'] = $_SESSION['keyword'];
        $_POST['category'] = $_SESSION['category'];
        $_POST['status'] = $_SESSION['status'];
       // $_SESSION['edit_flg'] = "";
    }else{
        $_SESSION['isbn'] = $_POST['isbn'];
        $_SESSION['keyword'] = $_POST['keyword'];
        $_SESSION['category'] = $_POST['category'];
        $_SESSION['status'] = $_POST['status'];
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
    if($_POST['keyword'] != ""){
        $where = $where . " AND (a.title ILIKE '%" . $_POST['keyword'] ."%'";
        $where = $where . " OR a.description ILIKE '%" . $_POST['keyword'] ."%'";
        $where = $where . " OR a.author ILIKE '%" . $_POST['keyword'] ."%'";
        $where = $where . " OR a.publisher ILIKE '%" . $_POST['keyword'] ."%'";
        $where = $where . " OR a.employee_id ILIKE '%" . $_POST['keyword'] . "%'";
        $where = $where .")";
    }

    if($_POST['category'] !=="0" && !empty($_POST['category'])){
        $where = $where ." AND a.category_id=" .$_POST['category'];
    }
    //echo $_POST['category'];

    switch($_POST['status']){
        case "ID降順":
            $order = " ORDER BY id DESC";
            break;
        case "ID昇順":
            $order = " ORDER BY id ASC";
            break;
        case "貸出日降順":
            $order = " ORDER BY checkout_date DESC NULLS LAST";
            break;
        case "更新日時降順":
            $order = " ORDER BY update_ts DESC NULLS LAST";
            break;
        case "出版日順":
            $order = " ORDER BY publishe_date";
            break;
        case "未返却順":
            $order = " ORDER BY checkout_flg DESC,checkout_date DESC NULLS LAST";
            break;
    }


    $sth = $dbh->query(
        'SELECT count(*) AS cnt'
        . ' FROM bookshelf AS a'
        . $where
    );
    $cnt = $sth->fetch(PDO::FETCH_ASSOC);

    if (isset($_SESSION['edit_flg']) && $_SESSION['edit_flg']==="1"){
        $_SESSION['edit_flg'] = "";
    }else{
        if(isset($_POST['search']) || isset($_POST['first_page'])){
            $_SESSION['offset'] = 0;
        }elseif(isset($_POST['pre_page'])){
            if($_SESSION['offset'] - 10 >= 0){
                $_SESSION['offset'] = $_SESSION['offset'] - 10;
            }
        }elseif(isset($_POST['next_page'])){
            if($cnt['cnt'] > $_SESSION['offset'] + 10){
                $_SESSION['offset'] = $_SESSION['offset'] + 10;
            }
        }elseif(isset($_POST['last_page'])){
            $_SESSION['offset'] =(floor($cnt['cnt']/10)*10 == $cnt['cnt'])?($cnt['cnt']-10):floor($cnt['cnt']/10)*10;
        }else{
            $_SESSION['offset'] = 0;
        }
    }
    
    $sth = $dbh->prepare(
        'SELECT a.*,b.avg_rate,c.cnt_review,d.col_cnt'
        . ' FROM bookshelf AS a'
        . ' LEFT JOIN'
        . ' (SELECT id,AVG(rate) AS avg_rate FROM history WHERE rate>0 GROUP BY id) AS b'
        . ' ON a.id=b.id'
        . ' LEFT JOIN'
        . ' (SELECT id,COUNT(*) AS cnt_review FROM history GROUP BY id) AS c'
        . ' ON a.id=c.id'
        . ' LEFT JOIN'
        . ' (SELECT isbn,count(*) AS col_cnt FROM bookshelf GROUP BY isbn) AS d'
        . ' ON a.isbn=d.isbn'
        .  $where
        //. ' ORDER BY a.id DESC'
        .  $order
        . ' LIMIT 10'
        . ' OFFSET :offset'
    );
    $sth->execute([
        'offset' => $_SESSION['offset']
        ]);
    $rows = $sth->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<head>
    <title>社内図書管理システム - 図書の検索</title>
    <link rel="stylesheet" href="./css/list.css">
    <link rel="stylesheet" href="./css/stardisp.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
    <script>
        function confirm_delete() {
            if(document.form_search_bottom.key.value==="削除"){
                var select = confirm("本当に書籍情報を削除しますか？");
                return select;
            }else{
                return true;
            } 
        };
        $(function(){
			$("#isbn").change(function(){
					var str = $(this).val();
					str = str.replace( /[Ａ-Ｚａ-ｚ０-９－！”＃＄％＆’（）＝＜＞，．？＿［］｛｝＠＾～￥]/g, function(s) {
							return String.fromCharCode(s.charCodeAt(0) - 65248);
					});
					$(this).val(str);
			}).change();
	    });
    </script>
</head>

<body>
    <form class="form_search" name="form_search" action="index.php" method="post">
        <div>
            ISBN CD: <input type="search" name="isbn" id="isbn" maxlength='13' value="<?php echo $_POST['isbn']?>">
            Keyword: <input type="search" name="keyword" value="<?php echo $_POST['keyword']?>">
            Category: <select name="category" onchange="submit(this.form)">
                <option value=0 <?php echo ($_POST['category']==0)?"selected":""; ?>>指定なし</option>
                <option value=1 <?php echo ($_POST['category']==1)?"selected":""; ?>>1.ネットワーク系</option>
                <option value=2 <?php echo ($_POST['category']==2)?"selected":""; ?>>2.サーバー系</option>
                <option value=3 <?php echo ($_POST['category']==3)?"selected":""; ?>>3.システム開発系</option>
                <option value=4 <?php echo ($_POST['category']==4)?"selected":""; ?>>4.ビジネス書系</option>
                <option value=9 <?php echo ($_POST['category']==9)?"selected":""; ?>>9.その他</option>
            </select>
            Sort: <select name="status" onchange="submit(this.form)">
                <option value="ID降順" <?php echo ($_POST['status']=="ID降順")?"selected":""; ?>>ID降順</option>
                <option value="ID昇順" <?php echo ($_POST['status']=="ID昇順")?"selected":""; ?>>ID昇順</option>
                <option value="貸出日降順" <?php echo ($_POST['status']=="貸出日降順")?"selected":""; ?>>貸出日降順</option>
                <option value="更新日時降順" <?php echo ($_POST['status']=="更新日時降順")?"selected":""; ?>>更新日時降順</option>
                <option value="出版日順" <?php echo ($_POST['status']=="出版日順")?"selected":""; ?>>出版日順</option>
                <option value="未返却順" <?php echo ($_POST['status']=="未返却順")?"selected":""; ?>>未返却順</option>
            </select>
            <input class="button" type="submit" name="search" value="Search">
            <input class="addbook_button" type="button" onclick="location.href='bookadd.php'" value="">
        </div>
        <hr class="hr01">
        <div class="page_search">
            <input class="button" type="submit" name="first_page" value="<<">
            <input class="button" type="submit" name="pre_page" value="<">
            <input class="button" type="submit" name="next_page" value=">">
            <input class="button" type="submit" name="last_page" value=">>">
            <span>Page: <?php echo intdiv($_SESSION['offset'],10)+1 ?> / <?php echo intdiv($cnt['cnt']-1,10)+1 ?> (<?php echo $cnt['cnt'] ?>)</span>
            <?php
            //if(!empty($_POST['isbn']) and !preg_match("/[0-9]{13}/", $_POST['isbn'])){
            //    echo "ISBNコードは0~9の数字のみの13桁を入力してください！";
            //}
            if(preg_match("/[^0-9]/", $_POST['isbn'])){
                echo "ISBNコードは0~9の数字のみです！";
            }
            ?>
        </div>
    </form>

    <script type="text/javascript">
        document.form_search.isbn.focus();
    </script>

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
                    <td class="td_id"><?php echo $id+$_SESSION['offset']+1; ?>
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
                    <!-- APIURLから取得をやめ、DBにバイナリ格納する方式に変更 -->
                    <!-- <td class="td_title"><?php echo htmlspecialchars($r['title']); ?><br><img src= <?php echo htmlspecialchars($r['thumbnail_url']); ?>> -->
                    <?php 
                        $cover_image= empty($r['cover_image'])?"Zg==":base64_encode(stream_get_contents($r['cover_image'])); 
                        if($cover_image=="Zg=="){
                            $img_src = './img/noimage.png'; 
                        }else{
                            $img_src = 'data:images/jpeg;base64,'.$cover_image; 
                        }
                    ?>
                    <td class="td_title"><?php echo htmlspecialchars($r['title']); ?><br><img src= <?php echo $img_src; ?>>
                        <br><div class="td_isbn">ISBN:<?php echo htmlspecialchars($r['isbn']); ?></div>
                    <td class="td_details" id="td_description"><?php echo htmlspecialchars($r['description']); ?>

                    <!-- カテゴリ名を設定 -->
                    <?php switch($r['category_id']): case 1: ?>
                        <?php $category_nm = "1:ネットワーク系"; break; ?>
                        <?php case 2: $category_nm = "2:サーバー系"; break; ?>
                        <?php case 3: $category_nm = "3:システム開発系"; break; ?>
                        <?php case 4: $category_nm = "4:ビジネス書系"; break; ?>
                        <?php case 9: $category_nm = "9:その他"; break; ?>
                        <?php default: $category_nm = ""?>
                    <?php endswitch; ?>
                    <td class="td_details"><?php echo htmlspecialchars($category_nm); ?>
                        <div class="td_div"><?php echo htmlspecialchars($r['author']); ?></div>
                        <div class="td_div"><?php echo htmlspecialchars($r['publisher']); ?></div>
                        <div class="td_div">出版日:<?php echo htmlspecialchars($r['publishe_date']); ?></div>
                        <div class="td_div">登録日:<?php echo htmlspecialchars($r['entry_date']); ?></div>
                        <div class="td_div">所蔵冊数:<?php echo rawurlencode($r['isbn']=="N/A"?1:$r['col_cnt']); ?></div>
                        <div class="td_div">ID:<?php echo rawurlencode($r['id']); ?></div>
                        <form name="book_edit" action="bookedit.php" method="post" onsubmit="return confirm_delete()">
                            <input type="hidden" name="id" value="<?php echo rawurlencode($r['id']); ?>"> 
                            <input class="button" type="submit" value="修正" onclick="document.form_search_bottom.key.value='修正'">
                            <input class="button" type="submit" name="sub_delete" value="削除" onclick="document.form_search_bottom.key.value='削除'">
                        </form>
            <?php   endforeach; ?>
        <?php endif; ?>
    </table>
    <hr class="hr01">
    <form class="form_search_bottom" name="form_search_bottom" action="index.php" method="post">
        <input type="hidden" name="isbn" value="<?php echo $_POST['isbn']?>">
        <input type="hidden" name="keyword" value="<?php echo $_POST['keyword']?>">
        <input type="hidden" name="category" value="<?php echo $_POST['category']?>">
        <input type="hidden" name="status" value="<?php echo $_POST['status']?>">
        <input class="button" type="submit" name="first_page" value="<<">
        <input class="button" type="submit" name="pre_page" value="<">
        <input class="button" type="submit" name="next_page" value=">">
        <input class="button" type="submit" name="last_page" value=">>">
        <span>Page: <?php echo intdiv($_SESSION['offset'],10)+1 ?> / <?php echo intdiv($cnt['cnt']-1,10)+1 ?> (<?php echo $cnt['cnt'] ?>)</span>
        <input type="hidden" name="key" value="">
    </form>
</body>
