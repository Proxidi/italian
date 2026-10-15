<?php
 header("Content-Type: text/html; charset=utf-8");
defined('BASEPATH') OR exit('No direct script access allowed');

class Nominas extends CI_Controller {
    
    private $permisos; 

    public function __construct()
    {
        parent::__construct();
        $this->permisos=$this->backend_lib->control();             
        $this->load->model('Mdl_nominas');             
        $this->load->model('Mdl_plazas');  
        $this->load->model('Mdl_remesas'); 
        
        $this->load->library('encryption');
        if(!$this->session->userdata("login")){
            redirect(base_url());
        }
    }

    public function index(){       
        $this->load->view("layouts/header");
        $this->load->view("layouts/aside");
        $this->load->view("administrador/nominas/panel_nominas");        
        $this->load->view("layouts/footer");          
    }

    public function tabla(){
        $plazas=$this->Mdl_plazas->getPlazas();                                        
        $plaza_id=$this->input->post("plaza_id");      
        $usuario_id=$this->input->post("usuario_id");      
        $usuarios=$this->Mdl_nominas->getUsuarios($plaza_id);
        $fechaInicio=$this->input->post("fechaInicioD");
        $fechaFin=$this->input->post("fechaFinD"); 
        $id_plaza=$this->input->post("plaza_idP");            
        $dias=$this->Mdl_nominas->getDiasNominaCompleto(); 
        $conceptos=$this->Mdl_nominas->getConceptos();

        
        if($this->input->post("buscar")){                                
            if($fechaInicio==null){
                $lunes=$this->fechaLunes(date("Y-m-d"));
                $domingo=$this->fechaDomingo(date("Y-m-d")); 
                $numeroSemana=$this->getSemana(date("Y-m-d"));
                $lunesE=$this->getWeekDates(2026,$numeroSemana,1,true);
                $martesE=$this->getWeekDates(2026,$numeroSemana,2,true);
                $miercolesE=$this->getWeekDates(2026,$numeroSemana,3,true);
                $juevesE=$this->getWeekDates(2026,$numeroSemana,4,true);     
                $viernesE=$this->getWeekDates(2026,$numeroSemana,5,true);     
                $sabadoE=$this->getWeekDates(2026,$numeroSemana,6,true);     
                $domingoE=$this->getWeekDates(2026,$numeroSemana,7,true);  
            }
            else{
                $lunes=$this->fechaLunes($fechaInicio);
                $domingo=$this->fechaDomingo($fechaInicio);
                $numeroSemana=$this->getSemana($fechaInicio);
                $lunesE=$this->getWeekDates(2026,$numeroSemana,1,true);
                $martesE=$this->getWeekDates(2026,$numeroSemana,2,true);
                $miercolesE=$this->getWeekDates(2026,$numeroSemana,3,true);
                $juevesE=$this->getWeekDates(2026,$numeroSemana,4,true);     
                $viernesE=$this->getWeekDates(2026,$numeroSemana,5,true);     
                $sabadoE=$this->getWeekDates(2026,$numeroSemana,6,true);     
                $domingoE=$this->getWeekDates(2026,$numeroSemana,7,true);
            }

            $semanas=$this->Mdl_nominas->getRolSemanal($plaza_id,$lunes,$domingo);
            $matutinos=$this->Mdl_nominas->getRolMatutinoAdmin($plaza_id,$lunes,$domingo);
            $vespertinos=$this->Mdl_nominas->getRolVespertinoAdmin($plaza_id,$lunes,$domingo);
            $fechaInicio=$lunes;
            $fechaFin=$domingo;
        }
        else{            
            $lunes=$this->fechaLunes(date("Y-m-d"));
            $domingo=$this->fechaDomingo(date("Y-m-d"));

            $semanas=$this->Mdl_nominas->getRolSemanal($plaza_id,$lunes,$domingo);
            $matutinos=$this->Mdl_nominas->getRolMatutinoAdmin($plaza_id,$lunes,$domingo);
            $vespertinos=$this->Mdl_nominas->getRolVespertinoAdmin($plaza_id,$lunes,$domingo);
            $fechaInicio=$lunes;
            $fechaFin=$domingo;            

            $numeroSemana=$this->getSemana(date("Y-m-d"));
            $lunesE=$this->getWeekDates(2026,$numeroSemana,1,true);
            $martesE=$this->getWeekDates(2026,$numeroSemana,2,true);
            $miercolesE=$this->getWeekDates(2026,$numeroSemana,3,true);
            $juevesE=$this->getWeekDates(2026,$numeroSemana,4,true);     
            $viernesE=$this->getWeekDates(2026,$numeroSemana,5,true);     
            $sabadoE=$this->getWeekDates(2026,$numeroSemana,6,true);     
            $domingoE=$this->getWeekDates(2026,$numeroSemana,7,true);         
        }

        if($plaza_id==null){$plaza_id=0;}

            $data=
            array
                (
                    'fechainicio' => $fechaInicio,
                    'fechafin'    => $fechaFin,                
                    'usuarios'    => $usuarios,               
                    'plazas'      => $plazas,
                    'dias'        => $dias,
                    'conceptos'   => $conceptos,
                    'id_plaza'    => $plaza_id,                
                    'id_usuario'  =>$usuario_id,
                    "semanas"     => $semanas,
                    'lunesE'      => $lunesE,
                    'martesE'     => $martesE,
                    'miercolesE'  => $miercolesE,
                    'juevesE'     => $juevesE,
                    'viernesE'    => $viernesE,
                    'sabadoE'     => $sabadoE,
                    'domingoE'    => $domingoE        
                );

            $this->load->view("layouts/header");
            $this->load->view("layouts/aside");
            $this->load->view("administrador/nominas/tabla",$data);        
            $this->load->view("layouts/footer");  
    }

    public function roles(){
        $medicion=array('t'=>microtime(true),'q'=>count($this->db->queries));

        //VARIABLES DE INICIO
            $plazas=$this->Mdl_plazas->getPlazas();                                                                                           
            $plaza_id=$this->input->post("plaza_id");
            $lugares=$this->Mdl_nominas->getLugares();
            $usuarios=$this->Mdl_nominas->getUsuarios($plaza_id);   
            $turnos=$this->Mdl_nominas->getTurnosNomina();             
            $fechaInicio=$this->input->post("fechaInicioD");
            $fechaFin=$this->input->post("fechaFinD");                     
            $id_lugar=$this->input->post("lugar_id_busqueda");          
            $id_turno=$this->input->post("turno_id_busqueda");  
            $bandera=0;     
            $data=array();
        //VARIABLES DE INICIO
        
        $nombreLugar="";
        $semanas="";

        
        if($this->input->post("buscar"))
        {
            $data = $this->_prepararBusquedaSemanalRoles(
                $plazas,$plaza_id,$lugares,$usuarios,$turnos,$fechaInicio,$fechaFin,$id_lugar,$id_turno
            );
        }
        else if($this->input->post("buscarRango")){
            $data = $this->_prepararBusquedaRangoRoles(
                $plazas,$plaza_id,$lugares,$usuarios,$turnos,$fechaInicio,$fechaFin,$id_lugar,$id_turno
            );
        }
        else
        {
            $data = $this->_prepararRolesIniciales(
                $plazas,$plaza_id,$id_lugar,$id_turno,$usuarios,$turnos,$lugares,$semanas
            );
        }

        $this->_logMedicionRoles('datos',$medicion,$data);

        $this->load->view("layouts/header");
        $this->load->view("layouts/aside");
        $this->load->view("administrador/nominas/roles",$data);
        $this->load->view("layouts/footer");

        $this->_logMedicionRoles('total',$medicion,$data);
    }

    // MEDICION TEMPORAL: resume tiempo y consultas de roles() desde $medicion hasta ahora.
    // 'datos' = hasta tener listos los datos; 'total' = incluye render de vistas.
    // No registra SQL ni datos de empleados. Retirar al terminar las pruebas.
    private function _logMedicionRoles($etapa,$medicion,$data){
        $tiempos=array_slice($this->db->query_times,$medicion['q']);
        log_message('error',sprintf(
            '[MEDICION roles] etapa=%s bandera=%s plaza=%s lugar=%s turno=%s consultas=%d sql_ms=%.1f max_consulta_ms=%.1f total_ms=%.1f',
            $etapa,
            isset($data['bandera']) ? $data['bandera'] : '-',
            var_export($this->input->post("plaza_id"),true),
            var_export($this->input->post("lugar_id_busqueda"),true),
            var_export($this->input->post("turno_id_busqueda"),true),
            count($tiempos),
            array_sum($tiempos)*1000,
            ($tiempos ? max($tiempos) : 0)*1000,
            (microtime(true)-$medicion['t'])*1000
        ));
    }

    private function _prepararBusquedaRangoRoles(
        $plazas,$plaza_id,$lugares,$usuarios,$turnos,$fechaInicio,$fechaFin,$id_lugar,$id_turno
    ){
        $plazasReporte = array(
            1=>'Casitas',2=>'Matamoros',3=>'Oaxaca',4=>'PozaRica',
            5=>'ReynosaI',6=>'ReynosaII',7=>'Teziutlan'
        );
        $lugaresReporte = array(1=>'Barra',2=>'Mesas',3=>'Cocina',4=>'Apoyo');
        $data = array(
            'fechaInicio'=>$fechaInicio,
            'fechaFin'=>$fechaFin,
            'plazas'=>$plazas,
            'id'=>$plaza_id,
            'id_lugar'=>$id_lugar,
            'id_turno'=>$id_turno,
            'usuarios'=>$usuarios
        );

        if($id_lugar==null){
            $bandera=($plaza_id==null) ? 5 : 7;
            $numeroSemana=$this->getSemana(date("Y-m-d"));
            $etiquetas=$this->_getEtiquetasSemanaRoles($numeroSemana);

            // Conservar las claves y el orden de las lecturas por lugar y plaza.
            foreach($lugaresReporte as $lugarId=>$lugarNombre){
                if($plaza_id==null){
                    foreach($plazasReporte as $plazaId=>$plazaNombre){
                        $data[strtolower($lugarNombre).$plazaNombre]=$this->Mdl_nominas->getRangoLugarPlaza($plazaId,$fechaInicio,$fechaFin,$lugarId)->Total;
                    }
                }else{
                    $data[strtolower($lugarNombre).'GenericoRango']=$this->Mdl_nominas->getRangoLugarPlaza($plaza_id,$fechaInicio,$fechaFin,$lugarId)->Total;
                }
            }

            if($plaza_id==null){
                foreach($plazasReporte as $plazaId=>$plazaNombre){
                    $data['acumuladoFilaRango'.$plazaNombre]=$this->Mdl_nominas->getTotalFilaPlaza($plazaId,$fechaInicio,$fechaFin)->Total;
                }
            }else{
                $data['acumuladoFilaRangoGenerico']=$this->Mdl_nominas->getTotalFilaPlaza($plaza_id,$fechaInicio,$fechaFin)->Total;
            }

            // Estas lecturas generales también existían al seleccionar una plaza,
            // aunque esa rama no enviaba sus resultados a la vista.
            foreach($lugaresReporte as $lugarId=>$lugarNombre){
                $total=$this->Mdl_nominas->getTotalColumnaLugar($fechaInicio,$fechaFin,$lugarId)->Total;
                if($plaza_id==null){
                    $data['acumuladoTotalColumna'.$lugarNombre]=$total;
                }
            }
            $total=$this->Mdl_nominas->getTotalRangoRol($fechaInicio,$fechaFin)->Total;
            if($plaza_id==null){
                $data['acumuladoTotalRango']=$total;
            }

            $data['turnos']=$turnos;
            $data['lugares']=$lugares;
            $dias=array('lunes','martes','miercoles','jueves','viernes','sabado','domingo');
            foreach($dias as $indice=>$dia){
                $data[$dia.'E']=$etiquetas[$indice];
            }
        }else{
            $bandera=($plaza_id==null) ? 6 : 8;
            $nombreLugar=$this->Mdl_nominas->getNombreLugar($id_lugar)->Nombre;

            // La búsqueda por lugar conserva el alcance original: las siete plazas.
            foreach($plazasReporte as $plazaId=>$plazaNombre){
                $data['lugar'.$plazaNombre]=$this->Mdl_nominas->getRangoLugarPlaza($plazaId,$fechaInicio,$fechaFin,$id_lugar)->Total;
            }
            $data['acumuladoLugar']=$this->Mdl_nominas->getTotalColumnaLugar($fechaInicio,$fechaFin,$id_lugar)->Total;
            $data['nombreLugar']=$nombreLugar;
            $data['turnos']=$turnos;
            $data['lugares']=$lugares;
        }

        $data['bandera']=$bandera;
        $data['semanas']="";
        return $data;
    }

    private function _prepararBusquedaSemanalRoles(
        $plazas,$plaza_id,$lugares,$usuarios,$turnos,$fechaInicio,$fechaFin,$id_lugar,$id_turno
    ){
        $semanas="";
        $plazasReporte=array(
            1=>'Casitas',2=>'Matamoros',3=>'Oaxaca',4=>'PozaRica',
            5=>'ReynosaI',6=>'ReynosaII',7=>'Teziutlan'
        );
        $lugaresReporte=array(1=>'Barra',2=>'Mesas',3=>'Cocina',4=>'Apoyo');
        $diasReporte=array(
            1=>'Lunes',2=>'Martes',3=>'Miercoles',4=>'Jueves',
            5=>'Viernes',6=>'Sabado',7=>'Domingo'
        );

        // Todas las plazas y todos los lugares.
        if($plaza_id==null && $id_lugar==null){
            $id_turno="";
            $bandera=0;
            $lunes=$this->fechaLunes($fechaInicio);
            $domingo=$this->fechaDomingo($fechaInicio);
            $fechaInicio=$lunes;
            $fechaFin=$domingo;
            $data=array(
                'fechaInicio'=>$fechaInicio,
                'fechaFin'=>$fechaFin,
                'plazas'=>$plazas,
                'id'=>$plaza_id,
                'id_lugar'=>$id_lugar,
                'id_turno'=>$id_turno,
                'usuarios'=>$usuarios
            );

            // Primero los nombres; conservar matutino y vespertino por lugar.
            foreach($plazasReporte as $plazaId=>$plazaNombre){
                foreach($lugaresReporte as $lugarId=>$lugarNombre){
                    $data['matutinos'.$lugarNombre.$plazaNombre]=$this->Mdl_nominas->getRolMatutino($plazaId,$lunes,$domingo,$lugarId);
                    $data['vespertinos'.$lugarNombre.$plazaNombre]=$this->Mdl_nominas->getRolVespertino($plazaId,$lunes,$domingo,$lugarId);
                }
            }

            // Después los totales diarios y por lugar de cada plaza.
            foreach($plazasReporte as $plazaId=>$plazaNombre){
                foreach($diasReporte as $diaId=>$diaNombre){
                    $data['ac'.$diaNombre.$plazaNombre]=$this->Mdl_nominas->getTotalDia($plazaId,$lunes,$domingo,$diaId)->Total;
                }
                foreach($lugaresReporte as $lugarId=>$lugarNombre){
                    $data['ac'.$lugarNombre.$plazaNombre]=$this->Mdl_nominas->getTotalRol($plazaId,$lunes,$domingo,$lugarId)->Total;
                }
            }

            $numeroSemana=$this->getSemana($fechaInicio);
            $etiquetas=$this->_getEtiquetasSemanaRoles($numeroSemana);
            $data['turnos']=$turnos;
            $data['lugares']=$lugares;
            foreach($diasReporte as $diaId=>$diaNombre){
                $data[strtolower($diaNombre).'E']=$etiquetas[$diaId-1];
            }
            $data['bandera']=$bandera;
            $data['semanas']=$semanas;
            return $data;
        }

        // Todas las plazas, un lugar y ambos turnos.
        if($plaza_id==null && ($id_turno==null || $id_turno==3)){
            $bandera=2;
            $nombreLugar=$this->Mdl_nominas->getNombreLugar($id_lugar)->Nombre."<br>"."Matutito-Vespertino";
            if($fechaInicio==null){
                $lunes=$this->fechaLunes(date("Y-m-d"));
                $domingo=$this->fechaDomingo(date("Y-m-d"));
                $numeroSemana=$this->getSemana(date("Y-m-d"));
            }else{
                $lunes=$this->fechaLunes($fechaInicio);
                $domingo=$this->fechaDomingo($fechaInicio);
                $numeroSemana=$this->getSemana($fechaInicio);
            }
            $etiquetas=$this->_getEtiquetasSemanaRoles($numeroSemana);
            $data=array(
                'fechaInicio'=>$lunes,
                'fechaFin'=>$domingo,
                'plazas'=>$plazas,
                'id'=>$plaza_id,
                'id_lugar'=>$id_lugar,
                'nombreLugar'=>$nombreLugar,
                'id_turno'=>$id_turno,
                'usuarios'=>$usuarios,
                'turnos'=>$turnos,
                'lugares'=>$lugares
            );

            // Conservar primero los siete días matutinos y después los vespertinos.
            foreach($plazasReporte as $plazaId=>$plazaNombre){
                foreach($diasReporte as $diaId=>$diaNombre){
                    $data['matutinos'.$plazaNombre.$diaNombre]=$this->Mdl_nominas->getTotalRolMatutino($plazaId,$lunes,$domingo,$id_lugar,$diaId)->Total;
                }
                foreach($diasReporte as $diaId=>$diaNombre){
                    $data['vespertinos'.$plazaNombre.$diaNombre]=$this->Mdl_nominas->getTotalRolVespertino($plazaId,$lunes,$domingo,$id_lugar,$diaId)->Total;
                }
            }
            foreach($plazasReporte as $plazaId=>$plazaNombre){
                $data['acumuladoFila'.$plazaNombre]=$this->Mdl_nominas->getTotalFilaLugar($plazaId,$lunes,$domingo,$id_lugar)->Total;
            }
            foreach($diasReporte as $diaId=>$diaNombre){
                $data['acumuladoColumna'.$diaNombre.'Matutino']=$this->Mdl_nominas->getTotalColumnaSubTotalTurno(1,$lunes,$domingo,$id_lugar,$diaId)->Total;
                $data['acumuladoColumna'.$diaNombre.'Vespertino']=$this->Mdl_nominas->getTotalColumnaSubTotalTurno(2,$lunes,$domingo,$id_lugar,$diaId)->Total;
            }
            foreach($diasReporte as $diaId=>$diaNombre){
                $data['acumuladoTotalColumna'.$diaNombre]=$this->Mdl_nominas->getTotalColumnaDia($lunes,$domingo,$id_lugar,$diaId)->Total;
            }
            $data['acumuladoTotalSemanaRol']=$this->Mdl_nominas->getTotalSemanaRol($lunes,$domingo,$id_lugar)->Total;
            $fechaInicio=$lunes;
            $fechaFin=$domingo;
            foreach($diasReporte as $diaId=>$diaNombre){
                $data[strtolower($diaNombre).'E']=$etiquetas[$diaId-1];
            }
            $data['bandera']=$bandera;
            $data['semanas']=$semanas;
            return $data;
        }

        // Todas las plazas, un lugar y un turno específico.
        if($plaza_id==null){
            $nombreTurno=$this->Mdl_nominas->getNombreTurno($id_turno)->Nombre;
            $nombreLugar=$this->Mdl_nominas->getNombreLugar($id_lugar)->Nombre."-".$nombreTurno;
            $bandera=3;
            if($fechaInicio==null){
                $lunes=$this->fechaLunes(date("Y-m-d"));
                $domingo=$this->fechaDomingo(date("Y-m-d"));
                $numeroSemana=$this->getSemana(date("Y-m-d"));
            }else{
                $lunes=$this->fechaLunes($fechaInicio);
                $domingo=$this->fechaDomingo($fechaInicio);
                $numeroSemana=$this->getSemana($fechaInicio);
            }
            $etiquetas=$this->_getEtiquetasSemanaRoles($numeroSemana);
            $fechaInicio=$lunes;
            $fechaFin=$domingo;
            $data=array(
                'fechaInicio'=>$fechaInicio,
                'fechaFin'=>$fechaFin,
                'plazas'=>$plazas,
                'id'=>$plaza_id,
                'id_lugar'=>$id_lugar,
                'id_turno'=>$id_turno,
                'usuarios'=>$usuarios,
                'nombreLugar'=>$nombreLugar
            );

            // Conservar las lecturas por plaza y día antes de los acumulados.
            foreach($plazasReporte as $plazaId=>$plazaNombre){
                foreach($diasReporte as $diaId=>$diaNombre){
                    $data[$plazaNombre.$diaNombre]=$this->Mdl_nominas->getTotalRolLugarDia($plazaId,$lunes,$domingo,$id_lugar,$diaId,$id_turno)->Total;
                }
            }
            foreach($plazasReporte as $plazaId=>$plazaNombre){
                $data['acumuladoFila'.$plazaNombre.'TurnoLugar']=$this->Mdl_nominas->getTotalFilaRolLugar($plazaId,$lunes,$domingo,$id_lugar,$id_turno)->Total;
            }
            foreach($diasReporte as $diaId=>$diaNombre){
                $data['acumuladoTotalColumna'.$diaNombre.'TurnoLugar']=$this->Mdl_nominas->getTotalColumnaDiaTurnoLugar($lunes,$domingo,$id_lugar,$diaId,$id_turno)->Total;
            }
            $data['acumuladoTotalColumnaSemanaTurnoLugar']=$this->Mdl_nominas->getTotalSemanaTurnoLugar($lunes,$domingo,$id_lugar,$id_turno)->Total;
            $data['turnos']=$turnos;
            $data['lugares']=$lugares;
            foreach($diasReporte as $diaId=>$diaNombre){
                $data[strtolower($diaNombre).'E']=$etiquetas[$diaId-1];
            }
            $data['bandera']=$bandera;
            $data['semanas']=$semanas;
            return $data;
        }

        // Una plaza y todos los lugares.
        if($id_lugar==null){
            $bandera=4;
            if($fechaInicio==null){
                $lunes=$this->fechaLunes(date("Y-m-d"));
                $domingo=$this->fechaDomingo(date("Y-m-d"));
                $numeroSemana=$this->getSemana(date("Y-m-d"));
            }else{
                $lunes=$this->fechaLunes($fechaInicio);
                $domingo=$this->fechaDomingo($fechaInicio);
                $numeroSemana=$this->getSemana($fechaInicio);
            }
            $etiquetas=$this->_getEtiquetasSemanaRoles($numeroSemana);

            // Conservar los conteos antes de las lecturas de nombres.
            $acumulados=array();
            foreach($diasReporte as $diaId=>$diaNombre){
                $acumulados['ac'.$diaNombre]=$this->Mdl_nominas->getTotalDia($plaza_id,$lunes,$domingo,$diaId)->Total;
            }
            foreach($lugaresReporte as $lugarId=>$lugarNombre){
                $acumulados['ac'.$lugarNombre]=$this->Mdl_nominas->getTotalRol($plaza_id,$lunes,$domingo,$lugarId)->Total;
            }
            $roles=array();
            foreach($lugaresReporte as $lugarId=>$lugarNombre){
                $roles['matutinos'.$lugarNombre]=$this->Mdl_nominas->getRolMatutino($plaza_id,$lunes,$domingo,$lugarId);
                $roles['vespertinos'.$lugarNombre]=$this->Mdl_nominas->getRolVespertino($plaza_id,$lunes,$domingo,$lugarId);
            }
            $fechaInicio=$lunes;
            $fechaFin=$domingo;
            $nombrePlaza=$this->Mdl_plazas->getPlazaID($plaza_id)->nombrePlaza;

            // La vista conserva sus claves y el orden original de los datos.
            $data=array(
                'fechaInicio'=>$fechaInicio,
                'fechaFin'=>$fechaFin,
                'plazas'=>$plazas,
                'id'=>$plaza_id,
                'id_lugar'=>$id_lugar,
                'id_turno'=>$id_turno,
                'usuarios'=>$usuarios,
                'nombrePlaza'=>$nombrePlaza
            );
            $data=$data+$roles+$acumulados;
            $data['turnos']=$turnos;
            $data['lugares']=$lugares;
            foreach($diasReporte as $diaId=>$diaNombre){
                $data[strtolower($diaNombre).'E']=$etiquetas[$diaId-1];
            }
            $data['bandera']=$bandera;
            $data['semanas']=$semanas;
            return $data;
        }

        // Una plaza y un lugar específico.
        $nombreLugar=$this->Mdl_nominas->getNombreLugar($id_lugar)->Nombre;
        $nombrePlaza=$this->Mdl_plazas->getPlazaID($plaza_id)->nombrePlaza;
        $bandera=1;
        if($fechaInicio==null){
            $lunes=$this->fechaLunes(date("Y-m-d"));
            $domingo=$this->fechaDomingo(date("Y-m-d"));
            $numeroSemana=$this->getSemana(date("Y-m-d"));
        }else{
            $lunes=$this->fechaLunes($fechaInicio);
            $domingo=$this->fechaDomingo($fechaInicio);
            $numeroSemana=$this->getSemana($fechaInicio);
        }
        $etiquetas=$this->_getEtiquetasSemanaRoles($numeroSemana);
        $fechaInicio=$lunes;
        $fechaFin=$domingo;
        $data=array(
            'fechaInicio'=>$fechaInicio,
            'fechaFin'=>$fechaFin,
            'plazas'=>$plazas,
            'id'=>$plaza_id,
            'id_lugar'=>$id_lugar,
            'id_turno'=>$id_turno,
            'usuarios'=>$usuarios
        );
        foreach($diasReporte as $diaId=>$diaNombre){
            $data['ac'.$diaNombre]=$this->Mdl_nominas->getTotalDiaGenerico($plaza_id,$lunes,$domingo,$diaId,$id_lugar)->Total;
        }
        $acGenerico=$this->Mdl_nominas->getTotalRol($plaza_id,$lunes,$domingo,$id_lugar)->Total;
        $data['matutinosGenerico']=$this->Mdl_nominas->getRolMatutino($plaza_id,$lunes,$domingo,$id_lugar);
        // Conservar la llamada matutina del listado vespertino hasta revisar su corrección.
        $data['vespertinosGenerico']=$this->Mdl_nominas->getRolMatutino($plaza_id,$lunes,$domingo,$id_lugar);
        $data['acGenerico']=$acGenerico;
        $data['nombreLugar']=$nombreLugar;
        $data['nombrePlaza']=$nombrePlaza;
        $data['turnos']=$turnos;
        $data['lugares']=$lugares;
        foreach($diasReporte as $diaId=>$diaNombre){
            $data[strtolower($diaNombre).'E']=$etiquetas[$diaId-1];
        }
        $data['bandera']=$bandera;
        $data['semanas']=$semanas;
        return $data;
    }

