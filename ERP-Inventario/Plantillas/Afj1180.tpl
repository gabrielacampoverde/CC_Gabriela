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
      function f_agregarDetalle() {
         var lcCtaCnt = document.getElementById("pcCtaCnt").value;
         if (lcCtaCnt.length < 5) {
            alert("DEBE INGRESAR 5 CARACTERES PARA LA BÚSQUEDA");
            return;
         }
         // var lcDescri = document.getElementById("pcDescri");
         // lcDescri.innerHTML = '';
         var lcSend = "Id=BuscarCuenta&pcCtaCnt=" + lcCtaCnt;
         // alert(lcSend);
         $.post("Afj1180.php", lcSend).done(function(lcResult) {
            //console.log(lcResult);
            var laJson = JSON.parse(lcResult);
            if (laJson.ERROR) {
               alert(laJson.ERROR);
            } else {
               var lcDescri = document.getElementById("pcDescri");
               lcDescri.text = laJson.CDESCRI;
               lcDescri.value = laJson.CDESCRI;
               lcDescri.add;
               $('#lcDescri').selectpicker('refresh');
               var lcCenCos= document.getElementById("pcCenCos");
               //console.log(laJson['PADATOS'][1]['CCENCOS']);
               for(var i = 0; i < laJson['PADATOS'].length; i++){
                 //console.log(laJson['PADATA']['CCENCOS']);
                  //console.log(laJson['PADATOS']);
                  var loOption = document.createElement("option");
                  if(laJson['PADATA']['CCENCOS'] == laJson['PADATOS'][i]['CCENCOS']){
                     console.log(laJson['PADATOS'][i]['CCENCOS']);
                     loOption.setAttribute("selected", laJson['PADATOS'][i].CCENCOS + ` - ` + laJson['PADATOS'][i].CDESCRI);
                     //lcCenCos.value = laJson.CCENCOS;
                     //lcCenCos.add;
                     //$('#lcCenCos').selectpicker('refresh');
                  }
               }
               
               
               
            }
         });
      }
   </script>   
</head>
<body class="bg-light text-dark">
<div id="header"></div>
<form action="Afj1180.php" method="post" enctype="multipart/form-data" id="poForm">
   <input type="hidden" name="Id" id="Id">
   <input type="hidden" name="p_nBehavior" id="p_nBehavior">
   <div class="container-fluid"> 
      {if $snBehavior eq 0}
         <div class="card-header text-dark" style="background:#fbc804; color:black;">
            <div class="input-group input-group-sm d-flex justify-content-between KTitCar">
               <div class="col text-left"><strong>CREAR CUENTA CONTABLE</strong></div>
               <div class="col-auto"><b>{$scNombre}</b></div>
            </div>
         </div>
         <div class="card">
            <div class="p-2 card-body">
               <div class="input-group mb-1">
                  <div class="input-group-prepend col-lg-2 px-0">
                     <span class="input-group-text w-100 new-ucsm KTitCam">Cuenta</span>
                  </div>
                  <input type="text" id="pcCtaCnt" name="paData[CCTACNT]" class="form-control form-control-sm col-sm-3" style="text-transform: uppercase;" value="{$saDatas['CCTACNT']}"  autofocus>
                  <span class="input-group-btn" >
                     <button type="submit" name="Boton" value="BuscarCuenta" class="btn btn-info" id="b_buscar" style="height: 42px !important;"> 
                        <i class="fas fa-search"></i>&nbsp;&nbsp;BUSCAR
                     </button>
                  </span>
               </div>
               <div class="input-group mb-1">
                  <div class="input-group-prepend col-lg-2 px-0">
                     <span class="input-group-text w-100 new-ucsm KTitCam">Descripción</span>
                  </div>
                  <input type="text" maxlength="200" id="pcDescri" class="form-control text-uppercase black-input" name="paData[CDESCRI]" value="{$saDatas['CDESCRI']}">
               </div>
               <div class="input-group mb-1">
                  <div class="input-group-prepend col-lg-2 px-0">
                     <span class="input-group-text w-100 new-ucsm">Centro de Costo</span>
                  </div>
                  <select class="selectpicker form-control col-sm-12 black-input col-lg-6" data-live-search="true" id="pcCenCos" name="paData[CCENCOS]" >
                     {foreach from=$saCenCos item=i}                        
                        <option value="{$i['CCENCOS']}" {if $i['CCENCOS'] eq $saDatas['CCENCOS']} selected {/if}>{$i['CDESCRI']}</option>
                     {/foreach}                                          
                  </select>
               </div>
            <br>
            <div class="card-footer text-center">
               <div class="card-footer text-center">
                  <button type="submit" name="Boton" value="GuardarCuenta" style="font-weight: 500;" class="btn btn-primary col-md-2" formnovalidate>GUARDAR</button> 
                  <button type="submit" name="Boton" value="Salir" style="font-weight: 500;" class="btn btn-danger col-md-2" formnovalidate>SALIR</button> 
               </div>
            </div>
         </div>
      {/if}
   </div>
   <br><br><br>
   <div id="footer"></div>
</form>
</body>
</html>
