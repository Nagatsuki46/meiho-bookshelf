<?php

  $url = parse_url(getenv('DATABASE_URL'));
  $dsn = sprintf('pgsql:host=%s;dbname=%s',$url['host'],substr($url['path'],1));
  $dbh = new PDO(
          $dsn,
          $url['user'],
          $url['pass'],
          [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
  );

  $error ="";
  
  if ($_POST['display_flg']==="1"){
    $to_redirect="checkout.php";
  }else{
    $to_redirect="return.php";
  }
  if (isset($_POST['sub_delete'])){
    //選択された履歴を削除をする
    $sth = $dbh->prepare(
      'DELETE FROM history'
      . ' WHERE id= :id'
      . ' AND return_ts= :return_ts');
    $sth->execute([
      'id' => $_POST['id'],
      'return_ts' => $_POST['return_ts']
      ]);
    
    //POSTの情報を引き継ぐ形でリダイレクト(307)
    header('Location: '.$to_redirect,true,307);
    exit;
  }

  //更新ボタンが押されていたら履歴を更新する
  if (isset($_POST['sub_update'])){
    $sth = $dbh->prepare(
      'UPDATE history'
      . ' SET rate= :rate,'
      . ' review= :review'
      . ' WHERE id= :id'
      . ' AND return_ts= :return_ts');
    $sth->execute([
      'rate' => ($_POST['score']==="")?0:$_POST['score'],
      'review' => $_POST['review'],
      'id' => $_POST['id'],
      'return_ts' => $_POST['return_ts']
    ]);

    //POSTの情報を引き継ぐ形でリダイレクト(307)
    header('Location: '.$to_redirect,true,307);
    exit;
  }elseif(isset($_POST['sub_cancel'])){

    //POSTの情報を引き継ぐ形でリダイレクト(307)
    header('Location: '.$to_redirect,true,307);
    exit;
  }


  if (isset($_POST['id']) && ctype_digit($_POST['id'])){
    $sth = $dbh->prepare(
      'SELECT a.*,b.*'
      . ' FROM bookshelf AS a'
      . ' LEFT JOIN history AS b'
      . ' ON a.id=b.id'
      . ' WHERE a.id= :id'
      . ' AND b.return_ts= :return_ts');
    $sth->execute([
      'id' => $_POST['id'],
      'return_ts' => $_POST['return_ts']
      ]);
    $origin = $sth->fetch(PDO::FETCH_ASSOC);
  } 
?>
<!DOCTYPE html>
<head>
  <title>社内図書管理システム - レビュー編集画面</title>
  <link rel="stylesheet" href="./css/editreview.css">
  <link rel="stylesheet" href="./css/stardisp.css">
  <link rel="stylesheet" href="https://ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/themes/smoothness/jquery-ui.css">
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
  <script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js"></script>
  <script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1/i18n/jquery.ui.datepicker-ja.min.js"></script>
  <script src="./js/jquery.raty.js"></script>
  <script>
    $(function() {
      $('#star1').raty({
          score:<?php echo ($origin['rate']===null)?0:$origin['rate']; ?>,
          click: function(score) {
            $.post('./editreview.php',{score:score})
          }
      });
    });
  </script>
</head>

<body>
  <p class="error"><?php echo $error ?></p>
  <p>レビューの編集画面です。</p>
  <div class="block">
    <form action="editreview.php" method="post" name="returnform">
      <dl class="title">
        <dt class="dt_title"><?php echo htmlspecialchars($origin['title']); ?>
        <?php 
          $cover_image= empty($origin['cover_image'])?"Zg==":base64_encode(stream_get_contents($origin['cover_image'])); 
          if($cover_image=="Zg=="){
              $img_src = './img/noimage.png'; 
          }else{
              $img_src = 'data:images/jpeg;base64,'.$cover_image; 
          }
        ?>
        <dt><img src= <?php echo $img_src; ?>>
        <dt class="dt_isbn">ISBN: <?php echo rawurlencode($origin['isbn']); ?>
      </dl>
      <dl class="edit">
          <dt class="dt_details">貸出先（苗字のみ）
          <dd class="dt_div"><?php echo htmlspecialchars($origin['employee_id']); ?>
          <dt class="dt_details">貸出日
          <dd class="dt_div"><?php echo rawurlencode($origin['checkout_date']); ?>
          <dt class="dt_details">返却予定日
          <dd class="dt_div"><?php echo htmlspecialchars($origin['exp_return_date']); ?>
          <dt class="dt_details">返却日
          <dd class="dt_div"><?php echo htmlspecialchars($origin['return_date']); ?>
          <hr class="hr01">
          <dt class="dt_details">レビュー
          <dd><p id="star1"></p>
          <dd><textarea id="review" name="review" rows="5" cols="30"><?php echo htmlspecialchars($origin['review']); ?></textarea>
          
      </dl>
      <input type="hidden" name="id" value="<?php echo rawurlencode($origin['id']); ?>">
      <input type="hidden" name="return_ts" value="<?php echo $origin['return_ts']; ?>">
      <input type="hidden" name="mode" value="1">
      <input type="hidden" name="display_flg" value="<?php echo $_POST['display_flg']; ?>">
      <input class="edit_button" type="submit" name="sub_update" value="更新">
      <input class="general_button" type="submit" name="sub_cancel" value="キャンセル"> 
    </form>
  </div>
</body>