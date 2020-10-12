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
      header('Location: ./return.php',true,307);
  }

  if (isset($_POST['id']) && ctype_digit($_POST['id'])){
    $sth = $dbh->prepare(
      'SELECT id, title, isbn, author, publisher,'
      . ' publishe_date, description, entry_date, thumbnail_url,'
      . ' checkout_date,employee_id,exp_return_date'
      . ' FROM bookshelf WHERE id= :id'
    );
    $sth->execute(['id' => $_POST['id']]);
    $origin = $sth->fetch(PDO::FETCH_ASSOC);

    $sth = $dbh->prepare(
        'SELECT id, return_ts, employee_id,'
        . ' checkout_date, return_date, rate, review'
        . ' FROM history WHERE id= :id'
        . ' ORDER BY return_ts DESC'
    );
    $sth->execute(['id' => $_POST['id']]);
    $history = $sth->fetchAll(PDO::FETCH_ASSOC);
  } 
?>
<!DOCTYPE html>
<head>
  <title>返却画面</title>
  <link rel="stylesheet" href="return.css">
  <link rel="stylesheet" href="https://ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/themes/smoothness/jquery-ui.css">
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
  <script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js"></script>
  <script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1/i18n/jquery.ui.datepicker-ja.min.js"></script>
  <script src="/js/jquery.raty.js"></script>
  <script>
    $(function() {
      $( "#datepicker" ).datepicker({
        dateFormat: 'yy-mm-dd',
        defaultDate: new Date(),
        minDate: new Date()
      });
      dt = new Date()
      y = dt.getFullYear();
      m = ("0" + (dt.getMonth() + 1)).slice(-2);
      d = ("0" + dt.getDate()).slice(-2);
      document.returnform.datepicker.value = y + "-" + m + "-" + d;

      //$('#star1').raty();
      $('#star1').raty({
          click: function(score) {
            $.post('./return.php',{score:score})
          }
      });
    });
  
  </script>
</head>

<body>
  <p class="error"><?php echo $error ?></p>
  <p>返却する本は、こちらで合っていますか？</p>
  <!-- <p>返却する場合は、返却ボタンを押してください。</p> -->
  <div class="block">
    <form action="return.php" method="post" name="returnform">
      <dl class="title">
        <dt class="dt_title"><?php echo htmlspecialchars($origin['title']); ?>
        <dt><img src= <?php echo htmlspecialchars($origin['thumbnail_url']); ?>>
        <dt class="dt_isbn">ISBN: <?php echo rawurlencode($origin['isbn']); ?>
      </dl>
      <dl class="edit">
          <dt class="dt_details">社員番号（借りている人）
          <dd class="dt_div"><?php echo rawurlencode($origin['employee_id']); ?>
          <dt class="dt_details">貸出日
          <dd class="dt_div"><?php echo rawurlencode($origin['checkout_date']); ?>
          <dt class="dt_details">返却予定日
          <dd class="dt_div"><?php echo htmlspecialchars($origin['exp_return_date']); ?>
          <dt class="dt_details">返却日
          <dd><input type="text" id="datepicker" name="return_date" required>
          <dt class="dt_details">レビュー
          <dd><p id="star1"></p>
          <dd><textarea id="review" name="review" rows="5" cols="30"></textarea>
          
      </dl>
      <input type="hidden" name="id" value="<?php echo rawurlencode($origin['id']); ?>">
      <input type="hidden" name="employee_id" value="<?php echo rawurlencode($origin['employee_id']); ?>">
      <input type="hidden" name="checkout_date" value="<?php echo htmlspecialchars($origin['checkout_date']); ?>">
      <input type="hidden" name="exp_return_date" value="<?php echo htmlspecialchars($origin['exp_return_date']); ?>">
      <input type="hidden" name="mode" value="1">
      <input class="return_button" type="submit" value="返却">
      <!-- <input class="button" type="button" onclick="history.back()" value="キャンセル">  -->
      <input class="general_button" type="button" onclick="location.href='index.php'" value="キャンセル"> 
    </form>
  </div>

  <!-- レビューリスト表示 -->
  <div class="block" id="reviewlist">
    <?php   foreach($history as $ht): ?>
      <p><?php echo rawurlencode($ht['employee_id']); ?></p>
      <p>貸出日:<?php echo htmlspecialchars($ht['checkout_date']); ?> 返却日:<?php echo htmlspecialchars($ht['return_date']); ?><p>
      <p><span class="star5_rating" data-rate=<?php echo rawurlencode($ht['rate']); ?>></p>
      <form action="editreview.php" method="post" onsubmit="return confirm_test()">
        <input type="submit" name="sub_update" value="編集">
        <input type="submit" name="sub_delete" value="削除">
      </form>
      <textarea id="ht_review" rows="5" cols="30" readonly><?php echo htmlspecialchars($ht['review']); ?></textarea>
    <?php   endforeach; ?>
  </div>
</body>