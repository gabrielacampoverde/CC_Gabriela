// ------------------------------------------------------------------------------
// MORA - ESCUELAS PROFESIONALES - POR MONTO
// Creacion  2021-06-30 GCH
// ------------------------------------------------------------------------------
 $(document).ready(function() {
    // Constantes
    const btnMonto = document.getElementById("btnMonto");
    const montoDiagrama = document.getElementById("montoDiagrama");
    const porcentajeDiagrama = document.getElementById("porcentajeDiagrama");
    const btnPorcentaje = document.getElementById('btnPorcentaje');

    // Inicializa estados
    porcentajeDiagrama.style.display = 'none';
    montoDiagrama.style.display= 'contents';

    // Listeners
    btnMonto.addEventListener('click',() => {
        montoDiagrama.style.display ='contents';
        porcentajeDiagrama.style.display = 'none';
    })
    btnPorcentaje.addEventListener('click',() => {
        porcentajeDiagrama.style.display = 'contents';
        montoDiagrama.style.display = 'none';
    })
  });

$(document).ready(function() {
    const tableHead = document.getElementById('tableHead');
    const tableBody = document.getElementById('tableBody');
    tableBody.style.display = 'none';
    tableHead.addEventListener('click',() => {
        tableBody.style.display = tableBody.style.display == 'none' ? 'contents' : 'none';
    });

    let x = document.getElementById('datos');
    let y = document.getElementById('data');
    let cCobr = JSON.parse(x.textContent);
    let cData = JSON.parse(y.textContent);

    let cNomUni = [];
    let nMonto = [];

    for (let item of cCobr) {
        item.CNOMUNI ? cNomUni.push(item.CNOMUNI) : null;
        item.NMONTO ? nMonto.push(item.NMONTO) : null;
    }

    if (cNomUni.length > 0 && nMonto.length > 0) {
        let chartData = {
            labels: cNomUni,
            datasets: [{
                    label: 'Monto',
                    data: nMonto,
                    backgroundColor: "#3e95cd",
                    borderWidth: 2,
                    hoverBorderWidth: 0,
                },
            ],
        };

        var mostrar = $("#miGrafico");
        var grafico = new Chart(mostrar, {
            type:"bar",
            data:chartData,
            options: {
                plugins:{
                    title: {
                        display: true,
                        text: cData.CDESCRI,
                    }
                }
            }
        });
    }
});

// ------------------------------------------------------------------------------
// MORA - ESCUELAS PROFESIONALES - POR PORCENTAJE
// Creacion  2021-06-30 GCH
// ------------------------------------------------------------------------------
$(document).ready(function() {
    let x = document.getElementById('datos');
    let y = document.getElementById('data');
    let cCobr = JSON.parse(x.textContent);
    let cData = JSON.parse(y.textContent);

    let cNomUni = [];
    let nPorMor = [];

    for (let item of cCobr) {
        item.CNOMUNI ? cNomUni.push(item.CNOMUNI) : null;
        item.NPORMOR ? nPorMor.push(item.NPORMOR) : null;
    }
    console.log(cNomUni);
    console.log(nPorMor);
    if (cNomUni.length > 0 && nPorMor.length > 0) {
        let chartData = {
            labels: cNomUni,
            datasets: [{
                    label: 'Porcentaje',
                    data: nPorMor,
                    backgroundColor: "#3e95cd",
                    borderWidth: 2,
                    hoverBorderWidth: 0,
                },
            ],
        };

        var mostrar = $("#miGrafico1");
        var grafico = new Chart(mostrar, {
            type:"bar",
            data:chartData,
            options: {
                plugins:{
                    title: {
                        display: true,
                        text: cData.CDESCRI,
                    }
                }
            }
        });
    }
});