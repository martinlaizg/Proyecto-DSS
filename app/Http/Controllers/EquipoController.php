<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Redirect;
use App\Equipo;
use App\Partido;
use App\Estadio;
use App\Patrocinador;
use App\Participar;
use Carbon\Carbon;
use Validator;

class EquipoController extends Controller
{
      public function getHome(){

            $UA = Equipo::where('nombreEquipo','like','%UA%')->first();
            $ultLocal = $UA->partidosLocal()->where('fecha','<',Carbon::now())->get();
            $ultVisitante = $UA->partidosVisitante()->where('fecha','<',Carbon::now())->get();
            $proxLocal = $UA->partidosLocal()->where('fecha','>',Carbon::now())->get();
            $proxVisitante = $UA->partidosVisitante()->where('fecha','>',Carbon::now())->get();
            $ultimosPartidos = $ultLocal->merge($ultVisitante)->sortByDesc('fecha');
            //obtengo el ultimo partido
            $ultimoPartido = $ultimosPartidos->first();


            $participarTitular = Participar::with('usuario')
            ->where('partido_id','=', $ultimoPartido->id)
            ->where('asistencia','=',1)->get();




            $participarBanquillo = Participar::with('usuario')
            ->where('partido_id','=',$ultimoPartido->id)
            ->where('asistencia','=',2)->get();


            return view('home',[
                  'equipos' => Equipo::get(),
                  'estadios' => Estadio::get(),
                  'ultimoPartido' =>  $ultimoPartido,
                  'titulares' => $participarTitular,
                  'banquillo' => $participarBanquillo,
                  'ultPartidos' => $ultimosPartidos->take(5),
                  'proxPartidos' => $proxLocal->merge($proxVisitante)->sortBy('fecha')->take(5)
            ]);
      }

      public function configuracion(){
            return view('configuracion');
      }

      public function formulario(){
            return view('config.equipo.crear');
      }

      public function crearEquipo(Request $request){

            $estadio = new Estadio();
            $estadio->nombre = $request->input('estadioNombre');
            $estadio->capacidad = $request->input('aforo');

            $equipo = new Equipo(); //Si falla el crear equipo el Estadio sigue creado
            $equipo->cif = $request->input('cif');
            $equipo->nombreEquipo = $request->input('equipoNombre');
            $equipo->presupuesto = $request->input('presupuesto');
            $equipo->logo = '';
            //Por defecto se crea sin ningun patrocinador.
            $patrocinador = Patrocinador::where('nombre','=','libre')->first();
            $equipo->patrocinador_id = $patrocinador->id;
            //consigo el nuevo estadio

            return $this->captarErrores($estadio,$equipo,$request);
      }


      public function getEquipo($id){
            return view('equipo',[
                  'equipo' => Equipo::find($id)
            ]);
      }

      public function getEquipos(){
            //consigo todos los equipos con los estadios
            $team = Equipo::with('estadio','patrocinador')->paginate(10);

            return view('equipos',[
                  'lista' => $team
            ]);
      }

      public function editar(){
            $team = Equipo::with('estadio','patrocinador')->paginate(10);
            return view('config.equipo.editar',['lista' => $team]);
      }

      public function modificarEquipo($id){
            $equipo = Equipo::find($id);
            return view('config.equipo.modificar',[
                  'equipo' => $equipo,
                  'estadio' => $equipo->estadio()->first()
            ]);
      }


      public function modificarEquipoPost(Request $request, $id){
            $equipo = Equipo::find($id);
            $equipo->cif = $request->cif;
            $equipo->nombreEquipo = $request->nombreEquipo;
            $equipo->presupuesto = $request->presupuesto;

            $estadio = Estadio::find($equipo->estadio);
            $estadio->nombre = $request->nombre;
            $estadio->capacidad = $request->capacidad;


            return $this->captarErrores($estadio,$equipo,$request);

      }

      public function captarErrores($estadio,$equipo,$request){
            try{
                  //le pongo el valor aqui para poder hacer la condicion en el if
                  $estadio->save();
                  $equipo->estadio_id = Estadio::where('nombre','=', $estadio->nombre)->first()->id;
                  $equipo->save();
                  //creo partidos con el nuevo equipo

                  return Redirect::to('/partido');
            }
            catch(\Illuminate\Database\QueryException $e){
                  $validator = Validator::make($request->all(), [
                        'title' => '2',
                        'body' => '2',
                  ]);
                  $validator->getMessageBag()->add('unique','Error, el CIF introducido ya existe.');
                  return back()->withErrors($validator)->withInput();
            }
      }




      public function eliminar($id){
            $equipo = Equipo::find($id);
            $estadio = $equipo->estadio()->first();
            $estadio->delete();
            $equipo->delete();
            return back();
      }
}