    private function _prepararRolesIniciales($plazas,$plaza_id,$id_lugar,$id_turno,$usuarios,$turnos,$lugares,$semanas){
        $lunes = $this->fechaLunes(date("Y-m-d"));
        $domingo = $this->fechaDomingo(date("Y-m-d"));
        // Los sufijos conservan los nombres de datos que espera la vista actual.
        $plazasReporte = array(
            1 => 'Casitas',
            2 => 'Matamoros',
            3 => 'Oaxaca',
            4 => 'PozaRica',
            5 => 'ReynosaI',
            6 => 'ReynosaII',
            7 => 'Teziutlan'
        );
        $lugaresReporte = array(1 => 'Barra',2 => 'Mesas',3 => 'Cocina',4 => 'Apoyo');
        $diasReporte = array(1 => 'Lunes',2 => 'Martes',3 => 'Miercoles',4 => 'Jueves',5 => 'Viernes',6 => 'Sabado',7 => 'Domingo');

        $data = array(
            'fechaInicio' => $lunes,
            'fechaFin' => $domingo,
            'plazas' => $plazas,
            'id' => $plaza_id,
            'id_lugar' => $id_lugar,
            'id_turno' => $id_turno,
            'usuarios' => $usuarios
        );

        foreach ($plazasReporte as $plazaId => $plazaNombre) {
            foreach ($lugaresReporte as $lugarId => $lugarNombre) {
                $data['matutinos'.$lugarNombre.$plazaNombre] = $this->Mdl_nominas->getRolMatutino($plazaId,$lunes,$domingo,$lugarId);
                $data['vespertinos'.$lugarNombre.$plazaNombre] = $this->Mdl_nominas->getRolVespertino($plazaId,$lunes,$domingo,$lugarId);
            }
        }

        foreach ($plazasReporte as $plazaId => $plazaNombre) {
            foreach ($diasReporte as $diaId => $diaNombre) {
                $data['ac'.$diaNombre.$plazaNombre] = $this->Mdl_nominas->getTotalDia($plazaId,$lunes,$domingo,$diaId)->Total;
            }
            foreach ($lugaresReporte as $lugarId => $lugarNombre) {
                $data['ac'.$lugarNombre.$plazaNombre] = $this->Mdl_nominas->getTotalRol($plazaId,$lunes,$domingo,$lugarId)->Total;
            }
        }

        $numeroSemana = $this->getSemana(date("Y-m-d"));
        $etiquetas = $this->_getEtiquetasSemanaRoles($numeroSemana);
        $data['turnos'] = $turnos;
        $data['lugares'] = $lugares;
        foreach ($diasReporte as $diaId => $diaNombre) {
            $data[strtolower($diaNombre).'E'] = $etiquetas[$diaId-1];
        }
        $data['bandera'] = 0;
        $data['semanas'] = $semanas;
        return $data;
    }

    private function _getEtiquetasSemanaRoles($numeroSemana){
        $etiquetas = array();
        for ($dia = 1; $dia <= 7; $dia++) {
            $etiquetas[] = $this->getWeekDates(2026,$numeroSemana,$dia,true);
        }
        return $etiquetas;
    }

    public function correccionRoles(){

        $plazas=$this->Mdl_plazas->getPlazas();   
        $roles=$this->Mdl_nominas->getRoles();  
       
        $activos=$this->Mdl_nominas->getActivos();
        $nominas=$this->Mdl_remesas->getInsumosID(15);
        $turnos=$this->Mdl_nominas->getTurnosNomina();
                       
        $dias=$this->Mdl_nominas->getDiasNominaCompleto();
        $conceptos=$this->Mdl_nominas->getConceptos();

        $plaza_id=$this->input->post("plaza_id");

        $usuarios=$this->Mdl_nominas->getUsuarios($plaza_id);
        $lugares=$this->Mdl_nominas->getLugares();
        $days=$this->asignarDias2();

        $fechaInicio=$this->input->post("fechaInicioD");
        $fechaFin=$this->input->post("fechaFinD"); 
        $id_plaza=$this->input->post("plaza_idP");      
        
        $lunes=$this->fechaLunes(date("Y-m-d"));
        $domingo=$this->fechaDomingo(date("Y-m-d"));
        $estado=0;

        
        $semanas=$this->Mdl_nominas->getRolSemanal($plaza_id,$lunes,$domingo);                

        $matutinosBarra=$this->Mdl_nominas->getRolMatutino($plaza_id,$lunes,$domingo,1);
        $vespertinosBarra=$this->Mdl_nominas->getRolVespertino($plaza_id,$lunes,$domingo,1);

        $matutinosMesas=$this->Mdl_nominas->getRolMatutino($plaza_id,$lunes,$domingo,2);
        $vespertinosMesas=$this->Mdl_nominas->getRolVespertino($plaza_id,$lunes,$domingo,2);

        $matutinosCocina=$this->Mdl_nominas->getRolMatutino($plaza_id,$lunes,$domingo,3);
        $vespertinosCocina=$this->Mdl_nominas->getRolVespertino($plaza_id,$lunes,$domingo,3);

        $matutinosApoyo=$this->Mdl_nominas->getRolMatutino($plaza_id,$lunes,$domingo,4);
        $vespertinosApoyo=$this->Mdl_nominas->getRolVespertino($plaza_id,$lunes,$domingo,4);
        

        if($this->input->post("buscar")){
            if($fechaInicio==null){
                $lunes=$this->fechaLunes(date("Y-m-d"));
                $domingo=$this->fechaDomingo(date("Y-m-d")); 
                $numeroSemana=$this->getSemana(date("Y-m-d"));
                $lunesE=$this->getWeekDates(2026,$numeroSemana,1,true);
                $martesE=$this->getWeekDates(2026,$numeroSemana,2,true);
                $miercolesE=$this->getWeekDates(2026,$numeroSemana,3,true);
                $juevesE=$this->getWeekDates(2026,$numeroSemana,4,true);     
                $viernesE=$this->getWeekDates(2026,$numeroSemana,5,true);     
                $sabadoE=$this->getWeekDates(2026,$numeroSemana,6,true);     
                $domingoE=$this->getWeekDates(2026,$numeroSemana,7,true);     
            }else{                
                $lunes=$this->fechaLunes($fechaInicio);
                $domingo=$this->fechaDomingo($fechaInicio);
                $numeroSemana=$this->getSemana($fechaInicio);
                $lunesE=$this->getWeekDates(2026,$numeroSemana,1,true);
                $martesE=$this->getWeekDates(2026,$numeroSemana,2,true);
                $miercolesE=$this->getWeekDates(2026,$numeroSemana,3,true);
                $juevesE=$this->getWeekDates(2026,$numeroSemana,4,true);     
                $viernesE=$this->getWeekDates(2026,$numeroSemana,5,true);     
                $sabadoE=$this->getWeekDates(2026,$numeroSemana,6,true);     
                $domingoE=$this->getWeekDates(2026,$numeroSemana,7,true);     

            }

                $semanas=$this->Mdl_nominas->getRolSemanal($plaza_id,$lunes,$domingo);                                

                $matutinosBarra=$this->Mdl_nominas->getRolMatutino($plaza_id,$lunes,$domingo,1);
                $vespertinosBarra=$this->Mdl_nominas->getRolVespertino($plaza_id,$lunes,$domingo,1);

                $matutinosMesas=$this->Mdl_nominas->getRolMatutino($plaza_id,$lunes,$domingo,2);
                $vespertinosMesas=$this->Mdl_nominas->getRolVespertino($plaza_id,$lunes,$domingo,2);

                $matutinosCocina=$this->Mdl_nominas->getRolMatutino($plaza_id,$lunes,$domingo,3);
                $vespertinosCocina=$this->Mdl_nominas->getRolVespertino($plaza_id,$lunes,$domingo,3);

                $matutinosApoyo=$this->Mdl_nominas->getRolMatutino($plaza_id,$lunes,$domingo,4);
                $vespertinosApoyo=$this->Mdl_nominas->getRolVespertino($plaza_id,$lunes,$domingo,4);

                $acLunes=$this->Mdl_nominas->getTotalDia($plaza_id,$lunes,$domingo,1);
                $acLunes=$acLunes->Total;    
    
                $acMartes=$this->Mdl_nominas->getTotalDia($plaza_id,$lunes,$domingo,2);
                $acMartes=$acMartes->Total;    
                
                $acMiercoles=$this->Mdl_nominas->getTotalDia($plaza_id,$lunes,$domingo,3);
                $acMiercoles=$acMiercoles->Total;    
                
                $acJueves=$this->Mdl_nominas->getTotalDia($plaza_id,$lunes,$domingo,4);
                $acJueves=$acJueves->Total;    
                
                $acViernes=$this->Mdl_nominas->getTotalDia($plaza_id,$lunes,$domingo,5);
                $acViernes=$acViernes->Total;    
                
                $acSabado=$this->Mdl_nominas->getTotalDia($plaza_id,$lunes,$domingo,6);
                $acSabado=$acSabado->Total;    
                
                $acDomingo=$this->Mdl_nominas->getTotalDia($plaza_id,$lunes,$domingo,7);
                $acDomingo=$acDomingo->Total;    

                $acBarra=$this->Mdl_nominas->getTotalRol($plaza_id,$lunes,$domingo,1);
                $acBarra=$acBarra->Total;
                $acMesas=$this->Mdl_nominas->getTotalRol($plaza_id,$lunes,$domingo,2);
                $acMesas=$acMesas->Total;
                $acCocina=$this->Mdl_nominas->getTotalRol($plaza_id,$lunes,$domingo,3);
                $acCocina=$acCocina->Total;
                $acApoyo=$this->Mdl_nominas->getTotalRol($plaza_id,$lunes,$domingo,4);
                $acApoyo=$acApoyo->Total; 
    
                $acTotal=$this->Mdl_nominas->getTotalTurnosPlaza($plaza_id,$lunes,$domingo);
                $acTotal=$acTotal->sumaTurnos; 

                $fechaInicio=$lunes;
                $fechaFin=$domingo;
                $estado=$this->Mdl_nominas->getEstado($plaza_id,$fechaInicio,$fechaFin);
                if($estado!=null){
                    $estado=$estado->Estado;
                }
        }else{

            $lunes=$this->fechaLunes(date("Y-m-d"));
            $domingo=$this->fechaDomingo(date("Y-m-d"));                
            
            $semanas=$this->Mdl_nominas->getRolSemanal($plaza_id,$lunes,$domingo);                        
           
            $matutinosBarra=$this->Mdl_nominas->getRolMatutino($plaza_id,$lunes,$domingo,1);
            $vespertinosBarra=$this->Mdl_nominas->getRolVespertino($plaza_id,$lunes,$domingo,1);

            $matutinosMesas=$this->Mdl_nominas->getRolMatutino($plaza_id,$lunes,$domingo,2);
            $vespertinosMesas=$this->Mdl_nominas->getRolVespertino($plaza_id,$lunes,$domingo,2);

            $matutinosCocina=$this->Mdl_nominas->getRolMatutino($plaza_id,$lunes,$domingo,3);
            $vespertinosCocina=$this->Mdl_nominas->getRolVespertino($plaza_id,$lunes,$domingo,3);

            $matutinosApoyo=$this->Mdl_nominas->getRolMatutino($plaza_id,$lunes,$domingo,4);
            $vespertinosApoyo=$this->Mdl_nominas->getRolVespertino($plaza_id,$lunes,$domingo,4);

            $acLunes=$this->Mdl_nominas->getTotalDia($plaza_id,$lunes,$domingo,1);
            $acLunes=$acLunes->Total;    

            $acMartes=$this->Mdl_nominas->getTotalDia($plaza_id,$lunes,$domingo,2);
            $acMartes=$acMartes->Total;    
            
            $acMiercoles=$this->Mdl_nominas->getTotalDia($plaza_id,$lunes,$domingo,3);
            $acMiercoles=$acMiercoles->Total;    
            
            $acJueves=$this->Mdl_nominas->getTotalDia($plaza_id,$lunes,$domingo,4);
            $acJueves=$acJueves->Total;    
            
            $acViernes=$this->Mdl_nominas->getTotalDia($plaza_id,$lunes,$domingo,5);
            $acViernes=$acViernes->Total;    
            
            $acSabado=$this->Mdl_nominas->getTotalDia($plaza_id,$lunes,$domingo,6);
            $acSabado=$acSabado->Total;    
            
            $acDomingo=$this->Mdl_nominas->getTotalDia($plaza_id,$lunes,$domingo,7);
            $acDomingo=$acDomingo->Total;    


            $acBarra=$this->Mdl_nominas->getTotalRol($plaza_id,$lunes,$domingo,1);
            $acBarra=$acBarra->Total;
            $acMesas=$this->Mdl_nominas->getTotalRol($plaza_id,$lunes,$domingo,2);
            $acMesas=$acMesas->Total;
            $acCocina=$this->Mdl_nominas->getTotalRol($plaza_id,$lunes,$domingo,3);
            $acCocina=$acCocina->Total;
            $acApoyo=$this->Mdl_nominas->getTotalRol($plaza_id,$lunes,$domingo,4);
            $acApoyo=$acApoyo->Total; 
            $acTotal=$this->Mdl_nominas->getTotalTurnosPlaza($plaza_id,$lunes,$domingo);
            $acTotal=$acTotal->sumaTurnos; 


            $fechaInicio=$lunes;
            $fechaFin=$domingo;
            $estado=$this->Mdl_nominas->getEstado($plaza_id,$fechaInicio,$fechaFin);
            if($estado!=null){
                $estado=$estado->Estado;
            }

            $numeroSemana=$this->getSemana(date("Y-m-d"));
            $lunesE=$this->getWeekDates(2026,$numeroSemana,1,true);
            $martesE=$this->getWeekDates(2026,$numeroSemana,2,true);
            $miercolesE=$this->getWeekDates(2026,$numeroSemana,3,true);
            $juevesE=$this->getWeekDates(2026,$numeroSemana,4,true);     
            $viernesE=$this->getWeekDates(2026,$numeroSemana,5,true);     
            $sabadoE=$this->getWeekDates(2026,$numeroSemana,6,true);     
            $domingoE=$this->getWeekDates(2026,$numeroSemana,7,true); 

        }

        if($plaza_id==null){
            $plaza_id=0;
            $nombrePlaza="";
        }else{            
            $nombrePlaza=$this->Mdl_plazas->getPlazaID($plaza_id);
            $nombrePlaza=$nombrePlaza->nombrePlaza;
        }

        $data=
            array(
                'fechaInicio' => $fechaInicio,
                'fechaFin'    => $fechaFin,
                'roles'       => $roles,            
                'id_plaza'    => $plaza_id,
                'nombrePlaza' => $nombrePlaza,
                'usuarios'    => $usuarios,
                'activos'     => $activos,
                'nominas'     => $nominas,
                'turnos'      => $turnos,
                "dias"        => $dias,
                "semanas"     => $semanas,                                              
                'days'        => $days,                
                'conceptos'   => $conceptos, 
                'estado'      => $estado, 
                'plazas'      => $plazas,
                'matutinosBarra'   => $matutinosBarra,
                'vespertinosBarra'   => $vespertinosBarra,
                'matutinosMesas'   => $matutinosMesas,
                'vespertinosMesas'   => $vespertinosMesas,
                'matutinosCocina'   => $matutinosCocina,
                'vespertinosCocina'   => $vespertinosCocina,
                'matutinosApoyo'   => $matutinosApoyo,
                'vespertinosApoyo'   => $vespertinosApoyo,
                'acLunes'    => $acLunes,
                'acMartes'    => $acMartes,
                'acMiercoles'   => $acMiercoles,
                'acJueves'    => $acJueves,
                'acViernes'    => $acViernes,
                'acSabado'    => $acSabado,
                'acDomingo'    => $acDomingo,         
                'acBarra'=>$acBarra,
                'acMesas'=>$acMesas,            
                'acCocina'=>$acCocina,
                'acApoyo'=>$acApoyo,                    
                'acTotal'=>$acTotal,
                'lugares'   => $lugares,
                'lunesE'      => $lunesE,
                'martesE'      => $martesE,
                'miercolesE'      => $miercolesE,
                'juevesE'      => $juevesE,
                'viernesE'      => $viernesE,
                'sabadoE'      => $sabadoE,
                'domingoE'      => $domingoE
            );
                
            $this->load->view("layouts/header");
            $this->load->view("layouts/aside");
            $this->load->view("administrador/nominas/correccion_roles",$data);        
            $this->load->view("layouts/footer");  
    }

