<?php
  //セッションの時間を設定する
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
  $error = "";
  if (isset($_POST['mode']) && $_POST['mode']==="1" && isset($_POST['checkout_date']) && isset($_POST['exp_return_date'])){
    //if(preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/', $_POST['exp_return_date'])){
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
        . ' checkout_ts= :checkout_ts,'
        . ' employee_id= :employee_id,'
        . ' exp_return_date= :exp_return_date'
        . ' WHERE id= :id');
      $sth->execute([
        'id' => $_POST['id'],
        'checkout_date' => $_POST['checkout_date'],
        'checkout_ts' => date("Y-m-d H:i:s"),
        'employee_id' => $_POST['employee_id'],
        'exp_return_date' => $_POST['exp_return_date']
        ]);
      header('Location: ./index.php');
      exit;
    }
  }

  if (isset($_POST['id']) && ctype_digit($_POST['id'])){
    $sth = $dbh->prepare(
      'SELECT *'
      . ' FROM bookshelf WHERE id= :id');
    $sth->execute(['id' => $_POST['id']]);
    $origin = $sth->fetch(PDO::FETCH_ASSOC);

    $sth = $dbh->prepare(
      'SELECT *'
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
  <link rel="stylesheet" href="../css/checkout.css">
  <link rel="stylesheet" href="../css/stardisp.css">
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

    function confirm_delete() {
        if (document.checkoutform.key.value === "削除"){
          var select = confirm("本当にレビューを削除しますか？\nレビューを削除すると貸出履歴も削除されます。");
          return select;
        }else{
          return true;
        }
    };
  </script>
</head>

<body>
  <p class="error"><?php echo $error ?></p>
  <p>借りたい本は、こちらで合っていますか？</p>
  <!-- <p>借りる場合は、社員番号と返却予定日を入れて、貸出ボタンを押してください。</p> -->
  <div class="block">
    <form action="checkout.php" method="post" name="checkoutform">
      <dl>
        <dt class="dt_title"><?php echo htmlspecialchars($origin['title']); ?>
        <!-- <dt><img src= <?php echo htmlspecialchars($origin['thumbnail_url']); ?>> -->
        <?php $img_src = 'data:images/jpeg;base64,'.base64_encode(stream_get_contents($origin['cover_image'])); ?>
        <dt><img src= <?php echo $img_src; ?>>
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
      <!-- <input class="button" type="button" onclick="history.back()" value="キャンセル"> -->
      <input class="general_button" type="button" onclick="location.href='index.php'" value="キャンセル">
      <input type="hidden" name="key" value="">
    </form>
  </div>

  <!-- レビューリスト表示 -->
  <div class="block" id="reviewlist">
    <?php   foreach($history as $ht): ?>
      <p><?php echo rawurlencode($ht['employee_id']); ?></p>
      <p>貸出日:<?php echo htmlspecialchars($ht['checkout_date']); ?> 返却日:<?php echo htmlspecialchars($ht['return_date']); ?><p>
      <p><span class="star5_rating" data-rate=<?php echo rawurlencode($ht['rate']); ?>></p>

      <!-- レビュー編集・削除フォーム -->
      <form action="editreview.php" method="post" name="edit_review" onsubmit="return confirm_delete()">
        <input type="hidden" name="id" value="<?php echo rawurlencode($ht['id']); ?>">
        <input type="hidden" name="return_ts" value="<?php echo $ht['return_ts']; ?>">
        <input type="hidden" name="display_flg" value="1">
        <input type="submit" name="sub_edit"  value="編集" onclick="checkoutform.key.value='編集'" >
        <input type="submit" name="sub_delete"  value="削除" onclick="checkoutform.key.value='削除'" >
      </form>
      <p><textarea id="ht_review" rows="5" cols="30" readonly><?php echo htmlspecialchars($ht['review']); ?></textarea></p>
    <?php   endforeach; ?>
  </div>
  
</body>