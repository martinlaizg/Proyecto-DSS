<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Temporada extends Model
{
      protected $table = 'temporada';
      protected $dates = ['fecha'];



      public function jugar(){
        return $this->belongsToMany('App\Jugar','jugar','id');
      } 
}
