{{--
  กดรูปพนักงานเพื่อดูภาพใหญ่ — ใช้ร่วมทุกหน้าฝั่งการลา

  ผูก listener ที่ document แบบ delegation ไม่ใช่ที่รูปแต่ละใบ
  เพราะตารางถูกวาดใหม่ทุกครั้งที่เปลี่ยนหน้า/แท็บ ถ้าผูกที่รูปจะหลุดทันทีที่ re-render
  ใช้กับ <img data-leave-photo data-caption-name="..." data-caption-code="...">
--}}
@once
  <div class="photo-zoom" data-photo-zoom hidden>
    <button class="photo-zoom-close" type="button" data-photo-zoom-close aria-label="ปิด">×</button>
    <figure>
      <img data-photo-zoom-image src="" alt="">
      <figcaption>
        <strong data-photo-zoom-name></strong>
        <small data-photo-zoom-code></small>
      </figcaption>
    </figure>
  </div>

  <style>
    img[data-leave-photo] { cursor: zoom-in; transition: transform .18s ease; }
    img[data-leave-photo]:hover { transform: scale(1.08); }

    .photo-zoom {
      position: fixed; inset: 0; z-index: 1400;
      display: grid; place-items: center; padding: 1.5rem;
      background: rgb(8 12 10 / 82%); backdrop-filter: blur(6px);
      cursor: zoom-out;
    }
    .photo-zoom[hidden] { display: none; }
    .photo-zoom figure { margin: 0; display: grid; justify-items: center; gap: .8rem; cursor: default; }
    .photo-zoom img {
      max-width: min(30rem, 82vw); max-height: 70vh;
      border-radius: 6px; object-fit: contain;
      box-shadow: 0 24px 70px rgb(0 0 0 / 45%);
      animation: photo-zoom-in .2s ease;
    }
    @keyframes photo-zoom-in { from { opacity: 0; transform: scale(.94); } to { opacity: 1; transform: none; } }
    .photo-zoom figcaption { text-align: center; color: #fff; }
    .photo-zoom figcaption strong { display: block; font-size: .95rem; }
    .photo-zoom figcaption small { display: block; margin-top: .2rem; opacity: .72; font-size: .78rem; }
    .photo-zoom-close {
      position: absolute; top: 1rem; right: 1.2rem;
      width: 2.4rem; height: 2.4rem; border: 0; border-radius: 50%;
      background: rgb(255 255 255 / 14%); color: #fff; font-size: 1.4rem; line-height: 1; cursor: pointer;
    }
    .photo-zoom-close:hover { background: rgb(255 255 255 / 26%); }
    @media (prefers-reduced-motion: reduce) {
      img[data-leave-photo], .photo-zoom img { transition: none; animation: none; }
    }
  </style>

  <script>
    (function photoZoom() {
      'use strict';

      var overlay = document.querySelector('[data-photo-zoom]');
      if (!overlay) return;

      var image = overlay.querySelector('[data-photo-zoom-image]');
      var name = overlay.querySelector('[data-photo-zoom-name]');
      var code = overlay.querySelector('[data-photo-zoom-code]');
      var previousFocus = null;

      function open(target) {
        image.src = target.currentSrc || target.src;
        image.alt = target.alt || '';
        name.textContent = target.dataset.captionName || target.alt || '';
        code.textContent = target.dataset.captionCode || '';
        previousFocus = document.activeElement;
        overlay.hidden = false;
        document.body.style.overflow = 'hidden';
        overlay.querySelector('[data-photo-zoom-close]').focus();
      }

      function close() {
        overlay.hidden = true;
        image.src = '';
        document.body.style.overflow = '';
        if (previousFocus && typeof previousFocus.focus === 'function') previousFocus.focus();
      }

      /* capture = true เพื่อให้ทำงานแม้รูปอยู่ในโมดัลที่ดักคลิกของตัวเองไว้ */
      document.addEventListener('click', function (event) {
        var target = event.target.closest ? event.target.closest('img[data-leave-photo]') : null;
        if (!target) return;
        event.preventDefault();
        event.stopPropagation();
        open(target);
      }, true);

      overlay.addEventListener('click', function (event) {
        if (event.target === overlay || event.target.hasAttribute('data-photo-zoom-close')) close();
      });
      document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && !overlay.hidden) close();
      });
    })();
  </script>
@endonce
