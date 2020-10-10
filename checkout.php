 <?php
  
  //セッションを使って検索条件を保持する
  session_cache_expire(60);
  session_start();
  $_SESSION['edit_flg'] = "1";

  $url = parse_url(getenv('DATABASE_URL'));
  $dsn = sprintf('pgsql:host=%s;dbname=%s',$url['host'],substr($url['path'],1));
  $dbh = new PDO(
          $dsn,
          $url['user'],
          $url['pass'],
          [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
  );

  //貸出ボタンのsubmit時の入力チェックをいれる（mode=1で判別）
  if (isset($_POST['mode']) && $_POST['mode']==="1"){

    //if(preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/', $_POST['exp_return_date'])){
    $error = "";
    if(date("Y-m-d",strtotime($_POST['checkout_date']))!==$_POST['checkout_date']){
      $error = $error . "<p class='error'>※入力された貸出日(" . $_POST['checkout_date'] . ")が正しくありません。</p>";
    }
    if(date("Y-m-d",strtotime($_POST['exp_return_date']))!==$_POST['exp_return_date']){
      $error = $error ."<p class='error'>※入力された返却予定日(" . $_POST['exp_return_date'] . ")が正しくありません。</p>";
    }

    if ($error===""){
      $sth = $dbh->prepare(
        'UPDATE bookshelf'
        . ' SET checkout_flg=1,'
        . ' checkout_date= :checkout_date,'
        . ' employee_id= :employee_id,'
        . ' exp_return_date= :exp_return_date'
        . ' WHERE id= :id');
      $sth->execute([
        'id' => $_POST['id'],
        'checkout_date' => $_POST['checkout_date'],
        'employee_id' => $_POST['employee_id'],
        'exp_return_date' => $_POST['exp_return_date']
        ]);
      header('Location: ./index.php');
    }
  }

  if (isset($_POST['id']) && ctype_digit($_POST['id'])){
    $sth = $dbh->prepare(
      'SELECT id, title, isbn, author, publisher,'
      . ' publishe_date, description, entry_date, thumbnail_url'
      . ' FROM bookshelf WHERE id= :id');
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
  <title>貸出画面</title>
  <link rel="stylesheet" href="checkout.css">
  <link rel="stylesheet" href="https://ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/themes/smoothness/jquery-ui.css">
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
  <script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js"></script>
  <script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1/i18n/jquery.ui.datepicker-ja.min.js"></script>
  <script>
    $(function() {
      $( "#dtp1" ).datepicker({
        dateFormat: 'yy-mm-dd',
        defaultDate: new Date(),
      });
      $( "#dtp2" ).datepicker({
        dateFormat: 'yy-mm-dd',
        defaultDate: new Date(),
        minDate: new Date()
      });
      dt = new Date()
      y = dt.getFullYear();
      m = ("0" + (dt.getMonth() + 1)).slice(-2);
      d = ("0" + dt.getDate()).slice(-2);
      document.checkoutform.dtp1.value = y + "-" + m + "-" + d;

      dt.setDate(dt.getDate() + 7);
      y = dt.getFullYear();
      m = ("0" + (dt.getMonth() + 1)).slice(-2);
      d = ("0" + dt.getDate()).slice(-2);
      document.checkoutform.dtp2.value = y + "-" + m + "-" + d;
    });
  </script>
</head>

<body>
  <?php echo $error ?>
  <p>借りたい本は、こちらで合っていますか？</p>
  <!-- <p>借りる場合は、社員番号と返却予定日を入れて、貸出ボタンを押してください。</p> -->
  <form action="checkout.php" method="post" name="checkoutform">
    <div class="block">
      <dl>
        <dt class="dt_title"><?php echo htmlspecialchars($origin['title']); ?>
        <dt><img src= <?php echo htmlspecialchars($origin['thumbnail_url']); ?>>
        <dt class="dt_isbn">ISBN: <?php echo rawurlencode($origin['isbn']); ?>
      </dl>
      <dl class="edit">
          <dt class="dt_details">社員番号（借りる人）
          <dd><input type="text" name="employee_id" required>
          <dt class="dt_details">貸出日
          <dd><input type="text" id="dtp1" name="checkout_date" required>
          <dt class="dt_details">返却予定日
          <dd><input type="text" id="dtp2" name="exp_return_date" required>
      </dl>
      <input type="hidden" name="id" value="<?php echo rawurlencode($origin['id']); ?>">
      <input type="hidden" name="mode" value="1">
      <input class="checkout_button" type="submit" value="貸出">
      <input class="button" type="button" onclick="history.back()" value="キャンセル">
    </div>

    <!-- レビューリスト表示 -->
    <div class="block" id="reviewlist">
      <?php   foreach($history as $ht): ?>
        <p><span class="star5_rating" data-rate=<?php echo rawurlencode($ht['rate']); ?>></p>
        <p><?php echo rawurlencode($ht['employee_id']); ?></p>
        <p>貸出日:<?php echo htmlspecialchars($ht['checkout_date']); ?> 返却日:<?php echo htmlspecialchars($ht['return_date']); ?><p>
        <textarea id="ht_review" rows="4" cols="30" readonly><?php echo htmlspecialchars($ht['review']); ?></textarea>
      <?php   endforeach; ?>
    </div>
  </form>
</body>