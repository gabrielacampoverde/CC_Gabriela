// ------------------------------------------------------------------------------
// CAJA
// Creacion  2021-05-10 GCH
// ------------------------------------------------------------------------------
$(document).ready(function() {
    let x = document.getElementById('datos');
    let y = document.getElementById('data');
    let cCheBPC = JSON.parse(x.textContent);
    let cData = JSON.parse(y.textContent);
    // console.log(cCheBPC); 
    // console.log(cData);
    let cPeriod = [];
    let nSaldo = [];
    let nDescri = [];

    for (let item of cCheBPC) {
        item.CPERIOD ? cPeriod.push(item.CPERIOD) : null;
        item.NSALDO ? nSaldo.push(item.NSALDO) : null;
    }
    console.log(cPeriod);
    console.log(nSaldo);
    if (cPeriod.length > 0 ) {
        let chartData = {
            labels: cPeriod,
            datasets: [{
                    label: cData.CSUBTIT,
                    data: nSaldo,
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
                    text: cData.CDESCRI
              }
            }
        });
    }
});