    public function consultarEstado(){    
        if($this->input->is_ajax_request()){ 
            $plaza_id=$this->input->post("plaza_id");       
            $fechaInicio=$this->input->post("fechaInicio");
            $fechaFin=$this->input->post("fechaFin");                     
            $estado=0;
            $estado=$this->Mdl_nominas->getEstado($plaza_id,$fechaInicio,$fechaFin);

            if($estado!=null){
                $estado=$estado->Estado;
                    echo $estado;
            }else{
                $estado=0;
                    echo $estado;
            }
            
        }else{
            redirect(base_url()."Inicio");
        }
    }

    public function analisisNominas(){                  

        $plazas=$this->Mdl_plazas->getPlazas();                                                                                           
        $plaza_id=$this->input->post("plaza_id");
        $lugares=$this->Mdl_nominas->getLugares();
        $usuarios=$this->Mdl_nominas->getUsuarios($plaza_id);           
        $turnos=$this->Mdl_nominas->getTurnosNomina();                     
        $fechaInicio=$this->input->post("fechaInicioD");
        $fechaFin=$this->input->post("fechaFinD");
        $id_lugar=$this->input->post("lugar_id_busqueda");          
        $id_turno=$this->input->post("turno_id_busqueda");  
        $data=array();
        $bandera=0;

        if($this->input->post("buscar"))
        {                           
            if($plaza_id==null){                
                $fechaFin=$fechaInicio;                                               
                //TURNOS
                    $turnosCasitas=$this->Mdl_nominas->getTotalTurnosPlaza(1,$fechaInicio,$fechaInicio);
                    if($turnosCasitas!=null){$turnosCasitas=$turnosCasitas->sumaTurnos;}else{$turnosCasitas=0;}
                    
                    $turnosMatamoros=$this->Mdl_nominas->getTotalTurnosPlaza(2,$fechaInicio,$fechaInicio);
                    if($turnosMatamoros!=null){$turnosMatamoros=$turnosMatamoros->sumaTurnos;}else{$turnosMatamoros=0;}

                    $turnosOaxaca=$this->Mdl_nominas->getTotalTurnosPlaza(3,$fechaInicio,$fechaInicio);
                    if($turnosOaxaca!=null){$turnosOaxaca=$turnosOaxaca->sumaTurnos;}else{$turnosOaxaca=0;}

                    $turnosPozaRica=$this->Mdl_nominas->getTotalTurnosPlaza(4,$fechaInicio,$fechaInicio);
                    if($turnosPozaRica!=null){$turnosPozaRica=$turnosPozaRica->sumaTurnos;}else{$turnosPozaRica=0;}

                    $turnosReynosaI=$this->Mdl_nominas->getTotalTurnosPlaza(5,$fechaInicio,$fechaInicio);
                    if($turnosReynosaI!=null){$turnosReynosaI=$turnosReynosaI->sumaTurnos;}else{$turnosReynosaI=0;}

                    $turnosReynosaII=$this->Mdl_nominas->getTotalTurnosPlaza(6,$fechaInicio,$fechaInicio);
                    if($turnosReynosaII!=null){$turnosReynosaII=$turnosReynosaII->sumaTurnos;}else{$turnosReynosaII=0;}

                    $turnosTeziutlan=$this->Mdl_nominas->getTotalTurnosPlaza(7,$fechaInicio,$fechaInicio);
                    if($turnosTeziutlan!=null){$turnosTeziutlan=$turnosTeziutlan->sumaTurnos;}else{$turnosTeziutlan=0;}
                //TURNOS
                
                //UNIDADES
                    $unidadesCasitas=$this->Mdl_nominas->getTotalUnidadesPlaza(1,$fechaInicio,$fechaInicio);
                    $unidadesCasitas=$unidadesCasitas->sumaUnidades;

                    $unidadesMatamoros=$this->Mdl_nominas->getTotalUnidadesPlaza(2,$fechaInicio,$fechaInicio);
                    $unidadesMatamoros=$unidadesMatamoros->sumaUnidades;

                    $unidadesOaxaca=$this->Mdl_nominas->getTotalUnidadesPlaza(3,$fechaInicio,$fechaInicio);
                    $unidadesOaxaca=$unidadesOaxaca->sumaUnidades;

                    $unidadesPozaRica=$this->Mdl_nominas->getTotalUnidadesPlaza(4,$fechaInicio,$fechaInicio);
                    $unidadesPozaRica=$unidadesPozaRica->sumaUnidades;

                    $unidadesReynosaI=$this->Mdl_nominas->getTotalUnidadesPlaza(5,$fechaInicio,$fechaInicio);
                    $unidadesReynosaI=$unidadesReynosaI->sumaUnidades;

                    $unidadesReynosaII=$this->Mdl_nominas->getTotalUnidadesPlaza(6,$fechaInicio,$fechaInicio);
                    $unidadesReynosaII=$unidadesReynosaII->sumaUnidades;

                    $unidadesTeziutlan=$this->Mdl_nominas->getTotalUnidadesPlaza(7,$fechaInicio,$fechaInicio);
                    $unidadesTeziutlan=$unidadesTeziutlan->sumaUnidades;          
                //UNIDADES
                            
                //INGRESOS 
                    $ingresosCasitas=$this->Mdl_nominas->getTotalIngresosPlaza(1,$fechaInicio,$fechaInicio);
                    $ingresosCasitas=$ingresosCasitas->sumaIngresos;

                    $ingresosMatamoros=$this->Mdl_nominas->getTotalIngresosPlaza(2,$fechaInicio,$fechaInicio);
                    $ingresosMatamoros=$ingresosMatamoros->sumaIngresos;

                    $ingresosOaxaca=$this->Mdl_nominas->getTotalIngresosPlaza(3,$fechaInicio,$fechaInicio);
                    $ingresosOaxaca=$ingresosOaxaca->sumaIngresos;

                    $ingresosPozaRica=$this->Mdl_nominas->getTotalIngresosPlaza(4,$fechaInicio,$fechaInicio);
                    $ingresosPozaRica=$ingresosPozaRica->sumaIngresos;

                    $ingresosReynosaI=$this->Mdl_nominas->getTotalIngresosPlaza(5,$fechaInicio,$fechaInicio);
                    $ingresosReynosaI=$ingresosReynosaI->sumaIngresos;

                    $ingresosReynosaII=$this->Mdl_nominas->getTotalIngresosPlaza(6,$fechaInicio,$fechaInicio);
                    $ingresosReynosaII=$ingresosReynosaII->sumaIngresos;

                    $ingresosTeziutlan=$this->Mdl_nominas->getTotalIngresosPlaza(7,$fechaInicio,$fechaInicio);
                    $ingresosTeziutlan=$ingresosTeziutlan->sumaIngresos;
                //INGRESOS   
                        
                //ACUMULADOS TOTALES  
                    $totalTurnos=$this->Mdl_nominas->getTotalTurnos($fechaInicio,$fechaInicio);
                    if($totalTurnos!=null){$totalTurnos=$totalTurnos->sumaTurnos;}else{$totalTurnos=0;}

                    $totalUnidades=$this->Mdl_nominas->getTotalUnidades($fechaInicio,$fechaInicio);
                    if($totalUnidades!=null){$totalUnidades=$totalUnidades->sumaUnidades;}else{$totalUnidades=0;}

                    $totalIngresos=$this->Mdl_nominas->getTotalIngresos($fechaInicio,$fechaInicio);
                    if($totalIngresos!=null){$totalIngresos=$totalIngresos->sumaIngresos;}else{$totalIngresos=0;}

                    $totalHorasHombre=$totalTurnos*6.5;
                //ACUMULADOS TOTALES            
            }else{                
                $bandera=1;
                $fechaFin=$fechaInicio;                                               
                //TURNOS
                    $turnosGenerico=$this->Mdl_nominas->getTotalTurnosPlaza($plaza_id,$fechaInicio,$fechaInicio);
                    if($turnosGenerico!=null){$turnosGenerico=$turnosGenerico->sumaTurnos;}else{$turnosGenerico=0;}                                        
                //TURNOS
                
                //UNIDADES
                    $unidadesGenerico=$this->Mdl_nominas->getTotalUnidadesPlaza($plaza_id,$fechaInicio,$fechaInicio);
                    $unidadesGenerico=$unidadesGenerico->sumaUnidades;                            
                //UNIDADES
                            
                //INGRESOS 
                    $ingresosGenerico=$this->Mdl_nominas->getTotalIngresosPlaza($plaza_id,$fechaInicio,$fechaInicio);
                    $ingresosGenerico=$ingresosGenerico->sumaIngresos;                   
                //INGRESOS   
                        
                //ACUMULADOS TOTALES                     
                   $totalTurnosGenerico=$this->Mdl_nominas->getTotalTurnosPlaza($plaza_id,$fechaInicio,$fechaInicio);
                   if($totalTurnosGenerico!=null){$totalTurnosGenerico=$totalTurnosGenerico->sumaTurnos;}else{$totalTurnosGenerico=0;}

                   $totalUnidadesGenerico=$this->Mdl_nominas->getTotalUnidadesPlaza($plaza_id,$fechaInicio,$fechaInicio);
                   if($totalUnidadesGenerico!=null){$totalUnidadesGenerico=$totalUnidadesGenerico->sumaUnidades;}else{$totalUnidadesGenerico=0;}

                   $totalIngresosGenerico=$this->Mdl_nominas->getTotalIngresosPlaza($plaza_id,$fechaInicio,$fechaInicio);
                   if($totalIngresosGenerico!=null){$totalIngresosGenerico=$totalIngresosGenerico->sumaIngresos;}else{$totalIngresosGenerico=0;}  
                   
                   $totalHorasHombreGenerico=$totalTurnosGenerico*6.5;
                //ACUMULADOS TOTALES
            }                    
        }else if($this->input->post("buscarRango")){
            if($plaza_id==null){                
            
                //TURNOS        
                    $turnosCasitas=$this->Mdl_nominas->getTotalTurnosPlaza(1,$fechaInicio,$fechaFin);
                    if($turnosCasitas!=null){$turnosCasitas=$turnosCasitas->sumaTurnos;}else{$turnosCasitas=0;}
                    
                    $turnosMatamoros=$this->Mdl_nominas->getTotalTurnosPlaza(2,$fechaInicio,$fechaFin);
                    if($turnosMatamoros!=null){$turnosMatamoros=$turnosMatamoros->sumaTurnos;}else{$turnosMatamoros=0;}

                    $turnosOaxaca=$this->Mdl_nominas->getTotalTurnosPlaza(3,$fechaInicio,$fechaFin);
                    if($turnosOaxaca!=null){$turnosOaxaca=$turnosOaxaca->sumaTurnos;}else{$turnosOaxaca=0;}

                    $turnosPozaRica=$this->Mdl_nominas->getTotalTurnosPlaza(4,$fechaInicio,$fechaFin);
                    if($turnosPozaRica!=null){$turnosPozaRica=$turnosPozaRica->sumaTurnos;}else{$turnosPozaRica=0;}

                    $turnosReynosaI=$this->Mdl_nominas->getTotalTurnosPlaza(5,$fechaInicio,$fechaFin);
                    if($turnosReynosaI!=null){$turnosReynosaI=$turnosReynosaI->sumaTurnos;}else{$turnosReynosaI=0;}

                    $turnosReynosaII=$this->Mdl_nominas->getTotalTurnosPlaza(6,$fechaInicio,$fechaFin);
                    if($turnosReynosaII!=null){$turnosReynosaII=$turnosReynosaII->sumaTurnos;}else{$turnosReynosaII=0;}

                    $turnosTeziutlan=$this->Mdl_nominas->getTotalTurnosPlaza(7,$fechaInicio,$fechaFin);
                    if($turnosTeziutlan!=null){$turnosTeziutlan=$turnosTeziutlan->sumaTurnos;}else{$turnosTeziutlan=0;}
                //TURNOS

                //UNIDADES        
                    $unidadesCasitas=$this->Mdl_nominas->getTotalUnidadesPlaza(1,$fechaInicio,$fechaFin);
                    $unidadesCasitas=$unidadesCasitas->sumaUnidades;

                    $unidadesMatamoros=$this->Mdl_nominas->getTotalUnidadesPlaza(2,$fechaInicio,$fechaFin);
                    $unidadesMatamoros=$unidadesMatamoros->sumaUnidades;

                    $unidadesOaxaca=$this->Mdl_nominas->getTotalUnidadesPlaza(3,$fechaInicio,$fechaFin);
                    $unidadesOaxaca=$unidadesOaxaca->sumaUnidades;

                    $unidadesPozaRica=$this->Mdl_nominas->getTotalUnidadesPlaza(4,$fechaInicio,$fechaFin);
                    $unidadesPozaRica=$unidadesPozaRica->sumaUnidades;

                    $unidadesReynosaI=$this->Mdl_nominas->getTotalUnidadesPlaza(5,$fechaInicio,$fechaFin);
                    $unidadesReynosaI=$unidadesReynosaI->sumaUnidades;

                    $unidadesReynosaII=$this->Mdl_nominas->getTotalUnidadesPlaza(6,$fechaInicio,$fechaFin);
                    $unidadesReynosaII=$unidadesReynosaII->sumaUnidades;

                    $unidadesTeziutlan=$this->Mdl_nominas->getTotalUnidadesPlaza(7,$fechaInicio,$fechaFin);
                    $unidadesTeziutlan=$unidadesTeziutlan->sumaUnidades;
                //UNIDADES
                            
                //INGRESOS       
                    $ingresosCasitas=$this->Mdl_nominas->getTotalIngresosPlaza(1,$fechaInicio,$fechaFin);
                    $ingresosCasitas=$ingresosCasitas->sumaIngresos;

                    $ingresosMatamoros=$this->Mdl_nominas->getTotalIngresosPlaza(2,$fechaInicio,$fechaFin);
                    $ingresosMatamoros=$ingresosMatamoros->sumaIngresos;

                    $ingresosOaxaca=$this->Mdl_nominas->getTotalIngresosPlaza(3,$fechaInicio,$fechaFin);
                    $ingresosOaxaca=$ingresosOaxaca->sumaIngresos;

                    $ingresosPozaRica=$this->Mdl_nominas->getTotalIngresosPlaza(4,$fechaInicio,$fechaFin);
                    $ingresosPozaRica=$ingresosPozaRica->sumaIngresos;

                    $ingresosReynosaI=$this->Mdl_nominas->getTotalIngresosPlaza(5,$fechaInicio,$fechaFin);
                    $ingresosReynosaI=$ingresosReynosaI->sumaIngresos;

                    $ingresosReynosaII=$this->Mdl_nominas->getTotalIngresosPlaza(6,$fechaInicio,$fechaFin);
                    $ingresosReynosaII=$ingresosReynosaII->sumaIngresos;

                    $ingresosTeziutlan=$this->Mdl_nominas->getTotalIngresosPlaza(7,$fechaInicio,$fechaFin);
                    $ingresosTeziutlan=$ingresosTeziutlan->sumaIngresos;
                //INGRESOS   

                //ACUMULADOS TOTALES            
                    $totalTurnos=$this->Mdl_nominas->getTotalTurnos($fechaInicio,$fechaFin);
                    if($totalTurnos!=null){$totalTurnos=$totalTurnos->sumaTurnos;}else{$totalTurnos=0;}

                    $totalUnidades=$this->Mdl_nominas->getTotalUnidades($fechaInicio,$fechaFin);
                    if($totalUnidades!=null){$totalUnidades=$totalUnidades->sumaUnidades;}else{$totalUnidades=0;}

                    $totalIngresos=$this->Mdl_nominas->getTotalIngresos($fechaInicio,$fechaFin);
                    if($totalIngresos!=null){$totalIngresos=$totalIngresos->sumaIngresos;}else{$totalIngresos=0;}

                    $totalHorasHombre=$totalTurnos*6.5;
                //ACUMULADOS TOTALES
            }else{
                $bandera=1;
                //TURNOS
                    $turnosGenerico=$this->Mdl_nominas->getTotalTurnosPlaza($plaza_id,$fechaInicio,$fechaFin);
                    if($turnosGenerico!=null){$turnosGenerico=$turnosGenerico->sumaTurnos;}else{$turnosGenerico=0;}                                        
                //TURNOS
                
                //UNIDADES
                    $unidadesGenerico=$this->Mdl_nominas->getTotalUnidadesPlaza($plaza_id,$fechaInicio,$fechaFin);
                    $unidadesGenerico=$unidadesGenerico->sumaUnidades;                            
                //UNIDADES
                            
                //INGRESOS 
                    $ingresosGenerico=$this->Mdl_nominas->getTotalIngresosPlaza($plaza_id,$fechaInicio,$fechaFin);
                    $ingresosGenerico=$ingresosGenerico->sumaIngresos;                   
                //INGRESOS   

                //ACUMULADOS TOTALES  
                    $totalTurnosGenerico=$this->Mdl_nominas->getTotalTurnosPlaza($plaza_id,$fechaInicio,$fechaFin);
                    if($totalTurnosGenerico!=null){$totalTurnosGenerico=$totalTurnosGenerico->sumaTurnos;}else{$totalTurnosGenerico=0;}

                    $totalUnidadesGenerico=$this->Mdl_nominas->getTotalUnidadesPlaza($plaza_id,$fechaInicio,$fechaFin);
                    if($totalUnidadesGenerico!=null){$totalUnidadesGenerico=$totalUnidadesGenerico->sumaUnidades;}else{$totalUnidadesGenerico=0;}

                    $totalIngresosGenerico=$this->Mdl_nominas->getTotalIngresosPlaza($plaza_id,$fechaInicio,$fechaFin);
                    if($totalIngresosGenerico!=null){$totalIngresosGenerico=$totalIngresosGenerico->sumaIngresos;}else{$totalIngresosGenerico=0;}

                    $totalHorasHombreGenerico=$totalTurnosGenerico*6.5;
                //ACUMULADOS TOTALES
            }                                
        }
        else{
            $fechaInicio=date('Y-m-d',strtotime("-1 days"));
            $fechaFin=date('Y-m-d',strtotime("-1 days"));

            //TURNOS        
              $turnosCasitas=$this->Mdl_nominas->getTotalTurnosPlaza(1,$fechaInicio,$fechaInicio);
              if($turnosCasitas!=null){$turnosCasitas=$turnosCasitas->sumaTurnos;}else{$turnosCasitas=0;}
              
              $turnosMatamoros=$this->Mdl_nominas->getTotalTurnosPlaza(2,$fechaInicio,$fechaInicio);
              if($turnosMatamoros!=null){$turnosMatamoros=$turnosMatamoros->sumaTurnos;}else{$turnosMatamoros=0;}

              $turnosOaxaca=$this->Mdl_nominas->getTotalTurnosPlaza(3,$fechaInicio,$fechaInicio);
              if($turnosOaxaca!=null){$turnosOaxaca=$turnosOaxaca->sumaTurnos;}else{$turnosOaxaca=0;}

              $turnosPozaRica=$this->Mdl_nominas->getTotalTurnosPlaza(4,$fechaInicio,$fechaInicio);
              if($turnosPozaRica!=null){$turnosPozaRica=$turnosPozaRica->sumaTurnos;}else{$turnosPozaRica=0;}

              $turnosReynosaI=$this->Mdl_nominas->getTotalTurnosPlaza(5,$fechaInicio,$fechaInicio);
              if($turnosReynosaI!=null){$turnosReynosaI=$turnosReynosaI->sumaTurnos;}else{$turnosReynosaI=0;}

              $turnosReynosaII=$this->Mdl_nominas->getTotalTurnosPlaza(6,$fechaInicio,$fechaInicio);
              if($turnosReynosaII!=null){$turnosReynosaII=$turnosReynosaII->sumaTurnos;}else{$turnosReynosaII=0;}

              $turnosTeziutlan=$this->Mdl_nominas->getTotalTurnosPlaza(7,$fechaInicio,$fechaInicio);
              if($turnosTeziutlan!=null){$turnosTeziutlan=$turnosTeziutlan->sumaTurnos;}else{$turnosTeziutlan=0;}
            //TURNOS

            //UNIDADES        
              $unidadesCasitas=$this->Mdl_nominas->getTotalUnidadesPlaza(1,$fechaInicio,$fechaInicio);
              $unidadesCasitas=$unidadesCasitas->sumaUnidades;

              $unidadesMatamoros=$this->Mdl_nominas->getTotalUnidadesPlaza(2,$fechaInicio,$fechaInicio);
              $unidadesMatamoros=$unidadesMatamoros->sumaUnidades;

              $unidadesOaxaca=$this->Mdl_nominas->getTotalUnidadesPlaza(3,$fechaInicio,$fechaInicio);
              $unidadesOaxaca=$unidadesOaxaca->sumaUnidades;

              $unidadesPozaRica=$this->Mdl_nominas->getTotalUnidadesPlaza(4,$fechaInicio,$fechaInicio);
              $unidadesPozaRica=$unidadesPozaRica->sumaUnidades;

              $unidadesReynosaI=$this->Mdl_nominas->getTotalUnidadesPlaza(5,$fechaInicio,$fechaInicio);
              $unidadesReynosaI=$unidadesReynosaI->sumaUnidades;

              $unidadesReynosaII=$this->Mdl_nominas->getTotalUnidadesPlaza(6,$fechaInicio,$fechaInicio);
              $unidadesReynosaII=$unidadesReynosaII->sumaUnidades;

              $unidadesTeziutlan=$this->Mdl_nominas->getTotalUnidadesPlaza(7,$fechaInicio,$fechaInicio);
              $unidadesTeziutlan=$unidadesTeziutlan->sumaUnidades;
            //UNIDADES
                      
            //INGRESOS       
             $ingresosCasitas=$this->Mdl_nominas->getTotalIngresosPlaza(1,$fechaInicio,$fechaInicio);
             $ingresosCasitas=$ingresosCasitas->sumaIngresos;

             $ingresosMatamoros=$this->Mdl_nominas->getTotalIngresosPlaza(2,$fechaInicio,$fechaInicio);
             $ingresosMatamoros=$ingresosMatamoros->sumaIngresos;

             $ingresosOaxaca=$this->Mdl_nominas->getTotalIngresosPlaza(3,$fechaInicio,$fechaInicio);
             $ingresosOaxaca=$ingresosOaxaca->sumaIngresos;

             $ingresosPozaRica=$this->Mdl_nominas->getTotalIngresosPlaza(4,$fechaInicio,$fechaInicio);
             $ingresosPozaRica=$ingresosPozaRica->sumaIngresos;

             $ingresosReynosaI=$this->Mdl_nominas->getTotalIngresosPlaza(5,$fechaInicio,$fechaInicio);
             $ingresosReynosaI=$ingresosReynosaI->sumaIngresos;

             $ingresosReynosaII=$this->Mdl_nominas->getTotalIngresosPlaza(6,$fechaInicio,$fechaInicio);
             $ingresosReynosaII=$ingresosReynosaII->sumaIngresos;

             $ingresosTeziutlan=$this->Mdl_nominas->getTotalIngresosPlaza(7,$fechaInicio,$fechaInicio);
             $ingresosTeziutlan=$ingresosTeziutlan->sumaIngresos;
            //INGRESOS   

            //ACUMULADOS TOTALES            
                $totalTurnos=$this->Mdl_nominas->getTotalTurnos($fechaInicio,$fechaInicio);
                if($totalTurnos!=null){$totalTurnos=$totalTurnos->sumaTurnos;}else{$totalTurnos=0;}

                $totalUnidades=$this->Mdl_nominas->getTotalUnidades($fechaInicio,$fechaInicio);
                if($totalUnidades!=null){$totalUnidades=$totalUnidades->sumaUnidades;}else{$totalUnidades=0;}

                $totalIngresos=$this->Mdl_nominas->getTotalIngresos($fechaInicio,$fechaInicio);
                if($totalIngresos!=null){$totalIngresos=$totalIngresos->sumaIngresos;}else{$totalIngresos=0;}

                $totalHorasHombre=$totalTurnos*6.5;
            //ACUMULADOS TOTALES            
        }

        switch ($bandera) {
            case 0:                
                $data=
                array(
                    'fechaInicio' => $fechaInicio,
                    'fechaFin'    => $fechaFin,
                    'plazas'      => $plazas,
                    'id'          => $plaza_id,
                    'id_lugar'    => $id_lugar,
                    'id_turno'    => $id_turno,
                    'usuarios'    => $usuarios,                                                                           
                    'turnos'    => $turnos,                                                               
                    'lugares'   => $lugares,
                    'turnosCasitas'=>$turnosCasitas,
                    'turnosMatamoros'=>$turnosMatamoros,        
                    'turnosOaxaca'=>$turnosOaxaca,        
                    'turnosPozaRica'=>$turnosPozaRica,        
                    'turnosReynosaI'=>$turnosReynosaI,        
                    'turnosReynosaII'=>$turnosReynosaII,        
                    'turnosTeziutlan'=>$turnosTeziutlan,
                    'unidadesCasitas'=>$unidadesCasitas,
                    'unidadesMatamoros'=>$unidadesMatamoros,        
                    'unidadesOaxaca'=>$unidadesOaxaca,        
                    'unidadesPozaRica'=>$unidadesPozaRica,        
                    'unidadesReynosaI'=>$unidadesReynosaI,        
                    'unidadesReynosaII'=>$unidadesReynosaII,        
                    'unidadesTeziutlan'=>$unidadesTeziutlan,
                    'ingresosCasitas'=>$ingresosCasitas,
                    'ingresosMatamoros'=>$ingresosMatamoros,        
                    'ingresosOaxaca'=>$ingresosOaxaca,        
                    'ingresosPozaRica'=>$ingresosPozaRica,        
                    'ingresosReynosaI'=>$ingresosReynosaI,        
                    'ingresosReynosaII'=>$ingresosReynosaII,        
                    'ingresosTeziutlan'=>$ingresosTeziutlan,            
                    'totalTurnos'=>$totalTurnos,
                    'totalUnidades'=>$totalUnidades,
                    'totalIngresos'=>$totalIngresos,
                    'totalHorasHombre'=>$totalHorasHombre,
                    'bandera'=>$bandera  
                );
            break;

            case 1:                

                $nombrePlaza=$this->Mdl_plazas->getPlazaID($plaza_id);
                $nombrePlaza=$nombrePlaza->nombrePlaza;
                $data=
                array(
                    'fechaInicio' => $fechaInicio,
                    'fechaFin'    => $fechaFin,
                    'plazas'      => $plazas,
                    'id'          => $plaza_id,
                    'id_lugar'    => $id_lugar,
                    'id_turno'    => $id_turno,
                    'usuarios'    => $usuarios,                                                                           
                    'turnos'    => $turnos,                                                               
                    'unidadesGenerico'   => $unidadesGenerico,
                    'ingresosGenerico'    => $ingresosGenerico,
                    'turnosGenerico'    => $turnosGenerico,                                                                                                                           
                    'totalTurnosGenerico'=>$totalTurnosGenerico,
                    'totalUnidadesGenerico'=>$totalUnidadesGenerico,
                    'totalIngresosGenerico'=>$totalIngresosGenerico,
                    'totalHorasHombreGenerico'=>$totalHorasHombreGenerico,
                    'bandera'=>$bandera,
                    'nombrePlaza'=>$nombrePlaza
                );
            break;                        
        }

      $this->load->view("layouts/header");
      $this->load->view("layouts/aside");
      $this->load->view("administrador/nominas/analisis_nominas",$data);
      $this->load->view("layouts/footer");
    }

