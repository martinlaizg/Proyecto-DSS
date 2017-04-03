<!-- Muestra nuestros jugadores de la base de datos -->
{{ $lista->links() }}
<table class="table table-striped table-responsive" cellspacing="0" width="100%">
      <thead>
            <tr>
                  @foreach($values as $key => $v)
                  @if($v == 'Fecha')
                  <th class="text-center">{{ $v }}</th>
                  @elseif($v == 'Equipo Visitante')
                  <th class="text-right">{{ $v }}</th>
                  @else
                  <th>{{ $v }}</th>
                  @endif
                  @endforeach
                  <th class="text-center">Acciones</th>
            </tr>
      </thead>
      <tbody>
            @foreach($lista as $elemento)
            <tr>
                  @foreach($values as $key => $v)
                  @if($v=='Goles Local' or $v=='Goles Visitante' or $v=='Fecha')
                  <td class="text-center">{!!$elemento[$key]!!}</td>
                  @elseif($v == 'Equipo Visitante')
                  <td class="text-right">{!!$elemento[$key]!!}</th>
                  @else
                  <td>{!!$elemento[$key]!!}</td>
                  @endif
                  @endforeach
                  <td>
                        <div class="row">
                                    <button href="{{ action('PartidoController@EliminarPartido', $elemento->id) }}" type="submit" class="btn btn-danger">Borrar</button>
                              <div class="col-md-6">
                              </div>
                                    <button type="submit" class="btn btn-warning btn-block">Modificar</button>
                              <div class="col-md-6">
                        </div>
                              </div>

                  </td>
            </tr>
            @endforeach
      </tbody>
</table>
