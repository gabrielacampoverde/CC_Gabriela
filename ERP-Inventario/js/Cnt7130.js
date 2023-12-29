// ------------------------------------------------------------------------------
// DEVOLUCIONES TRAMITADAS -  DASHBOARD
// Creacion  2021-05-20 GCH
// ------------------------------------------------------------------------------
$(document).ready(function() {
    const tableHead = document.getElementById('tableHead');
    const tableBody = document.getElementById('tableBody');
    console.log(tableBody);
    tableBody.style.display = 'none';
    tableHead.addEventListener('click',()=> {
        tableBody.style.display = tableBody.style.display == 'none' ? 'contents' : 'none';
    });

    let datos = document.getElementById('datos');
    let data = document.getElementById('data');

    let cDevTra = JSON.parse(datos.textContent);
    let cData = JSON.parse(data.textContent);
    console.log(cDevTra); 
    console.log(cData);

    let cDesEst = [];
    let nCantid = [];

    for (let item of cDevTra) {
        item.CDESEST ? cDesEst.push(item.CDESEST) : null;
        item.NCANTID ? nCantid.push(item.NCANTID) : null;
    }

    console.log(cDesEst);
    console.log(nCantid);
    if (cDesEst.length > 0 && nCantid.length > 0) {
        let chartData = {
            labels: cDesEst,
            datasets: [{
                    label: 'Saldo',
                    data: nCantid,
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
                title: {
                    display: true,
                    text: 'DEVOLUCIONES TRAMITADAS',
              }
            }
        });
    }
});