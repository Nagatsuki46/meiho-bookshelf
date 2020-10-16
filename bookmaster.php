<?php

  if(isset($_POST['isbn']) && $_POST['isbn'] != ""){
    $url = "https://www.googleapis.com/books/v1/volumes?q=ISBM:".$_POST['isbn'];
    $conn = curl_init(); // cURLセッションの初期化
    curl_setopt($conn, CURLOPT_URL, $url); //　取得するURLを指定
    curl_setopt($conn, CURLOPT_RETURNTRANSFER, true); // 実行結果を文字列で返す。
    $res =  curl_exec($conn);
    //var_dump($res);
    curl_close($conn); //セッションの終了

    $arr = json_decode($res,true);
  }else{
    $arr = "";
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
      <dt>Image: 
      <dd><img src=<?php echo htmlspecialchars($arr['items'][0]['volumeInfo']['imageLinks']['smallThumbnail']); ?>>
      <input type="hidden" name="thumbnail_url" value="<?php echo htmlspecialchars($arr['items'][0]['volumeInfo']['imageLinks']['smallThumbnail']); ?>">
      <?php
        $authors ="";
        foreach($arr['items'][0]['volumeInfo']['authors'] as $ats):
          if ($authors !=""){
            $authors = $authors.",";
          }
          $authors = $authors.$ats;
        endforeach;  
      ?>
      <dt>Title: 
      <dd><input type="text" name="title" value="<?php echo $arr['items'][0]['volumeInfo']['title']; ?>">
      <dt>Authors:
      <dd><input type="text" name="authors" class="authors" value="<?php echo $authors; ?>">
      <dt>Publisher:
      <dd><input type="text" name="publisher" class="publisher" value="<?php echo $arr['items'][0]['volumeInfo']['publisher']; ?>">
      <dt>PublishedDate:
      <dd><input type="text" name="publisheDate" class="publisheDate" value="<?php echo $arr['items'][0]['volumeInfo']['publishedDate']; ?>">
      <dt>Description:
      <dd><textarea id="description" name="description" rows="10" cols="100" ><?php echo $arr['items'][0]['volumeInfo']['description']; ?></textarea>
    </dl>
    <input class="button" type="submit" name="update"value="登録">
    <input class="button" type="submit" name="end"value="終了"">
  </form>
</body>
