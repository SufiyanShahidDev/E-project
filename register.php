<?php
include 'header.php';
include 'dbconnect.php';

require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/* =========================
   SEND OTP FUNCTION
========================= */
function sendOTP($email, $otp) {
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.example.com'; // change if real SMTP
        $mail->SMTPAuth = true;
        $mail->Username = 'your_email@example.com';
        $mail->Password = 'your_email_password';
        $mail->SMTPSecure = 'ssl';
        $mail->Port = 465;

        $mail->setFrom('your_email@example.com', 'Glamour Salon');
        $mail->addAddress($email);

        $mail->isHTML(true);
        $mail->Subject = 'OTP Verification - Glamour Salon';
        $mail->Body = "Your OTP is: <b>$otp</b>. It will expire in 10 minutes.";

        return $mail->send();

    } catch (Exception $e) {
        return false;
    }
}

/* =========================
   REGISTER LOGIC
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {

    $first_name = $_POST['first_name'];
    $last_name  = $_POST['last_name'];
    $email      = $_POST['email'];
    $password   = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $telephone  = $_POST['telephone'];

    try {

        // Check user already exists
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);

        if ($stmt->rowCount() > 0) {
            $message = "Email already exists!";
            $message_type = "danger";
        } else {

            // OTP generate
            $otp = rand(100000, 999999);
            $expiry = date('Y-m-d H:i:s', strtotime('+10 minutes'));

            // check otp table
            $check = $pdo->prepare("SELECT * FROM user_otp WHERE email = ?");
            $check->execute([$email]);

            if ($check->rowCount() > 0) {

                $update = $pdo->prepare("
                    UPDATE user_otp 
                    SET first_name=?, last_name=?, password=?, telephone=?, otp_code=?, otp_expiry=?
                    WHERE email=?
                ");

                $update->execute([
                    $first_name,
                    $last_name,
                    $password,
                    $telephone,
                    $otp,
                    $expiry,
                    $email
                ]);

            } else {

                $insert = $pdo->prepare("
                    INSERT INTO user_otp 
                    (first_name, last_name, email, password, telephone, otp_code, otp_expiry)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");

                $insert->execute([
                    $first_name,
                    $last_name,
                    $email,
                    $password,
                    $telephone,
                    $otp,
                    $expiry
                ]);
            }

            // Send OTP
            if (sendOTP($email, $otp)) {
                echo "<script>
                        alert('OTP sent to your email');
                        window.location.href='verify.php?email=$email';
                      </script>";
                exit;
            } else {
                $message = "OTP send failed!";
                $message_type = "danger";
            }
        }

    } catch (Exception $e) {
        $message = "Error: " . $e->getMessage();
        $message_type = "danger";
    }
}
?>

<!-- =========================
     HTML FORM
========================= -->

<section class="register-area ptb-90">
<div class="container">
<div class="row justify-content-center">
<div class="col-md-6">

<?php if(isset($message)): ?>
    <div class="alert alert-<?= $message_type ?>">
        <?= $message ?>
    </div>
<?php endif; ?>

<form method="POST">

    <input type="text" name="first_name" placeholder="First Name" required class="form-control"><br>

    <input type="text" name="last_name" placeholder="Last Name" required class="form-control"><br>

    <input type="email" name="email" placeholder="Email" required class="form-control"><br>

    <input type="password" name="password" placeholder="Password" required class="form-control"><br>

    <input type="tel" name="telephone" placeholder="Phone" required class="form-control"><br>

    <button type="submit" name="register" class="btn btn-primary">Register</button>

</form>

</div>
</div>
</div>
</section>

<?php include 'footer.php'; ?>