<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'phpmailer/Exception.php';
require 'phpmailer/PHPMailer.php';
require 'phpmailer/SMTP.php';

$conn = new mysqli("localhost", "root", "", "library");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];

    // Check if email exists
    $result = $conn->query("SELECT * FROM students WHERE email='$email'");
    if ($result->num_rows > 0) {
        $token = bin2hex(random_bytes(50)); // unique reset token
        $expiry = date("Y-m-d H:i:s", strtotime("+30 minutes"));

        // Save token in DB (make sure your students table has reset_token and reset_expiry columns!)
        $conn->query("UPDATE students SET reset_token='$token', reset_expiry='$expiry' WHERE email='$email'");

        // Send email
        $mail = new PHPMailer(true);
        try {
            // Gmail SMTP settings
            $mail->isSMTP();
            $mail->Host = "smtp.gmail.com";
            $mail->SMTPAuth = true;
            $mail->Username = "boiburna17@gmail.com"; // ✅ your Gmail
            $mail->Password = "cptc oqkx ozwp uiyt";   // ✅ your Gmail App Password
            $mail->SMTPSecure = "tls";
            $mail->Port = 587;

            // Sender & recipient
            $mail->setFrom("boiburna17@gmail.com", "Library System");
            $mail->addAddress($email);

            // Content
            $mail->isHTML(true);
            $mail->Subject = "Password Reset Request";
            $mail->Body = "Click the link below to reset your password:<br>
            <a href='http://localhost/library/reset_password.php?token=$token'>Reset Password</a>";

            $mail->send();
            echo "✅ Reset link sent to your email!";
        } catch (Exception $e) {
            echo "❌ Email could not be sent. Error: {$mail->ErrorInfo}";
        }
    } else {
        echo "❌ Email not found!";
    }
}
?>
