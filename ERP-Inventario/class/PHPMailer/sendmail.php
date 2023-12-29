<?php  
	$mensaje = '';
	$mensaje .= '<div>
		<div>
			<div style="display:inline-block;">Nombre docente:</div>
			<div style="display:inline-block;">'.$_POST['docente'].'</div>
		</div><br>		
		<div>
			<div style="display:inline-block;">Descripción del trabajo:</div>
			<div style="display:inline-block;">'.$_POST['mensaje'].'</div>
		</div><br>
		<div>
			<div style="display:inline-block;">Fecha de presentación:</div>
			<div style="display:inline-block;">'.$_POST['fecha'].'</div>
		</div>		
	</div>';
	
	//return $mensaje;
	$cabeceras = "MIME-Version: 1.0" . "\r\n";
	$cabeceras .= "Content-type:text/html;charset=UTF-8" . "\r\n"; 
	$cabeceras .= 'From: <proyectos@tucsm.org>' . "\r\n";
	//$cabeceras .= 'Cc: r.arisaca@aqpviaweb.com' . "\r\n";  
	
	require 'PHPMailerAutoload.php'; 
	$mail = new PHPMailer; 
	$mail->isSMTP();
	$mail->SMTPDebug = 2;
	$mail->Debugoutput = 'error_log';
	$mail->Host = "smtp.office365.com";
	$mail->Port = 25;
	$mail->SMTPAuth = true;
	$mail->Username = "70574143@ucsm.edu.pe";
	$mail->Password = "Ucsm4143.";
	$mail->setFrom('70574143@ucsm.edu.pe', 'Proyecto de software'); 
	//$mail->addReplyTo('secretaria@aspecive.org', '');   
	$mail->addAddress($_POST['correo_alumno'], 'JURADO'); 
	$mail->Subject = 'Curso jurado: '.$_POST['asunto'];
	$mail->msgHTML($mensaje);  
	$mail->AltBody = $mensaje;   
	if (!$mail->send()) {
		echo "Mailer Error: " . $mail->ErrorInfo;
	} else {
		echo 1;
	}
?>