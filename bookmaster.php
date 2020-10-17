<?php

  if(isset($_POST['isbn']) && $_POST['isbn'] != ""){
    $url = "https://www.googleapis.com/books/v1/volumes?q=ISBM:".$_POST['isbn'];
    $conn = curl_init(); // cURLセッションの初期化
    curl_setopt($conn, CURLOPT_URL, $url); //　取得するURLを指定
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
    $img_data=file_get_contents($smallThumbnail,false,$context);
    $img_dir = './img/books/'.$_POST['isbn'].'.jpg';
    file_put_contents($img_dir,$img_data);
    
    echo $smallThumbnail;
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
</head>

<body>
  <form action="bookmaster.php" method="post" class="isbninfo">
    ISBN CD: <input type="text" name="isbn" maxlength='13' value="<?php echo $_POST['isbn']?>">
    <input class="button" type="submit" value="Get Info">
  </form>
  <form action="bookmaster.php" method="post" class="edit">
    <dl>
      <dt>Title: 
      <dd><input type="text" name="title" value="<?php echo $title; ?>">
      <dt>Image: 
      <dd><img src=<?php echo htmlspecialchars($smallThumbnail); ?>>
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
      <dt>ISBN CD: 
      <dd><input type="text" name="title" readonly value="<?php echo rawurlencode($_POST['isbn']); ?>">
      <dt>Authors:
      <dd><input type="text" name="authors" class="authors" value="<?php echo htmlspecialchars($str_authors); ?>">
      <dt>Publisher:
      <dd><input type="text" name="publisher" class="publisher" value="<?php echo htmlspecialchars($publisher); ?>">
      <dt>PublishedDate:
      <dd><input type="text" name="publisheDate" class="publisheDate" value="<?php echo htmlspecialchars($publishedDate); ?>">
      <dt>Description:
      <dd><textarea id="description" name="description" rows="10" cols="100" ><?php echo htmlspecialchars($description); ?></textarea>
    </dl>
    <input class="button" type="submit" name="update"value="登録">
    <input class="button" type="submit" name="end"value="終了"">
  </form>
</body>