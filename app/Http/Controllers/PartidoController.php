<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use App\Http\Controllers\Controller;
use Validator;
use App\Equipo;
use App\Participar;
use App\Partido;
use App\Temporada;
use App\Estadio;
use App\Usuario;
use App\Competicion;


class PartidoController extends Controller
{
    public function getPartidos(Request $request){

        //Manejo de variables
        $equipo1 =  $request->input('equipo1','Todos');
        $equipo2 =  $request->input('equipo2','Todos');
        $temporadaFiltro =$request->input('temporada','temporadaActual'); 


        //Gestión de partidos where('rol','=',$rol)
        $partidos = null;
        //Condición 1: A contra B
        if($equipo1 != 'Todos' && $equipo2 != 'Todos'){
            $partidos = Partido::with('competicion','temporada',
                'equipoLocal','equipoVisitante','estadio')
                ->where([
                    ['equipoLocal_id','=',$equipo1],
                    ['equipoVisitante_id','=',$equipo2]
                    ])
                ->orWhere([
                    ['equipoLocal_id','=',$equipo2],
                    ['equipoVisitante_id','=',$equipo1]
                    ]);
        }
        //Condición 2: A contra todos ->orWhere('equipoLocal','=',$equipo1);

        else{
            $partidos = Partido::with('competicion','temporada',
                'equipoLocal','equipoVisitante','estadio');
        }
        
        
        return view('partidos', [
                'partidos' => $partidos->paginate(10),
                'equipos'  => Equipo::get(),
                'equipo1'  => $equipo1,
                'equipo2'  => $equipo2
                ]);
    }




    public function editarPartidos(){
        $partidos = Partido::with('competicion','temporada',
        'equipoLocal','equipoVisitante','estadio')->paginate(10);


        return view('config/partido/editarPartidos', [
                'partidos' => $partidos]);
    }

    public function eliminarPartido($id){
        $participar = Participar::where('partido_id','=',$id)->first();
        //si no hay datos de partido borra el partido directamente
        if($participar == null){
            $partido = Partido::find($id);
            $partido->delete();
            return Redirect::to("/config/editar/partidos");
        }else{
            $participar = Participar::where('partido_id','=',$id)->get();
            //borramos todas las tablas de participar con el partido asociado
            foreach($participar as $p){
                $aux = Participar::find($p->id);
                $aux ->delete();
            }
            $partido = Partido::find($id);
            $partido->delete();
            return Redirect::to("/config/editar/partidos");
        }
    }


    public function formularioInsertar(){

        $equipos = Equipo::where('nombreEquipo','<>','Libre')->get();
        $temporadas = Temporada::with('partido')->get();
        $competiciones = Competicion::with('partido')->get();

        return view ('config/partido/crearPartido',[ 'competiciones' => $competiciones,
        'equipos' => $equipos,'temporadas' => $temporadas]);
    }

    public function crearPartido(Request $request){
        $partido = new Partido();
 

        $partido->equipoLocal_id = $request->equipoLocal;
        $partido->equipoVisitante_id = $request->equipoVisitante;
        $partido->temporada_id = $request->temporada_id;
        $partido->competicion_id = $request->competicion_id;
        $partido->golesLocal = $request->golesLocal;
        $partido->golesVisitante = $request->golesVisitante;
        $partido->fecha = $request->fecha;

        $equipo = Equipo::find($request->equipoLocal);

        $partido->estadio_id = $equipo->estadio_id;

        return $this->verErrores($partido,$request,true);


    }


    public function modificarPartido(Request $request,$id){
        $partido = Partido::find($id);

        //miro si se ha modificado algun equipo 
        //para borrar los datos de modificarPartido

        if( $partido->equipoLocal_id ==  $request->equipoLocal
        &&  $partido->equipoVisitante_id ==  $request->equipoVisitante){
            $igual = true;
        }else{
            $igual = false;
        }
        
        $partido->equipoLocal_id = $request->equipoLocal;
        $partido->equipoVisitante_id = $request->equipoVisitante;
        $partido->temporada_id = $request->temporada_id;
        $partido->competicion_id = $request->competicion_id;
        $partido->golesLocal = $request->golesLocal;
        $partido->golesVisitante = $request->golesVisitante;
        $partido->fecha = $request->fecha;

        $equipo = Equipo::find($request->equipoLocal);

        $partido->estadio_id = $equipo->estadio_id;

        return $this->verErrores($partido,$request,$igual);


    }



    public function formularioModificar($id){

        $equipos = Equipo::where('nombreEquipo','<>','Libre')->get();
        $temporadas = Temporada::with('partido')->get();

        $competiciones = Competicion::with('partido')->get();

        return view ('config/partido/modificarPartido',[ 'competiciones' => $competiciones,
        'equipos' => $equipos,'temporadas' => $temporadas, 'idmodificar' => $id]);
    }


     public function verErrores($partido,$request,$igual){
        //si es el musmo equipo error
        if($partido->equipoLocal_id ==  $partido->equipoVisitante_id){
            $validator = Validator::make($request->all(), [
            'title' => '2',
            'body' => '2',
            ]);
            $validator->getMessageBag()->add('unique','Error, no se puede crear un partido con el mismo equipo.');
            return back()->withErrors($validator)->withInput();

        }else{

            try{
                $partido->save();
                if($igual == false){
                    $participar = Participar::where('partido_id','=',$partido->id)->get();
                    //borramos todas las tablas de participar con el partido asociado
                    foreach($participar as $p){
                        $aux = Participar::find($p->id);
                        $aux ->delete();
                    }
                }
                $valor= $partido->id;
                $valor=trim($valor);
                return Redirect::to("/config/partido/" . $valor);
                
            }
            //excecipon en la bbdd
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
