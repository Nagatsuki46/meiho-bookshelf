 <?php
$url = parse_url(getenv('DATABASE_URL'));
$dsn = sprintf('pgsql:host=%s;dbname=%s',$url['host'],substr($url['path'],1));
$dbh = new PDO(
        $dsn,
        $url['user'],
        $url['pass'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);
if (isset($_POST['isbn']) && ctype_digit($_POST['isbn'])){
  $sth = $dbh->prepare(
    'SELECT id, title, isbn, author, publisher,'
    . ' publishe_date, description, entry_date, thumbnail_url'
    . ' FROM bookshelf WHERE isbn= :isbn');
  $sth->execute(['isbn' => $_POST['isbn']]);
  $origin = $sth->fetch(PDO::FETCH_ASSOC);
} 
?>
<!DOCTYPE html>
<head>
  <title>貸出画面</title>
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
      document.checkoutform.datepicker.value = y + "/" + m + "/" + d;
    });
  </script>
</head>

<form action="index.php" method="post" name="checkoutform">
  <dl>
    <dt><?php echo htmlspecialchars($origin['title']); ?>
    <dt><img src= <?php echo htmlspecialchars($origin['thumbnail_url']); ?>>
    <dt>ISBN: <?php echo rawurlencode($origin['isbn']); ?>
    <dt>社員番号（借りる人）
    <dd><input type="text" name="employee_num" value="">
    <dt>返却予定日
    <dd><input type="text" id="datepicker" name="datepicker">
  </dl>
  <input type="submit" value="貸出">
  <input type="button" onclick="history.back()" value="キャンセル">
</form>