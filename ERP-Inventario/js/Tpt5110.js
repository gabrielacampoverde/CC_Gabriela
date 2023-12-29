$(document).ready(function() {
    let datosSeg2023 = document.getElementById('DatosM2023');
    let cTestSeg2023 = JSON.parse(datosSeg2023.textContent);
    // const tableHead = document.getElementById('tableHead');
    // tableHead.addEventListener('click',() => {
    //     tableBody.style.display = tableBody.style.display == 'none' ? 'contents' : 'none';
    // });
    // let y = document.getElementById('Data');
    // let cData = JSON.parse(y.textContent);
    const labelsSeg2023 = [];
    const dataSeg2023 = [];
    // console.log(labelsSeg2023);
    for (let item of cTestSeg2023) {
        labelsSeg2023.push(item.DDESFEC);
        dataSeg2023.push(parseFloat(item.NCANALU));
    }
    // console.log(labels);
    if (cTestSeg2023.length > 0) {
        const grafico = new Chart( $("#miGrafico22"), getChartConfig22(dataSeg2023,labelsSeg2023));
        // const grafico = new Chart( ctx, getChartConfig(data,labels));
    }
});

$(document).ready(function() {
    let datosSegInc2023 = document.getElementById('DatosM2023');
    let cTestSegInc2023 = JSON.parse(datosSegInc2023.textContent);
    const labelsSegInc2023 = [];
    const dataSegInc2023 = [];
    // console.log(labelsSeg2023);
    for (let item of cTestSegInc2023) {
        labelsSegInc2023.push(item.DDESFEC);
        dataSegInc2023.push(parseFloat(item.NINCRE1));
    }
    // console.log(labels);
    if (cTestSegInc2023.length > 0) {
        const grafico = new Chart( $("#miGrafico23"), getChartConfig23(dataSegInc2023,labelsSegInc2023));
        // const grafico = new Chart( ctx, getChartConfig(data,labels));
    }
});

$(document).ready(function() {
    let datosSegMont2023 = document.getElementById('DatosM2023');
    let cTestSegMont2023 = JSON.parse(datosSegMont2023.textContent);
    const labelsSegMont2023 = [];
    const dataSegMont2023 = [];
    // console.log(labelsSeg2023);
    for (let item of cTestSegMont2023) {
        labelsSegMont2023.push(item.DDESFEC);
        dataSegMont2023.push(parseFloat(item.NMONTO));
    }
    // console.log(labels);
    if (cTestSegMont2023.length > 0) {
        const grafico = new Chart( $("#miGrafico24"), getChartConfig24(dataSegMont2023,labelsSegMont2023));
        // const grafico = new Chart( ctx, getChartConfig(data,labels));
    }
});

$(document).ready(function() {
    let datosSegInc22023 = document.getElementById('DatosM2023');
    let cTestSegInc22023 = JSON.parse(datosSegInc22023.textContent);
    const labelsSegInc22023 = [];
    const dataSegInc22023 = [];
    // console.log(labelsSeg2023);
    for (let item of cTestSegInc22023) {
        labelsSegInc22023.push(item.DDESFEC);
        dataSegInc22023.push(parseFloat(item.NINCRE2));
    }
    // console.log(labels);
    if (cTestSegInc22023.length > 0) {
        const grafico = new Chart( $("#miGrafico25"), getChartConfig25(dataSegInc22023,labelsSegInc22023));
        // const grafico = new Chart( ctx, getChartConfig(data,labels));
    }
});
