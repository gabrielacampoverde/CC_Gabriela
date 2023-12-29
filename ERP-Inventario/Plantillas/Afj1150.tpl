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
         var lcTipAfj = document.getElementById("pcTipAfj").value;
         if (lcTipAfj.length < 5) {
            alert("DEBE INGRESAR 5 CARACTERES PARA LA BÚSQUEDA");
            return;
         }
         // var lcDescri = document.getElementById("pcDescri");
         // lcDescri.innerHTML = '';
         var lcSend = "Id=BuscarTipoActivo&pcTipAfj=" + lcTipAfj;
         // alert(lcSend);
         $.post("Afj1150.php", lcSend).done(function(lcResult) {
            // console.log(lcResult);
            var laJson = JSON.parse(lcResult);
            if (laJson.ERROR) {
               alert(laJson.ERROR);
            } else {
               var lcDescri = document.getElementById("pcDescri");
               lcDescri.text = laJson.CDESCRI;
               lcDescri.value = laJson.CDESCRI;
               lcDescri.add;
               $('#lcDescri').selectpicker('refresh');
               var lcFacDep = document.getElementById("pcFacDep");
               lcFacDep.text = laJson.NFACDEP;
               lcFacDep.value = laJson.NFACDEP;
               lcFacDep.add;
               $('#lcFacDep').selectpicker('refresh');
               var lcCntAct = document.getElementById("pcCntAct");
               lcCntAct.text = laJson.CCNTACT;
               lcCntAct.value = laJson.CCNTACT;
               lcCntAct.add;
               $('#lcCntAct').selectpicker('refresh');
               var lcCntDep = document.getElementById("pcCntDep");
               lcCntDep.text = laJson.CCNTDEP;
               lcCntDep.value = laJson.CCNTDEP;
               lcCntDep.add;
               $('#lcCntDep').selectpicker('refresh');
               var lcCntCtr = document.getElementById("pcCntCtr");
               lcCntCtr.text = laJson.CCNTCTR;
               lcCntCtr.value = laJson.CCNTCTR;
               lcCntCtr.add;
               $('#lcCntCtr').selectpicker('refresh');
               var lcCntBaj = document.getElementById("pcCntBaj");
               lcCntBaj.text = laJson.CCNTBAJ;
               lcCntBaj.value = laJson.CCNTBAJ;
               lcCntBaj.add;
               $('#lcCntBaj').selectpicker('refresh');
               var lcClase= document.getElementById("pcClase");
               lcClase.text = laJson.CCLASE;
               lcClase.value = laJson.CCLASE;
               lcClase.add;
               $('#lcClase').selectpicker('refresh');
               var lcEstado= document.getElementById("pcEstado");
               lcEstado.text = laJson.CESTADO;
               lcEstado.value = laJson.CESTADO;
               lcEstado.add;
               $('#lcEstado').selectpicker('refresh');
            }
         });
      }
   </script>   
