<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
            <!-- Content Header (Page header) -->
            <section class="content-header">
                <h1>
                Tabla de Nominas <?php echo $this->session->userdata("plaza_nombre")?>                    
                    <?php 
                        $lunes = date( 'Y-m-d', strtotime( 'monday this week' ));
                        $domingo = date( 'Y-m-d', strtotime( 'sunday this week' ));                      
                    ?>
                <small>Listado del <?php echo $lunes?> al <?php echo $domingo?> </small>
                </h1>
                <h4>Estado: En captura</h4>
                <a href="<?php echo base_url(); ?>Nominas" class="small-box-footer">Volver al Panel de Inicio <i class="fa fa-arrow-circle-right"></i></a>
            </section>
            <!-- Main content -->
            <section class="content">
            <!-- Default box -->
                <div class="box box-solid">
                    <div class="box-body">
                        <div class="row">
                            <div class="col-md-12">  
                            <form action="<?php echo current_url();?>" method="POST" class="form-horizontal">
                                <div class="form-group">

                                    <label for="" class="col-md-1 control-label">Desde:</label>
                                        <div class="col-md-2">
                                            <input type="date" class="form-control" name="fechaInicioD" id="fechaInicioD" value="<?php echo !empty($fechaInicio) ? $fechaInicio:'';?>">
                                        </div>

                                    <label for="" class="col-md-1 control-label">Hasta:</label>
                                        <div class="col-md-2">
                                            <input type="date" class="form-control" name="fechaFinD" id="fechaFinD" value="<?php  echo !empty($fechaFin) ? $fechaFin:'';?>">
                                        </div>

                                    <label for="plaza_id" class="col-md-1 control-label">Plaza:</label>                                        
                                        <div class="col-md-2">
                                            <select name="plaza_id" id="plaza_id" class="form-control">                                                  
                                            <?php foreach ($plazas as $plaza):?>  
                                            <?php if($plaza->id==1):?>
                                            <option value="" selected>
                                                Todas las Plazas
                                            </option>
                                            <?php endif;?>
                                            <?php if($plaza->id==$id):?>
                                            <option value="<?php echo $plaza->id?>" selected>
                                            <?php echo $plaza->Nombre;?>
                                            </option>
                                            <?php else:?>
                                            <option value="<?php echo $plaza->id?>">
                                            <?php echo $plaza->Nombre;?>
                                            </option>
                                            <?php endif;?>
                                            <?php endforeach;?>?>
                                            </select>                                                                                
                                        </div>

                                        <label for="lugar_id_busqueda" class="col-md-1 control-label">Lugar:</label>                                        
                                        <div class="col-md-2">
                                            <select name="lugar_id_busqueda" id="lugar_id_busqueda" class="form-control">                                                  
                                            <?php foreach ($lugares as $lugar):?>  
                                            <?php if($lugar->id==1):?>
                                            <option value="" selected>
                                                Seleccionar Lugar
                                            </option>
                                            <?php endif;?>
                                            <?php if($lugar->id==$id_lugar):?>
                                            <option value="<?php echo $lugar->id?>" selected>
                                            <?php echo $lugar->Nombre;?>
                                            </option>
                                            <?php else:?>
                                            <option value="<?php echo $lugar->id?>">
                                            <?php echo $lugar->Nombre;?>
                                            </option>
                                            <?php endif;?>
                                            <?php endforeach;?>?>
                                            </select>                                                                                
                                        </div>
                                                                                                     
                                </div>                
                                <div class="form-group">

                                <label for="turno_id_busqueda" class="col-md-1 control-label">Turno:</label>                                        
                                    <div class="col-md-2">
                                        <select name="turno_id_busqueda" id="turno_id_busqueda" class="form-control">                                                  
                                            <?php foreach ($turnos as $turno):?>  
                                            <?php if($turno->id==1):?>
                                            <option value="" selected>
                                                Seleccionar Turno
                                            </option>
                                            <?php endif;?>
                                            <?php if($turno->id==$id_turno):?>
                                            <option value="<?php echo $turno->id?>" selected>
                                            <?php echo $turno->Nombre;?>
                                            </option>
                                            <?php else:?>
                                            <option value="<?php echo $turno->id?>">
                                            <?php echo $turno->Nombre;?>
                                            </option>
                                            <?php endif;?>
                                            <?php endforeach;?>?>
                                        </select>                                                                                
                                    </div>
                                
                                <label for="" class="col-md-1 control-label">Options:</label>
                                    <div class="col-md-4">                                        
                                        <input type="submit" name="buscar" value="Buscar" class="btn btn-primary">                                                                                                                                                                                                                                                                                          
                                        <input type="submit" name="buscarRango" value="Buscar Rango" class="btn btn-danger">                                                                                                                                                                                                                                                                                          
                                    </div>                                       

                                </div>
                            </form>                        
                            </div>
                            </div>
                            <div class="row">
                            <div class="col-md-12">                                                                                          
                                <div class="form-group">

                                    <?php switch($bandera): 
                                        case 0: ?>
                                        <!--TODAS LAS PLAZAS TODOS LOS LUGARES DE LA CAFETERIA-->
                                       
                                            <table class="display responsive table table-bordered  btn-hover nowrap" style="width:100%" id="tbl_usuarios_5">
                                                <!--VARIABLES INICIO DEL PROYECTO-->
                                                    <?php                                                     
                                                    $acLunesBarraMCasitas="";  
                                                    $acLunesBarraVCasitas="";  
                                                    $acMartesBarraMCasitas=""; 
                                                    $acMartesBarraVCasitas=""; 
                                                    $acMiercolesBarraMCasitas="";
                                                    $acMiercolesBarraVCasitas="";        
                                                    $acJuevesBarraMCasitas="";   
                                                    $acJuevesBarraVCasitas="";        
                                                    $acViernesBarraMCasitas="";
                                                    $acViernesBarraVCasitas="";        
                                                    $acSabadoBarraMCasitas=""; 
                                                    $acSabadoBarraVCasitas=""; 
                                                    $acDomingoBarraMCasitas="";                                     
                                                    $acDomingoBarraVCasitas=""; 
                                                    
                                                    $acLunesMesasMCasitas="";
                                                    $acLunesMesasVCasitas="";
                                                    $acMartesMesasMCasitas="";
                                                    $acMartesMesasVCasitas="";
                                                    $acMiercolesMesasMCasitas="";
                                                    $acMiercolesMesasVCasitas="";
                                                    $acJuevesMesasMCasitas="";
                                                    $acJuevesMesasVCasitas="";
                                                    $acViernesMesasMCasitas="";
                                                    $acViernesMesasVCasitas="";
                                                    $acSabadoMesasMCasitas="";
                                                    $acSabadoMesasVCasitas="";
                                                    $acDomingoMesasMCasitas="";
                                                    $acDomingoMesasVCasitas="";
                                                    
                                                                    
                                                    $acLunesCocinaMCasitas="";
                                                    $acLunesCocinaVCasitas="";
                                                    $acMartesCocinaMCasitas="";
                                                    $acMartesCocinaVCasitas="";
                                                    $acMiercolesCocinaMCasitas="";
                                                    $acMiercolesCocinaVCasitas="";
                                                    $acJuevesCocinaMCasitas="";
                                                    $acJuevesCocinaVCasitas="";
                                                    $acViernesCocinaMCasitas="";
                                                    $acViernesCocinaVCasitas="";
                                                    $acSabadoCocinaMCasitas="";
                                                    $acSabadoCocinaVCasitas="";
                                                    $acDomingoCocinaMCasitas="";
                                                    $acDomingoCocinaVCasitas="";
                                                                                              
                                                    $acLunesApoyoMCasitas="";
                                                    $acLunesApoyoVCasitas="";
                                                    $acMartesApoyoMCasitas="";
                                                    $acMartesApoyoVCasitas="";
                                                    $acMiercolesApoyoMCasitas="";
                                                    $acMiercolesApoyoVCasitas="";
                                                    $acJuevesApoyoMCasitas="";
                                                    $acJuevesApoyoVCasitas="";
                                                    $acViernesApoyoMCasitas="";
                                                    $acViernesApoyoVCasitas="";
                                                    $acSabadoApoyoMCasitas="";
                                                    $acSabadoApoyoVCasitas="";
                                                    $acDomingoApoyoMCasitas="";
                                                    $acDomingoApoyoVCasitas="";
                                                    ?>
                                            
                                                <thead>
                                                    <tr>       

                                                        <?php 
                                                            $lunes = date( 'd', strtotime( 'monday this week' ));
                                                            $martes = date( 'd', strtotime( 'tuesday this week' ));
                                                            $miercoles = date( 'd', strtotime( 'wednesday this week' ));
                                                            $jueves = date( 'd', strtotime( 'thursday this week' ));
                                                            $viernes = date( 'd', strtotime( 'friday this week' ));
                                                            $sabado = date( 'd', strtotime( 'saturday this week' ));
                                                            $domingo = date( 'd', strtotime( 'sunday this week' ));                                
                                                        ?> 
                                                               
                                                        <th>PLAZA</th>      
                                                        <th>TURNO</th>                                                     
                                                        <th colspan="2">Lun <?php echo $lunesE;?></th>  
                                                        <th colspan="2">Mar <?php echo $martesE;?></th>                     
                                                        <th colspan="2">Mier <?php echo $miercolesE;?></th>                                    
                                                        <th colspan="2">Jue <?php echo $juevesE;?></th>                                                                        
                                                        <th colspan="2">Vie <?php echo $viernesE;?></th>
                                                        <th colspan="2">Sab <?php echo $sabadoE;?></th>
                                                        <th colspan="2">Dom <?php echo $domingoE;?></th>   
                                                        <th>Totales</th>                                                                                                  
                                                    </tr>
                                                </thead>
                                                <tbody>     

                                                    <!--FOR(S) CASITAS-->                                                    
                                                        <?php if(!empty($matutinosBarraCasitas)):?>                           
                                                                <?php foreach ($matutinosBarraCasitas as $matutinoBarraCasitas):?>
                                                                    <?php switch ($matutinoBarraCasitas->dia_id) {
                                                                        case 1;
                                                                            $acLunesBarraMCasitas=$acLunesBarraMCasitas."".$matutinoBarraCasitas->Nombre_Rol."<br>";                                        
                                                                        break;

                                                                        case 2;                                 
                                                                            $acMartesBarraMCasitas=$acMartesBarraMCasitas."".$matutinoBarraCasitas->Nombre_Rol."<br>";       
                                                                        break;

                                                                        case 3;                                        
                                                                            $acMiercolesBarraMCasitas=$acMiercolesBarraMCasitas."".$matutinoBarraCasitas->Nombre_Rol."<br>";
                                                                        break;

                                                                        case 4;                                        
                                                                            $acJuevesBarraMCasitas=$acJuevesBarraMCasitas."".$matutinoBarraCasitas->Nombre_Rol."<br>";
                                                                        break;

                                                                        case 5;                                        
                                                                            $acViernesBarraMCasitas=$acViernesBarraMCasitas."".$matutinoBarraCasitas->Nombre_Rol."<br>";
                                                                        break;

                                                                        case 6;   
                                                                            $acSabadoBarraMCasitas=$acSabadoBarraMCasitas."".$matutinoBarraCasitas->Nombre_Rol."<br>";                                     
                                                                        break;

                                                                        case 7;                                        
                                                                            $acDomingoBarraMCasitas=$acDomingoBarraMCasitas."".$matutinoBarraCasitas->Nombre_Rol."<br>";
                                                                        break;                                                                        
                                                                    }?>                              
                                                                <?php endforeach;?>                                                              
                                                        <?php endif;?>  

                                                        <?php if(!empty($vespertinosBarraCasitas)):?>                           
                                                            <?php foreach ($vespertinosBarraCasitas as $vespertinoBarraCasitas):?>
                                                                <?php switch ($vespertinoBarraCasitas->dia_id) {
                                                                    case 1;
                                                                        $acLunesBarraVCasitas=$acLunesBarraVCasitas."".$vespertinoBarraCasitas->Nombre_Rol."<br>";                                        
                                                                    break;

                                                                    case 2;                                 
                                                                        $acMartesBarraVCasitas=$acMartesBarraVCasitas."".$vespertinoBarraCasitas->Nombre_Rol."<br>";       
                                                                    break;

                                                                    case 3;                                        
                                                                        $acMiercolesBarraVCasitas=$acMiercolesBarraVCasitas."".$vespertinoBarraCasitas->Nombre_Rol."<br>";
                                                                    break;

                                                                    case 4;                                        
                                                                        $acJuevesBarraVCasitas=$acJuevesBarraVCasitas."".$vespertinoBarraCasitas->Nombre_Rol."<br>";
                                                                    break;

                                                                    case 5;                                        
                                                                        $acViernesBarraVCasitas=$acViernesBarraVCasitas."".$vespertinoBarraCasitas->Nombre_Rol."<br>";
                                                                    break;

                                                                    case 6;   
                                                                        $acSabadoBarraVCasitas=$acSabadoBarraVCasitas."".$vespertinoBarraCasitas->Nombre_Rol."<br>";                                     
                                                                    break;

                                                                    case 7;                                        
                                                                        $acDomingoBarraVCasitas=$acDomingoBarraVCasitas."".$vespertinoBarraCasitas->Nombre_Rol."<br>";
                                                                    break;                                                                        
                                                                }?>                              
                                                            <?php endforeach;?>                                                              
                                                        <?php endif;?>  
                                                
                                                        <?php if(!empty($matutinosMesasCasitas)):?>                           
                                                            <?php foreach ($matutinosMesasCasitas as $matutinoMesasCasitas):?>
                                                                <?php switch ($matutinoMesasCasitas->dia_id) {
                                                                    case 1;
                                                                        $acLunesMesasMCasitas=$acLunesMesasMCasitas."".$matutinoMesasCasitas->Nombre_Rol."<br>";                                        
                                                                    break;

                                                                    case 2;                                 
                                                                        $acMartesMesasMCasitas=$acMartesMesasMCasitas."".$matutinoMesasCasitas->Nombre_Rol."<br>";       
                                                                    break;

                                                                    case 3;                                        
                                                                        $acMiercolesMesasMCasitas=$acMiercolesMesasMCasitas."".$matutinoMesasCasitas->Nombre_Rol."<br>";
                                                                    break;

                                                                    case 4;                                        
                                                                        $acJuevesMesasMCasitas=$acJuevesMesasMCasitas."".$matutinoMesasCasitas->Nombre_Rol."<br>";
                                                                    break;

                                                                    case 5;                                        
                                                                        $acViernesMesasMCasitas=$acViernesMesasMCasitas."".$matutinoMesasCasitas->Nombre_Rol."<br>";
                                                                    break;

                                                                    case 6;   
                                                                        $acSabadoMesasMCasitas=$acSabadoMesasMCasitas."".$matutinoMesasCasitas->Nombre_Rol."<br>";                                     
                                                                    break;

                                                                    case 7;                                        
                                                                        $acDomingoMesasMCasitas=$acDomingoMesasMCasitas."".$matutinoMesasCasitas->Nombre_Rol."<br>";
                                                                    break;                                                                        
                                                                }?>                              
                                                            <?php endforeach;?>                                                              
                                                        <?php endif;?>  

                                                        <?php if(!empty($vespertinosMesasCasitas)):?>                           
                                                            <?php foreach ($vespertinosMesasCasitas as $vespertinoMesasCasitas):?>
                                                                <?php switch ($vespertinoMesasCasitas->dia_id) {
                                                                    case 1;
                                                                        $acLunesMesasVCasitas=$acLunesMesasVCasitas."".$vespertinoMesasCasitas->Nombre_Rol."<br>";                                        
                                                                    break;

                                                                    case 2;                                 
                                                                        $acMartesMesasVCasitas=$acMartesMesasVCasitas."".$vespertinoMesasCasitas->Nombre_Rol."<br>";       
                                                                    break;

                                                                    case 3;                                        
                                                                        $acMiercolesMesasVCasitas=$acMiercolesMesasVCasitas."".$vespertinoMesasCasitas->Nombre_Rol."<br>";
                                                                    break;

                                                                    case 4;                                        
                                                                        $acJuevesMesasVCasitas=$acJuevesMesasVCasitas."".$vespertinoMesasCasitas->Nombre_Rol."<br>";
                                                                    break;

                                                                    case 5;                                        
                                                                        $acViernesMesasVCasitas=$acViernesMesasVCasitas."".$vespertinoMesasCasitas->Nombre_Rol."<br>";
                                                                    break;

                                                                    case 6;   
                                                                        $acSabadoMesasVCasitas=$acSabadoMesasVCasitas."".$vespertinoMesasCasitas->Nombre_Rol."<br>";                                     
                                                                    break;

                                                                    case 7;                                        
                                                                        $acDomingoMesasVCasitas=$acDomingoMesasVCasitas."".$vespertinoMesasCasitas->Nombre_Rol."<br>";
                                                                    break;                                                                        
                                                                }?>                              
                                                            <?php endforeach;?>                                                              
                                                        <?php endif;?>  

                                                        <?php if(!empty($matutinosCocinaCasitas)):?>                           
                                                            <?php foreach ($matutinosCocinaCasitas as $matutinoCocinaCasitas):?>
                                                                <?php switch ($matutinoCocinaCasitas->dia_id) {
                                                                    case 1;
                                                                        $acLunesCocinaMCasitas=$acLunesCocinaMCasitas."".$matutinoCocinaCasitas->Nombre_Rol."<br>";                                        
                                                                    break;

                                                                    case 2;                                 
                                                                        $acMartesCocinaMCasitas=$acMartesCocinaMCasitas."".$matutinoCocinaCasitas->Nombre_Rol."<br>";       
                                                                    break;

                                                                    case 3;                                        
                                                                        $acMiercolesCocinaMCasitas=$acMiercolesCocinaMCasitas."".$matutinoCocinaCasitas->Nombre_Rol."<br>";
                                                                    break;

                                                                    case 4;                                        
                                                                        $acJuevesCocinaMCasitas=$acJuevesCocinaMCasitas."".$matutinoCocinaCasitas->Nombre_Rol."<br>";
                                                                    break;

                                                                    case 5;                                        
                                                                        $acViernesCocinaMCasitas=$acViernesCocinaMCasitas."".$matutinoCocinaCasitas->Nombre_Rol."<br>";
                                                                    break;

                                                                    case 6;   
                                                                        $acSabadoCocinaMCasitas=$acSabadoCocinaMCasitas."".$matutinoCocinaCasitas->Nombre_Rol."<br>";                                     
                                                                    break;

                                                                    case 7;                                        
                                                                        $acDomingoCocinaMCasitas=$acDomingoCocinaMCasitas."".$matutinoCocinaCasitas->Nombre_Rol."<br>";
                                                                    break;                                                                        
                                                                }?>                              
                                                            <?php endforeach;?>                                                              
                                                        <?php endif;?> 

                                                        <?php if(!empty($vespertinosCocinaCasitas)):?>                           
                                                            <?php foreach ($vespertinosCocinaCasitas as $vespertinoCocinaCasitas):?>
                                                                <?php switch ($vespertinoCocinaCasitas->dia_id) {
                                                                    case 1;
                                                                        $acLunesCocinaVCasitas=$acLunesCocinaVCasitas."".$vespertinoCocinaCasitas->Nombre_Rol."<br>";                                        
                                                                    break;

                                                                    case 2;                                 
                                                                        $acMartesCocinaVCasitas=$acMartesCocinaVCasitas."".$vespertinoCocinaCasitas->Nombre_Rol."<br>";       
                                                                    break;

                                                                    case 3;                                        
                                                                        $acMiercolesCocinaVCasitas=$acMiercolesCocinaVCasitas."".$vespertinoCocinaCasitas->Nombre_Rol."<br>";
                                                                    break;

                                                                    case 4;                                        
                                                                        $acJuevesCocinaVCasitas=$acJuevesCocinaVCasitas."".$vespertinoCocinaCasitas->Nombre_Rol."<br>";
                                                                    break;

                                                                    case 5;                                        
                                                                        $acViernesCocinaVCasitas=$acViernesCocinaVCasitas."".$vespertinoCocinaCasitas->Nombre_Rol."<br>";
                                                                    break;

                                                                    case 6;   
                                                                        $acSabadoCocinaVCasitas=$acSabadoCocinaVCasitas."".$vespertinoCocinaCasitas->Nombre_Rol."<br>";                                     
                                                                    break;

                                                                    case 7;                                        
                                                                        $acDomingoCocinaVCasitas=$acDomingoCocinaVCasitas."".$vespertinoCocinaCasitas->Nombre_Rol."<br>";
                                                                    break;                                                                        
                                                                }?>                              
                                                            <?php endforeach;?>                                                              
                                                        <?php endif;?>  

                                                        <?php if(!empty($matutinosApoyoCasitas)):?>                           
                                                            <?php foreach ($matutinosApoyoCasitas as $matutinoApoyoCasitas):?>
                                                                <?php switch ($matutinoApoyoCasitas->dia_id) {
                                                                    case 1;
                                                                        $acLunesApoyoMCasitas=$acLunesApoyoMCasitas."".$matutinoApoyoCasitas->Nombre_Rol."<br>";                                        
                                                                    break;

                                                                    case 2;                                 
                                                                        $acMartesApoyoMCasitas=$acMartesApoyoMCasitas."".$matutinoApoyoCasitas->Nombre_Rol."<br>";       
                                                                    break;

                                                                    case 3;                                        
                                                                        $acMiercolesApoyoMCasitas=$acMiercolesApoyoMCasitas."".$matutinoApoyoCasitas->Nombre_Rol."<br>";
                                                                    break;

                                                                    case 4;                                        
                                                                        $acJuevesApoyoMCasitas=$acJuevesApoyoMCasitas."".$matutinoApoyoCasitas->Nombre_Rol."<br>";
                                                                    break;

                                                                    case 5;                                        
                                                                        $acViernesApoyoMCasitas=$acViernesApoyoMCasitas."".$matutinoApoyoCasitas->Nombre_Rol."<br>";
                                                                    break;

                                                                    case 6;   
                                                                        $acSabadoApoyoMCasitas=$acSabadoApoyoMCasitas."".$matutinoApoyoCasitas->Nombre_Rol."<br>";                                     
                                                                    break;

                                                                    case 7;                                        
                                                                        $acDomingoApoyoMCasitas=$acDomingoApoyoMCasitas."".$matutinoApoyoCasitas->Nombre_Rol."<br>";
                                                                    break;                                                                        
                                                                }?>                              
                                                            <?php endforeach;?>                                                              
                                                        <?php endif;?> 

                                                        <?php if(!empty($vespertinosApoyoCasitas)):?>                           
                                                            <?php foreach ($vespertinosApoyoCasitas as $vespertinoApoyoCasitas):?>
                                                                <?php switch ($vespertinoApoyoCasitas->dia_id) {
                                                                    case 1;
                                                                        $acLunesApoyoVCasitas=$acLunesApoyoVCasitas."".$vespertinoApoyoCasitas->Nombre_Rol."<br>";                                        
                                                                    break;

                                                                    case 2;                                 
                                                                        $acMartesApoyoVCasitas=$acMartesApoyoVCasitas."".$vespertinoApoyoCasitas->Nombre_Rol."<br>";       
                                                                    break;

                                                                    case 3;                                        
                                                                        $acMiercolesApoyoVCasitas=$acMiercolesApoyoVCasitas."".$vespertinoApoyoCasitas->Nombre_Rol."<br>";
                                                                    break;

                                                                    case 4;                                        
                                                                        $acJuevesApoyoVCasitas=$acJuevesApoyoVCasitas."".$vespertinoApoyoCasitas->Nombre_Rol."<br>";
                                                                    break;

                                                                    case 5;                                        
                                                                        $acViernesApoyoVCasitas=$acViernesApoyoVCasitas."".$vespertinoApoyoCasitas->Nombre_Rol."<br>";
                                                                    break;

                                                                    case 6;   
                                                                        $acSabadoApoyoVCasitas=$acSabadoApoyoVCasitas."".$vespertinoApoyoCasitas->Nombre_Rol."<br>";                                     
                                                                    break;

                                                                    case 7;                                        
                                                                        $acDomingoApoyoVCasitas=$acDomingoApoyoVCasitas."".$vespertinoApoyoCasitas->Nombre_Rol."<br>";
                                                                    break;                                                                        
                                                                }?>                              
                                                            <?php endforeach;?>                                                              
                                                        <?php endif;?>
                                                    <!--FOR(S) CASITAS-->  
                                                    
                                                    <tr>
                                                        <td>CA</td>
                                                        <td>BARRA</td>
                                                        <td rowspan="1"><?=$acLunesBarraMCasitas;?></td>  
                                                        <td rowspan="1"><?=$acLunesBarraVCasitas;?></td>  
                                                        <td rowspan="1"><?=$acMartesBarraMCasitas;?></td> 
                                                        <td rowspan="1"><?=$acMartesBarraVCasitas;?></td> 
                                                        <td rowspan="1"><?=$acMiercolesBarraMCasitas;?></td>
                                                        <td rowspan="1"><?=$acMiercolesBarraVCasitas;?></td>        
                                                        <td rowspan="1"><?=$acJuevesBarraMCasitas;?></td>   
                                                        <td rowspan="1"><?=$acJuevesBarraVCasitas;?></td>        
                                                        <td rowspan="1"><?=$acViernesBarraMCasitas;?></td>
                                                        <td rowspan="1"><?=$acViernesBarraVCasitas;?></td>        
                                                        <td rowspan="1"><?=$acSabadoBarraMCasitas;?></td> 
                                                        <td rowspan="1"><?=$acSabadoBarraVCasitas;?></td> 
                                                        <td rowspan="1"><?=$acDomingoBarraMCasitas;?></td>                                     
                                                        <td rowspan="1"><?=$acDomingoBarraVCasitas;?></td> 
                                                        <td bgcolor="#d1ffaf"><b><?=$acBarraCasitas;?></b></td>                               
                                                    </tr>   

                                                    <tr>
                                                        <td>CA</td>
                                                        <td>MESAS</td>
                                                        <td rowspan="1"><?=$acLunesMesasMCasitas;?></td>  
                                                        <td rowspan="1"><?=$acLunesMesasVCasitas;?></td>  
                                                        <td rowspan="1"><?=$acMartesMesasMCasitas;?></td> 
                                                        <td rowspan="1"><?=$acMartesMesasVCasitas;?></td> 
                                                        <td rowspan="1"><?=$acMiercolesMesasMCasitas;?></td>
                                                        <td rowspan="1"><?=$acMiercolesMesasVCasitas;?></td>        
                                                        <td rowspan="1"><?=$acJuevesMesasMCasitas;?></td>   
                                                        <td rowspan="1"><?=$acJuevesMesasVCasitas;?></td>        
                                                        <td rowspan="1"><?=$acViernesMesasMCasitas;?></td>
                                                        <td rowspan="1"><?=$acViernesMesasVCasitas;?></td>        
                                                        <td rowspan="1"><?=$acSabadoMesasMCasitas;?></td> 
                                                        <td rowspan="1"><?=$acSabadoMesasVCasitas;?></td> 
                                                        <td rowspan="1"><?=$acDomingoMesasMCasitas;?></td>                                     
                                                        <td rowspan="1"><?=$acDomingoMesasVCasitas;?></td>  
                                                        <td bgcolor="#d1ffaf"><b><?=$acMesasCasitas;?></b></td>                                             
                                                    </tr> 
                                                    
                                                    <tr>
                                                        <td>CA</td>
                                                        <td>COCINA</td>
                                                        <td rowspan="1"><?=$acLunesCocinaMCasitas;?></td>  
                                                        <td rowspan="1"><?=$acLunesCocinaVCasitas;?></td>  
                                                        <td rowspan="1"><?=$acMartesCocinaMCasitas;?></td> 
                                                        <td rowspan="1"><?=$acMartesCocinaVCasitas;?></td> 
                                                        <td rowspan="1"><?=$acMiercolesCocinaMCasitas;?></td>
                                                        <td rowspan="1"><?=$acMiercolesCocinaVCasitas;?></td>        
                                                        <td rowspan="1"><?=$acJuevesCocinaMCasitas;?></td>   
                                                        <td rowspan="1"><?=$acJuevesCocinaVCasitas;?></td>        
                                                        <td rowspan="1"><?=$acViernesCocinaMCasitas;?></td>
                                                        <td rowspan="1"><?=$acViernesCocinaVCasitas;?></td>        
                                                        <td rowspan="1"><?=$acSabadoCocinaMCasitas;?></td> 
                                                        <td rowspan="1"><?=$acSabadoCocinaVCasitas;?></td> 
                                                        <td rowspan="1"><?=$acDomingoCocinaMCasitas;?></td>                                     
                                                        <td rowspan="1"><?=$acDomingoCocinaVCasitas;?></td>                                    
                                                        <td bgcolor="#d1ffaf"><b><?=$acCocinaCasitas;?></b></td>            
                                                    </tr> 

                                                    <tr>
                                                        <td>CA</td>
                                                        <td>APOYO</td>
                                                        <td rowspan="1"><?=$acLunesApoyoMCasitas;?></td>  
                                                        <td rowspan="1"><?=$acLunesApoyoVCasitas;?></td>  
                                                        <td rowspan="1"><?=$acMartesApoyoMCasitas;?></td> 
                                                        <td rowspan="1"><?=$acMartesApoyoVCasitas;?></td> 
                                                        <td rowspan="1"><?=$acMiercolesApoyoMCasitas;?></td>
                                                        <td rowspan="1"><?=$acMiercolesApoyoVCasitas;?></td>        
                                                        <td rowspan="1"><?=$acJuevesApoyoMCasitas;?></td>   
                                                        <td rowspan="1"><?=$acJuevesApoyoVCasitas;?></td>        
                                                        <td rowspan="1"><?=$acViernesApoyoMCasitas;?></td>
                                                        <td rowspan="1"><?=$acViernesApoyoVCasitas;?></td>        
                                                        <td rowspan="1"><?=$acSabadoApoyoMCasitas;?></td> 
                                                        <td rowspan="1"><?=$acSabadoApoyoVCasitas;?></td> 
                                                        <td rowspan="1"><?=$acDomingoApoyoMCasitas;?></td>                                     
                                                        <td rowspan="1"><?=$acDomingoApoyoVCasitas;?></td>                                     
                                                        <td bgcolor="#d1ffaf"><b><?=$acApoyoCasitas;?></b></td>           
                                                    </tr>       

                                                    <tr>
                                                        <td bgcolor="#76c5ff"><b>TOTALES</b></td>
                                                        <td bgcolor="#76c5ff"></td>                                                       
                                                        <td bgcolor="#d1ffaf" colspan="2"><b><?=$acLunesCasitas;?></b></td>  
                                                        <td bgcolor="#d1ffaf" colspan="2"><b><?=$acMartesCasitas;?></b></td>  
                                                        <td bgcolor="#d1ffaf" colspan="2"><b><?=$acMiercolesCasitas;?></b></td> 
                                                        <td bgcolor="#d1ffaf" colspan="2"><b><?=$acJuevesCasitas;?></b></td> 
                                                        <td bgcolor="#d1ffaf" colspan="2"><b><?=$acViernesCasitas;?></b></td>
                                                        <td bgcolor="#d1ffaf" colspan="2"><b><?=$acSabadoCasitas;?></b></td>        
                                                        <td bgcolor="#d1ffaf" colspan="2"><b><?=$acDomingoCasitas;?></b></td>                                                   
                                                        <td bgcolor="#d1ffaf"></td>   
                                                    </tr>                                         

                                                </tbody>                            
                                            </table>

                                            <table class="display responsive table table-bordered  btn-hover nowrap" style="width:100%" id="tbl_usuarios_6">
                                            
                                                <!--VARIABLES INICIO DEL PROYECTO-->
                                                    <?php                                                     
                                                    $acLunesBarraMMatamoros="";  
                                                    $acLunesBarraVMatamoros="";  
                                                    $acMartesBarraMMatamoros=""; 
                                                    $acMartesBarraVMatamoros=""; 
                                                    $acMiercolesBarraMMatamoros="";
                                                    $acMiercolesBarraVMatamoros="";        
                                                    $acJuevesBarraMMatamoros="";   
                                                    $acJuevesBarraVMatamoros="";        
                                                    $acViernesBarraMMatamoros="";
                                                    $acViernesBarraVMatamoros="";        
                                                    $acSabadoBarraMMatamoros=""; 
                                                    $acSabadoBarraVMatamoros=""; 
                                                    $acDomingoBarraMMatamoros="";                                     
                                                    $acDomingoBarraVMatamoros=""; 
                                                    
                                                    $acLunesMesasMMatamoros="";
                                                    $acLunesMesasVMatamoros="";
                                                    $acMartesMesasMMatamoros="";
                                                    $acMartesMesasVMatamoros="";
                                                    $acMiercolesMesasMMatamoros="";
                                                    $acMiercolesMesasVMatamoros="";
                                                    $acJuevesMesasMMatamoros="";
                                                    $acJuevesMesasVMatamoros="";
                                                    $acViernesMesasMMatamoros="";
                                                    $acViernesMesasVMatamoros="";
                                                    $acSabadoMesasMMatamoros="";
                                                    $acSabadoMesasVMatamoros="";
                                                    $acDomingoMesasMMatamoros="";
                                                    $acDomingoMesasVMatamoros="";
                                                    
                                                                    
                                                    $acLunesCocinaMMatamoros="";
                                                    $acLunesCocinaVMatamoros="";
                                                    $acMartesCocinaMMatamoros="";
                                                    $acMartesCocinaVMatamoros="";
                                                    $acMiercolesCocinaMMatamoros="";
                                                    $acMiercolesCocinaVMatamoros="";
                                                    $acJuevesCocinaMMatamoros="";
                                                    $acJuevesCocinaVMatamoros="";
                                                    $acViernesCocinaMMatamoros="";
                                                    $acViernesCocinaVMatamoros="";
                                                    $acSabadoCocinaMMatamoros="";
                                                    $acSabadoCocinaVMatamoros="";
                                                    $acDomingoCocinaMMatamoros="";
                                                    $acDomingoCocinaVMatamoros="";
                                                                                            
                                                    $acLunesApoyoMMatamoros="";
                                                    $acLunesApoyoVMatamoros="";
                                                    $acMartesApoyoMMatamoros="";
                                                    $acMartesApoyoVMatamoros="";
                                                    $acMiercolesApoyoMMatamoros="";
                                                    $acMiercolesApoyoVMatamoros="";
                                                    $acJuevesApoyoMMatamoros="";
                                                    $acJuevesApoyoVMatamoros="";
                                                    $acViernesApoyoMMatamoros="";
                                                    $acViernesApoyoVMatamoros="";
                                                    $acSabadoApoyoMMatamoros="";
                                                    $acSabadoApoyoVMatamoros="";
                                                    $acDomingoApoyoMMatamoros="";
                                                    $acDomingoApoyoVMatamoros="";
                                                    ?>
                                            
                                                <thead>
                                                    <tr>       

                                                    <?php 
                                                        $lunes = date( 'd', strtotime( 'monday this week' ));
                                                        $martes = date( 'd', strtotime( 'tuesday this week' ));
                                                        $miercoles = date( 'd', strtotime( 'wednesday this week' ));
                                                        $jueves = date( 'd', strtotime( 'thursday this week' ));
                                                        $viernes = date( 'd', strtotime( 'friday this week' ));
                                                        $sabado = date( 'd', strtotime( 'saturday this week' ));
                                                        $domingo = date( 'd', strtotime( 'sunday this week' ));                                
                                                    ?> 
                                                           
                                                    <th>PLAZA</th>      
                                                    <th>TURNO</th>                                                     
                                                    <th colspan="2">Lun <?php echo $lunesE;?></th>  
                                                    <th colspan="2">Mar <?php echo $martesE;?></th>                     
                                                    <th colspan="2">Mier <?php echo $miercolesE;?></th>                                    
                                                    <th colspan="2">Jue <?php echo $juevesE;?></th>                                                                        
                                                    <th colspan="2">Vie <?php echo $viernesE;?></th>
                                                    <th colspan="2">Sab <?php echo $sabadoE;?></th>
                                                    <th colspan="2">Dom <?php echo $domingoE;?></th>   
                                                    <th>Totales</th>                                                                                                  
                                                </tr>
                                                </thead>
                                                <tbody>     

                                                     <!--FOR(S) MATAMOROS-->                                                    
                                                        <?php if(!empty($matutinosBarraMatamoros)):?>                           
                                                            <?php foreach ($matutinosBarraMatamoros as $matutinoBarraMatamoros):?>
                                                                <?php switch ($matutinoBarraMatamoros->dia_id) {
                                                                    case 1;
                                                                        $acLunesBarraMMatamoros=$acLunesBarraMMatamoros."".$matutinoBarraMatamoros->Nombre_Rol."<br>";                                        
                                                                    break;

                                                                    case 2;                                 
                                                                        $acMartesBarraMMatamoros=$acMartesBarraMMatamoros."".$matutinoBarraMatamoros->Nombre_Rol."<br>";       
                                                                    break;

                                                                    case 3;                                        
                                                                        $acMiercolesBarraMMatamoros=$acMiercolesBarraMMatamoros."".$matutinoBarraMatamoros->Nombre_Rol."<br>";
                                                                    break;

                                                                    case 4;                                        
                                                                        $acJuevesBarraMMatamoros=$acJuevesBarraMMatamoros."".$matutinoBarraMatamoros->Nombre_Rol."<br>";
                                                                    break;

                                                                    case 5;                                        
                                                                        $acViernesBarraMMatamoros=$acViernesBarraMMatamoros."".$matutinoBarraMatamoros->Nombre_Rol."<br>";
                                                                    break;

                                                                    case 6;   
                                                                        $acSabadoBarraMMatamoros=$acSabadoBarraMMatamoros."".$matutinoBarraMatamoros->Nombre_Rol."<br>";                                     
                                                                    break;

                                                                    case 7;                                        
                                                                        $acDomingoBarraMMatamoros=$acDomingoBarraMMatamoros."".$matutinoBarraMatamoros->Nombre_Rol."<br>";
                                                                    break;                                                                        
                                                                }?>                              
                                                            <?php endforeach;?>                                                              
                                                        <?php endif;?>  

                                                        <?php if(!empty($vespertinosBarraMatamoros)):?>                           
                                                            <?php foreach ($vespertinosBarraMatamoros as $vespertinoBarraMatamoros):?>
                                                                <?php switch ($vespertinoBarraMatamoros->dia_id) {
                                                                    case 1;
                                                                        $acLunesBarraVMatamoros=$acLunesBarraVMatamoros."".$vespertinoBarraMatamoros->Nombre_Rol."<br>";                                        
                                                                    break;

                                                                    case 2;                                 
                                                                        $acMartesBarraVMatamoros=$acMartesBarraVMatamoros."".$vespertinoBarraMatamoros->Nombre_Rol."<br>";       
                                                                    break;

                                                                    case 3;                                        
                                                                        $acMiercolesBarraVMatamoros=$acMiercolesBarraVMatamoros."".$vespertinoBarraMatamoros->Nombre_Rol."<br>";
                                                                    break;

                                                                    case 4;                                        
                                                                        $acJuevesBarraVMatamoros=$acJuevesBarraVMatamoros."".$vespertinoBarraMatamoros->Nombre_Rol."<br>";
                                                                    break;

                                                                    case 5;                                        
                                                                        $acViernesBarraVMatamoros=$acViernesBarraVMatamoros."".$vespertinoBarraMatamoros->Nombre_Rol."<br>";
                                                                    break;

                                                                    case 6;   
                                                                        $acSabadoBarraVMatamoros=$acSabadoBarraVMatamoros."".$vespertinoBarraMatamoros->Nombre_Rol."<br>";                                     
                                                                    break;

                                                                    case 7;                                        
                                                                        $acDomingoBarraVMatamoros=$acDomingoBarraVMatamoros."".$vespertinoBarraMatamoros->Nombre_Rol."<br>";
                                                                    break;                                                                        
                                                                }?>                              
                                                            <?php endforeach;?>                                                              
                                                        <?php endif;?>  
                                                
                                                        <?php if(!empty($matutinosMesasMatamoros)):?>                           
                                                            <?php foreach ($matutinosMesasMatamoros as $matutinoMesasMatamoros):?>
                                                                <?php switch ($matutinoMesasMatamoros->dia_id) {
                                                                    case 1;
                                                                        $acLunesMesasMMatamoros=$acLunesMesasMMatamoros."".$matutinoMesasMatamoros->Nombre_Rol."<br>";                                        
                                                                    break;

                                                                    case 2;                                 
                                                                        $acMartesMesasMMatamoros=$acMartesMesasMMatamoros."".$matutinoMesasMatamoros->Nombre_Rol."<br>";       
                                                                    break;

                                                                    case 3;                                        
                                                                        $acMiercolesMesasMMatamoros=$acMiercolesMesasMMatamoros."".$matutinoMesasMatamoros->Nombre_Rol."<br>";
                                                                    break;

                                                                    case 4;                                        
                                                                        $acJuevesMesasMMatamoros=$acJuevesMesasMMatamoros."".$matutinoMesasMatamoros->Nombre_Rol."<br>";
                                                                    break;

                                                                    case 5;                                        
                                                                        $acViernesMesasMMatamoros=$acViernesMesasMMatamoros."".$matutinoMesasMatamoros->Nombre_Rol."<br>";
                                                                    break;

                                                                    case 6;   
                                                                        $acSabadoMesasMMatamoros=$acSabadoMesasMMatamoros."".$matutinoMesasMatamoros->Nombre_Rol."<br>";                                     
                                                                    break;

                                                                    case 7;                                        
                                                                        $acDomingoMesasMMatamoros=$acDomingoMesasMMatamoros."".$matutinoMesasMatamoros->Nombre_Rol."<br>";
                                                                    break;                                                                        
                                                                }?>                              
                                                            <?php endforeach;?>                                                              
                                                        <?php endif;?>  

                                                        <?php if(!empty($vespertinosMesasMatamoros)):?>                           
                                                            <?php foreach ($vespertinosMesasMatamoros as $vespertinoMesasMatamoros):?>
                                                                <?php switch ($vespertinoMesasMatamoros->dia_id) {
                                                                    case 1;
                                                                        $acLunesMesasVMatamoros=$acLunesMesasVMatamoros."".$vespertinoMesasMatamoros->Nombre_Rol."<br>";                                        
                                                                    break;

                                                                    case 2;                                 
                                                                        $acMartesMesasVMatamoros=$acMartesMesasVMatamoros."".$vespertinoMesasMatamoros->Nombre_Rol."<br>";       
                                                                    break;

                                                                    case 3;                                        
                                                                        $acMiercolesMesasVMatamoros=$acMiercolesMesasVMatamoros."".$vespertinoMesasMatamoros->Nombre_Rol."<br>";
                                                                    break;

                                                                    case 4;                                        
                                                                        $acJuevesMesasVMatamoros=$acJuevesMesasVMatamoros."".$vespertinoMesasMatamoros->Nombre_Rol."<br>";
                                                                    break;

                                                                    case 5;                                        
                                                                        $acViernesMesasVMatamoros=$acViernesMesasVMatamoros."".$vespertinoMesasMatamoros->Nombre_Rol."<br>";
                                                                    break;

                                                                    case 6;   
                                                                        $acSabadoMesasVMatamoros=$acSabadoMesasVMatamoros."".$vespertinoMesasMatamoros->Nombre_Rol."<br>";                                     
                                                                    break;

                                                                    case 7;                                        
                                                                        $acDomingoMesasVMatamoros=$acDomingoMesasVMatamoros."".$vespertinoMesasMatamoros->Nombre_Rol."<br>";
                                                                    break;                                                                        
                                                                }?>                              
                                                            <?php endforeach;?>                                                              
                                                        <?php endif;?>  

                                                        <?php if(!empty($matutinosCocinaMatamoros)):?>                           
                                                            <?php foreach ($matutinosCocinaMatamoros as $matutinoCocinaMatamoros):?>
                                                                <?php switch ($matutinoCocinaMatamoros->dia_id) {
                                                                    case 1;
                                                                        $acLunesCocinaMMatamoros=$acLunesCocinaMMatamoros."".$matutinoCocinaMatamoros->Nombre_Rol."<br>";                                        
                                                                    break;

                                                                    case 2;                                 
                                                                        $acMartesCocinaMMatamoros=$acMartesCocinaMMatamoros."".$matutinoCocinaMatamoros->Nombre_Rol."<br>";       
                                                                    break;

                                                                    case 3;                                        
                                                                        $acMiercolesCocinaMMatamoros=$acMiercolesCocinaMMatamoros."".$matutinoCocinaMatamoros->Nombre_Rol."<br>";
                                                                    break;

                                                                    case 4;                                        
                                                                        $acJuevesCocinaMMatamoros=$acJuevesCocinaMMatamoros."".$matutinoCocinaMatamoros->Nombre_Rol."<br>";
                                                                    break;

                                                                    case 5;                                        
                                                                        $acViernesCocinaMMatamoros=$acViernesCocinaMMatamoros."".$matutinoCocinaMatamoros->Nombre_Rol."<br>";
                                                                    break;

                                                                    case 6;   
                                                                        $acSabadoCocinaMMatamoros=$acSabadoCocinaMMatamoros."".$matutinoCocinaMatamoros->Nombre_Rol."<br>";                                     
                                                                    break;

                                                                    case 7;                                        
                                                                        $acDomingoCocinaMMatamoros=$acDomingoCocinaMMatamoros."".$matutinoCocinaMatamoros->Nombre_Rol."<br>";
                                                                    break;                                                                        
                                                                }?>                              
                                                            <?php endforeach;?>                                                              
                                                        <?php endif;?> 

                                                        <?php if(!empty($vespertinosCocinaMatamoros)):?>                           
                                                            <?php foreach ($vespertinosCocinaMatamoros as $vespertinoCocinaMatamoros):?>
                                                                <?php switch ($vespertinoCocinaMatamoros->dia_id) {
                                                                    case 1;
                                                                        $acLunesCocinaVMatamoros=$acLunesCocinaVMatamoros."".$vespertinoCocinaMatamoros->Nombre_Rol."<br>";                                        
                                                                    break;

                                                                    case 2;                                 
                                                                        $acMartesCocinaVMatamoros=$acMartesCocinaVMatamoros."".$vespertinoCocinaMatamoros->Nombre_Rol."<br>";       
                                                                    break;

                                                                    case 3;                                        
                                                                        $acMiercolesCocinaVMatamoros=$acMiercolesCocinaVMatamoros."".$vespertinoCocinaMatamoros->Nombre_Rol."<br>";
                                                                    break;

                                                                    case 4;                                        
                                                                        $acJuevesCocinaVMatamoros=$acJuevesCocinaVMatamoros."".$vespertinoCocinaMatamoros->Nombre_Rol."<br>";
                                                                    break;

                                                                    case 5;                                        
                                                                        $acViernesCocinaVMatamoros=$acViernesCocinaVMatamoros."".$vespertinoCocinaMatamoros->Nombre_Rol."<br>";
                                                                    break;

                                                                    case 6;   
                                                                        $acSabadoCocinaVMatamoros=$acSabadoCocinaVMatamoros."".$vespertinoCocinaMatamoros->Nombre_Rol."<br>";                                     
                                                                    break;

                                                                    case 7;                                        
                                                                        $acDomingoCocinaVMatamoros=$acDomingoCocinaVMatamoros."".$vespertinoCocinaMatamoros->Nombre_Rol."<br>";
                                                                    break;                                                                        
                                                                }?>                              
                                                            <?php endforeach;?>                                                              
                                                        <?php endif;?>  

                                                        <?php if(!empty($matutinosApoyoMatamoros)):?>                           
                                                            <?php foreach ($matutinosApoyoMatamoros as $matutinoApoyoMatamoros):?>
                                                                <?php switch ($matutinoApoyoMatamoros->dia_id) {
                                                                    case 1;
                                                                        $acLunesApoyoMMatamoros=$acLunesApoyoMMatamoros."".$matutinoApoyoMatamoros->Nombre_Rol."<br>";                                        
                                                                    break;

                                                                    case 2;                                 
                                                                        $acMartesApoyoMMatamoros=$acMartesApoyoMMatamoros."".$matutinoApoyoMatamoros->Nombre_Rol."<br>";       
                                                                    break;

                                                                    case 3;                                        
                                                                        $acMiercolesApoyoMMatamoros=$acMiercolesApoyoMMatamoros."".$matutinoApoyoMatamoros->Nombre_Rol."<br>";
                                                                    break;

                                                                    case 4;                                        
                                                                        $acJuevesApoyoMMatamoros=$acJuevesApoyoMMatamoros."".$matutinoApoyoMatamoros->Nombre_Rol."<br>";
                                                                    break;

                                                                    case 5;                                        
                                                                        $acViernesApoyoMMatamoros=$acViernesApoyoMMatamoros."".$matutinoApoyoMatamoros->Nombre_Rol."<br>";
                                                                    break;

                                                                    case 6;   
                                                                        $acSabadoApoyoMMatamoros=$acSabadoApoyoMMatamoros."".$matutinoApoyoMatamoros->Nombre_Rol."<br>";                                     
                                                                    break;

                                                                    case 7;                                        
                                                                        $acDomingoApoyoMMatamoros=$acDomingoApoyoMMatamoros."".$matutinoApoyoMatamoros->Nombre_Rol."<br>";
                                                                    break;                                                                        
                                                                }?>                              
                                                            <?php endforeach;?>                                                              
                                                        <?php endif;?> 

                                                        <?php if(!empty($vespertinosApoyoMatamoros)):?>                           
                                                            <?php foreach ($vespertinosApoyoMatamoros as $vespertinoApoyoMatamoros):?>
                                                                <?php switch ($vespertinoApoyoMatamoros->dia_id) {
                                                                    case 1;
                                                                        $acLunesApoyoVMatamoros=$acLunesApoyoVMatamoros."".$vespertinoApoyoMatamoros->Nombre_Rol."<br>";                                        
                                                                    break;

                                                                    case 2;                                 
                                                                        $acMartesApoyoVMatamoros=$acMartesApoyoVMatamoros."".$vespertinoApoyoMatamoros->Nombre_Rol."<br>";       
                                                                    break;

                                                                    case 3;                                        
                                                                        $acMiercolesApoyoVMatamoros=$acMiercolesApoyoVMatamoros."".$vespertinoApoyoMatamoros->Nombre_Rol."<br>";
                                                                    break;

                                                                    case 4;                                        
                                                                        $acJuevesApoyoVMatamoros=$acJuevesApoyoVMatamoros."".$vespertinoApoyoMatamoros->Nombre_Rol."<br>";
                                                                    break;

                                                                    case 5;                                        
                                                                        $acViernesApoyoVMatamoros=$acViernesApoyoVMatamoros."".$vespertinoApoyoMatamoros->Nombre_Rol."<br>";
                                                                    break;

                                                                    case 6;   
                                                                        $acSabadoApoyoVMatamoros=$acSabadoApoyoVMatamoros."".$vespertinoApoyoMatamoros->Nombre_Rol."<br>";                                     
                                                                    break;

                                                                    case 7;                                        
                                                                        $acDomingoApoyoVMatamoros=$acDomingoApoyoVMatamoros."".$vespertinoApoyoMatamoros->Nombre_Rol."<br>";
                                                                    break;                                                                        
                                                                }?>                              
                                                            <?php endforeach;?>                                                              
                                                        <?php endif;?>
                                                    <!--FOR(S) MATAMOROS-->   
                                                    
                                                    <tr>
                                                        <td>MA</td>
                                                        <td>BARRA</td>
                                                        <td rowspan="1"><?=$acLunesBarraMMatamoros;?></td>  
                                                        <td rowspan="1"><?=$acLunesBarraVMatamoros;?></td>  
                                                        <td rowspan="1"><?=$acMartesBarraMMatamoros;?></td> 
                                                        <td rowspan="1"><?=$acMartesBarraVMatamoros;?></td> 
                                                        <td rowspan="1"><?=$acMiercolesBarraMMatamoros;?></td>
                                                        <td rowspan="1"><?=$acMiercolesBarraVMatamoros;?></td>        
                                                        <td rowspan="1"><?=$acJuevesBarraMMatamoros;?></td>   
                                                        <td rowspan="1"><?=$acJuevesBarraVMatamoros;?></td>        
                                                        <td rowspan="1"><?=$acViernesBarraMMatamoros;?></td>
                                                        <td rowspan="1"><?=$acViernesBarraVMatamoros;?></td>        
                                                        <td rowspan="1"><?=$acSabadoBarraMMatamoros;?></td> 
                                                        <td rowspan="1"><?=$acSabadoBarraVMatamoros;?></td> 
                                                        <td rowspan="1"><?=$acDomingoBarraMMatamoros;?></td>                                     
                                                        <td rowspan="1"><?=$acDomingoBarraVMatamoros;?></td> 
                                                        <td bgcolor="#d1ffaf"><?=$acBarraMatamoros;?></td>                              
                                                    </tr>   

                                                    <tr>
                                                        <td>MA</td>
                                                        <td>MESAS</td>
                                                        <td rowspan="1"><?=$acLunesMesasMMatamoros;?></td>  
                                                        <td rowspan="1"><?=$acLunesMesasVMatamoros;?></td>  
                                                        <td rowspan="1"><?=$acMartesMesasMMatamoros;?></td> 
                                                        <td rowspan="1"><?=$acMartesMesasVMatamoros;?></td> 
                                                        <td rowspan="1"><?=$acMiercolesMesasMMatamoros;?></td>
                                                        <td rowspan="1"><?=$acMiercolesMesasVMatamoros;?></td>        
                                                        <td rowspan="1"><?=$acJuevesMesasMMatamoros;?></td>   
                                                        <td rowspan="1"><?=$acJuevesMesasVMatamoros;?></td>        
                                                        <td rowspan="1"><?=$acViernesMesasMMatamoros;?></td>
                                                        <td rowspan="1"><?=$acViernesMesasVMatamoros;?></td>        
                                                        <td rowspan="1"><?=$acSabadoMesasMMatamoros;?></td> 
                                                        <td rowspan="1"><?=$acSabadoMesasVMatamoros;?></td> 
                                                        <td rowspan="1"><?=$acDomingoMesasMMatamoros;?></td>                                     
                                                        <td rowspan="1"><?=$acDomingoMesasVMatamoros;?></td>  
                                                        <td bgcolor="#d1ffaf"><b><?=$acMesasMatamoros;?></b></td>                                                                 
                                                    </tr> 
                                                    
                                                    <tr>
                                                        <td>MA</td>
                                                        <td>COCINA</td>
                                                        <td rowspan="1"><?=$acLunesCocinaMMatamoros;?></td>  
                                                        <td rowspan="1"><?=$acLunesCocinaVMatamoros;?></td>  
                                                        <td rowspan="1"><?=$acMartesCocinaMMatamoros;?></td> 
                                                        <td rowspan="1"><?=$acMartesCocinaVMatamoros;?></td> 
                                                        <td rowspan="1"><?=$acMiercolesCocinaMMatamoros;?></td>
                                                        <td rowspan="1"><?=$acMiercolesCocinaVMatamoros;?></td>        
                                                        <td rowspan="1"><?=$acJuevesCocinaMMatamoros;?></td>   
                                                        <td rowspan="1"><?=$acJuevesCocinaVMatamoros;?></td>        
                                                        <td rowspan="1"><?=$acViernesCocinaMMatamoros;?></td>
                                                        <td rowspan="1"><?=$acViernesCocinaVMatamoros;?></td>        
                                                        <td rowspan="1"><?=$acSabadoCocinaMMatamoros;?></td> 
                                                        <td rowspan="1"><?=$acSabadoCocinaVMatamoros;?></td> 
                                                        <td rowspan="1"><?=$acDomingoCocinaMMatamoros;?></td>                                     
                                                        <td rowspan="1"><?=$acDomingoCocinaVMatamoros;?></td>                                    
                                                        <td bgcolor="#d1ffaf"><b><?=$acCocinaMatamoros;?></b></td>                                                                 
                                                    </tr> 

                                                    <tr>
                                                        <td>MA</td>
                                                        <td>APOYO</td>
                                                        <td rowspan="1"><?=$acLunesApoyoMMatamoros;?></td>  
                                                        <td rowspan="1"><?=$acLunesApoyoVMatamoros;?></td>  
                                                        <td rowspan="1"><?=$acMartesApoyoMMatamoros;?></td> 
                                                        <td rowspan="1"><?=$acMartesApoyoVMatamoros;?></td> 
                                                        <td rowspan="1"><?=$acMiercolesApoyoMMatamoros;?></td>
                                                        <td rowspan="1"><?=$acMiercolesApoyoVMatamoros;?></td>        
                                                        <td rowspan="1"><?=$acJuevesApoyoMMatamoros;?></td>   
                                                        <td rowspan="1"><?=$acJuevesApoyoVMatamoros;?></td>        
                                                        <td rowspan="1"><?=$acViernesApoyoMMatamoros;?></td>
                                                        <td rowspan="1"><?=$acViernesApoyoVMatamoros;?></td>        
                                                        <td rowspan="1"><?=$acSabadoApoyoMMatamoros;?></td> 
                                                        <td rowspan="1"><?=$acSabadoApoyoVMatamoros;?></td> 
                                                        <td rowspan="1"><?=$acDomingoApoyoMMatamoros;?></td>                                     
                                                        <td rowspan="1"><?=$acDomingoApoyoVMatamoros;?></td>                                     
                                                        <td bgcolor="#d1ffaf"><b><?=$acApoyoMatamoros;?></b></td>                                                                 
                                                    </tr>       

                                                    <tr>                                                        
                                                        <td bgcolor="#76c5ff"><b>TOTALES</b></td>
                                                        <td bgcolor="#76c5ff"></td>
                                                        <td bgcolor="#d1ffaf" colspan="2"><b><?=$acLunesMatamoros;?></b></td>  
                                                        <td bgcolor="#d1ffaf" colspan="2"><b><?=$acMartesMatamoros;?></b></td>  
                                                        <td bgcolor="#d1ffaf" colspan="2"><b><?=$acMiercolesMatamoros;?></b></td> 
                                                        <td bgcolor="#d1ffaf" colspan="2"><b><?=$acJuevesMatamoros;?></b></td> 
                                                        <td bgcolor="#d1ffaf" colspan="2"><b><?=$acViernesMatamoros;?></b></td>
                                                        <td bgcolor="#d1ffaf" colspan="2"><b><?=$acSabadoMatamoros;?></b></td>        
                                                        <td bgcolor="#d1ffaf" colspan="2"><b><?=$acDomingoMatamoros;?></b></td>                                                  
                                                        <td bgcolor="#d1ffaf"></td>   
                                                    </tr>                                         

                                                </tbody>                            
                                            </table>

                                            <table class="display responsive table table-bordered  btn-hover nowrap" style="width:100%" id="tbl_usuarios_7">
                                            
                                                <!--VARIABLES INICIO DEL PROYECTO-->
                                                    <?php                                                     
                                                        $acLunesBarraMOaxaca="";  
                                                        $acLunesBarraVOaxaca="";  
                                                        $acMartesBarraMOaxaca=""; 
                                                        $acMartesBarraVOaxaca=""; 
                                                        $acMiercolesBarraMOaxaca="";
                                                        $acMiercolesBarraVOaxaca="";        
                                                        $acJuevesBarraMOaxaca="";   
                                                        $acJuevesBarraVOaxaca="";        
                                                        $acViernesBarraMOaxaca="";
                                                        $acViernesBarraVOaxaca="";        
                                                        $acSabadoBarraMOaxaca=""; 
                                                        $acSabadoBarraVOaxaca=""; 
                                                        $acDomingoBarraMOaxaca="";                                     
                                                        $acDomingoBarraVOaxaca=""; 
                                                        
                                                        $acLunesMesasMOaxaca="";
                                                        $acLunesMesasVOaxaca="";
                                                        $acMartesMesasMOaxaca="";
                                                        $acMartesMesasVOaxaca="";
                                                        $acMiercolesMesasMOaxaca="";
                                                        $acMiercolesMesasVOaxaca="";
                                                        $acJuevesMesasMOaxaca="";
                                                        $acJuevesMesasVOaxaca="";
                                                        $acViernesMesasMOaxaca="";
                                                        $acViernesMesasVOaxaca="";
                                                        $acSabadoMesasMOaxaca="";
                                                        $acSabadoMesasVOaxaca="";
                                                        $acDomingoMesasMOaxaca="";
                                                        $acDomingoMesasVOaxaca="";
                                                                                                                        
                                                        $acLunesCocinaMOaxaca="";
                                                        $acLunesCocinaVOaxaca="";
                                                        $acMartesCocinaMOaxaca="";
                                                        $acMartesCocinaVOaxaca="";
                                                        $acMiercolesCocinaMOaxaca="";
                                                        $acMiercolesCocinaVOaxaca="";
                                                        $acJuevesCocinaMOaxaca="";
                                                        $acJuevesCocinaVOaxaca="";
                                                        $acViernesCocinaMOaxaca="";
                                                        $acViernesCocinaVOaxaca="";
                                                        $acSabadoCocinaMOaxaca="";
                                                        $acSabadoCocinaVOaxaca="";
                                                        $acDomingoCocinaMOaxaca="";
                                                        $acDomingoCocinaVOaxaca="";
                                                                                                
                                                        $acLunesApoyoMOaxaca="";
                                                        $acLunesApoyoVOaxaca="";
                                                        $acMartesApoyoMOaxaca="";
                                                        $acMartesApoyoVOaxaca="";
                                                        $acMiercolesApoyoMOaxaca="";
                                                        $acMiercolesApoyoVOaxaca="";
                                                        $acJuevesApoyoMOaxaca="";
                                                        $acJuevesApoyoVOaxaca="";
                                                        $acViernesApoyoMOaxaca="";
                                                        $acViernesApoyoVOaxaca="";
                                                        $acSabadoApoyoMOaxaca="";
                                                        $acSabadoApoyoVOaxaca="";
                                                        $acDomingoApoyoMOaxaca="";
                                                        $acDomingoApoyoVOaxaca="";
                                                    ?>
                                            
                                                <thead>
                                                    <tr>       

                                                    <?php 
                                                        $lunes = date( 'd', strtotime( 'monday this week' ));
                                                        $martes = date( 'd', strtotime( 'tuesday this week' ));
                                                        $miercoles = date( 'd', strtotime( 'wednesday this week' ));
                                                        $jueves = date( 'd', strtotime( 'thursday this week' ));
                                                        $viernes = date( 'd', strtotime( 'friday this week' ));
                                                        $sabado = date( 'd', strtotime( 'saturday this week' ));
                                                        $domingo = date( 'd', strtotime( 'sunday this week' ));                                
                                                    ?> 
                                                        
                                                    <th>PLAZA</th>      
                                                    <th>TURNO</th>                                                     
                                                    <th colspan="2">Lun <?php echo $lunesE;?></th>  
                                                    <th colspan="2">Mar <?php echo $martesE;?></th>                     
                                                    <th colspan="2">Mier <?php echo $miercolesE;?></th>                                    
                                                    <th colspan="2">Jue <?php echo $juevesE;?></th>                                                                        
                                                    <th colspan="2">Vie <?php echo $viernesE;?></th>
                                                    <th colspan="2">Sab <?php echo $sabadoE;?></th>
                                                    <th colspan="2">Dom <?php echo $domingoE;?></th>   
                                                    <th>Totales</th>  
                                                </tr>
                                                </thead>
                                                <tbody>                                                 
                                                    <!--FOR(S) OAXACA-->                                                    
                                                        <?php if(!empty($matutinosBarraOaxaca)):?>                           
                                                            <?php foreach ($matutinosBarraOaxaca as $matutinoBarraOaxaca):?>
                                                                <?php switch ($matutinoBarraOaxaca->dia_id) {
                                                                    case 1;
                                                                        $acLunesBarraMOaxaca=$acLunesBarraMOaxaca."".$matutinoBarraOaxaca->Nombre_Rol."<br>";                                        
                                                                    break;

                                                                    case 2;                                 
                                                                        $acMartesBarraMOaxaca=$acMartesBarraMOaxaca."".$matutinoBarraOaxaca->Nombre_Rol."<br>";       
                                                                    break;

                                                                    case 3;                                        
                                                                        $acMiercolesBarraMOaxaca=$acMiercolesBarraMOaxaca."".$matutinoBarraOaxaca->Nombre_Rol."<br>";
                                                                    break;

                                                                    case 4;                                        
                                                                        $acJuevesBarraMOaxaca=$acJuevesBarraMOaxaca."".$matutinoBarraOaxaca->Nombre_Rol."<br>";
                                                                    break;

                                                                    case 5;                                        
                                                                        $acViernesBarraMOaxaca=$acViernesBarraMOaxaca."".$matutinoBarraOaxaca->Nombre_Rol."<br>";
                                                                    break;

                                                                    case 6;   
                                                                        $acSabadoBarraMOaxaca=$acSabadoBarraMOaxaca."".$matutinoBarraOaxaca->Nombre_Rol."<br>";                                     
                                                                    break;

                                                                    case 7;                                        
                                                                        $acDomingoBarraMOaxaca=$acDomingoBarraMOaxaca."".$matutinoBarraOaxaca->Nombre_Rol."<br>";
                                                                    break;                                                                        
                                                                }?>                              
                                                            <?php endforeach;?>                                                              
                                                        <?php endif;?>  

                                                        <?php if(!empty($vespertinosBarraOaxaca)):?>                           
                                                            <?php foreach ($vespertinosBarraOaxaca as $vespertinoBarraOaxaca):?>
                                                                <?php switch ($vespertinoBarraOaxaca->dia_id) {
                                                                    case 1;
                                                                        $acLunesBarraVOaxaca=$acLunesBarraVOaxaca."".$vespertinoBarraOaxaca->Nombre_Rol."<br>";                                        
                                                                    break;

                                                                    case 2;                                 
                                                                        $acMartesBarraVOaxaca=$acMartesBarraVOaxaca."".$vespertinoBarraOaxaca->Nombre_Rol."<br>";       
                                                                    break;

                                                                    case 3;                                        
                                                                        $acMiercolesBarraVOaxaca=$acMiercolesBarraVOaxaca."".$vespertinoBarraOaxaca->Nombre_Rol."<br>";
                                                                    break;

                                                                    case 4;                                        
                                                                        $acJuevesBarraVOaxaca=$acJuevesBarraVOaxaca."".$vespertinoBarraOaxaca->Nombre_Rol."<br>";
                                                                    break;

                                                                    case 5;                                        
                                                                        $acViernesBarraVOaxaca=$acViernesBarraVOaxaca."".$vespertinoBarraOaxaca->Nombre_Rol."<br>";
                                                                    break;

                                                                    case 6;   
                                                                        $acSabadoBarraVOaxaca=$acSabadoBarraVOaxaca."".$vespertinoBarraOaxaca->Nombre_Rol."<br>";                                     
                                                                    break;

                                                                    case 7;                                        
                                                                        $acDomingoBarraVOaxaca=$acDomingoBarraVOaxaca."".$vespertinoBarraOaxaca->Nombre_Rol."<br>";
                                                                    break;                                                                        
                                                                }?>                              
                                                            <?php endforeach;?>                                                              
                                                        <?php endif;?>  
                                                
                                                        <?php if(!empty($matutinosMesasOaxaca)):?>                           
                                                            <?php foreach ($matutinosMesasOaxaca as $matutinoMesasOaxaca):?>
                                                                <?php switch ($matutinoMesasOaxaca->dia_id) {
                                                                    case 1;
                                                                        $acLunesMesasMOaxaca=$acLunesMesasMOaxaca."".$matutinoMesasOaxaca->Nombre_Rol."<br>";                                        
                                                                    break;

                                                                    case 2;                                 
                                                                        $acMartesMesasMOaxaca=$acMartesMesasMOaxaca."".$matutinoMesasOaxaca->Nombre_Rol."<br>";       
                                                                    break;

                                                                    case 3;                                        
                                                                        $acMiercolesMesasMOaxaca=$acMiercolesMesasMOaxaca."".$matutinoMesasOaxaca->Nombre_Rol."<br>";
                                                                    break;

                                                                    case 4;                                        
                                                                        $acJuevesMesasMOaxaca=$acJuevesMesasMOaxaca."".$matutinoMesasOaxaca->Nombre_Rol."<br>";
                                                                    break;

                                                                    case 5;                                        
                                                                        $acViernesMesasMOaxaca=$acViernesMesasMOaxaca."".$matutinoMesasOaxaca->Nombre_Rol."<br>";
                                                                    break;

                                                                    case 6;   
                                                                        $acSabadoMesasMOaxaca=$acSabadoMesasMOaxaca."".$matutinoMesasOaxaca->Nombre_Rol."<br>";                                     
                                                                    break;

                                                                    case 7;                                        
                                                                        $acDomingoMesasMOaxaca=$acDomingoMesasMOaxaca."".$matutinoMesasOaxaca->Nombre_Rol."<br>";
                                                                    break;                                                                        
                                                                }?>                              
                                                            <?php endforeach;?>                                                              
                                                        <?php endif;?>  

                                                        <?php if(!empty($vespertinosMesasOaxaca)):?>                           
                                                            <?php foreach ($vespertinosMesasOaxaca as $vespertinoMesasOaxaca):?>
                                                                <?php switch ($vespertinoMesasOaxaca->dia_id) {
                                                                    case 1;
                                                                        $acLunesMesasVOaxaca=$acLunesMesasVOaxaca."".$vespertinoMesasOaxaca->Nombre_Rol."<br>";                                        
                                                                    break;

                                                                    case 2;                                 
                                                                        $acMartesMesasVOaxaca=$acMartesMesasVOaxaca."".$vespertinoMesasOaxaca->Nombre_Rol."<br>";       
                                                                    break;

                                                                    case 3;                                        
                                                                        $acMiercolesMesasVOaxaca=$acMiercolesMesasVOaxaca."".$vespertinoMesasOaxaca->Nombre_Rol."<br>";
                                                                    break;

                                                                    case 4;                                        
                                                                        $acJuevesMesasVOaxaca=$acJuevesMesasVOaxaca."".$vespertinoMesasOaxaca->Nombre_Rol."<br>";
                                                                    break;

                                                                    case 5;                                        
                                                                        $acViernesMesasVOaxaca=$acViernesMesasVOaxaca."".$vespertinoMesasOaxaca->Nombre_Rol."<br>";
                                                                    break;

                                                                    case 6;   
                                                                        $acSabadoMesasVOaxaca=$acSabadoMesasVOaxaca."".$vespertinoMesasOaxaca->Nombre_Rol."<br>";                                     
                                                                    break;

                                                                    case 7;                                        
                                                                        $acDomingoMesasVOaxaca=$acDomingoMesasVOaxaca."".$vespertinoMesasOaxaca->Nombre_Rol."<br>";
                                                                    break;                                                                        
                                                                }?>                              
                                                            <?php endforeach;?>                                                              
                                                        <?php endif;?>  

                                                        <?php if(!empty($matutinosCocinaOaxaca)):?>                           
                                                            <?php foreach ($matutinosCocinaOaxaca as $matutinoCocinaOaxaca):?>
                                                                <?php switch ($matutinoCocinaOaxaca->dia_id) {
                                                                    case 1;
                                                                        $acLunesCocinaMOaxaca=$acLunesCocinaMOaxaca."".$matutinoCocinaOaxaca->Nombre_Rol."<br>";                                        
                                                                    break;

                                                                    case 2;                                 
                                                                        $acMartesCocinaMOaxaca=$acMartesCocinaMOaxaca."".$matutinoCocinaOaxaca->Nombre_Rol."<br>";       
                                                                    break;

                                                                    case 3;                                        
                                                                        $acMiercolesCocinaMOaxaca=$acMiercolesCocinaMOaxaca."".$matutinoCocinaOaxaca->Nombre_Rol."<br>";
                                                                    break;

                                                                    case 4;                                        
                                                                        $acJuevesCocinaMOaxaca=$acJuevesCocinaMOaxaca."".$matutinoCocinaOaxaca->Nombre_Rol."<br>";
                                                                    break;

                                                                    case 5;                                        
                                                                        $acViernesCocinaMOaxaca=$acViernesCocinaMOaxaca."".$matutinoCocinaOaxaca->Nombre_Rol."<br>";
                                                                    break;

                                                                    case 6;   
                                                                        $acSabadoCocinaMOaxaca=$acSabadoCocinaMOaxaca."".$matutinoCocinaOaxaca->Nombre_Rol."<br>";                                     
                                                                    break;

                                                                    case 7;                                        
                                                                        $acDomingoCocinaMOaxaca=$acDomingoCocinaMOaxaca."".$matutinoCocinaOaxaca->Nombre_Rol."<br>";
                                                                    break;                                                                        
                                                                }?>                              
                                                            <?php endforeach;?>                                                              
                                                        <?php endif;?> 

                                                        <?php if(!empty($vespertinosCocinaOaxaca)):?>                           
                                                            <?php foreach ($vespertinosCocinaOaxaca as $vespertinoCocinaOaxaca):?>
                                                                <?php switch ($vespertinoCocinaOaxaca->dia_id) {
                                                                    case 1;
                                                                        $acLunesCocinaVOaxaca=$acLunesCocinaVOaxaca."".$vespertinoCocinaOaxaca->Nombre_Rol."<br>";                                        
                                                                    break;

                                                                    case 2;                                 
                                                                        $acMartesCocinaVOaxaca=$acMartesCocinaVOaxaca."".$vespertinoCocinaOaxaca->Nombre_Rol."<br>";       
                                                                    break;

                                                                    case 3;                                        
                                                                        $acMiercolesCocinaVOaxaca=$acMiercolesCocinaVOaxaca."".$vespertinoCocinaOaxaca->Nombre_Rol."<br>";
                                                                    break;

                                                                    case 4;                                        
                                                                        $acJuevesCocinaVOaxaca=$acJuevesCocinaVOaxaca."".$vespertinoCocinaOaxaca->Nombre_Rol."<br>";
                                                                    break;

                                                                    case 5;                                        
                                                                        $acViernesCocinaVOaxaca=$acViernesCocinaVOaxaca."".$vespertinoCocinaOaxaca->Nombre_Rol."<br>";
                                                                    break;

                                                                    case 6;   
                                                                        $acSabadoCocinaVOaxaca=$acSabadoCocinaVOaxaca."".$vespertinoCocinaOaxaca->Nombre_Rol."<br>";                                     
                                                                    break;

                                                                    case 7;                                        
                                                                        $acDomingoCocinaVOaxaca=$acDomingoCocinaVOaxaca."".$vespertinoCocinaOaxaca->Nombre_Rol."<br>";
                                                                    break;                                                                        
                                                                }?>                              
                                                            <?php endforeach;?>                                                              
                                                        <?php endif;?>  

                                                        <?php if(!empty($matutinosApoyoOaxaca)):?>                           
                                                            <?php foreach ($matutinosApoyoOaxaca as $matutinoApoyoOaxaca):?>
                                                                <?php switch ($matutinoApoyoOaxaca->dia_id) {
                                                                    case 1;
                                                                        $acLunesApoyoMOaxaca=$acLunesApoyoMOaxaca."".$matutinoApoyoOaxaca->Nombre_Rol."<br>";                                        
                                                                    break;

                                                                    case 2;                                 
                                                                        $acMartesApoyoMOaxaca=$acMartesApoyoMOaxaca."".$matutinoApoyoOaxaca->Nombre_Rol."<br>";       
                                                                    break;

                                                                    case 3;                                        
                                                                        $acMiercolesApoyoMOaxaca=$acMiercolesApoyoMOaxaca."".$matutinoApoyoOaxaca->Nombre_Rol."<br>";
                                                                    break;

                                                                    case 4;                                        
                                                                        $acJuevesApoyoMOaxaca=$acJuevesApoyoMOaxaca."".$matutinoApoyoOaxaca->Nombre_Rol."<br>";
                                                                    break;

                                                                    case 5;                                        
                                                                        $acViernesApoyoMOaxaca=$acViernesApoyoMOaxaca."".$matutinoApoyoOaxaca->Nombre_Rol."<br>";
                                                                    break;

                                                                    case 6;   
                                                                        $acSabadoApoyoMOaxaca=$acSabadoApoyoMOaxaca."".$matutinoApoyoOaxaca->Nombre_Rol."<br>";                                     
                                                                    break;

                                                                    case 7;                                        
                                                                        $acDomingoApoyoMOaxaca=$acDomingoApoyoMOaxaca."".$matutinoApoyoOaxaca->Nombre_Rol."<br>";
                                                                    break;                                                                        
                                                                }?>                              
                                                            <?php endforeach;?>                                                              
                                                        <?php endif;?> 

                                                        <?php if(!empty($vespertinosApoyoOaxaca)):?>                           
                                                            <?php foreach ($vespertinosApoyoOaxaca as $vespertinoApoyoOaxaca):?>
                                                                <?php switch ($vespertinoApoyoOaxaca->dia_id) {
                                                                    case 1;
                                                                        $acLunesApoyoVOaxaca=$acLunesApoyoVOaxaca."".$vespertinoApoyoOaxaca->Nombre_Rol."<br>";                                        
                                                                    break;

                                                                    case 2;                                 
                                                                        $acMartesApoyoVOaxaca=$acMartesApoyoVOaxaca."".$vespertinoApoyoOaxaca->Nombre_Rol."<br>";       
                                                                    break;

                                                                    case 3;                                        
                                                                        $acMiercolesApoyoVOaxaca=$acMiercolesApoyoVOaxaca."".$vespertinoApoyoOaxaca->Nombre_Rol."<br>";
                                                                    break;

                                                                    case 4;                                        
                                                                        $acJuevesApoyoVOaxaca=$acJuevesApoyoVOaxaca."".$vespertinoApoyoOaxaca->Nombre_Rol."<br>";
                                                                    break;

                                                                    case 5;                                        
                                                                        $acViernesApoyoVOaxaca=$acViernesApoyoVOaxaca."".$vespertinoApoyoOaxaca->Nombre_Rol."<br>";
                                                                    break;

                                                                    case 6;   
                                                                        $acSabadoApoyoVOaxaca=$acSabadoApoyoVOaxaca."".$vespertinoApoyoOaxaca->Nombre_Rol."<br>";                                     
                                                                    break;

                                                                    case 7;                                        
                                                                        $acDomingoApoyoVOaxaca=$acDomingoApoyoVOaxaca."".$vespertinoApoyoOaxaca->Nombre_Rol."<br>";
                                                                    break;                                                                        
                                                                }?>                              
                                                            <?php endforeach;?>                                                              
                                                        <?php endif;?>
                                                    <!--FOR(S) OAXACA-->  

                                                    <tr>
                                                        <td>OA</td>
                                                        <td>BARRA</td>
                                                        <td rowspan="1"><?=$acLunesBarraMOaxaca;?></td>  
                                                        <td rowspan="1"><?=$acLunesBarraVOaxaca;?></td>  
                                                        <td rowspan="1"><?=$acMartesBarraMOaxaca;?></td> 
                                                        <td rowspan="1"><?=$acMartesBarraVOaxaca;?></td> 
                                                        <td rowspan="1"><?=$acMiercolesBarraMOaxaca;?></td>
                                                        <td rowspan="1"><?=$acMiercolesBarraVOaxaca;?></td>        
                                                        <td rowspan="1"><?=$acJuevesBarraMOaxaca;?></td>   
                                                        <td rowspan="1"><?=$acJuevesBarraVOaxaca;?></td>        
                                                        <td rowspan="1"><?=$acViernesBarraMOaxaca;?></td>
                                                        <td rowspan="1"><?=$acViernesBarraVOaxaca;?></td>        
                                                        <td rowspan="1"><?=$acSabadoBarraMOaxaca;?></td> 
                                                        <td rowspan="1"><?=$acSabadoBarraVOaxaca;?></td> 
                                                        <td rowspan="1"><?=$acDomingoBarraMOaxaca;?></td>                                     
                                                        <td rowspan="1"><?=$acDomingoBarraVOaxaca;?></td> 
                                                        <td bgcolor="#d1ffaf"><b><?=$acBarraOaxaca;?></b></td>                                  
                                                    </tr>   

                                                    <tr>
                                                        <td>OA</td>
                                                        <td>MESAS</td>
                                                        <td rowspan="1"><?=$acLunesMesasMOaxaca;?></td>  
                                                        <td rowspan="1"><?=$acLunesMesasVOaxaca;?></td>  
                                                        <td rowspan="1"><?=$acMartesMesasMOaxaca;?></td> 
                                                        <td rowspan="1"><?=$acMartesMesasVOaxaca;?></td> 
                                                        <td rowspan="1"><?=$acMiercolesMesasMOaxaca;?></td>
                                                        <td rowspan="1"><?=$acMiercolesMesasVOaxaca;?></td>        
                                                        <td rowspan="1"><?=$acJuevesMesasMOaxaca;?></td>   
                                                        <td rowspan="1"><?=$acJuevesMesasVOaxaca;?></td>        
                                                        <td rowspan="1"><?=$acViernesMesasMOaxaca;?></td>
                                                        <td rowspan="1"><?=$acViernesMesasVOaxaca;?></td>        
                                                        <td rowspan="1"><?=$acSabadoMesasMOaxaca;?></td> 
                                                        <td rowspan="1"><?=$acSabadoMesasVOaxaca;?></td> 
                                                        <td rowspan="1"><?=$acDomingoMesasMOaxaca;?></td>                                     
                                                        <td rowspan="1"><?=$acDomingoMesasVOaxaca;?></td>  
                                                        <td bgcolor="#d1ffaf"><b><?=$acMesasOaxaca;?></b></td>                                  
                                                    </tr> 
                                                    
                                                    <tr>
                                                        <td>OA</td>
                                                        <td>COCINA</td>
                                                        <td rowspan="1"><?=$acLunesCocinaMOaxaca;?></td>  
                                                        <td rowspan="1"><?=$acLunesCocinaVOaxaca;?></td>  
                                                        <td rowspan="1"><?=$acMartesCocinaMOaxaca;?></td> 
                                                        <td rowspan="1"><?=$acMartesCocinaVOaxaca;?></td> 
                                                        <td rowspan="1"><?=$acMiercolesCocinaMOaxaca;?></td>
                                                        <td rowspan="1"><?=$acMiercolesCocinaVOaxaca;?></td>        
                                                        <td rowspan="1"><?=$acJuevesCocinaMOaxaca;?></td>   
                                                        <td rowspan="1"><?=$acJuevesCocinaVOaxaca;?></td>        
                                                        <td rowspan="1"><?=$acViernesCocinaMOaxaca;?></td>
                                                        <td rowspan="1"><?=$acViernesCocinaVOaxaca;?></td>        
                                                        <td rowspan="1"><?=$acSabadoCocinaMOaxaca;?></td> 
                                                        <td rowspan="1"><?=$acSabadoCocinaVOaxaca;?></td> 
                                                        <td rowspan="1"><?=$acDomingoCocinaMOaxaca;?></td>                                     
                                                        <td rowspan="1"><?=$acDomingoCocinaVOaxaca;?></td>                                    
                                                        <td bgcolor="#d1ffaf"><b><?=$acCocinaOaxaca;?></b></td>                                
                                                    </tr> 

                                                    <tr>
                                                        <td>OA</td>
                                                        <td>APOYO</td>
                                                        <td rowspan="1"><?=$acLunesApoyoMOaxaca;?></td>  
                                                        <td rowspan="1"><?=$acLunesApoyoVOaxaca;?></td>  
                                                        <td rowspan="1"><?=$acMartesApoyoMOaxaca;?></td> 
                                                        <td rowspan="1"><?=$acMartesApoyoVOaxaca;?></td> 
                                                        <td rowspan="1"><?=$acMiercolesApoyoMOaxaca;?></td>
                                                        <td rowspan="1"><?=$acMiercolesApoyoVOaxaca;?></td>        
                                                        <td rowspan="1"><?=$acJuevesApoyoMOaxaca;?></td>   
                                                        <td rowspan="1"><?=$acJuevesApoyoVOaxaca;?></td>        
                                                        <td rowspan="1"><?=$acViernesApoyoMOaxaca;?></td>
                                                        <td rowspan="1"><?=$acViernesApoyoVOaxaca;?></td>        
                                                        <td rowspan="1"><?=$acSabadoApoyoMOaxaca;?></td> 
                                                        <td rowspan="1"><?=$acSabadoApoyoVOaxaca;?></td> 
                                                        <td rowspan="1"><?=$acDomingoApoyoMOaxaca;?></td>                                     
                                                        <td rowspan="1"><?=$acDomingoApoyoVOaxaca;?></td>                                     
                                                        <td bgcolor="#d1ffaf"><b><?=$acApoyoOaxaca;?></b></td>                                
                                                    </tr>       

                                                    <tr>                                                        
                                                        <td bgcolor="#76c5ff"><b>TOTALES</b></td>
                                                        <td bgcolor="#76c5ff"></td>
                                                        <td bgcolor="#d1ffaf" colspan="2"><b><?=$acLunesOaxaca;?></b></td>  
                                                        <td bgcolor="#d1ffaf" colspan="2"><b><?=$acMartesOaxaca;?></b></td>  
                                                        <td bgcolor="#d1ffaf" colspan="2"><b><?=$acMiercolesOaxaca;?></b></td> 
                                                        <td bgcolor="#d1ffaf" colspan="2"><b><?=$acJuevesOaxaca;?></b></td> 
                                                        <td bgcolor="#d1ffaf" colspan="2"><b><?=$acViernesOaxaca;?></b></td>
                                                        <td bgcolor="#d1ffaf" colspan="2"><b><?=$acSabadoOaxaca;?></b></td>        
                                                        <td bgcolor="#d1ffaf" colspan="2"><b><?=$acDomingoOaxaca;?></b></td>                                                   
                                                        <td bgcolor="#d1ffaf"></td>   
                                                    </tr>                                         
                                                </tbody>                            
                                            </table>

                                            <table class="display responsive table table-bordered  btn-hover nowrap" style="width:100%" id="tbl_usuarios_8">
                                            
                                                <!--VARIABLES INICIO DEL PROYECTO-->
                                                    <?php                                                     
                                                    $acLunesBarraMPozaRica="";  
                                                    $acLunesBarraVPozaRica="";  
                                                    $acMartesBarraMPozaRica=""; 
                                                    $acMartesBarraVPozaRica=""; 
                                                    $acMiercolesBarraMPozaRica="";
                                                    $acMiercolesBarraVPozaRica="";        
                                                    $acJuevesBarraMPozaRica="";   
                                                    $acJuevesBarraVPozaRica="";        
                                                    $acViernesBarraMPozaRica="";
                                                    $acViernesBarraVPozaRica="";        
                                                    $acSabadoBarraMPozaRica=""; 
                                                    $acSabadoBarraVPozaRica=""; 
                                                    $acDomingoBarraMPozaRica="";                                     
                                                    $acDomingoBarraVPozaRica=""; 
                                                    
                                                    $acLunesMesasMPozaRica="";
                                                    $acLunesMesasVPozaRica="";
                                                    $acMartesMesasMPozaRica="";
                                                    $acMartesMesasVPozaRica="";
                                                    $acMiercolesMesasMPozaRica="";
                                                    $acMiercolesMesasVPozaRica="";
                                                    $acJuevesMesasMPozaRica="";
                                                    $acJuevesMesasVPozaRica="";
                                                    $acViernesMesasMPozaRica="";
                                                    $acViernesMesasVPozaRica="";
                                                    $acSabadoMesasMPozaRica="";
                                                    $acSabadoMesasVPozaRica="";
                                                    $acDomingoMesasMPozaRica="";
                                                    $acDomingoMesasVPozaRica="";
                                                    
                                                                    
                                                    $acLunesCocinaMPozaRica="";
                                                    $acLunesCocinaVPozaRica="";
                                                    $acMartesCocinaMPozaRica="";
                                                    $acMartesCocinaVPozaRica="";
                                                    $acMiercolesCocinaMPozaRica="";
                                                    $acMiercolesCocinaVPozaRica="";
                                                    $acJuevesCocinaMPozaRica="";
                                                    $acJuevesCocinaVPozaRica="";
                                                    $acViernesCocinaMPozaRica="";
                                                    $acViernesCocinaVPozaRica="";
                                                    $acSabadoCocinaMPozaRica="";
                                                    $acSabadoCocinaVPozaRica="";
                                                    $acDomingoCocinaMPozaRica="";
                                                    $acDomingoCocinaVPozaRica="";
                                                                                            
                                                    $acLunesApoyoMPozaRica="";
                                                    $acLunesApoyoVPozaRica="";
                                                    $acMartesApoyoMPozaRica="";
                                                    $acMartesApoyoVPozaRica="";
                                                    $acMiercolesApoyoMPozaRica="";
                                                    $acMiercolesApoyoVPozaRica="";
                                                    $acJuevesApoyoMPozaRica="";
                                                    $acJuevesApoyoVPozaRica="";
                                                    $acViernesApoyoMPozaRica="";
                                                    $acViernesApoyoVPozaRica="";
                                                    $acSabadoApoyoMPozaRica="";
                                                    $acSabadoApoyoVPozaRica="";
                                                    $acDomingoApoyoMPozaRica="";
                                                    $acDomingoApoyoVPozaRica="";
                                                    ?>
                                            
                                                <thead>
                                                    <tr>       

                                                        <?php 
                                                            $lunes = date( 'd', strtotime( 'monday this week' ));
                                                            $martes = date( 'd', strtotime( 'tuesday this week' ));
                                                            $miercoles = date( 'd', strtotime( 'wednesday this week' ));
                                                            $jueves = date( 'd', strtotime( 'thursday this week' ));
                                                            $viernes = date( 'd', strtotime( 'friday this week' ));
                                                            $sabado = date( 'd', strtotime( 'saturday this week' ));
                                                            $domingo = date( 'd', strtotime( 'sunday this week' ));                                
                                                        ?> 
                                                            
                                                        <th>PLAZA</th>      
                                                        <th>TURNO</th>                                                     
                                                        <th colspan="2">Lun <?php echo $lunesE;?></th>  
                                                        <th colspan="2">Mar <?php echo $martesE;?></th>                     
                                                        <th colspan="2">Mier <?php echo $miercolesE;?></th>                                    
                                                        <th colspan="2">Jue <?php echo $juevesE;?></th>                                                                        
                                                        <th colspan="2">Vie <?php echo $viernesE;?></th>
                                                        <th colspan="2">Sab <?php echo $sabadoE;?></th>
                                                        <th colspan="2">Dom <?php echo $domingoE;?></th>   
                                                        <th>Totales</th>                                                                                                  
                                                    </tr>
                                                </thead>
                                                <tbody>     
                                                    
                                                    <!--FOR(S) POZARICA-->                                                    
                                                        <?php if(!empty($matutinosBarraPozaRica)):?>                           
                                                            <?php foreach ($matutinosBarraPozaRica as $matutinoBarraPozaRica):?>
                                                                <?php switch ($matutinoBarraPozaRica->dia_id) {
                                                                    case 1;
                                                                        $acLunesBarraMPozaRica=$acLunesBarraMPozaRica."".$matutinoBarraPozaRica->Nombre_Rol."<br>";                                        
                                                                    break;

                                                                    case 2;                                 
                                                                        $acMartesBarraMPozaRica=$acMartesBarraMPozaRica."".$matutinoBarraPozaRica->Nombre_Rol."<br>";       
                                                                    break;

                                                                    case 3;                                        
                                                                        $acMiercolesBarraMPozaRica=$acMiercolesBarraMPozaRica."".$matutinoBarraPozaRica->Nombre_Rol."<br>";
                                                                    break;

                                                                    case 4;                                        
                                                                        $acJuevesBarraMPozaRica=$acJuevesBarraMPozaRica."".$matutinoBarraPozaRica->Nombre_Rol."<br>";
                                                                    break;

                                                                    case 5;                                        
                                                                        $acViernesBarraMPozaRica=$acViernesBarraMPozaRica."".$matutinoBarraPozaRica->Nombre_Rol."<br>";
                                                                    break;

                                                                    case 6;   
                                                                        $acSabadoBarraMPozaRica=$acSabadoBarraMPozaRica."".$matutinoBarraPozaRica->Nombre_Rol."<br>";                                     
                                                                    break;

                                                                    case 7;                                        
                                                                        $acDomingoBarraMPozaRica=$acDomingoBarraMPozaRica."".$matutinoBarraPozaRica->Nombre_Rol."<br>";
                                                                    break;                                                                        
                                                                }?>                              
                                                            <?php endforeach;?>                                                              
                                                        <?php endif;?>  

                                                        <?php if(!empty($vespertinosBarraPozaRica)):?>                           
                                                            <?php foreach ($vespertinosBarraPozaRica as $vespertinoBarraPozaRica):?>
                                                                <?php switch ($vespertinoBarraPozaRica->dia_id) {
                                                                    case 1;
                                                                        $acLunesBarraVPozaRica=$acLunesBarraVPozaRica."".$vespertinoBarraPozaRica->Nombre_Rol."<br>";                                        
                                                                    break;

                                                                    case 2;                                 
                                                                        $acMartesBarraVPozaRica=$acMartesBarraVPozaRica."".$vespertinoBarraPozaRica->Nombre_Rol."<br>";       
                                                                    break;

                                                                    case 3;                                        
                                                                        $acMiercolesBarraVPozaRica=$acMiercolesBarraVPozaRica."".$vespertinoBarraPozaRica->Nombre_Rol."<br>";
                                                                    break;

                                                                    case 4;                                        
                                                                        $acJuevesBarraVPozaRica=$acJuevesBarraVPozaRica."".$vespertinoBarraPozaRica->Nombre_Rol."<br>";
                                                                    break;

                                                                    case 5;                                        
                                                                        $acViernesBarraVPozaRica=$acViernesBarraVPozaRica."".$vespertinoBarraPozaRica->Nombre_Rol."<br>";
                                                                    break;

                                                                    case 6;   
                                                                        $acSabadoBarraVPozaRica=$acSabadoBarraVPozaRica."".$vespertinoBarraPozaRica->Nombre_Rol."<br>";                                     
                                                                    break;

                                                                    case 7;                                        
                                                                        $acDomingoBarraVPozaRica=$acDomingoBarraVPozaRica."".$vespertinoBarraPozaRica->Nombre_Rol."<br>";
                                                                    break;                                                                        
                                                                }?>                              
                                                            <?php endforeach;?>                                                              
                                                        <?php endif;?>  
                                                
                                                        <?php if(!empty($matutinosMesasPozaRica)):?>                           
                                                            <?php foreach ($matutinosMesasPozaRica as $matutinoMesasPozaRica):?>
                                                                <?php switch ($matutinoMesasPozaRica->dia_id) {
                                                                    case 1;
                                                                        $acLunesMesasMPozaRica=$acLunesMesasMPozaRica."".$matutinoMesasPozaRica->Nombre_Rol."<br>";                                        
                                                                    break;

                                                                    case 2;                                 
                                                                        $acMartesMesasMPozaRica=$acMartesMesasMPozaRica."".$matutinoMesasPozaRica->Nombre_Rol."<br>";       
                                                                    break;

                                                                    case 3;                                        
                                                                        $acMiercolesMesasMPozaRica=$acMiercolesMesasMPozaRica."".$matutinoMesasPozaRica->Nombre_Rol."<br>";
                                                                    break;

                                                                    case 4;                                        
                                                                        $acJuevesMesasMPozaRica=$acJuevesMesasMPozaRica."".$matutinoMesasPozaRica->Nombre_Rol."<br>";
                                                                    break;

                                                                    case 5;                                        
                                                                        $acViernesMesasMPozaRica=$acViernesMesasMPozaRica."".$matutinoMesasPozaRica->Nombre_Rol."<br>";
                                                                    break;

                                                                    case 6;   
                                                                        $acSabadoMesasMPozaRica=$acSabadoMesasMPozaRica."".$matutinoMesasPozaRica->Nombre_Rol."<br>";                                     
                                                                    break;

                                                                    case 7;                                        
                                                                        $acDomingoMesasMPozaRica=$acDomingoMesasMPozaRica."".$matutinoMesasPozaRica->Nombre_Rol."<br>";
                                                                    break;                                                                        
                                                                }?>                              
                                                            <?php endforeach;?>                                                              
                                                        <?php endif;?>  

                                                        <?php if(!empty($vespertinosMesasPozaRica)):?>                           
                                                            <?php foreach ($vespertinosMesasPozaRica as $vespertinoMesasPozaRica):?>
                                                                <?php switch ($vespertinoMesasPozaRica->dia_id) {
                                                                    case 1;
                                                                        $acLunesMesasVPozaRica=$acLunesMesasVPozaRica."".$vespertinoMesasPozaRica->Nombre_Rol."<br>";                                        
                                                                    break;

                                                                    case 2;                                 
                                                                        $acMartesMesasVPozaRica=$acMartesMesasVPozaRica."".$vespertinoMesasPozaRica->Nombre_Rol."<br>";       
                                                                    break;

                                                                    case 3;                                        
                                                                        $acMiercolesMesasVPozaRica=$acMiercolesMesasVPozaRica."".$vespertinoMesasPozaRica->Nombre_Rol."<br>";
                                                                    break;

                                                                    case 4;                                        
                                                                        $acJuevesMesasVPozaRica=$acJuevesMesasVPozaRica."".$vespertinoMesasPozaRica->Nombre_Rol."<br>";
                                                                    break;

                                                                    case 5;                                        
                                                                        $acViernesMesasVPozaRica=$acViernesMesasVPozaRica."".$vespertinoMesasPozaRica->Nombre_Rol."<br>";
                                                                    break;

                                                                    case 6;   
                                                                        $acSabadoMesasVPozaRica=$acSabadoMesasVPozaRica."".$vespertinoMesasPozaRica->Nombre_Rol."<br>";                                     
                                                                    break;

                                                                    case 7;                                        
                                                                        $acDomingoMesasVPozaRica=$acDomingoMesasVPozaRica."".$vespertinoMesasPozaRica->Nombre_Rol."<br>";
                                                                    break;                                                                        
                                                                }?>                              
                                                            <?php endforeach;?>                                                              
                                                        <?php endif;?>  

                                                        <?php if(!empty($matutinosCocinaPozaRica)):?>                           
                                                            <?php foreach ($matutinosCocinaPozaRica as $matutinoCocinaPozaRica):?>
                                                                <?php switch ($matutinoCocinaPozaRica->dia_id) {
                                                                    case 1;
                                                                        $acLunesCocinaMPozaRica=$acLunesCocinaMPozaRica."".$matutinoCocinaPozaRica->Nombre_Rol."<br>";                                        
                                                                    break;

                                                                    case 2;                                 
                                                                        $acMartesCocinaMPozaRica=$acMartesCocinaMPozaRica."".$matutinoCocinaPozaRica->Nombre_Rol."<br>";       
                                                                    break;

                                                                    case 3;                                        
                                                                        $acMiercolesCocinaMPozaRica=$acMiercolesCocinaMPozaRica."".$matutinoCocinaPozaRica->Nombre_Rol."<br>";
                                                                    break;

                                                                    case 4;                                        
                                                                        $acJuevesCocinaMPozaRica=$acJuevesCocinaMPozaRica."".$matutinoCocinaPozaRica->Nombre_Rol."<br>";
                                                                    break;

                                                                    case 5;                                        
                                                                        $acViernesCocinaMPozaRica=$acViernesCocinaMPozaRica."".$matutinoCocinaPozaRica->Nombre_Rol."<br>";
                                                                    break;

                                                                    case 6;   
                                                                        $acSabadoCocinaMPozaRica=$acSabadoCocinaMPozaRica."".$matutinoCocinaPozaRica->Nombre_Rol."<br>";                                     
                                                                    break;

                                                                    case 7;                                        
                                                                        $acDomingoCocinaMPozaRica=$acDomingoCocinaMPozaRica."".$matutinoCocinaPozaRica->Nombre_Rol."<br>";
                                                                    break;                                                                        
                                                                }?>                              
                                                            <?php endforeach;?>                                                              
                                                        <?php endif;?> 

                                                        <?php if(!empty($vespertinosCocinaPozaRica)):?>                           
                                                            <?php foreach ($vespertinosCocinaPozaRica as $vespertinoCocinaPozaRica):?>
                                                                <?php switch ($vespertinoCocinaPozaRica->dia_id) {
                                                                    case 1;
                                                                        $acLunesCocinaVPozaRica=$acLunesCocinaVPozaRica."".$vespertinoCocinaPozaRica->Nombre_Rol."<br>";                                        
                                                                    break;

                                                                    case 2;                                 
                                                                        $acMartesCocinaVPozaRica=$acMartesCocinaVPozaRica."".$vespertinoCocinaPozaRica->Nombre_Rol."<br>";       
                                                                    break;

                                                                    case 3;                                        
                                                                        $acMiercolesCocinaVPozaRica=$acMiercolesCocinaVPozaRica."".$vespertinoCocinaPozaRica->Nombre_Rol."<br>";
                                                                    break;

                                                                    case 4;                                        
                                                                        $acJuevesCocinaVPozaRica=$acJuevesCocinaVPozaRica."".$vespertinoCocinaPozaRica->Nombre_Rol."<br>";
                                                                    break;

                                                                    case 5;                                        
                                                                        $acViernesCocinaVPozaRica=$acViernesCocinaVPozaRica."".$vespertinoCocinaPozaRica->Nombre_Rol."<br>";
                                                                    break;

                                                                    case 6;   
                                                                        $acSabadoCocinaVPozaRica=$acSabadoCocinaVPozaRica."".$vespertinoCocinaPozaRica->Nombre_Rol."<br>";                                     
                                                                    break;

                                                                    case 7;                                        
                                                                        $acDomingoCocinaVPozaRica=$acDomingoCocinaVPozaRica."".$vespertinoCocinaPozaRica->Nombre_Rol."<br>";
                                                                    break;                                                                        
                                                                }?>                              
                                                            <?php endforeach;?>                                                              
                                                        <?php endif;?>  

                                                        <?php if(!empty($matutinosApoyoPozaRica)):?>                           
                                                            <?php foreach ($matutinosApoyoPozaRica as $matutinoApoyoPozaRica):?>
                                                                <?php switch ($matutinoApoyoPozaRica->dia_id) {
                                                                    case 1;
                                                                        $acLunesApoyoMPozaRica=$acLunesApoyoMPozaRica."".$matutinoApoyoPozaRica->Nombre_Rol."<br>";                                        
                                                                    break;

                                                                    case 2;                                 
                                                                        $acMartesApoyoMPozaRica=$acMartesApoyoMPozaRica."".$matutinoApoyoPozaRica->Nombre_Rol."<br>";       
                                                                    break;

                                                                    case 3;                                        
                                                                        $acMiercolesApoyoMPozaRica=$acMiercolesApoyoMPozaRica."".$matutinoApoyoPozaRica->Nombre_Rol."<br>";
                                                                    break;

                                                                    case 4;                                        
                                                                        $acJuevesApoyoMPozaRica=$acJuevesApoyoMPozaRica."".$matutinoApoyoPozaRica->Nombre_Rol."<br>";
                                                                    break;

                                                                    case 5;                                        
                                                                        $acViernesApoyoMPozaRica=$acViernesApoyoMPozaRica."".$matutinoApoyoPozaRica->Nombre_Rol."<br>";
                                                                    break;

                                                                    case 6;   
                                                                        $acSabadoApoyoMPozaRica=$acSabadoApoyoMPozaRica."".$matutinoApoyoPozaRica->Nombre_Rol."<br>";                                     
                                                                    break;

                                                                    case 7;                                        
                                                                        $acDomingoApoyoMPozaRica=$acDomingoApoyoMPozaRica."".$matutinoApoyoPozaRica->Nombre_Rol."<br>";
                                                                    break;                                                                        
                                                                }?>                              
                                                            <?php endforeach;?>                                                              
                                                        <?php endif;?> 

                                                        <?php if(!empty($vespertinosApoyoPozaRica)):?>                           
                                                            <?php foreach ($vespertinosApoyoPozaRica as $vespertinoApoyoPozaRica):?>
                                                                <?php switch ($vespertinoApoyoPozaRica->dia_id) {
                                                                    case 1;
                                                                        $acLunesApoyoVPozaRica=$acLunesApoyoVPozaRica."".$vespertinoApoyoPozaRica->Nombre_Rol."<br>";                                        
                                                                    break;

                                                                    case 2;                                 
                                                                        $acMartesApoyoVPozaRica=$acMartesApoyoVPozaRica."".$vespertinoApoyoPozaRica->Nombre_Rol."<br>";       
                                                                    break;

                                                                    case 3;                                        
                                                                        $acMiercolesApoyoVPozaRica=$acMiercolesApoyoVPozaRica."".$vespertinoApoyoPozaRica->Nombre_Rol."<br>";
                                                                    break;

                                                                    case 4;                                        
                                                                        $acJuevesApoyoVPozaRica=$acJuevesApoyoVPozaRica."".$vespertinoApoyoPozaRica->Nombre_Rol."<br>";
                                                                    break;

                                                                    case 5;                                        
                                                                        $acViernesApoyoVPozaRica=$acViernesApoyoVPozaRica."".$vespertinoApoyoPozaRica->Nombre_Rol."<br>";
                                                                    break;

                                                                    case 6;   
                                                                        $acSabadoApoyoVPozaRica=$acSabadoApoyoVPozaRica."".$vespertinoApoyoPozaRica->Nombre_Rol."<br>";                                     
                                                                    break;

                                                                    case 7;                                        
                                                                        $acDomingoApoyoVPozaRica=$acDomingoApoyoVPozaRica."".$vespertinoApoyoPozaRica->Nombre_Rol."<br>";
                                                                    break;                                                                        
                                                                }?>                              
                                                            <?php endforeach;?>                                                              
                                                        <?php endif;?>
                                                    <!--FOR(S) POZARICA-->                                                                                                                                                         
                                                    <tr>
                                                        <td>PZ</td>
                                                        <td>BARRA</td>
                                                        <td rowspan="1"><?=$acLunesBarraMPozaRica;?></td>  
                                                        <td rowspan="1"><?=$acLunesBarraVPozaRica;?></td>  
                                                        <td rowspan="1"><?=$acMartesBarraMPozaRica;?></td> 
                                                        <td rowspan="1"><?=$acMartesBarraVPozaRica;?></td> 
                                                        <td rowspan="1"><?=$acMiercolesBarraMPozaRica;?></td>
                                                        <td rowspan="1"><?=$acMiercolesBarraVPozaRica;?></td>        
                                                        <td rowspan="1"><?=$acJuevesBarraMPozaRica;?></td>   
                                                        <td rowspan="1"><?=$acJuevesBarraVPozaRica;?></td>        
                                                        <td rowspan="1"><?=$acViernesBarraMPozaRica;?></td>
                                                        <td rowspan="1"><?=$acViernesBarraVPozaRica;?></td>        
                                                        <td rowspan="1"><?=$acSabadoBarraMPozaRica;?></td> 
                                                        <td rowspan="1"><?=$acSabadoBarraVPozaRica;?></td> 
                                                        <td rowspan="1"><?=$acDomingoBarraMPozaRica;?></td>                                     
                                                        <td rowspan="1"><?=$acDomingoBarraVPozaRica;?></td> 
                                                        <td bgcolor="#d1ffaf"><b><?=$acBarraPozaRica;?></b></td>                               
                                                    </tr>   

                                                    <tr>
                                                        <td>PZ</td>
                                                        <td>MESAS</td>
                                                        <td rowspan="1"><?=$acLunesMesasMPozaRica;?></td>  
                                                        <td rowspan="1"><?=$acLunesMesasVPozaRica;?></td>  
                                                        <td rowspan="1"><?=$acMartesMesasMPozaRica;?></td> 
                                                        <td rowspan="1"><?=$acMartesMesasVPozaRica;?></td> 
                                                        <td rowspan="1"><?=$acMiercolesMesasMPozaRica;?></td>
                                                        <td rowspan="1"><?=$acMiercolesMesasVPozaRica;?></td>        
                                                        <td rowspan="1"><?=$acJuevesMesasMPozaRica;?></td>   
                                                        <td rowspan="1"><?=$acJuevesMesasVPozaRica;?></td>        
                                                        <td rowspan="1"><?=$acViernesMesasMPozaRica;?></td>
                                                        <td rowspan="1"><?=$acViernesMesasVPozaRica;?></td>        
                                                        <td rowspan="1"><?=$acSabadoMesasMPozaRica;?></td> 
                                                        <td rowspan="1"><?=$acSabadoMesasVPozaRica;?></td> 
                                                        <td rowspan="1"><?=$acDomingoMesasMPozaRica;?></td>                                     
                                                        <td rowspan="1"><?=$acDomingoMesasVPozaRica;?></td>  
                                                        <td bgcolor="#d1ffaf"><b><?=$acMesasPozaRica;?></b></td>                               
                                                    </tr> 
                                                    
                                                    <tr>
                                                        <td>PZ</td>
                                                        <td>COCINA</td>
                                                        <td rowspan="1"><?=$acLunesCocinaMPozaRica;?></td>  
                                                        <td rowspan="1"><?=$acLunesCocinaVPozaRica;?></td>  
                                                        <td rowspan="1"><?=$acMartesCocinaMPozaRica;?></td> 
                                                        <td rowspan="1"><?=$acMartesCocinaVPozaRica;?></td> 
                                                        <td rowspan="1"><?=$acMiercolesCocinaMPozaRica;?></td>
                                                        <td rowspan="1"><?=$acMiercolesCocinaVPozaRica;?></td>        
                                                        <td rowspan="1"><?=$acJuevesCocinaMPozaRica;?></td>   
                                                        <td rowspan="1"><?=$acJuevesCocinaVPozaRica;?></td>        
                                                        <td rowspan="1"><?=$acViernesCocinaMPozaRica;?></td>
                                                        <td rowspan="1"><?=$acViernesCocinaVPozaRica;?></td>        
                                                        <td rowspan="1"><?=$acSabadoCocinaMPozaRica;?></td> 
                                                        <td rowspan="1"><?=$acSabadoCocinaVPozaRica;?></td> 
                                                        <td rowspan="1"><?=$acDomingoCocinaMPozaRica;?></td>                                     
                                                        <td rowspan="1"><?=$acDomingoCocinaVPozaRica;?></td>                                    
                                                        <td bgcolor="#d1ffaf"><b><?=$acCocinaPozaRica;?></b></td>                             
                                                    </tr> 

                                                    <tr>
                                                        <td>PZ</td>
                                                        <td>APOYO</td>
                                                        <td rowspan="1"><?=$acLunesApoyoMPozaRica;?></td>  
                                                        <td rowspan="1"><?=$acLunesApoyoVPozaRica;?></td>  
                                                        <td rowspan="1"><?=$acMartesApoyoMPozaRica;?></td> 
                                                        <td rowspan="1"><?=$acMartesApoyoVPozaRica;?></td> 
                                                        <td rowspan="1"><?=$acMiercolesApoyoMPozaRica;?></td>
                                                        <td rowspan="1"><?=$acMiercolesApoyoVPozaRica;?></td>        
                                                        <td rowspan="1"><?=$acJuevesApoyoMPozaRica;?></td>   
                                                        <td rowspan="1"><?=$acJuevesApoyoVPozaRica;?></td>        
                                                        <td rowspan="1"><?=$acViernesApoyoMPozaRica;?></td>
                                                        <td rowspan="1"><?=$acViernesApoyoVPozaRica;?></td>        
                                                        <td rowspan="1"><?=$acSabadoApoyoMPozaRica;?></td> 
                                                        <td rowspan="1"><?=$acSabadoApoyoVPozaRica;?></td> 
                                                        <td rowspan="1"><?=$acDomingoApoyoMPozaRica;?></td>                                     
                                                        <td rowspan="1"><?=$acDomingoApoyoVPozaRica;?></td>                                     
                                                        <td bgcolor="#d1ffaf"><b><?=$acApoyoPozaRica;?></b></td>
                                                    </tr>       

                                                    <tr>                                                        
                                                        <td bgcolor="#76c5ff"><b>Total</b></td>
                                                        <td bgcolor="#76c5ff"></td>
                                                        <td bgcolor="#d1ffaf" colspan="2"><b><?=$acLunesPozaRica;?></b></td>  
                                                        <td bgcolor="#d1ffaf" colspan="2"><b><?=$acMartesPozaRica;?></b></td>  
                                                        <td bgcolor="#d1ffaf" colspan="2"><b><?=$acMiercolesPozaRica;?></b></td> 
                                                        <td bgcolor="#d1ffaf" colspan="2"><b><?=$acJuevesPozaRica;?></b></td> 
                                                        <td bgcolor="#d1ffaf" colspan="2"><b><?=$acViernesPozaRica;?></b></td>
                                                        <td bgcolor="#d1ffaf" colspan="2"><b><?=$acSabadoPozaRica;?></b></td>        
                                                        <td bgcolor="#d1ffaf" colspan="2"><b><?=$acDomingoPozaRica;?></b></td>                                                    
                                                        <td bgcolor="#d1ffaf"></td>   
                                                    </tr>                                         

                                                </tbody>                            
                                            </table>

                                            <table class="display responsive table table-bordered  btn-hover nowrap" style="width:100%" id="tbl_usuarios_9">
                                            
                                                    <!--VARIABLES INICIO DEL PROYECTO-->
                                                    <?php                                                     
                                                        $acLunesBarraMReynosaI="";  
                                                        $acLunesBarraVReynosaI="";  
                                                        $acMartesBarraMReynosaI=""; 
                                                        $acMartesBarraVReynosaI=""; 
                                                        $acMiercolesBarraMReynosaI="";
                                                        $acMiercolesBarraVReynosaI="";        
                                                        $acJuevesBarraMReynosaI="";   
                                                        $acJuevesBarraVReynosaI="";        
                                                        $acViernesBarraMReynosaI="";
                                                        $acViernesBarraVReynosaI="";        
                                                        $acSabadoBarraMReynosaI=""; 
                                                        $acSabadoBarraVReynosaI=""; 
                                                        $acDomingoBarraMReynosaI="";                                     
                                                        $acDomingoBarraVReynosaI=""; 
                                                        
                                                        $acLunesMesasMReynosaI="";
                                                        $acLunesMesasVReynosaI="";
                                                        $acMartesMesasMReynosaI="";
                                                        $acMartesMesasVReynosaI="";
                                                        $acMiercolesMesasMReynosaI="";
                                                        $acMiercolesMesasVReynosaI="";
                                                        $acJuevesMesasMReynosaI="";
                                                        $acJuevesMesasVReynosaI="";
                                                        $acViernesMesasMReynosaI="";
                                                        $acViernesMesasVReynosaI="";
                                                        $acSabadoMesasMReynosaI="";
                                                        $acSabadoMesasVReynosaI="";
                                                        $acDomingoMesasMReynosaI="";
                                                        $acDomingoMesasVReynosaI="";
                                                        
                                                                        
                                                        $acLunesCocinaMReynosaI="";
                                                        $acLunesCocinaVReynosaI="";
                                                        $acMartesCocinaMReynosaI="";
                                                        $acMartesCocinaVReynosaI="";
                                                        $acMiercolesCocinaMReynosaI="";
                                                        $acMiercolesCocinaVReynosaI="";
                                                        $acJuevesCocinaMReynosaI="";
                                                        $acJuevesCocinaVReynosaI="";
                                                        $acViernesCocinaMReynosaI="";
                                                        $acViernesCocinaVReynosaI="";
                                                        $acSabadoCocinaMReynosaI="";
                                                        $acSabadoCocinaVReynosaI="";
                                                        $acDomingoCocinaMReynosaI="";
                                                        $acDomingoCocinaVReynosaI="";
                                                                                                
                                                        $acLunesApoyoMReynosaI="";
                                                        $acLunesApoyoVReynosaI="";
                                                        $acMartesApoyoMReynosaI="";
                                                        $acMartesApoyoVReynosaI="";
                                                        $acMiercolesApoyoMReynosaI="";
                                                        $acMiercolesApoyoVReynosaI="";
                                                        $acJuevesApoyoMReynosaI="";
                                                        $acJuevesApoyoVReynosaI="";
                                                        $acViernesApoyoMReynosaI="";
                                                        $acViernesApoyoVReynosaI="";
                                                        $acSabadoApoyoMReynosaI="";
                                                        $acSabadoApoyoVReynosaI="";
                                                        $acDomingoApoyoMReynosaI="";
                                                        $acDomingoApoyoVReynosaI="";
                                                    ?>
                                            
                                                <thead>
                                                    <tr>       

                                                        <?php 
                                                            $lunes = date( 'd', strtotime( 'monday this week' ));
                                                            $martes = date( 'd', strtotime( 'tuesday this week' ));
                                                            $miercoles = date( 'd', strtotime( 'wednesday this week' ));
                                                            $jueves = date( 'd', strtotime( 'thursday this week' ));
                                                            $viernes = date( 'd', strtotime( 'friday this week' ));
                                                            $sabado = date( 'd', strtotime( 'saturday this week' ));
                                                            $domingo = date( 'd', strtotime( 'sunday this week' ));                                
                                                        ?> 
                                                            
                                                        <th>PLAZA</th>      
                                                        <th>TURNO</th>                                                     
                                                        <th colspan="2">Lun <?php echo $lunesE;?></th>  
                                                        <th colspan="2">Mar <?php echo $martesE;?></th>                     
                                                        <th colspan="2">Mier <?php echo $miercolesE;?></th>                                    
                                                        <th colspan="2">Jue <?php echo $juevesE;?></th>                                                                        
                                                        <th colspan="2">Vie <?php echo $viernesE;?></th>
                                                        <th colspan="2">Sab <?php echo $sabadoE;?></th>
                                                        <th colspan="2">Dom <?php echo $domingoE;?></th>   
                                                        <th>Totales</th>                                                                                                  
                                                    </tr>
                                                </thead>
                                                <tbody>  
                                                <!--FOR(S) ReynosaI-->                                                    
                                                    <?php if(!empty($matutinosBarraReynosaI)):?>                           
                                                        <?php foreach ($matutinosBarraReynosaI as $matutinoBarraReynosaI):?>
                                                            <?php switch ($matutinoBarraReynosaI->dia_id) {
                                                                case 1;
                                                                    $acLunesBarraMReynosaI=$acLunesBarraMReynosaI."".$matutinoBarraReynosaI->Nombre_Rol."<br>";                                        
                                                                break;

                                                                case 2;                                 
                                                                    $acMartesBarraMReynosaI=$acMartesBarraMReynosaI."".$matutinoBarraReynosaI->Nombre_Rol."<br>";       
                                                                break;

                                                                case 3;                                        
                                                                    $acMiercolesBarraMReynosaI=$acMiercolesBarraMReynosaI."".$matutinoBarraReynosaI->Nombre_Rol."<br>";
                                                                break;

                                                                case 4;                                        
                                                                    $acJuevesBarraMReynosaI=$acJuevesBarraMReynosaI."".$matutinoBarraReynosaI->Nombre_Rol."<br>";
                                                                break;

                                                                case 5;                                        
                                                                    $acViernesBarraMReynosaI=$acViernesBarraMReynosaI."".$matutinoBarraReynosaI->Nombre_Rol."<br>";
                                                                break;

                                                                case 6;   
                                                                    $acSabadoBarraMReynosaI=$acSabadoBarraMReynosaI."".$matutinoBarraReynosaI->Nombre_Rol."<br>";                                     
                                                                break;

                                                                case 7;                                        
                                                                    $acDomingoBarraMReynosaI=$acDomingoBarraMReynosaI."".$matutinoBarraReynosaI->Nombre_Rol."<br>";
                                                                break;                                                                        
                                                            }?>                              
                                                        <?php endforeach;?>                                                              
                                                    <?php endif;?>  

                                                    <?php if(!empty($vespertinosBarraReynosaI)):?>                           
                                                        <?php foreach ($vespertinosBarraReynosaI as $vespertinoBarraReynosaI):?>
                                                            <?php switch ($vespertinoBarraReynosaI->dia_id) {
                                                                case 1;
                                                                    $acLunesBarraVReynosaI=$acLunesBarraVReynosaI."".$vespertinoBarraReynosaI->Nombre_Rol."<br>";                                        
                                                                break;

                                                                case 2;                                 
                                                                    $acMartesBarraVReynosaI=$acMartesBarraVReynosaI."".$vespertinoBarraReynosaI->Nombre_Rol."<br>";       
                                                                break;

                                                                case 3;                                        
                                                                    $acMiercolesBarraVReynosaI=$acMiercolesBarraVReynosaI."".$vespertinoBarraReynosaI->Nombre_Rol."<br>";
                                                                break;

                                                                case 4;                                        
                                                                    $acJuevesBarraVReynosaI=$acJuevesBarraVReynosaI."".$vespertinoBarraReynosaI->Nombre_Rol."<br>";
                                                                break;

                                                                case 5;                                        
                                                                    $acViernesBarraVReynosaI=$acViernesBarraVReynosaI."".$vespertinoBarraReynosaI->Nombre_Rol."<br>";
                                                                break;

                                                                case 6;   
                                                                    $acSabadoBarraVReynosaI=$acSabadoBarraVReynosaI."".$vespertinoBarraReynosaI->Nombre_Rol."<br>";                                     
                                                                break;

                                                                case 7;                                        
                                                                    $acDomingoBarraVReynosaI=$acDomingoBarraVReynosaI."".$vespertinoBarraReynosaI->Nombre_Rol."<br>";
                                                                break;                                                                        
                                                            }?>                              
                                                        <?php endforeach;?>                                                              
                                                    <?php endif;?>  

                                                    <?php if(!empty($matutinosMesasReynosaI)):?>                           
                                                        <?php foreach ($matutinosMesasReynosaI as $matutinoMesasReynosaI):?>
                                                            <?php switch ($matutinoMesasReynosaI->dia_id) {
                                                                case 1;
                                                                    $acLunesMesasMReynosaI=$acLunesMesasMReynosaI."".$matutinoMesasReynosaI->Nombre_Rol."<br>";                                        
                                                                break;

                                                                case 2;                                 
                                                                    $acMartesMesasMReynosaI=$acMartesMesasMReynosaI."".$matutinoMesasReynosaI->Nombre_Rol."<br>";       
                                                                break;

                                                                case 3;                                        
                                                                    $acMiercolesMesasMReynosaI=$acMiercolesMesasMReynosaI."".$matutinoMesasReynosaI->Nombre_Rol."<br>";
                                                                break;

                                                                case 4;                                        
                                                                    $acJuevesMesasMReynosaI=$acJuevesMesasMReynosaI."".$matutinoMesasReynosaI->Nombre_Rol."<br>";
                                                                break;

                                                                case 5;                                        
                                                                    $acViernesMesasMReynosaI=$acViernesMesasMReynosaI."".$matutinoMesasReynosaI->Nombre_Rol."<br>";
                                                                break;

                                                                case 6;   
                                                                    $acSabadoMesasMReynosaI=$acSabadoMesasMReynosaI."".$matutinoMesasReynosaI->Nombre_Rol."<br>";                                     
                                                                break;

                                                                case 7;                                        
                                                                    $acDomingoMesasMReynosaI=$acDomingoMesasMReynosaI."".$matutinoMesasReynosaI->Nombre_Rol."<br>";
                                                                break;                                                                        
                                                            }?>                              
                                                        <?php endforeach;?>                                                              
                                                    <?php endif;?>  

                                                    <?php if(!empty($vespertinosMesasReynosaI)):?>                           
                                                        <?php foreach ($vespertinosMesasReynosaI as $vespertinoMesasReynosaI):?>
                                                            <?php switch ($vespertinoMesasReynosaI->dia_id) {
                                                                case 1;
                                                                    $acLunesMesasVReynosaI=$acLunesMesasVReynosaI."".$vespertinoMesasReynosaI->Nombre_Rol."<br>";                                        
                                                                break;

                                                                case 2;                                 
                                                                    $acMartesMesasVReynosaI=$acMartesMesasVReynosaI."".$vespertinoMesasReynosaI->Nombre_Rol."<br>";       
                                                                break;

                                                                case 3;                                        
                                                                    $acMiercolesMesasVReynosaI=$acMiercolesMesasVReynosaI."".$vespertinoMesasReynosaI->Nombre_Rol."<br>";
                                                                break;

                                                                case 4;                                        
                                                                    $acJuevesMesasVReynosaI=$acJuevesMesasVReynosaI."".$vespertinoMesasReynosaI->Nombre_Rol."<br>";
                                                                break;

                                                                case 5;                                        
                                                                    $acViernesMesasVReynosaI=$acViernesMesasVReynosaI."".$vespertinoMesasReynosaI->Nombre_Rol."<br>";
                                                                break;

                                                                case 6;   
                                                                    $acSabadoMesasVReynosaI=$acSabadoMesasVReynosaI."".$vespertinoMesasReynosaI->Nombre_Rol."<br>";                                     
                                                                break;

                                                                case 7;                                        
                                                                    $acDomingoMesasVReynosaI=$acDomingoMesasVReynosaI."".$vespertinoMesasReynosaI->Nombre_Rol."<br>";
                                                                break;                                                                        
                                                            }?>                              
                                                        <?php endforeach;?>                                                              
                                                    <?php endif;?>  

                                                    <?php if(!empty($matutinosCocinaReynosaI)):?>                           
                                                        <?php foreach ($matutinosCocinaReynosaI as $matutinoCocinaReynosaI):?>
                                                            <?php switch ($matutinoCocinaReynosaI->dia_id) {
                                                                case 1;
                                                                    $acLunesCocinaMReynosaI=$acLunesCocinaMReynosaI."".$matutinoCocinaReynosaI->Nombre_Rol."<br>";                                        
                                                                break;

                                                                case 2;                                 
                                                                    $acMartesCocinaMReynosaI=$acMartesCocinaMReynosaI."".$matutinoCocinaReynosaI->Nombre_Rol."<br>";       
                                                                break;

                                                                case 3;                                        
                                                                    $acMiercolesCocinaMReynosaI=$acMiercolesCocinaMReynosaI."".$matutinoCocinaReynosaI->Nombre_Rol."<br>";
                                                                break;

                                                                case 4;                                        
                                                                    $acJuevesCocinaMReynosaI=$acJuevesCocinaMReynosaI."".$matutinoCocinaReynosaI->Nombre_Rol."<br>";
                                                                break;

                                                                case 5;                                        
                                                                    $acViernesCocinaMReynosaI=$acViernesCocinaMReynosaI."".$matutinoCocinaReynosaI->Nombre_Rol."<br>";
                                                                break;

                                                                case 6;   
                                                                    $acSabadoCocinaMReynosaI=$acSabadoCocinaMReynosaI."".$matutinoCocinaReynosaI->Nombre_Rol."<br>";                                     
                                                                break;

                                                                case 7;                                        
                                                                    $acDomingoCocinaMReynosaI=$acDomingoCocinaMReynosaI."".$matutinoCocinaReynosaI->Nombre_Rol."<br>";
                                                                break;                                                                        
                                                            }?>                              
                                                        <?php endforeach;?>                                                              
                                                    <?php endif;?> 

                                                    <?php if(!empty($vespertinosCocinaReynosaI)):?>                           
                                                        <?php foreach ($vespertinosCocinaReynosaI as $vespertinoCocinaReynosaI):?>
                                                            <?php switch ($vespertinoCocinaReynosaI->dia_id) {
                                                                case 1;
                                                                    $acLunesCocinaVReynosaI=$acLunesCocinaVReynosaI."".$vespertinoCocinaReynosaI->Nombre_Rol."<br>";                                        
                                                                break;

                                                                case 2;                                 
                                                                    $acMartesCocinaVReynosaI=$acMartesCocinaVReynosaI."".$vespertinoCocinaReynosaI->Nombre_Rol."<br>";       
                                                                break;

                                                                case 3;                                        
                                                                    $acMiercolesCocinaVReynosaI=$acMiercolesCocinaVReynosaI."".$vespertinoCocinaReynosaI->Nombre_Rol."<br>";
                                                                break;

                                                                case 4;                                        
                                                                    $acJuevesCocinaVReynosaI=$acJuevesCocinaVReynosaI."".$vespertinoCocinaReynosaI->Nombre_Rol."<br>";
                                                                break;

                                                                case 5;                                        
                                                                    $acViernesCocinaVReynosaI=$acViernesCocinaVReynosaI."".$vespertinoCocinaReynosaI->Nombre_Rol."<br>";
                                                                break;

                                                                case 6;   
                                                                    $acSabadoCocinaVReynosaI=$acSabadoCocinaVReynosaI."".$vespertinoCocinaReynosaI->Nombre_Rol."<br>";                                     
                                                                break;

                                                                case 7;                                        
                                                                    $acDomingoCocinaVReynosaI=$acDomingoCocinaVReynosaI."".$vespertinoCocinaReynosaI->Nombre_Rol."<br>";
                                                                break;                                                                        
                                                            }?>                              
                                                        <?php endforeach;?>                                                              
                                                    <?php endif;?>  

                                                    <?php if(!empty($matutinosApoyoReynosaI)):?>                           
                                                        <?php foreach ($matutinosApoyoReynosaI as $matutinoApoyoReynosaI):?>
                                                            <?php switch ($matutinoApoyoReynosaI->dia_id) {
                                                                case 1;
                                                                    $acLunesApoyoMReynosaI=$acLunesApoyoMReynosaI."".$matutinoApoyoReynosaI->Nombre_Rol."<br>";                                        
                                                                break;

                                                                case 2;                                 
                                                                    $acMartesApoyoMReynosaI=$acMartesApoyoMReynosaI."".$matutinoApoyoReynosaI->Nombre_Rol."<br>";       
                                                                break;

                                                                case 3;                                        
                                                                    $acMiercolesApoyoMReynosaI=$acMiercolesApoyoMReynosaI."".$matutinoApoyoReynosaI->Nombre_Rol."<br>";
                                                                break;

                                                                case 4;                                        
                                                                    $acJuevesApoyoMReynosaI=$acJuevesApoyoMReynosaI."".$matutinoApoyoReynosaI->Nombre_Rol."<br>";
                                                                break;

                                                                case 5;                                        
                                                                    $acViernesApoyoMReynosaI=$acViernesApoyoMReynosaI."".$matutinoApoyoReynosaI->Nombre_Rol."<br>";
                                                                break;

                                                                case 6;   
                                                                    $acSabadoApoyoMReynosaI=$acSabadoApoyoMReynosaI."".$matutinoApoyoReynosaI->Nombre_Rol."<br>";                                     
                                                                break;

                                                                case 7;                                        
                                                                    $acDomingoApoyoMReynosaI=$acDomingoApoyoMReynosaI."".$matutinoApoyoReynosaI->Nombre_Rol."<br>";
                                                                break;                                                                        
                                                            }?>                              
                                                        <?php endforeach;?>                                                              
                                                    <?php endif;?> 

                                                    <?php if(!empty($vespertinosApoyoReynosaI)):?>                           
                                                        <?php foreach ($vespertinosApoyoReynosaI as $vespertinoApoyoReynosaI):?>
                                                            <?php switch ($vespertinoApoyoReynosaI->dia_id) {
                                                                case 1;
                                                                    $acLunesApoyoVReynosaI=$acLunesApoyoVReynosaI."".$vespertinoApoyoReynosaI->Nombre_Rol."<br>";                                        
                                                                break;

                                                                case 2;                                 
                                                                    $acMartesApoyoVReynosaI=$acMartesApoyoVReynosaI."".$vespertinoApoyoReynosaI->Nombre_Rol."<br>";       
                                                                break;

                                                                case 3;                                        
                                                                    $acMiercolesApoyoVReynosaI=$acMiercolesApoyoVReynosaI."".$vespertinoApoyoReynosaI->Nombre_Rol."<br>";
                                                                break;

                                                                case 4;                                        
                                                                    $acJuevesApoyoVReynosaI=$acJuevesApoyoVReynosaI."".$vespertinoApoyoReynosaI->Nombre_Rol."<br>";
                                                                break;

                                                                case 5;                                        
                                                                    $acViernesApoyoVReynosaI=$acViernesApoyoVReynosaI."".$vespertinoApoyoReynosaI->Nombre_Rol."<br>";
                                                                break;

                                                                case 6;   
                                                                    $acSabadoApoyoVReynosaI=$acSabadoApoyoVReynosaI."".$vespertinoApoyoReynosaI->Nombre_Rol."<br>";                                     
                                                                break;

                                                                case 7;                                        
                                                                    $acDomingoApoyoVReynosaI=$acDomingoApoyoVReynosaI."".$vespertinoApoyoReynosaI->Nombre_Rol."<br>";
                                                                break;                                                                        
                                                            }?>                              
                                                        <?php endforeach;?>                                                              
                                                    <?php endif;?>
                                                <!--FOR(S) ReynosaI-->                                                                                                                                                                                                        
                                                    <tr>
                                                        <td>RI</td>
                                                        <td>BARRA</td>
                                                        <td rowspan="1"><?=$acLunesBarraMReynosaI;?></td>  
                                                        <td rowspan="1"><?=$acLunesBarraVReynosaI;?></td>  
                                                        <td rowspan="1"><?=$acMartesBarraMReynosaI;?></td> 
                                                        <td rowspan="1"><?=$acMartesBarraVReynosaI;?></td> 
                                                        <td rowspan="1"><?=$acMiercolesBarraMReynosaI;?></td>
                                                        <td rowspan="1"><?=$acMiercolesBarraVReynosaI;?></td>        
                                                        <td rowspan="1"><?=$acJuevesBarraMReynosaI;?></td>   
                                                        <td rowspan="1"><?=$acJuevesBarraVReynosaI;?></td>        
                                                        <td rowspan="1"><?=$acViernesBarraMReynosaI;?></td>
                                                        <td rowspan="1"><?=$acViernesBarraVReynosaI;?></td>        
                                                        <td rowspan="1"><?=$acSabadoBarraMReynosaI;?></td> 
                                                        <td rowspan="1"><?=$acSabadoBarraVReynosaI;?></td> 
                                                        <td rowspan="1"><?=$acDomingoBarraMReynosaI;?></td>                                     
                                                        <td rowspan="1"><?=$acDomingoBarraVReynosaI;?></td> 
                                                        <td bgcolor="#d1ffaf"><b><?=$acBarraReynosaI;?></b></td>                                   
                                                    </tr>   

                                                    <tr>
                                                        <td>RI</td>
                                                        <td>MESAS</td>
                                                        <td rowspan="1"><?=$acLunesMesasMReynosaI;?></td>  
                                                        <td rowspan="1"><?=$acLunesMesasVReynosaI;?></td>  
                                                        <td rowspan="1"><?=$acMartesMesasMReynosaI;?></td> 
                                                        <td rowspan="1"><?=$acMartesMesasVReynosaI;?></td> 
                                                        <td rowspan="1"><?=$acMiercolesMesasMReynosaI;?></td>
                                                        <td rowspan="1"><?=$acMiercolesMesasVReynosaI;?></td>        
                                                        <td rowspan="1"><?=$acJuevesMesasMReynosaI;?></td>   
                                                        <td rowspan="1"><?=$acJuevesMesasVReynosaI;?></td>        
                                                        <td rowspan="1"><?=$acViernesMesasMReynosaI;?></td>
                                                        <td rowspan="1"><?=$acViernesMesasVReynosaI;?></td>        
                                                        <td rowspan="1"><?=$acSabadoMesasMReynosaI;?></td> 
                                                        <td rowspan="1"><?=$acSabadoMesasVReynosaI;?></td> 
                                                        <td rowspan="1"><?=$acDomingoMesasMReynosaI;?></td>                                     
                                                        <td rowspan="1"><?=$acDomingoMesasVReynosaI;?></td>  
                                                        <td bgcolor="#d1ffaf"><b><?=$acMesasReynosaI;?></b></td>                                                                      
                                                    </tr> 
                                                    
                                                    <tr>
                                                        <td>RI</td>
                                                        <td>COCINA</td>
                                                        <td rowspan="1"><?=$acLunesCocinaMReynosaI;?></td>  
                                                        <td rowspan="1"><?=$acLunesCocinaVReynosaI;?></td>  
                                                        <td rowspan="1"><?=$acMartesCocinaMReynosaI;?></td> 
                                                        <td rowspan="1"><?=$acMartesCocinaVReynosaI;?></td> 
                                                        <td rowspan="1"><?=$acMiercolesCocinaMReynosaI;?></td>
                                                        <td rowspan="1"><?=$acMiercolesCocinaVReynosaI;?></td>        
                                                        <td rowspan="1"><?=$acJuevesCocinaMReynosaI;?></td>   
                                                        <td rowspan="1"><?=$acJuevesCocinaVReynosaI;?></td>        
                                                        <td rowspan="1"><?=$acViernesCocinaMReynosaI;?></td>
                                                        <td rowspan="1"><?=$acViernesCocinaVReynosaI;?></td>        
                                                        <td rowspan="1"><?=$acSabadoCocinaMReynosaI;?></td> 
                                                        <td rowspan="1"><?=$acSabadoCocinaVReynosaI;?></td> 
                                                        <td rowspan="1"><?=$acDomingoCocinaMReynosaI;?></td>                                     
                                                        <td rowspan="1"><?=$acDomingoCocinaVReynosaI;?></td>                                    
                                                        <td bgcolor="#d1ffaf"><b><?=$acCocinaReynosaI;?></b></td>                                    
                                                    </tr> 

                                                    <tr>
                                                        <td>RI</td>
                                                        <td>APOYO</td>
                                                        <td rowspan="1"><?=$acLunesApoyoMReynosaI;?></td>  
                                                        <td rowspan="1"><?=$acLunesApoyoVReynosaI;?></td>  
                                                        <td rowspan="1"><?=$acMartesApoyoMReynosaI;?></td> 
                                                        <td rowspan="1"><?=$acMartesApoyoVReynosaI;?></td> 
                                                        <td rowspan="1"><?=$acMiercolesApoyoMReynosaI;?></td>
                                                        <td rowspan="1"><?=$acMiercolesApoyoVReynosaI;?></td>        
                                                        <td rowspan="1"><?=$acJuevesApoyoMReynosaI;?></td>   
                                                        <td rowspan="1"><?=$acJuevesApoyoVReynosaI;?></td>        
                                                        <td rowspan="1"><?=$acViernesApoyoMReynosaI;?></td>
                                                        <td rowspan="1"><?=$acViernesApoyoVReynosaI;?></td>        
                                                        <td rowspan="1"><?=$acSabadoApoyoMReynosaI;?></td> 
                                                        <td rowspan="1"><?=$acSabadoApoyoVReynosaI;?></td> 
                                                        <td rowspan="1"><?=$acDomingoApoyoMReynosaI;?></td>                                     
                                                        <td rowspan="1"><?=$acDomingoApoyoVReynosaI;?></td>                                     
                                                        <td bgcolor="#d1ffaf"><b><?=$acApoyoReynosaI;?></b></td>                                  
                                                    </tr>       

                                                    <tr>                                                        
                                                        <td bgcolor="#76c5ff"><b>TOTALES</b></td>
                                                        <td bgcolor="#76c5ff"></td>
                                                        <td bgcolor="#d1ffaf" colspan="2"><b><?=$acLunesReynosaI;?></b></td>  
                                                        <td bgcolor="#d1ffaf" colspan="2"><b><?=$acMartesReynosaI;?></b></td>  
                                                        <td bgcolor="#d1ffaf" colspan="2"><b><?=$acMiercolesReynosaI;?></b></td> 
                                                        <td bgcolor="#d1ffaf" colspan="2"><b><?=$acJuevesReynosaI;?></b></td> 
                                                        <td bgcolor="#d1ffaf" colspan="2"><b><?=$acViernesReynosaI;?></b></td>
                                                        <td bgcolor="#d1ffaf" colspan="2"><b><?=$acSabadoReynosaI;?></b></td>        
                                                        <td bgcolor="#d1ffaf" colspan="2"><b><?=$acDomingoReynosaI;?></b></td>                                                    
                                                        <td bgcolor="#d1ffaf"></td>   
                                                    </tr>                                         

                                                </tbody>                            
                                            </table>

                                            <table class="display responsive table table-bordered  btn-hover nowrap" style="width:100%" id="tbl_usuarios_10">
                                            
                                                <!--VARIABLES INICIO DEL PROYECTO-->
                                                    <?php                                                     
                                                    $acLunesBarraMReynosaII="";  
                                                    $acLunesBarraVReynosaII="";  
                                                    $acMartesBarraMReynosaII=""; 
                                                    $acMartesBarraVReynosaII=""; 
                                                    $acMiercolesBarraMReynosaII="";
                                                    $acMiercolesBarraVReynosaII="";        
                                                    $acJuevesBarraMReynosaII="";   
                                                    $acJuevesBarraVReynosaII="";        
                                                    $acViernesBarraMReynosaII="";
                                                    $acViernesBarraVReynosaII="";        
                                                    $acSabadoBarraMReynosaII=""; 
                                                    $acSabadoBarraVReynosaII=""; 
                                                    $acDomingoBarraMReynosaII="";                                     
                                                    $acDomingoBarraVReynosaII=""; 
                                                    
                                                    $acLunesMesasMReynosaII="";
                                                    $acLunesMesasVReynosaII="";
                                                    $acMartesMesasMReynosaII="";
                                                    $acMartesMesasVReynosaII="";
                                                    $acMiercolesMesasMReynosaII="";
                                                    $acMiercolesMesasVReynosaII="";
                                                    $acJuevesMesasMReynosaII="";
                                                    $acJuevesMesasVReynosaII="";
                                                    $acViernesMesasMReynosaII="";
                                                    $acViernesMesasVReynosaII="";
                                                    $acSabadoMesasMReynosaII="";
                                                    $acSabadoMesasVReynosaII="";
                                                    $acDomingoMesasMReynosaII="";
                                                    $acDomingoMesasVReynosaII="";
                                                    
                                                                    
                                                    $acLunesCocinaMReynosaII="";
                                                    $acLunesCocinaVReynosaII="";
                                                    $acMartesCocinaMReynosaII="";
                                                    $acMartesCocinaVReynosaII="";
                                                    $acMiercolesCocinaMReynosaII="";
                                                    $acMiercolesCocinaVReynosaII="";
                                                    $acJuevesCocinaMReynosaII="";
                                                    $acJuevesCocinaVReynosaII="";
                                                    $acViernesCocinaMReynosaII="";
                                                    $acViernesCocinaVReynosaII="";
                                                    $acSabadoCocinaMReynosaII="";
                                                    $acSabadoCocinaVReynosaII="";
                                                    $acDomingoCocinaMReynosaII="";
                                                    $acDomingoCocinaVReynosaII="";
                                                                                            
                                                    $acLunesApoyoMReynosaII="";
                                                    $acLunesApoyoVReynosaII="";
                                                    $acMartesApoyoMReynosaII="";
                                                    $acMartesApoyoVReynosaII="";
                                                    $acMiercolesApoyoMReynosaII="";
                                                    $acMiercolesApoyoVReynosaII="";
                                                    $acJuevesApoyoMReynosaII="";
                                                    $acJuevesApoyoVReynosaII="";
                                                    $acViernesApoyoMReynosaII="";
                                                    $acViernesApoyoVReynosaII="";
                                                    $acSabadoApoyoMReynosaII="";
                                                    $acSabadoApoyoVReynosaII="";
                                                    $acDomingoApoyoMReynosaII="";
                                                    $acDomingoApoyoVReynosaII="";
                                                    ?>
                                                
                                        
                                                <thead>
                                                    <tr>       

                                                        <?php 
                                                            $lunes = date( 'd', strtotime( 'monday this week' ));
                                                            $martes = date( 'd', strtotime( 'tuesday this week' ));
                                                            $miercoles = date( 'd', strtotime( 'wednesday this week' ));
                                                            $jueves = date( 'd', strtotime( 'thursday this week' ));
                                                            $viernes = date( 'd', strtotime( 'friday this week' ));
                                                            $sabado = date( 'd', strtotime( 'saturday this week' ));
                                                            $domingo = date( 'd', strtotime( 'sunday this week' ));                                
                                                        ?> 
                                                            
                                                        <th>PLAZA</th>      
                                                        <th>TURNO</th>                                                     
                                                        <th colspan="2">Lun <?php echo $lunesE;?></th>  
                                                        <th colspan="2">Mar <?php echo $martesE;?></th>                     
                                                        <th colspan="2">Mier <?php echo $miercolesE;?></th>                                    
                                                        <th colspan="2">Jue <?php echo $juevesE;?></th>                                                                        
                                                        <th colspan="2">Vie <?php echo $viernesE;?></th>
                                                        <th colspan="2">Sab <?php echo $sabadoE;?></th>
                                                        <th colspan="2">Dom <?php echo $domingoE;?></th>   
                                                        <th>Totales</th>                                                                                                  
                                                    </tr>
                                                </thead>
                                                <tbody>     
                                                <!--FOR(S) ReynosaII-->                                                    
                                                    <?php if(!empty($matutinosBarraReynosaII)):?>                           
                                                        <?php foreach ($matutinosBarraReynosaII as $matutinoBarraReynosaII):?>
                                                            <?php switch ($matutinoBarraReynosaII->dia_id) {
                                                                case 1;
                                                                    $acLunesBarraMReynosaII=$acLunesBarraMReynosaII."".$matutinoBarraReynosaII->Nombre_Rol."<br>";                                        
                                                                break;

                                                                case 2;                                 
                                                                    $acMartesBarraMReynosaII=$acMartesBarraMReynosaII."".$matutinoBarraReynosaII->Nombre_Rol."<br>";       
                                                                break;

                                                                case 3;                                        
                                                                    $acMiercolesBarraMReynosaII=$acMiercolesBarraMReynosaII."".$matutinoBarraReynosaII->Nombre_Rol."<br>";
                                                                break;

                                                                case 4;                                        
                                                                    $acJuevesBarraMReynosaII=$acJuevesBarraMReynosaII."".$matutinoBarraReynosaII->Nombre_Rol."<br>";
                                                                break;

                                                                case 5;                                        
                                                                    $acViernesBarraMReynosaII=$acViernesBarraMReynosaII."".$matutinoBarraReynosaII->Nombre_Rol."<br>";
                                                                break;

                                                                case 6;   
                                                                    $acSabadoBarraMReynosaII=$acSabadoBarraMReynosaII."".$matutinoBarraReynosaII->Nombre_Rol."<br>";                                     
                                                                break;

                                                                case 7;                                        
                                                                    $acDomingoBarraMReynosaII=$acDomingoBarraMReynosaII."".$matutinoBarraReynosaII->Nombre_Rol."<br>";
                                                                break;                                                                        
                                                            }?>                              
                                                        <?php endforeach;?>                                                              
                                                    <?php endif;?>  

                                                    <?php if(!empty($vespertinosBarraReynosaII)):?>                           
                                                        <?php foreach ($vespertinosBarraReynosaII as $vespertinoBarraReynosaII):?>
                                                            <?php switch ($vespertinoBarraReynosaII->dia_id) {
                                                                case 1;
                                                                    $acLunesBarraVReynosaII=$acLunesBarraVReynosaII."".$vespertinoBarraReynosaII->Nombre_Rol."<br>";                                        
                                                                break;

                                                                case 2;                                 
                                                                    $acMartesBarraVReynosaII=$acMartesBarraVReynosaII."".$vespertinoBarraReynosaII->Nombre_Rol."<br>";       
                                                                break;

                                                                case 3;                                        
                                                                    $acMiercolesBarraVReynosaII=$acMiercolesBarraVReynosaII."".$vespertinoBarraReynosaII->Nombre_Rol."<br>";
                                                                break;

                                                                case 4;                                        
                                                                    $acJuevesBarraVReynosaII=$acJuevesBarraVReynosaII."".$vespertinoBarraReynosaII->Nombre_Rol."<br>";
                                                                break;

                                                                case 5;                                        
                                                                    $acViernesBarraVReynosaII=$acViernesBarraVReynosaII."".$vespertinoBarraReynosaII->Nombre_Rol."<br>";
                                                                break;

                                                                case 6;   
                                                                    $acSabadoBarraVReynosaII=$acSabadoBarraVReynosaII."".$vespertinoBarraReynosaII->Nombre_Rol."<br>";                                     
                                                                break;

                                                                case 7;                                        
                                                                    $acDomingoBarraVReynosaII=$acDomingoBarraVReynosaII."".$vespertinoBarraReynosaII->Nombre_Rol."<br>";
                                                                break;                                                                        
                                                            }?>                              
                                                        <?php endforeach;?>                                                              
                                                    <?php endif;?>  

                                                    <?php if(!empty($matutinosMesasReynosaII)):?>                           
                                                        <?php foreach ($matutinosMesasReynosaII as $matutinoMesasReynosaII):?>
                                                            <?php switch ($matutinoMesasReynosaII->dia_id) {
                                                                case 1;
                                                                    $acLunesMesasMReynosaII=$acLunesMesasMReynosaII."".$matutinoMesasReynosaII->Nombre_Rol."<br>";                                        
                                                                break;

                                                                case 2;                                 
                                                                    $acMartesMesasMReynosaII=$acMartesMesasMReynosaII."".$matutinoMesasReynosaII->Nombre_Rol."<br>";       
                                                                break;

                                                                case 3;                                        
                                                                    $acMiercolesMesasMReynosaII=$acMiercolesMesasMReynosaII."".$matutinoMesasReynosaII->Nombre_Rol."<br>";
                                                                break;

                                                                case 4;                                        
                                                                    $acJuevesMesasMReynosaII=$acJuevesMesasMReynosaII."".$matutinoMesasReynosaII->Nombre_Rol."<br>";
                                                                break;

                                                                case 5;                                        
                                                                    $acViernesMesasMReynosaII=$acViernesMesasMReynosaII."".$matutinoMesasReynosaII->Nombre_Rol."<br>";
                                                                break;

                                                                case 6;   
                                                                    $acSabadoMesasMReynosaII=$acSabadoMesasMReynosaII."".$matutinoMesasReynosaII->Nombre_Rol."<br>";                                     
                                                                break;

                                                                case 7;                                        
                                                                    $acDomingoMesasMReynosaII=$acDomingoMesasMReynosaII."".$matutinoMesasReynosaII->Nombre_Rol."<br>";
                                                                break;                                                                        
                                                            }?>                              
                                                        <?php endforeach;?>                                                              
                                                    <?php endif;?>  

                                                    <?php if(!empty($vespertinosMesasReynosaII)):?>                           
                                                        <?php foreach ($vespertinosMesasReynosaII as $vespertinoMesasReynosaII):?>
                                                            <?php switch ($vespertinoMesasReynosaII->dia_id) {
                                                                case 1;
                                                                    $acLunesMesasVReynosaII=$acLunesMesasVReynosaII."".$vespertinoMesasReynosaII->Nombre_Rol."<br>";                                        
                                                                break;

                                                                case 2;                                 
                                                                    $acMartesMesasVReynosaII=$acMartesMesasVReynosaII."".$vespertinoMesasReynosaII->Nombre_Rol."<br>";       
                                                                break;

                                                                case 3;                                        
                                                                    $acMiercolesMesasVReynosaII=$acMiercolesMesasVReynosaII."".$vespertinoMesasReynosaII->Nombre_Rol."<br>";
                                                                break;

                                                                case 4;                                        
                                                                    $acJuevesMesasVReynosaII=$acJuevesMesasVReynosaII."".$vespertinoMesasReynosaII->Nombre_Rol."<br>";
                                                                break;

                                                                case 5;                                        
                                                                    $acViernesMesasVReynosaII=$acViernesMesasVReynosaII."".$vespertinoMesasReynosaII->Nombre_Rol."<br>";
                                                                break;

                                                                case 6;   
                                                                    $acSabadoMesasVReynosaII=$acSabadoMesasVReynosaII."".$vespertinoMesasReynosaII->Nombre_Rol."<br>";                                     
                                                                break;

                                                                case 7;                                        
                                                                    $acDomingoMesasVReynosaII=$acDomingoMesasVReynosaII."".$vespertinoMesasReynosaII->Nombre_Rol."<br>";
                                                                break;                                                                        
                                                            }?>                              
                                                        <?php endforeach;?>                                                              
                                                    <?php endif;?>  

                                                    <?php if(!empty($matutinosCocinaReynosaII)):?>                           
                                                        <?php foreach ($matutinosCocinaReynosaII as $matutinoCocinaReynosaII):?>
                                                            <?php switch ($matutinoCocinaReynosaII->dia_id) {
                                                                case 1;
                                                                    $acLunesCocinaMReynosaII=$acLunesCocinaMReynosaII."".$matutinoCocinaReynosaII->Nombre_Rol."<br>";                                        
                                                                break;

                                                                case 2;                                 
                                                                    $acMartesCocinaMReynosaII=$acMartesCocinaMReynosaII."".$matutinoCocinaReynosaII->Nombre_Rol."<br>";       
                                                                break;

                                                                case 3;                                        
                                                                    $acMiercolesCocinaMReynosaII=$acMiercolesCocinaMReynosaII."".$matutinoCocinaReynosaII->Nombre_Rol."<br>";
                                                                break;

                                                                case 4;                                        
                                                                    $acJuevesCocinaMReynosaII=$acJuevesCocinaMReynosaII."".$matutinoCocinaReynosaII->Nombre_Rol."<br>";
                                                                break;

                                                                case 5;                                        
                                                                    $acViernesCocinaMReynosaII=$acViernesCocinaMReynosaII."".$matutinoCocinaReynosaII->Nombre_Rol."<br>";
                                                                break;

                                                                case 6;   
                                                                    $acSabadoCocinaMReynosaII=$acSabadoCocinaMReynosaII."".$matutinoCocinaReynosaII->Nombre_Rol."<br>";                                     
                                                                break;

                                                                case 7;                                        
                                                                    $acDomingoCocinaMReynosaII=$acDomingoCocinaMReynosaII."".$matutinoCocinaReynosaII->Nombre_Rol."<br>";
                                                                break;                                                                        
                                                            }?>                              
                                                        <?php endforeach;?>                                                              
                                                    <?php endif;?> 

                                                    <?php if(!empty($vespertinosCocinaReynosaII)):?>                           
                                                        <?php foreach ($vespertinosCocinaReynosaII as $vespertinoCocinaReynosaII):?>
                                                            <?php switch ($vespertinoCocinaReynosaII->dia_id) {
                                                                case 1;
                                                                    $acLunesCocinaVReynosaII=$acLunesCocinaVReynosaII."".$vespertinoCocinaReynosaII->Nombre_Rol."<br>";                                        
                                                                break;

                                                                case 2;                                 
                                                                    $acMartesCocinaVReynosaII=$acMartesCocinaVReynosaII."".$vespertinoCocinaReynosaII->Nombre_Rol."<br>";       
                                                                break;

                                                                case 3;                                        
                                                                    $acMiercolesCocinaVReynosaII=$acMiercolesCocinaVReynosaII."".$vespertinoCocinaReynosaII->Nombre_Rol."<br>";
                                                                break;

                                                                case 4;                                        
                                                                    $acJuevesCocinaVReynosaII=$acJuevesCocinaVReynosaII."".$vespertinoCocinaReynosaII->Nombre_Rol."<br>";
                                                                break;

                                                                case 5;                                        
                                                                    $acViernesCocinaVReynosaII=$acViernesCocinaVReynosaII."".$vespertinoCocinaReynosaII->Nombre_Rol."<br>";
                                                                break;

                                                                case 6;   
                                                                    $acSabadoCocinaVReynosaII=$acSabadoCocinaVReynosaII."".$vespertinoCocinaReynosaII->Nombre_Rol."<br>";                                     
                                                                break;

                                                                case 7;                                        
                                                                    $acDomingoCocinaVReynosaII=$acDomingoCocinaVReynosaII."".$vespertinoCocinaReynosaII->Nombre_Rol."<br>";
                                                                break;                                                                        
                                                            }?>                              
                                                        <?php endforeach;?>                                                              
                                                    <?php endif;?>  

                                                    <?php if(!empty($matutinosApoyoReynosaII)):?>                           
                                                        <?php foreach ($matutinosApoyoReynosaII as $matutinoApoyoReynosaII):?>
                                                            <?php switch ($matutinoApoyoReynosaII->dia_id) {
                                                                case 1;
                                                                    $acLunesApoyoMReynosaII=$acLunesApoyoMReynosaII."".$matutinoApoyoReynosaII->Nombre_Rol."<br>";                                        
                                                                break;

                                                                case 2;                                 
                                                                    $acMartesApoyoMReynosaII=$acMartesApoyoMReynosaII."".$matutinoApoyoReynosaII->Nombre_Rol."<br>";       
                                                                break;

                                                                case 3;                                        
                                                                    $acMiercolesApoyoMReynosaII=$acMiercolesApoyoMReynosaII."".$matutinoApoyoReynosaII->Nombre_Rol."<br>";
                                                                break;

                                                                case 4;                                        
                                                                    $acJuevesApoyoMReynosaII=$acJuevesApoyoMReynosaII."".$matutinoApoyoReynosaII->Nombre_Rol."<br>";
                                                                break;

                                                                case 5;                                        
                                                                    $acViernesApoyoMReynosaII=$acViernesApoyoMReynosaII."".$matutinoApoyoReynosaII->Nombre_Rol."<br>";
                                                                break;

                                                                case 6;   
                                                                    $acSabadoApoyoMReynosaII=$acSabadoApoyoMReynosaII."".$matutinoApoyoReynosaII->Nombre_Rol."<br>";                                     
                                                                break;

                                                                case 7;                                        
                                                                    $acDomingoApoyoMReynosaII=$acDomingoApoyoMReynosaII."".$matutinoApoyoReynosaII->Nombre_Rol."<br>";
                                                                break;                                                                        
                                                            }?>                              
                                                        <?php endforeach;?>                                                              
                                                    <?php endif;?> 

                                                    <?php if(!empty($vespertinosApoyoReynosaII)):?>                           
                                                        <?php foreach ($vespertinosApoyoReynosaII as $vespertinoApoyoReynosaII):?>
                                                            <?php switch ($vespertinoApoyoReynosaII->dia_id) {
                                                                case 1;
                                                                    $acLunesApoyoVReynosaII=$acLunesApoyoVReynosaII."".$vespertinoApoyoReynosaII->Nombre_Rol."<br>";                                        
                                                                break;

                                                                case 2;                                 
                                                                    $acMartesApoyoVReynosaII=$acMartesApoyoVReynosaII."".$vespertinoApoyoReynosaII->Nombre_Rol."<br>";       
                                                                break;

                                                                case 3;                                        
                                                                    $acMiercolesApoyoVReynosaII=$acMiercolesApoyoVReynosaII."".$vespertinoApoyoReynosaII->Nombre_Rol."<br>";
                                                                break;

                                                                case 4;                                        
                                                                    $acJuevesApoyoVReynosaII=$acJuevesApoyoVReynosaII."".$vespertinoApoyoReynosaII->Nombre_Rol."<br>";
                                                                break;

                                                                case 5;                                        
                                                                    $acViernesApoyoVReynosaII=$acViernesApoyoVReynosaII."".$vespertinoApoyoReynosaII->Nombre_Rol."<br>";
                                                                break;

                                                                case 6;   
                                                                    $acSabadoApoyoVReynosaII=$acSabadoApoyoVReynosaII."".$vespertinoApoyoReynosaII->Nombre_Rol."<br>";                                     
                                                                break;

                                                                case 7;                                        
                                                                    $acDomingoApoyoVReynosaII=$acDomingoApoyoVReynosaII."".$vespertinoApoyoReynosaII->Nombre_Rol."<br>";
                                                                break;                                                                        
                                                            }?>                              
                                                        <?php endforeach;?>                                                              
                                                    <?php endif;?>
                                                <!--FOR(S) ReynosaII-->                                                                                                      
                                                    <tr>
                                                        <td>RII</td>
                                                        <td>BARRA</td>
                                                        <td rowspan="1"><?=$acLunesBarraMReynosaII;?></td>  
                                                        <td rowspan="1"><?=$acLunesBarraVReynosaII;?></td>  
                                                        <td rowspan="1"><?=$acMartesBarraMReynosaII;?></td> 
                                                        <td rowspan="1"><?=$acMartesBarraVReynosaII;?></td> 
                                                        <td rowspan="1"><?=$acMiercolesBarraMReynosaII;?></td>
                                                        <td rowspan="1"><?=$acMiercolesBarraVReynosaII;?></td>        
                                                        <td rowspan="1"><?=$acJuevesBarraMReynosaII;?></td>   
                                                        <td rowspan="1"><?=$acJuevesBarraVReynosaII;?></td>        
                                                        <td rowspan="1"><?=$acViernesBarraMReynosaII;?></td>
                                                        <td rowspan="1"><?=$acViernesBarraVReynosaII;?></td>        
                                                        <td rowspan="1"><?=$acSabadoBarraMReynosaII;?></td> 
                                                        <td rowspan="1"><?=$acSabadoBarraVReynosaII;?></td> 
                                                        <td rowspan="1"><?=$acDomingoBarraMReynosaII;?></td>                                     
                                                        <td rowspan="1"><?=$acDomingoBarraVReynosaII;?></td> 
                                                        <td bgcolor="#d1ffaf"><b><?=$acBarraReynosaII;?></b></td>                                 
                                                    </tr>   

                                                    <tr>
                                                        <td>RII</td>
                                                        <td>MESAS</td>
                                                        <td rowspan="1"><?=$acLunesMesasMReynosaII;?></td>  
                                                        <td rowspan="1"><?=$acLunesMesasVReynosaII;?></td>  
                                                        <td rowspan="1"><?=$acMartesMesasMReynosaII;?></td> 
                                                        <td rowspan="1"><?=$acMartesMesasVReynosaII;?></td> 
                                                        <td rowspan="1"><?=$acMiercolesMesasMReynosaII;?></td>
                                                        <td rowspan="1"><?=$acMiercolesMesasVReynosaII;?></td>        
                                                        <td rowspan="1"><?=$acJuevesMesasMReynosaII;?></td>   
                                                        <td rowspan="1"><?=$acJuevesMesasVReynosaII;?></td>        
                                                        <td rowspan="1"><?=$acViernesMesasMReynosaII;?></td>
                                                        <td rowspan="1"><?=$acViernesMesasVReynosaII;?></td>        
                                                        <td rowspan="1"><?=$acSabadoMesasMReynosaII;?></td> 
                                                        <td rowspan="1"><?=$acSabadoMesasVReynosaII;?></td> 
                                                        <td rowspan="1"><?=$acDomingoMesasMReynosaII;?></td>                                     
                                                        <td rowspan="1"><?=$acDomingoMesasVReynosaII;?></td>  
                                                        <td bgcolor="#d1ffaf"><b><?=$acMesasReynosaII;?></b></td>              
                                                    </tr> 
                                                    
                                                    <tr>
                                                        <td>RII</td>
                                                        <td>COCINA</td>
                                                        <td rowspan="1"><?=$acLunesCocinaMReynosaII;?></td>  
                                                        <td rowspan="1"><?=$acLunesCocinaVReynosaII;?></td>  
                                                        <td rowspan="1"><?=$acMartesCocinaMReynosaII;?></td> 
                                                        <td rowspan="1"><?=$acMartesCocinaVReynosaII;?></td> 
                                                        <td rowspan="1"><?=$acMiercolesCocinaMReynosaII;?></td>
                                                        <td rowspan="1"><?=$acMiercolesCocinaVReynosaII;?></td>        
                                                        <td rowspan="1"><?=$acJuevesCocinaMReynosaII;?></td>   
                                                        <td rowspan="1"><?=$acJuevesCocinaVReynosaII;?></td>        
                                                        <td rowspan="1"><?=$acViernesCocinaMReynosaII;?></td>
                                                        <td rowspan="1"><?=$acViernesCocinaVReynosaII;?></td>        
                                                        <td rowspan="1"><?=$acSabadoCocinaMReynosaII;?></td> 
                                                        <td rowspan="1"><?=$acSabadoCocinaVReynosaII;?></td> 
                                                        <td rowspan="1"><?=$acDomingoCocinaMReynosaII;?></td>                                     
                                                        <td rowspan="1"><?=$acDomingoCocinaVReynosaII;?></td>                                    
                                                        <td bgcolor="#d1ffaf"><b><?=$acCocinaReynosaII;?></b></td>   
                                                    </tr> 

                                                    <tr>
                                                        <td>RII</td>
                                                        <td>APOYO</td>
                                                        <td rowspan="1"><?=$acLunesApoyoMReynosaII;?></td>  
                                                        <td rowspan="1"><?=$acLunesApoyoVReynosaII;?></td>  
                                                        <td rowspan="1"><?=$acMartesApoyoMReynosaII;?></td> 
                                                        <td rowspan="1"><?=$acMartesApoyoVReynosaII;?></td> 
                                                        <td rowspan="1"><?=$acMiercolesApoyoMReynosaII;?></td>
                                                        <td rowspan="1"><?=$acMiercolesApoyoVReynosaII;?></td>        
                                                        <td rowspan="1"><?=$acJuevesApoyoMReynosaII;?></td>   
                                                        <td rowspan="1"><?=$acJuevesApoyoVReynosaII;?></td>        
                                                        <td rowspan="1"><?=$acViernesApoyoMReynosaII;?></td>
                                                        <td rowspan="1"><?=$acViernesApoyoVReynosaII;?></td>        
                                                        <td rowspan="1"><?=$acSabadoApoyoMReynosaII;?></td> 
                                                        <td rowspan="1"><?=$acSabadoApoyoVReynosaII;?></td> 
                                                        <td rowspan="1"><?=$acDomingoApoyoMReynosaII;?></td>                                     
                                                        <td rowspan="1"><?=$acDomingoApoyoVReynosaII;?></td>                                     
                                                        <td bgcolor="#d1ffaf"><b><?=$acApoyoReynosaII;?></b></td>   
                                                    </tr>       

                                                    <tr>
                                                        <td bgcolor="#76c5ff"><b>TOTALES</b></td>
                                                        <td bgcolor="#76c5ff"></td>                                                        
                                                        <td bgcolor="#d1ffaf" colspan="2"><b><?=$acLunesReynosaII;?></b></td>  
                                                        <td bgcolor="#d1ffaf" colspan="2"><b><?=$acMartesReynosaII;?></b></td>  
                                                        <td bgcolor="#d1ffaf" colspan="2"><b><?=$acMiercolesReynosaII;?></b></td> 
                                                        <td bgcolor="#d1ffaf" colspan="2"><b><?=$acJuevesReynosaII;?></b></td> 
                                                        <td bgcolor="#d1ffaf" colspan="2"><b><?=$acViernesReynosaII;?></b></td>
                                                        <td bgcolor="#d1ffaf" colspan="2"><b><?=$acSabadoReynosaII;?></b></td>        
                                                        <td bgcolor="#d1ffaf" colspan="2"><b><?=$acDomingoReynosaII;?></b></td>                                                   
                                                        <td bgcolor="#d1ffaf"></td>   
                                                    </tr>                                         

                                                </tbody>                            
                                            </table>

                                            <table class="display responsive table table-bordered  btn-hover nowrap" style="width:100%" id="tbl_usuarios_11">                                            
                                                <!--VARIABLES INICIO DEL PROYECTO-->
                                                    <?php                                                     
                                                    $acLunesBarraMTeziutlan="";  
                                                    $acLunesBarraVTeziutlan="";  
                                                    $acMartesBarraMTeziutlan=""; 
                                                    $acMartesBarraVTeziutlan=""; 
                                                    $acMiercolesBarraMTeziutlan="";
                                                    $acMiercolesBarraVTeziutlan="";        
                                                    $acJuevesBarraMTeziutlan="";   
                                                    $acJuevesBarraVTeziutlan="";        
                                                    $acViernesBarraMTeziutlan="";
                                                    $acViernesBarraVTeziutlan="";        
                                                    $acSabadoBarraMTeziutlan=""; 
                                                    $acSabadoBarraVTeziutlan=""; 
                                                    $acDomingoBarraMTeziutlan="";                                     
                                                    $acDomingoBarraVTeziutlan=""; 
                                                    
                                                    $acLunesMesasMTeziutlan="";
                                                    $acLunesMesasVTeziutlan="";
                                                    $acMartesMesasMTeziutlan="";
                                                    $acMartesMesasVTeziutlan="";
                                                    $acMiercolesMesasMTeziutlan="";
                                                    $acMiercolesMesasVTeziutlan="";
                                                    $acJuevesMesasMTeziutlan="";
                                                    $acJuevesMesasVTeziutlan="";
                                                    $acViernesMesasMTeziutlan="";
                                                    $acViernesMesasVTeziutlan="";
                                                    $acSabadoMesasMTeziutlan="";
                                                    $acSabadoMesasVTeziutlan="";
                                                    $acDomingoMesasMTeziutlan="";
                                                    $acDomingoMesasVTeziutlan="";
                                                    
                                                                    
                                                    $acLunesCocinaMTeziutlan="";
                                                    $acLunesCocinaVTeziutlan="";
                                                    $acMartesCocinaMTeziutlan="";
                                                    $acMartesCocinaVTeziutlan="";
                                                    $acMiercolesCocinaMTeziutlan="";
                                                    $acMiercolesCocinaVTeziutlan="";
                                                    $acJuevesCocinaMTeziutlan="";
                                                    $acJuevesCocinaVTeziutlan="";
                                                    $acViernesCocinaMTeziutlan="";
                                                    $acViernesCocinaVTeziutlan="";
                                                    $acSabadoCocinaMTeziutlan="";
                                                    $acSabadoCocinaVTeziutlan="";
                                                    $acDomingoCocinaMTeziutlan="";
                                                    $acDomingoCocinaVTeziutlan="";
                                                                                            
                                                    $acLunesApoyoMTeziutlan="";
                                                    $acLunesApoyoVTeziutlan="";
                                                    $acMartesApoyoMTeziutlan="";
                                                    $acMartesApoyoVTeziutlan="";
                                                    $acMiercolesApoyoMTeziutlan="";
                                                    $acMiercolesApoyoVTeziutlan="";
                                                    $acJuevesApoyoMTeziutlan="";
                                                    $acJuevesApoyoVTeziutlan="";
                                                    $acViernesApoyoMTeziutlan="";
                                                    $acViernesApoyoVTeziutlan="";
                                                    $acSabadoApoyoMTeziutlan="";
                                                    $acSabadoApoyoVTeziutlan="";
                                                    $acDomingoApoyoMTeziutlan="";
                                                    $acDomingoApoyoVTeziutlan="";
                                                    ?>
                                            
                                    
                                                <thead>
                                                    <tr>       

                                                        <?php 
                                                            $lunes = date( 'd', strtotime( 'monday this week' ));
                                                            $martes = date( 'd', strtotime( 'tuesday this week' ));
                                                            $miercoles = date( 'd', strtotime( 'wednesday this week' ));
                                                            $jueves = date( 'd', strtotime( 'thursday this week' ));
                                                            $viernes = date( 'd', strtotime( 'friday this week' ));
                                                            $sabado = date( 'd', strtotime( 'saturday this week' ));
                                                            $domingo = date( 'd', strtotime( 'sunday this week' ));                                
                                                        ?> 
                                                            
                                                        <th>PLAZA</th>      
                                                        <th>TURNO</th>                                                     
                                                        <th colspan="2">Lun <?php echo $lunesE;?></th>  
                                                        <th colspan="2">Mar <?php echo $martesE;?></th>                     
                                                        <th colspan="2">Mier <?php echo $miercolesE;?></th>                                    
                                                        <th colspan="2">Jue <?php echo $juevesE;?></th>                                                                        
                                                        <th colspan="2">Vie <?php echo $viernesE;?></th>
                                                        <th colspan="2">Sab <?php echo $sabadoE;?></th>
                                                        <th colspan="2">Dom <?php echo $domingoE;?></th>   
                                                        <th>Totales</th>                                                                                                  
                                                    </tr>
                                                </thead>
                                                <tbody>     
                                                        
                                                        <!--FOR(S) TEZIUTLAN-->                                                    
                                                            <?php if(!empty($matutinosBarraTeziutlan)):?>                           
                                                                <?php foreach ($matutinosBarraTeziutlan as $matutinoBarraTeziutlan):?>
                                                                    <?php switch ($matutinoBarraTeziutlan->dia_id) {
                                                                        case 1;
                                                                            $acLunesBarraMTeziutlan=$acLunesBarraMTeziutlan."".$matutinoBarraTeziutlan->Nombre_Rol."<br>";                                        
                                                                        break;

                                                                        case 2;                                 
                                                                            $acMartesBarraMTeziutlan=$acMartesBarraMTeziutlan."".$matutinoBarraTeziutlan->Nombre_Rol."<br>";       
                                                                        break;

                                                                        case 3;                                        
                                                                            $acMiercolesBarraMTeziutlan=$acMiercolesBarraMTeziutlan."".$matutinoBarraTeziutlan->Nombre_Rol."<br>";
                                                                        break;

                                                                        case 4;                                        
                                                                            $acJuevesBarraMTeziutlan=$acJuevesBarraMTeziutlan."".$matutinoBarraTeziutlan->Nombre_Rol."<br>";
                                                                        break;

                                                                        case 5;                                        
                                                                            $acViernesBarraMTeziutlan=$acViernesBarraMTeziutlan."".$matutinoBarraTeziutlan->Nombre_Rol."<br>";
                                                                        break;

                                                                        case 6;   
                                                                            $acSabadoBarraMTeziutlan=$acSabadoBarraMTeziutlan."".$matutinoBarraTeziutlan->Nombre_Rol."<br>";                                     
                                                                        break;

                                                                        case 7;                                        
                                                                            $acDomingoBarraMTeziutlan=$acDomingoBarraMTeziutlan."".$matutinoBarraTeziutlan->Nombre_Rol."<br>";
                                                                        break;                                                                        
                                                                    }?>                              
                                                                <?php endforeach;?>                                                              
                                                            <?php endif;?>  

                                                            <?php if(!empty($vespertinosBarraTeziutlan)):?>                           
                                                                <?php foreach ($vespertinosBarraTeziutlan as $vespertinoBarraTeziutlan):?>
                                                                    <?php switch ($vespertinoBarraTeziutlan->dia_id) {
                                                                        case 1;
                                                                            $acLunesBarraVTeziutlan=$acLunesBarraVTeziutlan."".$vespertinoBarraTeziutlan->Nombre_Rol."<br>";                                        
                                                                        break;

                                                                        case 2;                                 
                                                                            $acMartesBarraVTeziutlan=$acMartesBarraVTeziutlan."".$vespertinoBarraTeziutlan->Nombre_Rol."<br>";       
                                                                        break;

                                                                        case 3;                                        
                                                                            $acMiercolesBarraVTeziutlan=$acMiercolesBarraVTeziutlan."".$vespertinoBarraTeziutlan->Nombre_Rol."<br>";
                                                                        break;

                                                                        case 4;                                        
                                                                            $acJuevesBarraVTeziutlan=$acJuevesBarraVTeziutlan."".$vespertinoBarraTeziutlan->Nombre_Rol."<br>";
                                                                        break;

                                                                        case 5;                                        
                                                                            $acViernesBarraVTeziutlan=$acViernesBarraVTeziutlan."".$vespertinoBarraTeziutlan->Nombre_Rol."<br>";
                                                                        break;

                                                                        case 6;   
                                                                            $acSabadoBarraVTeziutlan=$acSabadoBarraVTeziutlan."".$vespertinoBarraTeziutlan->Nombre_Rol."<br>";                                     
                                                                        break;

                                                                        case 7;                                        
                                                                            $acDomingoBarraVTeziutlan=$acDomingoBarraVTeziutlan."".$vespertinoBarraTeziutlan->Nombre_Rol."<br>";
                                                                        break;                                                                        
                                                                    }?>                              
                                                                <?php endforeach;?>                                                              
                                                            <?php endif;?>  
                                                    
                                                            <?php if(!empty($matutinosMesasTeziutlan)):?>                           
                                                                <?php foreach ($matutinosMesasTeziutlan as $matutinoMesasTeziutlan):?>
                                                                    <?php switch ($matutinoMesasTeziutlan->dia_id) {
                                                                        case 1;
                                                                            $acLunesMesasMTeziutlan=$acLunesMesasMTeziutlan."".$matutinoMesasTeziutlan->Nombre_Rol."<br>";                                        
                                                                        break;

                                                                        case 2;                                 
                                                                            $acMartesMesasMTeziutlan=$acMartesMesasMTeziutlan."".$matutinoMesasTeziutlan->Nombre_Rol."<br>";       
                                                                        break;

                                                                        case 3;                                        
                                                                            $acMiercolesMesasMTeziutlan=$acMiercolesMesasMTeziutlan."".$matutinoMesasTeziutlan->Nombre_Rol."<br>";
                                                                        break;

                                                                        case 4;                                        
                                                                            $acJuevesMesasMTeziutlan=$acJuevesMesasMTeziutlan."".$matutinoMesasTeziutlan->Nombre_Rol."<br>";
                                                                        break;

                                                                        case 5;                                        
                                                                            $acViernesMesasMTeziutlan=$acViernesMesasMTeziutlan."".$matutinoMesasTeziutlan->Nombre_Rol."<br>";
                                                                        break;

                                                                        case 6;   
                                                                            $acSabadoMesasMTeziutlan=$acSabadoMesasMTeziutlan."".$matutinoMesasTeziutlan->Nombre_Rol."<br>";                                     
                                                                        break;

                                                                        case 7;                                        
                                                                            $acDomingoMesasMTeziutlan=$acDomingoMesasMTeziutlan."".$matutinoMesasTeziutlan->Nombre_Rol."<br>";
                                                                        break;                                                                        
                                                                    }?>                              
                                                                <?php endforeach;?>                                                              
                                                            <?php endif;?>  

                                                            <?php if(!empty($vespertinosMesasTeziutlan)):?>                           
                                                                <?php foreach ($vespertinosMesasTeziutlan as $vespertinoMesasTeziutlan):?>
                                                                    <?php switch ($vespertinoMesasTeziutlan->dia_id) {
                                                                        case 1;
                                                                            $acLunesMesasVTeziutlan=$acLunesMesasVTeziutlan."".$vespertinoMesasTeziutlan->Nombre_Rol."<br>";                                        
                                                                        break;

                                                                        case 2;                                 
                                                                            $acMartesMesasVTeziutlan=$acMartesMesasVTeziutlan."".$vespertinoMesasTeziutlan->Nombre_Rol."<br>";       
                                                                        break;

                                                                        case 3;                                        
                                                                            $acMiercolesMesasVTeziutlan=$acMiercolesMesasVTeziutlan."".$vespertinoMesasTeziutlan->Nombre_Rol."<br>";
                                                                        break;

                                                                        case 4;                                        
                                                                            $acJuevesMesasVTeziutlan=$acJuevesMesasVTeziutlan."".$vespertinoMesasTeziutlan->Nombre_Rol."<br>";
                                                                        break;

                                                                        case 5;                                        
                                                                            $acViernesMesasVTeziutlan=$acViernesMesasVTeziutlan."".$vespertinoMesasTeziutlan->Nombre_Rol."<br>";
                                                                        break;

                                                                        case 6;   
                                                                            $acSabadoMesasVTeziutlan=$acSabadoMesasVTeziutlan."".$vespertinoMesasTeziutlan->Nombre_Rol."<br>";                                     
                                                                        break;

                                                                        case 7;                                        
                                                                            $acDomingoMesasVTeziutlan=$acDomingoMesasVTeziutlan."".$vespertinoMesasTeziutlan->Nombre_Rol."<br>";
                                                                        break;                                                                        
                                                                    }?>                              
                                                                <?php endforeach;?>                                                              
                                                            <?php endif;?>  

                                                            <?php if(!empty($matutinosCocinaTeziutlan)):?>                           
                                                                <?php foreach ($matutinosCocinaTeziutlan as $matutinoCocinaTeziutlan):?>
                                                                    <?php switch ($matutinoCocinaTeziutlan->dia_id) {
                                                                        case 1;
                                                                            $acLunesCocinaMTeziutlan=$acLunesCocinaMTeziutlan."".$matutinoCocinaTeziutlan->Nombre_Rol."<br>";                                        
                                                                        break;

                                                                        case 2;                                 
                                                                            $acMartesCocinaMTeziutlan=$acMartesCocinaMTeziutlan."".$matutinoCocinaTeziutlan->Nombre_Rol."<br>";       
                                                                        break;

                                                                        case 3;                                        
                                                                            $acMiercolesCocinaMTeziutlan=$acMiercolesCocinaMTeziutlan."".$matutinoCocinaTeziutlan->Nombre_Rol."<br>";
                                                                        break;

                                                                        case 4;                                        
                                                                            $acJuevesCocinaMTeziutlan=$acJuevesCocinaMTeziutlan."".$matutinoCocinaTeziutlan->Nombre_Rol."<br>";
                                                                        break;

                                                                        case 5;                                        
                                                                            $acViernesCocinaMTeziutlan=$acViernesCocinaMTeziutlan."".$matutinoCocinaTeziutlan->Nombre_Rol."<br>";
                                                                        break;

                                                                        case 6;   
                                                                            $acSabadoCocinaMTeziutlan=$acSabadoCocinaMTeziutlan."".$matutinoCocinaTeziutlan->Nombre_Rol."<br>";                                     
                                                                        break;

                                                                        case 7;                                        
                                                                            $acDomingoCocinaMTeziutlan=$acDomingoCocinaMTeziutlan."".$matutinoCocinaTeziutlan->Nombre_Rol."<br>";
                                                                        break;                                                                        
                                                                    }?>                              
                                                                <?php endforeach;?>                                                              
                                                            <?php endif;?> 

                                                            <?php if(!empty($vespertinosCocinaTeziutlan)):?>                           
                                                                <?php foreach ($vespertinosCocinaTeziutlan as $vespertinoCocinaTeziutlan):?>
                                                                    <?php switch ($vespertinoCocinaTeziutlan->dia_id) {
                                                                        case 1;
                                                                            $acLunesCocinaVTeziutlan=$acLunesCocinaVTeziutlan."".$vespertinoCocinaTeziutlan->Nombre_Rol."<br>";                                        
                                                                        break;

                                                                        case 2;                                 
                                                                            $acMartesCocinaVTeziutlan=$acMartesCocinaVTeziutlan."".$vespertinoCocinaTeziutlan->Nombre_Rol."<br>";       
                                                                        break;

                                                                        case 3;                                        
                                                                            $acMiercolesCocinaVTeziutlan=$acMiercolesCocinaVTeziutlan."".$vespertinoCocinaTeziutlan->Nombre_Rol."<br>";
                                                                        break;

                                                                        case 4;                                        
                                                                            $acJuevesCocinaVTeziutlan=$acJuevesCocinaVTeziutlan."".$vespertinoCocinaTeziutlan->Nombre_Rol."<br>";
                                                                        break;

                                                                        case 5;                                        
                                                                            $acViernesCocinaVTeziutlan=$acViernesCocinaVTeziutlan."".$vespertinoCocinaTeziutlan->Nombre_Rol."<br>";
                                                                        break;

                                                                        case 6;   
                                                                            $acSabadoCocinaVTeziutlan=$acSabadoCocinaVTeziutlan."".$vespertinoCocinaTeziutlan->Nombre_Rol."<br>";                                     
                                                                        break;

                                                                        case 7;                                        
                                                                            $acDomingoCocinaVTeziutlan=$acDomingoCocinaVTeziutlan."".$vespertinoCocinaTeziutlan->Nombre_Rol."<br>";
                                                                        break;                                                                        
                                                                    }?>                              
                                                                <?php endforeach;?>                                                              
                                                            <?php endif;?>  

                                                            <?php if(!empty($matutinosApoyoTeziutlan)):?>                           
                                                                <?php foreach ($matutinosApoyoTeziutlan as $matutinoApoyoTeziutlan):?>
                                                                    <?php switch ($matutinoApoyoTeziutlan->dia_id) {
                                                                        case 1;
                                                                            $acLunesApoyoMTeziutlan=$acLunesApoyoMTeziutlan."".$matutinoApoyoTeziutlan->Nombre_Rol."<br>";                                        
                                                                        break;

                                                                        case 2;                                 
                                                                            $acMartesApoyoMTeziutlan=$acMartesApoyoMTeziutlan."".$matutinoApoyoTeziutlan->Nombre_Rol."<br>";       
                                                                        break;

                                                                        case 3;                                        
                                                                            $acMiercolesApoyoMTeziutlan=$acMiercolesApoyoMTeziutlan."".$matutinoApoyoTeziutlan->Nombre_Rol."<br>";
                                                                        break;

                                                                        case 4;                                        
                                                                            $acJuevesApoyoMTeziutlan=$acJuevesApoyoMTeziutlan."".$matutinoApoyoTeziutlan->Nombre_Rol."<br>";
                                                                        break;

                                                                        case 5;                                        
                                                                            $acViernesApoyoMTeziutlan=$acViernesApoyoMTeziutlan."".$matutinoApoyoTeziutlan->Nombre_Rol."<br>";
                                                                        break;

                                                                        case 6;   
                                                                            $acSabadoApoyoMTeziutlan=$acSabadoApoyoMTeziutlan."".$matutinoApoyoTeziutlan->Nombre_Rol."<br>";                                     
                                                                        break;

                                                                        case 7;                                        
                                                                            $acDomingoApoyoMTeziutlan=$acDomingoApoyoMTeziutlan."".$matutinoApoyoTeziutlan->Nombre_Rol."<br>";
                                                                        break;                                                                        
                                                                    }?>                              
                                                                <?php endforeach;?>                                                              
                                                            <?php endif;?> 

                                                            <?php if(!empty($vespertinosApoyoTeziutlan)):?>                           
                                                                <?php foreach ($vespertinosApoyoTeziutlan as $vespertinoApoyoTeziutlan):?>
                                                                    <?php switch ($vespertinoApoyoTeziutlan->dia_id) {
                                                                        case 1;
                                                                            $acLunesApoyoVTeziutlan=$acLunesApoyoVTeziutlan."".$vespertinoApoyoTeziutlan->Nombre_Rol."<br>";                                        
                                                                        break;

                                                                        case 2;                                 
                                                                            $acMartesApoyoVTeziutlan=$acMartesApoyoVTeziutlan."".$vespertinoApoyoTeziutlan->Nombre_Rol."<br>";       
                                                                        break;

                                                                        case 3;                                        
                                                                            $acMiercolesApoyoVTeziutlan=$acMiercolesApoyoVTeziutlan."".$vespertinoApoyoTeziutlan->Nombre_Rol."<br>";
                                                                        break;

                                                                        case 4;                                        
                                                                            $acJuevesApoyoVTeziutlan=$acJuevesApoyoVTeziutlan."".$vespertinoApoyoTeziutlan->Nombre_Rol."<br>";
                                                                        break;

                                                                        case 5;                                        
                                                                            $acViernesApoyoVTeziutlan=$acViernesApoyoVTeziutlan."".$vespertinoApoyoTeziutlan->Nombre_Rol."<br>";
                                                                        break;

                                                                        case 6;   
                                                                            $acSabadoApoyoVTeziutlan=$acSabadoApoyoVTeziutlan."".$vespertinoApoyoTeziutlan->Nombre_Rol."<br>";                                     
                                                                        break;

                                                                        case 7;                                        
                                                                            $acDomingoApoyoVTeziutlan=$acDomingoApoyoVTeziutlan."".$vespertinoApoyoTeziutlan->Nombre_Rol."<br>";
                                                                        break;                                                                        
                                                                    }?>                              
                                                                <?php endforeach;?>                                                              
                                                            <?php endif;?>
                                                        <!--FOR(S) TEZIUTLAN-->  


                                                                                                    
                                                    <tr>
                                                        <td>TZ</td>
                                                        <td>BARRA</td>
                                                        <td rowspan="1"><?=$acLunesBarraMTeziutlan;?></td>  
                                                        <td rowspan="1"><?=$acLunesBarraVTeziutlan;?></td>  
                                                        <td rowspan="1"><?=$acMartesBarraMTeziutlan;?></td> 
                                                        <td rowspan="1"><?=$acMartesBarraVTeziutlan;?></td> 
                                                        <td rowspan="1"><?=$acMiercolesBarraMTeziutlan;?></td>
                                                        <td rowspan="1"><?=$acMiercolesBarraVTeziutlan;?></td>        
                                                        <td rowspan="1"><?=$acJuevesBarraMTeziutlan;?></td>   
                                                        <td rowspan="1"><?=$acJuevesBarraVTeziutlan;?></td>        
                                                        <td rowspan="1"><?=$acViernesBarraMTeziutlan;?></td>
                                                        <td rowspan="1"><?=$acViernesBarraVTeziutlan;?></td>        
                                                        <td rowspan="1"><?=$acSabadoBarraMTeziutlan;?></td> 
                                                        <td rowspan="1"><?=$acSabadoBarraVTeziutlan;?></td> 
                                                        <td rowspan="1"><?=$acDomingoBarraMTeziutlan;?></td>                                     
                                                        <td rowspan="1"><?=$acDomingoBarraVTeziutlan;?></td> 
                                                        <td bgcolor="#d1ffaf"><b><?=$acBarraTeziutlan;?></b></td>                                   
                                                    </tr>   

                                                    <tr>
                                                        <td>TZ</td>
                                                        <td>MESAS</td>
                                                        <td rowspan="1"><?=$acLunesMesasMTeziutlan;?></td>  
                                                        <td rowspan="1"><?=$acLunesMesasVTeziutlan;?></td>  
                                                        <td rowspan="1"><?=$acMartesMesasMTeziutlan;?></td> 
                                                        <td rowspan="1"><?=$acMartesMesasVTeziutlan;?></td> 
                                                        <td rowspan="1"><?=$acMiercolesMesasMTeziutlan;?></td>
                                                        <td rowspan="1"><?=$acMiercolesMesasVTeziutlan;?></td>        
                                                        <td rowspan="1"><?=$acJuevesMesasMTeziutlan;?></td>   
                                                        <td rowspan="1"><?=$acJuevesMesasVTeziutlan;?></td>        
                                                        <td rowspan="1"><?=$acViernesMesasMTeziutlan;?></td>
                                                        <td rowspan="1"><?=$acViernesMesasVTeziutlan;?></td>        
                                                        <td rowspan="1"><?=$acSabadoMesasMTeziutlan;?></td> 
                                                        <td rowspan="1"><?=$acSabadoMesasVTeziutlan;?></td> 
                                                        <td rowspan="1"><?=$acDomingoMesasMTeziutlan;?></td>                                     
                                                        <td rowspan="1"><?=$acDomingoMesasVTeziutlan;?></td>  
                                                        <td bgcolor="#d1ffaf"><b><?=$acMesasTeziutlan;?></b></td>                                  
                                                    </tr> 
                                                    
                                                    <tr>
                                                        <td>TZ</td>
                                                        <td>COCINA</td>
                                                        <td rowspan="1"><?=$acLunesCocinaMTeziutlan;?></td>  
                                                        <td rowspan="1"><?=$acLunesCocinaVTeziutlan;?></td>  
                                                        <td rowspan="1"><?=$acMartesCocinaMTeziutlan;?></td> 
                                                        <td rowspan="1"><?=$acMartesCocinaVTeziutlan;?></td> 
                                                        <td rowspan="1"><?=$acMiercolesCocinaMTeziutlan;?></td>
                                                        <td rowspan="1"><?=$acMiercolesCocinaVTeziutlan;?></td>        
                                                        <td rowspan="1"><?=$acJuevesCocinaMTeziutlan;?></td>   
                                                        <td rowspan="1"><?=$acJuevesCocinaVTeziutlan;?></td>        
                                                        <td rowspan="1"><?=$acViernesCocinaMTeziutlan;?></td>
                                                        <td rowspan="1"><?=$acViernesCocinaVTeziutlan;?></td>        
                                                        <td rowspan="1"><?=$acSabadoCocinaMTeziutlan;?></td> 
                                                        <td rowspan="1"><?=$acSabadoCocinaVTeziutlan;?></td> 
                                                        <td rowspan="1"><?=$acDomingoCocinaMTeziutlan;?></td>                                     
                                                        <td rowspan="1"><?=$acDomingoCocinaVTeziutlan;?></td>                                    
                                                        <td bgcolor="#d1ffaf"><b><?=$acCocinaTeziutlan;?></b></td>                                  
                                                    </tr> 

                                                    <tr>
                                                        <td>TZ</td>
                                                        <td>APOYO</td>
                                                        <td rowspan="1"><?=$acLunesApoyoMTeziutlan;?></td>  
                                                        <td rowspan="1"><?=$acLunesApoyoVTeziutlan;?></td>  
                                                        <td rowspan="1"><?=$acMartesApoyoMTeziutlan;?></td> 
                                                        <td rowspan="1"><?=$acMartesApoyoVTeziutlan;?></td> 
                                                        <td rowspan="1"><?=$acMiercolesApoyoMTeziutlan;?></td>
                                                        <td rowspan="1"><?=$acMiercolesApoyoVTeziutlan;?></td>        
                                                        <td rowspan="1"><?=$acJuevesApoyoMTeziutlan;?></td>   
                                                        <td rowspan="1"><?=$acJuevesApoyoVTeziutlan;?></td>        
                                                        <td rowspan="1"><?=$acViernesApoyoMTeziutlan;?></td>
                                                        <td rowspan="1"><?=$acViernesApoyoVTeziutlan;?></td>        
                                                        <td rowspan="1"><?=$acSabadoApoyoMTeziutlan;?></td> 
                                                        <td rowspan="1"><?=$acSabadoApoyoVTeziutlan;?></td> 
                                                        <td rowspan="1"><?=$acDomingoApoyoMTeziutlan;?></td>                                     
                                                        <td rowspan="1"><?=$acDomingoApoyoVTeziutlan;?></td>                                     
                                                        <td bgcolor="#d1ffaf"><b><?=$acApoyoTeziutlan;?></b></td>                                  
                                                    </tr>       

                                                    <tr>
                                                        <td bgcolor="#76c5ff"><b>Total</b></td>
                                                        <td bgcolor="#76c5ff"></td>                                                        
                                                        <td bgcolor="#d1ffaf" colspan="2"><b><?=$acLunesTeziutlan;?></b></td>  
                                                        <td bgcolor="#d1ffaf" colspan="2"><b><?=$acMartesTeziutlan;?></b></td>  
                                                        <td bgcolor="#d1ffaf" colspan="2"><b><?=$acMiercolesTeziutlan;?></b></td> 
                                                        <td bgcolor="#d1ffaf" colspan="2"><b><?=$acJuevesTeziutlan;?></b></td> 
                                                        <td bgcolor="#d1ffaf" colspan="2"><b><?=$acViernesTeziutlan;?></b></td>
                                                        <td bgcolor="#d1ffaf" colspan="2"><b><?=$acSabadoTeziutlan;?></b></td>        
                                                        <td bgcolor="#d1ffaf" colspan="2"><b><?=$acDomingoTeziutlan;?></b></td>                                                
                                                        <td bgcolor="#d1ffaf"></td>   
                                                    </tr>                                         

                                                </tbody>                            
                                            </table>                                          
                                        <!--TODAS LAS PLAZAS TODOS LOS LUGARES DE LA CAFETERIA-->
                                        <?php break; ?>

                                        <?php case 1: ?>
                                        <!--UNA PLAZA SELECCIONADA UN LUGAR  DE LA CAFETERIA-->
                                             <table class="display responsive table table-bordered  btn-hover nowrap" style="width:100%" id="tbl_usuarios_13">                                        
                                        
                                                <!--VARIABLES INICIO DEL PROYECTO-->
                                                <?php 
                                                
                                                    $acLunesGenericoM="";
                                                    $acLunesGenericoV="";
                                                    $acMartesGenericoM="";
                                                    $acMartesGenericoV="";
                                                    $acMiercolesGenericoM="";
                                                    $acMiercolesGenericoV="";
                                                    $acJuevesGenericoM="";
                                                    $acJuevesGenericoV="";
                                                    $acViernesGenericoM="";
                                                    $acViernesGenericoV="";
                                                    $acSabadoGenericoM="";
                                                    $acSabadoGenericoV="";
                                                    $acDomingoGenericoM="";
                                                    $acDomingoGenericoV="";

                                                    
                                                    
                                                
                                                ?>
                                                <thead>
                                                
                                                    <tr>       

                                                        <?php 
                                                            $lunes = date( 'd', strtotime( 'monday this week' ));
                                                            $martes = date( 'd', strtotime( 'tuesday this week' ));
                                                            $miercoles = date( 'd', strtotime( 'wednesday this week' ));
                                                            $jueves = date( 'd', strtotime( 'thursday this week' ));
                                                            $viernes = date( 'd', strtotime( 'friday this week' ));
                                                            $sabado = date( 'd', strtotime( 'saturday this week' ));
                                                            $domingo = date( 'd', strtotime( 'sunday this week' ));                                
                                                        ?> 
                                                                    
                                                            <th><b><?=$nombreLugar;?></b></th>                                                     
                                                            <th colspan="2">Lun <?php echo $lunesE;?></th>  
                                                            <th colspan="2">Mar <?php echo $martesE;?></th>                     
                                                            <th colspan="2">Mier <?php echo $miercolesE;?></th>                                    
                                                            <th colspan="2">Jue <?php echo $juevesE;?></th>                                                                        
                                                            <th colspan="2">Vie <?php echo $viernesE;?></th>
                                                            <th colspan="2">Sab <?php echo $sabadoE;?></th>
                                                            <th colspan="2">Dom <?php echo $domingoE;?></th>   
                                                            <th>Totales</th>                                                                                                  
                                                    </tr>

                                                </thead>
                                                <tbody>    

                                                    <!--FOR(S)-->
                                                        <?php if(!empty($matutinosGenerico)):?>                           
                                                            <?php foreach ($matutinosGenerico as $matutinoGenerico):?>
                                                                <?php switch ($matutinoGenerico->dia_id) {
                                                                    case 1;
                                                                        $acLunesGenericoM=$acLunesGenericoM."".$matutinoGenerico->Nombre_Rol."<br>";                                        
                                                                    break;

                                                                    case 2;                                 
                                                                        $acMartesGenericoM=$acMartesGenericoM."".$matutinoGenerico->Nombre_Rol."<br>";       
                                                                    break;

                                                                    case 3;                                        
                                                                        $acMiercolesGenericoM=$acMiercolesGenericoM."".$matutinoGenerico->Nombre_Rol."<br>";
                                                                    break;

                                                                    case 4;                                        
                                                                        $acJuevesGenericoM=$acJuevesGenericoM."".$matutinoGenerico->Nombre_Rol."<br>";
                                                                    break;

                                                                    case 5;                                        
                                                                        $acViernesGenericoM=$acViernesGenericoM."".$matutinoGenerico->Nombre_Rol."<br>";
                                                                    break;

                                                                    case 6;   
                                                                        $acSabadoGenericoM=$acSabadoGenericoM."".$matutinoGenerico->Nombre_Rol."<br>";                                     
                                                                    break;

                                                                    case 7;                                        
                                                                        $acDomingoGenericoM=$acDomingoGenericoM."".$matutinoGenerico->Nombre_Rol."<br>";
                                                                    break;                                                                        
                                                                }?>                              
                                                            <?php endforeach;?>                                                              
                                                        <?php endif;?>  

                                                        <?php if(!empty($vespertinosGenerico)):?>                           
                                                            <?php foreach ($vespertinosGenerico as $vespertinoGenerico):?>
                                                                <?php switch ($vespertinoGenerico->dia_id) {
                                                                    case 1;
                                                                        $acLunesGenericoV=$acLunesGenericoV."".$vespertinoGenerico->Nombre_Rol."<br>";                                        
                                                                    break;

                                                                    case 2;                                 
                                                                        $acMartesGenericoV=$acMartesGenericoV."".$vespertinoGenerico->Nombre_Rol."<br>";       
                                                                    break;

                                                                    case 3;                                        
                                                                        $acMiercolesGenericoV=$acMiercolesGenericoV."".$vespertinoGenerico->Nombre_Rol."<br>";
                                                                    break;

                                                                    case 4;                                        
                                                                        $acJuevesGenericoV=$acJuevesGenericoV."".$vespertinoGenerico->Nombre_Rol."<br>";
                                                                    break;

                                                                    case 5;                                        
                                                                        $acViernesGenericoV=$acViernesGenericoV."".$vespertinoGenerico->Nombre_Rol."<br>";
                                                                    break;

                                                                    case 6;   
                                                                        $acSabadoGenericoV=$acSabadoGenericoV."".$vespertinoGenerico->Nombre_Rol."<br>";                                     
                                                                    break;

                                                                    case 7;                                        
                                                                        $acDomingoGenericoV=$acDomingoGenericoV."".$vespertinoGenerico->Nombre_Rol."<br>";
                                                                    break;                                                                        
                                                                }?>                              
                                                            <?php endforeach;?>                                                              
                                                        <?php endif;?>  

                                                    <!--FOR(S)-->  
                                                    <tr>
                                                        <td><b><?=$nombrePlaza;?></b></td>
                                                        <td rowspan="1"><?=$acLunesGenericoM;?></td>  
                                                        <td rowspan="1"><?=$acLunesGenericoV;?></td>  
                                                        <td rowspan="1"><?=$acMartesGenericoM;?></td> 
                                                        <td rowspan="1"><?=$acMartesGenericoV;?></td> 
                                                        <td rowspan="1"><?=$acMiercolesGenericoM;?></td>
                                                        <td rowspan="1"><?=$acMiercolesGenericoV;?></td>        
                                                        <td rowspan="1"><?=$acJuevesGenericoM;?></td>   
                                                        <td rowspan="1"><?=$acJuevesGenericoV;?></td>        
                                                        <td rowspan="1"><?=$acViernesGenericoM;?></td>
                                                        <td rowspan="1"><?=$acViernesGenericoV;?></td>        
                                                        <td rowspan="1"><?=$acSabadoGenericoM;?></td> 
                                                        <td rowspan="1"><?=$acSabadoGenericoV;?></td> 
                                                        <td rowspan="1"><?=$acDomingoGenericoM;?></td>                                     
                                                        <td rowspan="1"><?=$acDomingoGenericoV;?></td> 
                                                        <td bgcolor="#d1ffaf"><b><?=$acGenerico;?></b></td>                                   
                                                    </tr>   

                                                    <tr>
                                                        <td bgcolor="#76c5ff"><b>Total</b></td>
                                                        <td bgcolor="#d1ffaf" colspan="2"><b><?=$acLunes;?></b></td>  
                                                        <td bgcolor="#d1ffaf" colspan="2"><b><?=$acMartes;?></b></td>  
                                                        <td bgcolor="#d1ffaf" colspan="2"><b><?=$acMiercoles;?></b></td> 
                                                        <td bgcolor="#d1ffaf" colspan="2"><b><?=$acJueves;?></b></td> 
                                                        <td bgcolor="#d1ffaf" colspan="2"><b><?=$acViernes;?></b></td>
                                                        <td bgcolor="#d1ffaf" colspan="2"><b><?=$acSabado;?></b></td>        
                                                        <td bgcolor="#d1ffaf" colspan="2"><b><?=$acDomingo;?></b></td>                                                   
                                                        <td bgcolor="#d1ffaf"><b><?=($acGenerico);?></td>   
                                                    </tr>                                                                                                                   

                                                </tbody>                            
                                            </table>
                                        <!--UNA PLAZA SELECCIONADA UN LUGAR  DE LA CAFETERIA-->
                                        <?php break; ?>

                                        <?php case 2: ?>                                        
                                        <!--TODAS LAS PLAZAS SELECCIONADAS UN LUGAR  DE LA CAFETERIA-->
                                            <table class="display responsive table table-bordered  btn-hover nowrap" style="width:100%" id="tbl_usuarios_12">
                                                
                                                <!--VARIABLES INICIO DEL PROYECTO-->
                                                    <?php 
                                                    
                                                        $acLunesBarraM="";
                                                        $acLunesBarraV="";
                                                        $acMartesBarraM="";
                                                        $acMartesBarraV="";
                                                        $acMiercolesBarraM="";
                                                        $acMiercolesBarraV="";
                                                        $acJuevesBarraM="";
                                                        $acJuevesBarraV="";
                                                        $acViernesBarraM="";
                                                        $acViernesBarraV="";
                                                        $acSabadoBarraM="";
                                                        $acSabadoBarraV="";
                                                        $acDomingoBarraM="";
                                                        $acDomingoBarraV="";

                                                        $acLunesMesasM="";
                                                        $acLunesMesasV="";
                                                        $acMartesMesasM="";
                                                        $acMartesMesasV="";
                                                        $acMiercolesMesasM="";
                                                        $acMiercolesMesasV="";
                                                        $acJuevesMesasM="";
                                                        $acJuevesMesasV="";
                                                        $acViernesMesasM="";
                                                        $acViernesMesasV="";
                                                        $acSabadoMesasM="";
                                                        $acSabadoMesasV="";
                                                        $acDomingoMesasM="";
                                                        $acDomingoMesasV="";

                                                        $acLunesCocinaM="";
                                                        $acLunesCocinaV="";
                                                        $acMartesCocinaM="";
                                                        $acMartesCocinaV="";
                                                        $acMiercolesCocinaM="";
                                                        $acMiercolesCocinaV="";
                                                        $acJuevesCocinaM="";
                                                        $acJuevesCocinaV="";
                                                        $acViernesCocinaM="";
                                                        $acViernesCocinaV="";
                                                        $acSabadoCocinaM="";
                                                        $acSabadoCocinaV="";
                                                        $acDomingoCocinaM="";
                                                        $acDomingoCocinaV="";

                                                        $acLunesApoyoM="";
                                                        $acLunesApoyoV="";
                                                        $acMartesApoyoM="";
                                                        $acMartesApoyoV="";
                                                        $acMiercolesApoyoM="";
                                                        $acMiercolesApoyoV="";
                                                        $acJuevesApoyoM="";
                                                        $acJuevesApoyoV="";
                                                        $acViernesApoyoM="";
                                                        $acViernesApoyoV="";
                                                        $acSabadoApoyoM="";
                                                        $acSabadoApoyoV="";
                                                        $acDomingoApoyoM="";
                                                        $acDomingoApoyoV="";
                                                        
                                                        $acMartesM="";
                                                        $acMiercolesM="";
                                                        $acJuevesM="";
                                                        $acViernesM="";
                                                        $acSabadoM="";
                                                        $acDomingoM="";
                                                        
                                                        $acLunesV="";
                                                        $acMartesV="";
                                                        $acMiercolesV="";
                                                        $acJuevesV="";
                                                        $acViernesV="";
                                                        $acSabadoV="";
                                                        $acDomingoV="";
                                                    ?>
                                            
                                                <thead>
                                                    <tr>       

                                                        <?php 
                                                            $lunes = date( 'd', strtotime( 'monday this week' ));
                                                            $martes = date( 'd', strtotime( 'tuesday this week' ));
                                                            $miercoles = date( 'd', strtotime( 'wednesday this week' ));
                                                            $jueves = date( 'd', strtotime( 'thursday this week' ));
                                                            $viernes = date( 'd', strtotime( 'friday this week' ));
                                                            $sabado = date( 'd', strtotime( 'saturday this week' ));
                                                            $domingo = date( 'd', strtotime( 'sunday this week' ));                                
                                                        ?> 

                                                        <th><?php echo $nombreLugar;?></th>                                                     
                                                        <th colspan="2">Lun <?php echo $lunesE;?></th>  
                                                        <th colspan="2">Mar <?php echo $martesE;?></th>                     
                                                        <th colspan="2">Mier <?php echo $miercolesE;?></th>                                    
                                                        <th colspan="2">Jue <?php echo $juevesE;?></th>                                                                        
                                                        <th colspan="2">Vie <?php echo $viernesE;?></th>
                                                        <th colspan="2">Sab <?php echo $sabadoE;?></th>
                                                        <th colspan="2">Dom <?php echo $domingoE;?></th>   
                                                        <th>Totales</th>                                                                                                  
                                                    </tr>
                                                </thead>

                                                <tbody>    
                                         

                                                    <tr>
                                                        <td><b>CASITAS</b></td>
                                                        <td rowspan="1"><?=$matutinosCasitasLunes;?></td>  
                                                        <td rowspan="1"><?=$vespertinosCasitasLunes;?></td>  
                                                        <td rowspan="1"><?=$matutinosCasitasMartes;?></td> 
                                                        <td rowspan="1"><?=$vespertinosCasitasMartes;?></td> 
                                                        <td rowspan="1"><?=$matutinosCasitasMiercoles;?></td>
                                                        <td rowspan="1"><?=$vespertinosCasitasMiercoles;?></td>        
                                                        <td rowspan="1"><?=$matutinosCasitasJueves;?></td>   
                                                        <td rowspan="1"><?=$vespertinosCasitasJueves;?></td>        
                                                        <td rowspan="1"><?=$matutinosCasitasViernes;?></td>
                                                        <td rowspan="1"><?=$vespertinosCasitasViernes;?></td>        
                                                        <td rowspan="1"><?=$matutinosCasitasSabado;?></td> 
                                                        <td rowspan="1"><?=$vespertinosCasitasSabado;?></td> 
                                                        <td rowspan="1"><?=$matutinosCasitasDomingo;?></td>                                     
                                                        <td rowspan="1"><?=$vespertinosCasitasDomingo;?></td> 
                                                        <td bgcolor="#d1ffaf"><b><?=$acumuladoFilaCasitas;?></b></td>                                   
                                                    </tr>   

                                                    <tr>
                                                        <td><b>MATAMOROS</b></td>
                                                        <td rowspan="1"><?=$matutinosMatamorosLunes;?></td>  
                                                        <td rowspan="1"><?=$vespertinosMatamorosLunes;?></td>  
                                                        <td rowspan="1"><?=$matutinosMatamorosMartes;?></td> 
                                                        <td rowspan="1"><?=$vespertinosMatamorosMartes;?></td> 
                                                        <td rowspan="1"><?=$matutinosMatamorosMiercoles;?></td>
                                                        <td rowspan="1"><?=$vespertinosMatamorosMiercoles;?></td>        
                                                        <td rowspan="1"><?=$matutinosMatamorosJueves;?></td>   
                                                        <td rowspan="1"><?=$vespertinosMatamorosJueves;?></td>        
                                                        <td rowspan="1"><?=$matutinosMatamorosViernes;?></td>
                                                        <td rowspan="1"><?=$vespertinosMatamorosViernes;?></td>        
                                                        <td rowspan="1"><?=$matutinosMatamorosSabado;?></td> 
                                                        <td rowspan="1"><?=$vespertinosMatamorosSabado;?></td> 
                                                        <td rowspan="1"><?=$matutinosMatamorosDomingo;?></td>                                     
                                                        <td rowspan="1"><?=$vespertinosMatamorosDomingo;?></td> 
                                                        <td bgcolor="#d1ffaf"><b><?=$acumuladoFilaMatamoros;?></b></td>                                                              
                                                    </tr> 
                                                    
                                                    <tr>
                                                        <td><b>OAXACA</b></td>
                                                        <td rowspan="1"><?=$matutinosOaxacaLunes;?></td>  
                                                        <td rowspan="1"><?=$vespertinosOaxacaLunes;?></td>  
                                                        <td rowspan="1"><?=$matutinosOaxacaMartes;?></td> 
                                                        <td rowspan="1"><?=$vespertinosOaxacaMartes;?></td> 
                                                        <td rowspan="1"><?=$matutinosOaxacaMiercoles;?></td>
                                                        <td rowspan="1"><?=$vespertinosOaxacaMiercoles;?></td>        
                                                        <td rowspan="1"><?=$matutinosOaxacaJueves;?></td>   
                                                        <td rowspan="1"><?=$vespertinosOaxacaJueves;?></td>        
                                                        <td rowspan="1"><?=$matutinosOaxacaViernes;?></td>
                                                        <td rowspan="1"><?=$vespertinosOaxacaViernes;?></td>        
                                                        <td rowspan="1"><?=$matutinosOaxacaSabado;?></td> 
                                                        <td rowspan="1"><?=$vespertinosOaxacaSabado;?></td> 
                                                        <td rowspan="1"><?=$matutinosOaxacaDomingo;?></td>                                     
                                                        <td rowspan="1"><?=$vespertinosOaxacaDomingo;?></td>                                 
                                                        <td bgcolor="#d1ffaf"><b><?=$acumuladoFilaOaxaca;?></b></td>                                                             
                                                    </tr> 

                                                    <tr>
                                                        <td><b>POZA RICA</b></td>
                                                        <td rowspan="1"><?=$matutinosPozaRicaLunes;?></td>  
                                                        <td rowspan="1"><?=$vespertinosPozaRicaLunes;?></td>  
                                                        <td rowspan="1"><?=$matutinosPozaRicaMartes;?></td> 
                                                        <td rowspan="1"><?=$vespertinosPozaRicaMartes;?></td> 
                                                        <td rowspan="1"><?=$matutinosPozaRicaMiercoles;?></td>
                                                        <td rowspan="1"><?=$vespertinosPozaRicaMiercoles;?></td>        
                                                        <td rowspan="1"><?=$matutinosPozaRicaJueves;?></td>   
                                                        <td rowspan="1"><?=$vespertinosPozaRicaJueves;?></td>        
                                                        <td rowspan="1"><?=$matutinosPozaRicaViernes;?></td>
                                                        <td rowspan="1"><?=$vespertinosPozaRicaViernes;?></td>        
                                                        <td rowspan="1"><?=$matutinosPozaRicaSabado;?></td> 
                                                        <td rowspan="1"><?=$vespertinosPozaRicaSabado;?></td> 
                                                        <td rowspan="1"><?=$matutinosPozaRicaDomingo;?></td>                                     
                                                        <td rowspan="1"><?=$vespertinosPozaRicaDomingo;?></td>                                      
                                                        <td bgcolor="#d1ffaf"><b><?=$acumuladoFilaPozaRica;?></b></td>                                                                
                                                    </tr>     
                                                    
                                                    <tr>
                                                        <td><b>REYNOSA I</b></td>
                                                        <td rowspan="1"><?=$matutinosReynosaILunes;?></td>  
                                                        <td rowspan="1"><?=$vespertinosReynosaILunes;?></td>  
                                                        <td rowspan="1"><?=$matutinosReynosaIMartes;?></td> 
                                                        <td rowspan="1"><?=$vespertinosReynosaIMartes;?></td> 
                                                        <td rowspan="1"><?=$matutinosReynosaIMiercoles;?></td>
                                                        <td rowspan="1"><?=$vespertinosReynosaIMiercoles;?></td>        
                                                        <td rowspan="1"><?=$matutinosReynosaIJueves;?></td>   
                                                        <td rowspan="1"><?=$vespertinosReynosaIJueves;?></td>        
                                                        <td rowspan="1"><?=$matutinosReynosaIViernes;?></td>
                                                        <td rowspan="1"><?=$vespertinosReynosaIViernes;?></td>        
                                                        <td rowspan="1"><?=$matutinosReynosaISabado;?></td> 
                                                        <td rowspan="1"><?=$vespertinosReynosaISabado;?></td> 
                                                        <td rowspan="1"><?=$matutinosReynosaIDomingo;?></td>                                     
                                                        <td rowspan="1"><?=$vespertinosReynosaIDomingo;?></td>                                      
                                                        <td bgcolor="#d1ffaf"><b><?=$acumuladoFilaReynosaI;?></b></td>                                                             
                                                    </tr>     

                                                    <tr>
                                                        <td><b>REYNOSA II</b></td>
                                                        <td rowspan="1"><?=$matutinosReynosaIILunes;?></td>  
                                                        <td rowspan="1"><?=$vespertinosReynosaIILunes;?></td>  
                                                        <td rowspan="1"><?=$matutinosReynosaIIMartes;?></td> 
                                                        <td rowspan="1"><?=$vespertinosReynosaIIMartes;?></td> 
                                                        <td rowspan="1"><?=$matutinosReynosaIIMiercoles;?></td>
                                                        <td rowspan="1"><?=$vespertinosReynosaIIMiercoles;?></td>        
                                                        <td rowspan="1"><?=$matutinosReynosaIIJueves;?></td>   
                                                        <td rowspan="1"><?=$vespertinosReynosaIIJueves;?></td>        
                                                        <td rowspan="1"><?=$matutinosReynosaIIViernes;?></td>
                                                        <td rowspan="1"><?=$vespertinosReynosaIIViernes;?></td>        
                                                        <td rowspan="1"><?=$matutinosReynosaIISabado;?></td> 
                                                        <td rowspan="1"><?=$vespertinosReynosaIISabado;?></td> 
                                                        <td rowspan="1"><?=$matutinosReynosaIIDomingo;?></td>                                     
                                                        <td rowspan="1"><?=$vespertinosReynosaIIDomingo;?></td>                                    
                                                        <td bgcolor="#d1ffaf"><b><?=$acumuladoFilaReynosaII;?></b></td>                                                             
                                                    </tr>     

                                                    <tr>
                                                        <td><b>TEZIUTLÁN</b></td>
                                                        <td rowspan="1"><?=$matutinosTeziutlanLunes;?></td>  
                                                        <td rowspan="1"><?=$vespertinosTeziutlanLunes;?></td>  
                                                        <td rowspan="1"><?=$matutinosTeziutlanMartes;?></td> 
                                                        <td rowspan="1"><?=$vespertinosTeziutlanMartes;?></td> 
                                                        <td rowspan="1"><?=$matutinosTeziutlanMiercoles;?></td>
                                                        <td rowspan="1"><?=$vespertinosTeziutlanMiercoles;?></td>        
                                                        <td rowspan="1"><?=$matutinosTeziutlanJueves;?></td>   
                                                        <td rowspan="1"><?=$vespertinosTeziutlanJueves;?></td>        
                                                        <td rowspan="1"><?=$matutinosTeziutlanViernes;?></td>
                                                        <td rowspan="1"><?=$vespertinosTeziutlanViernes;?></td>        
                                                        <td rowspan="1"><?=$matutinosTeziutlanSabado;?></td> 
                                                        <td rowspan="1"><?=$vespertinosTeziutlanSabado;?></td> 
                                                        <td rowspan="1"><?=$matutinosTeziutlanDomingo;?></td>                                     
                                                        <td rowspan="1"><?=$vespertinosTeziutlanDomingo;?></td>                                     
                                                        <td bgcolor="#d1ffaf"><b><?=$acumuladoFilaTeziutlan;?></b></td>                                                             
                                                    </tr>

                                                    <tr>
                                                        <td bgcolor="#b3edff"><b>Subtotal</b></td>
                                                        <td bgcolor="#ffe9b5" rowspan="1"><b><?=$acumuladoColumnaLunesMatutino;?></b></td>  
                                                        <td bgcolor="#ffe9b5" rowspan="1"><b><?=$acumuladoColumnaLunesVespertino;?></b></td>  
                                                        <td bgcolor="#ffe9b5" rowspan="1"><b><?=$acumuladoColumnaMartesMatutino;?></b></td>  
                                                        <td bgcolor="#ffe9b5" rowspan="1"><b><?=$acumuladoColumnaMartesVespertino;?></b></td> 
                                                        <td bgcolor="#ffe9b5" rowspan="1"><b><?=$acumuladoColumnaMiercolesMatutino;?></b></td>  
                                                        <td bgcolor="#ffe9b5" rowspan="1"><b><?=$acumuladoColumnaMiercolesVespertino;?></b></td> 
                                                        <td bgcolor="#ffe9b5" rowspan="1"><b><?=$acumuladoColumnaJuevesMatutino;?></b></td>  
                                                        <td bgcolor="#ffe9b5" rowspan="1"><b><?=$acumuladoColumnaJuevesVespertino;?></b></td> 
                                                        <td bgcolor="#ffe9b5" rowspan="1"><b><?=$acumuladoColumnaViernesMatutino;?></b></td>  
                                                        <td bgcolor="#ffe9b5" rowspan="1"><b><?=$acumuladoColumnaViernesVespertino;?></b></td> 
                                                        <td bgcolor="#ffe9b5" rowspan="1"><b><?=$acumuladoColumnaSabadoMatutino;?></b></td>  
                                                        <td bgcolor="#ffe9b5" rowspan="1"><b><?=$acumuladoColumnaSabadoVespertino;?></b></td> 
                                                        <td bgcolor="#ffe9b5" rowspan="1"><b><?=$acumuladoColumnaDomingoMatutino;?></b></td>  
                                                        <td bgcolor="#ffe9b5" rowspan="1"><b><?=$acumuladoColumnaDomingoVespertino;?></b></td>                                 
                                                        <td bgcolor="#d1ffaf"></td>  
                                                    </tr>

                                                    <tr>
                                                        <td bgcolor="#b3edff"><b>Total</b></td>
                                                       <td bgcolor="#d1ffaf" colspan="2"><b><?=$acumuladoTotalColumnaLunes;?></b></td>
                                                       <td bgcolor="#d1ffaf" colspan="2"><b><?=$acumuladoTotalColumnaMartes;?></b></td>
                                                       <td bgcolor="#d1ffaf" colspan="2"><b><?=$acumuladoTotalColumnaMiercoles;?></b></td>
                                                       <td bgcolor="#d1ffaf" colspan="2"><b><?=$acumuladoTotalColumnaJueves;?></b></td>
                                                       <td bgcolor="#d1ffaf" colspan="2"><b><?=$acumuladoTotalColumnaViernes;?></b></td>
                                                       <td bgcolor="#d1ffaf" colspan="2"><b><?=$acumuladoTotalColumnaSabado;?></b></td>
                                                       <td bgcolor="#d1ffaf" colspan="2"><b><?=$acumuladoTotalColumnaDomingo;?></b></td>                                                                       
                                                       <td><b><?=$acumuladoTotalSemanaRol;?></b></td>                                                          
                                                    </tr>                                                                                                                   

                                                </tbody>                            
                                            </table>
                                        <!--TODAS LAS PLAZAS SELECCIONADAS UN LUGAR  DE LA CAFETERIA-->
                                        <?php break; ?>

                                        <?php case 3: ?>                                        
                                        <!--TODAS LAS PLAZAS SELECCIONADAS UN LUGAR  DE LA CAFETERIA-->
                                            <table class="display responsive table table-bordered  btn-hover nowrap" style="width:100%" id="tbl_usuarios_12">
                                                    
                                                    <!--VARIABLES INICIO DEL PROYECTO-->
                                                        <?php 
                                                        
                                                            $acLunesBarraM="";
                                                            $acLunesBarraV="";
                                                            $acMartesBarraM="";
                                                            $acMartesBarraV="";
                                                            $acMiercolesBarraM="";
                                                            $acMiercolesBarraV="";
                                                            $acJuevesBarraM="";
                                                            $acJuevesBarraV="";
                                                            $acViernesBarraM="";
                                                            $acViernesBarraV="";
                                                            $acSabadoBarraM="";
                                                            $acSabadoBarraV="";
                                                            $acDomingoBarraM="";
                                                            $acDomingoBarraV="";

                                                            $acLunesMesasM="";
                                                            $acLunesMesasV="";
                                                            $acMartesMesasM="";
                                                            $acMartesMesasV="";
                                                            $acMiercolesMesasM="";
                                                            $acMiercolesMesasV="";
                                                            $acJuevesMesasM="";
                                                            $acJuevesMesasV="";
                                                            $acViernesMesasM="";
                                                            $acViernesMesasV="";
                                                            $acSabadoMesasM="";
                                                            $acSabadoMesasV="";
                                                            $acDomingoMesasM="";
                                                            $acDomingoMesasV="";

                                                            $acLunesCocinaM="";
                                                            $acLunesCocinaV="";
                                                            $acMartesCocinaM="";
                                                            $acMartesCocinaV="";
                                                            $acMiercolesCocinaM="";
                                                            $acMiercolesCocinaV="";
                                                            $acJuevesCocinaM="";
                                                            $acJuevesCocinaV="";
                                                            $acViernesCocinaM="";
                                                            $acViernesCocinaV="";
                                                            $acSabadoCocinaM="";
                                                            $acSabadoCocinaV="";
                                                            $acDomingoCocinaM="";
                                                            $acDomingoCocinaV="";

                                                            $acLunesApoyoM="";
                                                            $acLunesApoyoV="";
                                                            $acMartesApoyoM="";
                                                            $acMartesApoyoV="";
                                                            $acMiercolesApoyoM="";
                                                            $acMiercolesApoyoV="";
                                                            $acJuevesApoyoM="";
                                                            $acJuevesApoyoV="";
                                                            $acViernesApoyoM="";
                                                            $acViernesApoyoV="";
                                                            $acSabadoApoyoM="";
                                                            $acSabadoApoyoV="";
                                                            $acDomingoApoyoM="";
                                                            $acDomingoApoyoV="";
                                                            
                                                            $acMartesM="";
                                                            $acMiercolesM="";
                                                            $acJuevesM="";
                                                            $acViernesM="";
                                                            $acSabadoM="";
                                                            $acDomingoM="";
                                                            
                                                            $acLunesV="";
                                                            $acMartesV="";
                                                            $acMiercolesV="";
                                                            $acJuevesV="";
                                                            $acViernesV="";
                                                            $acSabadoV="";
                                                            $acDomingoV="";
                                                        ?>
                                                
                                                    <thead>
                                                        <tr>       

                                                            <?php 
                                                                $lunes = date( 'd', strtotime( 'monday this week' ));
                                                                $martes = date( 'd', strtotime( 'tuesday this week' ));
                                                                $miercoles = date( 'd', strtotime( 'wednesday this week' ));
                                                                $jueves = date( 'd', strtotime( 'thursday this week' ));
                                                                $viernes = date( 'd', strtotime( 'friday this week' ));
                                                                $sabado = date( 'd', strtotime( 'saturday this week' ));
                                                                $domingo = date( 'd', strtotime( 'sunday this week' ));                                
                                                            ?> 

                                                            <th><?php echo $nombreLugar;?></th>                                                     
                                                            <th>Lun <?php echo $lunesE;?></th>  
                                                            <th>Mar <?php echo $martesE;?></th>                     
                                                            <th>Mier <?php echo $miercolesE;?></th>                                    
                                                            <th>Jue <?php echo $juevesE;?></th>                                                                        
                                                            <th>Vie <?php echo $viernesE;?></th>
                                                            <th>Sab <?php echo $sabadoE;?></th>
                                                            <th>Dom <?php echo $domingoE;?></th>   
                                                            <th>Totales</th>                                                                                                  
                                                        </tr>
                                                    </thead>

                                                    <tbody>    
                                            

                                                        <tr>
                                                            <td><b>CASITAS</b></td>
                                                            <td rowspan="1"><?=$CasitasLunes;?></td>  
                                                            <td rowspan="1"><?=$CasitasMartes;?></td> 
                                                            <td rowspan="1"><?=$CasitasMiercoles;?></td>  
                                                            <td rowspan="1"><?=$CasitasJueves;?></td>  
                                                            <td rowspan="1"><?=$CasitasViernes;?></td>  
                                                            <td rowspan="1"><?=$CasitasSabado;?></td>  
                                                            <td rowspan="1"><?=$CasitasDomingo;?></td>   
                                                            <td bgcolor="#d1ffaf"><b><?=$acumuladoFilaCasitasTurnoLugar;?><b></td>                                   
                                                        </tr>   

                                                        <tr>
                                                            <td><b>MATAMOROS</b></td>
                                                            <td rowspan="1"><?=$MatamorosLunes;?></td>  
                                                            <td rowspan="1"><?=$MatamorosMartes;?></td> 
                                                            <td rowspan="1"><?=$MatamorosMiercoles;?></td>  
                                                            <td rowspan="1"><?=$MatamorosJueves;?></td>  
                                                            <td rowspan="1"><?=$MatamorosViernes;?></td>  
                                                            <td rowspan="1"><?=$MatamorosSabado;?></td>  
                                                            <td rowspan="1"><?=$MatamorosDomingo;?></td>                                                      
                                                            <td bgcolor="#d1ffaf"><b><?=$acumuladoFilaMatamorosTurnoLugar;?><b></td>                                  
                                                        </tr> 
                                                        
                                                        <tr>
                                                            <td><b>OAXACA</b></td>
                                                            <td rowspan="1"><?=$OaxacaLunes;?></td>  
                                                            <td rowspan="1"><?=$OaxacaMartes;?></td> 
                                                            <td rowspan="1"><?=$OaxacaMiercoles;?></td>  
                                                            <td rowspan="1"><?=$OaxacaJueves;?></td>  
                                                            <td rowspan="1"><?=$OaxacaViernes;?></td>  
                                                            <td rowspan="1"><?=$OaxacaSabado;?></td>  
                                                            <td rowspan="1"><?=$OaxacaDomingo;?></td>                                                      
                                                            <td bgcolor="#d1ffaf"><b><?=$acumuladoFilaOaxacaTurnoLugar;?><b></td>                                  
                                                        </tr> 

                                                        <tr>
                                                            <td><b>POZA RICA</b></td>
                                                            <td rowspan="1"><?=$PozaRicaLunes;?></td>  
                                                            <td rowspan="1"><?=$PozaRicaMartes;?></td> 
                                                            <td rowspan="1"><?=$PozaRicaMiercoles;?></td>  
                                                            <td rowspan="1"><?=$PozaRicaJueves;?></td>  
                                                            <td rowspan="1"><?=$PozaRicaViernes;?></td>  
                                                            <td rowspan="1"><?=$PozaRicaSabado;?></td>  
                                                            <td rowspan="1"><?=$PozaRicaDomingo;?></td>                                                                                                                                          
                                                            <td bgcolor="#d1ffaf"><b><?=$acumuladoFilaPozaRicaTurnoLugar;?><b></td>                                                                                
                                                        </tr>     
                                                        
                                                        <tr>
                                                            <td><b>REYNOSA I</b></td>
                                                            <td rowspan="1"><?=$ReynosaILunes;?></td>  
                                                            <td rowspan="1"><?=$ReynosaIMartes;?></td> 
                                                            <td rowspan="1"><?=$ReynosaIMiercoles;?></td>  
                                                            <td rowspan="1"><?=$ReynosaIJueves;?></td>  
                                                            <td rowspan="1"><?=$ReynosaIViernes;?></td>  
                                                            <td rowspan="1"><?=$ReynosaISabado;?></td>  
                                                            <td rowspan="1"><?=$ReynosaIDomingo;?></td>                                                                                            
                                                            <td bgcolor="#d1ffaf"><b><?=$acumuladoFilaReynosaITurnoLugar;?><b></td>                                                                            
                                                        </tr>     

                                                        <tr>
                                                            <td><b>REYNOSA II</b></td>
                                                            <td rowspan="1"><?=$ReynosaIILunes;?></td>  
                                                            <td rowspan="1"><?=$ReynosaIIMartes;?></td> 
                                                            <td rowspan="1"><?=$ReynosaIIMiercoles;?></td>  
                                                            <td rowspan="1"><?=$ReynosaIIJueves;?></td>  
                                                            <td rowspan="1"><?=$ReynosaIIViernes;?></td>  
                                                            <td rowspan="1"><?=$ReynosaIISabado;?></td>  
                                                            <td rowspan="1"><?=$ReynosaIIDomingo;?></td>                                                                                           
                                                            <td bgcolor="#d1ffaf"><b><?=$acumuladoFilaReynosaIITurnoLugar;?><b></td>                                                                           
                                                        </tr>     

                                                        <tr>
                                                            <td><b>TEZIUTLÁN</b></td>
                                                            <td rowspan="1"><?=$TeziutlanLunes;?></td>  
                                                            <td rowspan="1"><?=$TeziutlanMartes;?></td> 
                                                            <td rowspan="1"><?=$TeziutlanMiercoles;?></td>  
                                                            <td rowspan="1"><?=$TeziutlanJueves;?></td>  
                                                            <td rowspan="1"><?=$TeziutlanViernes;?></td>  
                                                            <td rowspan="1"><?=$TeziutlanSabado;?></td>  
                                                            <td rowspan="1"><?=$TeziutlanDomingo;?></td>                                                                                    
                                                            <td bgcolor="#d1ffaf"><b><?=$acumuladoFilaTeziutlanTurnoLugar;?><b></td>                                                                           
                                                        </tr>                                                    
                                                        <tr>
                                                            <td bgcolor="#b3edff"><b>Total</b></td>
                                                            <td bgcolor="#d1ffaf"><b><?=$acumuladoTotalColumnaLunesTurnoLugar;?></b></td>
                                                            <td bgcolor="#d1ffaf"><b><?=$acumuladoTotalColumnaMartesTurnoLugar;?></b></td>
                                                            <td bgcolor="#d1ffaf"><b><?=$acumuladoTotalColumnaMiercolesTurnoLugar;?></b></td>
                                                            <td bgcolor="#d1ffaf"><b><?=$acumuladoTotalColumnaJuevesTurnoLugar;?></b></td>
                                                            <td bgcolor="#d1ffaf"><b><?=$acumuladoTotalColumnaViernesTurnoLugar;?></b></td>
                                                            <td bgcolor="#d1ffaf"><b><?=$acumuladoTotalColumnaSabadoTurnoLugar;?></b></td>
                                                            <td bgcolor="#d1ffaf"><b><?=$acumuladoTotalColumnaDomingoTurnoLugar;?></b></td>                                                                                                                                                                                                                                                                                                                                  
                                                            <td bgcolor="#d1ffaf"><b><?=$acumuladoTotalColumnaSemanaTurnoLugar;?></b></td>
                                                        </tr>                                                                                                                   

                                                    </tbody>                            
                                            </table>
                                        <!--TODAS LAS PLAZAS SELECCIONADAS UN LUGAR  DE LA CAFETERIA-->
                                        <?php break; ?>  

                                        <?php case 4: ?>
                                        <!--UNA PLAZA SELECCIONADA TODOS LOS LUGARES DE LA CAFETERIA-->
                                            
                                            <table class="display responsive table table-bordered  btn-hover nowrap" style="width:100%" id="tbl_usuarios_5">
                                            
                                                <!--VARIABLES INICIO DEL PROYECTO-->
                                                    <?php                                                     
                                                        $acLunesBarraM="";  
                                                        $acLunesBarraV="";  
                                                        $acMartesBarraM=""; 
                                                        $acMartesBarraV=""; 
                                                        $acMiercolesBarraM="";
                                                        $acMiercolesBarraV="";        
                                                        $acJuevesBarraM="";   
                                                        $acJuevesBarraV="";        
                                                        $acViernesBarraM="";
                                                        $acViernesBarraV="";        
                                                        $acSabadoBarraM=""; 
                                                        $acSabadoBarraV=""; 
                                                        $acDomingoBarraM="";                                     
                                                        $acDomingoBarraV=""; 
                                                        
                                                        $acLunesMesasM="";
                                                        $acLunesMesasV="";
                                                        $acMartesMesasM="";
                                                        $acMartesMesasV="";
                                                        $acMiercolesMesasM="";
                                                        $acMiercolesMesasV="";
                                                        $acJuevesMesasM="";
                                                        $acJuevesMesasV="";
                                                        $acViernesMesasM="";
                                                        $acViernesMesasV="";
                                                        $acSabadoMesasM="";
                                                        $acSabadoMesasV="";
                                                        $acDomingoMesasM="";
                                                        $acDomingoMesasV="";
                                                        
                                                                        
                                                        $acLunesCocinaM="";
                                                        $acLunesCocinaV="";
                                                        $acMartesCocinaM="";
                                                        $acMartesCocinaV="";
                                                        $acMiercolesCocinaM="";
                                                        $acMiercolesCocinaV="";
                                                        $acJuevesCocinaM="";
                                                        $acJuevesCocinaV="";
                                                        $acViernesCocinaM="";
                                                        $acViernesCocinaV="";
                                                        $acSabadoCocinaM="";
                                                        $acSabadoCocinaV="";
                                                        $acDomingoCocinaM="";
                                                        $acDomingoCocinaV="";
                                                                                                
                                                        $acLunesApoyoM="";
                                                        $acLunesApoyoV="";
                                                        $acMartesApoyoM="";
                                                        $acMartesApoyoV="";
                                                        $acMiercolesApoyoM="";
                                                        $acMiercolesApoyoV="";
                                                        $acJuevesApoyoM="";
                                                        $acJuevesApoyoV="";
                                                        $acViernesApoyoM="";
                                                        $acViernesApoyoV="";
                                                        $acSabadoApoyoM="";
                                                        $acSabadoApoyoV="";
                                                        $acDomingoApoyoM="";
                                                        $acDomingoApoyoV="";
                                                    ?>
                                            
                                                <thead>
                                                    <tr>       

                                                        <?php 
                                                            $lunes = date( 'd', strtotime( 'monday this week' ));
                                                            $martes = date( 'd', strtotime( 'tuesday this week' ));
                                                            $miercoles = date( 'd', strtotime( 'wednesday this week' ));
                                                            $jueves = date( 'd', strtotime( 'thursday this week' ));
                                                            $viernes = date( 'd', strtotime( 'friday this week' ));
                                                            $sabado = date( 'd', strtotime( 'saturday this week' ));
                                                            $domingo = date( 'd', strtotime( 'sunday this week' ));                                
                                                        ?> 
                                                               
                                                        <th>PLAZA</th>      
                                                        <th>TURNO</th>                                                     
                                                        <th colspan="2">Lun <?php echo $lunesE;?></th>  
                                                        <th colspan="2">Mar <?php echo $martesE;?></th>                     
                                                        <th colspan="2">Mier <?php echo $miercolesE;?></th>                                    
                                                        <th colspan="2">Jue <?php echo $juevesE;?></th>                                                                        
                                                        <th colspan="2">Vie <?php echo $viernesE;?></th>
                                                        <th colspan="2">Sab <?php echo $sabadoE;?></th>
                                                        <th colspan="2">Dom <?php echo $domingoE;?></th>   
                                                        <th>Totales</th>                                                                                                  
                                                    </tr>
                                                </thead>
                                                <tbody>                                                       
                                                   
                                                <!--FOR(S) GENERICO-->  
                                                    <?php if(!empty($matutinosBarra)):?>                           
                                                        <?php foreach ($matutinosBarra as $matutinoBarra):?>
                                                            <?php switch ($matutinoBarra->dia_id) {
                                                                case 1;
                                                                    $acLunesBarraM=$acLunesBarraM."".$matutinoBarra->Nombre_Rol."<br>";                                        
                                                                break;

                                                                case 2;                                 
                                                                    $acMartesBarraM=$acMartesBarraM."".$matutinoBarra->Nombre_Rol."<br>";       
                                                                break;

                                                                case 3;                                        
                                                                    $acMiercolesBarraM=$acMiercolesBarraM."".$matutinoBarra->Nombre_Rol."<br>";
                                                                break;

                                                                case 4;                                        
                                                                    $acJuevesBarraM=$acJuevesBarraM."".$matutinoBarra->Nombre_Rol."<br>";
                                                                break;

                                                                case 5;                                        
                                                                    $acViernesBarraM=$acViernesBarraM."".$matutinoBarra->Nombre_Rol."<br>";
                                                                break;

                                                                case 6;   
                                                                    $acSabadoBarraM=$acSabadoBarraM."".$matutinoBarra->Nombre_Rol."<br>";                                     
                                                                break;

                                                                case 7;                                        
                                                                    $acDomingoBarraM=$acDomingoBarraM."".$matutinoBarra->Nombre_Rol."<br>";
                                                                break;                                                                        
                                                            }?>                              
                                                        <?php endforeach;?>                                                              
                                                    <?php endif;?>  

                                                    <?php if(!empty($vespertinosBarra)):?>                           
                                                        <?php foreach ($vespertinosBarra as $vespertinoBarra):?>
                                                            <?php switch ($vespertinoBarra->dia_id) {
                                                                case 1;
                                                                    $acLunesBarraV=$acLunesBarraV."".$vespertinoBarra->Nombre_Rol."<br>";                                        
                                                                break;

                                                                case 2;                                 
                                                                    $acMartesBarraV=$acMartesBarraV."".$vespertinoBarra->Nombre_Rol."<br>";       
                                                                break;

                                                                case 3;                                        
                                                                    $acMiercolesBarraV=$acMiercolesBarraV."".$vespertinoBarra->Nombre_Rol."<br>";
                                                                break;

                                                                case 4;                                        
                                                                    $acJuevesBarraV=$acJuevesBarraV."".$vespertinoBarra->Nombre_Rol."<br>";
                                                                break;

                                                                case 5;                                        
                                                                    $acViernesBarraV=$acViernesBarraV."".$vespertinoBarra->Nombre_Rol."<br>";
                                                                break;

                                                                case 6;   
                                                                    $acSabadoBarraV=$acSabadoBarraV."".$vespertinoBarra->Nombre_Rol."<br>";                                     
                                                                break;

                                                                case 7;                                        
                                                                    $acDomingoBarraV=$acDomingoBarraV."".$vespertinoBarra->Nombre_Rol."<br>";
                                                                break;                                                                        
                                                            }?>                              
                                                        <?php endforeach;?>                                                              
                                                    <?php endif;?>  
                                    
                                                    <?php if(!empty($matutinosMesas)):?>                           
                                                        <?php foreach ($matutinosMesas as $matutinoMesas):?>
                                                            <?php switch ($matutinoMesas->dia_id) {
                                                                case 1;
                                                                    $acLunesMesasM=$acLunesMesasM."".$matutinoMesas->Nombre_Rol."<br>";                                        
                                                                break;

                                                                case 2;                                 
                                                                    $acMartesMesasM=$acMartesMesasM."".$matutinoMesas->Nombre_Rol."<br>";       
                                                                break;

                                                                case 3;                                        
                                                                    $acMiercolesMesasM=$acMiercolesMesasM."".$matutinoMesas->Nombre_Rol."<br>";
                                                                break;

                                                                case 4;                                        
                                                                    $acJuevesMesasM=$acJuevesMesasM."".$matutinoMesas->Nombre_Rol."<br>";
                                                                break;

                                                                case 5;                                        
                                                                    $acViernesMesasM=$acViernesMesasM."".$matutinoMesas->Nombre_Rol."<br>";
                                                                break;

                                                                case 6;   
                                                                    $acSabadoMesasM=$acSabadoMesasM."".$matutinoMesas->Nombre_Rol."<br>";                                     
                                                                break;

                                                                case 7;                                        
                                                                    $acDomingoMesasM=$acDomingoMesasM."".$matutinoMesas->Nombre_Rol."<br>";
                                                                break;                                                                        
                                                            }?>                              
                                                        <?php endforeach;?>                                                              
                                                    <?php endif;?>  

                                                    <?php if(!empty($vespertinosMesas)):?>                           
                                                        <?php foreach ($vespertinosMesas as $vespertinoMesas):?>
                                                            <?php switch ($vespertinoMesas->dia_id) {
                                                                case 1;
                                                                    $acLunesMesasV=$acLunesMesasV."".$vespertinoMesas->Nombre_Rol."<br>";                                        
                                                                break;

                                                                case 2;                                 
                                                                    $acMartesMesasV=$acMartesMesasV."".$vespertinoMesas->Nombre_Rol."<br>";       
                                                                break;

                                                                case 3;                                        
                                                                    $acMiercolesMesasV=$acMiercolesMesasV."".$vespertinoMesas->Nombre_Rol."<br>";
                                                                break;

                                                                case 4;                                        
                                                                    $acJuevesMesasV=$acJuevesMesasV."".$vespertinoMesas->Nombre_Rol."<br>";
                                                                break;

                                                                case 5;                                        
                                                                    $acViernesMesasV=$acViernesMesasV."".$vespertinoMesas->Nombre_Rol."<br>";
                                                                break;

                                                                case 6;   
                                                                    $acSabadoMesasV=$acSabadoMesasV."".$vespertinoMesas->Nombre_Rol."<br>";                                     
                                                                break;

                                                                case 7;                                        
                                                                    $acDomingoMesasV=$acDomingoMesasV."".$vespertinoMesas->Nombre_Rol."<br>";
                                                                break;                                                                        
                                                            }?>                              
                                                        <?php endforeach;?>                                                              
                                                    <?php endif;?>  

                                                    <?php if(!empty($matutinosCocina)):?>                           
                                                        <?php foreach ($matutinosCocina as $matutinoCocina):?>
                                                            <?php switch ($matutinoCocina->dia_id) {
                                                                case 1;
                                                                    $acLunesCocinaM=$acLunesCocinaM."".$matutinoCocina->Nombre_Rol."<br>";                                        
                                                                break;

                                                                case 2;                                 
                                                                    $acMartesCocinaM=$acMartesCocinaM."".$matutinoCocina->Nombre_Rol."<br>";       
                                                                break;

                                                                case 3;                                        
                                                                    $acMiercolesCocinaM=$acMiercolesCocinaM."".$matutinoCocina->Nombre_Rol."<br>";
                                                                break;

                                                                case 4;                                        
                                                                    $acJuevesCocinaM=$acJuevesCocinaM."".$matutinoCocina->Nombre_Rol."<br>";
                                                                break;

                                                                case 5;                                        
                                                                    $acViernesCocinaM=$acViernesCocinaM."".$matutinoCocina->Nombre_Rol."<br>";
                                                                break;

                                                                case 6;   
                                                                    $acSabadoCocinaM=$acSabadoCocinaM."".$matutinoCocina->Nombre_Rol."<br>";                                     
                                                                break;

                                                                case 7;                                        
                                                                    $acDomingoCocinaM=$acDomingoCocinaM."".$matutinoCocina->Nombre_Rol."<br>";
                                                                break;                                                                        
                                                            }?>                              
                                                        <?php endforeach;?>                                                              
                                                    <?php endif;?> 

                                                    <?php if(!empty($vespertinosCocina)):?>                           
                                                        <?php foreach ($vespertinosCocina as $vespertinoCocina):?>
                                                            <?php switch ($vespertinoCocina->dia_id) {
                                                                case 1;
                                                                    $acLunesCocinaV=$acLunesCocinaV."".$vespertinoCocina->Nombre_Rol."<br>";                                        
                                                                break;

                                                                case 2;                                 
                                                                    $acMartesCocinaV=$acMartesCocinaV."".$vespertinoCocina->Nombre_Rol."<br>";       
                                                                break;

                                                                case 3;                                        
                                                                    $acMiercolesCocinaV=$acMiercolesCocinaV."".$vespertinoCocina->Nombre_Rol."<br>";
                                                                break;

                                                                case 4;                                        
                                                                    $acJuevesCocinaV=$acJuevesCocinaV."".$vespertinoCocina->Nombre_Rol."<br>";
                                                                break;

                                                                case 5;                                        
                                                                    $acViernesCocinaV=$acViernesCocinaV."".$vespertinoCocina->Nombre_Rol."<br>";
                                                                break;

                                                                case 6;   
                                                                    $acSabadoCocinaV=$acSabadoCocinaV."".$vespertinoCocina->Nombre_Rol."<br>";                                     
                                                                break;

                                                                case 7;                                        
                                                                    $acDomingoCocinaV=$acDomingoCocinaV."".$vespertinoCocina->Nombre_Rol."<br>";
                                                                break;                                                                        
                                                            }?>                              
                                                        <?php endforeach;?>                                                              
                                                    <?php endif;?>  

                                                    <?php if(!empty($matutinosApoyo)):?>                           
                                                        <?php foreach ($matutinosApoyo as $matutinoApoyo):?>
                                                            <?php switch ($matutinoApoyo->dia_id) {
                                                                case 1;
                                                                    $acLunesApoyoM=$acLunesApoyoM."".$matutinoApoyo->Nombre_Rol."<br>";                                        
                                                                break;

                                                                case 2;                                 
                                                                    $acMartesApoyoM=$acMartesApoyoM."".$matutinoApoyo->Nombre_Rol."<br>";       
                                                                break;

                                                                case 3;                                        
                                                                    $acMiercolesApoyoM=$acMiercolesApoyoM."".$matutinoApoyo->Nombre_Rol."<br>";
                                                                break;

                                                                case 4;                                        
                                                                    $acJuevesApoyoM=$acJuevesApoyoM."".$matutinoApoyo->Nombre_Rol."<br>";
                                                                break;

                                                                case 5;                                        
                                                                    $acViernesApoyoM=$acViernesApoyoM."".$matutinoApoyo->Nombre_Rol."<br>";
                                                                break;

                                                                case 6;   
                                                                    $acSabadoApoyoM=$acSabadoApoyoM."".$matutinoApoyo->Nombre_Rol."<br>";                                     
                                                                break;

                                                                case 7;                                        
                                                                    $acDomingoApoyoM=$acDomingoApoyoM."".$matutinoApoyo->Nombre_Rol."<br>";
                                                                break;                                                                        
                                                            }?>                              
                                                        <?php endforeach;?>                                                              
                                                    <?php endif;?> 

                                                    <?php if(!empty($vespertinosApoyo)):?>                           
                                                        <?php foreach ($vespertinosApoyo as $vespertinoApoyo):?>
                                                            <?php switch ($vespertinoApoyo->dia_id) {
                                                                case 1;
                                                                    $acLunesApoyoV=$acLunesApoyoV."".$vespertinoApoyo->Nombre_Rol."<br>";                                        
                                                                break;

                                                                case 2;                                 
                                                                    $acMartesApoyoV=$acMartesApoyoV."".$vespertinoApoyo->Nombre_Rol."<br>";       
                                                                break;

                                                                case 3;                                        
                                                                    $acMiercolesApoyoV=$acMiercolesApoyoV."".$vespertinoApoyo->Nombre_Rol."<br>";
                                                                break;

                                                                case 4;                                        
                                                                    $acJuevesApoyoV=$acJuevesApoyoV."".$vespertinoApoyo->Nombre_Rol."<br>";
                                                                break;

                                                                case 5;                                        
                                                                    $acViernesApoyoV=$acViernesApoyoV."".$vespertinoApoyo->Nombre_Rol."<br>";
                                                                break;

                                                                case 6;   
                                                                    $acSabadoApoyoV=$acSabadoApoyoV."".$vespertinoApoyo->Nombre_Rol."<br>";                                     
                                                                break;

                                                                case 7;                                        
                                                                    $acDomingoApoyoV=$acDomingoApoyoV."".$vespertinoApoyo->Nombre_Rol."<br>";
                                                                break;                                                                        
                                                            }?>                              
                                                        <?php endforeach;?>                                                              
                                                    <?php endif;?>
                                                <!--FOR(S) GENERICO-->  



                                                    <tr>
                                                        <td><?=$nombrePlaza;?></td>  
                                                        <td>BARRA</td>
                                                        <td rowspan="1"><?=$acLunesBarraM;?></td>  
                                                        <td rowspan="1"><?=$acLunesBarraV;?></td>  
                                                        <td rowspan="1"><?=$acMartesBarraM;?></td> 
                                                        <td rowspan="1"><?=$acMartesBarraV;?></td> 
                                                        <td rowspan="1"><?=$acMiercolesBarraM;?></td>
                                                        <td rowspan="1"><?=$acMiercolesBarraV;?></td>        
                                                        <td rowspan="1"><?=$acJuevesBarraM;?></td>   
                                                        <td rowspan="1"><?=$acJuevesBarraV;?></td>        
                                                        <td rowspan="1"><?=$acViernesBarraM;?></td>
                                                        <td rowspan="1"><?=$acViernesBarraV;?></td>        
                                                        <td rowspan="1"><?=$acSabadoBarraM;?></td> 
                                                        <td rowspan="1"><?=$acSabadoBarraV;?></td> 
                                                        <td rowspan="1"><?=$acDomingoBarraM;?></td>                                     
                                                        <td rowspan="1"><?=$acDomingoBarraV;?></td> 
                                                        <td bgcolor="#d1ffaf"><b><?=$acBarra;?></b></td>                                   
                                                    </tr>   

                                                    <tr>
                                                        <td><?=$nombrePlaza;?></td>  
                                                        <td>MESAS</td>
                                                        <td rowspan="1"><?=$acLunesMesasM;?></td>  
                                                        <td rowspan="1"><?=$acLunesMesasV;?></td>  
                                                        <td rowspan="1"><?=$acMartesMesasM;?></td> 
                                                        <td rowspan="1"><?=$acMartesMesasV;?></td> 
                                                        <td rowspan="1"><?=$acMiercolesMesasM;?></td>
                                                        <td rowspan="1"><?=$acMiercolesMesasV;?></td>        
                                                        <td rowspan="1"><?=$acJuevesMesasM;?></td>   
                                                        <td rowspan="1"><?=$acJuevesMesasV;?></td>        
                                                        <td rowspan="1"><?=$acViernesMesasM;?></td>
                                                        <td rowspan="1"><?=$acViernesMesasV;?></td>        
                                                        <td rowspan="1"><?=$acSabadoMesasM;?></td> 
                                                        <td rowspan="1"><?=$acSabadoMesasV;?></td> 
                                                        <td rowspan="1"><?=$acDomingoMesasM;?></td>                                     
                                                        <td rowspan="1"><?=$acDomingoMesasV;?></td>  
                                                        <td bgcolor="#d1ffaf"><b><?=$acMesas;?></b></td>                       
                                                    </tr> 
                                                    
                                                    <tr>
                                                    <td><?=$nombrePlaza;?></td>   
                                                        <td>COCINA</td>
                                                        <td rowspan="1"><?=$acLunesCocinaM;?></td>  
                                                        <td rowspan="1"><?=$acLunesCocinaV;?></td>  
                                                        <td rowspan="1"><?=$acMartesCocinaM;?></td> 
                                                        <td rowspan="1"><?=$acMartesCocinaV;?></td> 
                                                        <td rowspan="1"><?=$acMiercolesCocinaM;?></td>
                                                        <td rowspan="1"><?=$acMiercolesCocinaV;?></td>        
                                                        <td rowspan="1"><?=$acJuevesCocinaM;?></td>   
                                                        <td rowspan="1"><?=$acJuevesCocinaV;?></td>        
                                                        <td rowspan="1"><?=$acViernesCocinaM;?></td>
                                                        <td rowspan="1"><?=$acViernesCocinaV;?></td>        
                                                        <td rowspan="1"><?=$acSabadoCocinaM;?></td> 
                                                        <td rowspan="1"><?=$acSabadoCocinaV;?></td> 
                                                        <td rowspan="1"><?=$acDomingoCocinaM;?></td>                                     
                                                        <td rowspan="1"><?=$acDomingoCocinaV;?></td>                                    
                                                        <td bgcolor="#d1ffaf"><b><?=$acCocina;?></b></td>                       
                                                    </tr> 

                                                    <tr>
                                                        <td><?=$nombrePlaza;?></td>  
                                                        <td>APOYO</td>
                                                        <td rowspan="1"><?=$acLunesApoyoM;?></td>  
                                                        <td rowspan="1"><?=$acLunesApoyoV;?></td>  
                                                        <td rowspan="1"><?=$acMartesApoyoM;?></td> 
                                                        <td rowspan="1"><?=$acMartesApoyoV;?></td> 
                                                        <td rowspan="1"><?=$acMiercolesApoyoM;?></td>
                                                        <td rowspan="1"><?=$acMiercolesApoyoV;?></td>        
                                                        <td rowspan="1"><?=$acJuevesApoyoM;?></td>   
                                                        <td rowspan="1"><?=$acJuevesApoyoV;?></td>        
                                                        <td rowspan="1"><?=$acViernesApoyoM;?></td>
                                                        <td rowspan="1"><?=$acViernesApoyoV;?></td>        
                                                        <td rowspan="1"><?=$acSabadoApoyoM;?></td> 
                                                        <td rowspan="1"><?=$acSabadoApoyoV;?></td> 
                                                        <td rowspan="1"><?=$acDomingoApoyoM;?></td>                                     
                                                        <td rowspan="1"><?=$acDomingoApoyoV;?></td>                                     
                                                        <td bgcolor="#d1ffaf"><b><?=$acApoyo;?></b></td>                       
                                                    </tr>       

                                                    <tr>
                                                        <td></td>
                                                        <td bgcolor="#76c5ff">Total</td>
                                                        <td bgcolor="#d1ffaf" colspan="2"><b><?=$acLunes;?></b></td>  
                                                        <td bgcolor="#d1ffaf" colspan="2"><b><?=$acMartes;?></b></td>  
                                                        <td bgcolor="#d1ffaf" colspan="2"><b><?=$acMiercoles;?></b></td> 
                                                        <td bgcolor="#d1ffaf" colspan="2"><b><?=$acJueves;?></b></td> 
                                                        <td bgcolor="#d1ffaf" colspan="2"><b><?=$acViernes;?></b></td>
                                                        <td bgcolor="#d1ffaf" colspan="2"><b><?=$acSabado;?></b></td>        
                                                        <td bgcolor="#d1ffaf" colspan="2"><b><?=$acDomingo;?></b></td>                                                   
                                                        <td bgcolor="#d1ffaf"></td>   
                                                    </tr>   

                                                </tbody>                            
                                            </table>

                                                                               
                                        <!--UNA PLAZA SELECCIONADA TODOS LOS LUGARES DE LA CAFETERIA-->
                                        <?php break; ?>    

                                        <?php case 5: ?>
                                        <!--BUSQUEDA EN RANGO DE TODAS LAS PLAZAS CON TODOS LOS LUGARES SELECCIONADOS-->                                            
                                            <table class="display responsive table table-bordered  btn-hover nowrap" style="width:100%" id="tbl_usuarios_12">                                                    
                                                    <!--VARIABLES INICIO DEL PROYECTO-->                                                                                                        
                                                    <thead>
                                                        <tr>       

                                                            <?php 
                                                                $lunes = date( 'd', strtotime( 'monday this week' ));
                                                                $martes = date( 'd', strtotime( 'tuesday this week' ));
                                                                $miercoles = date( 'd', strtotime( 'wednesday this week' ));
                                                                $jueves = date( 'd', strtotime( 'thursday this week' ));
                                                                $viernes = date( 'd', strtotime( 'friday this week' ));
                                                                $sabado = date( 'd', strtotime( 'saturday this week' ));
                                                                $domingo = date( 'd', strtotime( 'sunday this week' ));                                
                                                            ?> 

                                                            <th><?=$fechaInicio." al <br>".$fechaFin;?></th>                                                                                                                 
                                                            <th>Barra</th>                                                                                                                 
                                                            <th>Mesas</th>
                                                            <th>Cocina</th>
                                                            <th>Apoyo</th>
                                                            <th>Totales</th>                                                                                                  
                                                        </tr>
                                                    </thead>

                                                    <tbody>                                                
                                                        <tr>
                                                            <td>CASITAS</td>                                                            
                                                            <td><?=$barraCasitas;?></td>                                                              
                                                            <td><?=$mesasCasitas;?></td>                                                            
                                                            <td><?=$cocinaCasitas;?></td>
                                                            <td><?=$apoyoCasitas;?></td>                                                                                                                        
                                                            <td bgcolor="#d1ffaf"><b><?=$acumuladoFilaRangoCasitas;?></b></td>                                                                                                                            
                                                        </tr>   
                                                        <tr>
                                                            <td>MATAMOROS</td>
                                                            <td><?=$barraMatamoros;?></td>                                                              
                                                            <td><?=$mesasMatamoros;?></td>                                                            
                                                            <td><?=$cocinaMatamoros;?></td>
                                                            <td><?=$apoyoMatamoros;?></td>                                                            
                                                            <td bgcolor="#d1ffaf"><b><?=$acumuladoFilaRangoMatamoros;?></b></td>                                                                                            
                                                        </tr>                                                         
                                                        <tr>
                                                            <td>OAXACA</td>
                                                            <td><?=$barraOaxaca;?></td>                                                              
                                                            <td><?=$mesasOaxaca;?></td>                                                            
                                                            <td><?=$cocinaOaxaca;?></td>
                                                            <td><?=$apoyoOaxaca;?></td>  
                                                            <td bgcolor="#d1ffaf"><b><?=$acumuladoFilaRangoOaxaca;?></b></td>                                                                                            
                                                        </tr> 
                                                        <tr>
                                                            <td>POZA RICA</td>
                                                            <td><?=$barraPozaRica;?></td>                                                              
                                                            <td><?=$mesasPozaRica;?></td>                                                            
                                                            <td><?=$cocinaPozaRica;?></td>
                                                            <td><?=$apoyoPozaRica;?></td>  
                                                            <td bgcolor="#d1ffaf"><b><?=$acumuladoFilaRangoPozaRica;?></b></td>                                                                                             
                                                        </tr>                                                             
                                                        <tr>
                                                            <td>REYNOSA I</td>                                                            
                                                            <td><?=$barraReynosaI;?></td>                                                              
                                                            <td><?=$mesasReynosaI;?></td>                                                            
                                                            <td><?=$cocinaReynosaI;?></td>
                                                            <td><?=$apoyoReynosaI;?></td> 
                                                            <td bgcolor="#d1ffaf"><b><?=$acumuladoFilaRangoReynosaI;?></b></td>                                                                                                                                                                                                                        
                                                        </tr>     
                                                        <tr>
                                                            <td>REYNOSA II</td>
                                                            <td><?=$barraReynosaII;?></td>                                                              
                                                            <td><?=$mesasReynosaII;?></td>                                                            
                                                            <td><?=$cocinaReynosaII;?></td>
                                                            <td><?=$apoyoReynosaII;?></td> 
                                                            <td bgcolor="#d1ffaf"><b><?=$acumuladoFilaRangoReynosaII;?></b></td>                                                                                                                                                                                                                                                                                                                                                    
                                                        </tr>     
                                                        <tr>
                                                            <td>TEZIUTLÁN</td>
                                                            <td><?=$barraTeziutlan;?></td>                                                              
                                                            <td><?=$mesasTeziutlan;?></td>                                                            
                                                            <td><?=$cocinaTeziutlan;?></td>
                                                            <td><?=$apoyoTeziutlan;?></td> 
                                                            <td bgcolor="#d1ffaf"><b><?=$acumuladoFilaRangoTeziutlan;?></b></td>                                                                                                                            
                                                        </tr>                                                    
                                                        <tr>                                                            
                                                            <td bgcolor="#b3edff">TOTAL</td>
                                                            <td bgcolor="#d1ffaf"><b><?=$acumuladoTotalColumnaBarra;?></b></td>                                                            
                                                            <td bgcolor="#d1ffaf"><b><?=$acumuladoTotalColumnaMesas;?></b></td>                                                            
                                                            <td bgcolor="#d1ffaf"><b><?=$acumuladoTotalColumnaCocina;?></b></td>                                                            
                                                            <td bgcolor="#d1ffaf"><b><?=$acumuladoTotalColumnaApoyo;?></b></td>                                                            
                                                            <td bgcolor="#d1ffaf"><b><?=$acumuladoTotalRango;?></b></td>                                                                                                                            
                                                        </tr>                                                                                                                   
                                                    </tbody>                            
                                            </table>                                                                           
                                        <!--BUSQUEDA EN RANGO DE TODAS LAS PLAZAS CON TODOS LOS LUGARES SELECCIONADOS-->
                                        <?php break; ?>    

                                        <?php case 6: ?>                                        
                                        <!--BUSQUEDA EN RANGO DE TODAS LAS PLAZAS CON UN LUGAR SELECCIONADO-->                                            
                                            <table class="display responsive table table-bordered  btn-hover nowrap" style="width:100%" id="tbl_usuarios_12">                                                    
                                                                                                                                                        
                                                    <thead>
                                                        <tr>                                                                  
                                                            <th><?=$fechaInicio." al ".$fechaFin;?></th>                                                                                                                 
                                                            <th><?=$nombreLugar;?></th>                                                                                                                                                                                                                                                                            
                                                            <th>Totales</th>
                                                        </tr>
                                                    </thead>

                                                    <tbody>                                                
                                                        <tr>
                                                            <td>CASITAS</td>                                                            
                                                            <td><?=$lugarCasitas;?></td>                                                              
                                                           <td bgcolor="#d1ffaf"><b><?=$lugarCasitas;?></b></td>                                                                                                                            
                                                        </tr>   
                                                        <tr>
                                                            <td>MATAMOROS</td>
                                                            <td><?=$lugarMatamoros;?></td>                                                              
                                                           <td bgcolor="#d1ffaf"><b><?=$lugarMatamoros;?></b></td>                                                                                            
                                                        </tr>                                                         
                                                        <tr>
                                                            <td>OAXACA</td>
                                                            <td><?=$lugarOaxaca;?></td>                                                              
                                                           <td bgcolor="#d1ffaf"><b><?=$lugarOaxaca;?></b></td>                                                                                            
                                                        </tr> 
                                                        <tr>
                                                            <td>POZA RICA</td>
                                                            <td><?=$lugarPozaRica;?></td>                                                              
                                                           <td bgcolor="#d1ffaf"><b><?=$lugarPozaRica;?></b></td>                                                                                             
                                                        </tr>                                                             
                                                        <tr>
                                                            <td>REYNOSA I</td>                                                            
                                                            <td><?=$lugarReynosaI;?></td>                                                                                                                          
                                                            <td bgcolor="#d1ffaf"><b><?=$lugarReynosaI;?></b></td>                                                                                                                                                                                                                        
                                                        </tr>     
                                                        <tr>
                                                            <td>REYNOSA II</td>
                                                            <td><?=$lugarReynosaII;?></td>                                                                                                                          
                                                            <td bgcolor="#d1ffaf"><b><?=$lugarReynosaII;?></b></td>                                                                                                                                                                                                                                                                                                                                                    
                                                        </tr>     
                                                        <tr>
                                                            <td>TEZIUTLÁN</td>
                                                            <td><?=$lugarTeziutlan;?></td>                                                                                                                          
                                                            <td bgcolor="#d1ffaf"><b><?=$lugarTeziutlan;?></b></td>                                                                                                                            
                                                        </tr>                                                                                                            
                                                        <tr>                                                            
                                                            <td bgcolor="#b3edff">TOTAL</td>
                                                            <td bgcolor="#d1ffaf"><b><?=$acumuladoLugar;?></b></td>                                                                                                                        
                                                            <td bgcolor="#d1ffaf"><b><?=$acumuladoLugar;?></b></td>                                                                                                                            
                                                        </tr>                                                                                                                   
                                                    </tbody>                            
                                            </table>                                                                           
                                        <!--BUSQUEDA EN RANGO DE TODAS LAS PLAZAS CON UN LUGAR SELECCIONADO-->
                                        <?php break; ?>   

                                        <?php case 7: ?>                                        
                                        <!--BUSQUEDA EN RANGO DE TODAS LAS PLAZAS CON TODOS LOS LUGARES SELECCIONADOS-->                                            
                                            <table class="display responsive table table-bordered  btn-hover nowrap" style="width:100%" id="tbl_usuarios_12">                                                    
                                                <!--VARIABLES INICIO DEL PROYECTO-->                                                                                                        
                                                <thead>
                                                    <tr>                                                              
                                                        <th><?=$fechaInicio." al <br>".$fechaFin;?></th>                                                                                                                 
                                                        <th>Barra</th>                                                                                                                 
                                                        <th>Mesas</th>
                                                        <th>Cocina</th>
                                                        <th>Apoyo</th>
                                                        <th>Totales</th>                                                                                                  
                                                    </tr>
                                                </thead>
                                                <tbody>                                                
                                                    
                                                    <tr>
                                                        <td>CASITAS</td>                                                            
                                                        <td><?=$barraGenericoRango;?></td>                                                              
                                                        <td><?=$mesasGenericoRango;?></td>                                                            
                                                        <td><?=$cocinaGenericoRango;?></td>
                                                        <td><?=$apoyoGenericoRango;?></td>                                                                                                                        
                                                        <td bgcolor="#d1ffaf"><b><?=$acumuladoFilaRangoGenerico;?></b></td>                                                                                                                            
                                                    </tr>                                                    

                                                    <tr>                                                            
                                                        <td bgcolor="#b3edff">TOTAL</td>
                                                        <td bgcolor="#d1ffaf"><b><?=$barraGenericoRango;?></b></td>                                                            
                                                        <td bgcolor="#d1ffaf"><b><?=$mesasGenericoRango;?></b></td>                                                            
                                                        <td bgcolor="#d1ffaf"><b><?=$cocinaGenericoRango;?></b></td>                                                            
                                                        <td bgcolor="#d1ffaf"><b><?=$apoyoGenericoRango;?></b></td>                                                            
                                                        <td bgcolor="#d1ffaf"><b><?=$acumuladoFilaRangoGenerico;?></b></td>                                                                                                                            
                                                    </tr>                                                                                                                   
                                                </tbody>                            
                                            </table>                                                                           
                                        <!--BUSQUEDA EN RANGO DE TODAS LAS PLAZAS CON TODOS LOS LUGARES SELECCIONADOS-->                                    
                                        <?php break; ?>  

                                        <?php case 8: ?>                                        
                                        <!--BUSQUEDA EN RANGO DE TODAS LAS PLAZAS CON UN LUGAR SELECCIONADO-->                                            
                                            <table class="display responsive table table-bordered  btn-hover nowrap" style="width:100%" id="tbl_usuarios_12">                                                    
                                                                                                                                                        
                                                    <thead>
                                                        <tr>                                                                  
                                                            <th><?=$fechaInicio." al ".$fechaFin;?></th>                                                                                                                 
                                                            <th><?=$nombreLugar;?></th>                                                                                                                                                                                                                                                                            
                                                            <th>Totales</th>
                                                        </tr>
                                                    </thead>

                                                    <tbody>                                                
                                                        <tr>
                                                            <td>CASITAS</td>                                                            
                                                            <td><?=$lugarCasitas;?></td>                                                              
                                                           <td bgcolor="#d1ffaf"><b><?=$lugarCasitas;?></b></td>                                                                                                                            
                                                        </tr>   
                                                        <tr>
                                                            <td>MATAMOROS</td>
                                                            <td><?=$lugarMatamoros;?></td>                                                              
                                                           <td bgcolor="#d1ffaf"><b><?=$lugarMatamoros;?></b></td>                                                                                            
                                                        </tr>                                                         
                                                        <tr>
                                                            <td>OAXACA</td>
                                                            <td><?=$lugarOaxaca;?></td>                                                              
                                                           <td bgcolor="#d1ffaf"><b><?=$lugarOaxaca;?></b></td>                                                                                            
                                                        </tr> 
                                                        <tr>
                                                            <td>POZA RICA</td>
                                                            <td><?=$lugarPozaRica;?></td>                                                              
                                                           <td bgcolor="#d1ffaf"><b><?=$lugarPozaRica;?></b></td>                                                                                             
                                                        </tr>                                                             
                                                        <tr>
                                                            <td>REYNOSA I</td>                                                            
                                                            <td><?=$lugarReynosaI;?></td>                                                                                                                          
                                                            <td bgcolor="#d1ffaf"><b><?=$lugarReynosaI;?></b></td>                                                                                                                                                                                                                        
                                                        </tr>     
                                                        <tr>
                                                            <td>REYNOSA II</td>
                                                            <td><?=$lugarReynosaII;?></td>                                                                                                                          
                                                            <td bgcolor="#d1ffaf"><b><?=$lugarReynosaII;?></b></td>                                                                                                                                                                                                                                                                                                                                                    
                                                        </tr>     
                                                        <tr>
                                                            <td>TEZIUTLÁN</td>
                                                            <td><?=$lugarTeziutlan;?></td>                                                                                                                          
                                                            <td bgcolor="#d1ffaf"><b><?=$lugarTeziutlan;?></b></td>                                                                                                                            
                                                        </tr>                                                                                                            
                                                        <tr>                                                            
                                                            <td bgcolor="#b3edff">TOTAL</td>
                                                            <td bgcolor="#d1ffaf"><b><?=$acumuladoLugar;?></b></td>                                                                                                                        
                                                            <td bgcolor="#d1ffaf"><b><?=$acumuladoLugar;?></b></td>                                                                                                                            
                                                        </tr>                                                                                                                   
                                                    </tbody>                            
                                            </table>                                                                           
                                        <!--BUSQUEDA EN RANGO DE TODAS LAS PLAZAS CON UN LUGAR SELECCIONADO-->
                                        <?php break; ?>  

                                    <?php endswitch; ?>
                                </div>
                            </div>
                         </div>                    
                    </div>                    
                    <!-- /.box-body -->
                </div>
                <!-- /.box -->
            </section>
            <!-- /.content -->
        </div>
    <!-- /.content-wrapper -->





