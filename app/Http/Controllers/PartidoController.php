<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use App\Http\Controllers\Controller;
use Validator;
use App\Equipo;
use App\Partido;


class PartidoController extends Controller
{
    public function getPartidos(){
            //obtengo la temporada actual
  

            //obtengo los datos de partido con jugar con la temporada actual
            $partidos = Partido::with('competicion','temporada',
            'equipoLocal','equipoVisitante','estadio')->get();

            return view('partidos', [
                  'partidos' => $partidos]);
      }



   public function getPartidosConfig(){
        $teams = Partido::join('equipo as team1','partido.equipoLocal','=','team1.id')->
                              join('equipo as team2','partido.equipoVisitante','=','team2.id')->
                              select('partido.*','team1.nombreEquipo as equipoLocal','team2.nombreEquipo as equipoVisitante')->paginate(5);

        return view('editarPartidos', [
                                 'values' => [
                                             'equipoLocal'=> 'Equipo Local',
                                             'golesLocal'=>'Goles Local',
                                             'golesVisitante'=>'Goles Visitante',
                                             'equipoVisitante'=> 'Equipo Visitante',
                                             'fecha'=>'Fecha',
                                             'tipo' => 'Tipo'],
                                 'lista' =>  $teams,
                                 ]
                    );

   }


   public function EliminarPartido($id){
        $partido = Partido::find($id);
        $partido->delete();
        return back();
   }


   public function formularioModificar($id){

        $equipos = Equipo::orderBy('nombreEquipo')->where('nombreEquipo','<>','Libre')->get();


        return view ('modificarPartido',[
                                            'idmodificar' => $id],[
                                            'listaEquipos' =>  $equipos
                                            ]);
   }

   public function formularioInsertar(){

        return view ('crearPartido',['listaEquipos' => Equipo::orderBy('nombreEquipo')->get()]);
   }



   public function modificarPartido(Request $request,$id){
       $partido = Partido::find($id);
       $partido->equipoLocal = $request->equipoLocal;
       $partido->equipoVisitante = $request->equipoVisitante;
       $partido->golesLocal = $request->golesLocal;
       $partido->golesVisitante = $request->golesVisitante;
       $partido->fecha = $request->fecha;
       $partido->tipo = $request->tipo;

       return $this->verErrores($partido,$request);
   }


    public function crearPartido(Request $request){
         $partido = new Partido();
         $partido->equipoLocal = $request->equipoLocal;
         $partido->equipoVisitante = $request->equipoVisitante;
         $partido->golesLocal = $request->golesLocal;
         $partido->golesVisitante = $request->golesVisitante;
         $partido->fecha = $request->fecha;
         $partido->tipo = $request->tipo;


         return $this->verErrores($partido,$request);

     }

     public function verErrores($partido,$request){

        if($partido->equipoLocal ==  $partido->equipoVisitante){
            $validator = Validator::make($request->all(), [
            'title' => '2',
            'body' => '2',
            ]);
            $validator->getMessageBag()->add('unique','Error, no se puede crear un partido con el mismo equipo.');
            return back()->withErrors($validator)->withInput();

        }else{

            try{
                $partido->save();
                return Redirect::to('/config/partidos');
            }
            catch(\Illuminate\Database\QueryException $e){
                $validator = Validator::make($request->all(), [
                'title' => '2',
                'body' => '2',
                ]);
                $validator->getMessageBag()->add('unique','Error, existe ya un partido con las mismas caracteristicas');
                return back()->withErrors($validator)->withInput();
            }
        }

    }
}
