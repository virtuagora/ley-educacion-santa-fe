<?php
// Prepare view
$app->view(new \Slim\Views\Twig());
$app->view->parserOptions = array(
    'charset' => 'utf-8',
    'cache' => false,
    'auto_reload' => true,
    'strict_variables' => false,
    'autoescape' => true
);
$app->view->parserExtensions = array(new ExtendedTwig());

// Prepare singletons
$app->container->singleton('session', function () use ($app) {
    return new SessionManager($app->getMode());
});

$app->container->singleton('translator', function () {
    $trans = new Symfony\Component\Translation\Translator('es');
    $trans->addLoader('php', new Symfony\Component\Translation\Loader\PhpFileLoader());
    $trans->addResource('php', __DIR__.'/../locales/es.php', 'es');
    return $trans;
});

$app->api = false;

// Prepare error handler
$app->error(function (Exception $e) use ($app) {
    if ($app->api) {
        // TODO setar codigo de error correcto.
        $msg = array('code' => $e->getCode(), 'message' => $e->getMessage());
        if ($e instanceof TurnbackException) {
            $msg['errors'] = $e->getErrors();
        }
        echo json_encode($msg);
    } else {
        if ($e instanceof TurnbackException) {
            $app->flash('errors', $e->getErrors());
            ob_end_clean();
            $app->redirect($app->request->getReferrer());
        } else if ($e instanceof BearableException) {
            $app->render('misc/error.twig', array('mensaje' => $e->getMessage()), $e->getCode());
        } else if ($e instanceof Illuminate\Database\Eloquent\ModelNotFoundException) {
            $app->notFound();
        } else if ($e instanceof Illuminate\Database\QueryException && $e->getCode() == 23000) {
            $app->render('misc/error.twig', array('mensaje' => 'La información ingresada es inconsistente.'), 400);
        } else {
            $app->render('misc/fatal-error.twig', array('type' => get_class($e), 'exception' => $e));
            //$app->render('misc/error.twig', ['mensaje' => 'Ocurrió un error interno.'], 500);
        }
    }
});

// Prepare hooks
$app->hook('slim.before', function () use ($app) {
    $app->view()->appendData(array('user' => $app->session->user()));
});

// Prepare middlewares
$checkNoSession = function () use ($app) {
    if ($app->session->check()) {
        $app->redirectTo('shwPortal');
    }
};

$checkRole = function ($role) use ($app) {
    return function () use ($role, $app) {
        if (is_array($role)) {
            $userRoles = $app->session->grantedRoles($role);
            $rejected = empty($userRoles);
        } else {
            $rejected = !$app->session->hasRole($role);
        }
        if ($rejected) {
            throw new BearableException('No tiene permiso para realizar esta acción', 403);
        }
    };
};

$checkAdminAuth = function ($action) use ($app) {
    return function () use ($action, $app) {
        if (!$app->session->isAdminAllowedTo($action)) {
            throw new BearableException('No tiene permiso para realizar esta acción', 403);
        }
    };
};

$checkModifyAuth = function ($resource, $moderable = true) use ($app) {
    return function ($route) use ($resource, $moderable, $app) {
        $params = $route->getParams();
        $idRes = reset($params);
        $idUsr = $app->session->user('id');
        $query = call_user_func($resource.'::modifiableBy', $idUsr);
        if (is_null($query->find($idRes)) && !($moderable && $app->session->hasRole('mod'))) {
            throw new BearableException('No tiene permiso para realizar esta acción', 403);
        }
    };
};

// Prepare dispatcher
//$app->get('/test', function () use ($app) {
    //$app->render('lpe/test.twig');
    //$req = $app->request;
    //$uri = $req->headers->get('x-forwarded-host')?: $req->getUrl();
    //var_dump($uri);
//});

$app->group('/derecho', function () use ($app, $checkRole) {
    $app->get('/crear', $checkRole('mod'), 'DerechoCtrl:verCrear')->name('shwCrearDerecho');
    $app->post('/crear', $checkRole('mod'), 'DerechoCtrl:crear')->name('runCrearDerecho');
    $app->get('/:idDer', 'DerechoCtrl:ver')->name('shwDerecho');
    $app->post('/votar/:idSec', $checkRole('usr'), 'DerechoCtrl:votar')->name('runVotarSeccion');
    $app->get('/:idDer/modificar', $checkRole('mod'), 'DerechoCtrl:verModificar')->name('shwModifDerecho');
    $app->post('/:idDer/modificar', $checkRole('mod'), 'DerechoCtrl:modificar')->name('runModifDerecho');
});

