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
                if (empty(trim($product['name'] ?? ''))) {
                    Flash::danger("Le titre est obligatoire !");
                    View::renderTemplate('Product/Add.html');
                    die;
                }

                if (empty(trim($product['description'] ?? ''))) {
                    Flash::danger("La description est obligatoire !");
                    View::renderTemplate('Product/Add.html');
                    die;
                }

                $pictureError = $_FILES['picture']['error'] ?? UPLOAD_ERR_NO_FILE;

                if ($pictureError !== UPLOAD_ERR_OK) {
                    switch ($pictureError) {
                        case UPLOAD_ERR_NO_FILE:
                            Flash::danger("Veuillez ajouter une photo !");
                            break;
                        case UPLOAD_ERR_INI_SIZE:
                        case UPLOAD_ERR_FORM_SIZE:
                            Flash::danger("Le fichier est trop volumineux (" . ini_get('upload_max_filesize') . " maximum).");
                            break;
                        default:
                            Flash::danger("Une erreur est survenue lors de l'envoi du fichier.");
                    }
                    View::renderTemplate('Product/Add.html');
                    die;
                }

                $product['user_id'] = $_SESSION['user']['id'];
                $id = Articles::save($product);

                $pictureName = Upload::uploadFile($_FILES['picture'], $id);

                Articles::attachPicture($id, $pictureName);

                header('Location: /product/' . $id);
            } catch (\Exception $e){
                    var_dump($e);
            }
        }

        View::renderTemplate('Product/Add.html');
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
