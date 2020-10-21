<?php

  $url = parse_url(getenv('DATABASE_URL'));
  $dsn = sprintf('pgsql:host=%s;dbname=%s',$url['host'],substr($url['path'],1));
  $dbh = new PDO(
          $dsn,
          $url['user'],
          $url['pass'],
          [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
  );

  //終了ボタンが押された時
  if (isset($_POST['end'])){

  }

  //書籍情報の登録
  if (isset($_POST['update'])){

    $context = stream_context_create(array(
      'http' => array('ignore_errors' => true)
    ));
    $img_cover = file_get_contents($_POST['thumbnail_url'],false,$context);
    $sth = $dbh->prepare(
      "INSERT INTO bookshelf"
      . ' (id,title,isbn,author,publisher,publishe_date,'
      . ' entry_date,description,thumbnail_url,cover_image)'
      . ' VALUES('
      . ' :id,:title,:isbn,:author,:publisher,:publishe_date,'
      . ' :entry_date,:description,:thumbnail_url,:cover_image)');
    
    //画像データを格納する為にbindValue方式に変更
    $sth->bindValue(':id',$_POST['next_id']);
    $sth->bindValue(':title',$_POST['title']);
    $sth->bindValue(':isbn',$_POST['isbncd']);
    $sth->bindValue(':author',$_POST['author']);
    $sth->bindValue(':publisher',$_POST['publisher']);
    $sth->bindValue(':publishe_date',$_POST['publishe_date']);
    $sth->bindValue(':entry_date',date("Y-m-d"));
    $sth->bindValue(':description',$_POST['description']);
    $sth->bindValue(':thumbnail_url',$_POST['thumbnail_url']);
    $sth->bindValue(':cover_image',$img_cover,PDO::PARAM_LOB);
    $sth->execute();
    header('Location: ./bookadd.php');
    exit;
  }

  //次に登録するIDを取得
  $sth = $dbh->query(
    'SELECT MAX(id) +1 AS next_id FROM bookshelf'
  );
  $next_id = $sth->fetch(PDO::FETCH_ASSOC);
  if(empty($next_id['next_id'])){$next_id['next_id'] =1;}

  if(isset($_POST['isbn']) && $_POST['isbn'] != ""){
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

    $smallThumbnail = $arr['items'][0]['volumeInfo']['imageLinks']['smallThumbnail'];
    $authors  = $arr['items'][0]['volumeInfo']['authors'];
    $title = $arr['items'][0]['volumeInfo']['title'];
    $publisher = $arr['items'][0]['volumeInfo']['publisher'];
    $publishedDate = $arr['items'][0]['volumeInfo']['publishedDate'];
    $description = $arr['items'][0]['volumeInfo']['description'];

    $context = stream_context_create(array(
      'http' => array('ignore_errors' => true)
    ));
    $img_cover = file_get_contents($smallThumbnail,false,$context);
    $img_src = 'data:images/jpeg;base64,'.base64_encode($img_cover);

    //$img_dir = './img/books/'.$_POST['isbn'].'.jpg';
    //file_put_contents($img_dir,$img_data);
    //echo $smallThumbnail;
  }else{
    $_POST['isbn'] ="";
    $smallThumbnail = "";
    $authors  = "";
    $title = "";
    $publisher = "";
    $publishedDate = "";
    $description = "";
  }
 ?>

<!DOCTYPE html>
<head>
  <title>書籍情報取得画面</title>
  <link rel="stylesheet" href="../css/bookadd.css">
</head>

<body>
  <p class="error"><?php echo $error ?></p>
  <p>書籍情報の追加登録画面です。ISBN CDを入力して情報を取得後、登録ボタンを押してください。</p>
  <form action="bookadd.php" method="post" class="form_search">
    ISBN CD: <input type="text" name="isbn" maxlength='13' value="<?php echo $_POST['isbn']?>">
    <input class="button" type="submit" value="Get Info">
  </form>

  <hr class="hr01">

  <form action="bookadd.php" method="post" class="edit">
    <dl>
      <dt class="dt_id">ID: <?php echo $next_id['next_id']; ?>
      <input type="hidden" name="next_id" value="<?php echo $next_id['next_id']; ?>">
      <dt class="dt_details">Title: 
      <dd><input type="text" name="title" value="<?php echo $title; ?>">
      <dt class="dt_details">Cover image: 
      <!-- サムネイルのAPIではなく、イメージのバイナリから表示にする -->
      <!-- <dd><img src=<?php echo htmlspecialchars($smallThumbnail); ?>> -->
      <dd><img class="cover_image" src=<?php echo $img_src; ?>>
      <input type="hidden" name="thumbnail_url" value="<?php echo htmlspecialchars($smallThumbnail); ?>">
      <?php
        $str_authors ="";
        if ($authors != "") {
          foreach($authors as $ats):
            if ($str_authors !=""){
              $str_authors = $str_authors.",";
            }
            $str_authors = $str_authors.$ats;
          endforeach;
        }
      ?>
      <dt class="dt_details">ISBN CD: 
      <dd><input type="text" name="isbncd" value="<?php echo rawurlencode($_POST['isbn']); ?>">
      <dt class="dt_details">Authors:
      <dd><input type="text" name="authors" class="authors" value="<?php echo htmlspecialchars($str_authors); ?>">
      <dt class="dt_details">Publisher:
      <dd><input type="text" name="publisher" class="publisher" value="<?php echo htmlspecialchars($publisher); ?>">
      <dt class="dt_details">PublishedDate:
      <dd><input type="text" name="publishe_Date" class="publisheDate" value="<?php echo htmlspecialchars($publishedDate); ?>">
      <dt class="dt_details">Description:
      <dd><textarea id="description" name="description" rows="5" cols="100" ><?php echo htmlspecialchars($description); ?></textarea>
    </dl>
    <input class="add_button" type="submit" name="update" value="登録">
    <input class="general_button" type="button" onclick="location.href='index.php'" value="キャンセル">
  </form>
</body>