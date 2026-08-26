<?php

namespace App\Controllers;

use App\Models\Articles;
use App\Utility\Upload;
use App\Utility\Flash;
use \Core\View;

/**
 * Product controller
 */
class Product extends \Core\Controller
{

    /**
     * Affiche la page d'ajout
     * @return void
     */
    public function indexAction()
    {

        if(isset($_POST['submit'])) {

            try {
                $product = $_POST;

                // Validation
                $error = $this->validateProductSubmission($_POST, $_FILES);

                if ($error !== null) {
                    Flash::danger($error);
                    View::renderTemplate('Product/Add.html');
                    die;
                }

                $product['user_id'] = $_SESSION['user']['id'];
                $id = Articles::save($product);

                $pictureError = $_FILES['picture']['error'] ?? UPLOAD_ERR_NO_FILE;

                if ($pictureError !== UPLOAD_ERR_NO_FILE) {
                    $pictureName = Upload::uploadFile($_FILES['picture'], $id);
                    Articles::attachPicture($id, $pictureName);
                }

                header('Location: /product/' . $id);
            } catch (\Exception $e){
                    var_dump($e);
            }
        }

        View::renderTemplate('Product/Add.html');
    }

    /**
     * Valide les données soumises par le formulaire d'ajout de produit.
     *
     * @param array $product Les données de $_POST
     * @param array $files   Les données de $_FILES
     * @return string|null Message d'erreur, ou null si les données sont valides
     */
    protected function validateProductSubmission(array $product, array $files): ?string
    {
        if (empty(trim($product['name'] ?? ''))) {
            return "Le titre est obligatoire !";
        }

        if (empty(trim($product['description'] ?? ''))) {
            return "La description est obligatoire !";
        }

        $pictureError = $files['picture']['error'] ?? UPLOAD_ERR_NO_FILE;

        if ($pictureError !== UPLOAD_ERR_OK && $pictureError !== UPLOAD_ERR_NO_FILE) {
            return $this->pictureErrorMessage($pictureError);
        }

        return null;
    }

    /**
     * Retourne le message d'erreur correspondant à un code d'erreur d'upload PHP.
     *
     * @param int $errorCode
     * @return string
     */
    protected function pictureErrorMessage(int $errorCode): string
    {
        switch ($errorCode) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                return "Le fichier est trop volumineux (" . ini_get('upload_max_filesize') . " maximum).";
            default:
                return "Une erreur est survenue lors de l'envoi du fichier.";
        }
    }

    /**
     * Affiche la page d'un produit
     * @return void
     */
    public function showAction()
    {
        $id = $this->route_params['id'];

        try {
            Articles::addOneView($id);
            $suggestions = Articles::getSuggest();
            $article = Articles::getOne($id);
        } catch(\Exception $e){
            var_dump($e);
        }

        View::renderTemplate('Product/Show.html', [
            'id' => $id,
            'article' => $article[0],
            'suggestions' => $suggestions
        ]);
    }
}
