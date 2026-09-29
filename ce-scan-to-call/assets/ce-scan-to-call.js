/* ==========================================================================
   Camelback East | Scan-to-Call popup
   Desktop visitors who click a tel: link get a QR-code popup instead of a
   dead click. Phones/tablets keep the normal tap-to-call behavior.
   Site-wide: one listener on the document catches every tel: link
   (header, footer, buttons, Kadence blocks, dynamically added links).
   ========================================================================== */
(function () {
  "use strict";

  /* Settings come from Settings > Scan-to-Call (window.CESC_CONFIG). Defaults below are fallbacks. */
  var CONFIG = {
    phoneOverride: "", defaultCountryCode: "1",
    heading: "Scan to call", subheading: "", altText: "QR code that starts a phone call",
    callbackLabel: "Prefer we call you?",
    buttonText: "Schedule a consultation", buttonUrl: "", buttonNewTab: false,
    bg: "#d9c79b", text: "#111111", buttonBg: "#ffffff", buttonTextColor: "#111111",
    overlay: "rgba(0,0,0,.72)",
    fontFamily: "'Playfair Display', Georgia, 'Times New Roman', serif",
    testParam: "cesc_test", dataLayerEvents: true
  };
  var user = window.CESC_CONFIG || {};
  for (var k in user) { if (Object.prototype.hasOwnProperty.call(user, k)) CONFIG[k] = user[k]; }

  var qrcode = window.CESQR;
  if (!qrcode) return;
  var modal, lastFocus, cache = {};

  function isDesktop() {
    if (CONFIG.testParam && new RegExp("[?&]" + CONFIG.testParam + "=1").test(location.search)) return true;
    var ua = navigator.userAgent || "";
    if (/Android|iPhone|iPad|iPod|Mobi|Windows Phone/i.test(ua)) return false;
    if (/Macintosh/.test(ua) && navigator.maxTouchPoints > 1) return false; // iPadOS
    if (window.matchMedia && !window.matchMedia("(hover: hover) and (pointer: fine)").matches) return false;
    return true;
  }

  function normalize(href) {
    var raw = decodeURIComponent(href.replace(/^tel:/i, "")).split(";")[0].split(",")[0];
    var plus = /^\s*\+/.test(raw);
    var d = raw.replace(/\D/g, "");
    if (!d) return null;
    if (!plus && d.length === 10) d = CONFIG.defaultCountryCode + d;
    return { e164: "+" + d, digits: d };
  }

  function pretty(n) {
    var d = n.digits, cc = CONFIG.defaultCountryCode;
    if (cc === "1" && d.length === 11 && d.charAt(0) === "1") {
      return d.slice(1, 4) + "-" + d.slice(4, 7) + "-" + d.slice(7);
    }
    return n.e164;
  }

  function track(name, num) {
    if (!CONFIG.dataLayerEvents) return;
    (window.dataLayer = window.dataLayer || []).push({ event: name, phone_number: num });
  }

  function css() {
    var c = CONFIG;
    return "" +
    ".cesc-ov{position:fixed;inset:0;z-index:2147483000;display:none;align-items:center;justify-content:center;padding:20px;background:" + c.overlay + ";font-family:" + c.fontFamily + ";box-sizing:border-box}" +
    ".cesc-ov.cesc-open{display:flex;animation:cescfade .18s ease-out}" +
    "@keyframes cescfade{from{opacity:0}to{opacity:1}}" +
    ".cesc-ov *{box-sizing:border-box}" +
    ".cesc-card{position:relative;width:100%;max-width:440px;max-height:calc(100vh - 40px);overflow:auto;background:" + c.bg + ";color:" + c.text + ";padding:28px 32px 26px;text-align:center;box-shadow:0 20px 60px rgba(0,0,0,.5)}" +
    ".cesc-x{position:absolute;top:8px;right:10px;width:36px;height:36px;border:0;background:transparent;color:" + c.text + ";font:700 26px/1 Arial,sans-serif;cursor:pointer;padding:0}" +
    ".cesc-x:hover{opacity:.7}" +
    ".cesc-h{margin:6px 0 14px;font:400 clamp(38px,6vw,54px)/1.05 " + c.fontFamily + ";color:" + c.text + ";letter-spacing:0}" +
    ".cesc-sub{margin:-4px 0 14px;font:400 18px/1.35 " + c.fontFamily + "}" +
    ".cesc-qr{background:#fff;border:2px solid #000;padding:22px;display:flex;justify-content:center}" +
    ".cesc-qr svg{display:block;width:100%;max-width:280px;height:auto}" +
    ".cesc-num{display:inline-block;margin:14px 0 4px;font:400 clamp(28px,4.4vw,38px)/1.15 " + c.fontFamily + ";color:" + c.text + ";text-decoration:underline;text-underline-offset:3px}" +
    ".cesc-num:hover{opacity:.75}" +
    ".cesc-cb{margin:2px 0 12px;font:400 clamp(20px,3.2vw,27px)/1.2 " + c.fontFamily + "}" +
    ".cesc-btn{display:block;width:100%;padding:20px 12px;background:" + c.buttonBg + ";color:" + c.buttonTextColor + ";font:400 clamp(18px,2.6vw,23px)/1.2 " + c.fontFamily + ";letter-spacing:.08em;text-decoration:none;border:0}" +
    ".cesc-btn:hover{filter:brightness(.94)}" +
    ".cesc-x:focus-visible,.cesc-btn:focus-visible,.cesc-num:focus-visible{outline:3px solid #000;outline-offset:2px}" +
    "@media (prefers-reduced-motion:reduce){.cesc-ov.cesc-open{animation:none}}";
  }

  function qrSvg(e164) {
    if (cache[e164]) return cache[e164];
    var q = qrcode(0, "M");
    q.addData("tel:" + e164);
    q.make();
    return (cache[e164] = q.createSvgTag({ cellSize: 4, margin: 0, scalable: true }));
  }

  function build() {
    var st = document.createElement("style");
    st.id = "cesc-style";
    st.textContent = css();
    document.head.appendChild(st);

    var ov = document.createElement("div");
    ov.className = "cesc-ov";
    ov.setAttribute("role", "dialog");
    ov.setAttribute("aria-modal", "true");
    ov.setAttribute("aria-labelledby", "cesc-title");

    var hasBtn = !!CONFIG.buttonUrl;
    ov.innerHTML =
      '<div class="cesc-card">' +
        '<button type="button" class="cesc-x" aria-label="Close">&times;</button>' +
        '<p class="cesc-h" id="cesc-title"></p>' +
        (CONFIG.subheading ? '<p class="cesc-sub"></p>' : "") +
        '<div class="cesc-qr" role="img"></div>' +
        '<a class="cesc-num" data-cesc-skip="1" href="#"></a>' +
        (hasBtn ? '<p class="cesc-cb"></p><a class="cesc-btn" href="#"></a>' : "") +
      "</div>";

    ov.querySelector(".cesc-h").textContent = CONFIG.heading;
    if (CONFIG.subheading) ov.querySelector(".cesc-sub").textContent = CONFIG.subheading;
    ov.querySelector(".cesc-qr").setAttribute("aria-label", CONFIG.altText);
    if (hasBtn) {
      ov.querySelector(".cesc-cb").textContent = CONFIG.callbackLabel;
      var b = ov.querySelector(".cesc-btn");
      b.textContent = CONFIG.buttonText;
      b.href = CONFIG.buttonUrl;
      if (CONFIG.buttonNewTab) { b.target = "_blank"; b.rel = "noopener"; }
      b.addEventListener("click", function () { track("scan_to_call_schedule_click", modal.dataset.num); });
    }

    ov.addEventListener("mousedown", function (e) { if (e.target === ov) close(); });
    ov.querySelector(".cesc-x").addEventListener("click", close);
    ov.addEventListener("keydown", function (e) {
      if (e.key === "Escape") { e.preventDefault(); close(); return; }
      if (e.key !== "Tab") return;
      var f = ov.querySelectorAll("button,a[href]");
      if (!f.length) return;
      var first = f[0], last = f[f.length - 1];
      if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
      else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
    });

    document.body.appendChild(ov);
    return ov;
  }

  function open(num) {
    if (!modal) modal = build();
    modal.dataset.num = num.e164;
    modal.querySelector(".cesc-qr").innerHTML = qrSvg(num.e164);
    var a = modal.querySelector(".cesc-num");
    a.textContent = pretty(num);
    a.href = "tel:" + num.e164;
    lastFocus = document.activeElement;
    modal.classList.add("cesc-open");
    document.documentElement.style.overflow = "hidden";
    modal.querySelector(".cesc-x").focus();
    track("scan_to_call_open", num.e164);
  }

  function close() {
    if (!modal) return;
    modal.classList.remove("cesc-open");
    document.documentElement.style.overflow = "";
    if (lastFocus && lastFocus.focus) lastFocus.focus();
    track("scan_to_call_close", modal.dataset.num);
  }

  document.addEventListener("click", function (e) {
    if (e.defaultPrevented || e.button > 0) return;
    var a = e.target.closest && e.target.closest('a[href^="tel:" i], a[href^="TEL:"]');
    if (!a || a.hasAttribute("data-cesc-skip") || a.hasAttribute("data-cesc-native")) return;
    if (!isDesktop()) return;
    var num = CONFIG.phoneOverride ? normalize(CONFIG.phoneOverride) : normalize(a.getAttribute("href"));
    if (!num) return;
    e.preventDefault();
    open(num);
  }, true);
})();