    public function manager(){                
        $this->load->view("layouts/header");
        $this->load->view("layouts/aside");
        $this->load->view("manager/nominas/panel_nominas");        
        $this->load->view("layouts/footer");         
    }

    public function rolManager(){
        $roles=$this->Mdl_nominas->getRoles();  

        $activos=$this->Mdl_nominas->getActivos();
        $nominas=$this->Mdl_remesas->getInsumosID(15);
        $turnos=$this->Mdl_nominas->getTurnosNomina();

        $fechaHoy=date("Y-m-d");            
        $numeroDia=date('N', strtotime($fechaHoy));               
        $dias=$this->Mdl_nominas->getDiasNomina($numeroDia);
        $conceptos=$this->Mdl_nominas->getConceptos();

        $plaza_id=$this->session->userdata("plaza");
        $usuarios=$this->Mdl_nominas->getUsuarios($plaza_id);
        $lugares=$this->Mdl_nominas->getLugares();
        $days=$this->asignarDias2();

        $fechaInicio=$this->input->post("fechaInicioD");
        $fechaFin=$this->input->post("fechaFinD"); 
        $id_plaza=$this->input->post("plaza_idP");      
        
        $lunes=$this->fechaLunes(date("Y-m-d"));
        $domingo=$this->fechaDomingo(date("Y-m-d"));
        $estado=0;

        $semanas=$this->Mdl_nominas->getRolSemanal($plaza_id,$lunes,$domingo);                

        $matutinosBarra=$this->Mdl_nominas->getRolMatutino($plaza_id,$lunes,$domingo,1);
        $vespertinosBarra=$this->Mdl_nominas->getRolVespertino($plaza_id,$lunes,$domingo,1);

        $matutinosMesas=$this->Mdl_nominas->getRolMatutino($plaza_id,$lunes,$domingo,2);
        $vespertinosMesas=$this->Mdl_nominas->getRolVespertino($plaza_id,$lunes,$domingo,2);

        $matutinosCocina=$this->Mdl_nominas->getRolMatutino($plaza_id,$lunes,$domingo,3);
        $vespertinosCocina=$this->Mdl_nominas->getRolVespertino($plaza_id,$lunes,$domingo,3);

        $matutinosApoyo=$this->Mdl_nominas->getRolMatutino($plaza_id,$lunes,$domingo,4);
        $vespertinosApoyo=$this->Mdl_nominas->getRolVespertino($plaza_id,$lunes,$domingo,4);
        


        if($this->input->post("buscar")){
            if($fechaInicio==null){
                $lunes=$this->fechaLunes(date("Y-m-d"));
                $domingo=$this->fechaDomingo(date("Y-m-d")); 
                $numeroSemana=$this->getSemana(date("Y-m-d"));
                $lunesE=$this->getWeekDates(2026,$numeroSemana,1,true);
                $martesE=$this->getWeekDates(2026,$numeroSemana,2,true);
                $miercolesE=$this->getWeekDates(2026,$numeroSemana,3,true);
                $juevesE=$this->getWeekDates(2026,$numeroSemana,4,true);     
                $viernesE=$this->getWeekDates(2026,$numeroSemana,5,true);     
                $sabadoE=$this->getWeekDates(2026,$numeroSemana,6,true);     
                $domingoE=$this->getWeekDates(2026,$numeroSemana,7,true);     
            }else{                
                $lunes=$this->fechaLunes($fechaInicio);
                $domingo=$this->fechaDomingo($fechaInicio);
                $numeroSemana=$this->getSemana($fechaInicio);
                $lunesE=$this->getWeekDates(2026,$numeroSemana,1,true);
                $martesE=$this->getWeekDates(2026,$numeroSemana,2,true);
                $miercolesE=$this->getWeekDates(2026,$numeroSemana,3,true);
                $juevesE=$this->getWeekDates(2026,$numeroSemana,4,true);     
                $viernesE=$this->getWeekDates(2026,$numeroSemana,5,true);     
                $sabadoE=$this->getWeekDates(2026,$numeroSemana,6,true);     
                $domingoE=$this->getWeekDates(2026,$numeroSemana,7,true);     

            }

                $semanas=$this->Mdl_nominas->getRolSemanal($plaza_id,$lunes,$domingo);                                

                $matutinosBarra=$this->Mdl_nominas->getRolMatutino($plaza_id,$lunes,$domingo,1);
                $vespertinosBarra=$this->Mdl_nominas->getRolVespertino($plaza_id,$lunes,$domingo,1);

                $matutinosMesas=$this->Mdl_nominas->getRolMatutino($plaza_id,$lunes,$domingo,2);
                $vespertinosMesas=$this->Mdl_nominas->getRolVespertino($plaza_id,$lunes,$domingo,2);

                $matutinosCocina=$this->Mdl_nominas->getRolMatutino($plaza_id,$lunes,$domingo,3);
                $vespertinosCocina=$this->Mdl_nominas->getRolVespertino($plaza_id,$lunes,$domingo,3);

                $matutinosApoyo=$this->Mdl_nominas->getRolMatutino($plaza_id,$lunes,$domingo,4);
                $vespertinosApoyo=$this->Mdl_nominas->getRolVespertino($plaza_id,$lunes,$domingo,4);

                $acLunes=$this->Mdl_nominas->getTotalDia($plaza_id,$lunes,$domingo,1);
                $acLunes=$acLunes->Total;    
                $acMartes=$this->Mdl_nominas->getTotalDia($plaza_id,$lunes,$domingo,2);
                $acMartes=$acMartes->Total;                
                $acMiercoles=$this->Mdl_nominas->getTotalDia($plaza_id,$lunes,$domingo,3);
                $acMiercoles=$acMiercoles->Total;                
                $acJueves=$this->Mdl_nominas->getTotalDia($plaza_id,$lunes,$domingo,4);
                $acJueves=$acJueves->Total;                
                $acViernes=$this->Mdl_nominas->getTotalDia($plaza_id,$lunes,$domingo,5);
                $acViernes=$acViernes->Total;                
                $acSabado=$this->Mdl_nominas->getTotalDia($plaza_id,$lunes,$domingo,6);
                $acSabado=$acSabado->Total;                
                $acDomingo=$this->Mdl_nominas->getTotalDia($plaza_id,$lunes,$domingo,7);
                $acDomingo=$acDomingo->Total;  

                $acBarra=$this->Mdl_nominas->getTotalRol($plaza_id,$lunes,$domingo,1);
                $acBarra=$acBarra->Total;
                $acMesas=$this->Mdl_nominas->getTotalRol($plaza_id,$lunes,$domingo,2);
                $acMesas=$acMesas->Total;
                $acCocina=$this->Mdl_nominas->getTotalRol($plaza_id,$lunes,$domingo,3);
                $acCocina=$acCocina->Total;
                $acApoyo=$this->Mdl_nominas->getTotalRol($plaza_id,$lunes,$domingo,4);
                $acApoyo=$acApoyo->Total; 
    
                $acTotal=$this->Mdl_nominas->getTotalTurnosPlaza($plaza_id,$lunes,$domingo);
                $acTotal=$acTotal->sumaTurnos; 

                $fechaInicio=$lunes;
                $fechaFin=$domingo;
                $estado=$this->Mdl_nominas->getEstado($plaza_id,$fechaInicio,$fechaFin);
                if($estado!=null){
                    $estado=$estado->Estado;
                }
        }else{

            $lunes=$this->fechaLunes(date("Y-m-d"));
            $domingo=$this->fechaDomingo(date("Y-m-d"));                
            
            $semanas=$this->Mdl_nominas->getRolSemanal($plaza_id,$lunes,$domingo);                        
           
            $matutinosBarra=$this->Mdl_nominas->getRolMatutino($plaza_id,$lunes,$domingo,1);
            $vespertinosBarra=$this->Mdl_nominas->getRolVespertino($plaza_id,$lunes,$domingo,1);

            $matutinosMesas=$this->Mdl_nominas->getRolMatutino($plaza_id,$lunes,$domingo,2);
            $vespertinosMesas=$this->Mdl_nominas->getRolVespertino($plaza_id,$lunes,$domingo,2);

            $matutinosCocina=$this->Mdl_nominas->getRolMatutino($plaza_id,$lunes,$domingo,3);
            $vespertinosCocina=$this->Mdl_nominas->getRolVespertino($plaza_id,$lunes,$domingo,3);

            $matutinosApoyo=$this->Mdl_nominas->getRolMatutino($plaza_id,$lunes,$domingo,4);
            $vespertinosApoyo=$this->Mdl_nominas->getRolVespertino($plaza_id,$lunes,$domingo,4);

            $acLunes=$this->Mdl_nominas->getTotalDia($plaza_id,$lunes,$domingo,1);
            $acLunes=$acLunes->Total;    
            $acMartes=$this->Mdl_nominas->getTotalDia($plaza_id,$lunes,$domingo,2);
            $acMartes=$acMartes->Total;                
            $acMiercoles=$this->Mdl_nominas->getTotalDia($plaza_id,$lunes,$domingo,3);
            $acMiercoles=$acMiercoles->Total;                
            $acJueves=$this->Mdl_nominas->getTotalDia($plaza_id,$lunes,$domingo,4);
            $acJueves=$acJueves->Total;                
            $acViernes=$this->Mdl_nominas->getTotalDia($plaza_id,$lunes,$domingo,5);
            $acViernes=$acViernes->Total;                
            $acSabado=$this->Mdl_nominas->getTotalDia($plaza_id,$lunes,$domingo,6);
            $acSabado=$acSabado->Total;                
            $acDomingo=$this->Mdl_nominas->getTotalDia($plaza_id,$lunes,$domingo,7);
            $acDomingo=$acDomingo->Total;  

            $acBarra=$this->Mdl_nominas->getTotalRol($plaza_id,$lunes,$domingo,1);
            $acBarra=$acBarra->Total;
            $acMesas=$this->Mdl_nominas->getTotalRol($plaza_id,$lunes,$domingo,2);
            $acMesas=$acMesas->Total;
            $acCocina=$this->Mdl_nominas->getTotalRol($plaza_id,$lunes,$domingo,3);
            $acCocina=$acCocina->Total;
            $acApoyo=$this->Mdl_nominas->getTotalRol($plaza_id,$lunes,$domingo,4);
            $acApoyo=$acApoyo->Total; 

            $acTotal=$this->Mdl_nominas->getTotalTurnosPlaza($plaza_id,$lunes,$domingo);
            $acTotal=$acTotal->sumaTurnos; 



            $fechaInicio=$lunes;
            $fechaFin=$domingo;
            $estado=$this->Mdl_nominas->getEstado($plaza_id,$fechaInicio,$fechaFin);
            if($estado!=null){
                $estado=$estado->Estado;
            }

            $numeroSemana=$this->getSemana(date("Y-m-d"));
            $lunesE=$this->getWeekDates(2026,$numeroSemana,1,true);
            $martesE=$this->getWeekDates(2026,$numeroSemana,2,true);
            $miercolesE=$this->getWeekDates(2026,$numeroSemana,3,true);
            $juevesE=$this->getWeekDates(2026,$numeroSemana,4,true);     
            $viernesE=$this->getWeekDates(2026,$numeroSemana,5,true);     
            $sabadoE=$this->getWeekDates(2026,$numeroSemana,6,true);     
            $domingoE=$this->getWeekDates(2026,$numeroSemana,7,true); 

        }

        $data=
            array(
                'fechaInicio' => $fechaInicio,
                'fechaFin'    => $fechaFin,
                'roles'       => $roles,            
                'id'          => $plaza_id,
                'usuarios'    => $usuarios,
                'activos'     => $activos,
                'nominas'     => $nominas,
                'turnos'      => $turnos,
                "dias"        => $dias,
                "semanas"     => $semanas,                                              
                'days'        => $days,                
                'conceptos'   => $conceptos, 
                'estado'      => $estado, 
                'matutinosBarra'   => $matutinosBarra,
                'vespertinosBarra'   => $vespertinosBarra,
                'matutinosMesas'   => $matutinosMesas,
                'vespertinosMesas'   => $vespertinosMesas,
                'matutinosCocina'   => $matutinosCocina,
                'vespertinosCocina'   => $vespertinosCocina,
                'matutinosApoyo'   => $matutinosApoyo,
                'vespertinosApoyo'   => $vespertinosApoyo,
                'acLunes'    => $acLunes,
                'acMartes'    => $acMartes,
                'acMiercoles'   => $acMiercoles,
                'acJueves'    => $acJueves,
                'acViernes'    => $acViernes,
                'acSabado'    => $acSabado,
                'acDomingo'    => $acDomingo, 

                'acBarra'    => $acBarra,
                'acMesas'    => $acMesas,
                'acCocina'    => $acCocina,
                'acApoyo'    => $acApoyo, 
                'acTurnosRol'    =>$acTotal, 

                'lugares'   => $lugares,
                'lunesE'      => $lunesE,
                'martesE'      => $martesE,
                'miercolesE'      => $miercolesE,
                'juevesE'      => $juevesE,
                'viernesE'      => $viernesE,
                'sabadoE'      => $sabadoE,
                'domingoE'      => $domingoE
            );
                
            $this->load->view("layouts/header");
            $this->load->view("layouts/aside");
            $this->load->view("manager/nominas/list",$data);        
            $this->load->view("layouts/footer");  
    }

