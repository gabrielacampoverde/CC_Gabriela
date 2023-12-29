$(document).ready(function() {
    let datosMoraG = document.getElementById('Datos2023Mora');
    let cTestMoraG= JSON.parse(datosMoraG.textContent);

    const labelsM2023 = [];
    const dataM2023 = [];
    // console.log(labelsG2022);
    // console.log(data1G);
    for (let item of cTestMoraG) {
        labelsM2023.push(item.DDESFEC);
        dataM2023.push(parseFloat(item.NMONTO));
    }
    // console.log(labels);
    if (cTestMoraG.length > 0) {
        const grafico = new Chart( $("#miGraficoMoraMonto"), getChartConfigMora(dataM2023,labelsM2023));
        // const grafico = new Chart( ctx, getChartConfig(data,labels));
    }
});

$(document).ready(function() {
    let datosMoraI = document.getElementById('Datos2023Mora');
    let cTestMoraI = JSON.parse(datosMoraI.textContent);

    const labelsMoraI = [];
    const dataMoraI= [];
    //console.log(cTest1);
    for (let item of cTestMoraI) {
        labelsMoraI.push(item.DDESFEC);
        dataMoraI.push(parseFloat(item.NINCRE2));
    }
    // console.log(labels2022GI);
    // console.log(data2022GI);
    if (cTestMoraI.length > 0) {
        const grafico1 = new Chart( $("#miGraficoMoraIncr"), getChartConfigMoraIncr(dataMoraI,labelsMoraI));
        // const grafico = new Chart( ctx, getChartConfig(data,labels));
    }
});


