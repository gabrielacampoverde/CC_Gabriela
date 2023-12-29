// var loScriptSwal = document.createElement('script')
// loScriptSwal.setAttribute('src', 'https://cdnjs.cloudflare.com/ajax/libs/sweetalert2/11.7.12/sweetalert2.min.js')
//
// var loStyleSwal = document.createElement('style')
// loStyleSwal.setAttribute('src', 'https://cdnjs.cloudflare.com/ajax/libs/sweetalert2/11.7.12/sweetalert2.min.css')
// document.head.appendChild(loStyleSwal)
// document.head.appendChild(loScriptSwal)

/**
 * Método que muestra un mensaje usando SweetAlert2
 * @param {string | null} title - título del mensaje
 * @param {string | null} text - texto del mensaje
 * @param {string | null} icon - icono del mensaje (success, error, warning, info, question)
 * @param {Function} fResult - funcion configurable según se requiera
 * @param {string | null} confirmacion - configura visualización de la opcion de cancelar y aceptar
 * @returns {null}
 ***/
function f_mensajeAccion(title, text, icon, fResult , confirmacion = 'Aceptar') {
   Swal.fire({
      title,
      text,
      icon,
      allowOutsideClick: false,
      showCancelButton: true,
      confirmButtonColor: '#e9b517',
      cancelButtonColor: '#d33',
      confirmButtonText: confirmacion})
       .then(fResult);
}


/**
 * Método que muestra un mensaje usando SweetAlert2
 * @param {string | null} text - texto del mensaje
 * @param {string | null} icon - icono del mensaje (success, error, warning, info, question)
 * @param {string | null} title - título del mensaje
 * @returns {null}
 ***/
function f_mensajeSimple(text, icon = 'success', title = 'Atención') {
   Swal.fire({
      title,
      text,
      icon,
      allowOutsideClick: false,
      confirmButtonColor: '#e9b517',
      confirmButtonText: 'Aceptar'})
}
/**
 * Método que muestra un mensaje usando SweetAlert2
 * @param {string | null} text - texto del mensaje
 * @param {string | null} icon - icono del mensaje (success, error, warning, info, question)
 * @param {string | null} title - título del mensaje
 * @returns {null}
 ***/
function f_mensajeAlerta(text, icon = 'warning', title = 'Atención') {
   Swal.fire({
      title,
      text,
      icon,
      confirmButtonColor: '#3085d6',
      confirmButtonText: 'Aceptar'})
}

/**
 * Método que muestra una alerta usando SweetAlert2, y despues hace focus sobre un HTMLElement
 * @param {HTMLElement} pcElem - elemento html
 * @param {string | null} text - texto del mensaje
 * @param {string | null} icon - icono del mensaje (success, error, warning, info, question)
 * @param {string | null} title - título del mensaje
 * @returns {null}
 ***/
function f_mensajeFocus(pcElem, text, icon = 'warning', title = 'Atención') {
   Swal.fire({
      title, text, icon,
      allowOutsideClick: false,
      didClose: () => { pcElem.focus() }
   });
}

/**
 * Método que muestra un mensaje usando SweetAlert2
 * @param {HTMLElement} pcElem - elemento html
 * @param {string | null} text - texto del mensaje
 * @param {string | null} icon - icono del mensaje (success, error, warning, info, question)
 * @param {string | null} title - título del mensaje
 * @returns {null}
 ***/
function f_mensajeAlertaAccion(title, text, icon , fResult) {
   Swal.fire({
      title, text, icon,
      allowOutsideClick: false,
      didClose: fResult
   })
}
/**
 * Método que muestra un mensaje usando SweetAlert2, permite el ingreso de un input textarea y el envio del valor mismo
 * mediante una funcion
 * @param {string} title - titulo del modal
 * @param {string} btnLabelAceptar - texto para boton de confirmación
 * @param {function} fResult - funcion que se ejecutara despues del ingreso del texto
 * @returns {null}
 ***/
function f_mensajeTextArea(title, btnLabelAceptar, fResult) {
   Swal.fire({
      title,
      input: 'textarea',
      showCancelButton: true,
      confirmButtonText: btnLabelAceptar,
      cancelButtonText: 'Cancelar',
      allowOutsideClick: false,
      inputValidator: (valor) => {
         if (valor.length == 0) {
            return 'El campo no puede estar vacio'
         }
      }
   })
       .then(fResult)
}


/**
 * Método que hace un llamado a un metodo post, se puede configurar la respuesta al ser exitoso la accion post
 * Requiere Jquery, y que la devolcuion de la respuesta sea json [{OK: true, MENSAJE: 'Detalle accion'}]
 * @param {string} lcUrl - ruta api para metodo post
 * @param {string} lcPath - nombre archivo php para llamado post
 * @param {function} fResult - funcion que se ejecutara despues del ingreso del texto
 * @returns {null}
 ***/
function f_mensajePostAccionSimple(lcUrl, lcPath, fResult) {
   $.post(lcPath,lcUrl)
       .done(function(p_cResult) {
          if (!isJson(p_cResult)) {
             f_mensajeAlerta('Ocurrio un error, contacte con soporte del ERP', 'error')
             return;
          }
          let laJson = JSON.parse(p_cResult)
          if (!laJson.OK) {
             f_mensajeAlerta(laJson.MENSAJE, 'warning')
          } else {
             f_mensajeAlertaAccion('Atencion', laJson.MENSAJE, 'success', fResult)
          }
       })
       .fail(error => {
          f_mensajeAlerta('Ocurrio un error, contacte con soporte del ERP', 'error')
       })
}
/**
 * Método que hace un llamado a un metodo post, se puede configurar la respuesta al ser exitoso la accion post
 * Requiere Jquery, y que la devolcuion de la respuesta sea json [{OK: true, MENSAJE: 'Detalle accion'}]
 * @param {string} lcUrl - ruta api para metodo post
 * @param {string} lcPath - nombre archivo php para llamado post
 * @param {function} fResult - funcion que se ejecutara despues del ingreso del texto
 * @returns {null}
 ***/
function f_mensajePostAccionContenido(lcUrl, lcPath, fResult) {
   $.post(lcPath,lcUrl)
       .done(function(p_cResult) {
          if (!isJson(p_cResult)) {
             f_mensajeAlerta('Ocurrio un error, contacte con soporte del ERP', 'error')
             return;
          }
          let laJson = JSON.parse(p_cResult)
          if (!laJson.OK) {
             f_mensajeAlerta(laJson.MENSAJE, 'warning')
              f_setSpinner(false)
          } else {
             fResult(laJson.CONTENIDO)
          }
       })
       .fail(error => {
           f_setSpinner(false)
          f_mensajeAlerta('Ocurrio un error, contacte con soporte del ERP', 'error')
       })
}

function f_mensajeSelectAccion(title, options, lblBtnOk, lblBtnCancel, fResult) {
    Swal.fire({
        title,
        input: 'select',
        inputOptions: options,
        confirmButtonText: lblBtnOk,
        confirmButtonColor: '#e9b517',
        cancelButtonText: lblBtnCancel,
        cancelButtonColor: '#cc4100',
        showCancelButton: true,
        inputPlaceholder: 'Seleccione una opción',
        allowOutsideClick: false,
        inputValidator: (value) => {
            return new Promise((resolve) => {
                if (value === '') {
                    resolve('Elija una opción válida')
                } else {
                    resolve()
                }
            })
        }
    })
        .then(data => {
            fResult(data)
        })
}