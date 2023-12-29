<?php
   session_start();
   $lcSource = $_REQUEST['IS'];
   $loImagen = imagecreate(60, 15);
   $loColor = imagecolorallocate($loImagen, 255, 255, 255);
   $loTexto = imagecolorallocate($loImagen, 100, 100, 100);
   $r1 = rand(1, 9);
   $r2 = rand(1, 9);
   $r = rand(1, 3);
   if ($r == 1) {
      $lcTexto = $r1.' + '.$r2;
      $r3 = $r1 + $r2;
   } elseif ($r == 2) {
      $lcTexto = $r1.' - '.$r2;
      $r3 = $r1 - $r2;
   } elseif ($r == 3) {
      $lcTexto = $r1.' x '.$r2;
      $r3 = $r1 * $r2;
   }
   if($lcSource === 'A'){
      $_SESSION['A']['pcCaptcha'] = $r3;
   } else {
      $_SESSION['D']['pcCaptcha'] = $r3;
   }
   ImageFill($loImagen, 50, 0, $loColor);
   imagestring($loImagen, 80, 0, 0, $lcTexto, $loTexto);
   header('Content-type: image/png');
   imagepng($loImagen);
?>
