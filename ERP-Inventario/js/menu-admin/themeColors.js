const handleThemeUpdate = (cssVars) => {
    const root = document.querySelector(':root');
    const keys = Object.keys(cssVars);
    keys.forEach(key => {
        root.style.setProperty(key, cssVars[key]);
    });
}

function dynamicPrimaryColor(primaryColor) {
    'use strict'
    
    primaryColor.forEach((item) => {
        item.addEventListener('input', (e) => {
            const cssPropName = `--primary-${e.target.getAttribute('data-id')}`;
            const cssPropName1 = `--primary-${e.target.getAttribute('data-id1')}`;
            const cssPropName2 = `--primary-${e.target.getAttribute('data-id2')}`;
            const cssPropName7 = `--primary-${e.target.getAttribute('data-id7')}`;
            const cssPropName8 = `--darkprimary-${e.target.getAttribute('data-id8')}`;
            const cssPropName3 = `--dark-${e.target.getAttribute('data-id3')}`;
            const cssPropName4 = `--transparent-${e.target.getAttribute('data-id4')}`;
            const cssPropName5 = `--transparent-${e.target.getAttribute('data-id5')}`;
            const cssPropName6 = `--transparent-${e.target.getAttribute('data-id6')}`;
            const cssPropName9 = `--transparentprimary-${e.target.getAttribute('data-id9')}`;
            handleThemeUpdate({
                [cssPropName]: e.target.value,
                // 95 is used as the opacity 0.95  
                [cssPropName1]: e.target.value + 95,
                [cssPropName2]: e.target.value,
                [cssPropName3]: e.target.value,
                [cssPropName4]: e.target.value,
                [cssPropName5]: e.target.value,
                [cssPropName6]: e.target.value + 95,
                [cssPropName7]: e.target.value + 20,
                [cssPropName8]: e.target.value + 20,
                [cssPropName9]: e.target.value + 20,
            });
            
        });
    });
}

(function() {
    // checkOptions()
    'use strict'

    // Light theme color picker
    const dynamicPrimaryLight = document.querySelectorAll('input.color-primary-light');

    dynamicPrimaryColor(dynamicPrimaryLight);

    // dark theme color picker
    const DarkDynamicPrimaryLight = document.querySelectorAll('input.color-primary-dark');

    dynamicPrimaryColor(DarkDynamicPrimaryLight);

    // tranparent theme color picker
    const transparentDynamicPrimaryLight = document.querySelectorAll('input.color-primary-transparent');

    dynamicPrimaryColor(transparentDynamicPrimaryLight);

    // tranparent theme bgcolor picker
    const transparentDynamicPBgLight = document.querySelectorAll('input.color-bg-transparent');

    dynamicPrimaryColor(transparentDynamicPBgLight);

    localStorageBackup();

    $('#myonoffswitch1').on('click', function(){
        document.querySelector('body')?.classList.remove('dark-theme');
        document.querySelector('body')?.classList.remove('transparent-theme');
        document.querySelector('body')?.classList.remove('bg-img1');
        document.querySelector('body')?.classList.remove('bg-img2');
        document.querySelector('body')?.classList.remove('bg-img3');
        document.querySelector('body')?.classList.remove('bg-img4');
        
        localStorage.removeItem('dashleadBgImage');
        $('#myonoffswitch1').prop('checked', true);

        localStorage.setItem('dashleadlightMode', true);
        localStorage.removeItem('dashleaddarkMode');
        localStorage.removeItem('dashleadtransparentMode');
    })
    $('#myonoffswitch2').on('click', function(){
    document.querySelector('body')?.classList.remove('light-theme');
    document.querySelector('body')?.classList.remove('transparent-theme');
    document.querySelector('body')?.classList.remove('bg-img1');
    document.querySelector('body')?.classList.remove('bg-img2');
    document.querySelector('body')?.classList.remove('bg-img3');
    document.querySelector('body')?.classList.remove('bg-img4');
    
    localStorage.removeItem('dashleadBgImage');
    $('#myonoffswitch2').prop('checked', true);

    localStorage.setItem('dashleaddarkMode', true);
    localStorage.removeItem('dashleadlightMode');
    localStorage.removeItem('dashleadtransparentMode');
    })
    $('#myonoffswitchTransparent').on('click', function(){
    document.querySelector('body')?.classList.remove('dark-theme');
    document.querySelector('body')?.classList.remove('light-theme');
    document.querySelector('body')?.classList.remove('bg-img1');
    document.querySelector('body')?.classList.remove('bg-img2');
    document.querySelector('body')?.classList.remove('bg-img3');
    document.querySelector('body')?.classList.remove('bg-img4');
    
    localStorage.removeItem('dashleadBgImage');
    $('#myonoffswitchTransparent').prop('checked', true);
    localStorage.setItem('dashleadtransparentMode', true);
    localStorage.removeItem('dashleadlightMode');
    localStorage.removeItem('dashleaddarkMode');
    })

})();

