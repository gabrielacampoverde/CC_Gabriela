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
   <link rel="stylesheet" href="css2/loader.css">
   <link rel="stylesheet" type="text/css" href="./css2/loader.css">
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
      .head-fixed {
         position: sticky;
         top: 0;
         z-index: 1;
      }
   </style>
   <script> 

   </script>   
</head>
<body class="bg-light text-dark">
<div id="header"></div>
<form action="Afj2010.php" method="post" enctype="multipart/form-data" id="poForm">
   <input type="hidden" name="Id" id="Id">
   <input type="hidden" name="p_nBehavior" id="p_nBehavior">
   <div class="container-fluid"> 
      {if $snBehavior eq 0}
         <div class="card-header" style="background:#fbc804; color:black;">
            <div class="input-group input-group-sm d-flex justify-content-between KTitCar">
               <strong>BUSCAR ACTIVOS FIJOS</strong>
               <div class="input-group-prepend px-2"><strong>{$scNombre}</strong></div>
            </div>
         </div>
         <div class="card">
            <div class="p-2 card-body">
               <div class="input-group mb-3">
                  <div class="input-group-prepend">
                     <span class="input-group-text new-ucsm" id="basic-addon1">OPCIÓN</span>
                  </div>
                  <select class="selectpicker form-control col-sm-12 black-input col-lg-4" data-live-search="true" name="paData[OPCION]" >
                        <option >*</option>
                        <option >DESCRIPCIÓN</option>
                        <option >CENTRO DE RESPONSABILIDAD</option> 
                        <option >NRO. SERIE</option>
                        <option >MARCA</option>
                        <option >MODELO</option> 
                        <option >COMPROBANTE</option>
                        <option >CÓDIGO ARTÍCULO</option>                                        
                  </select>
                  <div class="input-group-prepend">
                     <span class="input-group-text new-ucsm" id="basic-addon1">DESCRIPCIÓN</span>
                  </div>
                  <input type="text" name="paData[CCODDES]" class="form-control text-uppercase col-lg-4 black-input" placeholder="Código/Descripción" autofocus>
                  <button type="submit" name="Boton" value="Buscar" id="btnApLicar" class="btn btn-primary col-md-2 " formnovalidate>
                     <i class="fas fa-search"></i>&nbsp;&nbsp;BUSCAR</button>
               </div>                    
               <div style="max-height:790px;">
                  <div class="table-responsive mh-60 mb-2 mt-2" style="color:black">
                     <table id="T_Activos" class="table table-sm table-hover text-12 table-bordered text-center" 
                     style="color:black">
                        <thead style="color:black" class="head-fixed">
                           <tr class="text-center">
                           <th scope="col" class="align-middle text-dark">#</th> 
                           <th scope="col" class="align-middle text-dark">CÓDIGO</th>
                           <th scope="col" class="align-middle text-dark">DESCRIPCIÓN</th>
                           <th scope="col" class="align-middle text-dark">FEC.ADQ.</th>
                           <th scope="col" class="align-middle text-dark">SITUACIÓN</th>
                           <th scope="col" class="align-middle text-dark">CENTRO DE RESPONSABILIDAD</th>
                           <th scope="col" class="align-middle text-dark">EMPLEADO</th>
                           <th scope="col" class="align-middle text-dark">MODELO</th>
                           <th scope="col" class="align-middle text-dark">SERIE</th>
                           <th width="30" style="word-break:break-all;"><i class="fas fa-file-pdf" style="color:#C70039;"></i></th>
                           </tr>
                        </thead>                           
                        <tbody> 
                        {$k = 1}
                        {foreach from=$saDatos item=i}
                           <tr class="text-center" class="custom-select" multiple>
                              <td class="text-center">{$k}</td>
                              <td class="text-center">{$i['CCODIGO']}</td>
                              <td class="text-left">{$i['CDESCRI']}</td>
                              <td class="text-center">{$i['DFECALT']}</td>
                              <td class="text-center">{$i['CSITUAC']} - {$i['CDESSIT']}</td>
                              <td class="text-left">{$i['CCENRES']} - {$i['CDESRES']}</td>
                              <td class="text-left">{$i['CCODEMP']} - {$i['CNOMEMP']}</td>
                              <td class="text-left">{$i['CMODELO']}</td>
                              <td class="text-left">{$i['CNROSER']}</td>
                              <td class="text-center">
                                 <form>
                                    <input type="hidden" name="pnIndice" value="{$k}" required/>
                                    <button type="submit" name="Boton" value="ReportePDF" >
                                       <i class="fas fa-file-pdf icon-tbl" style="color:#C70039;"></i>
                                    </button>
                                 </form>
                              </td>
                           </tr>  
                        {$k = $k + 1}
                        {/foreach}                                                                
                        </tbody>
                     </table>
                  </div>
               </div>                   
            </div>
            
         </div>
         <div class="card-footer text-center">
            <div class="card-footer text-center">
               <button type="submit" name="Boton" value="BusquedaEXCEL" class="btn btn-success col-md-2" style="height: 40px !important;" formnovalidate>
                     <i class="fas fa-file-excel"></i>&nbsp;&nbsp;Reporte EXCEL</button>
               <button type="submit" name="Boton" value="BusquedaPDF" class="btn btn-info col-md-2" style="height: 40px !important;" formnovalidate>
                  <i class="fas fa-file-pdf"></i>&nbsp;&nbsp;Reporte PDF</button>
               <button type="submit" name="Boton" value="Salir" class="btn btn-danger col-md-2" style="height: 40px !important;" formnovalidate>
                  <i class="fas fa-undo-alt"></i>&nbsp;&nbsp;MENÚ</button>
            </div>
         </div>
      {/if}
   </div>
   <div id="footer"></div>
<div class="modal fade" id="modal_loading" data-backdrop="static" data-keyboard="false" tabindex="-1">
   <div class="modal-dialog modal-lg" style="margin:10rem auto;max-width:300px" role="document">
      <div class="modal-content">
         <div class="modal-body">
            <div class="notificaciones">
               <div class="wrap">
                  <ul class="lista" id="lista">
                  <br>
                     <li>
                        <div class="container-fluid">
                           <div class="row">
                              <div class="col-sm-12">
                                 <center>
                                 <h5>Procesando, espere por favor...</h5>
                                 <img src="img/loading.gif" width="150" height="200" class="d-inline-block align-top" alt="">
                                 </center>
                              </div>
                           </div>
                        </div>
                        <br>
                     </li>
                  </ul>
               </div>
            </div>
         </div>
      </div>
   </div>
</div> 
</form>
<script>
   $("#btnApLicar").click("click", function(){
        // alert("event click");
        let md = $("#modal_loading");
        console.log(md);
        md.modal('show');
   });
</script>
</body>
</html>
