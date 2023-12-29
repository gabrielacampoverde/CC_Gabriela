$(document).ready(function() {
    let datosIIIG = document.getElementById('Datos2022IIG');
    let cTestIIIG= JSON.parse(datosIIIG.textContent);
    // const tableHead = document.getElementById('tableHead');
    // tableHead.addEventListener('click',() => {
    //     tableBody.style.display = tableBody.style.display == 'none' ? 'contents' : 'none';
    // });
    // let y = document.getElementById('Data');
    // let cData = JSON.parse(y.textContent);
    const labelsG2022 = [];
    const dataG = [];
    const data1G = [];
    // console.log(labelsG2022);
    // console.log(data1G);
    for (let item of cTestIIIG) {
        labelsG2022.push(item.DDESFEC);
        dataG.push(parseFloat(item.NEMITID));
        data1G.push(parseFloat(item.NPAGADO));
    }
    // console.log(labels);
    if (cTestIIIG.length > 0) {
        const grafico = new Chart( $("#miGraficoEmtPagIII"), getChartConfigIIIEP(dataG,data1G,labelsG2022));
        // const grafico = new Chart( ctx, getChartConfig(data,labels));
    }
});

$(document).ready(function() {
    let datosIIIG2022I = document.getElementById('Datos2022IIG');
    let cTest2022III = JSON.parse(datosIIIG2022I.textContent);

    const labels2022GI = [];
    const data2022GI= [];
    //console.log(cTest1);
    for (let item of cTest2022III) {
        labels2022GI.push(item.DDESFEC);
        data2022GI.push(parseFloat(item.NINCREM));
    }
    // console.log(labels2022GI);
    // console.log(data2022GI);
    if (cTest2022III.length > 0) {
        const grafico1 = new Chart( $("#miGraficoIncrIII"), getChartConfigIncr(data2022GI,labels2022GI));
        // const grafico = new Chart( ctx, getChartConfig(data,labels));
    }
});

$(document).ready(function() {
    let datosIIIG2022IM = document.getElementById('Datos2022IIG');
    let cTest2022IIIM = JSON.parse(datosIIIG2022IM.textContent);

    const labels2022IIIm = [];
    const data2022IIIm= [];
    //console.log(cTest1);
    for (let item of cTest2022IIIM) {
        labels2022IIIm.push(item.DDESFEC);
        data2022IIIm.push(parseFloat(item.NPORVEN));
    }
    // console.log(labels2022IIIm);
    // console.log(data2022IIIm);
    if (cTest2022IIIM.length > 0) {
        const grafico1 = new Chart( $("#miGraficoMoraIII"), getChartConfigIIIVnt(data2022IIIm,labels2022IIIm));
        // const grafico = new Chart( ctx, getChartConfig(data,labels));
    }
});

