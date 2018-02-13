var connok = "";
window.onload = function(){
    var conn = "";
    var hidden, state, visibilityChange;
    document.getElementById('talk_con').style.display = 'none';
    var Words = document.getElementById("words");
    var TalkWords = document.getElementById("talkwords");
    var TalkSub = document.getElementById("talksub"); 
    var Quit = document.getElementById("quit");
    var Start = document.getElementById("start");
    checkuserpage();
    $("#talkwords").keypress(function(e){
        code = (e.keyCode ? e.keyCode : e.which);
        if (code == 13) {
            $("#talksub").click();
        }
    });
    TalkSub.onclick = function(e){
        var str = "";
        if(TalkWords.value == "" ||connok != "ok"){
            return;
        }
            str = '<div class="btalk"><span>' + TalkWords.value +'</span></div>' ; 
            conn.send(TalkWords.value); 
        Words.innerHTML = Words.innerHTML + str;
        Words.scrollTop = Words.scrollHeight;
        document.getElementById('talkwords').value = "";
        $('#talksub').on('touchstart', function(e){
            e.preventDefault();
            e.stopPropagation();
            $('#talkwords').trigger('focus');
        })
    }
    Quit.onclick = function (){
        conn.close();;
        startchat('close');
        document.getElementById("start").disabled=true;
        setTimeout(function(){ 
            $("#waitboard").fadeIn(3000); 
            setTimeout(function(){
                document.getElementById("start").disabled=false;
            }, 2000);
        }, 1000);
        $("#talk_con").slideUp(600);
        
    }
    Start.onclick = function (){
        var waitingstr = "";
        var readmsg = 0;
        var Words = document.getElementById("words");
        Words.innerHTML = "";

        document.getElementById("start").disabled=true;

        $("#waitboard").slideUp(600);

        setTimeout(function(){
                $("#talk_con").fadeIn(2000);
            }, 1000);

        waitingstr = '<div class="systalk"><span>'+'連線中請稍候'+'</span></div>';

        Words.innerHTML = Words.innerHTML + waitingstr;

        setTimeout(function(){ 
            conn = new WebSocket('ws://ec2-54-92-215-154.compute-1.amazonaws.com:8080');
            // conn = new WebSocket('ws://127.0.0.1:8080');

            conn.onopen = function(e) {
            console.log("Connection established!");
            };

            conn.onmessage = function(e) {
                if(document[state] == 'hidden') {
                    readmsg++;
                    document.title = '('+readmsg+')雀雀';
                    playSoundsForHtml5(1);
                }
                if(e.data == "連線完成") {
                    var startstr = "";
                    var Words = document.getElementById("words");
                    startstr = '<div class="systalk"><span>'+'連線完成'+'</span></div>';
                    startchat();
                    Words.innerHTML = Words.innerHTML + startstr;
                }
                else if(e.data == "close") {
                    var closestr = "";
                    var Words = document.getElementById("words");
                    closestr = '<div class="systalk"><span>'+'對象已經離開'+'</span></div>';
                    Words.innerHTML = Words.innerHTML + closestr;
                    Words.scrollTop = Words.scrollHeight;
                    startchat('close');
                }
                else {
                    var restr = "";
                    var Words = document.getElementById("words");
                    restr = '<div class="atalk"><span>'+e.data+'</span></div>';
                    Words.innerHTML = Words.innerHTML + restr;
                    Words.scrollTop = Words.scrollHeight;
                }  
                document.addEventListener(visibilityChange, function() {
                    if(document[state] != 'hidden')
                    {
                        readmsg = 0;
                        document.title = '雀雀';
                    }
                    // console.log(readmsg);
                }, false);
                

            };
        }, 2000); 
    }
    function startchat(status) {
        if(status == 'close') {
            connok="";
            return;
        }
        connok = "ok";
    }
    function checkuserpage() {
        
        if (typeof document.hidden !== "undefined") {
           hidden = "hidden";
           visibilityChange = "visibilitychange";
           state = "visibilityState";
        } else if (typeof document.mozHidden !== "undefined") {
           hidden = "mozHidden";
           visibilityChange = "mozvisibilitychange";
           state = "mozVisibilityState";
        } else if (typeof document.msHidden !== "undefined") {
           hidden = "msHidden";
           visibilityChange = "msvisibilitychange";
           state = "msVisibilityState";
        } else if (typeof document.webkitHidden !== "undefined") {
           hidden = "webkitHidden";
           visibilityChange = "webkitvisibilitychange";
           state = "webkitVisibilityState";
        }
    }
    function playSoundsForHtml5(id){ 
        if(id< 0){ 
            return ; 
        }             
        var staticUrl = '/public/' +id+ '.mp3';    
        var soundsObj = document.createElement('AUDIO');//创建声音对象          
        soundsObj.setAttribute('src',staticUrl );//设置播放路径 
        soundsObj.setAttribute('autoplay', 'true');//设置自动播放 
        document.body.appendChild(soundsObj); 
        soundsObj.addEventListener('error', function(){ 
            document.body.removeChild(soundsObj); 
        }); 
        soundsObj.addEventListener('ended', function(){ 
            document.body.removeChild(soundsObj); 
        }); 
    } 
}
