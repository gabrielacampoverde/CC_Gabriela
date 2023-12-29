// ------------------------------------------------------------------------------
// DASHBOARD COBRANZA 2021-1
// Creacion  2021-08-09 GCH
// ------------------------------------------------------------------------------
$(document).ready(function() {
    let datos = document.getElementById('Datos');
    let cCobr = JSON.parse(datos.textContent);
    let y = document.getElementById('Data');
    let cData = JSON.parse(y.textContent);
    const labels = [];
    const data = [];
    for (let item of cCobr) {
        item.DFECHA && labels.push(item.DFECCOR);
        item.NCOBRAD && data.push(parseFloat(item.NCOBRAD));
    }
    console.log(labels);
    console.log(data);
    if (cCobr.length > 0) {
        const grafico = new Chart( $("#miGrafico"), getChartConfig(data,labels, cData));
    }
});

$(document).ready(function() {
    let datos = document.getElementById('Datos');
    let cCobr1 = JSON.parse(datos.textContent);
    let y = document.getElementById('Data');
    let cData = JSON.parse(y.textContent);
    console.log(cCobr1);
    const labels = [];
    const data1 = [];
    for (let item of cCobr1) {
        item.DFECHA && labels.push(item.DFECCOR);
        item.NPAGADO && data1.push(parseFloat(item.NPAGADO));
    }
    console.log(labels);
    console.log(data1);

    if (cCobr1.length > 0) {
        const grafico2 = new Chart( $("#miGrafico1"), getChartConfig1(data1,labels,cData));
    }
});
