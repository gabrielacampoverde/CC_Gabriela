$(document).ready(function() {
    let datosPredG = document.getElementById('Datos2023Predic');
    let cTestPredG= JSON.parse(datosPredG.textContent);

    const labelsP2023 = [];
    const dataP2023 = [];
    const dataP12023 = [];
    const dataP22023 = [];
    // console.log(dataP12023);
    // console.log(dataP2023);
    // console.log(dataP22023);
    // console.log(dataP22023);
    for (let item of cTestPredG) {
        labelsP2023.push(item.DDESFEC);
        if (item.NPAGADO!=0.00){
            dataP12023.push(parseFloat(item.NPAGADO));
        }
        if (item.NPREDRN!=0.00){
            dataP2023.push(parseFloat(item.NPREDRN));
        }
        if (item.NPREDMC!=0.00){
            dataP22023.push(parseFloat(item.NPREDMC));
        }
        // console.log('HOLAAAAAAAAAAAAAA');
        // console.log(dataP22023);
        // console.log('CAHUUUUUUUUUUUUUUUUUUUU');

        // alert(dataP12023);
        // dataP2023.push(parseFloat(item.NMONTO));
        // dataP12023.push(parseFloat(item.NPAGADO));
    }
    // console.log(labels);
    if (cTestPredG.length > 0) {
        const grafico = new Chart( $("#miGraficoPrediccion"), getChartConfigPredic(dataP2023,dataP12023,labelsP2023,dataP22023));
        // const grafico = new Chart( ctx, getChartConfig(data,labels));
    }
});



