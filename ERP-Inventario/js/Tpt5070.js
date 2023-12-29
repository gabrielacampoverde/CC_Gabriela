$(document).ready(function() {
    let datosBach2023 = document.getElementById('DatosB2023');
    let cTestBach2023 = JSON.parse(datosBach2023.textContent);
    // const tableHead = document.getElementById('tableHead');
    // tableHead.addEventListener('click',() => {
    //     tableBody.style.display = tableBody.style.display == 'none' ? 'contents' : 'none';
    // });
    // let y = document.getElementById('Data');
    // let cData = JSON.parse(y.textContent);
    const labelsBach2023 = [];
    const dataBach2023 = [];
    // console.log(labelsBach2023);
    for (let item of cTestBach2023) {
        labelsBach2023.push(item.DDESFEC);
        dataBach2023.push(parseFloat(item.NCANALU));
    }
    // console.log(labels);
    if (cTestBach2023.length > 0) {
        const grafico = new Chart( $("#miGrafico10"), getChartConfig10(dataBach2023,labelsBach2023));
        // const grafico = new Chart( ctx, getChartConfig(data,labels));
    }
});

$(document).ready(function() {
    let datosBachInc2023 = document.getElementById('DatosB2023');
    let cTestBachInc2023 = JSON.parse(datosBachInc2023.textContent);
    const labelsTitInc2023 = [];
    const dataBachInc2023 = [];
    // console.log(labelsBach2023);
    for (let item of cTestBachInc2023) {
        labelsTitInc2023.push(item.DDESFEC);
        dataBachInc2023.push(parseFloat(item.NINCRE1));
    }
    // console.log(labels);
    if (cTestBachInc2023.length > 0) {
        const grafico = new Chart( $("#miGrafico11"), getChartConfig11(dataBachInc2023,labelsTitInc2023));
        // const grafico = new Chart( ctx, getChartConfig(data,labels));
    }
});

$(document).ready(function() {
    let datosBachMont2023 = document.getElementById('DatosB2023');
    let cTestBachMont2023 = JSON.parse(datosBachMont2023.textContent);
    const labelsBachMont2023 = [];
    const dataTitMont2023 = [];
    // console.log(labelsBach2023);
    for (let item of cTestBachMont2023) {
        labelsBachMont2023.push(item.DDESFEC);
        dataTitMont2023.push(parseFloat(item.NMONTO));
    }
    // console.log(labels);
    if (cTestBachMont2023.length > 0) {
        const grafico = new Chart( $("#miGrafico12"), getChartConfig12(dataTitMont2023,labelsBachMont2023));
        // const grafico = new Chart( ctx, getChartConfig(data,labels));
    }
});

$(document).ready(function() {
    let datosBachInc22023 = document.getElementById('DatosB2023');
    let cTestBachInc22023 = JSON.parse(datosBachInc22023.textContent);
    const labelsBachInc22023 = [];
    const dataBachInc22023 = [];
    // console.log(labelsBach2023);
    for (let item of cTestBachInc22023) {
        labelsBachInc22023.push(item.DDESFEC);
        dataBachInc22023.push(parseFloat(item.NINCRE2));
    }
    // console.log(labels);
    if (cTestBachInc22023.length > 0) {
        const grafico = new Chart( $("#miGrafico13"), getChartConfig13(dataBachInc22023,labelsBachInc22023));
        // const grafico = new Chart( ctx, getChartConfig(data,labels));
    }
});
