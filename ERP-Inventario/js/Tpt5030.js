$(document).ready(function() {
    let datos = document.getElementById('Datos2022I');
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
        // const grafico = new Chart( ctx, getChartConfig(data,labels));
    }
});

$(document).ready(function() {
    let datos2022I = document.getElementById('Datos2022I');
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
        // const grafico = new Chart( ctx, getChartConfig(data,labels));
    }
});

$(document).ready(function() {
    let datos2022IM = document.getElementById('Datos2022I');
    let cTest2022IM = JSON.parse(datos2022IM.textContent);

    const labels2022Im = [];
    const data2022Im= [];
    //console.log(cTest1);
    for (let item of cTest2022IM) {
        labels2022Im.push(item.DDESFEC);
        data2022Im.push(parseFloat(item.NPORVEN));
    }
    console.log(labels2022Im);
    // console.log(data2022Im);
    if (cTest2022IM.length > 0) {
        const grafico1 = new Chart( $("#miGraficoMora"), getChartConfig5(data2022Im,labels2022Im));
        // const grafico = new Chart( ctx, getChartConfig(data,labels));
    }
});
