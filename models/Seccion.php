<?php use Illuminate\Database\Eloquent\Model as Eloquent;

class Seccion extends Eloquent {
    protected $table = 'secciones';
    protected $visible = array('id', 'descripcion', 'comentarios', 'votos_baja', 'votos_media', 'votos_alta','derecho_id');
    protected $with = array('comentarios');
    protected $appends = array('votos_baja', 'votos_media', 'votos_alta');

    public function derecho() {
        return $this->belongsTo('Derecho');
    }

    public function comentarios() {
        return $this->morphMany('Comentario', 'comentable');
    }
    
    public function votos() {
        return $this->hasMany('VotoSeccion');
    }
    
    public function getVotosBajaAttribute() {
        return $this->votos()->where('postura', '1')->count();
    }
    
    public function getVotosMediaAttribute() {
        return $this->votos()->where('postura', '2')->count();
    }
    
    public function getVotosAltaAttribute() {
        return $this->votos()->where('postura', '3')->count();
    }

    public function getRaizAttribute() {
        return $this->derecho;
    }
}
