<?php

  //セッションの時間を設定する
  session_cache_expire(30);
  session_start();
  if(!isset($_SESSION['item_id'])){
    $_SESSION['item_id'] = 0;
    $_SESSION['src_isbn'] = "";
  }

  $url = parse_url(getenv('DATABASE_URL'));
  $dsn = sprintf('pgsql:host=%s;dbname=%s',$url['host'],substr($url['path'],1));
  $dbh = new PDO(
          $dsn,
          $url['user'],
          $url['pass'],
          [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
  );

  //書籍情報の削除
  if (isset($_POST['sub_delete'])){
    $sth = $dbh->prepare(
      'DELETE FROM bookshelf'
      . ' WHERE id= :id'
      );
    $sth->execute([
      'id' => $_POST['id'],
      ]);
    
    //POSTの情報を引き継ぐ形でリダイレクト(307)
    header('Location: index.php',true,307);
    exit;
  }

  //書籍情報の登録
  if (isset($_POST['update'])){

    //更新時にも候補IDは0に戻しておく
    $_SESSION['item_id']=0;

    $sth = $dbh->prepare(
      'SELECT count(isbn) as cnt'
      . ' FROM bookshelf'
      . ' WHERE id<> :id'
      . ' AND isbn= :isbn');
    $sth->execute([
      'id' => $_POST['id'],
      'isbn' => $_POST['isbncd']
      ]);
    $exists = $sth->fetch(PDO::FETCH_ASSOC);

    if($exists['cnt']>0){
      $error ="既に同じ書籍（ISBN CD）が登録されています。";
    }else{
      $sth = $dbh->prepare(
        "UPDATE bookshelf"
        . ' SET title = :title,'
        . ' isbn = :isbn,'
        . ' author = :author,'
        . ' publisher = :publisher,'
        . ' publishe_date = :publishe_date,'
        . ' description = :description,'
        . ' thumbnail_url = :thumbnail_url,'
        . ' cover_image = :cover_image'
        . ' WHERE id = :id'
      );

      //画像データを格納する為にbindValue方式に変更
      $sth->bindValue(':id',$_POST['id']);
      $sth->bindValue(':title',$_POST['title']);
      $sth->bindValue(':isbn',$_POST['isbncd']);
      $sth->bindValue(':author',$_POST['author']);
      $sth->bindValue(':publisher',$_POST['publisher']);
      $sth->bindValue(':publishe_date',$_POST['publishe_date']);
      $sth->bindValue(':description',$_POST['description']);
      $sth->bindValue(':thumbnail_url',$_POST['thumbnail_url']);
      $sth->bindValue(':cover_image',$_SESSION['cover_image'],PDO::PARAM_LOB);
      $sth->execute();
      header('Location: ./index.php');
      exit;
    }
  }

  if(isset($_POST['isbn'])){
    if($_POST['isbn'] != ""){
    
      $img_url = "https://www.googleapis.com/books/v1/volumes?q=ISBM:".$_POST['isbn'];
      $conn = curl_init(); // cURLセッションの初期化
      curl_setopt($conn, CURLOPT_URL, $img_url); //　取得するURLを指定
      curl_setopt($conn, CURLOPT_RETURNTRANSFER, true); // 実行結果を文字列で返す。
      $res = curl_exec($conn);
      $error = curl_error($conn);
      //var_dump($res);
      curl_close($conn); //セッションの終了

      $arr = json_decode($res,true);
      echo $error;

      //前回検索したisbnと異なるisbnの場合は、候補の配列IDを0にリセットする
      if($_SESSION['src_isbn'] !== $_POST['isbn']){
        $_SESSION['item_id']=0;
        $_SESSION['src_isbn'] = $_POST['isbn'];
      }

      //前の候補、次の候補ボタンを押されたら参照する配列のIDを増減させる
      if(isset($_POST['pre_info']) && $_SESSION['item_id'] > 0){
        --$_SESSION['item_id'];
      }elseif(isset($_POST['next_info']) && count($arr)>$_SESSION['item_id']+1){
        ++$_SESSION['item_id'];
      }

      $authors  = $arr['items'][$_SESSION['item_id']]['volumeInfo']['authors'];
      $title = $arr['items'][$_SESSION['item_id']]['volumeInfo']['title'];
      $publisher = $arr['items'][$_SESSION['item_id']]['volumeInfo']['publisher'];
      $publishedDate = $arr['items'][$_SESSION['item_id']]['volumeInfo']['publishedDate'];
      $description = $arr['items'][$_SESSION['item_id']]['volumeInfo']['description'];
      $smallThumbnail = $arr['items'][$_SESSION['item_id']]['volumeInfo']['imageLinks']['smallThumbnail'];
      $industrys = $arr['items'][$_SESSION['item_id']]['volumeInfo']['industryIdentifiers'];

      foreach ($industrys as $id => $ind){
        if($ind['type']=="ISBN_13"){
          $isbn =  $ind['identifier'];
        }
      }

      if($isbn==""){
        $isbn = $_POST['isbn'];
      }

      $str_authors ="";
      if ($authors != "") {
        foreach($authors as $ats):
          if ($str_authors !=""){
            $str_authors = $str_authors.",";
          }
          $str_authors = $str_authors.$ats;
        endforeach;
      }

      $context = stream_context_create(array(
        'http' => array('ignore_errors' => true)
      ));
      $cover_image = file_get_contents($smallThumbnail,false,$context);
      $img_src = 'data:images/jpeg;base64,'.base64_encode($cover_image);

    }else{
      $smallThumbnail = "";
      $authors  = "";
      $title = "";
      $publisher = "";
      $publishedDate = "";
      $description = "";
      $img_src = "";
      $cover_image = "";
    }
  }elseif(isset($_POST['id'])){
    $sth = $dbh->prepare(
      'SELECT * FROM bookshelf WHERE id= :id');
    $sth->execute(['id' => $_POST['id']]);
    $origin = $sth->fetch(PDO::FETCH_ASSOC);

    $isbn = $origin['isbn'];
    $str_authors  = $origin['author'];
    $title = $origin['title'];
    $publisher = $origin['publisher'];
    $publishedDate = $origin['publishe_date'];
    $description = $origin['description'];
    $smallThumbnail = $origin['thumbnail_url'];
    $cover_image = stream_get_contents($origin['cover_image']);
    $img_src = 'data:images/jpeg;base64,'.base64_encode($cover_image);
  }
  $_SESSION['cover_image'] = $cover_image;
 ?>

<!DOCTYPE html>
<head>
  <title>書籍情報修正画面</title>
  <link rel="stylesheet" href="../css/bookadd.css">
</head>

<body>
  <p class="error"><?php echo $error ?></p>
  <p>書籍情報の修正画面です。修正内容を入力して更新ボタンを押してください。</p>
  <form action="bookedit.php" method="post" class="form_search">
    ISBN CD: <input type="text" name="isbn" maxlength='13' value="<?php echo $_POST['isbn']?>">
    <input class="button" type="submit" value="Get Info">
    <?php if(isset($_POST['isbn']) && $_POST['isbn'] != "" && count($arr)>1): ?>
      <input class="button" type="submit" name="pre_info" value="前の候補">
      <input class="button" type="submit" name="next_info" value="次の候補">
    <?php endif; ?> 
    <input type="hidden" name="id" value="<?php echo $_POST['id'];?>">
  </form>

  <hr class="hr01">

  <form action="bookedit.php" method="post" class="edit">
    <dl>
      <dt class="dt_id">ID: <?php echo $_POST['id']; ?>
      <input type="hidden" name="id" value="<?php echo $_POST['id']; ?>">
      <dt class="dt_details">Title: 
      <dd><input type="text" name="title" class="long_text" value="<?php echo $title; ?>" required>
      <dt class="dt_details">Cover image: 
      <!-- サムネイルのAPIではなく、イメージのバイナリから表示にする -->
      <dd><img class="cover_image" src=<?php echo $img_src; ?>>
      <input type="hidden" name="thumbnail_url" value="<?php echo htmlspecialchars($smallThumbnail); ?>">
      <dt class="dt_details">ISBN CD: 
      <dd><input type="text" name="isbncd" value="<?php echo rawurlencode($isbn); ?>" required>
      <dt class="dt_details">Authors:
      <dd><input type="text" name="author" class="long_text" value="<?php echo htmlspecialchars($str_authors); ?>">
      <dt class="dt_details">Publisher:
      <dd><input type="text" name="publisher" class="long_text" value="<?php echo htmlspecialchars($publisher); ?>">
      <dt class="dt_details">PublishedDate:
      <dd><input type="text" name="publishe_date" value="<?php echo htmlspecialchars($publishedDate); ?>">
      <dt class="dt_details">Description:
      <dd><textarea id="description" name="description" rows="5" cols="100" ><?php echo htmlspecialchars($description); ?></textarea>
    </dl>
    <input class="add_button" type="submit" name="update" value="更新">
    <input class="general_button" type="button" onclick="location.href='index.php'" value="キャンセル">
    <input type="hidden" name="cover_image"" value="<?php echo htmlspecialchars($cover_image); ?>">
  </form>
</body>