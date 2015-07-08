$.fn.nodoubletapzoom = function() {
    $(document).off('touchstart');
    $(document).on('touchstart', function preventZoom(e) {
        var t2 = e.timeStamp
            , t1 = $(this).data('lastTouch') || t2
            , dt = t2 - t1
            , fingers = e.originalEvent.touches.length;
        $(this).data('lastTouch', t2);
        if (!dt || dt > 500 || fingers > 1) return; // not double-tap

        e.preventDefault(); // double tap - prevent the zoom
        // also synthesize click events we just swallowed up
        $(document).trigger('click').trigger('click');
    });
};
$.fn.doubletapzoom = function() {
    $(document).off('touchstart');
};

(function($){

    $.session = {

        _id: null,

        _cookieCache: undefined,

        _init: function()
        {
            if (!window.name) {
                window.name = Math.random();
            }
            this._id = window.name;
            this._initCache();

            // See if we've changed protcols

            var matches = (new RegExp(this._generatePrefix() + "=([^;]+);")).exec(document.cookie);
            if (matches && document.location.protocol !== matches[1]) {
                this._clearSession();
                for (var key in this._cookieCache) {
                    try {
                        window.sessionStorage.setItem(key, this._cookieCache[key]);
                    } catch (e) {};
                }
            }

            document.cookie = this._generatePrefix() + "=" + document.location.protocol + ';path=/;expires=' + (new Date((new Date).getTime() + 120000)).toUTCString();

        },

        _generatePrefix: function()
        {
            return '__session:' + this._id + ':';
        },

        _initCache: function()
        {
            var cookies = document.cookie.split(';');
            this._cookieCache = {};
            for (var i = 0; i < cookies.length; i++) {
                var kv = cookies[i].split('=');
                if ((new RegExp(this._generatePrefix() + '.+')).test(kv[0]) && kv[1]) {
                    this._cookieCache[kv[0].split(':', 3)[2]] = kv[1];
                }
            }
        },

        _setFallback: function(key, value, onceOnly)
        {
            var cookie = this._generatePrefix() + key + "=" + value + "; path=/";
            if (onceOnly) {
                cookie += "; expires=" + (new Date(Date.now() + 120000)).toUTCString();
            }
            document.cookie = cookie;
            this._cookieCache[key] = value;
            return this;
        },

        _getFallback: function(key)
        {
            if (!this._cookieCache) {
                this._initCache();
            }
            return this._cookieCache[key];
        },

        _clearFallback: function()
        {
            for (var i in this._cookieCache) {
                document.cookie = this._generatePrefix() + i + '=; path=/; expires=Thu, 01 Jan 1970 00:00:01 GMT;';
            }
            this._cookieCache = {};
        },

        _deleteFallback: function(key)
        {
            document.cookie = this._generatePrefix() + key + '=; path=/; expires=Thu, 01 Jan 1970 00:00:01 GMT;';
            delete this._cookieCache[key];
        },

        get: function(key)
        {
            return window.sessionStorage.getItem(key) || this._getFallback(key);
        },

        set: function(key, value, onceOnly)
        {
            try {
                window.sessionStorage.setItem(key, value);
            } catch (e) {}
            this._setFallback(key, value, onceOnly || false);
            return this;
        },

        'delete': function(key){
            return this.remove(key);
        },

        remove: function(key)
        {
            try {
                window.sessionStorage.removeItem(key);
            } catch (e) {};
            this._deleteFallback(key);
            return this;
        },

        _clearSession: function()
        {
            try {
                window.sessionStorage.clear();
            } catch (e) {
                for (var i in window.sessionStorage) {
                    window.sessionStorage.removeItem(i);
                }
            }
        },

        clear: function()
        {
            this._clearSession();
            this._clearFallback();
            return this;
        }

    };

    $.session._init();

})(jQuery);
confidentGlobals.isMobile = /android|avantgo|blackberry|bolt|boost|cricket|docomo|fone|hiptop|mini|mobi|palm|iPhone|pie|tablet|webos|wos|phone|IEMobile/i.test(navigator.userAgent);
confidentGlobals.numberA = Math.ceil(Math.random() * 10);
confidentGlobals.numberB = Math.ceil(Math.random() * 10);
confidentGlobals.solveTimeout = setTimeout(function(){$.event.trigger({type: "confidentcaptcha", message: "solveTimeout"});}, 60000);
confidentGlobals.maxAttempts = 3;
function sendConfidentEvent(url, event){
    $.ajax({
        type: "GET",
        url: url + "&event_type=" + event + "&value=1"
    });
}
function setupCaptchaSession(){
    if( typeof $.session.get("confidentcaptcha_attempts") === "undefined"){
        $.session.set("confidentcaptcha_attempts", confidentGlobals.maxAttempts.toString());
    }
    confidentGlobals.attemptsLeft = parseInt($.session.get("confidentcaptcha_attempts"), 10);
}
function createArithmeticCaptcha(){
    setupCaptchaSession();
    $('#mathcaptcha_message').html("What is " + confidentGlobals.numberA + " + " + confidentGlobals.numberB + "? ");
    document.getElementById('arithmeticCaptchaNumberA').value = confidentGlobals.numberA;
    document.getElementById('arithmeticCaptchaNumberB').value = confidentGlobals.numberB;

    sendConfidentEvent(confidentGlobals.tracking_pixel, "0");
    $('#confidentcaptcha_badge').html(
        '<div id="confidentcaptcha_badge_tracker" style="background:url('+ confidentGlobals.tracking_pixel + "&event_type=1" +')"/>' +
            '<div id="confidentcaptcha_badge_message">Click To Verify</div>' +
            '<div id="confidentcaptcha_badge_grid">' +
            '<img src="confidentincludes/badge-grid.png"/>' +
            '</div>' +
            '<div id="confidentcaptcha_badge_logo">' +
            '<img src="confidentincludes/company-logo.png"/>' +
            '</div>'
    );

    if(!confidentGlobals.isMobile){
        sendConfidentEvent(confidentGlobals.tracking_pixel, "12");
        var desktopClose = $('<div id="confidentcaptcha_close"></div>');
        var desktopHeader = $('<div id="confidentcaptcha_modal_header_container" class="captcha-header_container" style="background:url('+ confidentGlobals.tracking_pixel + "&event_type=13" +')"><div id="confidentcaptcha_modal_header">QUICK VERIFICATION</div></div>');
        var desktopKeyline = $('<div class="keyline"></div>');
        var desktopMessage = $('<div id="confidentcaptcha_modal_message">Please verify that you\'re a real person</div>');
        $('#mathcaptcha_message').before(desktopClose, desktopHeader, desktopKeyline, desktopMessage);

        $('#confidentcaptcha_math_css').html(
            "#confidentcaptcha_numpad_container {width: 271px; margin: auto; padding-top: 10px;}\n" +
                "#confidentcaptcha_numpad {margin: 0; padding: 0; list-style: none;}\n" +
                "#confidentcaptcha_numpad li {float:left; margin: 0 5px 5px 0; width: 85px; height: 85px; line-height: 85px; text-align:center; background: #fff; border: 1px solid #aeaeae; border-radius: 5px; font: 200%/1.5 proxima-nova-i6, sans-serif; text-align: center; line-height: 85px;}\n" +
                ".confidentcaptcha_numpad_hover:hover { position: relative; top: 1px; left: 1px; border-color: #00BFFF !important; cursor: pointer;}" +
                "#mathcaptcha_message {text-align: center; font: 200%/1.5 proxima-nova-i6, sans-serif;}\n" +
                "#confidentcaptcha_user_input {text-align: center;}\n" +
                "#arithmeticCaptchaUserInput {width: 270px; font: 200%/1.5 proxima-nova-i6, sans-serif; border-radius: 5px; border: 1px solid #aeaeae; text-align: center;}\n" +
                ".lastitem {margin-right: 0 !important;}\n" +
                ".zeroinput {width: 269px !important;}\n" +
                "#confidentcaptcha_modal {display: none; z-index: 9001; vertical-align: middle; width: 400px; height: 600px; position: absolute; left: 0; right: 0; top: 20%; margin: auto; padding: 15px 40px 40px 40px; border-radius: 4px / 4px; -webkit-box-shadow: 5px 5px 5px #333; box-shadow: 5px 5px 5px #333; white-space: normal; zoom: 1; background: white;}\n" +
                "#confidentcaptcha_lightbox { display: none; position: fixed; left: 0; top: 0; width: 100%; background-color: black; -moz-opacity: 0.7; opacity:.70; filter: alpha(opacity=70); height: 100%; background-color: #1a1a1a; z-index: 9000; }\n" +
                ".keyline {border-bottom: 1px solid; height: 1px; border-bottom-color: #e5e5e5; margin-bottom: 10px;}\n" +
                "#confidentcaptcha_modal_header {margin-bottom: 10px; font-family: Impact, Charcoal, sans-serif; font-size: 24px;}\n" +
                "#confidentcaptcha_modal_message {margin-bottom: 10px; color: #666666; font-family: Helvetica, Arial, sans-serif; height: 50px; line-height: 49px; font-size: 13px;}\n" +
                ".confidentcaptcha_grey_bg {background-color: #dddddd; color: #666666; border-radius: 2px;}\n" +
                "#confidentcaptcha_close:before {content: 'x'; color: #666666; font-weight: 400; font-family: Helvetica, sans-serif; font-size: 25px;}\n" +
                "#confidentcaptcha_close {width: 25px; margin-left: 99%; text-align: center;}\n" +
                "#confidentcaptcha_close:hover {cursor: pointer; opacity: .5; filter: alpha(opacity = 50);}\n" +
                "#confidentcaptcha_badge { width: 140px; z-index: 8999; height: auto; vertical-align: middle; margin: 10px 10px 10px 10px; border-radius: 4px / 4px; -webkit-box-shadow: 3px 3px 3px #333; box-shadow: 3px 3px 3px #333; white-space: normal; zoom: 1; background: white; border: 1px solid rgba(0,0,0,.2);}\n" +
                "#confidentcaptcha_badge_message { cursor: pointer; font-weight: 600; font-family: proxima-nova-i6, sans-serif; vertical-align: middle; text-align: center; font-size: 15px; padding-top: 4px; margin-bottom: 4px; }\n" +
                "#confidentcaptcha_badge_grid {text-align: center;}\n" +
                "#confidentcaptcha_badge_logo { text-align: center;}\n" +
                "#confidentcaptcha_badge_logo img { height: 16px; }\n" +
                "#confidentcaptcha_alert {width: 11px; height: 11px;}");

        if(confidentGlobals.maxAttempts != confidentGlobals.attemptsLeft){
            $('#confidentcaptcha_modal_message').html('<div>&nbsp;&nbsp;&nbsp;&nbsp;<span><img id="confidentcaptcha_alert" src="confidentincludes/warning.png"/></span> You have '+ confidentGlobals.attemptsLeft +' attempt(s) left</div>');
            $('#confidentcaptcha_modal_message').addClass("confidentcaptcha_grey_bg");
            $('#confidentcaptcha_modal_header').html('TRY AGAIN');
            $('#confidentcaptcha_modal').show();
            $('#confidentcaptcha_lightbox').show();
        }
    }
    else{
        sendConfidentEvent(confidentGlobals.tracking_pixel, "5");
        calculateMobileCss();

        if(confidentGlobals.maxAttempts == confidentGlobals.attemptsLeft){
            var mobileHeader = $('<div id="confidentcaptcha_mobile_header" style="background:url('+ confidentGlobals.tracking_pixel + "&event_type=6" +')">QUICK VERIFICATION</div>');
            var mobileMessage = $('<div id="confidentcaptcha_mobile_message">Please verify that you\'re a real person</div>');
        }
        else{
            var mobileHeader = $('<div id="confidentcaptcha_mobile_header">TRY AGAIN</div>');
            var mobileMessage = $('<div id="confidentcaptcha_mobile_message" class="confidentcaptcha_grey_bg">You have '+ confidentGlobals.attemptsLeft +' attempt(s) left</div>');
            $('#confidentcaptcha_modal').show();
        }
        var mobileClose = $('<div id="confidentcaptcha_close"></div>');

        $('#mathcaptcha_message').before(mobileClose, mobileHeader, mobileMessage);
        $('#arithmeticCaptchaUserInput').attr('readonly', true);


        if ( (typeof window.onorientationchange != 'undefined') && (typeof orientation != 'undefined')) {
            //Orientation change for Chrome and Safari
            window.onorientationchange = function() {
                calculateMobileCss();
            }
        }
        //Orientation change for Firefox
        else if (typeof orientation === 'undefined' && (window.matchMedia("(orientation: landscape)").matches || window.matchMedia("(orientation: portrait)").matches)) {
            var mql = window.matchMedia("(orientation: portrait)");

            // Add a media query change listener
            mql.addListener(function(m) {
                calculateMobileCss();
            });
        }
    }
}
createArithmeticCaptcha();
$.event.trigger({type: "confidentcaptcha", message: "ready"});
function ajaxMathCaptcha(){
    var answerIsSingle = (confidentGlobals.numberA + confidentGlobals.numberB - ((confidentGlobals.numberA + confidentGlobals.numberB) % 10) == 0);
    var input = document.getElementById("arithmeticCaptchaUserInput").value;
    if(answerIsSingle && input.length == 1){
        validateArithmeticCaptcha();
    }
    if(!answerIsSingle && input.length == 2){
        validateArithmeticCaptcha();
    }
}
function validateArithmeticCaptcha(){
    sendConfidentEvent(confidentGlobals.tracking_pixel, "7");
    confidentGlobals.attemptsLeft = parseInt($.session.get("confidentcaptcha_attempts"), 10) - 1;
    $.session.set("confidentcaptcha_attempts", confidentGlobals.attemptsLeft.toString());
    confidentGlobals.attemptsLeft = parseInt($.session.get("confidentcaptcha_attempts"), 10);
    confidentGlobals.reachedMaxAttempts = confidentGlobals.attemptsLeft <= 0;
    clearTimeout(confidentGlobals.solveTimeout);
    var input = document.getElementById("arithmeticCaptchaUserInput").value;
    $.post(
        confidentGlobals.confidentcaptcha_callback,
        {
            endpoint: 'verify_captcha',
            arithmeticCaptchaUserInput: input,
            arithmeticCaptchaNumberA: confidentGlobals.numberA,
            arithmeticCaptchaNumberB: confidentGlobals.numberB
        },
        function (resp, textStatus, jqXHR) {
            sendConfidentEvent(confidentGlobals.tracking_pixel, "8");
            var obj = $.parseJSON(resp);
            var answer = obj.answer;
            if (answer == true) {
                $("#confidentcaptcha_badge_message").html('Verified');
                $("#confidentcaptcha_badge_message").css("color", "#2FB522");
                $("#confidentcaptcha_modal").hide();
                $("#confidentcaptcha_lightbox").hide();
                if(confidentGlobals.isMobile){
                    $("#confidentcaptcha_modal").hide();
                }
                $("#confidentcaptcha_badge").off("click");
                $("#confidentcaptcha_badge_message").css("cursor", "default");
                $.event.trigger({type: "confidentcaptcha", message: "solved"});
                $.session.clear();
            }
            else if(answer == false && confidentGlobals.reachedMaxAttempts){
                $('#confidentcaptcha_mobile_header').html('YOUR LAST ATTEMPT FAILED');
                $('#confidentcaptcha_modal_header').html('YOUR LAST ATTEMPT FAILED');
                $("#confidentcaptcha_badge_message").html('Failed');
                $("#confidentcaptcha_badge_message").css("color", "#C23B21");
                $("#confidentcaptcha_badge").off("click");
                $("#confidentcaptcha_badge_message").css("cursor", "default");
                $('#mathcaptcha_message').html('');
                $('#confidentcaptcha_user_input').html('');
                $('#confidentcaptcha_numpad_container').html('');
                $('#confidentcaptcha_mobile_message').html('Please close this window and try again');
                $('#confidentcaptcha_modal_message').html('<div>&nbsp;&nbsp;&nbsp;&nbsp;<span><img id="confidentcaptcha_alert" src="confidentincludes/warning.png"/></span> Please close this window and try again</div>');
                if(confidentGlobals.isMobile){
                    $('#confidentcaptcha_numpad_container').hide();
                    $('#mathcaptcha_message').hide();
                    $('#arithmeticCaptchaUserInput').hide();
                }
                else{
                    $("#confidentcaptcha_modal").css("height", "130px");
                }
                $.session.clear();
                $.event.trigger({type: "confidentcaptcha", message: "maxTries"});
            }
            else if(answer == false && !confidentGlobals.reachedMaxAttempts){
                $.ajax(
                    {
                        type: 'POST',
                        url: confidentGlobals.confidentcaptcha_callback,
                        data: {
                            endpoint: 'create_captcha'
                        },
                        success: function(xml){
                            var new_html = $(xml).unwrap();
                            $('#confidentcaptcha_wrapper').html(new_html);
                            $.event.trigger({type: "confidentcaptcha", message: "retry"});
                        },
                        error: function (xhr, status, error) {
                            $("#arithmeticCaptchaUserInput").prop('disabled', true);
                            $.event.trigger({type: "confidentcaptcha", message: "solveError"});
                        }
                    });
            }
        },
        'text'
    );
}
function calculateMobileCss(){
    var browser_width = window.innerWidth;
    var browser_height = window.innerHeight;
    if(browser_height > browser_width){
        var isPortrait = true
    }
    else{
        var isPortrait = false;
    }
    var mobileMessageLineHeight = Math.floor(.08 * window.innerHeight);
    if(isPortrait){
        var largeFontSize = Math.floor(.05 * window.innerHeight);
        var smallFontSize = Math.floor(.035 * window.innerHeight);
        var closeButtonSize = Math.floor(.06 * window.innerHeight);
        $('#confidentcaptcha_math_css').html(
            "#confidentcaptcha_modal {display: none; z-index:9999; position: fixed; top: 0px !important; left: 0px !important; right: 0px !important; bottom: 0px !important; background-color:#ffffff; width: "+ browser_width +"px; height: "+ browser_height +"px;}"+
                ".close {font-size: 20px; font-weight: bold; line-height: 18px; color: #000000; text-shadow: 0 1px 0 #ffffff; opacity: 0.2; filter: alpha(opacity=20); text-decoration: none; font-family: proxima-nova-i6, sans-serif;}\n" +
                "#confidentcaptcha_mobile_header {font-family: Impact, Charcoal, sans-serif; text-align: center; font-size: " + largeFontSize + "px;}\n" +
                "#confidentcaptcha_mobile_message {font-family: Helvetica, Arial, sans-serif; text-align: center; font-size: " + smallFontSize/1.1 + "px; margin-left: auto; margin-right: auto; width: 90%; padding-top: 10px; padding-bottom: 10px; margin-bottom: 20px;}\n" +
                "#confidentcaptcha_close:before {content: 'x'; color: #666666; font-weight: 400; font-family: Helvetica, sans-serif; font-size: " + closeButtonSize +"px;}\n" +
                "#confidentcaptcha_close {margin-left: 85%; text-align: center;}\n" +
                "#confidentcaptcha_numpad_container {margin-left: 5%; padding-top: 2%;}\n" +
                "#confidentcaptcha_numpad {margin: 0; padding: 0; list-style: none;}\n" +
                "#confidentcaptcha_numpad li {float:left; margin: 0 2% 2% 0; width: 30%; text-align:center; background: #fff; border: 1px solid #aeaeae; border-radius: 5px; font: "+ largeFontSize +"px/2.5 proxima-nova-i6, sans-serif;}\n" +
                ".confidentcaptcha_numpad_hover:active { position: relative; top: 1px; left: 1px; border-color: #00BFFF !important; cursor: pointer;}" +
                "#mathcaptcha_message {text-align: center; font: "+ largeFontSize +"px/1.5 proxima-nova-i6, sans-serif; margin-top: 3%;}\n" +
                "#confidentcaptcha_user_input {text-align: center;}\n" +
                "#arithmeticCaptchaUserInput {width: 70%; font: "+ largeFontSize +"px/1.5 proxima-nova-i6, sans-serif; border-radius: 5px; border: 1px solid #aeaeae; text-align: center;}\n" +
                ".lastitem {margin-right: 0 !important;}\n" +
                ".zeroinput {width: 95% !important;}\n" +
                ".confidentcaptcha_grey_bg {background-color: #dddddd; color: #666666; border-radius: 2px;}\n" +
                "#confidentcaptcha_badge { width: 140px; z-index: 8999; height: auto; vertical-align: middle; margin: 10px 10px 10px 10px; border-radius: 4px / 4px; -webkit-box-shadow: 3px 3px 3px #333; box-shadow: 3px 3px 3px #333; white-space: normal; zoom: 1; background: white; border: 1px solid rgba(0,0,0,.2);}\n" +
                "#confidentcaptcha_badge_message { cursor: pointer; font-weight: 600; font-family: proxima-nova-i6, sans-serif; vertical-align: middle; text-align: center; font-size: 15px; padding-top: 4px; margin-bottom: 4px; }\n" +
                "#confidentcaptcha_badge_grid {text-align: center;}\n" +
                "#confidentcaptcha_badge_logo { text-align: center;}\n" +
                "#confidentcaptcha_badge_logo img { height: 16px; }");
    }
    else{
        var largeFontSize = Math.floor(.1 * window.innerHeight);
        var smallFontSize = Math.floor(.07 * window.innerHeight);
        var closeButtonSize = Math.floor(.1 * window.innerHeight);
        $('#confidentcaptcha_math_css').html(
            "#confidentcaptcha_modal { display: none; z-index:9999; position: fixed; top: 0px !important; left: 0px !important; right: 0px !important; bottom: 0px !important; background-color:#ffffff; width: "+ browser_width +"px; height: "+ browser_height +"px;}"+
                ".close {font-size: 20px; font-weight: bold; line-height: 18px; color: #000000; text-shadow: 0 1px 0 #ffffff; opacity: 0.2; filter: alpha(opacity=20); text-decoration: none; font-family: proxima-nova-i6, sans-serif;}\n" +
                "#confidentcaptcha_mobile_header {margin-top: -50px; font-family: Impact, Charcoal, sans-serif; text-align: center; font-size: " + largeFontSize/1.3 + "px;}\n" +
                "#confidentcaptcha_mobile_message {font-family: Helvetica, Arial, sans-serif; text-align: center; font-size: " + smallFontSize/1.3 + "px; margin-left: auto; margin-right: auto; width: 90%; padding-top: 10px; padding-bottom: 10px;}\n" +
                "#confidentcaptcha_close:before {content: 'x'; color: #666666; font-weight: 400; font-family: Helvetica, sans-serif; font-size: " + closeButtonSize +"px;}\n" +
                "#confidentcaptcha_close {margin-left: 85%; text-align: center;}\n" +
                "#confidentcaptcha_numpad_container {margin-left: 5%; padding-top: 1%;}\n" +
                "#confidentcaptcha_numpad {margin: 0; padding: 0; list-style: none;}\n" +
                "#confidentcaptcha_numpad li {float:left; margin: 0 2% 2% 0; width: 30%; height: 25%; text-align:center; background: #fff; border: 1px solid #aeaeae; border-radius: 5px; font: "+ largeFontSize/2 +"px/2.1 proxima-nova-i6, sans-serif;}\n" +
                ".confidentcaptcha_numpad_hover:active { position: relative; top: 1px; left: 1px; border-color: #00BFFF !important; cursor: pointer;}" +
                "#mathcaptcha_message {text-align: center; font: "+ largeFontSize/1.5 +"px/1.5 proxima-nova-i6, sans-serif;}\n" +
                "#confidentcaptcha_user_input {text-align: center;}\n" +
                "#arithmeticCaptchaUserInput {width: 70%; font: "+ largeFontSize/2 +"px/1.5 proxima-nova-i6, sans-serif; border-radius: 5px; border: 1px solid #aeaeae; text-align: center;}\n" +
                ".lastitem {margin-right: 0 !important;}\n" +
                ".zeroinput {width: 95% !important;}\n" +
                ".confidentcaptcha_grey_bg {background-color: #dddddd; color: #666666; border-radius: 2px;}\n" +
                "#confidentcaptcha_badge { width: 140px; z-index: 8999; height: auto; vertical-align: middle; margin: 10px 10px 10px 10px; border-radius: 4px / 4px; -webkit-box-shadow: 3px 3px 3px #333; box-shadow: 3px 3px 3px #333; white-space: normal; zoom: 1; background: white; border: 1px solid rgba(0,0,0,.2);}\n" +
                "#confidentcaptcha_badge_message { cursor: pointer; font-weight: 600; font-family: proxima-nova-i6, sans-serif; vertical-align: middle; text-align: center; font-size: 15px; padding-top: 4px; margin-bottom: 4px; }\n" +
                "#confidentcaptcha_badge_grid {text-align: center;}\n" +
                "#confidentcaptcha_badge_logo { text-align: center;}\n" +
                "#confidentcaptcha_badge_logo img { height: 16px; }");
    }
}
var $write = $('#arithmeticCaptchaUserInput');
$('#confidentcaptcha_numpad li').off('click');
$('#confidentcaptcha_numpad li').on('click', function(e) {
    e.stopPropagation();
    var $this = $(this),
        character = $this.html();
    $write.val($write.val() + character);
    $write.focus();
    ajaxMathCaptcha();
});
$('#confidentcaptcha_mobile_close').off("click");
$('#confidentcaptcha_mobile_close').on('click', function(e){
    e.stopPropagation();
    $("#confidentcaptcha_wrapper").hide();
    $('body').off('touchmove');
    $('body').doubletapzoom();
    $.event.trigger({type: "confidentcaptcha", message: "mobileClose"});
    $.session.clear();
    setupCaptchaSession();
    $('#confidentcaptcha_mobile_message').removeClass('confidentcaptcha_grey_bg');
    $('#confidentcaptcha_mobile_message').html('Please verify that you\'re a real person');
    $('#confidentcaptcha_mobile_header').html('QUICK VERIFICATION');
});
$('#confidentcaptcha_close').off('click');
$('#confidentcaptcha_close').on( 'click', function(e) {
    e.stopPropagation();
    $('#confidentcaptcha_lightbox').hide();
    $('#confidentcaptcha_modal').hide();
    $.event.trigger({type: "confidentcaptcha", message: "desktopClose"});
    $.session.clear();
    setupCaptchaSession();
    $('#confidentcaptcha_modal_message').removeClass('confidentcaptcha_grey_bg');
    $('#confidentcaptcha_modal_message').html('Please verify that you\'re a real person');
    $('#confidentcaptcha_modal_header').html('QUICK VERIFICATION');
});
$('#confidentcaptcha_badge').off("click");
$('#confidentcaptcha_badge').on('click', function(e){
    e.stopPropagation();
    $('#confidentcaptcha_lightbox').show();
    $('#confidentcaptcha_modal').show();
    if (confidentGlobals.isMobile){
        $('body').off('touchmove');
        $('body').on('touchmove', function(event) {event.preventDefault(); });
        $('body').nodoubletapzoom();
    }
});