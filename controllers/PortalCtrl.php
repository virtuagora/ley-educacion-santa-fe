<?php use Augusthur\Validation as Validate;

class PortalCtrl extends Controller {

    public function verIndex() {
        $derechos = Contenido::where('contenible_type', 'Derecho')->get()->toArray();
        $eventos = Evento::whereDate('fecha', '>=', date('Y-m-d'))->orderBy('fecha', 'asc')->take(2)->get()->toArray();
        $ajustes = Ajuste::whereIn('key', ['titulo', 'intro', 'videos'])->get();
        foreach ($ajustes as $aju) {
            if ($aju->key == 'titulo') {
                $titulo = $aju->value;
            } elseif ($aju->key == 'intro') {
                $intro = $aju->value;
            } elseif ($aju->key == 'videos') {
                $videos = explode('&&', $aju->value);
            }
        }
        $this->render('lpe/portal/inicio.twig',  [
            'derechos' => $derechos,
            'eventos' => $eventos,
            'titulo' => $titulo,
            'intro' => $intro,
            'videos' => $videos,
        ]);
    }

    public function verPortal() {
        $this->render('lpe/portal/inicio.twig');
    }

    public function verLogin() {
        $helper = $this->facebook->getRedirectLoginHelper();
        //TODO cambiar a https://www.santafe.gob.ar/leyeducacion
        $fbPath = 'http://localhost:8000'.str_replace(
            '/public/',
            '/public/index.php/',
            $this->urlFor('fbLogin')
        );
        $fbUrl = $helper->getLoginUrl($fbPath, ['email']);
        $this->render('lpe/registro/login-static.twig', [
            'facebook' => $fbUrl,
        ]);
    }

    public function verAntecedentes() {
        $this->render('lpe/contenido/static/antecedentes.twig');
    }

    public function verPropuesta() {
        $this->render('lpe/contenido/static/propuesta.twig');
    }

    public function verTos() {
        $tos = Ajuste::where('key', 'tos')->firstOrFail();
        $this->render('lpe/contenido/static/tos.twig', ['tos' => $tos->toArray()]);
    }

    public function login() {
        $vdt = new Validate\Validator();
        $vdt->addRule('email', new Validate\Rule\Email())
            ->addRule('email', new Validate\Rule\MaxLength(128))
            ->addRule('password', new Validate\Rule\MaxLength(128));
        $req = $this->request;
        if ($vdt->validate($req->post()) && $this->session->login($vdt->getData('email'), $vdt->getData('password'))) {
            $this->redirectTo('shwIndex');
        } else {
            $this->flash('errors', array('Datos de ingreso incorrectos. Por favor vuelva a intentarlo.'));
            $this->redirectTo('shwLogin');
        }
    }

    public function logout() {
        $this->session->logout();
        $this->redirectTo('shwIndex');
    }

    public function facebookLogin()
    {
        $helper = $this->facebook->getRedirectLoginHelper();
        $accessToken = $helper->getAccessToken();
        if (!isset($accessToken)) {
            if ($helper->getError()) {
                $msg = 'Error: '.$helper->getError().'\n';
                $msg += 'Error Code: '.$helper->getErrorCode().'\n';
                $msg += 'Error Reason: '.$helper->getErrorReason().'\n';
                $msg += 'Error Description: '.$helper->getErrorDescription();
                $this->logger->info($msg);
            }
            throw new BearableException('Petición de acceso a Facebook inválida.', 400);
        }
        $response = $this->facebook->get('/me?fields=id,first_name,last_name,email', $accessToken);
        $userNode = $response->getGraphUser();
        if (!$userNode['email']) {
            //TODO mejorar
            throw new BearableException('No tenés una dirección de email asociada en facebook.', 400);
        }
        $user = Usuario::firstOrNew([
            'email' => $userNode['email'],
        ]);
        if (!$user->exists) {
            $user->facebook = $userNode['id'];
            $user->name = $userNode['first_name'];
            $user->password = '';
            $user->nombre = $userNode['first_name'];
            $user->apellido = $userNode['last_name'];
            $user->puntos = 0;
            $user->suspendido = false;
            $user->es_funcionario = false;
            $user->es_jefe = false;
            $user->img_tipo = 2;
            $user->img_hash = $userNode['id'];
            $user->birthday = null;
            $user->title = null;
            $user->address = null;
            $user->save();
        }
        $this->session->update($user);
        $this->redirectTo('shwIndex');
    }

