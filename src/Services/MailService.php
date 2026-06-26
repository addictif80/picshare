<?php

namespace PicShare\Services;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

class MailService
{
    private static function mailer(): PHPMailer
    {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = setting('smtp_host');
        $mail->SMTPAuth   = true;
        $mail->Username   = setting('smtp_user');
        $mail->Password   = setting('smtp_pass');
        $mail->Port       = (int) setting('smtp_port', 587);
        $mail->SMTPSecure = setting('smtp_encryption', 'tls') === 'ssl' ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
        $mail->setFrom(setting('smtp_from_email'), setting('smtp_from_name', APP_NAME));
        $mail->CharSet = 'UTF-8';
        $mail->isHTML(true);
        return $mail;
    }

    public static function send(string $to, string $toName, string $subject, string $htmlBody): bool
    {
        try {
            $mail = self::mailer();
            $mail->addAddress($to, $toName);
            $mail->Subject = $subject;
            $mail->Body    = self::wrapTemplate($htmlBody, $subject);
            $mail->AltBody = strip_tags($htmlBody);
            $mail->send();
            return true;
        } catch (\Exception $e) {
            error_log('MailService error: ' . $e->getMessage());
            return false;
        }
    }

    private static function wrapTemplate(string $content, string $title): string
    {
        $appName = APP_NAME;
        $logo = setting('site_logo') ? '<img src="' . BASE_URL . '/public/uploads/logos/' . setting('site_logo') . '" alt="' . $appName . '" style="height:40px;">' : '<span style="font-size:24px;font-weight:700;color:#6366f1;">' . $appName . '</span>';
        return <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>{$title}</title>
</head>
<body style="margin:0;padding:0;background:#f4f4f8;font-family:'Segoe UI',Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f8;padding:40px 20px;">
  <tr><td align="center">
    <table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;background:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,0.08);">
      <tr><td style="background:linear-gradient(135deg,#6366f1 0%,#8b5cf6 100%);padding:32px 40px;text-align:center;">
        {$logo}
      </td></tr>
      <tr><td style="padding:40px;">
        {$content}
      </td></tr>
      <tr><td style="background:#f8f8fc;padding:24px 40px;text-align:center;color:#9ca3af;font-size:13px;">
        © 2024 {$appName} — Tous droits réservés
      </td></tr>
    </table>
  </td></tr>
</table>
</body>
</html>
HTML;
    }

    public static function sendOtp(string $email, string $name, string $otp): bool
    {
        $appName = APP_NAME;
        $content = <<<HTML
<h2 style="color:#1a1a2e;font-size:24px;margin:0 0 8px;">Votre code de connexion</h2>
<p style="color:#6b7280;margin:0 0 32px;">Bonjour {$name}, utilisez ce code pour vous connecter à {$appName}.</p>
<div style="background:#f4f4f8;border-radius:12px;padding:32px;text-align:center;margin:0 0 32px;">
  <span style="font-size:48px;font-weight:700;letter-spacing:12px;color:#6366f1;">{$otp}</span>
</div>
<p style="color:#9ca3af;font-size:13px;text-align:center;margin:0;">Ce code expire dans <strong>10 minutes</strong>. Ne le partagez pas.</p>
HTML;
        return self::send($email, $name, "[$appName] Votre code de connexion", $content);
    }

    public static function sendInvitation(string $email, string $eventTitle, string $inviterName, string $token): bool
    {
        $appName = APP_NAME;
        $link = BASE_URL . '/invitation/' . $token;
        $content = <<<HTML
<h2 style="color:#1a1a2e;font-size:24px;margin:0 0 8px;">Invitation à co-gérer un album</h2>
<p style="color:#6b7280;margin:0 0 24px;"><strong>{$inviterName}</strong> vous invite à co-gérer l'événement <strong>"{$eventTitle}"</strong> sur {$appName}.</p>
<div style="text-align:center;margin:32px 0;">
  <a href="{$link}" style="background:linear-gradient(135deg,#6366f1,#8b5cf6);color:#fff;text-decoration:none;padding:14px 32px;border-radius:8px;font-weight:600;font-size:16px;display:inline-block;">Accepter l'invitation</a>
</div>
<p style="color:#9ca3af;font-size:13px;text-align:center;">Ce lien expire dans 7 jours.</p>
HTML;
        return self::send($email, $email, "[$appName] Invitation à rejoindre un événement", $content);
    }

    public static function sendEventClosed(string $email, string $name, string $albumTitle, string $albumUrl): bool
    {
        $appName = APP_NAME;
        $content = <<<HTML
<h2 style="color:#1a1a2e;font-size:24px;margin:0 0 8px;">L'événement est terminé !</h2>
<p style="color:#6b7280;margin:0 0 24px;">Bonjour {$name}, l'album <strong>"{$albumTitle}"</strong> est maintenant clôturé. Vous pouvez encore consulter les photos pendant 3 mois.</p>
<div style="text-align:center;margin:32px 0;">
  <a href="{$albumUrl}" style="background:linear-gradient(135deg,#6366f1,#8b5cf6);color:#fff;text-decoration:none;padding:14px 32px;border-radius:8px;font-weight:600;font-size:16px;display:inline-block;">Voir les photos</a>
</div>
<p style="color:#9ca3af;font-size:13px;text-align:center;">Les photos seront supprimées définitivement après 3 mois.</p>
HTML;
        return self::send($email, $name, "[$appName] L'album \"{$albumTitle}\" est terminé", $content);
    }

    public static function sendDownloadReady(string $email, string $name, string $albumTitle, string $downloadUrl): bool
    {
        $appName = APP_NAME;
        $content = <<<HTML
<h2 style="color:#1a1a2e;font-size:24px;margin:0 0 8px;">Votre album est prêt !</h2>
<p style="color:#6b7280;margin:0 0 24px;">Bonjour {$name}, votre paiement pour l'album <strong>"{$albumTitle}"</strong> a bien été reçu. Téléchargez votre archive maintenant.</p>
<div style="text-align:center;margin:32px 0;">
  <a href="{$downloadUrl}" style="background:linear-gradient(135deg,#10b981,#059669);color:#fff;text-decoration:none;padding:14px 32px;border-radius:8px;font-weight:600;font-size:16px;display:inline-block;">Télécharger mon album</a>
</div>
<p style="color:#9ca3af;font-size:13px;text-align:center;">Ce lien est valable 3 mois. Conservez-le précieusement.</p>
HTML;
        return self::send($email, $name, "[$appName] Téléchargez votre album \"{$albumTitle}\"", $content);
    }
}