function localStorageBackup() {
    'use strict'

    // if there is a value stored, update color picker and background color
    // Used to retrive the data from local storage
    if (localStorage.dashleadprimaryColor) {
        document.getElementById('colorID').value = localStorage.dashleadprimaryColor;
        document.querySelector('html').style.setProperty('--primary-bg-color', localStorage.dashleadprimaryColor);
        document.querySelector('html').style.setProperty('--primary-bg-hover', localStorage.dashleadprimaryHoverColor);
        document.querySelector('html').style.setProperty('--primary-bg-border', localStorage.dashleadprimaryBorderColor);
        document.querySelector('html').style.setProperty('--primary-transparentcolor', localStorage.dashleadprimaryTransparent);
		document.querySelector('body')?.classList.add('light-theme');
		document.querySelector('body')?.classList.remove('dark-theme');
		document.querySelector('body')?.classList.remove('transparent-theme');

        $('#myonoffswitch3').prop('checked', true);
        $('#myonoffswitch6').prop('checked', true);
        $('#myonoffswitch1').prop('checked', true);
    }

    if (localStorage.dashleaddarkPrimary) {
        document.getElementById('darkPrimaryColorID').value = localStorage.dashleaddarkPrimary;
        document.querySelector('html').style.setProperty('--primary-bg-color', localStorage.dashleaddarkPrimary);
        document.querySelector('html').style.setProperty('--primary-bg-hover', localStorage.dashleaddarkPrimary);
        document.querySelector('html').style.setProperty('--primary-bg-border', localStorage.dashleaddarkPrimary);
        document.querySelector('html').style.setProperty('--dark-primary', localStorage.dashleaddarkPrimary);
        document.querySelector('html').style.setProperty('--darkprimary-transparentcolor', localStorage.dashleaddarkprimaryTransparent);
		document.querySelector('body')?.classList.add('dark-theme');
		document.querySelector('body')?.classList.remove('light-theme');
		document.querySelector('body')?.classList.remove('transparent-theme');

        $('#myonoffswitch2').prop('checked', true);

    }


    if (localStorage.dashleadtransparentPrimary) {
        document.getElementById('transparentPrimaryColorID').value = localStorage.dashleadtransparentPrimary;
        document.querySelector('html').style.setProperty('--primary-bg-color', localStorage.dashleadtransparentPrimary);
        document.querySelector('html').style.setProperty('--primary-bg-hover', localStorage.dashleadtransparentPrimary);
        document.querySelector('html').style.setProperty('--primary-bg-border', localStorage.dashleadtransparentPrimary);
        document.querySelector('html').style.setProperty('--transparent-primary', localStorage.dashleadtransparentPrimary);
        document.querySelector('html').style.setProperty('--transparentprimary-transparentcolor', localStorage.dashleadtransparentprimaryTransparent);
		document.querySelector('body')?.classList.add('transparent-theme');
		document.querySelector('body')?.classList.remove('dark-theme');
		document.querySelector('body')?.classList.remove('light-theme');

        $('#myonoffswitchTransparent').prop('checked', true);
    }

    if (localStorage.dashleadtransparentBgImgPrimary) {
		document.getElementById('transparentBgImgPrimaryColorID').value = localStorage.dashleadtransparentBgImgPrimary;
		document.querySelector('html').style.setProperty('--primary-bg-color', localStorage.dashleadtransparentBgImgPrimary);
		document.querySelector('html').style.setProperty('--primary-bg-hover', localStorage.dashleadtransparentBgImgPrimary);
		document.querySelector('html').style.setProperty('--primary-bg-border', localStorage.dashleadtransparentBgImgPrimary);
		document.querySelector('html').style.setProperty('--transparent-primary', localStorage.dashleadtransparentBgImgPrimary);
		document.querySelector('html').style.setProperty('--transparentprimary-transparentcolor', localStorage.dashleadtransparentBgImgprimaryTransparent);
		document.querySelector('body')?.classList.add('transparent-theme');
		document.querySelector('body')?.classList.remove('dark-theme');
		document.querySelector('body')?.classList.remove('light-theme');
		
		$('#myonoffswitchTransparent').prop('checked', true);
	}
    
    if (localStorage.dashleadtransparentBgColor) {
        document.getElementById('transparentBgColorID').value = localStorage.dashleadtransparentBgColor;
        document.querySelector('html').style.setProperty('--transparent-body', localStorage.dashleadtransparentBgColor);
        document.querySelector('html').style.setProperty('--transparent-theme', localStorage.dashleadtransparentThemeColor);
        document.querySelector('html').style.setProperty('--transparentprimary-transparentcolor', localStorage.dashleadtransparentprimaryTransparent);
        document.querySelector('body').classList.add('transparent-theme');
        document.querySelector('body').classList.remove('dark-theme');
        document.querySelector('body').classList.remove('light-theme');


        $('#myonoffswitchTransparent').prop('checked', true);
    }
	if (localStorage.dashleadBgImage) {
		document.querySelector('body')?.classList.add('transparent-theme');
        let bgImg = localStorage.dashleadBgImage.split(' ')[0];
		document.querySelector('body')?.classList.add(bgImg);
		document.querySelector('body')?.classList.remove('dark-theme');
		document.querySelector('body')?.classList.remove('light-theme');
		
		$('#myonoffswitchTransparent').prop('checked', true);
	}
    if(localStorage.dashleadlightMode){
        document.querySelector('body')?.classList.add('light-theme');
		document.querySelector('body')?.classList.remove('dark-theme');
		document.querySelector('body')?.classList.remove('transparent-theme');
        $('#myonoffswitch1').prop('checked', true);
        $('#myonoffswitch6').prop('checked', true);
        $('#myonoffswitch23').prop('checked', true);
    }
    if(localStorage.dashleaddarkMode){
        document.querySelector('body')?.classList.remove('light-theme');
		document.querySelector('body')?.classList.add('dark-theme');
		document.querySelector('body')?.classList.remove('transparent-theme');
        $('#myonoffswitch8').prop('checked', true);
        $('#myonoffswitch2').prop('checked', true);
        $('#myonoffswitch5').prop('checked', true);
    }
    if(localStorage.dashleadtransparentMode){
        document.querySelector('body')?.classList.remove('light-theme');
		document.querySelector('body')?.classList.remove('dark-theme');
		document.querySelector('body')?.classList.add('transparent-theme');
        $('#myonoffswitchTransparent').prop('checked', true);
    }
    
    if(localStorage.dashleadrtl) {
        document.querySelector('body').classList.add('rtl')
    }

    if(localStorage.dashleadhorizontal) {
        document.querySelector('body').classList.add('horizontal')
    }
    
    if(localStorage.dashleadhorizontalHover) {
        document.querySelector('body').classList.add('horizontal-hover')
    }
}

