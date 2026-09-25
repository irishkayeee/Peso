document.addEventListener('DOMContentLoaded', function () {
  var piggy = document.getElementById('piggyBank');
  var speech = document.getElementById('piggySpeech');

  if (!piggy || !speech) return;

  var messages = [
    "Oink! Log your expenses today!",
    "Every peso counts!",
    "Let's hit that savings goal!",
    "Small steps, bigger dreams!",
    "Track it, don't lose it!",
    "Your budget says hi!",
    "Save today, smile tomorrow!"
  ];

  var hideTimeout;
  var audioCtx;

  function playCoinSound() {
    var AudioContextClass = window.AudioContext || window.webkitAudioContext;
    if (!AudioContextClass) return;
    if (!audioCtx) audioCtx = new AudioContextClass();
    if (audioCtx.state === 'suspended') audioCtx.resume();

    var now = audioCtx.currentTime;
    var notes = [
      { freq: 988, start: 0, duration: 0.11 },
      { freq: 1319, start: 0.07, duration: 0.25 }
    ];

    notes.forEach(function (note) {
      var osc = audioCtx.createOscillator();
      var gain = audioCtx.createGain();
      osc.type = 'square';
      osc.frequency.setValueAtTime(note.freq, now + note.start);
      gain.gain.setValueAtTime(0.12, now + note.start);
      gain.gain.exponentialRampToValueAtTime(0.001, now + note.start + note.duration);
      osc.connect(gain).connect(audioCtx.destination);
      osc.start(now + note.start);
      osc.stop(now + note.start + note.duration);
    });
  }

  function showPiggySpeech(withSound) {
    if (withSound) playCoinSound();

    var message = messages[Math.floor(Math.random() * messages.length)];
    speech.textContent = message;
    speech.classList.add('show');

    clearTimeout(hideTimeout);
    hideTimeout = setTimeout(function () {
      speech.classList.remove('show');
    }, 2200);
  }

  piggy.addEventListener('click', function () {
    showPiggySpeech(true);
  });

  if ('IntersectionObserver' in window) {
    var piggyObserver = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          showPiggySpeech(true);
        }
      });
    }, { threshold: 0.5 });

    piggyObserver.observe(piggy);
  }

  var aboutImg = document.getElementById('aboutImg');
  var aboutSpeech = document.getElementById('aboutSpeech');

  if (aboutImg && aboutSpeech) {
    var aboutMessages = [
      "I track every peso I spend!",
      "PESO keeps my budget on point!",
      "No more guessing where my money went!",
      "Small habits, big savings!",
      "PESO makes budgeting easy for students!"
    ];

    var aboutHideTimeout;

    function showAboutSpeech() {
      var message = aboutMessages[Math.floor(Math.random() * aboutMessages.length)];
      aboutSpeech.textContent = message;
      aboutSpeech.classList.add('show');

      clearTimeout(aboutHideTimeout);
      aboutHideTimeout = setTimeout(function () {
        aboutSpeech.classList.remove('show');
      }, 2200);
    }

    aboutImg.addEventListener('click', showAboutSpeech);

    if ('IntersectionObserver' in window) {
      var aboutObserver = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
          if (entry.isIntersecting) {
            showAboutSpeech();
          }
        });
      }, { threshold: 0.5 });

      aboutObserver.observe(aboutImg);
    }
  }
});
