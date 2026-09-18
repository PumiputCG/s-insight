@once
  <style>
    .ot-success-feedback {
      position: fixed;
      inset: 0;
      z-index: 1450;
      display: grid;
      place-items: center;
      padding: 1rem;
      background: rgba(18, 24, 22, .38);
      backdrop-filter: blur(7px);
    }

    .ot-success-feedback[hidden] { display: none; }

    .ot-success-feedback-card {
      width: min(22rem, 100%);
      border: 1px solid var(--line-light);
      border-radius: 8px;
      background: var(--panel);
      color: var(--light-text);
      box-shadow: 0 24px 60px rgba(18, 24, 22, .18);
      padding: 1.55rem 1.4rem 1.35rem;
      text-align: center;
      transform: translateY(.35rem) scale(.98);
      opacity: 0;
    }

    .ot-success-feedback.is-visible .ot-success-feedback-card {
      animation: ot-success-card-in .22s ease-out forwards;
    }

    .ot-success-feedback-icon {
      width: 4.25rem;
      height: 4.25rem;
      margin: 0 auto .85rem;
      color: var(--moss, #35a863);
    }

    .ot-success-feedback-icon svg {
      display: block;
      width: 100%;
      height: 100%;
    }

    .ot-success-feedback-ring,
    .ot-success-feedback-check {
      fill: none;
      stroke: currentColor;
      stroke-linecap: round;
      stroke-linejoin: round;
    }

    .ot-success-feedback-ring {
      stroke-width: 2.2;
      stroke-dasharray: 158;
      stroke-dashoffset: 158;
    }

    .ot-success-feedback-check {
      stroke-width: 3;
      stroke-dasharray: 38;
      stroke-dashoffset: 38;
    }

    .ot-success-feedback.is-visible .ot-success-feedback-ring {
      animation: ot-success-ring .34s ease-out forwards;
    }

    .ot-success-feedback.is-visible .ot-success-feedback-check {
      animation: ot-success-check .24s .18s ease-out forwards;
    }

    .ot-success-feedback-title {
      margin: 0;
      font-size: 1.05rem;
      font-weight: 750;
      letter-spacing: 0;
    }

    .ot-success-feedback-message {
      margin: .38rem 0 1.05rem;
      color: var(--muted-light);
      font-size: .88rem;
      line-height: 1.55;
    }

    .ot-success-feedback-close {
      min-width: 6.25rem;
      border: 1px solid var(--moss, #35a863);
      border-radius: 4px;
      background: var(--moss, #35a863);
      color: #fff;
      cursor: pointer;
      font-size: .82rem;
      font-weight: 700;
      padding: .62rem 1rem;
    }

    .ot-success-feedback-close:focus-visible {
      outline: 2px solid color-mix(in srgb, var(--moss, #35a863) 45%, transparent);
      outline-offset: 3px;
    }

    @keyframes ot-success-card-in {
      to { opacity: 1; transform: translateY(0) scale(1); }
    }

    @keyframes ot-success-ring {
      to { stroke-dashoffset: 0; }
    }

    @keyframes ot-success-check {
      to { stroke-dashoffset: 0; }
    }

    @media (prefers-reduced-motion: reduce) {
      .ot-success-feedback-card,
      .ot-success-feedback-ring,
      .ot-success-feedback-check {
        animation: none !important;
      }

      .ot-success-feedback-card {
        opacity: 1;
        transform: none;
      }

      .ot-success-feedback-ring,
      .ot-success-feedback-check {
        stroke-dashoffset: 0;
      }
    }
  </style>

  <div class="ot-success-feedback" data-ot-success-feedback role="alertdialog" aria-modal="true" aria-labelledby="otSuccessFeedbackTitle" aria-describedby="otSuccessFeedbackMessage" hidden>
    <section class="ot-success-feedback-card" tabindex="-1">
      <div class="ot-success-feedback-icon" aria-hidden="true">
        <svg viewBox="0 0 64 64">
          <circle class="ot-success-feedback-ring" cx="32" cy="32" r="25"></circle>
          <path class="ot-success-feedback-check" d="M20.5 33.5 28 41l16.5-18"></path>
        </svg>
      </div>
      <h2 class="ot-success-feedback-title" id="otSuccessFeedbackTitle" data-ot-success-title>—</h2>
      <p class="ot-success-feedback-message" id="otSuccessFeedbackMessage" data-ot-success-message>—</p>
      <button class="ot-success-feedback-close" type="button" data-ot-success-close data-i18n="leave.ok">ตกลง</button>
    </section>
  </div>

  <script>
    'use strict';

    (function () {
      if (window.otSuccessFeedback) return;

      var modal = document.querySelector('[data-ot-success-feedback]');
      if (!modal) return;

      var card = modal.querySelector('.ot-success-feedback-card');
      var title = modal.querySelector('[data-ot-success-title]');
      var message = modal.querySelector('[data-ot-success-message]');
      var closeButton = modal.querySelector('[data-ot-success-close]');
      var closeTimer = null;
      var onClose = null;
      var previousFocus = null;

      function copy(key, fallback) {
        var lang = document.documentElement.getAttribute('data-lang') || 'th';
        return (window.__portalCopy && window.__portalCopy[lang] && window.__portalCopy[lang][key]) || fallback;
      }

      function close() {
        if (modal.hidden) return;
        if (closeTimer) window.clearTimeout(closeTimer);
        closeTimer = null;
        modal.classList.remove('is-visible');
        modal.hidden = true;
        document.body.style.overflow = '';
        var callback = onClose;
        onClose = null;
        if (callback) callback();
        if (previousFocus && document.contains(previousFocus)) previousFocus.focus();
        previousFocus = null;
      }

      window.otSuccessFeedback = {
        show: function (detail, options) {
          options = options || {};
          if (closeTimer) window.clearTimeout(closeTimer);
          previousFocus = document.activeElement;
          onClose = typeof options.onClose === 'function' ? options.onClose : null;
          title.textContent = options.title || copy('leave.success', 'สำเร็จแล้ว') || 'สำเร็จแล้ว';
          message.textContent = detail || options.message || copy('leave.done', 'ดำเนินการเรียบร้อยแล้ว') || 'ดำเนินการเรียบร้อยแล้ว';
          closeButton.textContent = options.closeText || copy('leave.ok', 'ตกลง') || 'ตกลง';
          modal.hidden = false;
          document.body.style.overflow = 'hidden';
          modal.classList.remove('is-visible');
          void modal.offsetWidth;
          modal.classList.add('is-visible');
          window.requestAnimationFrame(function () { card.focus(); });
          if (options.duration !== 0) {
            closeTimer = window.setTimeout(close, options.duration || 1700);
          }
        },
        close: close
      };

      closeButton.addEventListener('click', close);
      modal.addEventListener('click', function (event) {
        if (event.target === modal) close();
      });
      document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && !modal.hidden) close();
      });
    })();
  </script>
@endonce
