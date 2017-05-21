<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Validator;
use Redirect;
use App\Usuario;
use App\Equipo;
use Auth;

use Intervention\Image\ImageManagerStatic as Image;

class UsuarioController extends Controller
{
      public function getUsuarios(Request $request){
            $equipo = $request->input('equipo','Todos');
            $rol = $request->input('rol','0');
            $cargo = $request->input('cargo','-1');
            $posicion = $request->input('posicion',0);
            $nombre = $request->input('nombre','Todos');

            if($rol != 3 ){
                  if($rol == -1){
                        $usuarios = Usuario::with('equipo');
                  }
                  else{
                        $usuarios = Usuario::with('equipo')->where('rol','=',$rol);
                  }
                  if($equipo != 'Todos'){
                        $usuarios = $usuarios->where('equipo_id','=',$equipo);
                  }
                  if($cargo>=0){
                        $usuarios = $usuarios->where('cargo','=',$cargo);
                  }
                  if($posicion != 0){
                        $usuarios = $usuarios->where('posicion','=',$posicion);
                  }
            }
            else {
                  $equipo = 'Todos';
                  $cargo = -1;
                  $posicion = 'Todas';
            }
            if($nombre != 'Todos'){
                  $usuarios = $usuarios->where('nombre','like','%'.$nombre.'%');
            }

            return view('usuarios',[
                  'usuarios' => $usuarios->paginate(18),
                  'equipo' => $equipo,
                  'rol' => $rol,
                  'cargo' => $cargo,
                  'posicion' => $posicion,
                  'equipos' => Equipo::get(),
                  'nombre' => $nombre
            ]);
      }

      public function getUsuario($id){
            return view('usuario',[
                  'usuario' => Usuario::with('equipo')->find($id)
            ]);
      }

      public function getFormUpdate($id){
            return view('config.usuario.modificar', [
                  'usuario' => Usuario::find($id)
            ]);
      }

      public function getFormCreate(){
            return view('config.usuario.crear', array(
                  'listaEquipos' => Equipo::orderBy('nombreEquipo')->get()
                  )
            );
      }

      public function getUsuariosUpdate(Request $request){
            $equipo = $request->input('equipo','Todos');
            $rol = $request->input('rol','0');
            $cargo = $request->input('cargo','-1');
            $posicion = $request->input('posicion',0);

            $usuarios = Usuario::with('equipo')->where('rol','=',$rol);
            if($rol < 3 ){
                  if($equipo != 'Todos'){
                        $usuarios = $usuarios->where('equipo_id','=',$equipo);
                  }
                  if($cargo>=0){
                        $usuarios = $usuarios->where('cargo','=',$cargo);
                  }
                  if($posicion != 0){
                        $usuarios = $usuarios->where('posicion','=',$posicion);
                  }
            }
            else {
                  $equipo = 'Todos';
                  $cargo = -1;
                  $posicion = 'Todas';
            }

            return view('config.usuario.listaModificar',[
                  'usuarios' => $usuarios->paginate(18),
                  'equipo' => $equipo,
                  'rol' => $rol,
                  'cargo' => $cargo,
                  'posicion' => $posicion,
                  'equipos' => Equipo::get()
            ]);
      }

      public function getConfig(){
            return view('config.configuracion');
      }

