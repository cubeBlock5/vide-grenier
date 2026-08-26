<?php

namespace App\Controllers;

use App\Models\Articles;
use App\Utility\Flash;
use App\Utility\Mailer;
use \Core\View;

/**
 * Contact controller
 */
class Contact extends \Core\Controller
{

    /**
     * Affiche et traite le formulaire de contact pour un article
     */
    public function indexAction()
    {
        $id = $this->route_params['id'];
        $articles = Articles::getOne($id);

        if (empty($articles)) {
            header('Location: /');
            die;
        }

        $article = $articles[0];

        if (isset($_POST['submit'])) {
            $error = $this->validateContactSubmission($_POST);

            if ($error !== null) {
                Flash::danger($error);
            } else {
                $email = trim($_POST['email'] ?? '');
                $message = $this->buildContactMessage($article, $_POST);

                Mailer::send($article['email'], $email, $message['subject'], $message['body']);

                Flash::success("Votre message a bien été envoyé à {$article['username']}.");
                header('Location: /product/' . $id);
                die;
            }
        }

        View::renderTemplate('Contact/index.html', [
            'article' => $article
        ]);
    }

    /**
     * Valide les données soumises par le formulaire de contact.
     *
     * @param array $data
     * @return string|null Message d'erreur, ou null si les données sont valides
     */
    protected function validateContactSubmission(array $data): ?string
    {
        $name = trim($data['name'] ?? '');
        $email = trim($data['email'] ?? '');
        $message = trim($data['message'] ?? '');

        if (empty($name) || empty($message)) {
            return "Merci de remplir tous les champs.";
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return "L'adresse email n'est pas valide.";
        }

        return null;
    }

    /**
     * Construit le sujet et le corps du message à envoyer au vendeur.
     *
     * @param array $article
     * @param array $data
     * @return array{subject: string, body: string}
     */
    protected function buildContactMessage(array $article, array $data): array
    {
        $name = trim($data['name'] ?? '');
        $email = trim($data['email'] ?? '');
        $message = trim($data['message'] ?? '');

        $subject = "Nouveau message concernant votre annonce \"{$article['name']}\"";
        $body = "Message de {$name} ({$email}) :\n\n{$message}";

        return [
            'subject' => $subject,
            'body' => $body
        ];
    }
}
