<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
            <!-- Content Header (Page header) -->
            <section class="content-header">
                <h1>
                Tabla de Nominas 
                <small>Listado del</small>
                </h1>                
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
                                        <div class="col-md-3">
                                            <input type="date" class="form-control" name="fechaInicioD" id="fechaInicioD" value="<?php echo !empty($fechaInicio) ? $fechaInicio:'';?>">
                                        </div>

                                    <label for="" class="col-md-1 control-label">Hasta:</label>
                                        <div class="col-md-3">
                                            <input type="date" class="form-control" name="fechaFinD" id="fechaFinD" value="<?php  echo !empty($fechaFin) ? $fechaFin:'';?>">
                                        </div>

                                    <label for="plaza_id" class="col-md-1 control-label">Plaza:</label>                                        
                                        <div class="col-md-3">
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

                                    
                                                                                                     
                                </div>                
                                <div class="form-group">                                                                                                                                       
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
                                        <!--TODAS LAS PLAZAS SELECCIONADAS -->
                                            <table class="display responsive table table-bordered  btn-hover nowrap" style="width:100%" id="tbl_usuarios">                                                
                                                    <!--VARIABLES INICIO DEL PROYECTO-->
                                                                            
                                                    <thead>
                                                        <tr>                                                               
                                                            <th>Plaza</th>
                                                            <th>Turnos</th>
                                                            <th>Horas Hombre</th>
                                                            <th>Unidades</th>
                                                            <th>Ventas</th>                                                   
                                                            <th>Unidades/Turno</th>                                                                                                  
                                                            <th>Ventas/Turno</th>   
                                                            <th>HH/Unidades</th>
                                                            <th>HH/Ingresos</th>                                                                                               
                                                        </tr>
                                                    </thead>
                                                    
                                                    <tbody>                                            
                                                        <tr>
                                                            <td bgcolor="#bae6ff">CASITAS</td>
                                                            <td><?=number_format($turnosCasitas);?></td>    
                                                            <td><?=round(number_format($turnosCasitas)*6.5,2);?></td>    
                                                            <td><?=number_format($unidadesCasitas);?></td>                                 
                                                            <td>$<?=number_format($ingresosCasitas);?></td>                                 
                                                            <td>                                                            
                                                                <?php if($turnosCasitas>0 && $unidadesCasitas>0):?>
                                                                    <?=number_format($unidadesCasitas/$turnosCasitas);?>
                                                                <?php else: ?>
                                                                    <?php echo number_format(0);?>
                                                                <?php endif; ?>                                                    
                                                            </td>
                                                            <td>
                                                                <?php if($turnosCasitas>0 && $ingresosCasitas>0):?>
                                                                    $<?=number_format($ingresosCasitas/$turnosCasitas);?>
                                                                <?php else: ?>
                                                                    $<?php echo number_format(0);?>
                                                                <?php endif; ?>                                                                                                            
                                                            </td>    
                                                            
                                                            <td>
                                                                <?php      
                                                                    if($unidadesCasitas>0 && $turnosCasitas>0){
                                                                        $resultado=round(number_format($unidadesCasitas)/(number_format($turnosCasitas)*6.5),3);                                                    
                                                                    }   
                                                                    else{
                                                                        $resultado=0;
                                                                    }                            
                                                                    echo $resultado;
                                                                ?>
                                                            </td>

                                                            <td>$
                                                                <?php
                                                                    if($ingresosCasitas>0 & $turnosCasitas>0){
                                                                        $resultado=number_format($ingresosCasitas/(number_format($turnosCasitas)*6.5));
                                                                    }else{
                                                                        $resultado=0;
                                                                    }                                                                    
                                                                    echo $resultado;
                                                                ?>
                                                            </td>                                                            
                                                        </tr>   

                                                        <tr>
                                                            <td bgcolor="#FA8072">MATAMOROS</td>                                                       
                                                            <td><?=number_format($turnosMatamoros);?></td>   
                                                            <td><?=round(number_format($turnosMatamoros)*6.5,2);?></td>                                  
                                                            <td><?=number_format($unidadesMatamoros);?></td>                                 
                                                            <td>$<?=number_format($ingresosMatamoros);?></td>                                 
                                                            <td>                                                            
                                                                <?php if($turnosMatamoros>0 && $unidadesMatamoros>0):?>
                                                                    <?=number_format($unidadesMatamoros/$turnosMatamoros);?>
                                                                <?php else: ?>
                                                                    <?php echo number_format(0);?>
                                                                <?php endif; ?>                                                    
                                                            </td>
                                                            <td>
                                                                <?php if($turnosMatamoros>0 && $ingresosMatamoros>0):?>
                                                                    $<?=number_format($ingresosMatamoros/$turnosMatamoros);?>
                                                                <?php else: ?>
                                                                    $<?php echo number_format(0);?>
                                                                <?php endif; ?>                                                                                                            
                                                            </td> 

                                                            <td>
                                                                <?php      
                                                                    if($unidadesMatamoros>0 && $turnosMatamoros>0){
                                                                        $resultado=round(number_format($unidadesMatamoros)/(number_format($turnosMatamoros)*6.5),3);                                                    
                                                                    }   
                                                                    else{
                                                                        $resultado=0;
                                                                    }                            
                                                                    echo $resultado;
                                                                ?>
                                                            </td>

                                                            <td>$
                                                                <?php
                                                                    if($ingresosMatamoros>0 & $turnosMatamoros>0){
                                                                        $resultado=number_format($ingresosMatamoros/(number_format($turnosMatamoros)*6.5));
                                                                    }else{
                                                                        $resultado=0;
                                                                    }                                                                    
                                                                    echo $resultado;
                                                                ?>
                                                            </td>  
                                                        </tr> 
                                                        
                                                        <tr>
                                                            <td bgcolor="#8cedbc">OAXACA</td>                                                        
                                                            <td><?=number_format($turnosOaxaca);?></td>   
                                                            <td><?=round(number_format($turnosOaxaca)*6.5,2);?></td>                                  
                                                            <td><?=number_format($unidadesOaxaca);?></td>                                 
                                                            <td>$<?=number_format($ingresosOaxaca);?></td>                                 
                                                            <td>                                                            
                                                                <?php if($turnosOaxaca>0 && $unidadesOaxaca>0):?>
                                                                    <?=number_format($unidadesOaxaca/$turnosOaxaca);?>
                                                                <?php else: ?>
                                                                    <?php echo number_format(0);?>
                                                                <?php endif; ?>                                                    
                                                            </td>
                                                            <td>
                                                                <?php if($turnosOaxaca>0 && $ingresosOaxaca>0):?>
                                                                    $<?=number_format($ingresosOaxaca/$turnosOaxaca);?>
                                                                <?php else: ?>
                                                                    $<?php echo number_format(0);?>
                                                                <?php endif; ?>                                                                                                            
                                                            </td>
                                                            
                                                            <td>
                                                                <?php      
                                                                    if($unidadesOaxaca>0 && $turnosOaxaca>0){
                                                                        $resultado=round(number_format($unidadesOaxaca)/(number_format($turnosOaxaca)*6.5),3);                                                    
                                                                    }   
                                                                    else{
                                                                        $resultado=0;
                                                                    }                            
                                                                    echo $resultado;
                                                                ?>
                                                            </td>

                                                            <td>$
                                                                <?php
                                                                    if($ingresosOaxaca>0 & $turnosOaxaca>0){
                                                                        $resultado=number_format($ingresosOaxaca/(number_format($turnosOaxaca)*6.5));
                                                                    }else{
                                                                        $resultado=0;
                                                                    }                                                                    
                                                                    echo $resultado;
                                                                ?>
                                                            </td>  

                                                        </tr> 

                                                        <tr >
                                                            <td bgcolor="#ecccbb" >POZA RICA</td>                                                        
                                                            <td><?=number_format($turnosPozaRica);?></td>                                 
                                                            <td><?=round(number_format($turnosPozaRica)*6.5,2);?></td>                                  
                                                            <td><?=number_format($unidadesPozaRica);?></td>                                 
                                                            <td>$<?=number_format($ingresosPozaRica);?></td>                                 
                                                            <td>                                                            
                                                                <?php if($turnosPozaRica>0 && $unidadesPozaRica>0):?>
                                                                    <?=number_format($unidadesPozaRica/$turnosPozaRica);?>
                                                                <?php else: ?>
                                                                    <?php echo number_format(0);?>
                                                                <?php endif; ?>                                                    
                                                            </td>
                                                            <td>
                                                                <?php if($turnosPozaRica>0 && $ingresosPozaRica>0):?>
                                                                    $<?=number_format($ingresosPozaRica/$turnosPozaRica);?>
                                                                <?php else: ?>
                                                                    $<?php echo number_format(0);?>
                                                                <?php endif; ?>                                                                                                            
                                                            </td>        
                                                            
                                                            <td>
                                                                <?php      
                                                                    if($unidadesPozaRica>0 && $turnosPozaRica>0){
                                                                        $resultado=round(number_format($unidadesPozaRica)/(number_format($turnosPozaRica)*6.5),3);                                                    
                                                                    }   
                                                                    else{
                                                                        $resultado=0;
                                                                    }                            
                                                                    echo $resultado;
                                                                ?>
                                                            </td>

                                                            <td>$
                                                                <?php
                                                                    if($ingresosPozaRica>0 & $turnosPozaRica>0){
                                                                        $resultado=number_format($ingresosPozaRica/(number_format($turnosPozaRica)*6.5));
                                                                    }else{
                                                                        $resultado=0;
                                                                    }                                                                    
                                                                    echo $resultado;
                                                                ?>
                                                            </td>                                              

                                                        </tr>     
                                                        
                                                        <tr >
                                                            <td bgcolor="#ffd682">REYNOSA I</td>                                                        
                                                            <td><?=number_format($turnosReynosaI);?></td>  
                                                            <td><?=round(number_format($turnosReynosaI)*6.5,2);?></td>                                                                 
                                                            <td><?=number_format($unidadesReynosaI);?></td>                                 
                                                            <td>$<?=number_format($ingresosReynosaI);?></td>                                 
                                                            <td>                                                            
                                                                <?php if($turnosReynosaI>0 && $unidadesReynosaI>0):?>
                                                                    <?=number_format($unidadesReynosaI/$turnosReynosaI);?>
                                                                <?php else: ?>
                                                                    <?php echo number_format(0);?>
                                                                <?php endif; ?>                                                    
                                                            </td>
                                                            <td>
                                                                <?php if($turnosReynosaI>0 && $ingresosReynosaI>0):?>
                                                                    $<?=number_format($ingresosReynosaI/$turnosReynosaI);?>
                                                                <?php else: ?>
                                                                    $<?php echo number_format(0);?>
                                                                <?php endif; ?>                                                                                                            
                                                            </td>   

                                                            <td>
                                                                <?php      
                                                                    if($unidadesReynosaI>0 && $turnosReynosaI>0){
                                                                        $resultado=round(number_format($unidadesReynosaI)/(number_format($turnosReynosaI)*6.5),3);                                                    
                                                                    }   
                                                                    else{
                                                                        $resultado=0;
                                                                    }                            
                                                                    echo $resultado;
                                                                ?>
                                                            </td>

                                                            <td>$
                                                                <?php
                                                                    if($ingresosReynosaI>0 & $turnosReynosaI>0){
                                                                        $resultado=number_format($ingresosReynosaI/(number_format($turnosReynosaI)*6.5));
                                                                    }else{
                                                                        $resultado=0;
                                                                    }                                                                    
                                                                    echo $resultado;
                                                                ?>
                                                            </td>  


                                                        </tr>     

                                                        <tr >
                                                            <td bgcolor="#e7bbec">REYNOSA II</td>                                                        
                                                            <td><?=number_format($turnosReynosaII);?></td>                                 
                                                            <td><?=round(number_format($turnosReynosaII)*6.5,2);?></td>                                                                 
                                                            <td><?=number_format($unidadesReynosaII);?></td>                                 
                                                            <td>$<?=number_format($ingresosReynosaII);?></td>                                 
                                                            <td>                                                            
                                                                <?php if($turnosReynosaII>0 && $unidadesReynosaII>0):?>
                                                                    <?=number_format($unidadesReynosaII/$turnosReynosaII);?>
                                                                <?php else: ?>
                                                                    <?php echo number_format(0);?>
                                                                <?php endif; ?>                                                    
                                                            </td>
                                                            <td>
                                                                <?php if($turnosReynosaII>0 && $ingresosReynosaII>0):?>
                                                                    $<?=number_format($ingresosReynosaII/$turnosReynosaII);?>
                                                                <?php else: ?>
                                                                    $<?php echo number_format(0);?>
                                                                <?php endif; ?>                                                                                                            
                                                            </td>   


                                                            <td>
                                                                <?php      
                                                                    if($unidadesReynosaII>0 && $turnosReynosaII>0){
                                                                        $resultado=round(number_format($unidadesReynosaII)/(number_format($turnosReynosaII)*6.5),3);                                                    
                                                                    }   
                                                                    else{
                                                                        $resultado=0;
                                                                    }                            
                                                                    echo $resultado;
                                                                ?>
                                                            </td>

                                                            <td>$
                                                                <?php
                                                                    if($ingresosReynosaII>0 & $turnosReynosaII>0){
                                                                        $resultado=number_format($ingresosReynosaII/(number_format($turnosReynosaII)*6.5));
                                                                    }else{
                                                                        $resultado=0;
                                                                    }                                                                    
                                                                    echo $resultado;
                                                                ?>
                                                            </td>  
                                                        </tr>     

                                                        <tr >
                                                            <td bgcolor="#c7d5d8">TEZIUTLÁN</td>                                                        
                                                            <td><?=number_format($turnosTeziutlan);?></td>                                 
                                                            <td><?=round(number_format($turnosTeziutlan)*6.5,2);?></td>                                                                 
                                                            <td><?=number_format($unidadesTeziutlan);?></td>                                 
                                                            <td>$<?=number_format($ingresosTeziutlan);?></td>                                 
                                                            <td>                                                            
                                                                <?php if($turnosTeziutlan>0 && $unidadesTeziutlan>0):?>
                                                                    <?=number_format($unidadesTeziutlan/$turnosTeziutlan);?>
                                                                <?php else: ?>
                                                                    <?php echo number_format(0);?>
                                                                <?php endif; ?>                                                    
                                                            </td>
                                                            <td>
                                                                <?php if($turnosTeziutlan>0 && $ingresosTeziutlan>0):?>
                                                                    $<?=number_format($ingresosTeziutlan/$turnosTeziutlan);?>
                                                                <?php else: ?>
                                                                    $<?php echo number_format(0);?>
                                                                <?php endif; ?>                                                                                                            
                                                            </td>    
                                                                                                                        
                                                            <td>
                                                                <?php      
                                                                    if($unidadesTeziutlan>0 && $turnosTeziutlan>0){
                                                                        $resultado=round(number_format($unidadesTeziutlan)/(number_format($turnosTeziutlan)*6.5),3);                                                    
                                                                    }   
                                                                    else{
                                                                        $resultado=0;
                                                                    }                            
                                                                    echo $resultado;
                                                                ?>
                                                            </td>

                                                            <td>$
                                                                <?php
                                                                    if($ingresosTeziutlan>0 & $turnosTeziutlan>0){
                                                                        $resultado=number_format($ingresosTeziutlan/(number_format($turnosTeziutlan)*6.5));
                                                                    }else{
                                                                        $resultado=0;
                                                                    }                                                                    
                                                                    echo $resultado;
                                                                ?>
                                                            </td>  

                                                        </tr>

                                                                                            
                                                        <tr>
                                                            <td><?=$fechaInicio."<br>".$fechaFin;?></td>                                                       
                                                            <td bgcolor="#d1ffaf"><?=number_format($totalTurnos);?></td> 
                                                            <td bgcolor="#d1ffaf"><?=$totalHorasHombre;?></td> 
                                                            <td bgcolor="#d1ffaf"><?=number_format($totalUnidades);?></td>                                 
                                                            <td bgcolor="#d1ffaf">$<?=number_format($totalIngresos);?></td>                                 
                                                            <td bgcolor="#d1ffaf"></td>                                                                                        
                                                            <td bgcolor="#d1ffaf"></td>
                                                            <td bgcolor="#d1ffaf"></td>
                                                            <td bgcolor="#d1ffaf"></td>
                                                        </tr>                                                                                                                   

                                                    </tbody>                            
                                            </table>
                                        <!--TODAS LAS PLAZAS SELECCIONADAS-->                                                                            
                                    <?php break; ?> 

                                    <?php case 1: ?>  
                                        <!--SOLO UNA PLAZA SELECCIONADA-->
                                            <table class="display responsive table table-bordered  btn-hover nowrap" style="width:100%" id="tbl_usuarios">                                                
                                                    <!--VARIABLES INICIO DEL PROYECTO-->                                                                            
                                                    <thead>
                                                        <tr>                                                               
                                                            <th>Plaza</th>
                                                            <th>Turnos</th>
                                                            <th>Unidades</th>
                                                            <th>Ventas</th>                                                   
                                                            <th>Unidades/Turno</th>                                                                                                  
                                                            <th>Ventas/Turno</th>                                                                                               
                                                        </tr>
                                                    </thead>                                                    
                                                    <tbody>                                            
                                                        <tr>

                                                            <?php switch($id): 
                                                                case 1: ?>                                                                
                                                                    <td bgcolor="#bae6ff"><?=$nombrePlaza;?></td>
                                                                <?php break; ?>

                                                                <?php case 2: ?>                                                                
                                                                    <td bgcolor="#FA8072"><?=$nombrePlaza;?></td>
                                                                <?php break; ?>
                                                                
                                                                <?php case 3: ?>                                                                                                                                
                                                                    <td bgcolor="#8cedbc"><?=$nombrePlaza;?></td>
                                                                <?php break; ?>

                                                                <?php case 4: ?>                                                                                                                                
                                                                    <td bgcolor="#ecccbb"><?=$nombrePlaza;?></td>
                                                                <?php break; ?>

                                                                <?php case 5: ?>                                                                
                                                                    <td bgcolor="#ffd682"><?=$nombrePlaza;?></td>
                                                                <?php break; ?>

                                                                <?php case 6: ?>                                                                                                                            
                                                                    <td bgcolor="#e7bbec"><?=$nombrePlaza;?></td>
                                                                <?php break; ?>
                                                                
                                                                <?php case 7: ?>                                                                                                                            
                                                                    <td bgcolor="#c7d5d8"><?=$nombrePlaza;?></td>
                                                                <?php break; ?>                                                                
                                                            <?php endswitch; ?>

                                                            <td><?=number_format($turnosGenerico);?></td>                                 
                                                            <td><?=number_format($unidadesGenerico);?></td>                                 
                                                            <td>$<?=number_format($ingresosGenerico);?></td>                                 
                                                            <td>                                                            
                                                                <?php if($turnosGenerico>0 && $unidadesGenerico>0):?>
                                                                    <?=number_format($unidadesGenerico/$turnosGenerico);?>
                                                                <?php else: ?>
                                                                    <?php echo number_format(0);?>
                                                                <?php endif; ?>                                                    
                                                            </td>
                                                            <td>
                                                                <?php if($turnosGenerico>0 && $ingresosGenerico>0):?>
                                                                    <?=number_format($ingresosGenerico/$turnosGenerico);?>
                                                                <?php else: ?>
                                                                    <?php echo number_format(0);?>
                                                                <?php endif; ?>                                                                                                            
                                                            </td>                     
                                                        </tr>   
                                                        <tr>
                                                            <td><?=$fechaInicio."<br>".$fechaFin;?></td>                                                       
                                                            <td bgcolor="#d1ffaf"><?=number_format($totalTurnosGenerico);?></td>   
                                                            <td bgcolor="#d1ffaf"><?=number_format($totalHorasHombreGenerico);?></td>   
                                                            <td bgcolor="#d1ffaf"><?=number_format($totalUnidadesGenerico);?></td>                                 
                                                            <td bgcolor="#d1ffaf">$<?=number_format($totalIngresosGenerico);?></td>                                 
                                                            <td bgcolor="#d1ffaf"></td>                                                                                        
                                                            <td bgcolor="#d1ffaf"></td>
                                                        </tr>                                                                                                                   
                                                    </tbody>                            
                                            </table>
                                        <!--SOLO UNA PLAzA SELECCIONADA-->  
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
    
</script>

    
