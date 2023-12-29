// ------------------------------------------------------------------------------
// DASHBOARD EVOLUCION COBRANZA
// Creacion  2021-07-16 GCH
// ------------------------------------------------------------------------------
$(document).ready(function() {
    let datos = document.getElementById('Datos');
    let cCobr = JSON.parse(datos.textContent);
    console.log(cCobr);
    const labels = [];
    const data = [];
    const data1 = [];
    for (let item of cCobr) {
        item.DFECHA && labels.push(item.DFECHA);
        item.NGIRADO && data.push(parseFloat(item.NGIRADO));
        item.NPAGADO && data1.push(parseFloat(item.NPAGADO));
    }
    console.log(labels);
    console.log(data);
    console.log(data1);

    if (cCobr.length > 0) {
        const grafico = new Chart( $("#miGrafico"), getChartConfig(data,data1,labels));
    }
});

$(document).ready(function() {
    let datos = document.getElementById('Datos');
    let cCobr1 = JSON.parse(datos.textContent);
    console.log(cCobr1);
    const labels = [];
    const data1 = [];
    const data2 = [];
    for (let item of cCobr1) {
        item.DFECHA && labels.push(item.DFECHA);
        item.NVENCID && data1.push(parseFloat(item.NVENCID));
        item.NPORVEN && data2.push(parseFloat(item.NPORVEN));
    }
    console.log(labels);
    console.log(data1);
    console.log(data2);

    if (cCobr1.length > 0) {
        const grafico2 = new Chart( $("#miGrafico1"), getChartConfig1(data1,labels));
        const grafico3 = new Chart( $("#miGrafico2"), getChartConfig2(data2,labels));
    }
});