<!DOCTYPE html>
<html>
<head>
   <title>ERP - Universidad Católica de Santa María</title>
   <meta http-equiv="content-type" content="text/html; charset=UTF-8">
   <link rel="icon" type="image/png" href="img/logo_ucsm.png">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <link rel="stylesheet" href="js/jquery-ui-1.12.1/jquery-ui.css">
   <link rel="stylesheet" href="bootstrap4/css/bootstrap.min.css">
   <link rel="stylesheet" href="bootstrap4/css/bootstrap-select.css">
   <script src="js/jquery-3.1.1.min.js"></script>
   <script src="js/jquery-ui-1.12.1/jquery-ui.js"></script>
   <script src="bootstrap4/js/bootstrap.bundle.min.js"></script>
   <script src="js/jquery.blockUI.js"></script>
   <script src="bootstrap4/js/bootstrap-select.js"></script>
   <link rel="stylesheet" href="css/style.css">
   <link rel="stylesheet" href="css/style.css?v=1.0">
   <script src="js/java.js"></script>
    {* plugin de tabla para paginacion *}
   <link rel="stylesheet" href="css/datatables.min.css">
   <script src="js/datatables.min.js"></script>
   <!-- Script para darle dinamismo a la tabla -->
   <link rel="stylesheet" type="text/css" href="css/datetimepicker.css">
   <script src="js/jquery.datetimepicker.full.min.js"></script>
   <link rel="stylesheet" href="sweetalert/sweetalert2.min.css">
   <script src="sweetalert/sweetalert2.all.min.js"></script>
   <script src="js/inputmask.js"></script>
   <script src="js/ajaxview.js"></script>
   <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.11.0/umd/popper.min.js" integrity="sha384-b/U6ypiBEHpOf/4+1nzFpr53nxSS+GLCkfwBdFNTxtclqqenISfwAzpKaMNFNmj4" crossorigin="anonymous"></script>
   <!-- Iconos para edit -->
   <script src="https://use.fontawesome.com/releases/v5.0.7/js/all.js"></script>
   <style>
      .mdl-1500{
         max-width:1500px !important;
      }

      .mdl-1000{
         max-width:1000px !important;
      }

      .mdl-900{
         max-width:900px !important;
      }

      .new-ucsm{
         background:#099957;
         color: white;
      }

      .black-input:focus {
        background: #EEFADB;  
        color: black;      
      }

      .black-input { 
        color: black;
        font-family: monospace;
        height: 42px !important; 
        font-size: x-large;
        border: solid #DBE1E1;
        font-weight: 500;
      }
      
      .KTitCar { 
         color: black; 
         font-weight: 500;
         font-size: 18px;
      }

      .KTitCam { 
         font-weight: 500;
         padding: .375rem .75rem;
      }

      .TitTab{ 
         font-weight: 500;
         font-size: large;
         background: #343434;
         color: white; 
         /*background: #8BCF9A;
         color: black; */
      }

      .BodTab{ 
         color: black; 
         font-size: large;
         text-align: left;
      }
      
      .black-input-mdl { 
         color: black;
         font-family: monospace;
         height: 35px !important; 
         font-size: large;
         border: solid #DBE1E1;         
         /*font-weight: 100;*/
      }

      .KTitCam-mdl { 
         padding: 3%;
         height: 35px !important; 
         font-size: large;
      }

      .icon-tbl { 
         cursor: pointer;
         color: #28A745;
         /*color: #007BFF;*/
         font-size: x-large;
      }

      /***************************/
      .services-principal .icon-box {
         padding: 60px 30px;
         transition: all ease-in-out 0.3s;
         background: #fefefe;
         box-shadow: 0px 5px 90px 0px rgba(110, 123, 131, 0.1);
         border-radius: 18px;
         border-bottom: 5px solid #fff;
         /*border: solid rgba(0, 146, 153, 0.2);*/
         border: solid #B6CEBE;
         cursor:pointer;
      }
  
      .services-principal .icon-box:hover {
         transform: translateY(-10px);
         /*border-color: #009299;*/
         border-color: #245433;
      }
      .line-separation{
         border: grey 1px solid !important;
      }
      /*.services-principal .icon-box:hover h4 a {
         color: #009299;
      }*/
   </style>

   <script>
      function Init() {
         var fecha = new Date();
         // console.log(fecha);
         var mes = fecha.getMonth()+1;
         var dia = fecha.getDate(); 
         var ano = fecha.getFullYear();
         if(dia<10)
            dia='0'+dia;
         if(mes<10)
            mes='0'+mes;
         document.getElementById('fechaActual').value=ano+"-"+mes+"-"+dia;
      }
   </script>   
