<?php use Illuminate\Database\Eloquent\Model as Eloquent;

class Comentario extends Eloquent {
    use Illuminate\Database\Eloquent\SoftDeletingTrait;

    //protected $table = 'comentarios';
    protected $dates = ['deleted_at'];
    protected $visible = ['id', 'cuerpo', 'comentable_type', 'comentable_id', 'votos', 'karma',
                          'created_at', 'updated_at', 'autor', 'respuestas'];
    protected $with = ['autor', 'respuestas','votos'];
    protected $appends = ['karma'];
    
    public function scopeModifiableBy($query, $id) {
        return $query->where('autor_id', $id);
    }

    public function comentable() {
        return $this->morphTo();
    }

    public function autor() {
        return $this->belongsTo('Usuario');
    }

    public function respuestas() {
        return $this->morphMany('Comentario', 'comentable');
    }

    public function votos() {
        return $this->hasMany('VotoComentario');
    }

    public function getKarmaAttribute() {
        return $this->votos()->sum('valor');
    }

    public function getRaizAttribute() {
        return $this->comentable->raiz;
    }

    public static function boot() {
        parent::boot();
        static::deleting(function($comentario) {
            $answerIds = $comentario->respuestas()->lists('id');
            if ($answerIds) {
                VotoComentario::whereIn('comentario_id', $answerIds)->delete();
                $comentario->respuestas()->delete();
            }
            $comentario->votos()->delete();
            return true;
        });
    }

}