    public function verRegistrar() {
        $this->render('lpe/registro/registro.twig', [
            'localidades' => ['Rosario','La Capital','General López','Castellanos','General Obligado',
                'San Lorenzo','Las Colonias','Constitución','Caseros','San Jerónimo','San Cristóbal',
                'Iriondo','San Martín','Vera','Belgrano','San Justo','San Javier','9 de Julio','Garay'],
            'ocupaciones' => ['Estudiante','Docente Nivel Inicial','Docente Nivel Primario',
                'Docente Nivel Secundario','Docente Nivel Terciario','Docente Universitario','Asistente escolar',
                'Representante gremial','Profesional','Empleado/a en relación de dependencia','Comerciante',
                'Funcionario/a, legislador/a y autoridad gubernamental','Representante de organización social',
                'Trabajador/a doméstico/a no remunerado/a','Otro']
        ]);
    }

    public function registrar() {
        $vdt = new Validate\Validator();
        $vdt->addRule('nombre', new Validate\Rule\Alpha(array(' ')))
            ->addRule('nombre', new Validate\Rule\MinLength(1))
            ->addRule('nombre', new Validate\Rule\MaxLength(32))
            ->addRule('apellido', new Validate\Rule\Alpha(array(' ')))
            ->addRule('apellido', new Validate\Rule\MinLength(1))
            ->addRule('apellido', new Validate\Rule\MaxLength(32))
            ->addRule('password', new Validate\Rule\MinLength(8))
            ->addRule('password', new Validate\Rule\MaxLength(128))
            ->addRule('password', new Validate\Rule\Matches('password2'))
            ->addRule('email', new Validate\Rule\Email())
            ->addRule('email', new Validate\Rule\MaxLength(128))
            ->addRule('email', new Validate\Rule\Unique('usuarios'))
            ->addRule('birthday', new Validate\Rule\Date('Y-m-d'))
            ->addRule('address', new Validate\Rule\InArray(['Rosario','La Capital','General López','Castellanos',
                'General Obligado','San Lorenzo','Las Colonias','Constitución','Caseros','San Jerónimo','San Cristóbal',
                'Iriondo','San Martín','Vera','Belgrano','San Justo','San Javier','9 de Julio','Garay']))
            ->addRule('title', new Validate\Rule\InArray(['Estudiante','Docente Nivel Inicial','Docente Nivel Primario',
                'Docente Nivel Secundario','Docente Nivel Terciario','Docente Universitario','Asistente escolar',
                'Representante gremial','Profesional','Empleado/a en relación de dependencia','Comerciante',
                'Funcionario/a, legislador/a y autoridad gubernamental','Representante de organización social',
                'Trabajador/a doméstico/a no remunerado/a','Otro']))
            ->addFilter('email', 'strtolower')
            ->addFilter('email', 'trim');
        $req = $this->request;
        if (!$vdt->validate($req->post())) {
            throw new TurnbackException($vdt->getErrors());
        }
        $recaptcha = new \ReCaptcha\ReCaptcha('6LcF-CYTAAAAACfCi_a60IK5E57PGC0xDp4Ko5ex');
        $resp = $recaptcha->verify($vdt->getData('g-recaptcha-response'));
        if (!$resp->isSuccess()) {
            throw new TurnbackException('El CAPTCHA es inválido.');
        }
        $cumple = Carbon\Carbon::parse($vdt->getData('birthday'));
        $limInf = Carbon\Carbon::create(1900, 1, 1, 0, 0, 0);
        $limSup = Carbon\Carbon::now();
        if (!$cumple->between($limInf, $limSup)) {
            throw new TurnbackException('Fecha inválida.');
        }
        $preuser = Preusuario::firstOrNew(['email' => $vdt->getData('email')]);
        $preuser->password = md5($vdt->getData('password'));
        $preuser->nombre = $vdt->getData('nombre');
        $preuser->apellido = $vdt->getData('apellido');
        $preuser->emailed_token = bin2hex(openssl_random_pseudo_bytes(16));
        $preuser->birthday = $cumple;
        $preuser->title = $vdt->getData('title');
        $preuser->address = $vdt->getData('address');
        $preuser->save();
        
        $to = $preuser->email;
        $subject = 'Confirma tu registro - Ley Provincial de Educacion - Santa Fe';
        $message = $this->view->fetch('email/registro.twig', [
            'id' => $preuser->id,
            'token' => $preuser->emailed_token
        ]);
        mail($to, $subject, $message);
        
        $this->render('lpe/registro/registro-exito.twig', array('email' => $preuser->email));
    }

