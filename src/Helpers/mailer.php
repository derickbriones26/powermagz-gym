<?php

/**
 * Lightweight mail helper. Uses PHP's built-in mail() as a fallback.
 * Swap for PHPMailer / SMTP in production by editing send_mail().
 */
function send_mail(string $to, string $subject, string $bodyHtml): bool
{
    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= 'From: ' . APP_NAME . ' <' . MAIL_FROM . ">\r\n";

    // Local dev: log instead of sending
    if (APP_ENV === 'local') {
        error_log("[MAIL] To: {$to} | Subject: {$subject}\n{$bodyHtml}");
        return true;
    }

    return @mail($to, $subject, $bodyHtml, $headers);
}

function send_otp(string $email, string $code): bool
{
    $body = '<p>Your PowerMagz verification code is:</p>'
        . '<h2 style="letter-spacing:6px;font-family:sans-serif">' . e($code) . '</h2>'
        . '<p>It expires in 10 minutes.</p>';
    return send_mail($email, 'Verify your PowerMagz account', $body);
}

function send_password_reset(string $email, string $resetUrl): bool
{
    $body = '<p>You requested a password reset. Click the link below:</p>'
        . '<p><a href="' . e($resetUrl) . '">' . e($resetUrl) . '</a></p>'
        . '<p>This link expires in 1 hour. If you did not request this, ignore this email.</p>';
    return send_mail($email, 'Reset your PowerMagz password', $body);
}
