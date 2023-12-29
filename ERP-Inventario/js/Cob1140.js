// ------------------------------------------------------------------------------
// DASHBOARD DESARROLLO DE LA MORA
// Creacion  2021-06-25 GCH
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

    let cFecha = [];
    let nMonto = [];

    for (let item of cCobr) {
        item.CFECHA ? cFecha.push(item.CFECHA) : null;
        item.NMONTO ? nMonto.push(item.NMONTO) : null;
    }

    if (cFecha.length > 0 && nMonto.length > 0) {
        let chartData = {
            labels: cFecha,
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
// DASHBOARD DESARROLLO DE LA MORA
// Creacion  2021-06-25 GCH
// ------------------------------------------------------------------------------
$(document).ready(function() {
    const tableHead1 = document.getElementById('tableHead1');
    const tableBody1 = document.getElementById('tableBody1');
    tableBody1.style.display = 'none';
    tableHead1.addEventListener('click',() => {
        tableBody1.style.display = tableBody1.style.display == 'none' ? 'contents' : 'none';
    });

    let x = document.getElementById('datos');
    let y = document.getElementById('data');
    let cCobr = JSON.parse(x.textContent);
    let cData = JSON.parse(y.textContent);


    let cFecha = [];
    let nPorcen = [];

    for (let item of cCobr) {
        item.CFECHA ? cFecha.push(item.CFECHA) : null;
        item.NPORCEN ? nPorcen.push(item.NPORCEN) : null;
    }

    if (cFecha.length > 0 && nPorcen.length > 0) {
        let chartData = {
            labels: cFecha,
            datasets: [{
                    label: 'Porcentaje',
                    data: nPorcen,
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
