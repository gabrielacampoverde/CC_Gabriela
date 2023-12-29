// Creación de Spinner en todo el body
const loBody = document.querySelector('body')
const loSpinnerContent = document.createElement('div')
loSpinnerContent.classList.add('cssload-container')
loSpinnerContent.classList.add('hide')
const loSpinnerBody = document.createElement('div')
loSpinnerBody.classList.add('cssload-speeding-wheel')
const loSpinnerImg = document.createElement('img')
loSpinnerImg.classList.add('mb-1');
loSpinnerImg.src = 'img/logo_ucsm.png'
const loSpinnerText = document.createElement('span')
loSpinnerText.innerText = 'Cargando...'

loSpinnerBody.appendChild(loSpinnerImg)
loSpinnerBody.appendChild(loSpinnerText)
loSpinnerContent.appendChild(loSpinnerBody)
loBody.appendChild(loSpinnerContent)


function f_setSpinner(estadoSpinner, pcLabelSpinner = 'Cargando...') {
    const spinnerContainer = document.querySelector('.cssload-container');
    loSpinnerText.innerText = pcLabelSpinner;
    if (estadoSpinner) {
        spinnerContainer.classList.add('show');
        spinnerContainer.classList.remove('hide');
    } else {
        spinnerContainer.classList.add('hide');
        spinnerContainer.classList.remove('show');
    }
}


