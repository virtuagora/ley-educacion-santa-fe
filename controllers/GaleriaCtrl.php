<?php use Augusthur\Validation as Validate;

class GaleriaCtrl extends Controller {

    public function verGaleria() {
        $this->render('lpe/contenido/galeria/listar.twig');
    }

    public function verAlbum($idAlb) {
        //TODO
        $this->render('lpe/contenido/galeria/verAlbum.twig', array(
        // nadaaa
        ));
    }


     public function crearAlbum() {
        //TODO
         //El retorno que lo lleve al album
    }

     public function eliminarAlbum($idAlb) {
        //TODO
         //El retorno que lo lleve a la galeria

    }

     public function subirFoto() {
        //TODO
         //El retorno que lo lleve al album

    }

     public function eliminarFoto($idFot) {
        //TODO
         //El retorno que lo lleve al album
    }
}
