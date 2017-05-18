<?php use Illuminate\Database\Eloquent\Model as Eloquent;

class Album extends Eloquent {
    protected $table = 'albumes';
    protected $visible = ['id', 'nombre', 'descripcion', 'fotos'];
    protected $with = ['fotos'];

    public function fotos() {
        return $this->hasMany('Foto');
    }
}
