var Index=function(){ var runRevolution=function(){var api;api=jQuery('.fullwidthabnner').revolution({delay:9000,startheight:450,startwidth:1120,hideThumbs:10,thumbWidth:100,thumbHeight:50,thumbAmount:5,videoJsPath:"assets/plugins/revolution_slider/rs-plugin/videojs",navigationType:"bullet", navigationArrows:"solo", navigationStyle:"round", navigationHAlign:"center", navigationVAlign:"bottom", navigationHOffset:0,navigationVOffset:20,soloArrowLeftHalign:"left",soloArrowLeftValign:"center",soloArrowLeftHOffset:20,soloArrowLeftVOffset:0,soloArrowRightHalign:"right",soloArrowRightValign:"center",soloArrowRightHOffset:20,soloArrowRightVOffset:0,touchenabled:"on", onHoverStop:"on", stopAtSlide:-1,stopAfterLoops:-1,shadow:0,fullWidth:"on", forceFullWidth:"on"});}; var runColorbox=function(){$(".group1").colorbox({rel:'group1',width:"85%"});};return{init:function(){runRevolution();runColorbox();}};}();