// triggers on changing the color picker
function changePrimaryColor() {
    'use strict'

    $('#myonoffswitch3').prop('checked', true);
    $('#myonoffswitch6').prop('checked', true);
    // checkOptions();

    var userColor = document.getElementById('colorID').value;
    localStorage.setItem('dashleadprimaryColor', userColor);
    // to store value as opacity 0.95 we use 95
    localStorage.setItem('dashleadprimaryHoverColor', userColor + 95);
    localStorage.setItem('dashleadprimaryBorderColor', userColor);
    localStorage.setItem('dashleadprimaryTransparent', userColor + 20);

    // removing dark theme properties
    localStorage.removeItem('dashleaddarkPrimary')
    localStorage.removeItem('dashleadtransparentBgColor');
    localStorage.removeItem('dashleadtransparentThemeColor');
    localStorage.removeItem('dashleadtransparentPrimary');
    localStorage.removeItem('dashleadtransparentBgImgPrimary');
	localStorage.removeItem('dashleadtransparentBgImgprimaryTransparent');
    localStorage.removeItem('dashleaddarkprimaryTransparent');
    document.querySelector('body').classList.add('light-theme');
    document.querySelector('body').classList.remove('transparent-theme');
    document.querySelector('body').classList.remove('dark-theme');
	localStorage.removeItem('dashleadBgImage');

    $('#myonoffswitch1').prop('checked', true);
    names()

    
    localStorage.setItem('dashleadlightMode', true);
    localStorage.removeItem('dashleaddarkMode');
    localStorage.removeItem('dashleadtransparentMode');
}