    public function usuariosManager(){
        $roles=$this->Mdl_nominas->getRoles();  
       
        $activos=$this->Mdl_nominas->getActivos();
        $nominas=$this->Mdl_remesas->getInsumosID(15);
        $turnos=$this->Mdl_nominas->getTurnosNomina();
        
        $fechaHoy=date("Y-m-d");            
        $numeroDia=date('N', strtotime($fechaHoy));               
        $dias=$this->Mdl_nominas->getDiasNomina($numeroDia);
        $conceptos=$this->Mdl_nominas->getConceptos();

        $plaza_id=$this->session->userdata("plaza");
        $usuarios=$this->Mdl_nominas->getUsuarios($plaza_id);
        $lugares=$this->Mdl_nominas->getLugares();
        $days=$this->asignarDias2();

        $fechaInicio=$this->input->post("fechaInicioD");
        $fechaFin=$this->input->post("fechaFinD"); 
        $id_plaza=$this->input->post("plaza_idP");      
        
        $lunes=$this->fechaLunes(date("Y-m-d"));
        $domingo=$this->fechaDomingo(date("Y-m-d"));
        
        $semanas=$this->Mdl_nominas->getRolSemanal($plaza_id,$lunes,$domingo);                

        $matutinosBarra=$this->Mdl_nominas->getRolMatutino($plaza_id,$lunes,$domingo,1);
        $vespertinosBarra=$this->Mdl_nominas->getRolVespertino($plaza_id,$lunes,$domingo,1);

        $matutinosMesas=$this->Mdl_nominas->getRolMatutino($plaza_id,$lunes,$domingo,2);
        $vespertinosMesas=$this->Mdl_nominas->getRolVespertino($plaza_id,$lunes,$domingo,2);

        $matutinosCocina=$this->Mdl_nominas->getRolMatutino($plaza_id,$lunes,$domingo,3);
        $vespertinosCocina=$this->Mdl_nominas->getRolVespertino($plaza_id,$lunes,$domingo,3);

        $matutinosApoyo=$this->Mdl_nominas->getRolMatutino($plaza_id,$lunes,$domingo,4);
        $vespertinosApoyo=$this->Mdl_nominas->getRolVespertino($plaza_id,$lunes,$domingo,4);
        

        if($this->input->post("buscar")){
            if($fechaInicio==null){
                $lunes=$this->fechaLunes(date("Y-m-d"));
                $domingo=$this->fechaDomingo(date("Y-m-d")); 
                $numeroSemana=$this->getSemana(date("Y-m-d"));
                $lunesE=$this->getWeekDates(2026,$numeroSemana,1,true);
                $martesE=$this->getWeekDates(2026,$numeroSemana,2,true);
                $miercolesE=$this->getWeekDates(2026,$numeroSemana,3,true);
                $juevesE=$this->getWeekDates(2026,$numeroSemana,4,true);     
                $viernesE=$this->getWeekDates(2026,$numeroSemana,5,true);     
                $sabadoE=$this->getWeekDates(2026,$numeroSemana,6,true);     
                $domingoE=$this->getWeekDates(2026,$numeroSemana,7,true);     
            }else{                
                $lunes=$this->fechaLunes($fechaInicio);
                $domingo=$this->fechaDomingo($fechaInicio);
                $numeroSemana=$this->getSemana($fechaInicio);
                $lunesE=$this->getWeekDates(2026,$numeroSemana,1,true);
                $martesE=$this->getWeekDates(2026,$numeroSemana,2,true);
                $miercolesE=$this->getWeekDates(2026,$numeroSemana,3,true);
                $juevesE=$this->getWeekDates(2026,$numeroSemana,4,true);     
                $viernesE=$this->getWeekDates(2026,$numeroSemana,5,true);     
                $sabadoE=$this->getWeekDates(2026,$numeroSemana,6,true);     
                $domingoE=$this->getWeekDates(2026,$numeroSemana,7,true);     

            }

                $semanas=$this->Mdl_nominas->getRolSemanal($plaza_id,$lunes,$domingo);                                

                $matutinosBarra=$this->Mdl_nominas->getRolMatutino($plaza_id,$lunes,$domingo,1);
                $vespertinosBarra=$this->Mdl_nominas->getRolVespertino($plaza_id,$lunes,$domingo,1);

                $matutinosMesas=$this->Mdl_nominas->getRolMatutino($plaza_id,$lunes,$domingo,2);
                $vespertinosMesas=$this->Mdl_nominas->getRolVespertino($plaza_id,$lunes,$domingo,2);

                $matutinosCocina=$this->Mdl_nominas->getRolMatutino($plaza_id,$lunes,$domingo,3);
                $vespertinosCocina=$this->Mdl_nominas->getRolVespertino($plaza_id,$lunes,$domingo,3);

                $matutinosApoyo=$this->Mdl_nominas->getRolMatutino($plaza_id,$lunes,$domingo,4);
                $vespertinosApoyo=$this->Mdl_nominas->getRolVespertino($plaza_id,$lunes,$domingo,4);

                $fechaInicio=$lunes;
                $fechaFin=$domingo;
        }else{

            $lunes=$this->fechaLunes(date("Y-m-d"));
            $domingo=$this->fechaDomingo(date("Y-m-d"));                
            
            $semanas=$this->Mdl_nominas->getRolSemanal($plaza_id,$lunes,$domingo);                        
           
            $matutinosBarra=$this->Mdl_nominas->getRolMatutino($plaza_id,$lunes,$domingo,1);
            $vespertinosBarra=$this->Mdl_nominas->getRolVespertino($plaza_id,$lunes,$domingo,1);

            $matutinosMesas=$this->Mdl_nominas->getRolMatutino($plaza_id,$lunes,$domingo,2);
            $vespertinosMesas=$this->Mdl_nominas->getRolVespertino($plaza_id,$lunes,$domingo,2);

            $matutinosCocina=$this->Mdl_nominas->getRolMatutino($plaza_id,$lunes,$domingo,3);
            $vespertinosCocina=$this->Mdl_nominas->getRolVespertino($plaza_id,$lunes,$domingo,3);

            $matutinosApoyo=$this->Mdl_nominas->getRolMatutino($plaza_id,$lunes,$domingo,4);
            $vespertinosApoyo=$this->Mdl_nominas->getRolVespertino($plaza_id,$lunes,$domingo,4);

            $fechaInicio=$lunes;
            $fechaFin=$domingo;

            $numeroSemana=$this->getSemana(date("Y-m-d"));
            $lunesE=$this->getWeekDates(2026,$numeroSemana,1,true);
            $martesE=$this->getWeekDates(2026,$numeroSemana,2,true);
            $miercolesE=$this->getWeekDates(2026,$numeroSemana,3,true);
            $juevesE=$this->getWeekDates(2026,$numeroSemana,4,true);     
            $viernesE=$this->getWeekDates(2026,$numeroSemana,5,true);     
            $sabadoE=$this->getWeekDates(2026,$numeroSemana,6,true);     
            $domingoE=$this->getWeekDates(2026,$numeroSemana,7,true); 

        }
        $data=
            array(
                'fechaInicio' => $fechaInicio,
                'fechaFin'    => $fechaFin,
                'roles'       => $roles,            
                'id'          => $plaza_id,
                'usuarios'    => $usuarios,
                'activos'     => $activos,
                'nominas'     => $nominas,
                'turnos'      => $turnos,
                "dias"        => $dias,
                "semanas"     => $semanas,                                              
                'days'        => $days,                
                'conceptos'   => $conceptos,                
                'matutinosBarra'   => $matutinosBarra,
                'vespertinosBarra'   => $vespertinosBarra,
                'matutinosMesas'   => $matutinosMesas,
                'vespertinosMesas'   => $vespertinosMesas,
                'matutinosCocina'   => $matutinosCocina,
                'vespertinosCocina'   => $vespertinosCocina,
                'matutinosApoyo'   => $matutinosApoyo,
                'vespertinosApoyo'   => $vespertinosApoyo,
                'lugares'   => $lugares,
                'lunesE'      => $lunesE,
                'martesE'      => $martesE,
                'miercolesE'      => $miercolesE,
                'juevesE'      => $juevesE,
                'viernesE'      => $viernesE,
                'sabadoE'      => $sabadoE,
                'domingoE'      => $domingoE
            );
                
            $this->load->view("layouts/header");
            $this->load->view("layouts/aside");
            $this->load->view("manager/nominas/users",$data);        
            $this->load->view("layouts/footer");  
    }

    public function usuariosEliminadosManager(){                    
        $plaza_id=$this->session->userdata("plaza");
        $usuarios=$this->Mdl_nominas->getUsuariosEliminados($plaza_id);        
        $fechaInicio=$this->input->post("fechaInicioD");
        $fechaFin=$this->input->post("fechaFinD"); 
        $id_plaza=$this->input->post("plaza_idP");      
        
        $data=
            array(
                'fechaInicio' => $fechaInicio,
                'fechaFin'    => $fechaFin,                          
                'id'          => $plaza_id,
                'usuarios'    => $usuarios,                                
                                
            );
                
            $this->load->view("layouts/header");
            $this->load->view("layouts/aside");
            $this->load->view("manager/nominas/users_delete",$data);        
            $this->load->view("layouts/footer");  
    }

    public function usuariosEliminadosAdministrador(){                    
        
        $plaza_id=$this->input->post("plaza_id");        
        $fechaInicio=$this->input->post("fechaInicioD");
        $fechaFin=$this->input->post("fechaFinD"); 
        $id_plaza=$this->input->post("plaza_idP");  
        
        $plazas=$this->Mdl_plazas->getPlazas();
        
        
        if($plaza_id==null){
            $usuarios=$this->Mdl_nominas->getUsuariosEliminadosT();
            $nombrePlaza="";
        }else{
            $usuarios=$this->Mdl_nominas->getUsuariosEliminados($plaza_id);
            $nombrePlaza=$this->Mdl_plazas->getPlazaID($plaza_id);
            $nombrePlaza=$nombrePlaza->nombrePlaza;
        }

        
        $data=
            array(
                'fechaInicio' => $fechaInicio,
                'fechaFin'    => $fechaFin,                          
                'id'          => $plaza_id,
                'usuarios'    => $usuarios, 
                'plazas'    => $plazas,
                'nombrePlaza'    => $nombrePlaza,

            );
                
            $this->load->view("layouts/header");
            $this->load->view("layouts/aside");
            $this->load->view("administrador/nominas/users_delete",$data);        
            $this->load->view("layouts/footer");  
    }

    public function usuariosAdministrador(){
        
        $plazas=$this->Mdl_plazas->getPlazas();
        $roles=$this->Mdl_nominas->getRoles();                 
        $plaza_id=$this->input->post("plaza_id");
        $fechaInicio=$this->input->post("fechaInicioD");
        $fechaFin=$this->input->post("fechaFinD");

        
        if($plaza_id==null){
            $usuarios=$this->Mdl_nominas->getUsuariosT();
        }else{
            $usuarios=$this->Mdl_nominas->getUsuarios($plaza_id);
        }
                                          
        $lunes=$this->fechaLunes(date("Y-m-d"));
        $domingo=$this->fechaDomingo(date("Y-m-d"));

        if($this->input->post("buscar")){
            if($fechaInicio==null){
                $lunes=$this->fechaLunes(date("Y-m-d"));
                $domingo=$this->fechaDomingo(date("Y-m-d"));                 
            }else{                
                $lunes=$this->fechaLunes($fechaInicio);
                $domingo=$this->fechaDomingo($fechaInicio);                            
            }                
                $fechaInicio=$lunes;
                $fechaFin=$domingo;
        }else{
            $lunes=$this->fechaLunes(date("Y-m-d"));
            $domingo=$this->fechaDomingo(date("Y-m-d"));                                                               
            $fechaInicio=$lunes;
            $fechaFin=$domingo;                
        }
        
        $data=
            array(
                'fechaInicio' => $fechaInicio,
                'fechaFin'    => $fechaFin,
                'roles'       => $roles,            
                'id'          => $plaza_id,
                'usuarios'    => $usuarios,                                                                                                    
                'plazas'      => $plazas                
            );
                
            $this->load->view("layouts/header");
            $this->load->view("layouts/aside");
            $this->load->view("administrador/nominas/users",$data);        
            $this->load->view("layouts/footer");  
    }

    public function saveUsuario(){
        if($this->input->is_ajax_request()){ 

            $nombre=$this->input->post("nombre_agregar");
            $apellido_paterno=$this->input->post("apellido_paterno_agregar");   
            $apellido_materno=$this->input->post("apellido_materno_agregar");   
            $fecha_nacimiento=$this->input->post("fecha_nacimiento_agregar");
            $nombre_rol=$this->input->post("nombre_rol_agregar");

            $telefono=$this->input->post("telefono_agregar");
            $CURP=$this->input->post("CURP_agregar");
            $fecha_ingreso=$this->input->post("fecha_ingreso_agregar");
            $rol_nomina_id=$this->input->post("rol_id_agregar");                
            $sueldo=$this->input->post("sueldo_agregar");
            $plaza_id=$this->session->userdata("plaza");


            if(is_numeric($sueldo)){
                if($sueldo>0){
                   
                    $data=array(
                        "Nombre"=>$nombre,
                        "Apellido_Paterno"=>$apellido_paterno,                        
                        "Apellido_Materno"=>$apellido_materno,  
                        "Nombre_Rol"=>$nombre_rol,  
                        "plaza_id"=>$plaza_id,                        
                        "rol_nomina_id"=>$rol_nomina_id,
                        "Fecha_Nacimiento"=>$fecha_nacimiento,
                        "Fecha_Ingreso"=>$fecha_ingreso,                        
                        "Fecha_Egreso"=>"0000-00-00",
                        "Telefono"=>$telefono,
                        "CURP"=>$CURP,
                        "Sueldo"=>$sueldo
                    );

                    //GUARDAMOS EL ID DEL INSUMO INSERTADO EN CASO DE QUE NO SE INSERTO SE GUARD FALSE            
                    $usuario_id=$this->Mdl_nominas->saveUsuario($data);                    
                    //PRIMERO GUARDAMOS EL USUARIO
                    if($usuario_id!=false){
                    //DESPUES DE GUARDARLO REGISTRAMOS EL EVENTO DE INICIO LABORAL 
                        $data=array(
                            "usuario_nomina_id"=>$usuario_id,
                            "concepto_id"=>8,                        
                            "Fecha"=>$fecha_ingreso,                        
                            "Importe"=>0,                        
                            "Nota"=>"INGRESO A LA EMPRESA"                            
                        );
                        if($this->Mdl_nominas->saveExtra($data)){
                            echo 1;
                        }else{
                            echo 0;
                        }                                               
                    }
                    else{   
                        echo 0;
                    }
                } else{
                    echo 0;
                }
            }
                else{
                    echo 0;
            }                                                                        
        }   
        else{
                redirect(base_url()."Inicio");
          }   
    }

    public function agregarRolSemanal(){
        if($this->input->is_ajax_request()){                                                        

            $usuario_id=$this->input->post("usuario_agregar_id");
            $dia_id=$this->input->post("dia_id");
            $turno_id=$this->input->post("turno_id");
            $plaza_id=$this->session->userdata("plaza");
            $lugar_id=$this->input->post("lugar_id");

            $fechaInicio=date( 'Y-m-d', strtotime('monday this week'));
            $fechaFin=date( 'Y-m-d', strtotime('sunday this week' ));                     
            $banderaTurnos=false;            
            
            //AREA PARA CHECAR EL DIA SELECCIONADO Y ASIGNAR LA FECHA CORRECTA DE ESE DIA
                switch ($dia_id) {
                    case 1:
                            $fecha=date( 'Y-m-d', strtotime( 'monday this week' ));                      
                        break;
                        case 2:
                            $fecha=date( 'Y-m-d', strtotime( 'tuesday this week' ));                                                                              
                        break;

                        case 3:
                            $fecha=date( 'Y-m-d', strtotime('wednesday this week' )); 
                        break;

                        case 4:
                            $fecha=date( 'Y-m-d', strtotime('thursday this week' ));                                                         
                        break;

                        case 5: 
                            $fecha=date( 'Y-m-d', strtotime('friday this week' ));                                                                                                                                 
                        break;

                        case 6:                         
                            $fecha=date( 'Y-m-d', strtotime('saturday this week' ));                                                                                                                                 
                        break;

                        case 7:  
                            $fecha=date( 'Y-m-d', strtotime('sunday this week' ));                                                                                                                                                                                     
                        break;               
                }
            //AREA PARA CHECAR EL DIA SELECCIONADO Y ASIGNAR LA FECHA CORRECTA DE ESE DIA
            
            $banderaMatutino=false;
            $banderaVespertino=false;
            $banderaTurnoDoble=false; 
            
            if($fecha>=date("Y-m-d")){
                //AREA PARA VERIFICAR SI SE TRATA DEL TURNO MATUTINO O VESPERTINO
                    switch ($turno_id) {
                        case 1:
                            $turnos=1;
                            $nominas=$this->Mdl_nominas->checarTurnos($fecha,$usuario_id,$turno_id);
                            $nominas=$nominas->Total;
                            if($nominas<1){
                                $banderaMatutino=true;
                                $nominas=$this->Mdl_nominas->checarTurnos($fecha,$usuario_id,2);
                                $nominas=$nominas->Total;
                                if($nominas<1){                                
                                    $banderaVespertino=false;
                                }
                                else{
                                    $banderaVespertino=true;                                
                                }                        
                            }
                            else{
                                $banderaMatutino=false;
                            }

                            if($banderaMatutino){
                                if($banderaVespertino){                                         
                                    $valorSueldo=$this->Mdl_nominas->consultarSueldoUsuario($usuario_id);
                                    if($valorSueldo!=null){
                                        $valorSueldo=$valorSueldo->Sueldo;
                                        $data=array(
                                            "usuario_nomina_id"=>$usuario_id,
                                            "rol_cafeteria_id"=>$lugar_id,
                                            'plaza_id'=>$plaza_id,
                                            "Fecha"=>$fecha,
                                            "dia_id"=>$dia_id,
                                            "turno_id"=>$turno_id,
                                            "Sueldo"=>$valorSueldo,
                                            "Extra"=>0                                                                                                 
                                        );
                                        if($this->Mdl_nominas->saveRolNominasU($data,$fecha,$usuario_id,2)){
                                            echo 1;
                                        }
                                        else{   
                                            echo 0;
                                        }
                                    }else{
                                        echo 0;
                                    }                                    
                                }else{          
                                    
                                    $valorSueldo=$this->Mdl_nominas->consultarSueldoUsuario($usuario_id);
                                    if($valorSueldo!=null){
                                        $valorSueldo=$valorSueldo->Sueldo;
                                        $data=array(
                                            "usuario_nomina_id"=>$usuario_id,
                                            "rol_cafeteria_id"=>$lugar_id,
                                            'plaza_id'=>$plaza_id,
                                            "Fecha"=>$fecha,
                                            "dia_id"=>$dia_id,
                                            "turno_id"=>$turno_id,
                                            "Sueldo"=>$valorSueldo,
                                            "Extra"=>0                                                                                 
                                        );
    
                                        if($this->Mdl_nominas->saveRolNominas($data)){
                                            echo 1;
                                        }
                                        else{   
                                            echo 0;
                                        }
                                    }else{
                                        echo 0;
                                    }                                   
                                }                                                                            
                            }else{
                                //NO SE PUEDEN MAS DE DOS TURNOS EN EL MATUTINO
                                //echo "PASANDO NUMERO DE TURNOS";
                                echo 2;
                            }

                        break;
                        case 2:
                            $turnos=2;          

                            $nominas=$this->Mdl_nominas->checarTurnos($fecha,$usuario_id,$turno_id);
                            $nominas=$nominas->Total;
                            if($nominas<1){
                                $banderaVespertino=true;
                                $nominas=$this->Mdl_nominas->checarTurnos($fecha,$usuario_id,1);
                                $nominas=$nominas->Total;
                                if($nominas<1){                                
                                    $banderaMatutino=false;
                                }
                                else{
                                    $banderaMatutino=true;                                
                                }  
                            }
                            else{
                                $banderaVespertino=false;
                            }
                            if($banderaVespertino){
                                if($banderaMatutino){  
                                    
                                    $valorSueldo=$this->Mdl_nominas->consultarSueldoUsuario($usuario_id);
                                    if($valorSueldo!=null){
                                        $valorSueldo=$valorSueldo->Sueldo;
                                        $data=array(
                                            "usuario_nomina_id"=>$usuario_id,
                                            "rol_cafeteria_id"=>$lugar_id,
                                            'plaza_id'=>$plaza_id,
                                            "Fecha"=>$fecha,
                                            "dia_id"=>$dia_id,
                                            "turno_id"=>$turno_id,
                                            "Sueldo"=>$valorSueldo,
                                            "Extra"=>0                                                                                          
                                        );
    
                                        if($this->Mdl_nominas->saveRolNominasU($data,$fecha,$usuario_id,1)){
                                            echo 1;
                                        }
                                        else{   
                                            echo 0;
                                        }
                                    }else{
                                        echo 0;
                                    }                                                                      
                                }
                                else{

                                    $valorSueldo=$this->Mdl_nominas->consultarSueldoUsuario($usuario_id);
                                    if($valorSueldo!=null){
                                        $valorSueldo=$valorSueldo->Sueldo;
                                        $data=array(
                                            "usuario_nomina_id"=>$usuario_id,
                                            "rol_cafeteria_id"=>$lugar_id,
                                            'plaza_id'=>$plaza_id,
                                            "Fecha"=>$fecha,
                                            "dia_id"=>$dia_id,
                                            "turno_id"=>$turno_id,
                                            "Sueldo"=>$valorSueldo,
                                            "Extra"=>0                                                                                         
                                        );
    
                                        if($this->Mdl_nominas->saveRolNominas($data)){
                                            echo 1;
                                        }
                                        else{   
                                            echo 0;
                                        }
                                    }else{
                                        echo 0;
                                    }                                    
                                }                              
                            }else{
                                //NO SE PUEDEN MAS DE DOS TURNOS EN EL VESPERTINO
                                //echo "PASANDO NUMERO DE TURNOS";
                                echo 2;
                            }

                        break;
                        case 3:                                            
                            $nominasM=$this->Mdl_nominas->checarTurnos($fecha,$usuario_id,1);
                            $nominasM=$nominasM->Total;
                            $nominasV=$this->Mdl_nominas->checarTurnos($fecha,$usuario_id,2);
                            $nominasV=$nominasV->Total;
                            if($nominasM<1 && $nominasV<1){
                                $banderaTurnoDoble=true;                        
                            }else{
                                $banderaTurnoDoble=false;                        
                            }

                            if($banderaTurnoDoble){


                                $valorSueldo=$this->Mdl_nominas->consultarSueldoUsuario($usuario_id);
                                if($valorSueldo!=null){
                                    $valorSueldo=$valorSueldo->Sueldo;
                                    $data=array(
                                        array(
                                            "usuario_nomina_id"=>$usuario_id,
                                            "rol_cafeteria_id"=>$lugar_id,
                                            'plaza_id'=>$plaza_id,
                                            "Fecha"=>$fecha,
                                            "dia_id"=>$dia_id,
                                            "turno_id"=>1,
                                            "Sueldo"=>$valorSueldo,
                                            "Extra"=>0                                                                                                                                                
                                        ),
                                        array(
                                            "usuario_nomina_id"=>$usuario_id,
                                            "rol_cafeteria_id"=>$lugar_id,
                                            'plaza_id'=>$plaza_id,
                                            "Fecha"=>$fecha,
                                            "dia_id"=>$dia_id,
                                            "turno_id"=>2,
                                            "Sueldo"=>$valorSueldo,
                                            "Extra"=>0                                                                         
                                        )
                                    );

                                    if($this->Mdl_nominas->saveRolNominasDoble($data)){
                                        echo 1;
                                    }
                                    else{   
                                        echo 0;
                                    }
                                }else{                                    
                                    echo 0;
                                }                                                                                                                         
                            }else{
                                //echo "PASANDO NUMERO DE TURNOS";
                                //SE SOBREPASARIA EL NÚMERO DE TURNOS
                                echo 2;
                            }
                        break;                                                 
                    } 
                //AREA PARA VERIFICAR SI SE TRATA DEL TURNO MATUTINO O VESPERTINO   
            }else{
                //EN DADO CASO DE QUE SE QUIEREN AGREGAR DIAS ANTERIORES AL ACTUAL
                echo 3;
            }         
        }   
        else{
                redirect(base_url()."Inicio");
          }   
    }

