<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($album['title']) ?> — Diaporama</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= asset('css/app.css') ?>">
  <style>
    body { background: #0a0a0f; color: #fff; margin: 0; overflow: hidden; }
    .slideshow-header {
      position: fixed; top: 0; left: 0; right: 0;
      display: flex; justify-content: space-between; align-items: center;
      padding: 16px 24px; background: linear-gradient(to bottom, rgba(0,0,0,0.7), transparent);
      z-index: 100;
    }
    .slideshow-title { font-size: 20px; font-weight: 600; }
    .slideshow-counter { font-size: 14px; opacity: 0.7; }
    .slideshow-stage { position: fixed; inset: 0; display: flex; align-items: center; justify-content: center; }
    .slide { position: absolute; inset: 0; display: flex; align-items: center; justify-content: center;
             opacity: 0; transition: opacity 0.8s ease; pointer-events: none; }
    .slide.active { opacity: 1; pointer-events: auto; }
    .slide img { max-width: 90vw; max-height: 85vh; object-fit: contain; border-radius: 8px;
                 box-shadow: 0 20px 60px rgba(0,0,0,0.5); }
    .slide-caption {
      position: absolute; bottom: 60px; left: 50%; transform: translateX(-50%);
      background: rgba(0,0,0,0.7); backdrop-filter: blur(8px);
      border-radius: 12px; padding: 12px 24px; text-align: center; min-width: 200px;
    }
    .slide-caption .author { font-weight: 600; font-size: 15px; }
    .slide-caption .time { font-size: 12px; opacity: 0.6; margin-top: 2px; }
    .slide-comments { margin-top: 8px; font-size: 13px; opacity: 0.8; }
    .slideshow-controls {
      position: fixed; bottom: 0; left: 0; right: 0;
      display: flex; justify-content: center; align-items: center; gap: 16px;
      padding: 16px; background: linear-gradient(to top, rgba(0,0,0,0.7), transparent);
    }
    .ctrl-btn { background: rgba(255,255,255,0.15); border: none; color: #fff; border-radius: 8px;
                padding: 10px 20px; cursor: pointer; font-size: 18px; transition: background 0.2s; }
    .ctrl-btn:hover { background: rgba(255,255,255,0.3); }
    .new-badge { position: absolute; top: 16px; right: 16px; background: #6366f1;
                 border-radius: 20px; padding: 4px 12px; font-size: 12px; font-weight: 600; animation: pulse 1s ease infinite; }
    @keyframes pulse { 0%,100% { opacity: 1; } 50% { opacity: 0.5; } }
    .slideshow-live { display: flex; align-items: center; gap: 6px; font-size: 13px; }
    .live-dot { width: 8px; height: 8px; background: #ef4444; border-radius: 50%; animation: pulse 1s ease infinite; }
  </style>
</head>
<body>
<div class="slideshow-header">
  <div class="slideshow-title"><?= e($album['title']) ?></div>
  <div class="slideshow-live"><div class="live-dot"></div> Temps réel</div>
  <div class="slideshow-counter" id="counter">— / —</div>
</div>

<div class="slideshow-stage" id="stage"></div>

<div class="slideshow-controls">
  <button class="ctrl-btn" onclick="slideshowNav(-1)">‹</button>
  <button class="ctrl-btn" id="play-btn" onclick="togglePlay()">⏸</button>
  <button class="ctrl-btn" onclick="slideshowNav(1)">›</button>
</div>

<script>
const albumToken = '<?= $album['access_token'] ?>';
let slides = [];
let current = 0;
let playing = true;
let timer = null;
let lastId = 0;
const INTERVAL = 4000;

function fetchPhotos() {
  fetch('/a/' + albumToken + '/feed?since=' + lastId)
    .then(r => r.json())
    .then(data => {
      if (data.photos?.length) {
        data.photos.reverse().forEach(photo => {
          slides.push(photo);
          addSlide(photo, true);
        });
        lastId = data.last_id;
        updateCounter();
      }
    });
}

function addSlide(photo, isNew = false) {
  const stage = document.getElementById('stage');
  const div = document.createElement('div');
  div.className = 'slide';
  div.id = 'slide-' + photo.id;

  let comments = '';
  if (photo.comment_count > 0) {
    comments = `<div class="slide-comments">💬 ${photo.comment_count} commentaire${photo.comment_count > 1 ? 's' : ''}</div>`;
  }

  div.innerHTML = `
    <img src="${photo.url}" alt="" loading="lazy">
    ${isNew ? '<div class="new-badge">✨ Nouveau</div>' : ''}
    <div class="slide-caption">
      <div class="author">📷 ${escHtml(photo.uploader_name)}</div>
      <div class="time">${photo.time_ago}</div>
      ${photo.reaction_count > 0 ? '<div style="margin-top:4px">❤️ ' + photo.reaction_count + '</div>' : ''}
      ${comments}
    </div>
  `;
  stage.appendChild(div);
}

function showSlide(idx) {
  document.querySelectorAll('.slide').forEach(s => s.classList.remove('active'));
  const slide = document.querySelectorAll('.slide')[idx];
  if (slide) slide.classList.add('active');
  current = idx;
  updateCounter();
}

function slideshowNav(dir) {
  const total = document.querySelectorAll('.slide').length;
  if (!total) return;
  showSlide((current + dir + total) % total);
}

function togglePlay() {
  playing = !playing;
  document.getElementById('play-btn').textContent = playing ? '⏸' : '▶';
  if (playing) startTimer(); else clearInterval(timer);
}

function startTimer() {
  clearInterval(timer);
  timer = setInterval(() => {
    const total = document.querySelectorAll('.slide').length;
    if (!total) return;
    showSlide((current + 1) % total);
  }, INTERVAL);
}

function updateCounter() {
  const total = document.querySelectorAll('.slide').length;
  document.getElementById('counter').textContent = total ? (current + 1) + ' / ' + total : '— / —';
}

function escHtml(t) {
  return t.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

// Initial load
fetch('/a/' + albumToken + '/feed?since=0')
  .then(r => r.json())
  .then(data => {
    if (data.photos?.length) {
      data.photos.reverse().forEach(photo => {
        slides.push(photo);
        addSlide(photo);
      });
      lastId = data.last_id;
      showSlide(0);
      startTimer();
    }
  });

// Poll for new photos every 5s
setInterval(fetchPhotos, 5000);

document.addEventListener('keydown', e => {
  if (e.key === 'ArrowLeft') slideshowNav(-1);
  if (e.key === 'ArrowRight') slideshowNav(1);
  if (e.key === ' ') togglePlay();
});
</script>
</body>
</html>