function darkPrimaryColor() {
    'use strict'

    var userColor = document.getElementById('darkPrimaryColorID').value;
    localStorage.setItem('dashleaddarkPrimary', userColor);
    localStorage.setItem('dashleaddarkprimaryTransparent', userColor + 20);
    $('#myonoffswitch5').prop('checked', true);
    $('#myonoffswitch8').prop('checked', true);
    // checkOptions();

    // removing light theme data 
    localStorage.removeItem('dashleadprimaryColor')
    localStorage.removeItem('dashleadprimaryHoverColor')
    localStorage.removeItem('dashleadprimaryBorderColor')
    localStorage.removeItem('dashleadprimaryTransparent');
    localStorage.removeItem('dashleadtransparentBgImgPrimary');
	localStorage.removeItem('dashleadtransparentBgImgprimaryTransparent');

    localStorage.removeItem('dashleadtransparentBgColor');
    localStorage.removeItem('dashleadtransparentThemeColor');
    localStorage.removeItem('dashleadtransparentPrimary');
	localStorage.removeItem('dashleadBgImage');

    document.querySelector('body').classList.add('dark-theme');
    document.querySelector('body').classList.remove('light-theme');
    document.querySelector('body').classList.remove('transparent-theme');

    $('#myonoffswitch2').prop('checked', true);
    names()
    
    localStorage.setItem('dashleaddarkMode', true);
    localStorage.removeItem('dashleadlightMode');
    localStorage.removeItem('dashleadtransparentMode');
}

function transparentPrimaryColor() {
    'use strict'
    
    $('#myonoffswitch3').prop('checked', false);
    $('#myonoffswitch6').prop('checked', false);
    $('#myonoffswitch5').prop('checked', false);
    $('#myonoffswitch8').prop('checked', false);

    var userColor = document.getElementById('transparentPrimaryColorID').value;
    localStorage.setItem('dashleadtransparentPrimary', userColor);
    localStorage.setItem('dashleadtransparentprimaryTransparent', userColor + 20);

    // removing light theme data 
    localStorage.removeItem('dashleaddarkPrimary');
    localStorage.removeItem('dashleadprimaryColor')
    localStorage.removeItem('dashleadprimaryHoverColor')
    localStorage.removeItem('dashleadprimaryBorderColor')
    localStorage.removeItem('dashleadprimaryTransparent');
    localStorage.removeItem('dashleadtransparentBgImgPrimary');
	localStorage.removeItem('dashleadtransparentBgImgprimaryTransparent');
    document.querySelector('body').classList.add('transparent-theme');
    document.querySelector('body').classList.remove('light-theme');
    document.querySelector('body').classList.remove('dark-theme');
	document.querySelector('body')?.classList.remove('bg-img1');
	document.querySelector('body')?.classList.remove('bg-img2');
	document.querySelector('body')?.classList.remove('bg-img3');
	document.querySelector('body')?.classList.remove('bg-img4');

    $('#myonoffswitchTransparent').prop('checked', true);
    // checkOptions();
    names()
    
    localStorage.setItem('dashleadtransparentMode', true);
    localStorage.removeItem('dashleadlightMode');
    localStorage.removeItem('dashleaddarkMode');
}

function transparentBgImgPrimaryColor() {
    'use strict'

    $('#myonoffswitch3').prop('checked', false);
    $('#myonoffswitch6').prop('checked', false);
    $('#myonoffswitch5').prop('checked', false);
    $('#myonoffswitch8').prop('checked', false);
	var userColor = document.getElementById('transparentBgImgPrimaryColorID').value;
	localStorage.setItem('dashleadtransparentBgImgPrimary', userColor);
	localStorage.setItem('dashleadtransparentBgImgprimaryTransparent', userColor+20);
	if(
		document.querySelector('body')?.classList.contains('bg-img1') == false &&
		document.querySelector('body')?.classList.contains('bg-img2') == false &&
		document.querySelector('body')?.classList.contains('bg-img3') == false &&
		document.querySelector('body')?.classList.contains('bg-img4') == false
		){
		document.querySelector('body')?.classList.add('bg-img1');
        localStorage.setItem('dashleadBgImage', 'bg-img1')
	}
    // removing light theme data 
	localStorage.removeItem('dashleaddarkPrimary');
	localStorage.removeItem('dashleadprimaryColor')
	localStorage.removeItem('dashleadprimaryHoverColor')
	localStorage.removeItem('dashleadprimaryBorderColor')
	localStorage.removeItem('dashleadprimaryTransparent');
	localStorage.removeItem('dashleaddarkprimaryTransparent');
	localStorage.removeItem('dashleadtransparentPrimary')
	localStorage.removeItem('dashleadtransparentprimaryTransparent');
	document.querySelector('body').classList.add('transparent-theme');
	document.querySelector('body')?.classList.remove('light-theme');
	document.querySelector('body')?.classList.remove('dark-theme');

	$('#myonoffswitchTransparent').prop('checked', true);
    // checkOptions();
	names();
    
    localStorage.setItem('dashleadtransparentMode', true);
    localStorage.removeItem('dashleadlightMode');
    localStorage.removeItem('dashleaddarkMode');
}