$app->group('/opinion', function () use ($app, $checkRole) {
    $app->get('/crear', $checkRole('mod'), 'OpinionCtrl:verCrear')->name('shwCrearOpinion');
    $app->post('/crear', $checkRole('mod'), 'OpinionCtrl:crear')->name('runCrearOpinion');
    $app->get('/:idOpi/modificar', $checkRole('mod'), 'OpinionCtrl:verModificar')->name('shwModifOpinion');
    $app->post('/:idOpi/modificar', $checkRole('mod'), 'OpinionCtrl:modificar')->name('runModifOpinion');
});

$app->group('/participante', function () use ($app, $checkRole) {
    $app->get('/', $checkRole('mod'), 'ParticipanteCtrl:listar')->name('shwListaPartici');
    $app->get('/crear', $checkRole('mod'), 'ParticipanteCtrl:verCrear')->name('shwCrearPartici');    
    $app->post('/crear', $checkRole('mod'), 'ParticipanteCtrl:crear')->name('runCrearPartici');
});

$app->group('/evento', function () use ($app, $checkRole) {
    $app->get('', 'EventoCtrl:listar')->name('shwListaEvento');
    $app->get('/crear', $checkRole('mod'), 'EventoCtrl:verCrear')->name('shwCrearEvento');
    $app->post('/crear', $checkRole('mod'), 'EventoCtrl:crear')->name('runCrearEvento');
    $app->get('/:idEve', 'EventoCtrl:ver')->name('shwEvento');
    $app->post('/:idEve/participar', $checkRole('usr'), 'EventoCtrl:participar')->name('runPartiEvento');
    $app->get('/:idEve/modificar', $checkRole('mod'), 'EventoCtrl:verModificar')->name('shwModifEvento');
    $app->post('/:idEve/modificar', $checkRole('mod'), 'EventoCtrl:modificar')->name('runModifEvento');
    $app->post('/:idEve/eliminar', $checkRole('mod'), 'EventoCtrl:eliminar')->name('runElimiEvento');
});

$app->group('/admin', function () use ($app, $checkRole) {
    $app->get('/', $checkRole('mod'), 'AdminCtrl:verIndexAdmin')->name('shwIndexAdmin');    
    $app->get('/upload', $checkRole('mod'), 'AdminCtrl:verSubirImagen')->name('shwCrearImagen');
    $app->post('/upload', $checkRole('mod'), 'AdminCtrl:subirImagen')->name('runCrearImagen');
    $app->get('/imagen/:idEve', 'AdminCtrl:verImagen')->name('shwImagen');
    $app->post('/sancionar/:idUsu', $checkRole('mod'), 'AdminCtrl:sancUsuario')->name('runSanUsuario');
    $app->get('/verificar', $checkRole('mod'), 'AdminCtrl:verVerifCiudadano')->name('shwAdmVrfUsuario');
    $app->post('/verificar', $checkRole('mod'), 'AdminCtrl:verifCiudadano')->name('runAdmVrfUsuario');
    $app->get('/ajustes', $checkRole('mod'), 'AdminCtrl:verAdminAjustes')->name('shwAdmAjuste');
    $app->post('/ajustes', $checkRole('mod'), 'AdminCtrl:adminAjustes')->name('runAdmAjuste');
    $app->get('/estadisticas', $checkRole('mod'), 'AdminCtrl:verEstadisticas')->name('shwEstadi');
    $app->get('/estadisticastemp', $checkRole('mod'), 'AdminCtrl:verEstadisticasTemporales')->name('shwEstadiTemp');
    $app->get('/exportar', $checkRole('mod'), 'AdminCtrl:verExportar')->name('shwExportar');
    $app->get('/emails', $checkRole('mod'), 'AdminCtrl:verEmails')->name('shwEmails');
    $app->get('/comments/:idDer', $checkRole('mod'), 'AdminCtrl:verComments')->name('shwComments');
    $app->get('/moderador/crear', $checkRole('mod'), 'AdminCtrl:verCrearModerador')->name('shwCrearModerad');
    $app->post('/moderador/crear', $checkRole('mod'), 'AdminCtrl:crearModerador')->name('runCrearModerad');
    $app->get('/comentarios', $checkRole('mod'), 'AdminCtrl:verComentariosAdmin')->name('shwComentariosAdmin');
    $app->get('/comentarios/:idDer', $checkRole('mod'), 'AdminCtrl:verComentariosAdminDerecho')->name('shwComentariosAdminDerecho');
    $app->post('/comentarios/:idDer/eliminar', $checkRole('mod'), 'AdminCtrl:eliminarComentariosAdminDerecho')->name('runComentariosAdminDerecho');
});

