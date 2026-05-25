<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require_once __DIR__ . '/vendor/autoload.php';
$mail = new PHPMailer(true);
try
{
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'pavankumarchinnaparapu123@gmail.com';
    $mail->Password = 'XXXX XXXX XXXX XXXX';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;
    $mail->setFrom('pavankumarchinnaparapu123@gmail.com', 'Melodix');
}
catch(Exception $e)
{
    echo $e->getMessage();
}
?>