function transparentBgColor() {
    'use strict'

    $('#myonoffswitch3').prop('checked', false);
    $('#myonoffswitch6').prop('checked', false);
    $('#myonoffswitch5').prop('checked', false);
    $('#myonoffswitch8').prop('checked', false);
    var userColor = document.getElementById('transparentBgColorID').value;
    localStorage.setItem('dashleadtransparentBgColor', userColor);
    localStorage.setItem('dashleadtransparentThemeColor', userColor + 95);
    localStorage.setItem('dashleadtransparentprimaryTransparent', userColor + 20);
    localStorage.removeItem('dashleadtransparentBgImgPrimary');
	localStorage.removeItem('dashleadtransparentBgImgprimaryTransparent');

    // removing light theme data 
    localStorage.removeItem('dashleaddarkPrimary');
    localStorage.removeItem('dashleadprimaryColor')
    localStorage.removeItem('dashleadprimaryHoverColor')
    localStorage.removeItem('dashleadprimaryBorderColor')
    localStorage.removeItem('dashleadprimaryTransparent');
	localStorage.removeItem('dashleadBgImage');
    document.querySelector('body').classList.add('transparent-theme');
    document.querySelector('body').classList.remove('light-theme');
    document.querySelector('body').classList.remove('dark-theme');
	document.querySelector('body')?.classList.remove('bg-img1');
	document.querySelector('body')?.classList.remove('bg-img2');
	document.querySelector('body')?.classList.remove('bg-img3');
	document.querySelector('body')?.classList.remove('bg-img4');

    $('#myonoffswitchTransparent').prop('checked', true);
    // checkOptions();
    
    localStorage.setItem('dashleadtransparentMode', true);
    localStorage.removeItem('dashleadlightMode');
    localStorage.removeItem('dashleaddarkMode');
}


function bgImage(e) {
    'use strict'

    $('#myonoffswitch3').prop('checked', false);
    $('#myonoffswitch6').prop('checked', false);
    $('#myonoffswitch5').prop('checked', false);
    $('#myonoffswitch8').prop('checked', false);
	let imgID = e.getAttribute('class');
	localStorage.setItem('dashleadBgImage', imgID);
    
    // removing light theme data 
	localStorage.removeItem('dashleaddarkPrimary');
	localStorage.removeItem('dashleadprimaryColor')
	localStorage.removeItem('dashleadtransparentBgColor');
	localStorage.removeItem('dashleadtransparentThemeColor');
	localStorage.removeItem('dashleadtransparentprimaryTransparent');
	localStorage.removeItem('dashleadlightMode');
	localStorage.removeItem('dashleaddarkMode');
	document.querySelector('body').classList.add('transparent-theme');
	document.querySelector('body')?.classList.remove('light-theme');
	document.querySelector('body')?.classList.remove('dark-theme');

	$('#myonoffswitchTransparent').prop('checked', true);
   // checkOptions();
}

// to check the value is hexa or not
const isValidHex = (hexValue) => /^#([A-Fa-f0-9]{3,4}){1,2}$/.test(hexValue)

const getChunksFromString = (st, chunkSize) => st.match(new RegExp(`.{${chunkSize}}`, "g"))
    // convert hex value to 256
const convertHexUnitTo256 = (hexStr) => parseInt(hexStr.repeat(2 / hexStr.length), 16)
    // get alpha value is equla to 1 if there was no value is asigned to alpha in function
const getAlphafloat = (a, alpha) => {
        if (typeof a !== "undefined") { return a / 255 }
        if ((typeof alpha != "number") || alpha < 0 || alpha > 1) {
            return 1
        }
        return alpha
    }
    // convertion of hex code to rgba code 
