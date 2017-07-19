<?php 
set_time_limit(0);
// require("PHPMailer/class.phpmailer.php");
require("PHPMailer/PHPMailerAutoload.php"); //下载的文件必须放在该文件所在目录
$mail = new PHPMailer(); //建立邮件发送类
$address ="1181796981@qq.com";
$mail->IsSMTP(); // 使用SMTP方式发送
$mail->Host = "smtp.163.com"; // 您的企业邮局域名
$mail->SMTPAuth = true; // 启用SMTP验证功能
$mail->SMTPDebug = 2;
$mail->Username = "18642639193@163.com"; // 邮局用户名(请填写完整的email地址)
$mail->Password = "djk071023"; // 邮局密码
$mail->Port=25;
$mail->From = "18642639193@163.com"; //邮件发送者email地址
$mail->FromName = "djk";
$mail->AddAddress("$address", "a");//收件人地址，可以替换成任何想要接收邮件的email信箱,格式是AddAddress("收件人email","收件人姓名")
//$mail->AddReplyTo("", "");
 
//$mail->AddAttachment("/var/tmp/file.tar.gz"); // 添加附件
//$mail->IsHTML(true); // set email format to HTML //是否使用HTML格式
 
$mail->Subject = "PHPMailer测试邮件"; //邮件标题
$mail->Body = "Hello,这是测试邮件"; //邮件内容
$mail->AltBody = "This is the body in plain text for non-HTML mail clients"; //附加信息，可以省略
 
if(!$mail->Send())
{
echo "邮件发送失败. <p>";
echo "错误原因: " . $mail->ErrorInfo;
exit;
}
 
echo "邮件发送成功";