    public function agregarRolSemanalAdmin(){
        if($this->input->is_ajax_request()){                                                        

            $usuario_id=$this->input->post("usuario_agregar_id");
            $dia_id=$this->input->post("dia_id");
            $turno_id=$this->input->post("turno_id");
            
            $plaza_id=$this->Mdl_nominas->getPlazaUsuario($usuario_id);
            $plaza_id=$plaza_id->Plaza;

            $lugar_id=$this->input->post("lugar_id");

            $fechaInicio=date( 'Y-m-d', strtotime('monday this week'));
            $fechaFin=date( 'Y-m-d', strtotime('sunday this week' ));                     
            $banderaTurnos=false;            
            
            //AREA PARA CHECAR EL DIA SELECCIONADO Y ASIGNAR LA FECHA CORRECTA DE ESE DIA
                switch ($dia_id) {
                    case 1:
                            $fecha=date( 'Y-m-d', strtotime( 'monday this week' ));                      
                        break;
                        case 2:
                            $fecha=date( 'Y-m-d', strtotime( 'tuesday this week' ));                                                                              
                        break;

                        case 3:
                            $fecha=date( 'Y-m-d', strtotime('wednesday this week' )); 
                        break;

                        case 4:
                            $fecha=date( 'Y-m-d', strtotime('thursday this week' ));                                                         
                        break;

                        case 5: 
                            $fecha=date( 'Y-m-d', strtotime('friday this week' ));                                                                                                                                 
                        break;

                        case 6:                         
                            $fecha=date( 'Y-m-d', strtotime('saturday this week' ));                                                                                                                                 
                        break;

                        case 7:  
                            $fecha=date( 'Y-m-d', strtotime('sunday this week' ));                                                                                                                                                                                     
                        break;               
                }
            //AREA PARA CHECAR EL DIA SELECCIONADO Y ASIGNAR LA FECHA CORRECTA DE ESE DIA
            
            $banderaMatutino=false;
            $banderaVespertino=false;
            $banderaTurnoDoble=false; 
                        
                //AREA PARA VERIFICAR SI SE TRATA DEL TURNO MATUTINO O VESPERTINO
                    switch ($turno_id) {
                        case 1:
                            $turnos=1;
                            $nominas=$this->Mdl_nominas->checarTurnos($fecha,$usuario_id,$turno_id);
                            $nominas=$nominas->Total;
                            if($nominas<1){
                                $banderaMatutino=true;
                                $nominas=$this->Mdl_nominas->checarTurnos($fecha,$usuario_id,2);
                                $nominas=$nominas->Total;
                                if($nominas<1){                                
                                    $banderaVespertino=false;
                                }
                                else{
                                    $banderaVespertino=true;                                
                                }                        
                            }
                            else{
                                $banderaMatutino=false;
                            }

                            if($banderaMatutino){
                                if($banderaVespertino){                                         
                                    $valorSueldo=$this->Mdl_nominas->consultarSueldoUsuario($usuario_id);
                                    if($valorSueldo!=null){
                                        $valorSueldo=$valorSueldo->Sueldo;
                                        $data=array(
                                            "usuario_nomina_id"=>$usuario_id,
                                            "rol_cafeteria_id"=>$lugar_id,
                                            'plaza_id'=>$plaza_id,
                                            "Fecha"=>$fecha,
                                            "dia_id"=>$dia_id,
                                            "turno_id"=>$turno_id,
                                            "Sueldo"=>$valorSueldo,
                                            "Extra"=>0                                                                                                 
                                        );
                                        if($this->Mdl_nominas->saveRolNominasU($data,$fecha,$usuario_id,2)){
                                            echo 1;
                                        }
                                        else{   
                                            echo 0;
                                        }
                                    }else{
                                        echo 0;
                                    }                                    
                                }else{          
                                    
                                    $valorSueldo=$this->Mdl_nominas->consultarSueldoUsuario($usuario_id);
                                    if($valorSueldo!=null){
                                        $valorSueldo=$valorSueldo->Sueldo;
                                        $data=array(
                                            "usuario_nomina_id"=>$usuario_id,
                                            "rol_cafeteria_id"=>$lugar_id,
                                            'plaza_id'=>$plaza_id,
                                            "Fecha"=>$fecha,
                                            "dia_id"=>$dia_id,
                                            "turno_id"=>$turno_id,
                                            "Sueldo"=>$valorSueldo,
                                            "Extra"=>0                                                                                 
                                        );
    
                                        if($this->Mdl_nominas->saveRolNominas($data)){
                                            echo 1;
                                        }
                                        else{   
                                            echo 0;
                                        }
                                    }else{
                                        echo 0;
                                    }                                   
                                }                                                                            
                            }else{
                                //NO SE PUEDEN MAS DE DOS TURNOS EN EL MATUTINO
                                //echo "PASANDO NUMERO DE TURNOS";
                                echo 2;
                            }

                        break;
                        case 2:
                            $turnos=2;          

                            $nominas=$this->Mdl_nominas->checarTurnos($fecha,$usuario_id,$turno_id);
                            $nominas=$nominas->Total;
                            if($nominas<1){
                                $banderaVespertino=true;
                                $nominas=$this->Mdl_nominas->checarTurnos($fecha,$usuario_id,1);
                                $nominas=$nominas->Total;
                                if($nominas<1){                                
                                    $banderaMatutino=false;
                                }
                                else{
                                    $banderaMatutino=true;                                
                                }  
                            }
                            else{
                                $banderaVespertino=false;
                            }
                            if($banderaVespertino){
                                if($banderaMatutino){  
                                    
                                    $valorSueldo=$this->Mdl_nominas->consultarSueldoUsuario($usuario_id);
                                    if($valorSueldo!=null){
                                        $valorSueldo=$valorSueldo->Sueldo;
                                        $data=array(
                                            "usuario_nomina_id"=>$usuario_id,
                                            "rol_cafeteria_id"=>$lugar_id,
                                            'plaza_id'=>$plaza_id,
                                            "Fecha"=>$fecha,
                                            "dia_id"=>$dia_id,
                                            "turno_id"=>$turno_id,
                                            "Sueldo"=>$valorSueldo,
                                            "Extra"=>0                                                                                          
                                        );
    
                                        if($this->Mdl_nominas->saveRolNominasU($data,$fecha,$usuario_id,1)){
                                            echo 1;
                                        }
                                        else{   
                                            echo 0;
                                        }
                                    }else{
                                        echo 0;
                                    }                                                                      
                                }
                                else{

                                    $valorSueldo=$this->Mdl_nominas->consultarSueldoUsuario($usuario_id);
                                    if($valorSueldo!=null){
                                        $valorSueldo=$valorSueldo->Sueldo;
                                        $data=array(
                                            "usuario_nomina_id"=>$usuario_id,
                                            "rol_cafeteria_id"=>$lugar_id,
                                            'plaza_id'=>$plaza_id,
                                            "Fecha"=>$fecha,
                                            "dia_id"=>$dia_id,
                                            "turno_id"=>$turno_id,
                                            "Sueldo"=>$valorSueldo,
                                            "Extra"=>0                                                                                         
                                        );
    
                                        if($this->Mdl_nominas->saveRolNominas($data)){
                                            echo 1;
                                        }
                                        else{   
                                            echo 0;
                                        }
                                    }else{
                                        echo 0;
                                    }                                    
                                }                              
                            }else{
                                //NO SE PUEDEN MAS DE DOS TURNOS EN EL VESPERTINO
                                //echo "PASANDO NUMERO DE TURNOS";
                                echo 2;
                            }

                        break;
                        case 3:                                            
                            $nominasM=$this->Mdl_nominas->checarTurnos($fecha,$usuario_id,1);
                            $nominasM=$nominasM->Total;
                            $nominasV=$this->Mdl_nominas->checarTurnos($fecha,$usuario_id,2);
                            $nominasV=$nominasV->Total;
                            if($nominasM<1 && $nominasV<1){
                                $banderaTurnoDoble=true;                        
                            }else{
                                $banderaTurnoDoble=false;                        
                            }

                            if($banderaTurnoDoble){


                                $valorSueldo=$this->Mdl_nominas->consultarSueldoUsuario($usuario_id);
                                if($valorSueldo!=null){
                                    $valorSueldo=$valorSueldo->Sueldo;
                                    $data=array(
                                        array(
                                            "usuario_nomina_id"=>$usuario_id,
                                            "rol_cafeteria_id"=>$lugar_id,
                                            'plaza_id'=>$plaza_id,
                                            "Fecha"=>$fecha,
                                            "dia_id"=>$dia_id,
                                            "turno_id"=>1,
                                            "Sueldo"=>$valorSueldo,
                                            "Extra"=>0                                                                                                                                                
                                        ),
                                        array(
                                            "usuario_nomina_id"=>$usuario_id,
                                            "rol_cafeteria_id"=>$lugar_id,
                                            'plaza_id'=>$plaza_id,
                                            "Fecha"=>$fecha,
                                            "dia_id"=>$dia_id,
                                            "turno_id"=>2,
                                            "Sueldo"=>$valorSueldo,
                                            "Extra"=>0                                                                         
                                        )
                                    );

                                    if($this->Mdl_nominas->saveRolNominasDoble($data)){
                                        echo 1;
                                    }
                                    else{   
                                        echo 0;
                                    }
                                }else{                                    
                                    echo 0;
                                }                                                                                                                         
                            }else{
                                //echo "PASANDO NUMERO DE TURNOS";
                                //SE SOBREPASARIA EL NÚMERO DE TURNOS
                                echo 2;
                            }
                        break;                                                 
                    } 
                //AREA PARA VERIFICAR SI SE TRATA DEL TURNO MATUTINO O VESPERTINO   
                  
        }   
        else{
                redirect(base_url()."Inicio");
          }   
    }

    public function actualizarRolSemanal(){
        if($this->input->is_ajax_request()){                                    
            $banderaError=false;
            $usuario_id=$this->input->post("id_editarN");
            $fecha_id=$this->input->post("dia_editarN");
            $matutino_editar=$this->input->post("matutino_editar");
            $vespertino_editar=$this->input->post("vespertino_editar");            
            $plaza_id=$this->session->userdata("plaza");               
            $numeroDia=date('N', strtotime(date("Y-m-d")));            
            $numeroDiaComparacion=date('N', strtotime($fechaInicio));                                                

            switch ($fecha_id) {
                case 0:                    
                    $fechaInicio=date( 'Y-m-d', strtotime( 'monday this week' ));         
                    $fechaFin=date('Y-m-d', strtotime( 'monday this week' ));
                    $numeroDiaComparacion=date('N', strtotime($fechaInicio)); 
                break;

                case 1:        
                    $fechaInicio=date( 'Y-m-d', strtotime( 'tuesday this week' ));           
                    $fechaFin=date('Y-m-d', strtotime( 'tuesday this week' ));
                    $numeroDiaComparacion=date('N', strtotime($fechaInicio));
                break;

                case 2:                    
                    $fechaInicio=date('Y-m-d', strtotime('wednesday this week' ));       
                    $fechaFin=date('Y-m-d', strtotime('wednesday this week' ));
                    $numeroDiaComparacion=date('N', strtotime($fechaInicio));
                break;

                case 3:                    
                    $fechaInicio=date('Y-m-d', strtotime('thursday this week' ));            
                    $fechaFin=date('Y-m-d', strtotime('thursday this week' )); 
                    $numeroDiaComparacion=date('N', strtotime($fechaInicio));
                break;

                case 4:                    
                    $fechaInicio=date('Y-m-d', strtotime('friday this week' ));             
                    $fechaFin=date('Y-m-d', strtotime('friday this week' )); 
                    $numeroDiaComparacion=date('N', strtotime($fechaInicio)); 
                break;

                case 5:                    
                    $fechaInicio=date( 'Y-m-d', strtotime('saturday this week' ));         
                    $fechaFin=date( 'Y-m-d', strtotime('saturday this week' )); 
                    $numeroDiaComparacion=date('N', strtotime($fechaInicio));    
                break;

                case 6:        
                    $fechaInicio=date( 'Y-m-d', strtotime('sunday this week' ));         
                    $fechaFin=date( 'Y-m-d', strtotime('sunday this week' ));              
                    $numeroDiaComparacion=date('N', strtotime($fechaInicio));
                break;                                
            }     


            if($numeroDiaComparacion>=$numeroDia){

            }else{

            }





            if($matutino_editar==5 && $vespertino_editar==5){
                if($this->Mdl_nominas->eliminarTurnos($usuario_id,$fechaInicio,$fechaFin)){
                        
                }else{                    
                    $banderaError=true;
                } 
            }
            else if($matutino_editar==5){                    
                if($this->Mdl_nominas->eliminarTurnoEspecifico($usuario_id,1,$fechaInicio,$fechaFin)){
                    //EN DADO CASO DE QUE YA EXISTA EL REGISTRO VESPERTINO LO VAMOS A ACTUALIZAR  
                    $disponibilidad=$this->Mdl_nominas->existenciaTurno2($usuario_id,2,$fechaInicio,$fechaFin);    
                    if($disponibilidad!=null){                        
                        $data=array("rol_cafeteria_id"=>$vespertino_editar);                
                        if($this->Mdl_nominas->actualizarTurnoEspecifico($usuario_id,2,$fechaInicio,$fechaFin,$data)){

                        }else{
                            $banderaError=true;
                        }
                    //EN DADO CASO DE QUE NO EXISTA EL REGISTRO VESPERTINO LO VAMOS A INSERTAR
                    }else{      
                        $valorSueldo=$this->Mdl_nominas->consultarSueldoUsuario($usuario_id);
                        if($valorSueldo!=null){
                            $valorSueldo=$valorSueldo->Sueldo;
                            $data=array(
                                "usuario_nomina_id"=>$usuario_id,
                                'rol_cafeteria_id'=>$vespertino_editar,
                                'plaza_id'=>$plaza_id,
                                "Fecha"=>$fechaInicio,
                                "dia_id"=>$fecha_id+1,
                                "turno_id"=>2,
                                "Sueldo"=>$valorSueldo                                    
                            );                                                              
                            if($this->Mdl_nominas->saveRolNominas($data)){
                                
                            }else{
                                $banderaError=true;
                            }
                        }else{
                            $banderaError=true;
                        }                                       
                    }                    
                }else{
                    $banderaError=true;
                }
            } 
            else if($vespertino_editar==5){
                if($this->Mdl_nominas->eliminarTurnoEspecifico($usuario_id,2,$fechaInicio,$fechaFin)){
                    //EN DADO CASO DE QUE YA EXISTA EL REGISTRO VESPERTINO LO VAMOS A ACTUALIZAR  
                    $disponibilidad=$this->Mdl_nominas->existenciaTurno2($usuario_id,1,$fechaInicio,$fechaFin);    
                    if($disponibilidad!=null){                        
                        $data=array("rol_cafeteria_id"=>$matutino_editar);                
                        if($this->Mdl_nominas->actualizarTurnoEspecifico($usuario_id,1,$fechaInicio,$fechaFin,$data)){

                        }else{
                            $banderaError=true;

                        }
                    //EN DADO CASO DE QUE NO EXISTA EL REGISTRO VESPERTINO LO VAMOS A INSERTAR
                    }else{
                        $valorSueldo=$this->Mdl_nominas->consultarSueldoUsuario($usuario_id);
                        if($valorSueldo!=null){
                            $valorSueldo=$valorSueldo->Sueldo;
                            $data=array(
                                "usuario_nomina_id"=>$usuario_id,
                                'rol_cafeteria_id'=>$matutino_editar,
                                'plaza_id'=>$plaza_id,
                                "Fecha"=>$fechaInicio,
                                "dia_id"=>$fecha_id+1,
                                "turno_id"=>1,
                                "Sueldo"=>$valorSueldo                                                                            
                            );                                                              
                            if($this->Mdl_nominas->saveRolNominas($data)){
                                
                            }else{
                                $banderaError=true;
                            }
                        }else{
                            $banderaError=true;
                        }                                                
                    }                    
                }else{
                    $banderaError=true;
                }                                    
            }            
            else{       
                /////////////////////////////////////////////////////////////////////////////////////////////////////
                //BLOQUE MATUTINO
                        $disponibilidad1=$this->Mdl_nominas->existenciaTurno2($usuario_id,1,$fechaInicio,$fechaFin);    
                        if($disponibilidad1!=null){                        
                            $data=array("rol_cafeteria_id"=>$matutino_editar);                
                            if($this->Mdl_nominas->actualizarTurnoEspecifico($usuario_id,1,$fechaInicio,$fechaFin,$data)){

                            }else{
                                $banderaError=true;
                            }
                        //EN DADO CASO DE QUE NO EXISTA EL REGISTRO VESPERTINO LO VAMOS A INSERTAR
                        }else{          
                            $valorSueldo=$this->Mdl_nominas->consultarSueldoUsuario($usuario_id);
                            if($valorSueldo!=null){
                                $valorSueldo=$valorSueldo->Sueldo;
                                $data=array(
                                    "usuario_nomina_id"=>$usuario_id,
                                    'rol_cafeteria_id'=>$matutino_editar,
                                    'plaza_id'=>$plaza_id,
                                    "Fecha"=>$fechaInicio,
                                    "dia_id"=>$fecha_id+1,
                                    "turno_id"=>1,
                                    "Sueldo"=>$valorSueldo                                        
                                );                                                              
                                if($this->Mdl_nominas->saveRolNominas($data)){
                                    
                                }else{
                                    $banderaError=true;
                                }
                            }
                            else{
                                $banderaError=true;
                            }                                  
                        }
                //BLOQUE MATUTINO
                /////////////////////////////////////////////////////////////////////////////////////////////////////                    

                
                /////////////////////////////////////////////////////////////////////////////////////////////////////
                //BLOQUE VESPERTINO            
                    $disponibilidad2=$this->Mdl_nominas->existenciaTurno2($usuario_id,2,$fechaInicio,$fechaFin);    
                    if($disponibilidad2!=null){                        
                        $data=array("rol_cafeteria_id"=>$vespertino_editar);                
                        if($this->Mdl_nominas->actualizarTurnoEspecifico($usuario_id,2,$fechaInicio,$fechaFin,$data)){

                        }else{
                            $banderaError=true;
                        }
                //EN DADO CASO DE QUE NO EXISTA EL REGISTRO VESPERTINO LO VAMOS A INSERTAR
                    }else{          
                        $valorSueldo=$this->Mdl_nominas->consultarSueldoUsuario($usuario_id);
                        if($valorSueldo!=null){
                            $valorSueldo=$valorSueldo->Sueldo;
                            $data=array(
                                "usuario_nomina_id"=>$usuario_id,
                                'rol_cafeteria_id'=>$vespertino_editar,
                                'plaza_id'=>$plaza_id,
                                "Fecha"=>$fechaInicio,
                                "dia_id"=>$fecha_id+1,
                                "turno_id"=>2,
                                "Sueldo"=>$valorSueldo                                  
                            );  

                            if($this->Mdl_nominas->saveRolNominas($data)){
                                
                            }else{
                                $banderaError=true;
                            }
                        }else{
                            $banderaError=true;
                        }                    
                    }
                //BLOQUE VESPERTINO
                /////////////////////////////////////////////////////////////////////////////////////////////////////                    
            }     
            
            
            if($banderaError==false){
                echo 1;
            }else{
                echo 0;
            }
        }   
        else
        {
            redirect(base_url()."Inicio");
        }   
    }

