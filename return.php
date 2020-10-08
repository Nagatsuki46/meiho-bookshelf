 <?php
  $url = parse_url(getenv('DATABASE_URL'));
  $dsn = sprintf('pgsql:host=%s;dbname=%s',$url['host'],substr($url['path'],1));
  $dbh = new PDO(
          $dsn,
          $url['user'],
          $url['pass'],
          [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
  );

  //返却ボタンのsubmit時の入力チェックをいれる（mode=1で判別）
  if (isset($_POST['id']) && $_POST['mode']==="1"){
    $sth = $dbh->prepare(
      'UPDATE bookshelf'
      . ' SET checkout_flg=0,'
      . ' employee_id=null,'
      . ' return_date= :return_date'
      . ' WHERE id= :id');
    $sth->execute([
      'id' => $_POST['id'],
      'return_date' => $_POST['return_date']
      ]);
    header('Location: ./index.php');
  }

  if (isset($_GET['id']) && ctype_digit($_GET['id'])){
    $sth = $dbh->prepare(
      'SELECT id, title, isbn, author, publisher,'
      . ' publishe_date, description, entry_date, thumbnail_url,'
      . ' employee_id,exp_return_date'
      . ' FROM bookshelf WHERE id= :id');
    $sth->execute(['id' => $_GET['id']]);
    $origin = $sth->fetch(PDO::FETCH_ASSOC);
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
  <script>
    $(function() {
      $( "#datepicker" ).datepicker({
        defaultDate: new Date(),
        minDate: new Date()
      });
      dt = new Date()
      y = dt.getFullYear();
      m = ("0" + (dt.getMonth() + 1)).slice(-2);
      d = ("0" + dt.getDate()).slice(-2);
      document.returnform.datepicker.value = y + "/" + m + "/" + d;
    });
  </script>
</head>

<body>
  <p>返却する本は、こちらで合っていますか？</p>
  <p>返却する場合は、返却ボタンを押してください。</p>
  <form action="return.php" method="post" name="returnform">
    <dl>
      <dt class="dt_title"><?php echo htmlspecialchars($origin['title']); ?>
      <dt><img src= <?php echo htmlspecialchars($origin['thumbnail_url']); ?>>
      <dt class="dt_isbn">ISBN: <?php echo rawurlencode($origin['isbn']); ?>
    </dl>
    <dl class="edit">
        <dt class="dt_details">社員番号（借りている人）
        <dd class="dt_div"><?php echo htmlspecialchars($origin['employee_id']); ?>
        <dt class="dt_details">返却予定日
        <dd class="dt_div"><?php echo htmlspecialchars($origin['exp_return_date']); ?>
        <dt class="dt_details">返却日
        <dd><input type="text" id="datepicker" name="return_date" required>
    </dl>
    <input type="hidden" name="id" value="<?php echo rawurlencode($origin['id']); ?>">
    <input type="hidden" name="mode" value="1">
    <input type="submit" value="返却">
    <input type="button" onclick="history.back()" value="キャンセル">
  </form>
</body>