<!--TABLA PARA MOSTRAR EL HISTORIAL DE EVENTOS DE UN USUARIO-->    
 <div class="modal fade" id="historialUsuarioModal">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span></button>
            <h4 class="modal-title">Historial de Usuario</h4>
        </div>
        <div class="modal-body">
            <div class="responsive-table">
                <table class="display responsive table table-bordered  btn-hover nowrap" style="width:100%" id="tbl_prueba_API" >
                                <thead>
                                    <tr>                                    
                                        <th>Fecha</th>                                                                                                                 
                                        <th>Usuario</th>                                                                                 
                                        <th>Concepto</th>  
                                        <th>Importe</th>  
                                        <th>Nota</th>  
                                        <th>Eliminar</th>  
                                    </tr>
                                </thead>
                                <tbody>                                                                
                                </tbody>                            
                </table> 
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-danger pull-left" data-dismiss="modal">Cerrar</button>
        </div>
        </div>
        <!-- /.modal-content -->
    </div>
    <!-- /.modal-dialog -->
    </div>
    <!-- /.modal -->
<!--TABLA PARA MOSTRAR EL HISTORIAL DE EVENTOS DE UN USUARIO-->



<script type="text/javascript">  
    var pruebaAPI= $('#tbl_prueba_API').DataTable({
            "aaSorting": [],
            dom: 'Bfrtip',
            "pageLength": 30,
            buttons: [
                {
                    extend: 'excelHtml5',
                    title: "Listado de Ventas",
                    exportOptions: {
                        columns: ':visible'
                    },
                },
                {
                    extend: 'pdfHtml5',
                    title: "Listado de Ventas",
                    exportOptions: {
                        columns: ':visible'
                    }
                    
                }
            ],
            "language": {
                "lengthMenu": "Mostrar _MENU_ registros por pagina",
                "zeroRecords": "No se encontraron resultados en su busqueda",
                "searchPlaceholder": "Buscar registros",
                "info": "Mostrando registros de _START_ al _END_ de un total de  _TOTAL_ registros",
                "infoEmpty": "No existen registros",
                "infoFiltered": "(filtrado de un total de _MAX_ registros)",
                "search": "Buscar:",
                "paginate": {
                    "first": "Primero",
                    "last": "Último",
                    "next": "Siguiente",
                    "previous": "Anterior"
                },
            },
            responsive: true
    });


    function verEventos(fechaInicio,fechaFin,usuario_id){    
        $('#historialUsuarioModal').modal('show');
        var parametros = {
                "fechaInicio" : fechaInicio,
                "fechaFin" : fechaFin,            
                "usuario_id" : usuario_id            
                };

                $.ajax({
                type: "POST",
                url: "<?php echo base_url();?>Nominas/consultarHistorialUsuarios",
                data: parametros,              
                dataType: "JSON",                                 
                success: function(response){  
                    pruebaAPI.clear();   
                    var len = response.length;                                           
                    for(var i=0; i<len; i++){
                        var id=response[i].usuario_nomina_id;
                        var fecha = response[i].Fecha;
                        var usuario=response[i].Usuario;
                        var concepto=response[i].Concepto; 
                        var extra=response[i].Extra;                        
                        var nota=response[i].Nota;          
                        var turno=response[i].turno_id;           
                        var eliminar='<a href="#" onclick="eliminarEvento(\''+fecha+'\',\''+id+'\','+turno+')" class="btn btn-danger"><span class="fa fa-remove"></span></a></td>';	                   
                        pruebaAPI.row.add([fecha,usuario,concepto,"$"+extra,nota,eliminar]);                    
                    }                                                
                    pruebaAPI.draw();                                                
                },
                error: function() { 
                    alert("Error posting feed.");
                }
            });
    }
</script>

    