function hexToRgba(hexValue, alpha) {
    'use strict'

    if (!isValidHex(hexValue)) { return null }
    const chunkSize = Math.floor((hexValue.length - 1) / 3)
    const hexArr = getChunksFromString(hexValue.slice(1), chunkSize)
    const [r, g, b, a] = hexArr.map(convertHexUnitTo256)
    return `rgba(${r}, ${g}, ${b}, ${getAlphafloat(a, alpha)})`
}


let myVarVal, myVarVal1, myVarVal2, myVarVal3

function names() {
    'use strict'

    let primaryColorVal = getComputedStyle(document.documentElement).getPropertyValue('--primary-bg-color').trim();

    //get variable
    myVarVal = localStorage.getItem("dashleadprimaryColor") || localStorage.getItem("dashleaddarkPrimary") || localStorage.getItem("dashleadtransparentPrimary") || localStorage.getItem("dashleadtransparentBgImgPrimary")  || primaryColorVal;
    myVarVal1 = localStorage.getItem("dashleadprimaryColor") || localStorage.getItem("dashleaddarkPrimary") || localStorage.getItem("dashleadtransparentPrimary") || localStorage.getItem("dashleadtransparentBgImgPrimary")  || "#05c3fb";
    myVarVal2 = localStorage.getItem("dashleadprimaryColor") || localStorage.getItem("dashleaddarkPrimary") || localStorage.getItem("dashleadtransparentPrimary") || localStorage.getItem("dashleadtransparentBgImgPrimary")  || null;
    myVarVal3 = localStorage.getItem("dashleadprimaryColor") || localStorage.getItem("dashleaddarkPrimary") || localStorage.getItem("dashleadtransparentPrimary") || localStorage.getItem("dashleadtransparentBgImgPrimary") || null;

    if(document.querySelector('#sales') !== null){
        index();
    }
    
    let colorData = hexToRgba(myVarVal || "#8760fb", 0.1)
    document.querySelector('html').style.setProperty('--primary01', colorData);

    let colorData1 = hexToRgba(myVarVal || "#8760fb", 0.2)
    document.querySelector('html').style.setProperty('--primary02', colorData1);

    let colorData2 = hexToRgba(myVarVal || "#8760fb", 0.3)
    document.querySelector('html').style.setProperty('--primary03', colorData2);

    let colorData3 = hexToRgba(myVarVal || "#8760fb", 0.6)
    document.querySelector('html').style.setProperty('--primary06', colorData3);

    let colorData4 = hexToRgba(myVarVal || "#8760fb", 0.9)
    document.querySelector('html').style.setProperty('--primary09', colorData4);

    let colorData5 = hexToRgba(myVarVal || "#8760fb", 0.05)
    document.querySelector('html').style.setProperty('--primary005', colorData5);

}
names()

// CHECK OPTIONS
function checkOptions() {
    "use strict";
    // horizontal
	if (document.querySelector('body').classList.contains('horizontal')) {
		$('#myonoffswitch35').prop('checked', true);
	}

	// horizontal-hover
	if (document.querySelector('body').classList.contains('horizontal-hover')) {
		$('#myonoffswitch111').prop('checked', true);
	}

	//RTL 
	if (document.querySelector('body').classList.contains('rtl')) {
		$('#myonoffswitch24').prop('checked', true);
	}

    // light header 
    if (document.querySelector('body').classList.contains('header-light')) {
        $('#myonoffswitch6').prop('checked', true);
    }
    // color header 
    if (document.querySelector('body').classList.contains('color-header')) {
        $('#myonoffswitch7').prop('checked', true);
    }
    // gradient header 
    if (document.querySelector('body').classList.contains('gradient-header')) {
        $('#myonoffswitch20').prop('checked', true);
    }
    // dark header 
    if (document.querySelector('body').classList.contains('dark-header')) {
        $('#myonoffswitch8').prop('checked', true);
    }

    // light menu
    if (document.querySelector('body').classList.contains('light-menu')) {
        $('#myonoffswitch3').prop('checked', true);
    }
    // color menu
    if (document.querySelector('body').classList.contains('color-menu')) {
        $('#myonoffswitch4').prop('checked', true);
    }
    // gradient menu
    if (document.querySelector('body').classList.contains('gradient-menu')) {
        $('#myonoffswitch19').prop('checked', true);
    }
    // dark menu
    if (document.querySelector('body').classList.contains('dark-menu')) {
        $('#myonoffswitch5').prop('checked', true);
    }
}
// checkOptions();