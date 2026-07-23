<?php
declare(strict_types=1);

$indexPath = __DIR__ . '/index.html';

if (!is_file($indexPath)) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=UTF-8');
    exit('Arquivo index.html não encontrado.');
}

$html = file_get_contents($indexPath);
if ($html === false) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=UTF-8');
    exit('Não foi possível carregar a página.');
}

$html = str_replace('<span class="loop-status">Loop automático</span>', '', $html);

$script = <<<'HTML'
<script>
(() => {
  'use strict';

  const video = document.getElementById('heroVideo');
  const videoFrame = document.getElementById('videoFrame');
  const videoLoader = document.getElementById('videoLoader');
  const form = document.getElementById('leadForm');
  const telefone = document.getElementById('telefone');
  const formMessage = document.getElementById('formMessage');
  const submitButton = document.getElementById('submitButton');
  const GROUP_URL = 'https://chat.whatsapp.com/Jc7FGLbhWA60NdfPItyGz5?mode=gi_t';
  const VERSION = '20260723-hero-visible-4';
  const VIDEO_URL = `assets/hero-video.php?v=${VERSION}`;
  const PARTS = Array.from(
    { length: 10 },
    (_, index) => `assets/hero-upload-${String(index + 1).padStart(2, '0')}.b64`
  );
  const REMOTE_BASES = [
    'https://raw.githubusercontent.com/RuanMarcos38/Zenit-ofertas/main/',
    'https://cdn.jsdelivr.net/gh/RuanMarcos38/Zenit-ofertas@main/'
  ];

  let objectUrl = '';
  let fallbackInProgress = false;
  let retryTimer = 0;

  function configureVideo() {
    video.autoplay = true;
    video.loop = true;
    video.controls = false;
    video.muted = true;
    video.defaultMuted = true;
    video.playsInline = true;
    video.setAttribute('autoplay', '');
    video.setAttribute('loop', '');
    video.setAttribute('muted', '');
    video.setAttribute('playsinline', '');
    video.setAttribute('webkit-playsinline', '');
  }

  function setLoader(message) {
    if (videoLoader) {
      videoLoader.removeAttribute('aria-hidden');
      videoLoader.innerHTML = `<span>${message}</span>`;
    }
  }

  function markReady() {
    videoFrame?.classList.add('is-ready');
    video.removeAttribute('poster');
    if (videoLoader) videoLoader.setAttribute('aria-hidden', 'true');
  }

  function playVideo() {
    configureVideo();
    const attempt = video.play();
    if (attempt && typeof attempt.catch === 'function') attempt.catch(() => {});
  }

  function applyBlob(blob) {
    if (objectUrl) URL.revokeObjectURL(objectUrl);
    objectUrl = URL.createObjectURL(blob);
    video.pause();
    video.removeAttribute('src');
    while (video.firstChild) video.removeChild(video.firstChild);
    video.src = objectUrl;
    video.load();
  }

  async function fetchPart(path) {
    const candidates = [
      `${path}?v=${VERSION}`,
      ...REMOTE_BASES.map((base) => `${base}${path}?v=${VERSION}`)
    ];

    let lastError;
    for (const url of candidates) {
      try {
        const response = await fetch(url, { cache: 'no-store', mode: 'cors' });
        if (!response.ok) throw new Error(`HTTP ${response.status}`);
        const content = await response.text();
        if (!content.trim()) throw new Error('Arquivo vazio');
        return content;
      } catch (error) {
        lastError = error;
      }
    }
    throw lastError || new Error(`Falha ao carregar ${path}`);
  }

  async function loadFallbackVideo() {
    if (fallbackInProgress) return;
    fallbackInProgress = true;
    window.clearTimeout(retryTimer);
    setLoader('Preparando vídeo');

    try {
      const chunks = await Promise.all(PARTS.map(fetchPart));
      const encoded = chunks.join('').replace(/\s+/g, '');
      const binary = atob(encoded);
      const bytes = new Uint8Array(binary.length);

      for (let index = 0; index < binary.length; index += 1) {
        bytes[index] = binary.charCodeAt(index);
      }

      applyBlob(new Blob([bytes], { type: 'video/mp4' }));
    } catch (error) {
      console.error('Falha ao carregar o vídeo da Hero.', error);
      fallbackInProgress = false;
      setLoader('Carregando vídeo');
      retryTimer = window.setTimeout(loadFallbackVideo, 3000);
    }
  }

  function loadPhysicalVideo() {
    configureVideo();
    video.src = VIDEO_URL;
    video.load();
    playVideo();

    window.setTimeout(() => {
      if (video.readyState < 2) loadFallbackVideo();
    }, 1500);
  }

  configureVideo();
  video.addEventListener('loadeddata', () => { markReady(); playVideo(); });
  video.addEventListener('canplay', () => { markReady(); playVideo(); });
  video.addEventListener('playing', markReady);
  video.addEventListener('error', loadFallbackVideo, { once: true });
  video.addEventListener('ended', () => { video.currentTime = 0; playVideo(); });
  video.addEventListener('pause', () => {
    if (document.visibilityState === 'visible' && !video.ended) {
      window.setTimeout(playVideo, 220);
    }
  });
  document.addEventListener('visibilitychange', () => {
    if (document.visibilityState === 'visible') playVideo();
  });
  window.addEventListener('pageshow', playVideo);
  ['pointerdown', 'touchstart', 'keydown'].forEach((eventName) => {
    window.addEventListener(eventName, () => {
      if (video.paused) playVideo();
    }, { passive: true });
  });

  loadPhysicalVideo();

  telefone?.addEventListener('input', () => {
    const digits = telefone.value.replace(/\D/g, '').slice(0, 11);
    if (digits.length > 10) telefone.value = digits.replace(/(\d{2})(\d{5})(\d{4})/, '($1) $2-$3');
    else if (digits.length > 6) telefone.value = digits.replace(/(\d{2})(\d{4})(\d{0,4})/, '($1) $2-$3');
    else if (digits.length > 2) telefone.value = digits.replace(/(\d{2})(\d{0,5})/, '($1) $2');
    else telefone.value = digits;
  });

  form?.addEventListener('submit', (event) => {
    event.preventDefault();
    const nome = document.getElementById('nome').value.trim();
    const email = document.getElementById('email').value.trim();
    const fone = telefone.value.replace(/\D/g, '');

    formMessage.className = 'form-message';
    formMessage.textContent = '';

    if (nome.length < 3) { formMessage.textContent = 'Digite seu nome completo.'; return; }
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) { formMessage.textContent = 'Digite um e-mail válido.'; return; }
    if (fone.length < 10 || fone.length > 11) { formMessage.textContent = 'Digite um telefone válido com DDD.'; return; }

    localStorage.setItem('zenite_lead', JSON.stringify({
      nome,
      email,
      telefone: telefone.value,
      criadoEm: new Date().toISOString()
    }));

    formMessage.className = 'form-message success';
    formMessage.textContent = 'Cadastro validado. Redirecionando para o grupo...';
    submitButton.disabled = true;
    submitButton.textContent = 'Abrindo o WhatsApp...';
    window.setTimeout(() => { window.location.href = GROUP_URL; }, 700);
  });
})();
</script>
HTML;

$pattern = '~<script>.*?</script>(?=\s*</body>)~si';
$patched = preg_replace($pattern, $script, $html, 1);

if (!is_string($patched) || $patched === $html) {
    $patched = str_replace('</body>', $script . "\n</body>", $html);
}

header('Content-Type: text/html; charset=UTF-8');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');
echo $patched;
