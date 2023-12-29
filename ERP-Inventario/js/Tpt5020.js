$(document).ready(function() {
    let datos = document.getElementById('Datos');
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
    console.log(labels);
    if (cTest.length > 0) {
        const grafico = new Chart( $("#miGrafico"), getChartConfig(data,data1,labels));
    }
});

$(document).ready(function() {
    let datos1 = document.getElementById('Datos');
    let cTest1 = JSON.parse(datos1.textContent);

    const labels1 = [];
    const data2= [];
    //console.log(cTest1);
    for (let item of cTest1) {
        labels1.push(item.DDESFEC);
        data2.push(parseFloat(item.NINCREM));
    }
    //console.log(labels1);
    if (cTest1.length > 0) {
        const grafico1 = new Chart( $("#miGrafico1"), getChartConfig2(data2,labels1));
    }
});
