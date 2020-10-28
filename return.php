<?php

  //セッションを使って検索条件を保持する
  session_cache_expire(30);
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

  //返却ボタンのsubmit時の入力チェックをいれる（mode=1で判別）
  $error ="";
  //レビュー編集画面から戻った際に返却日が空文字となるため、条件追加
  if (isset($_POST['mode']) && $_POST['mode']==="1" && isset($_POST['return_date'])){
    if (date("Y-m-d",strtotime($_POST['return_date']))===$_POST['return_date']){
      $sth = $dbh->prepare(
        'UPDATE bookshelf'
        . ' SET checkout_flg=0,'
        . ' employee_id=null,'
        . ' return_date= :return_date,'
        . ' update_ts = :update_ts'
        . ' WHERE id= :id');
      $sth->execute([
        'id' => $_POST['id'],
        'return_date' => $_POST['return_date'],
        'update_ts' => date("Y-m-d H:i:s")
        ]);

      //履歴テーブルへ貸出履歴を登録する
      $sth = $dbh->prepare(
        'INSERT INTO history'
        . '(id,return_ts,employee_id,checkout_date,exp_return_date,return_date,rate,review)'
        . ' VALUES('
        . ':id,:return_ts,:employee_id,:checkout_date,:exp_return_date,:return_date,:rate,:review)'
      );
      $sth->execute([
        'id' => $_POST['id'],
        'return_ts' => date("Y-m-d H:i:s"),
        'employee_id' => $_POST['employee_id'],
        'checkout_date' => $_POST['checkout_date'],
        'exp_return_date' => $_POST['exp_return_date'],
        'return_date' => $_POST['return_date'],
        'rate' => ($_POST['score']==="")?0:$_POST['score'],
        'review' => $_POST['review']
      ]);

      header('Location: ./index.php');
      exit;
    }else{
      $error = "※入力された返却日(" . $_POST['return_date'] . ")が正しくありません。";
    }
  }

  if (isset($_POST['id']) && ctype_digit($_POST['id'])){
    $sth = $dbh->prepare(
      'SELECT *'
      . ' FROM bookshelf WHERE id= :id'
    );
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
  <title>返却画面</title>
  <link rel="stylesheet" href="./css/return.css">
  <link rel="stylesheet" href="./css/stardisp.css">
  <link rel="stylesheet" href="https://ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/themes/smoothness/jquery-ui.css">
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
  <script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js"></script>
  <script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1/i18n/jquery.ui.datepicker-ja.min.js"></script>
  <script src="./js/jquery.raty.js"></script>
  <script>
    $(function() {
      $( "#datepicker" ).datepicker({
        dateFormat: 'yy-mm-dd',
        defaultDate: new Date()

        //返却日が過去日になってしまうケースも考えられるので、コメントアウトしておく
        //defaultDate: new Date(),
        //minDate: new Date()
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
    function confirm_delete() {
        if (document.returnform.key.value === "削除"){
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
  <p>返却する本は、こちらで合っていますか？</p>
  <!-- <p>返却する場合は、返却ボタンを押してください。</p> -->
  <div class="block">
    <form action="return.php" method="post" name="returnform">
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
        <dt class="dt_isbn">ISBN: <?php echo htmlspecialchars($origin['isbn']); ?>
      </dl>
      <hr class="hr01">
      <dl class="edit">
          <dt class="dt_details">借受者情報（苗字のみ）
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
      <input type="hidden" name="employee_id" value="<?php echo htmlspecialchars($origin['employee_id']); ?>">
      <input type="hidden" name="checkout_date" value="<?php echo htmlspecialchars($origin['checkout_date']); ?>">
      <input type="hidden" name="exp_return_date" value="<?php echo htmlspecialchars($origin['exp_return_date']); ?>">
      <input type="hidden" name="mode" value="1">
      <input class="return_button" type="submit" value="返却">
      <!-- <input class="button" type="button" onclick="history.back()" value="キャンセル">  -->
      <input class="general_button" type="button" onclick="location.href='index.php'" value="キャンセル"> 
      <input type="hidden" name="key" value="">
    </form>
  </div>

  <!-- レビューリスト表示 -->
  <div class="block" id="reviewlist">
    <?php   foreach($history as $ht): ?>
      <p><?php echo htmlspecialchars($ht['employee_id']); ?></p>
      <p>貸出日:<?php echo htmlspecialchars($ht['checkout_date']); ?> 返却日:<?php echo htmlspecialchars($ht['return_date']); ?><p>
      <p><span class="star5_rating" data-rate=<?php echo empty($ht['rate'])?0:$ht['rate']; ?>></p>

      <!-- レビュー編集・削除フォーム -->
      <form action="editreview.php" method="post" name="edit_review" onsubmit="return confirm_delete()">
        <input type="hidden" name="id" value="<?php echo rawurlencode($ht['id']); ?>">
        <input type="hidden" name="return_ts" value="<?php echo $ht['return_ts']; ?>">
        <input type="hidden" name="display_flg" value="2">
        <input type="submit" name="sub_edit"  value="編集" onclick="returnform.key.value='編集'" >
        <input type="submit" name="sub_delete"  value="削除" onclick="returnform.key.value='削除'" >
      </form>
      <textarea id="ht_review" rows="5" cols="30" readonly><?php echo htmlspecialchars($ht['review']); ?></textarea>
    <?php  endforeach; ?>
  </div>
</body>