</head>
<body class="bg-light text-dark">
<div id="header"></div>
<form action="Afj1150.php" method="post" enctype="multipart/form-data" id="poForm">
   <input type="hidden" name="Id" id="Id">
   <input type="hidden" name="p_nBehavior" id="p_nBehavior">
   <div class="container-fluid"> 
      {if $snBehavior eq 0}
         <div class="card-header text-dark" style="background:#fbc804; color:black;">
            <div class="input-group input-group-sm d-flex justify-content-between KTitCar">
               <div class="col text-left"><strong>TIPO DE ACTIVO FIJO</strong></div>
               <div class="col-auto"><b>{$scNombre}</b></div>
            </div>
         </div>
         <div class="card">
            <div class="p-2 card-body">
               <div class="input-group mb-1">
                  <div class="input-group-prepend col-lg-2 px-0">
                     <span class="input-group-text w-100 new-ucsm KTitCam">Código</span>
                  </div>
                  <!-- <input type="hidden"  value="{$saData['CTIPACT']}" name="paData[CTIPACT]"> -->
                  <input type="text" id="pcTipAfj" value="*" name="paData[CTIPACT]" class="form-control form-control-sm col-sm-3" style="text-transform: uppercase;" autofocus>
                  <span class="input-group-btn" >
                     <button type="button" class="btn btn-info" id="b_buscar" style="height: 42px !important;" onclick="f_agregarDetalle();"> 
                        <i class="fas fa-search"></i>&nbsp;&nbsp;BUSCAR
                     </button>
                  </span>&nbsp;&nbsp;
                  <div class="input-group-prepend col-lg-2 px-0">
                     <span class="input-group-text w-100 new-ucsm KTitCam">Estado</span>
                  </div>
                  <select class="form-control form-control-sm black-input col-sm-12 col-lg-4" id="pcEstado" name="paData[CCODEST]">
                     {foreach from=$saDatEst item=i}
                        <option value="{$i['CCODEST']}">{$i['CDESESR']}</option>
                     {/foreach}                     
                  </select>
               </div>
               <div class="input-group mb-1">
                  <div class="input-group-prepend col-lg-2 px-0">
                     <span class="input-group-text w-100 new-ucsm KTitCam">Descripción</span>
                  </div>
                  <input type="text" maxlength="200" id="pcDescri" class="form-control text-uppercase black-input" name="paData[CDESCRI]" >
               </div>
               <div class="input-group mb-1">
                  <div class="input-group-prepend col-lg-2 px-0">
                     <span class="input-group-text w-100 new-ucsm KTitCam">Clase de Activo</span>
                  </div>
                  <!-- <input type="hidden" name="paData[CCLASE]" id="pcClase" readonly> -->
                  <select class="form-control form-control-sm black-input col-sm-12 col-lg-4"  id="pcClase" name="paData[CCLASE]" >
                     {foreach from=$saDatCla item=i}
                        <option value="{$i['CCODCLA']}">{$i['CDESCLA']}</option>
                     {/foreach}                     
                  </select>
                  <div class="input-group-prepend col-lg-2 px-0">
                     <span class="input-group-text w-100 new-ucsm KTitCam">Cuenta Activo</span>
                  </div>
                  <input type="text" maxlength="12" id="pcCntAct" class="form-control text-uppercase black-input col-lg-4" name="paData[CCNTACT]" >                  
               </div>
               <div class="input-group mb-1">
                  <div class="input-group-prepend col-lg-2 px-0">
                     <span class="input-group-text w-100 new-ucsm KTitCam">Factor Depreciación</span>
                  </div>
                  <input type="text"  id="pcFacDep" class="form-control text-uppercase black-input col-lg-4" name="paData[CFACDEP]">
                  <div class="input-group-prepend col-lg-2 px-0">
                     <span class="input-group-text w-100 new-ucsm KTitCam">Cuenta Depreciación</span>
                  </div>
                  <input type="text" maxlength="12" id="pcCntDep" name="paData[CCNTDEP]" class="form-control text-uppercase black-input col-lg-4" >
               </div>
               <div class="input-group mb-1">
                  <div class="input-group-prepend col-lg-2 px-0">
                     <span class="input-group-text w-100 new-ucsm KTitCam">Cuenta Baja</span>
                  </div>
                  <input type="text" maxlength="12" id="pcCntBaj" name="paData[CCNTBAJ]" class="form-control text-uppercase black-input col-lg-4">  
                  <div class="input-group-prepend col-lg-2 px-0">
                     <span class="input-group-text w-100 new-ucsm KTitCam">Contra-Cuenta</span>
                  </div>
                  <input type="text" maxlength="12" id="pcCntCtr" name="paData[CCNTCTR]" class="form-control text-uppercase black-input col-lg-4">              
               </div>
            </div>
            <br>
            <div class="card-footer text-center">
               <div class="card-footer text-center">
                  <button type="submit" name="Boton" value="GuardarTipoAct" style="font-weight: 500;" class="btn btn-primary col-md-2" formnovalidate>GUARDAR</button> 
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
