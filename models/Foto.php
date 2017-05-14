<?php use Illuminate\Database\Eloquent\Model as Eloquent;

class Foto extends Eloquent {
    protected $table = 'fotos';
    protected $visible = ['id', 'descripcion', 'album'];

    public function album() {
        return $this->belongsTo('Album');
    }
}
