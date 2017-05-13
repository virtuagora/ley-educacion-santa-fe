<?php use Augusthur\Validation as Validate;

class AdminCtrl extends Controller {

    public function verAdminAjustes() {
        $ajustes = Ajuste::all();
        $this->render('lpe/admin/ajustes.twig', array('ajustes' => $ajustes->toArray()));
    }

    public function verComentariosAdmin() {
        $derechos = Contenido::where('contenible_type', 'Derecho')->get()->toArray();
        $this->render('lpe/admin/comentariosAdmin.twig',array('derechos' => $derechos));
    }

    public function verComentariosAdminDerecho($idDer) {
        $derecho = Derecho::with('contenido.tags')->findOrFail($idDer);
        $contenido = $derecho->contenido;
        $datosDer = array_merge($contenido->toArray(), $derecho->toArray());
        $this->render('lpe/admin/comentariosAdminDerecho.twig',array('derecho' => $datosDer));
    }

    public function eliminarComentariosAdminDerecho($idDer) {
        //RECIBIR EL STRING DE IDS DE COMENTARIOS DEL $idDer
        $vdt = new Validate\Validator();
        $vdt->addRule('comments', new Validate\Rule\NumNatural())
            ->addFilter('comments', FilterFactory::explode('&&'));
        $req = $this->request;
        $data = $this->request->post();
        if (!$vdt->validate($data)) {
            throw new TurnbackException($vdt->getErrors());
        }
        $comentarios = Comentario::whereIn('id', $vdt->getData('comments'))->get();
        foreach ($comentarios as $comment) {
            $comment->delete();
        }
        $this->flash('success', 'Comentario(s) borrado(s) exitosamente');
        $this->redirectTo('shwComentariosAdminDerecho',['idDer' => $idDer]);
    }
    public function verExportar() {
        $this->render('lpe/admin/exportar.twig');
    }

    public function verEmails() {
        $usuarios = Usuario::all();
        $this->response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $this->response->headers->set('Content-Disposition', 'attachment; filename=emails.csv');
        $output = fopen('php://output', 'w');
        fputcsv($output, ['nombre', 'apellido', 'email']);
        foreach ($usuarios as $usr) {
            fputcsv($output, [$usr->nombre, $usr->apellido, $usr->email]);
        }
    }

    public function verComments($idDer) {
        $derecho = Derecho::with('contenido.tags')->findOrFail($idDer);
        $contenido = $derecho->contenido;
        $datosDer = array_merge($contenido->toArray(), $derecho->toArray());
        $this->response->headers->set('Content-Type', 'application/json; charset=utf-8');
        $this->response->headers->set('Content-Disposition', 'attachment; filename=comments.json');
        echo json_encode($datosDer, JSON_PRETTY_PRINT);
    }

    public function verEstadisticas() {
        $usrData = [
            'total' => Usuario::count(),
            'comentadores' => Usuario::has('comentarios')->count(),
            'participantes' => Usuario::has('votos')->count(),
        ];
        $comData = [
            'aportes' => Comentario::where('comentable_type', 'Seccion')->count(),
            'respuestas' => Comentario::where('comentable_type', 'Comentario')->count(),
        ];
        $req = $this->request;
        $desde = $req->get('fechaDesde');
        $hasta = $req->get('fechaHasta');

        if(($desde != null) || ($hasta != null)){
            $queryUsu = Usuario::query();
            $queryApo = Comentario::where('comentable_type', 'Seccion');
            $queryRes = Comentario::where('comentable_type', 'Comentario');
            if ($desde) {
                $desdeDate = Carbon\Carbon::createFromFormat('Y-m-d',$desde);
                $queryUsu = $queryUsu->whereDate('created_at', '>=', $desdeDate);
                $queryApo = $queryApo->whereDate('created_at', '>=', $desdeDate);
                $queryRes = $queryRes->whereDate('created_at', '>=', $desdeDate);
            }
            if ($hasta) {
                $hastaDate = Carbon\Carbon::createFromFormat('Y-m-d',$hasta);
                $queryUsu = $queryUsu->whereDate('created_at', '<=', $hastaDate);
                $queryApo = $queryApo->whereDate('created_at', '<=', $hastaDate);
                $queryRes = $queryRes->whereDate('created_at', '<=', $hastaDate);
            }
            $datos = [
                'usuarios' => $queryUsu->get(),
                'aportes' => $queryApo->get(),
                'respuestas' => $queryRes->get(),
            ];
        } else{
            $datos = null;
        }
        $secciones = Seccion::all()->toArray();

        $this->render('lpe/admin/estadisticas.twig', [
            'datosUsuarios' => $usrData,
            'datosComentarios' => $comData,
            'datos' => $datos,
            'fechaDesde' => $desde,
            'fechaHasta' => $hasta,
            'secciones' => $secciones
        ]);
    }

    public function verEstadisticasTemporales() {
        $req = $this->request;
        $desde = $req->get('desde');
        $hasta = $req->get('hasta');

        $queryUsu = Usuario::query();
        $queryApo = Comentario::where('comentable_type', 'Seccion');
        $queryRes = Comentario::where('comentable_type', 'Comentario');

        if ($desde) {
            $desdeDate = Carbon\Carbon::parse($req->get('desde'));
            $queryUsu = $queryUsu->whereDate('created_at', '>=', $desdeDate);
            $queryApo = $queryApo->whereDate('created_at', '>=', $desdeDate);
            $queryRes = $queryRes->whereDate('created_at', '>=', $desdeDate);
        }
        if ($hasta) {
            $hastaDate = Carbon\Carbon::parse($req->get('hasta'));
            $queryUsu = $queryUsu->whereDate('created_at', '<=', $hastaDate);
            $queryApo = $queryApo->whereDate('created_at', '<=', $hastaDate);
            $queryRes = $queryRes->whereDate('created_at', '<=', $hastaDate);
        }
        $datos = [
            'usuarios' => $queryUsu->count(),
            'aportes' => $queryApo->count(),
            'respuestas' => $queryRes->count(),
        ];
        $this->render('lpe/admin/estadisticas.twig', ['datos' => $datos]);
    }

