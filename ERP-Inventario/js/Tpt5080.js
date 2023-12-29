$(document).ready(function() {
    let datosTit2023 = document.getElementById('DatosT2023');
    let cTestTit2023 = JSON.parse(datosTit2023.textContent);
    // const tableHead = document.getElementById('tableHead');
    // tableHead.addEventListener('click',() => {
    //     tableBody.style.display = tableBody.style.display == 'none' ? 'contents' : 'none';
    // });
    // let y = document.getElementById('Data');
    // let cData = JSON.parse(y.textContent);
    const labelsTit2023 = [];
    const dataTit2023 = [];
    // console.log(labelsTit2023);
    for (let item of cTestTit2023) {
        labelsTit2023.push(item.DDESFEC);
        dataTit2023.push(parseFloat(item.NCANALU));
    }
    // console.log(labels);
    if (cTestTit2023.length > 0) {
        const grafico = new Chart( $("#miGrafico14"), getChartConfig14(dataTit2023,labelsTit2023));
        // const grafico = new Chart( ctx, getChartConfig(data,labels));
    }
});

$(document).ready(function() {
    let datosTitInc2023 = document.getElementById('DatosT2023');
    let cTestTitInc2023 = JSON.parse(datosTitInc2023.textContent);
    const labelsTitInc2023 = [];
    const dataTitInc2023 = [];
    // console.log(labelsTit2023);
    for (let item of cTestTitInc2023) {
        labelsTitInc2023.push(item.DDESFEC);
        dataTitInc2023.push(parseFloat(item.NINCRE1));
    }
    // console.log(labels);
    if (cTestTitInc2023.length > 0) {
        const grafico = new Chart( $("#miGrafico15"), getChartConfig15(dataTitInc2023,labelsTitInc2023));
        // const grafico = new Chart( ctx, getChartConfig(data,labels));
    }
});

$(document).ready(function() {
    let datosTitMont2023 = document.getElementById('DatosT2023');
    let cTestTitMont2023 = JSON.parse(datosTitMont2023.textContent);
    const labelsTitMont2023 = [];
    const dataTitMont2023 = [];
    // console.log(labelsTit2023);
    for (let item of cTestTitMont2023) {
        labelsTitMont2023.push(item.DDESFEC);
        dataTitMont2023.push(parseFloat(item.NMONTO));
    }
    // console.log(labels);
    if (cTestTitMont2023.length > 0) {
        const grafico = new Chart( $("#miGrafico16"), getChartConfig16(dataTitMont2023,labelsTitMont2023));
        // const grafico = new Chart( ctx, getChartConfig(data,labels));
    }
});

$(document).ready(function() {
    let datosSegInc22023 = document.getElementById('DatosT2023');
    let cTestTitInc22023 = JSON.parse(datosSegInc22023.textContent);
    const labelsTitInc22023 = [];
    const dataTitInc22023 = [];
    // console.log(labelsTit2023);
    for (let item of cTestTitInc22023) {
        labelsTitInc22023.push(item.DDESFEC);
        dataTitInc22023.push(parseFloat(item.NINCRE2));
    }
    // console.log(labels);
    if (cTestTitInc22023.length > 0) {
        const grafico = new Chart( $("#miGrafico17"), getChartConfig17(dataTitInc22023,labelsTitInc22023));
        // const grafico = new Chart( ctx, getChartConfig(data,labels));
    }
});
