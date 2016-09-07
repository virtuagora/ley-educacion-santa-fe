<?php use Augusthur\Validation as Validate;

class UsuarioCtrl extends RMRController {

    protected $mediaTypes = array('json', 'view');
    protected $properties = array('id', 'nombre', 'apellido', 'es_funcionario', 'es_jefe', 'puntos', 'partido_id',
                                  'patrulla_id', 'created_at', 'suspendido', 'advertencia', 'verified_at');
    protected $searchable = true;

    public function queryModel($meth, $repr) {
        switch ($meth) {
            case 0: return Usuario::query();
            case 1: return Usuario::with('partido');
        }
    }

    public function executeListCtrl($paginator) {
        $this->notFound();
    }

    public function executeGetCtrl($usuario) {
        $req = $this->request;
        $url = $req->getUrl().$req->getPath();
        $paginator = new Paginator($usuario->acciones(), $url, $req->get());
        $acciones = $paginator->rows;
        $nav = $paginator->links;
        $datos = $usuario->toArray();
        $comentarios = $usuario->comentarios()->orderBy('created_at', 'desc')->take(5)->get()->toArray();
        $derechos = Contenido::where('contenible_type', 'Derecho')->get()->toArray();
        $datos['contenidos_count'] = $usuario->contenidos()->count();
        $datos['comentarios_count'] = $usuario->comentarios()->count();
        $this->render('lpe/usuario/ver.twig', array('usuario' => $datos,
                                                'acciones' => $acciones,
                                                'comentarios' => $comentarios,
                                                'derechos' => $derechos,
                                                'nav' => $nav));
    }

    public function verCambiarClave() {
        $this->render('/lpe/usuario/cambiar-clave.twig');
    }

    public function cambiarClave() {
        $usuario = $this->session->getUser();
        $vdt = new Validate\Validator();
        $vdt->setLabel('pass-old', 'La contraseña actual')
            ->setLabel('pass-new', 'La contraseña nueva')
            ->addRule('pass-old', new Validate\Rule\MatchesPassword($usuario->password))
            ->addRule('pass-new', new Validate\Rule\MinLength(8))
            ->addRule('pass-new', new Validate\Rule\MaxLength(128))
            ->addRule('pass-new', new Validate\Rule\Matches('pass-verif'));
        $req = $this->request;
        if (!$vdt->validate($req->post())) {
            throw new TurnbackException($vdt->getErrors());
        }
        if (!$this->session->login($this->session->user('email'), $vdt->getData('pass-old'))) {
            throw new TurnbackException('Contraseña inválida.');
        }
        $usuario->password = password_hash($vdt->getData('pass-new'), PASSWORD_DEFAULT);
        $usuario->save();
        $this->flash('success', 'Su contraseña fue modificada exitosamente.');
        $this->redirect($this->request->getReferrer());
    }

    public function verModificar() {
        $usuario = $this->session->getUser();
        $datos = $usuario->toArray();
        $datos['email'] = $usuario->email;
        $this->render('/lpe/usuario/modificar.twig', array('usuario' => $datos, 'localidades' => ['Rosario','La Capital','General López','Castellanos','General Obligado',
                'San Lorenzo','Las Colonias','Constitución','Caseros','San Jerónimo','San Cristóbal',
                'Iriondo','San Martín','Vera','Belgrano','San Justo','San Javier','9 de Julio','Garay'],
            'ocupaciones' => ['Estudiante','Docente Nivel Inicial','Docente Nivel Primario',
                'Docente Nivel Secundario','Docente Nivel Terciario','Docente Universitario','Asistente escolar',
                'Representante gremial','Profesional','Empleado/a en relación de dependencia','Comerciante',
                'Funcionario/a, legislador/a y autoridad gubernamental','Representante de organización social',
                'Trabajador/a doméstico/a no remunerado/a','Otro']));
    }