    public function verificarEmail($idPre, $token) {
        $vdt = new Validate\QuickValidator(array($this, 'notFound'));
        $vdt->test($idPre, new Validate\Rule\NumNatural());
        $vdt->test($token, new Validate\Rule\AlphaNumeric());
        $vdt->test($token, new Validate\Rule\ExactLength(32));
        $preuser = Preusuario::findOrFail($idPre);
        if ($token == $preuser->emailed_token) {
            $usuario = new Usuario;
            $usuario->email = $preuser->email;
            $usuario->password = $preuser->password;
            $usuario->nombre = $preuser->nombre;
            $usuario->apellido = $preuser->apellido;
            $usuario->puntos = 0;
            $usuario->suspendido = false;
            $usuario->es_funcionario = false;
            $usuario->es_jefe = false;
            $usuario->img_tipo = 1;
            $usuario->img_hash = md5($preuser->email);
            $usuario->birthday = $preuser->birthday;
            $usuario->title = $preuser->title;
            $usuario->address = $preuser->address;
            $usuario->save();
            $preuser->delete();
            $this->render('lpe/registro/validar-correo.twig', array('usuarioValido' => true,
                                                                'email' => $usuario->email));
        } else {
            $this->render('lpe/registro/validar-correo.twig', array('usuarioValido' => false));
        }
    }

    public function verRecuperarClave() {
        $this->render('lpe/registro/recuperar-clave.twig');
    }
    
    public function recuperarClave() {
        $vdt = new Validate\Validator();
        $vdt->addRule('email', new Validate\Rule\Email())
            ->addRule('email', new Validate\Rule\MaxLength(128))
            ->addFilter('email', 'strtolower')
            ->addFilter('email', 'trim');
        $req = $this->request;
        if (!$vdt->validate($req->post())) {
            throw new TurnbackException($vdt->getErrors());
        }
        $usuario = Usuario::where('email', $vdt->getData('email'))->first();
        if (is_null($usuario)) {
            throw new TurnbackException('Email inválido. ¿Estás seguro de que te registraste?');
        }
        $usuario->token = bin2hex(openssl_random_pseudo_bytes(16));
        $usuario->save();
        
        $to = $usuario->email;
        $subject = 'Reiniciar clave - Santa Fe Construye Educacion y Futuro. Dialogos para la ley provincial de educacion';
        $message = $this->view->fetch('email/recuperar.twig', [
            'id' => $usuario->id,
            'token' => $usuario->token
        ]);
        mail($to, $subject, $message);
        
        $this->redirectTo('shwRecuperarClave');
    }
    
    public function verReiniciarClave($idUsu, $token) {
        $vdt = new Validate\QuickValidator(array($this, 'notFound'));
        $vdt->test($idUsu, new Validate\Rule\NumNatural());
        $vdt->test($token, new Validate\Rule\AlphaNumeric());
        $vdt->test($token, new Validate\Rule\ExactLength(32));
        $this->render('lpe/registro/reiniciar-clave.twig', ['idUsu' => $idUsu, 'token' => $token]);
    }
    
    public function reiniciarClave($idUsu, $token) {
        $vdt = new Validate\QuickValidator(array($this, 'notFound'));
        $vdt->test($idUsu, new Validate\Rule\NumNatural());
        $vdt->test($token, new Validate\Rule\AlphaNumeric());
        $vdt->test($token, new Validate\Rule\ExactLength(32));
        $vdt = new Validate\Validator();
        $vdt->addRule('password', new Validate\Rule\MinLength(8))
            ->addRule('password', new Validate\Rule\MaxLength(128))
            ->addRule('password', new Validate\Rule\Matches('password2'));
        if (!$vdt->validate($this->request->post())) {
            throw new TurnbackException($vdt->getErrors());
        }
        $usuario = Usuario::findOrFail($idUsu);
        if ($token != $usuario->token) {
            throw new TurnbackException('El link ha expirado o es inválido. Recordá que solamente es válido por una hora.');
        }
        $ahora = Carbon\Carbon::now();
        if ($ahora->gt($usuario->updated_at->addHour())) {
            throw new TurnbackException('El link ha expirado o es inválido. Recordá que solamente es válido por una hora.');
        }
        $usuario->token = null;
        $usuario->password = md5($vdt->getData('password'));
        $usuario->save();
        $this->redirectTo('endReiniciarClave');
    }
    
    public function finReiniciarClave() {
        $this->render('lpe/registro/reiniciar-completo.twig');
    }
}
