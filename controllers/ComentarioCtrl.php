<?php use Augusthur\Validation as Validate;

class ComentarioCtrl extends RMRController {

    protected $mediaTypes = ['json'];
    protected $properties = ['id', 'comentable_type', 'comentable_id', 'votos', 'created_at', 'updated_at', 'autor_id'];

    public function queryModel($meth, $repr) {
        return Comentario::query();
    }

    public function comentar($tipoRaiz, $idRaiz) {
        $vdt = new Validate\Validator();
        $vdt->addRule('idRaiz', new Validate\Rule\NumNatural())
            ->addRule('tipoRaiz', new Validate\Rule\InArray(['Seccion', 'Comentario', 'Novedad', 'Evento']))
            ->addRule('cuerpo', new Validate\Rule\MinLength(4))
            ->addRule('cuerpo', new Validate\Rule\MaxLength(2048))
            ->addRule('seccion', new Validate\Rule\NumNatural())
            ->addFilter('tipoRaiz', 'ucfirst');
        $req = $this->request;
        $data = array_merge($req->post(), ['idRaiz' => $idRaiz, 'tipoRaiz' => $tipoRaiz]);
        if (!$vdt->validate($data)) {
            throw new TurnbackException($vdt->getErrors());
        }
        $comentable = call_user_func($vdt->getData('tipoRaiz').'::findOrFail', $vdt->getData('idRaiz'));
        if ($vdt->getData('tipoRaiz') == 'Comentario' && $comentable->comentable_type == 'Comentario') {
            throw new TurnbackException('No puede responderse una respuesta.');
        }
        $autor = $this->session->getUser();
        $comentario = new Comentario;
        $comentario->cuerpo = $vdt->getData('cuerpo');
        $comentario->autor()->associate($autor);
        $comentario->comentable()->associate($comentable);
        $comentario->save();
        $raiz = $comentable->raiz;
        $raiz->contenido()->increment('puntos', 3);
        $autor->increment('puntos', 5);
        $log = UserlogCtrl::createLog('newComenta', $autor->id, $raiz);
        NotificacionCtrl::createNotif($raiz->contenido->autor_id, $log);
        $this->flash('success', 'Su comentario fue enviado exitosamente.');

        $this->redirect( preg_replace('/\?.*/', '', $req->getReferrer()) . '?seccion=' . $vdt->getData('seccion') . '&comentario=' . $comentario->id );
    }

    public function votar($idCom) {
        $vdt = new Validate\Validator();
        $vdt->addRule('idCom', new Validate\Rule\NumNatural())
            ->addRule('valor', new Validate\Rule\InArray(array(-1, 1)))
            ->addRule('seccion', new Validate\Rule\NumNatural());
        $req = $this->request;
        $data = array_merge(array('idCom' => $idCom), $req->post());
        if (!$vdt->validate($data)) {
            throw new TurnbackException($vdt->getErrors());
        }
        $usuario = $this->session->getUser();
        $comentario = Comentario::findOrFail($idCom);
        $voto = VotoComentario::firstOrNew(array('comentario_id' => $comentario->id,
                                                 'usuario_id' => $usuario->id));
        if (!$voto->exists) {
            $voto->valor = $vdt->getData('valor');
            $voto->save();
            $comentario->increment('votos', $voto->valor);
            $comentario->autor()->increment('puntos', $voto->valor);
        } else {
            throw new TurnbackException('No puede votar dos veces el mismo comentario.');
        }
        $this->flash('success', 'Su voto fue registrado exitosamente.');
        $this->redirect( preg_replace('/\?.*/', '', $req->getReferrer()) . '?seccion=' . $vdt->getData('seccion') . '&comentario=' . $idCom);
    }

    public function eliminar() {
        $req = $this->request;
        $vdt = new Validate\QuickValidator([$this, 'notFound']);
        $vdt->test($req->post('id'), new Validate\Rule\NumNatural());
        $comentario = Comentario::findOrFail($req->post('id'));
        $comentario->delete();
        $this->flash('success', 'El comentario se ha eliminado exitosamente.');
        $this->redirect(preg_replace('/\?.*/', '', $req->getReferrer()) . '?seccion=' . $vdt->getData('seccion') );
    }
}
