<?php use Augusthur\Validation as Validate;

class GaleriaCtrl extends Controller {

    public function verGaleria() {
        $this->render('lpe/contenido/galeria/listar.twig');
    }

    public function verAlbum() {
        //TODO
        $this->render('lpe/contenido/galeria/verAlbum.twig', array(
        // nadaaa
        ));
    }

}
