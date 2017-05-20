<?php use Augusthur\Validation as Validate;

class GaleriaCtrl extends Controller {

    public function verGaleria() {
		$albunes = Album::all()->toArray();
        $this->render('lpe/contenido/galeria/listar.twig',[
            'albumes' => $albunes,
        ]);
    }

    public function verAlbum($idAlb) {
        $vdt = new Validate\QuickValidator([$this, 'notFound']);
        $vdt->test($idAlb, new Validate\Rule\NumNatural());
        $album = Album::with('fotos')->findOrFail($idAlb);
        $this->render('lpe/contenido/galeria/verAlbum.twig', [
            'album' => $album->toArray(),
        ]);
    }

    public function crearAlbum() {
        $vdt = new Validate\Validator();
        $vdt->addRule('nombre', new Validate\Rule\MinLength(1))
            ->addRule('nombre', new Validate\Rule\MaxLength(255))
            ->addRule('descripcion', new Validate\Rule\MaxLength(1000))
            ->addOptional('descripcion');
        $req = $this->request;
        if (!$vdt->validate($req->post())) {
            throw new TurnbackException($vdt->getErrors());
        }
        $album = new Album;
        $album->nombre = $vdt->getData('nombre');
        $album->descripcion = $vdt->getData('descripcion');
        $album->save();
        $dir = __DIR__ . '/../public/img/galeria/' . $album->id;
        if (!is_dir($dir)) mkdir($dir, 0777, true);
        if (!is_dir($dir.'/original')) mkdir($dir.'/original', 0777, true);
        if (!is_dir($dir.'/thumbnail')) mkdir($dir.'/thumbnail', 0777, true);
        $this->flash('success', 'El album fue creado exitosamente.');
        $this->redirectTo('shwAlbum', ['idAlb' => $album->id]);
        //TODO
        //El retorno que lo lleve al album
    }

    public function eliminarAlbum($idAlb) {
        $vdt = new Validate\QuickValidator([$this, 'notFound']);
        $vdt->test($idAlb, new Validate\Rule\NumNatural());
        $album = Album::findOrFail($idAlb);
        $album->delete();
        $this->flash('success', 'El album fue eliminado exitosamente.');
        $this->redirectTo('shwGaleria');
    }

    public function subirFoto() {
        $vdt = new Validate\Validator();
        $vdt->addRule('descripcion', new Validate\Rule\MaxLength(1000))
            ->addRule('album', new Validate\Rule\NumNatural())
            ->addRule('album', new Validate\Rule\Exists('albumes'))
            ->addOptional('descripcion');
        $req = $this->request;
        if (!$vdt->validate($req->post())) {
            throw new TurnbackException($vdt->getErrors());
        }

        $foto = new Foto;
        $foto->descripcion = $vdt->getData('descripcion');
        $foto->album_id = $vdt->getData('album');
        $foto->save();

        $pathOrig = __DIR__ . '/../public/img/galeria/'.$foto->album_id.'/'.$foto->id.'.jpg';
        $pathThum = __DIR__ . '/../public/img/galeria/'.$foto->album_id.'/thumbnail/'.$foto->id.'.jpg';
        if (isset($_FILES['foto'])) {
            $manager = new Intervention\Image\ImageManager(['driver' => 'imagick']);
            $imgOrig = $manager->make($_FILES['foto']['tmp_name'])
                ->resize(900, 900, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                })->save($pathOrig, 85);
            $imgThum = $manager->make($_FILES['foto']['tmp_name'])
                ->fit(400, 300, function ($constraint) {
//                ->resize(400, 300, function ($constraint) {
					$constraint->aspectRatio();
                    $constraint->upsize();
                })->save($pathThum, 80);
        } else {
            throw new TurnbackException('No seleccionó imagen.');
        }
        $this->flash('success', 'Foto subida exitosamente.');
        $this->redirectTo('shwAlbum', ['idAlb' => $foto->album_id]);
    }

    public function eliminarFoto($idFot) {
        $vdt = new Validate\QuickValidator([$this, 'notFound']);
        $vdt->test($idFot, new Validate\Rule\NumNatural());
        $foto = Foto::findOrFail($idFot);
        $idAlb = $foto->album_id;
        $pathOrig = __DIR__ . '/../public/img/galeria/'.$idAlb.'/'.$idFot.'.jpg';
        $pathThum = __DIR__ . '/../public/img/galeria/'.$idAlb.'/thumbnail/'.$idFot.'.jpg';
        if (file_exists($pathOrig)) unlink($pathOrig);
        if (file_exists($pathThum)) unlink($pathThum);
        $foto->delete();
        $this->flash('success', 'Foto eliminada exitosamente.');
        $this->redirectTo('shwAlbum', ['idAlb' => $idAlb]);
    }
}
