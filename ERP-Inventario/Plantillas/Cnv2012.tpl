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
<!-- <link rel="stylesheet" href="path/to/font-awesome/css/font-awesome.min.css"> -->
<link rel="stylesheet" href="css/style.css">
<link rel="stylesheet" href="css/style-input.css">   
<style>
  .black-input:focus {
    background: #FDFD96;
    color: black;
  }
</style>
</head>
<body>
<div id="header"></div>
<form action="Cnv2012.php" method="post" enctype="multipart/form-data">
<main role="main" class="container-fluid">
  <input type="hidden" name="Id" id="Id">
  <input type="hidden" name="p_nBehavior" id="p_nBehavior">
  <div class="container-fluid">
    <div class="card-header text-dark " style="background:#fbc804; color:black;">
        <div class="input-group input-group-sm d-flex justify-content-between">
          <div class="col text-left"><strong>CONVALIDACIONES</strong></div>
          <div class="col-auto"><b>{$scNombre}</b></div>
        </div>
    </div>
      {if $snBehavior eq 0}
        <div class="card-body" height= "54rem">
         <div class="row d-flex justify-content-center">
         <div class="col-sm-8">
         <div class="card text-center ">
         <div class="card-body" >
            {* Buscar activo fijo *}
            <div style="padding-left: 5rem;">
               <div class="input-group mb-1">
                  <div class="input-group-prepend col-lg-3 px-0">
                     <span class="input-group-text w-100 bg-ucsm">Unidad Académica</span>
                  </div>
                  <select class="selectpicker form-control col-sm-10 black-input col-lg-8" data-live-search="true" name="paData[CUNIACA]" >
                     {foreach from=$saDatos item=i}                        
                        <option value="{$i['CUNIACA']}">{$i['CUNIACA']} - {$i['CNOMUNI']}</option>
                     {/foreach}                                          
                  </select>
               </div>
           </div>
           <div class="card-footer text-center">
              <button type="submit" name="Boton" value="Siguiente" class="btn btn-primary col-md-2"> 
                 <i class="fas fa-search"></i>&nbsp;&nbsp;SIGUIENTE</button>
              <button type="submit" name="Boton" value="Salir" class="btn btn-danger col-md-2" formnovalidate>
                 <i class="fas fa-undo-alt"></i>&nbsp;&nbsp;MENÚ</button>
           </div>
        </div>
        </div>
        </div>
        </div>
        </div>
      {else if $snBehavior eq 1}
      <div class="card-footer text-muted">
        <div class="row d-flex justify-content-center">      
          <div class="col-md-6" >
            <canvas class="cCobr" id="miGrafico" height="300" width="500"></canvas>    
          </div>
        </div>
        <br><br><br><br>
        <div class="card-footer text-center">
          <button type="submit"  name="Boton1" value="Regresar" class="btn btn-danger col-sm-2 col-md-4" formnovalidate>ATRAS</button>
        </div>
        
      </div>
      {/if}
      <div id="Datos" style="display: none;">
        {json_encode($saDatos)}
      </div> 
      <div id="Data" style="display: none;">
        {json_encode($saData)}
      </div>     
</div>
</main>
</form>

<div id="footer"></div>  
<script src="js/jquery-3.1.1.min.js"></script>
<script src="js/jquery-ui-1.12.1/jquery-ui.js"></script>
<script src="bootstrap4/js/bootstrap.bundle.min.js"></script>
<script src="bootstrap4/js/bootstrap-select.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.5.0/chart.min.js" integrity="sha512-asxKqQghC1oBShyhiBwA+YgotaSYKxGP1rcSYTDrB0U6DxwlJjU59B67U8+5/++uFjcuVM8Hh5cokLjZlhm3Vg==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.5.0/chart.esm.min.js" integrity="sha512-h0dSZkvjBWllHan49Ajy8Tk9UVa27kFrqUyQl652qZAwHBJw4lvszsqxWS+A3VcS4QJreD1n9QN2/TYIHHiQpw==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.5.0/helpers.esm.min.js" integrity="sha512-b3xZ1Eh852+/Ltha4XJd59YP2d+I+B6NPdB4H+Wns29GX9x5pLwlp8jnQtJYog3d5Xk1SWvhT2lgJDDBvpV0ow==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js" integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl" crossorigin="anonymous"></script>
<script type="text/javascript" src="js/utils5010.js"></script>
<script type="text/javascript" src="js/Tpt5010.js"></script>
<script type="text/javascript" src="js/java.js"></script>
</body>
</html>