      public function create(Request $request){
            $validator = Validator::make($request->all(),[]);

            $usuario = new Usuario();
            $usuario->dni = $request->input('dni');
            $usuario->nombre = $request->input('nombre');
            $usuario->apellidos = $request->input('apellidos');
            $usuario->fNac = $request->input('fNac');

            $usuario->posicion = $request->input('posicion');
            $usuario->equipo_id = $request->input('equipo');
            $usuario->rol = $request->input('rol');

            $libre = Equipo::where('nombreEquipo','like','%Libre%')->first()->id;
            if($usuario->rol == 0){ //Jugador
                  if($request->equipo != $libre){
                        if(Usuario::where('equipo_id','=',$request->equipo)->where('dorsal','=',$request->dorsal)->first() != null){
                              $validator->getMessageBag()->add('dorsal','Existe jugador en el mismo equipo con ese dorsal');
                        }
                        else{
                              $usuario->dorsal = $request->dorsal;
                        }

                        if($request->cargo != 0 && Usuario::where('equipo_id','=',$request->equipo)->where('cargo','=',$request->cargo)->first() != null){
                              $validator->getMessageBag()->add('cargo','Existe jugador en el mismo equipo con ese cargo');
                        }
                        else{
                              $usuario->cargo = $request->cargo;
                        }
                  }
                  $usuario->posicion = $request->posicion;
            }
            elseif ($usuario->rol == 1) { //Entrenador
                  if($request->equipo != $libre){
                        if($request->cargo != 0 && Usuario::where('equipo_id','=',$request->equipo)->where('cargo','=',$request->cargo)->first() != null){
                              $validator->getMessageBag()->add('cargo','Existe entrenador en el mismo equipo con ese cargo');
                        }
                        else{
                              $usuario->cargo = $request->cargo;
                        }
                  }
            }
            if($usuario->rol < 3){
                  $usuario->salario = $request->input('salario');
            }
            if($request->password == $request->passwordCheck ){
                  $usuario->password = bcrypt($request->password);
            }
            else{
                  $validator->getMessageBag()->add('password','Las contraseñas deben de ser iguales');
            }
            if($request->foto != null ){
                  $usuario->foto = $request->foto;
            }
            if($validator->getMessageBag()->count() <= 0){
                  try {
                        $usuario->save();
                  } catch (\Illuminate\Database\QueryException $e) {
                        $validator->getMessageBag()->add('dni','Existe usuario con ese dni');
                  }
            }
            if($validator->getMessageBag()->count() > 0){
                  return Redirect::back()->withErrors($validator)->withInput($request->except('password'));
            }

            return redirect()->action('UsuarioController@getUsuario',[ 'id' => $usuario->id ]);
      }

      public function update(Request $request, $id){
            //Control de errores
            $errors = false;
            $validator = Validator::make($request->all(),[]);
            //Campos comunes
            $usuario = Usuario::find($id);
            $usuario->dni = $request->dni;
            $usuario->nombre = $request->nombre;
            $usuario->apellidos = $request->apellidos;
            $usuario->fNac = $request->fNac;
            $usuario->salario = $request->salario;
            if($request->equipo != null) $usuario->equipo_id = $request->equipo;
            $usuario->rol = $request->rol;

            if($usuario->rol == 0){ //Jugador
                  if(Usuario::where('equipo_id','=',$request->equipo)->where('dorsal','=',$request->dorsal)->where('id','<>',$id)->first() != null){
                        $validator->getMessageBag()->add('dorsal','Existe jugador en el mismo equipo con ese dorsal');
                  }
                  else{
                        $usuario->dorsal = $request->dorsal;
                  }

                  if($request->cargo != 0 && Usuario::where('equipo_id','=',$request->equipo)->where('cargo','=',$request->cargo)->where('id','<>',$id)->first() != null){
                        $validator->getMessageBag()->add('cargo','Existe jugador en el mismo equipo con ese cargo');
                  }
                  else{
                        $usuario->cargo = $request->cargo;
                  }
                  $usuario->posicion = $request->posicion;
            }
            elseif ($usuario->rol == 1) { //Entrenador
                  if($request->cargo != 0 && Usuario::where('equipo_id','=',$request->equipo)->where('cargo','=',$request->cargo)->where('id','<>',$id)->first() != null){

                        $validator->getMessageBag()->add('cargo','Existe entrenador en el mismo equipo con ese cargo');
                  }
                  else{
                        $usuario->cargo = $request->cargo;
                  }
            }
            if($request->password != null ){
                  $usuario->password = bcrypt($request->password);
            }
            if($request->foto != null ){
                  try{
                        Image::make($request->file('foto')->getRealPath())->fit(150)->encode('png')->save('images/users/'.$usuario->dni.'.png');
                        $usuario->foto = $usuario->dni . '.png';
                  }catch(\Intervention\Image\Exception\NotReadableException $e){
                        $validator->getMessageBag()->add('foto','Foto demasiado grande');
                  }
            }
            try {
                  $usuario->save();
            } catch (\Illuminate\Database\QueryException $e) {
                  $validator->getMessageBag()->add('dni','Existe usuario con ese dni');
            }


            if($validator->getMessageBag()->count()>0) return Redirect::back()->withErrors($validator);


            return redirect()->action('UsuarioController@getUsuario',[ 'id' => $id ]);//Probablemente ésto cambiará
      }

      public function delete($id){
            //Comprobamos que existe
            try{
                  $usuario = Usuario::findOrFail($id);
                  $usuario->delete();
            } catch (Illuminate\Database\Eloquent\ModelNotFoundException $excepcion){

            }
            return Redirect::back();
      }
}
