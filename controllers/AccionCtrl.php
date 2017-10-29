<?php use Augusthur\Validation as Validate;

class AccionCtrl extends Controller {
    
    public function listarAcciones($idDer) {
        $vdt = new Validate\QuickValidator([$this, 'notFound']);
        $vdt->test($idDer, new Validate\Rule\NumNatural());
        $derecho = Derecho::with(['contenido', 'resultados'])->findOrFail($idDer);
        $contenido = $derecho->contenido;
        $datosDer = array_merge($contenido->toArray(), $derecho->toArray());

        //$derecho = Derecho::with('contenido.tags')->findOrFail($idDer);
        //$derecho = $derecho->contenido;
        
        // Necesito la descripcion y los testimonios (en array)
        
        // TODO
        
        $this->render('lpe/contenido/accion/listarAcciones.twig', [
            'derecho' => $datosDer,
        ]);
    }
    
    public function verCrear($idDer) {
        // Necesito que me pases al menos el id del derecho al menos
        $derecho = Derecho::with('contenido')->findOrFail($idDer);
        $datosDer = array_merge($derecho->contenido->toArray(), $derecho->toArray());
        //$derecho = $derecho->contenido;
        
        $this->render('lpe/contenido/accion/crear.twig', [
            'derecho' => $datosDer
        ]);
    }
    
    public function crear($idDer) {
        $req = $this->request;
        $vdt = new Validate\Validator();
        $vdt->addRule('derecho', new Validate\Rule\NumNatural())
            ->addRule('descripcion', new Validate\Rule\MaxLength(2048));
        $data = array_merge($req->post(), ['derecho' => $idDer]);
        if (!$vdt->validate($data)) {
            throw new TurnbackException($vdt->getErrors());
        }
        $resultado = new Resultado;
        $resultado->descripcion = $vdt->getData('descripcion');
        $resultado->testimonios = json_decode($vdt->getData('testimonios'), true);
        $resultado->derecho_id = $vdt->getData('derecho');
        $resultado->save();
        
        $this->flash('success', 'La acción creó exitosamente.');
        $this->redirectTo('shwListarAcciones', ['idDer' => $idDer]);
    }
    
    public function verModificar($idAcc) {
        // Necesito que me pases al menos el id del derecho
        // la descripcion y los testimonios (en array)

        $vdt = new Validate\QuickValidator([$this, 'notFound']);
        $vdt->test($idAcc, new Validate\Rule\NumNatural());
        $accion = Resultado::with('derecho.contenido')->findOrFail($idAcc);
        $derecho = $accion->derecho;
        $datosDer = array_merge($derecho->contenido->toArray(), $derecho->toArray());
        
        $this->render('lpe/contenido/accion/editar.twig', [
            'derecho' => $datosDer,
            'accion' => $accion->toArray(),
        ]);
    }
    
    public function modificar($idAcc) {
        // yo te mando 2 elementos:
        // descripcion: cuerpo bbCode de la accion
        // testimonios: string de un array de JSONs (stringify)
        
        $vdt = new Validate\QuickValidator([$this, 'notFound']);
        $vdt->test($idAcc, new Validate\Rule\NumNatural());
        $resultado = Resultado::findOrFail($idAcc);

        $req = $this->request;
        $vdt = new Validate\Validator();
        $vdt->addRule('descripcion', new Validate\Rule\MaxLength(2048));
        if (!$vdt->validate($req->post())) {
            throw new TurnbackException($vdt->getErrors());
        }

        $resultado->descripcion = $vdt->getData('descripcion');
        $resultado->testimonios = json_decode($vdt->getData('testimonios'), true);
        $resultado->save();
        
        $idDer = $resultado->derecho->id;


        $this->flash('success', 'La acción se actualizó exitosamente.');
        $this->redirectTo('shwListarAcciones', ['idDer' => $idDer]);
    }
    
    public function eliminar($idAcc) {
        
        $vdt = new Validate\QuickValidator([$this, 'notFound']);
        $vdt->test($idAcc, new Validate\Rule\NumNatural());
        $resultado = Resultado::findOrFail($idAcc);
        $idDer = $resultado->derecho->id;

        $resultado->delete();
        
        $this->flash('success', 'La acción se eliminó exitosamente.');
        $this->redirectTo('shwListarAcciones', ['idDer' => $idDer]);
    }
}