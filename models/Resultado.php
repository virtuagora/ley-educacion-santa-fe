<?php use Illuminate\Database\Eloquent\Model as Eloquent;

class Resultado extends Eloquent {
    protected $table = 'resultados';
    protected $visible = ['id', 'descripcion', 'testimonios', 'derecho_id'];

    public function derecho() {
        return $this->belongsTo('Derecho');
    }

    public function setTestimoniosAttribute($value) {
        $this->attributes['testimonios'] = json_encode($value);
    }

    public function getTestimoniosAttribute($value) {
        return json_decode($value, true);
    }

    public function getRaizAttribute() {
        return $this->derecho;
    }
}
