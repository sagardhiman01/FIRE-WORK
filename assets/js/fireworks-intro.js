/**
 * Cinematic Slow Motion Fireworks Video Intro Engine for Ashish Traders Fireworks
 * Features the user's video clip: 'fireworks-intro.mp4' with synchronized brand reveal.
 */

(function () {
  const overlay = document.getElementById('skyShotIntroOverlay');
  const video = document.getElementById('introVideo');
  const brandReveal = document.getElementById('introBrandReveal');
  const skipBtn = document.getElementById('introSkipBtn');
  const enterBtn = document.getElementById('introEnterBtn');

  if (!overlay) return;

  let introState = 'playing';

  function playIntroSequence() {
    introState = 'playing';
    if (brandReveal) {
      brandReveal.classList.remove('visible-brand');
    }

    if (video) {
      video.currentTime = 0;
      const playPromise = video.play();
      if (playPromise !== undefined) {
        playPromise.catch(() => {
          // Autoplay was prevented; still show reveal
        });
      }
    }

    // Play physical sound accompaniment if unmuted
    if (window.soundEngine && !window.soundEngine.isMuted) {
      window.soundEngine.playRocketWhistle(1.2);
      setTimeout(() => {
        window.soundEngine.playExplosionBoom();
      }, 1300);
    }

    // Reveal brand logo & title as the slow-motion fireworks burst expands
    setTimeout(() => {
      if (brandReveal && introState === 'playing') {
        brandReveal.classList.add('visible-brand');
      }
    }, 1600);
  }

  function dismissIntro() {
    if (introState === 'dismissed') return;
    introState = 'dismissed';
    overlay.classList.add('hidden-intro');

    if (video) {
      video.pause();
    }

    // Enable page scroll
    document.body.style.overflow = 'auto';

    // Start ambient background fireworks
    if (window.initBackgroundFireworks) {
      window.initBackgroundFireworks();
    }
  }

  if (skipBtn) skipBtn.addEventListener('click', dismissIntro);
  if (enterBtn) enterBtn.addEventListener('click', dismissIntro);

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') dismissIntro();
  });

  window.replaySkyShotIntro = function () {
    overlay.classList.remove('hidden-intro');
    document.body.style.overflow = 'hidden';
    playIntroSequence();
  };

  // Start immediately on page load
  document.body.style.overflow = 'hidden';
  playIntroSequence();
})();
