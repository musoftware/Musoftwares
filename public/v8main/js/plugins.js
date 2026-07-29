var isMobile = false; //initiate as false
// device detection
if (/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|ipad|iris|kindle|Android|Silk|lge |maemo|midp|mmp|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows (ce|phone)|xda|xiino/i.test(navigator.userAgent)
    || /1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i.test(navigator.userAgent.substr(0, 4))) {
    isMobile = true;
}

if (screen.width < screen.height) {
    isMobile = true;
}


function toggler(selector) {
    $(selector).slideToggle();
}

function getSafeSession(k) { try { return sessionStorage.getItem(k); } catch(e) { return null; } }
function setSafeSession(k, v) { try { sessionStorage.setItem(k, v); } catch(e) {} }
function getSafeLocal(k) { try { return localStorage.getItem(k); } catch(e) { return null; } }
function setSafeLocal(k, v) { try { localStorage.setItem(k, v); } catch(e) {} }
function removeSafeLocal(k) { try { localStorage.removeItem(k); } catch(e) {} }

try { setSafeLocal('theme', 'dark'); } catch(e) {}

/* ==========================================================================
   30-DAY WELCOME INTRO SKIP & EXPIRATION ENGINE
   ========================================================================== */
function checkWelcomeSuppressed() {
    try {
        var skipUntil = parseInt(getSafeLocal('v8_skip_welcome_until') || '0', 10);
        return Date.now() < skipUntil;
    } catch(e) { return false; }
}

function bypassPreloaderNow() {
    $('.preloader-wrapper').stop(true, true).hide();
    $(".animation-start").stop(true, true).show().addClass('basic');
    $('.content .wrapper').show();
    $("#incenter_username").show();
}

// Early check on DOM ready
if (checkWelcomeSuppressed()) {
    $(document).ready(function () {
        bypassPreloaderNow();
    });
}

var play;

$(window).on('load', function () {
    if (checkWelcomeSuppressed()) {
        bypassPreloaderNow();
        return;
    }

    $('.preloader-wrapper .preloader .loading-Recovered').fadeOut(300, function () {
        $(".audio-test").fadeIn();
        if ((getSafeSession('audio') === 'true') || (getSafeSession('audio') === 'false')) {
            $(".audio-test button").parent().parent().hide();
            if ((getSafeSession('audio') === 'true')) {
                type('yes');
            } else {
                type();
            }
        }

        $(".audio-test button").click(function () {
            $(this).parent().parent().hide();

            // Save 30-day skip preference if checkbox checked
            if ($('#dontShowWelcome30Days').is(':checked')) {
                var thirtyDaysMs = 30 * 24 * 60 * 60 * 1000;
                setSafeLocal('v8_skip_welcome_until', Date.now() + thirtyDaysMs);
            }

            if ($(this).hasClass('yes')) {
                type('yes');
                setSafeSession('audio', 'true');
            } else {
                type();
                setSafeSession('audio', 'false');
            }
        });
    });
});

// Skip Intro Button Handler
$(document).on('click', '.skip-intro-now-btn', function (e) {
    e.preventDefault();
    var thirtyDaysMs = 30 * 24 * 60 * 60 * 1000;
    setSafeLocal('v8_skip_welcome_until', Date.now() + thirtyDaysMs);
    bypassPreloaderNow();
});

// Reset Welcome Intro Handler
$(document).on('click', '#resetWelcomeIntroBtn', function (e) {
    e.preventDefault();
    removeSafeLocal('v8_skip_welcome_until');
    alert('Welcome intro re-enabled! It will play on your next visit.');
});


function type(playAudio) {
    console.log(playAudio)

    if (playAudio == 'yes') {
        var audio = new Audio(resource_link + 'sound/welcome.mp3');
        audio.play();
    } else {
        $(".pause-btn").removeClass('active');
    }

    var typed = new Typed('.intro', {
        strings: ['Hello, ' + user_name + '...'],
        typeSpeed: 80
    });
    var typed = new Typed('.out', {
        strings: ['Welcome back!'],
        startDelay: 2500,
        typeSpeed: 80
    });
    $(".welcome").delay(1500).fadeIn(500);
    $(".preloader-wrapper").delay(5000).fadeOut(500);
    $(".animation-start").delay(5100).fadeIn(1200, function () {
            $(this).addClass('basic')

            var bgAudio = new Audio(resource_link + 'sound/sound-all-time.mp3');

            if (playAudio == 'yes') {
                $(".pause-btn").fadeIn();

                bgAudio.play();
                bgAudio.loop = true;
            }

            $(".pause-btn").click(function () {
                $(this).toggleClass('active');

                function togglePlay(bool) {
                    return bool ? bgAudio.play() : bgAudio.pause();
                }

                if ($(this).hasClass('active')) {
                    togglePlay(true)
                    setSafeSession('audio', 'true');
                } else {
                    togglePlay(false)
                    setSafeSession('audio', 'false');
                }
            });

            if (!isMobile) {
                $('.content .wrapper').fadeIn(500);
                $("#incenter_username").delay(500).fadeIn(500);
            } else {
                $("#incenter_username").hide();
            }


        }
    );
    $(".item").hover(function () {
        window.dispatchEvent(new Event('3d-hologram-pulse'));
        var hoverAudio = new Audio(resource_link + 'sound/hover-icons.mp3');

        if (playAudio == 'yes') {
            hoverAudio.play();
        }
    })
    // toggling function 
    $('.item-title').click(function () {
        $(this).toggleClass('active')

        var titleAudio = new Audio(resource_link + 'sound/basic-icons-hover.mp3');

        if (playAudio == 'yes') {
            titleAudio.play();
        }
    });

}


