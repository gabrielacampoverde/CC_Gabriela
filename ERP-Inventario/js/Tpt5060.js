$(document).ready(function() {
    let datosJur2023 = document.getElementById('Datos2023');
    let cTestJur2023 = JSON.parse(datosJur2023.textContent);
    // const tableHead = document.getElementById('tableHead');
    // tableHead.addEventListener('click',() => {
    //     tableBody.style.display = tableBody.style.display == 'none' ? 'contents' : 'none';
    // });
    // let y = document.getElementById('Data');
    // let cData = JSON.parse(y.textContent);
    const labelsJur2023 = [];
    const dataJur2023 = [];
    // console.log(labelsJur2023);
    for (let item of cTestJur2023) {
        labelsJur2023.push(item.DDESFEC);
        dataJur2023.push(parseFloat(item.NCANALU));
    }
    // console.log(labels);
    if (cTestJur2023.length > 0) {
        const grafico = new Chart( $("#miGrafico6"), getChartConfig6(dataJur2023,labelsJur2023));
        // const grafico = new Chart( ctx, getChartConfig(data,labels));
    }
});

$(document).ready(function() {
    let datosJurInc2023 = document.getElementById('Datos2023');
    let cTestJurInc2023 = JSON.parse(datosJurInc2023.textContent);
    const labelsJurInc2023 = [];
    const dataJurInc2023 = [];
    // console.log(labelsJur2023);
    for (let item of cTestJurInc2023) {
        labelsJurInc2023.push(item.DDESFEC);
        dataJurInc2023.push(parseFloat(item.NINCRE1));
    }
    // console.log(labels);
    if (cTestJurInc2023.length > 0) {
        const grafico = new Chart( $("#miGrafico7"), getChartConfig7(dataJurInc2023,labelsJurInc2023));
        // const grafico = new Chart( ctx, getChartConfig(data,labels));
    }
});

$(document).ready(function() {
    let datosJurMont2023 = document.getElementById('Datos2023');
    let cTestJurMont2023 = JSON.parse(datosJurMont2023.textContent);
    const labelsJurMont2023 = [];
    const dataJurMont2023 = [];
    // console.log(labelsJur2023);
    for (let item of cTestJurMont2023) {
        labelsJurMont2023.push(item.DDESFEC);
        dataJurMont2023.push(parseFloat(item.NMONTO));
    }
    // console.log(labels);
    if (cTestJurMont2023.length > 0) {
        const grafico = new Chart( $("#miGrafico8"), getChartConfig8(dataJurMont2023,labelsJurMont2023));
        // const grafico = new Chart( ctx, getChartConfig(data,labels));
    }
});

$(document).ready(function() {
    let datosJurInc22023 = document.getElementById('Datos2023');
    let cTestJurInc22023 = JSON.parse(datosJurInc22023.textContent);
    const labelsJurInc22023 = [];
    const dataJurInc22023 = [];
    // console.log(labelsJur2023);
    for (let item of cTestJurInc22023) {
        labelsJurInc22023.push(item.DDESFEC);
        dataJurInc22023.push(parseFloat(item.NINCRE2));
    }
    // console.log(labels);
    if (cTestJurInc22023.length > 0) {
        const grafico = new Chart( $("#miGrafico9"), getChartConfig9(dataJurInc22023,labelsJurInc22023));
        // const grafico = new Chart( ctx, getChartConfig(data,labels));
    }
});