    public function modificar() {
        $vdt = new Validate\Validator();
        $vdt->addRule('nombre', new Validate\Rule\Alpha(array(' ')))
            ->addRule('nombre', new Validate\Rule\MinLength(1))
            ->addRule('nombre', new Validate\Rule\MaxLength(32))
            ->addRule('apellido', new Validate\Rule\Alpha(array(' ')))
            ->addRule('apellido', new Validate\Rule\MinLength(1))
            ->addRule('apellido', new Validate\Rule\MaxLength(32))
            ->addRule('url', new Validate\Rule\URL())
            ->addRule('telefono', new Validate\Rule\Telephone())
            ->addRule('birthday', new Validate\Rule\Date('Y-m-d'))
            ->addRule('address', new Validate\Rule\InArray(['Rosario','La Capital','General López','Castellanos',
                'General Obligado','San Lorenzo','Las Colonias','Constitución','Caseros','San Jerónimo','San Cristóbal',
                'Iriondo','San Martín','Vera','Belgrano','San Justo','San Javier','9 de Julio','Garay']))
            ->addRule('title', new Validate\Rule\InArray(['Estudiante','Docente Nivel Inicial','Docente Nivel Primario',
                'Docente Nivel Secundario','Docente Nivel Terciario','Docente Universitario','Asistente escolar',
                'Representante gremial','Profesional','Empleado/a en relación de dependencia','Comerciante',
                'Funcionario/a, legislador/a y autoridad gubernamental','Representante de organización social',
                'Trabajador/a doméstico/a no remunerado/a','Otro']))
            ->addOptional('url')
            ->addOptional('telefono')
            ->addFilter('url', FilterFactory::emptyToNull())
            ->addFilter('telefono', FilterFactory::emptyToNull());
        $req = $this->request;
        if (!$vdt->validate($req->post())) {
            throw new TurnbackException($vdt->getErrors());
        }
        $cumple = Carbon\Carbon::parse($vdt->getData('birthday'));
        $limInf = Carbon\Carbon::create(1900, 1, 1, 0, 0, 0);
        $limSup = Carbon\Carbon::now();
        if (!$cumple->between($limInf, $limSup)) {
            throw new TurnbackException('Fecha inválida.');
        }
        $usuario = $this->session->getUser();
        $usuario->nombre = $vdt->getData('nombre');
        $usuario->apellido = $vdt->getData('apellido');
        $usuario->birthday = $cumple;
        $usuario->title = $vdt->getData('title');
        $usuario->address = $vdt->getData('address');
        $usuario->save();
        $contacto = $usuario->contacto ?: new Contacto;
        $contacto->web = $vdt->getData('url');
        $contacto->telefono = $vdt->getData('telefono');
        $contacto->contactable()->associate($usuario);
        $contacto->save();
        $this->flash('success', 'Sus datos fueron modificados exitosamente.');
        $this->redirect($this->request->getReferrer());
    }

    public function verImagen($idUsu, $res) {
        $vdt = new Validate\QuickValidator(array($this, 'notFound'));
        $vdt->test($idUsu, new Validate\Rule\NumNatural());
        $vdt->test($res, new Validate\Rule\InArray([32, 64, 160]));
        $usuario = Usuario::findOrFail($idUsu);
        $this->redirect(call_user_func($this->view->getInstance()->getFunction('avatarUrl')->getCallable(),
                                       $usuario->img_tipo, $usuario->img_hash, $res));
    }

    public function cambiarImagen() {
        $usuario = $this->session->getUser();
        $usuario->img_tipo = 0;
        $usuario->img_hash = $usuario->id;
        $usuario->save();
        ImageManager::cambiarImagen('usuario', $usuario->id, array(32, 64, 160));
        $this->session->update($usuario);
        $this->flash('success', 'Imagen cargada exitosamente.');
        $this->redirect($this->request->getReferrer());
    }

    public function verEliminar() {
        $this->render('usuario/eliminar.twig');
    }

    public function eliminar() {
        $vdt = new Validate\Validator();
        $vdt->addRule('password', new Validate\Rule\MinLength(8))
            ->addRule('password', new Validate\Rule\MaxLength(128));
        $req = $this->request;
        if (!$vdt->validate($req->post())) {
            throw new TurnbackException($vdt->getErrors());
        }
        if (!$this->session->login($this->session->user('email'), $vdt->getData('password'))) {
            throw new TurnbackException('Contraseña inválida.');
        }
        $usuario = $this->session->getUser();
        $usuario->delete();
        $this->session->logout();
        $this->flash('success', 'Su cuenta ha sido eliminada.');
        $this->redirectTo('shwIndex');
    }

}