    public function actualizarRolSemanalAdmin(){
        if($this->input->is_ajax_request()){                        
            $banderaError=false;

            $usuario_id=$this->input->post("id_editarN");
            $fecha_id=$this->input->post("dia_editarN");
            $matutino_editar=$this->input->post("matutino_editar");
            $vespertino_editar=$this->input->post("vespertino_editar");                        
            $plaza_id=$this->Mdl_nominas->getPlazaUsuario($usuario_id);
            $plaza_id=$plaza_id->Plaza;

            switch ($fecha_id) {
                case 0:                    
                    $fechaInicio=date( 'Y-m-d', strtotime( 'monday this week' ));         
                    $fechaFin=date('Y-m-d', strtotime( 'monday this week' )); 
                break;

                case 1:        
                    $fechaInicio=date( 'Y-m-d', strtotime( 'tuesday this week' ));           
                    $fechaFin=date('Y-m-d', strtotime( 'tuesday this week' ));
                break;

                case 2:                    
                    $fechaInicio=date('Y-m-d', strtotime('wednesday this week' ));       
                    $fechaFin=date('Y-m-d', strtotime('wednesday this week' ));
                break;

                case 3:                    
                    $fechaInicio=date('Y-m-d', strtotime('thursday this week' ));            
                    $fechaFin=date('Y-m-d', strtotime('thursday this week' )); 
                break;

                case 4:                    
                    $fechaInicio=date('Y-m-d', strtotime('friday this week' ));             
                    $fechaFin=date('Y-m-d', strtotime('friday this week' ));  
                break;

                case 5:                    
                    $fechaInicio=date( 'Y-m-d', strtotime('saturday this week' ));         
                    $fechaFin=date( 'Y-m-d', strtotime('saturday this week' ));     
                break;

                case 6:        
                    $fechaInicio=date( 'Y-m-d', strtotime('sunday this week' ));         
                    $fechaFin=date( 'Y-m-d', strtotime('sunday this week' ));              
                break;                                
            }     


            if($matutino_editar==5 && $vespertino_editar==5){
                if($this->Mdl_nominas->eliminarTurnos($usuario_id,$fechaInicio,$fechaFin)){
                        
                }else{                    
                    $banderaError=true;
                } 
            }
            else if($matutino_editar==5){                    
                if($this->Mdl_nominas->eliminarTurnoEspecifico($usuario_id,1,$fechaInicio,$fechaFin)){
                    //EN DADO CASO DE QUE YA EXISTA EL REGISTRO VESPERTINO LO VAMOS A ACTUALIZAR  
                    $disponibilidad=$this->Mdl_nominas->existenciaTurno2($usuario_id,2,$fechaInicio,$fechaFin);    
                    if($disponibilidad!=null){                        
                        $data=array("rol_cafeteria_id"=>$vespertino_editar);                
                        if($this->Mdl_nominas->actualizarTurnoEspecifico($usuario_id,2,$fechaInicio,$fechaFin,$data)){

                        }else{
                            $banderaError=true;
                        }
                    //EN DADO CASO DE QUE NO EXISTA EL REGISTRO VESPERTINO LO VAMOS A INSERTAR
                    }else{      
                        $valorSueldo=$this->Mdl_nominas->consultarSueldoUsuario($usuario_id);
                        if($valorSueldo!=null){
                            $valorSueldo=$valorSueldo->Sueldo;
                            $data=array(
                                "usuario_nomina_id"=>$usuario_id,
                                'rol_cafeteria_id'=>$vespertino_editar,
                                'plaza_id'=>$plaza_id,
                                "Fecha"=>$fechaInicio,
                                "dia_id"=>$fecha_id+1,
                                "turno_id"=>2,
                                "Sueldo"=>$valorSueldo                                    
                            );                                                              
                            if($this->Mdl_nominas->saveRolNominas($data)){
                                
                            }else{
                                $banderaError=true;
                            }
                        }else{
                            $banderaError=true;
                        }                                       
                    }                    
                }else{
                    $banderaError=true;
                }
            } 
            else if($vespertino_editar==5){
                if($this->Mdl_nominas->eliminarTurnoEspecifico($usuario_id,2,$fechaInicio,$fechaFin)){
                    //EN DADO CASO DE QUE YA EXISTA EL REGISTRO VESPERTINO LO VAMOS A ACTUALIZAR  
                    $disponibilidad=$this->Mdl_nominas->existenciaTurno2($usuario_id,1,$fechaInicio,$fechaFin);    
                    if($disponibilidad!=null){                        
                        $data=array("rol_cafeteria_id"=>$matutino_editar);                
                        if($this->Mdl_nominas->actualizarTurnoEspecifico($usuario_id,1,$fechaInicio,$fechaFin,$data)){

                        }else{
                            $banderaError=true;

                        }
                    //EN DADO CASO DE QUE NO EXISTA EL REGISTRO VESPERTINO LO VAMOS A INSERTAR
                    }else{
                        $valorSueldo=$this->Mdl_nominas->consultarSueldoUsuario($usuario_id);
                        if($valorSueldo!=null){
                            $valorSueldo=$valorSueldo->Sueldo;
                            $data=array(
                                "usuario_nomina_id"=>$usuario_id,
                                'rol_cafeteria_id'=>$matutino_editar,
                                'plaza_id'=>$plaza_id,
                                "Fecha"=>$fechaInicio,
                                "dia_id"=>$fecha_id+1,
                                "turno_id"=>1,
                                "Sueldo"=>$valorSueldo                                                                            
                            );                                                              
                            if($this->Mdl_nominas->saveRolNominas($data)){
                                
                            }else{
                                $banderaError=true;
                            }
                        }else{
                            $banderaError=true;
                        }                                                
                    }                    
                }else{
                    $banderaError=true;
                }                                    
            }            
            else{       

            /////////////////////////////////////////////////////////////////////////////////////////////////////
            //BLOQUE MATUTINO
                    $disponibilidad1=$this->Mdl_nominas->existenciaTurno2($usuario_id,1,$fechaInicio,$fechaFin);    
                    if($disponibilidad1!=null){                        
                        $data=array("rol_cafeteria_id"=>$matutino_editar);                
                        if($this->Mdl_nominas->actualizarTurnoEspecifico($usuario_id,1,$fechaInicio,$fechaFin,$data)){

                        }else{
                            $banderaError=true;
                        }
                    //EN DADO CASO DE QUE NO EXISTA EL REGISTRO VESPERTINO LO VAMOS A INSERTAR
                    }else{          
                        $valorSueldo=$this->Mdl_nominas->consultarSueldoUsuario($usuario_id);
                        if($valorSueldo!=null){
                            $valorSueldo=$valorSueldo->Sueldo;
                            $data=array(
                                "usuario_nomina_id"=>$usuario_id,
                                'rol_cafeteria_id'=>$matutino_editar,
                                'plaza_id'=>$plaza_id,
                                "Fecha"=>$fechaInicio,
                                "dia_id"=>$fecha_id+1,
                                "turno_id"=>1,
                                "Sueldo"=>$valorSueldo                                        
                            );                                                              
                            if($this->Mdl_nominas->saveRolNominas($data)){
                                
                            }else{
                                $banderaError=true;
                            }
                        }
                        else{
                            $banderaError=true;
                        }                                  
                    }
            //BLOQUE MATUTINO
            /////////////////////////////////////////////////////////////////////////////////////////////////////                    

            
            /////////////////////////////////////////////////////////////////////////////////////////////////////
            //BLOQUE VESPERTINO            
                $disponibilidad2=$this->Mdl_nominas->existenciaTurno2($usuario_id,2,$fechaInicio,$fechaFin);    
                if($disponibilidad2!=null){                        
                    $data=array("rol_cafeteria_id"=>$vespertino_editar);                
                    if($this->Mdl_nominas->actualizarTurnoEspecifico($usuario_id,2,$fechaInicio,$fechaFin,$data)){

                    }else{
                        $banderaError=true;
                    }
            //EN DADO CASO DE QUE NO EXISTA EL REGISTRO VESPERTINO LO VAMOS A INSERTAR
                }else{          
                    $valorSueldo=$this->Mdl_nominas->consultarSueldoUsuario($usuario_id);
                    if($valorSueldo!=null){
                        $valorSueldo=$valorSueldo->Sueldo;
                        $data=array(
                            "usuario_nomina_id"=>$usuario_id,
                            'rol_cafeteria_id'=>$vespertino_editar,
                            'plaza_id'=>$plaza_id,
                            "Fecha"=>$fechaInicio,
                            "dia_id"=>$fecha_id+1,
                            "turno_id"=>2,
                            "Sueldo"=>$valorSueldo                                  
                        );  

                        if($this->Mdl_nominas->saveRolNominas($data)){
                            
                        }else{
                            $banderaError=true;
                        }
                    }else{
                        $banderaError=true;
                    }                    
                }
            //BLOQUE VESPERTINO
            /////////////////////////////////////////////////////////////////////////////////////////////////////                    
            }     
            
            
            if($banderaError==false){
                echo 1;
            }else{
                echo 0;
            }
        }   
        else
        {
            redirect(base_url()."Inicio");
        }   
    }
    
    public function asignarDias(){           
        if($this->input->is_ajax_request()){ 
            $fechaInicio=$this->input->post('fechaInicio');                                                                
            $numeroDia=date('N', strtotime($fechaInicio));                    
            $output="";

            $fechaLunes=date( 'Y-m-d', strtotime( 'monday this week' ));                              
            $fechaMartes=date( 'Y-m-d', strtotime( 'tuesday this week' ));                                                                                      
            $fechaMiercoles=date( 'Y-m-d', strtotime('wednesday this week' ));         
            $fechaJueves=date( 'Y-m-d', strtotime('thursday this week' ));                                                                 
            $fechaViernes=date( 'Y-m-d', strtotime('friday this week' ));                                                                                                                                         
            $fechaSabado=date( 'Y-m-d', strtotime('saturday this week' ));                                                                                                                                         
            $fechaDomingo=date( 'Y-m-d', strtotime('sunday this week' ));   

            $diaLunes=date('d', strtotime( 'monday this week' ));                              
            $diaMartes=date('d', strtotime( 'tuesday this week' ));                                                                                      
            $diaMiercoles=date('d', strtotime('wednesday this week' ));         
            $diaJueves=date('d', strtotime('thursday this week' ));                                                                 
            $diaViernes=date('d', strtotime('friday this week' ));                                                                                                                                         
            $diaSabado=date('d', strtotime('saturday this week' ));                                                                                                                                         
            $diaDomingo=date('d', strtotime('sunday this week' ));   
            
            $data=array($fechaLunes,$fechaMartes,$fechaMiercoles,$fechaJueves,$fechaViernes,$fechaSabado,$fechaDomingo);
            $dataNombres=array('Lunes '.$diaLunes,'Martes '.$diaMartes,'Miercoles '.$diaMiercoles,'Jueves '.$diaJueves,'Viernes '.$diaViernes,'Sabado '.$diaSabado,'Domingo '.$diaDomingo);


            for ($i=0; $i <sizeof($data); $i++) { 
                $output .= '<option value="'.array_search($data[$i],$data).'">'.$dataNombres[$i].'</option>';
            }




            echo $output;            
        }   
        else{
                redirect(base_url()."Inicio");
            }                                
    }

    public function asignarDiasManager(){           
        if($this->input->is_ajax_request()){ 
            $fechaInicio=$this->input->post('fechaInicio');                                                                
            $numeroDia=date('N', strtotime($fechaInicio));                    
            $output="";

            $fechaLunes=date( 'Y-m-d', strtotime( 'monday this week' ));                              
            $fechaMartes=date( 'Y-m-d', strtotime( 'tuesday this week' ));                                                                                      
            $fechaMiercoles=date( 'Y-m-d', strtotime('wednesday this week' ));         
            $fechaJueves=date( 'Y-m-d', strtotime('thursday this week' ));                                                                 
            $fechaViernes=date( 'Y-m-d', strtotime('friday this week' ));                                                                                                                                         
            $fechaSabado=date( 'Y-m-d', strtotime('saturday this week' ));                                                                                                                                         
            $fechaDomingo=date( 'Y-m-d', strtotime('sunday this week' ));   

            $diaLunes=date('d', strtotime( 'monday this week' ));                              
            $diaMartes=date('d', strtotime( 'tuesday this week' ));                                                                                      
            $diaMiercoles=date('d', strtotime('wednesday this week' ));         
            $diaJueves=date('d', strtotime('thursday this week' ));                                                                 
            $diaViernes=date('d', strtotime('friday this week' ));                                                                                                                                         
            $diaSabado=date('d', strtotime('saturday this week' ));                                                                                                                                         
            $diaDomingo=date('d', strtotime('sunday this week' ));   
            
            $data=array($fechaLunes,$fechaMartes,$fechaMiercoles,$fechaJueves,$fechaViernes,$fechaSabado,$fechaDomingo);
            $dataNombres=array('Lunes '.$diaLunes,'Martes '.$diaMartes,'Miercoles '.$diaMiercoles,'Jueves '.$diaJueves,'Viernes '.$diaViernes,'Sabado '.$diaSabado,'Domingo '.$diaDomingo);
         
            switch ($numeroDia){
                case 1:                
                for ($i=0; $i <sizeof($data); $i++) { 
                    $output .= '<option value="'.array_search($data[$i],$data).'">'.$dataNombres[$i].'</option>';
                }                   
                break;

                case 2:  
                for ($i=1; $i <sizeof($data); $i++) { 
                    $output .= '<option value="'.array_search($data[$i],$data).'">'.$dataNombres[$i].'</option>';
                }
                   
                break;

                case 3:    
                for ($i=2; $i <sizeof($data); $i++) { 
                    $output .= '<option value="'.array_search($data[$i],$data).'">'.$dataNombres[$i].'</option>';
                }   
                break;

                case 4:                                    
                for ($i=3; $i <sizeof($data); $i++) { 
                    $output .= '<option value="'.array_search($data[$i],$data).'">'.$dataNombres[$i].'</option>';
                }   
                break;

                case 5:                                    
                for ($i=4; $i <sizeof($data); $i++) { 
                    $output .= '<option value="'.array_search($data[$i],$data).'">'.$dataNombres[$i].'</option>';
                }  
                break;

                case 6:                                    
                for ($i=5; $i <sizeof($data); $i++) { 
                    $output .= '<option value="'.array_search($data[$i],$data).'">'.$dataNombres[$i].'</option>';
                }    
                break;

                case 7:                                    
                for ($i=6; $i <sizeof($data); $i++) { 
                    $output .= '<option value="'.array_search($data[$i],$data).'">'.$dataNombres[$i].'</option>';
                }    
                break;                             
            }
            echo $output;            
        }   
        else{
                redirect(base_url()."Inicio");
            }                                
    }

    public function asignarDias2(){                               
            $fechaInicio=date("Y-m-d");            
            $numeroDia=date('N', strtotime($fechaInicio));                    
            $fechaLunes=date( 'Y-m-d', strtotime( 'monday this week' ));                              
            $fechaMartes=date( 'Y-m-d', strtotime( 'tuesday this week' ));                                                                                      
            $fechaMiercoles=date( 'Y-m-d', strtotime('wednesday this week' ));         
            $fechaJueves=date( 'Y-m-d', strtotime('thursday this week' ));                                                                 
            $fechaViernes=date( 'Y-m-d', strtotime('friday this week' ));                                                                                                                                         
            $fechaSabado=date( 'Y-m-d', strtotime('saturday this week' ));                                                                                                                                         
            $fechaDomingo=date( 'Y-m-d', strtotime('sunday this week' ));   
            $data=array();

            switch ($numeroDia){
                case 1:                
                    $data=array($fechaLunes,$fechaMartes,$fechaMiercoles,$fechaJueves,$fechaViernes,$fechaSabado,$fechaDomingo);
                break;

                case 2:  
                    $data=array($fechaMartes,$fechaMiercoles,$fechaJueves,$fechaViernes,$fechaSabado,$fechaDomingo);      
                break;

                case 3:    
                    $data=array($fechaMiercoles,$fechaJueves,$fechaViernes,$fechaSabado,$fechaDomingo);                       
                break;

                case 4:                                    
                    $data=array($fechaJueves,$fechaViernes,$fechaSabado,$fechaDomingo);                       
                break;

                case 5:                                    
                    $data=array($fechaViernes,$fechaSabado,$fechaDomingo);                       
                break;

                case 6:                                    
                    $data=array($fechaSabado,$fechaDomingo);                       
                break;

                case 7:                                    
                    $data=array($fechaDomingo);                       
                break;                             
            }
                                                                                                                                                                                                                                                 
            return $data;
    }

    public function consultarTurno(){ 
        if($this->input->is_ajax_request()){            
            
            $usuario_id=$this->input->post("usuario_id");
            $fechaInicio=$this->input->post("fecha_inicio");
            $fechaFin=$this->input->post("fecha_fin");
            $dia_id=$this->input->post("dia_id");

            $turno=$this->Mdl_nominas->getTurno($usuario_id,$dia_id,$fechaInicio,$fechaFin);
            if($turno){
                $turno=$turno->Turno;
                echo $this->Mdl_nominas->getTurnosID($turno);                            
            }else{
                $turno=0;
                echo $this->Mdl_nominas->getTurnosID($turno);                  
            }

            

        }   
        else{
                redirect(base_url()."Inicio");
          }                  
    }

    public function consultarDetallesRol(){
        if($this->input->is_ajax_request()){            
            $usuario_id=$this->input->post("usuario_id");            
            //$fechaInicio=$this->input->post("fecha_inicio");            
            //$fechaFin=$this->input->post("fecha_fin");                    
            $fechaInicio=date("Y-m-d");            
            $fechaFin=date("Y-m-d");                  
            echo json_encode($this->Mdl_nominas->getRolSemanalUsuario($usuario_id,$fechaInicio,$fechaFin));            
        }   
        else
        {
            redirect(base_url()."Inicio");
        }   
    }

    public function consultarDetallesRolAdmin(){
        if($this->input->is_ajax_request()){            
            $usuario_id=$this->input->post("usuario_id");            
            $fechaInicio=$this->input->post("fechaInicio");            
            //$fechaFin=$this->input->post("fechaFin");                                
            echo json_encode($this->Mdl_nominas->getRolSemanalUsuario($usuario_id,$fechaInicio,$fechaInicio));            
        }   
        else
        {
            redirect(base_url()."Inicio");
        }   
    }