$app->group('/comentario', function () use ($app, $checkRole, $checkModifyAuth) {
    $app->get('', 'ComentarioCtrl:listar')->name('shwListaComenta');
    $app->post('/comentar/:tipoRaiz/:idRaiz', $checkRole('usr'), 'ComentarioCtrl:comentar')->name('runComentar');
    $app->get('/:idCom', 'ComentarioCtrl:ver')->name('shwComenta');
    $app->post('/:idCom/votar', $checkRole('usr'), 'ComentarioCtrl:votar')->name('runVotarComenta');
    $app->post('/eliminar/:idCom', $checkModifyAuth('Comentario'), 'ComentarioCtrl:eliminar')->name('runElimiComenta');
});

$app->get('/', 'PortalCtrl:verIndex')->name('shwIndex');
$app->get('/portal', 'PortalCtrl:verPortal')->name('shwPortal');
//$app->get('/tos', 'PortalCtrl:verTos')->name('shwTos');
$app->get('/antecedentes', 'PortalCtrl:verAntecedentes')->name('shwAnteced');
$app->get('/propuesta', 'PortalCtrl:verPropuesta')->name('shwProp');
$app->get('/login', $checkNoSession, 'PortalCtrl:verLogin')->name('shwLogin');
$app->post('/login', $checkNoSession, 'PortalCtrl:login')->name('runLogin');
$app->post('/logout', 'PortalCtrl:logout')->name('runLogout');
$app->get('/registro', $checkNoSession, 'PortalCtrl:verRegistrar')->name('shwCrearUsuario');
$app->post('/registro', $checkNoSession, 'PortalCtrl:registrar')->name('runCrearUsuario');
$app->get('/validar/:idUsu/:token', 'PortalCtrl:verificarEmail')->name('runValidUsuario');
$app->get('/recuperar-clave', $checkNoSession, 'PortalCtrl:verRecuperarClave')->name('shwRecuperarClave');
$app->post('/recuperar-clave', $checkNoSession, 'PortalCtrl:recuperarClave')->name('runRecuperarClave');
$app->get('/reiniciar-clave/:idUsu/:token', $checkNoSession, 'PortalCtrl:verReiniciarClave')->name('shwReiniciarClave');
$app->post('/reiniciar-clave/:idUsu/:token', $checkNoSession, 'PortalCtrl:reiniciarClave')->name('runReiniciarClave');
$app->get('/reiniciar-clave', $checkNoSession, 'PortalCtrl:finReiniciarClave')->name('endReiniciarClave');

$app->group('/galeria', function () use ($app, $checkRole) {
    $app->get('/', 'GaleriaCtrl:verGaleria')->name('shwGaleria');
    $app->get('/album/:idAlb', 'GaleriaCtrl:verAlbum')->name('shwAlbum');
    $app->post('/album/crear', $checkRole('mod'), 'GaleriaCtrl:crearAlbum')->name('runCrearAlbum');
    $app->post('/eliminar/:idAlb', $checkRole('mod'), 'GaleriaCtrl:eliminarAlbum')->name('runElimiAlbum');
    $app->post('/foto/subir', $checkRole('mod'), 'GaleriaCtrl:subirFoto')->name('runSubirFoto');
    $app->post('/foto/:idFot', $checkRole('mod'), 'GaleriaCtrl:eliminarFoto')->name('runElimiFoto');
});

$app->get('/contenido/:idCon', 'ContenidoCtrl:ver')->name('shwConteni');
$app->get('/contenido', 'ContenidoCtrl:listar')->name('shwListaConteni');

$app->get('/usuario/:idUsu', 'UsuarioCtrl:ver')->name('shwUsuario');
$app->get('/usuario/:idUsu/imagen/:res', 'UsuarioCtrl:verImagen')->name('shwImgUsuario');
$app->get('/usuario', 'UsuarioCtrl:listar')->name('shwListaUsuario');

$app->group('/perfil', function () use ($app, $checkRole) {
    $app->get('/modificar', $checkRole('usr'), 'UsuarioCtrl:verModificar')->name('shwModifUsuario');
    $app->post('/modificar', $checkRole('usr'), 'UsuarioCtrl:modificar')->name('runModifUsuario');
//     $app->post('/cambiar-imagen', $checkRole('usr'), 'UsuarioCtrl:cambiarImagen')->name('runModifImgUsuario');
    $app->get('/cambiar-clave', $checkRole('usr'), 'UsuarioCtrl:verCambiarClave')->name('shwModifClvUsuario');
    $app->post('/cambiar-clave', $checkRole('usr'), 'UsuarioCtrl:cambiarClave')->name('runModifClvUsuario');
//     $app->get('/eliminar', $checkRole('usr'), 'UsuarioCtrl:verEliminar')->name('shwElimiUsuario');
//     $app->post('/eliminar', $checkRole('usr'), 'UsuarioCtrl:eliminar')->name('runElimiUsuario');
});
