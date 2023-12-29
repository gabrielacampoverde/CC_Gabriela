$(function () {
   $.ajaxSetup({ cache: false });
   $("#header").load("Plantillas/xheader.html");
   $("#headerw").load("Plantillas/xheaderw.html");
   $("#headerInv").load("Plantillas/xheaderInvitados.html");
   $("#headert").load("Plantillas/xheaderTramites.html");
   $("#headerpt").load("Plantillas/xheaderpt.html");
   $("#headerp").load("Plantillas/xheaderp.html");
   $("#headerE").load("Plantillas/header.html");
   $("#footer").load("Plantillas/xfooter.html");
   $("#headert_v2").load("Plantillas/xheaderpt_v2.html");
   $('[data-toggle="tooltip"]').tooltip();
   $(".datepicker").datepicker({
      changeMonth: true,
      changeYear: true,
      dateFormat: 'yy-mm-dd'
   });
})

function UpperCaseF(a) {
   a.value = a.value.toUpperCase();
}

function isNumber(n) {
   return !isNaN(parseFloat(n)) && isFinite(n);
}

function f_validateNumber(e, decimals) {
   var num = e.value;
   if (!isNaN(parseFloat(num)) && isFinite(num)) {
      e.value = parseFloat(Math.round(num * 100) / 100).toFixed(decimals);
   } else {
      e.value = 0;
   }
}

//var lvarName = getUrlVars()["varName"];
function getUrlVars() {
   var vars = {};
   var parts = window.location.href.replace(/[?&]+([^=&]+)=([^&]*)/gi, function(m,key,value) {
      vars[key] = value;
   });
   return vars;
}

function isJson(str) {
   try {
      JSON.parse(str);
   } catch (e) {
      return false;
   }
   return true;
}

function numberFormat(num, dec = 0) {
   num = parseFloat(Math.round(num * 100) / 100).toFixed(dec);
   return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}