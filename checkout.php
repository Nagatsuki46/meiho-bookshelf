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
  <link rel="stylesheet" href="//apps.bdimg.com/libs/jqueryui/1.10.4/css/jquery-ui.min.css">
  <script src="//apps.bdimg.com/libs/jquery/1.10.2/jquery.min.js"></script>
  <script src="//apps.bdimg.com/libs/jqueryui/1.10.4/jquery-ui.min.js"></script>
  <link rel="stylesheet" href="jqueryui/style.css">
  <script>
    $(function() {
      $( "#datepicker" ).datepicker();
    });
  </script>
</head>

<form action="index.php" method="post">
  <dl>
    <dt><?php echo htmlspecialchars($origin['title']); ?>
    <dt><img src= <?php echo htmlspecialchars($origin['thumbnail_url']); ?>>
    <dt>ISBN: <?php echo rawurlencode($origin['isbn']); ?>
    <dt>社員番号（借りる人）
    <dd><input type="text" name="employee_num" value="">
    <dt>返却日
    <dd><input type="text" id="datepicker">
  </dl>
  <input type="submit" value="貸出">
  <input type="button" onclick="history.back()" value="キャンセル">
</form>