</head>
<body class="bg-light text-dark" onload="Init()">
<div id="header"></div>
<form action="Afj1160.php" method="post" enctype="multipart/form-data" id="poForm">
   <input type="hidden" name="Id" id="Id">
   <input type="hidden" name="p_nBehavior" id="p_nBehavior">
   <div class="container-fluid"> 
      <div class="card-header" style="background:#fbc804; color:black;">
         <div class="input-group input-group-sm d-flex justify-content-between">
            <div class="col text-left"><strong>BAJA ACTIVO FIJO</strong></div>
            <div class="col-auto"><b>{$scNombre}</b></div>
         </div>
      </div>
      {if $snBehavior eq 0}
      <div class="card-body">
      <div class="row d-flex justify-content-center">
      <div class="col-sm-8">
      <div class="card text-center ">
         <div style="padding-left: 17rem;">
            <div class="card-body"><br>
               <div class="input-group-prepend">
                  <span class="input-group-text new-ucsm">Búsqueda</span>
                  <input type="text" name="paData[CCODDES]" class="form-control col-lg-6 black-input" placeholder="Código" autofocus>
               </div><br>
            </div>
         </div>
         <div class="card-footer text-center">
            <button type="submit" name="Boton" value="Buscar" class="btn btn-primary col-md-2" formnovalidate>
               <i class="fas fa-search"></i>&nbsp;&nbsp;BUSCAR</button>
            <button type="submit" name="Boton" value="Salir" class="btn btn-danger col-md-2" style="height: 40px !important;" formnovalidate>
               <i class="fas fa-undo-alt"></i>&nbsp;&nbsp;MENÚ</button>
         </div>
      </div>
      </div>
      </div>
      </div>
   {else if $snBehavior eq 1}
   <div class="card">
      <div class="p-3 card-body" >
         <div class="col text-left" style="background-color:#FABC49;border-radius: 8px;border:1px solid grey;"><strong>BAJA</strong></div>
         <div class="input-group mb-1">
            <div class="input-group-prepend col-lg-2 px-0">
               <span class="input-group-text w-100 new-ucsm  ">Fecha Baja</span>
            </div>
            <input type="date" class="form-control text-uppercase black-input col-lg-2" name="paData[DFECBAJ]" id="fechaActual" value="{$saData['DFECBAJ']}">
            <div class="input-group-prepend col-lg-2 px-0">
               <span class="input-group-text w-100 new-ucsm">Documento de Baja</span>
            </div>
            <input type="text" class="form-control text-uppercase black-input col-lg-6" maxlength="240" value="{$saData['CDOCBAJ']}" name="paData[CDOCBAJ]">
         </div>
         <div class="col text-left" style="background-color:#FABC49;border-radius: 8px;border:1px solid black;"><strong>DATOS ACTIVO FIJO</strong></div>
         <div class="input-group mb-1">
            <div class="input-group-prepend col-lg-2 px-0">
               <span class="input-group-text w-100 new-ucsm">Activo Fijo</span>
            </div>
            <input type="text" class="form-control text-uppercase black-input col-lg-2" value="{$saData['CCODIGO']}" readonly>
            <input type="text" class="form-control text-uppercase black-input col-lg-2" value="{$saData['CACTFIJ']}" name="paData[CACTFIJ]"  readonly>
            <input type="text" class="form-control text-uppercase black-input col-lg-6" value="{$saData['CDESCRI']}" readonly>
         </div>
         <div class="input-group mb-1">
            <div class="input-group-prepend col-lg-2 px-0">
               <span class="input-group-text w-100 new-ucsm">Tipo</span>
            </div>
            <input type="text" class="form-control text-uppercase black-input col-lg-4" value="{$saData['CTIPAFJ']} - {$saData['CDESTIP']}" readonly>
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            <span class="input-group-btn">
               <button class="btn btn-primary" type="button" style="height: 42px !important;" data-toggle="modal" tabindex="-1" data-target="#nuevoDetalle1" > 
                  <i class="fas fa-search"></i>&nbsp;&nbsp;Depreciación
               </button>
            </span>
         </div>
         <div class="input-group mb-1">
            <div class="input-group-prepend col-lg-2 px-0">
               <span class="input-group-text w-100 new-ucsm  ">Estado</span>
            </div>
            <input type="text" class="form-control text-uppercase black-input col-lg-4" value="{$saData['CSITUAC']} - {$saData['CDESSIT']}" readonly>
            <div class="input-group-prepend col-lg-2 px-0">
               <span class="input-group-text w-100 new-ucsm  ">Situación</span>
            </div>
            <input type="text" class="form-control text-uppercase black-input col-lg-4" value="{$saData['CSITUAC']} - {$saData['CDESSIT']}" readonly>
         </div>
         <div class="input-group mb-1">
            <div class="input-group-prepend col-lg-2 px-0">
               <span class="input-group-text w-100 new-ucsm">Centro Costo</span>
            </div>
            <input type="text" class="form-control text-uppercase black-input col-lg-4" value="{$saData['CCENCOS']} - {$saData['CDESCEN']}" readonly>              
            <div class="input-group-prepend col-lg-2 px-0">
               <span class="input-group-text w-100 new-ucsm">Centro Responsabilidad</span>
            </div>
            <input type="text" class="form-control text-uppercase black-input col-lg-4" value="{$saData['CCENRES']} - {$saData['CDESRES']}" readonly>
         </div>
         <div class="input-group mb-1">
            <div class="input-group-prepend col-lg-2 px-0">
               <span class="input-group-text w-100 new-ucsm  ">Empleado Responsable</span>
            </div>
            <input type="text" class="form-control text-uppercase black-input col-lg-4" value="{$saData['CCODEMP']} - {$saData['CNOMEMP']}" readonly>
            <div class="input-group-prepend col-lg-2 px-0">
               <span class="input-group-text w-100 new-ucsm">Código Artículo</span>
            </div>
            <input type="text" class="form-control text-uppercase black-input col-lg-4" value="{$saData['MDATOS']['CCODART']}" readonly>
         </div>          
         <div class="input-group mb-1">
            <div class="input-group-prepend col-lg-2 px-0">
               <span class="input-group-text w-100 new-ucsm  ">Fecha Alta</span>
            </div>
            <input type="text" class="form-control text-uppercase black-input col-lg-2" value="{$saData['DFECALT']}" readonly>
            <div class="input-group-prepend col-lg-2 px-0">
               <span class="input-group-text w-100 new-ucsm">Documento de Adquisición</span>
            </div>
            <input type="text" class="form-control text-uppercase black-input col-lg-2" value="{$saData['MDATOS']['CDOCADQ']}" readonly>
            <div class="input-group-prepend col-lg-2 px-0">
               <span class="input-group-text w-100 new-ucsm">Comprobante</span>
            </div>
            <input type="text" class="form-control text-uppercase black-input col-lg-2" value="{$saData['MDATOS']['CCODREF']}" readonly>
         </div>
         <div class="input-group mb-1"> 
            <div class="input-group-prepend col-lg-2 px-0">
               <span class="input-group-text w-100 new-ucsm ">Nro.RUC</span>
            </div>
            <input type="text" class="form-control text-uppercase black-input col-lg-2" value="{$saData['CNRORUC']}" readonly>
            <div class="input-group-prepend col-lg-2 px-0">
               <span class="input-group-text w-100 new-ucsm ">Razón Social</span>
            </div>
            <input type="text"class="form-control text-uppercase black-input col-lg-6"  value="{$saData['CRAZSOC']}" readonly>
         </div>
         <div class="input-group mb-1">
            <div class="input-group-prepend col-lg-2 px-0">
               <span class="input-group-text w-100 new-ucsm  ">Moneda</span>
            </div>
            <input type="text" class="form-control text-uppercase black-input col-lg-6" value="SOLES" readonly>
            <div class="input-group-prepend col-lg-2 px-0">
               <span class="input-group-text w-100 new-ucsm  ">Moneda Nacional</span>
            </div>
            <input type="text" style="text-align:right;" class="form-control text-uppercase black-input col-lg-2" value="{$saData['NMONTMN']|number_format:2:'.':','}" readonly>
            <div class="input-group-prepend col-lg-2 px-0">
               <span class="input-group-text w-100 new-ucsm  ">Moneda Extranjera</span>
            </div>
            <input type="text" style="text-align:right;" class="form-control text-uppercase black-input col-lg-2" value="{$saData['NMONTME']|number_format:2:'.':','}" readonly>
         </div>
         <div class="col text-left" style="background-color:#FABC49;border-radius: 8px;border:1px solid grey;"><strong>OTROS DATOS</strong></div>
         <div class="input-group mb-1">
            <div class="input-group-prepend col-lg-2 px-0">
               <span class="input-group-text w-100 new-ucsm  ">Marca</span>
            </div>
            <input type="text" class="form-control text-uppercase black-input col-lg-4" value="{$saData['MDATOS']['CMARCA']}" readonly>
            <div class="input-group-prepend col-lg-2 px-0">
               <span class="input-group-text w-100 new-ucsm  ">Modelo</span>
            </div>
            <input type="text" class="form-control text-uppercase black-input col-lg-4" value="{$saData['MDATOS']['CMODELO']}" readonly>
            <div class="input-group-prepend col-lg-2 px-0">
               <span class="input-group-text w-100 new-ucsm  ">Placa</span>
            </div>
            <input type="text" class="form-control text-uppercase black-input col-lg-4"  value="{$saData['MDATOS']['CPLACA']}" readonly>
         </div>
         <div class="input-group mb-1">
            <div class="input-group-prepend col-lg-2 px-0">
               <span class="input-group-text w-100 new-ucsm  ">Nro. Serie</span>
            </div>
            <input type="text" class="form-control text-uppercase black-input col-lg-2" value="{$saData['MDATOS']['CNROSER']}" readonly>
            <div class="input-group-prepend col-lg-2 px-0">
               <span class="input-group-text w-100 new-ucsm  ">Color</span>
            </div>
            <input type="text" class="form-control text-uppercase black-input col-lg-2" value="{$saData['MDATOS']['CCOLOR']}" readonly>
            <div class="input-group-prepend col-lg-2 px-0">
               <span class="input-group-text w-100 new-ucsm  ">Motor</span>
            </div>
            <input type="text" class="form-control text-uppercase black-input col-lg-2" value="{$saData['MDATOS']['CMOTOR']}" readonly>      
         </div>
      </div>
      <br>
      <div class="card-footer text-center">
         <button type="submit" name="Boton1" value="Baja" style="font-weight: 500;" class="btn btn-primary col-sm-2" formnovalidate>
            <i class="fa fa-file-excel"></i>&nbsp;&nbsp;BAJA</button> 
         <button type="submit" name="Boton1" value="Regresar" style="font-weight: 500;" class="btn btn-danger col-sm-2" formnovalidate>
            <i class="fas fa-undo-alt"></i>&nbsp;&nbsp;REGRESAR</button> 
      </div>
   </div>  
   {/if}
   </div>
   <br><br><br>
   <div id="footer"></div>
