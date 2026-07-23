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
    exit('Não foi possível carregar o site.');
}

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
  const VIDEO_PARTS = Array.from(
    { length: 5 },
    (_, index) => `assets/hero-video-${String(index + 1).padStart(2, '0')}.b64?v=20260723-5`
  );

  let fallbackStarted = false;
  let videoObjectUrl = '';

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

  function markReady() {
    videoFrame?.classList.add('is-ready');
    video.removeAttribute('poster');
    if (videoLoader) videoLoader.setAttribute('aria-hidden', 'true');
  }

  function playVideo() {
    configureVideo();
    const attempt = video.play();
    if (attempt && typeof attempt.catch === 'function') {
      attempt.catch(() => {});
    }
  }

  async function loadLocalVideo() {
    if (fallbackStarted) return;
    fallbackStarted = true;

    if (videoLoader) {
      videoLoader.removeAttribute('aria-hidden');
      videoLoader.innerHTML = '<span>Preparando animação</span>';
    }

    try {
      const chunks = await Promise.all(
        VIDEO_PARTS.map(async (url) => {
          const response = await fetch(url, { cache: 'no-store' });
          if (!response.ok) throw new Error(`Arquivo não encontrado: ${url}`);
          const text = await response.text();
          if (!text.trim()) throw new Error(`Arquivo vazio: ${url}`);
          return text;
        })
      );

      const base64 = chunks.join('').replace(/\s+/g, '');
      const binary = atob(base64);
      const bytes = new Uint8Array(binary.length);

      for (let index = 0; index < binary.length; index += 1) {
        bytes[index] = binary.charCodeAt(index);
      }

      if (videoObjectUrl) URL.revokeObjectURL(videoObjectUrl);
      videoObjectUrl = URL.createObjectURL(new Blob([bytes], { type: 'video/mp4' }));

      video.pause();
      while (video.firstChild) video.removeChild(video.firstChild);
      video.src = videoObjectUrl;
      video.load();
      video.addEventListener('loadeddata', () => { markReady(); playVideo(); }, { once: true });
      video.addEventListener('canplay', () => { markReady(); playVideo(); }, { once: true });
    } catch (error) {
      console.error('Falha ao carregar o vídeo da Hero.', error);
      fallbackStarted = false;
      if (videoLoader) videoLoader.innerHTML = '<span>Carregando animação</span>';
      window.setTimeout(loadLocalVideo, 2500);
    }
  }

  configureVideo();
  video.addEventListener('loadeddata', () => { markReady(); playVideo(); }, { once: true });
  video.addEventListener('canplay', markReady, { once: true });
  video.addEventListener('playing', markReady);
  video.addEventListener('ended', () => { video.currentTime = 0; playVideo(); });
  video.addEventListener('error', loadLocalVideo, { once: true });

  window.setTimeout(() => {
    if (video.readyState < 2) loadLocalVideo();
  }, 850);

  document.addEventListener('visibilitychange', () => {
    if (document.visibilityState === 'visible') playVideo();
  });
  window.addEventListener('pageshow', playVideo);
  ['pointerdown', 'touchstart', 'keydown'].forEach((eventName) => {
    window.addEventListener(eventName, () => {
      if (video.paused) playVideo();
    }, { passive: true });
  });
  playVideo();

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

$patched = str_replace(
    'assets/hero-video.mp4?v=20260723',
    'assets/hero-video.mp4?v=20260723-5',
    $patched
);

header('Content-Type: text/html; charset=UTF-8');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');
echo $patched;