    public function adminAjustes() {
        $vdt = new Validate\Validator();
        $vdt->addRule('tos', new Validate\Rule\MinLength(8))
            ->addRule('tos', new Validate\Rule\MaxLength(8192))
            ;
        $req = $this->request;
        if (!$vdt->validate($req->post())) {
            throw new TurnbackException($vdt->getErrors());
        }
        $ajustes = Ajuste::all();
        foreach ($ajustes as $ajuste) {
            $newValue = $vdt->getData($ajuste->key);
            if (isset($newValue)) {
                $ajuste->value = $newValue;
                $ajuste->save();
                AdminlogCtrl::createLog('', 1, 'mod', $this->session->user('id'), $ajuste);
            }
        }

        $this->flash('success', 'Los ajustes se han modificado exitosamente.');
        $this->redirectTo('shwAdmAjuste');
    }

    public function verCrearModerador() {
        $mods = Usuario::whereNotNull('patrulla_id')->get()->toArray();
        $this->render('lpe/admin/moderadores.twig', ['moderadores' => $mods]);
    }

    public function crearModerador() {
        $req = $this->request;
        $usuario = Usuario::findOrFail($req->post('id'));
        if ($usuario->patrulla_id) {
            throw new TurnbackException('Ese usuario ya es moderador.');
        }
        $usuario->patrulla_id = 1;
        $usuario->save();
        $this->flash('success', 'El usuario ya es moderador.');
        $this->redirectTo('shwCrearModerad');
    }

    public function sancUsuario($idUsu) {
        $vdt = new Validate\Validator();
        $vdt->addRule('idUsu', new Validate\Rule\NumNatural())
            ->addRule('tipo', new Validate\Rule\InArray(array('Suspension', 'Advertencia', 'Quita')))
            ->addRule('mensaje', new Validate\Rule\MinLength(4))
            ->addRule('mensaje', new Validate\Rule\MaxLength(128))
            ->addRule('cantidad', new Validate\Rule\NumNatural());
        $req = $this->request;
        $data = array_merge(array('idUsu' => $idUsu), $req->post());
        if (!$vdt->validate($data)) {
            throw new TurnbackException($vdt->getErrors());
        }
        $usuario = Usuario::findOrFail($vdt->getData('idUsu'));
        switch ($vdt->getData('tipo')) {
            case 'Suspension':
                $usuario->suspendido = true;
                if ($vdt->getData('cantidad') > 0) {
                    $usuario->fin_suspension = Carbon\Carbon::now()->addDays($vdt->getData('cantidad'));
                } else {
                    $usuario->fin_suspension = null;
                }
                $usuario->save();
                $mensaje = "El usuario fue suspendido.";
                break;
            case 'Advertencia':
                $usuario->advertencia = $vdt->getData('mensaje');
                $usuario->fin_advertencia = Carbon\Carbon::now()->addDays($vdt->getData('cantidad'));
                $usuario->save();
                $mensaje = "El usuario ha sido advertido.";
                break;
            case 'Quita':
                $usuario->decrement('puntos', $vdt->getData('cantidad'));
                $mensaje = "Se le han quitado los puntos al usuario.";
                break;
        }
        $subclase = strtolower(substr($vdt->getData('tipo'), 0, 3));
        $log = AdminlogCtrl::createLog($vdt->getData('mensaje'), 1, $subclase, $this->session->user('id'), $usuario);
        NotificacionCtrl::createNotif($usuario->id, $log);
        $this->flash('success', $mensaje);
        $this->redirect($req->getReferrer());
    }

    public function verIndexAdmin() {
        $this->render('lpe/admin/indexAdmin.twig');
    }

    public function verSubirImagen() {
        $dir = __DIR__ . '/../public/img/uploads';
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        $files = array_diff(scandir($dir), ['..', '.']);
        $this->render('lpe/admin/subirImagenes.twig', ['imagenes' => $files]);
    }

    public function subirImagen() {
        $req = $this->request;
        $asociar = $req->post('asociar');
        $vdt = new Validate\QuickValidator([$this, 'notFound']);
        $vdt->test($asociar, new Validate\Rule\InArray([
            '1', '2', '3', '4', '5', '6', '7', '8', '9', '10', 'no', 'banner'
        ]));
        if (in_array($asociar, ['1', '2', '3', '4', '5', '6', '7', '8', '9', '10'])) {
            $dir = __DIR__ . '/../public/img/bloque';
            $id = $asociar;
        } elseif ($asociar == 'banner') {
            $dir = __DIR__ . '/../public/img';
            $id = 'banner';
        } else {
            $dir = __DIR__ . '/../public/img/uploads';
            $id = time();
        }
        if (isset($_FILES['archivo'])) {
            if (!is_dir($dir)) {
                mkdir($dir, 0777, true);
            }
            $storage = new \Upload\Storage\FileSystem($dir, true);
            $file = new \Upload\File('archivo', $storage);
            $file->setName($id);
            $file->addValidations([
                new \Upload\Validation\Mimetype(['image/jpg', 'image/jpeg', 'image/png']),
                new \Upload\Validation\Size('2M')
            ]);
            $file->upload();
        } else {
            throw new TurnbackException('No seleccionó imagen.');
        }
        $this->flash('success', 'La imagen se cargó exitosamente.');
        $this->redirectTo('shwCrearImagen');
    }
}
