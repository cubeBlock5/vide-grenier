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
            $name = trim($_POST['name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $message = trim($_POST['message'] ?? '');

            if (empty($name) || empty($message)) {
                Flash::danger("Merci de remplir tous les champs.");
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                Flash::danger("L'adresse email n'est pas valide.");
            } else {
                $subject = "Nouveau message concernant votre annonce \"{$article['name']}\"";
                $body = "Message de {$name} ({$email}) :\n\n{$message}";

                Mailer::send($article['email'], $email, $subject, $body);

                Flash::success("Votre message a bien été envoyé à {$article['username']}.");
                header('Location: /product/' . $id);
                die;
            }
        }

        View::renderTemplate('Contact/index.html', [
            'article' => $article
        ]);
    }
}
