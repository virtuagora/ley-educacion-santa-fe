<?php

class AccionCtrl extends Controller {
    
    public function listarAcciones($idDer) {
        $derecho = Derecho::with('contenido.tags')->findOrFail($idDer);
        $derecho = $derecho->contenido;
        
        // Necesito la descripcion y los testimonios (en array)
        
        // TODO
        
        $this->render('lpe/contenido/accion/listarAcciones.twig', [
        'derecho' => $derecho,
        'acciones' => [],
        ]);
    }
    
    public function verCrear($idDer) {
        // Necesito que me pases al menos el id del derecho al menos
        $derecho = Derecho::with('contenido.tags')->findOrFail($idDer);
        $derecho = $derecho->contenido;
        
        $this->render('lpe/contenido/accion/crear.twig', [
        'derecho' => $derecho
        ]);
    }
    
    public function crear($idDer) {
        // yo te mando 2 elementos:
        // descripcion: cuerpo bbCode de la accion
        // testimonios: string de un array de JSONs (stringify)
        
        // TODO
        
        $this->flash('success', 'El derecho se creó exitosamente.');
        $this->redirectTo('listarAcciones', ['idDer' => $derecho->id]);
    }
    
    public function verModificar($idDer, $idAcc) {
        // Necesito que me pases al menos el id del derecho
        // la descripcion y los testimonios (en array)
        
        // TODO
        
        $this->render('lpe/contenido/accion/editar.twig', [
        'derecho' => $derecho
        ]);
    }
    
    public function modificar($idDer, $idAcc) {
        // yo te mando 2 elementos:
        // descripcion: cuerpo bbCode de la accion
        // testimonios: string de un array de JSONs (stringify)
        
        // TODO
        
        $this->flash('success', 'El derecho se creó exitosamente.');
        $this->redirectTo('listarAcciones', ['idDer' => $derecho->id]);
    }
    
    public function eliminar($idAcc) {
        
        // TODO
        
        $this->flash('success', 'El derecho se creó exitosamente.');
        $this->redirectTo('listarAcciones', ['idDer' => $derecho->id]);
    }
}