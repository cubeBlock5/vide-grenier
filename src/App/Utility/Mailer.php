<?php

namespace App\Utility;

/**
 * Mailer: envoie les emails du site.
 *
 * Par défaut (MAIL_DRIVER=log ou non défini), les emails ne sont pas
 * réellement envoyés : ils sont écrits dans logs/mails.log. Cela permet de
 * faire fonctionner et de tester le formulaire de contact sans serveur SMTP.
 * Sur un environnement disposant d'un MTA local, définir MAIL_DRIVER=mail
 * pour utiliser la fonction mail() de PHP.
 */
class Mailer
{
    public static function send(string $to, string $replyTo, string $subject, string $body): bool
    {
        if (getenv('MAIL_DRIVER') === 'mail') {
            $headers = "Reply-To: {$replyTo}\r\nContent-Type: text/plain; charset=UTF-8";
            return mail($to, $subject, $body, $headers);
        }

        return self::log($to, $replyTo, $subject, $body);
    }

    private static function log(string $to, string $replyTo, string $subject, string $body): bool
    {
        $logFile = dirname(__DIR__, 2) . '/logs/mails.log';

        $entry = "==== " . date('Y-m-d H:i:s') . " ====\n";
        $entry .= "To: {$to}\n";
        $entry .= "Reply-To: {$replyTo}\n";
        $entry .= "Subject: {$subject}\n\n";
        $entry .= "{$body}\n\n";

        return file_put_contents($logFile, $entry, FILE_APPEND | LOCK_EX) !== false;
    }
}