    public function consultarDetallesRol2(){
        if($this->input->is_ajax_request()){            
            $usuario_id=$this->input->post("usuario_id");  
            $fecha_id=$this->input->post("fecha_id");  
            $fechaInicio="";
            $fechaFin="";
            
            switch ($fecha_id) {
                case 0:                    
                    $fechaInicio=date( 'Y-m-d', strtotime( 'monday this week' ));         
                    $fechaFin=date('Y-m-d', strtotime( 'monday this week' )); 
                break;

                case 1:        
                    $fechaInicio=date( 'Y-m-d', strtotime( 'tuesday this week' ));           
                    $fechaFin=date('Y-m-d', strtotime( 'tuesday this week' ));
                break;

                case 2:                    
                    $fechaInicio=date('Y-m-d', strtotime('wednesday this week' ));       
                    $fechaFin=date('Y-m-d', strtotime('wednesday this week' ));
                break;

                case 3:                    
                    $fechaInicio=date('Y-m-d', strtotime('thursday this week' ));            
                    $fechaFin=date('Y-m-d', strtotime('thursday this week' )); 
                break;

                case 4:                    
                    $fechaInicio=date('Y-m-d', strtotime('friday this week' ));             
                    $fechaFin=date('Y-m-d', strtotime('friday this week' ));  
                break;

                case 5:                    
                    $fechaInicio=date( 'Y-m-d', strtotime('saturday this week' ));         
                    $fechaFin=date( 'Y-m-d', strtotime('saturday this week' ));     
                break;

                case 6:        
                    $fechaInicio=date( 'Y-m-d', strtotime('sunday this week' ));         
                    $fechaFin=date( 'Y-m-d', strtotime('sunday this week' ));              
                break;                                
            }                                            
            echo json_encode($this->Mdl_nominas->getRolSemanalUsuario($usuario_id,$fechaInicio,$fechaFin));            
        }   
        else
        {
            redirect(base_url()."Inicio");
        }   
    }

    public function asignarSueldo(){ 
        if($this->input->is_ajax_request()){            
            
            $usuario_id=$this->input->post("id_editarN");            
            $fechaInicio=$this->input->post("fecha_inicio_editarN");
            $fechaFin=$this->input->post("fecha_fin_editarN");
            $cantidad=$this->input->post("cantidad_N");
            $data=array("Sueldo"=>$cantidad);
            
            if($this->Mdl_nominas->asignarSueldoColaborador($usuario_id,$fechaInicio,$fechaFin,$data)){
                echo 1;
            }else{
                echo 0;
            }                   
        }   
        else{
                redirect(base_url()."Inicio");
          }                  
    }

    public function liberarNominaManagerC(){
        if($this->input->is_ajax_request()){       
            $fechaInicio=$this->input->post("fechaInicio");
            $fechaFin=$this->input->post("fechaFin");
            $plaza_id=$this->session->userdata("plaza");
            
            $data=array("Estado"=>1);
            $estado=0;
            if($this->Mdl_nominas->disponibilidadNomina($plaza_id,$fechaInicio,$fechaFin)){
                
                //VAMOS A VERIFICAR EL ESTADO DE LA NOMINA
                    $estado=$this->Mdl_nominas->getEstado($plaza_id,$fechaInicio,$fechaFin);
                        if($estado!=null){
                            $estado=$estado->Estado;
                        }else{
                            $estado=0;
                        }
                //VAMOS A VERIFICAR EL ESTADO DE LA NOMINA    
                if($estado==0){
                    if($this->Mdl_nominas->actualizarRolSemanal($plaza_id,$fechaInicio,$fechaFin,$data)){
                        echo 1;
                    }   
                    else{
                        echo 0;
                    }
                }else if($estado==1){
                    echo 3;

                }else if($estado==2) {
                    echo 4;
                }
            }else{
                echo 2;
            }
        }   
        else{
                redirect(base_url()."Inicio");
          } 
    }

    public function liberarNominaManagerAdminC(){
        if($this->input->is_ajax_request()){       

            $fechaInicio=$this->input->post("fechaInicio");
            $fechaFin=$this->input->post("fechaFin");            
            $plaza_id=$this->input->post("plaza_id");

            $data=array("Estado"=>1);
            $estado=0;
            if($this->Mdl_nominas->disponibilidadNomina($plaza_id,$fechaInicio,$fechaFin)){
                
                //VAMOS A VERIFICAR EL ESTADO DE LA NOMINA
                    $estado=$this->Mdl_nominas->getEstado($plaza_id,$fechaInicio,$fechaFin);
                        if($estado!=null){
                            $estado=$estado->Estado;
                        }else{
                            $estado=0;
                        }
                //VAMOS A VERIFICAR EL ESTADO DE LA NOMINA    
                if($estado==0){
                    if($this->Mdl_nominas->actualizarRolSemanal($plaza_id,$fechaInicio,$fechaFin,$data)){
                        echo 1;
                    }   
                    else{
                        echo 0;
                    }
                }else if($estado==1){
                    echo 3;

                }else if($estado==2) {
                    echo 4;
                }
            }else{
                echo 2;
            }           
        }   
        else{
                redirect(base_url()."Inicio");
          } 
    }

    public function habilitarNominaManagerAdminC(){
        if($this->input->is_ajax_request()){       

            $fechaInicio=$this->input->post("fechaInicio");
            $fechaFin=$this->input->post("fechaFin");
            $plaza_id=$this->input->post("plaza_id");

            $data=array("Estado"=>0);

            if($this->Mdl_nominas->disponibilidadNomina($plaza_id,$fechaInicio,$fechaFin)){
                
                //VAMOS A VERIFICAR EL ESTADO DE LA NOMINA
                    $estado=$this->Mdl_nominas->getEstado($plaza_id,$fechaInicio,$fechaFin);
                    if($estado!=null){
                        $estado=$estado->Estado;
                    }else{
                        $estado=0;
                    }
                //VAMOS A VERIFICAR EL ESTADO DE LA NOMINA     

                if($estado==0){
                    echo 3;
                }else if($estado==1){
                    if($this->Mdl_nominas->actualizarRolSemanal($plaza_id,$fechaInicio,$fechaFin,$data)){
                        echo 1;
                    }   
                    else{
                        echo 0;
                    }    
                }
                else if($estado==2){
                    echo 4;
                }     

            }else{
                echo 2;
            }
        }   
        else{
                redirect(base_url()."Inicio");
          } 
    }

    public function liberarNominaAdmin(){
        if($this->input->is_ajax_request()){ 

            $fechaInicio=$this->input->post("fechaInicio");
            $fechaFin=$this->input->post("fechaFin");
            $plaza_id=$this->input->post("plaza_id");            
            
            $data=array("Estado"=>2);
            if($plaza_id!=0){
                if($this->Mdl_nominas->disponibilidadNomina($plaza_id,$fechaInicio,$fechaFin)){
                    if($this->Mdl_nominas->disponibilidadNominaManager($plaza_id,$fechaInicio,$fechaFin)){
                        if($this->Mdl_nominas->actualizarRolSemanal($plaza_id,$fechaInicio,$fechaFin,$data)){
                            echo 1;
                        }   
                        else{
                            echo 0;
                        }
                    }
                    else{
                        echo 4;
                    }            
                }else{
                    echo 3;
                }                
            }else{
                echo 2;
            }            
        }   
        else{
                redirect(base_url()."Inicio");
          } 
    }

    public function fechaLunes($fecha){
        $date=$fecha;
        $Y_date = explode("-",$date);
        $year = $Y_date[0];           
        $weeknumber=date("W", strtotime($date));
        $gendate = new DateTime();
        $gendate->setISODate(2026,$weeknumber,1); //year , week num , day
        return $gendate->format('Y-m-d');                
    }

    public function fechaDomingo($fecha){
        $date=$fecha;
        $Y_date = explode("-",$date);
        $year = $Y_date[0];           
        $weeknumber=date("W", strtotime($date));
        $gendate = new DateTime();
        $gendate->setISODate(2026,$weeknumber,7); //year , week num , day
        return $gendate->format('Y-m-d');                
    }
    
    public function getSemana($fecha){
        $date=$fecha;
        $Y_date = explode("-",$date);
        $year = $Y_date[0];           
        $weeknumber=date("W", strtotime($date));
        return $weeknumber;
    }

    function getWeekDates($year,$week,$day,$start=true){
        $from = date("d", strtotime("{$year}-W{$week}-{$day}")); //Returns the date of monday in week
        $to = date("d", strtotime("{$year}-W{$week}-{$day}"));   //Returns the date of sunday in week
    
        if($start) {
            return $from;
        } else {
            return $to;
        }
        //return "Week {$week} in {$year} is from {$from} to {$to}.";
    }

    public function otorgarPermiso(){
        if($this->input->is_ajax_request()){ 
            $plaza_id=$this->input->post("plaza_id");  
            $fechaInicio=$this->input->post("fechaInicio");  
            $fechaFin=$this->input->post("fechaFin");
            $data=array("Estado"=>0);
            if($this->Mdl_nominas->actualizarRolSemanal($plaza_id,$fechaInicio,$fechaFin,$data)){
                echo 1;
            }   
            else{
                echo 0;
            }
        }   
        else{
                redirect(base_url()."Inicio");
          }
    }
    
    public function actualizarUsuario(){
        if($this->input->is_ajax_request()){ 

            $usuario_id=$this->input->post("usuario_id_editar");  
            $nombre=$this->input->post("nombre_editar");
            $apellidoPaterno=$this->input->post("apellido_paterno_editar");            
            $apellidoMaterno=$this->input->post("apellido_materno_editar");      
            $nombre_rol=$this->input->post("nombre_rol_editar");   
                 
            $fechaNacimiento=$this->input->post("fecha_nacimiento_editar");            
            $telefono=$this->input->post("telefono_editar");            
            $curp=$this->input->post("CURP_editar");                                    
            $fechaIngreso=$this->input->post("fecha_ingreso_editar");                        
            $rol_id=$this->input->post("rol_id_editar");
            $sueldo=$this->input->post("sueldo_editar");                 

                    
        $fechaIngresoActual=$this->Mdl_nominas->consultarFechaIngreso($usuario_id);             
        if($fechaIngresoActual!=null){$fechaIngresoActual=$fechaIngresoActual->fechaIngreso;}        
            if($fechaIngresoActual!=$fechaIngreso){                
                
                $concepto_id=8;
                $data=array("Fecha"=>$fechaIngreso);
                
                //public function actualizarEvento($concepto_id,$usuario_id,$data){  
                if($this->Mdl_nominas->actualizarEvento($concepto_id,$usuario_id,$data)){
                        $data=array(
                            "Nombre"=>$nombre,
                            "Apellido_Paterno"=>$apellidoPaterno,
                            "Apellido_Materno"=>$apellidoMaterno,
                            "Nombre_Rol"=>$nombre_rol,  
                            "rol_nomina_id"=>$rol_id,                        
                            "Fecha_Nacimiento"=>$fechaNacimiento,                    
                            "Fecha_Ingreso"=>$fechaIngreso,
                            "Telefono"=>$telefono,
                            "CURP"=>$curp,                        
                            "Sueldo"=>$sueldo
                        );
        
                        if($this->Mdl_nominas->actualizarUsuarioN($usuario_id,$data)){
                            echo 1;
                        }   
                        else{
                            echo 0;
                        }
                }else{
                    echo 0;
                }                
            }
            else
            {
                $data=array(
                    "Nombre"=>$nombre,
                    "Apellido_Paterno"=>$apellidoPaterno,
                    "Apellido_Materno"=>$apellidoMaterno,
                    "Nombre_Rol"=>$nombre_rol,  
                    "rol_nomina_id"=>$rol_id,                        
                    "Fecha_Nacimiento"=>$fechaNacimiento,                    
                    "Fecha_Ingreso"=>$fechaIngreso,
                    "Telefono"=>$telefono,
                    "CURP"=>$curp,                        
                    "Sueldo"=>$sueldo
                );

                if($this->Mdl_nominas->actualizarUsuarioN($usuario_id,$data)){
                    echo 1;
                }   
                else{
                    echo 0;
                }
            }
        }   
        else{
                redirect(base_url()."Inicio");
          }
    }

    public function actualizarUsuarioManager(){
        if($this->input->is_ajax_request()){ 

            $usuario_id=$this->input->post("usuario_id_editar");
            $nombre=$this->input->post("nombre_editar");                                    
            $apellidoPaterno=$this->input->post("apellido_paterno_editar");            
            $apellidoMaterno=$this->input->post("apellido_materno_editar");
            $nombre_rol=$this->input->post("nombre_rol_editar");                        
            $fechaNacimiento=$this->input->post("fecha_nacimiento_editar");
            $telefono=$this->input->post("telefono_editar");            
            $curp=$this->input->post("CURP_editar");
                                                

                $data=array(
                    "Nombre"=>$nombre,
                    "Apellido_Paterno"=>$apellidoPaterno,
                    "Apellido_Materno"=>$apellidoMaterno, 
                    "Nombre_Rol"=>$nombre_rol,         
                    "Fecha_Nacimiento"=>$fechaNacimiento,                                 
                    "Telefono"=>$telefono,
                    "CURP"=>$curp                                           
                );

                if($this->Mdl_nominas->actualizarUsuarioN($usuario_id,$data)){
                    echo 1;
                }   
                else{
                    echo 0;
                }          
        }   
        else{
                redirect(base_url()."Inicio");
          }
    }

    public function eliminarUsuario(){
        if($this->input->is_ajax_request()){                         
            $usuario_id=$this->input->post("usuario_id");      
            
            $lunes=$this->fechaLunes(date("Y-m-d"));
            $domingo=$this->fechaDomingo(date("Y-m-d"));

            $disponibilidad=$this->Mdl_nominas->checarDisponibilidadRol($lunes,$domingo,$usuario_id);
            if($disponibilidad==null){
                $data=array("Activo"=>0);
                if($this->Mdl_nominas->eliminarUsuarioN($usuario_id,$data)){
                    //UNA VEZ QUE SE ELIMINO EL REGISTRO GUARDAMOS EN EL HISTORIAL SU BAJA Y LE ASIGNAMOS LA FECHA DE EGRESO
                    $data=array(
                        "usuario_nomina_id"=>$usuario_id,
                        "concepto_id"=>9,                        
                        "Fecha"=>date("Y-m-d"),                        
                        "Importe"=>0,                        
                        "Nota"=>"EGRESO DE LA EMPRESA. Fecha: ".date("Y-m-d")                            
                    );
                        if($this->Mdl_nominas->saveExtra($data)){
                            echo 1;
                        }else{
                            echo 0;
                        }   
                }   
                else{
                    echo 0;
                }
            }else{
                echo 2;
            }
        }   
        else{
                redirect(base_url()."Inicio");
          } 
    }

    public function consultarUsuarios(){
        if($this->input->is_ajax_request()){            
            $plaza_id=$this->input->post('plaza_id');            
            echo $this->Mdl_nominas->getUsuariosPlaza($plaza_id);            
        }   
        else{
                redirect(base_url()."Inicio");
          }
    }

    public function consultarHistorialUsuarios(){
       if($this->input->is_ajax_request())
        {            
            $usuario_id=$this->input->post('usuario_id');  
            $fechaInicio=$this->input->post('fechaInicio');  
            $fechaFin=$this->input->post('fechaFin'); 
            echo json_encode($this->Mdl_nominas->gethistorialUsuarioAPI($fechaInicio,$fechaFin,$usuario_id));
        }   
        else
        {
            redirect(base_url()."Inicio");
        }                                                           
    }

    public function consultarHistorialUsuariosAdministrador(){
        if($this->input->is_ajax_request())
         {            
             $usuario_id=$this->input->post('usuario_id');               
             echo json_encode($this->Mdl_nominas->gethistorialUsuarioAPIManager($usuario_id));
         }   
         else
         {
             redirect(base_url()."Inicio");
         }                                                          
     }

    
     public function eliminarEventoUsuario(){
        if($this->input->is_ajax_request()){            
            $id=$this->input->post('id');  
            if($this->Mdl_nominas->eliminarEventoUsuarioN($id)){
                echo 1;
            }   
            else{
                echo 0;
            }              
        }   
         else
         {
             redirect(base_url()."Inicio");
         }
    }
    
    public function diaFeriadoC(){
        if($this->input->is_ajax_request())
        {
            $usuario_id=$this->input->post('usuario_id_DF'); 
            $concepto_id=$this->input->post('concepto_id_DF'); 
            $importe=$this->input->post('importe_DF'); 
            $nota=$this->input->post('nota_nota_DF');
            $dia_id=$this->input->post('dia_editar_DF');      

            $lunes=date( 'Y-m-d', strtotime( 'monday this week' ));
            $domingo=date( 'Y-m-d', strtotime('sunday this week' ));

            //AREA PARA CHECAR EL DIA SELECCIONADO Y ASIGNAR LA FECHA CORRECTA DE ESE DIA
              switch ($dia_id) {
                case 1:
                        $fecha=date( 'Y-m-d', strtotime( 'monday this week' ));                      
                    break;
                    case 2:
                        $fecha=date( 'Y-m-d', strtotime( 'tuesday this week' ));                                                                              
                    break;

                    case 3:
                        $fecha=date( 'Y-m-d', strtotime('wednesday this week' )); 
                    break;

                    case 4:
                        $fecha=date( 'Y-m-d', strtotime('thursday this week' ));                                                         
                    break;

                    case 5: 
                        $fecha=date( 'Y-m-d', strtotime('friday this week' ));                                                                                                                                 
                    break;

                    case 6:                         
                        $fecha=date( 'Y-m-d', strtotime('saturday this week' ));                                                                                                                                 
                    break;

                    case 7:  
                        $fecha=date( 'Y-m-d', strtotime('sunday this week' ));                                                                                                                                                                                     
                    break;               
                }
            //AREA PARA CHECAR EL DIA SELECCIONADO Y ASIGNAR LA FECHA CORRECTA DE ESE DIA
        
        $disponibilidadExtra=$this->Mdl_nominas->consultarDisponibilidadExtrasNomina($lunes,$domingo,$usuario_id);            
        if($disponibilidadExtra!=null){                
            
            $extraAcumulado=$this->Mdl_nominas->consultarAcumuladoExtra($lunes,$domingo,$usuario_id);            
            if($extraAcumulado!=null){
                $extraAcumulado=$extraAcumulado->Extra;
            }else{
                $extraAcumulado=0;
            }

            $extraAcumulado+=$importe;

            $dataU=array(
                "Extra"=>$extraAcumulado
            );

            $dataI=array(
                "usuario_nomina_id"=>$usuario_id,
                "concepto_id"=>$concepto_id,
                "Fecha"=>$fecha,
                "Importe"=>$importe,
                "Nota"=>$nota
            );

            if($this->Mdl_nominas->agregarNotaUsuarioM($usuario_id,$lunes,$domingo,$dataU,$dataI)){
                echo 1;
            }else{
                echo 0;
            }
        }
        else{
            echo 2;
        }           
        }   
        else
        {
            redirect(base_url()."Inicio");
        }            
    }

    public function eliminarEventoC(){
        if($this->input->is_ajax_request())
        {
            $fecha=$this->input->post('fecha'); 
            $usuario_id=$this->input->post('usuario_id'); 
            $code=$this->input->post('code'); 

            $importe=$this->Mdl_nominas->getImporteExtras($code);

            if($importe!=null){
                $importe=$importe->Importe;
            }else{
                $importe=0;
            }

            $lunes=date( 'Y-m-d', strtotime( 'monday this week' ));
            $domingo=date( 'Y-m-d', strtotime('sunday this week' ));
            
            $extraAcumulado=$this->Mdl_nominas->consultarAcumuladoExtra($lunes,$domingo,$usuario_id);                    
            if($extraAcumulado!=null){
                $extraAcumulado=$extraAcumulado->Extra;
            }else{
                $extraAcumulado=0;
            }
            $nuevoExtra=$extraAcumulado-$importe;            

            $dataU=array(
                "Extra"=>$nuevoExtra
            );

            if($this->Mdl_nominas->eliminarNotaUsuarioM($usuario_id,$lunes,$domingo,$dataU,$code)){
                echo 1;
            }else{
                echo 0;
            }                  
        }   
        else
        {
            redirect(base_url()."Inicio");
        }            

    }

    public function consultarSueldoID(){
        if($this->input->is_ajax_request())
        {
            $usuario_id=$this->input->post('usuario_id');             
            $sueldo=$this->Mdl_nominas->consultarSueldoUsuario($usuario_id);            
            if($sueldo!=null){
                $sueldo=$sueldo->Sueldo;
            }else{
                $sueldo=0;
            }

            echo $sueldo;
        
        }   
        else
        {
            redirect(base_url()."Inicio");
        }  
    }

}

    







