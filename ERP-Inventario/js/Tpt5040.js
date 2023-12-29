$(document).ready(function() {
    let datos = document.getElementById('Datos2022II');
    let cTest = JSON.parse(datos.textContent);
    // const tableHead = document.getElementById('tableHead');
    // tableHead.addEventListener('click',() => {
    //     tableBody.style.display = tableBody.style.display == 'none' ? 'contents' : 'none';
    // });
    // let y = document.getElementById('Data');
    // let cData = JSON.parse(y.textContent);
    const labels = [];
    const data = [];
    const data1 = [];
    //console.log(cTest);
    for (let item of cTest) {
        labels.push(item.DDESFEC);
        data.push(parseFloat(item.NEMITID));
        data1.push(parseFloat(item.NPAGADO));
    }
    // console.log(labels);
    if (cTest.length > 0) {
        const grafico = new Chart( $("#miGrafico"), getChartConfig3(data,data1,labels));
    }
});

$(document).ready(function() {
    let datos2022I = document.getElementById('Datos2022II');
    let cTest2022I = JSON.parse(datos2022I.textContent);

    const labels2022I = [];
    const data2022I= [];
    //console.log(cTest1);
    for (let item of cTest2022I) {
        labels2022I.push(item.DDESFEC);
        data2022I.push(parseFloat(item.NINCREM));
    }
    // console.log(labels2022I);
    // console.log(data2022I);
    if (cTest2022I.length > 0) {
        const grafico1 = new Chart( $("#miGrafico2"), getChartConfig4(data2022I,labels2022I));
    }
});

$(document).ready(function() {
    let datos2022IIM = document.getElementById('Datos2022II');
    let cTest2022IIM = JSON.parse(datos2022IIM.textContent);

    const labels2022IIm = [];
    const data2022IIm= [];
    //console.log(cTest1);
    for (let item of cTest2022IIM) {
        labels2022IIm.push(item.DDESFEC);
        data2022IIm.push(parseFloat(item.NPORVEN));
    }
    // console.log(labels2022IIm);
    // console.log(data2022IIm);
    if (cTest2022IIM.length > 0) {
        const grafico1 = new Chart( $("#miGraficoMoraIII2022"), getChartConfigMoraII(data2022IIm,labels2022IIm));
        // const grafico = new Chart( ctx, getChartConfig(data,labels));
    }
});