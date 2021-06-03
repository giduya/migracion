<?php namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\l17_usuarios;
use App\Models\l17_declaracion;
use App\Models\l17_datos_generales;
use App\Models\l17_domicilio_declarante;
use App\Models\l17_datos_encargo;
use App\Models\l17_domicilio_encargo;
use App\Models\l17_experiencia_laboral_fila;
use App\Models\l17_escolaridad_fila;


use App\Models\User;
use App\Models\Declaracion;
use App\Models\general_data;
use App\Models\domiciles;
use App\Models\employments;
use App\Models\work_experiences;
use App\Models\scholarships;

class MigrarController extends Controller
{

  public function migrar(Request $request)
  {

    $usuarios_viejo = l17_usuarios::where('rol','=','declarante')->get();

    foreach($usuarios_viejo as $usuario_viejo)
    {
      $usuario_nuevo = new User;

      $usuario_nuevo->username = $usuario_viejo->rfc;
      $usuario_nuevo->homoclave = $usuario_viejo->homoclave;
      $usuario_nuevo->name = $usuario_viejo->nombre;
      $usuario_nuevo->firstname = $usuario_viejo->apellido_paterno;
      $usuario_nuevo->lastname = $usuario_viejo->apellido_materno;
      $usuario_nuevo->type = 'user';
      $usuario_nuevo->user_verified_at = null;
      $usuario_nuevo->password = '$2y$10$rbWXo4q.NP.QShFgataDJ.dyGEPwIPdUaAb4';
      $usuario_nuevo->remember_token = null;
      $usuario_nuevo->deleted_at = null;
      $usuario_nuevo->node_id = 1;
      $usuario_nuevo->rol = null;
      $usuario_nuevo->declarant_type = null;
      $usuario_nuevo->status = 'active';
      $usuario_nuevo->level = 'C';
      $usuario_nuevo->is_new = 'SI';
      $usuario_nuevo->save();

      $declaracion_viejo = l17_declaracion::where('rfc','=',$usuario_viejo->rfc)->where('fecha_sello','=',1)->first();

      if($declaracion_viejo != null)
      {
        $declaracion_nuevo = new Declaracion;
        $declaracion_nuevo->type = "MODIFICACION";
        $declaracion_nuevo->status = "initial";
        $declaracion_nuevo->current_section = "aclaraciones";
        $declaracion_nuevo->target_date = "2021-05-31";
        $declaracion_nuevo->user_id = $usuario_nuevo->id;
        $declaracion_nuevo->deleted_at = null;
        $declaracion_nuevo->sign_date	= null;
        $declaracion_nuevo->tracking ="datos-generales,domicilio-del-declarante,escolaridad,empleo-cargo-comision,experiencia-laboral,ingresos-netos,aclaraciones";
        $declaracion_nuevo->save();

        $datos_viejos = l17_datos_generales::where('declaracion_id','=',$declaracion_viejo->id)->first();
        $datos_nuevos = new general_data;
        if(!empty($datos_viejos->nombre))
        {
          $datos_nuevos->name = $datos_viejos->nombre;
          $datos_nuevos->firstname = $datos_viejos->primer_apellido;
          $datos_nuevos->lastname = $datos_viejos->segundo_apellido;
          $datos_nuevos->curp = $datos_viejos->curp;
          $datos_nuevos->rfc = $datos_viejos->rfc;
          $datos_nuevos->homoclave = $datos_viejos->homoclave;
          $datos_nuevos->email = $datos_viejos->email_personal;
          $datos_nuevos->email_2 = $datos_viejos->email_laboral;
          $datos_nuevos->phone = $datos_viejos->celular;
          $datos_nuevos->phone_2 = $datos_viejos->celular;
          $datos_nuevos->civil_status = $datos_viejos->estado_civil;
          $datos_nuevos->patrimonial_regime = $datos_viejos->regimen_patrimonial;
          $datos_nuevos->patrimonial_regime_description = $datos_viejos->null;
          $datos_nuevos->country_of_birth = $datos_viejos->pais;
          $datos_nuevos->nationality = $datos_viejos->nacionalidad;
          $datos_nuevos->clarification = null;
          $datos_nuevos->declaration_id = $declaracion_nuevo->id;
          $datos_nuevos->save();
        }

        $domicilio_viejos = l17_domicilio_declarante::where('declaracion_id','=',$declaracion_viejo->id)->first();
        if(!empty($domicilio_viejos->calle))
        {
          $datos_nuevos = new domiciles;
          $datos_nuevos->location = "EN MÉXICO";
          $datos_nuevos->street = $domicilio_viejos->calle;

          if(!isset($domicilio_viejos->numero_exterior))
          {
            $datos_nuevos->outdoor_number = "S/N";
          }
          else
          {
            $datos_nuevos->outdoor_number = $domicilio_viejos->numero_exterior;
          }
          $datos_nuevos->interior_number = $domicilio_viejos->numero_interior;
          $datos_nuevos->colony = $domicilio_viejos->colonia;
          $datos_nuevos->municipality = $domicilio_viejos->municipio;
          $datos_nuevos->federal_entity = $domicilio_viejos->estado;
          $datos_nuevos->city = null;
          $datos_nuevos->state = null;
          $datos_nuevos->country = null;
          $datos_nuevos->postal_code = $domicilio_viejos->cp;
          $datos_nuevos->clarification = null;
          $datos_nuevos->declaration_id = $declaracion_nuevo->id;
          $datos_nuevos->save();
        }
        $escolaridad_viejos = l17_escolaridad_fila::where('declaracion_id','=',$declaracion_viejo->id)->get();
//dd($escolaridad_viejos); exit;
        foreach($escolaridad_viejos as $datos)
        {
          $datos_nuevos = new scholarships;
          $datos_nuevos->level = $datos->nivel_educativo;
          $datos_nuevos->institution = $datos->institucion;
          $datos_nuevos->career = $datos->conocimiento;
          $datos_nuevos->status = $datos->estatus;
          $datos_nuevos->document = $datos->documento;
          $datos_nuevos->document_date = null;
          $datos_nuevos->institution_location = "EN MÉXICO";
          $datos_nuevos->period = $datos->no_periodos;
          $datos_nuevos->type_period = $datos->periodo;
          $datos_nuevos->clarification = null;
          $datos_nuevos->declaration_id = $declaracion_nuevo->id;
          $datos_nuevos->save();
        }

        $laboral_viejos = l17_experiencia_laboral_fila::where('declaracion_id','=',$declaracion_viejo->id)->get();
        foreach($laboral_viejos as $laboral_viejo)
        {
          $datos_nuevos = new work_experiences;
          $datos_nuevos->sector = $laboral_viejo->sector;
          $datos_nuevos->level = "EJECUTIVO";
          $datos_nuevos->ambit = "MUNICIPIO / ALCALDÍA";
          $datos_nuevos->institution = $laboral_viejo->institucion;
          $datos_nuevos->administrative_unit = $laboral_viejo->unidad;
          $datos_nuevos->company = $laboral_viejo->razon_social;
          $datos_nuevos->rfc = null;
          $datos_nuevos->area = $laboral_viejo->area;
          $datos_nuevos->position = $laboral_viejo->puesto_o_cargo;
          $datos_nuevos->main_activities = $laboral_viejo->funcion;
          $datos_nuevos->main_sector = $laboral_viejo->sector;
          $datos_nuevos->description_sector = null;
          $datos_nuevos->location = "EN MÉXICO";
          $datos_nuevos->start_date = $laboral_viejo->ingreso;
          if(empty($laboral_viejo->egreso))
          {
            $datos_nuevos->end_date = $laboral_viejo->egreso;
          }
          else
          {
            $datos_nuevos->end_date = "2018-04-02";
          }
          $datos_nuevos->clarification = null;
          $datos_nuevos->declaration_id = $declaracion_nuevo->id;
          $datos_nuevos->save();
        }

        $encargo_viejos = l17_datos_encargo::where('declaracion_id','=',$declaracion_viejo->id)->first();
        $domicilio_viejos = l17_domicilio_encargo::where('declaracion_id','=',$declaracion_viejo->id)->first();
        $datos_nuevos = new employments;

        if(!empty($encargo_viejos->area_adscripcion))
        {
          $datos_nuevos->government_level = "MUNICIPIO / ALCALDÍA";
          $datos_nuevos->public_ambit = "EJECUTIVO";
          $datos_nuevos->public_entity = "AYTO MARAVATIO";
          $datos_nuevos->ascription_area = $encargo_viejos->area_adscripcion;
          $datos_nuevos->employment = $encargo_viejos->nombre_empleo;
          $datos_nuevos->fee = $encargo_viejos->honorarios;
          if(empty($encargo_viejos->nivel_cargo))
          {
            $datos_nuevos->employment_level = "operativo";
          }
          else
          {
            $datos_nuevos->employment_level = $encargo_viejos->nivel_cargo;
          }
          $datos_nuevos->principal_function = "FUNCIONES DE AREA";
          if(empty($encargo_viejos->fecha))
          {
            $datos_nuevos->entry_date = "2018-08-31";
          }
          elseif($encargo_viejos->fecha == "0000-00-00")
          {
            $datos_nuevos->entry_date = "2018-08-31";
          }
          else
          {
            $datos_nuevos->entry_date = $encargo_viejos->fecha;
          }
          $datos_nuevos->office_phone = $domicilio_viejos->telefono;
          $datos_nuevos->office_phone_extension = $domicilio_viejos->extension;
          $datos_nuevos->location = "EN MÉXICO";
          $datos_nuevos->street = $domicilio_viejos->calle;
          $datos_nuevos->outdoor_number = $domicilio_viejos->numero_exterior;
          $datos_nuevos->interior_number = $domicilio_viejos->numero_interior;
          $datos_nuevos->colony = $domicilio_viejos->colonia;
          $datos_nuevos->municipality = $domicilio_viejos->municipio;
          $datos_nuevos->federal_entity = $domicilio_viejos->estado;
          $datos_nuevos->city = null;
          $datos_nuevos->state = null;
          $datos_nuevos->country = null;
          $datos_nuevos->postal_code = $domicilio_viejos->cp;
          $datos_nuevos->clarification = null;
          $datos_nuevos->declaration_id = $declaracion_nuevo->id;
          $datos_nuevos->save();
        }
      }

    }//foreach

  }//public migrar

}