</form>
<!-- MODAL PARA MOSTRAR DEPRECIACION -->
<div class="modal fade" id="nuevoDetalle1" tabindex="-1" id="detalle">
   <div class="modal-dialog mw-60" role="document">
      <div class="modal-content" >
         <div class="modal-header bg-sc-ucsm">
            <h5 class="modal-title">DEPRECIACIÓN DEL ACTIVO FIJO</h5>
            <button type="button" class="close" data-dismiss="modal">
               <span aria-hidden="true">&times;</span>
            </button>
         </div>
         <div class="modal-body" >
            {if $saDato eq null}
            <div class="input-group mb-0">
               <div class="input-group-prepend col-lg-3 px-0">
                  <span class="input-group-text w-100 bg-ucsm">Valores</span>
               </div>
               <div class="input-group-prepend col-lg-3 px-0">
                  <span class="input-group-text w-100 bg-ucsm">Valor de Aper./Adq.</span>
               </div>
               <div class="input-group-prepend col-lg-3 px-0">
                  <span class="input-group-text w-100 bg-ucsm">Actualizado</span>
               </div>
               <div class="input-group-prepend col-lg-3 px-0">
                  <span class="input-group-text w-100 bg-ucsm">Final</span>
               </div>
            </div>
            <br>
            <div class="input-group mb-0">
               <div class="input-group-prepend col-lg-12 px-0">
                  <span class="input-group-text w-100" style="background-color: lightblue">ACTIVIVO FIJO SIN DEPRECIACIÓN</span>
               </div>
            </div>
            {else}
            <div class="input-group mb-0">
               <div class="input-group-prepend col-lg-3 px-0">
                  <span class="input-group-text w-100 bg-ucsm">Valores</span>
               </div>
               <div class="input-group-prepend col-lg-3 px-0">
                  <span class="input-group-text w-100 bg-ucsm">Valor de Aper./Adq.</span>
               </div>
               <div class="input-group-prepend col-lg-3 px-0">
                  <span class="input-group-text w-100 bg-ucsm">Actualizado</span>
               </div>
               <div class="input-group-prepend col-lg-3 px-0">
                  <span class="input-group-text w-100 bg-ucsm">Final</span>
               </div>
            </div>
            <div class="input-group mb-0" >
               <div class="input-group-prepend col-lg-3 px-0">
                  <span class="input-group-text w-100 bg-ucsm">Activo</span>
               </div>
               <input type="text" class="form-control form-control-sm col-sm-3" style="text-align: right; font-size: 18px; color: black;" value="{$saDato['NMONCAL']|number_format:2:".":","}" >
               <input type="text" class="form-control form-control-sm col-sm-3" style="text-align: right; font-size: 18px; color: black;" value="{$saDato['NMONCAL']|number_format:2:".":","}">
               <input type="text" class="form-control form-control-sm col-sm-3" style="text-align: right; font-size: 18px; color: black;" value="{$saDato['NMONCAL']|number_format:2:".":","}">
            </div>
            <div class="input-group mb-0">
               <div class="input-group-prepend col-lg-3 px-0">
                  <span class="input-group-text w-100 bg-ucsm">Depreciación</span>
               </div>
               <input type="text" class="form-control form-control-sm col-sm-3" style="text-align: right; font-size: 18px; color: black;" value="{$saDato['NDEPREC']|number_format:2:".":","}">
               <input type="text" class="form-control form-control-sm col-sm-3" style="text-align: right; font-size: 18px; color: black;" value="{$saDato['NDEPTOT']|number_format:2:".":","}">
               <input type="text" class="form-control form-control-sm col-sm-3" style="text-align: right; font-size: 18px; color: black; " value="{$saDato['NDEPTOT']|number_format:2:".":","}">
            </div>
            <div class="input-group mb-0">
               <div class="input-group-prepend col-lg-3 px-0">
                  <span class="input-group-text w-100 bg-ucsm">Neto</span>
               </div>
               <input type="text" class="form-control form-control-sm col-sm-3" style="text-align: right; font-size: 18px; color: black;" value="{$saDato['NVALCAL']|number_format:2:".":","}">
               <input type="text" class="form-control form-control-sm col-sm-3" style="text-align: right; font-size: 18px; color: black;" value="{$saDato['NDEPACT']|number_format:2:".":","}">
               <input type="text" class="form-control form-control-sm col-sm-3" style="text-align: right; font-size: 18px; color: black;" value="{$saDato['NDEPACT']|number_format:2:".":","}">
            </div>
            <div class="input-group mb-0">
               <div class="input-group-prepend col-lg-3 px-0">
                  <span class="input-group-text w-100 bg-ucsm">Ult. Depreciación</span>
               </div>
               <input type="text" class="form-control form-control-sm col-sm-3" style="text-align: right; font-size: 18px; color: black;" value="{$saDato['NDEPPER']|number_format:2:".":","}">
            </div>
            {/if}
            <br>
            <div class="input-group">
               <div class="card text-center" >
                  <div style="height:380px; overflow-y: scroll;">
                     <div >
                        <table class="table table-hover table-sm table-bordered">
                           <thead class="thead-dark">
                              <tr class="text-center">
                                 <th style="width: 10rem;">Periodo</th>
                                 <th style="width: 10rem;">Factor</th>
                                 <th style="width: 10rem;">Depr. Act.</th>
                                 <th style="width: 10rem;">Valor Neto</th>
                              </tr>
                           </thead>
                           <tbody style="color: black;">
                              {foreach from=$saDepre item=i}
                                 <tr class="text-center" class="custom-select" multiple>
                                    <td class="text-left">{$i['DMOVIMI']}</td>
                                    <td class="text-right">{$i['NFACTOR']|number_format:2:".":","}%</td>
                                    <td class="text-right">{$i['NMONTO']|number_format:2:".":","}</td>
                                    <td class="text-right">{$i['NDEPREC']|number_format:2:".":","}</td>
                                 </tr>
                              {/foreach}
                           </tbody>
                        </table>
                     </div>
                  </div>
               </div>
            </div>
         </div>
         <div class="modal-footer">
            <button type="button" class="btn btn-danger" data-dismiss="modal" onclick="$('#pcError').addClass('d-none');">Cerrar</button>
         </div>
      </div>
   </div>
</div>
</body>
</html>
